<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_normalize_acf_post_ids')) {
	/**
	 * @param mixed $value
	 * @return int[]
	 */
	function yoga_normalize_acf_post_ids($value): array {
		if ($value === null || $value === '' || $value === false) {
			return array();
		}

		if (!is_array($value)) {
			$value = array($value);
		}

		$ids = array();
		foreach ($value as $item) {
			if ($item instanceof WP_Post) {
				$ids[] = (int) $item->ID;
			} elseif (is_numeric($item)) {
				$ids[] = (int) $item;
			} elseif (is_array($item) && !empty($item['ID'])) {
				$ids[] = (int) $item['ID'];
			}
		}

		$ids = array_values(array_unique(array_filter($ids, static function (int $id): bool {
			return $id > 0;
		})));

		return $ids;
	}
}

if (!function_exists('yoga_get_tariff_product_root_id')) {
	function yoga_get_tariff_product_root_id(int $product_id): int {
		if ($product_id <= 0) {
			return 0;
		}

		if (!function_exists('wc_get_product')) {
			return $product_id;
		}

		$product = wc_get_product($product_id);
		if (!$product) {
			return $product_id;
		}

		if ($product->is_type('variation')) {
			$parent_id = (int) $product->get_parent_id();
			return $parent_id > 0 ? $parent_id : $product_id;
		}

		return $product_id;
	}
}

if (!function_exists('yoga_get_tariff_practice_ids')) {
	/**
	 * Практики, включённые в тариф. null = список не задан (доступны все практики).
	 *
	 * @return int[]|null
	 */
	function yoga_get_tariff_practice_ids(int $product_id): ?array {
		if ($product_id <= 0 || !function_exists('get_field')) {
			return null;
		}

		$root_id = yoga_get_tariff_product_root_id($product_id);
		$raw     = get_field('tariff_practices', $root_id);

		if (($raw === null || $raw === '' || $raw === false) && $root_id !== $product_id) {
			$raw = get_field('tariff_practices', $product_id);
		}

		if ($raw === null || $raw === '' || $raw === false) {
			return null;
		}

		$ids = yoga_normalize_acf_post_ids($raw);
		if ($ids === array()) {
			return null;
		}

		$practice_ids = array();
		foreach ($ids as $id) {
			if (get_post_type($id) === 'practice') {
				$practice_ids[] = $id;
			}
		}

		return $practice_ids === array() ? null : array_values(array_unique($practice_ids));
	}
}

if (!function_exists('yoga_user_has_active_tariff')) {
	function yoga_user_has_active_tariff(?int $user_id = null): bool {
		if (!function_exists('get_current_user_tariff')) {
			return false;
		}

		if ($user_id === null) {
			$user_id = get_current_user_id();
		}

		if ($user_id <= 0) {
			return false;
		}

		$tariff = get_current_user_tariff($user_id);

		return is_array($tariff) && !empty($tariff['product_id']);
	}
}

if (!function_exists('yoga_get_user_allowed_practice_ids')) {
	/**
	 * @return int[]|null null — ограничение по списку не задано (все практики тарифа).
	 */
	function yoga_get_user_allowed_practice_ids(?int $user_id = null): ?array {
		if (!function_exists('get_current_user_tariff')) {
			return null;
		}

		if ($user_id === null) {
			$user_id = get_current_user_id();
		}

		if ($user_id <= 0) {
			return null;
		}

		$tariff = get_current_user_tariff($user_id);
		if (!is_array($tariff) || empty($tariff['product_id'])) {
			return null;
		}

		return yoga_get_tariff_practice_ids((int) $tariff['product_id']);
	}
}

if (!function_exists('yoga_user_can_access_practice')) {
	/**
	 * Доступ к практике по тарифу. Для гостей/без тарифа — true (действуют правила секций для гостей).
	 */
	function yoga_user_can_access_practice(?int $user_id = null, ?int $practice_id = null): bool {
		if ($practice_id === null) {
			$practice_id = (int) get_the_ID();
		}

		if ($practice_id <= 0 || get_post_type($practice_id) !== 'practice') {
			return false;
		}

		if (!yoga_user_has_active_tariff($user_id)) {
			return true;
		}

		$allowed = yoga_get_user_allowed_practice_ids($user_id);
		if ($allowed === null) {
			return true;
		}

		return in_array($practice_id, $allowed, true);
	}
}

if (!function_exists('yoga_practice_is_tariff_locked_for_viewer')) {
	/**
	 * У подписчика нет этой практики в своём тарифе.
	 */
	function yoga_practice_is_tariff_locked_for_viewer(?int $practice_id = null, ?int $user_id = null): bool {
		if ($practice_id === null) {
			$practice_id = (int) get_the_ID();
		}

		if ($practice_id <= 0) {
			return false;
		}

		if (!yoga_user_has_active_tariff($user_id)) {
			return false;
		}

		return !yoga_user_can_access_practice($user_id, $practice_id);
	}
}

