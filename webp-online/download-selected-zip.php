<?php
/**
 * Descargar imágenes WebP seleccionadas en un archivo ZIP
 */

require_once __DIR__ . '/../config.php';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Método no permitido']));
}

// Verificar que la extensión ZIP está disponible
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Extensión ZIP no disponible']));
}

// Obtener archivos seleccionados
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['files']) || !is_array($data['files']) || empty($data['files'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'No se especificaron archivos']));
}

$selectedFiles = $data['files'];
$convertDir = CONVERT_DIR;
$validFiles = [];

// Validar que los archivos existen
foreach ($selectedFiles as $filename) {
    // Sanitizar nombre de archivo
    $filename = basename($filename);
    $filepath = $convertDir . $filename;
    
    // Verificar que existe y es WebP
    if (file_exists($filepath) && preg_match('/\.webp$/i', $filename)) {
        $validFiles[] = $filename;
    }
}

if (empty($validFiles)) {
    http_response_code(404);
    die(json_encode(['success' => false, 'error' => 'No se encontraron archivos válidos']));
}

// Crear archivo ZIP temporal
$zipFilename = 'webp-selected-' . date('Y-m-d-His') . '.zip';
$zipPath = TEMP_DIR . $zipFilename;

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'No se pudo crear el archivo ZIP']));
}

// Agregar archivos al ZIP
foreach ($validFiles as $file) {
    $filePath = $convertDir . $file;
    $zip->addFile($filePath, $file);
}

$zip->close();

// Registrar en log
logMessage('INFO', 'Selected ZIP download', [
    'files_count' => count($validFiles),
    'zip_size' => filesize($zipPath),
    'files' => implode(', ', $validFiles)
]);

// Devolver información del archivo
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'zip_filename' => $zipFilename,
    'files_count' => count($validFiles),
    'zip_size' => filesize($zipPath),
    'download_url' => 'download.php?file=' . urlencode('../temp/' . $zipFilename)
]);

exit;




