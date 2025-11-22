<?php
/**
 * Handler de subida de archivos (Drag & Drop)
 * Devuelve respuestas JSON consumidas por js/upload.js y js/app.js
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/media-utils.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Archivo no recibido'
    ]);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMap = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño permitido por el servidor',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño permitido por el formulario',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente',
        UPLOAD_ERR_NO_FILE    => 'No se envió ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'No existe el directorio temporal en el servidor',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco',
        UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida'
    ];

    $message = $errorMap[$file['error']] ?? 'Error desconocido al subir el archivo';
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
    echo json_encode([
        'success' => false,
        'error' => 'Extensión no permitida'
    ]);
    exit;
}

if ($file['size'] > MAX_FILE_SIZE) {
    echo json_encode([
        'success' => false,
        'error' => 'El archivo excede el tamaño máximo: ' . (MAX_FILE_SIZE / (1024 * 1024)) . ' MB'
    ]);
    exit;
}

if (!is_dir(UPLOAD_DIR)) {
    if (!@mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
        logMessage('ERROR', 'No se puede crear el directorio de subida', ['dir' => UPLOAD_DIR]);
        echo json_encode([
            'success' => false,
            'error' => 'No se puede preparar el directorio de subida'
        ]);
        exit;
    }
}

$baseName = pathinfo($file['name'], PATHINFO_FILENAME);
$sanitized = preg_replace('/[^A-Za-z0-9_\-]/', '_', $baseName);
if ($sanitized === '') {
    $sanitized = 'upload_' . date('Ymd_His');
}

$targetName = $sanitized . '.' . $extension;
$targetPath = UPLOAD_DIR . $targetName;
$counter = 1;
while (file_exists($targetPath)) {
    $targetName = $sanitized . '_' . $counter . '.' . $extension;
    $targetPath = UPLOAD_DIR . $targetName;
    $counter++;
}

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    logMessage('ERROR', 'No se pudo mover el archivo subido', ['tmp' => $file['tmp_name'], 'target' => $targetPath]);
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo almacenar el archivo'
    ]);
    exit;
}

@chmod($targetPath, 0644);

$thumbPath = getUploadThumbPath($targetName);
$thumbGenerated = generateThumbnail($targetPath, $thumbPath);
if (!$thumbGenerated) {
    logMessage('WARNING', 'No se pudo generar miniatura para upload', [
        'filename' => $targetName,
        'thumb_path' => $thumbPath
    ]);
}

logMessage('INFO', 'Archivo subido', [
    'filename' => basename($targetPath),
    'size' => $file['size']
]);

echo json_encode([
    'success' => true,
    'filename' => basename($targetPath),
    'url' => UPLOAD_PUBLIC_URL . basename($targetPath),
    'thumb_url' => $thumbGenerated ? getUploadThumbPublicUrl($targetName) : null,
    'size' => $file['size']
]);

