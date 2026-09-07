<?php
/** Local WordPress integration test. All email is intercepted; temporary users are removed. */
if (PHP_SAPI !== 'cli') {
	exit(1);
}

define('DOING_AJAX', true);
define('DISABLE_WP_CRON', true);
$captured = array();
// Install interception before WordPress loads, including any plugin startup mail.
$GLOBALS['wp_filter']['pre_wp_mail'][1][] = array(
	'function' => static function ($return, $args) use (&$captured) {
		$plain = '';
		if (class_exists('Yoga_Mail_Plugin')) {
			$transport = new class($args['headers']) {
				public $AltBody = '';
				private $headers;
				public function __construct($headers) {
					$this->headers = is_array($headers) ? $headers : explode("\n", $headers);
				}
				public function getCustomHeaders() {
					return array_map(static function ($header) {
						return array_map('trim', explode(':', $header, 2));
					}, $this->headers);
				}
				public function isHTML($enabled) {}
			};
			Yoga_Mail_Plugin::instance()->mailer()->set_alt_body($transport);
			$plain = $transport->AltBody;
		}
		$captured[] = array_merge($args, array('plain' => $plain));
		return true;
	},
	'accepted_args' => 2,
);
require dirname(__DIR__, 4) . '/wp-load.php';
if (parse_url(home_url(), PHP_URL_HOST) !== 'kundalini.test') {
	fwrite(STDERR, "This test only runs on kundalini.test.\n");
	exit(1);
}
require_once ABSPATH . 'wp-admin/includes/user.php';

function password_mail_assert($condition, $message) {
	if (!$condition) {
		throw new RuntimeException($message);
	}
}
// Error deliberately bypasses the account handler's catch(Exception).
class Password_Mail_Ajax_Exit extends Error {}
add_filter('wp_die_ajax_handler', static function () {
	return static function () { throw new Password_Mail_Ajax_Exit(); };
});
$wordpress_enabled = true;
add_filter('pre_option_yoga_mail_settings', static function () use (&$wordpress_enabled) {
	return array('wordpress_enabled' => $wordpress_enabled, 'custom_enabled' => false);
});

$fixture_ids = array();
$results = array();
try {
	foreach (array('reset', 'account', 'standard', 'disabled') as $scenario) {
		$id = wp_insert_user(array(
			'user_login' => 'password-mail-' . wp_generate_uuid4(),
			'user_email' => 'password-mail-' . wp_generate_uuid4() . '@example.invalid',
			'user_pass' => 'Fixture-old-pass-7!a',
			'role' => 'subscriber',
		));
		password_mail_assert(!is_wp_error($id), 'Fixture creation failed');
		$fixture_ids[] = $id;
		$user = get_userdata($id);
		$captured = array();
		if ($scenario === 'reset') {
			reset_password($user, 'Fixture-new-pass-8!b');
			// A duplicate completed-reset hook must not send a second customer email.
			do_action('after_password_reset', $user, 'Fixture-new-pass-8!b');
		} elseif ($scenario === 'account') {
			wp_set_current_user($id);
			$_POST = array(
				'nonce' => wp_create_nonce('yoga_ajax_nonce'),
				'current_password' => 'wrong-password',
				'new_password' => 'Fixture-new-pass-8!b',
				'repeat_password' => 'Fixture-new-pass-8!b',
			);
			ob_start();
			try { yoga_update_profile_ajax(); } catch (Password_Mail_Ajax_Exit $e) {}
			ob_end_clean();
			password_mail_assert(count($captured) === 0, 'Rejected password must not send mail');
			password_mail_assert(wp_check_password('Fixture-old-pass-7!a', get_userdata($id)->user_pass), 'Rejected password must not change credentials');
			$_POST['current_password'] = 'Fixture-old-pass-7!a';
			ob_start();
			try { yoga_update_profile_ajax(); } catch (Password_Mail_Ajax_Exit $e) {}
			$response = json_decode(ob_get_clean(), true);
			password_mail_assert(!empty($response['success']), 'Account update failed');
		} elseif ($scenario === 'standard') {
			wp_set_current_user(0);
			$result = wp_update_user(array('ID' => $id, 'user_pass' => 'Fixture-new-pass-8!b'));
			password_mail_assert(!is_wp_error($result), 'Standard update failed');
		} else {
			$wordpress_enabled = false;
			reset_password($user, 'Fixture-new-pass-8!b');
		}
		$customer_mails = array_values(array_filter($captured, static function ($mail) use ($user) {
			return $mail['to'] === $user->user_email;
		}));
		$expected = $scenario === 'disabled' ? 0 : 1;
		password_mail_assert(count($customer_mails) === $expected, $scenario . ': wrong customer email count');
		password_mail_assert(wp_check_password('Fixture-new-pass-8!b', get_userdata($id)->user_pass), $scenario . ': password was not changed');
		if ($expected) {
			$mail = $customer_mails[0];
			password_mail_assert($mail['subject'] === 'Пароль изменен', $scenario . ': wrong subject');
			password_mail_assert(strpos($mail['message'], '<!-- yoga-mail:wp-password-changed -->') !== false, $scenario . ': missing HTML template');
			password_mail_assert(strpos($mail['plain'], 'Вы успешно сменили пароль') !== false, $scenario . ': missing plain alternative');
			password_mail_assert(strpos($mail['message'], '{{') === false, $scenario . ': unresolved merge tag');
			password_mail_assert(strpos($mail['message'] . $mail['plain'], 'Fixture-new-pass') === false, $scenario . ': password leaked into mail');
		}
		$results[] = $scenario . ': PASS';
	}
} finally {
	wp_set_current_user(0);
	$_POST = array();
	foreach ($fixture_ids as $id) {
		wp_delete_user($id);
	}
}
echo implode(PHP_EOL, $results), PHP_EOL, "Temporary test users removed; no email transmitted.\n";
