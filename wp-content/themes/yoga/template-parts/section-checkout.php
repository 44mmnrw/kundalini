<?php
/**
 * Оформление подписки (/checkout/) — макет Figma (node 1984:15043).
 * WooCommerce cart используется только как техническая сессия, без шага «корзина».
 */

if (!defined('ABSPATH') || !function_exists('WC') || !WC()->cart) {
	return;
}

$wc_cart = WC()->cart;
$theme_uri = get_template_directory_uri();
$sprite_href = esc_url($theme_uri . '/assets/svg/sprite.svg');
$checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
$tariffs_url = function_exists('yoga_get_tariffs_page_url') ? yoga_get_tariffs_page_url() : home_url('/product-category/tariffs/');
$privacy_url = function_exists('yoga_get_privacy_policy_url') ? yoga_get_privacy_policy_url() : home_url('/privacy-policy/');
$order_button_text = apply_filters('woocommerce_order_button_text', __('Оплатить', 'woocommerce'));

$prefill_first = '';
$prefill_last = '';
$prefill_email = '';
$prefill_phone = '';
if (is_user_logged_in()) {
	$user = wp_get_current_user();
	$prefill_first = (string) $user->first_name;
	$prefill_last = (string) $user->last_name;
	$prefill_email = (string) $user->user_email;
	$prefill_phone = (string) get_user_meta($user->ID, 'phone', true);
	if ($prefill_phone === '') {
		$prefill_phone = (string) get_user_meta($user->ID, 'billing_phone', true);
	}
}
if (WC()->checkout) {
	$prefill_first = $prefill_first !== '' ? $prefill_first : (string) WC()->checkout->get_value('billing_first_name');
	$prefill_last = $prefill_last !== '' ? $prefill_last : (string) WC()->checkout->get_value('billing_last_name');
	$prefill_email = $prefill_email !== '' ? $prefill_email : (string) WC()->checkout->get_value('billing_email');
	$prefill_phone = $prefill_phone !== '' ? $prefill_phone : (string) WC()->checkout->get_value('billing_phone');
}
if ($prefill_phone === '' && class_exists('YTR_Checkout')) {
	$prefill_phone = YTR_Checkout::resolve_checkout_phone();
}

$line_subtotal = (float) $wc_cart->get_subtotal();
$line_discount = (float) $wc_cart->get_discount_total();
$line_total = (float) $wc_cart->get_total('edit');
$display_total = yoga_format_cart_price_display($line_total > 0 ? $line_total : $line_subtotal);
$display_subtotal = yoga_format_cart_price_display($line_subtotal);
$display_discount = yoga_format_cart_price_display($line_discount);

$primary_line_label = '';
foreach ($wc_cart->get_cart() as $cart_item) {
	$product = $cart_item['data'] ?? null;
	if ($product instanceof WC_Product) {
		$primary_line_label = $product->get_name();
		break;
	}
}
if ($primary_line_label === '') {
	$primary_line_label = __('Тариф', 'yoga');
}

$tariff_remove_key = '';
foreach ($wc_cart->get_cart() as $cart_item_key => $cart_item) {
	$tariff_remove_key = $cart_item_key;
	break;
}
?>

