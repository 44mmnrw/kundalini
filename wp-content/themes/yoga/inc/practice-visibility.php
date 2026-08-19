<?php
/**
 * Компонент темы: practice visibility.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_get_practice_section_layout_choices')) {



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
			$base = sanitize_html_class((string) $section['anchor_id']);
			if ($base === '') {
				$base = 'anchor';
			}


			return $base . '-' . ($index + 1);
		}

		return 'anchor_0' . ($index + 1);
	}
}

if (!function_exists('yoga_get_guest_visible_practice_layouts')) {





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

if (!function_exists('yoga_get_practice_section_allowed_tariff_ids')) {




	function yoga_get_practice_section_allowed_tariff_ids(array $section): array {
		$value = $section['section_allowed_tariffs'] ?? null;
		if (empty($value)) {
			return array();
		}

		if (function_exists('yoga_normalize_acf_post_ids')) {
			$ids = yoga_normalize_acf_post_ids($value);
		} else {
			$ids = array();
			foreach ((array) $value as $item) {
				if ($item instanceof WP_Post) {
					$ids[] = (int) $item->ID;
				} elseif (is_array($item) && isset($item['ID'])) {
					$ids[] = (int) $item['ID'];
				} elseif (is_numeric($item)) {
					$ids[] = (int) $item;
				}
			}
		}

		$tariff_ids = array();
		foreach ($ids as $id) {
			$id = (int) $id;
			if ($id <= 0) {
				continue;
			}

			$tariff_ids[] = $id;
			if (function_exists('yoga_get_tariff_product_root_id')) {
				$tariff_ids[] = yoga_get_tariff_product_root_id($id);
			}
		}

		return array_values(array_unique(array_filter(array_map('intval', $tariff_ids))));
	}
}

if (!function_exists('yoga_normalize_practice_section_tariff_display_name')) {
	function yoga_normalize_practice_section_tariff_display_name(string $name): string {
		$name = trim(wp_strip_all_tags($name));
		if ($name === '') {
			return '';
		}

		$name = html_entity_decode($name, ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');
		$name = preg_replace('/\s+/u', ' ', $name);
		$period_pattern = '(?:на\s+)?(?:\d+\s*)?(?:месяц(?:а|ев)?|мес\.?|год(?:а)?|годовой|месячный|month|year|monthly|yearly|annual|annually)';
		$patterns = array(
			'/\s*[\(\[]\s*' . $period_pattern . '\s*[\)\]]\s*$/iu',
			'/\s*[-–—\/|,;:]+\s*' . $period_pattern . '\s*$/iu',
			'/\s+' . $period_pattern . '\s*$/iu',
		);

		foreach ($patterns as $pattern) {
			$normalized = preg_replace($pattern, '', $name);
			if (is_string($normalized) && trim($normalized) !== '') {
				$name = trim($normalized);
			}
		}

		return trim(preg_replace('/\s+/u', ' ', $name) ?: $name);
	}
}

if (!function_exists('yoga_get_practice_section_tariff_name_key')) {
	function yoga_get_practice_section_tariff_name_key(string $name): string {
		$name = yoga_normalize_practice_section_tariff_display_name($name);
		$name = str_replace(array('Ё', 'ё'), array('Е', 'е'), $name);
		$name = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
		return trim(preg_replace('/\s+/u', ' ', $name) ?: $name);
	}
}

if (!function_exists('yoga_get_practice_section_allowed_tariff_names')) {




	function yoga_get_practice_section_allowed_tariff_names(array $section): array {
		$tariff_ids = yoga_get_practice_section_allowed_tariff_ids($section);
		if ($tariff_ids === array()) {
			return array();
		}

		$names = array();
		foreach ($tariff_ids as $tariff_id) {
			$tariff_id = (int) $tariff_id;
			if ($tariff_id <= 0) {
				continue;
			}

			$root_id = function_exists('yoga_get_tariff_product_root_id')
				? yoga_get_tariff_product_root_id($tariff_id)
				: $tariff_id;
			$display_id = $root_id > 0 ? $root_id : $tariff_id;

			$name = '';
			if (function_exists('wc_get_product')) {
				$product = wc_get_product($display_id);
				if ($product) {
					$name = (string) $product->get_name();
				}
			}

			if ($name === '') {
				$name = get_the_title($display_id);
			}

			$name = yoga_normalize_practice_section_tariff_display_name((string) $name);
			if ($name !== '') {
				$key = yoga_get_practice_section_tariff_name_key($name);
				if ($key !== '' && !isset($names[$key])) {
					$names[$key] = $name;
				}
			}
		}

		return array_values($names);
	}
}

if (!function_exists('yoga_get_practice_section_allowed_tariff_label')) {



	function yoga_get_practice_section_allowed_tariff_label(array $section): string {
		$names = yoga_get_practice_section_allowed_tariff_names($section);
		if ($names === array()) {
			return '';
		}

		if (count($names) === 1) {
			return sprintf(

				__('Доступно на тарифе %s', 'yoga'),
				$names[0]
			);
		}

		return sprintf(

			__('Доступно на тарифах: %s', 'yoga'),
			implode(', ', $names)
		);
	}
}

if (!function_exists('yoga_get_viewer_active_tariff_ids')) {



	function yoga_get_viewer_active_tariff_ids(?int $user_id = null): array {
		if (!function_exists('get_current_user_tariff')) {
			return array();
		}

		if ($user_id === null) {
			$user_id = get_current_user_id();
		}

		if ($user_id <= 0) {
			return array();
		}

		$tariff = get_current_user_tariff($user_id);
		if (!is_array($tariff) || empty($tariff['product_id'])) {
			return array();
		}

		$product_id = (int) $tariff['product_id'];
		if ($product_id <= 0) {
			return array();
		}

		$ids = array($product_id);
		if (function_exists('yoga_get_tariff_product_root_id')) {
			$ids[] = yoga_get_tariff_product_root_id($product_id);
		}

		return array_values(array_unique(array_filter(array_map('intval', $ids))));
	}
}

if (!function_exists('yoga_practice_section_has_media_type')) {



	function yoga_practice_section_has_media_type(array $section, string $media_type): bool {
		$media_type = sanitize_key($media_type);
		if ($media_type === '') {
			return false;
		}

		$stack = array($section);
		while ($stack !== array()) {
			$current = array_pop($stack);
			if (!is_array($current)) {
				continue;
			}

			foreach (array('media_type', 'media_type_mod') as $field_name) {
				if (sanitize_key((string) ($current[$field_name] ?? '')) === $media_type) {
					return true;
				}
			}

			foreach ($current as $value) {
				if (is_array($value)) {
					$stack[] = $value;
				}
			}
		}

		return false;
	}
}

if (!function_exists('yoga_tariff_hides_audio_section_paywall')) {
	function yoga_tariff_hides_audio_section_paywall(int $tariff_id): bool {
		if ($tariff_id <= 0) {
			return false;
		}

		$ids = array($tariff_id);
		if (function_exists('yoga_get_tariff_product_root_id')) {
			$ids[] = yoga_get_tariff_product_root_id($tariff_id);
		}

		foreach (array_values(array_unique(array_filter(array_map('intval', $ids)))) as $id) {
			if ($id <= 0) {
				continue;
			}

			if (function_exists('get_field') && (bool) get_field('hide_audio_section_paywall', $id)) {
				return true;
			}

			if ((bool) get_post_meta($id, 'hide_audio_section_paywall', true)) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('yoga_viewer_hides_audio_section_paywall')) {
	function yoga_viewer_hides_audio_section_paywall(?int $user_id = null): bool {
		foreach (yoga_get_viewer_active_tariff_ids($user_id) as $tariff_id) {
			if (yoga_tariff_hides_audio_section_paywall((int) $tariff_id)) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('yoga_should_hide_practice_section_paywall')) {



	function yoga_should_hide_practice_section_paywall(array $section, ?int $user_id = null): bool {
		return yoga_practice_section_has_media_type($section, 'audio')
			&& yoga_viewer_hides_audio_section_paywall($user_id);
	}
}

if (!function_exists('yoga_get_tariff_display_name_by_id')) {
	function yoga_get_tariff_display_name_by_id(int $tariff_id): string {
		if ($tariff_id <= 0) {
			return '';
		}

		$name = '';
		if (function_exists('wc_get_product')) {
			$product = wc_get_product($tariff_id);
			if ($product) {
				$name = (string) $product->get_name();
			}
		}

		if ($name === '') {
			$name = get_the_title($tariff_id);
		}

		return function_exists('yoga_normalize_practice_section_tariff_display_name')
			? yoga_normalize_practice_section_tariff_display_name((string) $name)
			: trim(wp_strip_all_tags((string) $name));
	}
}

if (!function_exists('yoga_get_viewer_active_tariff_name_keys')) {



	function yoga_get_viewer_active_tariff_name_keys(?int $user_id = null): array {
		$tariff_ids = yoga_get_viewer_active_tariff_ids($user_id);
		if ($tariff_ids === array()) {
			return array();
		}

		$keys = array();
		foreach ($tariff_ids as $tariff_id) {
			$name = yoga_get_tariff_display_name_by_id((int) $tariff_id);
			if ($name === '') {
				continue;
			}

			$key = function_exists('yoga_get_practice_section_tariff_name_key')
				? yoga_get_practice_section_tariff_name_key($name)
				: strtolower($name);
			if ($key !== '') {
				$keys[$key] = $key;
			}
		}

		return array_values($keys);
	}
}

if (!function_exists('yoga_can_view_practice_section')) {



	function yoga_can_view_practice_section(array $section, ?int $user_id = null, ?int $practice_id = null): bool {
		if (
			function_exists('yoga_practice_is_fully_open_for_guests')
			&& yoga_practice_is_fully_open_for_guests($practice_id)
		) {
			return true;
		}

		$allowed_tariff_ids = yoga_get_practice_section_allowed_tariff_ids($section);
		if ($allowed_tariff_ids === array()) {
			return true;
		}

		$viewer_tariff_ids = yoga_get_viewer_active_tariff_ids($user_id);
		if ($viewer_tariff_ids === array()) {
			return false;
		}

		if (array_intersect($allowed_tariff_ids, $viewer_tariff_ids) !== array()) {
			return true;
		}

		$allowed_name_keys = array();
		foreach (yoga_get_practice_section_allowed_tariff_names($section) as $name) {
			$key = yoga_get_practice_section_tariff_name_key($name);
			if ($key !== '') {
				$allowed_name_keys[$key] = $key;
			}
		}

		if ($allowed_name_keys === array()) {
			return false;
		}

		return array_intersect($allowed_name_keys, yoga_get_viewer_active_tariff_name_keys($user_id)) !== array();
	}
}

if (!function_exists('yoga_get_practice_questions_hidden_tariff_ids')) {



	function yoga_get_practice_questions_hidden_tariff_ids(): array {
		if (!function_exists('get_field')) {
			return array();
		}

		$value = get_field('practice_questions_hidden_tariffs', 'option');
		if (empty($value)) {
			return array();
		}

		if (function_exists('yoga_normalize_acf_post_ids')) {
			$ids = yoga_normalize_acf_post_ids($value);
		} else {
			$ids = array();
			foreach ((array) $value as $item) {
				if ($item instanceof WP_Post) {
					$ids[] = (int) $item->ID;
				} elseif (is_array($item) && isset($item['ID'])) {
					$ids[] = (int) $item['ID'];
				} elseif (is_numeric($item)) {
					$ids[] = (int) $item;
				}
			}
		}

		$tariff_ids = array();
		foreach ($ids as $id) {
			$id = (int) $id;
			if ($id <= 0) {
				continue;
			}

			$tariff_ids[] = $id;
			if (function_exists('yoga_get_tariff_product_root_id')) {
				$tariff_ids[] = yoga_get_tariff_product_root_id($id);
			}
		}

		return array_values(array_unique(array_filter(array_map('intval', $tariff_ids))));
	}
}

if (!function_exists('yoga_can_view_practice_questions_form')) {
	function yoga_can_view_practice_questions_form(?int $user_id = null): bool {
		if ($user_id === null) {
			$user_id = get_current_user_id();
		}

		if ($user_id <= 0) {
			return false;
		}

		$viewer_tariff_ids = yoga_get_viewer_active_tariff_ids($user_id);
		if ($viewer_tariff_ids === array()) {
			return false;
		}

		$hidden_tariff_ids = yoga_get_practice_questions_hidden_tariff_ids();
		if ($hidden_tariff_ids === array()) {
			return true;
		}

		return array_intersect($hidden_tariff_ids, $viewer_tariff_ids) === array();
	}
}

if (!function_exists('yoga_viewer_has_full_practice_sections')) {



	function yoga_viewer_has_full_practice_sections(?int $user_id = null, ?int $practice_id = null): bool {
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
		if (!is_array($tariff) || empty($tariff['product_id'])) {
			return false;
		}

		if ($practice_id === null) {
			$practice_id = (int) get_the_ID();
		}

		return true;
	}
}

if (!function_exists('yoga_practice_is_fully_open_for_guests')) {



	function yoga_practice_is_fully_open_for_guests(?int $practice_id = null): bool {
		if ($practice_id === null) {
			$practice_id = (int) get_the_ID();
		}

		if ($practice_id <= 0 || get_post_type($practice_id) !== 'practice' || !function_exists('get_field')) {
			return false;
		}

		return (bool) get_field('practice_open_for_guests', $practice_id);
	}
}

if (!function_exists('yoga_should_apply_guest_practice_section_filter')) {




	function yoga_should_apply_guest_practice_section_filter(?int $practice_id = null): bool {
		if (!yoga_guest_practice_section_filter_enabled()) {
			return false;
		}

		if (yoga_viewer_has_full_practice_sections(null, $practice_id)) {
			return false;
		}

		if (yoga_practice_is_fully_open_for_guests($practice_id)) {
			return false;
		}

		return true;
	}
}

if (!function_exists('yoga_can_view_practice_section_layout')) {



	function yoga_can_view_practice_section_layout(string $layout, ?int $practice_id = null): bool {
		$layout = sanitize_key($layout);
		if ($layout === '') {
			return false;
		}

		if (yoga_viewer_has_full_practice_sections(null, $practice_id)) {
			return true;
		}

		if (!yoga_should_apply_guest_practice_section_filter($practice_id)) {
			return true;
		}

		return in_array($layout, yoga_get_guest_visible_practice_layouts(), true);
	}
}

if (!function_exists('yoga_filter_practice_sections_for_viewer')) {




	function yoga_filter_practice_sections_for_viewer($sections, ?int $practice_id = null): array {
		if (!is_array($sections) || $sections === array()) {
			return array();
		}

		return array_values(array_filter($sections, static function ($section) use ($practice_id): bool {
			return is_array($section) && yoga_can_view_practice_section($section, null, $practice_id);
		}));
	}
}
