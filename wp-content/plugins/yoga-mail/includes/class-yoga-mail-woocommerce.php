<?php

if (!defined('ABSPATH')) {
	exit;
}

final class Yoga_Mail_WooCommerce {
	private const PAYMENT_RECEIPT_SENT_META = '_yoga_mail_payment_receipt_sent_at';
	private const PAYMENT_RECEIPT_LOCK_PREFIX = 'yoga_mail_payment_receipt_lock_';

	private $registry;
	private $renderer;
	private $mailer;
	private $configured = false;
	private $active_email;
	private $payment_receipt_sent_order_ids = array();

	public function __construct(Yoga_Mail_Registry $registry, Yoga_Mail_Renderer $renderer, Yoga_Mail_Mailer $mailer) {
		$this->registry = $registry;
		$this->renderer = $renderer;
		$this->mailer = $mailer;
	}

	public function init(): void {
		$this->disable_block_editor();
		add_action('woocommerce_order_status_processing', array($this, 'send_payment_success_receipt'), 1, 1);
		add_action('woocommerce_order_status_completed', array($this, 'send_payment_success_receipt'), 1, 1);
		add_filter('woocommerce_email_enabled_customer_processing_order', array($this, 'disable_standard_processing_email'), 999, 3);
		add_filter('woocommerce_email_enabled_customer_completed_order', array($this, 'maybe_disable_standard_completed_email'), 999, 3);
		add_action('woocommerce_email', array($this, 'configure'), 999);
		add_filter('woocommerce_email_styles', array($this, 'email_styles'), 999, 2);
		add_filter('woocommerce_mail_content', array($this, 'mark_mail_content'), 999);
		add_filter('woocommerce_email_subject_low_stock', array($this, 'low_stock_subject'), 20, 2);
		add_filter('woocommerce_email_content_low_stock', array($this, 'low_stock_content'), 20, 2);
		add_filter('woocommerce_email_subject_no_stock', array($this, 'no_stock_subject'), 20, 2);
		add_filter('woocommerce_email_content_no_stock', array($this, 'no_stock_content'), 20, 2);
		add_filter('woocommerce_email_subject_backorder', array($this, 'backorder_subject'), 20, 2);
		add_filter('woocommerce_email_content_backorder', array($this, 'backorder_content'), 20, 2);
	}

	public function send_payment_success_receipt($order_id): void {
		if (!$this->registry->flag('woocommerce_enabled') || !function_exists('wc_get_order')) {
			return;
		}

		$order = wc_get_order($order_id);
		if (!$order || !is_a($order, 'WC_Order') || !$order->is_paid()) {
			return;
		}
		if ((string) $order->get_meta('_ytr_renewal') === 'yes') {
			if (class_exists('YTR_Notifications') && method_exists('YTR_Notifications', 'send_renewal_success')) {
				$sent = YTR_Notifications::send_renewal_success($order);
				if ($sent || (string) $order->get_meta(YTR_Notifications::META_RENEWAL_SUCCESS_EMAIL_SENT_AT) !== '') {
					$this->payment_receipt_sent_order_ids[(int) $order->get_id()] = true;
				}
			}
			return;
		}
		if ((string) $order->get_meta(self::PAYMENT_RECEIPT_SENT_META) !== '') {
			return;
		}

		$email = (string) $order->get_billing_email();
		if ($email === '' || !is_email($email) || !$this->acquire_payment_receipt_lock((int) $order->get_id())) {
			return;
		}

		try {
			$order = wc_get_order($order->get_id());
			if (!$order || (string) $order->get_meta(self::PAYMENT_RECEIPT_SENT_META) !== '') {
				return;
			}

			$paid_at = $order->get_date_paid() ?: $order->get_date_created();
			$sent = $this->mailer->send('payment-success-receipt', array(
				'to' => $email,
				'data' => array(
					'receipt_number' => (string) $order->get_order_number(),
					'payment_date' => $paid_at ? wp_date('j F Y', $paid_at->getTimestamp()) : wp_date('j F Y'),
					'receipt_items' => $this->receipt_items_html($order),
					'total_amount' => $this->format_money((float) $order->get_total(), (string) $order->get_currency()),
					'payment_method' => $this->payment_method_label($order),
					'action_url' => (string) $order->get_view_order_url(),
				),
			));
			if ($sent) {
				$order->update_meta_data(self::PAYMENT_RECEIPT_SENT_META, (string) time());
				$order->add_order_note(__('Yoga Mail: покупателю отправлено письмо об успешной оплате с чеком.', 'yoga-mail'));
				$order->save();
				$this->payment_receipt_sent_order_ids[(int) $order->get_id()] = true;
			}
		} finally {
			delete_option(self::PAYMENT_RECEIPT_LOCK_PREFIX . (int) $order_id);
		}
	}

