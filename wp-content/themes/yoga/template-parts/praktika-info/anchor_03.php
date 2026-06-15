<?php
/**
 * Подключается из section-praktika.php внутри цикла practice_sections.
 *
 * @var array $section Текущая строка гибкого контента (ACF), макет anchor_03.
 * @var string $anchor_id То же, что $section['anchor_id'], задаётся перед подключением.
 */
?>
<span class="praktika-menu-anchor js-praktika-section-marker" id="<?php echo esc_attr($anchor_id); ?>" data-section-key="<?php echo esc_attr(isset($section_key) ? (string) $section_key : ''); ?>"></span>
<h3 class="<?php echo esc_attr($section['title_class']); ?>">
	<?php echo esc_html($section['title']); ?>
</h3>

<p><?php echo esc_html($section['intro_text']); ?></p>

<div class="praktika-quote">
	<?php echo apply_filters('the_content', $section['quote_text']); ?>
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