<?php
/**
 * Plugin Name: Yoga Subscribers
 * Description: Подписчики и журнал добровольных согласий на рассылку.
 * Version: 1.0.0
 * Author: Yoga
 */
defined('ABSPATH') || exit;

final class Yoga_Subscribers_Plugin {
	private const VERSION = '1.0.0';
	private const CONSENT_VERSION = '2026-07-12';
	private const CONSENT_TEXT = 'Я соглашаюсь на обработку персональных данных и получение рассылок';

	public static function init(): void {
		add_action('plugins_loaded', array(__CLASS__, 'maybe_upgrade'));
		add_action('wp_ajax_process_subscription', array(__CLASS__, 'subscribe'), 1);
		add_action('wp_ajax_nopriv_process_subscription', array(__CLASS__, 'subscribe'), 1);
		add_action('admin_menu', array(__CLASS__, 'menu'));
		add_action('admin_post_yoga_subscribers_export', array(__CLASS__, 'export'));
		add_action('admin_post_yoga_subscribers_delete', array(__CLASS__, 'delete'));
	}

	private static function table(): string { global $wpdb; return $wpdb->prefix . 'yoga_subscribers'; }

	public static function is_subscribed(string $email): bool {
		$email = strtolower(trim(sanitize_email($email)));
		if ($email === '' || !is_email($email)) return false;
		global $wpdb;
		return (bool) $wpdb->get_var($wpdb->prepare('SELECT 1 FROM '.self::table().' WHERE LOWER(email)=%s LIMIT 1', $email));
	}

	public static function activate(): void { self::install(); self::migrate(); }
	public static function maybe_upgrade(): void { if (get_option('yoga_subscribers_db_version') !== self::VERSION) self::install(); }

	private static function install(): void {
		global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; $table = self::table(); $charset = $wpdb->get_charset_collate();
		dbDelta("CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(190) NOT NULL,
			source varchar(100) NOT NULL DEFAULT 'website',
			ip_address varchar(45) NOT NULL DEFAULT '',
			user_agent text NULL,
			page_url text NULL,
			consent_text text NULL,
			consent_version varchar(50) NOT NULL DEFAULT '',
			consented_at datetime NULL,
			provider_status varchar(50) NOT NULL DEFAULT 'local',
			confirmed_at datetime NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY email (email)
		) {$charset};");
		update_option('yoga_subscribers_db_version', self::VERSION, false);
	}

	private static function migrate(): void {
		global $wpdb; $emails = get_option('subscription_emails', array()); if (!is_array($emails)) return;
		foreach ($emails as $email) { $email = sanitize_email((string)$email); if (!is_email($email)) continue;
			$wpdb->query($wpdb->prepare('INSERT IGNORE INTO '.self::table().' (email,source,provider_status,created_at) VALUES (%s,%s,%s,%s)', $email, 'legacy', 'legacy', current_time('mysql')));
		}
	}

	private static function ip(): string {
		$ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
		return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
	}

