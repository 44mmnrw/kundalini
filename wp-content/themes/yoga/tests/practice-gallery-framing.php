<?php
/** Run with: php tests/practice-gallery-framing.php */

define('ABSPATH', __DIR__ . '/');
function add_filter() {}
function add_action() {}
function yoga_is_acf_field_group_editor(): bool { return false; }
function wp_json_encode($value): string { return json_encode($value); }

require dirname(__DIR__) . '/inc/practice-gallery-framing.php';

$fail = static function (string $message): void {
	fwrite(STDERR, $message . PHP_EOL);
	exit(1);
};

$frames = yoga_practice_gallery_framing(json_encode(array(
	'23' => array('zoom' => 160, 'x' => 0, 'y' => 100),
	'24' => array('zoom' => 100, 'x' => 50, 'y' => 50),
	'25' => array('zoom' => 999, 'x' => -10, 'y' => 150),
	'26' => array('zoom' => 75, 'x' => 35, 'y' => 65),
	'27' => array('zoom' => 1, 'x' => 50, 'y' => 50),
	'bad' => array('zoom' => 150),
)));
if ($frames !== array(
	23 => array('zoom' => 160, 'x' => 0, 'y' => 100),
	25 => array('zoom' => 300, 'x' => 0, 'y' => 100),
	26 => array('zoom' => 75, 'x' => 35, 'y' => 65),
	27 => array('zoom' => 50, 'x' => 50, 'y' => 50),
)) {
	$fail('Framing values were not normalized correctly.');
}
if (yoga_practice_gallery_framing(yoga_save_practice_gallery_framing($frames)) !== $frames) {
	$fail('Saved framing values did not round-trip.');
}
if (yoga_save_practice_gallery_framing(addslashes(json_encode($frames))) !== json_encode($frames)) {
	$fail('WordPress-slashed form data was not saved.');
}

$field = array('sub_fields' => array(
	array('name' => 'gallery'),
	array('name' => 'modifications', 'sub_fields' => array(array('name' => 'gallery'))),
));
$field = yoga_add_practice_gallery_framing_fields($field);
if (($field['sub_fields'][1]['key'] ?? '') !== 'field_ex_gallery_framing'
	|| ($field['sub_fields'][2]['sub_fields'][1]['key'] ?? '') !== 'field_ex_modifications_gallery_framing'
	|| yoga_add_practice_gallery_framing_fields($field) !== $field) {
	$fail('Main and modification gallery fields were not inserted once.');
}

echo "Practice gallery framing OK\n";
