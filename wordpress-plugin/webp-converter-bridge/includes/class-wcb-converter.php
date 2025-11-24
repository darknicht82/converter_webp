<?php
/**
 * Project: WebP Converter Bridge
 * Author: Christian Aguirre
 * Date: 2025-11-21
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core logic for WebP conversion and delivery.
 */
class WebP_Converter_Bridge_Converter
{
    private $api_base;
    private $api_token;
    private $settings;

    public function __construct($api_base = '', $api_token = '', $settings = [])
    {
        $this->settings = !empty($settings) ? $settings : get_option('wcb_settings', []);
        
        // Use passed arguments if available, otherwise fallback to settings
        $this->api_base = !empty($api_base) ? rtrim($api_base, '/') : (isset($this->settings['api_base']) ? rtrim($this->settings['api_base'], '/') : '');
        $this->api_token = !empty($api_token) ? $api_token : (isset($this->settings['api_token']) ? $this->settings['api_token'] : '');
    }

    /**
     * Initialize hooks.
     */
    public function init(): void
    {
        // Hook for new uploads (runs after all sizes are generated)
        add_filter('wp_generate_attachment_metadata', [$this, 'process_attachment_metadata'], 10, 2);

        // Delivery hooks
        if (isset($this->settings['delivery_method']) && $this->settings['delivery_method'] === 'picture') {
            add_filter('the_content', [$this, 'replace_content_images']);
            add_filter('post_thumbnail_html', [$this, 'replace_html_images']);
            add_filter('widget_text', [$this, 'replace_content_images']);
        }
    }

    /**
     * Process attachment metadata to convert images to WebP.
     *
     * @param array $metadata
     * @param int $attachment_id
     * @return array
     */
    /**
     * Static array to prevent recursion across different instances
     */
    private static $is_processing = [];

    /**
     * Process attachment metadata to convert images to WebP.
     *
     * @param array $metadata
     * @param int $attachment_id
     * @return array
     */
    public function process_attachment_metadata($metadata, $attachment_id)
    {
        // Prevent infinite recursion (check static property)
        if (isset(self::$is_processing[$attachment_id])) {
            return $metadata;
        }

        if (empty($this->api_base) || empty($this->api_token)) {
            return $metadata;
        }

        // Verify it's an image
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return $metadata;
        }

        $mime_type = get_post_mime_type($attachment_id);
        if (!in_array($mime_type, ['image/jpeg', 'image/png'])) {
            return $metadata;
        }

        // Mark as processing
        self::$is_processing[$attachment_id] = true;

