<?php
/**
 * Project: WebP Converter Bridge
 * Author: Christian Aguirre
 * Date: 2025-11-21
 */
/**
 * Plugin Name: WebP Converter Bridge
 * Plugin URI: https://gsc-systems.local/webp
 * Description: Conecta un sitio WordPress con el servicio Conversor WebP para optimizar imágenes y registrar métricas.
 * Version: 1.0.1
 * Author: Christian Aguire
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * Text Domain: webp-converter-bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WCB_DEFAULT_API_BASE')) {
    define('WCB_DEFAULT_API_BASE', '{{API_BASE}}');
}

if (!defined('WCB_DEFAULT_API_TOKEN')) {
    define('WCB_DEFAULT_API_TOKEN', '{{API_TOKEN}}');
}

define('WCB_PLUGIN_VERSION', '1.0.1');
define('WCB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCB_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WCB_PLUGIN_DIR . 'includes/class-wcb-admin.php';
require_once WCB_PLUGIN_DIR . 'includes/class-wcb-converter.php';

/**
 * Inicializa el plugin.
 *
 * @return void
 */
function wcb_bootstrap(): void
{
    $admin = new WebP_Converter_Bridge_Admin();
    $admin->init();

    $core = new WebP_Converter_Bridge_Core();
    $core->init();
}
add_action('plugins_loaded', 'wcb_bootstrap');

