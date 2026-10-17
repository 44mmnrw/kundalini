<?php
/** Отзывы и персональные промокоды личного кабинета. */
if (!defined('ABSPATH')) { exit; }

function yoga_lk_section_icon(string $name, string $class = ''): void {
	echo '<img class="' . esc_attr($class) . '" src="' . esc_url(get_template_directory_uri() . '/assets/svg/lk-' . $name . '.svg') . '" alt="" aria-hidden="true">';
}

function yoga_lk_review_age(int $age): string {
	$last = $age % 10; $teens = $age % 100;
	return $age . ' ' . (($teens >= 11 && $teens <= 14) ? 'лет' : ($last === 1 ? 'год' : ($last >= 2 && $last <= 4 ? 'года' : 'лет')));
}

add_action('init', function (): void {
	register_post_type('yoga_review', array(
		'labels' => array('name' => 'Отзывы пользователей', 'singular_name' => 'Отзыв', 'edit_item' => 'Редактировать отзыв', 'add_new_item' => 'Добавить отзыв'),
		'public' => false, 'show_ui' => true, 'show_in_rest' => false,
		'menu_icon' => 'dashicons-testimonial', 'supports' => array('title', 'editor', 'thumbnail'),
		'capabilities' => array_fill_keys(array('edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts'), 'manage_options'),
	));
});

function yoga_get_account_reviews(int $user_id): array {
	return $user_id > 0 ? get_posts(array('post_type' => 'yoga_review', 'author' => $user_id, 'post_status' => array('pending', 'publish', 'draft', 'private'), 'posts_per_page' => -1)) : array();
}

function yoga_validate_account_review(array $input) {
	$data = array();
	foreach (array('name', 'email', 'job', 'age', 'text') as $key) {
		if (isset($input[$key]) && !is_scalar($input[$key])) {
			return new WP_Error('invalid_review', 'Проверьте заполнение полей.');
		}
		$data[$key] = sanitize_textarea_field((string) ($input[$key] ?? ''));
	}
	if ($data['name'] === '' || mb_strlen($data['name']) > 120 || !is_email($data['email'])) {
		return new WP_Error('invalid_contact', 'Укажите имя и корректный электронный адрес.');
	}
	if (mb_strlen($data['text']) < 80 || mb_strlen($data['text']) > 10000) {
		return new WP_Error('invalid_text', 'Текст отзыва должен содержать от 80 до 10 000 символов.');
	}
	if (mb_strlen($data['job']) > 200 || ($data['age'] !== '' && (!ctype_digit($data['age']) || (int) $data['age'] < 1 || (int) $data['age'] > 120))) {
		return new WP_Error('invalid_details', 'Проверьте профессию и возраст.');
	}
	return $data;
}

add_action('wp_ajax_yoga_submit_account_review', function (): void {
	if (!is_user_logged_in() || !check_ajax_referer('yoga_account_review', 'nonce', false)) {
		yoga_ajax_error('Обновите страницу и войдите в свой аккаунт.', 'forbidden', 403);
		return;
	}
	$user_id = get_current_user_id();
	$review_id = isset($_POST['review_id']) && is_scalar($_POST['review_id']) ? absint($_POST['review_id']) : 0;
	if ($review_id) {
		$review = get_post($review_id);
		if (!$review || $review->post_type !== 'yoga_review' || (int) $review->post_author !== $user_id || $review->post_status !== 'draft') {
			yoga_ajax_error('Этот отзыв нельзя редактировать.', 'forbidden', 403); return;
		}
	}
	$data = yoga_validate_account_review(wp_unslash($_POST));
	if (is_wp_error($data)) { yoga_ajax_error($data->get_error_message()); return; }
	// A unique option serializes concurrent submissions from the same account.
	$lock = 'yoga_review_submit_' . $user_id;
	if (!add_option($lock, time(), '', false)) {
		yoga_ajax_error('Отзыв уже отправляется. Пожалуйста, подождите.'); return;
	}
	$save = static function () use ($user_id, $data, $review_id) {
		if (!$review_id && yoga_get_account_reviews($user_id)) { return new WP_Error('duplicate_review', 'Вы уже оставили отзыв.'); }
		$photo_id = 0;
		if (!empty($_FILES['photo']) && (int) ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
			$file = $_FILES['photo'];
			if (!is_scalar($file['name'] ?? null) || !is_scalar($file['tmp_name'] ?? null) || (int) ($file['error'] ?? -1) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 10 * MB_IN_BYTES) {
				return new WP_Error('invalid_photo', 'Загрузите фото JPG или PNG размером до 10 МБ.');
			}
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$type = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], array('jpg|jpeg' => 'image/jpeg', 'png' => 'image/png'));
			if (empty($type['ext']) || !in_array(wp_get_image_mime($file['tmp_name']), array('image/jpeg', 'image/png'), true)) {
				return new WP_Error('invalid_photo', 'Допустимы только фотографии JPG и PNG.');
			}
			$photo_id = media_handle_upload('photo', 0, array(), array('mimes' => array('jpg|jpeg' => 'image/jpeg', 'png' => 'image/png')));
			if (is_wp_error($photo_id)) { return new WP_Error('photo_upload', 'Не удалось загрузить фото. Попробуйте ещё раз.'); }
		}
		$post_data = array('post_type' => 'yoga_review', 'post_status' => 'pending', 'post_author' => $user_id, 'post_title' => $data['name'], 'post_content' => $data['text']);
		if ($review_id) { $post_data['ID'] = $review_id; }
		$id = wp_insert_post(wp_slash($post_data), true);
		if (is_wp_error($id)) {
			if ($photo_id) { wp_delete_attachment($photo_id, true); }
			return new WP_Error('save_review', 'Не удалось сохранить отзыв. Попробуйте ещё раз.');
		}
		foreach (array('email', 'job', 'age') as $key) { update_post_meta($id, '_yoga_review_' . $key, $data[$key]); }
		if ($photo_id) { set_post_thumbnail($id, $photo_id); wp_update_post(array('ID' => $photo_id, 'post_parent' => $id)); }
		return $id;
	};
	try {
		$result = $save();
	} finally {
		delete_option($lock);
	}
	if (is_wp_error($result)) { yoga_ajax_error($result->get_error_message()); return; }
	yoga_ajax_success('Отзыв отправлен на модерацию.');
});

