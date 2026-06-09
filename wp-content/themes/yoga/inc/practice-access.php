<?php
/**
 * Доступ к практикам: practice_free_access + tariff_practices.
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_practice_free_access_value')) {
	/** Значение ACF (radio, field_6a2808b86ffb9). */
	function yoga_practice_free_access_value(): string {
		return 'Доступно без подписки';
	}
}

if (!function_exists('yoga_get_tariffs_page_url')) {
	function yoga_get_tariffs_page_url(): string {
		$pages = get_pages(
			array(
				'meta_key'    => '_wp_page_template',
				'meta_value'  => 'templates-page/tariffs.php',
				'number'      => 1,
				'post_status' => 'publish',
			)
		);
		if (!empty($pages)) {
			return get_permalink($pages[0]->ID);
		}

		$url = home_url('/product-category/tariffs/');
		$term = get_term_by('slug', 'tariffs', 'product_cat');
		if ($term instanceof WP_Term) {
			$link = get_term_link($term);
			if (!is_wp_error($link)) {
				$url = $link;
			}
		}

		return $url;
	}
}

if (!function_exists('yoga_is_practice_free_access')) {
	function yoga_is_practice_free_access(int $practice_id): bool {
		if ($practice_id <= 0 || !function_exists('get_field')) {
			return false;
		}

		$value = get_field('practice_free_access', $practice_id);
		if ($value === true || $value === 1 || $value === '1') {
			return true;
		}

		return (string) $value === yoga_practice_free_access_value();
	}
}

if (!function_exists('yoga_get_tariff_product_practice_ids')) {
	/**
	 * @return int[]
	 */
	function yoga_get_tariff_product_practice_ids(int $product_id): array {
		if ($product_id <= 0 || !function_exists('get_field')) {
			return array();
		}

		$candidate_ids = array($product_id);
		$parent_id     = (int) wp_get_post_parent_id($product_id);
		if ($parent_id > 0) {
			$candidate_ids[] = $parent_id;
		}

		$practice_ids = array();
		foreach ($candidate_ids as $candidate_id) {
			$linked = get_field('tariff_practices', $candidate_id);
			if (!is_array($linked)) {
				continue;
			}
			foreach ($linked as $practice_id) {
				$practice_id = (int) $practice_id;
				if ($practice_id > 0) {
					$practice_ids[] = $practice_id;
				}
			}
		}

		return array_values(array_unique($practice_ids));
	}
}

if (!function_exists('yoga_user_has_active_tariff')) {
	function yoga_user_has_active_tariff(?int $user_id = null): bool {
		if (!function_exists('get_current_user_tariff')) {
			return false;
		}

		$tariff = get_current_user_tariff($user_id);
		return is_array($tariff) && !empty($tariff['product_id']);
	}
}

if (!function_exists('yoga_user_can_access_practice')) {
	function yoga_user_can_access_practice(int $practice_id, ?int $user_id = null): bool {
		if ($practice_id <= 0 || get_post_type($practice_id) !== 'practice') {
			return false;
		}

		if (yoga_is_practice_free_access($practice_id)) {
			return true;
		}

		if (!yoga_user_has_active_tariff($user_id)) {
			return false;
		}

		if (!function_exists('get_current_user_tariff')) {
			return false;
		}

		$tariff = get_current_user_tariff($user_id);
		if (!is_array($tariff) || empty($tariff['product_id'])) {
			return false;
		}

		$allowed = yoga_get_tariff_product_practice_ids((int) $tariff['product_id']);

		return in_array($practice_id, $allowed, true);
	}
}

if (!function_exists('yoga_get_practice_card_url')) {
	function yoga_get_practice_card_url(int $practice_id): string {
		if (yoga_user_can_access_practice($practice_id)) {
			return get_permalink($practice_id) ?: yoga_get_tariffs_page_url();
		}

		return yoga_get_tariffs_page_url();
	}
}

add_action('template_redirect', 'yoga_restrict_locked_practice_page', 9);
function yoga_restrict_locked_practice_page(): void {
	if (!is_singular('practice')) {
		return;
	}

	$practice_id = (int) get_queried_object_id();
	if ($practice_id <= 0 || yoga_user_can_access_practice($practice_id)) {
		return;
	}

	wp_safe_redirect(yoga_get_tariffs_page_url());
	exit;
}
