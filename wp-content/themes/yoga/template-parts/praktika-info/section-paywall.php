<?php
/**
 * Заглушка для секции practice_sections без доступа.
 *
 * @var array  $section
 * @var string $layout
 * @var string $anchor_id
 * @var string $section_title
 */

if (!defined('ABSPATH')) {
	exit;
}

$tariffs_url = function_exists('yoga_get_tariffs_page_url') ? yoga_get_tariffs_page_url() : home_url('/');
?>
<span class="praktika-menu-anchor" id="<?php echo esc_attr($anchor_id); ?>"></span>
<div class="praktika-section-paywall">
	<h3 class="praktika-section-paywall__title mtb"><?php echo esc_html($section_title); ?></h3>
	<div class="praktika-section-paywall__card" role="status" aria-live="polite">
		<div class="praktika-section-paywall__icon" aria-hidden="true">
			<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
				<rect x="10" y="20" width="28" height="22" rx="4" stroke="currentColor" stroke-width="2"/>
				<path d="M16 20v-4a8 8 0 0 1 16 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				<circle cx="24" cy="31" r="3" fill="currentColor"/>
			</svg>
		</div>
		<p class="praktika-section-paywall__label"><?php esc_html_e('Доступно по подписке', 'yoga'); ?></p>
		<p class="praktika-section-paywall__text">
			<?php esc_html_e('Оформите тариф, чтобы открыть этот раздел практики.', 'yoga'); ?>
		</p>
		<a href="<?php echo esc_url($tariffs_url); ?>" class="btn btn_alt praktika-section-paywall__cta">
			<span><?php echo esc_html(function_exists('yoga_get_purchase_cta_text') ? yoga_get_purchase_cta_text() : __('Выбрать тариф', 'yoga')); ?></span>
		</a>
	</div>
</div>
