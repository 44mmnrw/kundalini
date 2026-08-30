<?php

if (!defined('ABSPATH')) {
	exit;
}

final class Yoga_Mail_Admin {
	private $registry;
	private $renderer;
	private $mailer;
	private $notice = '';
	private $notice_type = 'success';

	public function __construct(Yoga_Mail_Registry $registry, Yoga_Mail_Renderer $renderer, Yoga_Mail_Mailer $mailer) {
		$this->registry = $registry;
		$this->renderer = $renderer;
		$this->mailer = $mailer;
	}

	public function init(): void {
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('admin_init', array($this, 'handle_request'));
	}

	public function admin_menu(): void {
		add_menu_page(
			__('Yoga Mail', 'yoga-mail'),
			__('Yoga Mail', 'yoga-mail'),
			'manage_options',
			'yoga-mail',
			array($this, 'render_page'),
			'dashicons-email-alt2',
			58
		);
	}

	public function handle_request(): void {
		if (!is_admin() || !current_user_can('manage_options') || ($_GET['page'] ?? '') !== 'yoga-mail' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
			return;
		}
		check_admin_referer('yoga_mail_save');
		if (class_exists('WooCommerce') && function_exists('WC')) {
			WC()->mailer();
		}
		$template_id = sanitize_key((string) ($_POST['template_id'] ?? 'generic'));
		$action = sanitize_key((string) ($_POST['yoga_mail_action'] ?? 'save'));

		if ($action === 'reset') {
			$this->registry->reset_values($template_id);
			$this->notice = __('Шаблон восстановлен по умолчанию.', 'yoga-mail');
			return;
		}

		if ($action === 'test') {
			$recipient = sanitize_email((string) ($_POST['test_recipient'] ?? ''));
			if (!is_email($recipient)) {
				$this->notice = __('Укажите корректный адрес для теста.', 'yoga-mail');
				$this->notice_type = 'error';
				return;
			}
			$sent = $this->mailer->send($template_id, array(
				'to' => $recipient,
				'data' => $this->registry->examples($template_id),
				'bypass_flags' => true,
			));
			$this->notice = $sent ? __('Тестовое письмо отправлено.', 'yoga-mail') : __('Не удалось отправить тестовое письмо. Проверьте почтовый транспорт и журнал ошибок.', 'yoga-mail');
			$this->notice_type = $sent ? 'success' : 'error';
			return;
		}

		$logo_url = esc_url_raw((string) ($_POST['logo_url'] ?? ''));
		$logo_path = (string) wp_parse_url($logo_url, PHP_URL_PATH);
		if ($logo_url === '' || strtolower((string) wp_parse_url($logo_url, PHP_URL_SCHEME)) !== 'https' || !preg_match('/\.(png|svg)$/i', $logo_path)) {
			$this->notice = __('Логотип должен иметь абсолютный HTTPS-URL и формат PNG или SVG.', 'yoga-mail');
			$this->notice_type = 'error';
			return;
		}
		$logo_alt = sanitize_text_field((string) ($_POST['logo_alt'] ?? ''));
		if ($logo_alt === '') {
			$this->notice = __('Для логотипа обязателен alt-текст.', 'yoga-mail');
			$this->notice_type = 'error';
			return;
		}

		$settings = $this->registry->settings();
		$settings['custom_enabled'] = !empty($_POST['custom_enabled']);
		$settings['wordpress_enabled'] = !empty($_POST['wordpress_enabled']);
		$settings['woocommerce_enabled'] = !empty($_POST['woocommerce_enabled']);
		$settings['fallback_enabled'] = !empty($_POST['fallback_enabled']);
		$settings['logo_url'] = $logo_url;
		$settings['logo_alt'] = $logo_alt;
		$settings['footer_text'] = sanitize_text_field((string) ($_POST['footer_text'] ?? ''));
		update_option(Yoga_Mail_Registry::SETTINGS_OPTION, $settings, false);
		$template = isset($_POST['template']) && is_array($_POST['template']) ? wp_unslash($_POST['template']) : array();
		$saved = $this->registry->save_values($template_id, $template);
		$this->notice = $saved ? __('Настройки сохранены.', 'yoga-mail') : __('Настройки не сохранены: найден неизвестный merge-тег или тег использован не в том поле.', 'yoga-mail');
		$this->notice_type = $saved ? 'success' : 'error';
	}

	public function render_page(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Недостаточно прав.', 'yoga-mail'));
		}
		if (class_exists('WooCommerce') && function_exists('WC')) {
			WC()->mailer();
		}
		$templates = $this->registry->all();
		$selected = sanitize_key((string) ($_GET['template'] ?? ($_POST['template_id'] ?? 'generic')));
		if (!isset($templates[$selected])) {
			$selected = 'generic';
		}
		$definition = $templates[$selected];
		$values = $this->registry->values($selected);
		$settings = $this->registry->settings();
		$preview = $this->renderer->render($selected, $this->registry->examples($selected), true);
		$current_user = wp_get_current_user();
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Шаблоны писем Yoga Mail', 'yoga-mail'); ?></h1>
			<?php if ($this->notice !== '') : ?><div class="notice notice-<?php echo esc_attr($this->notice_type); ?> is-dismissible"><p><?php echo esc_html($this->notice); ?></p></div><?php endif; ?>
			<p><?php esc_html_e('Layout защищён кодом. Здесь редактируются только контент, тема, CTA и параметры включения.', 'yoga-mail'); ?></p>
			<form method="get" style="margin:16px 0;">
				<input type="hidden" name="page" value="yoga-mail">
				<label for="yoga-template"><strong><?php esc_html_e('Шаблон:', 'yoga-mail'); ?></strong></label>
				<select id="yoga-template" name="template" onchange="this.form.submit()">
					<?php $last_group = ''; foreach ($templates as $id => $item) : ?>
						<?php if ($last_group !== $item['group']) : if ($last_group !== '') echo '</optgroup>'; $last_group = $item['group']; ?><optgroup label="<?php echo esc_attr($last_group); ?>"><?php endif; ?>
						<option value="<?php echo esc_attr($id); ?>" <?php selected($selected, $id); ?>><?php echo esc_html($item['label']); ?></option>
					<?php endforeach; if ($last_group !== '') echo '</optgroup>'; ?>
				</select>
				<noscript><?php submit_button(__('Открыть', 'yoga-mail'), 'secondary', '', false); ?></noscript>
			</form>

