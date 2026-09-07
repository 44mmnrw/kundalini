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
		'progress_percentages_40' => array(25, 50, 75, 100),
		'progress_percentages_90' => array(30, 70),
		'progress_percentages_120' => array(25, 50, 75, 100),
		'progress_percentages_custom' => array(25, 50, 75, 100),
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

/**
 * @param mixed $value
 */
function kundalini_sadhanas_sanitize_progress_percentages($value): array {
	if (is_string($value)) {
		$value = preg_split('/[^0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
	}
	$value = is_array($value) ? $value : array();
	$percentages = array_values(array_unique(array_filter(array_map('absint', $value), static function (int $percentage): bool {
		return $percentage >= 1 && $percentage <= 100;
	})));
	sort($percentages, SORT_NUMERIC);
	return $percentages;
}

function kundalini_sadhanas_progress_profile(int $target_days): string {
	return in_array($target_days, array(40, 90, 120), true) ? (string) $target_days : 'custom';
}

function kundalini_sadhanas_progress_percentages(int $target_days): array {
	$profile = kundalini_sadhanas_progress_profile($target_days);
	return kundalini_sadhanas_sanitize_progress_percentages(
		kundalini_sadhanas_get_setting('progress_percentages_' . $profile)
	);
}

/**
 * Convert configured percentages into the days on which progress notifications fire.
 * The final day is handled by the separate completion notification.
 */
function kundalini_sadhanas_progress_milestones(int $target_days): array {
	$target_days = max(1, min(1000, $target_days));
	$milestones = array();
	foreach (kundalini_sadhanas_progress_percentages($target_days) as $percentage) {
		$day = (int) ceil(($target_days * $percentage) / 100);
		if ($day < $target_days) {
			$milestones[$day] = $day;
		}
	}
	ksort($milestones, SORT_NUMERIC);
	return array_values($milestones);
}

function kundalini_sadhanas_sanitize_settings($input): array {
	$input = is_array($input) ? $input : array();
	$result = array(
		'minimum_target_days' => max(1, min(1000, absint($input['minimum_target_days'] ?? 7))),
	);
	$defaults = kundalini_sadhanas_default_settings();
	foreach (array('40', '90', '120', 'custom') as $profile) {
		$key = 'progress_percentages_' . $profile;
		$result[$key] = kundalini_sadhanas_sanitize_progress_percentages($input[$key] ?? $defaults[$key]);
	}
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