add_action('add_meta_boxes_yoga_review', function (): void {
	add_meta_box('yoga-review-details', 'Данные автора отзыва', function ($post): void {
		foreach (array('email' => 'Электронная почта', 'job' => 'Профессия', 'age' => 'Возраст') as $key => $label) {
			echo '<p><strong>' . esc_html($label) . ':</strong> ' . esc_html(get_post_meta($post->ID, '_yoga_review_' . $key, true)) . '</p>';
		}
		echo '<p>Аккаунт: <a href="' . esc_url(get_edit_user_link((int) $post->post_author)) . '">№' . (int) $post->post_author . '</a></p>';
		echo '<p>«Опубликовать» — одобрить отзыв. «Черновик» — отклонить и разрешить автору исправить его.</p>';
	}, 'yoga_review', 'side');
});

/** Only coupons explicitly restricted to the account email belong in this list. */
function yoga_coupon_belongs_to_account($coupon, string $email): bool {
	return $email !== '' && in_array(strtolower($email), array_map('strtolower', $coupon->get_email_restrictions()), true);
}

function yoga_get_account_coupons(int $user_id): array {
	$user = get_userdata($user_id);
	if (!$user || !$user->user_email || !class_exists('WC_Coupon')) { return array(); }
	$ids = get_posts(array('post_type' => 'shop_coupon', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array(array('key' => 'customer_email', 'value' => '"' . $user->user_email . '"', 'compare' => 'LIKE'))));
	$result = array();
	foreach ($ids as $id) {
		$coupon = new WC_Coupon($id);
		if (!yoga_coupon_belongs_to_account($coupon, $user->user_email)) { continue; }
		$expiry = $coupon->get_date_expires();
		$limit = $coupon->get_usage_limit();
		$per_user = $coupon->get_usage_limit_per_user();
		$used = array_count_values(array_map('strtolower', array_map('strval', $coupon->get_used_by())));
		$used_by_account = ($used[(string) $user_id] ?? 0) + ($used[strtolower($user->user_email)] ?? 0);
		$result[] = array('coupon' => $coupon, 'active' => (!$expiry || $expiry->getTimestamp() >= time()) && (!$limit || $coupon->get_usage_count() < $limit) && (!$per_user || $used_by_account < $per_user));
	}
	return $result;
}

/** Published reviews use the existing public review component. */
function yoga_get_published_account_review_items(): array {
	$items = array();
	foreach (get_posts(array('post_type' => 'yoga_review', 'post_status' => 'publish', 'posts_per_page' => 50)) as $review) {
		$items[] = array('review_name' => $review->post_title, 'review_age' => get_post_meta($review->ID, '_yoga_review_age', true), 'review_job' => get_post_meta($review->ID, '_yoga_review_job', true), 'review_image' => get_post_thumbnail_id($review->ID), 'review_excerpt' => wp_trim_words($review->post_content, 40), 'review_full_text' => $review->post_content);
	}
	return $items;
}