			<form method="post">
				<?php wp_nonce_field('yoga_mail_save'); ?>
				<input type="hidden" name="template_id" value="<?php echo esc_attr($selected); ?>">
				<h2><?php esc_html_e('Поэтапное включение', 'yoga-mail'); ?></h2>
				<table class="form-table" role="presentation"><tbody>
				<?php foreach (array('custom_enabled' => 'Кастомные письма', 'wordpress_enabled' => 'Системные письма WordPress', 'woocommerce_enabled' => 'WooCommerce', 'fallback_enabled' => 'Глобальный fallback') as $key => $label) : ?>
				<tr><th><?php echo esc_html($label); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked(!empty($settings[$key])); ?>> <?php esc_html_e('Включено', 'yoga-mail'); ?></label></td></tr>
				<?php endforeach; ?>
				</tbody></table>

				<h2><?php esc_html_e('Бренд', 'yoga-mail'); ?></h2>
				<table class="form-table" role="presentation"><tbody>
				<tr><th><label for="logo_url">Logo URL</label></th><td><input class="regular-text" type="url" id="logo_url" name="logo_url" value="<?php echo esc_attr($settings['logo_url']); ?>" required></td></tr>
				<tr><th><label for="logo_alt">Logo alt</label></th><td><input class="regular-text" type="text" id="logo_alt" name="logo_alt" value="<?php echo esc_attr($settings['logo_alt']); ?>" required></td></tr>
				<tr><th><label for="footer_text"><?php esc_html_e('Служебный текст футера', 'yoga-mail'); ?></label></th><td><input class="regular-text" type="text" id="footer_text" name="footer_text" value="<?php echo esc_attr($settings['footer_text']); ?>"></td></tr>
				</tbody></table>

				<h2><?php echo esc_html($definition['label']); ?></h2>
				<p><strong><?php esc_html_e('Merge-теги:', 'yoga-mail'); ?></strong> <?php echo esc_html(implode(', ', array_map(static function ($tag) { return '{{' . $tag . '}}'; }, array_keys($definition['tags'])))); ?></p>
				<table class="form-table" role="presentation"><tbody>
				<?php foreach (array('subject' => 'Тема', 'preheader' => 'Прехедер', 'heading' => 'Заголовок', 'cta_label' => 'Подпись CTA', 'cta_url' => 'URL CTA', 'footer_note' => 'Примечание под CTA') as $field => $label) : ?>
				<tr><th><label for="field-<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?></label></th><td><input class="large-text" type="text" id="field-<?php echo esc_attr($field); ?>" name="template[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($values[$field]); ?>"></td></tr>
				<?php endforeach; ?>
				<tr><th><?php esc_html_e('Содержимое', 'yoga-mail'); ?></th><td><?php wp_editor($values['body'], 'yoga_mail_body', array('textarea_name' => 'template[body]', 'media_buttons' => false, 'textarea_rows' => 10, 'teeny' => true)); ?></td></tr>
				</tbody></table>
				<p class="submit">
					<button class="button button-primary" name="yoga_mail_action" value="save"><?php esc_html_e('Сохранить', 'yoga-mail'); ?></button>
					<button class="button" name="yoga_mail_action" value="reset" onclick="return confirm('<?php echo esc_js(__('Восстановить стандартный текст этого шаблона?', 'yoga-mail')); ?>')"><?php esc_html_e('Восстановить', 'yoga-mail'); ?></button>
				</p>
				<h2><?php esc_html_e('Тестовая отправка', 'yoga-mail'); ?></h2>
				<input type="email" class="regular-text" name="test_recipient" value="<?php echo esc_attr($current_user->user_email); ?>">
				<button class="button" name="yoga_mail_action" value="test"><?php esc_html_e('Отправить тест', 'yoga-mail'); ?></button>
			</form>

			<h2><?php esc_html_e('Предпросмотр', 'yoga-mail'); ?></h2>
			<?php if (is_wp_error($preview)) : ?><div class="notice notice-error"><p><?php echo esc_html($preview->get_error_message()); ?></p></div><?php else : ?>
				<p><strong><?php esc_html_e('HTML', 'yoga-mail'); ?></strong></p>
				<iframe title="Email preview" srcdoc="<?php echo esc_attr($preview['html']); ?>" style="width:100%;max-width:760px;height:720px;background:#fff;border:1px solid #ccd0d4;"></iframe>
				<p><strong><?php esc_html_e('text/plain', 'yoga-mail'); ?></strong></p><pre style="max-width:760px;padding:16px;background:#fff;border:1px solid #ccd0d4;white-space:pre-wrap;"><?php echo esc_html($preview['text']); ?></pre>
			<?php endif; ?>
		</div>
		<?php
	}
}
