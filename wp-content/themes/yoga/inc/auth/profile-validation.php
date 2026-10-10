<?php
/** Validate the complete profile before any values are saved. */
function yoga_validate_profile_changes(array $request, $account) {
	if (!$account instanceof WP_User) {
		return new WP_Error('profile_user_missing', 'Не удалось найти профиль. Войдите снова.');
	}
	$read = static function (string $key) use ($request): string {
		return isset($request[$key]) && is_string($request[$key]) ? wp_unslash($request[$key]) : '';
	};
	$changes = array(
		'first_name' => trim(sanitize_text_field($read('first_name'))),
		'email' => sanitize_email($read('email')),
		'timezone' => sanitize_text_field($read('timezone')),
		'new_password' => $read('new_password'),
	);
	if ($changes['first_name'] === '') {
		return new WP_Error('profile_name_required', 'Заполните имя. Изменения не сохранены.');
	}
	if ($changes['email'] === '' || !is_email($changes['email'])) {
		return new WP_Error('profile_email_required', 'Укажите корректную эл. почту. Изменения не сохранены.');
	}
	if (!array_key_exists($changes['timezone'], yoga_get_russian_timezone_options())) {
		return new WP_Error('profile_timezone_required', 'Выберите часовой пояс. Изменения не сохранены.');
	}
	$current_password = $read('current_password');
	$repeat_password = $read('repeat_password');
	if ($current_password !== '' || $changes['new_password'] !== '' || $repeat_password !== '') {
		if ($current_password === '' || $changes['new_password'] === '' || $repeat_password === '') {
			return new WP_Error('profile_password_incomplete', 'Для смены пароля заполните все три поля. Изменения не сохранены.');
		}
		if ($changes['new_password'] !== $repeat_password) {
			return new WP_Error('profile_password_mismatch', 'Пароли не совпадают. Изменения не сохранены.');
		}
		if (strlen($changes['new_password']) < 6) {
			return new WP_Error('profile_password_short', 'Новый пароль должен быть не короче 6 символов. Изменения не сохранены.');
		}
		if (!wp_check_password($current_password, $account->user_pass, $account->ID)) {
			return new WP_Error('profile_password_invalid', 'Текущий пароль указан неверно. Изменения не сохранены.');
		}
	}
	return $changes;
}
