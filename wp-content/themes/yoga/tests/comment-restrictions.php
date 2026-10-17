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
function current_time($type = 'timestamp', ...$args) { return $type === 'mysql' ? date('Y-m-d H:i:s') : time(); }
function sanitize_key($s) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($s)); }
function sanitize_text_field($s) { return strip_tags($s); }
function esc_url_raw($s) { return $s; }
function wp_generate_uuid4() { static $id = 0; return 'notification-' . ++$id; }
function human_time_diff($from, $to) { return '3 дня'; }
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
function checked($value) { if ($value) { echo 'checked="checked"'; } }
function post_password_required() { return false; }
function get_post_type($id) { return 'post'; }
require dirname(__DIR__) . '/inc/comments.php';
require dirname(__DIR__) . '/inc/notifications.php';
require dirname(__DIR__) . '/inc/comment-restrictions.php';
require dirname(__DIR__) . '/inc/admin/comment-restrictions.php';
require dirname(__DIR__) . '/inc/ajax/comments.php';
require dirname(__DIR__) . '/inc/render/comments.php';
function check($ok, $label) { if (!$ok) { throw new RuntimeException($label); } }
function rejected($callback, $label) {
    try { $callback(); } catch (RuntimeException $e) { check($e->getCode() === 403, $label); return; }
    throw new RuntimeException($label . ' was accepted');
}
if (($argv[1] ?? '') === 'render-notifications') {
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    foreach (array('specification', 'header', 'lk') as $style) {
        echo '<link rel="stylesheet" href="' . get_template_directory_uri() . '/assets/css/templates/' . $style . '.css">';
    }
    echo '<style>body{margin:0;padding:0}.header-lk{display:block;margin:0}.qa-feed{width:100%;max-width:875px}.header-lk.qa-feed{max-width:590px}</style>';
    foreach (array(false, true) as $popup) {
        echo $popup ? '<div class="header-lk qa-feed">' : '<div class="lk-slide--notifications qa-feed">';
        foreach (array(strtotime('2030-10-21 23:59:00'), 'permanent') as $state) {
            $notification = array('id' => 'preview-' . $state, 'type' => $state === 'permanent' ? 'comment_banned' : 'comment_restricted', 'title' => $state === 'permanent' ? 'Комментирование отключено' : 'Комментирование ограничено', 'message' => $state === 'permanent' ? 'Вы неоднократно нарушили правила платформы. Вы больше не можете оставлять комментарии. Решение окончательное и не подлежит пересмотру.' : 'Вы временно не можете оставлять комментарии. Ограничения будут сняты 21 октября 2030 года, 23:59. Пожалуйста, ознакомьтесь с правилами платформы во избежание повторных ограничений.', 'url' => home_url('/my-account/'), 'created_at' => date('Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS), 'read_at' => $state === 'permanent' ? 'read' : '');
            if ($state !== 'permanent') {
                $notification['title'] = 'Комментирование временно ограничено';
                $notification['comment_warnings_remaining'] = 3;
                $notification['comment_restricted_until'] = (new DateTimeImmutable('2026-10-15 23:59', wp_timezone()))->getTimestamp();
            }
            yoga_render_comment_restriction_notification($notification, $popup);
        }
        echo '</div>';
    }
    exit;
}
if (($argv[1] ?? '') === 'render') {
    $meta[7]['_yoga_comment_restricted_until'] = time() + 86400;
    if (($argv[2] ?? '') === 'permanent') { $meta[7]['_yoga_comment_restricted_permanently'] = 1; }
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
foreach (array(false, true) as $permanent_mode) {
if ($permanent_mode) {
    $meta[7]['_yoga_comment_restricted_permanently'] = 1;
    $meta[7]['_yoga_comment_restricted_until'] = time() - 1;
    check(yoga_is_comment_restricted(7), 'Permanent restriction survives expiry of old date');
    check(!yoga_is_comment_restricted(0) && !yoga_is_comment_restricted(8), 'Permanent restriction isolated to its user');
}
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
if ($permanent_mode) {
    check(str_contains($html, 'Комментирование недоступно') && str_contains($html, 'Решение окончательное и не подлежит пересмотру.'), 'Permanent notice uses Figma copy');
    check(!str_contains($html, 'Ограничения будут сняты') && !str_contains($html, 'Задать вопрос') && !str_contains($html, 'правилами платформы'), 'Permanent notice has no expiry or actions');
}
}
unset($meta[7]['_yoga_comment_restricted_permanently']);
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
$_POST = array('yoga_comment_restriction_nonce' => 'valid', 'yoga_comment_restricted_permanently' => '1', 'yoga_comment_restricted_until' => 'not-a-date', 'yoga_comment_restriction_reason' => '<b>Permanent</b>');
yoga_save_comment_restriction_profile(7);
check(yoga_is_comment_permanently_restricted(7) && !isset($meta[7]['_yoga_comment_restricted_until']), 'Permanent save clears old date and ignores date input');
check($meta[7]['_yoga_comment_restriction_reason'] === 'Permanent', 'Permanent reason sanitized');
ob_start(); yoga_render_comment_restriction_profile((object) array('ID' => 7)); $profile = ob_get_clean();
check(str_contains($profile, 'name="yoga_comment_restricted_permanently" value="1" checked="checked"') && str_contains($profile, 'Комментирование заблокировано бессрочно.'), 'Profile restores permanent checkbox and status');
$saved = $meta[7]; $admin = false; $_POST['yoga_comment_restriction_remove'] = '1';
yoga_save_comment_restriction_profile(7); check($meta[7] === $saved, 'Unauthorized permanent change denied');
$admin = true; $_POST['yoga_comment_restriction_nonce'] = 'invalid';
yoga_save_comment_restriction_profile(7); check($meta[7] === $saved, 'Bad nonce preserves permanent restriction');
$_POST['yoga_comment_restriction_nonce'] = 'valid'; unset($_POST['yoga_comment_restricted_permanently'], $_POST['yoga_comment_restriction_remove']);
yoga_save_comment_restriction_profile(7); check($meta[7] === $saved, 'Invalid replacement date preserves permanent restriction');
$_POST['yoga_comment_restricted_until'] = '2030-10-21T23:59';
yoga_save_comment_restriction_profile(7);
check(!yoga_is_comment_permanently_restricted(7) && yoga_is_comment_restricted(7), 'Permanent can be replaced by a temporary restriction');
$_POST['yoga_comment_restricted_permanently'] = '1'; yoga_save_comment_restriction_profile(7);
$_POST['yoga_comment_restriction_remove'] = '1'; yoga_save_comment_restriction_profile(7);
check(!yoga_is_comment_restricted(7) && !isset($meta[7]['_yoga_comment_restricted_permanently']) && !isset($meta[7]['_yoga_comment_restriction_reason']), 'Remove wins over checked permanent mode and clears all restriction metadata');
check(yoga_user_can_manage_own_theme_comment(1) && str_contains(yoga_render_ajax_comment(1), 'your-comm__btn_edit'), 'Cancelling permanent restriction restores actions');
$meta[7]['yoga_notifications'] = array();
unset($meta[7]['_yoga_comment_warnings_remaining'], $meta[7]['_yoga_comment_restriction_count']);
$_POST = array('yoga_comment_restriction_nonce' => 'valid', 'yoga_comment_restricted_until' => '2030-10-21T23:59', 'yoga_comment_restriction_reason' => 'Internal note');
yoga_save_comment_restriction_profile(7);
$events = yoga_get_user_notifications(7);
check(count($events) === 1 && $events[0]['type'] === 'comment_restricted' && str_contains($events[0]['message'], '21.10.2030') && $events[0]['comment_warnings_remaining'] === 2, 'Temporary restriction creates dated notification with warning snapshot');
check(yoga_get_comment_restriction_count(7) === 1, 'New restriction increments block counter');
$temporary_event = $events[0];
check(!str_contains($events[0]['message'], 'Internal note'), 'Private reason is not exposed in notification');
yoga_save_comment_restriction_profile(7);
$_POST['yoga_comment_restriction_reason'] = 'Changed internal note'; yoga_save_comment_restriction_profile(7);
check(count(yoga_get_user_notifications(7)) === 1, 'Unchanged restriction and reason edits do not duplicate notification');
$_POST['yoga_comment_restricted_until'] = '2030-10-22T23:59'; yoga_save_comment_restriction_profile(7);
check(count(yoga_get_user_notifications(7)) === 2, 'Changed deadline creates a new notification');
check(yoga_get_comment_warnings_remaining(7) === 2, 'Extending current restriction does not consume a warning');
check(yoga_get_comment_restriction_count(7) === 1, 'Save and extension do not inflate block counter');
$_POST['yoga_comment_restricted_permanently'] = '1'; yoga_save_comment_restriction_profile(7);
$events = yoga_get_user_notifications(7);
$event = array_values(array_filter($events, static fn($e) => $e['type'] === 'comment_banned'))[0];
check(count($events) === 3 && $event['title'] === 'Комментирование отключено' && str_contains($event['message'], 'Решение окончательное'), 'Permanent notification uses Figma copy');
check(yoga_get_comment_restriction_count(7) === 2, 'Escalating to permanent restriction increments block counter');
ob_start(); yoga_render_comment_restriction_profile((object) array('ID' => 7)); $counter_profile = ob_get_clean();
check(str_contains($counter_profile, 'Количество блокировок: <strong>2</strong>') && str_contains($counter_profile, 'Осталось предупреждений: <strong>2</strong>'), 'Admin profile shows block counter and remaining warnings');
yoga_save_comment_restriction_profile(7); check(count(yoga_get_user_notifications(7)) === 3, 'Repeated permanent save does not duplicate notification');
check(count(yoga_get_unread_user_notifications(7)) === 3 && yoga_get_user_notifications(8) === array(), 'Notifications are unread and isolated to target user');
ob_start(); yoga_render_comment_restriction_notification($event, true); $event_html = ob_get_clean();
check(str_contains($event_html, 'lk-notifications-popup__item') && str_contains($event_html, 'data-notification-id="' . $event['id'] . '"') && str_contains($event_html, 'comment-restriction-notification-close.svg'), 'Bell notification is connected to read tracking and Figma icon');
yoga_mark_user_notifications_read(7, $event['id']);
check(count(yoga_get_unread_user_notifications(7)) === 2, 'Restriction notification can be marked read');
$_POST['yoga_comment_restriction_remove'] = '1'; yoga_save_comment_restriction_profile(7);
check(count(yoga_get_user_notifications(7)) === 3, 'Removing restriction keeps history without inventing an event');
unset($_POST['yoga_comment_restriction_remove']); yoga_save_comment_restriction_profile(7);
check(count(yoga_get_user_notifications(7)) === 4, 'Reapplying restriction after cancellation creates a fresh notification');
unset($_POST['yoga_comment_restricted_permanently']); yoga_save_comment_restriction_profile(7);
check(yoga_get_comment_warnings_remaining(7) === 1, 'New temporary restriction consumes next warning');
$_POST['yoga_comment_restriction_remove'] = '1'; yoga_save_comment_restriction_profile(7);
unset($_POST['yoga_comment_restriction_remove']); yoga_save_comment_restriction_profile(7);
check(yoga_get_comment_warnings_remaining(7) === 0 && !yoga_is_comment_permanently_restricted(7), 'Zero warnings does not automatically set permanent ban');
$_POST['yoga_comment_restriction_remove'] = '1'; yoga_save_comment_restriction_profile(7);
unset($_POST['yoga_comment_restriction_remove']); yoga_save_comment_restriction_profile(7);
check(yoga_get_comment_warnings_remaining(7) === 0, 'Counter cannot go below zero');
ob_start(); yoga_render_comment_restriction_notification($temporary_event); $feed_html = ob_get_clean();
check(str_contains($feed_html, '<article') && str_contains($feed_html, 'comment-restriction-feed-warning.svg') && str_contains($feed_html, 'правилами платформы</a>') && str_contains($feed_html, 'Разблокировка:') && str_contains($feed_html, '2 предупреждения'), 'Feed uses warning icon, working rules link, date and original counter snapshot');
check(!str_contains($feed_html, '0 предупреждений'), 'History does not change when counter decreases');
echo "PASS: temporary and permanent restrictions, expiry, cancellation, mode switching, user isolation, AJAX/WP/REST enforcement, hidden controls, authorization, notification creation/deduplication/read tracking and rendering.\n";
