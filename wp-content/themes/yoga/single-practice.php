<?php
get_header();

$practice_prefill_name = '';
$practice_prefill_email = '';

if (is_user_logged_in()) {
	$current_user = wp_get_current_user();
	$practice_prefill_name = (string) ($current_user->display_name ?? '');
	$practice_prefill_email = (string) ($current_user->user_email ?? '');
}
	
	include(locate_template('template-parts/section-ways.php'));
	include(locate_template('template-parts/section-praktika.php'));
?>
<section class="section-form-questions" id="section-form-questions">
	<div class="container">
        <div class="row">
			<div class="form-questions">
				<div class="form-questions__main">
					<div class="form-questions__main-text">
						<h3>
							Остались вопросы?
						</h3>
						<p>
							Поделитесь опытом от практики крийи «Сохраняем тело красивым» или задайте вопрос о ее выполнении.
						</p>
					</div>
					<form action="#" class="form-questions__main-form contacts-form">
						<?php wp_nonce_field('contacts_nonce', 'contacts_nonce_field'); ?>
						<input name="contacts_name" type="text" class="input" required value="<?php echo esc_attr($practice_prefill_name); ?>" placeholder="Имя">
						<input name="contacts_email" type="email" class="input" required value="<?php echo esc_attr($practice_prefill_email); ?>" placeholder="E-mail">
						<div class="form-questions-textarea">
							<textarea name="contacts_message" id="" placeholder="Ваш вопрос" required class="input"></textarea>
							<input type="submit" id="form-questions-submit">
							<label for="form-questions-submit" class="btn">
								<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="">
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