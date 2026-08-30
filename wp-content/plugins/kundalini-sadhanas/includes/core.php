<?php
/**
 * Persistent Sadhana cycles stored in the dedicated database table.
 *
 * @package Yoga_Sadhanas
 */

if (!defined('ABSPATH')) {
	exit;
}

const YOGA_SADHANA_CRON_HOOK = 'yoga_sadhana_reconcile_due';
const YOGA_SADHANA_STORAGE_VERSION = '2';

function yoga_sadhana_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'yoga_sadhanas';
}

function yoga_sadhana_storage_exists(): bool {
	global $wpdb;
	$table = yoga_sadhana_table();
	$found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
	return is_string($found) && $found === $table;
}

function yoga_sadhana_install_storage(): bool {
	global $wpdb;
	$table = yoga_sadhana_table();
	$charset_collate = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL,
		practice_id bigint(20) unsigned NOT NULL,
		target_days smallint(5) unsigned NOT NULL,
		completed_days smallint(5) unsigned NOT NULL DEFAULT 0,
		status varchar(20) NOT NULL DEFAULT 'active',
		active_key varchar(64) DEFAULT NULL,
		started_on date NOT NULL,
		last_marked_on date DEFAULT NULL,
		completed_on date DEFAULT NULL,
		cancelled_on date DEFAULT NULL,
		reset_count smallint(5) unsigned NOT NULL DEFAULT 0,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY active_key (active_key),
		KEY user_status (user_id,status),
		KEY practice_id (practice_id),
		KEY last_marked_on (last_marked_on)
	) {$charset_collate};";
	dbDelta($sql);
	return yoga_sadhana_storage_exists();
}

function yoga_sadhana_legacy_status(string $status): string {
	return array(
		'sadhana-active' => 'active',
		'sadhana-completed' => 'completed',
		'sadhana-cancelled' => 'cancelled',
	)[$status] ?? '';
}

/**
 * One-time migration from the temporary post/postmeta implementation.
 */
function yoga_sadhana_migrate_legacy_posts(): void {
	global $wpdb;
	if (
		(string) get_option('yoga_sadhana_storage_version', '') === YOGA_SADHANA_STORAGE_VERSION
		&& yoga_sadhana_storage_exists()
	) {
		return;
	}

	if (!yoga_sadhana_install_storage()) {
		return;
	}
	$table = yoga_sadhana_table();
	$legacy_posts = get_posts(array(
		'post_type' => 'sadhana_cycle',
		'post_status' => array('sadhana-active', 'sadhana-completed', 'sadhana-cancelled'),
		'numberposts' => -1,
		'orderby' => 'ID',
		'order' => 'ASC',
		'suppress_filters' => false,
	));

	foreach ($legacy_posts as $post) {
		if (!$post instanceof WP_Post) {
			continue;
		}
		$status = yoga_sadhana_legacy_status((string) $post->post_status);
		if ($status === '') {
			continue;
		}
		$exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE id = %d", $post->ID));
		if ($exists > 0) {
			continue;
		}
		$user_id = (int) $post->post_author;
		$practice_id = (int) $post->post_parent;
		$active_key = $status === 'active' ? $user_id . ':' . $practice_id : null;
		$wpdb->insert($table, array(
			'id' => (int) $post->ID,
			'user_id' => $user_id,
			'practice_id' => $practice_id,
			'target_days' => max(1, absint(get_post_meta($post->ID, '_yoga_sadhana_target_days', true))),
			'completed_days' => absint(get_post_meta($post->ID, '_yoga_sadhana_completed_days', true)),
			'status' => $status,
			'active_key' => $active_key,
			'started_on' => (string) get_post_meta($post->ID, '_yoga_sadhana_started_on', true) ?: substr((string) $post->post_date, 0, 10),
			'last_marked_on' => (string) get_post_meta($post->ID, '_yoga_sadhana_last_marked_on', true) ?: null,
			'completed_on' => (string) get_post_meta($post->ID, '_yoga_sadhana_completed_on', true) ?: null,
			'cancelled_on' => (string) get_post_meta($post->ID, '_yoga_sadhana_cancelled_on', true) ?: null,
			'reset_count' => absint(get_post_meta($post->ID, '_yoga_sadhana_reset_count', true)),
			'created_at' => (string) ($post->post_date_gmt ?: $post->post_date),
			'updated_at' => (string) ($post->post_modified_gmt ?: $post->post_modified),
		), array('%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'));
	}

	update_option('yoga_sadhana_storage_version', YOGA_SADHANA_STORAGE_VERSION, false);
}

