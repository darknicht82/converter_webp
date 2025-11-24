<?php
/**
 * Display all active client tokens for WordPress plugin configuration
 */

require_once __DIR__ . '/config.php';

echo "=== TOKENS DE CLIENTES ACTIVOS ===\n\n";

$pdo = getIntegrationPdo();
$stmt = $pdo->query("
    SELECT id, client_name, contact_email, api_token, status, created_at
    FROM integration_clients 
    WHERE status = 'active'
    ORDER BY id
");

$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($clients)) {
    echo "No hay clientes activos.\n";
    exit;
}

echo sprintf("%-5s %-20s %-50s %s\n", "ID", "NOMBRE", "TOKEN", "STATUS");
echo str_repeat("-", 120) . "\n";

foreach ($clients as $client) {
    printf(
        "%-5d %-20s %-50s %s\n",
        $client['id'],
        substr($client['client_name'], 0, 20),
        $client['api_token'],
        $client['status']
    );
}

echo "\n=== INSTRUCCIONES ===\n";
echo "Copia el token completo del cliente que deseas usar en WordPress.\n";
echo "Pégalo en: WordPress Admin > Conversor WebP > Token de API\n";
echo "URL API: http://localhost:9191/webp/api.php\n";
