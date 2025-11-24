<?php
/**
 * WordPress Media Upload Helper
 * 
 * Funciones auxiliares para subir archivos WebP convertidos a la librería de medios de WordPress.
 * Puede ser usado tanto desde la API como desde el plugin.
 */

if (!function_exists('uploadWebpToWordPressMedia')) {
    /**
     * Sube un archivo WebP a la librería de medios de WordPress.
     * Si se proporciona un attachment ID, sobrescribe ese attachment.
     *
     * @param string $filePath Ruta absoluta del archivo .webp convertido.
     * @param string $originalFilename Nombre original del archivo (sin extensión).
     * @param int $attachmentId ID del attachment a sobrescribir (0 = crear nuevo).
     * @return array{success: bool, attachment_id: int|null, url: string|null, error: string|null}
     */
    function uploadWebpToWordPressMedia(string $filePath, string $originalFilename, int $attachmentId = 0): array
    {
        // Verificar que WordPress esté disponible
        if (!function_exists('wp_upload_dir')) {
            return [
                'success' => false,
                'attachment_id' => null,
                'url' => null,
                'error' => 'WordPress no está disponible'
            ];
        }

        // Verificar que el archivo existe
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'attachment_id' => null,
                'url' => null,
                'error' => 'El archivo no existe: ' . $filePath
            ];
        }

        // Cargar funciones de medios si no están disponibles
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $fileArray = [
            'name'     => $originalFilename . '.webp',
            'type'     => 'image/webp',
            'tmp_name' => $filePath,
            'error'    => 0,
            'size'     => filesize($filePath),
        ];

        // Si $attachmentId > 0, WordPress sobrescribirá ese attachment
        $newId = media_handle_sideload($fileArray, $attachmentId);
        
        if (is_wp_error($newId)) {
            return [
                'success' => false,
                'attachment_id' => null,
                'url' => null,
                'error' => $newId->get_error_message()
            ];
        }

        // Regenerar tamaños y actualizar metadatos
        $metadata = wp_generate_attachment_metadata($newId, get_attached_file($newId));
        wp_update_attachment_metadata($newId, $metadata);

        // Asegurar que el mime-type sea correcto
        wp_update_post([
            'ID'             => $newId,
            'post_mime_type' => 'image/webp',
        ]);

        // Obtener la URL del attachment
        $url = wp_get_attachment_url($newId);

        return [
            'success' => true,
            'attachment_id' => $newId,
            'url' => $url,
            'error' => null
        ];
    }
}

if (!function_exists('getAttachmentIdFromPath')) {
    /**
     * Obtiene el ID de un attachment a partir de su ruta de archivo.
     *
     * @param string $filePath Ruta completa del archivo.
     * @return int|null ID del attachment o null si no se encuentra.
     */
    function getAttachmentIdFromPath(string $filePath): ?int
    {
        if (!function_exists('attachment_url_to_postid')) {
            return null;
        }

        global $wpdb;
        
        $file = basename($filePath);
        $query = $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
            '%' . $wpdb->esc_like($file)
        );
        
        $attachmentId = $wpdb->get_var($query);
        
        return $attachmentId ? (int)$attachmentId : null;
    }
}

if (!function_exists('getAttachmentIdFromUrl')) {
    /**
     * Obtiene el ID de un attachment a partir de su URL.
     *
     * @param string $url URL del archivo.
     * @return int|null ID del attachment o null si no se encuentra.
     */
    function getAttachmentIdFromUrl(string $url): ?int
    {
        if (!function_exists('attachment_url_to_postid')) {
            return null;
        }

        $attachmentId = attachment_url_to_postid($url);
        return $attachmentId > 0 ? $attachmentId : null;
    }
}
