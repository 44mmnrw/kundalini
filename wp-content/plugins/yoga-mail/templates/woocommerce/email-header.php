<?php
if (!defined('ABSPATH')) {
	exit;
}
$font = 'font-family:Helvetica,sans-serif;';
$default_logo_url = defined('YOGA_MAIL_URL') ? YOGA_MAIL_URL . 'assets/images/email/logo.svg' : content_url('/plugins/yoga-mail/assets/images/email/logo.svg');
$logo_url = esc_url((string) ($settings['logo_url'] ?? $default_logo_url));
$logo_alt = esc_attr((string) ($settings['logo_alt'] ?? get_bloginfo('name')));
?>
<!doctype html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title><?php echo esc_html(wp_strip_all_tags((string) $heading)); ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f6f6f9;<?php echo esc_attr($font); ?>color:#1f1f1f;">
<!-- yoga-mail:<?php echo esc_html($template_id); ?> -->
<span style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;mso-hide:all;"><?php echo esc_html($preheader); ?></span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f6f6f9" style="width:100%;margin:0;padding:0;background-color:#f6f6f9;border-collapse:collapse;">
	<tr>
		<td align="center" bgcolor="#f6f6f9" style="padding:20px 0 0;background-color:#f6f6f9;">
			<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" align="center" style="width:100%;max-width:600px;border-collapse:separate;background-color:#f6f6f9;border-radius:20px 20px 0 0;">
				<tr>
					<td align="center" style="padding:0 20px;background-color:#f6f6f9;border-radius:20px 20px 0 0;">
						<table id="template_container" role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" align="center" style="width:100%;max-width:560px;border-collapse:separate;background-color:#ffffff;border-radius:30px 30px 0 0;">
							<tr>
								<td id="body_content" align="center" bgcolor="#ffffff" style="padding:40px 40px 60px;background-color:#ffffff;border-radius:30px 30px 0 0;<?php echo esc_attr($font); ?>font-size:14px;line-height:1.5;color:#1f1f1f;text-align:center;">
									<?php if ($logo_url !== '') : ?><img src="<?php echo $logo_url; ?>" width="54" height="35" alt="<?php echo $logo_alt; ?>" style="display:block;width:54px;height:35px;margin:0 auto;border:0;outline:none;text-decoration:none;"><?php endif; ?>
									<?php if ((string) $heading !== '') : ?><h1 style="margin:15px 0 30px;<?php echo esc_attr($font); ?>font-size:22px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;"><?php echo esc_html(wp_strip_all_tags((string) $heading)); ?></h1><?php endif; ?>
