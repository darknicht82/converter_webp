<?php
/**
 * Project: WebP Converter Bridge
 * Author: Christian Aguirre
 * Date: 2025-11-21
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin UI y settings del plugin WebP Converter Bridge.
 */
class WebP_Converter_Bridge_Admin
{
    const OPTION_GROUP = 'wcb_settings_group';
    const OPTION_NAME = 'wcb_settings';

    /**
     * Inicializa hooks.
     *
     * @return void
     */
    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_wcb_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_wcb_rewrite_rules', [$this, 'ajax_rewrite_rules']);
        add_action('wp_ajax_wcb_scan_images', [$this, 'ajax_scan_images']);
        add_action('wp_ajax_wcb_bulk_convert', [$this, 'ajax_bulk_convert']);
        add_action('wp_ajax_wcb_get_files', [$this, 'ajax_get_files']);
        add_action('wp_ajax_wcb_restore_backup', [$this, 'ajax_restore_backup']);
        add_action('wp_ajax_wcb_delete_backup', [$this, 'ajax_delete_backup']);
        add_action('wp_ajax_wcb_delete_file', [$this, 'ajax_delete_file']);
    }

    /**
     * Registra el menú en el panel de administración.
     *
     * @return void
     */
    public function register_menu(): void
    {
        add_menu_page(
            __('Conversor WebP', 'webp-converter-bridge'),
            __('Conversor WebP', 'webp-converter-bridge'),
            'manage_options',
            'webp-converter-bridge',
            [$this, 'render_settings_page'],
            'dashicons-format-image',
            56
        );
        
        add_submenu_page(
            'webp-converter-bridge',
            __('Reportes y Estadísticas', 'webp-converter-bridge'),
            __('📊 Reportes', 'webp-converter-bridge'),
            'manage_options',
            'webp-converter-report',
            [$this, 'render_report_page']
        );
    }

    /**
     * Define la estructura de opciones.
     *
     * @return void
     */
    public function register_settings(): void
    {
        register_setting(self::OPTION_GROUP, self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default' => [
                'api_base' => WCB_DEFAULT_API_BASE,
                'api_token' => WCB_DEFAULT_API_TOKEN,
                'cost_per_image' => '0.00',
                'delivery_method' => 'picture',
                'webp_quality' => '80'
            ]
        ]);

        add_settings_section(
            'wcb_section_system',
            __('Estado del Sistema y Límites', 'webp-converter-bridge'),
            [$this, 'render_system_section'],
            self::OPTION_NAME
        );

        add_settings_field(
            'force_limits',
            __('Forzar Límites de Recursos', 'webp-converter-bridge'),
            [$this, 'render_force_limits_field'],
            self::OPTION_NAME,
            'wcb_section_system'
        );

        add_settings_section(
            'wcb_section_connection',
            __('Conexión con el Servicio WebP', 'webp-converter-bridge'),
            function () {
                echo '<p>' . esc_html__('Configura la URL del servicio y el token proporcionado.', 'webp-converter-bridge') . '</p>';
            },
            self::OPTION_NAME
        );

        add_settings_field(
            'api_base',
            __('URL del Servicio', 'webp-converter-bridge'),
            [$this, 'render_api_base_field'],
            self::OPTION_NAME,
            'wcb_section_connection'
        );

        add_settings_field(
            'api_token',
            __('Token de API', 'webp-converter-bridge'),
            [$this, 'render_api_token_field'],
            self::OPTION_NAME,
            'wcb_section_connection'
        );

        add_settings_section(
            'wcb_section_conversion',
            __('Configuración de Conversión', 'webp-converter-bridge'),
            function () {
                echo '<p>' . esc_html__('Ajustes de calidad y procesamiento de imágenes.', 'webp-converter-bridge') . '</p>';
            },
            self::OPTION_NAME
        );

        add_settings_field(
            'webp_quality',
            __('Calidad WebP (1-100)', 'webp-converter-bridge'),
            [$this, 'render_webp_quality_field'],
            self::OPTION_NAME,
            'wcb_section_conversion'
        );

        add_settings_section(
            'wcb_section_cost',
            __('Costos y Métricas', 'webp-converter-bridge'),
            function () {
                echo '<p>' . esc_html__('Define el costo unitario por imagen para reportes y facturación.', 'webp-converter-bridge') . '</p>';
            },
            self::OPTION_NAME
        );

        add_settings_field(
            'cost_per_image',
            __('Costo por imagen (USD)', 'webp-converter-bridge'),
            [$this, 'render_cost_field'],
            self::OPTION_NAME,
            'wcb_section_cost'
        );

        add_settings_section(
            'wcb_section_delivery',
            __('Entrega de Imágenes', 'webp-converter-bridge'),
            function () {
                echo '<p>' . esc_html__('Cómo se servirán las imágenes WebP a los visitantes.', 'webp-converter-bridge') . '</p>';
            },
            self::OPTION_NAME
        );

        add_settings_field(
            'delivery_method',
            __('Método de Entrega', 'webp-converter-bridge'),
            [$this, 'render_delivery_method_field'],
            self::OPTION_NAME,
            'wcb_section_delivery'
        );

        add_settings_field(
            'rewrite_rules',
            __('Reglas de Reescritura', 'webp-converter-bridge'),
            [$this, 'render_rewrite_rules_field'],
            self::OPTION_NAME,
            'wcb_section_delivery'
        );
    }

    /**
     * Encola estilos para la página de ajustes.
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_assets(string $hook): void
    {
        if ($hook !== 'toplevel_page_webp-converter-bridge' && $hook !== 'conversor-webp_page_webp-converter-logs') {
            return;
        }

        wp_enqueue_style(
            'wcb-admin',
            WCB_PLUGIN_URL . 'assets/admin.css',
            [],
            WCB_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'wcb-admin',
            WCB_PLUGIN_URL . 'assets/admin.js',
            ['jquery'],
            WCB_PLUGIN_VERSION,
            true
        );

        wp_localize_script('wcb-admin', 'wcbAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcb_test_connection'),
            'fileNonce' => wp_create_nonce('wcb_file_manager')
        ]);

        wp_localize_script('wcb-admin', 'wcbRewrite', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcb_rewrite_rules')
        ]);

        wp_localize_script('wcb-admin', 'wcbBulk', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcb_bulk_action')
        ]);
    }

    /**
     * Devuelve las opciones guardadas.
     *
     * @return array
     */
    private function get_settings(): array
    {
        $defaults = [
            'api_base' => WCB_DEFAULT_API_BASE,
            'api_token' => WCB_DEFAULT_API_TOKEN,
            'cost_per_image' => '0.00',
            'delivery_method' => 'picture',
            'webp_quality' => '85'
        ];

        $settings = get_option(self::OPTION_NAME, $defaults);
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Renderiza la sección de estado del sistema.
     */
    public function render_system_section(): void
    {
        $memory_limit = ini_get('memory_limit');
        $time_limit = ini_get('max_execution_time');
        $uploads = wp_is_writable(wp_upload_dir()['basedir']) ? 'Escribible' : 'No escribible';
        
        echo '<div class="wcb-system-status" style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-left: 4px solid #72aee6; margin-bottom: 20px;">';
        echo '<p>' . esc_html__('Diagnóstico rápido de tu servidor:', 'webp-converter-bridge') . '</p>';
        echo '<ul style="list-style: disc; margin-left: 20px; margin-top: 5px;">';
        echo '<li><strong>Límite de Memoria PHP:</strong> ' . esc_html($memory_limit) . ' ' . ($this->return_bytes($memory_limit) < 268435456 ? '<span style="color: #d63638;">(Recomendado: 256M+)</span>' : '<span style="color: #00a32a;">(OK)</span>') . '</li>';
        echo '<li><strong>Tiempo de Ejecución:</strong> ' . esc_html($time_limit) . 's ' . ($time_limit < 60 && $time_limit != 0 ? '<span style="color: #d63638;">(Recomendado: 60s+)</span>' : '<span style="color: #00a32a;">(OK)</span>') . '</li>';
        echo '<li><strong>Permisos de Uploads:</strong> ' . esc_html($uploads) . '</li>';
        echo '</ul>';
        echo '<p class="description">' . esc_html__('Si experimentas errores 500 o tiempos de espera, activa la opción de abajo para intentar anular estos límites durante la conversión.', 'webp-converter-bridge') . '</p>';
        echo '</div>';
    }

    /**
     * Campo para forzar límites.
     */
    public function render_force_limits_field(): void
    {
        $settings = $this->get_settings();
        $force = isset($settings['force_limits']) ? $settings['force_limits'] : '0';
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[force_limits]" value="1" <?php checked($force, '1'); ?>>
            <?php esc_html_e('Intentar aumentar memoria y tiempo de ejecución dinámicamente (Recomendado si hay errores).', 'webp-converter-bridge'); ?>
        </label>
        <?php
    }

    /**
     * Helper para convertir notación shorthand de PHP a bytes.
     */
    private function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }

    /**
     * Sanitiza las opciones antes de guardarlas.
     *
     * @param array $settings
     * @return array
     */
    public function sanitize_settings($settings): array
    {
        $settings = is_array($settings) ? $settings : [];

        $settings['api_base'] = isset($settings['api_base'])
            ? esc_url_raw($settings['api_base'])
            : WCB_DEFAULT_API_BASE;

        // Sanitize token - allow empty, don't use placeholder as fallback
        if (isset($settings['api_token'])) {
            $token = sanitize_text_field($settings['api_token']);
            // If token is the placeholder, treat it as empty
            $settings['api_token'] = ($token === '{{API_TOKEN}}') ? '' : $token;
        } else {
            $settings['api_token'] = '';
        }

        $settings['cost_per_image'] = isset($settings['cost_per_image'])
            ? number_format((float)$settings['cost_per_image'], 2, '.', '')
            : '0.00';

        $settings['delivery_method'] = isset($settings['delivery_method']) && in_array($settings['delivery_method'], ['rewrite', 'picture'])
            ? $settings['delivery_method']
            : 'picture';

        $quality = isset($settings['webp_quality']) ? (int)$settings['webp_quality'] : 85;
        $settings['webp_quality'] = max(1, min(100, $quality));

        $settings['force_limits'] = isset($settings['force_limits']) ? '1' : '0';

        return $settings;
    }

    /**
     * Campo URL API.
     *
     * @return void
     */
    public function render_api_base_field(): void
    {
        $settings = $this->get_settings();
        printf(
            '<input type="url" id="wcb_api_base" name="%1$s[api_base]" value="%2$s" class="regular-text code" placeholder="https://tu-dominio.com/api.php" required />',
            esc_attr(self::OPTION_NAME),
            esc_attr($settings['api_base'])
        );
    }

    /**
     * Campo token API.
     *
     * @return void
     */
    public function render_api_token_field(): void
    {
        $settings = $this->get_settings();
        printf(
            '<input type="text" id="wcb_api_token" name="%1$s[api_token]" value="%2$s" class="regular-text code" autocomplete="off" placeholder="Ingresa tu token de API" />',
            esc_attr(self::OPTION_NAME),
            esc_attr($settings['api_token'])
        );
        echo '<p class="description">' . esc_html__('Este token debe coincidir con el generado en el panel del servicio WebP.', 'webp-converter-bridge') . '</p>';
    }

    /**
     * Campo calidad WebP.
     *
     * @return void
     */
    public function render_webp_quality_field(): void
    {
        $settings = $this->get_settings();
        $quality = $settings['webp_quality'] ?? '80';
        printf(
            '<input type="number" min="1" max="100" id="wcb_webp_quality" name="%1$s[webp_quality]" value="%2$s" class="small-text" />',
            esc_attr(self::OPTION_NAME),
            esc_attr($quality)
        );
        echo '<p class="description">' . esc_html__('Define la calidad de compresión. 80 es un buen balance entre calidad y peso.', 'webp-converter-bridge') . '</p>';
    }


    /**
     * Campo método de entrega.
     *
     * @return void
     */
    public function render_delivery_method_field(): void
    {
        $settings = $this->get_settings();
        $method = $settings['delivery_method'] ?? 'picture';
        ?>
        <fieldset>
            <label>
                <input type="radio" name="<?php echo esc_attr(self::OPTION_NAME); ?>[delivery_method]" value="rewrite" <?php checked($method, 'rewrite'); ?>>
                <?php esc_html_e('Nativo (.htaccess Rewrite Rules)', 'webp-converter-bridge'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('Modifica el archivo .htaccess para servir WebP automáticamente si el navegador lo soporta. No cambia el HTML.', 'webp-converter-bridge'); ?>
            </p>
            <br>
            <label>
                <input type="radio" name="<?php echo esc_attr(self::OPTION_NAME); ?>[delivery_method]" value="picture" <?php checked($method, 'picture'); ?>>
                <?php esc_html_e('Etiqueta <picture> (JavaScript-free)', 'webp-converter-bridge'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('Reemplaza las etiquetas <img> por <picture> en el contenido HTML.', 'webp-converter-bridge'); ?>
            </p>
        </fieldset>
        <?php
    }

    /**
     * Campo reglas rewrite.
     *
     * @return void
     */
    public function render_rewrite_rules_field(): void
    {
        $htaccess_path = ABSPATH . '.htaccess';
        $content = file_exists($htaccess_path) ? file_get_contents($htaccess_path) : '';
        $marker_start = '# BEGIN WebP Converter Bridge';
        $has_rules = strpos($content, $marker_start) !== false;

        ?>
        <div id="wcb-rewrite-controls">
            <?php if ($has_rules): ?>
                <p style="color: green; font-weight: bold; margin-bottom: 10px;">
                    <span class="dashicons dashicons-yes"></span> <?php esc_html_e('Las reglas .htaccess están activas.', 'webp-converter-bridge'); ?>
                </p>
                <button type="button" class="button" id="wcb-insert-rewrite" disabled>
                    <?php esc_html_e('Insertar Reglas en .htaccess', 'webp-converter-bridge'); ?>
                </button>
                <button type="button" class="button" id="wcb-remove-rewrite" style="color: #a00;">
                    <?php esc_html_e('Eliminar Reglas', 'webp-converter-bridge'); ?>
                </button>
            <?php else: ?>
                <p style="color: #666; margin-bottom: 10px;">
                    <?php esc_html_e('Las reglas no están presentes en .htaccess.', 'webp-converter-bridge'); ?>
                </p>
                <button type="button" class="button button-secondary" id="wcb-insert-rewrite">
                    <?php esc_html_e('Insertar Reglas en .htaccess', 'webp-converter-bridge'); ?>
                </button>
                <button type="button" class="button" id="wcb-remove-rewrite" disabled style="color: #a00; opacity: 0.5;">
                    <?php esc_html_e('Eliminar Reglas', 'webp-converter-bridge'); ?>
                </button>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 15px; background: #f0f0f1; padding: 10px; border: 1px solid #c3c4c7; border-radius: 4px;">
            <p style="margin-top: 0;"><strong><?php esc_html_e('Código a insertar:', 'webp-converter-bridge'); ?></strong></p>
            <code style="display: block; white-space: pre-wrap; font-size: 12px;">
# BEGIN WebP Converter Bridge
&lt;IfModule mod_rewrite.c&gt;
RewriteEngine On
RewriteCond %{HTTP_ACCEPT} image/webp
RewriteCond %{DOCUMENT_ROOT}/$1.webp -f
RewriteRule ^(.*?)\.(jpg|jpeg|png)$ $1.webp [T=image/webp,E=accept:1]
&lt;/IfModule&gt;
&lt;IfModule mod_headers.c&gt;
Header append Vary Accept env=REDIRECT_accept
&lt;/IfModule&gt;
AddType image/webp .webp
# END WebP Converter Bridge
            </code>
        </div>

        <p class="description" id="wcb-rewrite-status" style="margin-top: 5px;"></p>
        <?php
    }

    /**
     * Campo costo por imagen.
     *
     * @return void
     */
    public function render_cost_field(): void
    {
        $settings = $this->get_settings();
        $cost = $settings['cost_per_image'] ?? '0.00';
        printf(
            '<input type="text" id="wcb_cost_per_image" name="%1$s[cost_per_image]" value="%2$s" class="small-text" readonly />',
            esc_attr(self::OPTION_NAME),
            esc_attr($cost)
        );
        echo '<p class="description">' . esc_html__('Este valor se actualiza automáticamente al probar la conexión.', 'webp-converter-bridge') . '</p>';
    }

    /**
     * Renderiza la página principal del plugin con pestañas.
     */
    public function render_settings_page(): void
    {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
        ?>
        <div class="wrap wcb-admin">
            <h1><?php esc_html_e('WebP Converter Bridge', 'webp-converter-bridge'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=webp-converter-bridge&tab=dashboard" class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Dashboard', 'webp-converter-bridge'); ?>
                </a>
                <a href="?page=webp-converter-bridge&tab=conversion" class="nav-tab <?php echo $active_tab == 'conversion' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Conversión', 'webp-converter-bridge'); ?>
                </a>
                <a href="?page=webp-converter-bridge&tab=files" class="nav-tab <?php echo $active_tab == 'files' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Gestor de Archivos', 'webp-converter-bridge'); ?>
                </a>
                <a href="?page=webp-converter-bridge&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Configuración', 'webp-converter-bridge'); ?>
                </a>
            </h2>

            <div class="wcb-tab-content">
                <?php
                switch ($active_tab) {
                    case 'conversion':
                        $this->render_tab_conversion();
                        break;
                    case 'files':
                        $this->render_tab_files();
                        break;
                    case 'settings':
                        $this->render_tab_settings();
                        break;
                    case 'dashboard':
                    default:
                        $this->render_tab_dashboard();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_tab_dashboard(): void {
        echo '<div class="wcb-panel"><h2>Resumen General</h2><p>Próximamente: Gráficos y estadísticas.</p></div>';
    }

    private function render_tab_conversion(): void {
        ?>
        <div class="wcb-panel wcb-bulk-panel">
            <h2><?php esc_html_e('Conversión Masiva', 'webp-converter-bridge'); ?></h2>
            <p><?php esc_html_e('Escanea tu biblioteca de medios y convierte las imágenes existentes a WebP.', 'webp-converter-bridge'); ?></p>
            
            <div class="wcb-bulk-controls">
                <button type="button" class="button" id="wcb-scan-images">
                    <?php esc_html_e('Escanear Imágenes', 'webp-converter-bridge'); ?>
                </button>
                <span id="wcb-scan-status" style="margin-left: 10px; font-weight: bold;"></span>
            </div>

            <div id="wcb-bulk-progress-area" style="display: none; margin-top: 15px;">
                <div class="wcb-progress-bar-wrapper" style="background: #f0f0f1; border-radius: 4px; height: 20px; overflow: hidden; border: 1px solid #c3c4c7;">
                    <div id="wcb-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
                </div>
                <p>
                    <span id="wcb-progress-text">0%</span> 
                    (<span id="wcb-progress-count">0/0</span>)
                </p>
                <button type="button" class="button button-primary" id="wcb-start-bulk">
                    <?php esc_html_e('Iniciar Conversión', 'webp-converter-bridge'); ?>
                </button>
                <button type="button" class="button" id="wcb-stop-bulk" disabled>
                    <?php esc_html_e('Detener', 'webp-converter-bridge'); ?>
                </button>
                
                <!-- Health Stats -->
                <div id="wcb-health-stats" style="margin-top: 15px; display: flex; gap: 20px; font-size: 13px; background: #fff; padding: 10px; border: 1px solid #ddd;">
                    <div><strong>Memoria:</strong> <span id="wcb-stat-memory">-</span></div>
                    <div><strong>Tiempo Lote:</strong> <span id="wcb-stat-time">-</span></div>
                    <div><strong>Estado:</strong> <span id="wcb-stat-status" style="color: green;">Esperando...</span></div>
                </div>
            </div>
            
            <div id="wcb-bulk-log" style="margin-top: 15px; max-height: 150px; overflow-y: auto; background: #fff; border: 1px solid #ddd; padding: 10px; display: none;"></div>
        </div>
        <?php
    }

    private function render_tab_files(): void {
        ?>
        <div class="wcb-panel">
            <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="#" class="nav-tab nav-tab-active" data-type="webp"><?php esc_html_e('Imágenes WebP', 'webp-converter-bridge'); ?></a>
                <a href="#" class="nav-tab" data-type="backup"><?php esc_html_e('Backups (.original)', 'webp-converter-bridge'); ?></a>
            </h2>

            <div id="wcb-files-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="80"><?php esc_html_e('Vista', 'webp-converter-bridge'); ?></th>
                            <th><?php esc_html_e('Archivo', 'webp-converter-bridge'); ?></th>
                            <th><?php esc_html_e('Tamaño', 'webp-converter-bridge'); ?></th>
                            <th><?php esc_html_e('Fecha', 'webp-converter-bridge'); ?></th>
                            <th><?php esc_html_e('Acciones', 'webp-converter-bridge'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="wcb-files-list">
                        <tr><td colspan="5"><?php esc_html_e('Cargando...', 'webp-converter-bridge'); ?></td></tr>
                    </tbody>
                </table>
                
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="pagination-links"></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_tab_settings(): void {
        $settings = $this->get_settings();
        ?>
        <form method="post" action="options.php">
            <?php settings_fields(self::OPTION_GROUP); ?>
            
            <div class="wcb-panels">
                <!-- Panel 1: System Status -->
                <div class="wcb-panel">
                    <h2><?php esc_html_e('Estado del Sistema', 'webp-converter-bridge'); ?></h2>
                    <?php 
                    $this->render_system_section(); 
                    do_settings_fields(self::OPTION_NAME, 'wcb_section_system');
                    ?>
                </div>

                <!-- Panel 2: Connection -->
                <div class="wcb-panel">
                    <h2><?php esc_html_e('Conexión API', 'webp-converter-bridge'); ?></h2>
                    <p><?php esc_html_e('Configura la URL del servicio y el token proporcionado.', 'webp-converter-bridge'); ?></p>
                    <table class="form-table" role="presentation">
                        <?php do_settings_fields(self::OPTION_NAME, 'wcb_section_connection'); ?>
                    </table>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border: 1px solid #ccc; border-radius: 4px;">
                        <h3><?php esc_html_e('Prueba de Conexión', 'webp-converter-bridge'); ?></h3>
                        <p><?php esc_html_e('Verifica que la URL y el Token sean correctos antes de guardar.', 'webp-converter-bridge'); ?></p>
                        <button type="button" class="button button-secondary" id="wcb-test-connection">
                            <?php esc_html_e('Probar conexión ahora', 'webp-converter-bridge'); ?>
                        </button>
                        <div id="wcb-test-result" class="wcb-test-result" aria-live="polite" style="margin-top: 10px;"></div>
                    </div>
                </div>

                <!-- Panel 3: Conversion & Cost -->
                <div class="wcb-panel">
                    <h2><?php esc_html_e('Conversión y Costos', 'webp-converter-bridge'); ?></h2>
                    
                    <h3><?php esc_html_e('Configuración', 'webp-converter-bridge'); ?></h3>
                    <p><?php esc_html_e('Ajustes de calidad y procesamiento de imágenes.', 'webp-converter-bridge'); ?></p>
                    <table class="form-table" role="presentation">
                        <?php do_settings_fields(self::OPTION_NAME, 'wcb_section_conversion'); ?>
                    </table>
                    
                    <hr>
                    
                    <h3><?php esc_html_e('Costos', 'webp-converter-bridge'); ?></h3>
                    <p><?php esc_html_e('Define el costo unitario por imagen para reportes y facturación.', 'webp-converter-bridge'); ?></p>
                    <table class="form-table" role="presentation">
                        <?php do_settings_fields(self::OPTION_NAME, 'wcb_section_cost'); ?>
                    </table>
                </div>

                <!-- Panel 4: Delivery -->
                <div class="wcb-panel">
                    <h2><?php esc_html_e('Entrega de Imágenes', 'webp-converter-bridge'); ?></h2>
                    <p><?php esc_html_e('Cómo se servirán las imágenes WebP a los visitantes.', 'webp-converter-bridge'); ?></p>
                    <table class="form-table" role="presentation">
                        <?php do_settings_fields(self::OPTION_NAME, 'wcb_section_delivery'); ?>
                    </table>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <?php submit_button(__('Guardar todos los ajustes', 'webp-converter-bridge')); ?>
            </div>
        </form>
        <?php
    }


    /**
     * Maneja la prueba de conexión via AJAX.
     *
     * @return void
     */
    public function ajax_test_connection(): void
    {
        check_ajax_referer('wcb_test_connection', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permiso denegado.', 'webp-converter-bridge')], 403);
        }

        $settings = $this->get_settings();
        $apiBase = rtrim($settings['api_base'], '/');
        $token = $settings['api_token'];
        
        // Validar que haya token
        if (empty($token) || $token === '{{API_TOKEN}}') {
            wp_send_json_error([
                'message' => __('No se ha configurado un token válido.', 'webp-converter-bridge'),
                'details' => 'Por favor ingresa un token de API válido.'
            ], 400);
        }

        $response = wp_remote_get($apiBase . '?action=health', [
            'headers' => [
                'X-API-Token' => $token
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => __('No se pudo conectar con el servicio.', 'webp-converter-bridge'),
                'details' => $response->get_error_message()
            ], 500);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Validar código de respuesta
        if ($code !== 200) {
            $data = json_decode($body, true);
            $errorMsg = isset($data['error']) ? $data['error'] : 'Error desconocido';
            
            wp_send_json_error([
                'message' => sprintf(__('Error de conexión (HTTP %d)', 'webp-converter-bridge'), $code),
                'details' => $errorMsg
            ], $code);
        }
        
        // Si la conexión fue exitosa, extraer y guardar el cost_per_image
        $data = json_decode($body, true);
        
        // Verificar que la respuesta sea válida
        if (!isset($data['success']) || $data['success'] !== true) {
            wp_send_json_error([
                'message' => __('Respuesta inválida del servidor.', 'webp-converter-bridge'),
                'details' => 'El servidor no retornó una respuesta válida.'
            ], 500);
        }
        
        // Verificar si el API devolvió el costo
        if (isset($data['client']) && isset($data['client']['cost_per_image'])) {
            $cost = $data['client']['cost_per_image'];
            
            // Actualizar automáticamente las settings
            $settings['cost_per_image'] = number_format((float)$cost, 2, '.', '');
            update_option(self::OPTION_NAME, $settings);
            
            // Log para debugging
            error_log('WCB: Cost per image updated to ' . $cost . ' from API');
        }

        wp_send_json_success([
            'message' => __('Conexión exitosa con el servidor WebP.', 'webp-converter-bridge'),
            'body' => $body,
            'client_name' => $data['client']['client_name'] ?? 'Desconocido'
        ]);
    }

    /**
     * Maneja la inserción/borrado de reglas .htaccess.
     */
    public function ajax_rewrite_rules(): void
    {
        check_ajax_referer('wcb_rewrite_rules', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado.'], 403);
        }

        $action = $_POST['todo'] ?? '';
        $htaccess_path = ABSPATH . '.htaccess';

        if (file_exists($htaccess_path)) {
            if (!is_writable($htaccess_path)) {
                wp_send_json_error(['message' => 'El archivo .htaccess existe pero no es escribible. Verifica los permisos.'], 500);
            }
            $content = file_get_contents($htaccess_path);
        } else {
            if (!is_writable(ABSPATH)) {
                wp_send_json_error(['message' => 'No se puede crear el archivo .htaccess. El directorio raíz no es escribible.'], 500);
            }
            $content = '';
        }
        $marker_start = '# BEGIN WebP Converter Bridge';
        $marker_end = '# END WebP Converter Bridge';

        // Limpiar reglas existentes
        $pattern = '/' . preg_quote($marker_start, '/') . '.*?' . preg_quote($marker_end, '/') . '\s*/s';
        $content = preg_replace($pattern, '', $content);

        if ($action === 'insert') {
            $rules = [
                $marker_start,
                '<IfModule mod_rewrite.c>',
                'RewriteEngine On',
                'RewriteCond %{HTTP_ACCEPT} image/webp',
                'RewriteCond %{DOCUMENT_ROOT}/$1.webp -f',
                'RewriteRule ^(.*?)\.(jpg|jpeg|png)$ $1.webp [T=image/webp,E=accept:1]',
                '</IfModule>',
                '<IfModule mod_headers.c>',
                'Header append Vary Accept env=REDIRECT_accept',
                '</IfModule>',
                'AddType image/webp .webp',
                $marker_end
            ];
            
            $content = implode("\n", $rules) . "\n\n" . $content;
            $message = 'Reglas insertadas correctamente.';
        } else {
            $message = 'Reglas eliminadas correctamente.';
        }

        if (file_put_contents($htaccess_path, $content) === false) {
            wp_send_json_error(['message' => 'Error al escribir en .htaccess.'], 500);
        }

        wp_send_json_success(['message' => $message]);
    }

    /**
    * Renderiza la página de reportes.
    *
    * @return void
    */
    public function render_report_page(): void
    {
        require_once WCB_PLUGIN_DIR . 'report.php';
    }

    /**
     * AJAX: Get files for File Manager
     */
    public function ajax_get_files(): void
    {
        check_ajax_referer('wcb_file_manager', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado.'], 403);
        }

        $type = $_POST['type'] ?? 'webp'; // 'webp' or 'backup'
        $page = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $per_page,
            'offset'         => $offset,
            'fields'         => 'ids',
        ];

        if ($type === 'webp') {
            $args['post_mime_type'] = 'image/webp';
        } else {
            // For backups, we look for attachments that have the backup meta key
            $args['meta_query'] = [
                [
                    'key'     => '_wcb_original_file',
                    'compare' => 'EXISTS',
                ]
            ];
        }

        $query = new WP_Query($args);
        $files = [];

        foreach ($query->posts as $id) {
            $meta = wp_get_attachment_metadata($id);
            $file_path = get_attached_file($id);
            $thumb = wp_get_attachment_image_src($id, 'thumbnail');
            
            $item = [
                'id' => $id,
                'filename' => basename($file_path),
                'url' => wp_get_attachment_url($id),
                'thumbnail' => $thumb ? $thumb[0] : '',
                'size' => size_format(filesize($file_path)),
                'date' => get_the_date('Y-m-d H:i', $id)
            ];

            if ($type === 'backup') {
                $backup_path = get_post_meta($id, '_wcb_original_file', true);
                $item['backup_exists'] = file_exists($backup_path);
                $item['backup_size'] = file_exists($backup_path) ? size_format(filesize($backup_path)) : 'N/A';
            }

            $files[] = $item;
        }

        wp_send_json_success([
            'files' => $files,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page
        ]);
    }

    /**
     * AJAX: Restore original file from backup.
     */
    public function ajax_restore_backup(): void
    {
        check_ajax_referer('wcb_file_manager', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado.'], 403);
        }

        // Prevent timeouts for large files
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        ignore_user_abort(true);

        $id = intval($_POST['id']);
        $backup_path = get_post_meta($id, '_wcb_original_file', true);
        $current_path = get_attached_file($id);

        // Fallback: If stored path doesn't exist, check in the same directory as current file
        if ((!$backup_path || !file_exists($backup_path)) && $current_path) {
            $potential_backup = dirname($current_path) . '/' . basename($backup_path);
            if (file_exists($potential_backup)) {
                $backup_path = $potential_backup;
                // Update meta to fix it for future
                update_post_meta($id, '_wcb_original_file', $backup_path);
            } else {
                // Try looking for .original of the current filename (if it was renamed)
                $potential_backup_2 = $current_path . '.original';
                if (file_exists($potential_backup_2)) {
                    $backup_path = $potential_backup_2;
                    update_post_meta($id, '_wcb_original_file', $backup_path);
                }
            }
        }

        if (!$backup_path || !file_exists($backup_path)) {
            $debug_path = $backup_path ? $backup_path : 'MetaData Empty';
            wp_send_json_error(['message' => 'No se encontró el archivo de respaldo en: ' . $debug_path]);
        }
        
        // Restore file
        // We need to restore it to the original filename (e.g. image.jpg).
        $original_filename = str_replace('.original', '', basename($backup_path));
        $dir = dirname($current_path);
        $restore_path = $dir . '/' . $original_filename;
        
        if (!copy($backup_path, $restore_path)) {
             wp_send_json_error(['message' => 'Error al restaurar el archivo (copy).']);
        }
        
        update_attached_file($id, $restore_path);
        
        // Restore Mime Type
        $file_info = wp_check_filetype($restore_path);
        if ($file_info['type']) {
            global $wpdb;
            $wpdb->update(
                $wpdb->posts,
                ['post_mime_type' => $file_info['type']],
                ['ID' => $id],
                ['%s'],
                ['%d']
            );
            clean_post_cache($id);
        }
        
        // Delete the WebP file if it's different and exists
        if ($current_path !== $restore_path && file_exists($current_path)) {
            @unlink($current_path);
        }

        // Cleanup: Delete the backup file and metadata since it's now restored
        if (file_exists($backup_path)) {
            @unlink($backup_path);
        }
        delete_post_meta($id, '_wcb_original_file');

        wp_send_json_success(['message' => 'Archivo restaurado correctamente.']);
    }

    /**
     * AJAX: Delete backup file.
     */
    public function ajax_delete_backup(): void
    {
        check_ajax_referer('wcb_file_manager', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado.'], 403);
        }

        $id = intval($_POST['id']);
        $backup_path = get_post_meta($id, '_wcb_original_file', true);
        $current_path = get_attached_file($id);

        // Fallback logic (same as restore)
        if ((!$backup_path || !file_exists($backup_path)) && $current_path) {
            $potential_backup = dirname($current_path) . '/' . basename($backup_path);
            if (file_exists($potential_backup)) {
                $backup_path = $potential_backup;
            } else {
                $potential_backup_2 = $current_path . '.original';
                if (file_exists($potential_backup_2)) {
                    $backup_path = $potential_backup_2;
                }
            }
        }

        if ($backup_path && file_exists($backup_path)) {
            unlink($backup_path);
        }

        delete_post_meta($id, '_wcb_original_file');

        wp_send_json_success(['message' => 'Backup eliminado.']);
    }

    /**
     * AJAX: Delete attachment (WebP).
     */
    public function ajax_delete_file(): void
    {
        check_ajax_referer('wcb_file_manager', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado.'], 403);
        }

        $id = intval($_POST['id']);
        
        // Use WordPress native delete
        if (wp_delete_attachment($id, true)) {
            wp_send_json_success(['message' => 'Archivo eliminado.']);
        } else {
            wp_send_json_error(['message' => 'Error al eliminar el archivo.'], 500);
        }
    }
    /**
     * AJAX: Scan for all image attachments.
     */
    public function ajax_scan_images(): void
    {
        check_ajax_referer('wcb_bulk_action', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado.'], 403);
        }

        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        $query = new WP_Query($args);
        $all_ids = $query->posts;
        
        // Check which ones already have WebP versions
        $pending_ids = [];
        $converted_ids = [];
        
        foreach ($all_ids as $id) {
            $file_path = get_attached_file($id);
            if (!$file_path) {
                continue;
            }
            
            // Check if WebP version exists
            $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);
            
            if (file_exists($webp_path)) {
                $converted_ids[] = $id;
            } else {
                $pending_ids[] = $id;
            }
        }

        wp_send_json_success([
            'count' => count($all_ids),
            'ids'   => $pending_ids, // Only return pending ones for conversion
            'converted_count' => count($converted_ids),
            'pending_count' => count($pending_ids),
            'converted_ids' => $converted_ids
        ]);
    }

    /**
     * AJAX: Process a batch of images.
     */
    public function ajax_bulk_convert(): void
    {
        // 1. Liberar sesión inmediatamente para evitar bloqueos (Deadlocks)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $settings = $this->get_settings();
        
        // 2. Aumentar límites agresivamente
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '1024M');
            @ini_set('max_execution_time', '600');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }
        
        // Prevent script termination by client disconnect
        ignore_user_abort(true);

        // Register shutdown function
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                if (ob_get_length()) ob_clean();
                $msg = 'Fatal Error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'];
                // Log fatal error using our custom logger if possible, or error_log fallback
                error_log('WCB Fatal: ' . $msg);
                wp_send_json_error(['message' => $msg], 500);
            }
        });

        check_ajax_referer('wcb_bulk_action', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado.'], 403);
        }

        $ids = isset($_POST['ids']) ? (array)$_POST['ids'] : [];
        if (empty($ids)) {
            wp_send_json_error(['message' => 'No se recibieron IDs.'], 400);
        }

        // We need to instantiate the Core class to use its logic
        if (!class_exists('WebP_Converter_Bridge_Converter')) {
            require_once WCB_PLUGIN_DIR . 'includes/class-wcb-converter.php';
        }

        $converter = new WebP_Converter_Bridge_Converter($settings['api_base'], $settings['api_token'], $settings);
        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($ids as $id) {
            $file_path = get_attached_file($id);
            $filename = basename($file_path);
            
            $this->log("Iniciando conversión: $filename (ID: $id)", 'INFO');

            try {
                if ($converter->convert_attachment($id)) {
                    $results['success']++;
                    $results['details'][] = ['id' => $id, 'filename' => $filename, 'status' => 'success'];
                    $this->log("Conversión exitosa: $filename", 'SUCCESS');
                } else {
                    $results['failed']++;
                    $results['details'][] = ['id' => $id, 'filename' => $filename, 'status' => 'failed'];
                    $this->log("Fallo conversión: $filename", 'ERROR');
                }
            } catch (Throwable $e) {
                $this->log("Excepción en $filename: " . $e->getMessage(), 'ERROR');
                $results['failed']++;
                $results['details'][] = ['id' => $id, 'filename' => $filename, 'status' => 'failed', 'error' => $e->getMessage()];
            }
            
            // Force memory cleanup
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        $results['metrics'] = [
            'memory' => size_format(memory_get_usage()),
            'memory_peak' => size_format(memory_get_peak_usage()),
            'time' => number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 2) . 's'
        ];

        wp_send_json_success($results);
    }
    

    /**
     * Escribe un mensaje en el log personalizado del plugin.
     * 
     * @param string $message El mensaje a registrar.
     * @param string $type Tipo de mensaje (INFO, ERROR, SUCCESS).
     */
    private function log(string $message, string $type = 'INFO'): void
    {
        $log_dir = wp_upload_dir()['basedir'] . '/wcb-logs';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            // Proteger directorio
            file_put_contents($log_dir . '/.htaccess', 'deny from all');
            file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
        }

        $log_file = $log_dir . '/conversion.log';
        $timestamp = current_time('mysql');
        $entry = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;

        // Rotación simple: si supera 5MB, reiniciar
        if (file_exists($log_file) && filesize($log_file) > 5 * 1024 * 1024) {
            rename($log_file, $log_file . '.' . time() . '.bak');
        }

        file_put_contents($log_file, $entry, FILE_APPEND);
    }
}
