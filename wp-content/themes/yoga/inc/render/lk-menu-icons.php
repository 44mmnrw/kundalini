<?php
/**
 * Компонент темы: lk menu icons.
 *
 * @package Yoga
 */
/** New sections shown by the sidebar_lk design (2113:25393). */
function yoga_get_additional_lk_menu_sections(): array {
	return array(
		'reviews' => array('target' => '10', 'label' => __('Мои отзывы', 'yoga'), 'icon' => 'lk-menu-reviews.svg'),
		'promocodes' => array('target' => '11', 'label' => __('Мои промокоды', 'yoga'), 'icon' => 'lk-menu-promocodes.svg'),
		'warnings' => array('target' => '12', 'label' => __('Мои предупреждения', 'yoga'), 'icon' => 'lk-menu-warnings.svg'),
	);
}

function yoga_render_additional_lk_menu_items(string $active_target): void {
	foreach (yoga_get_additional_lk_menu_sections() as $section => $item) {
		?>
		<button type="button" class="sidebar-menu__item sidebar-menu__item--additional<?php echo $active_target === $item['target'] ? ' active' : ''; ?>" data-target="<?php echo esc_attr($item['target']); ?>">
			<span class="sidebar-menu__item-icon"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/' . $item['icon']); ?>" alt=""></span>
			<span class="sidebar-menu__label"><?php echo esc_html($item['label']); ?></span>
		</button>
		<?php
	}
}

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
			'lk-modal-close',
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
					$fill = '';
					if (preg_match('/\\bfill=["\\\']([^"\\\']+)["\\\']/i', $attributes, $fill_match)) {
						$fill = $fill_match[1];
					}
					$symbols[$id_match[1]] = array(
						'markup' => $match[0],
						'viewbox' => $viewbox_match[1],
						'fill' => $fill,
						'content' => $match[2] ?? '',
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
			'<svg class="%1$s" viewBox="%2$s" fill="%3$s" preserveAspectRatio="xMidYMid meet" overflow="visible" aria-hidden="true" focusable="false">%4$s</svg>',
			esc_attr($class_name),
			esc_attr($symbols[$symbol_id]['viewbox']),
			esc_attr($symbols[$symbol_id]['fill']),
			$symbols[$symbol_id]['content']
		);
	}
}
