<?php
/**
 * Компонент темы: search.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

	if (!function_exists('yoga_is_practice_type_term_available')) {






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