	public function disable_standard_processing_email($enabled, $order = null, $email = null) {
		return $this->registry->flag('woocommerce_enabled') ? false : $enabled;
	}

	public function maybe_disable_standard_completed_email($enabled, $order, $email = null) {
		if (
			!$this->registry->flag('woocommerce_enabled')
			|| !is_object($order)
			|| !method_exists($order, 'get_meta')
			|| !method_exists($order, 'get_id')
		) {
			return $enabled;
		}

		$order_id = (int) $order->get_id();
		if (isset($this->payment_receipt_sent_order_ids[$order_id])) {
			return false;
		}

		$fresh_order = function_exists('wc_get_order') ? wc_get_order($order_id) : false;
		if ($fresh_order && is_a($fresh_order, 'WC_Order')) {
			$order = $fresh_order;
		}

		if ((string) $order->get_meta('_ytr_renewal') === 'yes') {
			$meta_key = class_exists('YTR_Notifications')
				? YTR_Notifications::META_RENEWAL_SUCCESS_EMAIL_SENT_AT
				: '_ytr_renewal_success_email_sent_at';
			return (string) $order->get_meta($meta_key) !== '' ? false : $enabled;
		}
		return (string) $order->get_meta(self::PAYMENT_RECEIPT_SENT_META) !== '' ? false : $enabled;
	}

	public function configure($emails): void {
		if ($this->configured || !is_object($emails) || !method_exists($emails, 'get_emails')) {
			return;
		}
		$this->configured = true;
		if ($this->registry->flag('woocommerce_enabled')) {
			remove_action('woocommerce_email_header', array($emails, 'email_header'));
			remove_action('woocommerce_email_footer', array($emails, 'email_footer'));
			add_action('woocommerce_email_header', array($this, 'email_header'), 10, 2);
			add_action('woocommerce_email_footer', array($this, 'email_footer'), 10, 1);
		}

		foreach ((array) $emails->get_emails() as $email) {
			if (!is_object($email) || empty($email->id)) {
				continue;
			}
			$id = sanitize_key((string) $email->id);
			$template_id = 'woocommerce-' . $id;
			$this->registry->register($template_id, array(
				'label' => 'WooCommerce: ' . (method_exists($email, 'get_title') ? $email->get_title() : $id),
				'group' => 'WooCommerce',
				'defaults' => array(
					'subject' => '{{subject}}', 'preheader' => '{{subject}}', 'heading' => '{{heading}}',
					'body' => '{{content}}', 'cta_label' => '', 'cta_url' => '', 'footer_note' => '',
				),
				'tags' => array(
					'heading' => array('type' => 'text', 'example' => 'Информация о заказе'),
					'customer_name' => array('type' => 'text', 'example' => 'Анна'),
				),
			));
			if ($this->registry->flag('woocommerce_enabled')) {
				$email->email_type = 'multipart';
			}
			$this->add_email_filters($id);
		}
	}

	public function email_header($heading, $email = null): void {
		$this->active_email = $email;
		$template_id = $this->template_id($email);
		$settings = $this->registry->settings();
		$preheader = '';
		if (is_object($email) && method_exists($email, 'get_preheader')) {
			$preheader = (string) $email->get_preheader();
		}
		include YOGA_MAIL_PATH . 'templates/woocommerce/email-header.php';
	}

	public function email_footer($email = null): void {
		$email = $email ?: $this->active_email;
		$template_id = $this->template_id($email);
		$settings = $this->registry->settings();
		$data = $this->email_data($email, array('subject' => '', 'heading' => '', 'content' => ''));
		$cta_label = $this->renderer->render_field($template_id, 'cta_label', $data, 'text');
		$cta_url = $this->renderer->render_field($template_id, 'cta_url', $data, 'url');
		$footer_note = $this->renderer->render_field($template_id, 'footer_note', $data, 'text');
		$cta_label = is_wp_error($cta_label) ? '' : (string) $cta_label;
		$cta_url = is_wp_error($cta_url) ? '' : (string) $cta_url;
		$footer_note = is_wp_error($footer_note) ? '' : (string) $footer_note;
		include YOGA_MAIL_PATH . 'templates/woocommerce/email-footer.php';
		$this->active_email = null;
	}

