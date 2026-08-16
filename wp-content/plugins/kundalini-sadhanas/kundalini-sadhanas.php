<?php
/**
 * Plugin Name: Yoga Sadhanas
 * Description: Управление садханами, прогрессом, уведомлениями и письмами.
 * Version: 1.0.0
 * Author: AxeCode.Tech
 * Text Domain: kundalini-sadhanas
 */

if (!defined('ABSPATH')) {
	exit;
}

define('KUNDALINI_SADHANAS_VERSION', '1.0.0');
define('KUNDALINI_SADHANAS_FILE', __FILE__);
define('KUNDALINI_SADHANAS_PATH', plugin_dir_path(__FILE__));

require_once KUNDALINI_SADHANAS_PATH . 'includes/settings.php';
require_once KUNDALINI_SADHANAS_PATH . 'includes/core.php';
require_once KUNDALINI_SADHANAS_PATH . 'includes/ajax.php';
require_once KUNDALINI_SADHANAS_PATH . 'includes/admin.php';

function kundalini_sadhanas_activate(): void {
	yoga_sadhana_migrate_legacy_posts();
	if (!wp_next_scheduled(YOGA_SADHANA_CRON_HOOK)) {
		wp_schedule_event(time() + 300, 'hourly', YOGA_SADHANA_CRON_HOOK);
	}
}
register_activation_hook(__FILE__, 'kundalini_sadhanas_activate');

function kundalini_sadhanas_deactivate(): void {
	wp_clear_scheduled_hook(YOGA_SADHANA_CRON_HOOK);
}
register_deactivation_hook(__FILE__, 'kundalini_sadhanas_deactivate');
