<?php
/** User-specific comment restrictions. Expiry is stored as a UTC timestamp. */
if (!defined('ABSPATH')) { exit; }

function yoga_get_comment_restriction_until(int $user_id): int {
    if ($user_id <= 0) { return 0; }
    $until = (int) get_user_meta($user_id, '_yoga_comment_restricted_until', true);
    return $until > time() ? $until : 0;
}

function yoga_is_comment_permanently_restricted(int $user_id): bool {
    return $user_id > 0 && (bool) get_user_meta($user_id, '_yoga_comment_restricted_permanently', true);
}

function yoga_is_comment_restricted(int $user_id): bool {
    return yoga_is_comment_permanently_restricted($user_id) || yoga_get_comment_restriction_until($user_id) > 0;
}

function yoga_comment_restriction_error(int $user_id): ?WP_Error {
    if (yoga_is_comment_permanently_restricted($user_id)) {
        return new WP_Error('yoga_comment_restricted', __('Комментирование недоступно.', 'yoga'), array('status' => 403));
    }
    $until = yoga_get_comment_restriction_until($user_id);
    if (!$until) { return null; }
    return new WP_Error('yoga_comment_restricted', sprintf(
        __('Комментирование ограничено до %s.', 'yoga'),
        wp_date('j F Y, H:i', $until)
    ), array('status' => 403));
}

// Cover the standard WordPress submission route as well as the theme's AJAX routes.
add_filter('preprocess_comment', static function (array $data): array {
    $error = yoga_comment_restriction_error((int) get_current_user_id());
    if ($error) { wp_die(esc_html($error->get_error_message()), '', array('response' => 403)); }
    return $data;
});
add_filter('rest_pre_insert_comment', static function ($comment, $request) {
    return yoga_comment_restriction_error((int) get_current_user_id()) ?: $comment;
}, 10, 2);

// REST deletion has no rest_pre_insert_comment hook, including for moderators.
add_filter('rest_pre_dispatch', static function ($result, $server, $request) {
    if (in_array($request->get_method(), array('POST', 'PUT', 'PATCH', 'DELETE'), true)
        && preg_match('#^/wp/v2/comments(?:/\d+)?/?$#', $request->get_route())) {
        return yoga_comment_restriction_error((int) get_current_user_id()) ?: $result;
    }
    return $result;
}, 10, 3);

add_filter('map_meta_cap', static function (array $caps, string $cap, int $user_id): array {
    if ($cap === 'edit_comment' && yoga_is_comment_restricted($user_id)) {
        return array('do_not_allow');
    }
    return $caps;
}, 10, 3);

// Also hide standard WordPress comment controls outside the theme renderer.
foreach (array('comment_reply_link', 'edit_comment_link') as $hook) {
    add_filter($hook, static function ($link) {
        return yoga_is_comment_restricted((int) get_current_user_id()) ? '' : $link;
    });
}

function yoga_render_comment_restriction_notice(): void {
    $permanent = yoga_is_comment_permanently_restricted((int) get_current_user_id());
    $until = yoga_get_comment_restriction_until((int) get_current_user_id());
    if (!$permanent && !$until) { return; }
    $nav = yoga_lk_sidebar_secondary_nav_urls();
    $contacts_url = $nav['contacts'] . '#section-form-questions';
    $rules_url = yoga_get_legal_document_url('user_agreement', home_url('/user-agreement/'));
    require get_template_directory() . '/template-parts/comment-restriction-notice.php';
}