	public static function subscribe(): void {
		check_ajax_referer('subscription_nonce', 'nonce');
		$email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
		$consent = sanitize_text_field(wp_unslash($_POST['consent'] ?? ''));
		if (!is_email($email)) wp_send_json_error(array('message'=>'Введите корректный email.'), 422);
		if ($consent !== '1') wp_send_json_error(array('message'=>'Необходимо согласие на обработку данных и рассылку.'), 422);
		$page = esc_url_raw(wp_unslash($_POST['page_url'] ?? wp_get_referer() ?: ''));
		$source = sanitize_key(wp_unslash($_POST['source'] ?? 'website'));
		$ua = sanitize_textarea_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? ''));
		$status = 'local';
		global $wpdb; $now = current_time('mysql');
		$sql = $wpdb->prepare('INSERT INTO '.self::table().' (email,source,ip_address,user_agent,page_url,consent_text,consent_version,consented_at,provider_status,created_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s) ON DUPLICATE KEY UPDATE source=VALUES(source),ip_address=VALUES(ip_address),user_agent=VALUES(user_agent),page_url=VALUES(page_url),consent_text=VALUES(consent_text),consent_version=VALUES(consent_version),consented_at=VALUES(consented_at),provider_status=VALUES(provider_status)', $email,$source,self::ip(),$ua,$page,self::CONSENT_TEXT,self::CONSENT_VERSION,$now,$status,$now);
		if ($wpdb->query($sql) === false) wp_send_json_error(array('message'=>'Не удалось сохранить подписку.'), 500);
		wp_send_json_success(array('message'=>'Проверьте почту и подтвердите подписку.'));
	}

	public static function menu(): void {
		add_menu_page('Подписчики','Подписчики','manage_options','yoga-subscribers',array(__CLASS__,'page'),'dashicons-email-alt',58);
	}

	private static function source_label(string $source): string {
		$labels = array(
			'footer' => 'Форма подписки в подвале',
			'subscription-section' => 'Блок подписки на странице',
			'website' => 'Форма на сайте',
			'legacy' => 'Перенесён из старой базы',
		);
		return $labels[$source] ?? ($source !== '' ? $source : 'Источник не указан');
	}

	public static function page(): void {
		if (!current_user_can('manage_options')) wp_die('Недостаточно прав.'); global $wpdb;
		$rows=$wpdb->get_results('SELECT * FROM '.self::table().' ORDER BY id DESC'); ?>
		<div class="wrap"><h1 class="wp-heading-inline">Подписчики</h1> <a class="page-title-action" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=yoga_subscribers_export'),'yoga_subscribers_export')); ?>">Экспорт CSV</a><hr class="wp-header-end">
		<?php if (isset($_GET['deleted'])) : ?><div class="notice <?php echo $_GET['deleted'] === '1' ? 'notice-success' : 'notice-error'; ?> is-dismissible"><p><?php echo $_GET['deleted'] === '1' ? 'Email удалён.' : 'Не удалось удалить email.'; ?></p></div><?php endif; ?>
		<p>Всего: <strong><?php echo esc_html((string)count($rows)); ?></strong></p><table class="widefat striped"><thead><tr><th>Email</th><th>Дата согласия</th><th>IP</th><th>Источник</th><th>Страница</th><th>Статус</th><th>Действия</th></tr></thead><tbody>
		<?php if(!$rows):?><tr><td colspan="7">Подписчиков пока нет.</td></tr><?php endif; foreach($rows as $r):?><?php $delete_url = wp_nonce_url(add_query_arg(array('action'=>'yoga_subscribers_delete','subscriber_id'=>(int)$r->id),admin_url('admin-post.php')),'yoga_subscribers_delete_'.(int)$r->id); ?><tr><td><?php echo esc_html($r->email);?></td><td><?php echo esc_html($r->consented_at ?: $r->created_at);?></td><td><?php echo esc_html($r->ip_address);?></td><td><?php echo esc_html(self::source_label((string)$r->source));?></td><td><?php if($r->page_url):?><a href="<?php echo esc_url($r->page_url);?>" target="_blank" rel="noopener">Открыть</a><?php endif;?></td><td><?php echo esc_html($r->provider_status);?></td><td><a class="button button-small button-link-delete" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Удалить этот email из подписчиков?');">Удалить</a></td></tr><?php endforeach;?></tbody></table></div>
	<?php }

	public static function delete(): void {
		if (!current_user_can('manage_options')) wp_die('Недостаточно прав.');
		$id = absint($_GET['subscriber_id'] ?? 0);
		if ($id < 1) wp_die('Некорректный подписчик.');
		check_admin_referer('yoga_subscribers_delete_'.$id);
		global $wpdb;
		$email = (string) $wpdb->get_var($wpdb->prepare('SELECT email FROM '.self::table().' WHERE id=%d', $id));
		$deleted = $wpdb->delete(self::table(), array('id'=>$id), array('%d'));
		if ($deleted === 1 && $email !== '') {
			self::remove_from_legacy_options($email);
		}
		wp_safe_redirect(add_query_arg(array('page'=>'yoga-subscribers','deleted'=>$deleted === 1 ? '1' : '0'),admin_url('admin.php')));
		exit;
	}

	private static function remove_from_legacy_options(string $email): void {
		$email = strtolower(trim(sanitize_email($email)));
		foreach (array('subscription_emails', 'yoga_subscribers') as $option_name) {
			$emails = get_option($option_name, array());
			if (!is_array($emails)) continue;
			$filtered = array_values(array_filter($emails, static function($stored_email) use ($email): bool {
				return strtolower(trim(sanitize_email((string) $stored_email))) !== $email;
			}));
			if ($filtered !== $emails) update_option($option_name, $filtered, false);
		}
	}

	public static function export(): void {
		if(!current_user_can('manage_options'))wp_die('Недостаточно прав.'); check_admin_referer('yoga_subscribers_export'); global $wpdb;
		$rows=$wpdb->get_results('SELECT email,source,ip_address,user_agent,page_url,consent_text,consent_version,consented_at,provider_status,confirmed_at,created_at FROM '.self::table().' ORDER BY id DESC',ARRAY_A);
		nocache_headers(); header('Content-Type:text/csv; charset=UTF-8'); header('Content-Disposition:attachment; filename="yoga-subscribers-'.gmdate('Y-m-d').'.csv"'); $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF"); if($rows){fputcsv($out,array_keys($rows[0]),';');foreach($rows as $row)fputcsv($out,$row,';');} fclose($out); exit;
	}
}
register_activation_hook(__FILE__,array('Yoga_Subscribers_Plugin','activate'));
Yoga_Subscribers_Plugin::init();
