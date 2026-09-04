<?php

if (!defined('ABSPATH')) {
	exit;
}

final class Yoga_Mail_Registry {
	public const SETTINGS_OPTION = 'yoga_mail_settings';
	public const TEMPLATES_OPTION = 'yoga_mail_templates';
	public const LEGACY_SETTINGS_OPTION = 'kundalini_mail_settings';
	public const LEGACY_TEMPLATES_OPTION = 'kundalini_mail_templates';

	private $runtime = array();

	public static function default_settings(): array {
		return array(
			'custom_enabled'      => false,
			'wordpress_enabled'   => false,
			'woocommerce_enabled' => false,
			'fallback_enabled'    => false,
			'logo_url'            => defined('YOGA_MAIL_URL') ? YOGA_MAIL_URL . 'assets/images/email/logo.svg' : content_url('/plugins/yoga-mail/assets/images/email/logo.svg'),
			'logo_alt'            => (string) get_bloginfo('name'),
			'footer_text'         => 'Это служебное письмо по вашему аккаунту на онлайн-платформе Кундалини Класс.',
		);
	}

	public function settings(): array {
		$stored = get_option(self::SETTINGS_OPTION, null);
		if (!is_array($stored)) {
			$stored = get_option(self::LEGACY_SETTINGS_OPTION, array());
		}
		$settings = wp_parse_args(is_array($stored) ? $stored : array(), self::default_settings());
		if ((string) $settings['logo_url'] === content_url('/themes/yoga/assets/img/logo.png')) {
			$settings['logo_url'] = self::default_settings()['logo_url'];
		}
		if ((string) $settings['footer_text'] === 'Kundalini Class') {
			$settings['footer_text'] = self::default_settings()['footer_text'];
		}
		return $settings;
	}

	public function flag(string $name): bool {
		$settings = $this->settings();
		return !empty($settings[$name]);
	}

	public function register(string $id, array $definition): void {
		$id = sanitize_key($id);
		if ($id === '') {
			return;
		}
		$this->runtime[$id] = $this->normalize_definition($id, $definition);
	}

	public function all(): array {
		$definitions = array_merge($this->defaults(), $this->runtime);
		$definitions = apply_filters('kundalini_mail_template_definitions', $definitions);
		return apply_filters('yoga_mail_template_definitions', $definitions);
	}

	public function get(string $id): ?array {
		$all = $this->all();
		return isset($all[$id]) ? $all[$id] : null;
	}

	public function values(string $id): ?array {
		$definition = $this->get($id);
		if (!$definition) {
			return null;
		}
		$stored = get_option(self::TEMPLATES_OPTION, null);
		if (!is_array($stored)) {
			$stored = get_option(self::LEGACY_TEMPLATES_OPTION, array());
		}
		$custom = is_array($stored) && isset($stored[$id]) && is_array($stored[$id]) ? $stored[$id] : array();
		return wp_parse_args($custom, $definition['defaults']);
	}

	public function examples(string $id): array {
		$definition = $this->get($id);
		$examples = array();
		if (!$definition) {
			return $examples;
		}
		foreach ($definition['tags'] as $tag => $details) {
			$examples[$tag] = isset($details['example']) ? $details['example'] : '';
		}
		return $examples;
	}

	public function save_values(string $id, array $input): bool {
		$definition = $this->get($id);
		if (!$definition) {
			return false;
		}
		$clean = array(
			'subject'     => sanitize_text_field((string) ($input['subject'] ?? '')),
			'preheader'   => sanitize_text_field((string) ($input['preheader'] ?? '')),
			'heading'     => sanitize_text_field((string) ($input['heading'] ?? '')),
			'body'        => wp_kses_post((string) ($input['body'] ?? '')),
			'cta_label'   => sanitize_text_field((string) ($input['cta_label'] ?? '')),
			'cta_url'     => sanitize_text_field((string) ($input['cta_url'] ?? '')),
			'footer_note' => sanitize_text_field((string) ($input['footer_note'] ?? '')),
		);
		foreach ($clean as $field => $value) {
			if (preg_match_all('/\{\{\s*([a-zA-Z0-9_-]+)\s*\}\}/', (string) $value, $matches)) {
				foreach ($matches[1] as $tag) {
					$tag = sanitize_key($tag);
					if (!isset($definition['tags'][$tag])) {
						return false;
					}
					$type = (string) ($definition['tags'][$tag]['type'] ?? 'text');
					if ($field === 'cta_url' && $type !== 'url') {
						return false;
					}
					if ($field === 'body' && $type === 'url') {
						return false;
					}
					if (!in_array($field, array('body', 'cta_url'), true) && $type !== 'text') {
						return false;
					}
				}
			}
		}
		$stored = get_option(self::TEMPLATES_OPTION, array());
		$stored = is_array($stored) ? $stored : array();
		$stored[$id] = $clean;
		update_option(self::TEMPLATES_OPTION, $stored, false);
		return true;
	}

	public function reset_values(string $id): bool {
		$stored = get_option(self::TEMPLATES_OPTION, array());
		if (!is_array($stored) || !array_key_exists($id, $stored)) {
			return true;
		}
		unset($stored[$id]);
		update_option(self::TEMPLATES_OPTION, $stored, false);
		return true;
	}