<section class="section-checkout" id="section-checkout">
	<div class="container">
		<div class="row">
			<div class="yoga-checkout-column">
				<div class="yoga-checkout">
				<?php if (function_exists('wc_print_notices')) : ?>
					<div class="yoga-checkout__notices"><?php wc_print_notices(); ?></div>
				<?php endif; ?>

				<?php if ($wc_cart->is_empty()) : ?>
					<div class="yoga-checkout-empty">
						<div class="yoga-checkout-empty__message">
							<span class="yoga-checkout-empty__icon" aria-hidden="true">
								<img src="<?php echo esc_url($theme_uri . '/assets/svg/checkout-empty-cart.svg'); ?>" alt="" width="34" height="31">
							</span>
							<div class="yoga-checkout-empty__copy">
								<h1><?php esc_html_e('В корзине пока пусто', 'yoga'); ?></h1>
								<p><?php esc_html_e('Выберите тариф и добавьте его в корзину', 'yoga'); ?></p>
							</div>
						</div>
						<a class="yoga-checkout-empty__button" href="<?php echo esc_url($tariffs_url); ?>">
							<span><?php esc_html_e('Выбрать тариф', 'yoga'); ?></span>
							<span class="yoga-checkout-empty__button-icon" aria-hidden="true">
								<svg viewBox="0 0 16 16" focusable="false"><path d="M3 13L13 3M6 3H13V10" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
							</span>
						</a>
					</div>
				<?php else : ?>
				<h1 class="yoga-checkout__title"><?php esc_html_e('КОРЗИНА', 'yoga'); ?></h1>

				<div class="yoga-checkout__layout">
					<div class="yoga-checkout__main">
						<?php foreach ($wc_cart->get_cart() as $cart_item_key => $cart_item) :
							$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
							if (!$_product || !$_product->exists() || $cart_item['quantity'] <= 0) {
								continue;
							}
							if (!apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
								continue;
							}

							$line_product_id = yoga_cart_get_line_product_id($cart_item);
							$period_label = yoga_get_tariff_period_label($line_product_id);
							$line_price = (float) $_product->get_price() * (int) $cart_item['quantity'];
							?>
							<article class="yoga-checkout-tariff">
								<div class="yoga-checkout-tariff__decor" aria-hidden="true"></div>
								<div class="yoga-checkout-tariff__head">
									<div class="yoga-checkout-tariff__info">
										<span class="yoga-checkout-tariff__badge"><?php esc_html_e('Тариф', 'yoga'); ?></span>
										<div class="yoga-checkout-tariff__titles">
											<h2 class="yoga-checkout-tariff__name"><?php echo esc_html($_product->get_name()); ?></h2>
											<?php if ($period_label !== '') : ?>
												<p class="yoga-checkout-tariff__period"><?php echo esc_html($period_label); ?></p>
											<?php endif; ?>
										</div>
									</div>
									<form method="post" action="<?php echo esc_url($checkout_url); ?>" class="yoga-checkout-tariff__remove-form">
										<?php wp_nonce_field('yoga-cart', 'yoga_remove_nonce', false, true); ?>
										<input type="hidden" name="yoga_remove" value="<?php echo esc_attr($cart_item_key); ?>">
										<button type="submit" class="yoga-checkout-tariff__remove" aria-label="<?php esc_attr_e('Убрать тариф из корзины', 'yoga'); ?>">
											<svg class="yoga-checkout-tariff__remove-icon" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
												<use href="<?php echo esc_url($sprite_href); ?>#checkout-trash"></use>
											</svg>
										</button>
									</form>
								</div>
								<div class="yoga-checkout-tariff__total">
									<span class="yoga-checkout-tariff__total-label"><?php esc_html_e('Стоимость', 'yoga'); ?></span>
									<span class="yoga-checkout-tariff__total-value"><?php echo esc_html(yoga_format_cart_price_display($line_price)); ?></span>
								</div>
							</article>
						<?php endforeach; ?>

						<form id="yoga-checkout" name="checkout" method="post" class="yoga-checkout__form checkout woocommerce-checkout" action="<?php echo esc_url($checkout_url); ?>" enctype="multipart/form-data">
							<?php
							if (WC()->checkout) {
								// The theme has its own promo-code field in the order summary.
								// Prevent WooCommerce from rendering its standard nested coupon form here.
								$coupon_form_priority = has_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form');
								if ($coupon_form_priority !== false) {
									remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', $coupon_form_priority);
								}
								do_action('woocommerce_before_checkout_form', WC()->checkout());
								if ($coupon_form_priority !== false) {
									add_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', $coupon_form_priority);
								}
							}
							wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce');
							?>
							<input type="hidden" name="billing_country" value="<?php echo esc_attr((string) WC()->checkout->get_value('billing_country') ?: 'RU'); ?>">

							<div class="yoga-checkout-block yoga-checkout-block_data">
								<h3 class="yoga-checkout-block__title"><?php esc_html_e('Ваши данные', 'yoga'); ?></h3>
								<div class="yoga-checkout-fields">
									<div class="yoga-checkout-fields__row">
										<label class="yoga-checkout-field">
											<span class="yoga-checkout-field__icon" aria-hidden="true">
												<svg class="yoga-checkout-field__svg" width="24" height="24"><use href="<?php echo $sprite_href; ?>#login-user-icon"></use></svg>
											</span>
											<input type="text" class="yoga-checkout-field__input" name="billing_first_name" value="<?php echo esc_attr($prefill_first); ?>" placeholder="<?php esc_attr_e('Имя', 'yoga'); ?>" autocomplete="given-name" required>
										</label>
										<label class="yoga-checkout-field">
											<span class="yoga-checkout-field__icon" aria-hidden="true">
												<svg class="yoga-checkout-field__svg" width="24" height="24"><use href="<?php echo $sprite_href; ?>#login-user-icon"></use></svg>
											</span>
											<input type="text" class="yoga-checkout-field__input" name="billing_last_name" value="<?php echo esc_attr($prefill_last); ?>" placeholder="<?php esc_attr_e('Фамилия', 'yoga'); ?>" autocomplete="family-name" required>
										</label>
									</div>
									<label class="yoga-checkout-field yoga-checkout-field_full">
										<span class="yoga-checkout-field__icon" aria-hidden="true">
											<svg class="yoga-checkout-field__svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
												<path d="M3 6.5 11.2 12.2c.5.35 1.1.35 1.6 0L21 6.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
												<rect x="3" y="5" width="18" height="14" rx="2.5" stroke="currentColor" stroke-width="1.2"/>
											</svg>
										</span>
										<input type="email" class="yoga-checkout-field__input" name="billing_email" value="<?php echo esc_attr($prefill_email); ?>" placeholder="<?php esc_attr_e('эл. почта', 'yoga'); ?>" autocomplete="email" required>
									</label>
									<label class="yoga-checkout-field yoga-checkout-field_full">
										<span class="yoga-checkout-field__icon" aria-hidden="true">
											<svg class="yoga-checkout-field__svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
												<path d="M6.5 3h11a2.5 2.5 0 0 1 2.5 2.5v13a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 18.5v-13A2.5 2.5 0 0 1 6.5 3Z" stroke="currentColor" stroke-width="1.2"/>
												<path d="M9 7h6M9 11h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
											</svg>
										</span>
										<input type="tel" class="yoga-checkout-field__input input_phone" name="billing_phone" value="<?php echo esc_attr($prefill_phone); ?>" autocomplete="tel" required>
									</label>
									<p class="yoga-checkout-field__hint"><?php esc_html_e('Нужен для сохранения карты и автоплатежей.', 'yoga'); ?></p>
								</div>
							</div>

							<?php
							if ($wc_cart->needs_payment() && function_exists('yoga_render_checkout_payment_block')) {
								yoga_render_checkout_payment_block();
							}
							?>
						</form>
					</div>

					<aside class="yoga-checkout__summary">
							<div class="yoga-checkout-summary">
								<div class="yoga-checkout-summary__body">
									<h3 class="yoga-checkout-summary__title"><?php esc_html_e('Ваш заказ', 'yoga'); ?></h3>
									<div class="yoga-checkout-summary__lines">
										<div class="yoga-checkout-summary__line">
											<span class="yoga-checkout-summary__line-label"><?php echo esc_html(sprintf(__('Тариф %s', 'yoga'), $primary_line_label)); ?></span>
											<span class="yoga-checkout-summary__line-value"><?php echo esc_html($display_subtotal); ?></span>
										</div>
										<div class="yoga-checkout-summary__line yoga-checkout-summary__line_discount">
											<span class="yoga-checkout-summary__line-label"><?php esc_html_e('Скидка', 'yoga'); ?></span>
											<span class="yoga-checkout-summary__line-value"><?php echo esc_html($display_discount); ?></span>
										</div>
									</div>

									<?php if (wc_coupons_enabled()) : ?>
										<div class="yoga-checkout-promo" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-coupon-nonce="<?php echo esc_attr(wp_create_nonce('yoga-apply-coupon')); ?>">
											<label class="yoga-checkout-promo__field">
												<span class="yoga-checkout-promo__icon" aria-hidden="true">
													<svg width="17" height="17" viewBox="0 0 17 17" aria-hidden="true" focusable="false">
														<use href="<?php echo $sprite_href; ?>#checkout-price-tag"></use>
													</svg>
												</span>
												<input type="text" name="coupon_code" class="yoga-checkout-promo__input" id="coupon_code" form="yoga-checkout" value="" placeholder="<?php esc_attr_e('Промокод', 'yoga'); ?>">
												<button type="button" class="yoga-checkout-promo__apply"><?php esc_html_e('Применить', 'yoga'); ?></button>
											</label>
										</div>
									<?php endif; ?>

									<div class="yoga-checkout-summary__total">
										<span class="yoga-checkout-summary__total-label"><?php esc_html_e('Итого', 'yoga'); ?></span>
										<span class="yoga-checkout-summary__total-value"><?php echo esc_html($display_total); ?></span>
									</div>

									<?php if ($tariff_remove_key !== '') : ?>
										<form method="post" action="<?php echo esc_url($checkout_url); ?>" class="yoga-checkout-summary__remove-form">
											<?php wp_nonce_field('yoga-cart', 'yoga_remove_nonce', false, true); ?>
											<input type="hidden" name="yoga_remove" value="<?php echo esc_attr($tariff_remove_key); ?>">
											<button type="submit" class="yoga-checkout-summary__remove">
												<?php esc_html_e('Убрать тариф из корзины', 'yoga'); ?>
											</button>
										</form>
									<?php endif; ?>
								</div>

								<div class="yoga-checkout-summary__footer">
									<button type="submit" class="btn btn_icon single_add_to_cart_button yoga-checkout-summary__submit" form="yoga-checkout" name="woocommerce_checkout_place_order" id="place_order" value="<?php echo esc_attr($order_button_text); ?>">
										<span><?php echo esc_html(sprintf(__('оплатить %s', 'yoga'), $display_total)); ?></span>
										<div class="btn-icon">
											<svg class="btn-icon-arrow btn-icon-arrow_black active" aria-hidden="true" focusable="false">
												<use href="<?php echo $sprite_href; ?>#slick-arrow"></use>
											</svg>
											<svg class="btn-icon-arrow btn-icon-arrow_green" aria-hidden="true" focusable="false">
												<use href="<?php echo $sprite_href; ?>#slick-arrow"></use>
											</svg>
										</div>
									</button>
									<p class="yoga-checkout-summary__legal">
										<?php
										echo wp_kses(
											sprintf(
												__('Нажимая кнопку «Оплатить», вы соглашаетесь с <a href="%1$s">условиями оферты</a> и <a href="%2$s">политикой конфиденциальности</a>.', 'yoga'),
												esc_url($privacy_url),
												esc_url($privacy_url)
											),
											array('a' => array('href' => array()))
										);
										?>
									</p>
								</div>
							</div>
					</aside>
				</div>
				<?php
				if (WC()->checkout) {
					do_action('woocommerce_after_checkout_form', WC()->checkout());
				}
				?>
				<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>
