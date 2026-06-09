<?php
/**
 * Видимость секций practice_sections на фронте (не путать с Conditional Logic ACF в админке).
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_get_practice_section_layout_choices')) {
	/**
	 * @return array<string, string> layout name => label
	 */
	function yoga_get_practice_section_layout_choices(): array {
		return array(
			'anchor_01' => 'Anchor 01 — О крийе',
			'anchor_02' => 'Anchor 02 — Эффекты крийи',
			'anchor_03' => 'Anchor 03 — Философия практики',
			'anchor_04' => 'Anchor 04 — Рекомендации',
			'anchor_05' => 'Anchor 05 — Техника выполнения',
			'anchor_06' => 'Anchor 06 — Комментарии',
		);
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

		$url  = home_url('/product-category/tariffs/');
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

if (!function_exists('yoga_get_practice_section_display_title')) {
	function yoga_get_practice_section_display_title(array $section, string $layout): string {
		foreach (array('section_title', 'main_title', 'title') as $key) {
			if (!empty($section[$key])) {
				return (string) $section[$key];
			}
		}

		$choices = yoga_get_practice_section_layout_choices();

		return $choices[$layout] ?? $layout;
	}
}

if (!function_exists('yoga_get_practice_section_anchor_id')) {
	function yoga_get_practice_section_anchor_id(array $section, int $index): string {
		if (!empty($section['anchor_id'])) {
			return sanitize_html_class((string) $section['anchor_id']);
		}

		return 'anchor_0' . ($index + 1);
	}
}

if (!function_exists('yoga_get_guest_visible_practice_layouts')) {
	/**
	 * Белый список layout'ов для гостей (Настройки темы).
	 *
	 * @return string[]
	 */
	function yoga_get_guest_visible_practice_layouts(): array {
		if (!function_exists('get_field')) {
			return array();
		}

		$selected = get_field('guest_practice_sections', 'option');
		if (!is_array($selected) || $selected === array()) {
			return array();
		}

		$allowed = array_keys(yoga_get_practice_section_layout_choices());
		$layouts = array();
		foreach ($selected as $layout) {
			$layout = sanitize_key((string) $layout);
			if ($layout !== '' && in_array($layout, $allowed, true)) {
				$layouts[] = $layout;
			}
		}

		return array_values(array_unique($layouts));
	}
}

if (!function_exists('yoga_guest_practice_section_filter_enabled')) {
	function yoga_guest_practice_section_filter_enabled(): bool {
		return yoga_get_guest_visible_practice_layouts() !== array();
	}
}

if (!function_exists('yoga_viewer_has_full_practice_sections')) {
	/**
	 * Полный доступ ко всем якорям — только при активном оплаченном тарифе.
	 */
	function yoga_viewer_has_full_practice_sections(?int $user_id = null): bool {
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

if (!function_exists('yoga_should_apply_guest_practice_section_filter')) {
	/**
	 * Ограничение из «Настройки темы» для гостей и пользователей без тарифа.
	 */
	function yoga_should_apply_guest_practice_section_filter(): bool {
		if (!yoga_guest_practice_section_filter_enabled()) {
			return false;
		}

		return !yoga_viewer_has_full_practice_sections();
	}
}

if (!function_exists('yoga_can_view_practice_section_layout')) {
	/**
	 * Можно ли показать layout practice_sections текущему посетителю.
	 */
	function yoga_can_view_practice_section_layout(string $layout): bool {
		$layout = sanitize_key($layout);
		if ($layout === '') {
			return false;
		}

		if (yoga_viewer_has_full_practice_sections()) {
			return true;
		}

		if (!yoga_should_apply_guest_practice_section_filter()) {
			return true;
		}

		return in_array($layout, yoga_get_guest_visible_practice_layouts(), true);
	}
}

if (!function_exists('yoga_filter_practice_sections_for_viewer')) {
	/**
	 * @param array<int, array<string, mixed>>|null $sections
	 * @return array<int, array<string, mixed>>
	 */
	function yoga_filter_practice_sections_for_viewer($sections): array {
		if (!is_array($sections) || $sections === array()) {
			return array();
		}

		return array_values(
			array_filter(
				$sections,
				static function (array $section): bool {
					$layout = sanitize_key((string) ($section['acf_fc_layout'] ?? ''));
					return yoga_can_view_practice_section_layout($layout);
				}
			)
		);
	}
}
