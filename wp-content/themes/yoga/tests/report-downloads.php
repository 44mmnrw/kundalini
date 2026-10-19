<?php
/** In-memory quota regressions; no database, filesystem mutation or WP bootstrap. */
define('ABSPATH', __DIR__ . '/');
define('DAY_IN_SECONDS', 86400);
function get_current_user_id() { return 7; }
function get_current_user_tariff($id) { return array('product_id' => 10, 'period' => 'month', 'access_end' => time() + DAY_IN_SECONDS); }
function calculate_access_duration($period) { return 30 * DAY_IN_SECONDS; }
function get_field($key, $id) { return $key === 'tariff_download_limit' ? 1 : 'https://example.com/protocol.pdf'; }
function get_post_type($id) { return 'practice'; }
function wp_upload_dir() { return array('baseurl' => 'https://example.com', 'basedir' => __DIR__); }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function yoga_viewer_has_full_practice_sections($user_id, $id) { return $user_id > 0; }
function get_user_meta($id, $key, $single) { return $GLOBALS['log']; }
function update_user_meta($id, $key, $value) { $GLOBALS['log'] = $value; return true; }
function wp_cache_delete($id, $group) { $GLOBALS['invalidated']++; }
$wpdb = new class {
    public $usermeta = 'test_usermeta', $locked = false, $available = true;
    function prepare($query, $key) { return $query; }
    function get_var($query) {
        if (str_contains($query, 'RELEASE_LOCK')) { $this->locked = false; return 1; }
        if (!$this->available || $this->locked) return 0;
        $this->locked = true;
        return 1;
    }
};
$GLOBALS['log'] = array(); $GLOBALS['invalidated'] = 0;
require dirname(__DIR__) . '/inc/downloads/download-limits.php';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
check(yoga_claim_practice_download(7, 101), 'first protocol is granted');
check(yoga_get_user_downloads_remaining(7) === 0, 'quota is consumed');
check(!yoga_claim_practice_download(7, 102), 'different protocol denied at limit');
check(yoga_claim_practice_download(7, 101), 'range/resume for the same protocol still allowed');
check(count($GLOBALS['log']) === 1, 'range/resume does not consume twice');
check(!$wpdb->locked, 'lock released after success and denial');
$wpdb->available = false;
check(!yoga_claim_practice_download(7, 101), 'unavailable concurrent lock fails closed');
check(count($GLOBALS['log']) === 1, 'failed lock leaves quota intact');
check($GLOBALS['invalidated'] === 3, 'quota refreshed under lock');
echo "PASS: protocol quota, resume, lock contention and lock release.\n";
