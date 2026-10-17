<?php
/** Форма отправки отзыва: Figma 2836:31522, 2994:29719. */
$user = get_userdata((int) $args['user_id']);
$review = $args['review'] ?? null;
$existing_photo = $review && has_post_thumbnail($review->ID) ? get_the_post_thumbnail_url($review->ID, 'thumbnail') : '';
?>
<dialog class="lk-review-dialog" aria-labelledby="lk-review-form-title">
	<button type="button" class="lk-review-dialog__close" data-close-review aria-label="Закрыть"><svg viewBox="0 0 16 16" aria-hidden="true"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-modal-close'); ?>"></use></svg></button>
	<div class="lk-review-dialog__head"><h2 id="lk-review-form-title">Оставьте отзыв</h2><p>Поделитесь впечатлениями о сервисе и получите <strong>промокод на скидку 30%</strong> для любого тарифа. Обязательное условие — заполнить все поля и прикрепить фото. Без этого промокод не выдаётся.</p></div>
	<form class="lk-review-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" enctype="multipart/form-data">
		<input type="hidden" name="action" value="yoga_submit_account_review">
		<input type="hidden" name="review_id" value="<?php echo $review ? (int) $review->ID : 0; ?>">
		<?php wp_nonce_field('yoga_account_review', 'nonce'); ?>
		<div class="lk-review-form__photo">
			<div><span class="lk-review-form__preview"<?php echo $existing_photo ? ' data-has-photo' : ''; ?>><?php yoga_lk_section_icon('review-upload'); ?><img data-review-photo-preview data-existing-photo="<?php echo esc_url($existing_photo); ?>"<?php if ($existing_photo) : ?> src="<?php echo esc_url($existing_photo); ?>"<?php else : ?> hidden<?php endif; ?> alt="Предпросмотр фото"></span><label for="lk-review-photo" class="lk-community__purple-button">Загрузить фото</label><input id="lk-review-photo" name="photo" type="file" accept="image/jpeg,image/png" aria-describedby="lk-review-photo-help" class="lk-review-form__file"></div>
			<p id="lk-review-photo-help">Загрузите своё фото. На фото должно быть отчётливо видно ваше лицо. Формат.jpg,.png, вес до 10 мб.</p>
		</div>
		<div class="lk-review-form__fields">
			<label>Ваше имя<sup>*</sup><input name="name" type="text" required maxlength="120" autocomplete="name" placeholder="Ваше имя" value="<?php echo esc_attr($review ? $review->post_title : ($user->first_name ?: $user->display_name)); ?>"></label>
			<label>Электронный адрес<sup>*</sup><input name="email" type="email" required autocomplete="email" placeholder="ivanov@mail.ru" value="<?php echo esc_attr($review ? get_post_meta($review->ID, '_yoga_review_email', true) : $user->user_email); ?>"></label>
			<label>Ваша профессия<input name="job" type="text" maxlength="200" placeholder="Например, психолог..." value="<?php echo $review ? esc_attr(get_post_meta($review->ID, '_yoga_review_job', true)) : ''; ?>"></label>
			<label>Ваш возраст<input name="age" type="number" min="1" max="120" step="1" placeholder="Например: 32" value="<?php echo $review ? esc_attr(get_post_meta($review->ID, '_yoga_review_age', true)) : ''; ?>"></label>
		</div>
		<label class="lk-review-form__text"><span>Текст отзыва<sup>*</sup><small>Мин.80 символов</small></span><textarea name="text" required minlength="80" maxlength="10000" placeholder="Расскажите о ваших ощущениях, результатах и впечатлениях..."><?php echo $review ? esc_html($review->post_content) : ''; ?></textarea></label>
		<p class="lk-review-form__message" role="status" hidden></p>
		<div class="lk-review-form__buttons"><button type="button" data-close-review>Отменить</button><button type="submit">Отправить отзыв</button></div>
		<p class="lk-review-form__consent">Нажимая кнопку «Отправить отзыв», я соглашаюсь на публикацию отзыва на сайте <?php echo esc_html(wp_parse_url(home_url('/'), PHP_URL_HOST)); ?>. Моё фото и данные будут доступны всем посетителям сайта.</p>
	</form>
</dialog>