function yoga_sadhana_register_storage(): void {
	yoga_sadhana_migrate_legacy_posts();
	if (!wp_next_scheduled(YOGA_SADHANA_CRON_HOOK)) {
		wp_schedule_event(time() + 300, 'hourly', YOGA_SADHANA_CRON_HOOK);
	}
}
add_action('init', 'yoga_sadhana_register_storage', 1);

function yoga_sadhana_user_timezone(int $user_id): DateTimeZone {
	$timezone = trim((string) get_user_meta($user_id, 'timezone', true));
	if ($timezone === '') {
		$timezone = 'Europe/Moscow';
	}
	try {
		return new DateTimeZone($timezone);
	} catch (Exception $exception) {
		return new DateTimeZone('Europe/Moscow');
	}
}

function yoga_sadhana_today(int $user_id): string {
	return (new DateTimeImmutable('now', yoga_sadhana_user_timezone($user_id)))->format('Y-m-d');
}

/** @return mixed */
function yoga_sadhana_with_lock(string $key, callable $callback) {
	global $wpdb;
	$lock_name = substr($wpdb->prefix . 'yoga_sadhana:' . md5($key), 0, 64);
	$locked = (int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lock_name)) === 1;
	if (!$locked) {
		return new WP_Error('sadhana_busy', __('Садхана сейчас обновляется. Попробуйте ещё раз.', 'yoga'));
	}
	try {
		return $callback();
	} finally {
		$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
	}
}

function yoga_sadhana_valid_practice(int $user_id, int $practice_id): bool {
	$post = get_post($practice_id);
	if (!$post instanceof WP_Post || $post->post_type !== 'practice' || $post->post_status !== 'publish') {
		return false;
	}
	return !function_exists('yoga_user_can_access_practice') || yoga_user_can_access_practice($user_id, $practice_id);
}

function yoga_sadhana_row_from_db(object|array $record): array {
	$row = (array) $record;
	return array(
		'id' => absint($row['id'] ?? 0),
		'user_id' => absint($row['user_id'] ?? 0),
		'practice_id' => absint($row['practice_id'] ?? 0),
		'target_days' => absint($row['target_days'] ?? 0),
		'completed_days' => absint($row['completed_days'] ?? 0),
		'status' => sanitize_key((string) ($row['status'] ?? '')),
		'started_on' => (string) ($row['started_on'] ?? ''),
		'last_marked_on' => (string) ($row['last_marked_on'] ?? ''),
		'completed_on' => (string) ($row['completed_on'] ?? ''),
		'cancelled_on' => (string) ($row['cancelled_on'] ?? ''),
		'reset_count' => absint($row['reset_count'] ?? 0),
		'created_at' => (string) ($row['created_at'] ?? ''),
		'updated_at' => (string) ($row['updated_at'] ?? ''),
	);
}

function yoga_sadhana_get(int $sadhana_id, int $user_id = 0): ?array {
	global $wpdb;
	$table = yoga_sadhana_table();
	$sql = "SELECT * FROM {$table} WHERE id = %d";
	$args = array($sadhana_id);
	if ($user_id > 0) {
		$sql .= ' AND user_id = %d';
		$args[] = $user_id;
	}
	$record = $wpdb->get_row($wpdb->prepare($sql, ...$args));
	return $record ? yoga_sadhana_row_from_db($record) : null;
}

function yoga_sadhana_find_active_id(int $user_id, int $practice_id): int {
	global $wpdb;
	$table = yoga_sadhana_table();
	return (int) $wpdb->get_var($wpdb->prepare(
		"SELECT id FROM {$table} WHERE user_id = %d AND practice_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
		$user_id,
		$practice_id
	));
}

function yoga_sadhana_get_active(int $user_id, int $practice_id, bool $normalize = true): ?array {
	$id = yoga_sadhana_find_active_id($user_id, $practice_id);
	$row = $id > 0 ? yoga_sadhana_get($id, $user_id) : null;
	return $row && $normalize ? yoga_sadhana_normalize($row) : $row;
}

