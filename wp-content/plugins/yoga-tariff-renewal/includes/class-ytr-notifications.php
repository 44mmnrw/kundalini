<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Notifications {
	public const EXPIRING_HOOK = 'ytr_subscription_expiring_check';
	public const META_RENEWAL_SUCCESS_EMAIL_SENT_AT = '_ytr_renewal_success_email_sent_at';

	private const META_FAILURE_EMAIL_SENT_AT = '_ytr_failure_email_sent_at';
	private const META_EXPIRING_SITE_END      = '_ytr_expiring_site_end';
	private const META_EXPIRING_EMAIL_END     = '_ytr_expiring_email_end';
	private const META_EXPIRING_CANDIDATE_END = '_ytr_expiring_candidate_end';
	private const META_EXPIRING_PRODUCT_NAME  = '_ytr_expiring_product_name';
	private const META_ENDED_EMAIL_END        = '_ytr_ended_email_end';
	private const META_CARD_EXPIRING_SITE     = '_ytr_card_expiring_site';
	private const META_CARD_EXPIRING_EMAIL    = '_ytr_card_expiring_email';
	private const EXPIRING_WINDOW             = 3 * DAY_IN_SECONDS;
	private static $auto_renew_status_cache    = array();

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
	 * Notify users with disabled auto-renew whose active tariff expires within the next three days.
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
				self::maybe_send_ended_notification($user_id);
				self::maybe_send_expiring_notification($user_id);
				self::maybe_send_card_expiring_notification($user_id);
			}

			++$page;
		} while (count($user_ids) === 100);
	}

	private static function maybe_send_card_expiring_notification(int $user_id): void {
		if (!class_exists('YTR_Saved_Cards')) {
			return;
		}

		$cards = YTR_Saved_Cards::get_cards($user_id);
		$card = isset($cards[0]) && is_array($cards[0]) ? $cards[0] : null;
		$selected_id = class_exists('YTR_User') ? YTR_User::get_payment_method_id($user_id) : '';
		if ($selected_id !== '') {
			foreach ($cards as $candidate) {
				if (!is_array($candidate)) {
					continue;
				}
				$candidate_id = (string) ($candidate['payment_method_id'] ?? $candidate['id'] ?? '');
				if ($candidate_id === $selected_id) {
					$card = $candidate;
					break;
				}
			}
		}
		if ($card === null) {
			return;
		}

		$month = (int) ($card['exp_month'] ?? 0);
		$year  = (int) ($card['exp_year'] ?? 0);
		if ($year > 0 && $year < 100) {
			$year += 2000;
		}
		if ($month < 1 || $month > 12 || $year < 2000) {
			return;
		}

		$now            = new DateTimeImmutable('now', wp_timezone());
		$current_period = ((int) $now->format('Y') * 12) + (int) $now->format('n');
		$expiry_period  = ($year * 12) + $month;
		if (
			$expiry_period !== ($current_period - 1)
			&& $expiry_period !== $current_period
			&& $expiry_period !== ($current_period + 1)
		) {
			return;
		}

		$last4 = preg_replace('/\D+/', '', (string) ($card['last4'] ?? ''));
		$token = sprintf('%04d-%02d:%s', $year, $month, $last4);
		$title = __('Проблема с оплатой', 'yoga-tariff-renewal');
		if ($expiry_period === ($current_period + 1)) {
			$message = __('Срок действия вашей привязанной карты истекает в следующем месяце. Пожалуйста, обновите метод оплаты.', 'yoga-tariff-renewal');
		} elseif ($expiry_period === $current_period) {
			$message = __('Срок действия вашей привязанной карты истекает в этом месяце. Пожалуйста, обновите метод оплаты.', 'yoga-tariff-renewal');
		} else {
			$message = __('Срок действия вашей привязанной карты истёк. Пожалуйста, обновите метод оплаты.', 'yoga-tariff-renewal');
		}
		$account_url = function_exists('yoga_get_lk_section_url')
			? yoga_get_lk_section_url('subscription')
			: self::get_account_url();

		$site_enabled = !function_exists('yoga_notification_preference')
			|| yoga_notification_preference($user_id, 'payment_card_expiring_site', true);
		if ($site_enabled && function_exists('yoga_add_user_notification')) {
			yoga_add_user_notification($user_id, 'payment_card_expiring', $title, $message, $account_url, array(
				'dedupe_key' => 'payment_card_expiring:' . $token,
			));
			update_user_meta($user_id, self::META_CARD_EXPIRING_SITE, $token);
		}
		if ($expiry_period < $current_period) {
			return;
		}

		$email_enabled = !function_exists('yoga_notification_preference')
			|| yoga_notification_preference($user_id, 'payment_card_expiring_email', false);
		if (!$email_enabled || (string) get_user_meta($user_id, self::META_CARD_EXPIRING_EMAIL, true) === $token) {
			return;
		}

		$user = get_user_by('id', $user_id);
		if (!$user instanceof WP_User || !is_email($user->user_email)) {
			return;
		}

		$tariff = function_exists('get_current_user_tariff') ? get_current_user_tariff($user_id) : false;
		$subscription_name = is_array($tariff) && !empty($tariff['product_name'])
			? (string) $tariff['product_name']
			: __('Подписка', 'yoga-tariff-renewal');
		$email_title = __('Скоро истечет срок карты', 'yoga-tariff-renewal');
		$email_message = sprintf(
			/* translators: %s: subscription name */
			__('Срок действия карты, привязанной к подписке «%s», скоро истекает. Обновите данные, чтобы оплата прошла без перебоев.', 'yoga-tariff-renewal'),
			$subscription_name
		) . "\n\n" . __('Обновить карту:', 'yoga-tariff-renewal') . "\n" . $account_url;
		$sent = function_exists('yoga_mail_send')
			? yoga_mail_send('payment-card-expiring', array(
				'to' => (string) $user->user_email,
				'subject' => $email_title,
				'content' => nl2br(esc_html($email_message)),
				'data' => array(
					'user_name' => $user->display_name ?: $user->user_login,
					'user_email' => $user->user_email,
					'action_url' => $account_url,
					'subscription_name' => $subscription_name,
					'payment_card' => strlen($last4) >= 4 ? '•• ' . substr($last4, -4) : __('Карта', 'yoga-tariff-renewal'),
					'card_expiry' => sprintf('%02d/%02d', $month, $year % 100),
				),
			))
			: wp_mail((string) $user->user_email, $email_title, $email_message);
		if ($sent) {
			update_user_meta($user_id, self::META_CARD_EXPIRING_EMAIL, $token);
		}
	}

	private static function maybe_send_expiring_notification(int $user_id): void {
		if (self::has_active_auto_renewal($user_id)) {
			return;
		}

		$tariff = get_current_user_tariff($user_id);
		if (!is_array($tariff) || empty($tariff['access_end'])) {
			return;
		}

		$access_end = (int) $tariff['access_end'];
		$remaining  = $access_end - current_time('timestamp');
		if ($remaining <= 0 || $remaining > self::EXPIRING_WINDOW) {
			return;
		}
		update_user_meta($user_id, self::META_EXPIRING_CANDIDATE_END, $access_end);
		update_user_meta(
			$user_id,
			self::META_EXPIRING_PRODUCT_NAME,
			!empty($tariff['product_name']) ? (string) $tariff['product_name'] : __('Подписка', 'yoga-tariff-renewal')
		);

		$end_date = wp_date('j F Y', $access_end, wp_timezone());
		$title    = __('Подписка скоро заканчивается', 'yoga-tariff-renewal');
		$message  = sprintf(
			/* translators: %s: subscription expiration date */
			__('Ваша подписка действует до %s. Продлите её, чтобы сохранить доступ к практикам.', 'yoga-tariff-renewal'),
			$end_date
		);
		$account_url = self::get_subscription_url();

		$site_enabled = !function_exists('yoga_notification_preference')
			|| yoga_notification_preference($user_id, 'subscription_expiring_site', true);
		if ($site_enabled && function_exists('yoga_add_user_notification')) {
			yoga_add_user_notification($user_id, 'subscription_expiring', $title, $message, $account_url, array(
				'dedupe_key' => 'subscription_expiring:' . $access_end,
			));
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
		$sent = function_exists('yoga_mail_send')
			? yoga_mail_send('subscription-expiring', array(
				'to' => (string) $user->user_email,
				'subject' => $title,
				'content' => nl2br(esc_html($email_message)),
				'data' => array(
					'user_name' => $user->display_name ?: $user->user_login,
					'user_email' => $user->user_email,
					'action_url' => $account_url,
					'subscription_name' => !empty($tariff['product_name']) ? (string) $tariff['product_name'] : __('Подписка', 'yoga-tariff-renewal'),
					'expiration_date' => $end_date,
				),
			))
			: wp_mail((string) $user->user_email, $title, $email_message);
		if ($sent) {
			update_user_meta($user_id, self::META_EXPIRING_EMAIL_END, $access_end);
		}
	}

	private static function maybe_send_ended_notification(int $user_id): void {
		if (self::has_active_auto_renewal($user_id)) {
			return;
		}
		if (function_exists('get_current_user_tariff') && is_array(get_current_user_tariff($user_id))) {
			return;
		}

		$email_enabled = !function_exists('yoga_notification_preference')
			|| yoga_notification_preference($user_id, 'subscription_ended_email', true);
		if (!$email_enabled) {
			return;
		}

		$access_end = (int) get_user_meta($user_id, self::META_EXPIRING_CANDIDATE_END, true);
		if (
			$access_end <= 0
			|| $access_end > current_time('timestamp')
			|| (int) get_user_meta($user_id, self::META_ENDED_EMAIL_END, true) === $access_end
		) {
			return;
		}

		$user = get_user_by('id', $user_id);
		if (!$user instanceof WP_User || !is_email($user->user_email)) {
			return;
		}

		$subscription_name = trim((string) get_user_meta($user_id, self::META_EXPIRING_PRODUCT_NAME, true));
		if ($subscription_name === '' && class_exists('YTR_User') && function_exists('wc_get_product')) {
			$product = wc_get_product(YTR_User::get_tariff_product_id($user_id));
			if ($product) {
				$subscription_name = (string) $product->get_name();
			}
		}
		if ($subscription_name === '') {
			$subscription_name = __('Подписка', 'yoga-tariff-renewal');
		}

		$end_date = wp_date('j F Y', $access_end, wp_timezone());
		$account_url = self::get_subscription_url();
		$subject = __('Подписка завершилась', 'yoga-tariff-renewal');
		$message = sprintf(
			/* translators: 1: subscription name, 2: expiration date */
			__('Подписка «%1$s» завершилась. Доступ был активен до %2$s. Ваши данные и история сохранены.', 'yoga-tariff-renewal'),
			$subscription_name,
			$end_date
		);

		$sent = function_exists('yoga_mail_send')
			? yoga_mail_send('subscription-ended', array(
				'to' => (string) $user->user_email,
				'subject' => $subject,
				'content' => nl2br(esc_html($message)),
				'data' => array(
					'user_name' => $user->display_name ?: $user->user_login,
					'user_email' => $user->user_email,
					'subscription_name' => $subscription_name,
					'expiration_date' => $end_date,
					'action_url' => $account_url,
				),
			))
			: wp_mail((string) $user->user_email, $subject, $message . "\n\n" . $account_url);

		if ($sent) {
			update_user_meta($user_id, self::META_ENDED_EMAIL_END, $access_end);
		}
	}

	/**
	 * Проверяет фактическое автопродление и восстанавливает служебные meta,
	 * если recurring-карта уже сохранена, но cron ещё не видел её в профиле.
	 */
	private static function has_active_auto_renewal(int $user_id): bool {
		if ($user_id <= 0 || !class_exists('YTR_User')) {
			return false;
		}
		if (array_key_exists($user_id, self::$auto_renew_status_cache)) {
			return self::$auto_renew_status_cache[$user_id];
		}

		if (class_exists('YTR_LK') && method_exists('YTR_LK', 'maybe_backfill_auto_renew')) {
			YTR_LK::maybe_backfill_auto_renew($user_id);
		}

		if (class_exists('YTR_LK') && method_exists('YTR_LK', 'user_has_renewable_payment_setup')) {
			self::$auto_renew_status_cache[$user_id] = YTR_LK::user_has_renewable_payment_setup($user_id);
		} else {
			self::$auto_renew_status_cache[$user_id] = YTR_User::is_auto_renew_enabled($user_id);
		}

		return self::$auto_renew_status_cache[$user_id];
	}

	public static function send_renewal_success(WC_Order $order): bool {
		if (
			(string) $order->get_meta('_ytr_renewal') !== 'yes'
			|| (string) $order->get_meta(self::META_RENEWAL_SUCCESS_EMAIL_SENT_AT) !== ''
		) {
			return false;
		}

		$email = (string) $order->get_billing_email();
		$user_id = (int) $order->get_customer_id();
		$user = $user_id > 0 ? get_user_by('id', $user_id) : false;
		if ($email === '' && $user instanceof WP_User) {
			$email = (string) $user->user_email;
		}
		if ($email === '' || !is_email($email)) {
			return false;
		}

		$user_name = $user instanceof WP_User
			? (string) ($user->display_name ?: $user->user_login)
			: trim((string) $order->get_formatted_billing_full_name());
		$subject = __('Подписка автоматически продлена', 'yoga-tariff-renewal');
		$message = sprintf(
			/* translators: %s: subscription name */
			__('Оплата прошла. Подписка «%s» автоматически продлена, доступ ко всем практикам сохраняется.', 'yoga-tariff-renewal'),
			self::get_subscription_name($order)
		);
		$receipt_url = (string) $order->get_view_order_url();

		$sent = function_exists('yoga_mail_send')
			? yoga_mail_send('renewal-success', array(
				'to' => $email,
				'subject' => $subject,
				'content' => nl2br(esc_html($message)),
				'data' => array(
					'user_name' => $user_name !== '' ? $user_name : __('Практик', 'yoga-tariff-renewal'),
					'user_email' => $email,
					'subscription_name' => self::get_subscription_name($order),
					'total_amount' => self::format_order_total($order),
					'payment_card' => self::get_payment_card_label($user_id),
					'next_charge_date' => self::get_next_charge_date($order, $user_id),
					'action_url' => $receipt_url,
				),
			))
			: wp_mail($email, $subject, $message . "\n\n" . $receipt_url);

		if ($sent) {
			$order->update_meta_data(self::META_RENEWAL_SUCCESS_EMAIL_SENT_AT, (string) time());
			$order->add_order_note(__('Автопродление: пользователю отправлено письмо об успешной оплате.', 'yoga-tariff-renewal'));
			$order->save();
		}

		return $sent;
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
		$user_id = (int) $order->get_customer_id();
		$sent = function_exists('yoga_mail_send')
			? yoga_mail_send('renewal-failed', array(
				'to' => $email,
				'subject' => $subject,
				'content' => nl2br(esc_html($message)),
				'data' => array(
					'user_email' => $email,
					'action_url' => self::get_subscription_url(),
					'order_number' => (string) $order->get_order_number(),
					'order_url' => (string) $order->get_view_order_url(),
					'subscription_name' => self::get_subscription_name($order),
					'total_amount' => self::format_order_total($order),
					'payment_card' => self::get_payment_card_label($user_id),
					'next_attempt_date' => self::get_next_attempt_date($user_id),
				),
			))
			: wp_mail($email, $subject, $message);

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

	private static function get_subscription_name(WC_Order $order): string {
		$names = array();
		foreach ($order->get_items('line_item') as $item) {
			$name = trim((string) $item->get_name());
			if ($name !== '') {
				$names[] = $name;
			}
		}
		return $names !== array() ? implode(', ', $names) : __('Подписка', 'yoga-tariff-renewal');
	}

	private static function format_order_total(WC_Order $order): string {
		if (function_exists('wc_price')) {
			$formatted = wc_price((float) $order->get_total(), array('currency' => (string) $order->get_currency()));
			return trim(html_entity_decode(wp_strip_all_tags($formatted), ENT_QUOTES, 'UTF-8'));
		}
		return number_format_i18n((float) $order->get_total(), 0) . ' ' . (string) $order->get_currency();
	}

	private static function get_payment_card_label(int $user_id): string {
		if ($user_id <= 0 || !class_exists('YTR_Saved_Cards')) {
			return __('Карта', 'yoga-tariff-renewal');
		}

		$selected_id = class_exists('YTR_User') ? YTR_User::get_payment_method_id($user_id) : '';
		$cards = YTR_Saved_Cards::get_cards($user_id);
		$selected = array();
		foreach ($cards as $card) {
			if (!is_array($card)) {
				continue;
			}
			if ($selected === array()) {
				$selected = $card;
			}
			$card_id = (string) ($card['payment_method_id'] ?? $card['id'] ?? '');
			if ($selected_id !== '' && $card_id === $selected_id) {
				$selected = $card;
				break;
			}
		}

		$last4 = preg_replace('/\D+/', '', (string) ($selected['last4'] ?? ''));
		return strlen($last4) >= 4 ? '•• ' . substr($last4, -4) : __('Карта', 'yoga-tariff-renewal');
	}

	private static function get_next_attempt_date(int $user_id): string {
		$last_attempt = class_exists('YTR_User') ? YTR_User::get_last_renewal_attempt($user_id) : time();
		if ($last_attempt <= 0) {
			$last_attempt = time();
		}
		$interval = class_exists('YTR_Renewal') ? YTR_Renewal::get_retry_interval_minutes() : DAY_IN_SECONDS / MINUTE_IN_SECONDS;
		return wp_date('j F Y', $last_attempt + max(1, (int) $interval) * MINUTE_IN_SECONDS);
	}

	private static function get_next_charge_date(WC_Order $order, int $user_id): string {
		$next_charge = class_exists('YTR_Tariff') ? YTR_Tariff::get_access_end_timestamp($user_id) : 0;
		if ($next_charge <= 0) {
			$paid_at = $order->get_date_paid() ?: $order->get_date_created();
			if ($paid_at) {
				$date = new DateTimeImmutable('@' . $paid_at->getTimestamp());
				$next_charge = $date->setTimezone(wp_timezone())->modify('+1 month')->getTimestamp();
			}
		}
		return $next_charge > 0 ? wp_date('j F Y', $next_charge, wp_timezone()) : __('Через месяц', 'yoga-tariff-renewal');
	}

	private static function get_subscription_url(): string {
		if (function_exists('yoga_get_lk_section_url')) {
			$url = (string) yoga_get_lk_section_url('subscription');
			if ($url !== '') {
				return $url;
			}
		}
		return self::get_account_url();
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
