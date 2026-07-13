<?php
	@ini_set( 'upload_max_size' , '256M' );
	@ini_set( 'post_max_size', '256M');
	@ini_set( 'max_execution_time', '300' );
	// Регистрация меню
	require_once get_template_directory() . '/inc/core/ajax-responses.php';
	require_once get_template_directory() . '/inc/core/dependencies.php';
	require_once get_template_directory() . '/inc/core/legal-documents.php';
	require_once get_template_directory() . '/inc/security/smartcaptcha.php';
	require_once get_template_directory() . '/inc/security/copy-protection.php';
	// Axecode.tech: интеграция ACF вынесена в /inc/integrations/acf.php.
	// Зачем: не держим bootstrap/hooks ACF в template-parts и централизуем
	// все регистрации на acf/init, чтобы избежать побочных эффектов ранней загрузки.
	require_once get_template_directory() . '/inc/integrations/acf.php';
	require_once get_template_directory() . '/inc/practice-tariff-access.php';
	require_once get_template_directory() . '/inc/practice-visibility.php';
	require_once get_template_directory() . '/inc/practice-content.php';
	require_once get_template_directory() . '/inc/downloads/download-limits.php';
	require_once get_template_directory() . '/inc/downloads/download-handler.php';
	require_once get_template_directory() . '/inc/security/protected-media.php';
	require_once get_template_directory() . '/inc/ajax/payments.php';
	require_once get_template_directory() . '/inc/ajax/favorites.php';
	require_once get_template_directory() . '/inc/admin/practice-duplicate.php';
	require_once get_template_directory() . '/inc/woocommerce/cart-helpers.php';
	require_once get_template_directory() . '/inc/woocommerce/tariff-cart.php';
	require_once get_template_directory() . '/inc/woocommerce/checkout-template.php';
	require_once get_template_directory() . '/inc/woocommerce/checkout-payment.php';
	require_once get_template_directory() . '/inc/woocommerce/checkout-yookassa.php';
	require_once get_template_directory() . '/inc/woocommerce/payment-success.php';
	// Подключение стилей и скриптов
	require_once get_template_directory() . '/inc/ajax/auth-sms.php';
	require_once get_template_directory() . '/inc/ajax/email-verification.php';
	require_once get_template_directory() . '/inc/auth/login-modal.php';

	// Стили
		// Скрипты (jQuery уже входит в состав WordPress)
	// Plyr CSS
	// Axecode.tech: guard для fallback ACF только на фронтенде.
	// Зачем:
	// 1) Шаблоны темы напрямую вызывают ACF-хелперы и не должны падать, если ACF временно отключен.
	// 2) В админке и WP-CLI нельзя регистрировать заглушки, иначе при активации ACF возможен
	//    фатал "Cannot redeclare get_field()".
	$yoga_allow_acf_fallback = !is_admin() && !(defined('WP_CLI') && WP_CLI) && (php_sapi_name() !== 'cli');
	if ($yoga_allow_acf_fallback) {
		if (!function_exists('get_field')) {
			function get_field($selector, $post_id = false, $format_value = true) {
				return null;
			}
		}
		if (!function_exists('the_field')) {
			function the_field($selector, $post_id = false, $format_value = true) {
				echo '';
			}
		}
		if (!function_exists('have_rows')) {
			function have_rows($selector, $post_id = false) {
				return false;
			}
		}
		if (!function_exists('the_row')) {
			function the_row() {
				return null;
			}
		}
		if (!function_exists('the_sub_field')) {
			function the_sub_field($selector, $format_value = true) {
				echo '';
			}
		}
	}
	if (!function_exists('yoga_get_purchase_cta_text')) {
		function yoga_get_purchase_cta_text(): string {
			$default_text = 'Выбрать тариф';
			if (!function_exists('get_field')) {
				return $default_text;
			}
			$text = trim((string) get_field('purchase_cta_text', 'option'));
			return $text !== '' ? $text : $default_text;
		}
	}
	if (!function_exists('yoga_get_practice_card_image_url')) {
		/**
		 * URL обложки карточки практики: ACF поле image, иначе миниатюра записи.
		 *
		 * @param string $size Размер вложения / миниатюры (large, medium и т.д.).
		 */
		function yoga_get_practice_card_image_url(int $post_id, string $size = 'large'): string {
			if ($post_id <= 0) {
				return '';
			}
			$image = function_exists('get_field') ? get_field('image', $post_id) : null;
			$url = '';
			if (is_array($image)) {
				if (!empty($image['url'])) {
					$url = (string) $image['url'];
				} elseif (!empty($image['ID'])) {
					$thumb = wp_get_attachment_image_url((int) $image['ID'], $size);
					$url = $thumb ? (string) $thumb : '';
				}
			} elseif (is_numeric($image)) {
				$thumb = wp_get_attachment_image_url((int) $image, $size);
				$url = $thumb ? (string) $thumb : '';
			} elseif (is_string($image) && $image !== '') {
				$url = $image;
			}
			if ($url !== '') {
				return esc_url_raw($url);
			}
			$featured = get_the_post_thumbnail_url($post_id, $size);
			return $featured ? (string) $featured : '';
		}
	}
	if (!function_exists('yoga_ajax_error')) {
		// Plyr JS - загружаем первым
		// Axecode.tech: единый формат ошибок AJAX.
		// Зачем: фронтенд стабильно получает поля code/message и HTTP-статус.
		function yoga_ajax_error(string $message, string $code = 'error', int $status = 400, array $extra = array()) {
			$payload = array_merge(array(
				'code' => $code,
				'message' => $message,
			), $extra);
			wp_send_json_error($payload, $status);
		}
	}
	if (!function_exists('yoga_ajax_success')) {
		// Кастомный скрипт - зависит от plyr-js и jQuery
		function yoga_ajax_success($message = '', $data = array(), $status = 200) {
			$payload = array_merge(array(
				'message' => $message,
			), $data);
			wp_send_json_success($payload, $status);
		}
	}
	// Локализация базовых строк (переводы/подписи)
	function my_theme_setup() {
		register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'yoga' ),
        'footer'  => __( 'Footer Menu', 'yoga' ),
		) );
		add_theme_support( 'post-thumbnails' );
	}
	add_action( 'after_setup_theme', 'my_theme_setup' );

	/** Поиск на сайте — только записи блога (post), без практик и прочих CPT. */
	if (!function_exists('yoga_search_main_query_only_posts')) {
		/**
		 * Limit the main front-end search query to blog posts.
		 *
		 * @param WP_Query $query Current WordPress query instance.
		 * @return void
		 */
		function yoga_search_main_query_only_posts($query) {
			if (is_admin() || !($query instanceof WP_Query) || !$query->is_main_query() || !$query->is_search()) {
				return;
			}
			$query->set('post_type', 'post');
		}
	}
	add_action('pre_get_posts', 'yoga_search_main_query_only_posts', 9);

	// Логичная структура URL для практик:
	// /library/{category}/{type}/{practice}
	if (!function_exists('yoga_customize_practice_post_type_rewrite')) {
		/**
		 * @param array<string, mixed> $args Post type registration arguments.
		 * @param string               $post_type Post type key.
		 * @return array<string, mixed>
		 */
		function yoga_customize_practice_post_type_rewrite($args, $post_type) {
			if ($post_type !== 'practice') {
				return $args;
			}

			$rewrite = isset($args['rewrite']) && is_array($args['rewrite']) ? $args['rewrite'] : array();
			$args['rewrite'] = array_merge($rewrite, array(
				'slug' => 'practice',
				'with_front' => false,
			));

			return $args;
		}
	}
	add_filter('register_post_type_args', 'yoga_customize_practice_post_type_rewrite', 20, 2);

	if (!function_exists('yoga_customize_practice_type_taxonomy_rewrite')) {
		/**
		 * @param array<string, mixed> $args Taxonomy registration arguments.
		 * @param string               $taxonomy Taxonomy key.
		 * @return array<string, mixed>
		 */
		function yoga_customize_practice_type_taxonomy_rewrite($args, $taxonomy) {
			if ($taxonomy !== 'practice-type') {
				return $args;
			}

			$rewrite = isset($args['rewrite']) && is_array($args['rewrite']) ? $args['rewrite'] : array();
			$args['rewrite'] = array_merge($rewrite, array(
				'slug' => 'library',
				'with_front' => false,
				'hierarchical' => true,
			));

			return $args;
		}
	}
	add_filter('register_taxonomy_args', 'yoga_customize_practice_type_taxonomy_rewrite', 20, 2);

	if (!function_exists('yoga_get_practice_primary_term_path')) {
		/**
		 * @param int $post_id Practice post ID.
		 * @return string
		 */
		function yoga_get_practice_primary_term_path($post_id) {
			$terms = get_the_terms((int) $post_id, 'practice-type');
			if (empty($terms) || is_wp_error($terms)) {
				return '';
			}

			$child_term = null;
			foreach ($terms as $term) {
				if ((int) $term->parent > 0) {
					$child_term = $term;
					break;
				}
			}

			if (!$child_term) {
				return '';
			}

			$parent_term = get_term((int) $child_term->parent, 'practice-type');
			if (!$parent_term || is_wp_error($parent_term)) {
				return '';
			}

			return $parent_term->slug . '/' . $child_term->slug;
		}
	}

	if (!function_exists('yoga_filter_practice_permalink')) {
		/**
		 * @param string  $post_link Existing permalink.
		 * @param WP_Post $post Post object.
		 * @param bool    $leavename Whether to retain the post name placeholder.
		 * @param bool    $sample Whether this is a sample permalink.
		 * @return string
		 */
		function yoga_filter_practice_permalink($post_link, $post, $leavename, $sample) {
			if (!$post instanceof WP_Post || $post->post_type !== 'practice') {
				return $post_link;
			}

			$term_path = yoga_get_practice_primary_term_path($post->ID);
			if ($term_path === '') {
				return $post_link;
			}

			$slug = $leavename ? '%postname%' : $post->post_name;
			return home_url('/library/' . $term_path . '/' . $slug . '/');
		}
	}
	add_filter('post_type_link', 'yoga_filter_practice_permalink', 20, 4);

	if (!function_exists('yoga_register_practice_library_rewrite_rules')) {
		function yoga_register_practice_library_rewrite_rules() {
			add_rewrite_rule(
				'^library/([^/]+)/([^/]+)/([^/]+)/?$',
				'index.php?post_type=practice&name=$matches[3]',
				'top'
			);
		}
	}
	add_action('init', 'yoga_register_practice_library_rewrite_rules', 20);

	if (!function_exists('yoga_redirect_legacy_practice_urls')) {
		function yoga_redirect_legacy_practice_urls() {
			if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
				return;
			}

			$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
			$path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/');
			if ($path === '') {
				return;
			}

			$target_path = '';

			if (strpos($path, 'practice-type/') === 0) {
				$tail = ltrim(substr($path, strlen('practice-type/')), '/');
				$target_path = 'library' . ($tail !== '' ? '/' . $tail : '');
			} elseif (strpos($path, 'practice/') === 0 || strpos($path, 'practices/') === 0) {
				$prefix = strpos($path, 'practices/') === 0 ? 'practices/' : 'practice/';
				$tail = ltrim(substr($path, strlen($prefix)), '/');
				$practice = get_page_by_path($tail, OBJECT, 'practice');
				if ($practice instanceof WP_Post) {
					wp_safe_redirect(get_permalink($practice->ID), 301);
					exit;
				}
				$target_path = 'practice' . ($tail !== '' ? '/' . $tail : '');
			}

			if ($target_path === '') {
				if (is_singular('practice')) {
					$canonical = trailingslashit((string) wp_parse_url(get_permalink(), PHP_URL_PATH));
					$current = trailingslashit('/' . $path);
					if ($canonical && $current !== $canonical) {
						$query_string = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
						$target_url = home_url($canonical);
						if ($query_string !== '') {
							$target_url .= '?' . $query_string;
						}
						wp_safe_redirect($target_url, 301);
						exit;
					}
				}
				return;
			}

			$target_url = home_url('/' . $target_path . '/');
			$query_string = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
			if ($query_string !== '') {
				$target_url .= '?' . $query_string;
			}

			wp_safe_redirect($target_url, 301);
			exit;
		}
	}
	add_action('template_redirect', 'yoga_redirect_legacy_practice_urls', 0);

	if (!function_exists('yoga_flush_rewrite_rules_once_for_practice_urls')) {
		function yoga_flush_rewrite_rules_once_for_practice_urls() {
			if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
				return;
			}

			$version = 'yoga_practice_urls_v2';
			if (get_option('yoga_rewrite_version') === $version) {
				return;
			}

			flush_rewrite_rules(false);
			update_option('yoga_rewrite_version', $version, false);
		}
	}
	add_action('init', 'yoga_flush_rewrite_rules_once_for_practice_urls', 99);

	if (!function_exists('yoga_has_child_practice_type_term')) {
		function yoga_has_child_practice_type_term(array $term_ids): bool {
			foreach ($term_ids as $term_id) {
				$term = get_term((int) $term_id, 'practice-type');
				if ($term instanceof WP_Term && !is_wp_error($term) && (int) $term->parent > 0) {
					return true;
				}
			}
			return false;
		}
	}

	if (!function_exists('yoga_lk_sidebar_secondary_nav_urls')) {
		/**
		 * URL внешних пунктов бокового меню ЛК (Figma sidebar_lk 620:12651).
		 *
		 * @return array{library:string,tariffs:string,about:string,blog:string,contacts:string,faq:string}
		 */
		function yoga_lk_sidebar_secondary_nav_urls(): array {
			$fallback = home_url('/');

			$library = $fallback;
			$parents = get_terms(array(
				'taxonomy' => 'practice-type',
				'parent' => 0,
				'hide_empty' => false,
				'orderby' => 'term_order',
				'order' => 'ASC',
				'number' => 1,
			));
			if (!is_wp_error($parents) && !empty($parents)) {
				$link = get_term_link($parents[0]);
				if (!is_wp_error($link)) {
					$library = $link;
				}
			}

			$tariffs = '';
			$tariffs_pages = get_pages(array(
				'meta_key' => '_wp_page_template',
				'meta_value' => 'templates-page/tariffs.php',
				'number' => 1,
				'post_status' => 'publish',
			));
			if (!empty($tariffs_pages)) {
				$tariffs = get_permalink($tariffs_pages[0]->ID);
			}
			if ($tariffs === '') {
				$tariffs = home_url('/product-category/tariffs/');
				$tariffs_term = get_term_by('slug', 'tariffs', 'product_cat');
				if ($tariffs_term instanceof WP_Term) {
					$tl = get_term_link($tariffs_term);
					if (!is_wp_error($tl)) {
						$tariffs = $tl;
					}
				}
			}

			$about = '';
			$about_page = get_page_by_path('o-nas');
			if ($about_page instanceof WP_Post) {
				$about = get_permalink($about_page);
			}

			$blog = '';
			$blog_page_id = (int) get_option('page_for_posts');
			if ($blog_page_id > 0) {
				$blog = get_permalink($blog_page_id);
			}

			$contacts = '';
			$contact_pages = get_pages(array(
				'meta_key' => '_wp_page_template',
				'meta_value' => 'templates-page/contacts.php',
				'number' => 1,
				'post_status' => 'publish',
			));
			if (!empty($contact_pages)) {
				$contacts = get_permalink($contact_pages[0]->ID);
			}

			$faq = '';
			$faq_pages = get_pages(array(
				'meta_key' => '_wp_page_template',
				'meta_value' => 'templates-page/faq.php',
				'number' => 1,
				'post_status' => 'publish',
			));
			if (!empty($faq_pages)) {
				$faq = get_permalink($faq_pages[0]->ID);
			}

			return array(
				'library' => $library,
				'tariffs' => $tariffs !== '' ? $tariffs : $fallback,
				'about' => $about !== '' ? $about : $fallback,
				'blog' => $blog !== '' ? $blog : $fallback,
				'contacts' => $contacts !== '' ? $contacts : $fallback,
				'faq' => $faq !== '' ? $faq : $fallback,
			);
		}
	}

	if (!function_exists('yoga_collect_practice_type_term_ids')) {
		function yoga_collect_practice_type_term_ids(array $postarr): array {
			$term_ids = array();

			if (isset($postarr['tax_input']) && is_array($postarr['tax_input']) && isset($postarr['tax_input']['practice-type'])) {
				$raw_terms = $postarr['tax_input']['practice-type'];
				if (!is_array($raw_terms)) {
					$raw_terms = array($raw_terms);
				}

				foreach ($raw_terms as $raw_term) {
					if (is_numeric($raw_term)) {
						$term_ids[] = (int) $raw_term;
						continue;
					}

					$raw_term = trim((string) $raw_term);
					if ($raw_term === '') {
						continue;
					}

					$term = get_term_by('slug', $raw_term, 'practice-type');
					if ($term instanceof WP_Term) {
						$term_ids[] = (int) $term->term_id;
					}
				}
			}

			if (empty($term_ids) && !empty($postarr['ID'])) {
				$current_terms = wp_get_object_terms((int) $postarr['ID'], 'practice-type', array('fields' => 'ids'));
				if (is_array($current_terms) && !is_wp_error($current_terms)) {
					$term_ids = array_map('intval', $current_terms);
				}
			}

			return array_values(array_unique(array_filter($term_ids)));
		}
	}

	if (!function_exists('yoga_block_practice_publish_without_child_type')) {
		function yoga_block_practice_publish_without_child_type(array $data, array $postarr): array {
			if (is_admin() !== true) {
				return $data;
			}

			if (($data['post_type'] ?? '') !== 'practice') {
				return $data;
			}

			$target_status = $data['post_status'] ?? '';
			if (!in_array($target_status, array('publish', 'future'), true)) {
				return $data;
			}

			if (!empty($postarr['ID']) && wp_is_post_revision((int) $postarr['ID'])) {
				return $data;
			}

			$term_ids = yoga_collect_practice_type_term_ids($postarr);
			if (yoga_has_child_practice_type_term($term_ids)) {
				return $data;
			}

			$data['post_status'] = 'draft';
			$GLOBALS['yoga_practice_publish_blocked'] = true;
			return $data;
		}
	}
	add_filter('wp_insert_post_data', 'yoga_block_practice_publish_without_child_type', 20, 2);

	if (!function_exists('yoga_add_practice_publish_block_notice_query_arg')) {
		function yoga_add_practice_publish_block_notice_query_arg(string $location): string {
			if (empty($GLOBALS['yoga_practice_publish_blocked'])) {
				return $location;
			}
			return add_query_arg('yoga_practice_type_error', '1', $location);
		}
	}
	add_filter('redirect_post_location', 'yoga_add_practice_publish_block_notice_query_arg');

	if (!function_exists('yoga_show_practice_publish_block_notice')) {
		function yoga_show_practice_publish_block_notice(): void {
			if (!is_admin() || empty($_GET['yoga_practice_type_error'])) {
				return;
			}

			echo '<div class="notice notice-error is-dismissible"><p>Для публикации практики выберите дочерний "Тип практики" (внутри категории).</p></div>';
		}
	}
	add_action('admin_notices', 'yoga_show_practice_publish_block_notice');
	
	// Опции ACF
	function my_theme_scripts() {
		$theme_uri = get_template_directory_uri();
		$theme_dir = get_template_directory();
		$reset_style_ver = file_exists($theme_dir . '/assets/css/reset.css') ? filemtime($theme_dir . '/assets/css/reset.css') : '1.0.0';
		$breakpoints_style_ver = file_exists($theme_dir . '/assets/css/breakpoints.css') ? filemtime($theme_dir . '/assets/css/breakpoints.css') : '1.0.0';
		$main_style_ver = file_exists($theme_dir . '/assets/css/style.css') ? filemtime($theme_dir . '/assets/css/style.css') : '1.0.0';
		$specification_style_ver = file_exists($theme_dir . '/assets/css/templates/specification.css') ? filemtime($theme_dir . '/assets/css/templates/specification.css') : '1.0.0';
		$header_style_ver = file_exists($theme_dir . '/assets/css/templates/header.css') ? filemtime($theme_dir . '/assets/css/templates/header.css') : '1.0.0';
		$footer_style_ver = file_exists($theme_dir . '/assets/css/templates/footer.css') ? filemtime($theme_dir . '/assets/css/templates/footer.css') : '1.0.0';
		$notifications_style_ver = file_exists($theme_dir . '/assets/css/templates/notifications.css') ? filemtime($theme_dir . '/assets/css/templates/notifications.css') : '1.0.0';
		$modals_style_ver = file_exists($theme_dir . '/assets/css/templates/modals.css') ? filemtime($theme_dir . '/assets/css/templates/modals.css') : '1.0.0';
		$modal_component_styles = array(
			'modal-mobile-menus'  => 'mobile-menus.css',
			'modal-auth'          => 'auth.css',
			'modal-filters'       => 'filters.css',
			'modal-confirmations' => 'confirmations.css',
			'modal-cards'         => 'cards.css',
			'modal-cookie'        => 'cookie.css',
			'modal-utilities'     => 'utilities.css',
			'modal-auth-states'   => 'auth-states.css',
		);
		$homepage_style_ver = file_exists($theme_dir . '/assets/css/templates/homepage.css') ? filemtime($theme_dir . '/assets/css/templates/homepage.css') : '1.0.0';
		$kriyi_style_ver = file_exists($theme_dir . '/assets/css/templates/kriyi.css') ? filemtime($theme_dir . '/assets/css/templates/kriyi.css') : '1.0.0';
		$library_style_ver = file_exists($theme_dir . '/assets/css/templates/library.css') ? filemtime($theme_dir . '/assets/css/templates/library.css') : '1.0.0';
		$library_filters_style_ver = file_exists($theme_dir . '/assets/css/templates/library-filters.css') ? filemtime($theme_dir . '/assets/css/templates/library-filters.css') : '1.0.0';
		$praktika_style_ver = file_exists($theme_dir . '/assets/css/templates/praktika.css') ? filemtime($theme_dir . '/assets/css/templates/praktika.css') : '1.0.0';
		$form_questions_style_ver = file_exists($theme_dir . '/assets/css/templates/form-questions.css') ? filemtime($theme_dir . '/assets/css/templates/form-questions.css') : '1.0.0';
		$rules_style_ver = file_exists($theme_dir . '/assets/css/templates/rules.css') ? filemtime($theme_dir . '/assets/css/templates/rules.css') : '1.0.0';
		$faq_style_ver = file_exists($theme_dir . '/assets/css/templates/faq.css') ? filemtime($theme_dir . '/assets/css/templates/faq.css') : '1.0.0';
		$lk_style_ver = file_exists($theme_dir . '/assets/css/templates/lk.css') ? filemtime($theme_dir . '/assets/css/templates/lk.css') : '1.0.0';
		$blog_form_style_ver = file_exists($theme_dir . '/assets/css/templates/blog-form.css') ? filemtime($theme_dir . '/assets/css/templates/blog-form.css') : '1.0.0';
		$blog_style_ver = file_exists($theme_dir . '/assets/css/templates/blog.css') ? filemtime($theme_dir . '/assets/css/templates/blog.css') : '1.0.0';
		$post_style_ver = file_exists($theme_dir . '/assets/css/templates/post.css') ? filemtime($theme_dir . '/assets/css/templates/post.css') : '1.0.0';
		$popular_articles_style_ver = file_exists($theme_dir . '/assets/css/templates/popular-articles.css') ? filemtime($theme_dir . '/assets/css/templates/popular-articles.css') : '1.0.0';
		$notfound_style_ver = file_exists($theme_dir . '/assets/css/templates/notfound.css') ? filemtime($theme_dir . '/assets/css/templates/notfound.css') : '1.0.0';
		$about_style_ver = file_exists($theme_dir . '/assets/css/templates/about.css') ? filemtime($theme_dir . '/assets/css/templates/about.css') : '1.0.0';
		$tariffs_style_ver = file_exists($theme_dir . '/assets/css/templates/tariffs.css') ? filemtime($theme_dir . '/assets/css/templates/tariffs.css') : '1.0.0';
		$subscription_style_ver = file_exists($theme_dir . '/assets/css/templates/subscription.css') ? filemtime($theme_dir . '/assets/css/templates/subscription.css') : '1.0.0';
		$ways_style_ver = file_exists($theme_dir . '/assets/css/templates/ways.css') ? filemtime($theme_dir . '/assets/css/templates/ways.css') : '1.0.0';
		$checkout_style_ver = file_exists($theme_dir . '/assets/css/templates/checkout.css') ? filemtime($theme_dir . '/assets/css/templates/checkout.css') : '1.0.0';
		$payment_success_style_ver = file_exists($theme_dir . '/assets/css/templates/payment-success.css') ? filemtime($theme_dir . '/assets/css/templates/payment-success.css') : '1.0.0';
		$question_success_style_ver = file_exists($theme_dir . '/assets/css/templates/question-success.css') ? filemtime($theme_dir . '/assets/css/templates/question-success.css') : '1.0.0';
		$main_script_ver = file_exists($theme_dir . '/assets/js/script.js') ? filemtime($theme_dir . '/assets/js/script.js') : '1.0.0';
		$practice_player_script_ver = file_exists($theme_dir . '/assets/js/practice-player.js') ? filemtime($theme_dir . '/assets/js/practice-player.js') : '1.0.0';
		
		// В режиме разработки принудительно отключаем кэш ассетов.
		if (defined('WP_DEBUG') && WP_DEBUG) {
			$reset_style_ver = time();
			$breakpoints_style_ver = time();
			$main_style_ver = time();
			$specification_style_ver = time();
			$header_style_ver = time();
			$footer_style_ver = time();
			$modals_style_ver = time();
			$homepage_style_ver = time();
			$kriyi_style_ver = time();
			$library_style_ver = time();
			$library_filters_style_ver = time();
			$praktika_style_ver = time();
			$form_questions_style_ver = time();
			$rules_style_ver = time();
			$faq_style_ver = time();
			$lk_style_ver = time();
			$blog_form_style_ver = time();
			$blog_style_ver = time();
			$post_style_ver = time();
			$popular_articles_style_ver = time();
			$notfound_style_ver = time();
			$about_style_ver = time();
			$tariffs_style_ver = time();
			$subscription_style_ver = time();
			$ways_style_ver = time();
			$checkout_style_ver = time();
			$payment_success_style_ver = time();
			$question_success_style_ver = time();
			$main_script_ver = time();
			$practice_player_script_ver = time();
		}
		
		// Обработчик AJAX для формы подписки
		$is_homepage = is_front_page() || is_page_template('templates-page/homepage.php');
		$is_lk_template = is_page_template('templates-page/lk.php') || is_page('my-account') || (function_exists('is_account_page') && is_account_page());
		$is_contacts_template = is_page_template('templates-page/contacts.php');
		$is_tariffs_template = is_page_template('templates-page/tariffs.php');
		$is_faq_template = is_page_template('templates-page/faq.php');
		$is_404_template = is_page_template('templates-page/404.php');
		$is_privacy_template = is_page_template('templates-page/privacy-policy.php');
		$is_about_template = is_page_template('templates-page/about.php');
		$is_practice_tax = is_tax('practice-type');
		$is_product_cat_tax = is_tax('product_cat');
		$is_archive_page = is_archive();
		$is_post_single = is_singular('post');
		$is_practice_single = is_singular('practice');
		$is_order_received = function_exists('yoga_is_order_received_request') && yoga_is_order_received_request();
		$is_checkout_page = function_exists('is_checkout') && is_checkout() && !$is_order_received;
		$is_payment_success_page = function_exists('yoga_is_payment_success_screen') && yoga_is_payment_success_screen();
		$is_question_success_template = is_page_template('templates-page/question-success.php');
		$common_style_deps = array('main-style');

		wp_enqueue_style( 'reset-style', $theme_uri . '/assets/css/reset.css', array(), $reset_style_ver );
		wp_enqueue_style( 'yoga-breakpoints', $theme_uri . '/assets/css/breakpoints.css', array(), $breakpoints_style_ver );
		wp_enqueue_style( 'main-style', $theme_uri . '/assets/css/style.css', array( 'reset-style', 'yoga-breakpoints' ), $main_style_ver );
		wp_enqueue_style( 'specification-style', $theme_uri . '/assets/css/templates/specification.css', $common_style_deps, $specification_style_ver );
		wp_enqueue_style( 'header-style', $theme_uri . '/assets/css/templates/header.css', $common_style_deps, $header_style_ver );
		wp_enqueue_style( 'footer-style', $theme_uri . '/assets/css/templates/footer.css', $common_style_deps, $footer_style_ver );
		wp_enqueue_style( 'notifications-style', $theme_uri . '/assets/css/templates/notifications.css', $common_style_deps, $notifications_style_ver );
		wp_enqueue_style( 'modals-style', $theme_uri . '/assets/css/templates/modals.css', $common_style_deps, $modals_style_ver );
		$modal_style_dependency = 'modals-style';
		foreach ($modal_component_styles as $modal_style_handle => $modal_style_file) {
			$modal_style_path = '/assets/css/templates/modals/' . $modal_style_file;
			$modal_style_version = (defined('WP_DEBUG') && WP_DEBUG)
				? time()
				: (file_exists($theme_dir . $modal_style_path) ? filemtime($theme_dir . $modal_style_path) : '1.0.0');
			wp_enqueue_style($modal_style_handle, $theme_uri . $modal_style_path, array($modal_style_dependency), $modal_style_version);
			$modal_style_dependency = $modal_style_handle;
		}

		if ($is_homepage) {
			wp_enqueue_style( 'homepage-style', $theme_uri . '/assets/css/templates/homepage.css', $common_style_deps, $homepage_style_ver );
		}
		if ($is_practice_tax || $is_lk_template) {
			wp_enqueue_style( 'library-style', $theme_uri . '/assets/css/templates/library.css', $common_style_deps, $library_style_ver );
			wp_enqueue_style( 'library-filters-style', $theme_uri . '/assets/css/templates/library-filters.css', array( 'library-style' ), $library_filters_style_ver );
		}
		/* Форма «Остались вопросы» подключается до praktika.css, чтобы стили страницы практики шли последними среди темы и не перебивались соседним листом с тем же приоритетом специфичности. */
		if ($is_practice_single || $is_contacts_template || $is_faq_template) {
			wp_enqueue_style( 'form-questions-style', $theme_uri . '/assets/css/templates/form-questions.css', $common_style_deps, $form_questions_style_ver );
		}
		if ($is_practice_single) {
			wp_enqueue_style(
				'praktika-style',
				$theme_uri . '/assets/css/templates/praktika.css',
				array( 'form-questions-style', 'modals-style' ),
				$praktika_style_ver
			);
		}
		if ($is_privacy_template) {
			wp_enqueue_style( 'rules-style', $theme_uri . '/assets/css/templates/rules.css', $common_style_deps, $rules_style_ver );
		}
		if ($is_faq_template || $is_lk_template || is_user_logged_in()) {
			wp_enqueue_style( 'faq-style', $theme_uri . '/assets/css/templates/faq.css', $common_style_deps, $faq_style_ver );
		}
		if ($is_lk_template) {
			wp_enqueue_style( 'lk-style', $theme_uri . '/assets/css/templates/lk.css', $common_style_deps, $lk_style_ver );
		}
		if ($is_archive_page) {
			wp_enqueue_style( 'blog-form-style', $theme_uri . '/assets/css/templates/blog-form.css', $common_style_deps, $blog_form_style_ver );
			wp_enqueue_style( 'blog-style', $theme_uri . '/assets/css/templates/blog.css', $common_style_deps, $blog_style_ver );
		}
		if ($is_post_single) {
			wp_enqueue_style( 'post-style', $theme_uri . '/assets/css/templates/post.css', $common_style_deps, $post_style_ver );
			wp_enqueue_style( 'popular-articles-style', $theme_uri . '/assets/css/templates/popular-articles.css', $common_style_deps, $popular_articles_style_ver );
			wp_enqueue_style(
				'yoga-blog-comments',
				$theme_uri . '/assets/css/templates/praktika.css',
				array( 'post-style' ),
				$praktika_style_ver
			);
		}
		if ($is_404_template) {
			wp_enqueue_style( 'notfound-style', $theme_uri . '/assets/css/templates/notfound.css', $common_style_deps, $notfound_style_ver );
		}
		if ($is_about_template) {
			wp_enqueue_style( 'about-style', $theme_uri . '/assets/css/templates/about.css', $common_style_deps, $about_style_ver );
		}

		$load_kriyi_style = false;
		if ($is_practice_tax) {
			$current_term = get_queried_object();
			$load_kriyi_style = $current_term instanceof WP_Term && !empty($current_term->parent);
		}
		if ($is_lk_template) {
			$load_kriyi_style = true;
		}
		if ($load_kriyi_style) {
			wp_enqueue_style( 'kriyi-style', $theme_uri . '/assets/css/templates/kriyi.css', $common_style_deps, $kriyi_style_ver );
		}

		if ($is_homepage || $is_tariffs_template || $is_product_cat_tax) {
			wp_enqueue_style( 'tariffs-style', $theme_uri . '/assets/css/templates/tariffs.css', $common_style_deps, $tariffs_style_ver );
		}
		if ($is_checkout_page) {
			wp_enqueue_style( 'tariffs-style', $theme_uri . '/assets/css/templates/tariffs.css', $common_style_deps, $tariffs_style_ver );
			wp_enqueue_style( 'checkout-style', $theme_uri . '/assets/css/templates/checkout.css', array_merge( $common_style_deps, array( 'tariffs-style' ) ), $checkout_style_ver );
		}
		if ($is_payment_success_page) {
			wp_enqueue_style( 'payment-success-style', $theme_uri . '/assets/css/templates/payment-success.css', $common_style_deps, $payment_success_style_ver );
		}
		if ($is_question_success_template) {
			wp_enqueue_style( 'question-success-style', $theme_uri . '/assets/css/templates/question-success.css', $common_style_deps, $question_success_style_ver );
		}
		if (is_page() && get_page_template_slug(get_queried_object_id()) === '') {
			wp_enqueue_style('ways-style', $theme_uri . '/assets/css/templates/ways.css', array('specification-style'), $ways_style_ver);
			wp_enqueue_style('rules-style', $theme_uri . '/assets/css/templates/rules.css', $common_style_deps, $rules_style_ver);
		}
		if ($is_homepage || $is_archive_page || $is_post_single || $is_contacts_template || $is_tariffs_template || $is_product_cat_tax) {
			wp_enqueue_style( 'subscription-style', $theme_uri . '/assets/css/templates/subscription.css', $common_style_deps, $subscription_style_ver );
		}
		if (
			is_single() ||
			$is_archive_page ||
			$is_practice_tax ||
			$is_product_cat_tax ||
			$is_contacts_template ||
			$is_faq_template ||
			$is_404_template ||
			$is_privacy_template ||
			$is_about_template ||
			$is_tariffs_template
		) {
			wp_enqueue_style( 'ways-style', $theme_uri . '/assets/css/templates/ways.css', array( 'specification-style' ), $ways_style_ver );
		}
		wp_enqueue_style( 'mulish-style', $theme_uri . '/assets/css/mulish.css', array(), '1.0.0' );
		wp_enqueue_style( 'animate-style', $theme_uri . '/assets/css/animate.css', array(), '1.0.0' );
		
		// Проверка nonce для безопасности
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'spincrement', $theme_uri . '/assets/js/jquery.spincrement.min.js', array('jquery'), null, true );
		wp_enqueue_script( 'machheight', $theme_uri . '/assets/js/machheight.js', array('jquery'), null, true );
		wp_enqueue_script( 'wow', $theme_uri . '/assets/js/wow.min.js', array('jquery'), null, true );
		wp_enqueue_script( 'slick', $theme_uri . '/assets/slick/slick.min.js', array('jquery'), null, true );
		wp_enqueue_script( 'maskedinput', $theme_uri . '/assets/js/jquery.maskedinput.js', array('jquery'), null, true );
		
		wp_enqueue_script( 'fancybox', $theme_uri . '/assets/libs/fancybox/jquery.fancybox.min.js', array('jquery', 'slick'), null, true );
		
		wp_enqueue_script( 'main-script', $theme_uri . '/assets/js/script.js', array('jquery', 'slick', 'fancybox'), $main_script_ver, true );
		
		// Здесь можно добавить логику подписки:
		wp_enqueue_style('plyr-css', get_template_directory_uri() . '/assets/css/plyr.css');
		
		wp_enqueue_style('plyr-audio-custom', get_template_directory_uri() . '/assets/css/plyr-custom.css');
		
		// - Добавление в базу данных
		wp_enqueue_script('plyr-js', get_template_directory_uri() . '/assets/js/plyr.min.js', array(), '3.7.8', true);
		
		// - Интеграция с сервисом рассылок (Mailchimp, SendPulse и т.д.)
		wp_enqueue_script('practice-player', get_template_directory_uri() . '/assets/js/practice-player.js', 
        array('plyr-js', 'jquery'), $practice_player_script_ver, true);
		
		
		
		
		// Локализация базовых строк (переводы/подписи)
		wp_localize_script('practice-js', 'practiceI18n', [
		'pause' => 'Пауза',
		'play' => 'Пуск',
		'next' => 'Далее',
		'prev' => 'Назад',
		'stage' => 'Этап',
		'locked' => 'Доступ только по подписке',
		'demo_over' => 'Демо-фрагмент завершён',
		]);
	}
	add_action( 'wp_enqueue_scripts', 'my_theme_scripts' );
	
	// Пример: сохранение в опции WordPress
	// Отправка email администратору (опционально)
