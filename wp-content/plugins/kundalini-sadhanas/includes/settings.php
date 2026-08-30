<?php
/**
 * Notification settings and email templates.
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
			'subject' => __('Что такое садхана?', 'kundalini-sadhanas'),
			'body' => __("Сат Нам, {user_name}!\n\nСадхана началась. Мы расскажем, как спокойная ежедневная практика помогает менять состояние.\n\nВ библиотеку практик:\n{library_url}", 'kundalini-sadhanas'),
		),
		'progress' => array(
			'label' => __('Поздравление с прогрессом', 'kundalini-sadhanas'),
			'site_enabled' => true,
			'email_enabled' => true,
			'subject' => __('Садхана: {milestone} дней', 'kundalini-sadhanas'),
			'body' => __("Вы практикуете «{practice_title}» уже {milestone} дней подряд. Продолжайте!\n\nОткрыть практику:\n{url}", 'kundalini-sadhanas'),
		),
		'interrupted' => array(
			'label' => __('Садхана прервана', 'kundalini-sadhanas'),
			'site_enabled' => true,
			'email_enabled' => false,
			'subject' => __('Садхана началась сначала', 'kundalini-sadhanas'),
			'body' => __("В садхане «{practice_title}» был пропущен день. Прогресс сброшен до нуля.\n\nОткрыть практику:\n{url}", 'kundalini-sadhanas'),
		),
		'completed' => array(
			'label' => __('Садхана завершена', 'kundalini-sadhanas'),
			'site_enabled' => true,
			'email_enabled' => true,
			'subject' => __('Садхана завершена', 'kundalini-sadhanas'),
			'body' => __("Вы завершили садхану «{practice_title}» продолжительностью {target_days} дней.\n\nОткрыть практику:\n{url}", 'kundalini-sadhanas'),
		),
	);
}

function kundalini_sadhanas_default_settings(): array {
	$settings = array(
		'minimum_target_days' => 7,
	);
	foreach (kundalini_sadhanas_notification_events() as $event => $definition) {
		foreach (array('site_enabled', 'email_enabled', 'subject', 'body') as $field) {
			$settings[$event . '_' . $field] = $definition[$field];
		}
	}
	return $settings;
}

function kundalini_sadhanas_get_settings(): array {
	$stored = get_option('kundalini_sadhanas_settings', array());
	return array_merge(kundalini_sadhanas_default_settings(), is_array($stored) ? $stored : array());
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
		$result[$event . '_subject'] = sanitize_text_field((string) ($input[$event . '_subject'] ?? $definition['subject']));
		$result[$event . '_body'] = sanitize_textarea_field((string) ($input[$event . '_body'] ?? $definition['body']));
	}
	return $result;
}

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

function kundalini_sadhanas_render_email(string $event, array $context): array {
	$settings = kundalini_sadhanas_get_settings();
	$replacements = array();
	foreach ($context as $key => $value) {
		$replacements['{' . $key . '}'] = (string) $value;
	}
	$subject = strtr((string) ($settings[$event . '_subject'] ?? ''), $replacements);
	$body = strtr((string) ($settings[$event . '_body'] ?? ''), $replacements);
	return array(
		'subject' => wp_strip_all_tags($subject),
		'body' => wp_strip_all_tags($body),
	);
}
