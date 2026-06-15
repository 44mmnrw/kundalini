<?php
/**
 * Подключается из section-praktika.php внутри цикла practice_sections.
 *
 * @var array $section Текущая строка гибкого контента (ACF), макет anchor_02.
 * @var string $anchor_id То же, что $section['anchor_id'], задаётся перед подключением.
 */
?>
<span class="praktika-menu-anchor" id="<?php echo esc_attr($anchor_id); ?>"></span>
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