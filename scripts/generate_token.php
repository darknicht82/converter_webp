<?php
/**
 * Script CLI para generar tokens de integración
 * Uso: php scripts/generate_token.php "Nombre Cliente" "email@contacto.com" [quota]
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos.\n");
}

require_once __DIR__ . '/../config.php';

// Verificar argumentos
if ($argc < 2) {
    echo "Uso: php scripts/generate_token.php \"Nombre Cliente\" \"email@contacto.com\" [quota]\n";
    exit(1);
}

$clientName = $argv[1];
$contactEmail = $argv[2] ?? null;
$monthlyQuota = isset($argv[3]) ? (int)$argv[3] : null;

echo "Generando token para: $clientName ($contactEmail)...\n";

try {
    $client = createIntegrationClient([
        'client_name' => $clientName,
        'contact_email' => $contactEmail,
        'monthly_quota' => $monthlyQuota,
        'cost_per_image' => 0.001, // Costo por defecto
        'status' => 'active'
    ]);

    if ($client) {
        echo "\n✅ Cliente creado exitosamente:\n";
        echo "----------------------------------------\n";
        echo "ID: " . $client['id'] . "\n";
        echo "Nombre: " . $client['client_name'] . "\n";
        echo "Token: " . $client['api_token'] . "\n";
        echo "----------------------------------------\n";
        echo "Guarda este token en un lugar seguro.\n";
    } else {
        echo "\n❌ Error al crear el cliente.\n";
    }

} catch (Exception $e) {
    echo "\n❌ Excepción: " . $e->getMessage() . "\n";
}
