<?php
/** Additional audio choices for exercises and their execution variants. */
if (!defined('ABSPATH')) {
	exit;
}

function yoga_add_exercise_audio_fields(array $field): array {
	if (yoga_is_acf_field_group_editor() || empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
		return $field;
	}
	$insert = static function (array $fields, string $key, string $parent_key, int $parent_id): array {
		foreach ($fields as $child) {
			if (($child['name'] ?? '') === 'additional_audio') {
				return $fields;
			}
		}
		$media_type_key = '';
		foreach ($fields as $child) {
			if (($child['name'] ?? '') === 'media_type') {
				$media_type_key = (string) $child['key'];
			}
		}
		if ($media_type_key === '') {
			return $fields;
		}
		$audio_field = array(
			'key' => $key,
			'name' => 'additional_audio',
			'label' => 'Дополнительные аудиозаписи',
			'type' => 'repeater',
			'parent' => $parent_id,
			'parent_repeater' => $parent_key,
			'layout' => 'block',
			'button_label' => 'Добавить аудио',
			'collapsed' => $key . '_title',
			'min' => 0,
			'max' => 0,
			'required' => 0,
			'instructions' => 'Записи появятся в выборе аудио рядом с основным медиафайлом. Название необязательно. Можно оставить основной медиафайл пустым и заполнить только этот список.',
			'conditional_logic' => array(array(array('field' => $media_type_key, 'operator' => '==', 'value' => 'audio'))),
			'sub_fields' => array(
				array('key' => $key . '_title', 'name' => 'title', 'label' => 'Название аудио', 'type' => 'text', 'required' => 0, 'parent_repeater' => $key),
				array('key' => $key . '_file', 'name' => 'file', 'label' => 'Аудиофайл', 'type' => 'file', 'required' => 1, 'return_format' => 'array', 'library' => 'all', 'mime_types' => 'mp3,m4a,aac,ogg,wav,flac', 'parent_repeater' => $key),
			),
		);
		if (function_exists('acf_get_valid_field')) {
			$audio_field = acf_get_valid_field($audio_field);
		}
		foreach ($fields as $index => $child) {
			if (($child['name'] ?? '') === 'media_file') {
				array_splice($fields, $index + 1, 0, array($audio_field));
				return $fields;
			}
		}
		$fields[] = $audio_field;
		return $fields;
	};
	$field['sub_fields'] = $insert($field['sub_fields'], 'field_ex_additional_audio', 'field_exercise_items', (int) ($field['ID'] ?? 0));
	foreach ($field['sub_fields'] as $index => $child) {
		if (($child['name'] ?? '') === 'modifications' && !empty($child['sub_fields'])) {
			$field['sub_fields'][$index]['sub_fields'] = $insert($child['sub_fields'], 'field_ex_modifications_additional_audio', 'field_ex_modifications', (int) ($child['ID'] ?? 0));
		}
	}
	return $field;
}
add_filter('acf/load_field/key=field_exercise_items', 'yoga_add_exercise_audio_fields', 31);

/** Add an optional primary-track title to each execution variant. */
function yoga_add_exercise_audio_title_fields(array $field): array {
	if (yoga_is_acf_field_group_editor()) {
		return $field;
	}
	$insert = static function (array $fields, string $parent_key, int $parent_id): array {
		$by_name = array_column($fields, null, 'name');
		if (isset($by_name['audio_title']) || empty($by_name['media_type']['key'])) {
			return $fields;
		}
		$title = array(
			'key' => $parent_key === 'field_exercise_items' ? 'field_ex_audio_title' : 'field_ex_modifications_audio_title',
			'name' => 'audio_title',
			'label' => 'Название основного аудио',
			'type' => 'text',
			'parent' => $parent_id,
			'parent_repeater' => $parent_key,
			'required' => 0,
			'default_value' => '',
			'instructions' => 'Верхний заголовок плеера вместо «Аудиопрактика», а также название в списке аудио. Если не заполнено, используется «Основное аудио».',
			'conditional_logic' => array(array(array('field' => $by_name['media_type']['key'], 'operator' => '==', 'value' => 'audio'))),
		);
		$title = yoga_validate_practice_exercise_field_tree($title);
		foreach ($fields as $index => $child) {
			if (($child['name'] ?? '') === 'media_file') {
				array_splice($fields, $index, 0, array($title));
				break;
			}
		}
		return $fields;
	};
	$field['sub_fields'] = $insert($field['sub_fields'] ?? array(), 'field_exercise_items', (int) ($field['ID'] ?? 0));
	foreach ($field['sub_fields'] as $index => $child) {
		if (($child['name'] ?? '') === 'modifications') {
			$field['sub_fields'][$index]['sub_fields'] = $insert($child['sub_fields'] ?? array(), 'field_ex_modifications', (int) ($child['ID'] ?? 0));
		}
	}
	return $field;
}
add_filter('acf/load_field/key=field_exercise_items', 'yoga_add_exercise_audio_title_fields', 32);

/** Keep the existing single file as the first choice; ignore empty/invalid rows. */
function yoga_get_exercise_audio_tracks(array $args): array {
	if (($args['media_type'] ?? '') !== 'audio') {
		return array();
	}
	$tracks = array();
	$primary = yoga_get_practice_media_file_url($args['media_file'] ?? null);
	if ($primary !== '') {
		$title = is_scalar($args['audio_title'] ?? '') ? trim(wp_strip_all_tags((string) ($args['audio_title'] ?? ''))) : '';
		$tracks[] = array('src' => $primary, 'title' => $title !== '' ? $title : 'Основное аудио');
	}
	$rows = $args['additional_audio'] ?? array();
	foreach (is_array($rows) ? $rows : array() as $row) {
		if (!is_array($row)) {
			continue;
		}
		$src = yoga_get_practice_media_file_url($row['file'] ?? null);
		if ($src === '') {
			continue;
		}
		$title = is_scalar($row['title'] ?? '') ? trim(wp_strip_all_tags((string) ($row['title'] ?? ''))) : '';
		$tracks[] = array('src' => $src, 'title' => $title !== '' ? $title : 'Аудио ' . (count($tracks) + 1));
	}
	return $tracks;
}
