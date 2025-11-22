<?php
/**
 * API de Exportación para Social Media Designer
 * Recibe canvas en base64 y lo guarda como WebP optimizado
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Log de debug
error_log("Social Export - Raw input length: " . strlen($rawInput));
error_log("Social Export - JSON decode result: " . ($input ? 'OK' : 'FAILED'));

if (!$input) {
    error_log("Social Export - JSON Error: " . json_last_error_msg());
    jsonError('Error al decodificar JSON: ' . json_last_error_msg(), 400);
}

if (!isset($input['image_data'])) {
    error_log("Social Export - Missing image_data. Keys: " . implode(', ', array_keys($input)));
    jsonError('Faltan datos de imagen', 400);
}

$imageData = $input['image_data'];
$filename = $input['filename'] ?? 'social-design';
$quality = $input['quality'] ?? 85;
$format = $input['format'] ?? 'webp';
$template = $input['template'] ?? 'Diseño Social';

// Limpiar nombre de archivo
$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename);

// Decodificar base64
if (strpos($imageData, 'data:') === 0) {
    $imageData = substr($imageData, strpos($imageData, ',') + 1);
}

$imageData = base64_decode($imageData);

if ($imageData === false) {
    jsonError('Datos de imagen inválidos', 400);
}

// Guardar en temporal
$tempFile = TEMP_DIR . 'social_' . uniqid() . '.tmp';
file_put_contents($tempFile, $imageData);

// Crear recurso GD
$img = null;
$imgInfo = @getimagesize($tempFile);

if (!$imgInfo) {
    @unlink($tempFile);
    jsonError('No se pudo procesar la imagen', 500);
}

// Cargar según tipo
switch($imgInfo[2]) {
    case IMAGETYPE_PNG:
        $img = @imagecreatefrompng($tempFile);
        break;
    case IMAGETYPE_JPEG:
        $img = @imagecreatefromjpeg($tempFile);
        break;
    default:
        @unlink($tempFile);
        jsonError('Tipo de imagen no soportado', 400);
}

if (!$img) {
    @unlink($tempFile);
    jsonError('Error al crear recurso de imagen', 500);
}

// Preparar para transparencia (si es PNG)
if ($imgInfo[2] === IMAGETYPE_PNG) {
    imagealphablending($img, false);
    imagesavealpha($img, true);
}

// Determinar ruta de salida
$extension = $format === 'jpg' ? 'jpg' : $format;
$outputFilename = $filename . '.' . $extension;
$outputPath = CONVERT_DIR . $outputFilename;

// Guardar según formato
$success = false;

switch($format) {
    case 'webp':
        $success = imagewebp($img, $outputPath, $quality);
        break;
    case 'png':
        $pngQuality = (int)(($quality / 100) * 9);
        $success = imagepng($img, $outputPath, 9 - $pngQuality);
        break;
    case 'jpg':
        $success = imagejpeg($img, $outputPath, $quality);
        break;
}

// Liberar recursos
imagedestroy($img);
@unlink($tempFile);

if (!$success) {
    jsonError('Error al guardar la imagen', 500);
}

// Log
logMessage('INFO', 'Social design exported', [
    'filename' => $outputFilename,
    'template' => $template,
    'format' => $format,
    'quality' => $quality,
    'size' => filesize($outputPath)
]);

// Respuesta exitosa
jsonResponse([
    'success' => true,
    'message' => 'Diseño exportado exitosamente',
    'filename' => $outputFilename,
    'size' => filesize($outputPath),
    'download_url' => rtrim(BASE_URL, '/') . '/webp-online/download.php?file=' . urlencode($outputFilename),
    'preview_url' => CONVERT_PUBLIC_URL . $outputFilename,
    'template' => $template,
    'format' => $format
], 201);

