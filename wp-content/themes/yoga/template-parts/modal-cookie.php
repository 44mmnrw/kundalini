<?php
/**
 * Баннер согласия на cookie (Figma: desktop 265:5642, mobile 593:16200).
 *
 * Ссылка «Подробнее» — из ACF «Политика конфиденциальности», как в подвале.
 */
if (!defined('ABSPATH')) {
	exit;
}

$privacy_url = '';
if (function_exists('get_field')) {
	$privacy_url = trim((string) get_field('privacy_policy_link', 'option'));
}
if ($privacy_url === '') {
	$privacy_url = '#';
}

$cookie_text = __('Сайт использует файлы куки для обеспечения удобства пользователей сайта, его улучшения, предоставления персонализированных рекомендаций.', 'yoga');
?>
<div class="modal-cookie" id="yoga-modal-cookie" role="dialog" aria-live="polite" aria-label="<?php echo esc_attr__('Уведомление о файлах cookie', 'yoga'); ?>">
	<div class="cookie">
		<p class="cookie__info">
			<span class="cookie__text"><?php echo esc_html($cookie_text); ?></span>
			<?php echo ' '; ?>
			<a href="<?php echo esc_url($privacy_url); ?>" class="cookie__more"><?php esc_html_e('Подробнее.', 'yoga'); ?></a>
		</p>
		<div class="cookie__buttons">
			<button type="button" class="btn cookie__btn cookie__btn-decline">
				<span><?php esc_html_e('Отклонить', 'yoga'); ?></span>
			</button>
			<button type="button" class="btn cookie__btn cookie__btn-accept">
				<span><?php esc_html_e('Принять', 'yoga'); ?></span>
			</button>
		</div>
	</div>
</div>

