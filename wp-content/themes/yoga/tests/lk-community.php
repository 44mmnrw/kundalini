<?php
/** In-memory checks: account isolation, validation and authenticated review submission. */
define('ABSPATH', dirname(__DIR__, 4) . '/');
define('MB_IN_BYTES', 1048576);
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/class-wp-error.php';
$logged_in = true; $nonce = true; $posts = array(); $options = array(); $metadata = array(); $query_args = array(); $fail_insert = false;
class TestResponse extends Exception { public $success; public $data; function __construct($success, $data) { $this->success = $success; $this->data = $data; } }
function is_user_logged_in() { return $GLOBALS['logged_in']; }
function check_ajax_referer(...$args) { return $GLOBALS['nonce']; }
function get_current_user_id() { return 7; }
function wp_unslash($v) { return $v; }
function wp_slash($v) { return $v; }
function absint($v) { return abs((int) $v); }
function get_post($id) { return $GLOBALS['posts'][$id] ?? null; }
function sanitize_textarea_field($v) { return trim(strip_tags($v)); }
function is_email($v) { return filter_var($v, FILTER_VALIDATE_EMAIL); }
function is_wp_error($v) { return $v instanceof WP_Error; }
function yoga_ajax_error($message, ...$args) { throw new TestResponse(false, $message); }
function yoga_ajax_success($message, ...$args) { throw new TestResponse(true, $message); }
function add_option($key, $v, ...$args) { if (isset($GLOBALS['options'][$key])) { return false; } $GLOBALS['options'][$key] = $v; return true; }
function delete_option($key) { unset($GLOBALS['options'][$key]); }
function get_posts($args) {
 $GLOBALS['query_args'][] = $args;
 if ($args['post_type'] === 'shop_coupon') { return array(1,2,3,4); }
 return array_values(array_filter($GLOBALS['posts'], static function ($p) use ($args) { return (!isset($args['author']) || $p->post_author === $args['author']) && in_array($p->post_status, (array) $args['post_status'], true); }));
}
function wp_insert_post($data, ...$args) { if ($GLOBALS['fail_insert']) { return new WP_Error('fail'); } $id = $data['ID'] ?? count($GLOBALS['posts']) + 1; $GLOBALS['posts'][$id] = (object) array_merge($data, array('ID' => $id)); return $id; }
function update_post_meta($id, $key, $v) { $GLOBALS['metadata'][$id][$key] = $v; }
function get_post_meta($id, $key, ...$args) { return $GLOBALS['metadata'][$id][$key] ?? ''; }
function get_userdata($id) { return $id > 0 ? (object) array('user_email' => 'member@example.test', 'first_name' => 'Елена', 'display_name' => 'Елена') : false; }
class TestCouponDate extends DateTimeImmutable { function date_i18n($format) { return $this->format($format); } }
class WC_Coupon {
 public $id;
 function __construct($id) { $this->id = $id; }
 function get_email_restrictions() { return $this->id === 3 ? array('other@example.test') : ($this->id === 4 ? array() : array('MEMBER@example.test')); }
 function get_date_expires() { return $this->id === 2 ? new TestCouponDate('2020-01-01') : null; }
 function get_usage_limit() { return 0; }
 function get_usage_count() { return 0; }
 function get_usage_limit_per_user() { return $this->id === 1 && !empty($GLOBALS['coupon_used']) ? 1 : 0; }
 function get_used_by() { return array('7'); }
 function get_description() { return 'Действует на продление любой подписки и покупку отдельных курсов'; }
 function get_meta($key) { return 'Скидка за отзыв с фото'; }
 function get_amount() { return 15; }
 function get_discount_type() { return 'percent'; }
 function get_code() { return 'REVIEW15'; }
}
function get_template_directory_uri() { return '/wp-content/themes/yoga'; }
function esc_html($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_attr($v) { return esc_html($v); }
function esc_url($v) { return esc_html($v); }
function __($v, ...$args) { return $v; }
function _n($one, $many, $n, ...$args) { return $n === 1 ? $one : $many; }
function has_post_thumbnail($id) { return false; }
function get_the_date(...$args) { return '17.10.2026'; }
function yoga_get_lk_section_url($s) { return '?lk-section=' . $s; }
function wp_nonce_field(...$args) { echo '<input type="hidden" name="nonce" value="test">'; }
function admin_url($s) { return '/never-submit-fixture/' . $s; }
function home_url(...$args) { return 'https://example.test'; }
function wp_parse_url($u, $component) { return parse_url($u, $component); }
function wc_format_localized_decimal($v) { return $v; }
function get_template_part($path, $name = null, $args = array()) { require dirname(__DIR__) . '/' . $path . '.php'; }
require dirname(__DIR__) . '/inc/lk-community.php';
function check($ok, $label) { if (!$ok) { throw new RuntimeException($label); } }
function submit() { try { do_action('wp_ajax_yoga_submit_account_review'); } catch (TestResponse $r) { return $r; } throw new RuntimeException('No response'); }
$_POST = array('name' => 'Елена', 'email' => 'member@example.test', 'job' => 'Психолог', 'age' => '31', 'text' => str_repeat('Мой отзыв о занятиях. ', 6));
if (str_starts_with($argv[1] ?? '', 'render')) {
 $mode = $argv[1];
 if (str_contains($mode, 'filled')) { submit(); }
 if (str_contains($mode, 'denied')) { $posts[1]->post_status = 'draft'; }
 if (str_contains($mode, 'approved')) { $posts[1]->post_status = 'publish'; }
 echo '<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
 foreach (array('templates/specification','templates/lk','templates/lk-community') as $css) { echo '<link rel="stylesheet" href="' . get_template_directory_uri() . '/assets/css/' . $css . '.css">'; }
 echo '<style>html{font-size:16px}body{margin:0;padding:0;--font-family-base:Mulish,sans-serif}.qa{max-width:875px;margin:0 auto}.qa-mobile{max-width:320px}.qa > section{margin-bottom:50px}</style><main class="qa' . (str_contains($mode, 'mobile') ? ' qa-mobile' : '') . '">';
 $args = array('user_id' => 7);
 echo '<section>'; include dirname(__DIR__) . '/template-parts/lk-reviews.php'; echo '</section><section>';
 if (!str_contains($mode, 'filled')) { $args['user_id'] = 0; }
 include dirname(__DIR__) . '/template-parts/lk-promocodes.php';
 echo '</section></main><script src="' . get_template_directory_uri() . '/assets/js/lk-community.js"></script></html>';
 exit;
}
check(!is_wp_error(yoga_validate_account_review($_POST)), 'Valid review');
check(is_wp_error(yoga_validate_account_review(array_merge($_POST, array('name' => array('bad'))))), 'Reject array payload');
check(is_wp_error(yoga_validate_account_review(array_merge($_POST, array('text' => 'коротко')))), 'Enforce minimum length server-side');
check(is_wp_error(yoga_validate_account_review(array_merge($_POST, array('email' => 'invalid')))), 'Validate contact email');
$logged_in = false; check(!submit()->success && !$posts, 'Reject signed-out submission'); $logged_in = true;
$nonce = false; check(!submit()->success && !$posts, 'Reject invalid nonce'); $nonce = true;
$_FILES['photo'] = array('error' => UPLOAD_ERR_OK, 'name' => 'oversize.jpg', 'tmp_name' => '', 'size' => 11 * MB_IN_BYTES);
check(!submit()->success && !$posts && !$options, 'Reject oversized upload and release lock'); unset($_FILES['photo']);
$fail_insert = true; check(!submit()->success && !$options, 'Release lock after failed save'); $fail_insert = false;
check(submit()->success, 'Submit authenticated review');
check(count($posts) === 1 && $posts[1]->post_author === 7 && $posts[1]->post_status === 'pending', 'Correct author and moderation status');
check(!submit()->success && count($posts) === 1 && !$options, 'Prevent duplicates and release lock');
$_POST['review_id'] = 1;
check(!submit()->success, 'Cannot edit pending review');
$posts[1]->post_status = 'draft'; $posts[1]->post_author = 8;
check(!submit()->success, 'Cannot edit another account review');
$posts[1]->post_author = 7;
check(submit()->success && count($posts) === 1 && $posts[1]->post_status === 'pending', 'Resubmit own rejected review to moderation');
unset($_POST['review_id']);
check(!yoga_get_account_reviews(8), 'Reviews isolated by author');
$coupons = yoga_get_account_coupons(7);
check(count($coupons) === 2 && $coupons[0]['active'] && !$coupons[1]['active'], 'Only exact email matches; classify expired coupon');
$coupon_used = true; check(!yoga_get_account_coupons(7)[0]['active'], 'Per-account redemption limit');
check(!yoga_get_account_coupons(0), 'No coupons for missing account');
echo "PASS: validation, authentication, nonce, upload limit, moderation, duplicate submission, rejected review editing, lock cleanup, review and coupon isolation, coupon expiry and usage limits.\n";
