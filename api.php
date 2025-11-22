<?php
/**
 * Project: WebP Converter Bridge
 * Author: Christian Aguirre
 * Date: 2025-11-21
 */
/**
 * API REST para Conversión WebP
 * Compatible con N8N y otras herramientas de automatización
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/webp-online/converter.php';

// Solo permitir métodos específicos
$allowedMethods = ['POST', 'GET', 'OPTIONS'];
if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
    jsonError('Método no permitido', 405);
}

// Manejo de CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Determinar acción para peticiones GET
$action = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'health';
}

// Gestión de tokens: soporta API global y clientes de integración WordPress
$providedToken = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
$providedToken = is_string($providedToken) ? trim($providedToken) : null;
if ($providedToken === '') {
    $providedToken = null;
}

$integrationClient = null;
$tokenStatus = $providedToken === null ? 'missing' : 'unknown';

if ($providedToken !== null) {
    if (API_TOKEN !== null && hash_equals(API_TOKEN, $providedToken)) {
        $tokenStatus = 'global';
    } else {
        $integrationClient = findIntegrationClientByToken($providedToken);
        if ($integrationClient) {
            $clientStatus = $integrationClient['status'] ?? 'active';
            if ($clientStatus !== 'active') {
                logIntegrationEvent('WARNING', 'Intento de acceso con token no activo', [
                    'token' => $providedToken,
                    'status' => $clientStatus,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                jsonError('Token de cliente no está activo', 403, ['status' => $clientStatus]);
            }
            $tokenStatus = 'client';
        } else {
            $tokenStatus = 'invalid';
            logIntegrationEvent('WARNING', 'Token no reconocido', [
                'token' => $providedToken,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        }
    }
}

$requiresAuth = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $protectedActions = ['list'];
    if ($action !== null && in_array($action, $protectedActions, true)) {
        $requiresAuth = true;
    }
}

if ($tokenStatus === 'invalid') {
    jsonError('Token de API inválido', 401);
}

if ($requiresAuth && $tokenStatus === 'missing') {
    jsonError('Token de API requerido', 401, ['header' => API_TOKEN_HEADER]);
}

if ($integrationClient) {
    touchIntegrationClientUsage((int)$integrationClient['id']);
}

/**
 * GET /api.php?action=health
 * Verifica el estado del servicio
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'health';
    
    switch ($action) {
        case 'health':
            jsonResponse([
                'success' => true,
                'status' => 'online',
                'environment' => IS_DOCKER ? 'docker' : 'mamp',
                'version' => '1.0.0',
                'features' => [
                    'upload' => true,
                    'url' => true,
                    'base64' => true,
                    'batch' => true,
                    'resize' => true
                ],
                'client' => $integrationClient ? [
                    'id' => $integrationClient['id'],
                    'client_name' => $integrationClient['client_name'],
                    'cost_per_image' => $integrationClient['cost_per_image'] ?? '0.00',
                    'status' => $integrationClient['status']
                ] : null,
                // Deprecated, use 'client' instead
                'client_config' => $integrationClient ? [
                    'cost_per_image' => $integrationClient['cost_per_image'] ?? '0.00'
                ] : null
            ]);
            break;
            
        case 'list':
            $dir = $_GET['type'] === 'source' ? UPLOAD_DIR : CONVERT_DIR;
            $pattern = $_GET['type'] === 'source' ? '/\.(jpg|jpeg|png|gif)$/i' : '/\.webp$/i';
            
            $files = @scandir($dir);
            if ($files === false) {
                jsonError('No se puede leer el directorio', 500);
            }
            
            $images = array_filter($files, function($file) use ($pattern) {
                return preg_match($pattern, $file);
            });
            
            $fileList = array_map(function($file) use ($dir) {
                $path = $dir . $file;
                $size = @filesize($path);
                $info = @getimagesize($path);
                
                return [
                    'filename' => $file,
                    'size' => $size,
                    'size_formatted' => $size ? round($size / 1024, 2) . ' KB' : 'N/A',
                    'dimensions' => $info ? $info[0] . 'x' . $info[1] : 'N/A',
                    'url' => BASE_URL . '/' . basename($dir) . $file
                ];
            }, array_values($images));
            
            jsonResponse([
                'success' => true,
                'count' => count($fileList),
                'files' => $fileList
            ]);
            break;
            
        case 'download-plugin':
            $pluginDir = __DIR__ . '/wordpress-plugin';
            if (!is_dir($pluginDir)) {
                jsonError('Directorio del plugin no encontrado', 404);
            }

            $zipFile = TEMP_DIR . 'webp-converter-bridge.zip';
            $zip = new ZipArchive();

            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                jsonError('No se pudo crear el archivo ZIP', 500);
            }

            // Función recursiva para añadir archivos
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($pluginDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($pluginDir) + 1);
                    $zip->addFile($filePath, 'webp-converter-bridge/' . $relativePath);
                }
            }



            $zip->close();

            if (file_exists($zipFile)) {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="webp-converter-bridge.zip"');
                header('Content-Length: ' . filesize($zipFile));
                header('Pragma: no-cache');
                header('Expires: 0');
                readfile($zipFile);
                
                // Limpiar archivo temporal después de enviar
                if (!IS_DOCKER) { // En Docker a veces causa problemas borrar inmediatamente si el buffer no se vació
                     @unlink($zipFile);
                }
                exit;
            } else {
                jsonError('Error al generar el archivo ZIP', 500);
            }
            break;

        default:
            jsonError('Acción no reconocida', 400);
    }
}

/**
 * POST /api.php
 * Convierte imágenes a WebP
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $converter = new ImageConverter();
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $metricsClient = ($tokenStatus === 'client') ? $integrationClient : null;
    
    // Determinar el tipo de entrada
    $input = null;
    $inputType = null;
    
    // 1. Multipart/form-data (Upload directo)
    if (strpos($contentType, 'multipart/form-data') !== false && isset($_FILES['image'])) {
        $inputType = 'upload';
        $input = $_FILES['image'];
    }
    // 2. JSON
    elseif (strpos($contentType, 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            jsonError('JSON inválido', 400);
        }
        
        // Determinar subtipo
        if (isset($input['url'])) {
            $inputType = 'url';
        } elseif (isset($input['base64'])) {
            $inputType = 'base64';
        } elseif (isset($input['batch'])) {
            $inputType = 'batch';
        } elseif (isset($input['filename'])) {
            $inputType = 'existing';
        }
    }
    // 3. Form URL-encoded
    elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        if (isset($_POST['url'])) {
            $inputType = 'url';
            $input = $_POST;
        } elseif (isset($_POST['filename'])) {
            $inputType = 'existing';
            $input = $_POST;
        }
    }
    
    if (!$inputType) {
        jsonError('Tipo de entrada no reconocido. Proporciona: image (upload), url, base64, batch o filename', 400);
    }
    
    // Parámetros comunes
    $quality = isset($input['quality']) ? (int)$input['quality'] : DEFAULT_QUALITY;
    $outputName = $input['output_name'] ?? null;
    
    // Opciones de conversión
    $options = [];
    if (isset($input['max_width'])) {
        $options['max_width'] = (int)$input['max_width'];
    }
    if (isset($input['max_height'])) {
        $options['max_height'] = (int)$input['max_height'];
    }
    
    // Procesar según tipo de entrada
    try {
        switch ($inputType) {
            case 'upload':
                // Manejar upload directo
                if ($input['error'] !== UPLOAD_ERR_OK) {
                    jsonError('Error en el upload: ' . $input['error'], 400);
                }
                
                // Generar nombre de salida
                if (!$outputName) {
                    $outputName = pathinfo($input['name'], PATHINFO_FILENAME) . '_' . time();
                }
                $outputName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $outputName);
                
                $tempSource = $input['tmp_name'];
                $destination = CONVERT_DIR . $outputName . '.webp';
                
                $success = $converter->convertToWebP($tempSource, $destination, $quality, $options);
                
                if ($success) {
                    $fileSize = filesize($destination);
                    $originalSize = $input['size'];

                    if ($integrationClient) {
                        recordIntegrationConversion($integrationClient, [
                            'api_token' => $providedToken,
                            'source_filename' => $input['name'] ?? ($outputName . '.tmp'),
                            'webp_filename' => $outputName . '.webp',
                            'source_bytes' => $originalSize,
                            'converted_bytes' => $fileSize
                        ]);
                    }
                    
                    jsonResponse([
                        'success' => true,
                        'message' => 'Imagen convertida exitosamente',
                        'data' => [
                            'filename' => $outputName . '.webp',
                            'url' => CONVERT_PUBLIC_URL . $outputName . '.webp',
                            'size' => $fileSize,
                            'original_size' => $originalSize,
                            'savings' => round((1 - $fileSize / $originalSize) * 100, 2) . '%',
                            'quality' => $quality
                        ]
                    ], 201);
                } else {
                    jsonError('Error en la conversión', 500, $converter->getErrors());
                }
                break;
                
            case 'url':
                // Convertir desde URL
                $url = $input['url'];
                
                if (!$outputName) {
                    $outputName = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME) . '_' . time();
                }
                $outputName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $outputName);
                
                $destination = CONVERT_DIR . $outputName . '.webp';
                
                $success = $converter->convertFromURL($url, $destination, $quality, $options);
                
                if ($success) {
                    $fileSize = filesize($destination);
                    $sourceFilename = basename(parse_url($url, PHP_URL_PATH)) ?: $outputName;

                    if ($integrationClient) {
                        recordIntegrationConversion($integrationClient, [
                            'api_token' => $providedToken,
                            'source_filename' => $sourceFilename,
                            'webp_filename' => $outputName . '.webp',
                            'source_bytes' => 0,
                            'converted_bytes' => $fileSize
                        ]);
                    }
                    
                    jsonResponse([
                        'success' => true,
                        'message' => 'Imagen descargada y convertida',
                        'data' => [
                            'filename' => $outputName . '.webp',
                            'url' => CONVERT_PUBLIC_URL . $outputName . '.webp',
                            'size' => $fileSize,
                            'source_url' => $url,
                            'quality' => $quality
                        ]
                    ], 201);
                } else {
                    jsonError('Error en la conversión desde URL', 500, $converter->getErrors());
                }
                break;
                
            case 'base64':
                // Convertir desde base64
                $base64Data = $input['base64'];
                
                if (!$outputName) {
                    $outputName = 'base64_' . time();
                }
                $outputName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $outputName);
                
                $base64Payload = $base64Data;
                if (strpos($base64Payload, 'data:') === 0) {
                    $base64Payload = substr($base64Payload, strpos($base64Payload, ',') + 1);
                }
                $decodedBytes = base64_decode($base64Payload, true);
                $estimatedSourceBytes = $decodedBytes !== false ? strlen($decodedBytes) : 0;
                
                $destination = CONVERT_DIR . $outputName . '.webp';
                
                $success = $converter->convertFromBase64($base64Data, $destination, $quality, $options);
                
                if ($success) {
                    $fileSize = filesize($destination);

                    if ($metricsClient) {
                        recordIntegrationConversion($metricsClient, [
                            'api_token' => $providedToken,
                            'source_filename' => $outputName . '.tmp',
                            'webp_filename' => $outputName . '.webp',
                            'source_bytes' => $estimatedSourceBytes,
                            'converted_bytes' => $fileSize
                        ]);
                    }
                    
                    jsonResponse([
                        'success' => true,
                        'message' => 'Imagen base64 convertida',
                        'data' => [
                            'filename' => $outputName . '.webp',
                            'url' => CONVERT_PUBLIC_URL . $outputName . '.webp',
                            'size' => $fileSize,
                            'quality' => $quality
                        ]
                    ], 201);
                } else {
                    jsonError('Error en la conversión desde base64', 500, $converter->getErrors());
                }
                break;
                
            case 'existing':
                // Convertir archivo existente en upload/
                $filename = $input['filename'];
                $sourcePath = UPLOAD_DIR . basename($filename); // Seguridad: solo nombre de archivo
                
                if (!file_exists($sourcePath)) {
                    jsonError('Archivo no encontrado: ' . $filename, 404);
                }
                
                if (!$outputName) {
                    $outputName = pathinfo($filename, PATHINFO_FILENAME) . '_' . time();
                }
                $outputName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $outputName);
                
                $destination = CONVERT_DIR . $outputName . '.webp';
                
                $originalSize = filesize($sourcePath);
                $success = $converter->convertToWebP($sourcePath, $destination, $quality, $options);
                
                if ($success) {
                    $fileSize = filesize($destination);

                    if ($metricsClient) {
                        recordIntegrationConversion($metricsClient, [
                            'api_token' => $providedToken,
                            'source_filename' => basename($filename),
                            'webp_filename' => $outputName . '.webp',
                            'source_bytes' => $originalSize,
                            'converted_bytes' => $fileSize
                        ]);
                    }
                    
                    jsonResponse([
                        'success' => true,
                        'message' => 'Archivo existente convertido',
                        'data' => [
                            'filename' => $outputName . '.webp',
                            'url' => CONVERT_PUBLIC_URL . $outputName . '.webp',
                            'size' => $fileSize,
                            'original_size' => $originalSize,
                            'savings' => round((1 - $fileSize / $originalSize) * 100, 2) . '%',
                            'quality' => $quality
                        ]
                    ], 201);
                } else {
                    jsonError('Error en la conversión', 500, $converter->getErrors());
                }
                break;
                
            case 'batch':
                // Conversión por lotes
                $batch = $input['batch'];
                
                if (!is_array($batch) || empty($batch)) {
                    jsonError('El parámetro batch debe ser un array no vacío', 400);
                }
                
                $files = [];
                $batchMeta = [];
                foreach ($batch as $item) {
                    $filename = $item['filename'] ?? null;
                    if (!$filename) continue;
                    
                    $sourcePath = UPLOAD_DIR . basename($filename);
                    if (!file_exists($sourcePath)) continue;
                    
                    $outputName = $item['output_name'] ?? pathinfo($filename, PATHINFO_FILENAME) . '_' . time();
                    $outputName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $outputName);
                    
                    $files[] = [
                        'source' => $sourcePath,
                        'destination' => CONVERT_DIR . $outputName . '.webp'
                    ];
                    $batchMeta[$outputName . '.webp'] = [
                        'source_filename' => basename($filename),
                        'source_bytes' => @filesize($sourcePath) ?: 0
                    ];
                }
                
                if (empty($files)) {
                    jsonError('No se encontraron archivos válidos para procesar', 400);
                }
                
                $results = $converter->convertBatch($files, $quality, $options);

                if ($metricsClient && !empty($results['successful'])) {
                    foreach ($results['successful'] as $successfulItem) {
                        $destinationName = $successfulItem['destination'];
                        $meta = $batchMeta[$destinationName] ?? [
                            'source_filename' => $successfulItem['source'],
                            'source_bytes' => 0
                        ];

                        recordIntegrationConversion($metricsClient, [
                            'api_token' => $providedToken,
                            'source_filename' => $meta['source_filename'],
                            'webp_filename' => $destinationName,
                            'source_bytes' => $meta['source_bytes'],
                            'converted_bytes' => (int)($successfulItem['size'] ?? 0)
                        ]);
                    }
                }
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Conversión por lotes completada',
                    'data' => $results
                ], 200);
                break;
        }
        
    } catch (Exception $e) {
        logMessage('ERROR', 'API Exception', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        jsonError('Error interno del servidor', 500, ['exception' => $e->getMessage()]);
    }
}

