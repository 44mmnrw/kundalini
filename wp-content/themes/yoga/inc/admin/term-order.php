<?php
/**
 * Manual ordering for the practice taxonomy dictionaries.
 *
 * @package Yoga
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_get_sortable_term_taxonomies')) {
	function yoga_get_sortable_term_taxonomies(): array {
		$taxonomies = apply_filters(
			'yoga_sortable_term_taxonomies',
			array(
				'practice-difficulty',
				'practice-duration',
				'practice-type',
				'practice-goal',
			)
		);

		if (!is_array($taxonomies)) {
			return array();
		}

		return array_values(array_unique(array_filter(array_map('sanitize_key', $taxonomies))));
	}
}

if (!function_exists('yoga_is_sortable_term_taxonomy')) {
	function yoga_is_sortable_term_taxonomy(string $taxonomy): bool {
		return in_array(sanitize_key($taxonomy), yoga_get_sortable_term_taxonomies(), true);
	}
}

if (!function_exists('yoga_get_term_order_meta_key')) {
	function yoga_get_term_order_meta_key(string $taxonomy): string {
		return $taxonomy === 'practice-type' ? 'practice_type_card_order' : '_yoga_term_order';
	}
}

if (!function_exists('yoga_get_term_manual_order')) {
	function yoga_get_term_manual_order(int $term_id, string $taxonomy): ?int {
		if ($term_id <= 0 || !yoga_is_sortable_term_taxonomy($taxonomy)) {
			return null;
		}

		$meta_key = yoga_get_term_order_meta_key($taxonomy);
		if (!metadata_exists('term', $term_id, $meta_key)) {
			return null;
		}

		$value = get_term_meta($term_id, $meta_key, true);
		return is_numeric($value) ? (int) $value : null;
	}
}

if (!function_exists('yoga_should_apply_manual_term_order')) {
	function yoga_should_apply_manual_term_order(array $taxonomies, array $args): bool {
		if (isset($args['yoga_manual_order']) && !$args['yoga_manual_order']) {
			return false;
		}

		$taxonomies = array_values(array_unique(array_filter(array_map('sanitize_key', $taxonomies))));
		if (count($taxonomies) !== 1 || !yoga_is_sortable_term_taxonomy($taxonomies[0])) {
			return false;
		}

		return ($args['fields'] ?? 'all') !== 'count';
	}
}

if (!function_exists('yoga_manual_term_order_sql')) {
	/**
	 * Apply the saved order before LIMIT/OFFSET so admin pagination is stable.
	 */
	function yoga_manual_term_order_sql(string $orderby, array $args, array $taxonomies): string {
		if (!yoga_should_apply_manual_term_order($taxonomies, $args)) {
			return $orderby;
		}

		global $wpdb;
		$meta_key = yoga_get_term_order_meta_key($taxonomies[0]);
		$meta_key = esc_sql($meta_key);
		$order_value = "(SELECT CAST(yoga_term_order.meta_value AS SIGNED) FROM {$wpdb->termmeta} AS yoga_term_order WHERE yoga_term_order.term_id = t.term_id AND yoga_term_order.meta_key = '{$meta_key}' LIMIT 1)";

		// WP_Term_Query appends the requested direction after this fragment.
		return "CASE WHEN {$order_value} IS NULL THEN 1 ELSE 0 END ASC, {$order_value} ASC, t.name ASC, t.term_id";
	}
}
add_filter('get_terms_orderby', 'yoga_manual_term_order_sql', 90, 3);

if (!function_exists('yoga_sort_terms_by_manual_order')) {
	/**
	 * Keep the final result ordered even when another plugin filters get_terms.
	 */
	function yoga_sort_terms_by_manual_order($terms, array $taxonomies, array $args) {
		if (
			!yoga_should_apply_manual_term_order($taxonomies, $args)
			|| !is_array($terms)
			|| count($terms) < 2
			|| array_keys($terms) !== range(0, count($terms) - 1)
		) {
			return $terms;
		}

		$taxonomy = $taxonomies[0];
		$first = reset($terms);
		if (!is_object($first) && !is_int($first) && !ctype_digit((string) $first)) {
			return $terms;
		}

		usort($terms, static function ($term_a, $term_b) use ($taxonomy): int {
			$term_a_id = is_object($term_a) && isset($term_a->term_id) ? (int) $term_a->term_id : (int) $term_a;
			$term_b_id = is_object($term_b) && isset($term_b->term_id) ? (int) $term_b->term_id : (int) $term_b;
			$order_a = yoga_get_term_manual_order($term_a_id, $taxonomy);
			$order_b = yoga_get_term_manual_order($term_b_id, $taxonomy);

			if ($order_a !== $order_b) {
				if ($order_a === null) {
					return 1;
				}
				if ($order_b === null) {
					return -1;
				}
				return $order_a <=> $order_b;
			}

			if (is_object($term_a) && is_object($term_b) && isset($term_a->name, $term_b->name)) {
				$name_order = strnatcasecmp((string) $term_a->name, (string) $term_b->name);
				if ($name_order !== 0) {
					return $name_order;
				}
			}

			return $term_a_id <=> $term_b_id;
		});

		return $terms;
	}
}
add_filter('get_terms', 'yoga_sort_terms_by_manual_order', 99, 3);