function yoga_sadhana_notification_url(array $row): string {
	$practice_id = absint($row['practice_id'] ?? 0);
	$url = $practice_id > 0 ? get_permalink($practice_id) : '';
	if (is_string($url) && $url !== '') {
		return $url;
	}
	return function_exists('yoga_get_lk_section_url') ? yoga_get_lk_section_url('sadhanas') : home_url('/');
}

function yoga_sadhana_notify(array $row, string $event, int $milestone = 0): void {
	$user_id = absint($row['user_id'] ?? 0);
	if ($user_id <= 0) {
		return;
	}
	$practice_id = absint($row['practice_id'] ?? 0);
	$practice_title = get_the_title($practice_id) ?: __('Практика', 'yoga');
	$url = yoga_sadhana_notification_url($row);
	$revision = absint($row['reset_count'] ?? 0);
	if ($event === 'progress') {
		$type = 'sadhana_progress';
		$title = sprintf(__('Садхана: %d дней', 'yoga'), $milestone);
		$message = sprintf(__('Вы практикуете «%1$s» уже %2$d дней подряд. Продолжайте!', 'yoga'), $practice_title, $milestone);
		$key_suffix = 'progress:' . $revision . ':' . $milestone;
	} elseif ($event === 'interrupted') {
		$type = 'sadhana_interrupted';
		$title = __('Садхана началась сначала', 'yoga');
		$message = sprintf(__('В садхане «%s» был пропущен день. Прогресс сброшен до нуля.', 'yoga'), $practice_title);
		$key_suffix = 'interrupted:' . $revision;
	} else {
		$type = 'sadhana_completed';
		$title = __('Садхана завершена', 'yoga');
		$message = sprintf(__('Вы завершили садхану «%1$s» продолжительностью %2$d дней.', 'yoga'), $practice_title, absint($row['target_days'] ?? 0));
		$key_suffix = 'completed';
	}
	$dedupe_key = 'sadhana:' . absint($row['id'] ?? 0) . ':' . $key_suffix;
	$notification_context = array(
		'practice_title' => $practice_title,
		'milestone' => $milestone,
		'target_days' => absint($row['target_days'] ?? 0),
		'completed_days' => absint($row['completed_days'] ?? 0),
		'url' => $url,
	);

	if (kundalini_sadhanas_channel_enabled($user_id, $event, 'site') && function_exists('yoga_add_user_notification')) {
		yoga_add_user_notification($user_id, $type, $title, $message, $url, array('dedupe_key' => $dedupe_key, 'post_id' => $practice_id));
	}
	do_action('kundalini_sadhanas_notification', $user_id, $event, $title, $message, $notification_context, $row);

	if (!kundalini_sadhanas_channel_enabled($user_id, $event, 'email')) {
		return;
	}
	$user = get_user_by('id', $user_id);
	if ($user instanceof WP_User && is_email($user->user_email)) {
		$email = kundalini_sadhanas_render_email($event, $notification_context);
		if (function_exists('yoga_mail_send')) {
			yoga_mail_send('sadhana-' . sanitize_key($event), array(
				'to' => (string) $user->user_email,
				'subject' => $email['subject'],
				'content' => nl2br(esc_html($email['body'])),
				'data' => array_merge($notification_context, array(
					'user_name' => $user->display_name ?: $user->user_login,
					'user_email' => $user->user_email,
				)),
			));
		} else {
			wp_mail((string) $user->user_email, $email['subject'], $email['body']);
		}
	}
}

function yoga_sadhana_normalize(array $row): array {
	if (($row['status'] ?? '') !== 'active' || absint($row['completed_days'] ?? 0) <= 0 || empty($row['last_marked_on'])) {
		return $row;
	}
	$sadhana_id = absint($row['id']);
	$user_id = absint($row['user_id']);
	$did_reset = false;
	$result = yoga_sadhana_with_lock('cycle:' . $sadhana_id, static function () use ($sadhana_id, $user_id, &$did_reset) {
		global $wpdb;
		$current = yoga_sadhana_get($sadhana_id, $user_id);
		if (!$current || $current['status'] !== 'active' || $current['completed_days'] <= 0 || empty($current['last_marked_on'])) {
			return $current;
		}
		$today = yoga_sadhana_today($user_id);
		$yesterday = (new DateTimeImmutable($today, yoga_sadhana_user_timezone($user_id)))->modify('-1 day')->format('Y-m-d');
		if ((string) $current['last_marked_on'] >= $yesterday) {
			return $current;
		}
		$wpdb->update(yoga_sadhana_table(), array(
			'completed_days' => 0,
			'last_marked_on' => null,
			'started_on' => $today,
			'reset_count' => $current['reset_count'] + 1,
			'updated_at' => current_time('mysql', true),
		), array('id' => $sadhana_id), array('%d', '%s', '%s', '%d', '%s'), array('%d'));
		$did_reset = true;
		return yoga_sadhana_get($sadhana_id, $user_id);
	});
	if (is_wp_error($result) || !is_array($result)) {
		return $row;
	}
	if ($did_reset) {
		yoga_sadhana_notify($result, 'interrupted');
	}
	return $result;
}

