<?php
/**
 * Theme-styled ACF editor for practices.
 *
 * The module changes only the presentation of existing ACF controls. Field
 * keys, submitted names and WordPress' regular publishing flow stay intact.
 *
 * @package Yoga
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_is_modern_practice_editor')) {
	function yoga_is_modern_practice_editor(): bool {
		if (!is_admin()) {
			return false;
		}

		if (isset($_GET['yoga_editor']) && sanitize_key(wp_unslash($_GET['yoga_editor'])) === 'classic') {
			return false;
		}

		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'practice') {
			return false;
		}

		return in_array($GLOBALS['pagenow'] ?? '', array('post.php', 'post-new.php'), true);
	}
}

if (!function_exists('yoga_practice_editor_taxonomy_terms')) {
	function yoga_practice_editor_taxonomy_terms(string $taxonomy): array {
		$terms = get_terms(array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		));
		if (is_wp_error($terms) || !$terms) {
			return array();
		}

		$result = array();
		foreach ($terms as $term) {
			$value = (string) $term->name;
			if ($taxonomy === 'practice-difficulty') {
				$search = function_exists('mb_strtolower')
					? mb_strtolower($term->slug . ' ' . $term->name, 'UTF-8')
					: strtolower($term->slug . ' ' . $term->name);

				if (preg_match('/(?:легк|начин|begin|easy|novice|tip[-_ ]?1|type[-_ ]?1)/u', $search)) {
					$value = 'beginner';
				} elseif (preg_match('/(?:сред|intermediate|medium|middle|tip[-_ ]?2|type[-_ ]?2)/u', $search)) {
					$value = 'intermediate';
				} elseif (preg_match('/(?:слож|продвин|advanced|hard|expert|pro|tip[-_ ]?3|type[-_ ]?3)/u', $search)) {
					$value = 'advanced';
				}
			}

			$result[] = array(
				'id'    => (int) $term->term_id,
				'parent' => (int) $term->parent,
				'name'  => (string) $term->name,
				'slug'  => (string) $term->slug,
				'value' => $value,
			);
		}

		return $result;
	}
}

if (!function_exists('yoga_enqueue_practice_editor_assets')) {
	function yoga_enqueue_practice_editor_assets(): void {
		if (!yoga_is_modern_practice_editor()) {
			return;
		}

		$theme_dir = get_template_directory();
		$theme_uri = get_template_directory_uri();
		$style_path = $theme_dir . '/assets/css/admin-practice-editor.css';
		$script_path = $theme_dir . '/assets/js/admin-practice-editor.js';
		$font_path = $theme_dir . '/assets/css/mulish.css';
		$sprite_path = $theme_dir . '/assets/svg/sprite.svg';

		wp_enqueue_style(
			'yoga-admin-practice-editor-font',
			$theme_uri . '/assets/css/mulish.css',
			array(),
			file_exists($font_path) ? (string) filemtime($font_path) : '1.0.0'
		);

		wp_enqueue_style(
			'yoga-admin-practice-editor',
			$theme_uri . '/assets/css/admin-practice-editor.css',
			array('yoga-admin-practice-editor-font'),
			file_exists($style_path) ? (string) filemtime($style_path) : '1.0.0'
		);

		wp_enqueue_script(
			'yoga-admin-practice-editor',
			$theme_uri . '/assets/js/admin-practice-editor.js',
			array('jquery', 'jquery-ui-sortable', 'acf-input'),
			file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0',
			true
		);

		$post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
		$selected_taxonomy_terms = static function (string $taxonomy) use ($post_id): array {
			if ($post_id <= 0) {
				return array();
			}

			$term_ids = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
			return is_wp_error($term_ids) ? array() : array_values(array_map('absint', $term_ids));
		};
		$classic_url = $post_id > 0
			? add_query_arg('yoga_editor', 'classic', get_edit_post_link($post_id, 'raw'))
			: add_query_arg(
				array('post_type' => 'practice', 'yoga_editor' => 'classic'),
				admin_url('post-new.php')
			);
		$hidden_sidebar_taxonomies = array();
		foreach (array('practice-duration', 'practice-difficulty', 'practice-type', 'practice-goal') as $taxonomy_name) {
			$taxonomy = get_taxonomy($taxonomy_name);
			if (!$taxonomy) {
				continue;
			}

			$hidden_sidebar_taxonomies[] = array(
				'slug'  => $taxonomy_name,
				'label' => (string) $taxonomy->labels->menu_name,
			);
		}

		wp_localize_script(
			'yoga-admin-practice-editor',
			'yogaPracticeEditor',
			array(
				'classicUrl' => $classic_url,
				'taxonomyNonce' => wp_create_nonce('yoga_save_practice_taxonomies'),
				'guestAccessNonce' => wp_create_nonce('yoga_save_practice_guest_access'),
				'hiddenSidebarTaxonomies' => $hidden_sidebar_taxonomies,
				'taxonomySync' => array(
					'difficulty' => array(
						'restBase' => 'practice-difficulty',
						'terms'    => yoga_practice_editor_taxonomy_terms('practice-difficulty'),
					),
					'duration' => array(
						'restBase' => 'practice-duration',
						'terms'    => yoga_practice_editor_taxonomy_terms('practice-duration'),
					),
					'types' => array(
						'restBase' => 'practice-type',
						'label'    => (string) get_taxonomy('practice-type')->labels->menu_name,
						'terms'    => yoga_practice_editor_taxonomy_terms('practice-type'),
						'selectedIds' => $selected_taxonomy_terms('practice-type'),
					),
					'goals' => array(
						'restBase' => 'practice-goal',
						'label'    => (string) get_taxonomy('practice-goal')->labels->menu_name,
						'terms'    => yoga_practice_editor_taxonomy_terms('practice-goal'),
						'selectedIds' => $selected_taxonomy_terms('practice-goal'),
					),
				),
				'spriteUrl'  => add_query_arg(
					'ver',
					file_exists($sprite_path) ? (string) filemtime($sprite_path) : '1.0.0',
					$theme_uri . '/assets/svg/sprite.svg'
				),
				'labels' => array(
					'editorTitle' => 'Редактор практики',
					'editorDescription' => 'Выберите раздел слева и заполните его содержимое.',
					'general' => 'Основные настройки',
					'classicView' => 'Стандартный вид',
					'addSection' => 'Добавить секцию',
					'emptySections' => 'Добавьте первую секцию практики.',
					'needsAttention' => 'Нужно проверить',
					'move' => 'Изменить порядок',
					'moveUp' => 'Переместить выше',
					'moveDown' => 'Переместить ниже',
					'duplicate' => 'Дублировать',
					'remove' => 'Удалить',
					'confirmRemoveSection' => 'Удалить эту секцию? Действие применится после сохранения практики.',
					'confirmRemoveStep' => 'Удалить этот шаг вместе со всеми его упражнениями? Действие применится после сохранения практики.',
					'confirmRemoveExercise' => 'Удалить это упражнение? Действие применится после сохранения практики.',
					'confirmRemoveModification' => 'Удалить эту дополнительную модификацию? Действие применится после сохранения практики.',
					'expand' => 'Раскрыть',
					'collapse' => 'Свернуть',
					'section' => 'Секция',
					'step' => 'Шаг',
					'exercise' => 'Упражнение',
					'modification' => 'Модификация',
					'mainModification' => 'Выполнение',
					'additionalModification' => 'Дополнительная модификация',
				),
				'layoutLabels' => array(
					'anchor_01' => 'О крийе',
					'anchor_02' => 'Эффекты крийи',
					'anchor_03' => 'Философия практики',
					'anchor_04' => 'Рекомендации',
					'anchor_05' => 'Техника выполнения',
					'anchor_07' => 'Видео выполнение',
					'anchor_06' => 'Комментарии',
				),
			)
		);
	}
}

add_action('admin_enqueue_scripts', 'yoga_enqueue_practice_editor_assets', 30);

if (!function_exists('yoga_practice_default_visual_editor')) {
	function yoga_practice_default_visual_editor(string $editor): string {
		return yoga_is_modern_practice_editor() ? 'tinymce' : $editor;
	}
}

add_filter('wp_default_editor', 'yoga_practice_default_visual_editor', 20);

if (!function_exists('yoga_add_practice_editor_body_class')) {
	function yoga_add_practice_editor_body_class(string $classes): string {
		if (yoga_is_modern_practice_editor()) {
			$classes .= ' yoga-modern-practice-editor';
		}

		return $classes;
	}
}

add_filter('admin_body_class', 'yoga_add_practice_editor_body_class');

if (!function_exists('yoga_save_practice_editor_taxonomies')) {
	/**
	 * Persist the custom taxonomy checklists even when the Gutenberg data store
	 * is unavailable on the legacy meta-box screen.
	 */
	function yoga_save_practice_editor_taxonomies(int $post_id): void {
		if (
			wp_is_post_revision($post_id)
			|| wp_is_post_autosave($post_id)
			|| !isset($_POST['_yoga_practice_taxonomy_nonce'])
			|| !wp_verify_nonce(
				sanitize_text_field(wp_unslash($_POST['_yoga_practice_taxonomy_nonce'])),
				'yoga_save_practice_taxonomies'
			)
			|| !current_user_can('edit_post', $post_id)
		) {
			return;
		}

		$submitted = isset($_POST['yoga_practice_taxonomies']) && is_array($_POST['yoga_practice_taxonomies'])
			? wp_unslash($_POST['yoga_practice_taxonomies'])
			: array();
		$taxonomies = array(
			'types' => 'practice-type',
			'goals' => 'practice-goal',
		);

		foreach ($taxonomies as $input_name => $taxonomy) {
			$term_ids = isset($submitted[$input_name]) && is_array($submitted[$input_name])
				? array_values(array_unique(array_filter(array_map('absint', $submitted[$input_name]))))
				: array();
			wp_set_object_terms($post_id, $term_ids, $taxonomy, false);
		}
	}
}