	public function email_styles(string $css, $email): string {
		if (!$this->registry->flag('woocommerce_enabled')) {
			return $css;
		}
		return $css . "\n#template_container{width:560px;max-width:560px;border:0;background:#ffffff;}"
			. "#template_body td,#body_content td{font-family:Mulish,Arial,Helvetica,sans-serif;color:#1f1f1f;}"
			. "a{color:#9153e1;}";
	}

	public function mark_mail_content(string $content): string {
		if (!$this->registry->flag('woocommerce_enabled') || strpos($content, '<!-- yoga-mail:') !== false) {
			return $content;
		}
		$template_id = $this->template_id($this->find_sending_email());
		return '<!-- yoga-mail:' . esc_html($template_id) . ' -->' . $content;
	}

	public function low_stock_subject(string $subject, $product): string {
		return $this->stock_subject('woocommerce-low-stock', $subject, $product);
	}

	public function low_stock_content(string $message, $product): string {
		return $this->stock_content('woocommerce-low-stock', __('Мало товара на складе', 'yoga-mail'), $message, $product);
	}

	public function no_stock_subject(string $subject, $product): string {
		return $this->stock_subject('woocommerce-no-stock', $subject, $product);
	}

	public function no_stock_content(string $message, $product): string {
		return $this->stock_content('woocommerce-no-stock', __('Товар закончился', 'yoga-mail'), $message, $product);
	}

	public function backorder_subject(string $subject, $args): string {
		return $this->stock_subject('woocommerce-backorder', $subject, $args);
	}

	public function backorder_content(string $message, $args): string {
		return $this->stock_content('woocommerce-backorder', __('Оформлен предзаказ', 'yoga-mail'), $message, $args);
	}

	private function add_email_filters(string $id): void {
		$template_id = 'woocommerce-' . $id;
		add_filter('woocommerce_email_subject_' . $id, function ($subject, $object, $email) use ($template_id) {
			return $this->field_or_original($template_id, 'subject', $subject, $email, array('subject' => $subject));
		}, 999, 3);
		add_filter('woocommerce_email_heading_' . $id, function ($heading, $object, $email) use ($template_id) {
			return $this->field_or_original($template_id, 'heading', $heading, $email, array('heading' => $heading, 'subject' => $heading));
		}, 999, 3);
		add_filter('woocommerce_email_preheader' . $id, function ($preheader, $object, $email) use ($template_id) {
			return $this->field_or_original($template_id, 'preheader', $preheader, $email, array('subject' => method_exists($email, 'get_subject') ? $email->get_subject() : $preheader));
		}, 999, 3);
		add_filter('woocommerce_email_additional_content_' . $id, function ($content, $object, $email) use ($template_id) {
			$result = $this->renderer->render_field($template_id, 'body', $this->email_data($email, array('subject' => method_exists($email, 'get_subject') ? $email->get_subject() : '', 'content' => $content)), 'html');
			return is_wp_error($result) ? $content : $this->renderer->inline_content_styles((string) $result);
		}, 999, 3);
	}

	private function field_or_original(string $template_id, string $field, string $original, $email, array $extra): string {
		if (!$this->registry->flag('woocommerce_enabled')) {
			return $original;
		}
		$result = $this->renderer->render_field($template_id, $field, $this->email_data($email, $extra), 'text');
		return is_wp_error($result) ? $original : (string) $result;
	}

	private function stock_subject(string $template_id, string $subject, $object): string {
		if (!$this->registry->flag('woocommerce_enabled')) {
			return $subject;
		}
		$result = $this->renderer->render_field($template_id, 'subject', array('subject' => $subject, 'content' => ''), 'text');
		return is_wp_error($result) ? $subject : (string) $result;
	}

	private function stock_content(string $template_id, string $subject, string $message, $object): string {
		if (!$this->registry->flag('woocommerce_enabled')) {
			return $message;
		}
		$rendered = $this->renderer->render($template_id, array('subject' => $subject, 'content' => nl2br(esc_html($message))), false);
		if (is_wp_error($rendered)) {
			return $message;
		}
		$this->mailer->remember_prepared($rendered['html'], $rendered['text'], $template_id);
		return $rendered['html'];
	}

