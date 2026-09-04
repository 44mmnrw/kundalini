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
function wp_lostpassword_url() { return 'https://example.com/wp-login.php?action=lostpassword'; }
function wp_date($format) { return $format === 'Y' ? '2026' : '14 июля 2026, 21:30'; }
function wp_mail($to, $subject, $message, $headers = array(), $attachments = array(), $embeds = array()) { $GLOBALS['km_last_mail'] = compact('to', 'subject', 'message', 'headers', 'attachments', 'embeds'); return true; }
function wp_parse_args($args, $defaults = array()) { return array_merge($defaults, is_array($args) ? $args : array()); }
function sanitize_key($key) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($key)); }
function absint($value) { return abs((int) $value); }
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
require YOGA_MAIL_PATH . 'includes/class-yoga-mail-wordpress.php';
require YOGA_MAIL_PATH . 'includes/class-yoga-mail-woocommerce.php';

function km_assert($condition, $message) {
	if (!$condition) {
		fwrite(STDERR, "FAIL: {$message}\n");
		exit(1);
	}
}

$registry = new Yoga_Mail_Registry();
$renderer = new Yoga_Mail_Renderer($registry);
km_assert($registry->get('generic')['designed'] === false, 'generic template is marked as basic');
km_assert($registry->get('sadhana-completed')['designed'] === true, 'dedicated Figma template is marked as ready');
km_assert($registry->get('wp-comment-notification')['designed'] === false, 'unimplemented WordPress template is marked as basic');
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
km_assert(strpos($result['html'], 'font-family:Helvetica,sans-serif') !== false, 'Helvetica font exists');
km_assert(stripos($result['html'], 'Arial') === false && stripos($result['html'], 'Mulish') === false, 'legacy email fonts are absent');
foreach ($registry->all() as $template_id => $definition) {
	if (empty($definition['designed'])) {
		continue;
	}
	$font_check = $renderer->render($template_id, array(), true);
	km_assert(!is_wp_error($font_check), $template_id . ' renders for font verification');
	km_assert(strpos($font_check['html'], 'font-family:Helvetica,sans-serif') !== false, $template_id . ' uses Helvetica');
	km_assert(stripos($font_check['html'], 'Arial') === false && stripos($font_check['html'], 'Mulish') === false, $template_id . ' has no legacy fonts');
}
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

$password_changed = $renderer->render('wp-password-changed', array(
	'action_url' => 'https://example.com/wp-login.php?action=lostpassword',
	'event_datetime' => '14 июля 2026, 21:30',
), false);
km_assert(!is_wp_error($password_changed), 'password-changed template renders');
km_assert($password_changed['subject'] === 'Пароль изменен', 'password-changed subject matches Figma');
km_assert(strpos($password_changed['html'], 'Вы успешно сменили пароль от аккаунта.') !== false, 'password-changed confirmation exists');
km_assert(strpos($password_changed['html'], '14 июля 2026, 21:30') !== false, 'password-changed event datetime exists');
km_assert(strpos($password_changed['html'], 'background-color:#f6f6f9') !== false, 'password-changed datetime panel exists');
km_assert(strpos($password_changed['html'], 'border:1px solid #e15355') !== false, 'password-changed security warning exists');
km_assert(strpos($password_changed['html'], 'Это были не вы?') !== false, 'password-changed recovery CTA exists');
km_assert(strpos($password_changed['html'], 'href="https://example.com/wp-login.php?action=lostpassword"') !== false, 'password-changed CTA uses recovery URL');
km_assert(strpos($password_changed['html'], 'support@platform.kundalini-class.ru') !== false, 'password-changed reuses shared footer');
km_assert(strpos($password_changed['text'], 'Это были не вы?: https://example.com/wp-login.php?action=lostpassword') !== false, 'plain password-changed CTA includes recovery URL');

$email_changed = $renderer->render('wp-email-changed', array(
	'old_email' => 'marina@example.com',
	'new_email' => 'marina.k@example.com',
	'action_url' => 'mailto:support@platform.kundalini-class.ru',
	'event_datetime' => '14 июля 2026, 21:30',
), false);
km_assert(!is_wp_error($email_changed), 'email-changed template renders');
km_assert($email_changed['subject'] === 'Адрес эл. почты изменен', 'email-changed subject matches Figma');
km_assert(strpos($email_changed['html'], 'Вы успешно сменили адрес эл. почты для входа в аккаунт.') !== false, 'email-changed confirmation exists');
km_assert(strpos($email_changed['html'], 'marina@example.com') !== false, 'email-changed old address exists');
km_assert(strpos($email_changed['html'], 'marina.k@example.com') !== false, 'email-changed new address exists');
km_assert(strpos($email_changed['html'], 'color:#9153e1') !== false, 'email-changed new address uses violet accent');
km_assert(substr_count($email_changed['html'], 'height:1px;font-size:1px;line-height:1px;background-color:#ffffff') === 2, 'email-changed details contain two Figma-white separators');
km_assert(strpos($email_changed['html'], '14 июля 2026, 21:30') !== false, 'email-changed event datetime exists');
km_assert(strpos($email_changed['html'], 'border:1px solid #e15355') !== false, 'email-changed security warning exists');
km_assert(strpos($email_changed['html'], 'href="mailto:support@platform.kundalini-class.ru"') !== false, 'email-changed CTA opens support email');
km_assert(strpos($email_changed['html'], 'support@platform.kundalini-class.ru') !== false, 'email-changed reuses shared footer');
km_assert(strpos($email_changed['text'], 'Связаться с поддержкой: mailto:support@platform.kundalini-class.ru') !== false, 'plain email-changed CTA includes support address');

$verification_code = $renderer->render('email-verification', array(
	'code_digit_1' => '4',
	'code_digit_2' => '8',
	'code_digit_3' => '2',
	'code_digit_4' => '9',
	'ttl_minutes' => '60',
), false);
km_assert(!is_wp_error($verification_code), 'email-verification code template renders');
km_assert($verification_code['subject'] === 'Ваш код подтверждения', 'email-verification code subject matches Figma');
km_assert(strpos($verification_code['html'], 'Введите этот код на сайте, чтобы подтвердить свою эл. почту.') !== false, 'email-verification code instruction matches Figma');
km_assert(substr_count($verification_code['html'], 'width="44" height="54"') === 4, 'email-verification renders four code cells');
km_assert(substr_count($verification_code['html'], 'border:1px solid #dedee1;border-radius:12px') === 4, 'email-verification code cells match Figma styling');
km_assert(strpos($verification_code['html'], '>4</td>') !== false && strpos($verification_code['html'], '>9</td>') !== false, 'email-verification renders dynamic code digits');
km_assert(strpos($verification_code['html'], 'Код действует 60 мин.') !== false, 'email-verification renders dynamic expiration note');
km_assert(strpos($verification_code['html'], 'support@platform.kundalini-class.ru') !== false, 'email-verification reuses shared footer');
km_assert((bool) preg_match('/4\D*8\D*2\D*9/u', $verification_code['text']), 'plain email-verification contains the code');