if (!function_exists('yoga_get_published_tariff_products')) {
	/**
	 * @return WC_Product[]
	 */
	function yoga_get_published_tariff_products(): array {
		if (!function_exists('wc_get_products')) {
			return array();
		}

		$products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => -1,
				'category' => array('tariffs'),
				'orderby'  => 'menu_order',
				'order'    => 'ASC',
			)
		);

		return is_array($products) ? $products : array();
	}
}

if (!function_exists('yoga_get_tariffs_for_practice')) {
	/**
	 * Тарифы, в которых указана практика.
	 *
	 * @return array<int, array{id:int,name:string}>
	 */
	function yoga_get_tariffs_for_practice(int $practice_id): array {
		if ($practice_id <= 0) {
			return array();
		}

		static $cache = array();
		if (isset($cache[$practice_id])) {
			return $cache[$practice_id];
		}

		$tariffs = array();
		foreach (yoga_get_published_tariff_products() as $product) {
			$product_id = (int) $product->get_id();
			$practice_ids = yoga_get_tariff_practice_ids($product_id);

			if ($practice_ids === null) {
				continue;
			}

			if (!in_array($practice_id, $practice_ids, true)) {
				continue;
			}

			$root_id = yoga_get_tariff_product_root_id($product_id);
			$tariffs[$root_id] = array(
				'id'   => $root_id,
				'name' => $product->get_name(),
			);
		}

		$cache[$practice_id] = array_values($tariffs);

		return $cache[$practice_id];
	}
}

if (!function_exists('yoga_get_practice_tariff_paywall_message')) {
	function yoga_get_practice_tariff_paywall_message(int $practice_id): string {
		$tariffs = yoga_get_tariffs_for_practice($practice_id);

		if ($tariffs === array()) {
			return (string) apply_filters(
				'yoga_practice_tariff_paywall_message_fallback',
				__('Оформите тариф, чтобы открыть эту практику.', 'yoga'),
				$practice_id
			);
		}

		$names = array();
		foreach ($tariffs as $tariff) {
			$name = trim((string) ($tariff['name'] ?? ''));
			if ($name !== '') {
				$names[] = '«' . $name . '»';
			}
		}

		if ($names === array()) {
			return (string) apply_filters(
				'yoga_practice_tariff_paywall_message_fallback',
				__('Оформите тариф, чтобы открыть эту практику.', 'yoga'),
				$practice_id
			);
		}

		if (count($names) === 1) {
			return (string) apply_filters(
				'yoga_practice_tariff_paywall_message_single',
				sprintf(
					/* translators: %s: tariff name */
					__('Практика доступна в тарифе %s.', 'yoga'),
					$names[0]
				),
				$practice_id,
				$tariffs
			);
		}

		return (string) apply_filters(
			'yoga_practice_tariff_paywall_message_multiple',
			sprintf(
				/* translators: %s: comma-separated tariff names */
				__('Практика доступна в тарифах: %s.', 'yoga'),
				implode(', ', $names)
			),
			$practice_id,
			$tariffs
		);
	}
}

if (!function_exists('yoga_get_section_paywall_label')) {
	function yoga_get_section_paywall_label(?int $user_id = null): string {
		if (yoga_user_has_active_tariff($user_id)) {
			return (string) apply_filters(
				'yoga_section_paywall_label_subscriber',
				__('Недоступно в вашем тарифе', 'yoga'),
				$user_id
			);
		}

		return (string) apply_filters(
			'yoga_section_paywall_label_guest',
			__('Доступно по подписке', 'yoga'),
			$user_id
		);
	}
}

if (!function_exists('yoga_get_section_paywall_text')) {
	function yoga_get_section_paywall_text(?int $practice_id = null, ?int $user_id = null): string {
		if ($practice_id === null) {
			$practice_id = (int) get_the_ID();
		}

		if (yoga_user_has_active_tariff($user_id)) {
			if (yoga_practice_is_tariff_locked_for_viewer($practice_id, $user_id)) {
				return yoga_get_practice_tariff_paywall_message($practice_id);
			}

			return (string) apply_filters(
				'yoga_section_paywall_text_subscriber',
				__('Этот раздел недоступен в вашем тарифе.', 'yoga'),
				$practice_id,
				$user_id
			);
		}

		return (string) apply_filters(
			'yoga_section_paywall_text_guest',
			__('Оформите тариф, чтобы открыть этот раздел практики.', 'yoga'),
			$practice_id,
			$user_id
		);
	}
}

if (!function_exists('yoga_practice_card_tariff_lock_class')) {
	function yoga_practice_card_tariff_lock_class(int $practice_id, ?int $user_id = null): string {
		return yoga_practice_is_tariff_locked_for_viewer($practice_id, $user_id)
			? 'kriyi-item--tariff-locked'
			: '';
	}
}
