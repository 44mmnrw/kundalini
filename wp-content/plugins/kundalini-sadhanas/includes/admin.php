<?php
/**
 * WordPress admin settings screen.
 */

if (!defined('ABSPATH')) {
	exit;
}

function kundalini_sadhanas_place_practice_menu(array $args, string $post_type): array {
	if ($post_type === 'practice') {
		$args['menu_position'] = 57;
	}
	return $args;
}
add_filter('register_post_type_args', 'kundalini_sadhanas_place_practice_menu', 100, 2);

function kundalini_sadhanas_enable_custom_menu_order($enabled): bool {
	return true;
}
add_filter('custom_menu_order', 'kundalini_sadhanas_enable_custom_menu_order');

function kundalini_sadhanas_order_admin_menu(array $menu_order): array {
	$practice_slug = 'edit.php?post_type=practice';
	$sadhanas_slug = 'kundalini-sadhanas';
	$practice_index = array_search($practice_slug, $menu_order, true);
	$sadhanas_index = array_search($sadhanas_slug, $menu_order, true);

	if ($practice_index === false || $sadhanas_index === false) {
		return $menu_order;
	}

	array_splice($menu_order, $sadhanas_index, 1);
	$practice_index = array_search($practice_slug, $menu_order, true);
	array_splice($menu_order, ((int) $practice_index) + 1, 0, array($sadhanas_slug));

	return $menu_order;
}
add_filter('menu_order', 'kundalini_sadhanas_order_admin_menu');

function kundalini_sadhanas_register_admin_menu(): void {
	add_menu_page(
		__('Садханы', 'kundalini-sadhanas'),
		__('Садханы', 'kundalini-sadhanas'),
		'manage_options',
		'kundalini-sadhanas',
		'kundalini_sadhanas_render_settings_page',
		'dashicons-calendar-alt',
		58
	);
}
add_action('admin_menu', 'kundalini_sadhanas_register_admin_menu');

function kundalini_sadhanas_register_settings(): void {
	register_setting('kundalini_sadhanas', 'kundalini_sadhanas_settings', array(
		'type' => 'array',
		'sanitize_callback' => 'kundalini_sadhanas_sanitize_settings',
		'default' => kundalini_sadhanas_default_settings(),
	));
}
add_action('admin_init', 'kundalini_sadhanas_register_settings');

function kundalini_sadhanas_admin_counts(): array {
	global $wpdb;
	$table = yoga_sadhana_table();
	if (!yoga_sadhana_storage_exists()) {
		return array('active' => 0, 'completed' => 0, 'cancelled' => 0);
	}
	$rows = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status", OBJECT_K);
	return array(
		'active' => isset($rows['active']) ? (int) $rows['active']->total : 0,
		'completed' => isset($rows['completed']) ? (int) $rows['completed']->total : 0,
		'cancelled' => isset($rows['cancelled']) ? (int) $rows['cancelled']->total : 0,
	);
}

