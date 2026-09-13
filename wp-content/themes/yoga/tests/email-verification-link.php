<?php

define('ABSPATH', __DIR__ . '/');
define('DAY_IN_SECONDS', 86400);

class WP_Error {
	private $code;
	private $message;
	private $data;

	public function __construct($code, $message, $data = null) {
		$this->code = $code;
		$this->message = $message;
		$this->data = $data;
	}

	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}

function add_action() {}
function get_user_by($field, $id) { return $field === 'id' && $id === 42 ? $GLOBALS['verification_user'] : false; }
function get_user_meta($id, $key) { return $GLOBALS['verification_meta'][$id][$key] ?? ''; }
function update_user_meta($id, $key, $value) { $GLOBALS['verification_meta'][$id][$key] = $value; }
function delete_user_meta($id, $key) { unset($GLOBALS['verification_meta'][$id][$key]); }
function sanitize_email($value) { return filter_var((string) $value, FILTER_SANITIZE_EMAIL); }
function is_email($value) { return (bool) filter_var($value, FILTER_VALIDATE_EMAIL); }
function wp_salt($scheme) { return 'verification-test-secret'; }
function wp_generate_password($length) { return str_repeat('A', $length); }
function home_url($path = '/') { return 'https://example.test' . $path; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function yoga_mail_send($template, $args) {
	$GLOBALS['verification_mails'][] = array('template' => $template, 'args' => $args);
	return $GLOBALS['verification_send_ok'];
}

function assert_verification($condition, $message) {
	if (!$condition) {
		fwrite(STDERR, $message . "\n");
		exit(1);
	}
}

require dirname(__DIR__) . '/inc/ajax/email-verification.php';

$GLOBALS['verification_user'] = (object) array('ID' => 42, 'user_email' => 'person@example.test', 'display_name' => 'Person');
$GLOBALS['verification_meta'] = array();
$GLOBALS['verification_mails'] = array();
$GLOBALS['verification_send_ok'] = true;

$result = yoga_send_email_verification_link(42);
assert_verification(is_array($result), 'Profile link should be sent.');
assert_verification($GLOBALS['verification_mails'][0]['template'] === 'email-verification-profile', 'Profile must use the link template.');
$mail_data = $GLOBALS['verification_mails'][0]['args']['data'];
assert_verification(!isset($mail_data['code']) && isset($mail_data['action_url']), 'Mail must contain a link, not a code.');
parse_str((string) parse_url($mail_data['action_url'], PHP_URL_QUERY), $link_args);
assert_verification(($link_args['uid'] ?? null) === '42' && ($link_args['yoga_verify_email'] ?? null) === '1', 'Link must identify the account.');
assert_verification(hash_equals(get_user_meta(42, 'yoga_email_link_hash'), yoga_email_verification_link_hash($link_args['token'])), 'Only the token hash should be stored.');
assert_verification((int) get_user_meta(42, 'yoga_email_link_expires') > time() + 23 * 3600, 'Link must remain valid for 24 hours.');

$retry = yoga_send_email_verification_link(42);
assert_verification($retry instanceof WP_Error && $retry->get_error_code() === 'rate_limited', 'Immediate resend must be rate limited.');
assert_verification(count($GLOBALS['verification_mails']) === 1, 'Rate limit must prevent another email.');

delete_user_meta(42, 'yoga_email_link_sent_at');
$previous_hash = get_user_meta(42, 'yoga_email_link_hash');
$GLOBALS['verification_send_ok'] = false;
$failed = yoga_send_email_verification_link(42);
assert_verification($failed instanceof WP_Error && $failed->get_error_code() === 'mail_failed', 'Mail failure must be reported.');
assert_verification(get_user_meta(42, 'yoga_email_link_hash') === $previous_hash && get_user_meta(42, 'yoga_email_link_sent_at') === '', 'Failed resend must preserve the previous link without a cooldown.');

$GLOBALS['verification_send_ok'] = true;
$registration = yoga_send_registration_email_verification_link(42);
assert_verification(is_array($registration) && end($GLOBALS['verification_mails'])['template'] === 'email-verification-registration', 'Registration must retain its dedicated link template.');

echo "Email verification link tests passed.\n";
