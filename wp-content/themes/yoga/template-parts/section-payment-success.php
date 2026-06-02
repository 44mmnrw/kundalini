<?php
/**
 * Успешная оплата — Figma node 1989:15056.
 *
 * @var array $ctx
 */
$ctx = function_exists('yoga_get_payment_success_context')
	? yoga_get_payment_success_context()
	: array(
		'tariff_name'      => '',
		'subscription_end' => '',
		'home_url'         => home_url('/'),
		'support_email'    => 'yuoga@mail.ru',
		'has_order'        => false,
	);

$theme_uri   = get_template_directory_uri();
$icon_circle = $theme_uri . '/assets/images/payment-success/icon-circle.svg';
$icon_check  = $theme_uri . '/assets/images/payment-success/icon-check.svg';
$tariff_name = $ctx['tariff_name'] !== '' ? $ctx['tariff_name'] : __('тариф', 'yoga');
?>

<section class="section-payment-success" id="section-payment-success">
	<div class="container">
		<div class="row">
			<div class="yoga-payment-success">
				<div class="yoga-payment-success__icon" aria-hidden="true">
					<img src="<?php echo esc_url($icon_circle); ?>" alt="" class="yoga-payment-success__icon-circle" width="67" height="67">
					<img src="<?php echo esc_url($icon_check); ?>" alt="" class="yoga-payment-success__icon-check" width="30" height="30">
				</div>

				<h1 class="yoga-payment-success__title"><?php esc_html_e('Спасибо за покупку', 'yoga'); ?></h1>

				<p class="yoga-payment-success__lead">
					<?php esc_html_e('Тариф', 'yoga'); ?>
					<strong class="yoga-payment-success__tariff"><?php echo esc_html($tariff_name); ?></strong>
					<?php esc_html_e(' успешно подключен. Теперь вам доступны все практики и материалы.', 'yoga'); ?>
				</p>

				<?php if ($ctx['subscription_end'] !== '') : ?>
					<div class="yoga-payment-success__panel">
						<p class="yoga-payment-success__panel-label"><?php esc_html_e('Срок действия подписки', 'yoga'); ?></p>
						<p class="yoga-payment-success__panel-value"><?php echo esc_html($ctx['subscription_end']); ?></p>
					</div>
				<?php endif; ?>

				<a href="<?php echo esc_url($ctx['home_url']); ?>" class="yoga-payment-success__btn btn">
					<span><?php esc_html_e('Перейти на главную', 'yoga'); ?></span>
				</a>

				<div class="yoga-payment-success__help">
					<p><?php esc_html_e('Возникли вопросы или нужна помощь?', 'yoga'); ?></p>
					<p>
						<?php esc_html_e('Напишите нам на', 'yoga'); ?>
						<a href="mailto:<?php echo esc_attr($ctx['support_email']); ?>" class="yoga-payment-success__email">
							<?php echo esc_html($ctx['support_email']); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
	</div>
</section>
