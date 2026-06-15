<?php
/**
 * Подключается из section-praktika.php внутри цикла practice_sections.
 *
 * @var array $section Текущая строка гибкого контента (ACF), макет anchor_06.
 * @var string $anchor_id
 */
$anchor_id = isset($anchor_id) && $anchor_id !== ''
	? (string) $anchor_id
	: (string) ($section['anchor_id'] ?? 'anchor_06');
?>
<span class="praktika-menu-anchor" id="<?php echo esc_attr($anchor_id); ?>"></span>
<div class="praktika-comments">
    <h3><?php echo esc_html($section['title']); ?></h3>
    
    <!-- Упрощённая форма комментариев -->
    <div class="comment-form-main">
    <form id="custom-comment-form" class="comment-form">
        <textarea name="comment" class="input textarea-resize" placeholder="Оставьте комментарий" rows="1" required></textarea>
        <button type="submit" class="btn">ОТПРАВИТЬ</button>
        
        <input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>">
        <input type="hidden" name="action" value="submit_custom_comment">
        <?php wp_nonce_field('yoga_ajax_nonce', 'comment_security'); ?>
    </form>
</div>

    <!-- Список комментариев -->
    <div class="comments-items">
        <?php yoga_render_threaded_ajax_comments_list((int) get_the_ID()); ?>
    </div>
</div>
