<?php

if (!defined('ABSPATH')) {
	exit;
}

final class Yoga_Mail_WordPress {
	private $registry;
	private $renderer;
	private $mailer;

	public function __construct(Yoga_Mail_Registry $registry, Yoga_Mail_Renderer $renderer, Yoga_Mail_Mailer $mailer) {
		$this->registry = $registry;
		$this->renderer = $renderer;
		$this->mailer = $mailer;
	}

	public function init(): void {
		add_filter('wp_new_user_notification_email', array($this, 'new_user'), 20, 3);
		add_filter('retrieve_password_title', array($this, 'reset_password_title'), 20, 3);
		add_filter('retrieve_password_message', array($this, 'reset_password_message'), 20, 4);
		add_filter('password_reset_expiration', array($this, 'reset_password_expiration'), 20, 1);
		add_filter('password_change_email', array($this, 'password_changed'), 20, 3);
		add_filter('email_change_email', array($this, 'email_changed'), 20, 3);
		add_filter('site_admin_email_change_email', array($this, 'admin_email_changed'), 20, 3);
		add_filter('comment_notification_subject', array($this, 'comment_notification_subject'), 20, 2);
		add_filter('comment_notification_text', array($this, 'comment_notification'), 20, 2);
		add_filter('comment_moderation_subject', array($this, 'comment_moderation_subject'), 20, 2);
		add_filter('comment_moderation_text', array($this, 'comment_moderation'), 20, 2);
		add_filter('recovery_mode_email', array($this, 'recovery_mode'), 20, 2);
	}

	public function new_user(array $email, WP_User $user, string $blogname): array {
		if (!$this->enabled()) {
			return $email;
		}
		return $this->brand_email_array('wp-new-user', $email, array(
			'user_name' => $user->display_name ?: $user->user_login,
			'user_email' => $user->user_email,
		));
	}

	public function reset_password_title(string $title, string $user_login, WP_User $user): string {
		return $this->enabled() ? $this->configured_subject('wp-reset-password', $title, array('user_name' => $user->display_name ?: $user_login, 'user_email' => $user->user_email)) : $title;
	}

	public function reset_password_message(string $message, string $key, string $user_login, WP_User $user): string {
		if (!$this->enabled()) {
			return $message;
		}
		$url = network_site_url('wp-login.php?action=rp&key=' . rawurlencode($key) . '&login=' . rawurlencode($user_login), 'login');
		return $this->brand_message('wp-reset-password', __('Восстановление пароля'), $message, array(
			'user_name' => $user->display_name ?: $user_login,
			'user_email' => $user->user_email,
			'action_url' => $url,
		));
	}

	public function reset_password_expiration(int $expiration): int {
		return $this->enabled() ? HOUR_IN_SECONDS : $expiration;
	}

	public function password_changed(array $email, array $user, array $userdata): array {
		return $this->enabled() ? $this->brand_email_array('wp-password-changed', $email, array(
			'user_name' => (string) ($user['display_name'] ?? $user['user_login'] ?? ''),
			'action_url' => wp_lostpassword_url(),
			'event_datetime' => wp_date('j F Y, H:i'),
		)) : $email;
	}

	public function email_changed(array $email, array $user, array $userdata): array {
		$new_email = (string) ($userdata['user_email'] ?? '');
		return $this->enabled() ? $this->brand_email_array('wp-email-changed', $email, array(
			'user_name' => (string) ($user['display_name'] ?? $user['user_login'] ?? ''),
			'user_email' => $new_email,
			'old_email' => (string) ($user['user_email'] ?? ''),
			'new_email' => $new_email,
			'action_url' => 'mailto:support@platform.kundalini-class.ru',
			'event_datetime' => wp_date('j F Y, H:i'),
		)) : $email;
	}

	public function admin_email_changed(array $email, string $old_email, string $new_email): array {
		return $this->enabled() ? $this->brand_email_array('wp-admin-email-changed', $email, array('user_email' => $new_email)) : $email;
	}

	public function comment_notification_subject(string $subject, int $comment_id): string {
		return $this->enabled() ? $this->configured_subject('wp-comment-notification', $subject, array()) : $subject;
	}

	public function comment_notification(string $message, int $comment_id): string {
		return $this->enabled() ? $this->brand_message('wp-comment-notification', __('Новый комментарий'), $message, array()) : $message;
	}

	public function comment_moderation_subject(string $subject, int $comment_id): string {
		return $this->enabled() ? $this->configured_subject('wp-comment-moderation', $subject, array()) : $subject;
	}

	public function comment_moderation(string $message, int $comment_id): string {
		return $this->enabled() ? $this->brand_message('wp-comment-moderation', __('Комментарий ожидает модерации'), $message, array()) : $message;
	}

	public function recovery_mode(array $email, string $url): array {
		if (!$this->enabled()) {
			return $email;
		}
		return $this->brand_email_array('wp-recovery-mode', $email, array('action_url' => $url));
	}

	private function enabled(): bool {
		return $this->registry->flag('wordpress_enabled');
	}

	private function brand_email_array(string $template_id, array $email, array $data): array {
		$subject = (string) ($email['subject'] ?? '');
		$message = (string) ($email['message'] ?? '');
		$email['subject'] = $this->configured_subject($template_id, $subject, $data);
		$email['message'] = $this->brand_message($template_id, $email['subject'], $message, $data);
		return $email;
	}

	private function configured_subject(string $template_id, string $subject, array $data): string {
		$data['subject'] = $subject;
		$data['content'] = '';
		$result = $this->renderer->render_field($template_id, 'subject', $data, 'text');
		return is_wp_error($result) ? $subject : wp_strip_all_tags((string) $result);
	}

	private function brand_message(string $template_id, string $subject, string $message, array $data): string {
		$is_html = stripos($message, '<p') !== false || stripos($message, '<table') !== false || stripos($message, '<html') !== false;
		$data['subject'] = $subject;
		$data['content'] = $is_html ? $message : nl2br(esc_html($message));
		$rendered = $this->renderer->render($template_id, $data, false);
		if (is_wp_error($rendered)) {
			return $message;
		}
		$this->mailer->remember_prepared($rendered['html'], $rendered['text'], $template_id);
		return $rendered['html'];
	}
}
