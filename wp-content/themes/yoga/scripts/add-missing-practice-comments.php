<?php
/**
 * Add the comments section to existing practices that already have sections.
 *
 * Preview: php wp-content/themes/yoga/scripts/add-missing-practice-comments.php
 * Apply:   php wp-content/themes/yoga/scripts/add-missing-practice-comments.php --apply
 */

if (PHP_SAPI !== 'cli') {
	exit(1);
}

$arguments = array_slice($argv, 1);
if (array_diff($arguments, array('--apply')) || count($arguments) !== count(array_unique($arguments))) {
	fwrite(STDERR, "Usage: php add-missing-practice-comments.php [--apply]\n");
	exit(2);
}
$apply = in_array('--apply', $arguments, true);

require dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('add_row') || !function_exists('acf_get_field')) {
	fwrite(STDERR, "ACF practice sections field is unavailable.\n");
	exit(1);
}

$sections_field = acf_get_field('field_practice_sections');
if (!is_array($sections_field)) {
	fwrite(STDERR, "ACF practice sections field is unavailable.\n");
	exit(1);
}
$comments_layout = null;
foreach (($sections_field['layouts'] ?? array()) as $layout) {
	if (($layout['name'] ?? '') === 'anchor_06') {
		$comments_layout = $layout;
		break;
	}
}
$sub_field_names = is_array($comments_layout) ? array_column($comments_layout['sub_fields'] ?? array(), 'name') : array();
if (array_diff(array('anchor_id', 'title', 'section_title', 'comments'), $sub_field_names)) {
	fwrite(STDERR, "ACF comments layout is unavailable or has changed.\n");
	exit(1);
}

$page = 1;
$checked = 0;
$missing = 0;
$added = 0;

do {
	$query = new WP_Query(array(
		'post_type'              => 'practice',
		'post_status'            => array('publish', 'future', 'private', 'pending', 'draft'),
		'posts_per_page'         => 100,
		'paged'                  => $page++,
		'orderby'                => 'ID',
		'order'                  => 'ASC',
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	));
	$practice_ids = $query->posts;

	foreach ($practice_ids as $practice_id) {
		$checked++;
		$layouts = get_post_meta($practice_id, 'practice_sections', true);
		if (!is_array($layouts) || !$layouts || in_array('anchor_06', $layouts, true)) {
			continue;
		}

		$missing++;
		echo ($apply ? 'Adding' : 'Would add') . ' comments section to practice #' . $practice_id . PHP_EOL;
		if (!$apply) {
			continue;
		}

		$row = array(
			'acf_fc_layout' => 'anchor_06',
			'anchor_id'     => 'anchor_07',
			'title'         => 'Комментарии',
			'section_title' => 'Комментарии',
			'comments'      => array(),
		);
		$result = add_row('field_practice_sections', $row, $practice_id);
		$updated_layouts = get_post_meta($practice_id, 'practice_sections', true);
		$new_row_prefix = 'practice_sections_' . count($layouts) . '_';
		if (
			!$result
			|| !is_array($updated_layouts)
			|| count($updated_layouts) !== count($layouts) + 1
			|| array_slice($updated_layouts, 0, count($layouts)) !== $layouts
			|| end($updated_layouts) !== 'anchor_06'
			|| get_post_meta($practice_id, $new_row_prefix . 'title', true) !== 'Комментарии'
			|| get_post_meta($practice_id, $new_row_prefix . 'section_title', true) !== 'Комментарии'
		) {
			fwrite(STDERR, "Failed to verify practice #{$practice_id}; stopping.\n");
			exit(1);
		}
		$added++;
	}
} while (count($practice_ids) === 100);

echo "Checked: {$checked}; missing comments: {$missing}";
echo $apply ? "; added: {$added}.\n" : ". Run with --apply to add them.\n";