function yoga_subscribe_handler() {
		// Проверка nonce для безопасности
		if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
			wp_die('Ошибка безопасности');
		}
		
		$email = sanitize_email($_POST['email']);
		
		if (!is_email($email)) {
			wp_send_json_error('Некорректный email адрес');
		}
		
		// Здесь можно добавить логику подписки:
		// Обработка AJAX комментариев
		// Проверка nonce - используем ваш 'yoga_ajax_nonce'
		// Определяем автора используя ваши данные
		
		// Данные комментария
		$subscribers = get_option('yoga_subscribers', array());
		if (!in_array($email, $subscribers)) {
			$subscribers[] = $email;
			update_option('yoga_subscribers', $subscribers);
			
			// Отправка email администратору (опционально)
			$admin_email = get_option('admin_email');
			$subject = 'Новый подписчик на сайте ' . get_bloginfo('name');
			$message = "Новый email подписчика: $email\n";
			$message .= "Время: " . current_time('mysql') . "\n";
			wp_mail($admin_email, $subject, $message);
		}
		
		wp_send_json_success('Подписка успешно оформлена');
	}
	add_action('wp_ajax_yoga_subscribe', 'yoga_subscribe_handler');
	add_action('wp_ajax_nopriv_yoga_subscribe', 'yoga_subscribe_handler');
	
	// Обработка ответов на комментарии
	function yoga_ajax_localization() {

		$current_user = wp_get_current_user();
		$question_success_page = get_page_by_path('question-sent');
		$question_success_url = $question_success_page
			? get_permalink($question_success_page)
			: home_url('/question-sent/');

		$yoga_ajax_data = array(
			'ajax_url'       => admin_url('admin-ajax.php'),
			'nonce'          => wp_create_nonce('yoga_ajax_nonce'),
			'user_id'        => get_current_user_id(),
			'user_logged_in' => is_user_logged_in(),
			'user_email'     => $current_user->user_email,
			'email_verification_nonce' => wp_create_nonce('yoga_email_verification'),
			'site_url'       => home_url(),
			'sprite_url'     => add_query_arg(
				'ver',
				file_exists(get_template_directory() . '/assets/svg/sprite.svg')
					? (string) filemtime(get_template_directory() . '/assets/svg/sprite.svg')
					: wp_get_theme()->get('Version'),
				get_template_directory_uri() . '/assets/svg/sprite.svg'
			),
			'question_success_url' => $question_success_url,
			'post_id'        => get_the_ID(),
			'smartcaptcha_enabled' => function_exists('yoga_smartcaptcha_is_enforced') && yoga_smartcaptcha_is_enforced(),
			'smartcaptcha_sitekey' => function_exists('yoga_smartcaptcha_client_key') ? yoga_smartcaptcha_client_key() : '',
			'notification_preferences' => function_exists('yoga_get_user_notification_preferences')
				? yoga_get_user_notification_preferences((int) get_current_user_id())
				: array(),
			'lk_section_by_target' => function_exists('yoga_get_lk_section_by_target') ? yoga_get_lk_section_by_target() : array(),
		);

		wp_localize_script('main-script', 'yoga_ajax', $yoga_ajax_data);

		if ($yoga_ajax_data['smartcaptcha_enabled']) {
			wp_register_script(
				'yandex-smartcaptcha',
				'https://smartcaptcha.cloud.yandex.ru/captcha.js',
				array(),
				null,
				true
			);
			wp_script_add_data('yandex-smartcaptcha', 'strategy', 'defer');
			wp_enqueue_script('yandex-smartcaptcha');
		}
	}
	add_action('wp_enqueue_scripts', 'yoga_ajax_localization');
	
if (!function_exists('yoga_get_user_public_name')) {
	function yoga_get_user_public_name(int $user_id): string {
		if ($user_id <= 0) {
			return '';
		}

		$user = get_userdata($user_id);
		if (!$user) {
			return '';
		}

		$first_name = trim((string) get_user_meta($user_id, 'first_name', true));
		$last_name = trim((string) get_user_meta($user_id, 'last_name', true));
		$full_name = trim($first_name . ' ' . $last_name);

		if ($full_name !== '') {
			return $full_name;
		}
		if ($first_name !== '') {
			return $first_name;
		}
		if (!empty($user->display_name)) {
			return (string) $user->display_name;
		}

		return (string) $user->user_login;
	}
}

if (!function_exists('yoga_get_user_avatar_id')) {
	function yoga_get_user_avatar_id(int $user_id): int {
		if ($user_id <= 0) {
			return 0;
		}

		$avatar_id = function_exists('get_field')
			? (int) get_field('user_avatar', 'user_' . $user_id)
			: (int) get_user_meta($user_id, 'user_avatar', true);

		return $avatar_id > 0 && get_post_type($avatar_id) === 'attachment' ? $avatar_id : 0;
	}
}

if (!function_exists('yoga_get_user_avatar_html')) {
	function yoga_get_user_avatar_html(int $user_id, int $size = 60, string $class = 'avatar'): string {
		if ($user_id > 0) {
			$avatar_id = yoga_get_user_avatar_id($user_id);
			if ($avatar_id > 0) {
				$attachment = wp_get_attachment_image(
					$avatar_id,
					array($size, $size),
					false,
					array(
						'class' => $class,
						'alt' => '',
						'loading' => 'lazy',
						'decoding' => 'async',
					)
				);
				if (!empty($attachment)) {
					return $attachment;
				}
			}
		}

		return get_avatar($user_id, $size, '', '', array('class' => $class));
	}
}

/**
 * Комментарий оставлен текущим залогиненным пользователем (по user_id или по email для legacy).
 */
function yoga_comment_is_owned_by_logged_in_user(WP_Comment $comment): bool {
	if (!is_user_logged_in()) {
		return false;
	}
	$current_id = (int) get_current_user_id();
	if ($current_id <= 0) {
		return false;
	}
	$comment_uid = (int) $comment->user_id;
	if ($comment_uid > 0 && $comment_uid === $current_id) {
		return true;
	}
	$user = wp_get_current_user();
	if (!$user || trim((string) $user->user_email) === '') {
		return false;
	}
	$c_email = trim((string) $comment->comment_author_email);
	return $c_email !== '' && strcasecmp($c_email, trim((string) $user->user_email)) === 0;
}

/**
 * После wp_new_comment/wp_insert_comment иногда остаётся user_id = 0 или пустой email — чиним привязку к автору.
 */
function yoga_practice_comment_fix_author_binding(int $comment_id, int $author_user_id): void {
	if ($comment_id <= 0 || $author_user_id <= 0) {
		return;
	}
	$c = get_comment($comment_id);
	if (!$c instanceof WP_Comment) {
		return;
	}
	$user = get_userdata($author_user_id);
	if (!$user) {
		return;
	}
	$updates = array('comment_ID' => $comment_id);
	if ((int) $c->user_id !== $author_user_id) {
		$updates['user_id'] = $author_user_id;
	}
	$email = trim((string) $user->user_email);
	if ($email !== '' && strcasecmp(trim((string) $c->comment_author_email), $email) !== 0) {
		$updates['comment_author_email'] = $email;
	}
	if (count($updates) > 1) {
		wp_update_comment($updates);
	}
}

/**
 * Типы записей, где включён единый AJAX-блок комментариев (практика, блог).
 */
function yoga_ajax_comment_supported_post_types(): array {
	return array('practice', 'post');
}

/**
 * Разрешить редактирование/удаление своего комментария без current_user_can('edit_comment'):
 * для CPT practice/post у автора часто нет edit_post на родительской записи.
 */
