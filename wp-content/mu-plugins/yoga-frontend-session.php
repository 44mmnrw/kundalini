<?php
/**
 * Plugin Name: Yoga Frontend Sessions
 * Description: Restricts customer login sessions independently of the active theme.
 */
/**
 * Customer-only sessions for the frontend login, including administrator accounts.
 * Persistent account roles and normal WordPress authentication remain unchanged.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('auth_cookie_valid', 'yoga_capture_authenticated_session', -99999, 2);
function yoga_capture_authenticated_session(array $cookie, WP_User $user): void {
    $GLOBALS['yoga_authenticated_session_tokens'][$user->ID] = $cookie['token'];
    $session = WP_Session_Tokens::get_instance($user->ID)->get($cookie['token']);
    $GLOBALS['yoga_authenticated_session_frontend_only'][$user->ID] = !empty($session['yoga_frontend_only']);
}

function yoga_frontend_session_is_limited(int $user_id): bool {
    if ($user_id <= 0) {
        return false;
    }
    if (isset($GLOBALS['yoga_authenticated_session_frontend_only'][$user_id])) {
        return $GLOBALS['yoga_authenticated_session_frontend_only'][$user_id];
    }

    if (!isset($GLOBALS['yoga_authenticated_session_tokens'][$user_id])) {
        // Use validated cookies, not just the editable logged-in cookie. An admin
        // request can authenticate using its auth cookie without a logged-in cookie.
        $authenticated_id = wp_validate_auth_cookie();
        if ((int) $authenticated_id !== $user_id && isset($_COOKIE[LOGGED_IN_COOKIE])) {
            wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in');
        }
    }

    $token = $GLOBALS['yoga_authenticated_session_tokens'][$user_id] ?? '';
    if ($token === '') {
        return false;
    }
    $session = WP_Session_Tokens::get_instance($user_id)->get($token);
    return !empty($session['yoga_frontend_only']);
}

function yoga_account_has_administrative_access(WP_User $account): bool {
    return in_array('administrator', (array) $account->roles, true)
        || !empty($account->allcaps['manage_options'])
        || (is_multisite() && is_super_admin($account->ID));
}

function yoga_frontend_session_can_change_credentials(WP_User $account): bool {
    return !yoga_frontend_session_is_limited((int) $account->ID)
        || !yoga_account_has_administrative_access($account);
}

add_filter('authenticate', 'yoga_record_full_authentication', PHP_INT_MAX, 3);
function yoga_record_full_authentication($user, $username, $password) {
    $GLOBALS['yoga_full_authentication_user_id'] = $user instanceof WP_User
        && empty($GLOBALS['yoga_frontend_login_user_id']) ? (int) $user->ID : 0;
    return $user;
}

add_filter('attach_session_information', 'yoga_attach_frontend_session_information', 10, 2);
function yoga_attach_frontend_session_information(array $session, int $user_id): array {
    $frontend_login = (int) ($GLOBALS['yoga_frontend_login_user_id'] ?? 0) === $user_id;
    $full_authentication = (int) ($GLOBALS['yoga_full_authentication_user_id'] ?? 0) === $user_id;
    if ($frontend_login || (!$full_authentication && yoga_frontend_session_is_limited($user_id))) {
        // Keep the restriction when WordPress reissues cookies after profile changes.
        $session['yoga_frontend_only'] = true;
    }
    return $session;
}

function yoga_frontend_signon(WP_User $account, string $login, string $password) {
    $previous_context = $GLOBALS['yoga_frontend_login_user_id'] ?? 0;
    $GLOBALS['yoga_frontend_login_user_id'] = (int) $account->ID;
    $allow_customer_login = static function ($result, $username, $submitted_password) use ($account, $login) {
        if (!is_wp_error($result) || $username !== $login) {
            return $result;
        }
        $codes = $result->get_error_codes();
        if (count($codes) !== 1 || !in_array($codes[0], array('wfls_twofactor_required', 'wfls_twofactor_blocked'), true)) {
            return $result;
        }
        // Only waive the second factor for a password-verified administrator.
        // Other errors (CAPTCHA, account blocks, wrong passwords) still reject login.
        if (!yoga_account_has_administrative_access($account)
            || !wp_check_password($submitted_password, $account->user_pass, $account->ID)) {
            return $result;
        }
        return $account;
    };
    add_filter('authenticate', $allow_customer_login, PHP_INT_MAX, 3);
    try {
        $result = wp_signon(array(
            'user_login' => $login,
            'user_password' => $password,
            'remember' => true,
        ), is_ssl());
        if ($result instanceof WP_User) {
            $GLOBALS['yoga_authenticated_session_frontend_only'][$result->ID] = true;
        }
        return $result;
    } finally {
        remove_filter('authenticate', $allow_customer_login, PHP_INT_MAX);
        $GLOBALS['yoga_frontend_login_user_id'] = $previous_context;
    }
}

function yoga_set_frontend_auth_cookie(int $user_id, bool $remember = false): void {
    $previous_context = $GLOBALS['yoga_frontend_login_user_id'] ?? 0;
    $GLOBALS['yoga_frontend_login_user_id'] = $user_id;
    $GLOBALS['yoga_authenticated_session_frontend_only'][$user_id] = true;
    try {
        wp_set_auth_cookie($user_id, $remember);
    } finally {
        $GLOBALS['yoga_frontend_login_user_id'] = $previous_context;
    }
}

add_filter('map_meta_cap', 'yoga_limit_frontend_session_capabilities', PHP_INT_MAX, 4);
function yoga_limit_frontend_session_capabilities(array $caps, string $cap, int $user_id, array $args): array {
    if (!yoga_frontend_session_is_limited($user_id)) {
        return $caps;
    }
    if (in_array($cap, array('view_order', 'pay_for_order', 'order_again', 'cancel_order', 'download_file'), true)
        && function_exists('wc_customer_has_capability')) {
        // Check order ownership as a customer, including for multisite super admins.
        $customer_caps = wc_customer_has_capability(array(), array($cap), array_merge(array($cap, $user_id), $args));
        return !empty($customer_caps[$cap]) ? array('read') : array('do_not_allow');
    }
    return in_array($cap, array('read', 'level_0', 'exist'), true) ? $caps : array('do_not_allow');
}

add_filter('user_has_cap', 'yoga_frontend_session_customer_capabilities', PHP_INT_MAX, 4);
function yoga_frontend_session_customer_capabilities(array $allcaps, array $caps, array $args, WP_User $user): array {
    return yoga_frontend_session_is_limited((int) $user->ID)
        ? array_intersect_key($allcaps, array('read' => true, 'level_0' => true)) : $allcaps;
}

add_filter('show_admin_bar', 'yoga_hide_frontend_session_admin_bar');
function yoga_hide_frontend_session_admin_bar(bool $show): bool {
    return yoga_frontend_session_is_limited(get_current_user_id()) ? false : $show;
}

add_action('init', 'yoga_require_full_authentication_for_admin', 1);
function yoga_require_full_authentication_for_admin(): void {
    if (is_admin() && !wp_doing_ajax() && yoga_frontend_session_is_limited(get_current_user_id())) {
        wp_safe_redirect(wp_login_url(admin_url(), true));
        exit;
    }
}