        try {
            // 1. Convert and Replace the main file
            // Passing attachment_id triggers the replacement logic
            $this->convert_file($file_path, $attachment_id);

            // Since we replaced the file, return the new metadata
            $new_metadata = wp_get_attachment_metadata($attachment_id);
            
            // Cleanup
            unset(self::$is_processing[$attachment_id]);
            
            return $new_metadata;
        } catch (Exception $e) {
            // Ensure we clean up even on error
            unset(self::$is_processing[$attachment_id]);
            error_log('WCB Error in process_attachment_metadata: ' . $e->getMessage());
            return $metadata;
        }
    }

    /**
     * Public wrapper for bulk conversion.
     *
     * @param int $attachment_id
     * @return bool
     */
    public function convert_attachment($attachment_id): bool
    {
        $metadata = wp_get_attachment_metadata($attachment_id);
        $new_metadata = $this->process_attachment_metadata($metadata, $attachment_id);
        return !empty($new_metadata);
    }

    /**
     * Convert a file to WebP and replace the attachment.
     *
     * @param string $file_path Absolute path to source file
     * @param int $attachment_id Attachment ID to update
     * @throws Exception
     */
    private function convert_file(string $file_path, int $attachment_id): void
    {
        error_log("WCB Debug: Starting convert_file for ID $attachment_id");

        // 1. Prepare API request
        $boundary = wp_generate_password(24);
        $headers = [
            'X-API-Token' => $this->api_token,
            'content-type' => 'multipart/form-data; boundary=' . $boundary
        ];

        error_log("WCB Debug: Reading file $file_path");
        if (!file_exists($file_path)) {
            throw new Exception("File not found: $file_path");
        }

        $payload = '';
        // File field
        $payload .= "--" . $boundary . "\r\n";
        $payload .= "Content-Disposition: form-data; name=\"image\"; filename=\"" . basename($file_path) . "\"\r\n";
        
        // Use WordPress function to get mime type (safer than mime_content_type which requires fileinfo extension)
        $mime_type = get_post_mime_type($attachment_id);
        if (!$mime_type) {
            $mime_type = 'application/octet-stream';
        }
        
        $payload .= "Content-Type: " . $mime_type . "\r\n\r\n";
        $payload .= file_get_contents($file_path) . "\r\n";
        
        // Quality field
        $quality = isset($this->settings['webp_quality']) ? $this->settings['webp_quality'] : '85';
        $payload .= "--" . $boundary . "\r\n";
        $payload .= "Content-Disposition: form-data; name=\"quality\"\r\n\r\n";
        $payload .= $quality . "\r\n";

        $payload .= "--" . $boundary . "--\r\n";

        error_log("WCB Debug: Sending API request to " . $this->api_base);

        // 2. Send to API
        $response = wp_remote_post($this->api_base . '?action=upload', [
            'headers' => $headers,
            'body' => $payload,
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            throw new Exception('API Error: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log("WCB Debug: API Response Code: $code");
        error_log("WCB Debug: API Response Body: " . substr($body, 0, 500)); // Log first 500 chars

        $data = json_decode($body, true);

        if (!in_array($code, [200, 201]) || empty($data['success'])) {
            throw new Exception('API Failed: ' . ($data['error'] ?? 'Unknown error (Code: ' . $code . ')'));
        }

        error_log("WCB Debug: API success, downloading WebP");

        // 3. Download WebP
        // API returns data in 'data' key
        $webp_url = $data['data']['url'] ?? $data['url'] ?? '';
        
        if (empty($webp_url)) {
            throw new Exception('API response missing URL');
        }
        $webp_content = file_get_contents($webp_url);
        if (!$webp_content) {
            throw new Exception('Failed to download converted WebP');
        }

        // 4. Backup & Replace Strategy
        $path_info = pathinfo($file_path);
        $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        
        // Backup original if not already backed up
        $backup_path = $file_path . '.original';
        if (!file_exists($backup_path)) {
            error_log("WCB Debug: Creating backup at $backup_path");
            copy($file_path, $backup_path);
            update_post_meta($attachment_id, '_wcb_original_file', $backup_path);
        }

        // Save WebP
        error_log("WCB Debug: Saving WebP to $webp_path");
        if (file_put_contents($webp_path, $webp_content) === false) {
            throw new Exception('Failed to save WebP file locally');
        }

        // 5. Update Attachment (Preserve Metadata)
        error_log("WCB Debug: Updating attachment metadata for ID $attachment_id");
        
        // Update file path to point to WebP
        update_attached_file($attachment_id, $webp_path);
        
        // Update mime type
        // Update mime type directly to avoid hooks/recursion
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            ['post_mime_type' => 'image/webp'],
            ['ID' => $attachment_id],
            ['%s'],
            ['%d']
        );
        
        // Clear post cache to ensure WP sees the change
        clean_post_cache($attachment_id);

        // 6. Log Conversion
        error_log("WCB Debug: Logging conversion to API");
        $this->logConversion(
            basename($file_path),
            filesize($file_path),
            filesize($webp_path)
        );
        
        error_log("WCB Debug: Finished convert_file for ID $attachment_id");
    }

    /**
     * Log conversion to API.
     */
    private function logConversion($filename, $origSize, $webpSize): void
    {
        wp_remote_post($this->api_base . '?action=log_conversion', [
            'headers' => [
                'X-API-Token' => $this->api_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'source_filename' => $filename,
                'source_bytes' => $origSize,
                'converted_bytes' => $webpSize
            ]),
            'blocking' => false // Async logging
        ]);
    }

    /**
     * Placeholder for content replacement (not implemented in this sprint)
     */
    public function replace_content_images($content) { return $content; }
    public function replace_html_images($html) { return $html; }

}

