<?php
/**
 * Переиспользуемый шаблонный блок: section paywall.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

$tariffs_url          = function_exists('yoga_get_tariffs_page_url') ? yoga_get_tariffs_page_url() : home_url('/');
$practice_id          = (int) get_the_ID();
$section_tariff_label = function_exists('yoga_get_practice_section_allowed_tariff_label')
	? yoga_get_practice_section_allowed_tariff_label(is_array($section ?? null) ? $section : array())
	: '';
$paywall_label        = $section_tariff_label !== ''
	? $section_tariff_label
	: (function_exists('yoga_get_section_paywall_label')
		? yoga_get_section_paywall_label()
		: __('Доступно по подписке', 'yoga'));
$paywall_text         = function_exists('yoga_get_section_paywall_text')
	? yoga_get_section_paywall_text($practice_id)
	: __('Оформите тариф, чтобы открыть этот раздел практики.', 'yoga');
?>
<span class="praktika-menu-anchor js-praktika-section-marker" id="<?php echo esc_attr($anchor_id); ?>" data-section-key="<?php echo esc_attr(isset($section_key) ? (string) $section_key : ''); ?>"></span>
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
		<div class="praktika-section-paywall__copy">
			<p class="praktika-section-paywall__label"><?php echo esc_html($paywall_label); ?></p>
			<p class="praktika-section-paywall__text"><?php echo esc_html($paywall_text); ?></p>
		</div>
		<a href="<?php echo esc_url($tariffs_url); ?>" class="btn btn_alt btn_icon praktika-section-paywall__cta">
			<span><?php echo esc_html(function_exists('yoga_get_purchase_cta_text') ? yoga_get_purchase_cta_text() : __('Выбрать тариф', 'yoga')); ?></span>
			<div class="btn-icon" aria-hidden="true">
				<svg class="btn-icon-arrow btn-icon-arrow_black active" focusable="false">
					<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
				</svg>
				<svg class="btn-icon-arrow btn-icon-arrow_green" focusable="false">
					<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
				</svg>
			</div>
		</a>
	</div>
</div>
