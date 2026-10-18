<?php

if (!defined('ABSPATH')) {
	exit;
}

/** Routing for saved incoming questions; never affects customer emails. */
final class Yoga_Mail_Events {
	public const OPTION = 'yoga_mail_events';
	private $notice = '';
	private $error = false;

	public static function definitions(): array {
		return array(
			'lk' => array('label' => 'Новый вопрос из личного кабинета', 'template' => 'admin-new-question'),
			'faq' => array('label' => 'Новый вопрос из FAQ', 'template' => 'admin-faq-question'),
			'practice' => array('label' => 'Новый вопрос по практике / крийе', 'template' => 'admin-practice-question'),
			'contacts' => array('label' => 'Новое сообщение из формы контактов', 'template' => 'admin-contact-message'),
		);
	}

	public static function templates(): array {
		return array_values(Yoga_Mail_Admin_Request::TEMPLATES);
	}

	/** Reject the complete input if any address is invalid, including header injection. */
	public static function parse_recipients(string $input) {
		$addresses = preg_split('/[\s,;]+/u', trim($input), -1, PREG_SPLIT_NO_EMPTY);
		$unique = array();
		foreach ($addresses as $address) {
			if (!is_email($address)) {
				return new WP_Error('invalid_recipient', 'Укажите корректные email. Разделяйте адреса запятыми или переносами строк.');
			}
			$unique[strtolower($address)] = $address;
		}
		return array_values($unique);
	}

	public static function settings(): array {
		$stored = get_option(self::OPTION, array());
		$stored = is_array($stored) ? $stored : array();
		$result = array();
		foreach (self::definitions() as $event => $definition) {
			// Preserve the current destinations until an administrator saves new rules.
			$legacy = $event === 'practice' ? (string) get_option('yoga_practice_questions_notification_email', '') : '';
			if ($event === 'contacts') {
				$legacy = 'sshell72@yandex.ru';
			}
			$rule = isset($stored[$event]) && is_array($stored[$event]) ? $stored[$event] : array();
			$result[$event] = wp_parse_args($rule, array('enabled' => true, 'recipients' => $legacy, 'template' => $definition['template']));
			$old_template = array('faq' => 'admin-new-question', 'practice' => 'admin-contact-message');
			if (empty($rule['request_design_version']) && isset($old_template[$event]) && $result[$event]['template'] === $old_template[$event]) {
				$result[$event]['template'] = $definition['template'];
			}
			if (!in_array($result[$event]['template'], self::templates(), true)) {
				$result[$event]['template'] = $definition['template'];
			}
		}
		return $result;
	}

	public static function validate(array $input) {
		$clean = array();
		foreach (self::definitions() as $event => $definition) {
			$rule = isset($input[$event]) && is_array($input[$event]) ? $input[$event] : array();
			$recipients = self::parse_recipients((string) ($rule['recipients'] ?? ''));
			if (is_wp_error($recipients)) {
				return $recipients;
			}
			$template = (string) ($rule['template'] ?? $definition['template']);
			if (!in_array($template, self::templates(), true)) {
				return new WP_Error('invalid_template', 'Выберите шаблон уведомления администратору.');
			}
			$clean[$event] = array('enabled' => !empty($rule['enabled']), 'recipients' => implode("\n", $recipients), 'template' => $template, 'request_design_version' => Yoga_Mail_Admin_Request::DESIGN_VERSION);
		}
		return $clean;
	}

	public static function send(string $event, int $question_id, array $args): bool {
		$rules = self::settings();
		$question = get_post($question_id);
		if (!isset($rules[$event]) || !$question instanceof WP_Post || $question->post_type !== 'question') {
			return false;
		}
		$rule = $rules[$event];
		if (empty($rule['enabled'])) {
			return true; // Intentionally skipped; saving the question and autoreply are independent.
		}
		$recipients = self::parse_recipients((string) $rule['recipients']);
		if (is_wp_error($recipients)) {
			return false;
		}
		if (!$recipients) {
			$recipients = self::parse_recipients((string) get_option('admin_email'));
		}
		if (is_wp_error($recipients) || !$recipients) {
			return false;
		}
		$args['data'] = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();
		$args['data'] = array_merge($args['data'], Yoga_Mail_Admin_Request::data($event, $question, $args['data']));
		// Only these fields may be supplied by an integration: no Cc/Bcc or recipient overrides.
		$mail = array_intersect_key($args, array_flip(array('subject', 'content', 'data')));
		$success = true;
		foreach ($recipients as $recipient) {
			$mail['to'] = $recipient;
			$sent = yoga_mail_send((string) $rule['template'], $mail);
			$success = $sent && $success; // Attempt every recipient even if an earlier send failed.
		}
		return $success;
	}

