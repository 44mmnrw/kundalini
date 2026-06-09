<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Защита текстового контента от копирования на фронтенде.
 *
 * Блокирует выделение, контекстное меню, Ctrl+C и «Сохранить как» (Ctrl+S).
 * Сохранённая локальная копия (file://) не показывает защищённый текст.
 * Формы, поля ввода и элементы с data-yoga-copy-allow остаются доступными.
 */

if (!function_exists('yoga_copy_protection_is_enabled')) {
	/**
	 * @return bool
	 */
	function yoga_copy_protection_is_enabled(): bool {
		if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
			return false;
		}

		return (bool) apply_filters('yoga_copy_protection_enabled', true);
	}
}

if (!function_exists('yoga_copy_protection_selectors')) {
	/**
	 * CSS-селекторы областей с защищённым текстом.
	 *
	 * @return string[]
	 */
	function yoga_copy_protection_selectors(): array {
		$selectors = array(
			'.praktika-info',
			'.post-main__content',
			'.rules',
			'.question__sub',
			'.about-text',
		);

		return apply_filters('yoga_copy_protection_selectors', $selectors);
	}
}

if (!function_exists('yoga_copy_protection_offline_message')) {
	/**
	 * @return string
	 */
	function yoga_copy_protection_offline_message(): string {
		return (string) apply_filters(
			'yoga_copy_protection_offline_message',
			__('Контент доступен только на сайте. Сохранённая копия страницы недоступна.', 'yoga')
		);
	}
}

if (!function_exists('yoga_copy_protection_inline_head_guard')) {
	/**
	 * Ранняя проверка file:// до отрисовки body — без вспышки текста в сохранённой копии.
	 */
	function yoga_copy_protection_inline_head_guard(): void {
		if (!yoga_copy_protection_is_enabled()) {
			return;
		}

		$selectors = yoga_copy_protection_selectors();
		if ($selectors === array()) {
			return;
		}

		$offline_message = yoga_copy_protection_offline_message();
		?>
		<script>
		(function () {
			if (window.location.protocol !== 'file:') {
				return;
			}
			document.documentElement.classList.add('yoga-copy-offline');
			window.yogaCopyProtectionOffline = <?php echo wp_json_encode(
				array(
					'message'   => $offline_message,
					'selectors' => array_values($selectors),
				),
				JSON_UNESCAPED_UNICODE
			); ?>;
		})();
		</script>
		<?php
	}
}
add_action('wp_head', 'yoga_copy_protection_inline_head_guard', 1);

if (!function_exists('yoga_copy_protection_enqueue_assets')) {
	function yoga_copy_protection_enqueue_assets(): void {
		if (!yoga_copy_protection_is_enabled()) {
			return;
		}

		$selectors = yoga_copy_protection_selectors();
		if ($selectors === array()) {
			return;
		}

		$theme_dir = get_template_directory();
		$theme_uri = get_template_directory_uri();

		$css_path = $theme_dir . '/assets/css/copy-protection.css';
		$js_path  = $theme_dir . '/assets/js/copy-protection.js';

		$css_ver = file_exists($css_path) ? (string) filemtime($css_path) : '1.0.0';
		$js_ver  = file_exists($js_path) ? (string) filemtime($js_path) : '1.0.0';

		if (defined('WP_DEBUG') && WP_DEBUG) {
			$css_ver = (string) time();
			$js_ver  = (string) time();
		}

		wp_enqueue_style(
			'yoga-copy-protection',
			$theme_uri . '/assets/css/copy-protection.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'yoga-copy-protection',
			$theme_uri . '/assets/js/copy-protection.js',
			array(),
			$js_ver,
			true
		);

		wp_localize_script(
			'yoga-copy-protection',
			'yogaCopyProtection',
			array(
				'enabled'        => true,
				'selectors'      => array_values($selectors),
				'offlineMessage' => yoga_copy_protection_offline_message(),
			)
		);
	}
}
add_action('wp_enqueue_scripts', 'yoga_copy_protection_enqueue_assets');

if (!function_exists('yoga_copy_protection_body_class')) {
	/**
	 * @param string[] $classes
	 * @return string[]
	 */
	function yoga_copy_protection_body_class(array $classes): array {
		if (yoga_copy_protection_is_enabled() && yoga_copy_protection_selectors() !== array()) {
			$classes[] = 'yoga-copy-protected';
		}

		return $classes;
	}
}
add_filter('body_class', 'yoga_copy_protection_body_class');
