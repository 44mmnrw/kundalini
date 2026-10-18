<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Send a registered Yoga Mail email.
 *
 * @param string $template_id Registered template identifier.
 * @param array  $args        to, data, headers, attachments, embeds, bypass_flags.
 */
function yoga_mail_send(string $template_id, array $args): bool {
	return Yoga_Mail_Plugin::instance()->mailer()->send($template_id, $args);
}

/** Send a staff notification only after the question has been saved. */
function yoga_mail_send_event(string $event, int $question_id, array $args): bool {
	return Yoga_Mail_Events::send($event, $question_id, $args);
}

/** Fiscal receipts are delivered by YooKassa, independently of the order email. */
function yoga_mail_fiscal_receipt_notice($order): string {
	if (!(bool) get_option('yookassa_enable_receipt', false) || !is_object($order) || !method_exists($order, 'get_payment_method')) {
		return '';
	}
	if (strpos((string) $order->get_payment_method(), 'yookassa') !== 0) {
		return '';
	}
	$email = (string) $order->get_billing_email();
	if (!is_email($email)) {
		return '';
	}
	if ((string) get_option('yookassa_access_token', '') === '' && substr((string) get_option('yookassa_shop_password', ''), 0, 4) === 'test') {
		return 'Это тестовый платёж. ЮKassa не формирует настоящий фискальный чек и не отправляет его покупателю.';
	}
	return 'После регистрации ЮKassa отправит фискальный чек отдельным письмом на ' . $email . '. Чек может прийти позже подтверждения оплаты.';
}

/**
 * Return the current template registry for integrations and diagnostics.
 */
function yoga_mail_templates(): array {
	return Yoga_Mail_Plugin::instance()->registry()->all();
}

/**
 * Backward-compatible alias for integrations created before the Yoga Mail rename.
 */
if (!function_exists('kundalini_mail_send')) {
	function kundalini_mail_send(string $template_id, array $args): bool {
		return yoga_mail_send($template_id, $args);
	}
}

/**
 * Backward-compatible template-registry alias.
 */
if (!function_exists('kundalini_mail_templates')) {
	function kundalini_mail_templates(): array {
		return yoga_mail_templates();
	}
}