	public function init(): void {
		add_action('admin_menu', array($this, 'menu'));
		add_action('admin_init', array($this, 'save'));
	}

	public function menu(): void {
		add_submenu_page('yoga-mail', 'Сценарии писем', 'Сценарии и получатели', 'manage_options', 'yoga-mail-events', array($this, 'render'));
	}

	public function save(): void {
		if (!is_admin() || !current_user_can('manage_options') || ($_GET['page'] ?? '') !== 'yoga-mail-events' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
			return;
		}
		check_admin_referer('yoga_mail_events');
		$input = isset($_POST['events']) && is_array($_POST['events']) ? wp_unslash($_POST['events']) : array();
		$clean = self::validate($input);
		$this->error = is_wp_error($clean);
		if ($this->error) {
			$this->notice = $clean->get_error_message() . ' Изменения не сохранены.';
			return;
		}
		update_option(self::OPTION, $clean, false);
		$this->notice = 'Сценарии сохранены.';
	}

	public function render(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Недостаточно прав.', 'yoga-mail'));
		}
		$rules = self::settings();
		$templates = yoga_mail_templates();
		?>
		<div class="wrap">
			<h1>Сценарии и получатели писем</h1>
			<?php if ($this->notice !== '') : ?><div class="notice notice-<?php echo $this->error ? 'error' : 'success'; ?>"><p><?php echo esc_html($this->notice); ?></p></div><?php endif; ?>
			<p>Для каждого события укажите получателей и шаблон письма. Каждый получатель получит отдельное письмо с кнопкой «Ответить из админки».</p>
			<p>Адреса можно разделять запятыми, точками с запятой или переносами строк. Повторы удаляются. Пустой список означает отправку на основной email сайта: <strong><?php echo esc_html((string) get_option('admin_email')); ?></strong>.</p>
			<p>Отключение сценария отключает только уведомление сотрудникам. Вопрос сохраняется в админке, автоответ пользователю продолжает работать. Для ответа по кнопке требуется вход в WordPress и доступ к вопросам.</p>
			<form method="post">
				<?php wp_nonce_field('yoga_mail_events'); ?>
				<?php foreach (self::definitions() as $event => $definition) : $rule = $rules[$event]; ?>
				<h2><?php echo esc_html($definition['label']); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row">Уведомление</th><td><label><input type="checkbox" name="events[<?php echo esc_attr($event); ?>][enabled]" value="1" <?php checked(!empty($rule['enabled'])); ?>> Отправлять сотрудникам</label></td></tr>
					<tr><th scope="row"><label for="recipients-<?php echo esc_attr($event); ?>">Получатели</label></th><td><textarea class="large-text" style="max-width:600px" rows="3" id="recipients-<?php echo esc_attr($event); ?>" name="events[<?php echo esc_attr($event); ?>][recipients]" placeholder="support@example.com&#10;teacher@example.com"><?php echo esc_textarea($rule['recipients']); ?></textarea></td></tr>
					<tr><th scope="row"><label for="template-<?php echo esc_attr($event); ?>">Шаблон</label></th><td><select id="template-<?php echo esc_attr($event); ?>" name="events[<?php echo esc_attr($event); ?>][template]">
					<?php foreach (self::templates() as $id) : ?><option value="<?php echo esc_attr($id); ?>" <?php selected($rule['template'], $id); ?>><?php echo esc_html($templates[$id]['label']); ?></option><?php endforeach; ?>
					</select></td></tr>
				</table>
				<?php endforeach; ?>
				<?php submit_button('Сохранить сценарии'); ?>
			</form>
		</div>
		<?php
	}
}
