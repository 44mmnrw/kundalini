<span class="praktika-menu-anchor" id="<?php echo esc_attr($section['anchor_id']); ?>"></span>
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