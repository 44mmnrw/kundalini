<?php
/**
 * Связка checkout с ЮKassa: выбор шлюза, тип способа оплаты, редирект.
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/yookassa-gateway.php';

if (!function_exists('yoga_yookassa_payment_type_map')) {
	/**
	 * @return array<string, string>
	 */
	function yoga_yookassa_payment_type_map(): array {
		return array(
			'sbp'          => 'sbp',
			'bank_card'    => 'bank_card',
			'sberpay'      => 'sberbank',
			'tinkoff_bank' => 'tinkoff_bank',
			'yoo_money'    => 'yoo_money',
			// 'yandex_pay'   => 'bank_card',
		);
	}
}

if (!function_exists('yoga_yookassa_gateway_candidates')) {
	/**
	 * @return string[]
	 */
	function yoga_yookassa_gateway_candidates(): array {
		if (get_option('yookassa_pay_mode') === '0') {
			return array('yookassa_widget', 'yookassa_epl');
		}

		return array('yookassa_epl', 'yookassa_widget');
	}
}

if (!function_exists('yoga_yookassa_bootstrap_epl_gateway')) {
	/**
	 * После мастера ЮKassa EPL иногда не создаётся в WooCommerce → Платежи.
	 */
	function yoga_yookassa_bootstrap_epl_gateway(): void {
		if (!get_option('yookassa_shop_id')) {
			return;
		}

		if (get_option('woocommerce_yookassa_epl_settings', false) !== false) {
			return;
		}

		update_option(
			'woocommerce_yookassa_epl_settings',
			array(
				'enabled'             => 'yes',
				'title'               => '',
				'description'         => '',
				'save_payment_method' => 'no',
			)
		);
	}
}
add_action('init', 'yoga_yookassa_bootstrap_epl_gateway', 20);

if (!function_exists('yoga_get_checkout_yookassa_gateway_id')) {
	function yoga_get_checkout_yookassa_gateway_id(): string {
		if (!function_exists('WC') || !WC()->payment_gateways()) {
			return '';
		}

		$available = WC()->payment_gateways()->get_available_payment_gateways();

		foreach (yoga_yookassa_gateway_candidates() as $gateway_id) {
			if (isset($available[$gateway_id])) {
				return $gateway_id;
			}
		}

		foreach ($available as $gateway_id => $gateway) {
			if (
				strpos($gateway_id, 'yookassa') === 0
				&& is_object($gateway)
				&& property_exists($gateway, 'pluginKey')
				&& $gateway->pluginKey === 'yookassa'
			) {
				return $gateway_id;
			}
		}

		return '';
	}
}

if (!function_exists('yoga_yookassa_get_merchant_payment_method_types')) {
	/**
	 * Список type из API /me (кэш плагина ЮKassa).
	 *
	 * @return string[]
	 */
	function yoga_yookassa_get_merchant_payment_method_types(bool $force_refresh = false): array {
		if ($force_refresh) {
			delete_transient('yoga_yookassa_merchant_payment_methods');
		}

		$cached = get_transient('yoga_yookassa_merchant_payment_methods');
		if (is_array($cached)) {
			return $cached;
		}

		$types = array();
		if (class_exists('YooKassaAdmin')) {
			$shop_info = YooKassaAdmin::getShopInfo($force_refresh);
			if (is_array($shop_info) && !empty($shop_info['payment_methods']) && is_array($shop_info['payment_methods'])) {
				$types = array_values(array_map('strval', $shop_info['payment_methods']));
			}
		}

		set_transient('yoga_yookassa_merchant_payment_methods', $types, 15 * MINUTE_IN_SECONDS);

		return $types;
	}
}

if (!function_exists('yoga_yookassa_get_payment_method_label')) {
	function yoga_yookassa_get_payment_method_label(string $api_type): string {
		$labels = array(
			'sbp'          => 'СБП',
			'tinkoff_bank' => 'T-Pay',
			'sberbank'     => 'SberPay',
			'bank_card'    => __('Банковская карта', 'yoga'),
			'yoo_money'    => 'YooMoney',
		);

		return $labels[$api_type] ?? $api_type;
	}
}

if (!function_exists('yoga_yookassa_is_merchant_type_available')) {
	function yoga_yookassa_is_merchant_type_available(string $api_type): bool {
		if ($api_type === '') {
			return false;
		}

		return in_array($api_type, yoga_yookassa_get_merchant_payment_method_types(), true);
	}
}

