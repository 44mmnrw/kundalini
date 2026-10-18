<?php
/** Offline tests against the installed SDK. No WordPress bootstrap or network calls. */
define('ABSPATH', __DIR__ . '/');
require dirname(__DIR__, 2) . '/yookassa/includes/lib/vendor/autoload.php';

$options = array('yookassa_enable_receipt' => true);
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL) !== false; }
class WC_Order {
	private $email;
	public function __construct($email) { $this->email = $email; }
	public function get_billing_email() { return $this->email; }
}
class YooKassaHandler {
	public static function isReceiptEnabled() { return get_option('yookassa_enable_receipt'); }
}
require dirname(__DIR__) . '/includes/class-ytr-yookassa.php';

function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
function request_with_receipt() {
	return \YooKassa\Request\Payments\CreatePaymentRequest::builder()
		->setAmount('100.00')
		->setCapture(true)
		->setPaymentMethodId('saved-method')
		->setReceipt(array(
			'customer' => array('email' => 'old@example.com'),
			'items' => array(array(
				'description' => 'Subscription',
				'quantity' => '3.00',
				'amount' => array('value' => '33.33', 'currency' => 'RUB'),
				'vat_code' => 1,
				'payment_mode' => 'full_payment',
				'payment_subject' => 'service',
			)),
		))->build();
}
$order = new WC_Order('buyer@example.com');
$request = request_with_receipt();
YTR_YooKassa::prepare_fiscal_receipt($request, $order);
$body = (new \YooKassa\Request\Payments\CreatePaymentRequestSerializer())->serialize($request);
check($body['receipt']['customer']['email'] === 'buyer@example.com', 'serialized receipt uses buyer email');
check($request->getReceipt()->getAmountValue() === 10000, 'receipt rounding matches payment exactly');
check($request->getAmount()->getIntegerValue() === 10000, 'payment amount unchanged');
check($body['payment_method_id'] === 'saved-method', 'saved payment method preserved');
foreach ($body['receipt']['items'] as $item) {
	check($item['vat_code'] === 1 && $item['payment_mode'] === 'full_payment' && $item['payment_subject'] === 'service', 'fiscal settings preserved');
}
$caught = false;
try { YTR_YooKassa::prepare_fiscal_receipt(request_with_receipt(), new WC_Order('invalid')); }
catch (InvalidArgumentException $e) { $caught = true; }
check($caught, 'invalid customer email stops payment preparation');
$without_receipt = \YooKassa\Request\Payments\CreatePaymentRequest::builder()->setAmount('100.00')->setCapture(true)->setPaymentMethodId('saved-method')->build();
$caught = false;
try { YTR_YooKassa::prepare_fiscal_receipt($without_receipt, $order); }
catch (InvalidArgumentException $e) { $caught = true; }
check($caught, 'enabled fiscalization requires receipt items');
$options['yookassa_enable_receipt'] = false;
YTR_YooKassa::prepare_fiscal_receipt($without_receipt, new WC_Order(''));
check($without_receipt->getReceipt() === null, 'disabled receipt setting preserved');

echo "PASS: fiscal receipt email, rounding, tax settings, validation and disabled mode.\n";
