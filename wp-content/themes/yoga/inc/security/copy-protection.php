<?php
/**
 * Компонент безопасности: copy protection.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}









if (!function_exists('yoga_copy_protection_is_enabled')) {



	function yoga_copy_protection_is_enabled(): bool {
		if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
			return false;
		}

		if (function_exists('yoga_copy_protection_is_blog_context') && yoga_copy_protection_is_blog_context()) {
			return false;
		}

		return (bool) apply_filters('yoga_copy_protection_enabled', true);
	}
}

if (!function_exists('yoga_copy_protection_is_blog_context')) {
	function yoga_copy_protection_is_blog_context(): bool {
		if (is_singular('post') || is_home() || is_tag() || is_date() || is_author()) {
			return true;
		}

		if (is_category()) {
			$queried = get_queried_object();
			if (!$queried instanceof WP_Term) {
				return true;
			}

			$blog_category = get_category_by_slug('blog');
			if (!$blog_category instanceof WP_Term) {
				return true;
			}

			return (int) $queried->term_id === (int) $blog_category->term_id
				|| cat_is_ancestor_of((int) $blog_category->term_id, (int) $queried->term_id);
		}

		return false;
	}
}

if (!function_exists('yoga_copy_protection_selectors')) {





	function yoga_copy_protection_selectors(): array {
		$selectors = array(
			'.praktika-info',
			'.fancybox-container',
			'.rules',
			'.question__sub',
			'.about-text',
		);

		return apply_filters('yoga_copy_protection_selectors', $selectors);
	}
}

if (!function_exists('yoga_copy_protection_offline_message')) {



	function yoga_copy_protection_offline_message(): string {
		return (string) apply_filters(
			'yoga_copy_protection_offline_message',
			__('Контент доступен только на сайте. Сохранённая копия страницы недоступна.', 'yoga')
		);
	}
}

if (!function_exists('yoga_copy_protection_block_devtools_shortcuts')) {
	function yoga_copy_protection_block_devtools_shortcuts(): bool {
		if (!function_exists('get_field')) {
			return false;
		}

		return (bool) get_field('copy_protection_block_devtools_shortcuts', 'option');
	}
}

if (!function_exists('yoga_copy_protection_disable_text_selection')) {
	function yoga_copy_protection_disable_text_selection(): bool {
		if (!function_exists('get_field')) {
			return false;
		}

		return (bool) get_field('copy_protection_disable_text_selection', 'option');
	}
}

if (!function_exists('yoga_copy_protection_block_context_menu')) {
	function yoga_copy_protection_block_context_menu(): bool {
		if (!function_exists('get_field')) {
			return true;
		}

		return !(bool) get_field('copy_protection_disable_context_menu_blocking', 'option');
	}
}

if (!function_exists('yoga_copy_protection_inline_head_guard')) {



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
			window.yogaAppRuntimeOffline = <?php echo wp_json_encode(
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
		$js_path  = $theme_dir . '/assets/js/runtime.js';

		$css_ver = file_exists($css_path) ? (string) filemtime($css_path) : '1.0.0';
		$js_ver  = file_exists($js_path) ? (string) filemtime($js_path) : '1.0.0';

		if (defined('WP_DEBUG') && WP_DEBUG) {
			$css_ver = (string) time();
			$js_ver  = (string) time();
		}

		wp_enqueue_style(
			'yoga-app-runtime-style',
			$theme_uri . '/assets/css/copy-protection.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'yoga-app-runtime',
			$theme_uri . '/assets/js/runtime.js',
			array(),
			$js_ver,
			true
		);

		wp_localize_script(
			'yoga-app-runtime',
			'yogaAppRuntime',
			array(
				'enabled'        => true,
				'selectors'      => array_values($selectors),
				'offlineMessage' => yoga_copy_protection_offline_message(),
				'blockContextMenu' => yoga_copy_protection_block_context_menu(),
				'blockDevtools'    => yoga_copy_protection_block_devtools_shortcuts(),
				'blockTextSelection' => yoga_copy_protection_disable_text_selection(),
			)
		);
	}
}
add_action('wp_enqueue_scripts', 'yoga_copy_protection_enqueue_assets');

if (!function_exists('yoga_copy_protection_body_class')) {




	function yoga_copy_protection_body_class(array $classes): array {
		if (yoga_copy_protection_is_enabled() && yoga_copy_protection_selectors() !== array()) {
			$classes[] = 'yoga-copy-protected';

			if (yoga_copy_protection_disable_text_selection()) {
				$classes[] = 'yoga-text-selection-disabled';
			}
		}

		return $classes;
	}
}
add_filter('body_class', 'yoga_copy_protection_body_class');
