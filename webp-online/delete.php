<?php
/**
 * Eliminar archivos - WebP Converter
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/media-utils.php';

header('Content-Type: application/json');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

// Leer input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['filename']) || !isset($input['type'])) {
    jsonError('Parámetros faltantes', 400);
}

$filename = basename($input['filename']); // Seguridad: solo nombre de archivo
$type = $input['type'];

// Determinar directorio
$dir = ($type === 'convert') ? CONVERT_DIR : UPLOAD_DIR;
$filepath = $dir . $filename;

// Verificar que existe
if (!file_exists($filepath)) {
    jsonError('Archivo no encontrado', 404);
}

// Verificar que está dentro del directorio permitido
$realPath = realpath($filepath);
$realDir = realpath($dir);

if (strpos($realPath, $realDir) !== 0) {
    jsonError('Acceso no autorizado', 403);
}

// Eliminar
if (@unlink($filepath)) {
    $thumbPath = ($type === 'convert') ? getConvertThumbPath($filename) : getUploadThumbPath($filename);
    if (is_file($thumbPath)) {
        @unlink($thumbPath);
    }
    logMessage('INFO', 'File deleted', [
        'filename' => $filename,
        'type' => $type
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Archivo eliminado correctamente'
    ]);
} else {
    jsonError('No se pudo eliminar el archivo', 500);
}

