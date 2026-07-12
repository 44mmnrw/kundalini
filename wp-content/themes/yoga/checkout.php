<?php
/**
 * Оформление подписки (/checkout/).
 */

if (function_exists('yoga_is_order_received_request') && yoga_is_order_received_request()) {
	$success_template = get_template_directory() . '/payment-success.php';
	if (is_readable($success_template)) {
		load_template($success_template);
		return;
	}
}

if (function_exists('yoga_handle_cart_mutation_request')) {
	yoga_handle_cart_mutation_request();
}

get_header();

if (function_exists('yoga_ensure_wc_cart_session')) {
	yoga_ensure_wc_cart_session();
}

$wc_cart = function_exists('WC') && WC()->cart ? WC()->cart : null;
$is_empty = !$wc_cart || $wc_cart->is_empty();
?>

<?php if ($is_empty) : ?>
	<?php get_template_part('template-parts/section', 'checkout'); ?>
<?php elseif (!is_user_logged_in()) : ?>
	<?php
	$tariff_name = '';
	if ($wc_cart) {
		foreach ($wc_cart->get_cart() as $cart_item) {
			$product = $cart_item['data'] ?? null;
			if ($product instanceof WC_Product) {
				$tariff_name = $product->get_name();
				break;
			}
		}
	}
	?>
	<section class="section-checkout section-checkout_auth" id="section-checkout">
		<div class="container">
			<div class="row">
				<div class="yoga-checkout-column">
					<div class="yoga-checkout yoga-checkout_auth">
					<h1 class="yoga-checkout__title"><?php esc_html_e('КОРЗИНА', 'yoga'); ?></h1>
					<p class="yoga-checkout-auth__text">
						<?php
						if ($tariff_name !== '') {
							printf(
								/* translators: %s: tariff name */
								esc_html__('Тариф «%s» выбран. Для оплаты войдите в аккаунт или зарегистрируйтесь.', 'yoga'),
								esc_html($tariff_name)
							);
						} else {
							esc_html_e('Для оплаты подписки войдите в аккаунт или зарегистрируйтесь.', 'yoga');
						}
						?>
					</p>
					<button type="button" class="btn btn_alt modal-call_login">
						<span><?php esc_html_e('Войти', 'yoga'); ?></span>
					</button>
					</div>
				</div>
			</div>
		</div>
	</section>
<?php else : ?>
	<?php get_template_part('template-parts/section', 'checkout'); ?>
<?php endif; ?>

<?php get_footer(); ?>
