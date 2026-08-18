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
		$classic_url = $post_id > 0
			? add_query_arg('yoga_editor', 'classic', get_edit_post_link($post_id, 'raw'))
			: add_query_arg(
				array('post_type' => 'practice', 'yoga_editor' => 'classic'),
				admin_url('post-new.php')
			);

		wp_localize_script(
			'yoga-admin-practice-editor',
			'yogaPracticeEditor',
			array(
				'classicUrl' => $classic_url,
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
					'confirmRemoveModification' => 'Удалить эту дополнительную модификацию? Действие применится после сохранения практики.',
					'expand' => 'Раскрыть',
					'collapse' => 'Свернуть',
					'section' => 'Секция',
					'step' => 'Шаг',
					'exercise' => 'Упражнение',
					'modification' => 'Модификация',
					'mainModification' => 'Основная модификация',
					'additionalModification' => 'Дополнительная модификация',
				),
				'layoutLabels' => array(
					'anchor_01' => 'О крийе',
					'anchor_02' => 'Эффекты крийи',
					'anchor_03' => 'Философия практики',
					'anchor_04' => 'Рекомендации',
					'anchor_05' => 'Техника выполнения',
					'anchor_06' => 'Комментарии',
				),
			)
		);
	}
}

add_action('admin_enqueue_scripts', 'yoga_enqueue_practice_editor_assets', 30);

if (!function_exists('yoga_add_practice_editor_body_class')) {
	function yoga_add_practice_editor_body_class(string $classes): string {
		if (yoga_is_modern_practice_editor()) {
			$classes .= ' yoga-modern-practice-editor';
		}

		return $classes;
	}
}

add_filter('admin_body_class', 'yoga_add_practice_editor_body_class');
