<?php
/**
 * Standalone renderer smoke tests: php tests/smoke.php
 */

define('ABSPATH', __DIR__ . '/');
define('YOGA_MAIL_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

$GLOBALS['km_options'] = array();

class WP_Error {
	private $code;
	private $message;
	public function __construct($code, $message) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}

function is_wp_error($value) { return $value instanceof WP_Error; }
function content_url($path = '') { return 'https://example.com/wp-content' . $path; }
function home_url($path = '') { return 'https://example.com' . $path; }
function get_bloginfo($key = '') { return $key === 'charset' ? 'UTF-8' : 'Kundalini Class'; }
function get_option($key, $default = false) { return array_key_exists($key, $GLOBALS['km_options']) ? $GLOBALS['km_options'][$key] : $default; }
function update_option($key, $value, $autoload = null) { $GLOBALS['km_options'][$key] = $value; return true; }
function apply_filters($hook, $value) { return $value; }
function add_filter() { return true; }
function add_action() { return true; }
function do_action() { return null; }
function wp_generate_uuid4() { static $id = 0; $id++; return '00000000-0000-4000-8000-' . str_pad((string) $id, 12, '0', STR_PAD_LEFT); }
function wp_mail($to, $subject, $message, $headers = array(), $attachments = array(), $embeds = array()) { $GLOBALS['km_last_mail'] = compact('to', 'subject', 'message', 'headers', 'attachments', 'embeds'); return true; }
function wp_parse_args($args, $defaults = array()) { return array_merge($defaults, is_array($args) ? $args : array()); }
function sanitize_key($key) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($key)); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function wp_kses_post($value) { return $value; }
function wp_strip_all_tags($value) { return strip_tags($value); }
function wp_specialchars_decode($value, $flags = ENT_NOQUOTES) { return html_entity_decode($value, $flags, 'UTF-8'); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return filter_var($value, FILTER_SANITIZE_URL); }
function esc_url_raw($value) { return filter_var($value, FILTER_SANITIZE_URL); }
function wp_http_validate_url($value) { return filter_var($value, FILTER_VALIDATE_URL) ? $value : false; }
function wpautop($value) { return '<p>' . str_replace("\n\n", '</p><p>', $value) . '</p>'; }

require YOGA_MAIL_PATH . 'includes/class-yoga-mail-registry.php';
require YOGA_MAIL_PATH . 'includes/class-yoga-mail-renderer.php';
require YOGA_MAIL_PATH . 'includes/class-yoga-mail-mailer.php';

function km_assert($condition, $message) {
	if (!$condition) {
		fwrite(STDERR, "FAIL: {$message}\n");
		exit(1);
	}
}

$registry = new Yoga_Mail_Registry();
$renderer = new Yoga_Mail_Renderer($registry);
$GLOBALS['km_options'][Yoga_Mail_Registry::LEGACY_SETTINGS_OPTION] = array('footer_text' => 'Legacy footer');
$GLOBALS['km_options'][Yoga_Mail_Registry::LEGACY_TEMPLATES_OPTION] = array('generic' => array('subject' => 'Legacy subject'));
km_assert($registry->settings()['footer_text'] === 'Legacy footer', 'legacy settings remain readable after rename');
km_assert($registry->values('generic')['subject'] === 'Legacy subject', 'legacy templates remain readable after rename');
unset($GLOBALS['km_options'][Yoga_Mail_Registry::LEGACY_SETTINGS_OPTION], $GLOBALS['km_options'][Yoga_Mail_Registry::LEGACY_TEMPLATES_OPTION]);
km_assert($registry->save_values('generic', array('body' => '<p>{{user_name}}</p>', 'cta_url' => '{{action_url}}')) === true, 'typed merge tags save in allowed fields');
km_assert($registry->save_values('generic', array('body' => '<p>{{action_url}}</p>')) === false, 'URL tags are rejected in editable HTML');
km_assert($registry->save_values('generic', array('cta_url' => '{{user_name}}')) === false, 'text tags are rejected in CTA URL');
$GLOBALS['km_options'][Yoga_Mail_Registry::TEMPLATES_OPTION] = array(
	'generic' => array('body' => '<p>Привет, {{user_name}}.</p>{{content}}'),
);
$result = $renderer->render('generic', array(
	'subject' => 'Проверка',
	'content' => '<p>Содержимое письма.</p>',
	'user_name' => 'Анна',
), false);

