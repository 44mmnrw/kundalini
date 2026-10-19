<?php
/** Real file route in isolated child processes; only temporary fixtures are written. */
if ($argc < 3) {
    $dir = sys_get_temp_dir() . '/yoga-media-' . bin2hex(random_bytes(6));
    mkdir($dir);
    file_put_contents($dir . '/protocol test.pdf', 'PROTOCOL_ONE');
    file_put_contents($dir . '/second.pdf', 'PROTOCOL_TWO');
    file_put_contents($dir . '/log.json', '[]');
    try {
        $run = static function ($mode) use ($dir) {
            // Keep the Windows command line ASCII even when the checkout path is Cyrillic.
            $payload = base64_encode(json_encode(array(__FILE__, $mode, $dir), JSON_UNESCAPED_UNICODE));
            $bootstrap = '$argv=json_decode(base64_decode("' . $payload . '"),true);$argc=count($argv);require $argv[0];';
            $stdout = $dir . '/stdout.txt'; $stderr = $dir . '/stderr.txt';
            $pipes = array();
            $process = proc_open(array(PHP_BINARY, '-r', $bootstrap), array(0 => array('file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'r'), 1 => array('file', $stdout, 'w'), 2 => array('file', $stderr, 'w')), $pipes, null, null, array('bypass_shell' => true));
            if (!is_resource($process)) throw new RuntimeException('Cannot start route fixture');
            $deadline = microtime(true) + 10;
            do {
                $status = proc_get_status($process);
                if (!$status['running']) break;
                if (microtime(true) > $deadline) {
                    proc_terminate($process); proc_close($process);
                    throw new RuntimeException('Route fixture timed out: ' . $mode);
                }
                usleep(10000);
            } while (true);
            $closed = proc_close($process);
            $body = file_get_contents($stdout); $errors = file_get_contents($stderr);
            if (($status['exitcode'] >= 0 ? $status['exitcode'] : $closed) !== 0) throw new RuntimeException($errors);
            return array($body, $errors);
        };
        $check = static function ($condition, $message) { if (!$condition) throw new RuntimeException($message); };
        list($body, $status) = $run('anonymous');
        $check($body === '' && str_contains($status, 'STATUS=401'), 'anonymous encoded PDF must be denied');
        list($body, $status) = $run('resolve');
        $check($body === 'RESOLVE_OK', 'encoded URL, query string and confinement checks');
        list($body, $status) = $run('head');
        $check($body === '' && str_contains($status, 'STATUS=200') && json_decode(file_get_contents($dir . '/log.json'), true) === array(), 'HEAD neither sends bytes nor consumes quota');
        list($body, $status) = $run('invalid-range');
        $check($body === '' && str_contains($status, 'STATUS=416') && json_decode(file_get_contents($dir . '/log.json'), true) === array(), 'invalid range leaves quota intact');
        list($body, $status) = $run('get');
        $check($body === 'PROTOCOL_ONE' && str_contains($status, 'STATUS=200'), 'authenticated encoded PDF delivered');
        list($body, $status) = $run('suffix');
        $check($body === 'ONE' && str_contains($status, 'STATUS=206'), 'suffix range resumes counted protocol');
        list($body, $status) = $run('second');
        $check($body === '' && str_contains($status, 'STATUS=403'), 'second protocol denied at limit');
        $check(count(json_decode(file_get_contents($dir . '/log.json'), true)) === 1, 'one download recorded across GET and range');
        file_put_contents($dir . '/log.json', '[]');
        list($body, $status) = $run('legacy-anonymous');
        $check($body === '' && str_contains($status, 'STATUS=401'), 'old host URL cannot expose a local protocol anonymously');
        list($body, $status) = $run('legacy-get');
        $check($body === 'PROTOCOL_ONE' && str_contains($status, 'STATUS=200'), 'old host local protocol is counted for an authorized user');
        list($body, $status) = $run('legacy-suffix');
        $check($body === 'ONE' && str_contains($status, 'STATUS=206'), 'old host protocol range resumes without a new charge');
        list($body, $status) = $run('legacy-second');
        $check($body === '' && str_contains($status, 'STATUS=403'), 'old host second protocol cannot bypass quota');
        $check(count(json_decode(file_get_contents($dir . '/log.json'), true)) === 1, 'legacy URLs share the same quota log');
        file_put_contents($dir . '/log.json', '[]');
        list($body, $status) = $run('legacy-ajax');
        $check($body === 'PROTOCOL_ONE' && count(json_decode(file_get_contents($dir . '/log.json'), true)) === 1, 'authorized AJAX download also resolves the migrated URL and consumes quota');
        echo "PASS: current/legacy hosts, anonymous denial, HEAD/ranges, quota and path confinement.\n";
    } finally {
        foreach (array('protocol test.pdf', 'second.pdf', 'log.json', 'stdout.txt', 'stderr.txt') as $name) { if (is_file($dir . '/' . $name)) unlink($dir . '/' . $name); }
        rmdir($dir);
    }
    exit;
}
define('ABSPATH', __DIR__ . '/'); define('DAY_IN_SECONDS', 86400);
$mode = $argv[1]; $fixture_dir = $argv[2];
function add_action(...$args) {}
function wp_upload_dir() { return array('basedir' => $GLOBALS['fixture_dir'], 'baseurl' => 'https://example.com/uploads'); }
function wp_unslash($v) { return $v; }
function trailingslashit($v) { return rtrim($v, '/\\') . '/'; }
function get_post_type($id) { return 'practice'; }
function wp_get_post_parent_id($id) { return 0; }
function get_field($field, $id) {
    if ($field === 'tariff_download_limit') return 1;
    $host = str_starts_with($GLOBALS['mode'], 'legacy-') ? 'old.example.com' : 'example.com';
    return $field === 'practice_download' ? 'https://' . $host . '/uploads/' . ($id === 102 ? 'second.pdf' : 'protocol%20test.pdf') : array();
}
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function get_current_user_id() { return str_ends_with($GLOBALS['mode'], 'anonymous') ? 0 : 7; }
function is_user_logged_in() { return get_current_user_id() > 0; }
function wp_check_filetype($path) { return array('type' => 'application/pdf'); }
function status_header($status) { fwrite(STDERR, 'STATUS=' . $status . "\n"); }
function nocache_headers() {}
function yoga_viewer_has_full_practice_sections($user, $practice) { return $user > 0; }
function get_current_user_tariff($id) { return array('product_id' => 10, 'period' => 'month', 'access_end' => time() + DAY_IN_SECONDS); }
function calculate_access_duration($period) { return 30 * DAY_IN_SECONDS; }
function get_user_meta(...$args) { return json_decode(file_get_contents($GLOBALS['fixture_dir'] . '/log.json'), true); }
function update_user_meta($id, $key, $value) { return file_put_contents($GLOBALS['fixture_dir'] . '/log.json', json_encode($value)) !== false; }
function wp_cache_delete(...$args) {}
function check_ajax_referer(...$args) {}
function sanitize_file_name($value) { return $value; }
$wpdb = new class {
    public $postmeta = 'fixture_postmeta', $usermeta = 'fixture_usermeta';
    function prepare($sql, ...$args) { return $sql; }
    function get_var($sql) { return str_contains($sql, 'LOCK(') ? 1 : (str_ends_with($GLOBALS['mode'], 'second') ? 56 : 55); }
    function get_col($sql) { return array(101, 102); }
    function esc_like($v) { return $v; }
};
require dirname(__DIR__) . '/inc/downloads/download-limits.php';
require dirname(__DIR__) . '/inc/security/protected-media.php';
if ($mode === 'legacy-ajax') {
    require dirname(__DIR__) . '/inc/downloads/download-handler.php';
    $_GET['practice_id'] = 101;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    yoga_handle_practice_download();
}
if ($mode === 'resolve') {
    $url = 'https://example.com/uploads/protocol%20test.pdf?download=1';
    if (yoga_url_to_upload_path($url) !== realpath($fixture_dir . '/protocol test.pdf')) throw new RuntimeException('Encoded path failed');
    foreach (array('https://other.example/uploads/protocol%20test.pdf', 'https://example.com/uploads-evil/protocol%20test.pdf', 'https://example.com/uploads/%2e%2e/outside.pdf', 'https://example.com/uploads/protocol%00.pdf') as $bad) {
        if (yoga_url_to_upload_path($bad) !== '') throw new RuntimeException('Unsafe path accepted');
    }
    echo 'RESOLVE_OK'; exit;
}
$_GET['yoga_protected_media'] = str_ends_with($mode, 'second') ? 'second.pdf' : 'protocol test.pdf';
$_SERVER['REQUEST_METHOD'] = $mode === 'head' ? 'HEAD' : 'GET';
if (str_ends_with($mode, 'suffix')) $_SERVER['HTTP_RANGE'] = 'bytes=-3';
if ($mode === 'invalid-range') $_SERVER['HTTP_RANGE'] = 'bytes=999-1000';
yoga_handle_protected_media_request();
