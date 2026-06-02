<?php



if (!defined('ABSPATH')) {

	exit;

}



if (!function_exists('yoga_format_cart_price_display')) {

	/**

	 * Цена в формате макета: 35.000 ₽

	 */

	function yoga_format_cart_price_display($amount): string {

		$amount = (float) wc_format_decimal($amount);

		if ($amount <= 0) {

			return '0 ₽';

		}



		return number_format($amount, 0, '', '.') . ' ₽';

	}

}



if (!function_exists('yoga_get_tariff_period_label')) {

	function yoga_get_tariff_period_label(int $product_id): string {

		if ($product_id <= 0 || !function_exists('get_field')) {

			return '';

		}



		$period = (string) get_field('price_period', $product_id);

		if ($period === 'year') {

			return __('Доступ на 1 год', 'yoga');

		}

		if ($period === 'month') {

			return __('Доступ на 1 месяц', 'yoga');

		}



		$price_text = trim((string) get_field('price_text', $product_id));

		if ($price_text !== '') {

			return $price_text;

		}



		return '';

	}

}



if (!function_exists('yoga_cart_get_line_product_id')) {

	function yoga_cart_get_line_product_id(array $cart_item): int {

		if (!empty($cart_item['variation_id'])) {

			return (int) $cart_item['variation_id'];

		}



		return (int) ($cart_item['product_id'] ?? 0);

	}

}


