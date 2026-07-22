<?php
/**
 * Компонент темы: single practice.
 *
 * @package Yoga
 */
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
$show_practice_questions_form = !function_exists('yoga_can_view_practice_questions_form')
	|| yoga_can_view_practice_questions_form(get_current_user_id());
$section_praktika_extra_class = $show_practice_questions_form ? '' : 'section-praktika_no-questions';

	include(locate_template('template-parts/section-ways.php'));
	include(locate_template('template-parts/section-praktika.php'));
	unset($section_praktika_extra_class);
?>
<?php if ($show_practice_questions_form) : ?>
<section class="section-form-questions section-form-questions_practice" id="section-form-questions">
	<div class="container">
        <div class="row">
			<?php $practice_form_sprite_href = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg'); ?>
			<div class="form-questions practice-form-layout">

				<div class="practice-form-layout__decor practice-form-layout__decor--oval" aria-hidden="true">
					<svg class="practice-form-layout__decor-svg" focusable="false" aria-hidden="true">
						<use href="<?php echo $practice_form_sprite_href; ?>#contacts-decor-oval"></use>
					</svg>
				</div>
				<div class="practice-form-layout__decor practice-form-layout__decor--star-four" aria-hidden="true">
					<svg class="practice-form-layout__decor-svg" viewBox="0 0 51 51" fill="none" focusable="false" aria-hidden="true">
						<path fill="#9153E1" d="M24.5056 9.34588C24.6325 8.15361 26.3675 8.15361 26.4944 9.34588L27.8668 22.2446C27.9166 22.7132 28.2868 23.0834 28.7554 23.1332L41.6541 24.5056C42.8464 24.6325 42.8464 26.3675 41.6541 26.4944L28.7554 27.8668C28.2868 27.9166 27.9166 28.2868 27.8668 28.7554L26.4944 41.6541C26.3675 42.8464 24.6325 42.8464 24.5056 41.6541L23.1332 28.7554C23.0834 28.2868 22.7132 27.9166 22.2446 27.8668L9.34588 26.4944C8.15361 26.3675 8.15361 24.6325 9.34588 24.5056L22.2446 23.1332C22.7132 23.0834 23.0834 22.7132 23.1332 22.2446L24.5056 9.34588Z"></path>
					</svg>
				</div>
				<div class="practice-form-layout__decor practice-form-layout__decor--star-eight" aria-hidden="true">
					<svg class="practice-form-layout__decor-svg practice-form-layout__decor-svg--star-eight" viewBox="0 0 51 51" fill="none" focusable="false" aria-hidden="true">
						<path fill="#9153E1" d="M24.505 9.94062C24.6251 8.74009 26.3749 8.7401 26.495 9.94063L27.4503 19.4837C27.5247 20.2277 28.3596 20.6298 28.9878 20.2241L37.0444 15.021C38.058 14.3664 39.1489 15.7344 38.2852 16.5768L31.4197 23.2736C30.8844 23.7958 31.0907 24.6992 31.7995 24.9374L40.8907 27.9922C42.0344 28.3765 41.645 30.0824 40.4478 29.9324L30.9315 28.7401C30.1896 28.6472 29.6118 29.3717 29.8675 30.0744L33.1474 39.0868C33.5601 40.2206 31.9836 40.9797 31.3545 39.9502L26.3533 31.7667C25.9633 31.1287 25.0367 31.1287 24.6467 31.7667L19.6455 39.9502C19.0164 40.9797 17.4399 40.2206 17.8526 39.0868L21.1325 30.0744C21.3882 29.3717 20.8104 28.6472 20.0685 28.7401L10.5521 29.9324C9.35497 30.0824 8.96563 28.3765 10.1093 27.9922L19.2005 24.9374C19.9093 23.7958 20.1156 24.6992 19.5803 23.2736L12.7148 16.5768C11.8511 15.7344 12.942 14.3664 13.9556 15.021L22.0122 20.2241C22.6404 20.6298 23.4753 20.2277 23.5497 19.4837L24.505 9.94062Z"></path>
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

										__('Поделитесь опытом от практики %1$s «%2$s» или задайте вопрос о ее выполнении.', 'yoga'),
										$practice_form_type_name,
										$practice_form_title
									)
								);
							} else {
								echo esc_html(
									sprintf(

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
						<input name="contacts_name" type="text" class="input" required value="<?php echo esc_attr($practice_prefill_name); ?>"<?php echo $practice_prefill_name !== '' ? ' readonly aria-readonly="true"' : ''; ?> placeholder="Имя">
						<input name="contacts_email" type="email" class="input" required value="<?php echo esc_attr($practice_prefill_email); ?>"<?php echo $practice_prefill_email !== '' ? ' readonly aria-readonly="true"' : ''; ?> placeholder="Эл. почта">
						<div class="form-questions-textarea">
							<textarea name="contacts_message" id="" placeholder="Ваш вопрос" required class="input"></textarea>
							<input type="submit" id="form-questions-submit">
							<label for="form-questions-submit" class="btn practice-form-layout__submit practice-form-layout__submit--sprite" aria-label="<?php esc_attr_e('Отправить вопрос', 'yoga'); ?>">
								<svg class="practice-form-layout__submit-icon" viewBox="0 0 20 20" focusable="false" aria-hidden="true"><use href="<?php echo $practice_form_sprite_href; ?>#site-arrow-green"></use></svg>
							</label>
						</div>
					</form>
				</div>
				<div class="form-questions__succes">
					<h3>
						Мы получили ваш вопрос!
					</h3>
					<p>
						Ответим в ближайшее время на указанную эл. почту, так же вы найдёте ответ в <a href="#" class="ref">личном кабинете.</a>
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
<?php endif; ?>
<?php
get_footer(); ?>
