<?php
/**
 * Theme styling for the native WordPress password reset form.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

function yoga_is_password_reset_page(): bool {
	global $action;
	return in_array($action, array('rp', 'resetpass'), true);
}

function yoga_enqueue_password_reset_styles(): void {
	if (!yoga_is_password_reset_page()) {
		return;
	}
	$relative_path = '/assets/css/templates/reset-password-page.css';
	$path = get_template_directory() . $relative_path;
	wp_enqueue_style(
		'yoga-reset-password-page',
		get_template_directory_uri() . $relative_path,
		array('login'),
		file_exists($path) ? (string) filemtime($path) : null
	);
}
add_action('login_enqueue_scripts', 'yoga_enqueue_password_reset_styles');

function yoga_password_reset_body_class(array $classes, string $action): array {
	if (in_array($action, array('rp', 'resetpass'), true)) {
		$classes[] = 'yoga-reset-page';
	}
	return $classes;
}
add_filter('login_body_class', 'yoga_password_reset_body_class', 10, 2);

function yoga_password_reset_logo_url(string $url): string {
	return yoga_is_password_reset_page() ? home_url('/') : $url;
}
add_filter('login_headerurl', 'yoga_password_reset_logo_url');

function yoga_password_reset_logo_text(string $text): string {
	return yoga_is_password_reset_page() ? (string) get_bloginfo('name') : $text;
}
add_filter('login_headertext', 'yoga_password_reset_logo_text');

function yoga_password_reset_message(string $message): string {
	if (!yoga_is_password_reset_page()) {
		return $message;
	}
	return '<div class="yoga-reset-heading"><span>'
		. esc_html__('Личный кабинет', 'yoga')
		. '</span><h2>'
		. esc_html__('Восстановление пароля', 'yoga')
		. '</h2></div>'
		. $message;
}
add_filter('login_message', 'yoga_password_reset_message');
