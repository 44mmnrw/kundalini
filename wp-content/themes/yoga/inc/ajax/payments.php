<?php
/**
 * AJAX-обработчики: payments.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('handle_add_payment_method')) {



	function handle_add_payment_method(): void {
		if (class_exists('YTR_LK')) {
			return;
		}

		if (!function_exists('yoga_require_woocommerce_for_ajax') || !yoga_require_woocommerce_for_ajax()) {
			return;
		}

		if (!is_user_logged_in()) {
			yoga_ajax_error('Не авторизован', 'not_authenticated', 401);
		}

		yoga_ajax_error(
			'Привязка карты недоступна. Установите и активируйте плагин автопродления тарифов.',
			'card_binding_unavailable',
			400
		);
	}
}

if (!function_exists('handle_remove_payment_method')) {



	function handle_remove_payment_method(): void {
		if (class_exists('YTR_LK')) {
			return;
		}

		if (!function_exists('yoga_require_woocommerce_for_ajax') || !yoga_require_woocommerce_for_ajax()) {
			return;
		}

		yoga_ajax_error(
			'Удаление карты недоступно. Установите и активируйте плагин автопродления тарифов.',
			'card_remove_unavailable',
			400
		);
	}
}

add_action('wp_ajax_add_payment_method', 'handle_add_payment_method');
add_action('wp_ajax_remove_payment_method', 'handle_remove_payment_method');
