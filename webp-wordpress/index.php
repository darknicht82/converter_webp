<?php
require_once __DIR__ . '/../config.php';

session_start();

if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

function addFlashMessage(string $type, string $message): void
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][$type][] = $message;
}

$createForm = [
    'client_name' => '',
    'contact_email' => '',
    'monthly_quota' => '',
    'cost_per_image' => '0.00',
    'status' => 'active',
    'notes' => ''
];

$editClient = null;
$errors = [];
$messages = [];

$flash = $_SESSION['flash_messages'] ?? [];
if (!empty($flash['success'])) {
    $messages = $flash['success'];
}
if (!empty($flash['error'])) {
    $errors = $flash['error'];
}
unset($_SESSION['flash_messages']);

$requestedEditId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $csrfToken)) {
        $errors[] = 'Token CSRF inválido. Recarga la página e inténtalo nuevamente.';
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'create_client':
                $clientName = trim($_POST['client_name'] ?? '');
                $contactEmail = trim($_POST['contact_email'] ?? '');
                $monthlyQuota = trim($_POST['monthly_quota'] ?? '');
                $costPerImage = trim($_POST['cost_per_image'] ?? '0');
                $status = $_POST['status'] ?? 'active';
                $notes = trim($_POST['notes'] ?? '');

                $createForm = [
                    'client_name' => $clientName,
                    'contact_email' => $contactEmail,
                    'monthly_quota' => $monthlyQuota,
                    'cost_per_image' => $costPerImage,
                    'status' => $status,
                    'notes' => $notes
                ];

                if ($clientName === '') {
                    $errors[] = 'El nombre del cliente es obligatorio.';
                    break;
                }

                if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'El correo electrónico no tiene un formato válido.';
                    break;
                }

                $quotaValue = ($monthlyQuota === '' ? null : max(0, (int)$monthlyQuota));
                $costValue = (float)$costPerImage;

                $newClient = createIntegrationClient([
                    'client_name' => $clientName,
                    'contact_email' => $contactEmail !== '' ? $contactEmail : null,
                    'monthly_quota' => $quotaValue,
                    'cost_per_image' => $costValue,
                    'status' => $status,
                    'notes' => $notes !== '' ? $notes : null
                ]);

                if ($newClient) {
                    addFlashMessage('success', 'Cliente creado correctamente. Token asignado: ' . substr($newClient['api_token'], -8));
                    header('Location: index.php');
                    exit;
                }

                $errors[] = 'No se pudo crear el cliente. Revisa el log para más detalles.';
                break;

            case 'update_client':
                $clientId = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
                $existing = $clientId > 0 ? getIntegrationClientById($clientId) : null;
                if (!$existing) {
                    $errors[] = 'Cliente no encontrado.';
                    break;
                }

                $clientName = trim($_POST['client_name'] ?? '');
                $contactEmail = trim($_POST['contact_email'] ?? '');
                $monthlyQuota = trim($_POST['monthly_quota'] ?? '');
                $costPerImage = trim($_POST['cost_per_image'] ?? '0');
                $status = $_POST['status'] ?? 'active';
                $notes = trim($_POST['notes'] ?? '');

                $editClient = array_merge($existing, [
                    'client_name' => $clientName,
                    'contact_email' => $contactEmail,
                    'monthly_quota' => $monthlyQuota,
                    'cost_per_image' => $costPerImage,
                    'status' => $status,
                    'notes' => $notes
                ]);
                $requestedEditId = $clientId;

                if ($clientName === '') {
                    $errors[] = 'El nombre del cliente es obligatorio.';
                    break;
                }

                if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'El correo electrónico no tiene un formato válido.';
                    break;
                }

                $quotaValue = ($monthlyQuota === '' ? null : max(0, (int)$monthlyQuota));
                $costValue = (float)$costPerImage;

                $success = updateIntegrationClient($clientId, [
                    'client_name' => $clientName,
                    'contact_email' => $contactEmail !== '' ? $contactEmail : null,
                    'monthly_quota' => $quotaValue,
                    'cost_per_image' => $costValue,
                    'status' => $status,
                    'notes' => $notes !== '' ? $notes : null
                ]);

                if ($success) {
                    addFlashMessage('success', 'Datos del cliente actualizados correctamente.');
                    header('Location: index.php?edit=' . $clientId);
                    exit;
                }

                $errors[] = 'No se pudo actualizar el cliente. Revisa el log para más detalles.';
                break;

            case 'regenerate_token':
                $clientId = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
                if ($clientId <= 0) {
                    $errors[] = 'Cliente inválido.';
                    break;
                }
                $newToken = regenerateIntegrationClientToken($clientId);
                if ($newToken) {
                    addFlashMessage('success', 'Token regenerado correctamente. Nuevo sufijo: ' . substr($newToken, -6));
                    header('Location: index.php?edit=' . $clientId);
                    exit;
                }
                $errors[] = 'No se pudo regenerar el token.';
                break;
        }
    }
}

