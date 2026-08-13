<?php
/**
 * AJAX handlers for Sadhana cycles.
 *
 * @package Yoga
 */

if (!defined('ABSPATH')) {
	exit;
}

function yoga_sadhana_ajax_user_id(): int {
	if (!is_user_logged_in()) {
		yoga_ajax_error(__('Необходима авторизация.', 'yoga'), 'unauthorized', 401);
	}
	check_ajax_referer('yoga_ajax_nonce', 'nonce');
	return (int) get_current_user_id();
}

function yoga_sadhana_ajax_payload(array|WP_Error $result, string $message): void {
	if (is_wp_error($result)) {
		yoga_ajax_error($result->get_error_message(), $result->get_error_code(), 400);
	}
	$user_id = (int) get_current_user_id();
	$data = array(
		'sadhana' => yoga_sadhana_public_data($result),
		'active_count' => yoga_sadhana_active_count($user_id),
	);
	if (($result['status'] ?? '') === 'active' && function_exists('yoga_get_sadhana_card_html')) {
		$data['active_card_html'] = yoga_get_sadhana_card_html($result, 'active');
	}
	if (($result['status'] ?? '') === 'active' && function_exists('yoga_get_practice_sadhana_counter_html')) {
		$data['progress_html'] = yoga_get_practice_sadhana_counter_html($result);
	}
	yoga_ajax_success($message, $data);
}

function yoga_ajax_sadhana_start(): void {
	$user_id = yoga_sadhana_ajax_user_id();
	$result = yoga_sadhana_start(
		$user_id,
		absint($_POST['practice_id'] ?? 0),
		absint($_POST['target_days'] ?? 0)
	);
	yoga_sadhana_ajax_payload($result, __('Садхана началась.', 'yoga'));
}
add_action('wp_ajax_yoga_sadhana_start', 'yoga_ajax_sadhana_start');
add_action('wp_ajax_nopriv_yoga_sadhana_start', 'yoga_ajax_sadhana_start');

function yoga_ajax_sadhana_mark_day(): void {
	$user_id = yoga_sadhana_ajax_user_id();
	$result = yoga_sadhana_mark_day($user_id, absint($_POST['sadhana_id'] ?? 0));
	yoga_sadhana_ajax_payload($result, !is_wp_error($result) && !empty($result['already_marked']) ? __('Сегодняшний день уже отмечен.', 'yoga') : __('День отмечен.', 'yoga'));
}
add_action('wp_ajax_yoga_sadhana_mark_day', 'yoga_ajax_sadhana_mark_day');
add_action('wp_ajax_nopriv_yoga_sadhana_mark_day', 'yoga_ajax_sadhana_mark_day');

function yoga_ajax_sadhana_cancel(): void {
	$user_id = yoga_sadhana_ajax_user_id();
	$sadhana_id = absint($_POST['sadhana_id'] ?? 0);
	if ($sadhana_id <= 0) {
		$practice_id = absint($_POST['practice_id'] ?? 0);
		$sadhana_id = $practice_id > 0 ? yoga_sadhana_find_active_id($user_id, $practice_id) : 0;
	}
	$result = yoga_sadhana_cancel($user_id, $sadhana_id);
	yoga_sadhana_ajax_payload($result, __('Садхана отменена.', 'yoga'));
}
add_action('wp_ajax_yoga_sadhana_cancel', 'yoga_ajax_sadhana_cancel');
add_action('wp_ajax_nopriv_yoga_sadhana_cancel', 'yoga_ajax_sadhana_cancel');

function yoga_ajax_sadhana_restart(): void {
	$user_id = yoga_sadhana_ajax_user_id();
	$result = yoga_sadhana_restart($user_id, absint($_POST['sadhana_id'] ?? 0));
	yoga_sadhana_ajax_payload($result, !is_wp_error($result) && !empty($result['already_active']) ? __('Эта садхана уже активна.', 'yoga') : __('Садхана началась снова.', 'yoga'));
}
add_action('wp_ajax_yoga_sadhana_restart', 'yoga_ajax_sadhana_restart');
add_action('wp_ajax_nopriv_yoga_sadhana_restart', 'yoga_ajax_sadhana_restart');