function yoga_user_can_manage_own_theme_comment(int $comment_id): bool {
	$c = get_comment($comment_id);
	if (!$c instanceof WP_Comment) {
		return false;
	}
	$post = get_post((int) $c->comment_post_ID);
	if (!$post instanceof WP_Post || !in_array($post->post_type, yoga_ajax_comment_supported_post_types(), true)) {
		return false;
	}
	return yoga_comment_is_owned_by_logged_in_user($c);
}

// Определяем автора
// Axecode.tech: шаблон комментария с разделением собственных/чужих действий.
// Зачем: один рендер-блок для списка, редактирования и ответа без дублирования HTML.
function custom_comment_template(WP_Comment $comment, array $args, int $depth) {
    $GLOBALS['comment'] = $comment;
    $is_own_comment = yoga_comment_is_owned_by_logged_in_user($comment);
    $comment_user_id = (int) $comment->user_id;
    $comment_author_name = trim((string) $comment->comment_author);

    if ($comment_user_id > 0) {
        $resolved_author_name = yoga_get_user_public_name($comment_user_id);
        if ($resolved_author_name !== '') {
            $comment_author_name = $resolved_author_name;
        }
    }
    if ($comment_author_name === '') {
        $comment_author_name = 'Пользователь';
    }

    $comment_avatar_html = $comment_user_id > 0
        ? yoga_get_user_avatar_html($comment_user_id, 60, 'avatar')
        : get_avatar($comment, 60, '', '', array('class' => 'avatar'));
    $yoga_sprite_href = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg');
    ?>
    <div class="praktika-comment <?php echo ($depth > 0) ? 'sub-answer' : ''; ?>" id="comment-<?php comment_ID(); ?>">
        <div class="praktika-comment-item <?php echo $is_own_comment ? 'praktika-comment-item_own' : ''; ?>">
            <div class="praktika-comment-item__main">
                <div class="praktika-comment-img">
                    <?php echo $comment_avatar_html; ?>
                </div>
                <b class="praktika-comment-name">
                    <?php echo esc_html($comment_author_name); ?>
                </b>
                <span class="praktika-comment-time">
                    <?php printf(_x('%s назад', '%s = human-readable time difference', 'textdomain'), human_time_diff(get_comment_time('U'), current_time('timestamp'))); ?>
                </span>
                <div class="praktika-comment-item__main-action">
                    <?php if ($is_own_comment): ?>
                        <div class="your-comm">
                            <button type="button" class="your-comm__btn your-comm__btn_edit" aria-label="<?php esc_attr_e('Редактировать комментарий', 'yoga'); ?>">
                                <svg class="your-comm__btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                    <use href="<?php echo esc_url($yoga_sprite_href); ?>#comment-edit"></use>
                                </svg>
                            </button>
                            <button type="button" class="your-comm__btn your-comm__btn_del" aria-label="<?php esc_attr_e('Удалить комментарий', 'yoga'); ?>">
                                <svg class="your-comm__btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                    <use href="<?php echo esc_url($yoga_sprite_href); ?>#comment-delete"></use>
                                </svg>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="answer-btn" role="button" tabindex="0">
                            <span>Ответить</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="praktika-comment-item__text">
                <?php 
                if ($comment->comment_parent != 0) {
                    $parent_comment = get_comment($comment->comment_parent);
                    if ($parent_comment) {
                        $parent_author_name = trim((string) $parent_comment->comment_author);
                        if ((int) $parent_comment->user_id > 0) {
                            $resolved_parent_name = yoga_get_user_public_name((int) $parent_comment->user_id);
                            if ($resolved_parent_name !== '') {
                                $parent_author_name = $resolved_parent_name;
                            }
                        }
                        echo '<b>@' . esc_html($parent_author_name) . '</b> ';
                    }
                }
                comment_text(); 
                ?>
            </div>
            
            <!-- Форма редактирования (только для своих комментариев) -->
            <?php if ($is_own_comment): ?>
            <form class="praktika-comment-item__edit hidden" id="edit-form-<?php echo $comment->comment_ID; ?>">
                <div class="answer-main answer-main_comment-edit">
                    <textarea name="comment_content" class="input textarea-resize" rows="1"><?php echo esc_textarea($comment->comment_content); ?></textarea>
                    <button type="button" class="btn btn_comment-update">
                        <?php esc_html_e('Обновить', 'yoga'); ?>
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>

        <!-- Форма ответа -->
        <div class="praktika-comment__answer hidden" id="reply-form-<?php echo $comment->comment_ID; ?>">
            <div class="answer-main">
                <div class="answer-main__image">
                    <?php echo yoga_get_user_avatar_html(get_current_user_id(), 40, 'avatar'); ?>
                </div>
                <textarea name="reply_content" class="input textarea-resize" placeholder="Ваш ответ" rows="1"></textarea>
                <button type="button" class="btn">
                    Отправить
                </button>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Дерево комментариев для AJAX-блока (практика и запись блога).
 */
function yoga_render_threaded_ajax_comments_list(int $post_id): void {
	if ($post_id <= 0) {
		return;
	}
	$comments = get_comments(array(
		'post_id' => $post_id,
		'status' => 'approve',
		'order' => 'ASC',
	));
	if (empty($comments)) {
		echo '<p>' . esc_html__('Пока нет комментариев. Будьте первым!', 'yoga') . '</p>';
		return;
	}
	echo '<div class="praktika-comments-list">';
	$comments_by_parent = array();
	foreach ($comments as $comment) {
		if ($comment instanceof WP_Comment) {
			$comments_by_parent[(int) $comment->comment_parent][] = $comment;
		}
	}
	$display_comments_tree = static function ($parent_id, $comments_by_parent, $depth = 0) use (&$display_comments_tree) {
		if (!isset($comments_by_parent[$parent_id])) {
			return;
		}
		foreach ($comments_by_parent[$parent_id] as $comment) {
			custom_comment_template($comment, array('max_depth' => 5), $depth);
			if ($depth < 4) {
				echo '<div class="praktika-comment__sub-answers">';
				$display_comments_tree((int) $comment->comment_ID, $comments_by_parent, $depth + 1);
				echo '</div>';
			}
		}
	};
	$display_comments_tree(0, $comments_by_parent);
	echo '</div>';
}

/** Render one comment with the same markup used by the initial comments tree. */
function yoga_render_ajax_comment(int $comment_id): string {
	$comment = get_comment($comment_id);
	if (!$comment instanceof WP_Comment) {
		return '';
	}

	$depth = 0;
	$parent_id = (int) $comment->comment_parent;
	while ($parent_id > 0 && $depth < 4) {
		$depth++;
		$parent = get_comment($parent_id);
		$parent_id = $parent instanceof WP_Comment ? (int) $parent->comment_parent : 0;
	}

	ob_start();
	custom_comment_template($comment, array('max_depth' => 5), $depth);
	return (string) ob_get_clean();
}

/** Return the freshly rendered comment so the client does not reload a cached page. */
function yoga_comment_ajax_success(int $comment_id): void {
	$comment = get_comment($comment_id);
	if (!$comment instanceof WP_Comment) {
		wp_send_json_error('Комментарий сохранён, но не удалось обновить список');
	}

	wp_send_json_success(array(
		'comment_id' => $comment_id,
		'parent_id'  => (int) $comment->comment_parent,
		'html'       => yoga_render_ajax_comment($comment_id),
	));
}

add_action('yoga_send_new_comment_notifications', static function (int $comment_id): void {
	wp_new_comment_notify_moderator($comment_id);
	wp_new_comment_notify_postauthor($comment_id);
});

/**
 * Insert a comment without making the visitor wait for WordPress email delivery.
 * Validation and flood protection still run through wp_new_comment when requested.
 *
 * @return int|false|WP_Error
 */
function yoga_insert_ajax_comment(array $comment_data, bool $validate = true) {
	remove_action('comment_post', 'wp_new_comment_notify_moderator');
	remove_action('comment_post', 'wp_new_comment_notify_postauthor');

	try {
		$comment_id = $validate
			? wp_new_comment($comment_data, true)
			: wp_insert_comment($comment_data);
	} finally {
		add_action('comment_post', 'wp_new_comment_notify_moderator');
		add_action('comment_post', 'wp_new_comment_notify_postauthor');
	}

	if (!is_wp_error($comment_id) && (int) $comment_id > 0) {
		wp_schedule_single_event(time() + 5, 'yoga_send_new_comment_notifications', array((int) $comment_id));
	}

	return $comment_id;
}

// Обновление комментариев (только для зарегистрированных пользователей)
add_action('wp_ajax_submit_custom_comment', 'handle_custom_comment');
add_action('wp_ajax_nopriv_submit_custom_comment', 'handle_custom_comment');

function handle_custom_comment() {
    // Проверяем, что пользователь может редактировать комментарий
    if (!isset($_POST['comment_security']) || !wp_verify_nonce($_POST['comment_security'], 'yoga_ajax_nonce')) {
        wp_send_json_error('Ошибка безопасности');
    }

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $comment_content = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';

    $target_post = $post_id > 0 ? get_post($post_id) : null;
    if (!$target_post instanceof WP_Post || !in_array($target_post->post_type, yoga_ajax_comment_supported_post_types(), true)) {
        wp_send_json_error('Комментирование для этой записи недоступно');
    }

    if ($comment_content === '') {
        wp_send_json_error('Введите текст комментария');
    }

    if (!comments_open($post_id)) {
        wp_send_json_error('Комментирование для этой записи закрыто');
    }

    if (!is_user_logged_in() && get_option('comment_registration')) {
        wp_send_json_error('Для отправки комментария необходимо авторизоваться');
    }
    
    // Добавление комментариев (только для зарегистрированных пользователей)
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $comment_author = yoga_get_user_public_name((int) $current_user->ID);
        if ($comment_author === '') {
            $comment_author = $current_user->display_name ?: $current_user->user_login;
        }
        $comment_author_email = $current_user->user_email;
        $user_id = $current_user->ID;
    } else {
        $comment_author = 'Гость';
        $comment_author_email = '';
        $user_id = 0;
    }
    
    // Проверяем, что пользователь может добавлять комментарий
    $comment_data = array(
        'comment_post_ID' => $post_id,
        'comment_content' => $comment_content,
        'comment_author' => $comment_author,
        'comment_author_email' => $comment_author_email,
        'comment_author_url' => '',
        'user_id' => (int) $user_id,
        'comment_approved' => 1
    );
    
    $comment_id = yoga_insert_ajax_comment($comment_data, true);
    
    if (!is_wp_error($comment_id) && $comment_id) {
        yoga_practice_comment_fix_author_binding((int) $comment_id, is_user_logged_in() ? (int) get_current_user_id() : 0);
        yoga_comment_ajax_success((int) $comment_id);
    } else {
        $error_message = is_wp_error($comment_id)
            ? $comment_id->get_error_message()
            : '';

        // Fallback: в некоторых окружениях wp_new_comment возвращает 0 без WP_Error.
        if (!$error_message) {
            $fallback_comment_id = yoga_insert_ajax_comment($comment_data, false);
            if ($fallback_comment_id) {
                yoga_practice_comment_fix_author_binding((int) $fallback_comment_id, is_user_logged_in() ? (int) get_current_user_id() : 0);
                yoga_comment_ajax_success((int) $fallback_comment_id);
            }
        }

        global $wpdb;
        if (!$error_message && !empty($wpdb->last_error)) {
            $error_message = 'DB: ' . $wpdb->last_error;
        }

        if (!$error_message) {
            $error_message = 'Ошибка при добавлении комментария';
        }

        error_log('handle_custom_comment failed. post_id=' . $post_id . '; user_id=' . $user_id . '; error=' . $error_message);
        wp_send_json_error($error_message);
    }
}

// Включить поддержку комментариев для custom post type
add_action('wp_ajax_submit_comment_reply', 'handle_comment_reply');
add_action('wp_ajax_nopriv_submit_comment_reply', 'handle_comment_reply');

add_action('yoga_send_comment_reply_email', static function (string $email, string $subject, string $message): void {
	if (is_email($email)) {
		wp_mail($email, $subject, $message);
	}
}, 10, 3);

function handle_comment_reply() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_send_json_error('Ошибка безопасности');
    }

    if (!is_user_logged_in()) {
        wp_send_json_error('Для ответа необходимо авторизоваться');
    }
    
    // Кастомизация аватаров
    $current_user = wp_get_current_user();
    $comment_author = yoga_get_user_public_name((int) $current_user->ID);
    if ($comment_author === '') {
        $comment_author = $current_user->display_name ?: $current_user->user_login;
    }
    $comment_author_email = $current_user->user_email;
    $user_id = $current_user->ID;

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $parent_id = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : 0;
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';

    $reply_post = $post_id > 0 ? get_post($post_id) : null;
    if (!$reply_post instanceof WP_Post || !in_array($reply_post->post_type, yoga_ajax_comment_supported_post_types(), true)) {
        wp_send_json_error('Ответ на комментарий для этой записи недоступен');
    }

    $parent_comment = $parent_id > 0 ? get_comment($parent_id) : null;
    if ($parent_id <= 0 || !$parent_comment) {
        wp_send_json_error('Некорректный родительский комментарий');
    }

    if ((int) $parent_comment->comment_post_ID !== $post_id) {
        wp_send_json_error('Некорректная привязка ответа к комментарию');
    }

    if ($content === '') {
        wp_send_json_error('Введите текст ответа');
    }

    if (!comments_open($post_id)) {
        wp_send_json_error('Комментирование для этой записи закрыто');
    }
    
    $comment_data = array(
        'comment_post_ID' => $post_id,
        'comment_content' => $content,
        'comment_parent' => $parent_id,
        'comment_author' => $comment_author,
        'comment_author_email' => $comment_author_email,
        'user_id' => (int) $user_id,
        'comment_approved' => 1,
    );
    
    $comment_id = yoga_insert_ajax_comment($comment_data, false);
    
    if ($comment_id) {
        yoga_practice_comment_fix_author_binding((int) $comment_id, (int) $user_id);
		$recipient_user_id = (int) $parent_comment->user_id;
		if ($recipient_user_id > 0 && $recipient_user_id !== (int) $user_id) {
			$reply_url = get_permalink($post_id) . '#comment-' . (int) $comment_id;
			$reply_message = sprintf(__('%s ответил(а) на ваш комментарий.', 'yoga'), $comment_author);
			yoga_add_user_notification(
				$recipient_user_id,
				'comment_reply',
				__('Ответ на комментарий', 'yoga'),
				$reply_message,
				$reply_url,
				array('comment_id' => (int) $comment_id, 'post_id' => $post_id)
			);

			if (yoga_notification_preference($recipient_user_id, 'comment_reply_email', true)) {
				$recipient = get_userdata($recipient_user_id);
				if ($recipient instanceof WP_User && is_email($recipient->user_email)) {
					wp_schedule_single_event(time() + 5, 'yoga_send_comment_reply_email', array(
						(string) $recipient->user_email,
						(string) __('Ответ на ваш комментарий', 'yoga'),
						(string) ($reply_message . "\n\n" . $reply_url),
					));
				}
			}
		}
        yoga_comment_ajax_success((int) $comment_id);
    } else {
        wp_send_json_error('Ошибка при добавлении ответа');
    }
}

// Время комментариев на русском
add_action('wp_ajax_update_comment', 'handle_comment_update');

function handle_comment_update() {
    if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_die('Ошибка безопасности');
    }
    
    $comment_id = intval($_POST['comment_id']);
    $comment = get_comment($comment_id);

    if (!$comment instanceof WP_Comment || !yoga_user_can_manage_own_theme_comment($comment_id)) {
        wp_send_json_error('Недостаточно прав для редактирования комментария');
    }
    
    $comment_data = array(
        'comment_ID' => $comment_id,
        'comment_content' => sanitize_textarea_field($_POST['content']),
    );
    
    $result = wp_update_comment($comment_data);
    
    if ($result) {
        wp_send_json_success(array(
			'comment_id' => $comment_id,
			'html' => yoga_render_ajax_comment($comment_id),
		));
    } else {
        wp_send_json_error('Ошибка при обновлении комментария');
    }
}

// Проверка nonce
add_action('wp_ajax_delete_comment', 'handle_comment_delete');

