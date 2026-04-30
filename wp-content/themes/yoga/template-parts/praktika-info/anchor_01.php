<span class="praktika-menu-anchor" id="<?php echo esc_attr($anchor_id); ?>"></span>
<h3><?php echo esc_html($section['main_title']); ?></h3>

	<?php echo apply_filters('the_content', $section['content']); ?>

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