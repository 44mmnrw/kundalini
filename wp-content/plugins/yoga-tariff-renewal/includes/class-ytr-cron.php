<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Cron {
	public const HOOK = 'ytr_daily_renewal_check';

	public const OPTION_INTERVAL   = 'ytr_cron_interval';
	public const OPTION_LAST_RUN   = 'ytr_cron_last_run';
	public const OPTION_LAST_STATS = 'ytr_cron_last_stats';
	public const OPTION_LAST_SOURCE = 'ytr_cron_last_source';

	public static function init(): void {
		add_action(self::HOOK, array(__CLASS__, 'run'));
		add_filter('cron_schedules', array(__CLASS__, 'add_schedules'));
		add_action('init', array(__CLASS__, 'sync_schedule'), 20);

		add_action('update_option_' . self::OPTION_INTERVAL, array(__CLASS__, 'reschedule'));
		add_action('update_option_ytr_enabled', array(__CLASS__, 'handle_enabled_change'), 10, 2);
	}

	/**
	 * @return array<string, array{interval:int,label:string}>
	 */
	public static function get_interval_definitions(): array {
		return array(
			'5'    => array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'label'    => __('Каждые 5 минут', 'yoga-tariff-renewal'),
			),
			'15'   => array(
				'interval' => 15 * MINUTE_IN_SECONDS,
				'label'    => __('Каждые 15 минут', 'yoga-tariff-renewal'),
			),
			'30'   => array(
				'interval' => 30 * MINUTE_IN_SECONDS,
				'label'    => __('Каждые 30 минут', 'yoga-tariff-renewal'),
			),
			'60'   => array(
				'interval' => HOUR_IN_SECONDS,
				'label'    => __('Каждый час', 'yoga-tariff-renewal'),
			),
			'360'  => array(
				'interval' => 6 * HOUR_IN_SECONDS,
				'label'    => __('Каждые 6 часов', 'yoga-tariff-renewal'),
			),
			'720'  => array(
				'interval' => 12 * HOUR_IN_SECONDS,
				'label'    => __('Каждые 12 часов', 'yoga-tariff-renewal'),
			),
			'1440' => array(
				'interval' => DAY_IN_SECONDS,
				'label'    => __('Раз в сутки', 'yoga-tariff-renewal'),
			),
		);
	}

	public static function sanitize_interval($value): string {
		$value = (string) $value;
		$defs  = self::get_interval_definitions();

		return isset($defs[$value]) ? $value : '60';
	}

	public static function get_interval_key(): string {
		return self::sanitize_interval(get_option(self::OPTION_INTERVAL, '60'));
	}

	public static function get_interval_seconds(): int {
		$defs = self::get_interval_definitions();
		$key  = self::get_interval_key();

		return (int) ($defs[$key]['interval'] ?? HOUR_IN_SECONDS);
	}

	public static function get_interval_label(): string {
		$defs = self::get_interval_definitions();
		$key  = self::get_interval_key();

		return (string) ($defs[$key]['label'] ?? $defs['60']['label']);
	}

	public static function get_schedule_name(): string {
		return 'ytr_renewal_' . self::get_interval_key();
	}

	/**
	 * @param array<string, array<string, int|string>> $schedules
	 * @return array<string, array<string, int|string>>
	 */
	public static function add_schedules(array $schedules): array {
		foreach (self::get_interval_definitions() as $key => $definition) {
			$schedules['ytr_renewal_' . $key] = array(
				'interval' => (int) $definition['interval'],
				'display'  => 'Yoga Tariff Renewal (' . $key . ' min)',
			);
		}

		return $schedules;
	}

	public static function sync_schedule(): void {
		if (!self::is_enabled()) {
			if (wp_next_scheduled(self::HOOK)) {
				wp_clear_scheduled_hook(self::HOOK);
			}
			return;
		}

		$event         = function_exists('wp_get_scheduled_event') ? wp_get_scheduled_event(self::HOOK) : null;
		$schedule_name = self::get_schedule_name();

		if (!$event || (string) ($event->schedule ?? '') !== $schedule_name) {
			self::reschedule();
		}
	}

	public static function schedule(): void {
		self::reschedule();
	}

	public static function reschedule(): void {
		wp_clear_scheduled_hook(self::HOOK);

		if (!self::is_enabled()) {
			return;
		}

		wp_schedule_event(time() + MINUTE_IN_SECONDS, self::get_schedule_name(), self::HOOK);
	}

	/**
	 * @param mixed $old_value
	 * @param mixed $new_value
	 */
	public static function handle_enabled_change($old_value, $new_value): void {
		if ((string) $new_value === 'yes') {
			self::reschedule();
			return;
		}

		wp_clear_scheduled_hook(self::HOOK);
	}

	public static function is_enabled(): bool {
		return class_exists('YTR_Renewal') && YTR_Renewal::is_enabled();
	}

	public static function run(): void {
		$stats = YTR_Renewal::process_due_renewals();
		self::record_run($stats, 'cron');

		if (class_exists('YooKassaLogger')) {
			YooKassaLogger::info('YTR renewal cron: ' . wp_json_encode($stats));
		}
	}

	/**
	 * @param array{processed:int,succeeded:int,failed:int,skipped:int} $stats
	 */
	public static function record_run(array $stats, string $source = 'cron'): void {
		update_option(self::OPTION_LAST_RUN, time(), false);
		update_option(self::OPTION_LAST_STATS, $stats, false);
		update_option(self::OPTION_LAST_SOURCE, sanitize_key($source), false);
	}

	/**
	 * @return array{
	 *     wp_cron_disabled:bool,
	 *     alternate_wp_cron:bool,
	 *     is_scheduled:bool,
	 *     schedule_name:string,
	 *     next_run:int,
	 *     last_run:int,
	 *     last_source:string,
	 *     last_stats:array<string,int>,
	 *     interval_seconds:int,
	 *     interval_label:string,
	 *     is_overdue:bool,
	 *     is_stale:bool,
	 *     status:string,
	 *     status_label:string,
	 *     status_message:string
	 * }
	 */
	public static function get_health(): array {
		$now              = time();
		$interval         = self::get_interval_seconds();
		$next_run         = (int) wp_next_scheduled(self::HOOK);
		$last_run         = (int) get_option(self::OPTION_LAST_RUN, 0);
		$last_stats       = get_option(self::OPTION_LAST_STATS, array());
		$last_source      = (string) get_option(self::OPTION_LAST_SOURCE, '');
		$wp_cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
		$is_scheduled     = $next_run > 0;
		// wp_next_scheduled() хранит UTC unix time — сравниваем только с time(), не с current_time().
		$overdue_grace    = max(2 * MINUTE_IN_SECONDS, (int) min($interval / 4, 15 * MINUTE_IN_SECONDS));
		$is_overdue       = $is_scheduled && $next_run < ($now - $overdue_grace);
		$is_stale         = $last_run <= 0 || ($now - $last_run) > ($interval * 2);
		$plugin_enabled   = self::is_enabled();

		if (!is_array($last_stats)) {
			$last_stats = array();
		}

		$status        = 'ok';
		$status_label  = __('Работает', 'yoga-tariff-renewal');
		$status_message = __('WP Cron запускает проверку продлений в ожидаемом интервале.', 'yoga-tariff-renewal');

		if (!$plugin_enabled) {
			$status         = 'disabled';
			$status_label   = __('Отключено', 'yoga-tariff-renewal');
			$status_message = __('Автопродление выключено в настройках плагина.', 'yoga-tariff-renewal');
		} elseif (!$is_scheduled) {
			$status         = 'error';
			$status_label   = __('Не запланировано', 'yoga-tariff-renewal');
			$status_message = __('Событие cron не найдено. Сохраните настройки или переактивируйте плагин.', 'yoga-tariff-renewal');
		} elseif ($is_overdue && $is_stale) {
			$status         = 'error';
			$status_label   = __('Не запускается', 'yoga-tariff-renewal');
			$status_message = $wp_cron_disabled
				? __('Следующий запуск просрочен, а последний успешный запуск давно не был зафиксирован. Настройте системный cron для wp-cron.php.', 'yoga-tariff-renewal')
				: __('Следующий запуск просрочен. WP Cron, вероятно, не срабатывает на сервере (нет трафика или блокировка loopback).', 'yoga-tariff-renewal');
		} elseif ($is_overdue) {
			$status         = 'warning';
			$status_label   = __('Ожидает запуска', 'yoga-tariff-renewal');
			$status_message = __('Время запуска прошло, но wp-cron.php ещё не обработал очередь. Это нормально на 2–15 минут между системными cron. Если «просрочено» держится часами — проверьте crontab.', 'yoga-tariff-renewal');
		} elseif ($is_stale && $last_run <= 0) {
			$status         = 'warning';
			$status_label   = __('Ещё не запускался', 'yoga-tariff-renewal');
			$status_message = __('После включения или смены интервала cron ещё ни разу не выполнялся.', 'yoga-tariff-renewal');
		} elseif ($is_stale) {
			$status         = 'warning';
			$status_label   = __('Давно не запускался', 'yoga-tariff-renewal');
			$status_message = __('Последний запуск был слишком давно относительно выбранного интервала.', 'yoga-tariff-renewal');
		} elseif ($wp_cron_disabled) {
			$status         = 'ok';
			$status_label   = __('Системный cron', 'yoga-tariff-renewal');
			$status_message = __('DISABLE_WP_CRON включён — это нормально, если wp-cron.php вызывается системным cron.', 'yoga-tariff-renewal');
		}

		return array(
			'wp_cron_disabled'  => $wp_cron_disabled,
			'alternate_wp_cron' => defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON,
			'is_scheduled'      => $is_scheduled,
			'schedule_name'     => self::get_schedule_name(),
			'next_run'          => $next_run,
			'last_run'          => $last_run,
			'last_source'       => $last_source,
			'last_stats'        => $last_stats,
			'interval_seconds'  => $interval,
			'interval_label'    => self::get_interval_label(),
			'is_overdue'        => $is_overdue,
			'is_stale'          => $is_stale,
			'status'            => $status,
			'status_label'      => $status_label,
			'status_message'    => $status_message,
		);
	}

	public static function format_timestamp(int $timestamp): string {
		if ($timestamp <= 0) {
			return '—';
		}

		return wp_date('d.m.Y H:i:s', $timestamp);
	}

	public static function format_ago(int $timestamp): string {
		if ($timestamp <= 0) {
			return '—';
		}

		return human_time_diff($timestamp, time()) . ' ' . __('назад', 'yoga-tariff-renewal');
	}

	public static function get_wp_cron_php_path(): string {
		return trailingslashit(ABSPATH) . 'wp-cron.php';
	}

	public static function get_wp_cron_url(): string {
		return site_url('wp-cron.php?doing_wp_cron');
	}

	/**
	 * @return array{php:string,curl:string}
	 */
	public static function get_crontab_examples(): array {
		$php_path = self::get_wp_cron_php_path();
		$cron_url = self::get_wp_cron_url();

		return array(
			'php'  => '*/5 * * * * cd ' . untrailingslashit(ABSPATH) . ' && php wp-cron.php >/dev/null 2>&1',
			'curl' => '*/5 * * * * curl -s ' . $cron_url . ' >/dev/null 2>&1',
		);
	}
}