function handle_comment_delete() {
    if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_die('Ошибка безопасности');
    }
    
    $comment_id = intval($_POST['comment_id']);

    if (!yoga_user_can_manage_own_theme_comment($comment_id)) {
        wp_send_json_error('Недостаточно прав для удаления комментария');
    }
    
    $result = wp_delete_comment($comment_id, true);
    
    if ($result) {
        wp_send_json_success('Комментарий удален');
    } else {
        wp_send_json_error('Ошибка при удалении комментария');
    }
}


	
	// Отправка email администратору
	function enable_comments_for_practice(bool $open, int $post_id): bool {
		$post = get_post($post_id);
		if ($post instanceof WP_Post && $post->post_type === 'practice') {
			return true;
		}
		return $open;
	}
	add_filter('comments_open', 'enable_comments_for_practice', 10, 2);
	
	//$to = get_option('admin_email');
	function add_comments_support_for_practice() {
		add_post_type_support('practice', 'comments');
	}
	add_action('init', 'add_comments_support_for_practice');
	
	// Сохранение в базу данных (опционально)
	add_filter('avatar_defaults', 'custom_avatar_defaults');
	function custom_avatar_defaults(array $avatar_defaults): array {
		$avatar_defaults[get_template_directory_uri() . '/assets/img/default-avatar.png'] = 'Default Avatar';
		return $avatar_defaults;
	}
	
	// Сохранение сообщения в базу данных
	function russian_comment_time(string $date, string $d, WP_Comment $comment): string {
		if (!is_admin()) {
			return human_time_diff(get_comment_time('U'), current_time('timestamp')) . ' назад';
		}
		return $date;
	}
	add_filter('get_comment_date', 'russian_comment_time', 10, 3);
	
	// Обработка AJAX подписки
	add_action('wp_ajax_process_contact_form', 'process_contact_form');
	add_action('wp_ajax_nopriv_process_contact_form', 'process_contact_form');
	
	function process_contact_form() {
		// Счетчик пунктов меню
		if (!isset($_POST['contacts_nonce_field']) || !wp_verify_nonce($_POST['contacts_nonce_field'], 'contacts_nonce')) {
			wp_send_json_error(array('message' => 'Ошибка безопасности'));
		}
		
		// Проверяем, является ли это первый пункт меню
		$name = isset($_POST['contacts_name']) ? sanitize_text_field($_POST['contacts_name']) : '';
		$email = isset($_POST['contacts_email']) ? sanitize_email($_POST['contacts_email']) : '';
		$phone = isset($_POST['contacts_phone']) ? sanitize_text_field($_POST['contacts_phone']) : '';
		$message = isset($_POST['contacts_message']) ? sanitize_textarea_field($_POST['contacts_message']) : '';
		
		if (empty($name) || empty($email) || empty($message)) {
			wp_send_json_error(array('message' => 'Пожалуйста, заполните все поля'));
		}
		
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'Пожалуйста, введите корректный email'));
		}
		
		// Добавляем класс main-menu-active-item только первому пункту
		// Создаем ссылку
		$to = 'sshell72@yandex.ru';
		$subject = 'Новое сообщение с формы контактов';
		$body = "
        Имя: $name
        Email: $email
        Телефон: $phone
        Сообщение: $message
		";
		
		$headers = array('Content-Type: text/html; charset=UTF-8');
		
		// Сначала всегда сохраняем обращение в БД, чтобы не терять заявки.
		$saved = save_contact_message($name, $email, $phone, $message);
		$sent = wp_mail($to, $subject, nl2br($body), $headers);
		
		if (!$saved) {
			wp_send_json_error(array('message' => 'Не удалось сохранить сообщение. Попробуйте еще раз.'));
		}
		
		if (!$sent) {
			error_log('process_contact_form: wp_mail failed for email ' . $email);
		}

		
		wp_send_json_success(array('message' => 'Сообщение отправлено успешно!'));
	}
	
	// Увеличиваем счетчик после обработки элемента
	function save_contact_message(string $name, string $email, string $phone, string $message): bool {
		$post_data = array(
		'post_title' => 'Вопрос от ' . $name,
		'post_content' => $message,
		'post_type' => 'question',
		'post_status' => 'publish',
        'meta_input' => array(
		'contact_email' => $email,
		'contact_phone' => $phone,
		'contact_date' => current_time('mysql'),
		'question_source' => 'practice_form'
        )
		);
		
		$post_id = wp_insert_post($post_data, true);
		return !is_wp_error($post_id) && (int) $post_id > 0;
	}
	
	// Сбрасываем счетчик при начале нового уровня меню
	add_action('wp_ajax_process_subscription', 'process_subscription');
	add_action('wp_ajax_nopriv_process_subscription', 'process_subscription');
	
	function yoga_subscription_email_domain_is_valid(string $email): bool {
		$at_pos = strrpos($email, '@');
		if ($at_pos === false || $at_pos >= strlen($email) - 1) {
			return false;
		}
		$domain = strtolower(substr($email, $at_pos + 1));
		if ($domain === '' || strpos($domain, '.') === false) {
			return false;
		}

		if (!function_exists('checkdnsrr')) {
			return true;
		}

		return checkdnsrr($domain, 'MX')
			|| checkdnsrr($domain, 'A')
			|| checkdnsrr($domain, 'AAAA');
	}

	function process_subscription() {
		if (!wp_verify_nonce($_POST['nonce'], 'subscription_nonce')) {
			wp_send_json_error(array('message' => 'Ошибка безопасности'));
		}

		$raw_email = isset($_POST['email']) ? wp_unslash((string) $_POST['email']) : '';
		$raw_trimmed = trim($raw_email);
		$email_len = function_exists('mb_strlen')
			? mb_strlen($raw_trimmed, 'UTF-8')
			: strlen($raw_trimmed);
		if ($email_len > 30) {
			wp_send_json_error(array('message' => 'Email не должен превышать 30 символов'));
		}

		$email = sanitize_email($raw_trimmed);
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'Пожалуйста, введите корректный email'));
		}

		if (!yoga_subscription_email_domain_is_valid($email)) {
			wp_send_json_error(array(
				'message' => 'Такого почтового домена не существует или он не принимает почту. Проверьте адрес.',
			));
		}

		$saved = save_subscription_email($email);
		
		if ($saved) {
			wp_mail(
            get_option('admin_email'),
            'Новая подписка на сайте',
            'Новый email для подписки: ' . $email
			);
			
			wp_send_json_success(array('message' => 'Подписка оформлена успешно!'));
			} else {
			wp_send_json_error(array('message' => 'Ошибка при сохранении подписки'));
		}
	}
	
	function save_subscription_email(string $email): bool {
		$existing_emails = get_option('subscription_emails', array());
		
		if (!in_array($email, $existing_emails)) {
			$existing_emails[] = $email;
			return update_option('subscription_emails', $existing_emails);
		}
		
		return true;
	}
	
	class Custom_Menu_Walker extends Walker_Nav_Menu {
		private $item_counter = 0; // Сбрасываем счетчик при завершении уровня меню
		
		function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
			// Сбрасываем счетчик для новых уровней
			$is_first_item = ($this->item_counter === 0);
			
			// Добавляем классы
			$active_class = $is_first_item ? 'main-menu-active-item' : '';
			
			$output .= '<li class="' . $active_class . '">';
			
			// Добавляем special класс для первого пункта
			$attributes = !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
			$attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
			$attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
			$attributes .= !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';
			
			// Для первого пункта не добавляем ссылку, для остальных - добавляем
			$args_before = is_object($args) ? ($args->before ?? '') : (is_array($args) ? ($args['before'] ?? '') : '');
			$args_link_before = is_object($args) ? ($args->link_before ?? '') : (is_array($args) ? ($args['link_before'] ?? '') : '');
			$args_link_after = is_object($args) ? ($args->link_after ?? '') : (is_array($args) ? ($args['link_after'] ?? '') : '');
			$args_after = is_object($args) ? ($args->after ?? '') : (is_array($args) ? ($args['after'] ?? '') : '');
			$item_title = apply_filters('the_title', $item->title, $item->ID);

			if (!empty($item->url) && function_exists('get_field')) {
				static $about_page_url = null;
				static $about_page_custom_title = null;

				if ($about_page_url === null) {
					$about_page = get_page_by_path('o-nas');
					if ($about_page instanceof WP_Post) {
						$about_page_url = untrailingslashit((string) get_permalink($about_page));
						$about_page_custom_title = trim((string) get_field('about_main_title', $about_page->ID));
					} else {
						$about_page_url = '';
						$about_page_custom_title = '';
					}
				}

				if ($about_page_url !== '' && $about_page_custom_title !== '') {
					$item_url = untrailingslashit((string) $item->url);
					if ($item_url === $about_page_url) {
						$item_title = $about_page_custom_title;
					}
				}
			}

			$normalized_item_title = trim(wp_strip_all_tags((string) $item_title));
			if ($normalized_item_title === 'ЧАСТЫЕ ВОПРОСЫ') {
				$item_title = 'Частые вопросы';
			}
			if ($normalized_item_title === 'О ПРЕПОДАВАТЕЛЕ') {
				$item_title = 'О преподавателе';
			}
			$item_output = $args_before;
			$item_output .= '<a class="ref"' . $attributes . '>';
			$item_output .= $args_link_before . $item_title . $args_link_after;
			
			// Добавляем span с текстом
			if ($is_first_item) {
				$item_output .= '<div class="ref-icon">';
				$item_output .= '<img src="' . get_template_directory_uri() . '/assets/img/menu-ref-icon.png" alt="" class="active">';
				$item_output .= '<img src="' . get_template_directory_uri() . '/assets/img/menu-ref-icon_violet.png" alt="">';
				$item_output .= '</div>';
			}
			
			$item_output .= '</a>';
			$item_output .= $args_after;
			
			$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
			
			// Кастомный walker
			$this->item_counter++;
		}
		
		// Обработка AJAX формы FAQ
		function start_lvl(&$output, $depth = 0, $args = array()) {
			$this->item_counter = 0;
			parent::start_lvl($output, $depth, $args);
		}
		
		// Проверка nonce
		function end_lvl(&$output, $depth = 0, $args = array()) {
			$this->item_counter = 0;
			parent::end_lvl($output, $depth, $args);
		}
	}
	
	class Mobile_Menu_Walker extends Walker_Nav_Menu {
		private $item_count = 0;
		
		function start_lvl(&$output, $depth = 0, $args = array()) {
			$this->item_count = 0; // Валидация данных
		}
		
		function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
			$this->item_count++;
			
			// Проверка обязательных полей
			$class_names = 'mobile-menu-main-item';
			
			// Проверка email
			if ($this->item_count === 1) {
				$class_names .= ' mobile-menu-main-item_sw';
			}
			
			$output .= '<li class="' . $class_names . '">';
			
			// Отправка email администратору
			if ($this->item_count !== 1) {
				$attributes = !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';
				$attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
				$attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
				
				$output .= '<a' . $attributes . '></a>';
			}
			
			// Отправка email
			$output .= '<span>' . apply_filters('the_title', $item->title, $item->ID) . '</span>';
			
			if ($this->item_count === 1) {
				$sprite_href = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg');
				$output .= '<span class="mobile-menu-main-item__chevron" aria-hidden="true">';
				$output .= '<svg class="mobile-menu-main-item__chevron-svg" viewBox="0 0 9 16" width="9" height="16" focusable="false">';
				$output .= '<use href="' . $sprite_href . '#lk-library-chevron" width="100%" height="100%"></use>';
				$output .= '</svg></span>';
			}
		}
		
		function end_el(&$output, $item, $depth = 0, $args = array()) {
			$output .= '</li>';
		}
	}
	
	
	// Создание Custom Post Type для вопросов
	add_action('wp_ajax_faq_contact_form', 'handle_faq_contact_form');
	add_action('wp_ajax_nopriv_faq_contact_form', 'handle_faq_contact_form');
	
	function handle_faq_contact_form() {
		// Добавление метаполей для вопросов
		if (!wp_verify_nonce($_POST['faq_nonce'], 'faq_contact_nonce')) {
			wp_send_json_error(array('message' => 'Ошибка безопасности'));
			exit;
		}
		
		// Сохранение метаполей
		$name = sanitize_text_field($_POST['name']);
		$email = sanitize_email($_POST['email']);
		$message = sanitize_textarea_field($_POST['message']);
		
		// === Сложность ===
		if (empty($name) || empty($email) || empty($message)) {
			wp_send_json_error(array('message' => 'Пожалуйста, заполните все поля'));
			exit;
		}
		
		/* register_taxonomy('practice-difficulty', ['practice'], [
		'label' => 'Сложность',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Важно для отображения в новом редакторе
		'rewrite' => ['slug' => 'duration'],
		'show_admin_column' => true, // Показывать колонку в списке записей
		]);
		
		// === Продолжительность ===
		register_taxonomy('practice-duration', ['practice'], [
		'label' => 'Продолжительность',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Важно для отображения в новом редакторе
		'rewrite' => ['slug' => 'duration'],
		'show_admin_column' => true, // Показывать колонку в списке записей
		]);
		
		// === Цель ===
		register_taxonomy('practice-goal', ['practice'], [
		'label' => 'Цели',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Важно для отображения в новом редакторе
		'rewrite' => ['slug' => 'goal'],
		'show_admin_column' => true, // Показывать колонку в списке записей
	]); */
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'Пожалуйста, введите корректный email'));
			exit;
		}
		
		// Axecode.tech: нормализация UTF-8 в email-блоке FAQ.
		$to = get_option('admin_email');
		$subject = 'Новый вопрос из раздела FAQ: ' . $name;
		$headers = array('Content-Type: text/html; charset=UTF-8');
		
		$body = "
        <h3>Новый вопрос из раздела FAQ</h3>
        <p><strong>Имя:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Вопрос:</strong></p>
        <p>" . nl2br($message) . "</p>
        <hr>
        <p><small>Сообщение отправлено с сайта " . get_bloginfo('name') . "</small></p>
		";
		
		$post_id = wp_insert_post(array(
			'post_title' => 'Вопрос от ' . $name,
			'post_content' => $message,
			'post_type' => 'question',
			'post_status' => 'publish',
            'meta_input' => array(
			'contact_email' => $email,
			'contact_date' => current_time('mysql')
            )
		), true);
		
		if (is_wp_error($post_id) || !$post_id) {
			wp_send_json_error(array('message' => 'Не удалось сохранить вопрос. Попробуйте еще раз.'));
			exit;
		}
		
		// Почту отправляем отдельно: если письмо не ушло, вопрос все равно уже есть в админке.
		$email_sent = wp_mail($to, $subject, $body, $headers);
		$question_success_page = get_page_by_path('question-sent');
		$question_success_url = $question_success_page
			? get_permalink($question_success_page)
			: home_url('/question-sent/');
		
		wp_send_json_success(array(
            'message' => get_field('faq_form_success_message', 'option') ?: 'Ваш вопрос отправлен! Мы ответим вам в ближайшее время.',
            'mail_sent' => (bool) $email_sent,
			'redirect_url' => $question_success_url
		));
		
		exit;
	}
	
	// Фильтр по категории (term_id)
	function register_faq_question_cpt() {
		// Axecode.tech: подписи в админке нормализованы в корректный UTF-8.
		// Зачем: ранее mojibake в labels ломал текст меню в wp-admin.
		register_post_type('faq_question', array(
        'labels' => array(
		'name' => 'Вопросы FAQ',
		'singular_name' => 'Вопрос',
		'menu_name' => 'Вопросы FAQ',
		'add_new' => 'Добавить вопрос',
		'add_new_item' => 'Добавить новый вопрос',
		'edit_item' => 'Редактировать вопрос',
		'new_item' => 'Новый вопрос',
		'view_item' => 'Просмотреть вопрос',
		'search_items' => 'Поиск вопросов',
		'not_found' => 'Вопросы не найдены',
		'not_found_in_trash' => 'Вопросы в корзине не найдены'
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        // `array()` is interpreted by WordPress as the default editor support.
        // Questions are handled entirely through the dedicated metaboxes below.
        'supports' => false,
        'menu_icon' => 'dashicons-format-chat'
		));
	}
	// add_action('init', 'register_faq_question_cpt');
	
	// Поиск по названию и описанию
	function add_faq_question_meta() {
		add_meta_box(
        'faq_question_meta',
        'Информация о вопросе',
        'display_faq_question_meta',
        'faq_question',
        'normal',
        'high'
		);
	}
	// add_action('add_meta_boxes', 'add_faq_question_meta');
	
	function display_faq_question_meta(WP_Post $post): void {
		$email = get_post_meta($post->ID, 'contact_email', true);
		$date = get_post_meta($post->ID, 'contact_date', true);
	?>
    <p><strong>Email:</strong> <?php echo esc_html($email); ?></p>
    <p><strong>Дата:</strong> <?php echo esc_html($date); ?></p>
    <?php
	}
	
	// Фильтры по таксономиям
	function save_faq_question_meta(int $post_id): void {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (!current_user_can('edit_post', $post_id)) return;
		
		if (isset($_POST['contact_email'])) {
			update_post_meta($post_id, 'contact_email', sanitize_email($_POST['contact_email']));
		}
	}
	// add_action('save_post_faq_question', 'save_faq_question_meta');
	
	// Сортировка
	// Формируем HTML ответ
	
	add_action('wp_ajax_filter_practices', 'filter_practices_callback');
	add_action('wp_ajax_nopriv_filter_practices', 'filter_practices_callback');

	if (!function_exists('yoga_is_practice_type_term_available')) {
		/**
		 * Термин practice-type доступен в меню (не «в разработке»).
		 * С ACF — поле practice_type_available; без ACF — term meta practice_type_available / синонимы ACF.
		 *
		 * @param WP_Term|int|string $term Термин, ID или slug.
		 */
		function yoga_is_practice_type_term_available($term): bool {
			if (is_numeric($term)) {
				$t = get_term((int) $term, 'practice-type');
				$term = ($t instanceof WP_Term) ? $t : null;
			} elseif (is_string($term) && $term !== '') {
				$t = get_term_by('slug', $term, 'practice-type');
				$term = ($t instanceof WP_Term) ? $t : null;
			}
			if (!($term instanceof WP_Term) || $term->taxonomy !== 'practice-type') {
				return true;
			}

			$term_id = (int) $term->term_id;
			$meta_key = 'practice_type_available';
			$acf_ref = 'practice-type_' . $term_id;

			if (function_exists('get_field')) {
				$value = get_field($meta_key, $acf_ref);
				if ($value === 0 || $value === '0') {
					return false;
				}
				if ($value === false) {
					return metadata_exists('term', $term_id, $meta_key) ? false : true;
				}
				if ($value === null || $value === '') {
					return true;
				}
				return (bool) $value;
			}

			$raw = get_term_meta($term_id, $meta_key, true);
			if ($raw === '' || $raw === false || $raw === null) {
				return true;
			}
			if (is_string($raw)) {
				$lower = strtolower(trim($raw));
				if (in_array($lower, array('0', 'false', 'no', 'off', ''), true)) {
					return false;
				}
				if (in_array($lower, array('1', 'true', 'yes', 'on'), true)) {
					return true;
				}
			}
			return (bool) $raw;
		}
	}

	if (!function_exists('yoga_get_practice_type_card_data')) {
		/**
		 * @param int $post_id Practice post ID.
		 * @return array<string, string>
		 */
		function yoga_get_practice_type_card_data($post_id) {
			$data = array(
				'term_name' => '',
				'class' => 'library-item_violet',
				'image_url' => '',
			);

			$terms = wp_get_post_terms($post_id, 'practice-type');
			if (empty($terms) || is_wp_error($terms)) {
				return $data;
			}

			$term = $terms[0];
			$data['term_name'] = $term->name;

			$color_value = '';
			$image_value = '';
			$term_ref = 'practice-type_' . (int) $term->term_id;

			if (function_exists('get_field')) {
				$color_value = (string) get_field('practice_type_card_color', $term_ref);
				$image_value = get_field('practice_type_card_image', $term_ref);
			}

			if ($color_value === '') {
				$color_value = (string) get_term_meta((int) $term->term_id, 'practice_type_card_color', true);
			}

			if (!$image_value) {
				$image_value = get_term_meta((int) $term->term_id, 'practice_type_card_image', true);
			}

			$color_value = strtolower(trim((string) $color_value));
			if ($color_value === 'green' || $color_value === 'library-item_green' || $color_value === 'зеленая' || $color_value === 'зелёная') {
				$data['class'] = 'library-item_green';
			} elseif ($color_value === 'violet_alt' || $color_value === 'library-item_violet_alt' || $color_value === 'фиолетовая (прозрачная, вариант 2)') {
				$data['class'] = 'library-item_violet_alt';
			} elseif ($color_value === 'violet' || $color_value === 'default' || $color_value === 'library-item_violet' || $color_value === 'фиолетовая' || $color_value === 'фиолетовая (прозрачная)') {
				$data['class'] = 'library-item_violet';
			} elseif ($color_value === 'pink' || $color_value === 'library-item_pink' || $color_value === 'розовая') {
				$data['class'] = 'library-item_pink';
			}

			if (is_array($image_value)) {
				if (!empty($image_value['url'])) {
					$data['image_url'] = (string) $image_value['url'];
				} elseif (!empty($image_value['ID'])) {
					$data['image_url'] = (string) wp_get_attachment_image_url((int) $image_value['ID'], 'medium');
				}
			} elseif (is_numeric($image_value)) {
				$data['image_url'] = (string) wp_get_attachment_image_url((int) $image_value, 'medium');
			} elseif (is_string($image_value)) {
				$data['image_url'] = $image_value;
			}

			return $data;
		}
	}
	
	function filter_practices_callback() {
		$filters = $_POST['filters'] ?? [];
		if (!is_array($filters)) {
			$filters = [];
		}
		$search  = sanitize_text_field($_POST['search'] ?? '');
		$term_id = intval($_POST['term_id'] ?? 0);
		
		$tax_query = [];
		if ($term_id) {
			$term_ids = array($term_id);
			$children = get_term_children($term_id, 'practice-type');
			if (!is_wp_error($children) && !empty($children)) {
				$term_ids = array_merge($term_ids, array_map('intval', $children));
			}
			$term_ids = array_values(array_unique(array_filter($term_ids, static function ($id) {
				return (int) $id > 0;
			})));

			$tax_query[] = [
            'taxonomy'         => 'practice-type',
            'field'            => 'term_id',
            'terms'            => $term_ids,
            'include_children' => false,
			];
		}
		
		foreach ($filters as $taxonomy => $terms) {
			$taxonomy = sanitize_text_field((string) $taxonomy);
			if ($taxonomy === '' || empty($terms)) {
				continue;
			}
			if (!is_array($terms)) {
				$terms = array($terms);
			}
			$terms = array_values(array_filter(array_map('sanitize_text_field', $terms)));
			if (empty($terms)) {
				continue;
			}
			$tax_query[] = [
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $terms,
			];
		}
		
		$args = [
        'post_type'      => 'practice',
        'posts_per_page' => -1,
		];
		if (!empty($tax_query)) {
			$args['tax_query'] = $tax_query;
		}
		if (!empty($search)) {
			$args['s'] = $search;
		}
		
		$query = new WP_Query($args);
		
		if ($query->have_posts()) {
			while ($query->have_posts()) {
			$query->the_post();
			$card_data = yoga_get_practice_type_card_data(get_the_ID());
			$library_variant_class = $card_data['class'];
			$library_term_image_url = $card_data['image_url'];
			$practice_level_raw = function_exists('yoga_get_practice_level_raw_for_cards')
				? yoga_get_practice_level_raw_for_cards((int) get_the_ID())
				: '';
			$practice_level_label = function_exists('yoga_normalize_practice_level_label')
				? yoga_normalize_practice_level_label($practice_level_raw !== '' ? $practice_level_raw : 'новичок')
				: ($practice_level_raw !== '' ? $practice_level_raw : 'новичок');
			?>
            <div class="library-item<?php echo $library_variant_class ? ' ' . esc_attr($library_variant_class) : ''; ?>">
                <div class="library-item__bg"></div>
                <div class="library-item__cat">
                    <?php echo esc_html($practice_level_label); ?>
                    <a href="<?php the_permalink(); ?>" target="_blank"></a>
				</div>
                <p class="library-item__text"><?php echo get_the_excerpt(); ?></p>
                <div class="library-item__img">
                    <?php if ($library_term_image_url) : ?>
						<img src="<?php echo esc_url($library_term_image_url); ?>" alt="<?php the_title_attribute(); ?>">
					<?php elseif (has_post_thumbnail()) : ?>
						<?php the_post_thumbnail('medium'); ?>
					<?php endif; ?>
				</div>
                <div class="library-item__btn">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/svg/library-card-corner-icon.svg" alt="">
				</div>
                <a href="<?php the_permalink(); ?>" class="library-item__link"></a>
			</div>
			<?php }
			wp_reset_postdata();
			} else {
			echo '<p>Нет практик по выбранным фильтрам.</p>';
		}
		
		wp_die();
	}
	
	// Дополнительные элементы если результатов больше 10
	add_action('wp_ajax_filter_practices_kriyi', 'filter_practices_callback_kriyi');
	add_action('wp_ajax_nopriv_filter_practices_kriyi', 'filter_practices_callback_kriyi');

	add_action('wp_ajax_search_practices_suggest', 'yoga_search_practices_suggest');
	add_action('wp_ajax_nopriv_search_practices_suggest', 'yoga_search_practices_suggest');
	
	function filter_practices_callback_kriyi() {
		// Возвращаем HTML и количество результатов
		check_ajax_referer('yoga_ajax_nonce', 'nonce');
		
		// Очистка корзины и добавление товара с редиректом
		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => -1,
        'post_status' => 'publish'
		);

		$raw_filters = array();
		if (!empty($_POST['filters']) && is_array($_POST['filters'])) {
			$raw_filters = $_POST['filters'];
		}

		$selected_type_terms = array();
		if (!empty($raw_filters['practice-type']) && is_array($raw_filters['practice-type'])) {
			$selected_type_terms = array_values(array_unique(array_map('intval', $raw_filters['practice-type'])));
			$selected_type_terms = array_filter($selected_type_terms, static function ($term_id) {
				return $term_id > 0;
			});
		}

		// Базовый term_id используем только если отдельно не задан фильтр "По типу".
		if (!empty($_POST['term_id']) && empty($selected_type_terms)) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'practice-type',
					'field' => 'term_id',
					'terms' => intval($_POST['term_id'])
				)
			);
		}
		
		$search_term = '';
		if (!empty($_POST['search'])) {
			$search_term = sanitize_text_field($_POST['search']);
		}
		
		// Очищаем корзину
		if (!empty($raw_filters)) {
			$filters = $raw_filters;
			
			if (!isset($args['tax_query'])) {
				$args['tax_query'] = array('relation' => 'AND');
				} else {
				$args['tax_query']['relation'] = 'AND';
			}
			
			foreach ($filters as $taxonomy => $terms) {
				if (!empty($terms)) {
					$args['tax_query'][] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => array_map('intval', $terms),
                    'operator' => 'IN'
					);
				}
			}
		}
		
		// Добавляем товар
		if (!empty($_POST['sort'])) {
			switch ($_POST['sort']) {
				case 'newness':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
				case 'popularity':
				default:
                // На части практик нет views_count, из-за чего meta-сортировка
                // отфильтровывает записи и даёт "Найдено: 0" при переключении типа.
                // Оставляем безопасную сортировку без потерь результатов.
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
			}
		}
		
		if ($search_term !== '') {
			$search_ids = array();
			
			// 1) Стандартный WP-поиск: title/content/excerpt.
			$text_search_args = $args;
			$text_search_args['fields'] = 'ids';
			$text_search_args['posts_per_page'] = -1;
			$text_search_args['s'] = $search_term;
			$text_search_query = new WP_Query($text_search_args);
			if (!empty($text_search_query->posts)) {
				$search_ids = array_merge($search_ids, $text_search_query->posts);
			}
			
			// 2) Поиск по всем ACF/meta значениям (включая "Основной текст") для post_type=practice.
			global $wpdb;
			$like_search = '%' . $wpdb->esc_like($search_term) . '%';
			$meta_sql = $wpdb->prepare(
				"SELECT DISTINCT pm.post_id
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE p.post_type = %s
				   AND p.post_status = %s
				   AND pm.meta_key NOT LIKE %s
				   AND pm.meta_value LIKE %s",
				'practice',
				'publish',
				'\_%',
				$like_search
			);
			$meta_search_ids = $wpdb->get_col($meta_sql);
			if (!empty($meta_search_ids)) {
				$search_ids = array_merge($search_ids, $meta_search_ids);
			}
			
			$search_ids = array_values(array_unique(array_map('intval', $search_ids)));
			$args['post__in'] = !empty($search_ids) ? $search_ids : array(0);
		}
		
		$query = new WP_Query($args);
		$count = $query->found_posts;
		
		// Редирект на checkout
		ob_start();
		
		if ($query->have_posts()) :
		$user_id = get_current_user_id();
		$user_favorites = get_user_meta($user_id, 'favorite_practices', true);
		if (!is_array($user_favorites)) {
			$user_favorites = array();
		}
        $item_count = 0;
        while ($query->have_posts()) : $query->the_post();
		$item_count++;
		$practice_level_raw_k = function_exists('yoga_get_practice_level_raw_for_cards')
			? yoga_get_practice_level_raw_for_cards((int) get_the_ID())
			: '';
		$practice_level = yoga_normalize_practice_level_label($practice_level_raw_k !== '' ? $practice_level_raw_k : 'новичок');
		$practice_description = get_field('short_description') ?: get_the_excerpt();
		$practice_image = yoga_get_practice_card_image_url((int) get_the_ID(), 'large');
		$is_favorite = in_array(get_the_ID(), $user_favorites, true);
		$hidden_class = ($item_count > 10) ? 'hidden' : '';
		$tariff_lock_class = function_exists('yoga_practice_card_tariff_lock_class')
			? yoga_practice_card_tariff_lock_class((int) get_the_ID(), $user_id)
			: '';
	?>
	
	<div class="kriyi-item <?php echo esc_attr(trim($hidden_class . ' ' . $tariff_lock_class)); ?>">
		<div class="kriyi-item__inner">
			<a href="<?php the_permalink(); ?>"></a>
			<span class="kriya-level"><?php echo esc_html($practice_level); ?></span>
			<div class="kriya-info">
				<h3><?php the_title(); ?></h3>
				<p><?php echo esc_html($practice_description); ?></p>
			</div>
			<div class="kriya-media">
				<div class="kriya-img">
					<?php if ($practice_image !== '') : ?>
					<img src="<?php echo esc_url($practice_image); ?>" alt="<?php the_title(); ?>">
					<?php endif; ?>
				</div>
				<div class="kriya-fav fav<?php echo $is_favorite ? ' active' : ''; ?>" data-practice-id="<?php echo get_the_ID(); ?>" role="button" tabindex="0" aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($is_favorite ? 'Убрать' : 'В избранное'); ?>">
					<span class="kriya-fav__icon" aria-hidden="true">
						<svg class="<?php echo !$is_favorite ? 'active' : ''; ?>"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
						<svg class="<?php echo $is_favorite ? 'active' : ''; ?>"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart-filled"></use></svg>
					</span>
					<span class="kriya-fav__text kriya-fav__text--add">В избранное</span>
					<span class="kriya-fav__text kriya-fav__text--remove">Убрать</span>
				</div>
				<div class="kriya-btn">
					<div class="kriya-btn__arrow">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
					</div>   
				</div>
			</div>      
		</div>
	</div>
	
	<?php
        endwhile;
        
        // Отключаем стандартную обработку WooCommerce для тарифов
        if ($count > 10) :
		for ($i = 0; $i < 2; $i++) :
	?>
	<div class="kriyi-item kriyi-item_last hidden">
		<div class="kriyi-item__inner">
			<a href="#"></a>
			<span class="kriya-level">Начинающий</span>
			<div class="kriya-info">
				<h3>Остальные крийи</h3>
				<p>Показать все доступные практики</p>
			</div>
			<div class="kriya-media">
				<div class="kriya-img"></div>
				<div class="kriya-fav kriya-fav--icon-only">
					<span class="kriya-fav__icon" aria-hidden="true">
						<svg class="active"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
						<svg><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart-filled"></use></svg>
					</span>
				</div>
				<div class="kriya-btn">
					<div class="kriya-btn__arrow">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
					</div>   
				</div>
			</div> 
		</div>
	</div>
	<?php
		endfor;
        endif;
        
		else :
        echo '<p class="no-practices">По вашему запросу ничего не найдено.</p>';
		endif;
		
		$html = ob_get_clean();
		
		// Отключаем стандартный редирект
		wp_send_json_success(array(
        'html' => $html,
        'count' => $count
		));
		
		wp_die();
	}

	function yoga_search_practices_suggest() {
		check_ajax_referer('yoga_ajax_nonce', 'nonce');

		$query = sanitize_text_field((string) ($_POST['query'] ?? ''));
		$term_id = isset($_POST['term_id']) ? (int) $_POST['term_id'] : 0;
		$query_lc = function_exists('mb_strtolower') ? mb_strtolower($query, 'UTF-8') : strtolower($query);

		if ($query === '' || mb_strlen($query, 'UTF-8') < 2) {
			wp_send_json_success(array('items' => array()));
		}

		global $wpdb;
		$like_search = '%' . $wpdb->esc_like($query) . '%';

		$term_ids = array();
		if ($term_id > 0) {
			$term_ids[] = $term_id;
			$children = get_term_children($term_id, 'practice-type');
			if (!is_wp_error($children) && !empty($children)) {
				$term_ids = array_merge($term_ids, array_map('intval', $children));
			}
			$term_ids = array_values(array_unique(array_filter($term_ids, static function ($id) {
				return $id > 0;
			})));
		}

		$sql_params = array(
			$like_search,        // has_meta_match CASE
			'practice',
			'publish',
		);

		$tax_join = '';
		$tax_where = '';
		if (!empty($term_ids)) {
			$placeholders = implode(',', array_fill(0, count($term_ids), '%d'));
			$tax_join = " INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
			              INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id ";
			$tax_where = " AND tt.taxonomy = 'practice-type' AND tt.term_id IN ({$placeholders}) ";
			$sql_params = array_merge($sql_params, $term_ids);
		}

		$sql_params[] = $like_search; // title
		$sql_params[] = $like_search; // excerpt
		$sql_params[] = $like_search; // content
		$sql_params[] = 60;           // limit

		$sql = "
			SELECT
				p.ID,
				p.post_title,
				p.post_excerpt,
				p.post_content,
				MAX(CASE WHEN pm.meta_key NOT LIKE '\\\\_%' AND pm.meta_value LIKE %s THEN 1 ELSE 0 END) AS has_meta_match
			FROM {$wpdb->posts} p
			{$tax_join}
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
			WHERE p.post_type = %s
			  AND p.post_status = %s
			  {$tax_where}
			GROUP BY p.ID
			HAVING (
				p.post_title LIKE %s
				OR p.post_excerpt LIKE %s
				OR p.post_content LIKE %s
				OR has_meta_match = 1
			)
			ORDER BY p.post_date DESC
			LIMIT %d
		";

		$prepared_sql = $wpdb->prepare($sql, $sql_params);
		$rows = $wpdb->get_results($prepared_sql, ARRAY_A);
		if (empty($rows)) {
			wp_send_json_success(array('items' => array()));
		}

		$ranked = array();
		foreach ($rows as $row) {
			$post_id = isset($row['ID']) ? (int) $row['ID'] : 0;
			if ($post_id <= 0) {
				continue;
			}

			$title = isset($row['post_title']) ? (string) $row['post_title'] : '';
			$excerpt = isset($row['post_excerpt']) ? (string) $row['post_excerpt'] : '';
			$content = isset($row['post_content']) ? wp_strip_all_tags((string) $row['post_content']) : '';
			$title_lc = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);
			$excerpt_lc = function_exists('mb_strtolower') ? mb_strtolower($excerpt, 'UTF-8') : strtolower($excerpt);
			$content_lc = function_exists('mb_strtolower') ? mb_strtolower($content, 'UTF-8') : strtolower($content);

			$score = 0;
			if ($title_lc === $query_lc) {
				$score += 1000;
			}
			if (strpos($title_lc, $query_lc) === 0) {
				$score += 650;
			} elseif (mb_stripos($title_lc, $query_lc, 0, 'UTF-8') !== false) {
				$score += 450;
			}
			if (mb_stripos($excerpt_lc, $query_lc, 0, 'UTF-8') !== false) {
				$score += 220;
			}
			if (mb_stripos($content_lc, $query_lc, 0, 'UTF-8') !== false) {
				$score += 120;
			}
			if (!empty($row['has_meta_match'])) {
				$score += 150;
			}

			$url = get_permalink($post_id);
			if ($title === '' || !$url || $score <= 0) {
				continue;
			}

			$ranked[] = array(
				'score' => $score,
				'id' => (int) $post_id,
				'title' => (string) $title,
				'url' => (string) $url,
			);
		}

		if (empty($ranked)) {
			wp_send_json_success(array('items' => array()));
		}

		usort($ranked, static function ($a, $b) {
			if ($a['score'] === $b['score']) {
				return strnatcasecmp($a['title'], $b['title']);
			}
			return ($a['score'] > $b['score']) ? -1 : 1;
		});

		$items = array();
		foreach ($ranked as $row) {
			$items[] = array(
				'id' => $row['id'],
				'title' => $row['title'],
				'url' => $row['url'],
			);
			if (count($items) >= 8) {
				break;
			}
		}

		wp_send_json_success(array('items' => $items));
	}
	
	// Альтернативное решение: создаем свою обработку checkout
	function theme_woocommerce_support() {
		add_theme_support('woocommerce');
		
		// Проверяем nonce
	}
	add_action('after_setup_theme', 'theme_woocommerce_support');
	
	// Обрабатываем заказ
	function theme_enqueue_checkout_scripts() {
		if (function_exists('is_checkout') && is_checkout()) {
			wp_enqueue_script('jquery');
			wp_enqueue_script('wc-checkout');
			wp_enqueue_script('wc-country-select');
			wp_enqueue_script('wc-address-i18n');
		}
	}
	add_action('wp_enqueue_scripts', 'theme_enqueue_checkout_scripts');

	/**
	 * Чекаут WooCommerce: для авторизованных подставить имя и фамилию из метаполей профиля (как в ЛК).
	 */
	add_filter('woocommerce_checkout_get_value', 'yoga_wc_checkout_prefill_names_from_profile', 10, 2);
	/**
	 * @param mixed  $value Current checkout field value.
	 * @param string $input Checkout field key.
	 * @return mixed
	 */
	function yoga_wc_checkout_prefill_names_from_profile($value, string $input) {
		if (!function_exists('is_user_logged_in') || !is_user_logged_in()) {
			return $value;
		}
		if ($value !== null && $value !== '' && trim((string) $value) !== '') {
			return $value;
		}
		$user_id = get_current_user_id();
		if ($input === 'billing_first_name') {
			return trim((string) get_user_meta($user_id, 'first_name', true));
		}
		if ($input === 'billing_last_name') {
			return trim((string) get_user_meta($user_id, 'last_name', true));
		}
		return $value;
	}
	
	// Добавляем возможности для пользователей
	add_action('template_redirect', 'fix_checkout_issues');
	function fix_checkout_issues() {
	if (!function_exists('WC')) {
		return;
	}

		if (function_exists('yoga_is_theme_checkout_context') && yoga_is_theme_checkout_context()) {
			if (function_exists('yoga_ensure_wc_cart_session')) {
				yoga_ensure_wc_cart_session();
			}
		}
	}
	
	/* function update_user_profile() {
		if (!isset($_POST['profile_nonce']) || !wp_verify_nonce($_POST['profile_nonce'], 'update_user_profile')) {
        wp_die('Ошибка безопасности');
		}
		
		if (!is_user_logged_in()) {
        wp_die('Вы не авторизованы');
		}
		
		$user_id = get_current_user_id();
		$user_data = array('ID' => $user_id);
		
		// Обновление основных данных
		if (!empty($_POST['first_name'])) {
        $user_data['first_name'] = sanitize_text_field($_POST['first_name']);
		}
		
		if (!empty($_POST['last_name'])) {
        $user_data['last_name'] = sanitize_text_field($_POST['last_name']);
		}
		
		if (!empty($_POST['email'])) {
        $user_data['user_email'] = sanitize_email($_POST['email']);
		}
		
		wp_update_user($user_data);
		
		// Обновление метаполей
		if (!empty($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
		}
		
		if (!empty($_POST['birthdate'])) {
        update_user_meta($user_id, 'birthdate', sanitize_text_field($_POST['birthdate']));
		}
		
		if (!empty($_POST['gender'])) {
        update_user_meta($user_id, 'gender', sanitize_text_field($_POST['gender']));
		}
		
		// Обработка смены пароля
		if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['repeat_password'])) {
        if ($_POST['new_password'] === $_POST['repeat_password']) {
		$user = get_user_by('id', $user_id);
		
		if (wp_check_password($_POST['current_password'], $user->user_pass, $user_id)) {
		wp_set_password($_POST['new_password'], $user_id);
		}
        }
		}
		
		// Обработка загрузки аватара
		if (!empty($_FILES['avatar'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('avatar', 0);
        
        if (!is_wp_error($attachment_id)) {
		update_user_meta($user_id, 'simple_local_avatar', $attachment_id);
        }
		}
		
		wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
		exit;
		}
		add_action('admin_post_update_user_profile', 'update_user_profile');
	add_action('admin_post_nopriv_update_user_profile', 'update_user_profile'); */
	
	
	// AJAX обработчик для обновления профиля
	function add_custom_capabilities() {
		$version = 'yoga_caps_v1';
		if (get_option('yoga_caps_version') === $version) {
			return;
		}
		
		$subscriber = get_role('subscriber');
		if ($subscriber instanceof WP_Role) {
			$subscriber->add_cap('read_private_practices');
			$subscriber->add_cap('edit_user_profile');
		}
		
		update_option('yoga_caps_version', $version, false);
	}
	add_action('after_switch_theme', 'add_custom_capabilities');
	add_action('admin_init', 'add_custom_capabilities');
	
	// Логируем запрос для отладки
	// Проверяем nonce
	// Обновление основных данных
	function yoga_update_profile_ajax() {
		// Обновление метаполей
		// Обработка смены пароля
		if (!isset($_POST['nonce'])) {
			wp_send_json_error('Ошибка безопасности', 400);
		}
		
		if (!wp_verify_nonce($_POST['nonce'], 'yoga_ajax_nonce')) {
			wp_send_json_error('Ошибка безопасности', 403);
		}
		
		if (!is_user_logged_in()) {
			wp_send_json_error('Вы не авторизованы', 401);
		}
		
		$user_id = get_current_user_id();
		$old_user = get_user_by('id', $user_id);
		$old_email = $old_user ? sanitize_email((string) $old_user->user_email) : '';
		$response = array();
		
		try {
			$user_data = array('ID' => $user_id);
			
			// Обработка загрузки аватара
			if (!empty($_POST['first_name'])) {
				$user_data['first_name'] = sanitize_text_field($_POST['first_name']);
			}
			
			if (!empty($_POST['last_name'])) {
				$user_data['last_name'] = sanitize_text_field($_POST['last_name']);
			}
			
			if (!empty($_POST['email'])) {
				$user_data['user_email'] = sanitize_email($_POST['email']);
			}
			
			$update_result = wp_update_user($user_data);
			if (is_wp_error($update_result)) {
				wp_send_json_error($update_result->get_error_message(), 422);
			}
			$new_email = isset($user_data['user_email']) ? sanitize_email($user_data['user_email']) : $old_email;
			if ($new_email !== '' && strcasecmp($new_email, $old_email) !== 0) {
				delete_user_meta($user_id, 'yoga_verified_email');
				delete_user_meta($user_id, 'yoga_email_verified_at');
				if (function_exists('yoga_clear_email_verification_code')) {
					yoga_clear_email_verification_code($user_id);
				}
				delete_user_meta($user_id, 'yoga_email_code_sent_at');
			}

			if (isset($_POST['timezone'])) {
				$timezone = sanitize_text_field(wp_unslash($_POST['timezone']));
				if ($timezone !== '' && in_array($timezone, timezone_identifiers_list(), true)) {
					update_user_meta($user_id, 'timezone', $timezone);
				} else {
					delete_user_meta($user_id, 'timezone');
				}
			}

			if (isset($_POST['phone'])) {
				$phone = sanitize_text_field(wp_unslash($_POST['phone']));
				$digits = preg_replace('/\D+/', '', $phone);
				if ($phone === '' || strlen($digits) < 10) {
					delete_user_meta($user_id, 'phone');
					delete_user_meta($user_id, 'billing_phone');
				} else {
					update_user_meta($user_id, 'phone', $phone);
					update_user_meta($user_id, 'billing_phone', $phone);
				}
			}
			
			if (!empty($_POST['birthdate'])) {
				update_user_meta($user_id, 'birthdate', sanitize_text_field($_POST['birthdate']));
			}
			
			if (!empty($_POST['gender'])) {
				update_user_meta($user_id, 'gender', sanitize_text_field($_POST['gender']));
			}
			
			/* $current_user = wp_get_current_user();
				$user_id = $current_user->ID; */
			if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['repeat_password'])) {
				if ($_POST['new_password'] === $_POST['repeat_password']) {
					$user = get_user_by('id', $user_id);
					
					if (wp_check_password($_POST['current_password'], $user->user_pass, $user_id)) {
						wp_set_password($_POST['new_password'], $user_id);
					}
				}
			}
			
			// Обновляем поле ACF для текущего пользователя
			// Обработчик удаления аватара
			if (!empty($_FILES['avatar'])) {
				require_once(ABSPATH . 'wp-admin/includes/image.php');
				require_once(ABSPATH . 'wp-admin/includes/file.php');
				require_once(ABSPATH . 'wp-admin/includes/media.php');
				
				// Шорткод для истории практик
				
				$attachment_id = media_handle_upload('avatar', 0);
				if (is_wp_error($attachment_id)) {
					wp_send_json_error($attachment_id->get_error_message(), 400);
				}

				$attachment = get_post($attachment_id);
				if ($attachment && $attachment->post_type === 'attachment') {
					$mime_type = get_post_mime_type($attachment_id);
					if (strpos($mime_type, 'image/') === 0) {
						$result = function_exists('update_field')
							? update_field('user_avatar', $attachment_id, 'user_' . $user_id)
							: update_user_meta($user_id, 'user_avatar', $attachment_id);

						if ($result === false && yoga_get_user_avatar_id($user_id) !== (int) $attachment_id) {
							wp_delete_attachment($attachment_id, true);
							wp_send_json_error('Ошибка при обновлении аватара', 500);
						}

						wp_send_json_success([
							'message'    => 'Аватар успешно обновлен',
							'avatar_id'  => (int) $attachment_id,
							'avatar_url' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
						]);
						} else {
						wp_delete_attachment($attachment_id, true);
						wp_send_json_error("Файл не является изображением: $mime_type");
					}
				}
			}
			
			wp_send_json_success('Данные успешно сохранены');
			
			} catch (Exception $e) {
			wp_send_json_error('Не удалось обновить профиль. Попробуйте еще раз.', 500);
		}
	}
	add_action('wp_ajax_update_user_profile', 'yoga_update_profile_ajax');

	function yoga_upload_avatar_ajax() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'yoga_ajax_nonce')) {
			wp_send_json_error('Ошибка безопасности', 403);
		}

		if (!is_user_logged_in()) {
			wp_send_json_error('Не авторизован', 401);
		}

		if (empty($_FILES['avatar']) || !isset($_FILES['avatar']['tmp_name'])) {
			wp_send_json_error('Файл не выбран', 400);
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$user_id = get_current_user_id();
		$old_avatar_id = yoga_get_user_avatar_id($user_id);
		$attachment_id = media_handle_upload('avatar', 0);

		if (is_wp_error($attachment_id)) {
			wp_send_json_error($attachment_id->get_error_message(), 400);
		}

		$mime_type = (string) get_post_mime_type($attachment_id);
		if (strpos($mime_type, 'image/') !== 0) {
			wp_delete_attachment($attachment_id, true);
			wp_send_json_error('Файл не является изображением', 400);
		}

		$result = function_exists('update_field')
			? update_field('user_avatar', $attachment_id, 'user_' . $user_id)
			: update_user_meta($user_id, 'user_avatar', $attachment_id);

		if ($result === false && yoga_get_user_avatar_id($user_id) !== (int) $attachment_id) {
			wp_delete_attachment($attachment_id, true);
			wp_send_json_error('Не удалось сохранить аватар', 500);
		}

		if ($old_avatar_id > 0 && $old_avatar_id !== (int) $attachment_id) {
			wp_delete_attachment($old_avatar_id, true);
		}

		wp_send_json_success([
			'avatar_id'  => (int) $attachment_id,
			'avatar_url' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
		]);
	}
	add_action('wp_ajax_upload_user_avatar', 'yoga_upload_avatar_ajax');

	// Функция для получения рекомендованных практик
	function delete_avatar_ajax() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'yoga_ajax_nonce')) {
			wp_send_json_error('Ошибка безопасности');
		}
		
		if (!is_user_logged_in()) {
			wp_send_json_error('Не авторизован');
		}
		
		$user_id = get_current_user_id();
		$avatar_id = yoga_get_user_avatar_id($user_id);

		if ($avatar_id > 0) {
			if (function_exists('delete_field')) {
				delete_field('user_avatar', 'user_' . $user_id);
			} else {
				delete_user_meta($user_id, 'user_avatar');
			}

			if (get_post_type($avatar_id) === 'attachment') {
				wp_delete_attachment($avatar_id, true);
			}
		}

		delete_user_meta($user_id, 'simple_local_avatar');
		
		wp_send_json_success('Аватар удален');
	}
	add_action('wp_ajax_delete_avatar', 'delete_avatar_ajax');
	
	// Если пользователь новый, показываем популярные практики
	function practice_history_shortcode() {
		if (!is_user_logged_in()) return '';
		
		$user_id = get_current_user_id();
		$completed_practices = get_user_meta($user_id, 'completed_practices', true);
		
		if (empty($completed_practices)) {
			return '<p>Вы еще не завершили ни одной практики.</p>';
		}
		
		ob_start();
	?>
    <div class="lk-kriyi">
        <div class="kriyi">
            <div class="kriyi__items">
                <?php 
					foreach ($completed_practices as $practice_id) {
						$practice = get_post($practice_id);
						if ($practice) {
							$level = get_the_terms($practice_id, 'practice-type');
							$level_name = !empty($level) ? $level[0]->name : 'Не указан';
						?>
                        <div class="kriyi-item">
                            <div class="kriyi-item__inner">
                                <a href="<?php echo get_permalink($practice_id); ?>"></a>
                                <span class="kriya-level"><?php echo $level_name; ?></span>
                                <div class="kriya-info">
                                    <h3><?php echo get_the_title($practice_id); ?></h3>
                                    <p><?php echo get_the_excerpt($practice_id); ?></p>
								</div>
                                <div class="kriya-media">
                                    <div class="kriya-img">
                                        <?php
										$_ph_hist_url = yoga_get_practice_card_image_url((int) $practice_id, 'medium');
										if ($_ph_hist_url !== '') :
										?>
										<img src="<?php echo esc_url($_ph_hist_url); ?>" alt="<?php echo esc_attr(get_the_title($practice_id)); ?>">
										<?php endif; ?>
									</div>
                                    <div class="kriya-fav kriya-fav--icon-only">
										<span class="kriya-fav__icon" aria-hidden="true">
											<svg class="active"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
											<svg><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart-filled"></use></svg>
										</span>
									</div>
                                    <div class="kriya-btn">
                                        <div class="kriya-btn__arrow">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
										</div>   
									</div>
								</div>      
							</div>
						</div>
                        <?php
						}
					}
				?>
			</div>
		</div>
	</div>
    <?php
		return ob_get_clean();
	}
	add_shortcode('practice_history', 'practice_history_shortcode');
	
	// Получаем практики на основе предпочтений пользователя
	function subscription_settings_shortcode() {
		if (!is_user_logged_in()) return '';
		if (!function_exists('wc_get_orders')) return '';
		
		$user_id = get_current_user_id();
		$orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => 'completed',
        'limit' => -1
		));
		
		ob_start();
	?>
    <div class="lk-settings">
        <div class="lk-settings__slide lk-settings__slide_main active" data-target="1">
            <h2>Настройки подписки</h2>
            <div class="lk-settings-part">
                <div class="lk-settings-item lk-settings-item_main">
                    <div class="lk-settings-item__col">
                        <p class="lk-settings-item__col-text">Ваш тариф:</p>
                        <div class="personal-status">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/personal-status-icon_settings.png" alt="" class="personal-status__img">
                            <span>
                                <?php 
							// 1. По уровню сложности (на основе завершенных практик)
									$active_subscriptions = function_exists('wcs_get_users_subscriptions')
										? wcs_get_users_subscriptions($user_id)
										: array();
									if (!empty($active_subscriptions)) {
										foreach ($active_subscriptions as $subscription) {
											if ($subscription->has_status('active')) {
												echo get_the_title($subscription->get_id());
												break;
											}
										}
										} else {
									echo 'Не активен';
									}
								?>
							</span>
						</div>
					</div>
                    <div class="lk-settings-item__col">
                        <p class="lk-settings-item__col-text">Действует до:</p>
                        <time>
                            <?php
								if (!empty($active_subscriptions)) {
									foreach ($active_subscriptions as $subscription) {
										if ($subscription->has_status('active')) {
											echo $subscription->get_date('end');
											break;
										}
									}
									} else {
								echo '—';
								}
							?>
						</time>
					</div>
				</div>
			</div>
            
            <div class="lk-settings-part">
                <h4>История покупок</h4>
                <?php
					if (!empty($orders)) {
						foreach ($orders as $order) {
						?>
                        <div class="lk-settings-item">
                            <div class="lk-settings-item__col">
                                <time><?php echo $order->get_date_created()->format('d.m.Y'); ?></time>
							</div>
                            <div class="lk-settings-item__col">
                                <div class="lk-settings-item__col-text">
                                    <b><?php echo $order->get_status(); ?></b>
								</div>
                                <p class="lk-settings-item__col-text"><?php echo $order->get_formatted_order_total(); ?></p>
							</div>
						</div>
                        <?php
						}
						} else {
						echo '<p>У вас пока нет завершенных заказов.</p>';
					}
				?>
			</div>
		</div>
	</div>
    <?php
		return ob_get_clean();
	}
	add_shortcode('subscription_settings', 'subscription_settings_shortcode');
	
	// 2. Похожие на избранные
	function get_recommended_practices(int $user_id): array {
		$completed_practices = get_user_meta($user_id, 'completed_practices', true) ?: array();
		$favorite_practices = get_user_meta($user_id, 'favorite_practices', true) ?: array();
		
	// 3. Новые практики, которые пользователь еще не проходил
		if (empty($completed_practices) && empty($favorite_practices)) {
			return get_popular_practices();
		}
		
	// Убираем дубликаты и уже завершенные практики
		$recommended = array();
		
	// Ограничиваем количество рекомендаций
		$user_levels = get_user_practice_levels($user_id);
		if (!empty($user_levels)) {
			$level_practices = get_practices_by_levels($user_levels, 6);
			$recommended = array_merge($recommended, $level_practices);
		}
		
		// Вспомогательные функции
		if (!empty($favorite_practices)) {
			$similar_practices = get_similar_practices($favorite_practices, 4);
			$recommended = array_merge($recommended, $similar_practices);
		}
		
		// Можно реализовать систему подсчета популярности на основе просмотров
		$new_practices = get_new_practices($user_id, 3);
		$recommended = array_merge($recommended, $new_practices);
		
		// Пока просто возвращаем последние практики
		$recommended = array_unique($recommended);
		$recommended = array_diff($recommended, $completed_practices);
		
		// Функция для получения вопросов пользователя
		return array_slice($recommended, 0, 12);
	}
	
	// Функция для отображения вопроса
	function get_user_practice_levels(int $user_id): array {
		$completed_practices = get_user_meta($user_id, 'completed_practices', true) ?: array();
		$levels = array();
		
		foreach ($completed_practices as $practice_id) {
			$practice_levels = wp_get_post_terms($practice_id, 'practice-type', array('fields' => 'ids'));
			$levels = array_merge($levels, $practice_levels);
		}
		
		return array_count_values($levels);
	}
	
	function get_practices_by_levels(array $level_counts, int $limit = 6): array {
		arsort($level_counts);
		$most_common_levels = array_slice(array_keys($level_counts), 0, 2);
		
		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => $limit,
        'tax_query' => array(
		array(
		'taxonomy' => 'practice-type',
		'field' => 'term_id',
		'terms' => $most_common_levels,
		)
        ),
        'fields' => 'ids'
		);
		
		$practices = get_posts($args);
		return $practices;
	}
	
	function get_similar_practices(array $favorite_practice_ids, int $limit = 4): array {
		if (empty($favorite_practice_ids)) return array();
		
		$similar = array();
		
		foreach ($favorite_practice_ids as $practice_id) {
			$practice_levels = wp_get_post_terms($practice_id, 'practice-type', array('fields' => 'ids'));
			
			if (!empty($practice_levels)) {
				$args = array(
                'post_type' => 'practice',
                'posts_per_page' => 2,
                'post__not_in' => $favorite_practice_ids,
                'tax_query' => array(
				array(
				'taxonomy' => 'practice-type',
				'field' => 'term_id',
				'terms' => $practice_levels,
				)
                ),
                'fields' => 'ids'
				);
				
				$similar_practices = get_posts($args);
				$similar = array_merge($similar, $similar_practices);
			}
		}
		
		return array_slice($similar, 0, $limit);
	}
	
	function get_new_practices(int $user_id, int $limit = 3): array {
		$completed_practices = get_user_meta($user_id, 'completed_practices', true) ?: array();
		
		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => $limit,
        'post__not_in' => $completed_practices,
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids'
		);
		
		return get_posts($args);
	}
	
	function get_popular_practices($limit = 8) {
		// Обработчик отправки вопроса
		// Создаем пост вопроса
		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids'
		);
		
		return get_posts($args);
	}
	
	
	function yoga_notification_has_live_source(array $notification): bool {
		if (($notification['type'] ?? '') !== 'question_answer') {
			return true;
		}

		$question_id = absint($notification['question_id'] ?? 0);
		if ($question_id <= 0) {
			return false;
		}

		$question = get_post($question_id);
		return $question instanceof WP_Post
			&& $question->post_type === 'question'
			&& $question->post_status !== 'trash';
	}

	/** @return array<int, array<string, mixed>> */
	function yoga_get_user_notifications(int $user_id, int $limit = 50): array {
		$notifications = get_user_meta($user_id, 'yoga_notifications', true);
		if (!is_array($notifications)) {
			return array();
		}
		$notifications = array_values(array_filter($notifications, static function ($notification): bool {
			return is_array($notification) && yoga_notification_has_live_source($notification);
		}));
		usort($notifications, static function (array $left, array $right): int {
			return strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''));
		});
		return array_slice($notifications, 0, max(1, $limit));
	}

	/** @return array<int, array<string, mixed>> */
	function yoga_get_unread_user_notifications(int $user_id): array {
		$notifications = yoga_get_user_notifications($user_id, 100);
		return array_values(array_filter($notifications, static function (array $notification): bool {
			return empty($notification['read_at']);
		}));
	}

	function yoga_notification_preference(int $user_id, string $key, bool $default = true): bool {
		$preferences = get_user_meta($user_id, 'yoga_notification_preferences', true);
		return is_array($preferences) && array_key_exists($key, $preferences) ? (bool) $preferences[$key] : $default;
	}

	function yoga_get_notification_preference_defaults(): array {
		return array(
			'subscription_expiring_site' => true,
			'subscription_expiring_email' => true,
			'payment_card_expiring_site' => true,
			'payment_card_expiring_email' => false,
			'subscription_ended_site' => true,
			'subscription_ended_email' => true,
			'question_answer_site' => true,
			'question_answer_email' => false,
			'comment_reply_site' => false,
			'comment_reply_email' => true,
			'new_practices_email' => true,
			'new_articles_email' => false,
			'promotions_email' => true,
		);
	}

	function yoga_get_user_notification_preferences(int $user_id): array {
		$preferences = get_user_meta($user_id, 'yoga_notification_preferences', true);
		$preferences = is_array($preferences) ? $preferences : array();
		$result = yoga_get_notification_preference_defaults();
		foreach ($result as $key => $default) {
			if (array_key_exists($key, $preferences)) {
				$result[$key] = (bool) $preferences[$key];
			}
		}
		return $result;
	}

	function yoga_save_notification_preference(): void {
		if (!is_user_logged_in()) wp_send_json_error(null, 401);
		check_ajax_referer('yoga_ajax_nonce', 'nonce');
		$key = sanitize_key((string) ($_POST['key'] ?? ''));
		if (!array_key_exists($key, yoga_get_notification_preference_defaults())) wp_send_json_error(null, 400);
		$preferences = get_user_meta(get_current_user_id(), 'yoga_notification_preferences', true);
		$preferences = is_array($preferences) ? $preferences : array();
		$preferences[$key] = !empty($_POST['enabled']);
		update_user_meta(get_current_user_id(), 'yoga_notification_preferences', $preferences);
		wp_send_json_success();
	}
	add_action('wp_ajax_yoga_save_notification_preference', 'yoga_save_notification_preference');

	function yoga_add_user_notification(int $user_id, string $type, string $title, string $message, string $url = '', array $context = array()): void {
		if ($user_id <= 0) {
			return;
		}
		$site_preference_keys = array(
			'question_answer' => 'question_answer_site',
			'comment_reply' => 'comment_reply_site',
			'subscription_expiring' => 'subscription_expiring_site',
			'payment_card_expiring' => 'payment_card_expiring_site',
			'subscription_ended' => 'subscription_ended_site',
		);
		if (isset($site_preference_keys[$type])) {
			$preference_key = $site_preference_keys[$type];
			$defaults = yoga_get_notification_preference_defaults();
			if (!yoga_notification_preference($user_id, $preference_key, (bool) ($defaults[$preference_key] ?? true))) {
				return;
			}
		}
		$notifications = yoga_get_user_notifications($user_id, 100);
		$notification = array(
			'id' => wp_generate_uuid4(),
			'type' => sanitize_key($type),
			'title' => sanitize_text_field($title),
			'message' => sanitize_text_field($message),
			'url' => esc_url_raw($url),
			'created_at' => current_time('mysql'),
			'read_at' => '',
		);
		if (!empty($context['question_id'])) {
			$notification['question_id'] = absint($context['question_id']);
		}
		if (!empty($context['comment_id'])) {
			$notification['comment_id'] = absint($context['comment_id']);
		}
		if (!empty($context['post_id'])) {
			$notification['post_id'] = absint($context['post_id']);
		}
		$notifications[] = $notification;
		update_user_meta($user_id, 'yoga_notifications', array_slice($notifications, -100));
	}

	function yoga_get_question_notification_user_id(int $question_id): int {
		$author_id = (int) get_post_field('post_author', $question_id);
		if ($author_id > 0) {
			return $author_id;
		}
		$email = sanitize_email((string) get_post_meta($question_id, 'contact_email', true));
		$user = $email !== '' ? get_user_by('email', $email) : false;
		return $user instanceof WP_User ? (int) $user->ID : 0;
	}

	function yoga_remove_question_notifications(int $question_id): void {
		if (get_post_type($question_id) !== 'question') {
			return;
		}

		$user_id = yoga_get_question_notification_user_id($question_id);
		if ($user_id <= 0) {
			return;
		}

		$notifications = get_user_meta($user_id, 'yoga_notifications', true);
		if (!is_array($notifications)) {
			return;
		}

		$remaining = array_values(array_filter($notifications, static function ($notification) use ($question_id): bool {
			return !is_array($notification)
				|| ($notification['type'] ?? '') !== 'question_answer'
				|| absint($notification['question_id'] ?? 0) !== $question_id;
		}));

		if (count($remaining) !== count($notifications)) {
			update_user_meta($user_id, 'yoga_notifications', $remaining);
		}
	}
	add_action('trashed_post', 'yoga_remove_question_notifications');
	add_action('before_delete_post', 'yoga_remove_question_notifications');

	function yoga_cleanup_orphaned_question_notifications(): void {
		if ((int) get_option('yoga_notification_source_schema_version', 0) >= 1) {
			return;
		}

		$user_ids = get_users(array(
			'meta_key' => 'yoga_notifications',
			'fields' => 'ID',
		));
		foreach ($user_ids as $user_id) {
			$notifications = get_user_meta((int) $user_id, 'yoga_notifications', true);
			if (!is_array($notifications)) {
				continue;
			}
			$remaining = array_values(array_filter($notifications, static function ($notification): bool {
				return is_array($notification) && yoga_notification_has_live_source($notification);
			}));
			if (count($remaining) !== count($notifications)) {
				update_user_meta((int) $user_id, 'yoga_notifications', $remaining);
			}
		}

		update_option('yoga_notification_source_schema_version', 1, false);
	}
	add_action('admin_init', 'yoga_cleanup_orphaned_question_notifications');

	function yoga_get_lk_notifications_url(): string {
		return function_exists('yoga_get_lk_section_url') ? yoga_get_lk_section_url('notifications') : home_url('/');
	}

	function yoga_get_lk_questions_url(): string {
		return function_exists('yoga_get_lk_section_url') ? yoga_get_lk_section_url('questions') : home_url('/');
	}

	function yoga_mark_user_notifications_read(int $user_id, string $notification_id = '', bool $mark_all = false): int {
		$notifications = yoga_get_user_notifications($user_id, 100);
		$changed = false;
		foreach ($notifications as &$notification) {
			$is_selected = $notification_id !== '' && hash_equals((string) ($notification['id'] ?? ''), $notification_id);
			if (($mark_all || $is_selected) && empty($notification['read_at'])) {
				$notification['read_at'] = current_time('mysql');
				$changed = true;
			}
		}
		unset($notification);

		if ($changed) {
			update_user_meta($user_id, 'yoga_notifications', $notifications);
		}

		return count(yoga_get_unread_user_notifications($user_id));
	}

	function yoga_get_notification_read_url(array $notification, string $section = 'notifications'): string {
		$notification_id = sanitize_text_field((string) ($notification['id'] ?? ''));
		$url = function_exists('yoga_get_lk_section_url') ? yoga_get_lk_section_url($section) : home_url('/');
		if ($notification_id === '') {
			return $url;
		}

		return add_query_arg(array(
			'read-notification' => $notification_id,
			'_yoga-notification-nonce' => wp_create_nonce('yoga_read_notification_' . $notification_id),
		), $url);
	}

	function yoga_handle_notification_read_route(): void {
		if (!is_user_logged_in() || empty($_GET['read-notification'])) {
			return;
		}

		$notification_id = sanitize_text_field(wp_unslash((string) $_GET['read-notification']));
		$nonce = sanitize_text_field(wp_unslash((string) ($_GET['_yoga-notification-nonce'] ?? '')));
		if ($notification_id === '' || !wp_verify_nonce($nonce, 'yoga_read_notification_' . $notification_id)) {
			return;
		}

		yoga_mark_user_notifications_read((int) get_current_user_id(), $notification_id);
	}
	add_action('template_redirect', 'yoga_handle_notification_read_route', 5);

	function yoga_mark_question_answer_notifications_read(): void {
		if (!is_user_logged_in()) {
			wp_send_json_error(array('message' => __('Необходима авторизация.', 'yoga')), 401);
		}
		check_ajax_referer('yoga_ajax_nonce', 'nonce');

		$user_id = (int) get_current_user_id();
		$mark_all = !empty($_POST['mark_all']);
		$notification_id = sanitize_text_field((string) ($_POST['notification_id'] ?? ''));
		wp_send_json_success(array(
			'unread_count' => yoga_mark_user_notifications_read($user_id, $notification_id, $mark_all),
		));
	}
	add_action('wp_ajax_yoga_mark_question_answer_notifications_read', 'yoga_mark_question_answer_notifications_read');

	// Отправляем уведомление администратору
	function get_user_questions(int $user_id): array {
		$args = array(
        'post_type' => 'question',
        'author' => $user_id,
        'post_status' => array('publish', 'pending', 'draft', 'private'),
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
		);
		
		return get_posts($args);
	}
	
	// Регистрируем тип записи для вопросов
	function display_question_item(WP_Post $question, bool $hidden = false): void {
		$question_id = $question->ID;
		$answers = yoga_get_question_answers($question_id);
		
		$status_class = !empty($answers) ? '' : 'lk-questions-item_new';
		$hidden_class = $hidden ? 'hidden' : '';
	?>
    <div class="lk-questions-item <?php echo $status_class . ' ' . $hidden_class; ?>">
        <div class="lk-question">
            <div class="lk-question__time">
                <time><?php echo get_the_date('d.m.Y', $question_id); ?></time>
                <time><?php echo get_the_time('H:i', $question_id); ?></time>
			</div>
            <div class="lk-question__text">
                <p><?php echo esc_html($question->post_content); ?></p>
			</div>
			<?php if (empty($answers)): ?>
			<span class="lk-question__status">Ожидает ответа</span>
			<?php endif; ?>
		</div>

		<?php foreach ($answers as $answer): ?>
		<?php
			$answer_content = (string) ($answer['content'] ?? '');
			$answer_date = (string) ($answer['created_at'] ?? '');
			$admin_id = (int) ($answer['admin_id'] ?? 0);
			$admin_name = $admin_id > 0 ? (string) get_the_author_meta('display_name', $admin_id) : __('Администратор', 'yoga');
			$answer_timestamp = $answer_date !== '' ? strtotime($answer_date) : false;
		?>
		<div class="lk-question lk-question_answer">
			<div class="lk-question__time">
				<b>Ответ <?php echo esc_html($admin_name); ?></b>
				<?php if ($answer_timestamp): ?>
					<time datetime="<?php echo esc_attr(wp_date('c', $answer_timestamp)); ?>"><?php echo esc_html(wp_date('d.m.Y', $answer_timestamp)); ?></time>
					<time><?php echo esc_html(wp_date('H:i', $answer_timestamp)); ?></time>
				<?php endif; ?>
			</div>
			<div class="lk-question__text">
				<?php echo wpautop(wp_kses_post($answer_content)); ?>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
    <?php
	}
	
	// Добавляем метабокс для ответа на вопрос
	// Axecode.tech: прием вопросов из личного кабинета.
	// Зачем: централизованная валидация nonce/авторизации и единый формат уведомления в админку.
	function handle_question_submission() {
		if (!isset($_POST['question_nonce']) || !wp_verify_nonce($_POST['question_nonce'], 'submit_question')) {
			wp_die('Ошибка безопасности');
		}
		
		if (!is_user_logged_in()) {
			wp_die('Вы не авторизованы');
		}
		
		$question_text = sanitize_textarea_field($_POST['question_text']);
		
		if (empty($question_text)) {
			wp_die('Вопрос не может быть пустым');
		}
		
		$user_id = get_current_user_id();
		
		// Сохранение ответа на вопрос
		$question_data = array(
        'post_title' => 'Вопрос от пользователя ' . $user_id,
        'post_content' => $question_text,
        'post_status' => 'publish',
        'post_type' => 'question',
        'post_author' => $user_id
		);
		
		$question_id = wp_insert_post($question_data);
		
		if (is_wp_error($question_id)) {
			wp_die('Ошибка при сохранении вопроса');
		}
		
		// Если ответ изменился или добавлен новый
		$admin_email = get_option('admin_email');
		$user = get_userdata($user_id);
		$subject = 'Новый вопрос в личном кабинете';
		$message = "Пользователь {$user->display_name} задал новый вопрос:\n\n";
		$message .= $question_text . "\\\\n\\\\n";
		$message .= "Ссылка для ответа: " . admin_url("post.php?post={$question_id}&action=edit");
		
		wp_mail($admin_email, $subject, $message);
		
		wp_redirect(add_query_arg('question_submitted', 'true', wp_get_referer()));
		exit;
	}
	add_action('admin_post_submit_question', 'handle_question_submission');
	add_action('admin_post_nopriv_submit_question', 'handle_question_submission');
	
	// Отправляем уведомление пользователю
	function register_question_post_type() {
		// Axecode.tech: для второго FAQ-подобного CPT также применены UTF-8 labels.
		// Зачем: единая и читаемая навигация в админке без кракозябр.
		$args = array(
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 25,
        'menu_icon' => 'dashicons-format-chat',
        'supports' => array(),
        'labels' => array(
		'name' => 'Вопросы FAQ',
		'singular_name' => 'Вопрос',
		'menu_name' => 'Вопросы FAQ',
		'add_new' => 'Добавить вопрос',
		'add_new_item' => 'Добавить новый вопрос',
		'edit_item' => 'Редактировать вопрос',
		'new_item' => 'Новый вопрос',
		'view_item' => 'Просмотреть вопрос',
		'search_items' => 'Поиск вопросов',
		'not_found' => 'Вопросы не найдены',
		'not_found_in_trash' => 'Вопросы в корзине не найдены'
        )
		);
		
		register_post_type('question', $args);
	}
	add_action('init', 'register_question_post_type');

	/**
	 * Questions are conversation records, not editorial content. Normalize legacy
	 * statuses once so wp-admin does not expose a draft/publishing workflow.
	 */
	function yoga_migrate_question_record_statuses(): void {
		if ((int) get_option('yoga_question_record_schema_version', 0) >= 1) {
			return;
		}
		global $wpdb;

		$question_ids = get_posts(array(
			'post_type' => 'question',
			'post_status' => array('draft', 'pending', 'private'),
			'posts_per_page' => -1,
			'fields' => 'ids',
			'no_found_rows' => true,
			'orderby' => 'none',
		));

		if (!empty($question_ids)) {
			$updated = $wpdb->query($wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_status = %s WHERE post_type = %s AND post_status IN (%s, %s, %s)",
				'publish',
				'question',
				'draft',
				'pending',
				'private'
			));
			if ($updated === false) {
				return;
			}
			foreach ($question_ids as $question_id) {
				clean_post_cache((int) $question_id);
			}
		}

		update_option('yoga_question_record_schema_version', 1, false);
	}
	add_action('admin_init', 'yoga_migrate_question_record_statuses');

	function yoga_question_admin_post_states(array $post_states, WP_Post $post): array {
		if ($post->post_type !== 'question') {
			return $post_states;
		}

		return empty(yoga_get_question_answers((int) $post->ID))
			? array('yoga_new' => __('Новое', 'yoga'))
			: array();
	}
	add_filter('display_post_states', 'yoga_question_admin_post_states', 10, 2);

	/**
	 * Keep the FAQ request screen free of WordPress's post title/editor UI.
	 * This runs after all CPT registration hooks, including plugins.
	 */
	function yoga_question_remove_default_editor(): void {
		remove_post_type_support('question', 'title');
		remove_post_type_support('question', 'editor');
	}
	add_action('init', 'yoga_question_remove_default_editor', 999);
	
	// Функция для получения активной подписки пользователя
	function add_question_answer_meta_box() {
		add_meta_box(
			'question_request',
			'Вопрос пользователя',
			'render_question_request_meta_box',
			'question',
			'normal',
			'high'
		);

		add_meta_box(
        'question_answer',
        'Ответ на вопрос',
        'render_question_answer_meta_box',
        'question',
        'normal',
        'high'
		);
	}
	add_action('add_meta_boxes', 'add_question_answer_meta_box');

	/**
	 * The question screen is a conversation, not a publishing workflow.
	 * Reply actions are handled by the dedicated "Отправить ответ" button.
	 */
	function yoga_question_remove_publish_box(WP_Post $post): void {
		remove_meta_box('submitdiv', 'question', 'side');
	}
	add_action('add_meta_boxes_question', 'yoga_question_remove_publish_box', 100);

	function yoga_question_screen_layout_columns(array $columns): array {
		$columns['question'] = 1;
		return $columns;
	}
	add_filter('screen_layout_columns', 'yoga_question_screen_layout_columns');

	function yoga_question_force_single_column($columns): int {
		return 1;
	}
	add_filter('get_user_option_screen_layout_question', 'yoga_question_force_single_column');

	/** @return array<int, array<string, mixed>> */
	function yoga_get_question_answers(int $post_id): array {
		$answers = get_post_meta($post_id, '_question_answers', true);
		if (is_array($answers)) {
			return $answers;
		}

		// Show an answer saved by the previous implementation without losing it.
		$legacy_answer = (string) get_post_meta($post_id, '_answer', true);
		if (trim(wp_strip_all_tags($legacy_answer)) === '') {
			return array();
		}

		return array(array(
			'content' => $legacy_answer,
			'created_at' => (string) get_post_meta($post_id, '_answer_date', true),
			'admin_id' => (int) get_post_meta($post_id, '_answer_admin', true),
			'sent_at' => (string) get_post_meta($post_id, '_answer_sent_at', true),
			'email' => (string) get_post_meta($post_id, '_answer_sent_email', true),
			'status' => (string) get_post_meta($post_id, '_answer_delivery_status', true),
		));
	}

	function yoga_get_unanswered_questions_count(): int {
		$question_ids = get_posts(array(
			'post_type' => 'question',
			'post_status' => array('publish', 'pending', 'draft', 'private'),
			'posts_per_page' => -1,
			'fields' => 'ids',
			'no_found_rows' => true,
			'orderby' => 'none',
		));

		$count = 0;
		foreach ($question_ids as $question_id) {
			if (empty(yoga_get_question_answers((int) $question_id))) {
				$count++;
			}
		}

		return $count;
	}

	function yoga_add_unanswered_questions_menu_count(): void {
		global $menu;

		$count = yoga_get_unanswered_questions_count();
		if ($count <= 0 || !is_array($menu)) {
			return;
		}

		foreach ($menu as &$menu_item) {
			if (!isset($menu_item[2]) || $menu_item[2] !== 'edit.php?post_type=question') {
				continue;
			}

			$menu_item[0] .= sprintf(
				' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span></span>',
				$count,
				esc_html(sprintf(_n('%d вопрос без ответа', '%d вопросов без ответа', $count, 'yoga'), $count))
			);
			break;
		}
		unset($menu_item);
	}
	add_action('admin_menu', 'yoga_add_unanswered_questions_menu_count', 999);

	function render_question_request_meta_box(WP_Post $post): void {
		$email = sanitize_email((string) get_post_meta($post->ID, 'contact_email', true));
		$date = get_post_meta($post->ID, 'contact_date', true) ?: $post->post_date;
		$answers = yoga_get_question_answers($post->ID);
		$notification_user_id = yoga_get_question_notification_user_id($post->ID);
		$email_notifications_enabled = $notification_user_id <= 0 || yoga_notification_preference($notification_user_id, 'question_answer_email', false);
		wp_nonce_field('manage_question_answers', 'question_answers_nonce');
		?>
		<div class="yoga-question-admin-card">
			<div class="yoga-question-admin-card__meta">
				<span><strong><?php esc_html_e('Получатель ответа:', 'yoga'); ?></strong> <?php echo $email ? esc_html($email) : esc_html__('e-mail не указан', 'yoga'); ?></span>
				<span><strong><?php esc_html_e('Получено:', 'yoga'); ?></strong> <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($date))); ?></span>
			</div>
			<div class="yoga-question-admin-card__message">
				<?php echo wpautop(esc_html($post->post_content)); ?>
			</div>
			<?php foreach ($answers as $answer_index => $answer): ?>
				<?php
				$content = isset($answer['content']) ? (string) $answer['content'] : '';
				$status = isset($answer['status']) ? (string) $answer['status'] : '';
				$sent_at = isset($answer['sent_at']) ? (string) $answer['sent_at'] : '';
				$created_at = isset($answer['created_at']) ? (string) $answer['created_at'] : '';
				$display_status = $status === 'failed' && !$email_notifications_enabled ? 'email_disabled' : $status;
				$status_labels = array(
					'sent' => __('Письмо отправлено', 'yoga'),
					'email_disabled' => __('E-mail-уведомления отключены', 'yoga'),
					'missing_recipient' => __('Не указан e-mail получателя', 'yoga'),
					'failed' => __('Ошибка отправки письма', 'yoga'),
				);
				$status_label = $status_labels[$display_status] ?? __('Письмо не отправлено', 'yoga');
				$status_class = in_array($display_status, array('sent', 'email_disabled', 'failed'), true) ? ' yoga-question-admin-card__status--' . $display_status : '';
				?>
				<div class="yoga-question-admin-card__reply">
					<div class="yoga-question-admin-card__reply-meta">
						<strong><?php esc_html_e('Ответ администратора', 'yoga'); ?></strong>
						<button type="submit" class="button-link-delete yoga-question-admin-card__delete" name="question_delete_answer" value="<?php echo esc_attr((string) $answer_index); ?>" onclick="return window.confirm('Удалить этот ответ?');">
							<?php esc_html_e('Удалить', 'yoga'); ?>
						</button>
						<?php if ($sent_at && $display_status === 'sent'): ?>
							<span class="yoga-question-admin-card__status yoga-question-admin-card__status--sent"><?php echo esc_html($status_label); ?></span>
							<span><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($sent_at))); ?></span>
						<?php else: ?>
							<span class="yoga-question-admin-card__status<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
							<?php if ($created_at): ?><span><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($created_at))); ?></span><?php endif; ?>
						<?php endif; ?>
					</div>
					<div class="yoga-question-admin-card__reply-text">
						<?php echo wpautop(wp_kses_post($content)); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
	
	function render_question_answer_meta_box(WP_Post $post): void {
		wp_nonce_field('save_question_answer', 'answer_nonce');
	?>
		<?php $recipient_email = sanitize_email((string) get_post_meta($post->ID, 'contact_email', true)); ?>
		<div class="yoga-question-answer">
			<div class="yoga-question-answer__head">
				<div>
					<label for="question_answer"><?php esc_html_e('Новый ответ', 'yoga'); ?></label>
					<p><?php esc_html_e('После отправки текст появится под вопросом, а это поле очистится.', 'yoga'); ?></p>
				</div>
				<span class="yoga-question-answer__recipient"><?php echo $recipient_email ? esc_html($recipient_email) : esc_html__('e-mail не указан', 'yoga'); ?></span>
			</div>
			<textarea id="question_answer" name="question_answer" class="large-text" rows="9" placeholder="Напишите ответ пользователю"></textarea>
			<div class="yoga-question-answer__footer">
				<button type="submit" class="button button-primary" name="question_send_reply" value="1">
					<?php esc_html_e('Отправить ответ', 'yoga'); ?>
				</button>
				<span><?php esc_html_e('Каждая отправка создаёт новый ответ и не изменяет предыдущие.', 'yoga'); ?></span>
			</div>
		</div>
    <?php
	}

	function yoga_question_admin_styles(): void {
		$screen = get_current_screen();
		if (!$screen || $screen->post_type !== 'question') {
			return;
		}
		?>
		<style>
			#poststuff #post-body.columns-2 { margin-right: 0; }
			#post-body.columns-2 #postbox-container-1 { display: none; }
			#poststuff #post-body.columns-2 #postbox-container-2 { width: 100%; }
			#question_request .inside, #question_answer .inside { padding: 0; margin: 0; }
			.yoga-question-admin-card, .yoga-question-answer { padding: 20px; }
			.yoga-question-admin-card__meta { display: flex; flex-wrap: wrap; gap: 8px 24px; color: #50575e; font-size: 13px; }
			.yoga-question-admin-card__message { margin-top: 16px; padding: 18px 20px; border-left: 3px solid #2271b1; background: #f6f7f7; font-size: 15px; line-height: 1.6; }
			.yoga-question-admin-card__message > :first-child { margin-top: 0; }
			.yoga-question-admin-card__message > :last-child { margin-bottom: 0; }
			.yoga-question-admin-card__reply { margin-top: 16px; padding: 18px 20px; border: 1px solid #b8d8bd; border-radius: 4px; background: #f6fbf6; }
			.yoga-question-admin-card__reply-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; color: #50575e; font-size: 13px; }
			.yoga-question-admin-card__reply-meta strong { color: #1d2327; font-size: 14px; }
			.yoga-question-admin-card__delete { margin-left: auto; }
			.yoga-question-admin-card__status { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #e7e7e7; color: #50575e; }
			.yoga-question-admin-card__status--sent { background: #d7f0d9; color: #1e6b2b; }
			.yoga-question-admin-card__status--email_disabled { background: #f0f0f1; color: #50575e; }
			.yoga-question-admin-card__status--failed { background: #fce2e3; color: #8a2424; }
			.yoga-question-admin-card__reply-text { margin-top: 12px; font-size: 14px; line-height: 1.6; }
			.yoga-question-admin-card__reply-text > :first-child { margin-top: 0; }
			.yoga-question-admin-card__reply-text > :last-child { margin-bottom: 0; }
			.yoga-question-answer__head, .yoga-question-answer__footer { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
			.yoga-question-answer__head label { display: block; font-weight: 600; font-size: 14px; }
			.yoga-question-answer__head p, .yoga-question-answer__footer span { margin: 4px 0 0; color: #646970; font-size: 13px; }
			.yoga-question-answer__recipient { padding: 6px 10px; border-radius: 999px; background: #f0f6fc; color: #135e96; font-size: 13px; }
			#question_answer textarea.large-text { display: block; width: 100%; min-height: 190px; margin: 16px 0; padding: 12px; border-color: #8c8f94; border-radius: 4px; line-height: 1.5; resize: vertical; }
			.yoga-question-answer__footer { align-items: flex-start; }
			.yoga-question-answer__delivery { margin: -4px 20px 16px; }
			@media (max-width: 782px) { .yoga-question-answer__head, .yoga-question-answer__footer { display: block; } .yoga-question-answer__recipient { display: inline-block; margin-top: 10px; } .yoga-question-answer__footer span { display: block; } }
		</style>
		<?php
	}
	add_action('admin_head-post.php', 'yoga_question_admin_styles');
	add_action('admin_head-post-new.php', 'yoga_question_admin_styles');
	
	// Если используете WooCommerce Subscriptions
	// Axecode.tech: сохранение ответа администратора и отправка email пользователю.
	// Зачем: синхронно фиксируем текст ответа, дату/автора и уведомление в одном хуке.
	function save_question_answer(int $post_id): void {
		if (!isset($_POST['answer_nonce']) || !wp_verify_nonce($_POST['answer_nonce'], 'save_question_answer')) {
			return;
		}
		
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		if (isset($_POST['question_delete_answer']) && isset($_POST['question_answers_nonce']) && wp_verify_nonce($_POST['question_answers_nonce'], 'manage_question_answers')) {
			$answer_index = absint($_POST['question_delete_answer']);
			$answers = yoga_get_question_answers($post_id);
			if (array_key_exists($answer_index, $answers)) {
				array_splice($answers, $answer_index, 1);
				update_post_meta($post_id, '_question_answers', $answers);
			}
			return;
		}

		// A sent reply becomes an immutable history entry; the compose field stays empty after reload.
		if (!empty($_POST['question_send_reply']) && isset($_POST['question_answer'])) {
			$answer = wp_kses_post((string) $_POST['question_answer']);
			if (trim(wp_strip_all_tags($answer)) === '') {
				return;
			}

			$recipient_email = sanitize_email((string) get_post_meta($post_id, 'contact_email', true));
			if ($recipient_email === '') {
				$question_author = get_userdata((int) get_post_field('post_author', $post_id));
				$recipient_email = $question_author ? sanitize_email((string) $question_author->user_email) : '';
			}

			$question = get_post($post_id);
			$subject = sprintf(__('Ответ на ваш вопрос — %s', 'yoga'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
			$question_text = $question ? wp_strip_all_tags((string) $question->post_content) : '';
			$body = '<p>' . esc_html__('Здравствуйте!', 'yoga') . '</p>';
			$body .= '<p><strong>' . esc_html__('Ваш вопрос:', 'yoga') . '</strong><br>' . nl2br(esc_html($question_text)) . '</p>';
			$body .= '<p><strong>' . esc_html__('Ответ:', 'yoga') . '</strong></p>' . wpautop($answer);
			$body .= '<p>' . esc_html__('С уважением, администрация сайта.', 'yoga') . '</p>';
			$notification_user_id = yoga_get_question_notification_user_id($post_id);
			$email_notifications_enabled = $notification_user_id <= 0 || yoga_notification_preference($notification_user_id, 'question_answer_email', false);
			$sent = false;
			if ($recipient_email === '') {
				$delivery_status = 'missing_recipient';
			} elseif (!$email_notifications_enabled) {
				$delivery_status = 'email_disabled';
			} else {
				$sent = wp_mail($recipient_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
				$delivery_status = $sent ? 'sent' : 'failed';
			}

			$answers = yoga_get_question_answers($post_id);
			$answers[] = array(
				'content' => $answer,
				'created_at' => current_time('mysql'),
				'admin_id' => get_current_user_id(),
				'sent_at' => $sent ? current_time('mysql') : '',
				'email' => $recipient_email,
				'status' => $delivery_status,
			);
			update_post_meta($post_id, '_question_answers', $answers);

			$notification_user_id = yoga_get_question_notification_user_id($post_id);
			if ($notification_user_id > 0) {
				yoga_add_user_notification(
					$notification_user_id,
					'question_answer',
					__('Получен ответ на ваш вопрос', 'yoga'),
					__('Администратор ответил на ваш вопрос. Откройте раздел «Мои вопросы».', 'yoga'),
					yoga_get_lk_questions_url(),
					array('question_id' => $post_id)
				);
			}
			return;
		}
		
		if (isset($_POST['question_answer'])) {
			$answer = wp_kses_post($_POST['question_answer']);
			$old_answer = get_post_meta($post_id, '_answer', true);
			
			update_post_meta($post_id, '_answer', $answer);
			if ($answer !== $old_answer) {
				update_post_meta($post_id, '_answer_date', current_time('mysql'));
				update_post_meta($post_id, '_answer_admin', get_current_user_id());
			}

			// Обычное сохранение ответа не отправляет письмо: для этого есть отдельная кнопка в метабоксе.
			if (empty($_POST['question_send_reply'])) {
				return;
			}

			if (trim(wp_strip_all_tags($answer)) === '') {
				update_post_meta($post_id, '_answer_delivery_status', 'empty_answer');
				return;
			}

			$recipient_email = sanitize_email((string) get_post_meta($post_id, 'contact_email', true));
			if ($recipient_email === '') {
				$question_author = get_userdata((int) get_post_field('post_author', $post_id));
				$recipient_email = $question_author ? sanitize_email((string) $question_author->user_email) : '';
			}

			if ($recipient_email === '') {
				update_post_meta($post_id, '_answer_delivery_status', 'missing_recipient');
				return;
			}

			$question = get_post($post_id);
			$subject = sprintf(__('Ответ на ваш вопрос — %s', 'yoga'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
			$question_text = $question ? wp_strip_all_tags((string) $question->post_content) : '';
			$body = '<p>' . esc_html__('Здравствуйте!', 'yoga') . '</p>';
			$body .= '<p><strong>' . esc_html__('Ваш вопрос:', 'yoga') . '</strong><br>' . nl2br(esc_html($question_text)) . '</p>';
			$body .= '<p><strong>' . esc_html__('Ответ:', 'yoga') . '</strong></p>' . wpautop(wp_kses_post($answer));
			$body .= '<p>' . esc_html__('С уважением, администрация сайта.', 'yoga') . '</p>';

			$sent = wp_mail($recipient_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
			update_post_meta($post_id, '_answer_delivery_status', $sent ? 'sent' : 'failed');
			if ($sent) {
				update_post_meta($post_id, '_answer_sent_at', current_time('mysql'));
				update_post_meta($post_id, '_answer_sent_by', get_current_user_id());
				update_post_meta($post_id, '_answer_sent_email', $recipient_email);
			}

			return;
			
			// Альтернатива: проверка через метаполя
			if ($answer !== $old_answer) {
				update_post_meta($post_id, '_answer_date', current_time('mysql'));
				update_post_meta($post_id, '_answer_admin', get_current_user_id());
				
				// Функция для получения истории заказов
				$question = get_post($post_id);
				$user = get_userdata($question->post_author);
				$subject = 'Ответ на ваш вопрос';
				$message = "Здравствуйте, {$user->display_name}!\n\n";
				$message .= "На ваш вопрос получен ответ:\\n\\n";
				$message .= "Вопрос: {$question->post_content}\n\n";
				$message .= "Ответ: {$answer}\n\n";
				$message .= "С уважением, администрация сайта";
				
				wp_mail($user->user_email, $subject, $message);
			}
		}
	}
	add_action('save_post_question', 'save_question_answer');
	
	// Функция для получения сохраненных карт
	function get_user_active_subscription() {
		if (!is_user_logged_in()) {
			return false;
		}

		// Основной источник — оплаченные заказы тарифов (тот же, что в header.php).
		if (function_exists('get_current_user_tariff')) {
			$tariff = get_current_user_tariff();
			if (is_array($tariff) && !empty($tariff['product_name'])) {
				return array(
					'id'         => (int) ($tariff['order_id'] ?? 0),
					'name'       => (string) $tariff['product_name'],
					'start_date' => !empty($tariff['order_date']) ? (string) $tariff['order_date'] : '',
					'end_date'   => !empty($tariff['access_end_date']) ? (string) $tariff['access_end_date'] : '',
					'status'     => 'active',
				);
			}
		}

		$user_id = get_current_user_id();

		if (class_exists('WC_Subscriptions') && function_exists('wcs_get_users_subscriptions')) {
			$subscriptions = wcs_get_users_subscriptions($user_id);
			
			foreach ($subscriptions as $subscription) {
				if ($subscription->has_status('active')) {
					return array(
                    'id' => $subscription->get_id(),
                    'name' => $subscription->get_name(),
                    'start_date' => $subscription->get_date('start'),
                    'end_date' => $subscription->get_date('end'),
                    'status' => $subscription->get_status()
					);
				}
			}
		}
		
		// Здесь должна быть интеграция с платежной системой (Stripe, etc.)
		$active_subscription = get_user_meta($user_id, 'active_subscription', true);
		if ($active_subscription && $active_subscription['end_date'] > current_time('mysql')) {
			return $active_subscription;
		}
		
		return false;
	}
	
	// Это упрощенный пример
	function get_user_orders_history() {
		if (!is_user_logged_in()) return array();
		if (!function_exists('wc_get_orders')) return array();
		
		$user_id = get_current_user_id();
		$orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => array('completed', 'processing'),
        'limit' => 10,
        'orderby' => 'date',
        'order' => 'DESC'
		));
		
		$order_history = array();
		
		foreach ($orders as $order) {
			foreach ($order->get_items() as $item) {
				$order_history[] = array(
                'id' => $order->get_id(),
                'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'product_name' => $item->get_name(),
                'total' => $order->get_total(),
                'status' => $order->get_status()
				);
			}
		}
		
		return $order_history;
	}
	
	// Сохранённые карты для ЛК (только маска + токен ЮKassa, без PAN/CVC).
	function get_user_saved_cards() {
		if (!is_user_logged_in()) {
			return array();
		}

		$user_id = get_current_user_id();

		if (class_exists('YTR_Saved_Cards')) {
			return YTR_Saved_Cards::get_cards_for_lk($user_id);
		}

		return array();
	}

	if (!function_exists('yoga_lk_render_payment_card_icon')) {
		/**
		 * Иконка платёжной системы в ЛК (спрайт или PNG-fallback).
		 */
		function yoga_lk_render_payment_card_icon(string $type, string $brand = ''): void {
			$type = preg_replace('/[^a-z0-9_-]/', '', $type);
			if ($type === '') {
				$type = 'default';
			}

			$sprite_icons = array(
				'mir'        => array(
					'id'       => 'lk-payment-mir',
					'viewBox'  => '0 0 50 15',
				),
				'mastercard' => array(
					'id'       => 'lk-payment-mastercard',
					'viewBox'  => '0 0 50 39',
				),
				'visa'       => array(
					'id'       => 'lk-payment-visa',
					'viewBox'  => '0 0 50 16',
				),
			);

			if (isset($sprite_icons[ $type ])) {
				$icon       = $sprite_icons[ $type ];
				$sprite_url = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#' . $icon['id']);
				echo '<svg class="lk-payment-card-icon lk-payment-card-icon--' . esc_attr($type) . '" viewBox="' . esc_attr($icon['viewBox']) . '" aria-hidden="true" focusable="false">';
				echo '<use href="' . $sprite_url . '" width="100%" height="100%"></use>';
				echo '</svg>';
				return;
			}

			if ($type === 'yoo_money') {
				echo '<img class="lk-payment-card-icon lk-payment-card-icon--yoo_money" src="' . esc_url(get_template_directory_uri() . '/assets/svg/YooMoney.svg') . '" alt="YooMoney" width="50" height="16">';
				return;
			}

			$icon_url = get_template_directory_uri() . '/assets/img/lk-payment-icon_' . $type . '.png';
			echo '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($brand) . '">';
		}
	}
	
	// Функция для подключения разных header'ов
	function subscription_management_shortcode() {
		ob_start();
	?>
    <!-- Axecode.tech: блок управления подпиской в ЛК; статус, срок и действия в одном месте. -->
    <div class="subscription-management">
        <h3>Управление подпиской</h3>
        <?php
			$subscription = get_user_active_subscription();
			if ($subscription) {
			?>
            <div class="subscription-info">
                <p><strong>Текущий тариф:</strong> <?php echo $subscription['name']; ?></p>
                <p><strong>Действует до:</strong> <?php echo date('d.m.Y', strtotime($subscription['end_date'])); ?></p>
                <p><strong>Статус:</strong> <?php echo $subscription['status']; ?></p>
			</div>
            
            <div class="subscription-actions">
                <a href="<?php echo esc_url(home_url('/lk/')); ?>#lk-slide-settings" class="btn btn-renew">Настройки подписки</a>
			</div>
            <p class="description">Чтобы отменить автопродление, удалите карту с пометкой «Для автопродления» в разделе «Способы оплаты».</p>
            <?php
				} else {
			?>
            <div class="no-subscription">
                <p>У вас нет активной подписки.</p>
                <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>" class="btn">
                    Выбрать тариф
				</a>
			</div>
            <?php
			}
		?>
	</div>
    <?php
		return ob_get_clean();
	}
	add_shortcode('subscription_management', 'subscription_management_shortcode');
	
	// Проверяем, используется ли шаблон «Личный кабинет» / WooCommerce account — одна шапка header.php.
	function yoga_is_lk_shell() {
		return is_page_template( 'templates-page/lk.php' )
			|| is_page( 'my-account' )
			|| ( function_exists( 'is_account_page' ) && is_account_page() );
	}

// Переопределяем стандартный get_header()
	function custom_get_header() {
		locate_template( 'header.php', true );
	}
	
	// Добавление AJAX обработчиков
	remove_action('get_header', 'wp_get_header');
	add_action('get_header', 'custom_get_header');
	
	function reading_time() {
		$content = get_post_field('post_content', get_the_ID());
		$plain_text = wp_strip_all_tags((string) $content);
		
		// Count words for any language, including Cyrillic.
		preg_match_all('/[\p{L}\p{N}\']+/u', $plain_text, $matches);
		$word_count = isset($matches[0]) ? count($matches[0]) : 0;
		
		return max(1, (int) ceil($word_count / 180));
	}

	if (!function_exists('yoga_minutes_word')) {
		/**
		 * @param int|string $minutes Number of minutes.
		 * @return string
		 */
		function yoga_minutes_word($minutes) {
			$minutes = abs((int) $minutes);
			$mod10 = $minutes % 10;
			$mod100 = $minutes % 100;

			if ($mod100 >= 11 && $mod100 <= 14) {
				return 'минут';
			}
			if ($mod10 === 1) {
				return 'минута';
			}
			if ($mod10 >= 2 && $mod10 <= 4) {
				return 'минуты';
			}

			return 'минут';
		}
	}

	if (!function_exists('yoga_format_minutes')) {
		/**
		 * @param int|string $minutes Number of minutes.
		 * @param bool       $short Whether to use the abbreviated label.
		 * @return string
		 */
		function yoga_format_minutes($minutes, $short = false) {
			$minutes = max(1, (int) $minutes);

			if ($short) {
				return $minutes . ' мин';
			}

			return $minutes . ' ' . yoga_minutes_word($minutes);
		}
	}

	function get_current_user_tariff($user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		if (!$user_id) return false;
		if (!yoga_has_woocommerce() || !function_exists('wc_get_orders')) return false;

		$paid_statuses = function_exists('wc_get_is_paid_statuses')
			? wc_get_is_paid_statuses()
			: array('processing', 'completed');
		
		$orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => $paid_statuses,
        'limit' => -1,
        'orderby' => 'date_paid',
        'order' => 'DESC'
		));
		
		$current_time = current_time('timestamp');
		$latest_tariff = null;
		$latest_end = 0;
		
		foreach ($orders as $order) {
			foreach ($order->get_items() as $item) {
				$product = $item->get_product();
				if (!$product) {
					continue;
				}
				$product_id   = (int) $product->get_id();
				$parent_id    = $product->is_type('variation') ? (int) $product->get_parent_id() : $product_id;
				$is_tariff    = function_exists('yoga_product_is_tariff') && yoga_product_is_tariff($product_id);
				$period       = function_exists('yoga_get_product_price_period')
					? yoga_get_product_price_period($product_id)
					: (string) get_field('price_period', $product_id);
				if (!$period && $parent_id > 0 && function_exists('yoga_get_product_price_period')) {
					$period = yoga_get_product_price_period($parent_id);
				} elseif (!$period && $parent_id > 0) {
					$period = get_field('price_period', $parent_id);
				}
				if (!$period && $is_tariff) {
					$period = 'month';
				}
				if ($period || $is_tariff) {
					if (!$period) {
						$period = 'month';
					}
					$order_date_obj = $order->get_date_completed();
					if (!$order_date_obj) {
						$order_date_obj = $order->get_date_paid();
					}
					if (!$order_date_obj) {
						$order_date_obj = $order->get_date_created();
					}
					if (!$order_date_obj) {
						continue;
					}
					$order_date = $order_date_obj->getTimestamp();
					$access_duration = calculate_access_duration($period);
					$access_end = $order_date + $access_duration;
					
					if ($access_end > $current_time && $access_end > $latest_end) {
						$latest_end = $access_end;
						$latest_tariff = array(
                        'product_id' => $product_id,
                        'product_name' => $product->get_name(),
                        'period' => $period,
                        'order_id' => $order->get_id(),
                        'order_date' => $order_date_obj->format('d.m.Y'),
                        'access_end' => $access_end,
                        'access_end_date' => date('d.m.Y H:i', $access_end),
                        'remaining_time' => $access_end - $current_time
						);
					}
				}
			}
		}
		
		return $latest_tariff;
	}

	if (!function_exists('yoga_get_lk_page_url')) {
		/**
		 * URL страницы с шаблоном «Личный кабинет» (templates-page/lk.php).
		 */
		function yoga_get_lk_page_url(): string {
			static $cached = null;
			if ($cached !== null) {
				return $cached;
			}
			$cached = '';
			$pages = get_pages(array(
				'meta_key' => '_wp_page_template',
				'meta_value' => 'templates-page/lk.php',
				'number' => 1,
				'post_status' => 'publish',
			));
			if (!empty($pages[0]) && $pages[0] instanceof WP_Post) {
				$cached = get_permalink($pages[0]->ID);
			}
			return $cached;
		}
	}

	function yoga_get_lk_sections(): array {
		return array(
			'profile' => '1',
			'history' => '2',
			'favorites' => '3',
			'recommendations' => '4',
			'questions' => '5',
			'subscription' => '6',
			'sadhanas' => '7',
			'notifications' => '8',
			'notification-settings' => '9',
		);
	}

	function yoga_get_lk_section_by_target(): array {
		return array_flip(yoga_get_lk_sections());
	}

	function yoga_get_requested_lk_section(): string {
		$section = isset($_GET['lk-section']) ? sanitize_key(wp_unslash((string) $_GET['lk-section'])) : '';
		return array_key_exists($section, yoga_get_lk_sections()) ? $section : '';
	}

	function yoga_get_initial_lk_target(): string {
		$section = yoga_get_requested_lk_section();
		$sections = yoga_get_lk_sections();
		return $section !== '' ? $sections[$section] : $sections['profile'];
	}

	function yoga_get_lk_section_url(string $section): string {
		$sections = yoga_get_lk_sections();
		$lk_url = yoga_get_lk_page_url();
		if ($lk_url === '' || !array_key_exists($section, $sections)) {
			return $lk_url !== '' ? $lk_url : home_url('/');
		}
		return add_query_arg('lk-section', $section, $lk_url);
	}

	// Axecode.tech: расчет периода доступа вынесен в отдельный helper для повторного использования.
	if (!function_exists('calculate_access_duration')) {
		// Восстановление пароля
		// Axecode.tech: нормализация периода доступа в секунды.
		// Зачем: поддержка форматов "30", "30d", "2m", "1y" и безопасный fallback по умолчанию.
		function calculate_access_duration(string $period): int {
			$period = strtolower(trim((string) $period));

			if ($period === '') {
				return 30 * DAY_IN_SECONDS;
			}

			if ($period === 'month') {
				return 30 * DAY_IN_SECONDS;
			}

			if ($period === 'day') {
				return DAY_IN_SECONDS;
			}

			if ($period === '3months') {
				return 90 * DAY_IN_SECONDS;
			}

			if ($period === '6months') {
				return 180 * DAY_IN_SECONDS;
			}

			if ($period === 'year') {
				return 365 * DAY_IN_SECONDS;
			}

			if ($period === 'lifetime') {
				return 100 * 365 * DAY_IN_SECONDS;
			}

			if (preg_match('/^(\d+)\s*([dwmy])?$/i', $period, $matches)) {
				$value = (int) $matches[1];
				$unit = isset($matches[2]) ? strtolower($matches[2]) : 'd';

				switch ($unit) {
					case 'w':
						return $value * WEEK_IN_SECONDS;
					case 'm':
						return $value * 30 * DAY_IN_SECONDS;
					case 'y':
						return $value * 365 * DAY_IN_SECONDS;
					case 'd':
					default:
						return $value * DAY_IN_SECONDS;
				}
			}

			return 30 * DAY_IN_SECONDS;
		}
	}

	// Дружелюбный роут блога: /blog/ -> архив рубрики со slug "blog".
	if (!function_exists('theme_register_blog_friendly_route')) {
		function theme_register_blog_friendly_route() {
			add_rewrite_rule('^blog/?$', 'index.php?category_name=blog', 'top');
		}
		add_action('init', 'theme_register_blog_friendly_route');
	}

	// 301-редирект со старого адреса /category/blog/ на /blog/.
	if (!function_exists('theme_redirect_legacy_blog_category_url')) {
		function theme_redirect_legacy_blog_category_url() {
			if (is_admin() || wp_doing_ajax()) {
				return;
			}

			$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
			if ($request_uri === '') {
				return;
			}

			$path = wp_parse_url($request_uri, PHP_URL_PATH);
			if (!is_string($path)) {
				return;
			}

			$normalized_path = untrailingslashit(strtolower($path));
			if ($normalized_path === '/category/blog') {
				$target = home_url('/blog/');
				if (!empty($_GET)) {
					$target = add_query_arg(wp_unslash($_GET), $target);
				}
				wp_safe_redirect($target, 301);
				exit;
			}
		}
		add_action('template_redirect', 'theme_redirect_legacy_blog_category_url', 1);
	}

	// Обработка /blog/ без необходимости вручную сбрасывать rewrite rules.
	if (!function_exists('theme_force_blog_request_to_category')) {
		/**
		 * @param array<string, mixed> $query_vars Parsed request variables.
		 * @return array<string, mixed>
		 */
		function theme_force_blog_request_to_category($query_vars) {
			if (is_admin()) {
				return $query_vars;
			}

			$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
			if ($request_uri === '') {
				return $query_vars;
			}

			$path = wp_parse_url($request_uri, PHP_URL_PATH);
			if (!is_string($path)) {
				return $query_vars;
			}

			$normalized_path = untrailingslashit(strtolower($path));
			if ($normalized_path !== '/blog') {
				return $query_vars;
			}

			$query_vars['category_name'] = 'blog';
			unset($query_vars['pagename'], $query_vars['name'], $query_vars['page'], $query_vars['page_id'], $query_vars['error']);

			return $query_vars;
		}
		add_filter('request', 'theme_force_blog_request_to_category', 1);
	}

	// Кастомный роутинг product_cat без префикса /product-category/.
	// Важно: это намеренный компромисс и может конфликтовать с одинаковыми slug страниц.
	if (!function_exists('yoga_get_product_cat_path')) {
		/**
		 * @param int|WP_Term $term Product category term or ID.
		 * @return string
		 */
		function yoga_get_product_cat_path($term): string {
			$term = get_term($term, 'product_cat');
			if (!$term instanceof WP_Term || is_wp_error($term)) {
				return '';
			}

			$slugs = array($term->slug);
			$parent_id = (int) $term->parent;

			while ($parent_id > 0) {
				$parent_term = get_term($parent_id, 'product_cat');
				if (!$parent_term instanceof WP_Term || is_wp_error($parent_term)) {
					break;
				}
				array_unshift($slugs, $parent_term->slug);
				$parent_id = (int) $parent_term->parent;
			}

			return implode('/', $slugs);
		}
	}

	if (!function_exists('yoga_find_product_cat_by_path')) {
		function yoga_find_product_cat_by_path(string $path) {
			$path = trim($path, '/');
			if ($path === '') {
				return null;
			}

			$segments = explode('/', $path);
			$last_slug = end($segments);
			if (!is_string($last_slug) || $last_slug === '') {
				return null;
			}

			$candidates = get_terms(array(
				'taxonomy' => 'product_cat',
				'hide_empty' => false,
				'slug' => $last_slug,
			));

			if (empty($candidates) || is_wp_error($candidates)) {
				return null;
			}

			foreach ($candidates as $candidate) {
				if (!$candidate instanceof WP_Term) {
					continue;
				}

				if (yoga_get_product_cat_path($candidate) === $path) {
					return $candidate;
				}
			}

			return null;
		}
	}

	if (!function_exists('yoga_filter_product_cat_link_without_base')) {
		/**
		 * @param string  $termlink Existing term link.
		 * @param WP_Term $term Term object.
		 * @param string  $taxonomy Taxonomy key.
		 * @return string
		 */
		function yoga_filter_product_cat_link_without_base($termlink, $term, $taxonomy) {
			if ($taxonomy !== 'product_cat' || !$term instanceof WP_Term) {
				return $termlink;
			}

			$path = yoga_get_product_cat_path($term);
			if ($path === '') {
				return $termlink;
			}

			return home_url('/' . trailingslashit($path));
		}
		add_filter('term_link', 'yoga_filter_product_cat_link_without_base', 20, 3);
	}

	if (!function_exists('yoga_route_product_cat_without_base')) {
		function yoga_product_cat_path_conflicts_with_public_content(string $path): bool {
			$path = trim($path, '/');
			if ($path === '') {
				return true;
			}

			$post_type_objects = get_post_types(array('public' => true), 'objects');
			if (empty($post_type_objects) || !is_array($post_type_objects)) {
				return false;
			}

			$post_type_names = array();
			foreach ($post_type_objects as $post_type_object) {
				if (!($post_type_object instanceof WP_Post_Type)) {
					continue;
				}
				if ($post_type_object->name === 'attachment') {
					continue;
				}
				$post_type_names[] = $post_type_object->name;
			}

			if (!empty($post_type_names) && get_page_by_path($path, OBJECT, $post_type_names)) {
				return true;
			}

			$segments = explode('/', $path);
			$first_segment = $segments[0] ?? '';
			if ($first_segment === '') {
				return false;
			}

			foreach ($post_type_objects as $post_type_object) {
				if (!($post_type_object instanceof WP_Post_Type) || $post_type_object->name === 'attachment') {
					continue;
				}

				$rewrite_slug = '';
				if (is_array($post_type_object->rewrite) && !empty($post_type_object->rewrite['slug'])) {
					$rewrite_slug = trim((string) $post_type_object->rewrite['slug'], '/');
				}

				if ($rewrite_slug !== '') {
					$rewrite_root = explode('/', $rewrite_slug)[0];
					if ($rewrite_root === $first_segment) {
						return true;
					}
				}

				if (!empty($post_type_object->has_archive)) {
					$archive_slug = is_string($post_type_object->has_archive)
						? trim($post_type_object->has_archive, '/')
						: $rewrite_slug;

					if ($archive_slug !== '') {
						$archive_root = explode('/', $archive_slug)[0];
						if ($archive_root === $first_segment) {
							return true;
						}
					}
				}
			}

			return false;
		}

		/**
		 * @param array<string, mixed> $query_vars Parsed request variables.
		 * @return array<string, mixed>
		 */
		function yoga_route_product_cat_without_base($query_vars) {
			if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
				return $query_vars;
			}

			$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
			$path = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');
			if ($path === '') {
				return $query_vars;
			}

			// Старый адрес обработается отдельным редиректом.
			if (strpos($path, 'product-category/') === 0) {
				return $query_vars;
			}

			// Системные маршруты и ресурсы не перехватываем.
			if (
				strpos($path, 'wp-') === 0 ||
				strpos($path, 'wc-') === 0 ||
				strpos($path, 'feed') === 0 ||
				preg_match('/\.(php|xml|xsl|json|txt|ico)$/i', $path)
			) {
				return $query_vars;
			}

			// Если путь занят публичным контентом/архивом CPT, приоритет у него.
			if (yoga_product_cat_path_conflicts_with_public_content($path)) {
				return $query_vars;
			}

			$matched_term = yoga_find_product_cat_by_path($path);
			if (!$matched_term instanceof WP_Term) {
				return $query_vars;
			}

			$query_vars['product_cat'] = trim(yoga_get_product_cat_path($matched_term), '/');
			unset($query_vars['name'], $query_vars['pagename'], $query_vars['page'], $query_vars['page_id'], $query_vars['error']);

			return $query_vars;
		}
		add_filter('request', 'yoga_route_product_cat_without_base', 2);
	}

	if (!function_exists('yoga_redirect_legacy_product_cat_base_url')) {
		function yoga_redirect_legacy_product_cat_base_url() {
			if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
				return;
			}

			$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
			$path = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');
			if ($path === '' || strpos($path, 'product-category/') !== 0) {
				return;
			}

			$new_path = trim(substr($path, strlen('product-category/')), '/');
			if ($new_path === '') {
				return;
			}

			$term = yoga_find_product_cat_by_path($new_path);
			if (!$term instanceof WP_Term) {
				return;
			}

			$target_url = home_url('/' . trailingslashit($new_path));
			$query = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
			if ($query !== '') {
				$target_url .= '?' . $query;
			}

			wp_safe_redirect($target_url, 301);
			exit;
		}
		add_action('template_redirect', 'yoga_redirect_legacy_product_cat_base_url', 1);
	}

	if (!function_exists('yoga_normalize_practice_level_label')) {
		/**
		 * @param mixed $value Raw practice level value.
		 */
		function yoga_normalize_practice_level_label($value): string {
			$raw_value = trim((string) $value);
			if ($raw_value === '') {
				return '';
			}

			$key = function_exists('mb_strtolower')
				? mb_strtolower($raw_value, 'UTF-8')
				: strtolower($raw_value);

			$level_map = array(
				'beginner' => 'Начинающий',
				'easy' => 'Начинающий',
				'novice' => 'Начинающий',
				'начинающий' => 'Начинающий',
				'новичек' => 'Начинающий',
				'новичёк' => 'Начинающий',
				'новичок' => 'Начинающий',
				'рќр°с‡рёрѕр°сћс‰рёр№' => 'Начинающий',
				'intermediate' => 'Средний',
				'medium' => 'Средний',
				'middle' => 'Средний',
				'средний' => 'Средний',
				'рўсђрµрґрѕрёр№' => 'Средний',
				'advanced' => 'Продвинутый',
				'pro' => 'Продвинутый',
				'expert' => 'Продвинутый',
				'профи' => 'Продвинутый',
				'рџсђрѕс„рё' => 'Продвинутый',
			);

			return $level_map[$key] ?? $raw_value;
		}
	}

	if (!function_exists('yoga_get_practice_level_slug')) {
		/**
		 * @param mixed $value Raw practice level value.
		 */
		function yoga_get_practice_level_slug($value): string {
			$normalized = yoga_normalize_practice_level_label($value);
			$key = function_exists('mb_strtolower')
				? mb_strtolower($normalized, 'UTF-8')
				: strtolower($normalized);

			$slug_map = array(
				'начинаюший' => 'beginner',
				'средний' => 'intermediate',
				'продвинутый' => 'advanced',
			);

			return $slug_map[$key] ?? '';
		}
	}

	if (!function_exists('yoga_get_practice_difficulty_label')) {
		/**
		 * @param WP_Term $term Practice difficulty term.
		 */
		function yoga_get_practice_difficulty_label($term): string {
			if (!$term instanceof WP_Term) {
				return '';
			}

			$by_slug = yoga_normalize_practice_level_label((string) $term->slug);
			if (in_array($by_slug, array('Начинающий', 'Средний', 'Продвинутый'), true)) {
				return $by_slug;
			}

			return yoga_normalize_practice_level_label((string) $term->name);
		}
	}

	if (!function_exists('yoga_get_practice_level_raw_for_cards')) {
		/**
		 * Уровень для карточек в списках: ACF practice_level (как на странице практики),
		 * затем legacy level, затем таксономия practice-difficulty.
		 */
		function yoga_get_practice_level_raw_for_cards(int $post_id): string {
			if ($post_id <= 0) {
				return '';
			}
			if (function_exists('get_field')) {
				foreach (array('practice_level', 'level') as $acf_key) {
					$val = get_field($acf_key, $post_id);
					if (is_array($val)) {
						if (!empty($val['label'])) {
							$val = trim((string) $val['label']);
						} elseif (!empty($val['value'])) {
							$val = trim((string) $val['value']);
						} else {
							continue;
						}
					} else {
						if ($val === null || $val === '') {
							continue;
						}
						$val = trim((string) $val);
					}
					if ($val !== '') {
						return $val;
					}
				}
			}
			$terms = wp_get_post_terms($post_id, 'practice-difficulty');
			if (!empty($terms) && !is_wp_error($terms)) {
				foreach ($terms as $term) {
					if (!($term instanceof WP_Term)) {
						continue;
					}
					if (function_exists('yoga_get_practice_difficulty_label')) {
						$label = yoga_get_practice_difficulty_label($term);
						if ($label !== '') {
							return $label;
						}
					}
					return trim((string) $term->name);
				}
			}
			return '';
		}
	}