if (!function_exists('yoga_yookassa_is_checkout_payment_request')) {
	function yoga_yookassa_is_checkout_payment_request(): bool {
		if (!empty($_REQUEST['wc-ajax']) && $_REQUEST['wc-ajax'] === 'checkout') {
			return true;
		}

		return function_exists('yoga_is_theme_checkout_context') && yoga_is_theme_checkout_context();
	}
}

if (!function_exists('yoga_get_checkout_payment_type_slug')) {
	function yoga_get_checkout_payment_type_slug(): string {
		if (!empty($_POST['yoga_checkout_payment_type'])) {
			return sanitize_key(wp_unslash($_POST['yoga_checkout_payment_type']));
		}

		if (function_exists('WC') && WC()->session) {
			$session_type = sanitize_key((string) WC()->session->get('yoga_yookassa_payment_type'));
			if ($session_type !== '') {
				return $session_type;
			}
		}

		$order = yoga_yookassa_get_checkout_order();
		if ($order instanceof WC_Order) {
			return sanitize_key((string) $order->get_meta('_yoga_checkout_payment_type'));
		}

		return '';
	}
}

if (!function_exists('yoga_store_checkout_payment_type_in_session')) {
	function yoga_store_checkout_payment_type_in_session(): void {
		if (!function_exists('WC') || !WC()->session || empty($_POST['yoga_checkout_payment_type'])) {
			return;
		}

		WC()->session->set(
			'yoga_yookassa_payment_type',
			sanitize_key(wp_unslash($_POST['yoga_checkout_payment_type']))
		);
	}
}
add_action('woocommerce_checkout_process', 'yoga_store_checkout_payment_type_in_session', 1);

if (!function_exists('yoga_yookassa_apply_gateway_to_posted_checkout_data')) {
	/**
	 * До валидации WC: СБП / T-Pay / SberPay должны идти через EPL (redirect), не виджет.
	 */
	function yoga_yookassa_apply_gateway_to_posted_checkout_data(array $data): array {
		if (empty($data['yoga_checkout_payment_type'])) {
			return $data;
		}

		$slug = sanitize_key((string) $data['yoga_checkout_payment_type']);
		$api  = yoga_map_checkout_payment_type_to_api($slug);
		if ($api !== '' && in_array($api, yoga_yookassa_redirect_confirmation_types(), true)) {
			yoga_yookassa_ensure_epl_gateway_enabled();
			$data['payment_method'] = 'yookassa_epl';
		}

		return $data;
	}
}
add_filter('woocommerce_checkout_posted_data', 'yoga_yookassa_apply_gateway_to_posted_checkout_data', 5);

if (!function_exists('yoga_save_checkout_payment_type_to_order')) {
	function yoga_save_checkout_payment_type_to_order(WC_Order $order): void {
		$slug = yoga_get_checkout_payment_type_slug();
		if ($slug === '') {
			return;
		}

		$order->update_meta_data('_yoga_checkout_payment_type', $slug);
	}
}
add_action('woocommerce_checkout_create_order', 'yoga_save_checkout_payment_type_to_order', 10, 1);

if (!function_exists('yoga_map_checkout_payment_type_to_api')) {
	function yoga_map_checkout_payment_type_to_api(string $slug): string {
		if ($slug === '') {
			return '';
		}

		$map = yoga_yookassa_payment_type_map();

		return (string) ($map[$slug] ?? '');
	}
}

if (!function_exists('yoga_get_selected_yookassa_payment_type_for_api')) {
	/**
	 * Тип для API ЮKassa — по выбору пользователя, без фильтра по кэшу /me.
	 */
	function yoga_get_selected_yookassa_payment_type_for_api(): string {
		$slug = yoga_get_checkout_payment_type_slug();
		if ($slug === '') {
			return '';
		}

		return yoga_map_checkout_payment_type_to_api($slug);
	}
}

if (!function_exists('yoga_get_selected_yookassa_payment_type')) {
	/**
	 * Тип с учётом доступности у мерчанта — для UI checkout.
	 */
	function yoga_get_selected_yookassa_payment_type(): string {
		$api_type = yoga_get_selected_yookassa_payment_type_for_api();
		if ($api_type === '' || !yoga_yookassa_is_merchant_type_available($api_type)) {
			return '';
		}

		return $api_type;
	}
}

