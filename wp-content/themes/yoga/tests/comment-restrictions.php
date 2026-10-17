<?php
/** CLI regression checks; in-memory user metadata, no database or email. */
define('ABSPATH', dirname(__DIR__, 4) . '/');
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/class-wp-error.php';
$meta = array(); $admin = true; $current_id = 7; $writes = 0;
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
function esc_url($s) { return esc_html($s); }
function wp_die($message, $title = '', $args = array()) { throw new RuntimeException($message, $args['response'] ?? 0); }
function wp_send_json_error($message, $status = 200) { throw new RuntimeException($message, $status); }
function get_template_directory() { return dirname(__DIR__); }
function get_template_directory_uri() { return 'file:///' . str_replace('\\', '/', dirname(__DIR__)); }
function home_url($path) { return 'https://example.test' . $path; }
function yoga_lk_sidebar_secondary_nav_urls() { return array('contacts' => home_url('/contacts/')); }
function yoga_get_legal_document_url($type, $fallback) { return home_url('/rules/'); }
function yoga_render_threaded_ajax_comments_list($id) { echo '<div class="existing-comment">Старый комментарий</div>'; }
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
function check($ok, $label) { if (!$ok) { throw new RuntimeException($label); } }
function rejected($callback, $label) {
    try { $callback(); } catch (RuntimeException $e) { check($e->getCode() === 403, $label); return; }
    throw new RuntimeException($label . ' was accepted');
}
if (($argv[1] ?? '') === 'render') {
    $meta[7]['_yoga_comment_restricted_until'] = (new DateTimeImmutable('2026-10-15 18:00', wp_timezone()))->getTimestamp();
    echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="' . get_template_directory_uri() . '/assets/css/templates/specification.css"><link rel="stylesheet" href="' . get_template_directory_uri() . '/assets/css/templates/praktika.css"><style>body{padding:0}.praktika-comments{padding-top:0}</style>';
    $section = array('title' => 'Комментарии');
    require dirname(__DIR__) . '/template-parts/praktika-info/anchor_06.php';
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
rejected(static function () { apply_filters('preprocess_comment', array()); }, 'Standard WP submission');
check(is_wp_error(apply_filters('rest_pre_insert_comment', new stdClass(), null)), 'REST submission');
check(is_wp_error(yoga_insert_ajax_comment(array(), false)), 'Direct theme insertion without WP validation');
$section = array('title' => 'Комментарии');
ob_start(); require dirname(__DIR__) . '/template-parts/praktika-info/anchor_06.php'; $html = ob_get_clean();
check(str_contains($html, 'comment-restriction-notice') && !str_contains($html, 'id="custom-comment-form"') && str_contains($html, 'Старый комментарий'), 'Practice notice preserves existing comments');
ob_start(); require dirname(__DIR__) . '/comments.php'; $html = ob_get_clean();
check(str_contains($html, 'comment-restriction-notice') && !str_contains($html, 'id="custom-comment-form"'), 'Blog notice');
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
echo "PASS: restriction expiry, user isolation, AJAX/WP/REST enforcement, admin authorization, nonce, validation, timezone and notice rendering.\n";