km_assert(!is_wp_error($result), 'generic template renders');
km_assert(strpos($result['html'], 'width="600"') !== false, '600px layout exists');
km_assert(strpos($result['html'], '<!-- yoga-mail:generic -->') !== false, 'marker exists');
km_assert(strpos($result['html'], '<button') === false, 'button element is not used');
km_assert(strpos($result['html'], '<div') === false, 'layout does not use div elements');
km_assert(strpos($result['html'], '<style') === false, 'layout does not use style blocks');
km_assert(strpos($result['html'], 'fonts.googleapis.com') !== false, 'Mulish link exists');
km_assert(strpos($result['html'], 'alt="Kundalini Class"') !== false, 'logo alt exists');
km_assert(strpos($result['html'], 'background-color:#1f1f1f') !== false, 'dark td/footer styles exist');
km_assert(strpos($result['text'], 'Привет, Анна.') !== false, 'plain text is generated');

$reset = $renderer->render('wp-reset-password', array(
	'user_name' => 'Марина',
	'action_url' => 'https://example.com/wp-login.php?action=rp&key=test-key&login=marina',
), false);
km_assert(!is_wp_error($reset), 'reset-password template renders');
km_assert($reset['subject'] === 'Восстановление пароля', 'reset-password subject matches Figma');
km_assert(strpos($reset['html'], 'width="560"') !== false, 'reset-password inner card is 560px');
km_assert(strpos($reset['html'], 'Сат Нам, Марина!') !== false, 'reset-password greeting is personalized');
km_assert(strpos($reset['html'], 'Создать новый пароль') !== false, 'reset-password CTA label exists');
km_assert(
	strpos($reset['html'], 'https://example.com/wp-login.php?action=rp&amp;key=test-key&amp;login=marina') !== false
		|| strpos($reset['html'], 'https://example.com/wp-login.php?action=rp&key=test-key&login=marina') !== false,
	'reset-password CTA URL is present'
);
km_assert(strpos($reset['html'], 'Ссылка действует 60 мин.') !== false, 'reset-password expiration note exists');
km_assert(strpos($reset['html'], 'support@platform.kundalini-class.ru') !== false, 'shared footer support exists');
km_assert(strpos($reset['html'], 'Политика конфиденциальности') !== false, 'shared footer privacy link exists');
km_assert(strpos($reset['html'], 'ИНН 632200860531') !== false, 'shared footer legal details exist');
km_assert(strpos($reset['html'], '/plugins/yoga-mail/assets/images/email/youtube.svg') !== false, 'shared footer uses local Figma icons');
km_assert(!preg_match('/<img(?![^>]*\salt=)[^>]*>/i', $reset['html']), 'every reset-password image has alt text');
km_assert(strpos($reset['text'], 'Создать новый пароль: https://example.com/wp-login.php?action=rp&key=test-key&login=marina') !== false, 'plain reset-password CTA includes URL');
$layout_source = file_get_contents(YOGA_MAIL_PATH . 'templates/layout/html.php');
$woocommerce_footer_source = file_get_contents(YOGA_MAIL_PATH . 'templates/woocommerce/email-footer.php');
km_assert(strpos($layout_source, "templates/partials/footer.php") !== false, 'HTML layout reuses shared footer partial');
km_assert(strpos($woocommerce_footer_source, "templates/partials/footer.php") !== false, 'WooCommerce reuses shared footer partial');
$settings = $registry->settings();
$template_id = 'woocommerce-test';
$heading = 'Заказ принят';
$preheader = 'Информация о заказе';
$cta_label = '';
$cta_url = '';
$footer_note = '';
ob_start();
include YOGA_MAIL_PATH . 'templates/woocommerce/email-header.php';
echo '<p>Содержимое заказа</p>';
include YOGA_MAIL_PATH . 'templates/woocommerce/email-footer.php';
$woocommerce_html = (string) ob_get_clean();
km_assert(substr_count($woocommerce_html, '<table') === substr_count($woocommerce_html, '</table>'), 'WooCommerce layout tables are balanced');
km_assert(strpos($woocommerce_html, 'width="560"') !== false, 'WooCommerce uses the shared 560px card');
km_assert(strpos($woocommerce_html, 'support@platform.kundalini-class.ru') !== false, 'WooCommerce renders the shared footer');

