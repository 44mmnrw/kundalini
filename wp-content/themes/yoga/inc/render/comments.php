<?php
/**
 * Компонент темы: comments.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}




function custom_comment_template(WP_Comment $comment, array $args, int $depth) {
    $GLOBALS['comment'] = $comment;
    $is_own_comment = yoga_comment_is_owned_by_logged_in_user($comment);
    $comment_user_id = (int) $comment->user_id;
    $comment_author_name = trim((string) $comment->comment_author);

    if ($comment_user_id > 0) {
        $resolved_author_name = yoga_get_user_public_name($comment_user_id);
        if ($resolved_author_name !== '') {
            $comment_author_name = $resolved_author_name;
        }
    }
    if ($comment_author_name === '') {
        $comment_author_name = 'Пользователь';
    }

    $comment_avatar_html = $comment_user_id > 0
        ? yoga_get_user_avatar_html($comment_user_id, 60, 'avatar')
        : get_avatar($comment, 60, '', '', array('class' => 'avatar'));
    $yoga_sprite_href = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg');
    ?>
    <div class="praktika-comment <?php echo ($depth > 0) ? 'sub-answer' : ''; ?>" id="comment-<?php comment_ID(); ?>">
        <div class="praktika-comment-item <?php echo $is_own_comment ? 'praktika-comment-item_own' : ''; ?>">
            <div class="praktika-comment-item__main">
                <div class="praktika-comment-img">
                    <?php echo $comment_avatar_html; ?>
                </div>
                <b class="praktika-comment-name">
                    <?php echo esc_html($comment_author_name); ?>
                </b>
                <span class="praktika-comment-time">
                    <?php echo esc_html(yoga_get_comment_time_label($comment)); ?>
                </span>
                <div class="praktika-comment-item__main-action">
                    <?php if ($is_own_comment): ?>
                        <div class="your-comm">
                            <button type="button" class="your-comm__btn your-comm__btn_edit" aria-label="<?php esc_attr_e('Редактировать комментарий', 'yoga'); ?>">
                                <svg class="your-comm__btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                    <use href="<?php echo esc_url($yoga_sprite_href); ?>#comment-edit"></use>
                                </svg>
                            </button>
                            <button type="button" class="your-comm__btn your-comm__btn_del" aria-label="<?php esc_attr_e('Удалить комментарий', 'yoga'); ?>">
                                <svg class="your-comm__btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                    <use href="<?php echo esc_url($yoga_sprite_href); ?>#comment-delete"></use>
                                </svg>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="answer-btn" role="button" tabindex="0">
                            <svg class="answer-btn__icon" aria-hidden="true" focusable="false"><use href="<?php echo esc_url($yoga_sprite_href); ?>#praktika-answer-icon"></use></svg>
                            <span>Ответить</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="praktika-comment-item__text">
                <?php
                if ($comment->comment_parent != 0) {
                    $parent_comment = get_comment($comment->comment_parent);
                    if ($parent_comment) {
                        $parent_author_name = trim((string) $parent_comment->comment_author);
                        if ((int) $parent_comment->user_id > 0) {
                            $resolved_parent_name = yoga_get_user_public_name((int) $parent_comment->user_id);
                            if ($resolved_parent_name !== '') {
                                $parent_author_name = $resolved_parent_name;
                            }
                        }
                        echo '<b>@' . esc_html($parent_author_name) . '</b> ';
                    }
                }
                comment_text();
                ?>
            </div>


            <?php if ($is_own_comment): ?>
            <form class="praktika-comment-item__edit hidden" id="edit-form-<?php echo $comment->comment_ID; ?>">
                <div class="answer-main answer-main_comment-edit">
                    <textarea name="comment_content" class="input textarea-resize" rows="1"><?php echo esc_textarea($comment->comment_content); ?></textarea>
                    <button type="button" class="btn btn_comment-update">
                        <?php esc_html_e('Обновить', 'yoga'); ?>
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>


        <div class="praktika-comment__answer hidden" id="reply-form-<?php echo $comment->comment_ID; ?>">
            <div class="answer-main">
                <div class="answer-main__image">
                    <?php echo yoga_get_user_avatar_html(get_current_user_id(), 40, 'avatar'); ?>
                </div>
                <textarea name="reply_content" class="input textarea-resize" placeholder="Ваш ответ" rows="1"></textarea>
                <button type="button" class="btn">
                    Отправить
                </button>
            </div>
        </div>
    </div>
    <?php
}




function yoga_render_threaded_ajax_comments_list(int $post_id): void {
	if ($post_id <= 0) {
		return;
	}
	$comments = get_comments(array(
		'post_id' => $post_id,
		'status' => 'approve',
		'order' => 'ASC',
	));
	if (empty($comments)) {
		echo '<p>' . esc_html__('Пока нет комментариев. Будьте первым!', 'yoga') . '</p>';
		return;
	}
	echo '<div class="praktika-comments-list">';
	$comments_by_parent = array();
	foreach ($comments as $comment) {
		if ($comment instanceof WP_Comment) {
			$comments_by_parent[(int) $comment->comment_parent][] = $comment;
		}
	}
	$display_comments_tree = static function ($parent_id, $comments_by_parent, $depth = 0) use (&$display_comments_tree) {
		if (!isset($comments_by_parent[$parent_id])) {
			return;
		}
		foreach ($comments_by_parent[$parent_id] as $comment) {
			custom_comment_template($comment, array('max_depth' => 5), $depth);
			if ($depth < 4) {
				echo '<div class="praktika-comment__sub-answers">';
				$display_comments_tree((int) $comment->comment_ID, $comments_by_parent, $depth + 1);
				echo '</div>';
			}
		}
	};
	$display_comments_tree(0, $comments_by_parent);
	echo '</div>';
}


function yoga_render_ajax_comment(int $comment_id): string {
	$comment = get_comment($comment_id);
	if (!$comment instanceof WP_Comment) {
		return '';
	}

	$depth = 0;
	$parent_id = (int) $comment->comment_parent;
	while ($parent_id > 0 && $depth < 4) {
		$depth++;
		$parent = get_comment($parent_id);
		$parent_id = $parent instanceof WP_Comment ? (int) $parent->comment_parent : 0;
	}

	ob_start();
	custom_comment_template($comment, array('max_depth' => 5), $depth);
	return (string) ob_get_clean();
}
