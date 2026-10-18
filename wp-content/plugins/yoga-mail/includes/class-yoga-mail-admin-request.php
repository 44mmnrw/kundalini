<?php

if (!defined('ABSPATH')) {
	exit;
}

/** Shared Figma layout and structured data for staff request notifications. */
final class Yoga_Mail_Admin_Request {
	public const DESIGN_VERSION = 1;
	public const TEMPLATES = array(
		'contacts' => 'admin-contact-message',
		'faq' => 'admin-faq-question',
		'lk' => 'admin-new-question',
		'practice' => 'admin-practice-question',
	);

	public static function is_template(string $id): bool {
		return in_array($id, self::TEMPLATES, true);
	}

	public static function definitions(): array {
		$titles = array(
			'contacts' => "Новое обращение отпр. с\n\"Контакты\"",
			'faq' => "Новое обращение отпр. с\n“Частые вопросы”",
			'lk' => "Новое обращение отпр. с\n“ЛК: Общие вопросы”",
			'practice' => "Новый вопрос по практике\n“{{practice_title}}”",
		);
		$labels = array('contacts' => 'Контакты', 'faq' => 'Частые вопросы', 'lk' => 'ЛК: Общие вопросы', 'practice' => 'Вопрос по практике');
		$definitions = array();
		foreach (self::TEMPLATES as $event => $id) {
			$definitions[$id] = array(
				'label' => 'Администратору: ' . $labels[$event],
				'group' => 'Административные',
				'designed' => true,
				'defaults' => array(
					'subject' => str_replace("\n", ' ', $titles[$event]),
					'preheader' => 'От кого: {{user_name}}. Дата: {{request_date}} в {{request_time}} по МСК.',
					'heading' => $titles[$event],
					'body' => self::body($event === 'practice' ? 'Текст вопроса:' : 'Текст обращения:'),
					'cta_label' => 'Ответить из админки',
					'cta_url' => '{{question_reply_url}}',
					'footer_note' => '',
				),
				'tags' => array(
					'request_date' => array('type' => 'text', 'example' => '14 июля 2026'),
					'request_time' => array('type' => 'text', 'example' => '21:32'),
					'user_name' => array('type' => 'text', 'example' => 'Марина Ксенофонтова'),
					'user_email' => array('type' => 'text', 'example' => 'marina.yoginia888@gmail.com'),
					'practice_title' => array('type' => 'text', 'example' => 'Крийя для пупочного центра'),
					'request_message' => array('type' => 'html', 'example' => 'Здравствуйте! Подскажите, пожалуйста, как правильно выполнять эту практику.'),
					'question_reply_url' => array('type' => 'url', 'example' => self::example_reply_url()),
					'request_reply_link' => array('type' => 'html', 'example' => self::reply_link(self::example_reply_url())),
				),
			);
		}
		return $definitions;
	}

	private static function body(string $message_label): string {
		return '<p style="margin:0;line-height:1.5;font-size:14px;text-align:center;"><strong>Дата: {{request_date}}</strong> в <strong>{{request_time}} по МСК.</strong></p>'
			. '<p style="margin:15px 0 0;line-height:1.5;font-size:14px;text-align:center;"><strong>От кого:</strong> {{user_name}}<br><strong>Эл. почта:</strong> {{user_email}}</p>'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:30px 0 0;border-collapse:separate;background-color:#f6f6f9;border-radius:15px 15px 15px 0;"><tr><td align="left" bgcolor="#f6f6f9" style="padding:20px;background-color:#f6f6f9;border-radius:15px 15px 15px 0;font-size:14px;line-height:1.5;color:#1f1f1f;text-align:left;word-break:break-word;">'
			. '<p style="margin:0;line-height:1;font-size:14px;font-weight:700;text-align:left;">' . $message_label . '</p>'
			. '<p style="margin:10px 0 0;line-height:1.5;font-size:14px;text-align:left;">{{request_message}}</p></td></tr></table>'
			. '<p style="margin:30px 0 0;line-height:1.5;font-size:14px;text-align:center;word-break:break-word;">Ссылка для ответа: {{request_reply_link}}</p>'
			. '<p style="margin:15px 0 0;line-height:1.5;font-size:14px;text-align:center;">Или перейдите по кнопке ниже ↓</p>';
	}

	private static function example_reply_url(): string {
		return home_url('/wp-admin/post.php?post=1183&action=edit');
	}

	public static function reply_link(string $url): string {
		return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" style="color:#9153e1;text-decoration:underline;word-break:break-word;">' . esc_html($url) . '</a>';
	}

	/** Values come from the saved question, not from preformatted email HTML. */
	public static function data(string $event, WP_Post $question, array $fallback = array()): array {
		$name = trim((string) get_post_meta($question->ID, 'contact_name', true));
		$email = (string) get_post_meta($question->ID, 'contact_email', true);
		$user = !empty($question->post_author) ? get_userdata((int) $question->post_author) : false;
		if ($name === '') {
			$name = $user ? (string) $user->display_name : (string) ($fallback['user_name'] ?? '');
		}
		if ($email === '') {
			$email = $user ? (string) $user->user_email : (string) ($fallback['user_email'] ?? '');
		}
		$created = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) ($question->post_date_gmt ?? ''), new DateTimeZone('UTC'));
		if (!$created || (int) $created->format('Y') < 1970) {
			$created = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) ($question->post_date ?? ''), wp_timezone());
		}
		$created = ($created ?: new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('Europe/Moscow'));
		$practice_id = $event === 'practice' ? absint(get_post_meta($question->ID, 'practice_id', true)) : 0;
		$url = admin_url('post.php?post=' . (int) $question->ID . '&action=edit') . '#question_answer';
		return array(
			'user_name' => $name !== '' ? $name : 'Не указано',
			'user_email' => $email,
			'request_date' => wp_date('j F Y', $created->getTimestamp(), new DateTimeZone('Europe/Moscow')),
			'request_time' => $created->format('H:i'),
			'request_message' => nl2br(esc_html((string) $question->post_content)),
			'practice_title' => $practice_id > 0 && get_post_type($practice_id) === 'practice' ? wp_specialchars_decode((string) get_the_title($practice_id), ENT_QUOTES) : 'Практика не указана',
			'question_reply_url' => $url,
			'request_reply_link' => self::reply_link($url),
		);
	}
}