if (!function_exists('yoga_add_manual_term_order_column')) {
	function yoga_add_manual_term_order_column(array $columns): array {
		$result = array();
		foreach ($columns as $column_key => $column_label) {
			$result[$column_key] = $column_label;
			if ($column_key === 'cb') {
				$result['yoga-term-order'] = __('Порядок', 'yoga');
			}
		}

		if (!isset($result['yoga-term-order'])) {
			$result = array('yoga-term-order' => __('Порядок', 'yoga')) + $result;
		}

		return $result;
	}
}

if (!function_exists('yoga_render_manual_term_order_column')) {
	function yoga_render_manual_term_order_column(string $content, string $column_name, int $term_id): string {
		if ($column_name !== 'yoga-term-order') {
			return $content;
		}

		return sprintf(
			'<span class="yoga-term-order-handle" data-term-id="%1$d" role="button" tabindex="0" aria-label="%2$s" title="%2$s"><span class="dashicons dashicons-menu" aria-hidden="true"></span></span>',
			$term_id,
			esc_attr__('Перетащить для изменения порядка', 'yoga')
		);
	}
}

foreach (yoga_get_sortable_term_taxonomies() as $yoga_sortable_taxonomy) {
	add_filter("manage_edit-{$yoga_sortable_taxonomy}_columns", 'yoga_add_manual_term_order_column');
	add_filter("manage_{$yoga_sortable_taxonomy}_custom_column", 'yoga_render_manual_term_order_column', 10, 3);
}
unset($yoga_sortable_taxonomy);

if (!function_exists('yoga_get_current_sortable_taxonomy')) {
	function yoga_get_current_sortable_taxonomy(): string {
		global $pagenow;
		if ($pagenow !== 'edit-tags.php') {
			return '';
		}

		$taxonomy = isset($_GET['taxonomy']) ? sanitize_key(wp_unslash($_GET['taxonomy'])) : '';
		return yoga_is_sortable_term_taxonomy($taxonomy) ? $taxonomy : '';
	}
}

if (!function_exists('yoga_enqueue_manual_term_order_assets')) {
	function yoga_enqueue_manual_term_order_assets(): void {
		$taxonomy = yoga_get_current_sortable_taxonomy();
		$taxonomy_object = $taxonomy !== '' ? get_taxonomy($taxonomy) : null;
		if (!$taxonomy_object || !current_user_can($taxonomy_object->cap->manage_terms)) {
			return;
		}

		$theme_dir = get_template_directory();
		$theme_uri = get_template_directory_uri();
		$style_path = $theme_dir . '/assets/css/admin-term-order.css';
		$script_path = $theme_dir . '/assets/js/admin-term-order.js';

		// Prevent a second sortable instance when WP Adminify ordering is enabled.
		wp_dequeue_script('adminify-post-type-order');
		wp_enqueue_style(
			'yoga-admin-term-order',
			$theme_uri . '/assets/css/admin-term-order.css',
			array(),
			file_exists($style_path) ? (string) filemtime($style_path) : '1.0.0'
		);
		wp_enqueue_script(
			'yoga-admin-term-order',
			$theme_uri . '/assets/js/admin-term-order.js',
			array('jquery', 'jquery-ui-sortable'),
			file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0',
			true
		);
		wp_localize_script(
			'yoga-admin-term-order',
			'yogaTermOrder',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'action' => 'yoga_update_term_order',
				'nonce' => wp_create_nonce('yoga_update_term_order'),
				'taxonomy' => $taxonomy,
				'saving' => __('Сохраняем порядок…', 'yoga'),
				'saved' => __('Порядок сохранён.', 'yoga'),
				'error' => __('Не удалось сохранить порядок. Обновите страницу и попробуйте снова.', 'yoga'),
			)
		);
	}
}
add_action('admin_enqueue_scripts', 'yoga_enqueue_manual_term_order_assets', 100);

