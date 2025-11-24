<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/integration-dashboard.php';

session_start();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;

$data = fetchIntegrationEventsPaginated($page, $perPage);
$events = $data['events'];
$totalPages = $data['pages'];
$currentPage = $data['current_page'];
$totalEvents = $data['total'];

function format_datetime(?string $value): string
{
    if (!$value) {
        return '—';
    }
    $dt = new DateTime($value);
    return $dt->format('d/m/Y H:i:s');
}

function format_mb(float $value): string
{
    return number_format($value / (1024 * 1024), 2) . ' MB';
}

function format_money(float $amount): string
{
    return '$' . number_format($amount, 4);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Conversión - WebP WordPress</title>
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
        .panel {
            background: rgba(15, 23, 42, 0.92);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(59, 130, 246, 0.08);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.35);
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
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        .pagination a, .pagination span {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #e0f2fe;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.3);
        }
        .pagination a:hover {
            background: rgba(59, 130, 246, 0.25);
        }
        .pagination .current {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #93c5fd;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
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
        <a href="index.php" class="back-link">← Volver al Dashboard</a>
        <h1>Registro de Conversiones</h1>
        <p class="lead">
            Historial detallado de todas las imágenes procesadas. Total: <strong><?php echo number_format($totalEvents); ?></strong> eventos.
        </p>

        <div class="panel">
            <?php if (empty($events)): ?>
                <p class="empty">No hay eventos registrados.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Archivo Original</th>
                            <th>Tamaño Orig.</th>
                            <th>Tamaño WebP</th>
                            <th>Costo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo format_datetime($event['created_at']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($event['client_name'] ?? 'Desconocido'); ?></strong>
                                </td>
                                <td title="<?php echo htmlspecialchars($event['source_filename']); ?>">
                                    <?php echo htmlspecialchars(mb_strimwidth($event['source_filename'] ?? '', 0, 40, '...')); ?>
                                </td>
                                <td><?php echo format_mb($event['source_bytes']); ?></td>
                                <td><?php echo format_mb($event['converted_bytes']); ?></td>
                                <td><?php echo format_money($event['cost']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?php echo $currentPage - 1; ?>">« Anterior</a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $currentPage - 2);
                        $end = min($totalPages, $currentPage + 2);
                        
                        if ($start > 1) {
                            echo '<span>...</span>';
                        }
                        
                        for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i === $currentPage): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($end < $totalPages): ?>
                            echo '<span>...</span>';
                        <?php endif; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?>">Siguiente »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