	private function defaults(): array {
		$common = $this->common_tags();
		$existing = array(
			'subject'     => '{{subject}}',
			'preheader'   => '{{subject}}',
			'heading'     => '{{subject}}',
			'body'        => '{{content}}',
			'cta_label'   => '',
			'cta_url'     => '',
			'footer_note' => '',
		);
		$definitions = array(
			'generic' => array('label' => 'Универсальное письмо', 'group' => 'Системные', 'defaults' => $existing),
			'wp-new-user' => array('label' => 'WordPress: новый пользователь', 'group' => 'WordPress', 'defaults' => $existing),
			'wp-reset-password' => array(
				'label' => 'WordPress: восстановление пароля',
				'group' => 'WordPress',
				'defaults' => array(
					'subject' => 'Восстановление пароля',
					'preheader' => 'Создайте новый пароль для аккаунта {{site_name}}',
					'heading' => 'Восстановление пароля',
					'body' => '<p style="margin:0;line-height:1;font-weight:700;">Сат Нам, {{user_name}}!</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;">Мы получили запрос на сброс пароля.<br>Нажмите кнопку ниже, чтобы задать новый.</p>',
					'cta_label' => 'Создать новый пароль',
					'cta_url' => '{{action_url}}',
					'footer_note' => 'Ссылка действует 60 мин. Если вы не запрашивали сброс — проигнорируйте письмо, пароль останется прежним.',
				),
				'tags' => array(
					'action_url' => array('type' => 'url', 'example' => home_url('/wp-login.php?action=rp&key=preview&login=marina')),
				),
			),
			'wp-password-changed' => array(
				'label' => 'WordPress: пароль изменён',
				'group' => 'WordPress',
				'defaults' => array(
					'subject' => 'Пароль изменен',
					'preheader' => 'Пароль от вашего аккаунта успешно изменён',
					'heading' => 'Пароль изменен',
					'body' => '<p style="margin:0;line-height:1.5;font-weight:400;text-align:center;">Вы успешно сменили пароль от аккаунта.<br>Теперь для входа используйте новый пароль.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f6f6f9;border-radius:15px;"><tr><td width="50%" valign="middle" bgcolor="#f6f6f9" style="padding:20px;background-color:#f6f6f9;border-radius:15px 0 0 15px;"><p style="margin:0;line-height:1;font-weight:400;color:#606060;text-align:left;">Дата и время</p></td><td width="50%" align="right" valign="middle" bgcolor="#f6f6f9" style="padding:20px;background-color:#f6f6f9;border-radius:0 15px 15px 0;"><p style="margin:0;line-height:1;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{event_datetime}}</p></td></tr></table><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:15px 0 0;border-collapse:separate;background-color:#fceeee;border:1px solid #e15355;border-radius:15px;"><tr><td bgcolor="#fceeee" style="padding:20px;background-color:#fceeee;border-radius:15px;"><p style="margin:0;line-height:1.5;font-weight:400;color:#e15355;text-align:left;">Это были не вы? Немедленно восстановите доступ и свяжитесь с поддержкой.</p></td></tr></table>',
					'cta_label' => 'Это были не вы?',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'action_url' => array('type' => 'url', 'example' => wp_lostpassword_url()),
				),
			),
			'wp-email-changed' => array(
				'label' => 'WordPress: email изменён',
				'group' => 'WordPress',
				'defaults' => array(
					'subject' => 'Адрес эл. почты изменен',
					'preheader' => 'Адрес электронной почты для входа в аккаунт изменён',
					'heading' => 'Адрес эл. почты изменен',
					'body' => '<p style="margin:0;line-height:1.5;font-weight:400;text-align:center;">Вы успешно сменили адрес эл. почты для входа в аккаунт.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f6f6f9;border-radius:15px;"><tr><td width="40%" valign="middle" bgcolor="#f6f6f9" style="padding:20px 0 0 20px;background-color:#f6f6f9;border-radius:15px 0 0;"><p style="margin:0;line-height:1;font-weight:400;color:#606060;text-align:left;">Прежний адрес</p></td><td width="60%" align="right" valign="middle" bgcolor="#f6f6f9" style="padding:20px 20px 0 0;background-color:#f6f6f9;border-radius:0 15px 0 0;"><p style="margin:0;line-height:1;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{old_email}}</p></td></tr><tr><td colspan="2" bgcolor="#f6f6f9" style="padding:15px 20px 0;background-color:#f6f6f9;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr></table></td></tr><tr><td width="40%" valign="middle" bgcolor="#f6f6f9" style="padding:15px 0 0 20px;background-color:#f6f6f9;"><p style="margin:0;line-height:1;font-weight:400;color:#606060;text-align:left;">Новый адрес</p></td><td width="60%" align="right" valign="middle" bgcolor="#f6f6f9" style="padding:15px 20px 0 0;background-color:#f6f6f9;"><p style="margin:0;line-height:1;font-weight:700;color:#9153e1;text-align:right;white-space:nowrap;">{{new_email}}</p></td></tr><tr><td colspan="2" bgcolor="#f6f6f9" style="padding:15px 20px 0;background-color:#f6f6f9;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr></table></td></tr><tr><td width="40%" valign="middle" bgcolor="#f6f6f9" style="padding:15px 0 20px 20px;background-color:#f6f6f9;border-radius:0 0 0 15px;"><p style="margin:0;line-height:1;font-weight:400;color:#606060;text-align:left;">Дата и время</p></td><td width="60%" align="right" valign="middle" bgcolor="#f6f6f9" style="padding:15px 20px 20px 0;background-color:#f6f6f9;border-radius:0 0 15px;"><p style="margin:0;line-height:1;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{event_datetime}}</p></td></tr></table><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:15px 0 0;border-collapse:separate;background-color:#fceeee;border:1px solid #e15355;border-radius:15px;"><tr><td bgcolor="#fceeee" style="padding:20px;background-color:#fceeee;border-radius:15px;"><p style="margin:0;line-height:1.5;font-weight:400;color:#e15355;text-align:left;">Это были не вы? Немедленно восстановите доступ и свяжитесь с поддержкой.</p></td></tr></table>',
					'cta_label' => 'Связаться с поддержкой',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'action_url' => array('type' => 'url', 'example' => 'mailto:support@platform.kundalini-class.ru'),
				),
			),
			'wp-admin-email-changed' => array('label' => 'WordPress: email администратора изменён', 'group' => 'WordPress', 'defaults' => $existing),
			'wp-comment-notification' => array('label' => 'WordPress: новый комментарий', 'group' => 'WordPress', 'defaults' => $existing),
			'wp-comment-moderation' => array('label' => 'WordPress: модерация комментария', 'group' => 'WordPress', 'defaults' => $existing),
			'wp-recovery-mode' => array('label' => 'WordPress: режим восстановления', 'group' => 'WordPress', 'defaults' => $existing),
			'email-verification' => array(
				'label' => 'Подтверждение email: код',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Ваш код подтверждения',
					'preheader' => 'Код для подтверждения вашей электронной почты',
					'heading' => 'Ваш код подтверждения',
					'body' => '<p style="margin:0;line-height:1.5;font-weight:400;text-align:center;">Введите этот код на сайте, чтобы подтвердить свою эл. почту.</p><table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:30px auto 0;border-collapse:separate;"><tr><td width="44" height="54" align="center" valign="middle" bgcolor="#f6f6f9" style="width:44px;height:54px;background-color:#f6f6f9;border:1px solid #dedee1;border-radius:12px;font-size:22px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;">{{code_digit_1}}</td><td width="15" style="width:15px;font-size:1px;line-height:1px;">&nbsp;</td><td width="44" height="54" align="center" valign="middle" bgcolor="#f6f6f9" style="width:44px;height:54px;background-color:#f6f6f9;border:1px solid #dedee1;border-radius:12px;font-size:22px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;">{{code_digit_2}}</td><td width="15" style="width:15px;font-size:1px;line-height:1px;">&nbsp;</td><td width="44" height="54" align="center" valign="middle" bgcolor="#f6f6f9" style="width:44px;height:54px;background-color:#f6f6f9;border:1px solid #dedee1;border-radius:12px;font-size:22px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;">{{code_digit_3}}</td><td width="15" style="width:15px;font-size:1px;line-height:1px;">&nbsp;</td><td width="44" height="54" align="center" valign="middle" bgcolor="#f6f6f9" style="width:44px;height:54px;background-color:#f6f6f9;border:1px solid #dedee1;border-radius:12px;font-size:22px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;">{{code_digit_4}}</td></tr></table>',
					'cta_label' => '',
					'cta_url' => '',
					'footer_note' => 'Код действует {{ttl_minutes}} мин. Никому не сообщайте его — сотрудники Кундалини Класс никогда не спрашивают код.',
				),
			),
			'email-verification-registration' => array(
				'label' => 'Подтверждение email: регистрация',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Подтвердите эл. почту',
					'preheader' => 'Подтвердите эл. почту, чтобы активировать аккаунт',
					'heading' => 'Осталось чуть-чуть..',
					'body' => '<p style="margin:0;line-height:1;font-weight:700;text-align:center;">Сат Нам, {{user_name}}!</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Спасибо за регистрацию. Остался один шаг — подтвердите вашу эл. почту, чтобы активировать аккаунт.</p>',
					'cta_label' => 'Подтвердить эл. почту',
					'cta_url' => '{{action_url}}',
					'footer_note' => 'Ссылка активна 24 ч. Если вы не регистрировались — просто проигнорируйте это письмо.',
				),
				'tags' => array(
					'action_url' => array('type' => 'url', 'example' => home_url('/?yoga_verify_email=1&uid=42&token=preview')),
				),
			),
			'email-verification-success' => array(
				'label' => 'Подтверждение email: регистрация завершена',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Добро пожаловать в Кундалини Класс',
					'preheader' => 'Электронная почта подтверждена — всё готово к практике',
					'heading' => 'Сат Нам, {{user_name}}!',
					'body' => '<p style="margin:0;line-height:1.5;font-weight:400;text-align:center;">Мы очень рады, что вы с нами. Всё готово к практике — вот с чего удобнее начать.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:15px 0 0;border-collapse:separate;background-color:#f6f6f9;border-radius:15px;"><tr><td bgcolor="#f6f6f9" style="padding:20px;background-color:#f6f6f9;border-radius:15px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td width="30" align="center" valign="middle" style="width:30px;padding:0;"><table role="presentation" width="30" height="30" cellpadding="0" cellspacing="0" border="0" align="center" style="width:30px;height:30px;border-collapse:separate;"><tr><td width="30" height="30" align="center" valign="middle" bgcolor="#9153e1" style="width:30px;height:30px;background-color:#9153e1;border-radius:15px;font-size:14px;line-height:30px;font-weight:700;color:#ffffff;text-align:center;">1</td></tr></table></td><td valign="middle" style="padding-left:15px;font-size:14px;line-height:1.5;font-weight:400;color:#1f1f1f;text-align:left;">Поставьте аватар в профиле, чтобы быть ярче в комментариях</td></tr><tr><td colspan="2" bgcolor="#f6f6f9" style="padding:15px 0;background-color:#f6f6f9;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr></table></td></tr><tr><td width="30" align="center" valign="middle" style="width:30px;padding:0;"><table role="presentation" width="30" height="30" cellpadding="0" cellspacing="0" border="0" align="center" style="width:30px;height:30px;border-collapse:separate;"><tr><td width="30" height="30" align="center" valign="middle" bgcolor="#9153e1" style="width:30px;height:30px;background-color:#9153e1;border-radius:15px;font-size:14px;line-height:30px;font-weight:700;color:#ffffff;text-align:center;">2</td></tr></table></td><td valign="middle" style="padding-left:15px;font-size:14px;line-height:1.5;font-weight:400;color:#1f1f1f;text-align:left;">Выберите часовой пояс в настройках — чтобы дни садханы считались верно</td></tr><tr><td colspan="2" bgcolor="#f6f6f9" style="padding:15px 0;background-color:#f6f6f9;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr></table></td></tr><tr><td width="30" align="center" valign="middle" style="width:30px;padding:0;"><table role="presentation" width="30" height="30" cellpadding="0" cellspacing="0" border="0" align="center" style="width:30px;height:30px;border-collapse:separate;"><tr><td width="30" height="30" align="center" valign="middle" bgcolor="#9153e1" style="width:30px;height:30px;background-color:#9153e1;border-radius:15px;font-size:14px;line-height:30px;font-weight:700;color:#ffffff;text-align:center;">3</td></tr></table></td><td valign="middle" style="padding-left:15px;font-size:14px;line-height:1.5;font-weight:400;color:#1f1f1f;text-align:left;">Настройте нужные уведомления и напоминания в настройках</td></tr><tr><td colspan="2" bgcolor="#f6f6f9" style="padding:15px 0;background-color:#f6f6f9;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr></table></td></tr><tr><td width="30" align="center" valign="middle" style="width:30px;padding:0;"><table role="presentation" width="30" height="30" cellpadding="0" cellspacing="0" border="0" align="center" style="width:30px;height:30px;border-collapse:separate;"><tr><td width="30" height="30" align="center" valign="middle" bgcolor="#9153e1" style="width:30px;height:30px;background-color:#9153e1;border-radius:15px;font-size:14px;line-height:30px;font-weight:700;color:#ffffff;text-align:center;">4</td></tr></table></td><td valign="middle" style="padding-left:15px;font-size:14px;line-height:1.5;font-weight:400;color:#1f1f1f;text-align:left;">Загляните в библиотеку практик и добавьте первую крийю в избранное</td></tr></table></td></tr></table><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:15px 0 0;border-collapse:separate;background-color:#f8f3fd;border-radius:15px;"><tr><td bgcolor="#f8f3fd" style="padding:20px;background-color:#f8f3fd;border-radius:15px;font-size:14px;line-height:1.5;font-weight:400;color:#9153e1;text-align:left;">А главное — выберите садхану: практику, которую вы выполняете каждый день подряд. Платформа сама посчитает дни и напомнит о занятии.</td></tr></table>',
					'cta_label' => 'Перейти в ЛК',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'action_url' => array('type' => 'url', 'example' => home_url('/lk/')),
				),
			),
			'payment-success-receipt' => array(
				'label' => 'WooCommerce: успешная оплата и чек',
				'group' => 'WooCommerce',
				'defaults' => array(
					'subject' => 'Оплата прошла успешно — чек №{{receipt_number}}',
					'preheader' => 'Спасибо за покупку! Чек по заказу №{{receipt_number}} внутри письма',
					'heading' => 'Спасибо за то что выбрали практиковать с нами!',
					'body' => '<p style="margin:0;line-height:1.5;font-weight:400;text-align:center;">Ваш чек о приобретении подписки ниже.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#ffffff;border:1px solid #dedee1;border-radius:15px;"><tr><td style="padding:20px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td valign="middle" style="padding:0 10px 15px 0;font-size:14px;line-height:1.5;font-weight:700;color:#1f1f1f;text-align:left;">Чек №{{receipt_number}}</td><td valign="middle" align="right" style="padding:0 0 15px 10px;font-size:14px;line-height:1.5;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{payment_date}}</td></tr><tr><td colspan="2" height="1" bgcolor="#dedee1" style="height:1px;font-size:1px;line-height:1px;background-color:#dedee1;">&nbsp;</td></tr>{{receipt_items}}<tr><td colspan="2" height="1" bgcolor="#dedee1" style="height:1px;font-size:1px;line-height:1px;background-color:#dedee1;">&nbsp;</td></tr><tr><td valign="middle" style="padding:15px 10px 0 0;font-size:14px;line-height:1.5;font-weight:700;color:#1f1f1f;text-align:left;">Итого оплачено</td><td valign="middle" align="right" style="padding:15px 0 0 10px;font-size:14px;line-height:1.5;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{total_amount}}</td></tr><tr><td valign="middle" style="padding:15px 10px 0 0;font-size:14px;line-height:1.5;font-weight:400;color:#606060;text-align:left;">Способ оплаты</td><td valign="middle" align="right" style="padding:15px 0 0 10px;font-size:14px;line-height:1.5;font-weight:400;color:#606060;text-align:right;white-space:nowrap;">{{payment_method}}</td></tr></table></td></tr></table>',
					'cta_label' => 'Посмотреть чек',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'receipt_number' => array('type' => 'text', 'example' => '10428'),
					'payment_date' => array('type' => 'text', 'example' => '14 июля 2026'),
					'receipt_items' => array('type' => 'html', 'example' => '<tr><td style="padding:15px 10px 15px 0;color:#606060;">Аришечный Pro Max, 1 месяц</td><td align="right" style="padding:15px 0 15px 10px;color:#606060;white-space:nowrap;">4 990 ₽</td></tr>'),
					'total_amount' => array('type' => 'text', 'example' => '4 990 ₽'),
					'payment_method' => array('type' => 'text', 'example' => 'Карта •• 4242'),
					'action_url' => array('type' => 'url', 'example' => home_url('/my-account/view-order/10428/')),
				),
			),
			'support-autoreply' => array(
				'label' => 'Автоответ поддержки: обращение получено',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Мы получили ваше письмо',
					'preheader' => 'Ваше обращение №{{request_number}} зарегистрировано — мы ответим в рабочее время',
					'heading' => 'Мы получили ваше письмо',
					'body' => '<p style="margin:0;line-height:1.5;font-weight:400;text-align:center;">Спасибо, что написали. Ваше обращение зарегистрировано — мы ответим в рабочее время, обычно в течение одного дня.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f6f6f9;border-radius:15px;"><tr><td bgcolor="#f6f6f9" style="padding:20px;background-color:#f6f6f9;border-radius:15px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td width="42%" valign="middle" style="padding:0 10px 15px 0;font-size:14px;line-height:1;font-weight:400;color:#606060;text-align:left;">Номер обращения</td><td width="58%" valign="middle" align="right" style="padding:0 0 15px 10px;font-size:14px;line-height:1;font-weight:700;color:#9153e1;text-align:right;white-space:nowrap;">№ {{request_number}}</td></tr><tr><td colspan="2" height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr><tr><td width="42%" valign="middle" style="padding:15px 10px;font-size:14px;line-height:1;font-weight:400;color:#606060;text-align:left;">Получено</td><td width="58%" valign="middle" align="right" style="padding:15px 0 15px 10px;font-size:14px;line-height:1;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{received_datetime}} по МСК</td></tr><tr><td colspan="2" height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr><tr><td width="42%" valign="middle" style="padding:15px 10px 0 0;font-size:14px;line-height:1;font-weight:400;color:#606060;text-align:left;">Мы работаем</td><td width="58%" valign="middle" align="right" style="padding:15px 0 0 10px;font-size:14px;line-height:1;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">с 11:00 до 22:00 по МСК</td></tr></table></td></tr></table><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:15px 0 0;border-collapse:separate;background-color:#f7f3fd;border-radius:15px;"><tr><td align="left" bgcolor="#f7f3fd" style="padding:20px;background-color:#f7f3fd;border-radius:15px;font-size:14px;line-height:1.5;font-weight:400;color:#9153e1;text-align:left;">Пожалуйста, не отвечайте на это письмо — оно отправлено автоматически. Если нужно что-то добавить, напишите нам на&nbsp;<a href="mailto:support@platform.kundalini-class.ru" style="font-weight:700;color:#9153e1;text-decoration:none;">support@platform.kundalini-class.ru</a>.</td></tr></table>',
					'cta_label' => '',
					'cta_url' => '',
					'footer_note' => '',
				),
				'tags' => array(
					'request_number' => array('type' => 'text', 'example' => '4821'),
					'received_datetime' => array('type' => 'text', 'example' => '14 июля 2026, 21:40'),
				),
			),
			'question-answer' => array(
				'label' => 'Ответ администратора',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Ответ от администратора',
					'preheader' => 'Администратор ответил на ваш вопрос в личных сообщениях {{answer_datetime}} по МСК',
					'heading' => 'Ответ от администратора',
					'body' => '<p style="margin:0;line-height:1.5;font-weight:400;text-align:center;"><strong style="font-weight:700;">Администратор</strong> ответил на ваш вопрос в личных сообщениях <strong style="font-weight:700;">{{answer_datetime}} по МСК.</strong></p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f7f3fd;border-radius:15px 15px 15px 0;"><tr><td align="left" bgcolor="#f7f3fd" style="padding:20px;background-color:#f7f3fd;border-radius:15px 15px 15px 0;font-family:Helvetica,sans-serif;font-size:14px;line-height:1.5;font-weight:400;color:#1f1f1f;text-align:left;"><table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;"><tr><td align="center" bgcolor="#9153e1" style="padding:6px 10px;background-color:#9153e1;border-radius:30px;font-family:Helvetica,sans-serif;font-size:12px;line-height:1;font-weight:700;color:#ffffff;text-align:center;text-transform:uppercase;white-space:nowrap;">Администратор</td></tr></table><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td align="left" style="padding:15px 0 0;font-family:Helvetica,sans-serif;font-size:14px;line-height:1.5;font-weight:400;color:#1f1f1f;text-align:left;">{{admin_answer}}</td></tr></table></td></tr></table>',
					'cta_label' => 'Открыть переписку',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'answer_datetime' => array('type' => 'text', 'example' => '14 июля 2026 в 21:32'),
					'admin_answer' => array('type' => 'html', 'example' => 'Здравствуйте! Продлить подписку можно в разделе «Тарифы» личного кабинета. Если возникнут вопросы — пишите, поможем.'),
					'action_url' => array('type' => 'url', 'example' => home_url('/lk/?section=questions')),
				),
			),
			'comment-reply' => array(
				'label' => 'Ответ на комментарий',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Вам ответили в комментариях',
					'preheader' => '{{reply_author}} ответил(а) на ваш комментарий к практике «{{practice_title}}»',
					'heading' => 'Вам ответили в комментариях',
					'body' => '<p style="margin:0;line-height:1.5;font-weight:400;text-align:center;"><strong style="font-weight:700;">{{reply_author}}</strong> ответил(а) на ваш комментарий к практике <strong style="font-weight:700;">«{{practice_title}}» {{reply_datetime}} по МСК.</strong></p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f6f6f9;border-radius:15px 15px 15px 0;"><tr><td width="40" valign="top" bgcolor="#f6f6f9" style="width:40px;padding:20px 0 20px 20px;background-color:#f6f6f9;border-radius:15px 0 0 0;">{{reply_avatar}}</td><td valign="top" bgcolor="#f6f6f9" style="padding:20px 20px 20px 15px;background-color:#f6f6f9;border-radius:0 15px 15px 0;text-align:left;"><p style="margin:0;font-size:14px;line-height:1;font-weight:700;color:#1f1f1f;text-align:left;">{{reply_author}}</p><p style="margin:10px 0 0;font-size:14px;line-height:1.5;font-weight:400;color:#1f1f1f;text-align:left;">{{reply_content}}</p></td></tr></table>',
					'cta_label' => 'Смотреть ответ',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'reply_author' => array('type' => 'text', 'example' => 'Ольга Идеальнова'),
					'practice_title' => array('type' => 'text', 'example' => 'Крийя для баланса'),
					'reply_datetime' => array('type' => 'text', 'example' => '14 июля 2026 в 21:32'),
					'reply_avatar' => array('type' => 'html', 'example' => '<img src="https://secure.gravatar.com/avatar/00000000000000000000000000000000?s=80&amp;d=mp" width="40" height="40" alt="Ольга Идеальнова" style="display:block;width:40px;height:40px;border:0;border-radius:20px;outline:none;text-decoration:none;">'),
					'reply_content' => array('type' => 'html', 'example' => 'Спасибо за практику! Очень полезное занятие.'),
					'action_url' => array('type' => 'url', 'example' => home_url('/practice/kriya-dlya-balansa/#comment-2037')),
				),
			),
			'sadhana-started' => array(
				'label' => 'Садхана: после старта',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Что такое садхана?',
					'preheader' => 'Садхана — это спокойная регулярность, которая меняет состояние',
					'heading' => 'Что такое садхана?',
					'body' => '<p style="margin:0;line-height:1;font-weight:700;text-align:center;">Сат Нам, {{user_name}}!</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Вы недавно зарегистрировались на платформе, и мы понемногу знакомим вас с тем, как здесь всё устроено. Начнём с главного — с садханы.</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Садхана — это личная практика, которую вы выполняете каждый день определённое количество дней подряд. Не соревнование и не марафон, а спокойная регулярность, которая меняет состояние.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f6f6f9;border-radius:15px;"><tr><td bgcolor="#f6f6f9" style="padding:20px;background-color:#f6f6f9;border-radius:15px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td width="19%" valign="middle" style="padding:0 10px 15px 0;font-size:14px;line-height:1;font-weight:700;color:#9153e1;text-align:left;white-space:nowrap;">40 дней</td><td width="81%" valign="middle" align="right" style="padding:0 0 15px 10px;font-size:14px;line-height:1;font-weight:400;color:#1f1f1f;text-align:right;white-space:nowrap;">чтобы освободиться от старой привычки</td></tr><tr><td colspan="2" height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr><tr><td width="19%" valign="middle" style="padding:15px 10px 15px 0;font-size:14px;line-height:1;font-weight:700;color:#9153e1;text-align:left;white-space:nowrap;">90 дней</td><td width="81%" valign="middle" align="right" style="padding:15px 0 15px 10px;font-size:14px;line-height:1;font-weight:400;color:#1f1f1f;text-align:right;white-space:nowrap;">чтобы закрепить новую</td></tr><tr><td colspan="2" height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr><tr><td width="19%" valign="middle" style="padding:15px 10px 0 0;font-size:14px;line-height:1;font-weight:700;color:#9153e1;text-align:left;white-space:nowrap;">120 дней</td><td width="81%" valign="middle" align="right" style="padding:15px 0 0 10px;font-size:14px;line-height:1;font-weight:400;color:#1f1f1f;text-align:right;white-space:nowrap;">чтобы новое стало вашей частью</td></tr></table></td></tr></table><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Мы считаем дни за вас: отмечайте практику в кабинете, а платформа покажет прогресс и напомнит, если вы забудете. Пропустили день — можно начать заново, это нормально.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f7f3fd;border-radius:15px;"><tr><td align="center" bgcolor="#f7f3fd" style="padding:20px;background-color:#f7f3fd;border-radius:15px;font-size:14px;line-height:1.5;font-weight:400;color:#9153e1;text-align:center;">Начните с малого: выберите короткую крийю на 7 дней. Регулярность важнее длительности.</td></tr></table>',
					'cta_label' => 'В библиотеку практик',
					'cta_url' => '{{library_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'library_url' => array('type' => 'url', 'example' => home_url('/practice-type/kriyi/')),
				),
			),
			'sadhana-progress' => array(
				'label' => 'Садхана: прохождение рубежа',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Садхана: {{milestone}} {{milestone_day_label}}',
					'preheader' => 'Уже {{milestone}} {{milestone_day_label}} подряд вы держите садхану «{{practice_title}}»',
					'heading' => '',
					'body' => '<table role="presentation" width="110" cellpadding="0" cellspacing="0" border="0" align="center" style="width:110px;margin:0 auto;border-collapse:separate;"><tr><td width="110" height="110" align="center" valign="middle" bgcolor="#9153e1" style="width:110px;height:110px;background-color:#9153e1;border-radius:55px;text-align:center;vertical-align:middle;"><p style="margin:0;font-family:Helvetica,sans-serif;font-size:38px;line-height:38px;font-weight:700;color:#e8ff57;text-align:center;">{{milestone}}</p><p style="margin:2px 0 0;font-family:Helvetica,sans-serif;font-size:14px;line-height:14px;font-weight:400;color:#ffffff;text-align:center;">{{milestone_day_label}}</p></td></tr></table><h1 style="margin:15px 0 0;font-family:Helvetica,sans-serif;font-size:22px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;">Вы прошли рубеж по садхане!</h1><p style="margin:15px 0 0;line-height:1;font-weight:700;text-align:center;">Сат Нам, {{user_name}}!</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Уже <strong style="font-weight:700;color:#9153e1;">{{milestone}} {{milestone_day_label}}</strong> подряд вы держите садхану <strong style="font-weight:700;color:#9153e1;">«{{practice_title}}»</strong>. Это настоящая дисциплина — продолжайте в своём ритме.</p>{{progress_component}}',
					'cta_label' => 'Продолжить практику',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'milestone_day_label' => array('type' => 'text', 'example' => 'дней'),
					'progress_component' => array('type' => 'html', 'example' => '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:15px 0 0;border-collapse:collapse;"><tr><td align="left" style="padding:0;font-size:14px;line-height:1;color:#606060;text-align:left;">День 40</td><td align="right" style="padding:0;font-size:14px;line-height:1;color:#606060;text-align:right;">Цель — 90 дней</td></tr><tr><td colspan="2" style="padding:10px 0 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:separate;"><tr><td height="10" bgcolor="#f8bdf6" style="height:10px;padding:0;background-color:#f8bdf6;border-radius:5px;font-size:1px;line-height:1px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td width="44%" height="10" bgcolor="#9153e1" style="width:44%;height:10px;background-color:#9153e1;border-radius:5px;font-size:1px;line-height:1px;">&nbsp;</td><td width="56%" height="10" style="width:56%;height:10px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table></td></tr></table></td></tr></table>'),
				),
			),
			'sadhana-interrupted' => array(
				'label' => 'Садхана: серия прервана',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Серия садханы прервалась...',
					'preheader' => 'Серия садханы «{{practice_title}}» прервалась на {{milestone}}-м дне — начать заново можно прямо сейчас',
					'heading' => 'Серия садханы прервалась...',
					'body' => '<p style="margin:0;line-height:1;font-weight:700;text-align:center;">Сат Нам, {{user_name}}!</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Серия садханы <strong style="font-weight:700;color:#9153e1;">«{{practice_title}}»</strong> прервалась на <strong style="font-weight:700;color:#9153e1;">{{milestone}}-м</strong> дне. Ничего страшного — это часть пути. Каждый новый день имеет значение, и начать заново можно прямо сейчас.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f7f3fd;border-radius:15px;"><tr><td align="center" bgcolor="#f7f3fd" style="padding:20px;background-color:#f7f3fd;border-radius:15px;font-family:Helvetica,sans-serif;font-size:14px;line-height:1;font-weight:600;letter-spacing:-0.154px;color:#9153e1;text-align:center;">Прогресс не потерян: ваши {{milestone}} {{milestone_day_label}} практики остаются с вами.</td></tr></table>',
					'cta_label' => 'Возобновить садхану',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'milestone_day_label' => array('type' => 'text', 'example' => 'дней'),
				),
			),
			'sadhana-completed' => array(
				'label' => 'Садхана: пройдена',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Садхана пройдена',
					'preheader' => 'Вы прошли все {{target_days}} {{target_day_label}} садханы «{{practice_title}}»',
					'heading' => '',
					'body' => '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto;border-collapse:separate;"><tr><td align="center" bgcolor="#e8ff57" style="padding:8px 14px;background-color:#e8ff57;border-radius:500px;font-family:Helvetica,sans-serif;font-size:14px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;text-transform:uppercase;white-space:nowrap;">Садхана пройдена</td></tr></table><h1 style="margin:15px 0 0;font-family:Helvetica,sans-serif;font-size:22px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;">Сат Нам, {{user_name}}! Вы прошли всю Садхану</h1><p style="margin:15px 0 0;line-height:1;font-weight:700;text-align:center;">Сат Нам, {{user_name}}!</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Все <strong style="font-weight:700;color:#9153e1;">{{target_days}}</strong> {{target_day_label}} садханы <strong style="font-weight:700;color:#9153e1;">«{{practice_title}}»</strong> пройдены.<br>Вы начали <strong style="font-weight:700;color:#9153e1;">{{started_date}} и дошли до конца</strong> — это большое достижение и настоящая трансформация.</p><table role="presentation" width="440" cellpadding="0" cellspacing="0" border="0" align="center" style="width:100%;max-width:440px;margin:30px auto 0;border-collapse:separate;"><tr><td width="214" align="center" valign="middle" bgcolor="#f6f6f9" style="width:50%;padding:16px;background-color:#f6f6f9;border-radius:15px;text-align:center;"><p style="margin:0;font-family:Helvetica,sans-serif;font-size:22px;line-height:1;font-weight:700;color:#9153e1;text-align:center;">{{target_days}}</p><p style="margin:4px 0 0;font-family:Helvetica,sans-serif;font-size:12px;line-height:1;font-weight:400;color:#606060;text-align:center;">{{target_day_label}} подряд</p></td><td width="12" style="width:12px;padding:0;font-size:1px;line-height:1px;">&nbsp;</td><td width="214" align="center" valign="middle" bgcolor="#f6f6f9" style="width:50%;padding:16px;background-color:#f6f6f9;border-radius:15px;text-align:center;"><p style="margin:0;font-family:Helvetica,sans-serif;font-size:22px;line-height:1;font-weight:700;color:#9153e1;text-align:center;">100%</p><p style="margin:4px 0 0;font-family:Helvetica,sans-serif;font-size:12px;line-height:1;font-weight:400;color:#606060;text-align:center;">садхана пройдена</p></td></tr></table>',
					'cta_label' => 'Начать новую садхану',
					'cta_url' => '{{library_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'target_day_label' => array('type' => 'text', 'example' => 'дней'),
					'started_date' => array('type' => 'text', 'example' => '4 июня 2026'),
					'library_url' => array('type' => 'url', 'example' => home_url('/practice-type/kriyi/')),
				),
			),
			'subscription-expiring' => array(
				'label' => 'Подписка заканчивается через 3 дня',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Подписка скоро закончится',
					'preheader' => 'До окончания подписки осталось 3 дня',
					'heading' => '',
					'body' => '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto;border-collapse:separate;"><tr><td align="center" bgcolor="#e8ff57" style="padding:8px 14px;background-color:#e8ff57;border-radius:500px;font-family:Helvetica,sans-serif;font-size:14px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;text-transform:uppercase;white-space:nowrap;">Осталось 3 дня</td></tr></table><h1 style="margin:15px 0 0;font-family:Helvetica,sans-serif;font-size:22px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;">Подписка скоро закончится</h1><p style="margin:15px 0 0;line-height:1;font-weight:700;text-align:center;">Сат Нам, {{user_name}}!</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Доступ к подписке <strong style="font-weight:700;color:#9153e1;">«{{subscription_name}}»</strong> закончится <strong style="font-weight:700;color:#1f1f1f;">{{expiration_date}}.</strong> Автопродление отключено, поэтому доступ к практикам не сохранится автоматически.</p>',
					'cta_label' => 'Продлить подписку',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'subscription_name' => array('type' => 'text', 'example' => 'Аришечный Pro Max, 1 месяц'),
					'expiration_date' => array('type' => 'text', 'example' => '17 июля 2026'),
					'action_url' => array('type' => 'url', 'example' => home_url('/lk/?section=subscription')),
				),
			),
			'subscription-ended' => array(
				'label' => 'Подписка завершилась',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Подписка завершилась',
					'preheader' => 'Доступ завершён, но ваши данные и история сохранены',
					'heading' => 'Подписка завершилась',
					'body' => '<p style="margin:0;line-height:1;font-weight:700;text-align:center;">Сат Нам, {{user_name}}!</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Ваша подписка <strong style="font-weight:700;color:#9153e1;">«{{subscription_name}}»</strong> завершилась, потому что автопродление было отключено. Доступ был активен до <strong style="font-weight:700;color:#1f1f1f;">{{expiration_date}}.</strong></p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:15px 0 0;border-collapse:separate;background-color:#f8f3fd;border-radius:16px;"><tr><td bgcolor="#f8f3fd" style="padding:16px 20px;background-color:#f8f3fd;border-radius:16px;font-family:Helvetica,sans-serif;font-size:14px;line-height:1.5;font-weight:400;color:#9153e1;text-align:left;">Ваши садханы, избранное и история сохранены — они снова будут доступны сразу после возобновления.</td></tr></table>',
					'cta_label' => 'Возобновить подписку',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'subscription_name' => array('type' => 'text', 'example' => 'Аришечный Pro Max, 1 месяц'),
					'expiration_date' => array('type' => 'text', 'example' => '14 июля 2026'),
					'action_url' => array('type' => 'url', 'example' => home_url('/lk/?section=subscription')),
				),
			),
			'payment-card-expiring' => array(
				'label' => 'Скоро истечёт срок карты',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Скоро истечет срок карты',
					'preheader' => 'Обновите карту, чтобы следующая оплата прошла без перебоев',
					'heading' => 'Скоро истечет срок карты',
					'body' => '<p style="margin:0;line-height:1;font-weight:700;text-align:center;">Сат Нам, {{user_name}}!</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Срок действия карты, привязанной к подписке <strong style="font-weight:700;color:#9153e1;">«{{subscription_name}}»,</strong> скоро истекает. Обновите данные, чтобы оплата прошла без перебоев.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:15px 0 0;border-collapse:separate;background-color:#f6f6f9;border-radius:16px;"><tr><td bgcolor="#f6f6f9" style="padding:20px;background-color:#f6f6f9;border-radius:16px;"><table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;"><tr><td align="center" valign="middle" bgcolor="#1f1f1f" style="padding:13px 10px;background-color:#1f1f1f;border-radius:8px;font-family:Helvetica,sans-serif;font-size:14px;line-height:1;font-weight:700;color:#e8ff57;text-align:center;text-transform:uppercase;white-space:nowrap;">Карта</td><td valign="middle" style="padding-left:15px;"><p style="margin:0;font-family:Helvetica,sans-serif;font-size:14px;line-height:1;font-weight:700;color:#1f1f1f;text-align:left;white-space:nowrap;">{{payment_card}}</p><p style="margin:5px 0 0;font-family:Helvetica,sans-serif;font-size:12px;line-height:1;font-weight:400;color:#606060;text-align:left;white-space:nowrap;">Действует до {{card_expiry}}</p></td></tr></table></td></tr></table>',
					'cta_label' => 'Обновить карту',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'subscription_name' => array('type' => 'text', 'example' => 'Аришечный Pro Max, 1 месяц'),
					'payment_card' => array('type' => 'text', 'example' => '•• 4242'),
					'card_expiry' => array('type' => 'text', 'example' => '08/26'),
					'action_url' => array('type' => 'url', 'example' => home_url('/lk/?section=subscription')),
				),
			),
			'renewal-success' => array(
				'label' => 'Автопродление: успешная оплата',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Подписка автоматически продлена',
					'preheader' => 'Оплата прошла — доступ ко всем практикам сохраняется',
					'heading' => '',
					'body' => '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto;border-collapse:separate;"><tr><td align="center" bgcolor="#e8ff57" style="padding:8px 14px;background-color:#e8ff57;border-radius:500px;font-family:Helvetica,sans-serif;font-size:14px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;text-transform:uppercase;white-space:nowrap;">Оплата прошла</td></tr></table><h1 style="margin:15px 0 0;font-family:Helvetica,sans-serif;font-size:22px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;">Подписка автоматически продлена</h1><p style="margin:15px 0 0;line-height:1;font-weight:700;text-align:center;">Сат Нам, {{user_name}}!</p><p style="margin:15px 0 0;line-height:1.5;font-weight:400;text-align:center;">Мы автоматически продлили вашу подписку <strong style="font-weight:700;color:#9153e1;">«{{subscription_name}}».</strong> Доступ ко всем практикам сохраняется.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f6f6f9;border-radius:15px;"><tr><td bgcolor="#f6f6f9" style="padding:20px;background-color:#f6f6f9;border-radius:15px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td valign="middle" style="padding:0 10px 15px 0;font-size:14px;line-height:1;font-weight:400;color:#606060;text-align:left;">Списано</td><td valign="middle" align="right" style="padding:0 0 15px 10px;font-size:14px;line-height:1;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{total_amount}}</td></tr><tr><td colspan="2" height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr><tr><td valign="middle" style="padding:15px 10px;font-size:14px;line-height:1;font-weight:400;color:#606060;text-align:left;">Карта</td><td valign="middle" align="right" style="padding:15px 0 15px 10px;font-size:14px;line-height:1;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{payment_card}}</td></tr><tr><td colspan="2" height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr><tr><td valign="middle" style="padding:15px 10px 0 0;font-size:14px;line-height:1;font-weight:400;color:#606060;text-align:left;">Следующее списание</td><td valign="middle" align="right" style="padding:15px 0 0 10px;font-size:14px;line-height:1;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{next_charge_date}}</td></tr></table></td></tr></table>',
					'cta_label' => 'Посмотреть чек',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'subscription_name' => array('type' => 'text', 'example' => 'Аришечный Pro Max, 1 месяц'),
					'total_amount' => array('type' => 'text', 'example' => '4 990 ₽'),
					'payment_card' => array('type' => 'text', 'example' => '•• 4242'),
					'next_charge_date' => array('type' => 'text', 'example' => '14 августа 2026'),
					'action_url' => array('type' => 'url', 'example' => home_url('/my-account/view-order/10429/')),
				),
			),
			'renewal-failed' => array(
				'label' => 'Ошибка автопродления',
				'group' => 'Kundalini',
				'defaults' => array(
					'subject' => 'Не удалось списать оплату',
					'preheader' => 'Обновите способ оплаты, чтобы сохранить доступ к подписке',
					'heading' => 'Не удалось списать оплату',
					'body' => '<p style="margin:0;line-height:1.5;font-weight:400;text-align:center;">Мы не смогли списать оплату по подписке <strong style="font-weight:700;color:#9153e1;">«{{subscription_name}}».</strong> Доступ пока сохраняется — просто обновите оплату.</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f6f6f9;border-radius:15px;"><tr><td bgcolor="#f6f6f9" style="padding:20px;background-color:#f6f6f9;border-radius:15px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td valign="middle" style="padding:0 10px 15px 0;font-size:14px;line-height:1.5;font-weight:400;color:#606060;text-align:left;">Сумма</td><td valign="middle" align="right" style="padding:0 0 15px 10px;font-size:14px;line-height:1.5;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{total_amount}}</td></tr><tr><td colspan="2" height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr><tr><td valign="middle" style="padding:15px 10px;font-size:14px;line-height:1.5;font-weight:400;color:#606060;text-align:left;">Карта</td><td valign="middle" align="right" style="padding:15px 0 15px 10px;font-size:14px;line-height:1.5;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{payment_card}}</td></tr><tr><td colspan="2" height="1" bgcolor="#ffffff" style="height:1px;font-size:1px;line-height:1px;background-color:#ffffff;">&nbsp;</td></tr><tr><td valign="middle" style="padding:15px 10px 0 0;font-size:14px;line-height:1.5;font-weight:400;color:#606060;text-align:left;">Следующая попытка</td><td valign="middle" align="right" style="padding:15px 0 0 10px;font-size:14px;line-height:1.5;font-weight:700;color:#1f1f1f;text-align:right;white-space:nowrap;">{{next_attempt_date}}</td></tr></table></td></tr></table><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:15px 0 0;border-collapse:separate;background-color:#fceeee;border:1px solid #e15355;border-radius:15px;"><tr><td bgcolor="#fceeee" style="padding:20px;background-color:#fceeee;border-radius:15px;font-size:14px;line-height:1.5;font-weight:400;color:#e15355;text-align:left;">Частые причины: недостаточно средств, превышен лимит по карте или истёк её срок. Проверьте баланс или используйте другую карту.</td></tr></table>',
					'cta_label' => 'Повторить оплату',
					'cta_url' => '{{action_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'subscription_name' => array('type' => 'text', 'example' => 'Аришечный Pro Max, 1 месяц'),
					'total_amount' => array('type' => 'text', 'example' => '4 990 ₽'),
					'payment_card' => array('type' => 'text', 'example' => '•• 4242'),
					'next_attempt_date' => array('type' => 'text', 'example' => '16 июля 2026'),
					'action_url' => array('type' => 'url', 'example' => home_url('/lk/?section=subscription')),
				),
			),
			'admin-new-subscriber' => array('label' => 'Администратору: новый подписчик', 'group' => 'Административные', 'defaults' => $existing),
			'admin-contact-message' => array('label' => 'Администратору: сообщение формы', 'group' => 'Административные', 'defaults' => $existing),
			'admin-new-question' => array('label' => 'Администратору: новый вопрос', 'group' => 'Административные', 'defaults' => $existing),
			'woocommerce-low-stock' => array('label' => 'WooCommerce: мало товара', 'group' => 'WooCommerce', 'defaults' => $existing),
			'woocommerce-no-stock' => array('label' => 'WooCommerce: товар закончился', 'group' => 'WooCommerce', 'defaults' => $existing),
			'woocommerce-backorder' => array('label' => 'WooCommerce: предзаказ', 'group' => 'WooCommerce', 'defaults' => $existing),
		);
		foreach ($definitions as $id => &$definition) {
			$definition['tags'] = wp_parse_args((array) ($definition['tags'] ?? array()), $common);
			$definition = $this->normalize_definition($id, $definition);
		}
		unset($definition);
		return $definitions;
	}

	private function common_tags(): array {
		return array(
			'site_name' => array('type' => 'text', 'example' => 'Kundalini Class'),
			'site_url'  => array('type' => 'url', 'example' => home_url('/')),
			'subject'   => array('type' => 'text', 'example' => 'Тема тестового письма'),
			'content'   => array('type' => 'html', 'example' => '<p>Пример содержимого письма.</p>'),
			'user_name' => array('type' => 'text', 'example' => 'Анна'),
			'user_email' => array('type' => 'text', 'example' => 'anna@example.com'),
			'old_email' => array('type' => 'text', 'example' => 'marina@example.com'),
			'new_email' => array('type' => 'text', 'example' => 'marina.k@example.com'),
			'action_url' => array('type' => 'url', 'example' => home_url('/')),
			'code' => array('type' => 'text', 'example' => '4829'),
			'code_digit_1' => array('type' => 'text', 'example' => '4'),
			'code_digit_2' => array('type' => 'text', 'example' => '8'),
			'code_digit_3' => array('type' => 'text', 'example' => '2'),
			'code_digit_4' => array('type' => 'text', 'example' => '9'),
			'order_number' => array('type' => 'text', 'example' => '1001'),
			'order_url' => array('type' => 'url', 'example' => home_url('/my-account/')),
			'practice_title' => array('type' => 'text', 'example' => 'Утренняя крийя'),
			'milestone' => array('type' => 'text', 'example' => '21'),
			'target_days' => array('type' => 'text', 'example' => '40'),
			'completed_days' => array('type' => 'text', 'example' => '21'),
			'ttl_minutes' => array('type' => 'text', 'example' => '10'),
			'phone' => array('type' => 'text', 'example' => '+7 900 000-00-00'),
			'customer_name' => array('type' => 'text', 'example' => 'Анна'),
			'event_datetime' => array('type' => 'text', 'example' => '14 июля 2026, 21:30'),
		);
	}

	private function normalize_definition(string $id, array $definition): array {
		$defaults = array(
			'subject' => '{{subject}}', 'preheader' => '', 'heading' => '{{subject}}',
			'body' => '{{content}}', 'cta_label' => '', 'cta_url' => '', 'footer_note' => '',
		);
		$values = wp_parse_args((array) ($definition['defaults'] ?? array()), $defaults);
		$designed = array_key_exists('designed', $definition)
			? (bool) $definition['designed']
			: trim((string) $values['body']) !== '{{content}}';
		return array(
			'id'       => $id,
			'label'    => (string) ($definition['label'] ?? $id),
			'group'    => (string) ($definition['group'] ?? 'Прочие'),
			'designed' => $designed,
			'defaults' => $values,
			'tags'     => wp_parse_args((array) ($definition['tags'] ?? array()), $this->common_tags()),
		);
	}
}
