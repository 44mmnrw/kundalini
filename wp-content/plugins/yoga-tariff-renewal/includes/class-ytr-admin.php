<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Admin {
	public static function init(): void {
		add_action('admin_menu', array(__CLASS__, 'register_menu'));
		add_action('admin_init', array(__CLASS__, 'register_settings'));
		add_action('admin_post_ytr_run_renewals', array(__CLASS__, 'handle_manual_run'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
	}

	public static function enqueue_assets(string $hook): void {
		if ($hook !== 'woocommerce_page_ytr-settings') {
			return;
		}

		wp_register_style('ytr-admin', false, array(), YTR_VERSION);
		wp_enqueue_style('ytr-admin');
		wp_add_inline_style(
			'ytr-admin',
			'.ytr-cron-status{margin:1em 0;padding:1em 1.25em;border-left:4px solid #72aee6;background:#fff}.ytr-cron-status--ok{border-left-color:#00a32a}.ytr-cron-status--warning{border-left-color:#dba617}.ytr-cron-status--error{border-left-color:#d63638}.ytr-cron-status--disabled{border-left-color:#a7aaad}.ytr-cron-status__title{margin:0 0 .5em;font-size:14px}.ytr-cron-status__badge{display:inline-block;padding:.15em .55em;border-radius:999px;font-size:12px;font-weight:600;line-height:1.5}.ytr-cron-status__badge--ok{background:#edfaef;color:#007017}.ytr-cron-status__badge--warning{background:#fcf9e8;color:#8a6d00}.ytr-cron-status__badge--error{background:#fcf0f1;color:#8a2424}.ytr-cron-status__badge--disabled{background:#f0f0f1;color:#50575e}.ytr-cron-table{margin-top:1em}.ytr-cron-table th{width:260px;font-weight:600}'
		);
	}

	public static function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__('Автопродление тарифов', 'yoga-tariff-renewal'),
			__('Автопродление', 'yoga-tariff-renewal'),
			'manage_woocommerce',
			'ytr-settings',
			array(__CLASS__, 'render_page')
		);
	}

	public static function register_settings(): void {
		register_setting('ytr_settings', 'ytr_enabled', array('type' => 'string', 'default' => 'yes'));
		register_setting('ytr_settings', 'ytr_days_before', array('type' => 'integer', 'default' => 1));
		register_setting('ytr_settings', 'ytr_max_retry_days', array('type' => 'integer', 'default' => 7));
		register_setting(
			'ytr_settings',
			YTR_Stub::OPTION,
			array(
				'type'              => 'string',
				'default'           => 'no',
				'sanitize_callback' => static function ($value): string {
					return $value === 'yes' ? 'yes' : 'no';
				},
			)
		);
		register_setting(
			'ytr_settings',
			'ytr_card_bind_amount',
			array(
				'type'              => 'number',
				'default'           => 1,
				'sanitize_callback' => static function ($value): float {
					return max(1.0, round((float) $value, 2));
				},
			)
		);
		register_setting(
			'ytr_settings',
			YTR_Cron::OPTION_INTERVAL,
			array(
				'type'              => 'string',
				'default'           => '60',
				'sanitize_callback' => array('YTR_Cron', 'sanitize_interval'),
			)
		);
	}

	public static function handle_manual_run(): void {
		if (!current_user_can('manage_woocommerce')) {
			wp_die('Forbidden');
		}

		check_admin_referer('ytr_run_renewals');

		$stats = YTR_Renewal::process_due_renewals();
		YTR_Cron::record_run($stats, 'manual');

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'ytr-settings',
					'ytr_ran'   => '1',
					'processed' => $stats['processed'],
					'succeeded' => $stats['succeeded'],
					'failed'    => $stats['failed'],
					'skipped'   => $stats['skipped'],
				),
				admin_url('admin.php')
			)
		);
		exit;
	}

	public static function render_page(): void {
		if (!current_user_can('manage_woocommerce')) {
			return;
		}

		$health     = YTR_Cron::get_health();
		$cron_url   = YTR_Cron::get_wp_cron_url();
		$cron_php   = YTR_Cron::get_wp_cron_php_path();
		$crontab    = YTR_Cron::get_crontab_examples();
		$auto_users = class_exists('YTR_User') ? YTR_User::get_auto_renew_user_ids() : array();
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Автопродление тарифов (ЮKassa)', 'yoga-tariff-renewal'); ?></h1>

			<?php if (!empty($_GET['ytr_ran'])) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						printf(
							esc_html__('Запуск завершён: обработано %1$d, успешно %2$d, ошибок %3$d, пропущено %4$d.', 'yoga-tariff-renewal'),
							(int) ($_GET['processed'] ?? 0),
							(int) ($_GET['succeeded'] ?? 0),
							(int) ($_GET['failed'] ?? 0),
							(int) ($_GET['skipped'] ?? 0)
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php self::render_cron_status($health, $cron_url, $cron_php, $crontab); ?>

			<?php if (class_exists('YTR_Stub') && YTR_Stub::is_enabled()) : ?>
				<div class="notice notice-error">
					<p>
						<strong><?php esc_html_e('Включён режим заглушки', 'yoga-tariff-renewal'); ?></strong>
						<?php esc_html_e('Привязка карты и автопродление имитируются без ЮKassa save_payment_method. Отключите после подключения автоплатежей в ЮKassa.', 'yoga-tariff-renewal'); ?>
					</p>
				</div>
			<?php endif; ?>

			<p>
				<?php
				printf(
					esc_html__('Пользователей с автопродлением: %d. ЮKassa: %s.', 'yoga-tariff-renewal'),
					count($auto_users),
					class_exists('YTR_YooKassa') && YTR_YooKassa::is_configured() ? 'OK' : esc_html__('не настроена', 'yoga-tariff-renewal')
				);
				?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields('ytr_settings'); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e('Включено', 'yoga-tariff-renewal'); ?></th>
						<td>
							<label>
								<input type="checkbox" name="ytr_enabled" value="yes" <?php checked(get_option('ytr_enabled', 'yes'), 'yes'); ?>>
								<?php esc_html_e('Автоматически продлевать тарифы', 'yoga-tariff-renewal'); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e('Режим заглушки', 'yoga-tariff-renewal'); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr(YTR_Stub::OPTION); ?>" value="yes" <?php checked(get_option(YTR_Stub::OPTION, 'no'), 'yes'); ?>>
								<?php esc_html_e('Имитировать привязку карты и автопродление (для верификации до подключения автоплатежей ЮKassa)', 'yoga-tariff-renewal'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Не обращается к ЮKassa с save_payment_method. Сохраняет тестовую карту Visa •••• 4242 и включает автопродление в ЛК. Снимите галочку после одобрения рекуррентов.', 'yoga-tariff-renewal'); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e('Частота проверки cron', 'yoga-tariff-renewal'); ?></th>
						<td>
							<select name="<?php echo esc_attr(YTR_Cron::OPTION_INTERVAL); ?>">
								<?php foreach (YTR_Cron::get_interval_definitions() as $key => $definition) : ?>
									<option value="<?php echo esc_attr($key); ?>" <?php selected(YTR_Cron::get_interval_key(), $key); ?>>
										<?php echo esc_html($definition['label']); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e('Как часто плагин проверяет пользователей, которым пора продлить тариф. После сохранения расписание пересоздаётся автоматически.', 'yoga-tariff-renewal'); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e('За сколько дней до окончания', 'yoga-tariff-renewal'); ?></th>
						<td>
							<input type="number" min="0" max="14" name="ytr_days_before" value="<?php echo esc_attr((string) get_option('ytr_days_before', 1)); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e('Повторы после истечения (дней)', 'yoga-tariff-renewal'); ?></th>
						<td>
							<input type="number" min="1" max="30" name="ytr_max_retry_days" value="<?php echo esc_attr((string) get_option('ytr_max_retry_days', 7)); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e('Сумма привязки карты (₽)', 'yoga-tariff-renewal'); ?></th>
						<td>
							<input type="number" min="1" max="100" step="0.01" name="ytr_card_bind_amount" value="<?php echo esc_attr((string) get_option('ytr_card_bind_amount', 1)); ?>">
							<p class="description">
								<?php esc_html_e('Списывается при привязке карты в ЛК через ЮKassa (save_payment_method). Минимум 1 ₽.', 'yoga-tariff-renewal'); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr>
			<h2><?php esc_html_e('Ручной запуск', 'yoga-tariff-renewal'); ?></h2>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<?php wp_nonce_field('ytr_run_renewals'); ?>
				<input type="hidden" name="action" value="ytr_run_renewals">
				<?php submit_button(__('Запустить проверку продлений сейчас', 'yoga-tariff-renewal'), 'secondary'); ?>
			</form>

			<hr>
			<h2><?php esc_html_e('Как это работает', 'yoga-tariff-renewal'); ?></h2>
			<ol>
				<li><?php esc_html_e('Пользователь оплачивает тариф картой/YooMoney с галочкой «Сохранить метод оплаты».', 'yoga-tariff-renewal'); ?></li>
				<li><?php esc_html_e('ЮKassa сохраняет payment_method_id.', 'yoga-tariff-renewal'); ?></li>
				<li><?php esc_html_e('Перед окончанием срока cron создаёт заказ и списывает оплату.', 'yoga-tariff-renewal'); ?></li>
				<li><?php esc_html_e('Новый оплаченный заказ продлевает доступ через get_current_user_tariff().', 'yoga-tariff-renewal'); ?></li>
			</ol>
			<div class="notice notice-warning" style="margin-top:1.5rem;padding:12px 16px;">
				<p><strong><?php esc_html_e('Автоплатежи в ЮKassa обязательны', 'yoga-tariff-renewal'); ?></strong></p>
				<p>
					<?php esc_html_e('Если при привязке карты или оплате тарифа появляется ошибка «This store can\'t make recurring payments / forbidden» — в магазине не подключены рекуррентные платежи. Это настраивается только через менеджера ЮKassa, не в коде сайта.', 'yoga-tariff-renewal'); ?>
				</p>
				<ol style="list-style:decimal;padding-left:1.25rem;">
					<li><?php esc_html_e('Личный кабинет ЮKassa → чат с менеджером.', 'yoga-tariff-renewal'); ?></li>
					<li><?php esc_html_e('Запрос: подключить автоплатежи и save_payment_method для банковских карт.', 'yoga-tariff-renewal'); ?></li>
					<li><?php esc_html_e('После подтверждения — повторить привязку карты в ЛК или оплату тарифа с галочкой сохранения карты.', 'yoga-tariff-renewal'); ?></li>
				</ol>
				<p>
					<a href="https://yookassa.ru/docs/support/payments/extra/autopayment" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Справка ЮKassa: автоплатежи', 'yoga-tariff-renewal'); ?></a>
					&nbsp;·&nbsp;
					<a href="https://yookassa.ru/developers/payment-acceptance/scenario-extensions/recurring-payments/basics" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Документация API', 'yoga-tariff-renewal'); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $health
	 * @param array{php:string,curl:string} $crontab
	 */
	private static function render_cron_status(array $health, string $cron_url, string $cron_php, array $crontab): void {
		$status = (string) ($health['status'] ?? 'warning');
		$last_stats = is_array($health['last_stats'] ?? null) ? $health['last_stats'] : array();
		?>
		<div class="ytr-cron-status ytr-cron-status--<?php echo esc_attr($status); ?>">
			<p class="ytr-cron-status__title">
				<strong><?php esc_html_e('Статус WP Cron', 'yoga-tariff-renewal'); ?></strong>
				<span class="ytr-cron-status__badge ytr-cron-status__badge--<?php echo esc_attr($status); ?>">
					<?php echo esc_html((string) ($health['status_label'] ?? '')); ?>
				</span>
			</p>
			<p><?php echo esc_html((string) ($health['status_message'] ?? '')); ?></p>

			<table class="widefat striped ytr-cron-table">
				<tbody>
					<tr>
						<th><?php esc_html_e('Событие', 'yoga-tariff-renewal'); ?></th>
						<td><code><?php echo esc_html(YTR_Cron::HOOK); ?></code></td>
					</tr>
					<tr>
						<th><?php esc_html_e('Интервал плагина', 'yoga-tariff-renewal'); ?></th>
						<td><?php echo esc_html((string) ($health['interval_label'] ?? '')); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e('Следующий запуск', 'yoga-tariff-renewal'); ?></th>
						<td>
							<?php
							echo esc_html(YTR_Cron::format_timestamp((int) ($health['next_run'] ?? 0)));
							if (!empty($health['is_overdue'])) {
								echo ' ';
								echo '<span style="color:#8a2424;">(' . esc_html__('просрочено', 'yoga-tariff-renewal') . ')</span>';
							} elseif ((int) ($health['next_run'] ?? 0) > 0 && (int) ($health['next_run'] ?? 0) < time()) {
								echo ' ';
								echo '<span class="description">(' . esc_html__('в очереди WP Cron', 'yoga-tariff-renewal') . ')</span>';
							}
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('Последний запуск плагина', 'yoga-tariff-renewal'); ?></th>
						<td>
							<?php
							echo esc_html(YTR_Cron::format_timestamp((int) ($health['last_run'] ?? 0)));
							if ((int) ($health['last_run'] ?? 0) > 0) {
								echo ' <span class="description">(' . esc_html(YTR_Cron::format_ago((int) $health['last_run'])) . ')</span>';
							}
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('Источник последнего запуска', 'yoga-tariff-renewal'); ?></th>
						<td>
							<?php
							$source = (string) ($health['last_source'] ?? '');
							if ($source === 'manual') {
								esc_html_e('Ручной запуск из админки', 'yoga-tariff-renewal');
							} elseif ($source === 'cron') {
								esc_html_e('WP Cron', 'yoga-tariff-renewal');
							} else {
								echo '—';
							}
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('Результат последнего запуска', 'yoga-tariff-renewal'); ?></th>
						<td>
							<?php
							if ($last_stats) {
								printf(
									esc_html__('обработано %1$d, успешно %2$d, ошибок %3$d, пропущено %4$d', 'yoga-tariff-renewal'),
									(int) ($last_stats['processed'] ?? 0),
									(int) ($last_stats['succeeded'] ?? 0),
									(int) ($last_stats['failed'] ?? 0),
									(int) ($last_stats['skipped'] ?? 0)
								);
							} else {
								echo '—';
							}
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('DISABLE_WP_CRON', 'yoga-tariff-renewal'); ?></th>
						<td>
							<?php
							echo !empty($health['wp_cron_disabled'])
								? esc_html__('Да — WP Cron по HTTP отключён, нужен системный cron', 'yoga-tariff-renewal')
								: esc_html__('Нет — cron запускается при посещениях сайта', 'yoga-tariff-renewal');
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('Путь wp-cron.php (PHP CLI)', 'yoga-tariff-renewal'); ?></th>
						<td><code><?php echo esc_html($cron_php); ?></code></td>
					</tr>
					<tr>
						<th><?php esc_html_e('URL для curl', 'yoga-tariff-renewal'); ?></th>
						<td><code><?php echo esc_html($cron_url); ?></code></td>
					</tr>
					<tr>
						<th><?php esc_html_e('Crontab (рекомендуется)', 'yoga-tariff-renewal'); ?></th>
						<td>
							<code><?php echo esc_html($crontab['php']); ?></code>
							<p class="description">
								<?php esc_html_e('Запуск через PHP CLI. Не используйте curl с путём к файлу — curl не выполняет PHP.', 'yoga-tariff-renewal'); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('Crontab (альтернатива)', 'yoga-tariff-renewal'); ?></th>
						<td>
							<code><?php echo esc_html($crontab['curl']); ?></code>
							<p class="description">
								<?php esc_html_e('HTTP-запрос к сайту. Нужен полный URL https://…, не путь /var/www/…', 'yoga-tariff-renewal'); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
}
