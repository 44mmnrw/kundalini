<?php
/** Простые счётчики выполнений практик. Просмотры страницы не учитываются. */
if (!defined('ABSPATH')) { exit; }

function yoga_statistics_activity_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'yoga_practice_activity';
}

function yoga_statistics_activity_exists(): bool {
	global $wpdb;
	$table = yoga_statistics_activity_table();
	return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table;
}

function yoga_statistics_install_activity(): void {
	if (!current_user_can('manage_options') || get_option('yoga_practice_activity_version') === '1') { return; }
	global $wpdb;
	$table = yoga_statistics_activity_table();
	$charset = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta("CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL,
		practice_id bigint(20) unsigned NOT NULL,
		session_key char(36) NOT NULL,
		started_at datetime NOT NULL,
		completed_at datetime DEFAULT NULL,
		completed_on date DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY user_session (user_id,session_key),
		KEY user_practice (user_id,practice_id),
		KEY practice_id (practice_id),
		KEY completed_on (completed_on)
	) {$charset};");
	if (yoga_statistics_activity_exists()) {
		update_option('yoga_practice_activity_version', '1', false);
		update_option('yoga_practice_activity_since', current_time('mysql', true), false);
	}
}
add_action('admin_init', 'yoga_statistics_install_activity');
add_action('after_switch_theme', 'yoga_statistics_install_activity');

function yoga_statistics_user_timezone(int $user_id): DateTimeZone {
	if (function_exists('yoga_sadhana_user_timezone')) { return yoga_sadhana_user_timezone($user_id); }
	try { return new DateTimeZone((string) get_user_meta($user_id, 'timezone', true) ?: 'Europe/Moscow'); }
	catch (Exception $error) { return new DateTimeZone('Europe/Moscow'); }
}

/** Учитываем доступную целиком практику, в том числе открытую без подписки. */
function yoga_statistics_can_track_practice(int $user_id, int $practice_id): bool {
	if ($user_id <= 0 || $practice_id <= 0 || get_post_type($practice_id) !== 'practice' || get_post_status($practice_id) !== 'publish') { return false; }
	return (function_exists('yoga_viewer_has_full_practice_sections') && yoga_viewer_has_full_practice_sections($user_id, $practice_id))
		|| (function_exists('yoga_practice_is_fully_open_for_guests') && yoga_practice_is_fully_open_for_guests($practice_id))
		|| (function_exists('yoga_guest_practice_section_filter_enabled') && !yoga_guest_practice_section_filter_enabled());
}

/** UUID makes retries, page reloads and pause/resume idempotent. Completion is an atomic update. */
function yoga_statistics_record_activity(int $user_id, int $practice_id, string $session_key, string $event) {
	if ($user_id <= 0 || $practice_id <= 0 || !in_array($event, array('start', 'complete'), true)
		|| !preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/D', $session_key)) {
		return new WP_Error('invalid_activity', 'Некорректные данные выполнения.');
	}
	if (!yoga_statistics_activity_exists()) {
		return new WP_Error('activity_unavailable', 'Не удалось сохранить прогресс. Попробуйте позже.');
	}
	global $wpdb;
	$table = yoga_statistics_activity_table();
	$now = current_time('mysql', true);
	if ($event === 'start') {
		$result = $wpdb->query($wpdb->prepare(
			"INSERT IGNORE INTO {$table} (user_id, practice_id, session_key, started_at) VALUES (%d, %d, %s, %s)",
			$user_id, $practice_id, $session_key, $now
		));
		if ($result === false) { return new WP_Error('activity_write_failed', 'Не удалось сохранить прогресс.'); }
	}
	$row = $wpdb->get_row($wpdb->prepare("SELECT practice_id, completed_at FROM {$table} WHERE user_id = %d AND session_key = %s", $user_id, $session_key), ARRAY_A);
	if (!$row || (int) $row['practice_id'] !== $practice_id) {
		return new WP_Error('activity_not_started', 'Сначала начните выполнение практики.');
	}
	if ($event === 'complete' && empty($row['completed_at'])) {
		$date = (new DateTimeImmutable('now', yoga_statistics_user_timezone($user_id)))->format('Y-m-d');
		$result = $wpdb->query($wpdb->prepare(
			"UPDATE {$table} SET completed_at = %s, completed_on = %s WHERE user_id = %d AND practice_id = %d AND session_key = %s AND completed_at IS NULL",
			$now, $date, $user_id, $practice_id, $session_key
		));
		if ($result === false) { return new WP_Error('activity_write_failed', 'Не удалось сохранить прогресс.'); }
		$row['completed_at'] = $now;
	}
	return array('completed' => !empty($row['completed_at']));
}