$registration_verification = $renderer->render('email-verification-registration', array(
	'user_name' => 'Марина',
	'action_url' => 'https://example.com/?yoga_verify_email=1&uid=42&token=test-token',
), false);
km_assert(!is_wp_error($registration_verification), 'registration email-verification template renders');
km_assert($registration_verification['subject'] === 'Подтвердите эл. почту', 'registration email-verification subject exists');
km_assert(strpos($registration_verification['html'], 'Осталось чуть-чуть..') !== false, 'registration email-verification heading matches Figma');
km_assert(strpos($registration_verification['html'], 'Сат Нам, Марина!') !== false, 'registration email-verification greeting is personalized');
km_assert(strpos($registration_verification['html'], 'Спасибо за регистрацию. Остался один шаг — подтвердите вашу эл. почту, чтобы активировать аккаунт.') !== false, 'registration email-verification body matches Figma');
km_assert(strpos($registration_verification['html'], 'Подтвердить эл. почту') !== false, 'registration email-verification CTA label exists');
km_assert(
	strpos($registration_verification['html'], 'https://example.com/?yoga_verify_email=1&amp;uid=42&amp;token=test-token') !== false
		|| strpos($registration_verification['html'], 'https://example.com/?yoga_verify_email=1&uid=42&token=test-token') !== false,
	'registration email-verification CTA URL exists'
);
km_assert(strpos($registration_verification['html'], 'Ссылка активна 24 ч.') !== false, 'registration email-verification expiration note matches Figma');
km_assert(strpos($registration_verification['html'], 'support@platform.kundalini-class.ru') !== false, 'registration email-verification reuses shared footer');
km_assert(strpos($registration_verification['text'], 'Подтвердить эл. почту: https://example.com/?yoga_verify_email=1&uid=42&token=test-token') !== false, 'plain registration email-verification includes confirmation URL');

$verification_success = $renderer->render('email-verification-success', array(
	'user_name' => 'Марина',
	'action_url' => 'https://example.com/lk/',
), false);
km_assert(!is_wp_error($verification_success), 'email-verification success template renders');
km_assert($verification_success['subject'] === 'Добро пожаловать в Кундалини Класс', 'email-verification success subject exists');
km_assert(strpos($verification_success['html'], 'Сат Нам, Марина!') !== false, 'email-verification success greeting is personalized');
km_assert(strpos($verification_success['html'], 'Всё готово к практике — вот с чего удобнее начать.') !== false, 'email-verification success introduction matches Figma');
km_assert(substr_count($verification_success['html'], '<td width="30" height="30"') === 4, 'email-verification success renders four numbered steps');
km_assert(substr_count($verification_success['html'], '<table role="presentation" width="30" height="30"') === 4, 'email-verification success keeps numbered circles fixed at 30 by 30 pixels');
km_assert(substr_count($verification_success['html'], 'height:1px;font-size:1px;line-height:1px;background-color:#ffffff') >= 3, 'email-verification success renders table dividers');
km_assert(strpos($verification_success['html'], 'background-color:#f8f3fd') !== false, 'email-verification success renders the sadhana panel');
km_assert(strpos($verification_success['html'], 'А главное — выберите садхану') !== false, 'email-verification success sadhana copy matches Figma');
km_assert(strpos($verification_success['html'], 'href="https://example.com/lk/"') !== false, 'email-verification success CTA opens personal account');
km_assert(strpos($verification_success['html'], 'support@platform.kundalini-class.ru') !== false, 'email-verification success reuses shared footer');
km_assert(strpos($verification_success['text'], 'Перейти в ЛК: https://example.com/lk/') !== false, 'plain email-verification success includes personal-account URL');

$subscription_expiring = $renderer->render('subscription-expiring', array(
	'user_name' => 'Марина',
	'subscription_name' => 'Аришечный Pro Max, 1 месяц',
	'expiration_date' => '17 июля 2026',
	'action_url' => 'https://example.com/lk/?section=subscription',
), false);
km_assert(!is_wp_error($subscription_expiring), 'subscription-expiring template renders');
km_assert($subscription_expiring['subject'] === 'Подписка скоро закончится', 'subscription-expiring subject matches Figma');
km_assert(strpos($subscription_expiring['html'], 'Осталось 3 дня') !== false, 'subscription-expiring badge matches Figma');
km_assert(strpos($subscription_expiring['html'], 'background-color:#e8ff57') !== false, 'subscription-expiring badge uses lime background');
km_assert(strpos($subscription_expiring['html'], 'Сат Нам, Марина!') !== false, 'subscription-expiring greeting is personalized');
km_assert(strpos($subscription_expiring['html'], '«Аришечный Pro Max, 1 месяц»') !== false, 'subscription-expiring tariff is dynamic');
km_assert(strpos($subscription_expiring['html'], '17 июля 2026.') !== false, 'subscription-expiring date is dynamic');
km_assert(strpos($subscription_expiring['html'], 'Автопродление отключено') !== false, 'subscription-expiring explains disabled auto-renew');
km_assert(strpos($subscription_expiring['html'], 'href="https://example.com/lk/?section=subscription"') !== false, 'subscription-expiring CTA opens subscription settings');
km_assert(strpos($subscription_expiring['html'], 'support@platform.kundalini-class.ru') !== false, 'subscription-expiring reuses shared footer');
km_assert(substr_count($subscription_expiring['html'], '<table') === substr_count($subscription_expiring['html'], '</table>'), 'subscription-expiring tables are balanced');
km_assert(strpos($subscription_expiring['text'], 'Продлить подписку: https://example.com/lk/?section=subscription') !== false, 'plain subscription-expiring email includes renewal URL');

