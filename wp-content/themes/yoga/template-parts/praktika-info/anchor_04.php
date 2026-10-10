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

<ul class="praktika-recommendations">
	<?php
	$recommendations = preg_split('/\R/u', (string) ($section['recommendations_text'] ?? ''));
	$has_recommendation = false;
	$paragraph_break = false;
	foreach ($recommendations as $recommendation) :
		$recommendation = trim($recommendation);
		if ($recommendation === '') {
			$paragraph_break = $has_recommendation;
			continue;
		}
		?>
		<li<?php if ($paragraph_break) : ?> class="recommendation-spaced"<?php endif; ?>><?php echo esc_html($recommendation); ?></li>
		<?php
		$has_recommendation = true;
		$paragraph_break = false;
	endforeach;
	?>
</ul>
