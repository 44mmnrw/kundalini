<?php
/**
 * Интеграция WooCommerce: cart helpers.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {

	exit;

}



if (!function_exists('yoga_format_cart_price_display')) {







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



		$period = function_exists('yoga_get_product_price_period')
			? yoga_get_product_price_period($product_id)
			: yoga_normalize_tariff_period_slug((string) get_field('price_period', $product_id));

		if ($period === 'year') {

			return __('Доступ на 1 год', 'yoga');

		}

		if ($period === 'month') {

			return __('Доступ на 1 месяц', 'yoga');

		}

		if ($period === 'day') {

			return __('Доступ на 1 день', 'yoga');

		}

		if ($period === '3months') {

			return __('Доступ на 3 месяца', 'yoga');

		}

		if ($period === '6months') {

			return __('Доступ на 6 месяцев', 'yoga');

		}

		if ($period === 'lifetime') {

			return __('Пожизненный доступ', 'yoga');

		}



		$price_text = trim((string) get_field('price_text', $product_id));

		if ($price_text !== '') {

			return $price_text;

		}



		return '';

	}

}



if (!function_exists('yoga_normalize_tariff_period_slug')) {

	function yoga_normalize_tariff_period_slug(string $period): string {
		$raw = mb_strtolower(trim($period));


		if (str_contains($raw, ':')) {
			$raw = trim((string) preg_replace('/\s*:.*$/u', '', $raw));
		}

		$aliases = array(
			'день'     => 'day',
			'day'      => 'day',
			'1day'     => 'day',
			'1-day'    => 'day',
			'1 day'    => 'day',
			'month'    => 'month',
			'месяц'    => 'month',
			'3months'  => '3months',
			'3-months' => '3months',
			'3 months' => '3months',
			'6months'  => '6months',
			'6-months' => '6months',
			'6 months' => '6months',
			'year'     => 'year',
			'год'      => 'year',
			'lifetime' => 'lifetime',
		);

		if (isset($aliases[$raw])) {
			return $aliases[$raw];
		}

		$period = sanitize_key(str_replace(array(' ', '_'), '-', $raw));

		return $aliases[$period] ?? $period;
	}

}



if (!function_exists('yoga_get_product_price_period')) {

	function yoga_get_product_price_period(int $product_id): string {
		if ($product_id <= 0) {
			return '';
		}

		if (function_exists('get_field')) {
			$period = (string) get_field('price_period', $product_id);
			if ($period !== '') {
				return yoga_normalize_tariff_period_slug($period);
			}
		}

		$product = wc_get_product($product_id);
		if (!$product) {
			return '';
		}

		if ($product->is_type('variation')) {
			$attribute_period = (string) $product->get_attribute('pa_period');
			if ($attribute_period !== '') {
				return yoga_normalize_tariff_period_slug($attribute_period);
			}

			$parent_id = (int) $product->get_parent_id();
			if ($parent_id > 0 && function_exists('get_field')) {
				$parent_period = (string) get_field('price_period', $parent_id);
				if ($parent_period !== '') {
					return yoga_normalize_tariff_period_slug($parent_period);
				}
			}
		}

		return '';
	}

}



if (!function_exists('yoga_find_tariff_offer_for_period')) {




	function yoga_find_tariff_offer_for_period($product, string $period_slug): ?array {
		if (!$product instanceof WC_Product) {
			return null;
		}

		$period_slug = yoga_normalize_tariff_period_slug($period_slug);
		if ($period_slug === '') {
			return null;
		}

		if ($product->is_type('variable')) {
			foreach ($product->get_children() as $variation_id) {
				$variation = wc_get_product((int) $variation_id);
				if (!$variation || !$variation->exists()) {
					continue;
				}

				$variation_period = yoga_get_product_price_period((int) $variation_id);
				if ($variation_period !== $period_slug) {
					continue;
				}

				$attribute_period = (string) $variation->get_attribute('pa_period');

				return array(
					'product_id'         => (int) $variation_id,
					'price'              => (string) $variation->get_price(),
					'price_text'         => function_exists('get_field') ? (string) get_field('price_text', (int) $variation_id) : '',
					'attribute_period'   => $attribute_period !== '' ? $attribute_period : $period_slug,
				);
			}

			return null;
		}

		$product_period = yoga_get_product_price_period((int) $product->get_id());
		if ($product_period !== $period_slug) {
			return null;
		}

		return array(
			'product_id'       => (int) $product->get_id(),
			'price'            => (string) $product->get_price(),
			'price_text'       => function_exists('get_field') ? (string) get_field('price_text', (int) $product->get_id()) : '',
			'attribute_period' => $period_slug,
		);
	}

}



if (!function_exists('yoga_get_tariffs_periods')) {




	function yoga_get_tariffs_periods($post_id = null): array {
		if (!$post_id) {
			$post_id = get_the_ID();
		}

		$periods = get_field('tariffs_periods', $post_id);
		if (!$periods) {
			$periods = get_field('tariffs_periods', 'options');
		}
		if (!is_array($periods)) {
			$periods = array();
		}

		foreach ($periods as $index => $period) {
			if (!is_array($period)) {
				continue;
			}

			$name = mb_strtolower(trim((string) ($period['period_name'] ?? '')));
			$slug = yoga_normalize_tariff_period_slug((string) ($period['period_slug'] ?? ''));

			if ($slug === '' && ($name === 'день' || $name === 'day')) {
				$slug = 'day';
			}

			if ($slug !== '') {
				$periods[$index]['period_slug'] = $slug;
			}

		}

		return $periods;
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


