<?php
/**
 * Exercise bibliography field and additive ACF schema migration.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

function yoga_exercise_sources_field(int $parent = 0): array {
	return array(
		'key' => 'field_ex_sources',
		'label' => 'Источники',
		'name' => 'sources',
		'type' => 'wysiwyg',
		'instructions' => 'Укажите источники описания упражнения: книги, издательство, год и ссылки. Можно добавить несколько абзацев. Поле общее для всех вариантов выполнения. Если не заполнено, блок на сайте не отображается.',
		'required' => 0,
		'default_value' => '',
		'tabs' => 'all',
		'toolbar' => 'basic',
		'media_upload' => 0,
		'delay' => 0,
		'parent' => $parent,
		'parent_repeater' => 'field_exercise_items',
	);
}

/** Keep the field in Common data after the existing editor schema filters. */
function yoga_add_exercise_sources_field(array $field): array {
	if (yoga_is_acf_field_group_editor() || empty($field['sub_fields'])) {
		return $field;
	}
	$sources = yoga_exercise_sources_field((int) ($field['ID'] ?? 0));
	$fields = array();
	foreach ($field['sub_fields'] as $sub_field) {
		if (($sub_field['key'] ?? '') === 'field_ex_sources') {
			$sources = $sub_field;
		} else {
			$fields[] = $sub_field;
		}
	}
	$position = count($fields);
	foreach ($fields as $index => $sub_field) {
		if (($sub_field['key'] ?? '') === 'field_ex_admin_main_modification') {
			$position = $index;
			break;
		}
	}
	if (function_exists('acf_get_valid_field')) {
		$sources = acf_get_valid_field($sources);
	}
	array_splice($fields, $position, 0, array($sources));
	$field['sub_fields'] = $fields;
	return $field;
}
add_filter('acf/load_field/key=field_exercise_items', 'yoga_add_exercise_sources_field', 30);

/** Explicitly invoked by the CLI script; never runs on a page request. */
function yoga_migrate_exercise_sources_field(bool $apply = false) {
	if (!function_exists('acf_get_field') || !function_exists('acf_update_field')) {
		return new WP_Error('acf_unavailable', 'ACF is unavailable.');
	}
	$parent = acf_get_field('field_exercise_items');
	if (empty($parent['ID']) || ($parent['type'] ?? '') !== 'repeater') {
		return new WP_Error('exercise_field_missing', 'The persisted exercise repeater is unavailable.');
	}
	$existing = get_posts(array(
		'post_type' => 'acf-field',
		'post_status' => 'any',
		'name' => 'field_ex_sources',
		'posts_per_page' => 2,
	));
	if ($existing) {
		$saved = acf_get_field((int) $existing[0]->ID);
		if (count($existing) !== 1 || (int) $existing[0]->post_parent !== (int) $parent['ID']
			|| ($saved['name'] ?? '') !== 'sources' || ($saved['type'] ?? '') !== 'wysiwyg') {
			return new WP_Error('sources_field_conflict', 'An incompatible field_ex_sources already exists; no changes made.');
		}
		return 'already_exists';
	}
	foreach (($parent['sub_fields'] ?? array()) as $sub_field) {
		if (($sub_field['name'] ?? '') === 'sources' && ($sub_field['key'] ?? '') !== 'field_ex_sources') {
			return new WP_Error('sources_name_conflict', 'Another sources field already exists; no changes made.');
		}
	}
	if (!$apply) {
		return 'would_create';
	}
	$new_field = yoga_exercise_sources_field((int) $parent['ID']);
	$new_field['menu_order'] = count($parent['sub_fields'] ?? array());
	$saved = acf_update_field($new_field);
	return !empty($saved['ID']) ? 'created' : new WP_Error('sources_create_failed', 'ACF could not persist the sources field.');
}
