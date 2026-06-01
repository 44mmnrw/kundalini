<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_get_practice_duplicate_excluded_meta_keys')) {
	/**
	 * @return string[]
	 */
	function yoga_get_practice_duplicate_excluded_meta_keys(): array {
		return array(
			'_edit_lock',
			'_edit_last',
			'_wp_old_slug',
			'_wp_trash_meta_status',
			'_wp_trash_meta_time',
		);
	}
}

if (!function_exists('yoga_duplicate_practice_post')) {
	/**
	 * Создаёт черновик-копию практики с мета и таксономиями.
	 *
	 * @return int|WP_Error ID новой записи.
	 */
	function yoga_duplicate_practice_post(int $source_id) {
		$source = get_post($source_id);

		if (!$source instanceof WP_Post || $source->post_type !== 'practice') {
			return new WP_Error('invalid_practice', 'Некорректная практика для копирования.');
		}

		if ($source->post_status === 'trash') {
			return new WP_Error('trashed_practice', 'Нельзя дублировать практику из корзины.');
		}

		$new_post_id = wp_insert_post(
			array(
				'post_type'      => 'practice',
				'post_status'    => 'draft',
				'post_title'     => sprintf('Копия: %s', $source->post_title),
				'post_content'   => $source->post_content,
				'post_excerpt'   => $source->post_excerpt,
				'post_author'    => get_current_user_id(),
				'post_parent'    => (int) $source->post_parent,
				'menu_order'     => (int) $source->menu_order,
				'comment_status' => $source->comment_status,
				'ping_status'    => $source->ping_status,
			),
			true
		);

		if (is_wp_error($new_post_id)) {
			return $new_post_id;
		}

		$new_post_id = (int) $new_post_id;
		$excluded_keys = yoga_get_practice_duplicate_excluded_meta_keys();

		foreach (get_post_meta($source_id) as $meta_key => $meta_values) {
			if (!is_string($meta_key) || in_array($meta_key, $excluded_keys, true)) {
				continue;
			}

			foreach ($meta_values as $meta_value) {
				add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
			}
		}

		$taxonomies = get_object_taxonomies('practice');
		foreach ($taxonomies as $taxonomy) {
			$term_ids = wp_get_object_terms($source_id, $taxonomy, array('fields' => 'ids'));
			if (is_wp_error($term_ids) || empty($term_ids)) {
				continue;
			}

			wp_set_object_terms($new_post_id, array_map('intval', $term_ids), $taxonomy);
		}

		$thumbnail_id = (int) get_post_thumbnail_id($source_id);
		if ($thumbnail_id > 0) {
			set_post_thumbnail($new_post_id, $thumbnail_id);
		}

		return $new_post_id;
	}
}

if (!function_exists('yoga_practice_duplicate_row_action')) {
	function yoga_practice_duplicate_row_action(array $actions, WP_Post $post): array {
		if ($post->post_type !== 'practice' || $post->post_status === 'trash') {
			return $actions;
		}

		if (!current_user_can('edit_post', $post->ID)) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url('admin.php?action=yoga_duplicate_practice&post=' . $post->ID),
			'yoga_duplicate_practice_' . $post->ID
		);

		$actions['yoga_duplicate'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url($url),
			esc_attr(sprintf('Дублировать «%s»', $post->post_title)),
			'Дублировать'
		);

		return $actions;
	}
}
add_filter('post_row_actions', 'yoga_practice_duplicate_row_action', 10, 2);

if (!function_exists('yoga_handle_duplicate_practice')) {
	function yoga_handle_duplicate_practice(): void {
		if (empty($_GET['post'])) {
			wp_die('Не указана практика для копирования.');
		}

		$post_id = (int) $_GET['post'];
		check_admin_referer('yoga_duplicate_practice_' . $post_id);

		$post = get_post($post_id);
		if (!$post instanceof WP_Post || $post->post_type !== 'practice') {
			wp_die('Некорректная практика.');
		}

		if (!current_user_can('edit_post', $post_id)) {
			wp_die('Недостаточно прав для копирования практики.');
		}

		$new_post_id = yoga_duplicate_practice_post($post_id);
		if (is_wp_error($new_post_id)) {
			wp_die(esc_html($new_post_id->get_error_message()));
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post'            => $new_post_id,
					'action'          => 'edit',
					'yoga_duplicated' => '1',
				),
				admin_url('post.php')
			)
		);
		exit;
	}
}
add_action('admin_action_yoga_duplicate_practice', 'yoga_handle_duplicate_practice');

if (!function_exists('yoga_show_practice_duplicated_notice')) {
	function yoga_show_practice_duplicated_notice(): void {
		if (empty($_GET['yoga_duplicated']) || empty($_GET['post'])) {
			return;
		}

		$post_id = (int) $_GET['post'];
		if ($post_id <= 0 || get_post_type($post_id) !== 'practice') {
			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p>Практика скопирована. Отредактируйте черновик и опубликуйте.</p></div>';
	}
}
add_action('admin_notices', 'yoga_show_practice_duplicated_notice');
