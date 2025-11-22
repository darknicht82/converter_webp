<?php
/**
 * Descargar todas las imágenes WebP en un archivo ZIP
 */

require_once __DIR__ . '/../config.php';

// Verificar que la extensión ZIP está disponible
if (!class_exists('ZipArchive')) {
    die('Error: La extensión ZIP no está disponible en PHP');
}

$convertDir = CONVERT_DIR;
$files = @scandir($convertDir);

if ($files === false) {
    die('Error: No se puede leer el directorio');
}

// Filtrar solo archivos WebP
$webpFiles = array_filter($files, function($file) use ($convertDir) {
    return preg_match('/\.webp$/i', $file) && is_file($convertDir . $file);
});

if (empty($webpFiles)) {
    die('No hay archivos WebP para descargar');
}

// Crear archivo ZIP temporal
$zipFilename = 'webp-images-' . date('Y-m-d-His') . '.zip';
$zipPath = TEMP_DIR . $zipFilename;

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die('Error: No se pudo crear el archivo ZIP');
}

// Agregar archivos al ZIP
foreach ($webpFiles as $file) {
    $filePath = $convertDir . $file;
    $zip->addFile($filePath, $file);
}

$zip->close();

// Enviar archivo al navegador
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
header('Content-Length: ' . filesize($zipPath));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

readfile($zipPath);

// Eliminar archivo temporal después de un momento
register_shutdown_function(function() use ($zipPath) {
    if (file_exists($zipPath)) {
        @unlink($zipPath);
    }
});

logMessage('INFO', 'ZIP download', [
    'files_count' => count($webpFiles),
    'zip_size' => filesize($zipPath)
]);

exit;

