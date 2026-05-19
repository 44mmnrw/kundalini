<?php
get_header();

$practice_prefill_name = '';
$practice_prefill_email = '';

if (is_user_logged_in()) {
	$current_user = wp_get_current_user();
	$practice_prefill_name = (string) ($current_user->display_name ?? '');
	$practice_prefill_email = (string) ($current_user->user_email ?? '');
}

$practice_form_type_name = '';
$practice_form_terms = get_the_terms(get_the_ID(), 'practice-type');
if (is_array($practice_form_terms) && ! is_wp_error($practice_form_terms) && $practice_form_terms !== array()) {
	$raw_practice_type = (string) $practice_form_terms[0]->name;
	$practice_form_type_name = function_exists('mb_strtolower')
		? mb_strtolower($raw_practice_type, 'UTF-8')
		: strtolower($raw_practice_type);
}
$practice_form_title = get_the_title(get_the_ID());
	
	include(locate_template('template-parts/section-ways.php'));
	include(locate_template('template-parts/section-praktika.php'));
?>
<section class="section-form-questions section-form-questions_practice" id="section-form-questions">
	<div class="container">
        <div class="row">
			<?php $practice_form_sprite_href = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg'); ?>
			<div class="form-questions practice-form-layout">
				<!-- Декор — Figma question_form 246:4302 (sprite) -->
				<div class="practice-form-layout__decor practice-form-layout__decor--oval" aria-hidden="true">
					<svg class="practice-form-layout__decor-svg" focusable="false" aria-hidden="true">
						<use href="<?php echo $practice_form_sprite_href; ?>#contacts-decor-oval"></use>
					</svg>
				</div>
				<div class="practice-form-layout__decor practice-form-layout__decor--star-four" aria-hidden="true">
					<svg class="practice-form-layout__decor-svg" focusable="false" aria-hidden="true">
						<use href="<?php echo $practice_form_sprite_href; ?>#contacts-star-four"></use>
					</svg>
				</div>
				<div class="practice-form-layout__decor practice-form-layout__decor--star-eight" aria-hidden="true">
					<svg class="practice-form-layout__decor-svg practice-form-layout__decor-svg--star-eight" focusable="false" aria-hidden="true">
						<use href="<?php echo $practice_form_sprite_href; ?>#contacts-star-eight"></use>
					</svg>
				</div>
				<div class="form-questions__badge practice-form-layout__badge" aria-hidden="true">
					<svg class="form-questions__badge-svg practice-form-layout__badge-svg" viewBox="0 0 48 48" focusable="false">
						<use href="<?php echo $practice_form_sprite_href; ?>#contacts-form-badge"></use>
					</svg>
				</div>
				<div class="form-questions__main practice-form-layout__panel">
					<div class="form-questions__main-text">
						<h3>
							Остались вопросы?
						</h3>
						<p>
							<?php
							if ($practice_form_type_name !== '') {
								echo esc_html(
									sprintf(
										/* translators: 1 = practice type (taxonomy practice-type name), 2 = practice post title */
										__('Поделитесь опытом от практики %1$s «%2$s» или задайте вопрос о ее выполнении.', 'yoga'),
										$practice_form_type_name,
										$practice_form_title
									)
								);
							} else {
								echo esc_html(
									sprintf(
										/* translators: %s practice post title */
										__('Поделитесь опытом от практики «%s» или задайте вопрос о ее выполнении.', 'yoga'),
										$practice_form_title
									)
								);
							}
							?>
						</p>
					</div>
					<form action="#" class="form-questions__main-form contacts-form">
						<?php wp_nonce_field('contacts_nonce', 'contacts_nonce_field'); ?>
						<input name="contacts_name" type="text" class="input" required value="<?php echo esc_attr($practice_prefill_name); ?>" placeholder="Имя">
						<input name="contacts_email" type="email" class="input" required value="<?php echo esc_attr($practice_prefill_email); ?>" placeholder="E-mail">
						<div class="form-questions-textarea">
							<textarea name="contacts_message" id="" placeholder="Ваш вопрос" required class="input"></textarea>
							<input type="submit" id="form-questions-submit">
							<label for="form-questions-submit" class="btn practice-form-layout__submit practice-form-layout__submit--sprite" aria-label="<?php esc_attr_e('Отправить вопрос', 'yoga'); ?>">
								<svg class="practice-form-layout__submit-icon" viewBox="0 0 205.8 205.8" focusable="false" aria-hidden="true">
									<use href="<?php echo $practice_form_sprite_href; ?>#hundreds-practices-arrow-hover"></use>
								</svg>
							</label>
						</div>
					</form>
				</div>
				<div class="form-questions__succes">
					<h3>
						Мы получили ваш вопрос!
					</h3>
					<p>
						Ответим в ближайшее время на указанный e-mail, так же вы найдёте ответ в <a href="#" class="ref">личном кабинете.</a>
					</p>
					<div class="btn">
						<span>
							Спросить ещё
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<?php
get_footer(); ?>