<?php
/**
 * Локальный спрайт меню ЛК. Символы объявляются в том же документе, где
 * используются, поэтому Firefox не создаёт внешний SVG-документ для <use>.
 */
if (!function_exists('yoga_get_lk_menu_sprite_symbols')) {
	function yoga_get_lk_menu_sprite_symbols(): array {
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
				if (in_array($id_match[1], $allowed_symbols, true)) {
					$symbols[$id_match[1]] = array(
						'markup' => $match[0],
						'viewbox' => $viewbox_match[1],
					);
				}
			}
		}

		return $symbols;
	}
}

if (!function_exists('yoga_render_lk_menu_sprite')) {
	function yoga_render_lk_menu_sprite(): void {
		static $rendered = false;
		if ($rendered) {
			return;
		}

		$rendered = true;
		$symbols = yoga_get_lk_menu_sprite_symbols();
		if (empty($symbols)) {
			return;
		}

		echo '<svg class="lk-menu-sprite" width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute;overflow:visible;pointer-events:none;"><defs>';
		echo implode('', array_column($symbols, 'markup'));
		echo '</defs></svg>';
	}
}

if (!function_exists('yoga_render_lk_menu_icon')) {
	function yoga_render_lk_menu_icon(string $symbol_id, string $class_name): void {
		$symbols = yoga_get_lk_menu_sprite_symbols();
		if (empty($symbols[$symbol_id])) {
			return;
		}

		printf(
			'<svg class="%1$s" viewBox="%2$s" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false"><use href="#%3$s" width="100%%" height="100%%"></use></svg>',
			esc_attr($class_name),
			esc_attr($symbols[$symbol_id]['viewbox']),
			esc_attr($symbol_id)
		);
	}
}
