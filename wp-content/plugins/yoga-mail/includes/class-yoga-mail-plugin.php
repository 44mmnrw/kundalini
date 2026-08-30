<?php

if (!defined('ABSPATH')) {
	exit;
}

final class Yoga_Mail_Plugin {
	private static $instance;
	private $registry;
	private $renderer;
	private $mailer;
	private $initialized = false;

	public static function instance(): self {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		if ($this->initialized) {
			return;
		}
		$this->initialized = true;
		$this->registry = new Yoga_Mail_Registry();
		$this->renderer = new Yoga_Mail_Renderer($this->registry);
		$this->mailer = new Yoga_Mail_Mailer($this->registry, $this->renderer);
		$this->mailer->init();

		(new Yoga_Mail_WordPress($this->registry, $this->renderer, $this->mailer))->init();
		(new Yoga_Mail_WooCommerce($this->registry, $this->renderer, $this->mailer))->init();
		if (is_admin()) {
			(new Yoga_Mail_Admin($this->registry, $this->renderer, $this->mailer))->init();
		}
	}

	public function registry(): Yoga_Mail_Registry {
		return $this->registry;
	}

	public function renderer(): Yoga_Mail_Renderer {
		return $this->renderer;
	}

	public function mailer(): Yoga_Mail_Mailer {
		return $this->mailer;
	}

	public static function activate(): void {
		$settings = get_option(Yoga_Mail_Registry::SETTINGS_OPTION, null);
		if (!is_array($settings)) {
			$legacy_settings = get_option(Yoga_Mail_Registry::LEGACY_SETTINGS_OPTION, null);
			add_option(Yoga_Mail_Registry::SETTINGS_OPTION, is_array($legacy_settings) ? $legacy_settings : Yoga_Mail_Registry::default_settings(), '', false);
		}
		if (get_option(Yoga_Mail_Registry::TEMPLATES_OPTION, null) === null) {
			$legacy_templates = get_option(Yoga_Mail_Registry::LEGACY_TEMPLATES_OPTION, null);
			add_option(Yoga_Mail_Registry::TEMPLATES_OPTION, is_array($legacy_templates) ? $legacy_templates : array(), '', false);
		}
		if (get_option('yoga_mail_previous_wc_block_editor', null) === null) {
			$legacy_previous = get_option('kundalini_mail_previous_wc_block_editor', null);
			add_option(
				'yoga_mail_previous_wc_block_editor',
				is_string($legacy_previous) ? $legacy_previous : (string) get_option('woocommerce_feature_block_email_editor_enabled', 'no'),
				'',
				false
			);
		}
		update_option('woocommerce_feature_block_email_editor_enabled', 'no', false);
	}

	public static function deactivate(): void {
		$previous = get_option('yoga_mail_previous_wc_block_editor', get_option('kundalini_mail_previous_wc_block_editor', null));
		if (is_string($previous) && in_array($previous, array('yes', 'no'), true)) {
			update_option('woocommerce_feature_block_email_editor_enabled', $previous, false);
		}
	}
}
