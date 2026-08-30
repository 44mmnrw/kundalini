<?php
if (!defined('ABSPATH')) {
	exit;
}
$font = "font-family:'Mulish',Arial,Helvetica,sans-serif;";
$default_logo_url = defined('YOGA_MAIL_URL') ? YOGA_MAIL_URL . 'assets/images/email/logo.svg' : content_url('/plugins/yoga-mail/assets/images/email/logo.svg');
$logo_url = esc_url((string) ($settings['logo_url'] ?? $default_logo_url));
$logo_alt = esc_attr((string) ($settings['logo_alt'] ?? get_bloginfo('name')));
?>
<!doctype html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700&amp;display=swap" rel="stylesheet">
	<title><?php echo esc_html($subject); ?></title>
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
						<table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" align="center" style="width:100%;max-width:560px;border-collapse:separate;background-color:#ffffff;border-radius:30px 30px 0 0;">
							<tr>
								<td align="center" bgcolor="#ffffff" style="padding:40px 40px 60px;background-color:#ffffff;border-radius:30px 30px 0 0;<?php echo esc_attr($font); ?>color:#1f1f1f;">
									<?php if ($logo_url !== '') : ?>
										<img src="<?php echo $logo_url; ?>" width="54" height="35" alt="<?php echo $logo_alt; ?>" style="display:block;width:54px;height:35px;margin:0 auto;border:0;outline:none;text-decoration:none;">
									<?php endif; ?>
									<?php if ($heading !== '') : ?>
										<h1 style="margin:15px 0 0;<?php echo esc_attr($font); ?>font-size:22px;line-height:1;font-weight:700;color:#1f1f1f;text-align:center;"><?php echo esc_html($heading); ?></h1>
									<?php endif; ?>
									<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
										<tr>
											<td align="center" style="padding:15px 0 0;<?php echo esc_attr($font); ?>color:#1f1f1f;text-align:center;">
												<?php echo wp_kses_post($body_html); ?>
											</td>
										</tr>
									</table>
									<?php if ($cta_label !== '' && $cta_url !== '') : ?>
										<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:30px auto 0;border-collapse:separate;">
											<tr>
												<td align="center" bgcolor="#1f1f1f" style="background-color:#1f1f1f;border-radius:500px;mso-padding-alt:23px 30px;">
													<a href="<?php echo esc_url($cta_url); ?>" style="display:inline-block;padding:23px 30px;<?php echo esc_attr($font); ?>font-size:14px;line-height:1;font-weight:600;letter-spacing:-0.154px;color:#e8ff57;text-decoration:none;border-radius:500px;background-color:#1f1f1f;"><?php echo esc_html($cta_label); ?></a>
												</td>
											</tr>
										</table>
									<?php endif; ?>
									<?php if ($footer_note !== '') : ?>
										<p style="margin:15px auto 0;max-width:400px;<?php echo esc_attr($font); ?>font-size:12px;line-height:1;font-weight:400;color:#626262;text-align:center;"><?php echo esc_html($footer_note); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td bgcolor="#1f1f1f" style="padding:0;background-color:#1f1f1f;border-radius:36px 36px 0 0;">
									<?php include YOGA_MAIL_PATH . 'templates/partials/footer.php'; ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
