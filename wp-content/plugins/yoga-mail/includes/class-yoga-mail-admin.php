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
		$template_status = sanitize_key((string) ($_GET['template_status'] ?? ($_POST['template_status'] ?? 'ready')));
		if (!in_array($template_status, array('all', 'ready', 'basic'), true)) {
			$template_status = 'ready';
		}
		$status_counts = array('all' => count($templates), 'ready' => 0, 'basic' => 0);
		foreach ($templates as $item) {
			$status_counts[!empty($item['designed']) ? 'ready' : 'basic']++;
		}
		$visible_templates = array_filter($templates, static function (array $item) use ($template_status): bool {
			if ($template_status === 'all') {
				return true;
			}
			return !empty($item['designed']) === ($template_status === 'ready');
		});
		$selected = sanitize_key((string) ($_GET['template'] ?? ($_POST['template_id'] ?? '')));
		if (!isset($templates[$selected])) {
			$selected = (string) array_key_first($visible_templates ?: $templates);
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
			<p><strong><?php echo esc_html(sprintf(__('Готово %1$d из %2$d шаблонов.', 'yoga-mail'), $status_counts['ready'], $status_counts['all'])); ?></strong> <?php esc_html_e('Базовые письма используют общий брендированный layout и требуют отдельной вёрстки.', 'yoga-mail'); ?></p>
			<ul class="subsubsub" style="float:none;margin:12px 0 16px;">
				<?php foreach (array('all' => __('Все', 'yoga-mail'), 'ready' => __('Готовые', 'yoga-mail'), 'basic' => __('Требуют вёрстки', 'yoga-mail')) as $status_key => $status_label) : ?>
					<li><a href="<?php echo esc_url(add_query_arg(array('page' => 'yoga-mail', 'template_status' => $status_key), admin_url('admin.php'))); ?>" class="<?php echo $template_status === $status_key ? 'current' : ''; ?>"><?php echo esc_html($status_label); ?> <span class="count">(<?php echo esc_html((string) $status_counts[$status_key]); ?>)</span></a><?php echo $status_key !== 'basic' ? ' | ' : ''; ?></li>
				<?php endforeach; ?>
			</ul>
			<table class="wp-list-table widefat fixed striped" style="max-width:1100px;margin:0 0 20px;">
				<thead><tr><th><?php esc_html_e('Письмо', 'yoga-mail'); ?></th><th style="width:180px;"><?php esc_html_e('Группа', 'yoga-mail'); ?></th><th style="width:170px;"><?php esc_html_e('Статус', 'yoga-mail'); ?></th><th style="width:110px;"></th></tr></thead>
				<tbody>
				<?php foreach ($visible_templates as $id => $item) : $is_ready = !empty($item['designed']); ?>
					<tr<?php echo $id === $selected ? ' class="active"' : ''; ?>>
						<td><strong><?php echo esc_html($item['label']); ?></strong><br><code><?php echo esc_html($id); ?></code></td>
						<td><?php echo esc_html($item['group']); ?></td>
						<td><span style="display:inline-block;padding:4px 9px;border-radius:12px;background:<?php echo $is_ready ? '#dff3e4' : '#f0f0f1'; ?>;color:<?php echo $is_ready ? '#176b2c' : '#50575e'; ?>;font-weight:600;"><?php echo esc_html($is_ready ? __('Готов', 'yoga-mail') : __('Базовый', 'yoga-mail')); ?></span></td>
						<td><a class="button button-small" href="<?php echo esc_url(add_query_arg(array('page' => 'yoga-mail', 'template_status' => $template_status, 'template' => $id), admin_url('admin.php'))); ?>"><?php esc_html_e('Открыть', 'yoga-mail'); ?></a></td>
					</tr>
				<?php endforeach; ?>
				<?php if (!$visible_templates) : ?><tr><td colspan="4"><?php esc_html_e('В этой категории пока нет писем.', 'yoga-mail'); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
			<form method="get" style="margin:16px 0;">
				<input type="hidden" name="page" value="yoga-mail">
				<input type="hidden" name="template_status" value="<?php echo esc_attr($template_status); ?>">
				<label for="yoga-template"><strong><?php esc_html_e('Шаблон:', 'yoga-mail'); ?></strong></label>
				<select id="yoga-template" name="template" onchange="this.form.submit()">
					<?php $last_group = ''; foreach ($visible_templates as $id => $item) : ?>
						<?php if ($last_group !== $item['group']) : if ($last_group !== '') echo '</optgroup>'; $last_group = $item['group']; ?><optgroup label="<?php echo esc_attr($last_group); ?>"><?php endif; ?>
						<option value="<?php echo esc_attr($id); ?>" <?php selected($selected, $id); ?>><?php echo esc_html(($item['designed'] ? '✓ ' : '○ ') . $item['label']); ?></option>
					<?php endforeach; if ($last_group !== '') echo '</optgroup>'; ?>
				</select>
				<noscript><?php submit_button(__('Открыть', 'yoga-mail'), 'secondary', '', false); ?></noscript>
			</form>

			<form method="post">
				<?php wp_nonce_field('yoga_mail_save'); ?>
				<input type="hidden" name="template_id" value="<?php echo esc_attr($selected); ?>">
				<input type="hidden" name="template_status" value="<?php echo esc_attr($template_status); ?>">
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