add_action('save_post_practice', 'yoga_save_practice_editor_taxonomies', 30);

if (!function_exists('yoga_save_practice_guest_access')) {
	/**
	 * Persist the guest-access toggle independently of the visual ACF field.
	 * The custom editor moves that field between DOM containers, which can make
	 * repeated Gutenberg meta-box saves omit its value.
	 */
	function yoga_save_practice_guest_access(int $post_id): void {
		if (
			wp_is_post_revision($post_id)
			|| wp_is_post_autosave($post_id)
			|| !isset($_POST['_yoga_practice_guest_access_nonce'])
			|| !wp_verify_nonce(
				sanitize_text_field(wp_unslash($_POST['_yoga_practice_guest_access_nonce'])),
				'yoga_save_practice_guest_access'
			)
			|| !current_user_can('edit_post', $post_id)
			|| !isset($_POST['yoga_practice_open_for_guests'])
		) {
			return;
		}

		$enabled = sanitize_key(wp_unslash($_POST['yoga_practice_open_for_guests'])) === '1';
		update_post_meta($post_id, 'practice_open_for_guests', $enabled ? '1' : '0');

		if (get_post_meta($post_id, '_practice_open_for_guests', true) === '') {
			update_post_meta($post_id, '_practice_open_for_guests', 'field_practice_open_for_guests');
		}

		clean_post_cache($post_id);
	}
}

add_action('save_post_practice', 'yoga_save_practice_guest_access', 40);
