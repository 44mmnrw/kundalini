<?php
/** One avatar field for the profile editor, account and WordPress avatar API. */
if (!defined('ABSPATH')) {
    exit;
}

function yoga_retire_duplicate_avatar_controls(): void {
    global $simple_local_avatars;
    if (!is_object($simple_local_avatars)) {
        return;
    }
    remove_action('show_user_profile', array($simple_local_avatars, 'edit_user_profile'));
    remove_action('edit_user_profile', array($simple_local_avatars, 'edit_user_profile'));
    remove_action('personal_options_update', array($simple_local_avatars, 'edit_user_profile_update'));
    remove_action('edit_user_profile_update', array($simple_local_avatars, 'edit_user_profile_update'));
    remove_filter('pre_get_avatar_data', array($simple_local_avatars, 'get_avatar_data'), 10);
}
add_action('init', 'yoga_retire_duplicate_avatar_controls', 100);

function yoga_load_existing_profile_avatar($value, $post_id) {
    if (!is_string($post_id) || !preg_match('/^user_(\d+)$/', $post_id, $match)) {
        return $value;
    }
    $user_id = (int) $match[1];
    return metadata_exists('user', $user_id, 'user_avatar') ? $value : yoga_get_user_avatar_id($user_id);
}
add_filter('acf/load_value/name=user_avatar', 'yoga_load_existing_profile_avatar', 10, 2);

function yoga_get_unified_avatar_data($args, $id_or_email) {
    if (!empty($args['force_default'])) {
        return $args;
    }
    $user_id = 0;
    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif ($id_or_email instanceof WP_User) {
        $user_id = (int) $id_or_email->ID;
    } elseif ($id_or_email instanceof WP_Comment) {
        $user_id = (int) $id_or_email->user_id;
        if (!$user_id) {
            $user = get_user_by('email', $id_or_email->comment_author_email);
            $user_id = $user ? (int) $user->ID : 0;
        }
    } elseif ($id_or_email instanceof WP_Post) {
        $user_id = (int) $id_or_email->post_author;
    } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        $user_id = $user ? (int) $user->ID : 0;
    }
    $avatar_id = yoga_get_user_avatar_id($user_id);
    if ($avatar_id > 0) {
        $size = max(1, (int) ($args['size'] ?? 96));
        $url = wp_get_attachment_image_url($avatar_id, array($size, $size));
        if ($url) {
            $args['url'] = $url;
            $args['found_avatar'] = true;
        }
    }
    return $args;
}
add_filter('pre_get_avatar_data', 'yoga_get_unified_avatar_data', 20, 2);
