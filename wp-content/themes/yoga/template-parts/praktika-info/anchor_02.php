<?php
/**
 * Переиспользуемый шаблонный блок: anchor 02.
 *
 * @package Yoga
 */
?>
<span class="praktika-menu-anchor js-praktika-section-marker" id="<?php echo esc_attr($anchor_id); ?>" data-section-key="<?php echo esc_attr(isset($section_key) ? (string) $section_key : ''); ?>"></span>
<h3 class="<?php echo esc_attr($section['title_class']); ?>">
	<?php echo esc_html($section['title']); ?>
</h3>

<?php if ($section['effects']) : ?>
<ul class="effects-list">
	<?php
		$effects = explode("\n", $section['effects']);
		foreach ($effects as $effect) :
		$effect = trim($effect);
	if (!empty($effect)) : ?>
	<li><?php echo esc_html($effect); ?></li>
	<?php endif;
	endforeach; ?>
</ul>
<?php endif;