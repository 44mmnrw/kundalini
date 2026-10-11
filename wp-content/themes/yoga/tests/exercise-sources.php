<?php
/** Run with: php wp-content/themes/yoga/tests/exercise-sources.php */
define('ABSPATH', dirname(__DIR__, 4) . '/');
require ABSPATH . 'wp-includes/compat.php';
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/formatting.php';
require ABSPATH . 'wp-includes/kses.php';
require ABSPATH . 'wp-includes/class-wp-token-map.php';
foreach (array('html5-named-character-references.php', 'class-wp-html-attribute-token.php', 'class-wp-html-span.php', 'class-wp-html-doctype-info.php', 'class-wp-html-text-replacement.php', 'class-wp-html-decoder.php', 'class-wp-html-tag-processor.php') as $html_api_file) {
	require ABSPATH . 'wp-includes/html-api/' . $html_api_file;
}
function wp_allowed_protocols() { return array('http', 'https', 'mailto'); }
function esc_attr__($text, $domain) { return htmlspecialchars($text, ENT_QUOTES); }
function get_template_directory_uri() { return 'https://example.test/theme'; }
function yoga_is_acf_field_group_editor() { return !empty($GLOBALS['field_editor']); }
function acf_get_valid_field($field) { return $field; }
class WP_Error {
	public function __construct(public string $code, public string $message) {}
}
function is_wp_error($value) { return $value instanceof WP_Error; }
$GLOBALS['parent_field'] = array('ID' => 55, 'type' => 'repeater', 'sub_fields' => array());
$GLOBALS['saved_fields'] = array();
$GLOBALS['field_writes'] = 0;
function acf_get_field($id) {
	return $id === 'field_exercise_items' ? $GLOBALS['parent_field'] : ($GLOBALS['saved_fields'][$id] ?? false);
}
function get_posts($args) {
	return array_map(static fn($field) => (object) array('ID' => $field['ID'], 'post_parent' => $field['parent']), array_values($GLOBALS['saved_fields']));
}
function acf_update_field($field) {
	$GLOBALS['field_writes']++;
	$field['ID'] = 101;
	$GLOBALS['saved_fields'][101] = $field;
	return $field;
}
require dirname(__DIR__) . '/inc/integrations/acf.php';
require dirname(__DIR__) . '/inc/exercise-sources.php';

function check_sources($valid, $message) {
	if (!$valid) { fwrite(STDERR, $message . "\n"); exit(1); }
}
$original = array('ID' => 55, 'sub_fields' => array(
	array('key' => 'field_ex_title', 'name' => 'title', 'type' => 'text'),
	array('key' => 'field_ex_subtitle', 'name' => 'subtitle', 'type' => 'text'),
	array('key' => 'field_ex_content', 'name' => 'content', 'type' => 'wysiwyg'),
));
$structured = yoga_restructure_practice_exercise_modifications($original);
$with_sources = yoga_add_exercise_sources_field($structured);
check_sources(yoga_add_exercise_sources_field($with_sources) === $with_sources, 'Repeated schema load duplicated or changed sources.');
check_sources(count(array_filter($with_sources['sub_fields'], static fn($field) => ($field['name'] ?? '') === 'sources')) === 1, 'Sources field missing.');
$existing_source = yoga_exercise_sources_field(55);
$existing_source['ID'] = 101;
$existing_source['instructions'] = 'Existing instructions';
$original['sub_fields'][] = $existing_source;
$with_persisted_source = yoga_add_exercise_sources_field(yoga_restructure_practice_exercise_modifications($original));
$retained_sources = array_values(array_filter($with_persisted_source['sub_fields'], static fn($field) => ($field['key'] ?? '') === 'field_ex_sources'));
check_sources(count($retained_sources) === 1 && ($retained_sources[0]['ID'] ?? 0) === 101 && ($retained_sources[0]['instructions'] ?? '') === 'Existing instructions', 'Existing sources field settings were lost during restructuring.');
$GLOBALS['field_editor'] = true;
check_sources(yoga_add_exercise_sources_field($original) === $original, 'Raw ACF schema editor was altered.');
$GLOBALS['field_editor'] = false;
check_sources(yoga_migrate_exercise_sources_field() === 'would_create' && $GLOBALS['field_writes'] === 0, 'Preview wrote a field.');
check_sources(yoga_migrate_exercise_sources_field(true) === 'created', 'Migration did not create field.');
check_sources(yoga_migrate_exercise_sources_field(true) === 'already_exists' && $GLOBALS['field_writes'] === 1, 'Repeat migration was not idempotent.');
$GLOBALS['saved_fields'][101]['parent'] = 999;
check_sources(is_wp_error(yoga_migrate_exercise_sources_field(true)) && $GLOBALS['field_writes'] === 1, 'Conflicting field was overwritten.');

function render_sources($sources) {
	$exercise = array('sources' => $sources);
	ob_start();
	include dirname(__DIR__) . '/template-parts/praktika-info/exercise-sources.php';
	return ob_get_clean();
}
foreach (array('', '  ', '<p><br></p>', '<p>&nbsp;</p>', array()) as $empty) {
	check_sources(render_sources($empty) === '', 'Empty source rendered a block.');
}
$html = render_sources('<p><a href="https://example.test/book">Book</a>, 2016</p><script>alert(1)</script><p>Another source</p>');
check_sources(str_contains($html, 'sprite.svg#exercise-sources-info') && str_contains($html, 'https://example.test/book') && str_contains($html, 'Another source'), 'Sources markup lost icon, link, or paragraphs.');
check_sources(!str_contains($html, 'Описание этой крийи составлено'), 'Template added fallback text to ACF content.');
check_sources(!str_contains($html, '<script'), 'Unsafe markup was rendered.');
check_sources(!str_contains(render_sources('<a href="javascript:alert(1)">Book</a>'), 'href="javascript:'), 'Unsafe link was rendered.');
echo "Exercise sources tests passed.\n";
