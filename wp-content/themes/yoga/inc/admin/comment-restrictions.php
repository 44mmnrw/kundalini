<?php
/** Comment moderation fields in the WordPress user profile. */
if (!defined('ABSPATH')) { exit; }

function yoga_can_edit_comment_restriction(int $user_id): bool {
    return current_user_can('manage_options') && current_user_can('edit_user', $user_id);
}

function yoga_render_comment_restriction_profile($user): void {
    if (!yoga_can_edit_comment_restriction((int) $user->ID)) { return; }
    $until = (int) get_user_meta($user->ID, '_yoga_comment_restricted_until', true);
    $permanent = yoga_is_comment_permanently_restricted((int) $user->ID);
    $reason = (string) get_user_meta($user->ID, '_yoga_comment_restriction_reason', true);
    wp_nonce_field('yoga_comment_restriction_' . $user->ID, 'yoga_comment_restriction_nonce');
    ?>
    <h2><?php esc_html_e('Ограничение комментариев', 'yoga'); ?></h2>
    <table class="form-table" role="presentation">
        <tr><th>Бессрочная блокировка</th><td>
            <label><input type="checkbox" name="yoga_comment_restricted_permanently" value="1" <?php checked($permanent); ?>> Запретить комментирование навсегда</label>
            <p class="description">Дата окончания не требуется и не учитывается. Запрет действует на комментарии, ответы, редактирование и удаление в практиках и блоге. Его можно отменить ниже, отметив «Снять запрет при сохранении пользователя».</p>
        </td></tr>
        <tr><th><label for="yoga-comment-until">Запретить до</label></th><td>
            <input type="datetime-local" id="yoga-comment-until" name="yoga_comment_restricted_until" aria-describedby="yoga-comment-until-help" value="<?php echo esc_attr($until ? wp_date('Y-m-d\TH:i', $until) : ''); ?>">
            <p class="description" id="yoga-comment-until-help"><strong>Для временного ограничения заполните и дату, и время (часы и минуты).</strong> Без времени временное ограничение не сохранится. Затем нажмите «Обновить пользователя».</p>
            <p class="description">Часовой пояс сайта: <?php echo esc_html(wp_timezone()->getName()); ?>. Временный запрет снимается автоматически после указанного времени. Пустое поле означает отсутствие временного запрета.</p>
            <p><?php echo $permanent ? 'Комментирование заблокировано бессрочно.' : (yoga_is_comment_restricted((int) $user->ID) ? 'Временное ограничение активно.' : 'Активного ограничения нет.'); ?></p>
        </td></tr>
        <tr><th><label for="yoga-comment-reason">Причина</label></th><td>
            <textarea id="yoga-comment-reason" name="yoga_comment_restriction_reason" class="large-text" rows="3"><?php echo esc_textarea($reason); ?></textarea>
            <p class="description">Служебная заметка. Пользователю не показывается.</p>
        </td></tr>
        <tr><th>Снять ограничение</th><td><label><input type="checkbox" name="yoga_comment_restriction_remove" value="1"> Снять запрет при сохранении пользователя</label></td></tr>
    </table>
    <?php
}
add_action('show_user_profile', 'yoga_render_comment_restriction_profile');
add_action('edit_user_profile', 'yoga_render_comment_restriction_profile');

/** Null means no authorized submission; 'permanent' means no expiry; WP_Error means invalid input. */
function yoga_comment_restriction_profile_input(int $user_id) {
    if (!yoga_can_edit_comment_restriction($user_id)
        || !isset($_POST['yoga_comment_restriction_nonce'])
        || !is_string($_POST['yoga_comment_restriction_nonce'])
        || !wp_verify_nonce(wp_unslash($_POST['yoga_comment_restriction_nonce']), 'yoga_comment_restriction_' . $user_id)) {
        return null;
    }
    if (!empty($_POST['yoga_comment_restriction_remove'])) { return 0; }
    if (isset($_POST['yoga_comment_restricted_permanently']) && $_POST['yoga_comment_restricted_permanently'] === '1') {
        return 'permanent';
    }
    $raw = $_POST['yoga_comment_restricted_until'] ?? '';
    if (!is_string($raw)) { return new WP_Error('yoga_comment_restriction_date', 'Укажите корректную дату и время окончания ограничения.'); }
    $raw = trim(wp_unslash($raw));
    if ($raw === '') { return 0; }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $raw, wp_timezone());
    if (!$date || $date->format('Y-m-d\TH:i') !== $raw) {
        return new WP_Error('yoga_comment_restriction_date', 'Укажите корректную дату и время окончания ограничения.');
    }
    return $date->getTimestamp();
}

add_action('user_profile_update_errors', static function ($errors, $update, $user): void {
    $input = yoga_comment_restriction_profile_input((int) $user->ID);
    if (is_wp_error($input)) { $errors->add($input->get_error_code(), $input->get_error_message()); }
}, 10, 3);

function yoga_save_comment_restriction_profile(int $user_id): void {
    $until = yoga_comment_restriction_profile_input($user_id);
    if ($until === null || is_wp_error($until)) { return; }
    if (!$until) {
        delete_user_meta($user_id, '_yoga_comment_restricted_permanently');
        delete_user_meta($user_id, '_yoga_comment_restricted_until');
        delete_user_meta($user_id, '_yoga_comment_restriction_reason');
        return;
    }
    if ($until === 'permanent') {
        update_user_meta($user_id, '_yoga_comment_restricted_permanently', 1);
        delete_user_meta($user_id, '_yoga_comment_restricted_until');
    } else {
        delete_user_meta($user_id, '_yoga_comment_restricted_permanently');
        update_user_meta($user_id, '_yoga_comment_restricted_until', $until);
    }
    $reason = $_POST['yoga_comment_restriction_reason'] ?? '';
    update_user_meta($user_id, '_yoga_comment_restriction_reason', is_string($reason) ? sanitize_textarea_field(wp_unslash($reason)) : '');
}
add_action('personal_options_update', 'yoga_save_comment_restriction_profile');
add_action('edit_user_profile_update', 'yoga_save_comment_restriction_profile');
