<?php

define('ABSPATH', __DIR__ . '/');

class WP_Term {
	public $term_id;
	public $name;
	public $taxonomy;

	public function __construct(int $term_id, string $name, string $taxonomy) {
		$this->term_id = $term_id;
		$this->name = $name;
		$this->taxonomy = $taxonomy;
	}
}

$GLOBALS['yoga_test_term_meta'] = array(
	1 => array('_yoga_term_order' => '30'),
	2 => array('_yoga_term_order' => '10'),
	4 => array('practice_type_card_order' => '5'),
);
$GLOBALS['wpdb'] = (object) array('termmeta' => 'wp_termmeta');

function apply_filters($hook, $value) {
	return $value;
}

function add_filter() {
	return true;
}

function add_action() {
	return true;
}

function sanitize_key($key) {
	return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $key));
}

function absint($value) {
	return abs((int) $value);
}

function esc_sql($value) {
	return addslashes((string) $value);
}

function metadata_exists($type, $term_id, $meta_key) {
	return array_key_exists($meta_key, $GLOBALS['yoga_test_term_meta'][$term_id] ?? array());
}

function get_term_meta($term_id, $meta_key, $single = false) {
	return $GLOBALS['yoga_test_term_meta'][$term_id][$meta_key] ?? '';
}

require dirname(__DIR__) . '/inc/admin/term-order.php';

function yoga_term_order_test_assert($condition, string $message): void {
	if (!$condition) {
		fwrite(STDERR, "FAIL: {$message}\n");
		exit(1);
	}
}

yoga_term_order_test_assert(
	yoga_get_sortable_term_taxonomies() === array('practice-difficulty', 'practice-duration', 'practice-type', 'practice-goal'),
	'all practice dictionaries support manual ordering'
);
yoga_term_order_test_assert(yoga_get_term_order_meta_key('practice-type') === 'practice_type_card_order', 'practice type keeps its legacy card order');
yoga_term_order_test_assert(yoga_get_term_order_meta_key('practice-goal') === '_yoga_term_order', 'other dictionaries use the shared order meta');
yoga_term_order_test_assert(yoga_get_term_manual_order(2, 'practice-difficulty') === 10, 'stored numeric order is read');
yoga_term_order_test_assert(yoga_get_term_manual_order(3, 'practice-difficulty') === null, 'missing order remains unset');

$orderby_sql = yoga_manual_term_order_sql('t.name', array(), array('practice-difficulty'));
yoga_term_order_test_assert(str_ends_with($orderby_sql, 't.term_id'), 'WordPress can append the query direction without producing duplicate SQL');

$terms = array(
	new WP_Term(1, 'Третий', 'practice-difficulty'),
	new WP_Term(3, 'Без порядка', 'practice-difficulty'),
	new WP_Term(2, 'Первый', 'practice-difficulty'),
);
$sorted = yoga_sort_terms_by_manual_order($terms, array('practice-difficulty'), array('fields' => 'all'));
yoga_term_order_test_assert(array_map(static fn($term) => $term->term_id, $sorted) === array(2, 1, 3), 'saved order is applied and unset items go last');

$untouched = yoga_sort_terms_by_manual_order($terms, array('practice-difficulty'), array('yoga_manual_order' => false));
yoga_term_order_test_assert(array_map(static fn($term) => $term->term_id, $untouched) === array(1, 3, 2), 'internal queries can opt out of ordering');

$unrelated = yoga_sort_terms_by_manual_order($terms, array('category'), array());
yoga_term_order_test_assert(array_map(static fn($term) => $term->term_id, $unrelated) === array(1, 3, 2), 'unrelated taxonomies are not changed');

yoga_term_order_test_assert(
	yoga_merge_visible_term_order(array(1, 2, 3, 4, 5), array(4, 2)) === array(1, 4, 3, 2, 5),
	'reordering one admin page preserves terms outside that page'
);
yoga_term_order_test_assert(
	yoga_merge_visible_term_order(array(1, 2, 3), array(2, 99)) === array(),
	'unknown term IDs are rejected'
);

fwrite(STDOUT, "Term order tests passed.\n");
