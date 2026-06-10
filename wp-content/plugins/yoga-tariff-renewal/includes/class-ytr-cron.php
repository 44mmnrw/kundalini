<?php



if (!defined('ABSPATH')) {

	exit;

}



final class YTR_Cron {

	public const HOOK = 'ytr_daily_renewal_check';

	public const INTERVAL = 'ytr_hourly_renewal';



	public static function init(): void {

		add_action(self::HOOK, array(__CLASS__, 'run'));

		add_filter('cron_schedules', array(__CLASS__, 'add_schedules'));

		add_action('init', array(__CLASS__, 'ensure_scheduled'), 20);

	}



	/**

	 * @param array<string, array<string, int|string>> $schedules

	 * @return array<string, array<string, int|string>>

	 */

	public static function add_schedules(array $schedules): array {

		$schedules[self::INTERVAL] = array(

			'interval' => HOUR_IN_SECONDS,

			'display'  => 'Yoga Tariff Renewal (hourly)',

		);



		return $schedules;

	}



	public static function ensure_scheduled(): void {

		if (!self::is_enabled()) {

			return;

		}



		if (!wp_next_scheduled(self::HOOK)) {

			wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, self::INTERVAL, self::HOOK);

		}

	}



	public static function schedule(): void {

		wp_clear_scheduled_hook(self::HOOK);

		wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, self::INTERVAL, self::HOOK);

	}



	public static function is_enabled(): bool {

		return class_exists('YTR_Renewal') && YTR_Renewal::is_enabled();

	}



	public static function run(): void {

		$stats = YTR_Renewal::process_due_renewals();



		if (class_exists('YooKassaLogger')) {

			YooKassaLogger::info('YTR renewal cron: ' . wp_json_encode($stats));

		}

	}

}