$subscription_ended = $renderer->render('subscription-ended', array(
	'user_name' => 'Марина',
	'subscription_name' => 'Аришечный Pro Max, 1 месяц',
	'expiration_date' => '14 июля 2026',
	'action_url' => 'https://example.com/lk/?section=subscription',
), false);
km_assert(!is_wp_error($subscription_ended), 'subscription-ended template renders');
km_assert($subscription_ended['subject'] === 'Подписка завершилась', 'subscription-ended subject matches Figma');
km_assert(strpos($subscription_ended['html'], 'Сат Нам, Марина!') !== false, 'subscription-ended greeting is personalized');
km_assert(strpos($subscription_ended['html'], '«Аришечный Pro Max, 1 месяц»') !== false, 'subscription-ended tariff is dynamic');
km_assert(strpos($subscription_ended['html'], '14 июля 2026.') !== false, 'subscription-ended date is dynamic');
km_assert(strpos($subscription_ended['html'], 'Ваши садханы, избранное и история сохранены') !== false, 'subscription-ended preservation notice matches Figma');
km_assert(strpos($subscription_ended['html'], 'background-color:#f8f3fd') !== false, 'subscription-ended preservation notice uses violet panel');
km_assert(strpos($subscription_ended['html'], 'href="https://example.com/lk/?section=subscription"') !== false, 'subscription-ended CTA opens subscription settings');
km_assert(strpos($subscription_ended['html'], 'support@platform.kundalini-class.ru') !== false, 'subscription-ended reuses shared footer');
km_assert(substr_count($subscription_ended['html'], '<table') === substr_count($subscription_ended['html'], '</table>'), 'subscription-ended tables are balanced');
km_assert(strpos($subscription_ended['text'], 'Возобновить подписку: https://example.com/lk/?section=subscription') !== false, 'plain subscription-ended email includes renewal URL');

$card_expiring = $renderer->render('payment-card-expiring', array(
	'user_name' => 'Марина',
	'subscription_name' => 'Аришечный Pro Max, 1 месяц',
	'payment_card' => '•• 4242',
	'card_expiry' => '08/26',
	'action_url' => 'https://example.com/lk/?section=subscription',
), false);
km_assert(!is_wp_error($card_expiring), 'payment-card-expiring template renders');
km_assert($card_expiring['subject'] === 'Скоро истечет срок карты', 'payment-card-expiring subject matches Figma');
km_assert(strpos($card_expiring['html'], 'Сат Нам, Марина!') !== false, 'payment-card-expiring greeting is personalized');
km_assert(strpos($card_expiring['html'], '«Аришечный Pro Max, 1 месяц»,') !== false, 'payment-card-expiring tariff is dynamic');
km_assert(strpos($card_expiring['html'], '>Карта</td>') !== false, 'payment-card-expiring card badge exists');
km_assert(strpos($card_expiring['html'], '•• 4242') !== false, 'payment-card-expiring card mask is dynamic');
km_assert(strpos($card_expiring['html'], 'Действует до 08/26') !== false, 'payment-card-expiring expiration date is dynamic');
km_assert(strpos($card_expiring['html'], 'background-color:#1f1f1f') !== false && strpos($card_expiring['html'], 'color:#e8ff57') !== false, 'payment-card-expiring badge matches Figma colors');
km_assert(strpos($card_expiring['html'], 'href="https://example.com/lk/?section=subscription"') !== false, 'payment-card-expiring CTA opens card settings');
km_assert(strpos($card_expiring['html'], 'support@platform.kundalini-class.ru') !== false, 'payment-card-expiring reuses shared footer');
km_assert(substr_count($card_expiring['html'], '<table') === substr_count($card_expiring['html'], '</table>'), 'payment-card-expiring tables are balanced');
km_assert(strpos($card_expiring['text'], 'Обновить карту: https://example.com/lk/?section=subscription') !== false, 'plain payment-card-expiring email includes settings URL');

$sadhana_started = $renderer->render('sadhana-started', array(
	'user_name' => 'Марина',
	'library_url' => 'https://example.com/practice-type/kriyi/',
), false);
km_assert(!is_wp_error($sadhana_started), 'sadhana-started template renders');
km_assert($sadhana_started['subject'] === 'Что такое садхана?', 'sadhana-started subject matches Figma');
km_assert(strpos($sadhana_started['html'], 'Сат Нам, Марина!') !== false, 'sadhana-started greeting is personalized');
km_assert(strpos($sadhana_started['html'], 'Садхана — это личная практика') !== false, 'sadhana-started explanation matches Figma');
km_assert(strpos($sadhana_started['html'], '40 дней') !== false && strpos($sadhana_started['html'], '90 дней') !== false && strpos($sadhana_started['html'], '120 дней') !== false, 'sadhana-started contains all practice milestones');
km_assert(substr_count($sadhana_started['html'], '<td align="left" width="19%"') === 3, 'sadhana-started milestone days are explicitly left aligned');
km_assert(strpos($sadhana_started['html'], 'padding:15px 10px 15px 0;font-size:14px;line-height:1;font-weight:700;color:#9153e1;text-align:left;white-space:nowrap;">90 дней</td>') !== false, 'sadhana-started milestone days share the same left edge');
km_assert(substr_count($sadhana_started['html'], 'height:1px;font-size:1px;line-height:1px;background-color:#ffffff') >= 2, 'sadhana-started milestone panel contains white separators');
km_assert(strpos($sadhana_started['html'], 'Регулярность важнее длительности.') !== false, 'sadhana-started contains the seven-day recommendation');
km_assert(strpos($sadhana_started['html'], 'href="https://example.com/practice-type/kriyi/"') !== false, 'sadhana-started CTA opens the practice library');
km_assert(strpos($sadhana_started['html'], 'support@platform.kundalini-class.ru') !== false, 'sadhana-started reuses shared footer');
km_assert(substr_count($sadhana_started['html'], '<table') === substr_count($sadhana_started['html'], '</table>'), 'sadhana-started tables are balanced');
km_assert(strpos($sadhana_started['text'], 'В библиотеку практик: https://example.com/practice-type/kriyi/') !== false, 'plain sadhana-started email includes the library URL');

