<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
if ($clientId <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Se requiere client_id']);
    exit;
}

$pdo = getIntegrationPdo();
$stmt = $pdo->prepare('SELECT * FROM integration_clients WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $clientId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
    exit;
}

$pluginTemplateDir = realpath(__DIR__ . '/../wordpress-plugin/webp-converter-bridge');
if (!$pluginTemplateDir || !is_dir($pluginTemplateDir)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Plantilla de plugin no disponible']);
    exit;
}

$slug = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($client['client_name'] ?? 'cliente'));
$filename = sprintf('webp-converter-bridge-%s.zip', trim($slug, '-_'));

$apiEndpoint = CORE_API_PUBLIC_ENDPOINT;
$token = $client['api_token'] ?? '';

$replacements = [
    '{{API_BASE}}' => $apiEndpoint,
    '{{API_TOKEN}}' => $token
];

$tempZipPath = tempnam(sys_get_temp_dir(), 'wcb_zip_');
if (!$tempZipPath) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No se pudo preparar el archivo temporal']);
    exit;
}

@unlink($tempZipPath);

$zip = new ZipArchive();
if ($zip->open($tempZipPath, ZipArchive::CREATE) !== true) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No se pudo crear el ZIP']);
    exit;
}

$baseLength = strlen($pluginTemplateDir) + 1;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pluginTemplateDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $fileInfo) {
    $path = $fileInfo->getPathname();
    $relativePath = substr($path, $baseLength);
    $targetPath = 'webp-converter-bridge/' . $relativePath;

    if ($fileInfo->isDir()) {
        $zip->addEmptyDir($targetPath);
        continue;
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        continue;
    }

    $processed = str_replace(array_keys($replacements), array_values($replacements), $contents);
    $zip->addFromString($targetPath, $processed);
}

$zip->close();

logIntegrationEvent('INFO', 'Plugin descargado', [
    'client_id' => $clientId,
    'client_name' => $client['client_name'] ?? null,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    'token_suffix' => substr($token, -6)
]);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length', (string)filesize($tempZipPath));
header('Cache-Control: no-store, must-revalidate');
header('Pragma: no-cache');

readfile($tempZipPath);
@unlink($tempZipPath);
exit;

