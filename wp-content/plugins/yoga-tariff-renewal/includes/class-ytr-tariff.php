<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Tariff {
	public static function is_tariff_product(int $product_id): bool {
		if ($product_id <= 0) {
			return false;
		}

		if (function_exists('yoga_product_is_tariff')) {
			return yoga_product_is_tariff($product_id);
		}

		return has_term('tariffs', 'product_cat', $product_id);
	}

	public static function order_contains_tariff(WC_Order $order): bool {
		if (function_exists('yoga_order_contains_tariff_product')) {
			return yoga_order_contains_tariff_product($order);
		}

		foreach ($order->get_items() as $item) {
			$product_id = (int) $item->get_product_id();
			if (self::is_tariff_product($product_id)) {
				return true;
			}
		}

		return false;
	}

	public static function get_tariff_product_id_from_order(WC_Order $order): int {
		foreach ($order->get_items() as $item) {
			$product_id = (int) $item->get_product_id();
			if (self::is_tariff_product($product_id)) {
				return $product_id;
			}
		}

		return 0;
	}

	/**
	 * @return array<string, mixed>|false
	 */
	public static function get_active_tariff(int $user_id) {
		if (function_exists('get_current_user_tariff')) {
			return get_current_user_tariff($user_id);
		}

		return false;
	}

	public static function get_access_end_timestamp(int $user_id): int {
		$tariff = self::get_active_tariff($user_id);
		if (!is_array($tariff) || empty($tariff['access_end'])) {
			return 0;
		}

		return (int) $tariff['access_end'];
	}
}