if (!function_exists('yoga_yookassa_redirect_confirmation_types')) {
	/**
	 * Способы, которые API принимает только с confirmation.type=redirect.
	 *
	 * @return string[]
	 */
	function yoga_yookassa_redirect_confirmation_types(): array {
		return array('sbp', 'sberbank', 'tinkoff_bank');
	}
}

if (!function_exists('yoga_yookassa_set_checkout_order')) {
	function yoga_yookassa_set_checkout_order(WC_Order $order): void {
		$GLOBALS['yoga_yookassa_checkout_order'] = $order;
	}
}
add_action('woocommerce_checkout_create_order', 'yoga_yookassa_set_checkout_order', 1, 1);

if (!function_exists('yoga_yookassa_clear_checkout_order')) {
	function yoga_yookassa_clear_checkout_order(): void {
		unset($GLOBALS['yoga_yookassa_checkout_order']);
	}
}
add_action('woocommerce_checkout_order_processed', 'yoga_yookassa_clear_checkout_order', 999);

if (!function_exists('yoga_yookassa_get_checkout_order')) {
	function yoga_yookassa_get_checkout_order(): ?WC_Order {
		$order = $GLOBALS['yoga_yookassa_checkout_order'] ?? null;

		return $order instanceof WC_Order ? $order : null;
	}
}

if (!function_exists('yoga_yookassa_get_return_url_for_order')) {
	function yoga_yookassa_get_return_url_for_order(WC_Order $order): string {
		$pattern = '?yookassa=returnUrl&yookassa-order-id=%s';
		if (class_exists('YooKassaGateway') && method_exists('YooKassaGateway', 'getReturnUrlPattern')) {
			$pattern = YooKassaGateway::getReturnUrlPattern();
		}

		return get_site_url(null, sprintf($pattern, $order->get_order_key()));
	}
}

if (!function_exists('yoga_yookassa_create_payment_data')) {
	/**
	 * @return \YooKassa\Model\PaymentData\AbstractPaymentData|null
	 */
	function yoga_yookassa_create_payment_data(string $type) {
		if ($type === '' || !class_exists('YooKassa\Model\PaymentData\PaymentDataFactory')) {
			return null;
		}

		try {
			$factory = new YooKassa\Model\PaymentData\PaymentDataFactory();

			return $factory->factory($type);
		} catch (Exception $e) {
			return null;
		}
	}
}

if (!function_exists('yoga_yookassa_apply_payment_type_to_request')) {
	/**
	 * @param \YooKassa\Request\Payments\CreatePaymentRequest $paymentRequest
	 */
	function yoga_yookassa_apply_payment_type_to_request($paymentRequest) {
		if (!method_exists($paymentRequest, 'setPaymentMethodData')) {
			return $paymentRequest;
		}

		$type = yoga_get_selected_yookassa_payment_type_for_api();
		if ($type === '') {
			return $paymentRequest;
		}

		$payment_data = yoga_yookassa_create_payment_data($type);
		if ($payment_data === null) {
			return $paymentRequest;
		}

		$paymentRequest->setPaymentMethodData($payment_data);

		if (in_array($type, yoga_yookassa_redirect_confirmation_types(), true)) {
		$order = yoga_yookassa_get_checkout_order();
			if ($order instanceof WC_Order && method_exists($paymentRequest, 'setConfirmation')) {
				$paymentRequest->setConfirmation(
					array(
						'type'      => 'redirect',
						'returnUrl' => yoga_yookassa_get_return_url_for_order($order),
					)
				);
			} elseif (function_exists('WC') && WC()->session) {
				$pending_order_id = absint(WC()->session->get('order_awaiting_payment'));
				if ($pending_order_id > 0) {
					$pending_order = wc_get_order($pending_order_id);
					if ($pending_order instanceof WC_Order && method_exists($paymentRequest, 'setConfirmation')) {
						$paymentRequest->setConfirmation(
							array(
								'type'      => 'redirect',
								'returnUrl' => yoga_yookassa_get_return_url_for_order($pending_order),
							)
						);
					}
				}
			}

			if (method_exists($paymentRequest, 'setSavePaymentMethod')) {
				$paymentRequest->setSavePaymentMethod(false);
			}
		}

		// СБП не поддерживает двухстадийные платежи (capture=false / hold).
		if ($type === 'sbp' && method_exists($paymentRequest, 'setCapture')) {
			$paymentRequest->setCapture(true);
		}

		return $paymentRequest;
	}
}
add_filter('woocommerce_yookassa_create_payment_request', 'yoga_yookassa_apply_payment_type_to_request', 20);

