<?php
/**
 * Модальное окно входа: восстановление пароля без страницы my-account/lost-password.
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('template_redirect', 'yoga_redirect_lost_password_endpoint_to_modal', 5);
function yoga_redirect_lost_password_endpoint_to_modal(): void {
	if (is_user_logged_in()) {
		return;
	}

	$is_lost_password = false;

	if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('lost-password')) {
		$is_lost_password = true;
	}

	if (!$is_lost_password && isset($_GET['action']) && $_GET['action'] === 'lostpassword') {
		$is_lost_password = true;
	}

	if (!$is_lost_password) {
		return;
	}

	wp_safe_redirect(home_url('/?open_login=recovery'));
	exit;
}

add_filter('lostpassword_url', 'yoga_lostpassword_url_to_modal', 10, 2);
function yoga_lostpassword_url_to_modal(string $url, string $redirect): string {
	return add_query_arg('open_login', 'recovery', home_url('/'));
}