$sadhana_progress_component = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:15px 0 0;border-collapse:collapse;"><tr><td align="left" style="padding:0;color:#606060;">День 40</td><td align="right" style="padding:0;color:#606060;">Цель — 90 дней</td></tr><tr><td colspan="2" bgcolor="#f8bdf6" style="background-color:#f8bdf6;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td width="44%" bgcolor="#9153e1" style="width:44%;background-color:#9153e1;">&nbsp;</td><td width="56%">&nbsp;</td></tr></table></td></tr></table>';
$sadhana_progress = $renderer->render('sadhana-progress', array(
	'user_name' => 'Марина',
	'milestone' => '40',
	'milestone_day_label' => 'дней',
	'practice_title' => 'Кундалини-крийя для рассвета',
	'target_days' => '90',
	'progress_component' => $sadhana_progress_component,
	'action_url' => 'https://example.com/practice/kundalini-kriya/',
), false);
km_assert(!is_wp_error($sadhana_progress), 'sadhana-progress template renders');
km_assert($sadhana_progress['subject'] === 'Садхана: 40 дней', 'sadhana-progress subject contains the milestone');
km_assert(strpos($sadhana_progress['html'], 'width:110px;height:110px') !== false, 'sadhana-progress milestone circle matches Figma dimensions');
km_assert(strpos($sadhana_progress['html'], '>40</p>') !== false && strpos($sadhana_progress['html'], '>дней</p>') !== false, 'sadhana-progress circle has the milestone and correct day label');
km_assert(strpos($sadhana_progress['html'], 'Вы прошли рубеж по садхане!') !== false, 'sadhana-progress heading matches Figma');
km_assert(strpos($sadhana_progress['html'], 'Сат Нам, Марина!') !== false, 'sadhana-progress greeting is personalized');
km_assert(strpos($sadhana_progress['html'], '«Кундалини-крийя для рассвета»') !== false, 'sadhana-progress practice title is dynamic');
km_assert(strpos($sadhana_progress['html'], 'День 40') !== false && strpos($sadhana_progress['html'], 'Цель — 90 дней') !== false, 'sadhana-progress bar labels are dynamic');
km_assert(strpos($sadhana_progress['html'], 'background-color:#f8bdf6') !== false && strpos($sadhana_progress['html'], 'width:44%;background-color:#9153e1') !== false, 'sadhana-progress bar matches Figma colors and percentage');
km_assert(strpos($sadhana_progress['html'], 'href="https://example.com/practice/kundalini-kriya/"') !== false, 'sadhana-progress CTA opens the practice');
km_assert(strpos($sadhana_progress['html'], 'support@platform.kundalini-class.ru') !== false, 'sadhana-progress reuses shared footer');
km_assert(substr_count($sadhana_progress['html'], '<table') === substr_count($sadhana_progress['html'], '</table>'), 'sadhana-progress tables are balanced');
km_assert(strpos($sadhana_progress['text'], 'Продолжить практику: https://example.com/practice/kundalini-kriya/') !== false, 'plain sadhana-progress email includes the practice URL');

$sadhana_interrupted = $renderer->render('sadhana-interrupted', array(
	'user_name' => 'Марина',
	'practice_title' => 'Кундалини-крийя для рассвета',
	'milestone' => '40',
	'milestone_day_label' => 'дней',
	'action_url' => 'https://example.com/practice/kundalini-kriya/',
), false);
km_assert(!is_wp_error($sadhana_interrupted), 'sadhana-interrupted template renders');
km_assert($sadhana_interrupted['subject'] === 'Серия садханы прервалась...', 'sadhana-interrupted subject matches Figma');
km_assert(strpos($sadhana_interrupted['html'], 'Серия садханы прервалась...') !== false, 'sadhana-interrupted heading matches Figma');
km_assert(strpos($sadhana_interrupted['html'], 'Сат Нам, Марина!') !== false, 'sadhana-interrupted greeting is personalized');
km_assert(strpos($sadhana_interrupted['html'], '«Кундалини-крийя для рассвета»') !== false, 'sadhana-interrupted practice title is dynamic');
km_assert(strpos($sadhana_interrupted['html'], '>40-м</strong> дне') !== false, 'sadhana-interrupted keeps the day reached before reset');
km_assert(strpos($sadhana_interrupted['html'], 'Прогресс не потерян: ваши 40 дней практики остаются с вами.') !== false, 'sadhana-interrupted reassurance matches Figma');
km_assert(strpos($sadhana_interrupted['html'], 'background-color:#f7f3fd') !== false && strpos($sadhana_interrupted['html'], 'color:#9153e1') !== false, 'sadhana-interrupted notice matches Figma colors');
km_assert(strpos($sadhana_interrupted['html'], 'href="https://example.com/practice/kundalini-kriya/"') !== false, 'sadhana-interrupted CTA opens the practice');
km_assert(strpos($sadhana_interrupted['html'], 'support@platform.kundalini-class.ru') !== false, 'sadhana-interrupted reuses shared footer');
km_assert(substr_count($sadhana_interrupted['html'], '<table') === substr_count($sadhana_interrupted['html'], '</table>'), 'sadhana-interrupted tables are balanced');
km_assert(strpos($sadhana_interrupted['text'], 'Возобновить садхану: https://example.com/practice/kundalini-kriya/') !== false, 'plain sadhana-interrupted email includes the practice URL');

$sadhana_completed = $renderer->render('sadhana-completed', array(
	'user_name' => 'Марина',
	'practice_title' => 'Кундалини-крийя для рассвета',
	'target_days' => '90',
	'target_day_label' => 'дней',
	'started_date' => '4 июня 2026',
	'library_url' => 'https://example.com/practice-type/kriyi/',
), false);
km_assert(!is_wp_error($sadhana_completed), 'sadhana-completed template renders');
km_assert($sadhana_completed['subject'] === 'Садхана пройдена', 'sadhana-completed subject matches Figma');
km_assert(strpos($sadhana_completed['html'], '>Садхана пройдена</td>') !== false, 'sadhana-completed status badge matches Figma');
km_assert(strpos($sadhana_completed['html'], 'Сат Нам, Марина! Вы прошли всю Садхану') !== false, 'sadhana-completed heading is personalized');
km_assert(strpos($sadhana_completed['html'], '«Кундалини-крийя для рассвета»') !== false, 'sadhana-completed practice title is dynamic');
km_assert(strpos($sadhana_completed['html'], '4 июня 2026 и дошли до конца') !== false, 'sadhana-completed start date is dynamic');
km_assert(strpos($sadhana_completed['html'], '>90</p>') !== false && strpos($sadhana_completed['html'], '>100%</p>') !== false, 'sadhana-completed result cards contain the duration and percentage');
km_assert(substr_count($sadhana_completed['html'], 'background-color:#f6f6f9;border-radius:15px') >= 2, 'sadhana-completed result cards match Figma styling');
km_assert(strpos($sadhana_completed['html'], 'href="https://example.com/practice-type/kriyi/"') !== false, 'sadhana-completed CTA opens the practice library');
km_assert(strpos($sadhana_completed['html'], 'support@platform.kundalini-class.ru') !== false, 'sadhana-completed reuses shared footer');
km_assert(substr_count($sadhana_completed['html'], '<table') === substr_count($sadhana_completed['html'], '</table>'), 'sadhana-completed tables are balanced');
km_assert(strpos($sadhana_completed['text'], 'Начать новую садхану: https://example.com/practice-type/kriyi/') !== false, 'plain sadhana-completed email includes the library URL');

