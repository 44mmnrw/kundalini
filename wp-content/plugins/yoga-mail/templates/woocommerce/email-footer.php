<?php
if (!defined('ABSPATH')) {
	exit;
}
$font = 'font-family:Helvetica,sans-serif;';
?>
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
</td></tr>
<tr><td bgcolor="#1f1f1f" style="padding:0;background-color:#1f1f1f;border-radius:36px 36px 0 0;">
	<?php include YOGA_MAIL_PATH . 'templates/partials/footer.php'; ?>
</td></tr>
</table></td></tr></table></td></tr></table></body></html>
