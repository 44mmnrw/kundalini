<?php

if (!defined('ABSPATH')) {
	exit;
}

require_once YTR_PLUGIN_DIR . 'includes/class-ytr-saved-cards.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-tariff.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-user.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-checkout.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-yookassa.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-orders.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-renewal.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-cron.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-admin.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-changelog.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-stub.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-card-binding.php';
require_once YTR_PLUGIN_DIR . 'includes/class-ytr-lk.php';

final class YTR_Plugin {
	private static ?self $instance = null;

	public static function instance(): self {
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init(): void {
		YTR_Checkout::init();
		YTR_Cron::init();
		YTR_Admin::init();
		YTR_Saved_Cards::init();
		YTR_Card_Binding::init();
		YTR_LK::init();

		add_action('woocommerce_payment_complete', array($this, 'on_payment_complete'), 30, 1);
		add_action('woocommerce_order_status_completed', array($this, 'on_order_completed'), 30, 1);
	}

	public static function activate(): void {
		YTR_Cron::schedule();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook(YTR_Cron::HOOK);
	}

	public function on_payment_complete(int $order_id): void {
		$this->maybe_activate_auto_renew_from_order($order_id);
	}

	public function on_order_completed(int $order_id): void {
		$this->maybe_activate_auto_renew_from_order($order_id);
	}

	private function maybe_activate_auto_renew_from_order(int $order_id): void {
		$order = wc_get_order($order_id);
		if (!$order instanceof WC_Order) {
			return;
		}

		if (class_exists('YTR_Card_Binding') && YTR_Card_Binding::is_binding_order($order)) {
			YTR_Card_Binding::complete_binding($order);
			return;
		}

		if (!YTR_Tariff::order_contains_tariff($order)) {
			return;
		}

		if ($order->get_meta('_ytr_renewal') === 'yes') {
			return;
		}

		if ($order->get_meta('_ytr_auto_renew_opt_in') !== 'yes') {
			return;
		}

		if (class_exists('YTR_Stub') && YTR_Stub::is_enabled()) {
			YTR_Stub::activate_from_tariff_order($order);
			return;
		}

		$user_id = (int) $order->get_customer_id();
		if ($user_id <= 0) {
			return;
		}

		$product_id = YTR_Tariff::get_tariff_product_id_from_order($order);
		if ($product_id <= 0) {
			return;
		}

		$payment_method_id = YTR_YooKassa::resolve_payment_method_id_for_order($order);
		if ($payment_method_id === '') {
			$order->add_order_note(__('Автопродление: способ оплаты не сохранён в ЮKassa.', 'yoga-tariff-renewal'));
			return;
		}

		YTR_User::enable_auto_renew($user_id, $product_id, $payment_method_id);
		YTR_Saved_Cards::sync_from_order($order);
		$order->add_order_note(__('Автопродление тарифа включено.', 'yoga-tariff-renewal'));
	}
}
