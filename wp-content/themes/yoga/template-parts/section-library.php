<?php
	// Получаем текущий термин таксономии
	$current_term = get_queried_object();
	
	// Проверяем, является ли термин родительским
	$is_parent = empty($current_term->parent);
	//var_dump($is_parent, $current_term->term_id);
	// Получаем дочерние термины, если это родительская категория
	if ($is_parent) {
		$child_terms = get_terms(array(
        'taxonomy' => 'practice-type',
        'parent' => $current_term->term_id,
        'hide_empty' => false,
		));
	}
?>
<section class="section-library" id="section-library">
	<div class="container">
		<div class="row">
			<div class="library-form">
				<form id="practice-filter-form" action="#" method="post">
					<div class="library-form-main">
						<div class="form-search">
							<div class="form-categories">
								<div class="form-categories__value">
									<?php
										$i = 0;
										// Сначала родительская категория
										echo '<span data-target="' . esc_attr($current_term->term_id) . '" class="active">';
										echo esc_html($current_term->name);
										echo '</span>';
										
										// Потом дочерние категории
										if (!empty($child_terms)) {
											foreach ($child_terms as $cat) {
												echo '<span data-target="' . esc_attr($cat->term_id) . '">';
												echo esc_html($cat->name);
												echo '</span>';
											}
										}
									?>
								</div>
							</div>
							<input type="text" class="input" name="s" placeholder="Что ищете?" required>
							<input type="submit" id="library-btn">
							<label for="library-btn" class="form-search__btn">
								<img src="<?php echo get_template_directory_uri(); ?>/assets/img/library-btn-arrow.png" class="active" alt="">
								<img src="<?php echo get_template_directory_uri(); ?>/assets/img/library-btn-arrow_purple.png" alt="">
							</label>
							
							<div class="form-search-list" style="display:none;"></div>
							
							<div class="form-cat-list">
								<?php
									// Родитель
									echo '<div class="form-cat-list__item active" data-target="' . esc_attr($current_term->term_id) . '">';
									echo '<span>' . esc_html($current_term->name) . '</span>';
									echo '</div>';
									
									// Дети
									if (!empty($child_terms)) {
										foreach ($child_terms as $cat) {
											echo '<div class="form-cat-list__item" data-target="' . esc_attr($cat->term_id) . '">';
											echo '<span>' . esc_html($cat->name) . '</span>';
											echo '</div>';
										}
									}
								?>
							</div>
							
						</div>
						
						<div class="filter-btn">
							<img src="<?php echo get_template_directory_uri(); ?>/assets/img/filter-img.png" alt="" class="filter-btn__img filter-btn__img_main active">
							<img src="<?php echo get_template_directory_uri(); ?>/assets/img/filter-close.png" alt="" class="filter-btn__img">
							<span>1</span>
						</div>
					</div>
					
					<div class="filter">
						<?php
							$filters = [
							'practice-difficulty' => 'По сложности',
							'practice-duration'   => 'По продолжительности',
							'practice-goal'       => 'По цели',
							];
							foreach ($filters as $taxonomy => $title) {
								$terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
								if ($terms && !is_wp_error($terms)) {
									echo '<div class="filter-item">';
									echo '<div class="filter-item__main"><span>' . esc_html($title) . '</span></div>';
									echo '<div class="filter-item__list">';
									$j = 1;
									foreach ($terms as $term) {
										$id = $taxonomy . '_' . $j;
										echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($taxonomy) . '[]" value="' . esc_attr($term->slug) . '">';
										echo '<label for="' . esc_attr($id) . '" class="checkbox-item">';
										echo '<div class="checkbox"></div>';
										echo '<span>' . esc_html($term->name) . '</span>';
										echo '</label>';
										$j++;
									}
									echo '</div></div>';
								}
							}
						?>
						<input type="reset" id="filt-reset">
						<label for="filt-reset" class="form-reset">
							<div class="form-reset__icon">
								<img src="<?php echo get_template_directory_uri(); ?>/assets/img/form-reset-icon.png" alt="" class="active">
								<img src="<?php echo get_template_directory_uri(); ?>/assets/img/form-reset-icon_active.png" alt="">
							</div>
							<span>Очистить</span>
						</label>
					</div>
				</form>
			</div>
		</div>
		
		<div class="row">
			<div class="library" id="practice-list">
				<?php
					$query = new WP_Query([
					'post_type' => 'practice',
					'tax_query' => [
					[
					'taxonomy' => 'practice-type',
					'field'    => 'slug',
					'terms'    => get_queried_object()->slug,
					],
					],
					'posts_per_page' => -1,
					]);
					
					if ($query->have_posts()) :
					while ($query->have_posts()) : $query->the_post();
				?>
				<div class="library-item">
					<div class="library-item__bg"></div>
					<div class="library-item__cat">
						<?php
							$cats = wp_get_post_terms(get_the_ID(), 'practice-type');
							if ($cats) echo esc_html($cats[0]->name);
						?>
						<a href="<?php the_permalink(); ?>" target="_blank"></a>
					</div>
					<p class="library-item__text"><?php echo get_the_excerpt(); ?></p>
					<div class="library-item__img">
						<?php if (has_post_thumbnail()) the_post_thumbnail('medium'); ?>
					</div>
					<div class="library-item__btn">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/library-item-btn.png" alt="">
					</div>
					<a href="<?php the_permalink(); ?>" class="library-item__link"></a>
				</div>
				<?php
					endwhile;
					wp_reset_postdata();
					else :
					echo '<p>Практик не найдено.</p>';
					endif;
				?>
			</div>
		</div>
	</div>
</section>
