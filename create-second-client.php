<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getIntegrationPdo();
    
    // Create WordPress client
    $wpToken = bin2hex(random_bytes(24));
    
    if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {
        $sql = "INSERT INTO integration_clients (client_name, contact_email, api_token, status, cost_per_image, created_at, updated_at) 
                VALUES ('WordPress Site', 'wp@example.com', :token, 'active', 0.001, NOW(), NOW())";
    } else {
        $sql = "INSERT INTO integration_clients (client_name, contact_email, api_token, status, cost_per_image, created_at, updated_at) 
                VALUES ('WordPress Site', 'wp@example.com', :token, 'active', 0.001, datetime('now'), datetime('now'))";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':token' => $wpToken]);
    
    echo "WordPress Client Token: " . $wpToken . "\n";
    
    // List all clients
    echo "\n=== All Clients in PostgreSQL ===\n";
    $stmt = $pdo->query("SELECT id, client_name, api_token, status, cost_per_image FROM integration_clients ORDER BY id");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($clients as $client) {
        echo "ID: {$client['id']} | {$client['client_name']} | Token: {$client['api_token']} | Status: {$client['status']}\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
