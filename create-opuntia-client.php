<?php
require '/var/www/html/config.php';

$client = createIntegrationClient([
    'client_name' => 'Opuntia WordPress',
    'contact_email' => 'admin@opuntia.local',
    'api_token' => 'e07ae44b27cb5e7904f0ce1c846e28b6ecd668d1dce325d2',
    'status' => 'active',
    'monthly_quota' => null,
    'cost_per_image' => 0,
    'notes' => 'WordPress local en MAMP (http://localhost/opuntia/)'
]);

echo json_encode($client, JSON_PRETTY_PRINT) . PHP_EOL;
