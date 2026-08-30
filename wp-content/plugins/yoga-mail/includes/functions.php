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
