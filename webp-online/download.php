<?php
/**
 * Endpoint de Descarga Forzada
 * Sirve archivos con headers que fuerzan descarga en lugar de visualización
 */

require_once __DIR__ . '/../config.php';

// Obtener nombre de archivo
$filename = $_GET['file'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    die('Nombre de archivo no especificado');
}

// Manejar rutas especiales (como ../temp/ para ZIPs)
$filepath = null;

if (strpos($filename, '../temp/') === 0) {
    // Permitir acceso a archivos en temp/ (para ZIPs temporales)
    $tempFilename = str_replace('../temp/', '', $filename);
    $tempFilename = basename($tempFilename); // Sanitizar
    $filepath = TEMP_DIR . $tempFilename;
} else {
    // Sanitizar nombre de archivo para seguridad
    $filename = basename($filename); // Evitar path traversal
    $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);
    
    // Intentar buscar en convert/ primero, luego en upload/
    if (file_exists(CONVERT_DIR . $filename)) {
        $filepath = CONVERT_DIR . $filename;
    } elseif (file_exists(UPLOAD_DIR . $filename)) {
        $filepath = UPLOAD_DIR . $filename;
    }
}

if (!$filepath || !file_exists($filepath)) {
    http_response_code(404);
    die('Archivo no encontrado');
}

// Obtener información del archivo
$filesize = filesize($filepath);
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Forzar descarga usando application/octet-stream
// Esto evita que el navegador intente abrir el archivo automáticamente
$contentType = 'application/octet-stream';

// Log de descarga
logMessage('INFO', 'File downloaded', [
    'filename' => $filename,
    'size' => $filesize,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// Limpiar cualquier salida anterior
if (ob_get_level()) {
    ob_end_clean();
}

// Headers REFORZADOS para forzar descarga SIN abrir
header_remove(); // Eliminar cualquier header previo

// Content-Type genérico para evitar que el navegador lo reconozca
header('Content-Type: application/octet-stream');
header('Content-Description: File Transfer');
header('Content-Transfer-Encoding: binary');

// CRÍTICO: attachment fuerza descarga
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');

// Tamaño del archivo
header('Content-Length: ' . $filesize);

// Prevenir caché
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Seguridad adicional
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');

// Configurar PHP para no añadir headers adicionales
ini_set('zlib.output_compression', 'Off');

// Limpiar buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Desactivar límite de tiempo para archivos grandes
set_time_limit(0);

// Leer y enviar archivo en chunks para evitar problemas de memoria
$handle = fopen($filepath, 'rb');
if ($handle === false) {
    http_response_code(500);
    die('Error al leer el archivo');
}

// Enviar archivo en bloques de 8KB
while (!feof($handle)) {
    echo fread($handle, 8192);
    flush();
}

fclose($handle);
exit;