function yoga_statistics_ajax_activity(): void {
	if (!is_user_logged_in() || !check_ajax_referer('yoga_practice_activity', 'nonce', false)) {
		wp_send_json_error(array('message' => 'Обновите страницу и войдите в аккаунт.'), 403); return;
	}
	$practice_id = isset($_POST['practice_id']) && is_scalar($_POST['practice_id']) ? absint($_POST['practice_id']) : 0;
	$user_id = (int) get_current_user_id();
	if (!yoga_statistics_can_track_practice($user_id, $practice_id)) {
		wp_send_json_error(array('message' => 'Практика недоступна.'), 403); return;
	}
	$session = isset($_POST['session_key']) && is_string($_POST['session_key']) ? strtolower(wp_unslash($_POST['session_key'])) : '';
	$event = isset($_POST['event']) && is_string($_POST['event']) ? wp_unslash($_POST['event']) : '';
	$result = yoga_statistics_record_activity($user_id, $practice_id, $session, $event);
	if (is_wp_error($result)) { wp_send_json_error(array('message' => $result->get_error_message()), 400); return; }
	wp_send_json_success($result);
}
add_action('wp_ajax_yoga_practice_activity', 'yoga_statistics_ajax_activity');

function yoga_statistics_enqueue_activity(): void {
	$practice_id = (int) get_queried_object_id();
	if (!is_user_logged_in() || !is_singular('practice') || !yoga_statistics_can_track_practice((int) get_current_user_id(), $practice_id)) { return; }
	$file = get_template_directory() . '/assets/js/practice-statistics.js';
	wp_enqueue_script('yoga-practice-statistics', get_template_directory_uri() . '/assets/js/practice-statistics.js', array(), (string) filemtime($file), true);
	wp_localize_script('yoga-practice-statistics', 'yogaPracticeStatistics', array('ajaxUrl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('yoga_practice_activity'), 'practiceId' => $practice_id, 'userId' => (int) get_current_user_id()));
}
add_action('wp_enqueue_scripts', 'yoga_statistics_enqueue_activity', 20);

function yoga_statistics_activity_summary(int $user_id = 0): array {
	$empty = array('available' => yoga_statistics_activity_exists(), 'starts' => 0, 'completions' => 0, 'practices_started' => 0, 'practices_completed' => 0, 'users' => 0);
	if (!$empty['available']) { return $empty; }
	global $wpdb;
	$table = yoga_statistics_activity_table();
	$where = $user_id > 0 ? $wpdb->prepare(' WHERE a.user_id = %d', $user_id) : '';
	$row = $wpdb->get_row("SELECT COUNT(*) AS starts, COUNT(completed_at) AS completions, COUNT(DISTINCT practice_id) AS practices_started, COUNT(DISTINCT CASE WHEN completed_at IS NOT NULL THEN practice_id END) AS practices_completed, COUNT(DISTINCT a.user_id) AS users FROM {$table} a INNER JOIN {$wpdb->users} u ON u.ID = a.user_id {$where}", ARRAY_A);
	foreach (array('starts', 'completions', 'practices_started', 'practices_completed', 'users') as $key) { $empty[$key] = (int) ($row[$key] ?? 0); }
	return $empty;
}

function yoga_statistics_popular_practices(int $user_id = 0, int $page = 1): array {
	if (!yoga_statistics_activity_exists()) { return array(); }
	global $wpdb;
	$table = yoga_statistics_activity_table();
	$where = $user_id > 0 ? $wpdb->prepare(' WHERE a.user_id = %d', $user_id) : '';
	$offset = max(0, $page - 1) * 20;
	return $wpdb->get_results($wpdb->prepare("SELECT practice_id, COUNT(*) AS starts, COUNT(completed_at) AS completions, MAX(started_at) AS last_started_at FROM {$table} a INNER JOIN {$wpdb->users} u ON u.ID = a.user_id {$where} GROUP BY practice_id ORDER BY starts DESC, practice_id ASC LIMIT 20 OFFSET %d", $offset), ARRAY_A) ?: array();
}

/** Read-only projection; viewing an admin report must not normalize cycles or send notifications. */
function yoga_statistics_sadhana_row(array $row, string $today): array {
	$days = max(0, (int) $row['completed_days']);
	$status = (string) $row['status'];
	$yesterday = (new DateTimeImmutable($today))->modify('-1 day')->format('Y-m-d');
	$last = (string) ($row['last_marked_on'] ?? '');
	if ($status === 'active' && (($days > 0 && $last !== '' && $last < $yesterday) || ($days === 0 && (int) ($row['reset_count'] ?? 0) > 0 && $last === ''))) { $status = 'cancelled'; }
	$row['status'] = $status;
	$row['streak'] = in_array($status, array('active', 'completed'), true) && $last >= $yesterday && $last <= $today ? $days : 0;
	return $row;
}

function yoga_statistics_sadhanas(int $user_id = 0): array {
	$summary = array('available' => function_exists('yoga_sadhana_storage_exists') && yoga_sadhana_storage_exists(), 'active' => 0, 'completed' => 0, 'cancelled' => 0, 'days' => 0, 'streak' => 0, 'popular' => array(), 'rows' => array());
	if (!$summary['available']) { return $summary; }
	global $wpdb;
	$table = yoga_sadhana_table();
	$after = 0;
	$today = array();
	do {
		$where = $wpdb->prepare(' WHERE s.id > %d', $after);
		if ($user_id > 0) { $where .= $wpdb->prepare(' AND s.user_id = %d', $user_id); }
		$rows = $wpdb->get_results("SELECT s.* FROM {$table} s INNER JOIN {$wpdb->users} u ON u.ID = s.user_id {$where} ORDER BY s.id ASC LIMIT 200", ARRAY_A) ?: array();
		foreach ($rows as $raw) {
			$uid = (int) $raw['user_id'];
			if (!isset($today[$uid])) { $today[$uid] = (new DateTimeImmutable('now', yoga_statistics_user_timezone($uid)))->format('Y-m-d'); }
			$row = yoga_statistics_sadhana_row($raw, $today[$uid]);
			$status = $row['status'];
			if (isset($summary[$status])) { $summary[$status]++; }
			$summary['days'] += max(0, (int) $row['completed_days']);
			$summary['streak'] = max($summary['streak'], $row['streak']);
			$pid = (int) $row['practice_id'];
			if (!isset($summary['popular'][$pid])) { $summary['popular'][$pid] = array('practice_id' => $pid, 'starts' => 0, 'active' => 0, 'completed' => 0, 'days' => 0); }
			$summary['popular'][$pid]['starts']++;
			if (isset($summary['popular'][$pid][$status])) { $summary['popular'][$pid][$status]++; }
			$summary['popular'][$pid]['days'] += max(0, (int) $row['completed_days']);
			if ($user_id > 0) { $summary['rows'][] = $row; }
			$after = (int) $row['id'];
		}
	} while (count($rows) === 200);
	uasort($summary['popular'], static function (array $a, array $b): int { return ($b['starts'] <=> $a['starts']) ?: ($a['practice_id'] <=> $b['practice_id']); });
	return $summary;
}
