<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Notifications {
	private const META_FAILURE_EMAIL_SENT_AT = '_ytr_failure_email_sent_at';

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
		$url = '';
		if (function_exists('wc_get_page_permalink')) {
			$url = (string) wc_get_page_permalink('myaccount');
		}

		return $url !== '' ? $url : home_url('/my-account/');
	}
}
