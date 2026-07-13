<?php
/**
 * Plugin Name: Yoga Subscribers
 * Description: Подписчики и журнал добровольных согласий на рассылку.
 * Version: 1.1.0
 * Author: Yoga
 */
defined('ABSPATH') || exit;

final class Yoga_Subscribers_Plugin {
	private const VERSION = '1.1.0';
	private const CONSENT_VERSION = '2026-07-12';
	private const CONSENT_TEXT = 'Я соглашаюсь на обработку персональных данных и получение рассылок';

	public static function init(): void {
		add_action('plugins_loaded', array(__CLASS__, 'maybe_upgrade'));
		add_action('wp_ajax_process_subscription', array(__CLASS__, 'subscribe'), 1);
		add_action('wp_ajax_nopriv_process_subscription', array(__CLASS__, 'subscribe'), 1);
		add_action('admin_menu', array(__CLASS__, 'menu'));
		add_action('admin_post_yoga_subscribers_export', array(__CLASS__, 'export'));
	}

	private static function table(): string { global $wpdb; return $wpdb->prefix . 'yoga_subscribers'; }

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

	public static function page(): void {
		if (!current_user_can('manage_options')) wp_die('Недостаточно прав.'); global $wpdb;
		$rows=$wpdb->get_results('SELECT * FROM '.self::table().' ORDER BY id DESC'); ?>
		<div class="wrap"><h1 class="wp-heading-inline">Подписчики</h1> <a class="page-title-action" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=yoga_subscribers_export'),'yoga_subscribers_export')); ?>">Экспорт CSV</a><hr class="wp-header-end"><p>Всего: <strong><?php echo esc_html((string)count($rows)); ?></strong></p><table class="widefat striped"><thead><tr><th>Email</th><th>Дата согласия</th><th>IP</th><th>Источник</th><th>Страница</th><th>Статус</th></tr></thead><tbody>
		<?php if(!$rows):?><tr><td colspan="6">Подписчиков пока нет.</td></tr><?php endif; foreach($rows as $r):?><tr><td><?php echo esc_html($r->email);?></td><td><?php echo esc_html($r->consented_at ?: $r->created_at);?></td><td><?php echo esc_html($r->ip_address);?></td><td><?php echo esc_html($r->source);?></td><td><?php if($r->page_url):?><a href="<?php echo esc_url($r->page_url);?>" target="_blank" rel="noopener">Открыть</a><?php endif;?></td><td><?php echo esc_html($r->provider_status);?></td></tr><?php endforeach;?></tbody></table></div>
	<?php }

	public static function export(): void {
		if(!current_user_can('manage_options'))wp_die('Недостаточно прав.'); check_admin_referer('yoga_subscribers_export'); global $wpdb;
		$rows=$wpdb->get_results('SELECT email,source,ip_address,user_agent,page_url,consent_text,consent_version,consented_at,provider_status,confirmed_at,created_at FROM '.self::table().' ORDER BY id DESC',ARRAY_A);
		nocache_headers(); header('Content-Type:text/csv; charset=UTF-8'); header('Content-Disposition:attachment; filename="yoga-subscribers-'.gmdate('Y-m-d').'.csv"'); $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF"); if($rows){fputcsv($out,array_keys($rows[0]),';');foreach($rows as $row)fputcsv($out,$row,';');} fclose($out); exit;
	}
}
register_activation_hook(__FILE__,array('Yoga_Subscribers_Plugin','activate'));
Yoga_Subscribers_Plugin::init();