function yoga_sadhana_start(int $user_id, int $practice_id, int $target_days): array|WP_Error {
	global $wpdb;
	$minimum_days = function_exists('kundalini_sadhanas_minimum_target_days')
		? kundalini_sadhanas_minimum_target_days()
		: 7;
	if ($target_days < $minimum_days || $target_days > 1000) {
		return new WP_Error(
			'invalid_duration',
			sprintf(__('Выберите срок от %1$d до %2$d дней.', 'kundalini-sadhanas'), $minimum_days, 1000)
		);
	}
	if (!yoga_sadhana_valid_practice($user_id, $practice_id)) {
		return new WP_Error('invalid_practice', __('Практика недоступна.', 'yoga'));
	}
	return yoga_sadhana_with_lock('active:' . $user_id . ':' . $practice_id, static function () use ($user_id, $practice_id, $target_days, $wpdb) {
		$existing_id = yoga_sadhana_find_active_id($user_id, $practice_id);
		if ($existing_id > 0) {
			$existing = yoga_sadhana_get($existing_id, $user_id);
			$existing['already_active'] = true;
			return $existing;
		}
		$now = current_time('mysql', true);
		$inserted = $wpdb->insert(yoga_sadhana_table(), array(
			'user_id' => $user_id,
			'practice_id' => $practice_id,
			'target_days' => $target_days,
			'completed_days' => 0,
			'status' => 'active',
			'active_key' => $user_id . ':' . $practice_id,
			'started_on' => yoga_sadhana_today($user_id),
			'reset_count' => 0,
			'created_at' => $now,
			'updated_at' => $now,
		), array('%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s'));
		if ($inserted === false) {
			return new WP_Error('create_failed', __('Не удалось начать садхану.', 'yoga'));
		}
		return yoga_sadhana_get((int) $wpdb->insert_id, $user_id) ?: new WP_Error('create_failed', __('Не удалось начать садхану.', 'yoga'));
	});
}

function yoga_sadhana_mark_day(int $user_id, int $sadhana_id): array|WP_Error {
	global $wpdb;
	$row = yoga_sadhana_get($sadhana_id, $user_id);
	if (!$row || $row['status'] !== 'active') {
		return new WP_Error('not_active', __('Активная садхана не найдена.', 'yoga'));
	}
	$row = yoga_sadhana_normalize($row);
	$result = yoga_sadhana_with_lock('cycle:' . $sadhana_id, static function () use ($user_id, $sadhana_id, $wpdb) {
		$current = yoga_sadhana_get($sadhana_id, $user_id);
		if (!$current || $current['status'] !== 'active') {
			return new WP_Error('not_active', __('Активная садхана не найдена.', 'yoga'));
		}
		$today = yoga_sadhana_today($user_id);
		if (!empty($current['last_marked_on']) && $current['last_marked_on'] >= $today) {
			$current['already_marked'] = true;
			return $current;
		}
		$completed_days = min($current['target_days'], $current['completed_days'] + 1);
		$is_completed = $completed_days >= $current['target_days'];
		$data = array(
			'completed_days' => $completed_days,
			'last_marked_on' => $today,
			'updated_at' => current_time('mysql', true),
		);
		$formats = array('%d', '%s', '%s');
		if ($is_completed) {
			$data['status'] = 'completed';
			$data['active_key'] = null;
			$data['completed_on'] = $today;
			$formats = array('%d', '%s', '%s', '%s', '%s', '%s');
		}
		$wpdb->update(yoga_sadhana_table(), $data, array('id' => $sadhana_id), $formats, array('%d'));
		return yoga_sadhana_get($sadhana_id, $user_id);
	});
	if (is_wp_error($result) || !empty($result['already_marked'])) {
		return $result;
	}
	if ($result['status'] === 'completed') {
		yoga_sadhana_notify($result, 'completed');
	} elseif (in_array($result['completed_days'], array(7, 21, 40, 90, 120), true)) {
		yoga_sadhana_notify($result, 'progress', $result['completed_days']);
	}
	return $result;
}

