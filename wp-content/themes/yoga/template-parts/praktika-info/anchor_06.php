<span class="praktika-menu-anchor" id="<?php echo esc_attr($section['anchor_id']); ?>"></span>
<div class="praktika-comments">
    <h3><?php echo esc_html($section['title']); ?></h3>
    
    <!-- Упрощенная форма комментариев -->
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
        <?php
        $comments = get_comments(array(
            'post_id' => get_the_ID(),
            'status' => 'approve',
            'order' => 'ASC'
        ));
        
        if ($comments) {
            echo '<div class="praktika-comments-list">';
            
            // Организуем комментарии по родителям
            $comments_by_parent = array();
            foreach ($comments as $comment) {
                $comments_by_parent[$comment->comment_parent][] = $comment;
            }
            
            // Функция для рекурсивного вывода
            function display_comments($parent_id, $comments_by_parent, $depth = 0) {
                if (!isset($comments_by_parent[$parent_id])) return;
                
                foreach ($comments_by_parent[$parent_id] as $comment) {
                    custom_comment_template($comment, array('max_depth' => 5), $depth);
                    
                    // Выводим дочерние комментарии
                    if ($depth < 4) {
                        echo '<div class="praktika-comment__sub-answers">';
                        display_comments($comment->comment_ID, $comments_by_parent, $depth + 1);
                        echo '</div>';
                    }
                }
            }
            
            // Выводим корневые комментарии
            display_comments(0, $comments_by_parent);
            
            echo '</div>';
        } else {
            echo '<p>Пока нет комментариев. Будьте первым!</p>';
        }
        ?>
    </div>
</div>
