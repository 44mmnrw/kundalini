<?php
/**
 * Компонент темы: page.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

get_header();

while (have_posts()) {
	the_post();
	?>
	<section class="section-ways section-ways_page" id="section-ways">
		<div class="container">
			<div class="row">
				<div class="ways">
					<ul>
						<li><a href="<?php echo esc_url(home_url('/')); ?>" class="ways-item ref">Главная</a></li>
						<li><span class="ways-item"><?php the_title(); ?></span></li>
					</ul>
					<h1 class="ways-heading"><?php the_title(); ?></h1>
				</div>
			</div>
		</div>
	</section>
	<section class="section-rules section-rules_page" id="section-rules">
		<div class="container">
			<div class="row">
				<article id="post-<?php the_ID(); ?>" <?php post_class('rules'); ?>>
					<?php the_content(); ?>
				</article>
			</div>
		</div>
	</section>
	<?php
}

get_footer();