if (!function_exists('yoga_yookassa_ensure_epl_gateway_enabled')) {
	function yoga_yookassa_ensure_epl_gateway_enabled(): void {
		yoga_yookassa_bootstrap_epl_gateway();

		$settings = get_option('woocommerce_yookassa_epl_settings', array());
		if (!is_array($settings)) {
			$settings = array();
		}

		if (($settings['enabled'] ?? '') === 'yes') {
			return;
		}

		$settings['enabled'] = 'yes';
		update_option('woocommerce_yookassa_epl_settings', $settings);
	}
}

if (!function_exists('yoga_yookassa_needs_redirect_gateway')) {
	function yoga_yookassa_needs_redirect_gateway(): bool {
		$api_type = yoga_get_selected_yookassa_payment_type_for_api();

		return $api_type !== '' && in_array($api_type, yoga_yookassa_redirect_confirmation_types(), true);
	}
}

if (!function_exists('yoga_yookassa_register_payment_gateways')) {
	/**
	 * EPL всегда в списке (нужен для СБП / T-Pay / SberPay с redirect).
	 */
	function yoga_yookassa_register_payment_gateways(array $methods): array {
		if (!get_option('yookassa_shop_id')) {
			return $methods;
		}

		yoga_yookassa_bootstrap_epl_gateway();

		$gateway_class = class_exists('Yoga_YooKassa_Gateway_EPL') ? 'Yoga_YooKassa_Gateway_EPL' : 'YooKassaGatewayEPL';
		$has_custom      = in_array('Yoga_YooKassa_Gateway_EPL', $methods, true);
		$has_epl         = in_array('YooKassaGatewayEPL', $methods, true);

		if (!$has_custom && !$has_epl) {
			$methods[] = $gateway_class;
		}

		foreach ($methods as $index => $class_name) {
			if ($class_name === 'YooKassaGatewayEPL' && class_exists('Yoga_YooKassa_Gateway_EPL')) {
				$methods[$index] = 'Yoga_YooKassa_Gateway_EPL';
			}
		}

		return $methods;
	}
}
add_filter('woocommerce_payment_gateways', 'yoga_yookassa_register_payment_gateways', 50);

