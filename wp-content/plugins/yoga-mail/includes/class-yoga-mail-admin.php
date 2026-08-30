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
		$status_counts = array('all' => count($templates), 'ready' => 0, 'basic' => 0);
		$grouped_templates = array();
		foreach ($templates as $item) {
			$status_counts[!empty($item['designed']) ? 'ready' : 'basic']++;
			$grouped_templates[$item['group']][$item['id']] = $item;
		}
		$selected = sanitize_key((string) ($_GET['template'] ?? ($_POST['template_id'] ?? '')));
		if (!isset($templates[$selected])) {
			$ready_templates = array_filter($templates, static function (array $item): bool {
				return !empty($item['designed']);
			});
			$selected = (string) array_key_first($ready_templates ?: $templates);
		}
		$definition = $templates[$selected];
		$values = $this->registry->values($selected);
		$settings = $this->registry->settings();
		$preview = $this->renderer->render($selected, $this->registry->examples($selected), true);
		$current_user = wp_get_current_user();
		?>
		<div class="wrap">
			<style>
				.yoga-mail-shell{max-width:1180px}.yoga-mail-card{box-sizing:border-box;margin:16px 0;padding:20px;background:#fff;border:1px solid #dcdcde;border-radius:8px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
				.yoga-mail-toolbar{display:flex;align-items:center;gap:16px;flex-wrap:wrap}.yoga-mail-toolbar__select{flex:1;min-width:320px;max-width:680px}.yoga-mail-toolbar select{width:100%;max-width:none}
				.yoga-mail-summary{margin:16px 0;background:#fff;border:1px solid #dcdcde;border-radius:8px}.yoga-mail-summary>summary{padding:15px 20px;font-size:14px;font-weight:600;cursor:pointer}.yoga-mail-summary[open]>summary{border-bottom:1px solid #dcdcde}
				.yoga-mail-settings-grid,.yoga-mail-fields,.yoga-mail-preview-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.yoga-mail-settings-grid{padding:20px}.yoga-mail-settings-block{padding:16px;background:#f6f7f7;border-radius:6px}.yoga-mail-settings-block h3{margin:0 0 14px}.yoga-mail-switches{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.yoga-mail-switch{padding:9px 10px;background:#fff;border:1px solid #dcdcde;border-radius:5px}
				.yoga-mail-field{min-width:0}.yoga-mail-field--wide{grid-column:1/-1}.yoga-mail-field label{display:block;margin:0 0 6px;font-weight:600}.yoga-mail-field input{box-sizing:border-box;width:100%;max-width:none}.yoga-mail-field .description{margin:6px 0 0}.yoga-mail-editor-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px}.yoga-mail-editor-heading h2{margin:0}.yoga-mail-tags{margin:16px 0 0}.yoga-mail-tags summary{cursor:pointer;color:#2271b1}.yoga-mail-tags code{display:block;margin-top:8px;padding:10px;white-space:normal;line-height:1.7}
				.yoga-mail-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:18px;padding-top:16px;border-top:1px solid #dcdcde}.yoga-mail-actions__test{display:flex;align-items:center;gap:8px;margin-left:auto}.yoga-mail-actions__test input{width:260px}.yoga-mail-preview-grid iframe{box-sizing:border-box;width:100%;height:720px;background:#fff;border:1px solid #ccd0d4}.yoga-mail-preview-grid pre{box-sizing:border-box;height:720px;margin:0;padding:16px;overflow:auto;background:#fff;border:1px solid #ccd0d4;white-space:pre-wrap}
				@media(max-width:900px){.yoga-mail-settings-grid,.yoga-mail-fields,.yoga-mail-preview-grid{grid-template-columns:1fr}.yoga-mail-field--wide{grid-column:auto}.yoga-mail-switches{grid-template-columns:1fr}.yoga-mail-actions__test{width:100%;margin-left:0}.yoga-mail-actions__test input{flex:1;width:auto}}
			</style>
			<div class="yoga-mail-shell">
			<h1><?php esc_html_e('Шаблоны писем Yoga Mail', 'yoga-mail'); ?></h1>
			<?php if ($this->notice !== '') : ?><div class="notice notice-<?php echo esc_attr($this->notice_type); ?> is-dismissible"><p><?php echo esc_html($this->notice); ?></p></div><?php endif; ?>
			<p><?php esc_html_e('Layout защищён кодом. Здесь редактируются только контент, тема, CTA и параметры включения.', 'yoga-mail'); ?></p>
			<p><strong><?php echo esc_html(sprintf(__('Готово %1$d из %2$d шаблонов.', 'yoga-mail'), $status_counts['ready'], $status_counts['all'])); ?></strong> <?php esc_html_e('В списке: ✓ — готовый шаблон, ○ — базовый шаблон без отдельной вёрстки.', 'yoga-mail'); ?></p>
			<form method="get" class="yoga-mail-card yoga-mail-toolbar">
				<input type="hidden" name="page" value="yoga-mail">
				<label for="yoga-template"><strong><?php esc_html_e('Шаблон письма', 'yoga-mail'); ?></strong></label>
				<div class="yoga-mail-toolbar__select"><select id="yoga-template" name="template" onchange="this.form.submit()">
					<?php foreach ($grouped_templates as $group => $items) : ?>
						<optgroup label="<?php echo esc_attr($group); ?>">
							<?php foreach ($items as $id => $item) : ?>
								<option value="<?php echo esc_attr($id); ?>" <?php selected($selected, $id); ?>><?php echo esc_html(($item['designed'] ? '✓ ' : '○ ') . $item['label']); ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
				</select></div>
				<noscript><?php submit_button(__('Открыть', 'yoga-mail'), 'secondary', '', false); ?></noscript>
			</form>

			<form method="post">
				<?php wp_nonce_field('yoga_mail_save'); ?>
				<input type="hidden" name="template_id" value="<?php echo esc_attr($selected); ?>">
				<details class="yoga-mail-summary">
					<summary><?php esc_html_e('Общие настройки Yoga Mail', 'yoga-mail'); ?></summary>
					<div class="yoga-mail-settings-grid">
						<section class="yoga-mail-settings-block"><h3><?php esc_html_e('Включение писем', 'yoga-mail'); ?></h3><div class="yoga-mail-switches">
						<?php foreach (array('custom_enabled' => 'Кастомные письма', 'wordpress_enabled' => 'WordPress', 'woocommerce_enabled' => 'WooCommerce', 'fallback_enabled' => 'Глобальный fallback') as $key => $label) : ?>
							<label class="yoga-mail-switch"><input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked(!empty($settings[$key])); ?>> <?php echo esc_html($label); ?></label>
						<?php endforeach; ?>
						</div></section>
						<section class="yoga-mail-settings-block"><h3><?php esc_html_e('Бренд', 'yoga-mail'); ?></h3><div class="yoga-mail-fields">
							<div class="yoga-mail-field yoga-mail-field--wide"><label for="logo_url">Logo URL</label><input type="url" id="logo_url" name="logo_url" value="<?php echo esc_attr($settings['logo_url']); ?>" required></div>
							<div class="yoga-mail-field"><label for="logo_alt">Logo alt</label><input type="text" id="logo_alt" name="logo_alt" value="<?php echo esc_attr($settings['logo_alt']); ?>" required></div>
							<div class="yoga-mail-field"><label for="footer_text"><?php esc_html_e('Текст футера', 'yoga-mail'); ?></label><input type="text" id="footer_text" name="footer_text" value="<?php echo esc_attr($settings['footer_text']); ?>"></div>
						</div></section>
					</div>
				</details>

				<section class="yoga-mail-card">
					<div class="yoga-mail-editor-heading"><h2><?php echo esc_html($definition['label']); ?></h2><code><?php echo esc_html($selected); ?></code></div>
					<div class="yoga-mail-fields">
					<?php foreach (array('subject' => 'Тема письма', 'preheader' => 'Прехедер', 'heading' => 'Заголовок', 'footer_note' => 'Примечание под CTA', 'cta_label' => 'Текст кнопки', 'cta_url' => 'URL кнопки') as $field => $label) : ?>
						<div class="yoga-mail-field"><label for="field-<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?></label><input type="text" id="field-<?php echo esc_attr($field); ?>" name="template[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($values[$field]); ?>"></div>
					<?php endforeach; ?>
						<div class="yoga-mail-field yoga-mail-field--wide"><label for="yoga_mail_body"><?php esc_html_e('Содержимое письма', 'yoga-mail'); ?></label><?php wp_editor($values['body'], 'yoga_mail_body', array('textarea_name' => 'template[body]', 'media_buttons' => false, 'textarea_rows' => 10, 'teeny' => true)); ?></div>
					</div>
					<details class="yoga-mail-tags"><summary><?php esc_html_e('Доступные merge-теги', 'yoga-mail'); ?></summary><code><?php echo esc_html(implode(', ', array_map(static function ($tag) { return '{{' . $tag . '}}'; }, array_keys($definition['tags'])))); ?></code></details>
					<div class="yoga-mail-actions">
					<button class="button button-primary" name="yoga_mail_action" value="save"><?php esc_html_e('Сохранить', 'yoga-mail'); ?></button>
					<button class="button" name="yoga_mail_action" value="reset" onclick="return confirm('<?php echo esc_js(__('Восстановить стандартный текст этого шаблона?', 'yoga-mail')); ?>')"><?php esc_html_e('Восстановить', 'yoga-mail'); ?></button>
						<div class="yoga-mail-actions__test"><label class="screen-reader-text" for="test_recipient"><?php esc_html_e('Адрес тестовой отправки', 'yoga-mail'); ?></label><input id="test_recipient" type="email" name="test_recipient" value="<?php echo esc_attr($current_user->user_email); ?>"><button class="button" name="yoga_mail_action" value="test"><?php esc_html_e('Отправить тест', 'yoga-mail'); ?></button></div>
					</div>
				</section>
			</form>

			<h2><?php esc_html_e('Предпросмотр', 'yoga-mail'); ?></h2>
			<?php if (is_wp_error($preview)) : ?><div class="notice notice-error"><p><?php echo esc_html($preview->get_error_message()); ?></p></div><?php else : ?>
				<div class="yoga-mail-preview-grid"><section><h3>HTML</h3><iframe title="Email preview" srcdoc="<?php echo esc_attr($preview['html']); ?>"></iframe></section><section><h3>text/plain</h3><pre><?php echo esc_html($preview['text']); ?></pre></section></div>
			<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
