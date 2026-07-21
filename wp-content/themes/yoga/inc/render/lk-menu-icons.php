<?php
/**
 * Выводит иконку меню личного кабинета как обычный SVG, без элемента <use>.
 * Firefox не применяет к такой разметке отдельный viewport символа спрайта.
 */
if (!function_exists('yoga_render_lk_menu_icon')) {
	function yoga_render_lk_menu_icon(string $symbol_id, string $class_name): void {
		static $symbols = null;

		$allowed_symbols = array(
			'lk-sidebar-user',
			'lk-sidebar-history',
			'lk-sidebar-lotus',
			'lk-sidebar-heart',
			'lk-sidebar-smile',
			'lk-sidebar-question',
			'lk_bell',
			'lk-sidebar-settings',
			'lk-sidebar-logout',
		);

		if (!in_array($symbol_id, $allowed_symbols, true)) {
			return;
		}

		if ($symbols === null) {
			$symbols = array();
			$sprite_path = get_template_directory() . '/assets/svg/sprite.svg';
			$sprite_markup = is_readable($sprite_path) ? (string) file_get_contents($sprite_path) : '';
			preg_match_all('/<symbol\\b([^>]*)>(.*?)<\\/symbol>/si', $sprite_markup, $matches, PREG_SET_ORDER);

			foreach ($matches as $match) {
				$attributes = $match[1] ?? '';
				if (!preg_match('/\\bid=["\\\']([^"\\\']+)["\\\']/i', $attributes, $id_match)) {
					continue;
				}
				if (!preg_match('/\\bviewBox=["\\\']([^"\\\']+)["\\\']/i', $attributes, $viewbox_match)) {
					continue;
				}
				$symbols[$id_match[1]] = array(
					'viewbox' => $viewbox_match[1],
					'content' => $match[2] ?? '',
				);
			}
		}

		if (empty($symbols[$symbol_id])) {
			return;
		}

		$icon = $symbols[$symbol_id];
		printf(
			'<svg class="%1$s" viewBox="%2$s" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">%3$s</svg>',
			esc_attr($class_name),
			esc_attr($icon['viewbox']),
			$icon['content']
		);
	}
}
