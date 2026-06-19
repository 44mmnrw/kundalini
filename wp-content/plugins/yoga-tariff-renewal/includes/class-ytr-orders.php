<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Orders {
	private const META_RENEWAL = '_ytr_renewal';
	private const META_IDEMPOTENCE_KEY = '_ytr_yookassa_idempotence_key';

	/**
	 * @return WC_Order|WP_Error
	 */
	public static function create_renewal_order(int $user_id, int $product_id) {
		$user = get_user_by('id', $user_id);
		if (!$user instanceof WP_User) {
			return new WP_Error('ytr_user', 'Пользователь не найден');
		}

		$product = wc_get_product($product_id);
		if (!$product instanceof WC_Product) {
			return new WP_Error('ytr_product', 'Товар тарифа не найден');
		}

		$order = wc_create_order(
			array(
				'customer_id' => $user_id,
				'status'      => 'pending',
			)
		);

		if (is_wp_error($order)) {
			return $order;
		}

		$order->add_product($product, 1);

		$billing_email = (string) get_user_meta($user_id, 'billing_email', true);
		if ($billing_email === '') {
			$billing_email = $user->user_email;
		}

		$order->set_billing_email($billing_email);
		$order->set_billing_first_name((string) get_user_meta($user_id, 'billing_first_name', true));
		$order->set_billing_last_name((string) get_user_meta($user_id, 'billing_last_name', true));
		$order->set_billing_phone((string) get_user_meta($user_id, 'billing_phone', true));

		$order->set_payment_method('yookassa_epl');
		$order->set_payment_method_title(__('ЮKassa (автопродление)', 'yoga-tariff-renewal'));

		$order->update_meta_data(self::META_RENEWAL, 'yes');
		$order->calculate_totals();
		self::get_renewal_idempotence_key($order);
		$order->save();

		return $order;
	}

	public static function get_renewal_idempotence_key(WC_Order $order): string {
		$key = (string) $order->get_meta(self::META_IDEMPOTENCE_KEY);
		if ($key !== '') {
			return $key;
		}

		$key = 'ytr-renewal-order-' . $order->get_id();
		$order->update_meta_data(self::META_IDEMPOTENCE_KEY, $key);
		$order->save();

		return $key;
	}

	public static function has_recent_renewal_attempt(int $user_id, int $within_seconds = DAY_IN_SECONDS): bool {
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 5,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'meta_key'    => self::META_RENEWAL,
				'meta_value'  => 'yes',
			)
		);

		foreach ($orders as $order) {
			if (!$order instanceof WC_Order) {
				continue;
			}

			if ($order->has_status(array('pending', 'on-hold')) && class_exists('YTR_YooKassa')) {
				YTR_YooKassa::sync_order_payment_status($order);
				$order = wc_get_order($order->get_id());
				if (!$order instanceof WC_Order) {
					continue;
				}
			}

			if ($order->has_status(array('processing', 'completed'))) {
				return true;
			}

			if ($order->has_status(array('pending', 'on-hold'))) {
				return true;
			}
		}

		return false;
	}
}
