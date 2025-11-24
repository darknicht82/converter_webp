<?php
/**
 * Configuración Global - WebP Converter
 * Auto-detecta si está corriendo en MAMP o Docker
 */

// Detectar entorno
define('IS_DOCKER', getenv('DOCKER_ENV') !== false || file_exists('/.dockerenv'));
define('IS_CLI', php_sapi_name() === 'cli');

// Configuración de rutas según entorno
if (IS_DOCKER) {
    define('BASE_DIR', '/var/www/html/');
    $defaultHost = 'localhost';
    $defaultPort = getenv('WEBP_HOST_PORT') ?: '8080';
    $defaultBasePath = '/webp';
} else {
    define('BASE_DIR', __DIR__ . '/');
    $defaultHost = 'localhost';
    $defaultPort = null;
    $defaultBasePath = '/webp';
}

$envBaseUrl = getenv('BASE_URL');
if ($envBaseUrl) {
    define('BASE_URL', rtrim($envBaseUrl, '/'));
} else {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == '443';
    $scheme = $isSecure ? 'https' : 'http';
    $hostHeader = $_SERVER['HTTP_HOST'] ?? null;
    
    if ($hostHeader) {
        $host = $hostHeader;
    } else {
        $host = $defaultHost;
        if ($defaultPort) {
            $host .= ':' . $defaultPort;
        }
    }
    
    $scriptDir = '';
    if (!IS_CLI && isset($_SERVER['SCRIPT_NAME'])) {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $scriptDir = rtrim($scriptDir, '/');
        if ($scriptDir === '.') $scriptDir = '';
        if ($scriptDir !== '') {
            $scriptDir = preg_replace('#/(webp-online|social-designer|webp-wordpress)$#', '', $scriptDir);
        }
    }
    
    if ($scriptDir === '' && $defaultBasePath) {
        $scriptDir = $defaultBasePath;
    }
    
    define('BASE_URL', rtrim($scheme . '://' . $host . $scriptDir, '/'));
}

$coreApiPublic = getenv('CORE_API_PUBLIC_URL') ?: BASE_URL;
$coreApiInternal = getenv('CORE_API_INTERNAL_URL');
if (!$coreApiInternal) {
    $coreApiInternal = IS_DOCKER ? 'http://webp-core' : BASE_URL;
}
define('CORE_API_PUBLIC_URL', rtrim($coreApiPublic, '/'));
define('CORE_API_INTERNAL_URL', rtrim($coreApiInternal, '/'));
define('CORE_API_PUBLIC_ENDPOINT', CORE_API_PUBLIC_URL . '/api.php');
define('CORE_API_INTERNAL_ENDPOINT', CORE_API_INTERNAL_URL . '/api.php');

$authPublicBase = getenv('AUTH_PUBLIC_URL') ?: CORE_API_PUBLIC_URL;
$authInternalBase = getenv('AUTH_INTERNAL_URL') ?: CORE_API_INTERNAL_URL;
define('AUTH_PUBLIC_ENDPOINT', rtrim($authPublicBase, '/') . '/auth.php');
define('AUTH_INTERNAL_ENDPOINT', rtrim($authInternalBase, '/') . '/auth.php');

// Detectar alcance de medios según el microservicio actual
$mediaScope = getenv('MEDIA_SCOPE') ?: (defined('MEDIA_SCOPE') ? MEDIA_SCOPE : null);
if (!$mediaScope && isset($_SERVER['SCRIPT_FILENAME'])) {
    $scriptPath = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']) ?: $_SERVER['SCRIPT_FILENAME']);
    foreach (['webp-online', 'webp-wordpress', 'social-designer'] as $candidate) {
        if (strpos($scriptPath, '/' . $candidate . '/') !== false) {
            $mediaScope = $candidate;
            break;
        }
    }
}
$mediaScope = $mediaScope ?: 'core';
if (!defined('MEDIA_SCOPE')) {
    define('MEDIA_SCOPE', $mediaScope);
} else {
    $mediaScope = MEDIA_SCOPE;
}

$mediaBaseMap = [
    'webp-online' => 'webp-online/media/',
    'webp-wordpress' => 'webp-wordpress/media/',
    'social-designer' => 'social-designer/media/',
    'core' => 'media/'
];
$mediaBaseRelative = $mediaBaseMap[$mediaScope] ?? $mediaBaseMap['core'];
define('MEDIA_BASE_RELATIVE', $mediaBaseRelative);

define('MEDIA_DIR', BASE_DIR . MEDIA_BASE_RELATIVE);
define('UPLOAD_DIR', MEDIA_DIR . 'upload/');
define('CONVERT_DIR', MEDIA_DIR . 'convert/');
define('LOGS_DIR', MEDIA_DIR . 'logs/');
define('TEMP_DIR', MEDIA_DIR . 'temp/');
define('DATABASE_DIR', BASE_DIR . 'database/');

