<?php
/**
 * WebP Converter - Logs Data Endpoint
 * 
 * Devuelve los logs de conversión en formato JSON
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/integration-db.php';

// CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-API-Token, Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Authenticate
$token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token requerido']);
    exit;
}

// Get client by token
$integrationClient = findIntegrationClientByToken($token);

if (!$integrationClient) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido']);
    exit;
}

// Get logs for this client
try {
    $db = getIntegrationPdo();
    
    $stmt = $db->prepare("
        SELECT 
            id,
            filename,
            original_size,
            webp_size,
            savings_percent,
            cost,
            status,
            created_at
        FROM conversion_logs
        WHERE client_id = :client_id
        ORDER BY created_at DESC
        LIMIT 500
    ");
    
    $stmt->execute(['client_id' => $integrationClient['id']]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary stats
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_conversions,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(original_size) as total_original_bytes,
            SUM(webp_size) as total_webp_bytes,
            SUM(cost) as total_cost
        FROM conversion_logs
        WHERE client_id = :client_id
    ");
    
    $statsStmt->execute(['client_id' => $integrationClient['id']]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'client' => [
            'id' => $integrationClient['id'],
            'name' => $integrationClient['client_name']
        ],
        'stats' => $stats,
        'logs' => $logs,
        'count' => count($logs)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al obtener logs',
        'message' => $e->getMessage()
    ]);
}
