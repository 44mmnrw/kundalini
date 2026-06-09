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

if (!function_exists('yoga_can_view_practice_section_layout')) {
	/**
	 * Можно ли показать layout practice_sections текущему посетителю.
	 */
	function yoga_can_view_practice_section_layout(string $layout): bool {
		$layout = sanitize_key($layout);
		if ($layout === '') {
			return false;
		}

		if (is_user_logged_in()) {
			return true;
		}

		if (!yoga_guest_practice_section_filter_enabled()) {
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
