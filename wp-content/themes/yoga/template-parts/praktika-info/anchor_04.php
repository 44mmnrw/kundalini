<?php
/**
 * Переиспользуемый шаблонный блок: anchor 04.
 *
 * @package Yoga
 */
?>
<span class="praktika-menu-anchor js-praktika-section-marker" id="<?php echo esc_attr($anchor_id); ?>" data-section-key="<?php echo esc_attr(isset($section_key) ? (string) $section_key : ''); ?>"></span>
<h3 class="<?php echo esc_attr($section['title_class']); ?>">
	<?php echo esc_html($section['title']); ?>
</h3>

<ul>
	<?php
        $recommendations = explode("\n", $section['recommendations_text']);
        foreach ($recommendations as $recommendation) :
		$recommendation = trim($recommendation);
	if (!empty($recommendation)) : ?>
	<li><?php echo esc_html($recommendation); ?></li>
	<?php endif;
	endforeach; ?>
</ul>