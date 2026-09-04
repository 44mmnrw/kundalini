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
$GLOBALS['yoga_test_options'] = array(
	'yoga_comment_role_badge_colors' => array(
		'moderator' => '#f8bdf6',
		'subscriber' => '#ffffff',
		'custom_member' => 'not-a-color',
	),
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

function get_option($key, $default = false) {
	return $GLOBALS['yoga_test_options'][$key] ?? $default;
}

function update_option($key, $value, $autoload = null) {
	$GLOBALS['yoga_test_options'][$key] = $value;
	return true;
}

function sanitize_hex_color($color) {
	$color = trim((string) $color);
	if ($color === '') {
		return '';
	}
	return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color) ? strtolower($color) : null;
}

function current_user_can() {
	return true;
}

function wp_unslash($value) {
	return $value;
}

function sanitize_text_field($value) {
	return trim(strip_tags((string) $value));
}

function wp_verify_nonce($nonce, $action) {
	return $nonce === $action . '-nonce';
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

function get_template_directory() {
	return dirname(__DIR__);
}

function wp_enqueue_style($handle) {
	$GLOBALS['yoga_test_enqueued_styles'][] = $handle;
}

function wp_enqueue_script($handle) {
	$GLOBALS['yoga_test_enqueued_scripts'][] = $handle;
}

require dirname(__DIR__) . '/inc/comments.php';
require dirname(__DIR__) . '/inc/admin/comment-role-badges.php';

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

$moderator_badge = yoga_get_user_comment_role_badge_data(20);
yoga_test_assert($moderator_badge['background_color'] === '#f8bdf6', 'configured role color is applied');
yoga_test_assert($moderator_badge['text_color'] === '#1f1f1f', 'dark text is selected for a light badge');
$administrator_badge = yoga_get_user_comment_role_badge_data(30);
yoga_test_assert($administrator_badge['background_color'] === '#9153e1', 'missing role color uses the violet default');
yoga_test_assert($administrator_badge['text_color'] === '#ffffff', 'white text is selected for a dark badge');
$custom_badge = yoga_get_user_comment_role_badge_data(50);
yoga_test_assert($custom_badge['background_color'] === '#9153e1', 'invalid saved colors are ignored');

$_POST = array(
	'members_edit_role_nonce' => 'edit_role-nonce',
	'yoga_comment_role_badge_color' => '#123456',
);
yoga_save_comment_role_badge_color('moderator');
yoga_test_assert(yoga_get_comment_role_badge_color('moderator') === '#123456', 'valid role colors are saved');

$_POST['yoga_comment_role_badge_color'] = 'red;background:url(javascript:alert(1))';
yoga_save_comment_role_badge_color('moderator');
yoga_test_assert(yoga_get_comment_role_badge_color('moderator') === '#123456', 'invalid CSS cannot replace a saved role color');

foreach (array('members', 'roles') as $members_page) {
	$GLOBALS['yoga_test_enqueued_styles'] = array();
	$GLOBALS['yoga_test_enqueued_scripts'] = array();
	$_GET['page'] = $members_page;
	yoga_enqueue_comment_role_badge_color_assets();
	yoga_test_assert(in_array('wp-color-picker', $GLOBALS['yoga_test_enqueued_styles'], true), 'color picker is loaded on the Members role screen');
	yoga_test_assert(in_array('yoga-comment-role-badge-color', $GLOBALS['yoga_test_enqueued_scripts'], true), 'badge color script is loaded on the Members role screen');
}

$GLOBALS['yoga_test_enqueued_styles'] = array();
$GLOBALS['yoga_test_enqueued_scripts'] = array();
$_GET['page'] = 'users';
yoga_enqueue_comment_role_badge_color_assets();
yoga_test_assert($GLOBALS['yoga_test_enqueued_styles'] === array(), 'color picker is not loaded on unrelated admin screens');
yoga_test_assert($GLOBALS['yoga_test_enqueued_scripts'] === array(), 'badge color script is not loaded on unrelated admin screens');

fwrite(STDOUT, "Comment role badge tests passed.\n");