if (!function_exists('yoga_render_manual_term_order_notice')) {
	function yoga_render_manual_term_order_notice(): void {
		$taxonomy = yoga_get_current_sortable_taxonomy();
		$taxonomy_object = $taxonomy !== '' ? get_taxonomy($taxonomy) : null;
		if (!$taxonomy_object || !current_user_can($taxonomy_object->cap->manage_terms)) {
			return;
		}
		?>
		<div class="notice notice-info inline yoga-term-order-help">
			<p><?php esc_html_e('Меняйте порядок пунктов перетаскиванием за значок в колонке «Порядок». Изменения сохраняются автоматически.', 'yoga'); ?></p>
		</div>
		<?php
	}
}
add_action('admin_notices', 'yoga_render_manual_term_order_notice');

if (!function_exists('yoga_merge_visible_term_order')) {
	/**
	 * Replace only the visible slots while preserving terms from other pages.
	 */
	function yoga_merge_visible_term_order(array $all_ids, array $submitted_ids): array {
		$all_ids = array_values(array_map('absint', $all_ids));
		$submitted_ids = array_values(array_unique(array_filter(array_map('absint', $submitted_ids))));
		$submitted_lookup = array_fill_keys($submitted_ids, true);
		$visible_positions = array();

		foreach ($all_ids as $position => $term_id) {
			if (isset($submitted_lookup[$term_id])) {
				$visible_positions[] = $position;
			}
		}

		if (count($visible_positions) !== count($submitted_ids)) {
			return array();
		}

		foreach ($visible_positions as $index => $position) {
			$all_ids[$position] = $submitted_ids[$index];
		}

		return $all_ids;
	}
}

if (!function_exists('yoga_update_manual_term_order')) {
	function yoga_update_manual_term_order(): void {
		check_ajax_referer('yoga_update_term_order', 'nonce');

		$taxonomy = isset($_POST['taxonomy']) ? sanitize_key(wp_unslash($_POST['taxonomy'])) : '';
		$taxonomy_object = $taxonomy !== '' ? get_taxonomy($taxonomy) : null;
		if (
			!yoga_is_sortable_term_taxonomy($taxonomy)
			|| !$taxonomy_object
			|| !current_user_can($taxonomy_object->cap->manage_terms)
		) {
			wp_send_json_error(array('message' => __('Недостаточно прав для изменения порядка.', 'yoga')), 403);
		}

		$submitted_ids = isset($_POST['term_ids']) && is_array($_POST['term_ids'])
			? array_values(array_unique(array_filter(array_map('absint', wp_unslash($_POST['term_ids'])))))
			: array();
		if (!$submitted_ids) {
			wp_send_json_error(array('message' => __('Не переданы пункты для сортировки.', 'yoga')), 400);
		}

		$valid_ids = get_terms(array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
			'include' => $submitted_ids,
			'fields' => 'ids',
			'number' => 0,
			'yoga_manual_order' => false,
		));
		$valid_ids = is_wp_error($valid_ids) ? array() : array_map('absint', $valid_ids);
		if (count($valid_ids) !== count($submitted_ids)) {
			wp_send_json_error(array('message' => __('Один или несколько пунктов не найдены.', 'yoga')), 400);
		}

		$all_terms = get_terms(array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
			'number' => 0,
			'orderby' => 'name',
			'order' => 'ASC',
			'yoga_manual_order' => false,
		));
		if (is_wp_error($all_terms)) {
			wp_send_json_error(array('message' => __('Не удалось получить список пунктов.', 'yoga')), 500);
		}

		$all_terms = yoga_sort_terms_by_manual_order(
			$all_terms,
			array($taxonomy),
			array('fields' => 'all')
		);
		$all_ids = array_map(static function ($term): int {
			return (int) $term->term_id;
		}, $all_terms);
		$all_ids = yoga_merge_visible_term_order($all_ids, $submitted_ids);
		if (!$all_ids) {
			wp_send_json_error(array('message' => __('Не удалось сопоставить порядок пунктов.', 'yoga')), 409);
		}

		$meta_key = yoga_get_term_order_meta_key($taxonomy);
		foreach ($all_ids as $index => $term_id) {
			update_term_meta($term_id, $meta_key, ($index + 1) * 10);
		}
		clean_term_cache($all_ids, $taxonomy);

		wp_send_json_success(array('message' => __('Порядок сохранён.', 'yoga')));
	}
}
add_action('wp_ajax_yoga_update_term_order', 'yoga_update_manual_term_order');
