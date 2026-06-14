<?php
/**
 * Блок «Способ оплаты» на checkout — сетка по Figma (node 1979:14114).
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_get_checkout_payment_methods')) {
	/**
	 * @return array<int, array{id: string, label: string, icon?: string, icon_sprite?: string, icon_active?: string, icon_bg: string}>
	 */
	function yoga_get_checkout_payment_methods(): array {
		$base = get_template_directory_uri() . '/assets/images/checkout/';
		$svg_base = get_template_directory_uri() . '/assets/svg/';

		$methods = array(
			array(
				'id'        => 'bank_card',
				'label'     => __('Банковская карта', 'yoga'),
				'icon'      => $svg_base . 'card.svg',
				'icon_bg'   => 'muted',
				'line_icon' => true,
			),
			array(
				'id'          => 'sbp',
				'label'       => 'СБП',
				'icon'        => $svg_base . 'sbp.svg',
				'icon_active' => $svg_base . 'sbp.svg',
				'icon_bg'     => 'light',
				'line_icon'   => true,
			),
			array(
				'id'      => 'sberpay', // API ЮKassa: sberbank
				'label'   => 'SberPay',
				'icon'    => $svg_base . 'SberPay.svg',
				'icon_bg' => 'light',
			),
			array(
				'id'          => 'alfa_pay',
				'label'       => 'Alfa Pay',
				'icon_sprite' => 'alfa-pay',
				'icon_bg'     => 'light',
			),
			array(
				'id'      => 'tinkoff_bank',
				'label'   => 'T-Pay',
				'icon'    => $svg_base . 'T-Pay.svg',
				'icon_bg' => 'light',
			),
			array(
				'id'      => 'yoo_money',
				'label'   => 'YooMoney',
				'icon'    => $svg_base . 'YooMoney.svg',
				'icon_bg' => 'light',
			),
		);

		return apply_filters('yoga_checkout_payment_methods', $methods);
	}
}

if (!function_exists('yoga_render_checkout_payment_block')) {
	function yoga_render_checkout_payment_block(): void {
		if (!function_exists('WC') || !WC()->cart || !WC()->cart->needs_payment()) {
			return;
		}

		$methods = yoga_get_checkout_payment_methods();
		if ($methods === array()) {
			return;
		}
		$default_id   = (string) $methods[0]['id'];
		$type_map     = function_exists('yoga_yookassa_payment_type_map') ? yoga_yookassa_payment_type_map() : array();
		$gateway_id   = function_exists('yoga_get_checkout_yookassa_gateway_id') ? yoga_get_checkout_yookassa_gateway_id() : '';
		$sprite_href  = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg');
		?>
		<div class="yoga-checkout-block yoga-checkout-block_payment">
			<h3 class="yoga-checkout-block__title"><?php esc_html_e('Способ оплаты', 'yoga'); ?></h3>

			<div class="yoga-checkout-payments" role="radiogroup" aria-label="<?php esc_attr_e('Способ оплаты', 'yoga'); ?>">
				<?php foreach ($methods as $index => $method) :
					$method_id = (string) $method['id'];
					$is_default = $method_id === $default_id || $index === 0;
					$icon_bg = (string) ($method['icon_bg'] ?? 'light');
					?>
					<label class="yoga-checkout-payment<?php echo $is_default ? ' is-active' : ''; ?><?php echo !empty($method['line_icon']) ? ' yoga-checkout-payment--line-icon' : ''; ?>">
						<input
							type="radio"
							class="yoga-checkout-payment__input"
							name="yoga_checkout_payment_type"
							value="<?php echo esc_attr($method_id); ?>"
							data-yookassa-type="<?php echo esc_attr((string) ($type_map[$method_id] ?? $method_id)); ?>"
							<?php checked($is_default); ?>
						>
						<span class="yoga-checkout-payment__icon yoga-checkout-payment__icon--<?php echo esc_attr($icon_bg); ?>" aria-hidden="true">
							<?php if (!empty($method['icon_sprite'])) : ?>
								<svg class="yoga-checkout-payment__icon-svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
									<use href="<?php echo $sprite_href; ?>#<?php echo esc_attr((string) $method['icon_sprite']); ?>"></use>
								</svg>
							<?php else : ?>
								<img
									class="yoga-checkout-payment__icon-img yoga-checkout-payment__icon-img--default"
									src="<?php echo esc_url((string) ($method['icon'] ?? '')); ?>"
									alt=""
									width="24"
									height="24"
									loading="lazy"
									decoding="async"
								>
								<?php if (!empty($method['icon_active'])) : ?>
									<img
										class="yoga-checkout-payment__icon-img yoga-checkout-payment__icon-img--active"
										src="<?php echo esc_url($method['icon_active']); ?>"
										alt=""
										width="24"
										height="24"
										loading="lazy"
										decoding="async"
									>
								<?php endif; ?>
							<?php endif; ?>
						</span>
						<span class="yoga-checkout-payment__label"><?php echo esc_html($method['label']); ?></span>
					</label>
				<?php endforeach; ?>
			</div>

			<input type="hidden" name="yoga_save_payment_method" id="yoga_save_payment_method_hidden" value="1">
			<label class="yoga-checkout-checkbox">
				<input type="checkbox" class="yoga-checkout-checkbox__input yoga-save-payment-method-cb" value="1" checked>
				<span class="yoga-checkout-checkbox__box" aria-hidden="true">
					<svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M2.5 7.2 5.8 10.5 11.5 3.8" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</span>
				<span class="yoga-checkout-checkbox__text"><?php esc_html_e('Сохранить метод оплаты для будущих покупок', 'yoga'); ?></span>
			</label>

			<?php if ($gateway_id !== '') : ?>
				<input type="hidden" name="payment_method" class="yoga-checkout-payment-method" value="<?php echo esc_attr($gateway_id); ?>">
			<?php endif; ?>

			<div class="yoga-checkout-wc-payment" aria-hidden="true">
				<?php woocommerce_checkout_payment(); ?>
			</div>
		</div>
		<?php
	}
}

add_action('wp_enqueue_scripts', 'yoga_enqueue_checkout_payment_script', 20);
function yoga_enqueue_checkout_payment_script(): void {
	if (!function_exists('is_checkout') || !is_checkout() || (function_exists('is_order_received_page') && is_order_received_page())) {
		return;
	}

	$theme_dir = get_template_directory();
	$script_path = $theme_dir . '/assets/js/checkout-payment.js';
	if (!is_readable($script_path)) {
		return;
	}

	wp_enqueue_script(
		'yoga-checkout-payment',
		get_template_directory_uri() . '/assets/js/checkout-payment.js',
		array('jquery', 'wc-checkout'),
		(string) filemtime($script_path),
		true
	);
}