$sadhana_core_source = file_get_contents(dirname(YOGA_MAIL_PATH) . '/kundalini-sadhanas/includes/core.php');
$sadhana_settings_source = file_get_contents(dirname(YOGA_MAIL_PATH) . '/kundalini-sadhanas/includes/settings.php');
$lk_source = file_get_contents(dirname(YOGA_MAIL_PATH, 2) . '/themes/yoga/templates-page/lk.php');
km_assert(strpos($sadhana_settings_source, "'started' => array(") !== false, 'sadhana module registers the started event');
km_assert(strpos($sadhana_settings_source, "'email_enabled' => true") !== false, 'sadhana started email is enabled by default');
km_assert(strpos($sadhana_core_source, "yoga_sadhana_notify(\$result, 'started');") !== false, 'new sadhana cycles trigger the started email');
km_assert(strpos($sadhana_core_source, "empty(\$result['already_active'])") !== false, 'already active sadhanas do not trigger duplicate started emails');
km_assert(strpos($sadhana_core_source, "'library_url' => yoga_sadhana_library_url()") !== false, 'sadhana adapter supplies the practice library URL');
km_assert(strpos($sadhana_core_source, 'function yoga_sadhana_day_label') !== false, 'sadhana adapter inflects the milestone day label');
km_assert(strpos($sadhana_core_source, "kundalini_sadhanas_progress_milestones()") !== false, 'sadhana progress email uses configured milestones');
km_assert(strpos($sadhana_core_source, "yoga_sadhana_notify(\$result, 'interrupted', \$interrupted_at);") !== false, 'interrupted email receives the day count captured before reset');
km_assert(strpos($sadhana_core_source, "'started_date' => yoga_sadhana_email_date") !== false, 'completed email receives a localized start date');
km_assert(strpos($sadhana_core_source, "'target_day_label' => yoga_sadhana_day_label") !== false, 'completed email receives the correctly inflected duration');
km_assert(strpos($sadhana_settings_source, "'_subject'") === false && strpos($sadhana_settings_source, "'_body'") === false, 'sadhana plugin no longer stores email templates');
km_assert(strpos($sadhana_settings_source, "'progress_milestones' => array(7, 21, 40, 90, 120)") !== false, 'sadhana milestone defaults are stored centrally');
km_assert(strpos($sadhana_settings_source, 'function kundalini_sadhanas_progress_milestones') !== false, 'sadhana module exposes sanitized progress milestones');
km_assert(strpos($sadhana_settings_source, "sort(\$result['progress_milestones'], SORT_NUMERIC)") !== false, 'sadhana milestone settings are sorted');
km_assert(strpos($sadhana_core_source, 'kundalini_sadhanas_render_email') === false, 'sadhana adapter delegates rendering to Yoga Mail');
km_assert(strpos($sadhana_core_source, "'subject' => \$email['subject']") === false, 'sadhana adapter does not override Yoga Mail subjects');
$sadhana_admin_source = file_get_contents(dirname(YOGA_MAIL_PATH) . '/kundalini-sadhanas/includes/admin.php');
km_assert(strpos($sadhana_admin_source, '_subject]') === false && strpos($sadhana_admin_source, '_body]') === false, 'sadhana admin no longer edits email templates');
km_assert(strpos($lk_source, "'sadhana_started_email'") !== false, 'users can control the sadhana-started email preference');
km_assert(strpos($lk_source, 'kundalini_sadhanas_progress_milestones()') !== false, 'notification preferences display configured sadhana milestones');
$yoga_mail_admin_source = file_get_contents(YOGA_MAIL_PATH . 'includes/class-yoga-mail-admin.php');
km_assert(strpos($yoga_mail_admin_source, "\$grouped_templates[\$item['group']]") !== false, 'admin dropdown groups each template category once');
km_assert(strpos($yoga_mail_admin_source, "\$item['designed'] ? '✓ ' : '○ '") !== false, 'admin dropdown displays template coverage markers');
km_assert(strpos($yoga_mail_admin_source, 'wp-list-table widefat fixed striped') === false, 'admin does not duplicate the dropdown with a separate template table');
km_assert(strpos($yoga_mail_admin_source, 'class="yoga-mail-fields"') !== false, 'admin editor uses a compact field grid');
km_assert(strpos($yoga_mail_admin_source, '<details class="yoga-mail-summary">') !== false, 'global mail settings are grouped in a collapsible section');
km_assert(strpos($yoga_mail_admin_source, 'class="yoga-mail-actions__test"') !== false, 'test sending is grouped with template actions');

$support_autoreply = $renderer->render('support-autoreply', array(
	'request_number' => '4821',
	'received_datetime' => '14 июля 2026, 21:40',
), false);
km_assert(!is_wp_error($support_autoreply), 'support-autoreply template renders');
km_assert($support_autoreply['subject'] === 'Мы получили ваше письмо', 'support-autoreply subject matches Figma');
km_assert(strpos($support_autoreply['html'], 'Ваше обращение зарегистрировано') !== false, 'support-autoreply introduction matches Figma');
km_assert(strpos($support_autoreply['html'], '№ 4821') !== false, 'support-autoreply contains the request number');
km_assert(strpos($support_autoreply['html'], '14 июля 2026, 21:40 по МСК') !== false, 'support-autoreply contains the received datetime');
km_assert(strpos($support_autoreply['html'], 'с 11:00 до 22:00 по МСК') !== false, 'support-autoreply contains support hours');
km_assert(substr_count($support_autoreply['html'], 'height:1px;font-size:1px;line-height:1px;background-color:#ffffff') >= 2, 'support-autoreply details contain white separators');
km_assert(strpos($support_autoreply['html'], 'background-color:#f7f3fd') !== false, 'support-autoreply warning uses the pale violet background');
km_assert(strpos($support_autoreply['html'], 'mailto:support@platform.kundalini-class.ru') !== false, 'support-autoreply contains the support email link');
km_assert(strpos($support_autoreply['html'], 'support@platform.kundalini-class.ru') !== false, 'support-autoreply reuses shared footer');
km_assert(substr_count($support_autoreply['html'], '<table') === substr_count($support_autoreply['html'], '</table>'), 'support-autoreply tables are balanced');
km_assert(strpos($support_autoreply['text'], '№ 4821') !== false && strpos($support_autoreply['text'], '14 июля 2026, 21:40 по МСК') !== false, 'plain support-autoreply contains request details');

$contact_form_source = file_get_contents(dirname(YOGA_MAIL_PATH, 2) . '/themes/yoga/functions.php');
km_assert(strpos($contact_form_source, 'function yoga_send_support_autoreply(string $recipient_email, int $request_id): bool') !== false, 'support-autoreply uses a shared sender');
km_assert(strpos($contact_form_source, '$request_id = save_contact_message(') !== false, 'contact form keeps the saved request ID');
km_assert(strpos($contact_form_source, 'yoga_send_support_autoreply($email, $request_id);') !== false, 'contact and practice forms send the support autoreply');
$faq_form_source = file_get_contents(dirname(YOGA_MAIL_PATH, 2) . '/themes/yoga/inc/ajax/questions.php');
km_assert(strpos($faq_form_source, 'yoga_send_support_autoreply($email, (int) $post_id);') !== false, 'FAQ form sends the support autoreply');

