<?php
/**
 * Notification channel settings.
 */

if (!defined('ABSPATH')) {
	exit;
}

function kundalini_sadhanas_notification_events(): array {
	return array(
		'started' => array(
			'label' => __('После старта садханы', 'kundalini-sadhanas'),
			'site_enabled' => false,
			'email_enabled' => true,
		),
		'progress' => array(
			'label' => __('Поздравление с прогрессом', 'kundalini-sadhanas'),
			'site_enabled' => true,
			'email_enabled' => true,
		),
		'interrupted' => array(
			'label' => __('Садхана прервана', 'kundalini-sadhanas'),
			'site_enabled' => true,
			'email_enabled' => false,
		),
		'completed' => array(
			'label' => __('Садхана завершена', 'kundalini-sadhanas'),
			'site_enabled' => true,
			'email_enabled' => true,
		),
	);
}

function kundalini_sadhanas_default_settings(): array {
	$settings = array(
		'minimum_target_days' => 7,
	);
	foreach (kundalini_sadhanas_notification_events() as $event => $definition) {
		foreach (array('site_enabled', 'email_enabled') as $field) {
			$settings[$event . '_' . $field] = $definition[$field];
		}
	}
	return $settings;
}

function kundalini_sadhanas_get_settings(): array {
	$stored = get_option('kundalini_sadhanas_settings', array());
	$defaults = kundalini_sadhanas_default_settings();
	$stored = is_array($stored) ? array_intersect_key($stored, $defaults) : array();
	return array_merge($defaults, $stored);
}

/** @return mixed */
function kundalini_sadhanas_get_setting(string $key) {
	$settings = kundalini_sadhanas_get_settings();
	return $settings[$key] ?? null;
}

function kundalini_sadhanas_minimum_target_days(): int {
	return max(1, min(1000, absint(kundalini_sadhanas_get_setting('minimum_target_days'))));
}

function kundalini_sadhanas_sanitize_settings($input): array {
	$input = is_array($input) ? $input : array();
	$result = array(
		'minimum_target_days' => max(1, min(1000, absint($input['minimum_target_days'] ?? 7))),
	);
	foreach (kundalini_sadhanas_notification_events() as $event => $definition) {
		$result[$event . '_site_enabled'] = !empty($input[$event . '_site_enabled']);
		$result[$event . '_email_enabled'] = !empty($input[$event . '_email_enabled']);
	}
	return $result;
}

function kundalini_sadhanas_remove_legacy_email_settings(): void {
	$stored = get_option('kundalini_sadhanas_settings', array());
	if (!is_array($stored)) {
		return;
	}
	$clean = array_intersect_key($stored, kundalini_sadhanas_default_settings());
	if ($clean !== $stored) {
		update_option('kundalini_sadhanas_settings', $clean, false);
	}
}
add_action('init', 'kundalini_sadhanas_remove_legacy_email_settings', 1);

function kundalini_sadhanas_filter_notification_defaults(array $defaults): array {
	$settings = kundalini_sadhanas_get_settings();
	foreach (array_keys(kundalini_sadhanas_notification_events()) as $event) {
		$defaults['sadhana_' . $event . '_site'] = !empty($settings[$event . '_site_enabled']);
		$defaults['sadhana_' . $event . '_email'] = !empty($settings[$event . '_email_enabled']);
	}
	return $defaults;
}
add_filter('yoga_notification_preference_defaults', 'kundalini_sadhanas_filter_notification_defaults');

function kundalini_sadhanas_channel_enabled(int $user_id, string $event, string $channel): bool {
	$key = $event . '_' . $channel . '_enabled';
	$default = (bool) kundalini_sadhanas_get_setting($key);
	$preference_key = 'sadhana_' . $event . '_' . $channel;
	return function_exists('yoga_notification_preference')
		? yoga_notification_preference($user_id, $preference_key, $default)
		: $default;
}
