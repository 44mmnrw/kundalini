<?php
/**
 * Plugin Name: Yoga Mail
 * Description: Единый брендированный слой писем WordPress, WooCommerce и модулей Kundalini.
 * Version: 1.0.1
 * Author: AxeCode.Tech
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Text Domain: yoga-mail
 */

if (!defined('ABSPATH')) {
	exit;
}

define('YOGA_MAIL_VERSION', '1.0.1');
define('YOGA_MAIL_FILE', __FILE__);
define('YOGA_MAIL_PATH', plugin_dir_path(__FILE__));
define('YOGA_MAIL_URL', plugin_dir_url(__FILE__));

require_once YOGA_MAIL_PATH . 'includes/class-yoga-mail-registry.php';
require_once YOGA_MAIL_PATH . 'includes/class-yoga-mail-renderer.php';
require_once YOGA_MAIL_PATH . 'includes/class-yoga-mail-mailer.php';
require_once YOGA_MAIL_PATH . 'includes/class-yoga-mail-wordpress.php';
require_once YOGA_MAIL_PATH . 'includes/class-yoga-mail-woocommerce.php';
require_once YOGA_MAIL_PATH . 'includes/class-yoga-mail-admin.php';
require_once YOGA_MAIL_PATH . 'includes/class-yoga-mail-plugin.php';
require_once YOGA_MAIL_PATH . 'includes/functions.php';

register_activation_hook(__FILE__, array('Yoga_Mail_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('Yoga_Mail_Plugin', 'deactivate'));

Yoga_Mail_Plugin::instance()->init();