$question_answer = $renderer->render('question-answer', array(
	'answer_datetime' => '14 июля 2026 в 21:32',
	'admin_answer' => 'Здравствуйте! Продлить подписку можно в разделе «Тарифы» личного кабинета.',
	'action_url' => 'https://example.com/lk/?section=questions',
), false);
km_assert(!is_wp_error($question_answer), 'question-answer template renders');
km_assert($question_answer['subject'] === 'Ответ от администратора', 'question-answer subject matches Figma');
km_assert(strpos($question_answer['html'], 'Администратор</strong> ответил на ваш вопрос в личных сообщениях') !== false, 'question-answer summary matches Figma');
km_assert(strpos($question_answer['html'], '14 июля 2026 в 21:32 по МСК.') !== false, 'question-answer contains localized datetime');
km_assert(strpos($question_answer['html'], 'background-color:#9153e1') !== false, 'question-answer renders the violet administrator badge');
km_assert(strpos($question_answer['html'], 'background-color:#f7f3fd') !== false, 'question-answer renders the pale violet reply card');
km_assert(strpos($question_answer['html'], 'border-radius:15px 15px 15px 0') !== false, 'question-answer card matches the speech-bubble shape');
km_assert(strpos($question_answer['html'], 'href="https://example.com/lk/?section=questions"') !== false, 'question-answer CTA opens the conversation');
km_assert(strpos($question_answer['html'], 'support@platform.kundalini-class.ru') !== false, 'question-answer reuses shared footer');
km_assert(substr_count($question_answer['html'], '<table') === substr_count($question_answer['html'], '</table>'), 'question-answer tables are balanced');
km_assert(strpos($question_answer['text'], 'Открыть переписку: https://example.com/lk/?section=questions') !== false, 'plain question-answer email includes conversation URL');

$question_answer_source = file_get_contents(dirname(YOGA_MAIL_PATH, 2) . '/themes/yoga/inc/admin/questions.php');
km_assert(strpos($question_answer_source, "function yoga_send_question_answer_email(string \$recipient_email, string \$answer): bool") !== false, 'question-answer email uses a shared sender');
km_assert(strpos($question_answer_source, "'answer_datetime' => \$answer_datetime") !== false, 'question-answer adapter supplies the localized datetime');
km_assert(strpos($question_answer_source, "'admin_answer' => nl2br(esc_html(\$answer_text))") !== false, 'question-answer adapter escapes administrator content');
km_assert(strpos($question_answer_source, "'action_url' => \$action_url") !== false, 'question-answer adapter supplies the conversation URL');

$comment_reply = $renderer->render('comment-reply', array(
	'reply_author' => 'Ольга Идеальнова',
	'practice_title' => 'Крийя для баланса',
	'reply_datetime' => '14 июля 2026 в 21:32',
	'reply_avatar' => '<img src="https://example.com/avatar.jpg" width="40" height="40" alt="Ольга Идеальнова" style="display:block;width:40px;height:40px;border:0;border-radius:20px;">',
	'reply_content' => 'Спасибо за практику! &lt;script&gt;alert(1)&lt;/script&gt;',
	'action_url' => 'https://example.com/practice/kriya-dlya-balansa/#comment-2037',
), false);
km_assert(!is_wp_error($comment_reply), 'comment-reply template renders');
km_assert($comment_reply['subject'] === 'Вам ответили в комментариях', 'comment-reply subject matches Figma');
km_assert(strpos($comment_reply['html'], '«Крийя для баланса» 14 июля 2026 в 21:32 по МСК.') !== false, 'comment-reply summary contains practice and localized datetime');
km_assert(strpos($comment_reply['html'], 'src="https://example.com/avatar.jpg"') !== false, 'comment-reply contains an absolute avatar URL');
km_assert(strpos($comment_reply['html'], 'border-radius:15px 15px 15px 0') !== false, 'comment-reply card matches the speech-bubble shape');
km_assert(strpos($comment_reply['html'], 'padding:20px 75px 20px 20px') !== false, 'comment-reply card preserves the Figma right whitespace');
km_assert(strpos($comment_reply['html'], '<td width="15" style="width:15px;padding:0;font-size:1px;line-height:1px;">') !== false, 'comment-reply avatar gap matches Figma');
km_assert(strpos($comment_reply['html'], 'width:330px;padding:0;text-align:left;') !== false, 'comment-reply text column matches Figma');
km_assert(strpos($comment_reply['html'], '<script>') === false, 'comment-reply content cannot inject executable HTML');
km_assert(strpos($comment_reply['html'], 'href="https://example.com/practice/kriya-dlya-balansa/#comment-2037"') !== false, 'comment-reply CTA opens the exact reply');
km_assert(strpos($comment_reply['html'], 'support@platform.kundalini-class.ru') !== false, 'comment-reply reuses shared footer');
km_assert(substr_count($comment_reply['html'], '<table') === substr_count($comment_reply['html'], '</table>'), 'comment-reply tables are balanced');
km_assert(strpos($comment_reply['text'], 'Смотреть ответ: https://example.com/practice/kriya-dlya-balansa/#comment-2037') !== false, 'plain comment-reply email includes the reply URL');

$comment_reply_source = file_get_contents(dirname(YOGA_MAIL_PATH, 2) . '/themes/yoga/inc/ajax/comments.php');
km_assert(strpos($comment_reply_source, "wp_schedule_single_event(time() + 5, 'yoga_send_comment_reply_email', array((int) \$comment_id))") !== false, 'comment-reply cron receives the reply ID');
km_assert(strpos($comment_reply_source, "'reply_avatar' => \$reply_avatar") !== false, 'comment-reply adapter supplies the trusted avatar component');
km_assert(strpos($comment_reply_source, "'reply_content' => \$reply_content") !== false, 'comment-reply adapter supplies escaped reply content');

