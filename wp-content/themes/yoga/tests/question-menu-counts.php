<?php
/** In-memory checks; admin migrations, database writes and mail are never run. */
define('ABSPATH', __DIR__ . '/');
function add_action(...$args) {}
function add_filter(...$args) {}
function __($s, ...$args) { return $s; }
function _n($one, $many, $n, ...$args) { return $n === 1 ? $one : $many; }
function esc_html($s) { return htmlspecialchars($s, ENT_QUOTES); }
function esc_attr($s) { return htmlspecialchars($s, ENT_QUOTES); }
function sanitize_key($s) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($s)); }
function wp_strip_all_tags($s) { return strip_tags($s); }
function get_post_meta($id, $key, ...$args) { return $GLOBALS['metadata'][$id][$key] ?? ''; }
function update_meta_cache($type, $ids) { $GLOBALS['primed'][] = array($type, $ids); }
function get_posts($args) {
	$GLOBALS['queries']++;
	return array_keys(array_filter($GLOBALS['records'], static function ($post) use ($args) {
		return $post['type'] === $args['post_type'] && in_array($post['status'], $args['post_status'], true);
	}));
}
require dirname(__DIR__) . '/inc/questions.php';
require dirname(__DIR__) . '/inc/admin/questions.php';
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
$queries = 0; $primed = array(); $records = array(); $metadata = array();
foreach (array(1 => 'lk', 2 => 'lk', 3 => 'contacts', 4 => 'faq', 5 => 'practice', 6 => 'practice', 7 => 'lk', 8 => 'faq', 9 => 'faq', 10 => 'practice', 11 => 'unknown') as $id => $source) {
	$records[$id] = array('type' => $id === 10 ? 'practice' : 'question', 'status' => array(2 => 'pending', 3 => 'draft', 4 => 'private', 8 => 'trash', 9 => 'auto-draft')[$id] ?? 'publish');
	$metadata[$id]['question_source'] = $source;
}
$metadata[6]['_question_answers'] = array(array('content' => 'Ответ'));
$metadata[7]['_answer'] = '<p>Старый ответ</p>';
$expected = array('total' => 6, 'lk' => 2, 'contacts' => 1, 'faq' => 1, 'practice' => 1);
check(yoga_get_unanswered_question_counts() === $expected, 'Count sources, exclude answers including legacy, trash, auto-drafts and other post types');
check(yoga_get_unanswered_questions_count() === 6, 'Keep parent counter API');
function build_menu(): void {
	global $menu, $submenu;
	$parent = 'edit.php?post_type=question';
	$menu = array(array('Вопросы', 'edit_posts', $parent), array('Блог', 'edit_posts', 'edit.php'));
	$submenu = array($parent => array(array('Все вопросы', 'edit_posts', $parent), array('Настройки уведомлений', 'manage_options', 'yoga-question-notifications')));
	foreach (yoga_question_source_labels() as $source => $label) { $submenu[$parent][] = array($label, 'edit_posts', $parent . '&question_source=' . $source); }
}
build_menu(); $queries = 0; $primed = array();
yoga_add_unanswered_questions_menu_count();
check($queries === 1 && count($primed) === 1 && $primed[0][0] === 'post', 'One shared query and batch metadata cache for all badges');
check(str_contains($menu[0][0], 'count-6') && $menu[1][0] === 'Блог', 'Parent total and unrelated menu');
$items = $submenu['edit.php?post_type=question'];
check($items[0][0] === 'Все вопросы' && $items[1][0] === 'Настройки уведомлений', 'Only source submenus get badges');
foreach (array_slice($items, 2) as $item) {
	parse_str(parse_url($item[2], PHP_URL_QUERY), $params);
	check(str_contains($item[0], 'count-' . $expected[$params['question_source']]), 'Badge for ' . $params['question_source']);
}
$metadata[1]['_question_answers'] = $metadata[2]['_question_answers'] = array(array('content' => 'Ответ'));
build_menu(); yoga_add_unanswered_questions_menu_count();
check(str_contains($menu[0][0], 'count-4'), 'Answer updates parent count');
check($submenu['edit.php?post_type=question'][2][0] === 'Вопросы в ЛК', 'Hide zero source badge after answering');
foreach ($records as $id => $record) { $metadata[$id]['_question_answers'] = array(array('content' => 'Ответ')); }
build_menu(); yoga_add_unanswered_questions_menu_count();
check($menu[0][0] === 'Вопросы' && yoga_question_unanswered_menu_badge(0) === '', 'Hide badges when no unanswered questions remain');
$records = array();
check(yoga_get_unanswered_question_counts() === array_fill_keys(array_keys($expected), 0), 'Empty database');
echo "PASS: source counters, unanswered status, legacy answers, excluded records, parent total, zero badges, answer updates and shared query.\n";
