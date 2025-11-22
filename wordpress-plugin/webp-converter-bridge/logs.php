<?php
/**
 * Project: WebP Converter Bridge
 * Author: Christian Aguirre
 * Date: 2025-11-21
 */
/**
 * WebP Converter Bridge - Logs Viewer
 * 
 * Muestra los logs de conversión desde WordPress
 * Este archivo es incluido por render_logs_page()
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get settings
$settings = get_option('wcb_settings', []);
$api_base = $settings['api_base'] ?? '';
$api_token = $settings['api_token'] ?? '';

// Fetch logs from API
$logs = [];
$api_logs_url = rtrim($api_base, '/api.php') . '/webp-wordpress/logs-data.php';

$response = wp_remote_get($api_logs_url, [
    'headers' => [
        'X-API-Token' => $api_token
    ],
    'timeout' => 15
]);

if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (isset($data['logs'])) {
        $logs = $data['logs'];
    }
}

// Get local WordPress debug log
$wp_log_file = WP_CONTENT_DIR . '/debug.log';
$wp_logs = [];

if (file_exists($wp_log_file)) {
    $lines = file($wp_log_file);
    $wcb_lines = array_filter($lines, function($line) {
        return strpos($line, 'WCB:') !== false || strpos($line, 'WCB ') !== false;
    });
    
    // Get last 100 lines
    $wp_logs = array_slice(array_reverse($wcb_lines), 0, 100);
}

get_header('admin');
?>

<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        background: #f0f0f1;
        margin: 0;
        padding: 20px;
    }
    
    .wcb-logs-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .wcb-header {
        background: white;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #2271b1;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .wcb-header h1 {
        margin: 0 0 10px 0;
        font-size: 24px;
    }
    
    .wcb-tabs {
        background: white;
        padding: 0;
        margin-bottom: 20px;
        border-bottom: 1px solid #c3c4c7;
    }
    
    .wcb-tabs button {
        background: none;
        border: none;
        padding: 15px 20px;
        cursor: pointer;
        font-size: 14px;
        border-bottom: 2px solid transparent;
    }
    
    .wcb-tabs button.active {
        border-bottom-color: #2271b1;
        color: #2271b1;
        font-weight: 600;
    }
    
    .wcb-tab-content {
        display: none;
        background: white;
        padding: 20px;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .wcb-tab-content.active {
        display: block;
    }
    
    .wcb-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .wcb-stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
    }
    
    .wcb-stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 14px;
        opacity: 0.9;
    }
    
    .wcb-stat-card .value {
        font-size: 32px;
        font-weight: 700;
    }
    
    .wcb-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .wcb-table th {
        background: #f6f7f7;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #c3c4c7;
    }
    
    .wcb-table td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f1;
    }
    
    .wcb-table tr:hover {
        background: #f6f7f7;
    }
    
    .status-success {
        color: #00a32a;
        font-weight: 600;
    }
    
    .status-failed {
        color: #d63638;
        font-weight: 600;
    }
    
    .wcb-log-entry {
        padding: 10px;
        margin-bottom: 5px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        background: #f6f7f7;
        border-left: 3px solid #666;
    }
    
    .wcb-log-entry.error {
        border-left-color: #d63638;
        background: #fee;
    }
    
    .wcb-log-entry.success {
        border-left-color: #00a32a;
    }
    
    .wcb-empty {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    .wcb-refresh {
        background: #2271b1;
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 4px;
        margin-bottom: 15px;
    }
    
    .wcb-refresh:hover {
        background: #135e96;
    }
</style>

<div class="wcb-logs-container">
    <div class="wcb-header">
        <h1>📊 WebP Converter - Logs y Estadísticas</h1>
        <p>Visualiza en tiempo real las conversiones y diagnósticos del sistema</p>
    </div>
    
    <div class="wcb-stats">
        <div class="wcb-stat-card">
            <h3>Conversiones del API</h3>
            <div class="value"><?php echo count($logs); ?></div>
        </div>
        <div class="wcb-stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <h3>Logs de WordPress</h3>
            <div class="value"><?php echo count($wp_logs); ?></div>
        </div>
        <div class="wcb-stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <h3>Estado del API</h3>
            <div class="value"><?php echo !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200 ? '✓ Online' : '✗ Offline'; ?></div>
        </div>
    </div>
    
    <div class="wcb-tabs">
        <button class="wcb-tab-btn active" data-tab="api-logs">Logs del API</button>
        <button class="wcb-tab-btn" data-tab="wp-logs">Logs de WordPress</button>
        <button class="wcb-tab-btn" data-tab="diagnostics">Diagnósticos</button>
    </div>
    
    <!-- API Logs Tab -->
    <div class="wcb-tab-content active" id="api-logs">
        <button class="wcb-refresh" onclick="location.reload()">🔄 Refrescar</button>
        
        <?php if (empty($logs)): ?>
            <div class="wcb-empty">
                <h3>No hay logs disponibles</h3>
                <p>Esto puede significar:</p>
                <ul style="text-align: left; display: inline-block;">
                    <li>No se han realizado conversiones aún</li>
                    <li>El token es incorrecto (es un token "master" en lugar de cliente)</li>
                    <li>El API no está registrando las conversiones correctamente</li>
                </ul>
            </div>
        <?php else: ?>
            <table class="wcb-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Archivo</th>
                        <th>Tamaño Original</th>
                        <th>Tamaño WebP</th>
                        <th>Ahorro</th>
                        <th>Costo</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['id'] ?? '—'); ?></td>
                            <td><?php echo esc_html($log['filename'] ?? '—'); ?></td>
                            <td><?php echo esc_html(size_format($log['original_size'] ?? 0)); ?></td>
                            <td><?php echo esc_html(size_format($log['webp_size'] ?? 0)); ?></td>
                            <td><?php echo esc_html(round($log['savings_percent'] ?? 0, 2)); ?>%</td>
                            <td>$<?php echo esc_html(number_format($log['cost'] ?? 0, 2)); ?></td>
                            <td class="status-<?php echo esc_attr($log['status'] ?? 'unknown'); ?>">
                                <?php echo esc_html(strtoupper($log['status'] ?? 'UNKNOWN')); ?>
                            </td>
                            <td><?php echo esc_html($log['created_at'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- WordPress Logs Tab -->
    <div class="wcb-tab-content" id="wp-logs">
        <button class="wcb-refresh" onclick="location.reload()">🔄 Refrescar</button>
        
        <?php if (empty($wp_logs)): ?>
            <div class="wcb-empty">
                <h3>No hay logs de WordPress</h3>
                <p>Activa WP_DEBUG_LOG en wp-config.php para ver los logs:</p>
                <pre style="text-align: left; display: inline-block; background: #f6f7f7; padding: 15px;">
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>
            </div>
        <?php else: ?>
            <div style="max-height: 600px; overflow-y: auto;">
                <?php foreach ($wp_logs as $line): ?>
                    <?php
                    $class = '';
                    if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
                        $class = 'error';
                    } elseif (stripos($line, 'success') !== false || stripos($line, 'finished') !== false) {
                        $class = 'success';
                    }
                    ?>
                    <div class="wcb-log-entry <?php echo $class; ?>">
                        <?php echo esc_html($line); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Diagnostics Tab -->
    <div class="wcb-tab-content" id="diagnostics">
        <h2>Información del Sistema</h2>
        
        <table class="wcb-table">
            <tr>
                <th>Configuración</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>URL del API</td>
                <td><code><?php echo esc_html($api_base); ?></code></td>
            </tr>
            <tr>
                <td>Token</td>
                <td><code><?php echo esc_html(substr($api_token, 0, 20) . '...'); ?></code></td>
            </tr>
            <tr>
                <td>Versión del Plugin</td>
                <td><?php echo defined('WCB_PLUGIN_VERSION') ? WCB_PLUGIN_VERSION : '1.0.1'; ?></td>
            </tr>
            <tr>
                <td>PHP Memory Limit</td>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
            <tr>
                <td>PHP Max Execution Time</td>
                <td><?php echo ini_get('max_execution_time'); ?>s</td>
            </tr>
            <tr>
                <td>WP_DEBUG</td>
                <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? '✓ Activo' : '✗ Inactivo'; ?></td>
            </tr>
            <tr>
                <td>WP_DEBUG_LOG</td>
                <td><?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '✓ Activo' : '✗ Inactivo'; ?></td>
            </tr>
        </table>
        
        <h3 style="margin-top: 30px;">Test de Conectividad</h3>
        <button class="wcb-refresh" onclick="testConnection()">🔍 Probar Conexión Ahora</button>
        <div id="test-result" style="margin-top: 15px; padding: 15px; background: #f6f7f7; display: none;"></div>
    </div>
</div>

<script>
// Tab switching
document.querySelectorAll('.wcb-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Remove active from all
        document.querySelectorAll('.wcb-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.wcb-tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active to clicked
        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
    });
});

// Connection test
function testConnection() {
    const resultDiv = document.getElementById('test-result');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<p>🔄 Probando conexión...</p>';
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'wcb_test_connection',
            nonce: '<?php echo wp_create_nonce('wcb_test_connection'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const body = JSON.parse(data.data.body);
            resultDiv.innerHTML = `
                <h4 style="color: #00a32a;">✓ Conexión Exitosa</h4>
                <pre style="background: white; padding: 15px; overflow-x: auto;">${JSON.stringify(body, null, 2)}</pre>
            `;
        } else {
            resultDiv.innerHTML = `<h4 style="color: #d63638;">✗ Error: ${data.data.message}</h4>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<h4 style="color: #d63638;">✗ Error de Red: ${error}</h4>`;
    });
}

// Auto-refresh every 30 seconds if on API logs tab
setInterval(function() {
    if (document.getElementById('api-logs').classList.contains('active')) {
        location.reload();
    }
}, 30000);
</script>

<?php
wp_footer();
?>
