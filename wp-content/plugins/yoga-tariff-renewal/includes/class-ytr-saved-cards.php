<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Безопасное хранение способов оплаты для ЛК.
 * На сайте — только маска (last4, бренд, срок) и ID токена ЮKassa, без PAN/CVC.
 */
final class YTR_Saved_Cards {
	public const META_KEY = '_ytr_saved_cards';

	public static function init(): void {
		add_action('woocommerce_payment_complete', array(__CLASS__, 'sync_from_order_id'), 25, 1);
		add_action('woocommerce_order_status_completed', array(__CLASS__, 'sync_from_order_id'), 25, 1);
	}

	public static function sync_from_order_id(int $order_id): void {
		$order = wc_get_order($order_id);
		if ($order instanceof WC_Order) {
			self::sync_from_order($order);
		}
	}

	public static function sync_from_order(WC_Order $order): bool {
		$user_id = (int) $order->get_customer_id();
		if ($user_id <= 0) {
			return false;
		}

		if (!self::is_yookassa_order($order)) {
			return false;
		}

		$payment_id = (string) $order->get_transaction_id();
		if ($payment_id === '' || !class_exists('YooKassaClientFactory')) {
			return false;
		}

		try {
			$payment = YooKassaClientFactory::getYooKassaClient()->getPaymentInfo($payment_id);
		} catch (Exception $e) {
			return false;
		}

		$card_data = self::build_card_from_payment($payment, $order);
		if ($card_data === null) {
			return false;
		}

		self::upsert_card($user_id, $card_data);

		return true;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_cards(int $user_id): array {
		if ($user_id <= 0) {
			return array();
		}

		$cards = get_user_meta($user_id, self::META_KEY, true);
		if (!is_array($cards)) {
			return array();
		}

		return array_values(array_filter($cards, 'is_array'));
	}

	/**
	 * Формат для шаблона ЛК (get_user_saved_cards).
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_cards_for_lk(int $user_id): array {
		$auto_method = class_exists('YTR_User') ? YTR_User::get_payment_method_id($user_id) : '';
		$result      = array();

		foreach (self::get_cards($user_id) as $card) {
			$card_id = (string) ($card['id'] ?? '');
			if ($card_id === '') {
				continue;
			}

			$card_pm = (string) ($card['payment_method_id'] ?? '');
			$is_auto = $auto_method !== ''
				&& ($card_pm === $auto_method || $card_id === $auto_method);

			$result[] = array(
				'id'        => $card_id,
				'brand'     => self::format_brand_for_display((string) ($card['brand'] ?? __('Карта', 'yoga-tariff-renewal'))),
				'last4'     => (string) ($card['last4'] ?? '****'),
				'type'      => (string) ($card['type'] ?? 'default'),
				'exp_month' => (string) ($card['exp_month'] ?? ''),
				'exp_year'  => (string) ($card['exp_year'] ?? ''),
				'recurring' => !empty($card['recurring']) ? '1' : '0',
				'is_auto'   => $is_auto ? '1' : '0',
			);
		}

		return $result;
	}

	public static function remove_card(int $user_id, string $card_id): bool {
		$card_id = sanitize_text_field($card_id);
		if ($user_id <= 0 || $card_id === '') {
			return false;
		}

		$cards   = self::get_cards($user_id);
		$removed = null;
		$next    = array();

		foreach ($cards as $card) {
			if (!is_array($card) || (string) ($card['id'] ?? '') !== $card_id) {
				$next[] = $card;
				continue;
			}
			$removed = $card;
		}

		if ($removed === null) {
			return false;
		}

		update_user_meta($user_id, self::META_KEY, $next);

		if (
			class_exists('YTR_User')
			&& !empty($removed['payment_method_id'])
			&& YTR_User::get_payment_method_id($user_id) === (string) $removed['payment_method_id']
		) {
			YTR_User::disable_auto_renew($user_id);
		}

		return true;
	}

	/**
	 * @param array<string, mixed> $card_data
	 */
	private static function upsert_card(int $user_id, array $card_data): void {
		$cards   = self::get_cards($user_id);
		$card_id = (string) ($card_data['id'] ?? '');
		$next    = array();
		$found   = false;

		foreach ($cards as $card) {
			if (!is_array($card)) {
				continue;
			}
			if ((string) ($card['id'] ?? '') === $card_id) {
				$next[]  = array_merge($card, $card_data);
				$found   = true;
				continue;
			}
			$next[] = $card;
		}

		if (!$found) {
			$next[] = $card_data;
		}

		update_user_meta($user_id, self::META_KEY, $next);
	}

	private static function is_yookassa_order(WC_Order $order): bool {
		$method = (string) $order->get_payment_method();
		return str_contains($method, 'yookassa');
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function build_card_from_payment($payment, WC_Order $order): ?array {
		if (!is_object($payment) || !method_exists($payment, 'getPaymentMethod')) {
			return null;
		}

		$method = $payment->getPaymentMethod();
		if (!$method || !method_exists($method, 'getType') || $method->getType() !== 'bank_card') {
			return null;
		}

		if (!method_exists($method, 'getCard')) {
			return null;
		}

		$card = $method->getCard();
		if (!$card) {
			return null;
		}

		$last4 = method_exists($card, 'getLast4') ? (string) $card->getLast4() : '';
		if ($last4 === '') {
			return null;
		}

		$brand    = method_exists($card, 'getCardType') ? (string) $card->getCardType() : 'Card';
		$exp_m    = method_exists($card, 'getExpiryMonth') ? (string) $card->getExpiryMonth() : '';
		$exp_y    = method_exists($card, 'getExpiryYear') ? (string) $card->getExpiryYear() : '';
		$saved    = method_exists($method, 'getSaved') && $method->getSaved();
		$method_id = ($saved && method_exists($method, 'getId')) ? (string) $method->getId() : '';
		$payment_id = method_exists($payment, 'getId') ? (string) $payment->getId() : '';

		return array(
			'id'                => $method_id !== '' ? $method_id : ('pay_' . $payment_id),
			'payment_method_id' => $method_id,
			'brand'             => self::format_brand_for_display(self::normalize_brand($brand)),
			'last4'             => $last4,
			'type'              => self::icon_slug_from_brand($brand),
			'exp_month'         => $exp_m,
			'exp_year'          => $exp_y,
			'recurring'         => $saved,
			'order_id'          => $order->get_id(),
			'saved_at'          => time(),
		);
	}

	private static function normalize_brand(string $brand): string {
		$map = array(
			'mastercard' => 'Mastercard',
			'visa'       => 'Visa',
			'mir'        => 'Мир',
			'maestro'    => 'Maestro',
		);
		$key = strtolower($brand);

		return $map[$key] ?? $brand;
	}

	public static function format_brand_for_display(string $brand): string {
		$key = strtolower(trim($brand));

		if ($key === 'mir') {
			return 'Мир';
		}

		return $brand;
	}

	private static function icon_slug_from_brand(string $brand): string {
		$key = strtolower($brand);
		if (str_contains($key, 'master')) {
			return 'mastercard';
		}
		if (str_contains($key, 'visa')) {
			return 'visa';
		}
		if (str_contains($key, 'mir')) {
			return 'mir';
		}
		if (str_contains($key, 'maestro')) {
			return 'maestro';
		}

		return 'default';
	}
}
