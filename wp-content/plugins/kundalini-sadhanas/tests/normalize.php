<?php
/**
 * Behaviour checks for automatic Sadhana interruption.
 */

define('ABSPATH', __DIR__ . '/');

class WP_Error {
	public function __construct(public string $code = '', public string $message = '') {}
}

function absint($value): int { return abs((int) $value); }
function sanitize_key($value): string { return strtolower(preg_replace('/[^a-z0-9_-]/', '', (string) $value)); }
function __($value): string { return (string) $value; }
function add_action(): void {}
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function get_user_meta(): string { return 'Europe/Moscow'; }
function current_time(): string { return gmdate('Y-m-d H:i:s'); }
function wp_date($format, $timestamp, $timezone): string { return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone)->format($format); }
function get_the_title(): string { return 'Тестовая практика'; }
function get_permalink(): string { return 'https://example.com/practice/test/'; }
function home_url($path = '/'): string { return 'https://example.com' . $path; }
function get_post_type_archive_link(): string { return 'https://example.com/practices/'; }
function kundalini_sadhanas_channel_enabled(): bool { return false; }

$GLOBALS['sadhana_test_notifications'] = array();
function do_action($hook, ...$args): void {
	if ($hook === 'kundalini_sadhanas_notification') {
		$GLOBALS['sadhana_test_notifications'][] = $args;
	}
}

final class Sadhana_Test_DB {
	public string $prefix = 'wp_';
	public array $rows = array();

	public function prepare(string $query, ...$args): string {
		$index = 0;
		return (string) preg_replace_callback('/%[ds]/', static function ($match) use (&$index, $args): string {
			$value = $args[$index++] ?? '';
			return $match[0] === '%d' ? (string) (int) $value : "'" . addslashes((string) $value) . "'";
		}, $query);
	}

	public function get_var(string $query) {
		if (str_contains($query, 'GET_LOCK') || str_contains($query, 'RELEASE_LOCK')) {
			return 1;
		}
		if (preg_match('/user_id = (\d+) AND practice_id = (\d+) ORDER BY id DESC LIMIT 1/', $query, $matches)) {
			foreach (array_reverse($this->rows, true) as $id => $row) {
				if ((int) $row['user_id'] === (int) $matches[1] && (int) $row['practice_id'] === (int) $matches[2]) {
					return $id;
				}
			}
		}
		if (preg_match("/user_id = (\d+) AND practice_id = (\d+) AND status = 'active'/", $query, $matches)) {
			foreach (array_reverse($this->rows, true) as $id => $row) {
				if ((int) $row['user_id'] === (int) $matches[1] && (int) $row['practice_id'] === (int) $matches[2] && $row['status'] === 'active') {
					return $id;
				}
			}
		}
		return 0;
	}

	public function get_row(string $query): ?object {
		if (!preg_match('/WHERE id = (\d+)/', $query, $matches)) {
			return null;
		}
		$id = (int) $matches[1];
		return isset($this->rows[$id]) ? (object) $this->rows[$id] : null;
	}

	public function get_results(string $query): array {
		preg_match('/user_id = (\d+)/', $query, $user_match);
		preg_match("/status = '([^']+)'/", $query, $status_match);
		$user_id = (int) ($user_match[1] ?? 0);
		$status = (string) ($status_match[1] ?? '');
		return array_values(array_map(static fn($row) => (object) $row, array_filter(
			$this->rows,
			static fn($row) => (int) $row['user_id'] === $user_id && $row['status'] === $status
		)));
	}

	public function get_col(string $query): array {
		return array_keys(array_filter($this->rows, static fn($row) => $row['status'] === 'active'));
	}

	public function update($table, array $data, array $where) {
		$id = (int) ($where['id'] ?? 0);
		if (!isset($this->rows[$id])) {
			return false;
		}
		$this->rows[$id] = array_merge($this->rows[$id], $data);
		return 1;
	}
}

function sadhana_test_assert($condition, string $message): void {
	if (!$condition) {
		fwrite(STDERR, "FAIL: {$message}\n");
		exit(1);
	}
	echo "PASS: {$message}\n";
}

