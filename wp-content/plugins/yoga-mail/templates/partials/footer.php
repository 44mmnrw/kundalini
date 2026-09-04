<?php
if (!defined('ABSPATH')) {
	exit;
}

$font = isset($font) ? (string) $font : 'font-family:Mulish,Arial,Helvetica,sans-serif;';
$asset_base_url = defined('YOGA_MAIL_URL')
	? rtrim(YOGA_MAIL_URL, '/') . '/assets/images/email/'
	: content_url('/plugins/yoga-mail/assets/images/email/');
$footer_option = static function (string $key): string {
	if (!function_exists('get_field')) {
		return '';
	}
	return esc_url_raw(trim((string) get_field($key, 'option')));
};
$socials = array(
	array('label' => 'YouTube', 'url' => $footer_option('youtube_link'), 'icon' => 'youtube.svg', 'width' => 25, 'height' => 24),
	array('label' => 'Rutube', 'url' => $footer_option('rutube_link'), 'icon' => 'rutube.svg', 'width' => 26, 'height' => 25),
	array('label' => 'Дзен', 'url' => $footer_option('dzen_link'), 'icon' => 'zen.svg', 'width' => 25, 'height' => 25),
	array('label' => 'Telegram', 'url' => $footer_option('telegram_link'), 'icon' => 'telegram.svg', 'width' => 25, 'height' => 25),
	array('label' => 'VK', 'url' => $footer_option('vk_link') ?: $footer_option('vkontakte_link'), 'icon' => 'vk.svg', 'width' => 25, 'height' => 25),
);
$privacy_url = function_exists('yoga_get_legal_document_url')
	? (string) yoga_get_legal_document_url('privacy_policy', '')
	: '';
if ($privacy_url === '' && function_exists('get_privacy_policy_url')) {
	$privacy_url = (string) get_privacy_policy_url();
}
if ($privacy_url === '') {
	$privacy_url = home_url('/privacy-policy/');
}
$service_text = trim((string) ($settings['footer_text'] ?? ''));
if ($service_text === '' || $service_text === 'Kundalini Class') {
	$service_text = 'Это служебное письмо по вашему аккаунту на онлайн-платформе Кундалини Класс.';
}
$copyright_year = function_exists('wp_date') ? wp_date('Y') : date('Y');
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:separate;background-color:#1f1f1f;border-radius:36px 36px 0 0;">
	<tr>
		<td align="center" bgcolor="#1f1f1f" style="padding:30px;background-color:#1f1f1f;border-radius:36px 36px 0 0;<?php echo esc_attr($font); ?>color:#ffffff;">
			<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto;border-collapse:collapse;">
				<tr>
					<?php foreach ($socials as $index => $social) : ?>
						<td align="center" valign="middle" style="padding:0 <?php echo $index === count($socials) - 1 ? '0' : '10px'; ?> 0 0;">
							<?php if ($social['url'] !== '') : ?><a href="<?php echo esc_url($social['url']); ?>" target="_blank" style="display:block;text-decoration:none;"><?php endif; ?>
							<img src="<?php echo esc_url($asset_base_url . $social['icon']); ?>" width="<?php echo (int) $social['width']; ?>" height="<?php echo (int) $social['height']; ?>" alt="<?php echo esc_attr($social['label']); ?>" style="display:block;width:<?php echo (int) $social['width']; ?>px;height:<?php echo (int) $social['height']; ?>px;border:0;outline:none;text-decoration:none;">
							<?php if ($social['url'] !== '') : ?></a><?php endif; ?>
						</td>
					<?php endforeach; ?>
				</tr>
			</table>

			<p style="margin:30px 0 0;<?php echo esc_attr($font); ?>font-size:14px;line-height:1;font-weight:700;color:#ffffff;text-align:center;">Поддержка — <a href="mailto:support@platform.kundalini-class.ru" style="color:#ffffff;text-decoration:none;">support@platform.kundalini-class.ru</a></p>
			<p style="margin:15px auto 0;max-width:460px;<?php echo esc_attr($font); ?>font-size:14px;line-height:1;font-weight:400;color:#bcbcbc;text-align:center;"><?php echo esc_html($service_text); ?></p>
			<p style="margin:30px 0 0;<?php echo esc_attr($font); ?>font-size:14px;line-height:1;font-weight:700;color:#ffffff;text-align:center;"><a href="<?php echo esc_url($privacy_url); ?>" style="color:#ffffff;text-decoration:none;">Политика конфиденциальности</a></p>
			<p style="margin:15px auto 0;max-width:460px;<?php echo esc_attr($font); ?>font-size:14px;line-height:1.5;font-weight:400;color:#bcbcbc;text-align:center;">ИП Ксенофонтова Марина Евгеньевна · ИНН 632200860531<br>· ОГРНИП 319631300101827</p>
			<p style="margin:15px 0 0;<?php echo esc_attr($font); ?>font-size:14px;line-height:1;font-weight:400;color:#bcbcbc;text-align:center;"><?php echo esc_html($copyright_year); ?> © Кундалини Класс. Все права защищены.</p>
		</td>
	</tr>
</table>
