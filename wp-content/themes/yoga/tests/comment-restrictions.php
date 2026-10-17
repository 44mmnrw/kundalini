<?php
/** CLI regression checks; in-memory user metadata, no database or email. */
define('ABSPATH', dirname(__DIR__, 4) . '/');
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/class-wp-error.php';
$meta = array(); $admin = true; $current_id = 7; $writes = 0;
define('DAY_IN_SECONDS', 86400);
class WP_Comment {
    public $comment_ID, $user_id, $comment_parent, $comment_post_ID = 42;
    public $comment_author = 'Автор', $comment_author_email = 'author@example.test';
    public $comment_content = 'Старый комментарий', $comment_date_gmt = '2020-01-01 00:00:00';
    function __construct($id, $user_id, $parent = 0) {
        $this->comment_ID = $id; $this->user_id = $user_id; $this->comment_parent = $parent;
    }
}
class WP_Post { public $post_type = 'practice'; }
$fixtures = array(1 => new WP_Comment(1, 7), 2 => new WP_Comment(2, 8), 3 => new WP_Comment(3, 7, 2));
function get_comment($id) { return $GLOBALS['fixtures'][$id] ?? null; }
function get_comments($args) { return array_values($GLOBALS['fixtures']); }
function get_post($id) { return new WP_Post(); }
function wp_get_current_user() { return (object) array('user_email' => 'viewer@example.test'); }
function yoga_get_user_public_name($id) { return 'Автор ' . $id; }
function yoga_get_user_comment_role_badge_data($id) { return array(); }
function yoga_get_user_avatar_html(...$args) { return ''; }
function current_time(...$args) { return time(); }
function comment_ID() { echo $GLOBALS['comment']->comment_ID; }
function comment_text() { echo esc_html($GLOBALS['comment']->comment_content); }
function wp_update_comment($data) { $GLOBALS['writes']++; return 1; }
function wp_delete_comment($id, $force = false) { $GLOBALS['writes']++; return true; }
function wp_send_json_success($data) { throw new RuntimeException('AJAX success', 200); }
function get_user_meta($id, $key, $single = true) { return $GLOBALS['meta'][$id][$key] ?? ''; }
function update_user_meta($id, $key, $value) { $GLOBALS['meta'][$id][$key] = $value; }
function delete_user_meta($id, $key) { unset($GLOBALS['meta'][$id][$key]); }
function current_user_can($cap, ...$args) { return $GLOBALS['admin']; }
function get_current_user_id() { return $GLOBALS['current_id']; }
function is_user_logged_in() { return get_current_user_id() > 0; }
function wp_timezone() { return new DateTimeZone('Europe/Moscow'); }
function wp_date($format, $timestamp) { return (new DateTimeImmutable('@' . $timestamp))->setTimezone(wp_timezone())->format($format); }
function __($s, $domain = '') { return $s; }
function wp_unslash($s) { return stripslashes($s); }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid'; }
function sanitize_textarea_field($s) { return strip_tags($s); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function esc_html($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function esc_attr($s) { return esc_html($s); }
function esc_textarea($s) { return esc_html($s); }
function esc_url($s) { return esc_html($s); }
function wp_die($message, $title = '', $args = array()) { throw new RuntimeException($message, $args['response'] ?? 0); }
function wp_send_json_error($message, $status = 200) { throw new RuntimeException($message, $status); }
function get_template_directory() { return dirname(__DIR__); }
function get_template_directory_uri() { return 'file:///' . str_replace('\\', '/', dirname(__DIR__)); }
function home_url($path) { return 'https://example.test' . $path; }
function yoga_lk_sidebar_secondary_nav_urls() { return array('contacts' => home_url('/contacts/')); }
function yoga_get_legal_document_url($type, $fallback) { return home_url('/rules/'); }
function get_the_ID() { return 42; }
function wp_nonce_field(...$args) {}
function esc_attr_e($s, $domain = '') { echo esc_attr($s); }
function esc_html_e($s, $domain = '') { echo esc_html($s); }
function post_password_required() { return false; }
function get_post_type($id) { return 'post'; }
require dirname(__DIR__) . '/inc/comments.php';
require dirname(__DIR__) . '/inc/comment-restrictions.php';
require dirname(__DIR__) . '/inc/admin/comment-restrictions.php';
require dirname(__DIR__) . '/inc/ajax/comments.php';
require dirname(__DIR__) . '/inc/render/comments.php';
function check($ok, $label) { if (!$ok) { throw new RuntimeException($label); } }
function rejected($callback, $label) {
    try { $callback(); } catch (RuntimeException $e) { check($e->getCode() === 403, $label); return; }
    throw new RuntimeException($label . ' was accepted');
}
if (($argv[1] ?? '') === 'render') {
    $meta[7]['_yoga_comment_restricted_until'] = time() + 86400;
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="' . get_template_directory_uri() . '/assets/css/templates/specification.css"><link rel="stylesheet" href="' . get_template_directory_uri() . '/assets/css/templates/praktika.css"><style>body{padding:0}.praktika-info{width:100%;max-width:793px}.praktika-comments{padding-top:0}</style><main class="praktika-info">';
    $section = array('title' => 'Комментарии');
    require dirname(__DIR__) . '/template-parts/praktika-info/anchor_06.php';
    echo '</main>';
    exit;
}
check(yoga_get_comment_restriction_until(7) === 0, 'No restriction');
$meta[7]['_yoga_comment_restricted_until'] = time() - 1;
check(yoga_get_comment_restriction_until(7) === 0, 'Expired restriction');
$meta[7]['_yoga_comment_restricted_until'] = time() + 3600;
check(yoga_comment_restriction_error(7) instanceof WP_Error, 'Active restriction');
check(yoga_get_comment_restriction_until(0) === 0 && yoga_get_comment_restriction_until(8) === 0, 'Independent users and guests');
$_POST = array('comment_security' => 'valid', 'security' => 'valid');
rejected('handle_custom_comment', 'AJAX comment');
rejected('handle_comment_reply', 'AJAX reply');
$_POST += array('comment_id' => 1, 'content' => 'Изменённый комментарий');
rejected('handle_comment_update', 'AJAX edit own comment');
rejected('handle_comment_delete', 'AJAX delete own comment');
$_POST['comment_id'] = 3;
rejected('handle_comment_update', 'AJAX edit own reply');
rejected('handle_comment_delete', 'AJAX delete own reply');
check($writes === 0, 'Restricted AJAX requests do not mutate comments');
check(!yoga_user_can_manage_own_theme_comment(1), 'Restriction overrides ownership');
check(apply_filters('map_meta_cap', array('edit_posts'), 'edit_comment', 7) === array('do_not_allow'), 'Standard WP edit/delete denied');
check(apply_filters('map_meta_cap', array('edit_posts'), 'edit_comment', 8) === array('edit_posts'), 'Other users keep their permissions');
foreach (array('POST', 'PUT', 'PATCH', 'DELETE') as $method) {
    $request = new class($method) {
        private $method;
        function __construct($method) { $this->method = $method; }
        function get_method() { return $this->method; }
        function get_route() { return '/wp/v2/comments/1'; }
    };
    check(is_wp_error(apply_filters('rest_pre_dispatch', null, null, $request)), 'REST ' . $method . ' blocked');
}
$request = new class {
    function get_method() { return 'GET'; }
    function get_route() { return '/wp/v2/comments/1'; }
};
check(apply_filters('rest_pre_dispatch', null, null, $request) === null, 'REST reading stays available');
check(apply_filters('comment_reply_link', '<a>Reply</a>') === '' && apply_filters('edit_comment_link', '<a>Edit</a>') === '', 'Standard WP action links hidden');
rejected(static function () { apply_filters('preprocess_comment', array()); }, 'Standard WP submission');
check(is_wp_error(apply_filters('rest_pre_insert_comment', new stdClass(), null)), 'REST submission');
check(is_wp_error(yoga_insert_ajax_comment(array(), false)), 'Direct theme insertion without WP validation');
$section = array('title' => 'Комментарии');
ob_start(); require dirname(__DIR__) . '/template-parts/praktika-info/anchor_06.php'; $html = ob_get_clean();
check(str_contains($html, 'comment-restriction-notice') && !str_contains($html, 'id="custom-comment-form"') && str_contains($html, 'Старый комментарий'), 'Practice notice preserves existing comments');
foreach (array('your-comm__btn_edit', 'your-comm__btn_del', 'class="answer-btn"', 'id="edit-form-', 'id="reply-form-') as $control) {
    check(!str_contains($html, $control), 'Practice restricted control absent: ' . $control);
}
ob_start(); require dirname(__DIR__) . '/comments.php'; $html = ob_get_clean();
check(str_contains($html, 'comment-restriction-notice') && !str_contains($html, 'id="custom-comment-form"'), 'Blog notice');
check(!str_contains($html, 'your-comm__btn') && !str_contains($html, 'id="reply-form-') && !str_contains($html, 'id="edit-form-'), 'Blog actions and forms hidden');
$_POST = array('yoga_comment_restriction_nonce' => 'valid', 'yoga_comment_restricted_until' => '2030-10-15T18:30', 'yoga_comment_restriction_reason' => '<b>Reason</b>');
yoga_save_comment_restriction_profile(7);
check(wp_date('Y-m-d\TH:i', $meta[7]['_yoga_comment_restricted_until']) === '2030-10-15T18:30', 'Site timezone roundtrip');
check($meta[7]['_yoga_comment_restriction_reason'] === 'Reason', 'Reason sanitization');
$saved = $meta[7]; $admin = false; $_POST['yoga_comment_restricted_until'] = '';
yoga_save_comment_restriction_profile(7); check($meta[7] === $saved, 'Unauthorized save denied');
$admin = true; $_POST['yoga_comment_restriction_nonce'] = 'invalid';
yoga_save_comment_restriction_profile(7); check($meta[7] === $saved, 'Bad nonce denied');
$_POST['yoga_comment_restriction_nonce'] = 'valid'; $_POST['yoga_comment_restricted_until'] = '2030-02-31T18:30';
$errors = new WP_Error(); do_action('user_profile_update_errors', $errors, true, (object) array('ID' => 7));
check($errors->has_errors(), 'Invalid date reported'); yoga_save_comment_restriction_profile(7); check($meta[7] === $saved, 'Invalid date preserves restriction');
$_POST['yoga_comment_restriction_remove'] = '1'; yoga_save_comment_restriction_profile(7);
check(yoga_get_comment_restriction_until(7) === 0 && !isset($meta[7]['_yoga_comment_restriction_reason']), 'Manual removal');
check(!is_wp_error(apply_filters('rest_pre_insert_comment', new stdClass(), null)), 'Access restored');
ob_start(); require dirname(__DIR__) . '/template-parts/praktika-info/anchor_06.php'; $html = ob_get_clean();
check(str_contains($html, 'id="custom-comment-form"') && !str_contains($html, 'comment-restriction-notice'), 'Form restored');
check(str_contains($html, 'your-comm__btn_edit') && str_contains($html, 'your-comm__btn_del') && str_contains($html, 'class="answer-btn"') && str_contains($html, 'id="edit-form-1"') && str_contains($html, 'id="reply-form-2"'), 'Actions and forms restored');
check(yoga_user_can_manage_own_theme_comment(1) && !yoga_user_can_manage_own_theme_comment(2), 'Ownership still enforced after restriction removal');
check(apply_filters('rest_pre_dispatch', null, null, new class {
    function get_method() { return 'DELETE'; }
    function get_route() { return '/wp/v2/comments/1'; }
}) === null, 'REST deletion gate released');
check(apply_filters('comment_reply_link', '<a>Reply</a>') === '<a>Reply</a>', 'Standard WP reply link restored');
foreach (array('handle_comment_update', 'handle_comment_delete') as $handler) {
    try { $_POST = array('security' => 'valid', 'comment_id' => 1, 'content' => 'Изменённый комментарий'); $handler(); }
    catch (RuntimeException $e) { check($e->getCode() === 200, 'Unrestricted AJAX action succeeds'); }
}
check($writes === 2, 'Own edit/delete restored after restriction removal');
$meta[7]['_yoga_comment_restricted_until'] = time() - 1;
check(yoga_user_can_manage_own_theme_comment(1), 'Expired restriction restores management');
check(str_contains(yoga_render_ajax_comment(1), 'your-comm__btn_edit'), 'Expired restriction restores controls');
echo "PASS: restriction expiry, user isolation, AJAX/WP/REST create/reply/edit/delete enforcement, hidden controls, restored ownership permissions, admin authorization, nonce, validation, timezone and notice rendering.\n";
