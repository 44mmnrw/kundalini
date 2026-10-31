<?php
/** Локальная интеграционная проверка: только TEMPORARY-таблицы, без загрузки WordPress. */
require __DIR__ . '/user-statistics.php';
$project_root = dirname(__DIR__, 4);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$config = file_get_contents($project_root . '/wp-config.php');
$credentials = array();
foreach (array('DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST') as $key) {
    if (!preg_match("~define\s*\(\s*'" . $key . "'\s*,\s*'((?:\\\\.|[^'\\\\])*)'\s*\)~", $config, $matches)) { throw new RuntimeException('Cannot read local database configuration'); }
    $credentials[$key] = str_replace(array("\\'", '\\\\'), array("'", '\\'), $matches[1]);
}
$host = explode(':', $credentials['DB_HOST']);
if (!in_array($host[0], array('localhost', '127.0.0.1'), true)) { throw new RuntimeException('Only local database testing is allowed'); }
try { $db = new mysqli($host[0], $credentials['DB_USER'], $credentials['DB_PASSWORD'], $credentials['DB_NAME'], isset($host[1]) ? (int) $host[1] : 3306); }
catch (Throwable $error) { throw new RuntimeException('Local database connection failed'); }
unset($credentials, $config);
$db->set_charset('utf8mb4');

class Statistics_Mysql_QA {
    public string $prefix;
    public string $users;
    public array $queries = array();
    function __construct(public mysqli $connection) { $this->prefix = 'stats_qa_' . bin2hex(random_bytes(4)) . '_'; $this->users = $this->prefix . 'users'; }
    function prepare($query, ...$args) {
        $index = 0;
        return preg_replace_callback('/%[ds]/', function ($match) use (&$index, $args) {
            $value = $args[$index++];
            return $match[0] === '%d' ? (string) (int) $value : "'" . $this->connection->real_escape_string((string) $value) . "'";
        }, $query);
    }
    function esc_like($name) { return $name; }
    function get_var($query) {
        if (str_starts_with($query, 'SHOW TABLES LIKE')) { return yoga_statistics_activity_table(); }
        $this->queries[] = $query;
        $row = $this->connection->query($query)->fetch_row(); return $row[0] ?? null;
    }
    function get_row($query, $format = ARRAY_A) { $this->queries[] = $query; return $this->connection->query($query)->fetch_assoc(); }
    function get_results($query, $format = ARRAY_A) { $this->queries[] = $query; return $this->connection->query($query)->fetch_all(MYSQLI_ASSOC); }
    function query($query) { $this->queries[] = $query; $this->connection->query($query); return $this->connection->affected_rows; }
}
$wpdb = $GLOBALS['wpdb'] = new Statistics_Mysql_QA($db);
function current_time($format, $gmt = false) { return gmdate('Y-m-d H:i:s'); }
function yoga_sadhana_table() { return $GLOBALS['wpdb']->prefix . 'yoga_sadhanas'; }
function count_users() { return array('total_users' => 2); }
function wp_unslash($value) { return stripslashes($value); }
function is_user_logged_in() { return $GLOBALS['qa_logged_in'] ?? true; }
function check_ajax_referer(...$args) { return $GLOBALS['qa_nonce_valid'] ?? true; }
function get_current_user_id() { return $GLOBALS['qa_user_id'] ?? 1; }
function get_post_type($id) { return in_array($id, array(10, 11), true) ? 'practice' : 'post'; }
function get_post_status($id) { return $id === 11 ? 'draft' : 'publish'; }
function yoga_viewer_has_full_practice_sections(...$args) { return $GLOBALS['qa_full_access'] ?? true; }
function yoga_practice_is_fully_open_for_guests($id) { return $GLOBALS['qa_open_practice'] ?? false; }
function yoga_guest_practice_section_filter_enabled() { return $GLOBALS['qa_section_filter'] ?? true; }
class Statistics_QA_Response extends Exception { function __construct(public bool $success, public array $data, public int $status) {} }
function wp_send_json_error($data, $status = 400) { throw new Statistics_QA_Response(false, $data, $status); }
function wp_send_json_success($data) { throw new Statistics_QA_Response(true, $data, 200); }
function qa_ajax(array $post): Statistics_QA_Response {
    $_POST = $post;
    try { yoga_statistics_ajax_activity(); } catch (Statistics_QA_Response $response) { return $response; }
    throw new RuntimeException('Missing response');
}