	private function template_id($email): string {
		$id = is_object($email) && !empty($email->id) ? sanitize_key((string) $email->id) : 'generic';
		return 'woocommerce-' . $id;
	}

	private function email_data($email, array $extra): array {
		$data = $extra;
		$data['content'] = isset($data['content']) ? $data['content'] : '';
		$data['subject'] = isset($data['subject']) ? $data['subject'] : '';
		$data['heading'] = isset($data['heading']) ? $data['heading'] : $data['subject'];
		$data['action_url'] = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/');
		$data['order_url'] = $data['action_url'];
		$data['order_number'] = '';
		$data['customer_name'] = '';
		if (is_object($email) && isset($email->object) && is_a($email->object, 'WC_Order')) {
			$order = $email->object;
			$data['order_number'] = (string) $order->get_order_number();
			$data['order_url'] = (string) $order->get_view_order_url();
			$data['action_url'] = $data['order_url'];
			$data['customer_name'] = trim((string) $order->get_formatted_billing_full_name());
		}
		return $data;
	}

	private function acquire_payment_receipt_lock(int $order_id): bool {
		$key = self::PAYMENT_RECEIPT_LOCK_PREFIX . $order_id;
		$locked_at = (int) get_option($key, 0);
		if ($locked_at > 0 && $locked_at < time() - 10 * MINUTE_IN_SECONDS) {
			delete_option($key);
		}
		return add_option($key, (string) time(), '', false);
	}

	private function receipt_items_html($order): string {
		$rows = '';
		foreach ($order->get_items('line_item') as $item) {
			$name = (string) $item->get_name();
			$quantity = max(1, (int) $item->get_quantity());
			if ($quantity > 1) {
				$name .= ' × ' . $quantity;
			}
			$amount = (float) $item->get_total() + (float) $item->get_total_tax();
			$rows .= '<tr><td valign="middle" style="padding:15px 10px 15px 0;font-size:14px;line-height:1.5;font-weight:400;color:#606060;text-align:left;">'
				. esc_html($name)
				. '</td><td valign="middle" align="right" style="padding:15px 0 15px 10px;font-size:14px;line-height:1.5;font-weight:400;color:#606060;text-align:right;white-space:nowrap;">'
				. esc_html($this->format_money($amount, (string) $order->get_currency()))
				. '</td></tr>';
		}
		return $rows;
	}

	private function payment_method_label($order): string {
		$label = trim(wp_strip_all_tags((string) $order->get_payment_method_title()));
		$user_id = (int) $order->get_customer_id();
		if ($user_id > 0 && class_exists('YTR_Saved_Cards')) {
			$cards = YTR_Saved_Cards::get_cards($user_id);
			$card = is_array($cards) && isset($cards[0]) && is_array($cards[0]) ? $cards[0] : array();
			$last4 = preg_replace('/\D+/', '', (string) ($card['last4'] ?? ''));
			if (strlen($last4) >= 4) {
				return 'Карта •• ' . substr($last4, -4);
			}
		}
		return $label !== '' ? $label : __('Карта', 'yoga-mail');
	}

	private function format_money(float $amount, string $currency): string {
		if (function_exists('wc_price')) {
			return trim(html_entity_decode(wp_strip_all_tags(wc_price($amount, array('currency' => $currency))), ENT_QUOTES, 'UTF-8'));
		}
		return number_format_i18n($amount, 0) . ($currency !== '' ? ' ' . $currency : '');
	}

	private function find_sending_email() {
		if (!function_exists('WC') || !WC() || !WC()->mailer()) {
			return null;
		}
		foreach ((array) WC()->mailer()->get_emails() as $email) {
			if (is_object($email) && !empty($email->sending)) {
				return $email;
			}
		}
		return null;
	}

	private function disable_block_editor(): void {
		$current = (string) get_option('woocommerce_feature_block_email_editor_enabled', 'no');
		if (get_option('yoga_mail_previous_wc_block_editor', null) === null) {
			$legacy = get_option('kundalini_mail_previous_wc_block_editor', null);
			add_option('yoga_mail_previous_wc_block_editor', is_string($legacy) ? $legacy : $current, '', false);
		}
		if ($current !== 'no') {
			update_option('woocommerce_feature_block_email_editor_enabled', 'no', false);
		}
	}
}
