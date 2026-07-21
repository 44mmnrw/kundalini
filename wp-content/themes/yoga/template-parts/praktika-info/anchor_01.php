<?php
/**
 * Переиспользуемый шаблонный блок: anchor 01.
 *
 * @package Yoga
 */
?>
<span class="praktika-menu-anchor js-praktika-section-marker" id="<?php echo esc_attr($anchor_id); ?>" data-section-key="<?php echo esc_attr(isset($section_key) ? (string) $section_key : ''); ?>"></span>
<h3><?php echo esc_html($section['main_title']); ?></h3>

	<?php
		$content_html = apply_filters('the_content', $section['content']);
		echo function_exists('yoga_practice_content_images_lightbox')
			? yoga_practice_content_images_lightbox($content_html)
			: $content_html;
	?>

<?php if ($section['sub_title']) : ?>
<h3><?php echo esc_html($section['sub_title']); ?></h3>
<?php endif; ?>
<?php if ($section['list_items']) : ?>
<ul class="practice-list">
	<?php
		$items = explode("\n", $section['list_items']);
		foreach ($items as $item) :
		$item = trim($item);
	if (!empty($item)) : ?>
	<li><?php echo esc_html($item); ?></li>
	<?php endif;
	endforeach; ?>
</ul>
<?php endif; ?>
