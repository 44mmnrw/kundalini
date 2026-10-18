<?php
/** Isolated rich-text image spacing checks; no WordPress or database bootstrap. */
function add_filter() {}
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_url_raw($value) { return $value; }
function wp_get_attachment_image_url() { return ''; }
require dirname(__DIR__) . '/inc/practice-content.php';

$checks = 0;
function check_spacing(bool $condition, string $message): void {
	if (!$condition) throw new RuntimeException($message);
	$GLOBALS['checks']++;
}
$image = '<p class="photo" id="photo"><img src="https://example.test/photo.jpg" class="aligncenter" alt="Photo"></p>';
$html = '<p>Before</p><p><br></p>' . $image . '<p>&nbsp;</p><p>&#160;</p><p>After</p><p>&nbsp;</p>';
$result = yoga_practice_content_images_lightbox($html);
check_spacing(substr_count($result, 'class="praktika-image-spacer"') === 3, 'Only adjacent blank paragraphs should be marked');
check_spacing(str_contains($result, 'class="photo praktika-content-image" id="photo"'), 'Image paragraph attributes must survive');
check_spacing(str_contains($result, '<p>After</p><p>&nbsp;</p>'), 'Unrelated blank paragraph must remain unchanged');
check_spacing(str_contains($result, 'data-fancybox="practice-content-images"'), 'Image must still open in the lightbox');
check_spacing(yoga_practice_content_images_lightbox($result) === $result, 'Repeated formatting must not duplicate classes or links');
$mixed = '<p>Caption <img src="https://example.test/photo.jpg"> text</p><p>&nbsp;</p>';
check_spacing(!str_contains(yoga_practice_content_images_lightbox($mixed), 'praktika-image-spacer'), 'Mixed text and image paragraph must not be treated as an image-only block');
foreach (array('&nbsp;', '&#0160;', '&#xa0;', "\u{00a0}", '<br />', '') as $blank) {
	$case = yoga_practice_content_images_lightbox($image . '<p>' . $blank . '</p><p>After</p>');
	check_spacing(substr_count($case, 'praktika-image-spacer') === 1, 'Blank paragraph form was not recognized');
}
echo "PASS: $checks image spacing checks; no database or remote writes.\n";
