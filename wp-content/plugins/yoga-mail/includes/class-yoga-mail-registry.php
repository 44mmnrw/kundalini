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
			),
			'wp-password-changed' => array('label' => 'WordPress: пароль изменён', 'group' => 'WordPress', 'defaults' => $existing),
			'wp-email-changed' => array('label' => 'WordPress: email изменён', 'group' => 'WordPress', 'defaults' => $existing),
			'wp-admin-email-changed' => array('label' => 'WordPress: email администратора изменён', 'group' => 'WordPress', 'defaults' => $existing),
			'wp-comment-notification' => array('label' => 'WordPress: новый комментарий', 'group' => 'WordPress', 'defaults' => $existing),
			'wp-comment-moderation' => array('label' => 'WordPress: модерация комментария', 'group' => 'WordPress', 'defaults' => $existing),
			'wp-recovery-mode' => array('label' => 'WordPress: режим восстановления', 'group' => 'WordPress', 'defaults' => $existing),
			'email-verification' => array('label' => 'Подтверждение email', 'group' => 'Kundalini', 'defaults' => $existing),
			'question-answer' => array('label' => 'Ответ на вопрос', 'group' => 'Kundalini', 'defaults' => $existing),
			'comment-reply' => array('label' => 'Ответ на комментарий', 'group' => 'Kundalini', 'defaults' => $existing),
			'sadhana-progress' => array('label' => 'Садхана: прогресс', 'group' => 'Kundalini', 'defaults' => $existing),
			'sadhana-interrupted' => array('label' => 'Садхана: прерывание', 'group' => 'Kundalini', 'defaults' => $existing),
			'sadhana-completed' => array('label' => 'Садхана: завершение', 'group' => 'Kundalini', 'defaults' => $existing),
			'subscription-expiring' => array('label' => 'Подписка заканчивается', 'group' => 'Kundalini', 'defaults' => $existing),
			'payment-card-expiring' => array('label' => 'Срок карты заканчивается', 'group' => 'Kundalini', 'defaults' => $existing),
			'renewal-failed' => array('label' => 'Ошибка автопродления', 'group' => 'Kundalini', 'defaults' => $existing),
			'admin-new-subscriber' => array('label' => 'Администратору: новый подписчик', 'group' => 'Административные', 'defaults' => $existing),
			'admin-contact-message' => array('label' => 'Администратору: сообщение формы', 'group' => 'Административные', 'defaults' => $existing),
			'admin-new-question' => array('label' => 'Администратору: новый вопрос', 'group' => 'Административные', 'defaults' => $existing),
			'woocommerce-low-stock' => array('label' => 'WooCommerce: мало товара', 'group' => 'WooCommerce', 'defaults' => $existing),
			'woocommerce-no-stock' => array('label' => 'WooCommerce: товар закончился', 'group' => 'WooCommerce', 'defaults' => $existing),
			'woocommerce-backorder' => array('label' => 'WooCommerce: предзаказ', 'group' => 'WooCommerce', 'defaults' => $existing),
		);
		foreach ($definitions as $id => &$definition) {
			$definition['tags'] = $common;
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
			'action_url' => array('type' => 'url', 'example' => home_url('/')),
			'code' => array('type' => 'text', 'example' => '123456'),
			'order_number' => array('type' => 'text', 'example' => '1001'),
			'order_url' => array('type' => 'url', 'example' => home_url('/my-account/')),
			'practice_title' => array('type' => 'text', 'example' => 'Утренняя крийя'),
			'milestone' => array('type' => 'text', 'example' => '21'),
			'target_days' => array('type' => 'text', 'example' => '40'),
			'completed_days' => array('type' => 'text', 'example' => '21'),
			'ttl_minutes' => array('type' => 'text', 'example' => '10'),
			'phone' => array('type' => 'text', 'example' => '+7 900 000-00-00'),
			'customer_name' => array('type' => 'text', 'example' => 'Анна'),
		);
	}

	private function normalize_definition(string $id, array $definition): array {
		$defaults = array(
			'subject' => '{{subject}}', 'preheader' => '', 'heading' => '{{subject}}',
			'body' => '{{content}}', 'cta_label' => '', 'cta_url' => '', 'footer_note' => '',
		);
		return array(
			'id'       => $id,
			'label'    => (string) ($definition['label'] ?? $id),
			'group'    => (string) ($definition['group'] ?? 'Прочие'),
			'defaults' => wp_parse_args((array) ($definition['defaults'] ?? array()), $defaults),
			'tags'     => wp_parse_args((array) ($definition['tags'] ?? array()), $this->common_tags()),
		);
	}
}
