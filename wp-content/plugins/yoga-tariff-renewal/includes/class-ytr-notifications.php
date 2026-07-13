<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Notifications {
	public const EXPIRING_HOOK = 'ytr_subscription_expiring_check';

	private const META_FAILURE_EMAIL_SENT_AT = '_ytr_failure_email_sent_at';
	private const META_EXPIRING_SITE_END      = '_ytr_expiring_site_end';
	private const META_EXPIRING_EMAIL_END     = '_ytr_expiring_email_end';
	private const EXPIRING_WINDOW             = 3 * DAY_IN_SECONDS;

	public static function init(): void {
		add_action(self::EXPIRING_HOOK, array(__CLASS__, 'send_expiring_notifications'));
		add_action('init', array(__CLASS__, 'schedule_expiring_check'), 30);
	}

	public static function schedule_expiring_check(): void {
		if (!wp_next_scheduled(self::EXPIRING_HOOK)) {
			wp_schedule_event(time() + MINUTE_IN_SECONDS, 'daily', self::EXPIRING_HOOK);
		}
	}

	/**
	 * Notify every user whose active tariff expires within the next three days.
	 * The access end timestamp is stored per channel so repeated cron runs are idempotent.
	 */
	public static function send_expiring_notifications(): void {
		if (!function_exists('get_current_user_tariff')) {
			return;
		}

		$page = 1;
		do {
			$query = new WP_User_Query(array(
				'fields' => 'ID',
				'number' => 100,
				'paged'  => $page,
				'orderby' => 'ID',
				'order'   => 'ASC',
			));
			$user_ids = array_map('intval', $query->get_results());

			foreach ($user_ids as $user_id) {
				self::maybe_send_expiring_notification($user_id);
			}

			++$page;
		} while (count($user_ids) === 100);
	}

	private static function maybe_send_expiring_notification(int $user_id): void {
		$tariff = get_current_user_tariff($user_id);
		if (!is_array($tariff) || empty($tariff['access_end'])) {
			return;
		}

		$access_end = (int) $tariff['access_end'];
		$remaining  = $access_end - current_time('timestamp');
		if ($remaining <= 0 || $remaining > self::EXPIRING_WINDOW) {
			return;
		}

		$end_date = wp_date('d.m.Y', $access_end, wp_timezone());
		$title    = __('Подписка скоро заканчивается', 'yoga-tariff-renewal');
		$message  = sprintf(
			/* translators: %s: subscription expiration date */
			__('Ваша подписка действует до %s. Продлите её, чтобы сохранить доступ к практикам.', 'yoga-tariff-renewal'),
			$end_date
		);
		$account_url = self::get_account_url();

		$site_enabled = !function_exists('yoga_notification_preference')
			|| yoga_notification_preference($user_id, 'subscription_expiring_site', true);
		if ($site_enabled
			&& (int) get_user_meta($user_id, self::META_EXPIRING_SITE_END, true) !== $access_end
			&& function_exists('yoga_add_user_notification')) {
			yoga_add_user_notification($user_id, 'subscription_expiring', $title, $message, $account_url);
			update_user_meta($user_id, self::META_EXPIRING_SITE_END, $access_end);
		}

		$email_enabled = !function_exists('yoga_notification_preference')
			|| yoga_notification_preference($user_id, 'subscription_expiring_email', true);
		if (!$email_enabled || (int) get_user_meta($user_id, self::META_EXPIRING_EMAIL_END, true) === $access_end) {
			return;
		}

		$user = get_user_by('id', $user_id);
		if (!$user instanceof WP_User || !is_email($user->user_email)) {
			return;
		}

		$email_message = $message . "\n\n" . __('Личный кабинет:', 'yoga-tariff-renewal') . "\n" . $account_url;
		if (wp_mail((string) $user->user_email, $title, $email_message)) {
			update_user_meta($user_id, self::META_EXPIRING_EMAIL_END, $access_end);
		}
	}

	public static function send_renewal_failure(WC_Order $order, string $reason): bool {
		if ($order->get_meta(self::META_FAILURE_EMAIL_SENT_AT) !== '') {
			return false;
		}

		$email = (string) $order->get_billing_email();
		if ($email === '') {
			$user = get_user_by('id', (int) $order->get_customer_id());
			if ($user instanceof WP_User) {
				$email = (string) $user->user_email;
			}
		}

		if ($email === '' || !is_email($email)) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: site name */
			__('Не удалось продлить подписку на сайте %s', 'yoga-tariff-renewal'),
			wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)
		);

		$message = self::build_failure_message($order, $reason);
		$sent    = wp_mail($email, $subject, $message);

		if ($sent) {
			$order->update_meta_data(self::META_FAILURE_EMAIL_SENT_AT, (string) time());
			$order->add_order_note(__('Автопродление: пользователю отправлено письмо о неудачном списании.', 'yoga-tariff-renewal'));
			$order->save();
		}

		return $sent;
	}

	private static function build_failure_message(WC_Order $order, string $reason): string {
		$lines = array(
			sprintf(
				/* translators: %s: site name */
				__('Здравствуйте! Не удалось автоматически продлить подписку на сайте %s.', 'yoga-tariff-renewal'),
				wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)
			),
			'',
			sprintf(
				/* translators: %d: order id */
				__('Заказ: #%d', 'yoga-tariff-renewal'),
				$order->get_id()
			),
			sprintf(
				/* translators: %s: payment error */
				__('Причина: %s', 'yoga-tariff-renewal'),
				$reason !== '' ? $reason : __('платеж не был подтвержден платежной системой', 'yoga-tariff-renewal')
			),
			'',
			__('Пожалуйста, проверьте карту или оплатите тариф заново в личном кабинете.', 'yoga-tariff-renewal'),
			self::get_account_url(),
		);

		return implode("\n", $lines);
	}

	private static function get_account_url(): string {
		if (function_exists('yoga_get_lk_page_url')) {
			$url = (string) yoga_get_lk_page_url();
			if ($url !== '') {
				return $url;
			}
		}

		$url = '';
		if (function_exists('wc_get_page_permalink')) {
			$url = (string) wc_get_page_permalink('myaccount');
		}

		return $url !== '' ? $url : home_url('/my-account/');
	}
}
