<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/integration-db.php';

header('Content-Type: text/plain');

// 1. Inicializar DB si hace falta
initializeIntegrationDatabase();

// 2. Buscar si ya existe el cliente
$pdo = getIntegrationPdo();
$stmt = $pdo->prepare("SELECT * FROM integration_clients WHERE client_name = 'WordPress Local'");
$stmt->execute();
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if ($client) {
    echo "El cliente 'WordPress Local' ya existe.\n";
    echo "ID: " . $client['id'] . "\n";
    echo "Token: " . $client['api_token'] . "\n";
} else {
    echo "Creando nuevo cliente 'WordPress Local'...\n";
    $result = createIntegrationClient('WordPress Local', 'admin@local.test');
    if ($result['success']) {
        echo "¡Cliente creado con éxito!\n";
        echo "Token: " . $result['token'] . "\n";
    } else {
        echo "Error al crear cliente: " . $result['error'] . "\n";
    }
}

echo "\n--- INSTRUCCIONES ---\n";
echo "Copia este Token y pégalo en los ajustes del Plugin de WordPress para que las conversiones se registren en el log.";