if ($requestedEditId) {
    $editClient = getIntegrationClientById($requestedEditId) ?: $editClient;
    if (!$editClient) {
        $errors[] = 'El cliente solicitado para edición no existe.';
    }
}

$stats = fetchIntegrationStats();
$clients = fetchIntegrationClientsWithMetrics();
$events = fetchRecentIntegrationEvents(8);

$statusOptions = [
    'active' => 'Activo',
    'paused' => 'Pausado',
    'revoked' => 'Revocado'
];

function format_money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function format_number(int $value): string
{
    return number_format($value, 0, ',', '.');
}

function format_datetime(?string $value): string
{
    if (!$value) {
        return '—';
    }
    $dt = new DateTime($value);
    return $dt->format('d/m/Y H:i');
}

function format_mb(float $value): string
{
    return number_format($value, 2) . ' MB';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversor WebP WordPress - Panel</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #0b1220, #111c33);
            color: #f8fafc;
            margin: 0;
            min-height: 100vh;
            padding: 90px 30px 40px;
        }
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(8, 13, 24, 0.92);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }
        .top-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand {
            font-weight: 700;
            font-size: 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .nav-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: rgba(226, 232, 240, 0.85);
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 999px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .nav-links a:hover {
            background: rgba(59, 130, 246, 0.25);
            color: #e0f2fe;
        }
        .nav-links a.active {
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            color: #fff;
            box-shadow: 0 12px 32px rgba(37, 99, 235, 0.35);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            margin-bottom: 10px;
            font-size: 32px;
        }
        .lead {
            color: rgba(226, 232, 240, 0.75);
            margin-bottom: 30px;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .flash-messages {
            margin-bottom: 20px;
        }
        .flash-message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 12px;
            font-size: 14px;
            border: 1px solid transparent;
        }
        .flash-message.success {
            background: rgba(34, 197, 94, 0.18);
            border-color: rgba(34, 197, 94, 0.35);
            color: #bbf7d0;
        }
        .flash-message.error {
            background: rgba(248, 113, 113, 0.18);
            border-color: rgba(248, 113, 113, 0.45);
            color: #fecaca;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .form-panel {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(59, 130, 246, 0.12);
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.35);
        }
        .form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }
        .form-field label {
            font-size: 13px;
            font-weight: 600;
            color: rgba(226, 232, 240, 0.82);
            letter-spacing: 0.02em;
        }
        .form-field input,
        .form-field select,
        .form-field textarea {
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: rgba(15, 23, 42, 0.6);
            color: #f8fafc;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            border-color: rgba(59, 130, 246, 0.6);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }
        .form-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .primary-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            color: #031022;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 28px rgba(56, 189, 248, 0.35);
        }
        .secondary-link {
            color: rgba(148, 163, 184, 0.9);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s ease;
        }
        .secondary-link:hover {
            color: rgba(226, 232, 240, 0.95);
        }
        .card {
            background: rgba(15, 23, 42, 0.88);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(59, 130, 246, 0.08);
        }
        .card h3 {
            margin: 0;
            font-size: 15px;
            color: rgba(148, 163, 184, 0.9);
        }
        .card-value {
            font-size: 32px;
            font-weight: 700;
            margin-top: 12px;
        }
        .grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        .panel {
            background: rgba(15, 23, 42, 0.92);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(59, 130, 246, 0.08);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.35);
        }
        .panel h2 {
            margin-top: 0;
            font-size: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            font-size: 14px;
        }
        th {
            font-weight: 600;
            color: rgba(226, 232, 240, 0.8);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.04em;
        }
        tbody tr:hover {
            background: rgba(59, 130, 246, 0.08);
        }
        .token {
            font-family: 'Fira Code', Consolas, monospace;
            background: rgba(15, 118, 110, 0.18);
            padding: 6px 10px;
            border-radius: 8px;
            color: #5eead4;
            display: inline-block;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: rgba(34, 197, 94, 0.18);
            color: #bbf7d0;
        }
        .status-paused {
            background: rgba(234, 179, 8, 0.2);
            color: #fde68a;
        }
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #22d3ee, #38bdf8);
            color: #021126;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .download-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(56, 189, 248, 0.35);
        }
        .inline-form {
            display: inline-block;
        }
        .token-btn {
            border: none;
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .token-btn:hover {
            background: rgba(37, 99, 235, 0.35);
            color: #e0f2fe;
        }
        .link-edit {
            color: #fbbf24;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-right: 10px;
        }
        .link-edit:hover {
            color: #fde68a;
        }
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .events-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .events-list li {
            padding: 12px 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            font-size: 13px;
        }
        .events-list li:last-child {
            border-bottom: none;
        }
        .events-list strong {
            color: #93c5fd;
        }
        .empty {
            color: rgba(226, 232, 240, 0.65);
            font-style: italic;
        }
        .help-card {
            margin-top: 20px;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.25);
            border-radius: 14px;
            padding: 20px;
        }
        .help-card code {
            background: rgba(15, 23, 42, 0.7);
            padding: 2px 6px;
            border-radius: 4px;
            color: #bae6fd;
        }
        @media (max-width: 980px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-inner">
            <div class="brand">🔗 WebP WordPress</div>
            <div class="nav-links">
                <a href="../index.php">🏠 <span>Inicio</span></a>
                <a href="../webp-online/index.php">⚙️ <span>Conversor</span></a>
                <a href="index.php" class="active">🔗 <span>WordPress</span></a>
                <a href="../social-designer/social-designer.php">🎨 <span>Social Designer</span></a>
            </div>
        </div>
    </nav>
    <div class="container">
        <h1>Dashboard WordPress</h1>
        <p class="lead">
            Gestiona tokens, descargas del plugin y monitorea el consumo de imágenes optimizadas para sitios WordPress.
        </p>

        <?php if (!empty($messages) || !empty($errors)): ?>
            <div class="flash-messages">
                <?php foreach ($messages as $message): ?>
                    <div class="flash-message success">✅ <?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
                <?php foreach ($errors as $error): ?>
                    <div class="flash-message error">⚠️ <?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-grid">
            <section class="form-panel">
                <h2>Crear nuevo cliente</h2>
                <p style="color: rgba(226,232,240,0.7); margin-bottom: 18px;">
                    Genera un token dedicado y define costos/cuotas para facturación.
                </p>
                <form method="post" action="index.php">
                    <input type="hidden" name="action" value="create_client">
                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]); ?>">

                    <div class="form-field">
                        <label for="create-client-name">Nombre del cliente *</label>
                        <input type="text" id="create-client-name" name="client_name" required value="<?php echo htmlspecialchars($createForm['client_name']); ?>">
                    </div>
                    <div class="form-field">
                        <label for="create-contact-email">Correo de contacto</label>
                        <input type="email" id="create-contact-email" name="contact_email" value="<?php echo htmlspecialchars($createForm['contact_email']); ?>">
                    </div>
                    <div class="form-field">
                        <label for="create-monthly-quota">Cuota mensual (imágenes)</label>
                        <input type="number" min="0" id="create-monthly-quota" name="monthly_quota" value="<?php echo htmlspecialchars($createForm['monthly_quota']); ?>">
                    </div>
                    <div class="form-field">
                        <label for="create-cost">Costo por imagen (USD)</label>
                        <input type="number" min="0" step="0.01" id="create-cost" name="cost_per_image" value="<?php echo htmlspecialchars($createForm['cost_per_image']); ?>">
                    </div>
                    <div class="form-field">
                        <label for="create-status">Estado inicial</label>
                        <select id="create-status" name="status">
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $createForm['status'] === $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="create-notes">Notas internas</label>
                        <textarea id="create-notes" name="notes" rows="3"><?php echo htmlspecialchars($createForm['notes']); ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="primary-btn">➕ Crear cliente</button>
                    </div>
                </form>
            </section>

            <?php if ($editClient): ?>
            <section class="form-panel">
                <h2>Editar cliente</h2>
                <p style="color: rgba(226,232,240,0.7); margin-bottom: 18px;">
                    Actualiza datos de contacto, estado y parámetros de facturación.
                </p>
                <form method="post" action="index.php?edit=<?php echo (int)$editClient['id']; ?>">
                    <input type="hidden" name="action" value="update_client">
                    <input type="hidden" name="client_id" value="<?php echo (int)$editClient['id']; ?>">
                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]); ?>">

                    <div class="form-field">
                        <label for="edit-client-name">Nombre del cliente *</label>
                        <input type="text" id="edit-client-name" name="client_name" required value="<?php echo htmlspecialchars($editClient['client_name'] ?? ''); ?>">
                    </div>
                    <div class="form-field">
                        <label for="edit-contact-email">Correo de contacto</label>
                        <input type="email" id="edit-contact-email" name="contact_email" value="<?php echo htmlspecialchars($editClient['contact_email'] ?? ''); ?>">
                    </div>
                    <div class="form-field">
                        <label for="edit-monthly-quota">Cuota mensual (imágenes)</label>
                        <input type="number" min="0" id="edit-monthly-quota" name="monthly_quota" value="<?php echo htmlspecialchars($editClient['monthly_quota'] ?? ''); ?>">
                    </div>
                    <div class="form-field">
                        <label for="edit-cost">Costo por imagen (USD)</label>
                        <input type="number" min="0" step="0.01" id="edit-cost" name="cost_per_image" value="<?php echo htmlspecialchars(number_format((float)($editClient['cost_per_image'] ?? 0), 2, '.', '')); ?>">
                    </div>
                    <div class="form-field">
                        <label for="edit-status">Estado actual</label>
                        <select id="edit-status" name="status">
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo (($editClient['status'] ?? 'active') === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="edit-notes">Notas internas</label>
                        <textarea id="edit-notes" name="notes" rows="3"><?php echo htmlspecialchars($editClient['notes'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="primary-btn">💾 Guardar cambios</button>
                        <a class="secondary-link" href="index.php">Cancelar</a>
                    </div>
                </form>
            </section>
            <?php endif; ?>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Clientes registrados</h3>
                <div class="card-value"><?php echo format_number($stats['total_clients']); ?></div>
            </div>
            <div class="card">
                <h3>Clientes activos</h3>
                <div class="card-value"><?php echo format_number($stats['active_clients']); ?></div>
            </div>
            <div class="card">
                <h3>Conversiones totales</h3>
                <div class="card-value"><?php echo format_number($stats['total_conversions']); ?></div>
            </div>
            <div class="card">
                <h3>Costo acumulado</h3>
                <div class="card-value"><?php echo format_money($stats['total_cost']); ?></div>
            </div>
            <div class="card">
                <h3>Ahorro total</h3>
                <div class="card-value"><?php echo format_mb(max(0, $stats['total_source_mb'] - $stats['total_converted_mb'])); ?></div>
            </div>
        </div>

        <div class="grid">
            <section class="panel">
                <h2>Clientes WordPress</h2>
                <?php if (empty($clients)): ?>
                    <p class="empty">Aún no hay clientes registrados. Crea tokens desde la base de datos o integra este panel con un formulario de alta.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Token</th>
                                <th>Estado</th>
                                <th>Imágenes</th>
                                <th>Costo</th>
                                <th>Último uso</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($client['client_name'] ?? 'Sin nombre'); ?></strong><br>
                                    <small><?php echo htmlspecialchars($client['contact_email'] ?? 'sin correo'); ?></small>
                                </td>
                                <td><span class="token"><?php echo htmlspecialchars($client['api_token']); ?></span></td>
                                <td>
                                    <?php
                                    $status = $client['status'] ?? 'active';
                                    $statusClass = $status === 'active' ? 'status-active' : 'status-paused';
                                    $statusLabel = $status === 'active' ? 'Activo' : ucfirst($status);
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($statusLabel); ?>
                                    </span>
                                </td>
                                <td><?php echo format_number($client['images_processed']); ?></td>
                                <td><?php echo format_money($client['total_cost']); ?></td>
                                <td><?php echo format_datetime($client['last_used_at'] ?? null); ?></td>
                                <td class="actions-cell">
                                    <a class="link-edit" href="index.php?edit=<?php echo (int)$client['id']; ?>">✏️ Editar</a>
                                    <form class="inline-form" method="post" action="index.php?edit=<?php echo (int)$client['id']; ?>">
                                        <input type="hidden" name="action" value="regenerate_token">
                                        <input type="hidden" name="client_id" value="<?php echo (int)$client['id']; ?>">
                                        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]); ?>">
                                        <button type="submit" class="token-btn" title="Regenerar token">
                                            🔄 Token
                                        </button>
                                    </form>
                                    <a class="download-btn" href="download-plugin.php?client_id=<?php echo (int)$client['id']; ?>">
                                        📦 Descargar plugin
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <aside class="panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 style="margin: 0;">Actividad reciente</h2>
                    <a href="logs.php" style="font-size: 13px; color: #38bdf8; text-decoration: none;">Ver todo →</a>
                </div>
                <?php if (empty($events)): ?>
                    <p class="empty">Aún no registramos conversiones desde WordPress.</p>
                <?php else: ?>
                    <ul class="events-list">
                        <?php foreach ($events as $event): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($event['client_name'] ?? 'Cliente'); ?></strong>
                                optimizó <code><?php echo htmlspecialchars($event['source_filename'] ?? 'N/A'); ?></code><br>
                                <small>
                                    <?php echo format_datetime($event['created_at']); ?>
                                    · <?php echo format_mb($event['source_bytes'] / (1024 * 1024)); ?> → <?php echo format_mb($event['converted_bytes'] / (1024 * 1024)); ?>
                                    · <?php echo format_money($event['cost']); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="help-card">
                    <h3>API del servicio</h3>
                    <p>
                        Endpoint: <code><?php echo CORE_API_PUBLIC_ENDPOINT; ?></code><br>
                        Header requerido: <code><?php echo API_TOKEN_HEADER; ?>: &lt;token&gt;</code>
                    </p>
                    <p>
                        Consulta la documentación en <code>documentation/webp-wordpress/README.md</code> para configurar el plugin y registrar nuevos clientes.
                    </p>
                </div>
            </aside>
        </div>
    </div>
    <script>
        window.APP_CONFIG = Object.assign(window.APP_CONFIG || {}, {
            apiBase: '<?php echo CORE_API_PUBLIC_ENDPOINT; ?>',
            authBase: '<?php echo AUTH_PUBLIC_ENDPOINT; ?>'
        });
    </script>
</body>
</html>

