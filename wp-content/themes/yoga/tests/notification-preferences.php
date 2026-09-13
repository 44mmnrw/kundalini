<?php

define('ABSPATH', __DIR__ . '/');

final class Yoga_Notification_Test_Response extends Exception {
	public bool $success;
	public $data;
	public int $http_status;

	public function __construct(bool $success, $data, int $http_status) {
		parent::__construct();
		$this->success = $success;
		$this->data = $data;
		$this->http_status = $http_status;
	}
}

function add_action() {}
function apply_filters($hook, $value) { return $value; }
function __($text, $domain = '') { return $text; }
function is_user_logged_in() { return $GLOBALS['notification_test_logged_in']; }
function get_current_user_id() { return 7; }
function check_ajax_referer($action, $field, $stop = true) { return $GLOBALS['notification_test_nonce_valid']; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
function get_user_meta($user_id, $key, $single = false) { return $GLOBALS['notification_test_meta'][$user_id][$key] ?? ''; }
function update_user_meta($user_id, $key, $value) { $GLOBALS['notification_test_meta'][$user_id][$key] = $value; return true; }
function wp_send_json_error($data = null, $status = 200) { throw new Yoga_Notification_Test_Response(false, $data, $status); }
function wp_send_json_success($data = null, $status = 200) { throw new Yoga_Notification_Test_Response(true, $data, $status); }

require dirname(__DIR__) . '/inc/notifications.php';
require dirname(__DIR__) . '/inc/ajax/notifications.php';

function notification_test_assert($condition, string $message): void {
	if (!$condition) {
		fwrite(STDERR, "FAIL: {$message}\n");
		exit(1);
	}
}

$defaults = yoga_get_notification_preference_defaults();
$template = file_get_contents(dirname(__DIR__) . '/templates-page/lk.php');
preg_match_all("/'([a-z_]+_(?:site|email))'/", $template, $matches);
notification_test_assert(!array_diff(array_unique($matches[1]), array_keys($defaults)), 'every visible switch is accepted by the server');

$GLOBALS['notification_test_logged_in'] = true;
$GLOBALS['notification_test_nonce_valid'] = true;
$GLOBALS['notification_test_meta'] = array();

foreach (array('sadhana_started_email' => false, 'new_articles_email' => true) as $key => $enabled) {
	$_POST = array('key' => $key, 'enabled' => $enabled ? '1' : '0');
	try {
		yoga_save_notification_preference();
	} catch (Yoga_Notification_Test_Response $response) {
		notification_test_assert($response->success && $response->data['enabled'] === $enabled, "saving {$key} succeeds");
	}
	notification_test_assert(yoga_notification_preference(7, $key, !$enabled) === $enabled, "{$key} persists");
}

foreach (array('question_answer_site', 'comment_reply_site') as $key) {
	notification_test_assert($defaults[$key] === true, "{$key} is enabled by default");
	$GLOBALS['notification_test_meta'][7]['yoga_notification_preferences'][$key] = false;
	notification_test_assert(yoga_notification_preference(7, $key, false), "old disabled {$key} cannot block notifications");
	notification_test_assert(yoga_get_user_notification_preferences(7)[$key], "old disabled {$key} is shown as enabled");
}

// All four message switches are read-only, including email preferences already saved by users.
$GLOBALS['notification_test_meta'][7]['yoga_notification_preferences']['question_answer_email'] = true;
$GLOBALS['notification_test_meta'][7]['yoga_notification_preferences']['comment_reply_email'] = false;
foreach (array('question_answer_site', 'question_answer_email', 'comment_reply_site', 'comment_reply_email') as $key) {
	notification_test_assert(yoga_is_locked_notification_preference($key), "{$key} is locked");
	$stored = $GLOBALS['notification_test_meta'][7]['yoga_notification_preferences'][$key];
	foreach (array('0', '1') as $enabled) {
		$_POST = array('key' => $key, 'enabled' => $enabled);
		try {
			yoga_save_notification_preference();
			notification_test_assert(false, "changing {$key} must be rejected");
		} catch (Yoga_Notification_Test_Response $response) {
			notification_test_assert(!$response->success && $response->http_status === 400, "changing {$key} is rejected");
		}
		notification_test_assert($GLOBALS['notification_test_meta'][7]['yoga_notification_preferences'][$key] === $stored, "{$key} remains unchanged");
	}
}
notification_test_assert(yoga_notification_preference(7, 'question_answer_email', false), 'locked email preference keeps its saved on state');
notification_test_assert(!yoga_notification_preference(7, 'comment_reply_email', true), 'locked email preference keeps its saved off state');

$GLOBALS['notification_test_nonce_valid'] = false;
$_POST = array('key' => 'sadhana_started_email', 'enabled' => '1');
try {
	yoga_save_notification_preference();
} catch (Yoga_Notification_Test_Response $response) {
	notification_test_assert(!$response->success && $response->http_status === 403, 'expired nonce returns a clear error');
}
notification_test_assert(!yoga_notification_preference(7, 'sadhana_started_email', true), 'expired nonce does not change the preference');

echo "Notification preference tests passed.\n";
