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
	<?php
		$quote_html = apply_filters('the_content', $section['quote_text']);
		echo function_exists('yoga_practice_content_images_lightbox')
			? yoga_practice_content_images_lightbox($quote_html)
			: $quote_html;
	?>
	<span class="praktika-quote__author">
		<?php echo esc_html($section['quote_author']); ?>
	</span>
</div>

<p><?php echo esc_html($section['before_list_text']); ?></p>

<ul>
    <?php
		$habits = explode("\n", $section['habits_text']);
		foreach ($habits as $habit) :
        $habit = trim($habit);
	if (!empty($habit)) : ?>
	<li><?php echo esc_html($habit); ?></li>
	<?php endif;
	endforeach; ?>
</ul>

<p><?php echo esc_html($section['conclusion_text']); ?></p>
