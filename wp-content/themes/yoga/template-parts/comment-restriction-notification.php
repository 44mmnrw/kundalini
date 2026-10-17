<?php
/** Restriction events: bell 2812:30330, account 2918:27962 and 2918:27972. */
if (!defined('ABSPATH')) { exit; }
$unread = empty($notification['read_at']);
$created_at = (string) ($notification['created_at'] ?? '');
$temporary = ($notification['type'] ?? '') === 'comment_restricted';
$tag = $popup ? 'a' : 'article';
$icon = $popup ? 'comment-restriction-notification-close.svg' : ($temporary ? 'comment-restriction-feed-warning.svg' : 'comment-restriction-feed-close.svg');
$title = !$popup && $temporary ? __('Комментирование временно ограничено', 'yoga') : (string) ($notification['title'] ?? '');
?>
<<?php echo $tag; ?> class="lk-notification comment-restriction-notification<?php echo $popup ? ' lk-notifications-popup__item' : ' comment-restriction-notification--feed'; ?><?php echo $unread ? ' lk-notification--unread' : ''; ?>" data-notification-id="<?php echo esc_attr((string) ($notification['id'] ?? '')); ?>" data-notification-type="<?php echo esc_attr((string) ($notification['type'] ?? '')); ?>" <?php if ($popup) : ?>href="<?php echo esc_url((string) ($notification['url'] ?? '')); ?>"<?php else : ?>tabindex="0"<?php endif; ?>>
    <span class="comment-restriction-notification__head">
        <span class="comment-restriction-notification__icon" aria-hidden="true"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/' . $icon); ?>" alt=""></span>
        <strong><?php echo esc_html($title); ?></strong>
        <span class="comment-restriction-notification__time"><?php if ($created_at !== '') : ?><time datetime="<?php echo esc_attr($created_at); ?>"><?php echo esc_html(human_time_diff(strtotime($created_at), current_time('timestamp')) . ' ' . __('назад', 'yoga')); ?></time><?php endif; ?><?php if ($unread) : ?><i class="lk-notifications-popup__unread-dot" aria-label="<?php esc_attr_e('Не прочитано', 'yoga'); ?>"></i><?php endif; ?></span>
    </span>
    <span class="comment-restriction-notification__message"><?php if (!$popup && $temporary && isset($notification['comment_warnings_remaining'], $notification['comment_restricted_until'])) : ?>
        <?php echo esc_html(yoga_comment_warnings_message((int) $notification['comment_warnings_remaining'])); ?> Пожалуйста, ознакомьтесь с <a href="<?php echo esc_url(yoga_get_legal_document_url('user_agreement', home_url('/user-agreement/'))); ?>">правилами платформы</a> во избежание повторных ограничений. Разблокировка: <strong title="<?php echo esc_attr(wp_date('j F Y, H:i', (int) $notification['comment_restricted_until'])); ?>"><?php echo esc_html(wp_date('d.m.Y', (int) $notification['comment_restricted_until'])); ?></strong>
    <?php else : ?><?php echo esc_html((string) ($notification['message'] ?? '')); ?><?php endif; ?></span>
</<?php echo $tag; ?>>
