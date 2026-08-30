<?php

if (!defined('ABSPATH')) {
	exit;
}

final class Yoga_Mail_Mailer {
	private $registry;
	private $renderer;
	private $prepared = array();
	private $pending_alt = array();

	public function __construct(Yoga_Mail_Registry $registry, Yoga_Mail_Renderer $renderer) {
		$this->registry = $registry;
		$this->renderer = $renderer;
	}

	public function init(): void {
		add_filter('wp_mail', array($this, 'filter_wp_mail'), 999);
		add_filter('pre_wp_mail', array($this, 'cleanup_preempted_mail'), PHP_INT_MAX, 2);
		add_action('phpmailer_init', array($this, 'set_alt_body'), 999);
		add_action('wp_mail_failed', array($this, 'log_wp_mail_failure'));
	}

	public function send(string $template_id, array $args): bool {
		$to = $args['to'] ?? '';
		$data = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();
		if (isset($args['subject'])) {
			$data['subject'] = (string) $args['subject'];
		}
		if (isset($args['content'])) {
			$data['content'] = (string) $args['content'];
		}
		$rendered = $this->renderer->render($template_id, $data, !empty($args['preview']));
		if (is_wp_error($rendered)) {
			$this->log($template_id, 'render_failed:' . $rendered->get_error_code());
			return false;
		}

		$headers = $args['headers'] ?? array();
		$attachments = isset($args['attachments']) && is_array($args['attachments']) ? $args['attachments'] : array();
		$embeds = isset($args['embeds']) && is_array($args['embeds']) ? $args['embeds'] : array();
		$bypass = !empty($args['bypass_flags']);
		if (!$bypass && !$this->registry->flag('custom_enabled')) {
			return (bool) wp_mail($to, $rendered['subject'], $rendered['text'], $this->prepare_plain_headers($headers), $attachments, $embeds);
		}

		$this->remember_prepared($rendered['html'], $rendered['text'], $template_id);
		$id = wp_generate_uuid4();
		$this->pending_alt[$id] = array('text' => $rendered['text'], 'template_id' => $template_id);
		$headers = $this->prepare_headers($headers, $id, $template_id);
		$sent = (bool) wp_mail($to, $rendered['subject'], $rendered['html'], $headers, $attachments, $embeds);
		unset($this->pending_alt[$id]);
		$this->log($template_id, $sent ? 'sent' : 'failed');
		return $sent;
	}

	public function remember_prepared(string $html, string $text, string $template_id): void {
		$this->prepared[sha1($html)] = array('text' => $text, 'template_id' => $template_id);
	}

	public function filter_wp_mail(array $args): array {
		$message = (string) ($args['message'] ?? '');
		$marker_template = $this->marker_template($message);
		if ($marker_template !== '') {
			$existing_id = $this->header_value($args['headers'] ?? array(), 'X-Yoga-Mail-ID');
			if ($existing_id !== '' && isset($this->pending_alt[$existing_id])) {
				$args['headers'] = $this->prepare_headers($args['headers'] ?? array(), $existing_id, $marker_template);
				return $args;
			}
			$prepared = $this->prepared[sha1($message)] ?? array(
				'text' => $this->renderer->plain_from_html($message),
				'template_id' => $marker_template,
			);
			$id = wp_generate_uuid4();
			$this->pending_alt[$id] = $prepared;
			$args['headers'] = $this->prepare_headers($args['headers'] ?? array(), $id, $prepared['template_id']);
			return $args;
		}

		if (!$this->registry->flag('fallback_enabled')) {
			return $args;
		}

		$is_html = $this->headers_are_html($args['headers'] ?? array()) || stripos($message, '<html') !== false || stripos($message, '<p') !== false;
		$content = $is_html ? $this->extract_body($message) : nl2br(esc_html($message));
		$plain = $is_html ? $this->renderer->plain_from_html($content) : trim($message);
		$rendered = $this->renderer->render('generic', array(
			'subject' => (string) ($args['subject'] ?? ''),
			'content' => $content,
		), false);
		if (is_wp_error($rendered)) {
			$this->log('generic', 'fallback_render_failed:' . $rendered->get_error_code());
			return $args;
		}
		$id = wp_generate_uuid4();
		$this->pending_alt[$id] = array('text' => $plain !== '' ? $plain : $rendered['text'], 'template_id' => 'generic');
		$args['message'] = $rendered['html'];
		$args['headers'] = $this->prepare_headers($args['headers'] ?? array(), $id, 'generic');
		return $args;
	}