try {
    $table = yoga_statistics_activity_table();
    $source = file_get_contents(dirname(__DIR__) . '/inc/statistics/activity.php');
    statistics_assert((bool) preg_match('~dbDelta\("(CREATE TABLE .*?)"\);~s', $source, $match), 'schema extracted from implementation');
    $sql = str_replace(array('CREATE TABLE', '{$table}', '{$charset}'), array('CREATE TEMPORARY TABLE', $table, 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'), $match[1]);
    $db->query($sql);
    $db->query("CREATE TEMPORARY TABLE {$wpdb->users} (ID bigint unsigned PRIMARY KEY)");
    $db->query("INSERT INTO {$wpdb->users} VALUES (1),(2)");
    $first = '11111111-1111-4111-8111-111111111111';
    $second = '22222222-2222-4222-8222-222222222222';
    $third = '33333333-3333-4333-8333-333333333333';
    statistics_assert(yoga_statistics_activity_summary()['starts'] === 0, 'no start recorded without player action');
    statistics_assert(yoga_statistics_record_activity(1, 10, $second, 'complete') instanceof WP_Error, 'completion before start rejected');
    for ($i=0; $i<3; $i++) { statistics_assert(is_array(yoga_statistics_record_activity(1, 10, $first, 'start')), 'start accepted'); }
    statistics_assert(yoga_statistics_activity_summary(1)['starts'] === 1, 'retries and reloads do not add starts');
    for ($i=0; $i<3; $i++) { statistics_assert(yoga_statistics_record_activity(1, 10, $first, 'complete')['completed'], 'completion is idempotent'); }
    statistics_assert(yoga_statistics_activity_summary(1)['completions'] === 1, 'duplicate finish counted once');
    statistics_assert(yoga_statistics_record_activity(1, 11, $first, 'start') instanceof WP_Error, 'one token cannot change practice');
    statistics_assert(yoga_statistics_record_activity(2, 10, $first, 'complete') instanceof WP_Error, 'another user cannot complete someone else session');
    yoga_statistics_record_activity(2, 11, $second, 'start');
    statistics_assert(yoga_statistics_activity_summary(2)['starts'] === 1 && yoga_statistics_activity_summary(1)['starts'] === 1, 'users isolated');
    $post = array('practice_id' => 10, 'session_key' => $third, 'event' => 'start', 'user_id' => 2);
    $GLOBALS['qa_nonce_valid'] = false;
    statistics_assert(qa_ajax($post)->status === 403, 'invalid nonce rejected');
    $GLOBALS['qa_nonce_valid'] = true; $GLOBALS['qa_logged_in'] = false;
    statistics_assert(qa_ajax($post)->status === 403, 'guests rejected');
    $GLOBALS['qa_logged_in'] = true; $GLOBALS['qa_full_access'] = false;
    statistics_assert(qa_ajax($post)->status === 403, 'preview access cannot inflate counts');
    $GLOBALS['qa_open_practice'] = true;
    statistics_assert(yoga_statistics_can_track_practice(1, 10), 'fully open practice counts without a tariff');
    statistics_assert(!yoga_statistics_can_track_practice(0, 10), 'open practice does not track guests');
    $GLOBALS['qa_open_practice'] = false; $GLOBALS['qa_section_filter'] = false;
    statistics_assert(yoga_statistics_can_track_practice(1, 10), 'disabled content restrictions allow complete practices');
    $GLOBALS['qa_section_filter'] = true; $GLOBALS['qa_full_access'] = true;
    statistics_assert(qa_ajax(array_merge($post, array('practice_id' => 11)))->status === 403, 'draft practice rejected');
    statistics_assert(qa_ajax($post)->success, 'valid start uses current account');
    statistics_assert(yoga_statistics_activity_summary(1)['starts'] === 2 && yoga_statistics_activity_summary(2)['starts'] === 1, 'posted user ID is ignored');
    yoga_statistics_record_activity(99, 10, '99999999-9999-4999-8999-999999999999', 'start');
    statistics_assert(yoga_statistics_activity_summary()['starts'] === 3, 'removed users excluded from activity reports');
    $popular = yoga_statistics_popular_practices();
    statistics_assert((int) $popular[0]['practice_id'] === 10 && (int) $popular[0]['starts'] === 2, 'popular practices ranked from stored starts');

    $sadhana_table = yoga_sadhana_table();
    $source = file_get_contents($project_root . '/wp-content/plugins/kundalini-sadhanas/includes/core.php');
    statistics_assert((bool) preg_match('~\$sql = "(CREATE TABLE .*?)";~s', $source, $match), 'sadhana schema extracted');
    $sql = str_replace(array('CREATE TABLE', '{$table}', '{$charset_collate}'), array('CREATE TEMPORARY TABLE', $sadhana_table, 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'), $match[1]);
    $db->query($sql);
    $GLOBALS['stats_sadhana_available'] = true;
    $GLOBALS['stats_timezone'] = 'Europe/Moscow';
    $today = new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow'));
    $dates = array($today->format('Y-m-d'), $today->modify('-1 day')->format('Y-m-d'), $today->modify('-2 days')->format('Y-m-d'));
    $fixtures = array(array('active',4,$dates[1],0),array('active',4,$dates[2],0),array('completed',7,$dates[0],0),array('active',0,null,0),array('active',0,null,1));
    foreach ($fixtures as $fixture) {
        $last = $fixture[2] === null ? 'NULL' : "'" . $fixture[2] . "'";
        $db->query("INSERT INTO {$sadhana_table} (user_id,practice_id,target_days,completed_days,status,started_on,last_marked_on,reset_count,created_at,updated_at) VALUES (1,10,7,{$fixture[1]},'{$fixture[0]}','{$dates[2]}',{$last},{$fixture[3]},UTC_TIMESTAMP(),UTC_TIMESTAMP())");
    }
    $before = $db->query("SELECT * FROM {$sadhana_table}")->fetch_all(MYSQLI_ASSOC);
    $start_queries = count($wpdb->queries);
    $sadhanas = yoga_statistics_sadhanas(1);
    statistics_assert($sadhanas['active'] === 2 && $sadhanas['completed'] === 1 && $sadhanas['cancelled'] === 2, 'effective cycle states include missed days and legacy reset');
    statistics_assert($sadhanas['days'] === 15 && $sadhanas['streak'] === 7, 'historical days retained and current maximum streak shown');
    statistics_assert($before === $db->query("SELECT * FROM {$sadhana_table}")->fetch_all(MYSQLI_ASSOC), 'report does not normalize or modify cycles');
    foreach (array_slice($wpdb->queries, $start_queries) as $query) { statistics_assert(str_starts_with($query, 'SELECT'), 'report only executes SELECT'); }
    ob_start(); yoga_statistics_render_admin_page(); $overview = ob_get_clean();
    ob_start(); yoga_statistics_render_user_profile((object) array('ID'=>1)); $profile = ob_get_clean();
    statistics_assert(str_contains($overview, 'Популярные садханы') && str_contains($profile, 'История садхан'), 'both admin screens render from real queries');
    echo "PASS: real MySQL temporary tables, atomic/idempotent activity writes, AJAX authorization, user isolation, read-only sadhanas and admin rendering.\n";
} finally { $db->close(); }
