<?php
/** Prepare a profile photo only after the complete form has passed validation. */
function yoga_prepare_profile_avatar_upload() {
	$file = $_FILES['avatar'] ?? array();
	if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
		return 0;
	}
	if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
		return new WP_Error('profile_avatar_upload_failed', 'Не удалось загрузить фото. Изменения не сохранены.');
	}
	if ((int) ($file['size'] ?? 0) > 10 * MB_IN_BYTES) {
		return new WP_Error('profile_avatar_too_large', 'Фото должно быть не больше 10 МБ. Изменения не сохранены.');
	}
	$mimes = array('jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png');
	$type = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $mimes);
	if (!in_array($type['type'] ?? '', array('image/jpeg', 'image/png'), true)) {
		return new WP_Error('profile_avatar_type_invalid', 'Выберите фото в формате JPG или PNG. Изменения не сохранены.');
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	return media_handle_upload('avatar', 0, array(), array('test_form' => false, 'mimes' => $mimes));
}

function yoga_save_profile_avatar(int $user_id, int $attachment_id, bool $remove): bool {
	if ($attachment_id <= 0 && !$remove) {
		return true;
	}
	$old_avatar_id = yoga_get_user_avatar_id($user_id);
	if ($attachment_id > 0) {
		if (function_exists('update_field')) {
			update_field('user_avatar', $attachment_id, 'user_' . $user_id);
		} else {
			update_user_meta($user_id, 'user_avatar', $attachment_id);
		}
		if (yoga_get_user_avatar_id($user_id) !== $attachment_id) {
			return false;
		}
		yoga_assign_avatar_to_folder($attachment_id);
	} else {
		if (function_exists('delete_field')) {
			delete_field('user_avatar', 'user_' . $user_id);
		} else {
			delete_user_meta($user_id, 'user_avatar');
		}
		delete_user_meta($user_id, 'simple_local_avatar');
		if (yoga_get_user_avatar_id($user_id) > 0) {
			return false;
		}
	}
	if ($old_avatar_id > 0 && $old_avatar_id !== $attachment_id && get_post_type($old_avatar_id) === 'attachment') {
		wp_delete_attachment($old_avatar_id, true);
	}
	return true;
}
