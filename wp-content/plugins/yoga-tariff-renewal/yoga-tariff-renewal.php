<?php
/**
 * Plugin Name: Yoga Tariff Renewal
 * Description: Автопродление тарифов через ЮKassa (рекуррентные платежи без WooCommerce Subscriptions).
 * Version: 1.3.2
 * Author: AxeCode.Tech
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * Text Domain: yoga-tariff-renewal
 */

if (!defined('ABSPATH')) {
	exit;
}

define('YTR_VERSION', '1.3.2');
define('YTR_PLUGIN_FILE', __FILE__);
define('YTR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YTR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once YTR_PLUGIN_DIR . 'includes/class-ytr-plugin.php';

/**
 * @return YTR_Plugin
 */
function ytr_plugin(): YTR_Plugin {
	return YTR_Plugin::instance();
}

add_action('plugins_loaded', static function (): void {
	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', static function (): void {
			echo '<div class="notice notice-error"><p>Yoga Tariff Renewal требует WooCommerce.</p></div>';
		});
		return;
	}

	ytr_plugin()->init();
}, 20);

register_activation_hook(__FILE__, array('YTR_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('YTR_Plugin', 'deactivate'));
