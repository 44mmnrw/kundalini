<?php
/** Run with PHP CLI. Uses WordPress hooks, cookie validation and session tokens. */
define('ABSPATH', dirname(__DIR__, 4) . '/');
define('AUTH_COOKIE', 'test_auth');
define('SECURE_AUTH_COOKIE', 'test_secure_auth');
define('LOGGED_IN_COOKIE', 'test_logged_in');
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
$_SERVER['REQUEST_METHOD'] = 'GET';

require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/class-wp-error.php';
require ABSPATH . 'wp-includes/class-wp-session-tokens.php';

class Memory_Session_Tokens extends WP_Session_Tokens {
    public static $sessions = array();
    protected function get_sessions() { return self::$sessions[$this->user_id] ?? array(); }
    protected function get_session($verifier) {
        $session = $this->get_sessions()[$verifier] ?? null;
        return $session && $session['expiration'] >= time() ? $session : null;
    }
    protected function update_session($verifier, $session = null) {
        if ($session === null) { unset(self::$sessions[$this->user_id][$verifier]); }
        else { self::$sessions[$this->user_id][$verifier] = $session; }
    }
    protected function destroy_other_sessions($verifier) {
        self::$sessions[$this->user_id] = array($verifier => $this->get_session($verifier));
    }
    protected function destroy_all_sessions() { self::$sessions[$this->user_id] = array(); }
}
add_filter('session_token_manager', static function () { return Memory_Session_Tokens::class; });

class WP_User {
    public $ID, $user_login, $user_pass, $roles, $allcaps;
    public function __construct($id, $role) {
        $this->ID = $id;
        $this->user_login = 'user' . $id;
        $this->user_pass = password_hash('correct-password', PASSWORD_BCRYPT);
        $this->roles = array($role);
        $this->allcaps = array('read' => true, 'level_0' => true);
        if ($role === 'administrator') {
            $this->allcaps += array('manage_options' => true, 'edit_posts' => true, 'edit_users' => true,
                'wf2fa_activate_2fa_self' => true, 'wf2fa_manage_settings' => true, 'wfls_manage_passkey_self' => true);
        }
    }
    public function exists() { return true; }
    public function has_cap($cap, ...$args) {
        // Core WP_User::has_cap order, including the multisite super-admin shortcut.
        $mapped = apply_filters('map_meta_cap', array($cap), $cap, $this->ID, $args);
        if (is_multisite() && is_super_admin($this->ID)) {
            return !in_array('do_not_allow', $mapped, true);
        }
        $caps = apply_filters('user_has_cap', $this->allcaps, $mapped, array_merge(array($cap, $this->ID), $args), $this);
        $caps['exist'] = true;
        unset($caps['do_not_allow']);
        foreach ($mapped as $required) { if (empty($caps[$required])) { return false; } }
        return true;
    }
}

function is_ssl() { return true; }
function is_multisite() { return !empty($GLOBALS['multisite']); }
function is_super_admin($id = null) { return ($id ?? get_current_user_id()) === 1; }
function is_admin() { return !empty($GLOBALS['admin_request']); }
function wp_doing_ajax() { return !empty($GLOBALS['ajax_request']); }
function get_current_user_id() { return $GLOBALS['current_user_id'] ?? 0; }
function current_user_can($cap, ...$args) {
    $user = get_userdata(get_current_user_id());
    return $user ? $user->has_cap($cap, ...$args) : false;
}
function wp_hash($data, $scheme = 'auth') { return hash_hmac('sha256', $data, 'test-secret-' . $scheme); }
function wp_generate_password($length = 12, $special = true, $extra = false) { return substr(bin2hex(random_bytes($length)), 0, $length); }
function wp_check_password($password, $hash, $id = '') { return password_verify($password, $hash); }
function get_user_by($field, $value) {
    foreach ($GLOBALS['accounts'] as $user) {
        if (($field === 'login' && $user->user_login === $value) || ($field === 'id' && $user->ID === $value)) { return $user; }
    }
    return false;
}
function get_userdata($id) { return get_user_by('id', $id); }
function is_wp_error($result) { return $result instanceof WP_Error; }
function wp_set_auth_cookie($id, $remember = false, $secure = '', $token = '') {
    $expiration = time() + ($remember ? 14 : 2) * DAY_IN_SECONDS;
    $token = $token ?: WP_Session_Tokens::get_instance($id)->create($expiration);
    $GLOBALS['issued_cookies'] = array(
        SECURE_AUTH_COOKIE => wp_generate_auth_cookie($id, $expiration, 'secure_auth', $token),
        LOGGED_IN_COOKIE => wp_generate_auth_cookie($id, $expiration, 'logged_in', $token),
    );
}
function wp_signon($credentials, $secure = '') {
    $user = get_user_by('login', $credentials['user_login']);
    $result = $user && wp_check_password($credentials['user_password'], $user->user_pass, $user->ID)
        ? $user : new WP_Error('incorrect_password', 'Wrong password');
    $result = apply_filters('authenticate', $result, $credentials['user_login'], $credentials['user_password']);
    if ($result instanceof WP_User) { wp_set_auth_cookie($result->ID, $credentials['remember']); }
    return $result;
}
function wc_get_order($id) {
    if (!isset($GLOBALS['order_owners'][$id])) { return false; }
    return new class($GLOBALS['order_owners'][$id]) {
        private $owner;
        public function __construct($owner) { $this->owner = $owner; }
        public function get_user_id() { return $this->owner; }
    };
}
function admin_url() { return '/wp-admin/'; }
function wp_login_url($redirect = '', $reauth = false) { return '/wp-login.php?redirect_to=' . $redirect . '&reauth=' . (int) $reauth; }
class Test_Redirect extends RuntimeException {}
function wp_safe_redirect($url) { throw new Test_Redirect($url); }

