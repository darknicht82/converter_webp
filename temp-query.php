<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/integration-db.php';

$pdo = getIntegrationPdo();
$stmt = $pdo->query('SELECT id, client_name, api_token FROM integration_clients LIMIT 5');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row) . PHP_EOL;
}
