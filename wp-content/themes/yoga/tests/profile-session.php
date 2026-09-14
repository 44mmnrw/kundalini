<?php

define('DAY_IN_SECONDS', 86400);

class WP_Session_Tokens {
	public static $valid_tokens = array();
	public static $user_id;

	public static function get_instance($user_id) {
		self::$user_id = $user_id;
		return new self();
	}

	public function verify($token) {
		return in_array($token, self::$valid_tokens, true);
	}
}

function apply_filters($hook, $default, $user_id, $remember) {
	if ($hook !== 'auth_cookie_expiration' || $user_id !== 42 || $remember !== false) {
		throw new RuntimeException('Unexpected auth cookie filter call.');
	}
	return $default;
}

function wp_set_auth_cookie($user_id, $remember, $secure, $token) {
	$GLOBALS['auth_cookie_call'] = compact('user_id', 'remember', 'secure', 'token');
}

function assert_auth_cookie($expected, $message) {
	if ($GLOBALS['auth_cookie_call'] !== $expected) {
		fwrite(STDERR, $message . "\n");
		exit(1);
	}
}

require dirname(__DIR__) . '/inc/auth/profile-session.php';

WP_Session_Tokens::$valid_tokens = array('current-session');
yoga_refresh_profile_auth_session(42, array(
	'token' => 'current-session',
	'expiration' => time() + DAY_IN_SECONDS,
));
assert_auth_cookie(array('user_id' => 42, 'remember' => false, 'secure' => '', 'token' => 'current-session'), 'Browser-session login must keep its token.');

yoga_refresh_profile_auth_session(42, array(
	'token' => 'current-session',
	'expiration' => time() + 14 * DAY_IN_SECONDS,
));
assert_auth_cookie(array('user_id' => 42, 'remember' => true, 'secure' => '', 'token' => 'current-session'), 'Remember-me login must stay persistent.');

WP_Session_Tokens::$valid_tokens = array();
yoga_refresh_profile_auth_session(42, array(
	'token' => 'revoked-session',
	'expiration' => time() + DAY_IN_SECONDS,
));
assert_auth_cookie(array('user_id' => 42, 'remember' => false, 'secure' => '', 'token' => ''), 'Revoked token must be replaced by WordPress.');

echo "Profile session tests passed.\n";
