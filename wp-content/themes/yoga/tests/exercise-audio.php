<?php
/** Run with: php wp-content/themes/yoga/tests/exercise-audio.php */
define('ABSPATH', __DIR__ . '/');
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
	$GLOBALS['audio_test_filters'][$hook][$priority][] = $callback;
}
function add_action() {}
function yoga_is_acf_field_group_editor() { return !empty($GLOBALS['schema_editor']); }
function acf_get_valid_field($field) { return $field; }
// Match ACF's shallow validation: children need their own validation pass.
function acf_validate_field($field) {
	$field += array('instructions' => '', 'wrapper' => array(), '_name' => $field['name'] ?? '');
	$field['wrapper'] += array('width' => '', 'class' => '', 'id' => '');
	return $field;
}
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function sanitize_html_class($value) { return sanitize_key($value); }
function esc_url_raw($value) { return preg_match('~^https?://~', $value) ? $value : ''; }
function esc_url($value) { return htmlspecialchars(esc_url_raw($value), ENT_QUOTES); }
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES); }
function wp_strip_all_tags($value) { return strip_tags($value); }
function wp_get_attachment_url($id) { return 'https://example.test/' . $id . '.mp3'; }
require dirname(__DIR__) . '/inc/integrations/acf.php';
require dirname(__DIR__) . '/inc/practice-media.php';
require dirname(__DIR__) . '/inc/exercise-sources.php';
require dirname(__DIR__) . '/inc/exercise-audio.php';
function check_audio($condition, $message) {
	if (!$condition) { fwrite(STDERR, $message . "\n"); exit(1); }
}
$main_fields = array(
	array('key' => 'field_ex_media_type', 'name' => 'media_type', 'type' => 'radio'),
	array('key' => 'field_ex_media_file', 'name' => 'media_file', 'type' => 'file'),
);
$field = array('ID' => 55, 'sub_fields' => array_merge($main_fields, array(
	array('name' => 'modifications', 'key' => 'field_ex_modifications', 'sub_fields' => array(
		array('key' => 'field_ex_modifications_media_type', 'name' => 'media_type', 'type' => 'radio'),
		array('key' => 'field_ex_modifications_media_file', 'name' => 'media_file', 'type' => 'file'),
	)),
)));
$result = yoga_add_exercise_audio_fields($field);
check_audio(yoga_add_exercise_audio_fields($result) === $result, 'Repeated schema load duplicated audio fields.');
check_audio($result['sub_fields'][2]['name'] === 'additional_audio' && $result['sub_fields'][3]['sub_fields'][2]['name'] === 'additional_audio', 'Audio field not available in main and modification.');
check_audio($result['sub_fields'][3]['sub_fields'][2]['conditional_logic'][0][0]['field'] === 'field_ex_modifications_media_type', 'Modification condition targets the wrong field.');
$GLOBALS['schema_editor'] = true;
check_audio(yoga_add_exercise_audio_fields($field) === $field, 'Raw schema editor changed.');
$GLOBALS['schema_editor'] = false;
// Run the actual editor filter chain, including the legacy-to-current layout conversion.
$legacy_modification_fields = array();
foreach (array('modification_name', 'matter', 'details', 'timing', 'media_type', 'media_file', 'duration', 'gallery', 'content') as $name) {
	$legacy_modification_fields[] = array('key' => 'field_ex_additional_modification_' . $name, 'name' => $name, 'type' => 'text');
}
$editor_field = array('ID' => 55, 'key' => 'field_exercise_items', 'sub_fields' => array_merge($main_fields, array(
	array('name' => 'duration', 'key' => 'field_ex_duration', 'type' => 'number'),
	array('name' => 'additional_modifications', 'key' => 'field_ex_additional_modifications', 'type' => 'repeater', 'sub_fields' => $legacy_modification_fields),
)));
$filters = $GLOBALS['audio_test_filters']['acf/load_field/key=field_exercise_items'];
ksort($filters);
foreach ($filters as $callbacks) {
	foreach ($callbacks as $callback) {
		$editor_field = $callback($editor_field);
	}
}
$editor_names = array_column($editor_field['sub_fields'], 'name');
function check_renderable_audio_fields($field) {
	check_audio(isset($field['instructions'], $field['wrapper']['width'], $field['wrapper']['class'], $field['wrapper']['id']), 'Nested ACF field lacks rendering defaults: ' . ($field['key'] ?? 'unknown'));
	foreach ($field['sub_fields'] ?? array() as $child) {
		check_renderable_audio_fields($child);
	}
}
check_renderable_audio_fields($editor_field);
$media_index = array_search('media_file', $editor_names, true);
check_audio($editor_names[$media_index + 1] === 'additional_audio' && $editor_names[$media_index + 2] === 'duration', 'Full ACF editor chain removed or misplaced the main audio list.');
$modifications = $editor_field['sub_fields'][array_search('modifications', $editor_names, true)];
check_audio(in_array('additional_audio', array_column($modifications['sub_fields'], 'name'), true), 'Full ACF editor chain lost modification audio.');
$rows = array(array('file' => 2, 'title' => '<b>Second</b>'), array('file' => '', 'title' => 'Empty'), null, array('file' => array('url' => 'https://example.test/third.ogg')));
$args = array('media_type' => 'audio', 'media_file' => array('url' => 'https://example.test/first.mp3'), 'additional_audio' => $rows);
$tracks = yoga_get_exercise_audio_tracks($args);
check_audio(count($tracks) === 3 && $tracks[0]['src'] === 'https://example.test/first.mp3' && $tracks[1]['title'] === 'Second' && $tracks[2]['title'] === 'Аудио 3', 'Audio normalization lost compatibility or included empty rows.');
check_audio(yoga_get_exercise_audio_tracks(array('media_type' => 'video', 'additional_audio' => $rows)) === array(), 'Audio list leaked into video.');
function render_audio($args) { ob_start(); yoga_render_practice_media_player($args); return ob_get_clean(); }
$single = render_audio(array('media_type' => 'audio', 'media_file' => 1));
check_audio(str_contains($single, '/1.mp3') && !str_contains($single, '<select'), 'Single-file practice changed.');
$multiple = render_audio($args);
check_audio(substr_count($multiple, '<option ') === 3 && substr_count($multiple, '<audio ') === 1, 'Expected one player with three choices.');
unset($args['media_file']);
$only_list = render_audio($args);
check_audio(str_contains($only_list, 'data-media-src="https://example.test/2.mp3"') && substr_count($only_list, '<option ') === 2, 'Audio list without primary file did not render.');
check_audio(render_audio(array('media_type' => 'audio', 'additional_audio' => array(array('file' => '')))) === '', 'Empty audio rendered a player.');
echo "Exercise audio tests passed.\n";
