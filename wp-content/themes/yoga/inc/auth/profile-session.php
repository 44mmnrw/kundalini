<?php
/**
 * Reissue the current user's authentication cookies after profile credentials change.
 *
 * @package Yoga
 */

function yoga_refresh_profile_auth_session(int $user_id, $logged_in_cookie): void {
	$token = is_array($logged_in_cookie) ? (string) ($logged_in_cookie['token'] ?? '') : '';
	$expiration = is_array($logged_in_cookie) ? (int) ($logged_in_cookie['expiration'] ?? 0) : 0;
	$default_lifetime = apply_filters('auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user_id, false);
	$remember = $expiration - time() > $default_lifetime;

	// A password-change hook may revoke the old token. Let WordPress create a new one then.
	if ($token !== '' && !WP_Session_Tokens::get_instance($user_id)->verify($token)) {
		$token = '';
	}

	wp_set_auth_cookie($user_id, $remember, '', $token);
}