require ABSPATH . 'wp-includes/pluggable.php';
require ABSPATH . 'wp-content/plugins/woocommerce/includes/wc-user-functions.php';
require ABSPATH . 'wp-content/mu-plugins/yoga-frontend-session.php';
require dirname(__DIR__) . '/inc/auth/profile-session.php';

// Simulate the installed 2FA provider's authentication outcomes, not its internals.
add_filter('authenticate', static function ($result) {
    if (!empty($GLOBALS['provider_throw'])) { throw new RuntimeException('Provider exception'); }
    if (is_wp_error($result)) { return $result; }
    if (!empty($GLOBALS['provider_error'])) {
        $error = new WP_Error($GLOBALS['provider_error'], 'Provider rejected login');
        if (!empty($GLOBALS['provider_extra_error'])) { $error->add($GLOBALS['provider_extra_error'], 'Additional rejection'); }
        return $error;
    }
    if ($result->ID === 1 && empty($GLOBALS['valid_second_factor'])) { return new WP_Error('wfls_twofactor_required', '2FA required'); }
    return $result;
}, 99);

$GLOBALS['accounts'] = array(1 => new WP_User(1, 'administrator'), 2 => new WP_User(2, 'customer'));
$GLOBALS['order_owners'] = array(10 => 1, 20 => 2);
$checks = 0;
function check($condition, $message) {
    global $checks;
    ++$checks;
    if (!$condition) { throw new RuntimeException($message); }
}
function new_request($cookies) {
    $_COOKIE = $cookies;
    foreach (array('yoga_authenticated_session_tokens', 'yoga_authenticated_session_frontend_only', 'yoga_frontend_login_user_id', 'yoga_full_authentication_user_id') as $key) { unset($GLOBALS[$key]); }
    $GLOBALS['current_user_id'] = wp_validate_auth_cookie();
    if (!$GLOBALS['current_user_id'] && isset($_COOKIE[LOGGED_IN_COOKIE])) {
        $GLOBALS['current_user_id'] = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in');
    }
}

$admin = $GLOBALS['accounts'][1];
$customer = $GLOBALS['accounts'][2];
check(yoga_frontend_signon($admin, 'user1', 'correct-password') === $admin, 'Admin frontend login must not request 2FA.');
check(yoga_frontend_session_is_limited(1), 'Successful frontend login must restrict the current request too.');
$frontend_cookies = $GLOBALS['issued_cookies'];
new_request($frontend_cookies);
check(yoga_frontend_session_is_limited(1), 'Frontend cookie must carry the server-side restriction.');
check($admin->roles === array('administrator'), 'Account role must remain unchanged.');
check($admin->has_cap('read'), 'Customer cabinet must remain accessible.');
foreach (array('manage_options', 'edit_posts', 'edit_user', 'create_app_password', 'manage_woocommerce',
    'wf2fa_activate_2fa_self', 'wf2fa_manage_settings', 'wfls_manage_passkey_self') as $cap) {
    check(!$admin->has_cap($cap, 1), 'Frontend session must deny ' . $cap);
}
check(!yoga_frontend_session_can_change_credentials($admin), 'Client-only admin must not change administrator credentials.');
check(!apply_filters('show_admin_bar', true), 'Frontend session must hide admin toolbar.');
foreach (array('view_order', 'pay_for_order', 'order_again', 'cancel_order') as $cap) {
    check($admin->has_cap($cap, 10), 'Must allow own order: ' . $cap);
    check(!$admin->has_cap($cap, 20), 'Must deny another customer order: ' . $cap);
}
$GLOBALS['multisite'] = true;
check(!$admin->has_cap('manage_options'), 'Multisite super admin shortcut must not elevate frontend session.');
check(!$admin->has_cap('view_order', 20), 'Super admin must not see another customer order.');
$GLOBALS['multisite'] = false;