define('UPLOAD_URL_PATH', MEDIA_BASE_RELATIVE . 'upload/');
define('CONVERT_URL_PATH', MEDIA_BASE_RELATIVE . 'convert/');
define('TEMP_URL_PATH', MEDIA_BASE_RELATIVE . 'temp/');
define('LOGS_URL_PATH', MEDIA_BASE_RELATIVE . 'logs/');
define('UPLOAD_PUBLIC_URL', rtrim(BASE_URL, '/') . '/' . UPLOAD_URL_PATH);
define('CONVERT_PUBLIC_URL', rtrim(BASE_URL, '/') . '/' . CONVERT_URL_PATH);
define('TEMP_PUBLIC_URL', rtrim(BASE_URL, '/') . '/' . TEMP_URL_PATH);

define('THUMB_EXTENSION', 'jpg');
define('UPLOAD_THUMBS_DIR', UPLOAD_DIR . 'thumbs/');
define('CONVERT_THUMBS_DIR', CONVERT_DIR . 'thumbs/');
define('UPLOAD_THUMBS_URL_PATH', UPLOAD_URL_PATH . 'thumbs/');
define('CONVERT_THUMBS_URL_PATH', CONVERT_URL_PATH . 'thumbs/');
define('UPLOAD_THUMBS_PUBLIC_URL', rtrim(BASE_URL, '/') . '/' . UPLOAD_THUMBS_URL_PATH);
define('CONVERT_THUMBS_PUBLIC_URL', rtrim(BASE_URL, '/') . '/' . CONVERT_THUMBS_URL_PATH);

define('INTEGRATION_DB_PATH', DATABASE_DIR . 'webp_integration.sqlite');

// Configuración de Base de Datos (PostgreSQL)
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'pgsql'); // sqlite o pgsql
define('DB_HOST', getenv('DB_HOST') ?: 'postgres');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'webp_integration');
define('DB_USER', getenv('DB_USER') ?: 'webp_user');
define('DB_PASS', getenv('DB_PASS') ?: 'webp_password');

define('API_TOKEN_HEADER', 'X-API-Token');
// Token de API Global (Opcional, para administración)
// Si se deja null, solo se permitirán tokens de clientes en base de datos
define('API_TOKEN', getenv('API_TOKEN') ?: null);

// CSRF Protection
define('CSRF_TOKEN_NAME', 'csrf_token');

// Asset versioning for cache busting
define('ASSET_VERSION', '2.0.0');

// Configuración de conversión
define('DEFAULT_QUALITY', 80);
define('MIN_QUALITY', 0);
define('MAX_QUALITY', 100);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('MAX_DIMENSION', 10000); // píxeles

define('MEMORY_LIMIT', '512M');
define('MAX_EXECUTION_TIME', 300);
define('CLEANUP_TEMP_AFTER', 3600); // 1 hora

// Aplicar configuraciones PHP
ini_set('memory_limit', MEMORY_LIMIT);
set_time_limit(MAX_EXECUTION_TIME);

// Crear directorios si no existen
$directories = [
    MEDIA_DIR,
    UPLOAD_DIR,
    CONVERT_DIR,
    LOGS_DIR,
    TEMP_DIR,
    DATABASE_DIR,
    UPLOAD_THUMBS_DIR,
    CONVERT_THUMBS_DIR
];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Función helper para logging
function logMessage($level, $message, $context = []) {
    if (!defined('ENABLE_LOGGING') || !ENABLE_LOGGING) return;
    
    $logFile = LOGS_DIR . 'app-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logLine = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";
    
    @file_put_contents($logFile, $logLine, FILE_APPEND);
}

function logIntegrationEvent($level, $message, $context = []) {
    if (!defined('ENABLE_LOGGING') || !ENABLE_LOGGING) return;

    $logFile = LOGS_DIR . 'wp-integration-' . date('Y-m-d') . '.log';
    $entry = [
        'timestamp' => date('c'),
        'level' => $level,
        'message' => $message,
        'context' => $context
    ];

    @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
}

// Función helper para respuestas JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Función helper para errores JSON
function jsonError($message, $status = 400, $details = []) {
    jsonResponse([
        'success' => false,
        'error' => $message,
        'details' => $details
    ], $status);
}

// Inicializar base de datos de integración (WordPress)
require_once __DIR__ . '/lib/integration-db.php';
require_once __DIR__ . '/lib/wp-media-helper.php';
try {
    initializeIntegrationDatabase();
    ensureIntegrationTriggers();
} catch (RuntimeException $integrationException) {
    logIntegrationEvent('ERROR', 'Fallo durante la inicialización de la base de integración', [
        'error' => $integrationException->getMessage()
    ]);
}

// Auto-cleanup de archivos temporales antiguos
function cleanupOldTempFiles() {
    if (!is_dir(TEMP_DIR)) return;
    
    $files = glob(TEMP_DIR . '*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= CLEANUP_TEMP_AFTER) {
                @unlink($file);
            }
        }
    }
}

// Ejecutar cleanup si no es CLI
if (!IS_CLI && rand(1, 100) === 1) {
    cleanupOldTempFiles();
}

if (defined('ENABLE_LOGGING') && ENABLE_LOGGING) {
    logMessage('INFO', 'Config loaded', [
        'environment' => IS_DOCKER ? 'Docker' : 'MAMP',
        'base_dir' => BASE_DIR
    ]);
}