function yoga_sadhana_cancel(int $user_id, int $sadhana_id): array|WP_Error {
	global $wpdb;
	return yoga_sadhana_with_lock('cycle:' . $sadhana_id, static function () use ($user_id, $sadhana_id, $wpdb) {
		$row = yoga_sadhana_get($sadhana_id, $user_id);
		if (!$row || $row['status'] !== 'active') {
			return new WP_Error('not_active', __('Активная садхана не найдена.', 'yoga'));
		}
		$updated = $wpdb->update(yoga_sadhana_table(), array(
			'status' => 'cancelled',
			'active_key' => null,
			'cancelled_on' => yoga_sadhana_today($user_id),
			'updated_at' => current_time('mysql', true),
		), array('id' => $sadhana_id), array('%s', '%s', '%s', '%s'), array('%d'));
		if ($updated === false) {
			return new WP_Error('cancel_failed', __('Не удалось отменить садхану.', 'yoga'));
		}
		return yoga_sadhana_get($sadhana_id, $user_id) ?: new WP_Error('cancel_failed', __('Не удалось отменить садхану.', 'yoga'));
	});
}

function yoga_sadhana_restart(int $user_id, int $completed_id): array|WP_Error {
	$row = yoga_sadhana_get($completed_id, $user_id);
	if (!$row || $row['status'] !== 'completed') {
		return new WP_Error('not_completed', __('Завершённая садхана не найдена.', 'yoga'));
	}
	return yoga_sadhana_start($user_id, $row['practice_id'], $row['target_days']);
}

function yoga_sadhana_get_user_rows(int $user_id, string $status): array {
	global $wpdb;
	if (!in_array($status, array('active', 'completed'), true)) {
		return array();
	}
	$table = yoga_sadhana_table();
	$records = $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM {$table} WHERE user_id = %d AND status = %s ORDER BY updated_at DESC, id DESC",
		$user_id,
		$status
	));
	$rows = array_map('yoga_sadhana_row_from_db', $records ?: array());
	if ($status === 'active') {
		foreach ($rows as $index => $row) {
			$rows[$index] = yoga_sadhana_normalize($row);
		}
	}
	return $rows;
}

function yoga_sadhana_active_count(int $user_id): int {
	global $wpdb;
	$table = yoga_sadhana_table();
	return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND status = 'active'", $user_id));
}

function yoga_sadhana_public_data(array $row): array {
	$user_id = absint($row['user_id'] ?? 0);
	$today = $user_id > 0 ? yoga_sadhana_today($user_id) : '';
	$next_day_at = $user_id > 0
		? (new DateTimeImmutable('tomorrow', yoga_sadhana_user_timezone($user_id)))->getTimestamp() * 1000
		: 0;
	$completed = absint($row['completed_days'] ?? 0);
	$total = max(1, absint($row['target_days'] ?? 1));
	return array(
		'id' => absint($row['id'] ?? 0),
		'practice_id' => absint($row['practice_id'] ?? 0),
		'status' => sanitize_key((string) ($row['status'] ?? '')),
		'completed_days' => $completed,
		'total_days' => $total,
		'progress' => min(100, (int) floor(($completed / $total) * 100)),
		'marked_today' => !empty($row['last_marked_on']) && (string) $row['last_marked_on'] >= $today,
		'next_day_at' => $next_day_at,
		'already_active' => !empty($row['already_active']),
	);
}

function yoga_sadhana_reconcile_due(): void {
	global $wpdb;
	$table = yoga_sadhana_table();
	$ids = $wpdb->get_col("SELECT id FROM {$table} WHERE status = 'active' ORDER BY id ASC");
	foreach ($ids as $id) {
		$row = yoga_sadhana_get((int) $id);
		if ($row) {
			yoga_sadhana_normalize($row);
		}
	}
}
add_action(YOGA_SADHANA_CRON_HOOK, 'yoga_sadhana_reconcile_due');
