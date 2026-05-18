<?php
	$GLOBALS['has_subscription_section'] = true;
?>
<section class="section-subscription wow fadeInUp delay-200ms" id="section-subscription">
    <div class="container">
        <div class="row">
            <div class="subscription">
                <?php 
					$decor_image = get_field('subscription_decor', 'option');
				if ($decor_image) : ?>
				<div class="subscription-decor">
					<img src="<?php echo esc_url($decor_image); ?>" alt="Декоративный элемент">
				</div>
                <?php endif; ?>
                
                <h3><?php echo esc_html(get_field('subscription_title', 'option') ?: 'Оставайтесь вместе с нами'); ?></h3>
                
                <h2>
                    <?php 
						$subtitle = get_field('subscription_subtitle', 'option');
						echo $subtitle ? wp_kses($subtitle, array(
                        'b' => array(),
                        'span' => array(),
                        'u' => array()
						)) : '<b>Подпишитесь,</b> чтобы всегда быть в курсе <span>новых материалов</span>, акций и <u>0</u> спецпредложений!';
					?>
				</h2>
			
			<form action="#" class="form subscription-form" method="post">
			<?php wp_nonce_field('subscription_nonce', 'subscription_nonce_field'); ?>
			<input type="email" name="subscription_email" class="input" maxlength="30" placeholder="<?php echo esc_attr(get_field('subscription_placeholder', 'option') ?: 'Ваша эл. почта'); ?>" required>
			
			<button type="submit" id="subscription-btn" style="display: none;"></button>
			
			<label for="subscription-btn" class="form-btn">
				<svg class="subscription-btn-icon" aria-hidden="true" focusable="false">
					<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
				</svg>
			</label>
				</form>
                
                <span class="form__succes">
                    <?php echo esc_html(get_field('subscription_success', 'option') ?: 'Подписка оформлена!'); ?>
				</span> 
			</div>
		</div>
	</div>
</section>