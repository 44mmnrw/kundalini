<?php
/**
 * Review modal populated from the selected homepage review.
 *
 * @package Yoga
 */
$theme_uri = get_template_directory_uri();
?>
<div class="modal modal_review">
    <div class="review-modal">
        <div class="review-modal__main">
            <div class="review-modal__main-image" hidden>
                <img src="" alt="<?php esc_attr_e('Фото пользователя', 'yoga'); ?>">
            </div>
            <div class="review-modal__main-info">
                <div class="review-modal__main-info-text">
                    <span class="review-modal-name"></span>
                    <span class="review-modal-age"></span>
                </div>
                <div class="review-modal-job"></div>
            </div>
        </div>
        <div class="review-modal__text"></div>
    </div>
    <div class="modal-close">
        <svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?php echo esc_url($theme_uri . '/assets/svg/sprite.svg#lk-modal-close'); ?>"></use></svg>
    </div>
</div>
