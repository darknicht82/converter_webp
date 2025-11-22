<?php
/**
 * API para Edición de Imágenes
 * Recibe comandos de edición y procesa
 */

if (!defined('MEDIA_SCOPE')) {
    define('MEDIA_SCOPE', 'webp-online');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/webp-online/image-processor.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['filename'])) {
    jsonError('Falta el nombre del archivo', 400);
}

$filename = basename($input['filename']);
$sourcePath = UPLOAD_DIR . $filename;

if (!file_exists($sourcePath)) {
    jsonError('Archivo no encontrado', 404);
}

$editor = new ImageEditor();

if (!$editor->loadImage($sourcePath)) {
    jsonError('No se pudo cargar la imagen', 500, $editor->getErrors());
}

// Procesar operaciones en orden
$operations = $input['operations'] ?? [];

foreach ($operations as $op) {
    $type = $op['type'] ?? null;
    
    switch ($type) {
        case 'crop':
            $editor->crop($op['x'], $op['y'], $op['width'], $op['height']);
            break;
            
        case 'resize':
            $maintainRatio = $op['maintain_ratio'] ?? true;
            $algorithm = $op['algorithm'] ?? 'bicubic';
            $editor->resize($op['width'], $op['height'], $maintainRatio, $algorithm);
            break;
            
        case 'rotate':
            $editor->rotate($op['angle']);
            break;
            
        case 'flip':
            $mode = $op['direction'] === 'vertical' ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL;
            $editor->flip($mode);
            break;
            
        case 'brightness':
            $editor->brightness($op['value']);
            break;
            
        case 'contrast':
            $editor->contrast($op['value']);
            break;
            
        case 'saturation':
            $editor->saturation($op['value']);
            break;
            
        case 'sharpen':
            $editor->sharpen();
            break;
            
        case 'blur':
            $editor->blur($op['times'] ?? 1);
            break;
            
        case 'grayscale':
            $editor->grayscale();
            break;
            
        case 'sepia':
            $editor->sepia();
            break;
            
        case 'auto_enhance':
            $editor->autoEnhance();
            break;
    }
}

// Guardar resultado
$outputName = $input['output_name'] ?? pathinfo($filename, PATHINFO_FILENAME) . '_edited';
$outputName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $outputName);
$quality = $input['quality'] ?? DEFAULT_QUALITY;
$destinationPath = CONVERT_DIR . $outputName . '.webp';

if ($editor->save($destinationPath, $quality, 'webp')) {
    $fileSize = filesize($destinationPath);
    $originalSize = filesize($sourcePath);
    
    // Generar miniatura para la imagen convertida
    require_once __DIR__ . '/webp-online/media-utils.php';
    $thumbPath = CONVERT_THUMBS_DIR . $outputName . '.jpg';
    if (generateThumbnail($destinationPath, $thumbPath)) {
        logMessage('INFO', 'Thumbnail generated for edited image', [
            'original' => $outputName . '.webp',
            'thumb' => basename($thumbPath)
        ]);
    } else {
        logMessage('WARNING', 'Failed to generate thumbnail for edited image', [
            'original' => $outputName . '.webp',
            'thumb_path' => $thumbPath
        ]);
    }
    
    logMessage('INFO', 'Image edited and converted', [
        'source' => $filename,
        'operations' => count($operations),
        'output' => $outputName . '.webp'
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Imagen editada y convertida',
        'data' => [
            'filename' => $outputName . '.webp',
            'url' => CONVERT_PUBLIC_URL . $outputName . '.webp',
            'size' => $fileSize,
            'original_size' => $originalSize,
            'savings' => round((1 - $fileSize / $originalSize) * 100, 2) . '%',
            'operations_applied' => count($operations)
        ]
    ], 201);
} else {
    jsonError('Error al guardar la imagen editada', 500, $editor->getErrors());
}

$editor->destroy();

