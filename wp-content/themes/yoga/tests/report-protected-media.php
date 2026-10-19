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
            $pipes = array();
            $process = proc_open(array(PHP_BINARY, __FILE__, $mode, $dir), array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
            fclose($pipes[0]);
            $body = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            if (proc_close($process) !== 0) throw new RuntimeException($errors);
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
        echo "PASS: real route denies anonymous encoded PDF, confines paths, handles HEAD/ranges and enforces shared quota.\n";
    } finally {
        foreach (array('protocol test.pdf', 'second.pdf', 'log.json') as $name) unlink($dir . '/' . $name);
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
    return $field === 'practice_download' ? 'https://example.com/uploads/' . ($id === 102 ? 'second.pdf' : 'protocol%20test.pdf') : array();
}
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function get_current_user_id() { return $GLOBALS['mode'] === 'anonymous' ? 0 : 7; }
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
$wpdb = new class {
    public $postmeta = 'fixture_postmeta', $usermeta = 'fixture_usermeta';
    function prepare($sql, ...$args) { return $sql; }
    function get_var($sql) { return str_contains($sql, 'LOCK(') ? 1 : ($GLOBALS['mode'] === 'second' ? 56 : 55); }
    function get_col($sql) { return array(101, 102); }
    function esc_like($v) { return $v; }
};
require dirname(__DIR__) . '/inc/downloads/download-limits.php';
require dirname(__DIR__) . '/inc/security/protected-media.php';
if ($mode === 'resolve') {
    $url = 'https://example.com/uploads/protocol%20test.pdf?download=1';
    if (yoga_url_to_upload_path($url) !== realpath($fixture_dir . '/protocol test.pdf')) throw new RuntimeException('Encoded path failed');
    foreach (array('https://other.example/uploads/protocol%20test.pdf', 'https://example.com/uploads-evil/protocol%20test.pdf', 'https://example.com/uploads/%2e%2e/outside.pdf', 'https://example.com/uploads/protocol%00.pdf') as $bad) {
        if (yoga_url_to_upload_path($bad) !== '') throw new RuntimeException('Unsafe path accepted');
    }
    echo 'RESOLVE_OK'; exit;
}
$_GET['yoga_protected_media'] = $mode === 'second' ? 'second.pdf' : 'protocol test.pdf';
$_SERVER['REQUEST_METHOD'] = $mode === 'head' ? 'HEAD' : 'GET';
if ($mode === 'suffix') $_SERVER['HTTP_RANGE'] = 'bytes=-3';
if ($mode === 'invalid-range') $_SERVER['HTTP_RANGE'] = 'bytes=999-1000';
yoga_handle_protected_media_request();
