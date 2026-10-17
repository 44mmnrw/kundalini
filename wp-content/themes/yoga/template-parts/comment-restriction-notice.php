<?php
/** Figma comments restriction notices, nodes 2806:29064 and 2808:29930. */
if (!defined('ABSPATH')) { exit; }
?>
<div class="comment-restriction-notice" role="status">
    <div class="comment-restriction-notice__head">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/comment-restriction-error.png'); ?>" width="22" height="22" alt="">
        <h4><?php echo $permanent ? 'Комментирование недоступно' : 'Комментирование ограничено'; ?></h4>
    </div>
    <?php if ($permanent) : ?>
    <p>Вы неоднократно нарушили правила платформы. Вы больше не можете оставлять комментарии. Решение окончательное и не подлежит пересмотру.</p>
    <?php else : ?>
    <p>Вы временно не можете оставлять комментарии. Ограничения будут сняты <strong title="<?php echo esc_attr(wp_date('j F Y, H:i', $until)); ?>"><?php echo esc_html(wp_date('j F Y', $until)); ?> года</strong>. Пожалуйста, ознакомьтесь с <a href="<?php echo esc_url($rules_url); ?>">правилами платформы</a> во избежание повторных ограничений. Если у вас есть вопросы, свяжитесь с нами через форму обратной связи или по контактам.</p>
    <a class="comment-restriction-notice__button" href="<?php echo esc_url($contacts_url); ?>">Задать вопрос</a>
    <?php endif; ?>
</div>