	public function set_alt_body($phpmailer): void {
		if (!is_object($phpmailer) || !method_exists($phpmailer, 'getCustomHeaders')) {
			return;
		}
		$mail_id = '';
		foreach ((array) $phpmailer->getCustomHeaders() as $header) {
			if (is_array($header) && isset($header[0], $header[1]) && strtolower((string) $header[0]) === 'x-yoga-mail-id') {
				$mail_id = (string) $header[1];
				break;
			}
		}
		if ($mail_id === '' || !isset($this->pending_alt[$mail_id])) {
			return;
		}
		$phpmailer->AltBody = (string) $this->pending_alt[$mail_id]['text'];
		if (method_exists($phpmailer, 'isHTML')) {
			$phpmailer->isHTML(true);
		}
		unset($this->pending_alt[$mail_id]);
	}

	public function cleanup_preempted_mail($return, array $args) {
		if ($return !== null) {
			$id = $this->header_value($args['headers'] ?? array(), 'X-Yoga-Mail-ID');
			if ($id !== '') {
				unset($this->pending_alt[$id]);
			}
		}
		return $return;
	}

	public function log_wp_mail_failure($error): void {
		$code = is_wp_error($error) ? $error->get_error_code() : 'unknown';
		$this->log('transport', 'wp_mail_failed:' . $code);
	}

	private function prepare_headers($headers, string $id, string $template_id): array {
		if (is_string($headers)) {
			$headers = preg_split('/\r?\n/', $headers);
		}
		$headers = is_array($headers) ? $headers : array();
		$headers = array_values(array_filter($headers, static function ($header): bool {
			return stripos((string) $header, 'Content-Type:') !== 0
				&& stripos((string) $header, 'X-Yoga-Mail-ID:') !== 0
				&& stripos((string) $header, 'X-Yoga-Mail-Template:') !== 0
				&& stripos((string) $header, 'X-Kundalini-Template:') !== 0;
		}));
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$headers[] = 'X-Yoga-Mail-ID: ' . $id;
		$headers[] = 'X-Yoga-Mail-Template: ' . sanitize_key($template_id);
		return $headers;
	}

	private function prepare_plain_headers($headers): array {
		if (is_string($headers)) {
			$headers = preg_split('/\r?\n/', $headers);
		}
		$headers = is_array($headers) ? $headers : array();
		$headers = array_values(array_filter($headers, static function ($header): bool {
			return stripos((string) $header, 'Content-Type:') !== 0;
		}));
		$headers[] = 'Content-Type: text/plain; charset=UTF-8';
		return $headers;
	}

	private function headers_are_html($headers): bool {
		$headers = is_array($headers) ? $headers : preg_split('/\r?\n/', (string) $headers);
		foreach ((array) $headers as $header) {
			if (stripos((string) $header, 'Content-Type:') === 0 && stripos((string) $header, 'text/html') !== false) {
				return true;
			}
		}
		return false;
	}

	private function header_value($headers, string $name): string {
		$headers = is_array($headers) ? $headers : preg_split('/\r?\n/', (string) $headers);
		foreach ((array) $headers as $header) {
			$parts = explode(':', (string) $header, 2);
			if (count($parts) === 2 && strtolower(trim($parts[0])) === strtolower($name)) {
				return trim($parts[1]);
			}
		}
		return '';
	}

	private function marker_template(string $message): string {
		if (preg_match('/<!--\s*yoga-mail:([a-z0-9_-]+)\s*-->/', $message, $matches)) {
			return sanitize_key($matches[1]);
		}
		return '';
	}

	private function extract_body(string $html): string {
		if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $matches)) {
			return wp_kses_post($matches[1]);
		}
		return wp_kses_post($html);
	}

	private function log(string $template_id, string $status): void {
		do_action('yoga_mail_log', sanitize_key($template_id), sanitize_key(str_replace(':', '_', $status)));
		if (defined('WP_DEBUG') && WP_DEBUG && strpos($status, 'failed') !== false) {
			error_log('[yoga-mail] template=' . sanitize_key($template_id) . ' status=' . sanitize_key(str_replace(':', '_', $status)));
		}
	}
}
