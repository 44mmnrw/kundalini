<?php
	@ini_set( 'upload_max_size' , '256M' );
	@ini_set( 'post_max_size', '256M');
	@ini_set( 'max_execution_time', '300' );
	// Регистрация меню
	require_once get_template_directory() . '/inc/core/ajax-responses.php';
	require_once get_template_directory() . '/inc/core/dependencies.php';
	require_once get_template_directory() . '/inc/notifications.php';
	require_once get_template_directory() . '/inc/ajax/notifications.php';
	require_once get_template_directory() . '/inc/questions.php';
	require_once get_template_directory() . '/inc/render/questions.php';
	require_once get_template_directory() . '/inc/ajax/questions.php';
	require_once get_template_directory() . '/inc/admin/questions.php';
	require_once get_template_directory() . '/inc/practices/search.php';
	require_once get_template_directory() . '/inc/ajax/practice-search.php';
	require_once get_template_directory() . '/inc/comments.php';
	require_once get_template_directory() . '/inc/render/comments.php';
	require_once get_template_directory() . '/inc/ajax/comments.php';
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
	require_once get_template_directory() . '/inc/sprite-icons-page.php';

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
		$library_filters_script_ver = file_exists($theme_dir . '/assets/js/library-filters.js') ? filemtime($theme_dir . '/assets/js/library-filters.js') : '1.0.0';
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
		$is_404_template = is_404() || is_page_template('templates-page/404.php');
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

		// Parent practice-type archives also render the existing kriyi list after
		// search/filtering, so its styles must be available there as well.
		$load_kriyi_style = $is_practice_tax;
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
		
		wp_enqueue_script( 'library-filters-script', $theme_uri . '/assets/js/library-filters.js', array('jquery'), $library_filters_script_ver, true );
		wp_enqueue_script( 'main-script', $theme_uri . '/assets/js/script.js', array('jquery', 'slick', 'fancybox', 'library-filters-script'), $main_script_ver, true );
		
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
			'library_url'    => function_exists('yoga_lk_sidebar_secondary_nav_urls')
				? (string) (yoga_lk_sidebar_secondary_nav_urls()['library'] ?? home_url('/'))
				: home_url('/'),
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
			'lk_page_url' => function_exists('yoga_get_lk_page_url') ? yoga_get_lk_page_url() : '',
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
		if (is_user_logged_in()) {
			$current_user = wp_get_current_user();
			$profile_name = trim((string) $current_user->display_name);
			$profile_email = sanitize_email((string) $current_user->user_email);
			if ($profile_name !== '') {
				$name = $profile_name;
			}
			if ($profile_email !== '') {
				$email = $profile_email;
			}
		}
		
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

	function yoga_is_subscription_email_subscribed(string $email): bool {
		$email = strtolower(trim(sanitize_email($email)));
		if ($email === '' || !is_email($email)) {
			return false;
		}
		if (class_exists('Yoga_Subscribers_Plugin') && method_exists('Yoga_Subscribers_Plugin', 'is_subscribed')) {
			return Yoga_Subscribers_Plugin::is_subscribed($email);
		}

		$stored_emails = array_merge(
			(array) get_option('subscription_emails', array()),
			(array) get_option('yoga_subscribers', array())
		);

		foreach ($stored_emails as $stored_email) {
			if (strtolower(trim(sanitize_email((string) $stored_email))) === $email) {
				return true;
			}
		}

		return false;
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
	function yoga_get_russian_timezone_options() {
		return array(
			'America/Los_Angeles'          => 'UTC−8 — Лос-Анджелес',
			'America/Denver'               => 'UTC−7 — Денвер',
			'America/Chicago'              => 'UTC−6 — Чикаго · Мехико',
			'America/New_York'             => 'UTC−5 — Нью-Йорк · Торонто',
			'America/Argentina/Buenos_Aires' => 'UTC−3 — Буэнос-Айрес · Сан-Паулу',
			'Europe/London'                => 'UTC+0 — Лондон · Лиссабон',
			'Europe/Berlin'                => 'UTC+1 — Берлин · Париж',
			'Europe/Kaliningrad'           => 'UTC+2 — Калининград · Тель-Авив',
			'Europe/Moscow'                => 'UTC+3 — Москва · Санкт-Петербург · Минск',
			'Europe/Samara'                => 'UTC+4 — Самара · Дубай · Тбилиси',
			'Asia/Yekaterinburg'           => 'UTC+5 — Екатеринбург · Ташкент · Алматы',
			'Asia/Omsk'                    => 'UTC+6 — Омск · Бишкек',
			'Asia/Novosibirsk'             => 'UTC+7 — Новосибирск · Красноярск · Бангкок',
			'Asia/Irkutsk'                 => 'UTC+8 — Иркутск · Бали · Сингапур',
			'Asia/Yakutsk'                 => 'UTC+9 — Якутск · Токио',
			'Asia/Vladivostok'             => 'UTC+10 — Владивосток · Хабаровск',
			'Asia/Magadan'                 => 'UTC+11 — Магадан · Южно-Сахалинск',
			'Asia/Kamchatka'               => 'UTC+12 — Петропавловск-Камчатский',
		);
	}

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
		$new_email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : $old_email;
		$email_changed = $new_email !== '' && strcasecmp($new_email, $old_email) !== 0;
		$response = array();
		
		try {
			$user_data = array('ID' => $user_id);

			if ($email_changed) {
				if (!is_email($new_email)) {
					wp_send_json_error('Укажите корректную эл. почту.', 422);
				}

				$login_owner_id = username_exists($new_email);
				if ($login_owner_id && (int) $login_owner_id !== $user_id) {
					wp_send_json_error('Эта эл. почта уже используется другим пользователем.', 422);
				}
			}
			
			// Обработка загрузки аватара
			if (!empty($_POST['first_name'])) {
				$user_data['first_name'] = sanitize_text_field($_POST['first_name']);
			}
			
			if (!empty($_POST['last_name'])) {
				$user_data['last_name'] = sanitize_text_field($_POST['last_name']);
			}
			
			if ($email_changed) {
				$user_data['user_email'] = $new_email;
			}
			
			$update_result = wp_update_user($user_data);
			if (is_wp_error($update_result)) {
				wp_send_json_error($update_result->get_error_message(), 422);
			}
			if ($email_changed) {
				global $wpdb;
				$login_updated = $wpdb->update(
					$wpdb->users,
					array('user_login' => $new_email),
					array('ID' => $user_id),
					array('%s'),
					array('%d')
				);
				if ($login_updated === false) {
					wp_update_user(array('ID' => $user_id, 'user_email' => $old_email));
					wp_send_json_error('Не удалось обновить эл. почту. Попробуйте ещё раз.', 500);
				}
				clean_user_cache($user_id);
				update_user_meta($user_id, 'billing_email', $new_email);
				delete_user_meta($user_id, 'yoga_verified_email');
				delete_user_meta($user_id, 'yoga_email_verified_at');
				if (function_exists('yoga_clear_email_verification_code')) {
					yoga_clear_email_verification_code($user_id);
				}
				delete_user_meta($user_id, 'yoga_email_code_sent_at');
			}

			if (isset($_POST['timezone'])) {
				$timezone = sanitize_text_field(wp_unslash($_POST['timezone']));
				if ($timezone !== '' && array_key_exists($timezone, yoga_get_russian_timezone_options())) {
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
			if (
				isset($_FILES['avatar']['error'])
				&& (int) $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE
			) {
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
											<svg><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
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
	
	

	// Отправляем уведомление администратору
	
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
			if ($order->get_meta('_ytr_card_binding') === 'yes') {
				$order_history[] = array(
					'id'           => $order->get_id(),
					'date'         => $order->get_date_created()->format('Y-m-d H:i:s'),
					'product_name' => __('Привязка карты', 'yoga'),
					'total'        => $order->get_total(),
					'status'       => $order->get_status(),
				);

				continue;
			}

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

	if (!function_exists('yoga_track_blog_post_view')) {
		/**
		 * Count real front-end article views used by the blog's Popular section.
		 */
		function yoga_track_blog_post_view(): void {
			if (!is_singular('post') || is_preview() || is_feed() || wp_doing_ajax()) {
				return;
			}

			if (isset($_SERVER['REQUEST_METHOD']) && strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'GET') {
				return;
			}

			$user_agent = isset($_SERVER['HTTP_USER_AGENT'])
				? strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])))
				: '';
			if ($user_agent !== '' && preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|telegrambot|whatsapp/i', $user_agent)) {
				return;
			}

			$post_id = (int) get_queried_object_id();
			if ($post_id <= 0) {
				return;
			}

			$views = max(0, (int) get_post_meta($post_id, 'yoga_post_views', true));
			update_post_meta($post_id, 'yoga_post_views', $views + 1);
		}
	}
	add_action('template_redirect', 'yoga_track_blog_post_view', 20);

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
