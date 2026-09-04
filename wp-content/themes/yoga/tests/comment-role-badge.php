<?php

define('ABSPATH', __DIR__ . '/');

class WP_User {
	public $roles;

	public function __construct(array $roles) {
		$this->roles = $roles;
	}
}

class WP_Comment {}
class WP_Post {}

final class Yoga_Test_Members_Role {
	private $label;

	public function __construct(string $label) {
		$this->label = $label;
	}

	public function get(string $key) {
		return $key === 'label' ? $this->label : null;
	}
}

$GLOBALS['yoga_test_users'] = array(
	10 => new WP_User(array('subscriber')),
	20 => new WP_User(array('moderator')),
	30 => new WP_User(array('moderator', 'administrator')),
	40 => new WP_User(array('subscriber', 'moderator')),
	50 => new WP_User(array('custom_member')),
);
$GLOBALS['yoga_test_roles'] = (object) array(
	'roles' => array(
		'subscriber' => array('name' => 'Subscriber', 'capabilities' => array('read' => true)),
		'moderator' => array('name' => 'Moderator', 'capabilities' => array('read' => true, 'moderate_comments' => true)),
		'administrator' => array('name' => 'Administrator', 'capabilities' => array('moderate_comments' => true)),
		'custom_member' => array('name' => '<b>Участник</b>', 'capabilities' => array('read' => true)),
	),
);
$GLOBALS['yoga_test_members_labels'] = array(
	'moderator' => 'Модератор',
	'administrator' => 'Administrator',
	'custom_member' => '<b>Участник</b>',
);

function get_userdata($user_id) {
	return $GLOBALS['yoga_test_users'][$user_id] ?? false;
}

function sanitize_key($key) {
	return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $key));
}

function wp_roles() {
	return $GLOBALS['yoga_test_roles'];
}

function members_get_role($role_slug) {
	return isset($GLOBALS['yoga_test_members_labels'][$role_slug])
		? new Yoga_Test_Members_Role($GLOBALS['yoga_test_members_labels'][$role_slug])
		: null;
}

function translate_user_role($label) {
	$translations = array(
		'Administrator' => 'Администратор',
		'Subscriber' => 'Подписчик',
	);
	return $translations[$label] ?? $label;
}

function wp_strip_all_tags($value) {
	return strip_tags((string) $value);
}

function add_action() {
	return true;
}

function add_filter() {
	return true;
}

function get_template_directory_uri() {
	return 'https://example.com/wp-content/themes/yoga';
}

require dirname(__DIR__) . '/inc/comments.php';

function yoga_test_assert($condition, string $message): void {
	if (!$condition) {
		fwrite(STDERR, "FAIL: {$message}\n");
		exit(1);
	}
}

yoga_test_assert(yoga_get_user_comment_role_badge(0) === '', 'guests never receive a verified role badge');
yoga_test_assert(yoga_get_user_comment_role_badge(10) === 'Подписчик', 'registered users receive their Members role label');
yoga_test_assert(yoga_get_user_comment_role_badge(20) === 'Модератор', 'Members moderator label is displayed');
yoga_test_assert(yoga_get_user_comment_role_badge(30) === 'Администратор', 'administrator role takes priority and is localized');
yoga_test_assert(yoga_get_user_comment_role_badge(40) === 'Модератор', 'staff role takes priority over a subscriber role');
yoga_test_assert(yoga_get_user_comment_role_badge(50) === 'Участник', 'Members role labels are stripped of markup');
yoga_test_assert(yoga_get_user_comment_role_badge(999) === '', 'unknown users never receive a badge');

fwrite(STDOUT, "Comment role badge tests passed.\n");