$renewal_success = $renderer->render('renewal-success', array(
	'user_name' => 'Марина',
	'subscription_name' => 'Аришечный Pro Max, 1 месяц',
	'total_amount' => '4 990 ₽',
	'payment_card' => '•• 4242',
	'next_charge_date' => '14 августа 2026',
	'action_url' => 'https://example.com/my-account/view-order/10429/',
), false);
km_assert(!is_wp_error($renewal_success), 'renewal-success template renders');
km_assert($renewal_success['subject'] === 'Подписка автоматически продлена', 'renewal-success subject matches Figma');
km_assert(strpos($renewal_success['html'], 'Оплата прошла') !== false, 'renewal-success badge matches Figma');
km_assert(strpos($renewal_success['html'], 'background-color:#e8ff57') !== false, 'renewal-success badge uses lime background');
km_assert(strpos($renewal_success['html'], 'Сат Нам, Марина!') !== false, 'renewal-success greeting is personalized');
km_assert(strpos($renewal_success['html'], '«Аришечный Pro Max, 1 месяц».') !== false, 'renewal-success tariff is dynamic');
km_assert(strpos($renewal_success['html'], '4 990 ₽') !== false && strpos($renewal_success['html'], '•• 4242') !== false, 'renewal-success payment details exist');
km_assert(strpos($renewal_success['html'], '14 августа 2026') !== false, 'renewal-success next charge date is dynamic');
km_assert(substr_count($renewal_success['html'], 'height:1px;font-size:1px;line-height:1px;background-color:#ffffff') >= 2, 'renewal-success payment panel contains white separators');
km_assert(strpos($renewal_success['html'], 'href="https://example.com/my-account/view-order/10429/"') !== false, 'renewal-success CTA opens receipt');
km_assert(strpos($renewal_success['html'], 'support@platform.kundalini-class.ru') !== false, 'renewal-success reuses shared footer');
km_assert(substr_count($renewal_success['html'], '<table') === substr_count($renewal_success['html'], '</table>'), 'renewal-success tables are balanced');
km_assert(strpos($renewal_success['text'], 'Посмотреть чек: https://example.com/my-account/view-order/10429/') !== false, 'plain renewal-success email includes receipt URL');

$payment_receipt = $renderer->render('payment-success-receipt', array(
	'receipt_number' => '10428',
	'payment_date' => '14 июля 2026',
	'receipt_items' => '<tr><td style="padding:15px 10px 15px 0;color:#606060;">Аришечный Pro Max, 1 месяц</td><td align="right" style="padding:15px 0 15px 10px;color:#606060;">4 990 ₽</td></tr>',
	'total_amount' => '4 990 ₽',
	'payment_method' => 'Карта •• 4242',
	'action_url' => 'https://example.com/my-account/view-order/10428/',
), false);
km_assert(!is_wp_error($payment_receipt), 'payment-success receipt template renders');
km_assert($payment_receipt['subject'] === 'Оплата прошла успешно — чек №10428', 'payment-success receipt subject includes order number');
km_assert(strpos($payment_receipt['html'], 'Спасибо за то что выбрали практиковать с нами!') !== false, 'payment-success receipt heading matches Figma');
km_assert(strpos($payment_receipt['html'], 'Чек №10428') !== false, 'payment-success receipt number exists');
km_assert(strpos($payment_receipt['html'], 'Аришечный Pro Max, 1 месяц') !== false, 'payment-success receipt contains trusted order item component');
km_assert(strpos($payment_receipt['html'], 'Итого оплачено') !== false && strpos($payment_receipt['html'], '4 990 ₽') !== false, 'payment-success receipt contains total');
km_assert(strpos($payment_receipt['html'], 'Карта •• 4242') !== false, 'payment-success receipt contains payment method');
km_assert(strpos($payment_receipt['html'], 'href="https://example.com/my-account/view-order/10428/"') !== false, 'payment-success receipt CTA opens order');
km_assert(strpos($payment_receipt['html'], 'support@platform.kundalini-class.ru') !== false, 'payment-success receipt reuses shared footer');
km_assert(substr_count($payment_receipt['html'], '<table') === substr_count($payment_receipt['html'], '</table>'), 'payment-success receipt tables are balanced');
km_assert(strpos($payment_receipt['text'], 'Посмотреть чек: https://example.com/my-account/view-order/10428/') !== false, 'plain payment-success receipt includes order URL');

$renewal_failed = $renderer->render('renewal-failed', array(
	'subscription_name' => 'Аришечный Pro Max, 1 месяц',
	'total_amount' => '4 990 ₽',
	'payment_card' => '•• 4242',
	'next_attempt_date' => '16 июля 2026',
	'action_url' => 'https://example.com/lk/?section=subscription',
), false);
km_assert(!is_wp_error($renewal_failed), 'renewal-failed template renders');
km_assert($renewal_failed['subject'] === 'Не удалось списать оплату', 'renewal-failed subject matches Figma');
km_assert(strpos($renewal_failed['html'], '«Аришечный Pro Max, 1 месяц».') !== false, 'renewal-failed subscription is dynamic');
km_assert(strpos($renewal_failed['html'], 'color:#9153e1') !== false, 'renewal-failed subscription uses violet accent');
km_assert(strpos($renewal_failed['html'], '4 990 ₽') !== false && strpos($renewal_failed['html'], '•• 4242') !== false, 'renewal-failed payment details exist');
km_assert(strpos($renewal_failed['html'], '16 июля 2026') !== false, 'renewal-failed next retry date exists');
km_assert(substr_count($renewal_failed['html'], 'height:1px;font-size:1px;line-height:1px;background-color:#ffffff') >= 2, 'renewal-failed details contain white separators');
km_assert(strpos($renewal_failed['html'], 'Частые причины: недостаточно средств') !== false, 'renewal-failed warning matches Figma');
km_assert(strpos($renewal_failed['html'], 'href="https://example.com/lk/?section=subscription"') !== false, 'renewal-failed CTA opens subscription settings');
km_assert(strpos($renewal_failed['html'], 'support@platform.kundalini-class.ru') !== false, 'renewal-failed reuses shared footer');
km_assert(substr_count($renewal_failed['html'], '<table') === substr_count($renewal_failed['html'], '</table>'), 'renewal-failed tables are balanced');
km_assert(strpos($renewal_failed['text'], 'Повторить оплату: https://example.com/lk/?section=subscription') !== false, 'plain renewal-failed email includes retry URL');

