<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Временная заглушка рекуррентных платежей (верификация до подключения автоплатежей в ЮKassa).
 * Отключить: WooCommerce → Автопродление → снять «Режим заглушки».
 */
final class YTR_Stub {
	public const OPTION = 'ytr_stub_recurring';

	public const PAYMENT_METHOD_PREFIX = 'ytr_stub_pm_';

	public static function is_enabled(): bool {
		return get_option(self::OPTION, 'no') === 'yes';
	}

	public static function is_stub_payment_method(string $payment_method_id): bool {
		return str_starts_with($payment_method_id, self::PAYMENT_METHOD_PREFIX);
	}

	public static function complete_binding_order(WC_Order $order): bool {
		if (!self::is_enabled() || !class_exists('YTR_Card_Binding') || !YTR_Card_Binding::is_binding_order($order)) {
			return false;
		}

		if ($order->get_meta('_ytr_card_binding_done') === 'yes') {
			return true;
		}

		$user_id = (int) $order->get_customer_id();
		if ($user_id <= 0) {
			return false;
		}

		if (!$order->is_paid()) {
			$order->payment_complete('ytr_stub_bind_' . $order->get_id());
		}

		$payment_method_id = self::assign_stub_card($user_id, $order->get_id());
		$product_id        = 0;

		if (class_exists('YTR_Tariff')) {
			$tariff = YTR_Tariff::get_active_tariff($user_id);
			if (is_array($tariff) && !empty($tariff['product_id'])) {
				$product_id = (int) $tariff['product_id'];
			}
		}

		if ($product_id > 0 && class_exists('YTR_User')) {
			YTR_User::enable_auto_renew($user_id, $product_id, $payment_method_id);
			$order->add_order_note(__('ЗАГЛУШКА: карта привязана. Автопродление тарифа включено.', 'yoga-tariff-renewal'));
		} else {
			$order->add_order_note(__('ЗАГЛУШКА: карта привязана для будущих автоплатежей.', 'yoga-tariff-renewal'));
		}

		$order->update_meta_data('_ytr_stub_binding', 'yes');
		$order->update_meta_data('_ytr_card_binding_done', 'yes');
		$order->save();

		return true;
	}

	public static function activate_from_tariff_order(WC_Order $order): bool {
		if (!self::is_enabled() || $order->get_meta('_ytr_auto_renew_opt_in') !== 'yes') {
			return false;
		}

		if (!class_exists('YTR_Tariff') || !YTR_Tariff::order_contains_tariff($order)) {
			return false;
		}

		if ($order->get_meta('_ytr_renewal') === 'yes') {
			return false;
		}

		$user_id = (int) $order->get_customer_id();
		if ($user_id <= 0) {
			return false;
		}

		$product_id = YTR_Tariff::get_tariff_product_id_from_order($order);
		if ($product_id <= 0) {
			return false;
		}

		$payment_method_id = self::assign_stub_card($user_id, $order->get_id());
		YTR_User::enable_auto_renew($user_id, $product_id, $payment_method_id);
		$order->update_meta_data('_ytr_stub_tariff', 'yes');
		$order->add_order_note(__('ЗАГЛУШКА: автопродление тарифа включено (без save_payment_method в ЮKassa).', 'yoga-tariff-renewal'));
		$order->save();

		return true;
	}

	/**
	 * @return array{success:bool,payment_id:string,status:string,message:string}
	 */
	public static function stub_charge_renewal(WC_Order $order): array {
		$payment_id = 'ytr_stub_pay_' . $order->get_id();
		$order->payment_complete($payment_id);

		if ($order->has_status('processing')) {
			$order->update_status('completed', __('ЗАГЛУШКА: автопродление тарифа.', 'yoga-tariff-renewal'));
		}

		$order->add_order_note(__('ЗАГЛУШКА: рекуррентный платёж имитирован.', 'yoga-tariff-renewal'));
		$order->save();

		return array(
			'success'    => true,
			'payment_id' => $payment_id,
			'status'     => 'succeeded',
			'message'    => __('ЗАГЛУШКА: оплачено', 'yoga-tariff-renewal'),
		);
	}

	public static function assign_stub_card(int $user_id, int $order_id = 0): string {
		$payment_method_id = self::PAYMENT_METHOD_PREFIX . $user_id . '_' . strtolower(wp_generate_password(12, false, false));

		if (class_exists('YTR_Saved_Cards')) {
			YTR_Saved_Cards::upsert_card_for_user(
				$user_id,
				array(
					'id'                => $payment_method_id,
					'payment_method_id' => $payment_method_id,
					'brand'             => 'Visa',
					'last4'             => '4242',
					'type'              => 'visa',
					'exp_month'         => '12',
					'exp_year'          => '30',
					'recurring'         => true,
					'stub'              => true,
					'order_id'          => $order_id,
					'saved_at'          => time(),
				)
			);
		}

		return $payment_method_id;
	}
}
