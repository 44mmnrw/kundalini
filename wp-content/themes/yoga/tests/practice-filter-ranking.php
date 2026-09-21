<?php

define('ABSPATH', __DIR__ . '/');

$GLOBALS['yoga_test_taxonomies'] = array(
	'practice-goal' => true,
	'practice-duration' => false,
	'unrelated' => false,
);
$GLOBALS['yoga_test_object_terms'] = array(
	'practice-goal' => array(
		30 => array(1, 2),
		20 => array(1),
		10 => array(3, 101),
		40 => array(),
	),
	'practice-duration' => array(
		30 => array(5),
		20 => array(6),
		10 => array(5),
		40 => array(),
	),
);

function add_action() {
	return true;
}

function sanitize_key($key) {
	return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $key));
}

function taxonomy_exists($taxonomy) {
	return array_key_exists($taxonomy, $GLOBALS['yoga_test_taxonomies']);
}

function is_object_in_taxonomy($object_type, $taxonomy) {
	return $object_type === 'practice' && str_starts_with($taxonomy, 'practice-');
}

function absint($value) {
	return abs((int) $value);
}

function is_wp_error($value) {
	return false;
}

function is_taxonomy_hierarchical($taxonomy) {
	return !empty($GLOBALS['yoga_test_taxonomies'][$taxonomy]);
}

function get_term_children($term_id, $taxonomy) {
	return $taxonomy === 'practice-goal' && (int) $term_id === 100 ? array(101) : array();
}

function wp_get_object_terms($object_ids, $taxonomy, $args = array()) {
	$terms = array();
	foreach ($object_ids as $object_id) {
		foreach ($GLOBALS['yoga_test_object_terms'][$taxonomy][$object_id] ?? array() as $term_id) {
			$terms[] = (object) array(
				'object_id' => (int) $object_id,
				'term_id' => (int) $term_id,
			);
		}
	}
	return $terms;
}

require dirname(__DIR__) . '/inc/ajax/practice-search.php';

function yoga_practice_filter_test_assert($condition, string $message): void {
	if (!$condition) {
		fwrite(STDERR, "FAIL: {$message}\n");
		exit(1);
	}
}

$normalized = yoga_normalize_practice_filter_terms(array(
	'practice-goal' => array('1', '1', '3', '0'),
	'practice-duration' => '5',
	'unrelated' => array(9),
	'not-a-taxonomy' => array(7),
));
yoga_practice_filter_test_assert(
	$normalized === array(
		'practice-goal' => array(1, 3),
		'practice-duration' => array(5),
	),
	'filters are normalized and limited to practice taxonomies'
);

$ranked = yoga_rank_practice_ids_by_filter_matches(
	array(30, 20, 10, 40),
	array(
		'practice-goal' => array(1, 3),
		'practice-duration' => array(5),
	)
);
yoga_practice_filter_test_assert(
	$ranked === array(30, 10, 20),
	'full matches come first, partial matches follow, and zero matches are omitted'
);

$parent_ranked = yoga_rank_practice_ids_by_filter_matches(
	array(30, 20, 10, 40),
	array('practice-goal' => array(100))
);
yoga_practice_filter_test_assert(
	$parent_ranked === array(10),
	'a selected parent term also matches practices assigned to its descendants'
);

$unfiltered = yoga_rank_practice_ids_by_filter_matches(array(30, 20, 10, 40), array());
yoga_practice_filter_test_assert(
	$unfiltered === array(30, 20, 10, 40),
	'without filters the original date order is preserved'
);

yoga_practice_filter_test_assert(
	yoga_get_practice_sort_query_args('oldest') === array('orderby' => 'date', 'order' => 'ASC'),
	'oldest sorting is converted to an ascending date query'
);
yoga_practice_filter_test_assert(
	yoga_get_practice_sort_query_args('title') === array('orderby' => 'title', 'order' => 'ASC'),
	'alphabetical sorting is converted to a title query'
);
yoga_practice_filter_test_assert(
	yoga_get_practice_sort_query_args('unexpected-value') === array('orderby' => 'date', 'order' => 'DESC'),
	'an unsupported sorting value falls back to newest first'
);

fwrite(STDOUT, "Practice filter ranking tests passed.\n");
