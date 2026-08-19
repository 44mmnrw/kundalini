<?php
/**
 * Переиспользуемый шаблонный блок: anchor 03.
 *
 * @package Yoga
 */
?>
<span class="praktika-menu-anchor js-praktika-section-marker" id="<?php echo esc_attr($anchor_id); ?>" data-section-key="<?php echo esc_attr(isset($section_key) ? (string) $section_key : ''); ?>"></span>
<h3 class="<?php echo esc_attr($section['title_class']); ?>">
	<?php echo esc_html($section['title']); ?>
</h3>

<div class="praktika-text">
	<?php
		$intro_html = wp_kses_post($section['intro_text'] ?? '');
		echo function_exists('yoga_practice_content_images_lightbox')
			? yoga_practice_content_images_lightbox($intro_html)
			: $intro_html;
	?>
</div>

<div class="praktika-quote">
	<svg class="praktika-quote__before" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#praktika-quote-before'); ?>"></use></svg>
	<?php
		$quote_html = apply_filters('the_content', $section['quote_text']);
		echo function_exists('yoga_practice_content_images_lightbox')
			? yoga_practice_content_images_lightbox($quote_html)
			: $quote_html;
	?>
	<span class="praktika-quote__author">
		<?php echo esc_html($section['quote_author']); ?>
	</span>
	<svg class="praktika-quote__after" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#praktika-quote-after'); ?>"></use></svg>
</div>

<?php
$philosophy_html = (string) ($section['philosophy_content'] ?? '');
if ($philosophy_html === '' && function_exists('yoga_build_practice_philosophy_content')) {
	$philosophy_html = yoga_build_practice_philosophy_content(
		$section['before_list_text'] ?? '',
		$section['habits_text'] ?? '',
		$section['conclusion_text'] ?? ''
	);
}

if ($philosophy_html !== '') :
	$philosophy_html = wp_kses_post($philosophy_html);
	$philosophy_html = function_exists('yoga_practice_content_images_lightbox')
		? yoga_practice_content_images_lightbox($philosophy_html)
		: $philosophy_html;
	?>
	<div class="praktika-text praktika-philosophy-text">
		<?php echo $philosophy_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
<?php endif; ?>