function kundalini_sadhanas_render_settings_page(): void {
	if (!current_user_can('manage_options')) {
		return;
	}
	$settings = kundalini_sadhanas_get_settings();
	$counts = kundalini_sadhanas_admin_counts();
	$next_cron = wp_next_scheduled(YOGA_SADHANA_CRON_HOOK);
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Садханы', 'kundalini-sadhanas'); ?></h1>
		<p><?php esc_html_e('Управление событиями и каналами уведомлений. Пользователь может переопределить каналы в личном кабинете.', 'kundalini-sadhanas'); ?></p>
		<div class="notice notice-info inline"><p><?php esc_html_e('Темы, содержимое и оформление писем настраиваются централизованно в Yoga Mail.', 'kundalini-sadhanas'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=yoga-mail')); ?>"><?php esc_html_e('Открыть шаблоны писем', 'kundalini-sadhanas'); ?></a></p></div>
		<div class="notice notice-info inline"><p>
			<?php
			echo esc_html(sprintf(
				__('Активных: %1$d · Завершённых: %2$d · Отменённых: %3$d · Следующая проверка пропусков: %4$s', 'kundalini-sadhanas'),
				$counts['active'],
				$counts['completed'],
				$counts['cancelled'],
				$next_cron ? wp_date('d.m.Y H:i', $next_cron) : __('не запланирована', 'kundalini-sadhanas')
			));
			?>
		</p></div>
		<form method="post" action="options.php">
			<?php settings_fields('kundalini_sadhanas'); ?>
			<h2><?php esc_html_e('Основные настройки', 'kundalini-sadhanas'); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="kundalini-sadhanas-minimum-days"><?php esc_html_e('Минимальная длительность садханы', 'kundalini-sadhanas'); ?></label></th>
					<td>
						<input
							class="small-text"
							id="kundalini-sadhanas-minimum-days"
							name="kundalini_sadhanas_settings[minimum_target_days]"
							type="number"
							min="1"
							max="1000"
							step="1"
							value="<?php echo esc_attr((string) $settings['minimum_target_days']); ?>"
						> <?php esc_html_e('дней', 'kundalini-sadhanas'); ?>
						<p class="description"><?php esc_html_e('Пользователь не сможет начать новую садхану на меньшее количество дней. Допустимое значение: от 1 до 1000.', 'kundalini-sadhanas'); ?></p>
					</td>
				</tr>
			</table>
			<h2><?php esc_html_e('Рубежи прогресса', 'kundalini-sadhanas'); ?></h2>
			<p><?php esc_html_e('Для каждой продолжительности задайте проценты прогресса, на которых пользователь получит поздравление.', 'kundalini-sadhanas'); ?></p>
			<table class="form-table" role="presentation">
				<?php
				$progress_profiles = array(
					'40' => array(
						__('Садхана на 40 дней', 'kundalini-sadhanas'),
						'25, 50, 75, 100',
						__('Например, 25, 50, 75, 100 — четыре четверти: 10-й, 20-й, 30-й и 40-й дни.', 'kundalini-sadhanas'),
					),
					'90' => array(
						__('Садхана на 90 дней', 'kundalini-sadhanas'),
						'30, 70',
						__('Например, 30, 70 — два рубежа: 27-й и 63-й дни.', 'kundalini-sadhanas'),
					),
					'120' => array(
						__('Садхана на 120 дней', 'kundalini-sadhanas'),
						'25, 50, 75, 100',
						__('Например, 25, 50, 75, 100 — четыре четверти: 30-й, 60-й, 90-й и 120-й дни.', 'kundalini-sadhanas'),
					),
					'custom' => array(
						__('Пользовательская продолжительность', 'kundalini-sadhanas'),
						'25, 50, 75, 100',
						__('Эти проценты применяются к любому сроку, кроме 40, 90 и 120 дней. Например, 50 означает рубеж на половине выбранного срока.', 'kundalini-sadhanas'),
					),
				);
				foreach ($progress_profiles as $profile => $definition) :
					$key = 'progress_percentages_' . $profile;
					$field_id = 'kundalini-sadhanas-' . str_replace('_', '-', $key);
					?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($definition[0]); ?></label></th>
						<td>
							<input
								class="regular-text"
								id="<?php echo esc_attr($field_id); ?>"
								name="kundalini_sadhanas_settings[<?php echo esc_attr($key); ?>]"
								type="text"
								value="<?php echo esc_attr(implode(', ', $settings[$key])); ?>"
								placeholder="<?php echo esc_attr($definition[1]); ?>"
							>
							<p class="description">
								<?php echo esc_html($definition[2]); ?>
							</p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<p class="description">
				<?php esc_html_e('Как заполнять: укажите абсолютные проценты от 1 до 100 через запятую. День рубежа рассчитывается от выбранной продолжительности и округляется вверх. На 100% отдельное письмо о прогрессе не отправляется: вместо него пользователь получает письмо «Садхана завершена». Повторы удаляются, значения сортируются. Оставьте поле пустым, чтобы отключить письма о промежуточном прогрессе для этой продолжительности.', 'kundalini-sadhanas'); ?>
			</p>
			<?php foreach (kundalini_sadhanas_notification_events() as $event => $definition) : ?>
				<h2><?php echo esc_html($definition['label']); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e('Каналы по умолчанию', 'kundalini-sadhanas'); ?></th>
						<td>
							<label><input type="checkbox" name="kundalini_sadhanas_settings[<?php echo esc_attr($event); ?>_site_enabled]" value="1" <?php checked(!empty($settings[$event . '_site_enabled'])); ?>> <?php esc_html_e('Уведомление на сайте', 'kundalini-sadhanas'); ?></label><br>
							<label><input type="checkbox" name="kundalini_sadhanas_settings[<?php echo esc_attr($event); ?>_email_enabled]" value="1" <?php checked(!empty($settings[$event . '_email_enabled'])); ?>> <?php esc_html_e('Письмо', 'kundalini-sadhanas'); ?></label>
						</td>
					</tr>
				</table>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
