<?php
/**
 * Historial de Conversiones - Endpoint para obtener historial
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/integration-db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getIntegrationPdo();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $query = "SELECT * FROM conversion_events WHERE 1=1";
    $params = [];
    
    if ($search) {
        $query .= " AND (source_filename LIKE ? OR output_filename LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total
    $countQuery = "SELECT COUNT(*) as total FROM conversion_events";
    if ($search) {
        $countQuery .= " WHERE (source_filename LIKE ? OR output_filename LIKE ?)";
    }
    $countStmt = $pdo->prepare($countQuery);
    if ($search) {
        $countStmt->execute([$searchTerm, $searchTerm]);
    } else {
        $countStmt->execute();
    }
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    jsonResponse([
        'success' => true,
        'data' => $events,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    jsonError('Error al obtener historial: ' . $e->getMessage(), 500);
}