$woocommerce_source = file_get_contents(YOGA_MAIL_PATH . 'includes/class-yoga-mail-woocommerce.php');
km_assert(strpos($woocommerce_source, "woocommerce_order_status_processing', array(\$this, 'send_payment_success_receipt')") !== false, 'payment-success receipt is connected to paid processing orders');
km_assert(strpos($woocommerce_source, 'PAYMENT_RECEIPT_SENT_META') !== false, 'payment-success receipt has duplicate-send protection');
km_assert(strpos($woocommerce_source, "woocommerce_email_enabled_customer_processing_order', array(\$this, 'disable_standard_processing_email')") !== false, 'standard processing email is always disabled when Yoga Mail handles WooCommerce');
km_assert(strpos($woocommerce_source, 'payment_receipt_sent_order_ids') !== false, 'completed-email suppression survives stale WooCommerce order objects');
km_assert(strpos($woocommerce_source, "YTR_Notifications::send_renewal_success(\$order)") !== false, 'renewal orders use the dedicated success template');
$GLOBALS['km_options'][Yoga_Mail_Registry::SETTINGS_OPTION] = array('woocommerce_enabled' => true);
$woocommerce_mailer = new Yoga_Mail_Mailer($registry, $renderer);
$woocommerce_adapter = new Yoga_Mail_WooCommerce($registry, $renderer, $woocommerce_mailer);
$woocommerce_styles = $woocommerce_adapter->email_styles('body{font-family:Arial,sans-serif;}h1{font-family:Mulish,Arial,sans-serif;}', null);
km_assert(substr_count($woocommerce_styles, 'font-family:Helvetica,sans-serif;') >= 3, 'WooCommerce styles use Helvetica throughout');
km_assert(stripos($woocommerce_styles, 'Arial') === false && stripos($woocommerce_styles, 'Mulish') === false, 'WooCommerce styles have no legacy fonts');
$GLOBALS['km_options'][Yoga_Mail_Registry::SETTINGS_OPTION] = array();
$renewal_notifications_source = file_get_contents(dirname(YOGA_MAIL_PATH) . '/yoga-tariff-renewal/includes/class-ytr-notifications.php');
km_assert(strpos($renewal_notifications_source, "'next_attempt_date' => self::get_next_attempt_date") !== false, 'renewal failure adapter supplies the next retry date');
km_assert(strpos($renewal_notifications_source, 'has_active_auto_renewal') !== false, 'three-day warning uses the resilient auto-renew check');
km_assert(strpos($renewal_notifications_source, 'maybe_backfill_auto_renew') !== false, 'notification cron restores auto-renew meta from an existing recurring card');
km_assert(strpos($renewal_notifications_source, "'expiration_date' => \$end_date") !== false, 'three-day warning supplies the localized expiration date');
km_assert(strpos($renewal_notifications_source, 'META_RENEWAL_SUCCESS_EMAIL_SENT_AT') !== false, 'renewal-success email has duplicate-send protection');
km_assert(strpos($renewal_notifications_source, "'next_charge_date' => self::get_next_charge_date") !== false, 'renewal-success adapter supplies the next charge date');
km_assert(strpos($renewal_notifications_source, 'maybe_send_ended_notification($user_id)') !== false, 'subscription-ended email is connected to the daily notification check');
km_assert(strpos($renewal_notifications_source, 'META_ENDED_EMAIL_END') !== false, 'subscription-ended email has duplicate-send protection');
km_assert(strpos($renewal_notifications_source, "'card_expiry' => sprintf('%02d/%02d'") !== false, 'card-expiring adapter supplies MM/YY');
km_assert(strpos($renewal_notifications_source, 'if ($expiry_period < $current_period)') !== false, 'expired cards do not receive the soon-expiring email');
km_assert(strpos($renewal_notifications_source, 'YTR_User::get_payment_method_id($user_id)') !== false, 'card-expiring adapter selects the card bound to auto-renew');

$email_verification_source = file_get_contents(dirname(YOGA_MAIL_PATH, 2) . '/themes/yoga/inc/ajax/email-verification.php');
km_assert(substr_count($email_verification_source, 'yoga_send_email_verification_success($user_id);') === 2, 'success email is connected to link and code verification');
km_assert(strpos($email_verification_source, 'yoga_email_verification_success_sending') !== false, 'success email has a duplicate-send lock');

$GLOBALS['km_options'][Yoga_Mail_Registry::SETTINGS_OPTION] = array('wordpress_enabled' => true);
$wordpress_adapter = new Yoga_Mail_WordPress($registry, $renderer, new Yoga_Mail_Mailer($registry, $renderer));
$adapted_password_changed = $wordpress_adapter->password_changed(
	array('subject' => 'Password changed', 'message' => 'Your password was changed.'),
	array('user_login' => 'marina', 'display_name' => 'Марина'),
	array()
);
km_assert($adapted_password_changed['subject'] === 'Пароль изменен', 'WordPress adapter replaces password-changed subject');
km_assert(strpos($adapted_password_changed['message'], '14 июля 2026, 21:30') !== false, 'WordPress adapter injects localized event datetime');
km_assert(strpos($adapted_password_changed['message'], 'href="https://example.com/wp-login.php?action=lostpassword"') !== false, 'WordPress adapter injects lost-password URL');
$adapted_email_changed = $wordpress_adapter->email_changed(
	array('subject' => 'Email changed', 'message' => 'Your email was changed.'),
	array('user_login' => 'marina', 'display_name' => 'Марина', 'user_email' => 'marina@example.com'),
	array('user_email' => 'marina.k@example.com')
);
km_assert($adapted_email_changed['subject'] === 'Адрес эл. почты изменен', 'WordPress adapter replaces email-changed subject');
km_assert(strpos($adapted_email_changed['message'], 'marina@example.com') !== false, 'WordPress adapter injects old email address');
km_assert(strpos($adapted_email_changed['message'], 'marina.k@example.com') !== false, 'WordPress adapter injects new email address');
km_assert(strpos($adapted_email_changed['message'], '14 июля 2026, 21:30') !== false, 'WordPress adapter injects email-change datetime');
km_assert(strpos($adapted_email_changed['message'], 'href="mailto:support@platform.kundalini-class.ru"') !== false, 'WordPress adapter injects support CTA');
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
$GLOBALS['km_options'][Yoga_Mail_Registry::SETTINGS_OPTION] = array('custom_enabled' => false, 'fallback_enabled' => false);
km_assert($mailer->send('sadhana-progress', array(
	'to' => 'anna@example.com',
	'data' => array(
		'user_name' => 'Анна',
		'practice_title' => 'Утренняя крийя',
		'milestone' => 40,
		'milestone_day_label' => 'дней',
		'target_days' => 90,
		'target_day_label' => 'дней',
		'action_url' => 'https://example.com/practice/morning/',
	),
)) === true, 'Yoga Mail builds the trusted sadhana progress component');
km_assert(strpos($GLOBALS['km_last_mail']['message'], 'День 40') !== false && strpos($GLOBALS['km_last_mail']['message'], 'Цель — 90 дней') !== false, 'sadhana progress component moved to Yoga Mail');
$GLOBALS['km_options'][Yoga_Mail_Registry::SETTINGS_OPTION] = array('fallback_enabled' => true);
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
