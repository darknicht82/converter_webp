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
class WebP_Converter_Bridge_Core
{
    private $api_base;
    private $api_token;
    private $settings;

    public function __construct()
    {
        $this->settings = get_option('wcb_settings', []);
        $this->api_base = isset($this->settings['api_base']) ? rtrim($this->settings['api_base'], '/') : '';
        $this->api_token = isset($this->settings['api_token']) ? $this->settings['api_token'] : '';
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
    public function process_attachment_metadata($metadata, $attachment_id)
    {
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

        // 1. Convert the main file
        $this->convert_file($file_path);

        // 2. Convert all generated sizes
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $upload_dir = wp_upload_dir();
            $base_dir = dirname($file_path);

            foreach ($metadata['sizes'] as $size_name => $size_info) {
                $size_file_path = $base_dir . '/' . $size_info['file'];
                $this->convert_file($size_file_path);
            }
        }

        return $metadata;
    }

    /**
     * Manually trigger conversion for an attachment.
     *
     * @param int $attachment_id
     * @return bool
     */
    public function convert_attachment($attachment_id)
    {
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!$metadata) {
            return false;
        }
        
        // Reuse the existing logic
        $this->process_attachment_metadata($metadata, $attachment_id);
        
        return true;
    }

    /**
     * Send a file to the API for conversion and save the result.
     *
     * @param string $file_path
     * @return bool
     */
    private function convert_file($file_path)
    {
        if (!file_exists($file_path)) {
            return false;
        }

        // Check if WebP already exists to avoid re-converting
        $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file_path);
        if (file_exists($webp_path)) {
            return true;
        }

        // Prepare API request
        $url = $this->api_base . '?action=convert'; // Assuming POST to root handles conversion, but let's be safe
        // Actually api.php handles POST to root.

        $boundary = wp_generate_password(24);
        $headers = [
            'X-API-Token' => $this->api_token,
            'content-type' => 'multipart/form-data; boundary=' . $boundary,
        ];

        $payload = '';
        // File field
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="image"; filename="' . basename($file_path) . '"' . "\r\n";
        // Robust MIME type detection for environments without fileinfo extension
        $mime_type = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            $mime_type = mime_content_type($file_path);
        } elseif (function_exists('wp_check_filetype')) {
            $check = wp_check_filetype($file_path);
            if ($check['type']) {
                $mime_type = $check['type'];
            }
        }

        $payload .= 'Content-Type: ' . $mime_type . "\r\n\r\n";
        $payload .= file_get_contents($file_path) . "\r\n";
        
        // Quality field (optional, could be a setting)
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="quality"' . "\r\n\r\n";
        
        $quality = isset($this->settings['webp_quality']) ? (int)$this->settings['webp_quality'] : 80;
        $payload .= $quality . "\r\n";

        $payload .= '--' . $boundary . '--';

        // Prevent local deadlock if sessions are active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        error_log("WCB: Sending request to " . $this->api_base . " for " . basename($file_path));

        $response = wp_remote_post($this->api_base, [
            'headers' => $headers,
            'body' => $payload,
            'timeout' => 60, // Increased timeout for bulk processing
            'sslverify' => false, // Disable SSL verify for local dev environments
        ]);

        error_log("WCB: Request finished. Response code: " . wp_remote_retrieve_response_code($response));

        if (is_wp_error($response)) {
            error_log('WCB Error: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 201 && $code !== 200) {
            error_log('WCB API Error: ' . $code . ' - ' . wp_remote_retrieve_body($response));
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        error_log("WCB: API Response - Code: $code, Body: " . print_r($body, true));
        
        if (!isset($body['success']) || !$body['success'] || !isset($body['data']['url'])) {
            error_log("WCB: Invalid API response format");
            return false;
        }

        // Download the WebP result
        $webp_url = $body['data']['url'];
        $webp_response = wp_remote_get($webp_url);
        
        if (is_wp_error($webp_response)) {
            return false;
        }

        $webp_content = wp_remote_retrieve_body($webp_response);
        $content_type = wp_remote_retrieve_header($webp_response, 'content-type');

        // Security Check 1: Validate Content-Type header
        if ($content_type && strpos($content_type, 'image/webp') === false && strpos($content_type, 'application/octet-stream') === false) {
            error_log('WCB Security: Invalid Content-Type received: ' . $content_type);
            return false;
        }

        // Security Check 2: Validate Magic Bytes (RIFF....WEBP)
        if (substr($webp_content, 0, 4) !== 'RIFF' || substr($webp_content, 8, 4) !== 'WEBP') {
            error_log('WCB Security: Invalid WebP magic bytes.');
            return false;
        }

        // Save locally
        file_put_contents($webp_path, $webp_content);

        return true;
    }

    /**
     * Replace <img> tags with <picture> tags in content.
     *
     * @param string $content
     * @return string
     */
    public function replace_content_images($content)
    {
        if (!is_string($content)) {
            return $content;
        }
        return preg_replace_callback('/<img[^>]+>/i', [$this, 'replace_img_tag'], $content);
    }

    /**
     * Alias for replace_content_images to match filter signature.
     */
    public function replace_html_images($html, $post_id = null, $post_thumbnail_id = null, $size = null, $attr = null)
    {
        return $this->replace_content_images($html);
    }

    /**
     * Callback to replace a single img tag.
     *
     * @param array $matches
     * @return string
     */
    private function replace_img_tag($matches)
    {
        $img_tag = $matches[0];

        // Extract src
        if (!preg_match('/src=["\']([^"\']+)["\']/', $img_tag, $src_match)) {
            return $img_tag;
        }
        $src = $src_match[1];

        // Check if it's a local image (simple check)
        $upload_dir = wp_upload_dir();
        if (strpos($src, $upload_dir['baseurl']) === false) {
            return $img_tag;
        }

        // Check if WebP version exists (by checking extension)
        // In a real scenario, we might want to check file existence, but that's expensive on every page load.
        // We assume if it's local jpg/png, we have the webp.
        if (!preg_match('/\.(jpg|jpeg|png)$/i', $src)) {
            return $img_tag;
        }

        $webp_src = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $src);
        
        // Also handle srcset if present
        $srcset = '';
        if (preg_match('/srcset=["\']([^"\']+)["\']/', $img_tag, $srcset_match)) {
            $original_srcset = $srcset_match[1];
            $webp_srcset = preg_replace('/\.(jpg|jpeg|png)/i', '.webp', $original_srcset);
            $srcset = '<source srcset="' . esc_attr($webp_srcset) . '" type="image/webp">';
        } else {
            $srcset = '<source srcset="' . esc_attr($webp_src) . '" type="image/webp">';
        }

        return '<picture>' . $srcset . $img_tag . '</picture>';
    }
}