new_request(array(SECURE_AUTH_COOKIE => $frontend_cookies[SECURE_AUTH_COOKIE]));
check(yoga_frontend_session_is_limited(1) && !$admin->has_cap('manage_options'), 'Deleting logged-in cookie must not elevate an admin auth cookie.');
$GLOBALS['admin_request'] = true;
try { yoga_require_full_authentication_for_admin(); check(false, 'Admin page must redirect.'); }
catch (Test_Redirect $e) { check(str_contains($e->getMessage(), '/wp-login.php') && str_contains($e->getMessage(), 'reauth=1'), 'Admin must require separate authentication.'); }
$GLOBALS['ajax_request'] = true;
yoga_require_full_authentication_for_admin();
check(!$admin->has_cap('manage_options'), 'AJAX route must retain restricted capabilities.');
$GLOBALS['ajax_request'] = $GLOBALS['admin_request'] = false;

check(is_wp_error(yoga_frontend_signon($admin, 'user1', 'wrong-password')), 'Wrong password must not bypass 2FA.');
foreach (array('wfls_twofactor_failed', 'captcha_failed', 'account_blocked') as $code) {
    $GLOBALS['provider_error'] = $code;
    check(yoga_frontend_signon($admin, 'user1', 'correct-password')->get_error_code() === $code, 'Must retain provider error: ' . $code);
}
$GLOBALS['provider_error'] = 'wfls_twofactor_required';
$GLOBALS['provider_extra_error'] = 'account_blocked';
check(is_wp_error(yoga_frontend_signon($admin, 'user1', 'correct-password')), '2FA request combined with another error must still reject login.');
$GLOBALS['provider_extra_error'] = '';
check(is_wp_error(yoga_frontend_signon($customer, 'user2', 'correct-password')), 'Must not waive a customer-configured second factor.');
$GLOBALS['provider_error'] = '';
$filter_count = count($GLOBALS['wp_filter']['authenticate']->callbacks[PHP_INT_MAX]);
$GLOBALS['provider_throw'] = true;
try { yoga_frontend_signon($admin, 'user1', 'correct-password'); check(false, 'Expected provider exception.'); }
catch (RuntimeException $e) { check($e->getMessage() === 'Provider exception', 'Must propagate unexpected authentication exception.'); }
$GLOBALS['provider_throw'] = false;
check(count($GLOBALS['wp_filter']['authenticate']->callbacks[PHP_INT_MAX]) === $filter_count && empty($GLOBALS['yoga_frontend_login_user_id']), 'Exception must clean up the temporary authentication bypass.');
check(wp_signon(array('user_login' => 'user1', 'user_password' => 'correct-password', 'remember' => true))->get_error_code() === 'wfls_twofactor_required', 'Admin login must still require 2FA after frontend login.');
$GLOBALS['valid_second_factor'] = true;
check(wp_signon(array('user_login' => 'user1', 'user_password' => 'correct-password', 'remember' => true)) === $admin, 'Separate 2FA authentication must succeed.');
$full_cookies = $GLOBALS['issued_cookies'];
new_request($full_cookies);
check(!yoga_frontend_session_is_limited(1) && $admin->has_cap('manage_options'), '2FA login must grant full admin access.');
check(yoga_frontend_session_can_change_credentials($admin), 'Fully authenticated admin can edit credentials.');
$forged = explode('|', $full_cookies[SECURE_AUTH_COOKIE]);
$forged[3] = str_repeat('0', 64);
new_request(array(SECURE_AUTH_COOKIE => implode('|', $forged), LOGGED_IN_COOKIE => $frontend_cookies[LOGGED_IN_COOKIE]));
check(yoga_frontend_session_is_limited(1) && !$admin->has_cap('manage_options'), 'Forged full-session cookie must not lift a valid client restriction.');

$GLOBALS['valid_second_factor'] = false;
new_request(array());
check(yoga_frontend_signon($customer, 'user2', 'correct-password') === $customer, 'Ordinary customer login must work.');
$customer_cookies = $GLOBALS['issued_cookies'];
new_request($customer_cookies);
check(yoga_frontend_session_can_change_credentials($customer), 'Ordinary customers may change their own credentials.');
check($customer->has_cap('view_order', 20) && !$customer->has_cap('view_order', 10), 'Customer order ownership must be preserved.');
$old_cookie = wp_parse_auth_cookie($customer_cookies[LOGGED_IN_COOKIE], 'logged_in');
WP_Session_Tokens::get_instance(2)->destroy_all();
$customer->user_pass = password_hash('changed-password', PASSWORD_BCRYPT);
yoga_refresh_profile_auth_session(2, $old_cookie);
new_request($GLOBALS['issued_cookies']);
check(yoga_frontend_session_is_limited(2), 'Password change and revoked token must preserve restriction on new session.');
$customer->roles = array('administrator');
$customer->allcaps['manage_options'] = true;
check(!$customer->has_cap('manage_options'), 'Promoting account role must not elevate existing frontend session.');

// VK and SMS create cookies directly, rather than using the password provider.
new_request(array());
yoga_set_frontend_auth_cookie(1, true);
new_request($GLOBALS['issued_cookies']);
check(yoga_frontend_session_is_limited(1) && !$admin->has_cap('manage_options'), 'Other frontend authentication methods must also create customer-only sessions.');
echo "Frontend session tests passed ($checks checks).\n";
