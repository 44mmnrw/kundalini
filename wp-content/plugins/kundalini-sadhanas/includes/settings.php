<?php
/**
 * Sadhana, celebration, and notification settings.
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
		'confetti_enabled' => true,
		'confetti_duration' => 5.4,
		'confetti_intensity' => 4,
		'confetti_colors' => array('#9153e1', '#f8bdf6', '#e8ff57', '#1f1f1f'),
		'confetti_size' => 1.15,
		'confetti_speed' => 38,
		'confetti_direction' => 'both',
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
	$settings = array_merge($defaults, $stored);
	$settings['confetti_colors'] = kundalini_sadhanas_sanitize_confetti_colors($settings['confetti_colors']);
	return $settings;
}

/** @return mixed */
function kundalini_sadhanas_get_setting(string $key) {
	$settings = kundalini_sadhanas_get_settings();
	return $settings[$key] ?? null;
}

function kundalini_sadhanas_minimum_target_days(): int {
	return max(1, min(1000, absint(kundalini_sadhanas_get_setting('minimum_target_days'))));
}

/** @param mixed $value */
function kundalini_sadhanas_confetti_number($value, float $default, float $minimum, float $maximum): float {
	if (!is_numeric($value)) {
		return $default;
	}
	return max($minimum, min($maximum, (float) $value));
}

/** @param mixed $value */
function kundalini_sadhanas_sanitize_confetti_colors($value): array {
	$defaults = kundalini_sadhanas_default_settings()['confetti_colors'];
	if (!is_array($value)) {
		return $defaults;
	}
	$colors = array();
	foreach ($defaults as $index => $default) {
		$color = $value[$index] ?? null;
		$colors[] = is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color)
			? strtolower($color)
			: $default;
	}
	return $colors;
}

function kundalini_sadhanas_confetti_config(): array {
	$settings = kundalini_sadhanas_get_settings();
	return array(
		'enabled' => !empty($settings['confetti_enabled']),
		'duration' => (int) round(kundalini_sadhanas_confetti_number($settings['confetti_duration'], 5.4, 1, 15) * 1000),
		'intensity' => (int) kundalini_sadhanas_confetti_number($settings['confetti_intensity'], 4, 1, 8),
		'colors' => kundalini_sadhanas_sanitize_confetti_colors($settings['confetti_colors']),
		'size' => kundalini_sadhanas_confetti_number($settings['confetti_size'], 1.15, 0.5, 2),
		'speed' => (int) round(kundalini_sadhanas_confetti_number($settings['confetti_speed'], 38, 15, 70)),
		'direction' => in_array($settings['confetti_direction'], array('both', 'left', 'right', 'center'), true)
			? $settings['confetti_direction']
			: 'both',
	);
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
	$result['confetti_enabled'] = !empty($input['confetti_enabled']);
	$result['confetti_duration'] = round(kundalini_sadhanas_confetti_number($input['confetti_duration'] ?? null, 5.4, 1, 15), 1);
	$result['confetti_intensity'] = (int) kundalini_sadhanas_confetti_number($input['confetti_intensity'] ?? null, 4, 1, 8);
	$result['confetti_colors'] = kundalini_sadhanas_sanitize_confetti_colors($input['confetti_colors'] ?? null);
	$result['confetti_size'] = round(kundalini_sadhanas_confetti_number($input['confetti_size'] ?? null, 1.15, 0.5, 2), 2);
	$result['confetti_speed'] = (int) round(kundalini_sadhanas_confetti_number($input['confetti_speed'] ?? null, 38, 15, 70));
	$result['confetti_direction'] = in_array($input['confetti_direction'] ?? '', array('both', 'left', 'right', 'center'), true)
		? $input['confetti_direction']
		: 'both';
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
