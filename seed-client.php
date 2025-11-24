<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getIntegrationPdo();
    
    // Check if client exists
    $stmt = $pdo->prepare("SELECT api_token FROM integration_clients WHERE client_name = 'Test Client'");
    $stmt->execute();
    $token = $stmt->fetchColumn();

    if ($token) {
        echo "TOKEN: " . $token . "\n";
    } else {
        // Create new client
        $token = bin2hex(random_bytes(24));
        $now = getSqlTimestampFunction();
        
        // Use explicit SQL for safety
        if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {
             $sql = "INSERT INTO integration_clients (client_name, api_token, status, created_at, updated_at) VALUES ('Test Client', :token, 'active', NOW(), NOW())";
        } else {
             $sql = "INSERT INTO integration_clients (client_name, api_token, status, created_at, updated_at) VALUES ('Test Client', :token, 'active', datetime('now'), datetime('now'))";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
        echo "TOKEN: " . $token . "\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
