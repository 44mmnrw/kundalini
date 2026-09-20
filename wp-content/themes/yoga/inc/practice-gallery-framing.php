<?php
/**
 * Per-image framing for practice exercise galleries.
 *
 * @package Yoga
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_practice_gallery_framing')) {
	function yoga_practice_gallery_framing($value): array {
		if (is_string($value)) {
			$value = json_decode($value, true);
		}
		if (!is_array($value)) {
			return array();
		}

		$framing = array();
		foreach ($value as $attachment_id => $settings) {
			$id = filter_var($attachment_id, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
			if (!$id || !is_array($settings)) {
				continue;
			}

			$zoom = isset($settings['zoom']) && is_numeric($settings['zoom']) ? (int) round((float) $settings['zoom']) : 100;
			$x = isset($settings['x']) && is_numeric($settings['x']) ? (int) round((float) $settings['x']) : 50;
			$y = isset($settings['y']) && is_numeric($settings['y']) ? (int) round((float) $settings['y']) : 50;
			$zoom = max(50, min(300, $zoom));
			$x = max(0, min(100, $x));
			$y = max(0, min(100, $y));

			if ($zoom !== 100 || $x !== 50 || $y !== 50) {
				$framing[(int) $id] = array('zoom' => $zoom, 'x' => $x, 'y' => $y);
			}
		}

		return $framing;
	}
}

if (!function_exists('yoga_save_practice_gallery_framing')) {
	function yoga_save_practice_gallery_framing($value): string {
		$framing = yoga_practice_gallery_framing($value);
		return $framing === array() ? '' : wp_json_encode($framing);
	}
}

add_filter('acf/update_value/key=field_ex_gallery_framing', 'yoga_save_practice_gallery_framing');
add_filter('acf/update_value/key=field_ex_modifications_gallery_framing', 'yoga_save_practice_gallery_framing');

if (!function_exists('yoga_add_practice_gallery_framing_fields')) {
	function yoga_add_practice_gallery_framing_fields(array $field): array {
		if (yoga_is_acf_field_group_editor() || empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
			return $field;
		}

		$make_field = static function (string $key, string $parent_repeater): array {
			$framing_field = array(
				'ID'              => 0,
				'key'             => $key,
				'label'           => 'Настройки кадра изображений',
				'name'            => 'gallery_framing',
				'_name'           => 'gallery_framing',
				'prefix'          => 'acf',
				'type'            => 'textarea',
				'required'        => 0,
				'wrapper'         => array('width' => '', 'class' => 'yoga-gallery-framing-storage', 'id' => ''),
				'default_value'   => '',
				'rows'            => 2,
				'new_lines'       => '',
				'parent'          => 0,
				'parent_repeater' => $parent_repeater,
			);
			return function_exists('acf_get_valid_field') ? acf_get_valid_field($framing_field) : $framing_field;
		};

		$insert_after_gallery = static function (array $fields, string $key, string $parent_repeater) use ($make_field): array {
			foreach ($fields as $sub_field) {
				if (($sub_field['name'] ?? '') === 'gallery_framing') {
					return $fields;
				}
			}
			foreach ($fields as $index => $sub_field) {
				if (($sub_field['name'] ?? '') === 'gallery') {
					array_splice($fields, $index + 1, 0, array($make_field($key, $parent_repeater)));
					break;
				}
			}
			return $fields;
		};

		$field['sub_fields'] = $insert_after_gallery($field['sub_fields'], 'field_ex_gallery_framing', 'field_exercise_items');
		foreach ($field['sub_fields'] as $index => $sub_field) {
			if (($sub_field['name'] ?? '') !== 'modifications' || empty($sub_field['sub_fields'])) {
				continue;
			}
			$field['sub_fields'][$index]['sub_fields'] = $insert_after_gallery(
				$sub_field['sub_fields'],
				'field_ex_modifications_gallery_framing',
				'field_ex_modifications'
			);
		}

		return $field;
	}
}

add_filter('acf/load_field/key=field_exercise_items', 'yoga_add_practice_gallery_framing_fields', 25);

if (!function_exists('yoga_enqueue_practice_gallery_framing_assets')) {
	function yoga_enqueue_practice_gallery_framing_assets(): void {
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'practice') {
			return;
		}

		$theme_dir = get_template_directory();
		$theme_uri = get_template_directory_uri();
		$style = '/assets/css/admin-practice-gallery-framing.css';
		$script = '/assets/js/admin-practice-gallery-framing.js';
		wp_enqueue_style('yoga-practice-gallery-framing', $theme_uri . $style, array(), (string) filemtime($theme_dir . $style));
		wp_enqueue_script('yoga-practice-gallery-framing', $theme_uri . $script, array('jquery', 'acf-input'), (string) filemtime($theme_dir . $script), true);
	}
}

add_action('admin_enqueue_scripts', 'yoga_enqueue_practice_gallery_framing_assets', 31);
