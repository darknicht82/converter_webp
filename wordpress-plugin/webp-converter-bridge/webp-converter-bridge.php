<?php
/**
 * Project: WebP Converter Bridge
 * Author: Christian Aguirre
 * Date: 2025-11-21
 */
/**
 * Plugin Name: WebP Converter Bridge
 * Description: Conecta un sitio WordPress con el servicio Conversor WebP para optimizar imágenes y registrar métricas.
 * Version: 1.1.4
 * Author: Christian Aguirre
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

if (!defined('WCB_PLUGIN_VERSION')) {
    define('WCB_PLUGIN_VERSION', '1.1.4');
}
if (!defined('WCB_PLUGIN_DIR')) {
    define('WCB_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WCB_PLUGIN_URL')) {
    define('WCB_PLUGIN_URL', plugin_dir_url(__FILE__));
}

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

    // Initialize Converter Core
    $wcb_converter = new WebP_Converter_Bridge_Converter();
    $wcb_converter->init();
}
add_action('plugins_loaded', 'wcb_bootstrap');
