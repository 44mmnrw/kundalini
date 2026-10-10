<?php
/** Empty initial values for editable practice fields; saved values are untouched. */
if (!defined('ABSPATH')) {
	exit;
}

function yoga_is_practice_content_field(array $field): bool {
	$key = (string) ($field['key'] ?? '');
	$key_pattern = '/^field_(anchor_|ex_|exercise_)|^field_practice_(level|time|download|sections|short_description|open_for_guests)$/';
	if (preg_match($key_pattern, $key)) {
		return true;
	}
	// Raw definitions avoid re-entering acf/load_field while a parent is loading.
	$parent = $field['parent'] ?? 0;
	$visited = array();
	while ($parent && !isset($visited[(string) $parent])) {
		$visited[(string) $parent] = true;
		$parent_field = function_exists('acf_get_raw_field') ? acf_get_raw_field($parent) : false;
		if ($parent_field) {
			if (preg_match($key_pattern, (string) ($parent_field['key'] ?? ''))) {
				return true;
			}
			$parent = $parent_field['parent'] ?? 0;
			continue;
		}
		$group = function_exists('acf_get_raw_field_group') ? acf_get_raw_field_group($parent) : false;
		foreach ((array) ($group['location'] ?? array()) as $rules) {
			foreach ($rules as $rule) {
				if (($rule['param'] ?? '') === 'post_type' && ($rule['operator'] ?? '') === '==' && ($rule['value'] ?? '') === 'practice') {
					return true;
				}
			}
		}
		return false;
	}
	return false;
}

function yoga_clear_practice_field_defaults(array $field, bool $in_practice = false): array {
	$in_practice = $in_practice || yoga_is_practice_content_field($field);
	if (!$in_practice) {
		return $field;
	}
	// These hidden fields control anchors, presentation and schema compatibility.
	$service_field = in_array($field['name'] ?? '', array('anchor_id', 'title_class', 'execution_name'), true);
	if (!$service_field) {
		$type = $field['type'] ?? '';
		if ($type === 'true_false') {
			$field['default_value'] = 0;
		} elseif (in_array($type, array('checkbox', 'relationship', 'gallery', 'repeater', 'flexible_content'), true)) {
			$field['default_value'] = array();
		} else {
			$field['default_value'] = '';
		}
		if (in_array($type, array('select', 'radio', 'button_group'), true)) {
			$field['allow_null'] = 1;
		}
	}
	foreach ((array) ($field['sub_fields'] ?? array()) as $index => $sub_field) {
		$field['sub_fields'][$index] = yoga_clear_practice_field_defaults($sub_field, true);
	}
	foreach ((array) ($field['layouts'] ?? array()) as $index => $layout) {
		foreach ((array) ($layout['sub_fields'] ?? array()) as $sub_index => $sub_field) {
			$field['layouts'][$index]['sub_fields'][$sub_index] = yoga_clear_practice_field_defaults($sub_field, true);
		}
	}
	return $field;
}

function yoga_load_empty_practice_field_defaults(array $field): array {
	if (function_exists('yoga_is_acf_field_group_editor') && yoga_is_acf_field_group_editor()) {
		return $field;
	}
	return yoga_clear_practice_field_defaults($field);
}
add_filter('acf/load_field', 'yoga_load_empty_practice_field_defaults', PHP_INT_MAX);