function sadhana_test_row(int $id, array $overrides = array()): array {
	$today = new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow'));
	return array_merge(array(
		'id' => $id,
		'user_id' => 7,
		'practice_id' => 11,
		'target_days' => 40,
		'completed_days' => 2,
		'status' => 'active',
		'active_key' => '7:11',
		'started_on' => $today->modify('-3 days')->format('Y-m-d'),
		'last_marked_on' => $today->modify('-2 days')->format('Y-m-d'),
		'completed_on' => null,
		'cancelled_on' => null,
		'reset_count' => 0,
		'created_at' => gmdate('Y-m-d H:i:s'),
		'updated_at' => gmdate('Y-m-d H:i:s'),
	), $overrides);
}

$wpdb = new Sadhana_Test_DB();
require dirname(__DIR__) . '/includes/core.php';

$wpdb->rows[1] = sadhana_test_row(1);
$result = yoga_sadhana_normalize(yoga_sadhana_row_from_db($wpdb->rows[1]));
sadhana_test_assert($result['status'] === 'cancelled', 'an overdue Sadhana is cancelled');
sadhana_test_assert($result['completed_days'] === 2, 'the reached day is preserved as history');
sadhana_test_assert($wpdb->rows[1]['active_key'] === null && $wpdb->rows[1]['cancelled_on'] !== null, 'the active slot is released and cancellation date is stored');
sadhana_test_assert(count($GLOBALS['sadhana_test_notifications']) === 1 && $GLOBALS['sadhana_test_notifications'][0][1] === 'interrupted', 'the interruption notification is sent once');
sadhana_test_assert($GLOBALS['sadhana_test_notifications'][0][2] === 'Садхана прервалась...', 'the interruption notification uses the requested title');
sadhana_test_assert(yoga_sadhana_get_user_rows(7, 'active') === array(), 'a newly cancelled Sadhana is omitted from the active list immediately');

$legacy_date = (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->modify('-1 day')->format('Y-m-d');
$wpdb->rows[2] = sadhana_test_row(2, array(
	'practice_id' => 12,
	'completed_days' => 0,
	'active_key' => '7:12',
	'started_on' => $legacy_date,
	'last_marked_on' => null,
	'reset_count' => 1,
));
$notifications_before_repair = count($GLOBALS['sadhana_test_notifications']);
$legacy_result = yoga_sadhana_normalize(yoga_sadhana_row_from_db($wpdb->rows[2]));
sadhana_test_assert($legacy_result['status'] === 'cancelled', 'a record reset by the old logic is repaired');
sadhana_test_assert($legacy_result['cancelled_on'] === $legacy_date, 'the original reset date becomes the cancellation date');
sadhana_test_assert(count($GLOBALS['sadhana_test_notifications']) === $notifications_before_repair, 'repair does not send a duplicate notification');

$wpdb->rows[3] = sadhana_test_row(3, array(
	'practice_id' => 13,
	'completed_days' => 0,
	'active_key' => '7:13',
	'started_on' => (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format('Y-m-d'),
	'last_marked_on' => null,
));
$wpdb->rows[4] = sadhana_test_row(4, array(
	'practice_id' => 14,
	'completed_days' => 1,
	'active_key' => '7:14',
	'last_marked_on' => (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->modify('-1 day')->format('Y-m-d'),
));
sadhana_test_assert(yoga_sadhana_normalize(yoga_sadhana_row_from_db($wpdb->rows[4]))['status'] === 'active', 'a Sadhana marked yesterday remains active');
sadhana_test_assert(yoga_sadhana_active_count(7) === 2, 'fresh and current Sadhanas remain in the counter while cancelled records are excluded');
sadhana_test_assert(yoga_sadhana_get_active(7, 12) === null, 'a repaired Sadhana is no longer returned as active');

$wpdb->rows[5] = sadhana_test_row(5, array('practice_id' => 15, 'status' => 'completed', 'completed_days' => 40));
sadhana_test_assert((yoga_sadhana_get_latest_completed(7, 15)['id'] ?? 0) === 5, 'a completed cycle remains visible before reset');
$wpdb->rows[6] = sadhana_test_row(6, array('practice_id' => 15, 'status' => 'cancelled'));
sadhana_test_assert(yoga_sadhana_get_latest_completed(7, 15) === null, 'a later reset returns the practice to its initial state');

echo "All Sadhana checks passed.\n";