if (!function_exists('yoga_yookassa_capture_payment_api_response')) {
	function yoga_yookassa_capture_payment_api_response(array $response, array $args, string $url): array {
		if (!function_exists('WC') || !WC()->session || !yoga_yookassa_is_checkout_payment_request()) {
			return $response;
		}

		if (
			(strpos($url, 'api.yookassa.ru') === false && strpos($url, 'yoomoney.ru') === false)
			|| strpos($url, '/payments') === false
		) {
			return $response;
		}

		$method = strtoupper((string) ($args['method'] ?? 'GET'));
		if ($method !== 'POST') {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		if ($code !== 200) {
			return $response;
		}

		$body = json_decode((string) wp_remote_retrieve_body($response), true);
		if (!is_array($body)) {
			return $response;
		}

		$confirmation = $body['confirmation'] ?? null;
		if (is_array($confirmation) && !empty($confirmation['confirmation_url'])) {
			WC()->session->set('yoga_yookassa_confirmation_url', (string) $confirmation['confirmation_url']);
		}

		return $response;
	}
}
add_filter('http_response', 'yoga_yookassa_capture_payment_api_response', 10, 3);

if (!function_exists('yoga_yookassa_get_payment_confirmation_redirect_url')) {
	/**
	 * СБП и ряд способов оплаты требуют confirmation.type=redirect (страница ЮKassa с QR).
	 *
	 * @see https://yookassa.ru/developers/payment-acceptance/integration-scenarios/manual-integration/other/sbp
	 */
	function yoga_yookassa_get_payment_confirmation_redirect_url(WC_Order $order): string {
		$payment_id = (string) $order->get_transaction_id();
		if ($payment_id === '' || !class_exists('YooKassaClientFactory')) {
			return '';
		}

		try {
			$payment = YooKassaClientFactory::getYooKassaClient()->getPaymentInfo($payment_id);
		} catch (Exception $e) {
			return '';
		}

		$confirmation = $payment->getConfirmation();
		if (!$confirmation || !method_exists($confirmation, 'getType')) {
			return '';
		}

		if ($confirmation->getType() !== 'redirect') {
			return '';
		}

		return method_exists($confirmation, 'getConfirmationUrl')
			? (string) $confirmation->getConfirmationUrl()
			: '';
	}
}

if (!function_exists('yoga_yookassa_redirect_confirmation_url_on_success')) {
	/**
	 * В режиме виджета плагин ведёт на order-pay, хотя СБП ждёт redirect на confirmation_url.
	 */
	function yoga_yookassa_redirect_confirmation_url_on_success(array $result, int $order_id): array {
		if (($result['result'] ?? '') !== 'success') {
			return $result;
		}

		$confirmation_url = '';
		if (function_exists('WC') && WC()->session) {
			$confirmation_url = (string) WC()->session->get('yoga_yookassa_confirmation_url');
			if ($confirmation_url !== '') {
				WC()->session->__unset('yoga_yookassa_confirmation_url');
			}
		}

		$order = wc_get_order($order_id);
		if ($confirmation_url === '' && $order instanceof WC_Order) {
			$confirmation_url = yoga_yookassa_get_payment_confirmation_redirect_url($order);
		}

		if ($confirmation_url === '' && $order instanceof WC_Order && yoga_yookassa_needs_redirect_gateway()) {
			return $result;
		}

		if ($confirmation_url !== '') {
			$result['redirect'] = $confirmation_url;
		}

		return $result;
	}
}
add_filter('woocommerce_payment_successful_result', 'yoga_yookassa_redirect_confirmation_url_on_success', 5, 2);

if (!function_exists('yoga_yookassa_redirect_confirmation_url_on_pay_page')) {
	/**
	 * Fallback: прямой заход на order-pay (обновление страницы, повтор оплаты).
	 */
	function yoga_yookassa_redirect_confirmation_url_on_pay_page(): void {
		if (!function_exists('is_checkout_pay_page') || !is_checkout_pay_page()) {
			return;
		}

		global $wp;
		$order_id = isset($wp->query_vars['order-pay']) ? absint($wp->query_vars['order-pay']) : 0;
		if ($order_id <= 0) {
			return;
		}

		$order = wc_get_order($order_id);
		if (!$order instanceof WC_Order) {
			return;
		}

		$confirmation_url = yoga_yookassa_get_payment_confirmation_redirect_url($order);
		if ($confirmation_url === '') {
			return;
		}

		wp_safe_redirect($confirmation_url);
		exit;
	}
}
add_action('template_redirect', 'yoga_yookassa_redirect_confirmation_url_on_pay_page', 1);

if (!function_exists('yoga_yookassa_force_checkout_payment_method')) {
	function yoga_yookassa_force_checkout_payment_method(): void {
		if (!yoga_yookassa_is_checkout_payment_request()) {
			return;
		}

		$gateway_id = yoga_get_checkout_yookassa_gateway_id();
		if ($gateway_id === '') {
			return;
		}

		$_POST['payment_method'] = $gateway_id;
	}
}
add_action('woocommerce_checkout_process', 'yoga_yookassa_force_checkout_payment_method', 5);

add_filter('woocommerce_available_payment_gateways', 'yoga_yookassa_checkout_payment_gateways', 20);
function yoga_yookassa_checkout_payment_gateways(array $gateways): array {
	if (!function_exists('yoga_is_theme_checkout_context') || !yoga_is_theme_checkout_context()) {
		return $gateways;
	}

	$yookassa_id = '';
	$candidates  = yoga_yookassa_gateway_candidates();

	if (yoga_yookassa_needs_redirect_gateway() && isset($gateways['yookassa_epl'])) {
		$yookassa_id = 'yookassa_epl';
	}

	foreach ($candidates as $candidate) {
		if ($yookassa_id !== '') {
			break;
		}
		if (isset($gateways[$candidate])) {
			$yookassa_id = $candidate;
			break;
		}
	}

	if ($yookassa_id === '') {
		foreach ($gateways as $gateway_id => $gateway) {
			if (
				strpos($gateway_id, 'yookassa') === 0
				&& is_object($gateway)
				&& property_exists($gateway, 'pluginKey')
				&& $gateway->pluginKey === 'yookassa'
			) {
				$yookassa_id = $gateway_id;
				break;
			}
		}
	}

	if ($yookassa_id === '') {
		return $gateways;
	}

	return array(
		$yookassa_id => $gateways[$yookassa_id],
	);
}

add_action('woocommerce_before_checkout_form', 'yoga_yookassa_checkout_missing_gateway_notice', 5);
function yoga_yookassa_checkout_missing_gateway_notice(): void {
	if (!function_exists('yoga_is_theme_checkout_context') || !yoga_is_theme_checkout_context()) {
		return;
	}
	if (!function_exists('wc_add_notice') || !function_exists('WC')) {
		return;
	}
	if (yoga_get_checkout_yookassa_gateway_id() !== '') {
		return;
	}
	if (!WC()->cart || WC()->cart->is_empty()) {
		return;
	}

	wc_add_notice(
		__(
			'Онлайн-оплата недоступна. Проверьте: WooCommerce → Настройки ЮKassa (сценарий «Умный платёж») и WooCommerce → Платежи — переключатель у способа «ЮKassa» / «Онлайн-оплата».',
			'yoga'
		),
		'error'
	);
}

if (!function_exists('yoga_yookassa_capture_api_error_response')) {
	function yoga_yookassa_capture_api_error_response(array $response, array $args, string $url): array {
		if (!function_exists('WC') || !WC()->session || !yoga_yookassa_is_checkout_payment_request()) {
			return $response;
		}

		if (strpos($url, 'api.yookassa.ru') === false && strpos($url, 'yoomoney.ru') === false) {
			return $response;
		}

		if (strpos($url, '/payments') === false) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		if ($code < 400) {
			return $response;
		}

		$body = json_decode((string) wp_remote_retrieve_body($response), true);
		if (!is_array($body)) {
			return $response;
		}

		$parts = array();
		if (!empty($body['description'])) {
			$parts[] = (string) $body['description'];
		}
		if (!empty($body['parameter'])) {
			$parts[] = 'Параметр: ' . (string) $body['parameter'];
		}
		if (!empty($body['code'])) {
			$parts[] = 'Код: ' . (string) $body['code'];
		}

		if ($parts !== array()) {
			WC()->session->set('yoga_yookassa_api_error', implode('. ', $parts));
		}

		return $response;
	}
}
add_filter('http_response', 'yoga_yookassa_capture_api_error_response', 10, 3);

if (!function_exists('yoga_yookassa_append_api_error_to_notices')) {
	function yoga_yookassa_append_api_error_to_notices(array $notices): array {
		if (empty($notices['error']) || !function_exists('WC') || !WC()->session) {
			return $notices;
		}

		$api_error = (string) WC()->session->get('yoga_yookassa_api_error');
		if ($api_error === '') {
			return $notices;
		}

		$appended = false;
		foreach ($notices['error'] as $key => $notice) {
			$text = is_array($notice) ? (string) ($notice['notice'] ?? '') : (string) $notice;
			if (strpos($text, 'Платеж не прошел') !== false || strpos($text, 'Платеж не прошёл') !== false) {
				$notices['error'][$key]['notice'] = $text . ' ' . esc_html($api_error);
				$appended = true;
				break;
			}
		}

		if (!$appended) {
			$notices['error'][] = array(
				'notice' => esc_html($api_error),
				'data'   => array(),
			);
		}

		WC()->session->__unset('yoga_yookassa_api_error');

		return $notices;
	}
}
add_filter('woocommerce_get_notices', 'yoga_yookassa_append_api_error_to_notices');

add_action('wp_enqueue_scripts', 'yoga_enqueue_yookassa_checkout_bridge', 25);
function yoga_enqueue_yookassa_checkout_bridge(): void {
	if (!function_exists('is_checkout') || !is_checkout() || (function_exists('is_order_received_page') && is_order_received_page())) {
		return;
	}

	$theme_dir   = get_template_directory();
	$script_path = $theme_dir . '/assets/js/checkout-yookassa.js';
	if (!is_readable($script_path)) {
		return;
	}

	wp_enqueue_script(
		'yoga-checkout-yookassa',
		get_template_directory_uri() . '/assets/js/checkout-yookassa.js',
		array('jquery', 'yoga-checkout-payment', 'wc-checkout'),
		(string) filemtime($script_path),
		true
	);

	wp_localize_script(
		'yoga-checkout-yookassa',
		'yogaYooKassa',
		array(
			'gatewayId'     => yoga_get_checkout_yookassa_gateway_id(),
			'typeMap'       => yoga_yookassa_payment_type_map(),
			'redirectTypes' => yoga_yookassa_redirect_confirmation_types(),
		)
	);
}
