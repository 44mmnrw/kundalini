<?php

define('ABSPATH', __DIR__ . '/');

function add_action() {}
function add_filter() {}
function get_post_meta($post_id, $key, $single = false) {
	return $GLOBALS['question_answer_test_meta'][$post_id][$key] ?? '';
}
function get_post_field($field, $post_id) {
	return $field === 'post_author' ? ($GLOBALS['question_answer_test_authors'][$post_id] ?? 0) : '';
}
function sanitize_key($value) {
	return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}
function sanitize_email($email) {
	return filter_var((string) $email, FILTER_SANITIZE_EMAIL);
}
function is_email($email) {
	return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}
function yoga_get_question_notification_user_id(int $post_id): int {
	return $GLOBALS['question_answer_test_users'][$post_id] ?? 0;
}
function yoga_notification_preference(int $user_id, string $key, bool $default = true): bool {
	return $GLOBALS['question_answer_test_preferences'][$user_id] ?? $default;
}

require dirname(__DIR__) . '/inc/admin/questions.php';

$GLOBALS['question_answer_test_meta'] = array(
	1 => array('contact_email' => 'guest@example.com', 'question_source' => 'faq'),
	2 => array('contact_email' => 'guest@example.com', 'question_source' => 'contacts'),
	3 => array('question_source' => 'lk'),
	4 => array('question_source' => 'lk'),
);
$GLOBALS['question_answer_test_users'] = array(1 => 0, 2 => 42, 3 => 42, 4 => 43);
$GLOBALS['question_answer_test_preferences'] = array(42 => false, 43 => true);
$GLOBALS['question_answer_test_authors'] = array(1 => 0, 2 => 42, 3 => 42, 4 => 43);

foreach (array(
	1 => true,  // Guest form, no account.
	2 => true,  // Guest form remains replyable even if the question has a user author.
	3 => false, // Personal-account question respects disabled email notifications.
	4 => true,  // Personal-account question respects enabled email notifications.
) as $post_id => $expected) {
	if (yoga_question_answer_email_enabled($post_id) !== $expected) {
		fwrite(STDERR, "Unexpected email setting for question {$post_id}.\n");
		exit(1);
	}
}

foreach (array(1 => false, 2 => false, 3 => true, 4 => true) as $post_id => $expected) {
	if (yoga_question_has_account_conversation($post_id) !== $expected) {
		fwrite(STDERR, "Unexpected conversation link setting for question {$post_id}.\n");
		exit(1);
	}
}

echo "Question answer email tests passed.\n";