$GLOBALS['km_options'][Yoga_Mail_Registry::TEMPLATES_OPTION] = array(
	'generic' => array('body' => '{{unknown_tag}}'),
);
$invalid = $renderer->render('generic', array('subject' => 'Проверка'), false);
km_assert(is_wp_error($invalid) && $invalid->get_error_code() === 'yoga_mail_unknown_merge_tag', 'unknown tags fail rendering');

$GLOBALS['km_options'][Yoga_Mail_Registry::TEMPLATES_OPTION] = array(
	'generic' => array('cta_label' => 'Открыть', 'cta_url' => 'javascript:alert(1)'),
);
$invalid_url = $renderer->render('generic', array('subject' => 'Проверка', 'content' => 'Текст'), false);
km_assert(is_wp_error($invalid_url) && $invalid_url->get_error_code() === 'yoga_mail_invalid_url', 'unsafe CTA URL fails rendering');

$GLOBALS['km_options'][Yoga_Mail_Registry::TEMPLATES_OPTION] = array();
$GLOBALS['km_options'][Yoga_Mail_Registry::SETTINGS_OPTION] = array('fallback_enabled' => true);
$mailer = new Yoga_Mail_Mailer($registry, $renderer);
$filtered = $mailer->filter_wp_mail(array(
	'to' => 'anna@example.com',
	'subject' => 'Fallback',
	'message' => "Первая строка\nВторая строка",
	'headers' => array('Reply-To: help@example.com'),
	'attachments' => array('/tmp/report.pdf'),
	'embeds' => array('/tmp/logo.png'),
));
km_assert(strpos($filtered['message'], '<!-- yoga-mail:generic -->') !== false, 'fallback brands plain email');
km_assert($filtered['attachments'] === array('/tmp/report.pdf'), 'fallback preserves attachments');
km_assert($filtered['embeds'] === array('/tmp/logo.png'), 'fallback preserves embeds');
km_assert(in_array('Reply-To: help@example.com', $filtered['headers'], true), 'fallback preserves headers');

$mail_id = '';
foreach ($filtered['headers'] as $header) {
	if (strpos($header, 'X-Yoga-Mail-ID:') === 0) {
		$mail_id = trim(substr($header, strlen('X-Yoga-Mail-ID:')));
	}
}
class KM_Fake_Mailer {
	public $AltBody = '';
	private $id;
	public function __construct($id) { $this->id = $id; }
	public function getCustomHeaders() { return array(array('X-Yoga-Mail-ID', $this->id)); }
	public function isHTML($value) {}
}
$fake_mailer = new KM_Fake_Mailer($mail_id);
$mailer->set_alt_body($fake_mailer);
km_assert(strpos($fake_mailer->AltBody, 'Первая строка') !== false, 'fallback sets matching AltBody');

$GLOBALS['km_options'][Yoga_Mail_Registry::SETTINGS_OPTION] = array('custom_enabled' => false, 'fallback_enabled' => false);
$mailer->send('generic', array(
	'to' => 'anna@example.com',
	'subject' => 'Plain rollout',
	'content' => '<p>Текст</p>',
	'headers' => array('Content-Type: text/html; charset=UTF-8', 'Reply-To: help@example.com'),
));
km_assert(in_array('Content-Type: text/plain; charset=UTF-8', $GLOBALS['km_last_mail']['headers'], true), 'disabled custom layer sends a real plain-text message');
km_assert(in_array('Reply-To: help@example.com', $GLOBALS['km_last_mail']['headers'], true), 'plain rollout preserves non-content headers');

echo "Yoga Mail smoke tests passed.\n";
