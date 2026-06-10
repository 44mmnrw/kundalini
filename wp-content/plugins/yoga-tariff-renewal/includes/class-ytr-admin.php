<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Admin {
	public static function init(): void {
		add_action('admin_menu', array(__CLASS__, 'register_menu'));
		add_action('admin_init', array(__CLASS__, 'register_settings'));
		add_action('admin_post_ytr_run_renewals', array(__CLASS__, 'handle_manual_run'));
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
	}

	public static function handle_manual_run(): void {
		if (!current_user_can('manage_woocommerce')) {
			wp_die('Forbidden');
		}

		check_admin_referer('ytr_run_renewals');

		$stats = YTR_Renewal::process_due_renewals();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'ytr-settings',
					'ytr_ran'    => '1',
					'processed'  => $stats['processed'],
					'succeeded'  => $stats['succeeded'],
					'failed'     => $stats['failed'],
					'skipped'    => $stats['skipped'],
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

		$next = wp_next_scheduled(YTR_Cron::HOOK);
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

			<p>
				<?php
				echo $next
					? esc_html(sprintf(__('Следующий cron: %s (интервал: каждый час)', 'yoga-tariff-renewal'), wp_date('d.m.Y H:i', $next)))
					: esc_html__('Cron не запланирован. Сохраните настройки или деактивируйте/активируйте плагин.', 'yoga-tariff-renewal');
				?>
			</p>
			<p>
				<?php
				$auto_users = class_exists('YTR_User') ? YTR_User::get_auto_renew_user_ids() : array();
				printf(
					esc_html__('Пользователей с автопродлением: %d. ЮKassa: %s.', 'yoga-tariff-renewal'),
					count($auto_users),
					class_exists('YTR_YooKassa') && YTR_YooKassa::is_configured() ? 'OK' : 'не настроена'
				);
				?>
			</p>
			<p class="description">
				<?php esc_html_e('На production добавьте системный cron: curl -s https://ваш-сайт.ru/wp-cron.php?doing_wp_cron каждые 5–15 минут, либо DISABLE_WP_CRON + wp cron event run ytr_daily_renewal_check.', 'yoga-tariff-renewal'); ?>
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
			<p>
				<?php esc_html_e('В ЛК ЮKassa для боевого магазина должны быть подключены автоплатежи.', 'yoga-tariff-renewal'); ?>
				<a href="https://yookassa.ru/developers/payment-acceptance/scenario-extensions/recurring-payments/basics" target="_blank" rel="noopener noreferrer">Документация</a>
			</p>
		</div>
		<?php
	}
}
