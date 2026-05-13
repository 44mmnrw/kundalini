<?php
	/**
		* Модальное окно меню (десктопная версия)
	*/
	$theme_uri = get_template_directory_uri();
?><div class="modal-menu">
    <div class="container">
        <div class="modal-menu-inner">
			<div class="modal-menu-inner">
				<?php
					$parent_terms = get_terms(array(
					'taxonomy' => 'practice-type',
					'parent' => 0,
					'hide_empty' => false,
					'orderby' => 'name',
					'order' => 'ASC'
					));
					
					if (!empty($parent_terms) && !is_wp_error($parent_terms)) :
					foreach ($parent_terms as $parent_term) :
					$is_available = yoga_is_practice_type_term_available($parent_term);
					
					$column_class = $is_available ? 'modal-menu-column' : 'modal-menu-column modal-menu-column_unavailable';
				?>
				<div class="<?php echo esc_attr($column_class); ?>">
					<h2><?php echo esc_html($parent_term->name); ?></h2>
					
					<?php if (!$is_available) : ?>
                    <span class="modal-menu-unavailable">в разработке</span>
					<?php endif; ?>
					
					<nav>
						<ul>
							<li class="modal-menu-item">
								<?php if ($is_available) : ?>
                                <a href="<?php echo esc_url(get_term_link($parent_term)); ?>"></a>
                                <span class="modal-menu-item__text">Все практики</span>
                                <div class="btn-icon">
                                    <svg class="btn-icon-arrow btn-icon-arrow_black active" aria-hidden="true" focusable="false">
                                        <use href="<?php echo esc_url($theme_uri . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                                    </svg>
                                    <svg class="btn-icon-arrow btn-icon-arrow_green" aria-hidden="true" focusable="false">
                                        <use href="<?php echo esc_url($theme_uri . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                                    </svg>
								</div>
								<?php else : ?>
                                <a href="#"></a>
                                <span class="modal-menu-item__text">Все практики</span>
                                <div class="btn-icon">
                                    <img src="<?php echo esc_url($theme_uri . '/assets/img/btn-arrow_grey.png'); ?>" alt="" class="active">
								</div>
								<?php endif; ?>
							</li>
							
							<?php
								$child_terms = get_terms(array(
								'taxonomy' => 'practice-type',
								'parent' => $parent_term->term_id,
								'hide_empty' => false,
								'orderby' => 'name',
								'order' => 'ASC'
								));
								
								if (!empty($child_terms) && !is_wp_error($child_terms)) :
								foreach ($child_terms as $child_term) :
							?>
							<li class="modal-menu-item">
								<?php if ($is_available) : ?>
								<a href="<?php echo esc_url(get_term_link($child_term)); ?>"></a>
								<span class="modal-menu-item__text"><?php echo esc_html($child_term->name); ?></span>
								<div class="btn-icon">
									<svg class="btn-icon-arrow btn-icon-arrow_black active" aria-hidden="true" focusable="false">
										<use href="<?php echo esc_url($theme_uri . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
									</svg>
									<svg class="btn-icon-arrow btn-icon-arrow_green" aria-hidden="true" focusable="false">
										<use href="<?php echo esc_url($theme_uri . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
									</svg>
								</div>
								<?php else : ?>
								<a href="#"></a>
								<span class="modal-menu-item__text"><?php echo esc_html($child_term->name); ?></span>
								<div class="btn-icon">
									<img src="<?php echo esc_url($theme_uri . '/assets/img/btn-arrow_grey.png'); ?>" alt="" class="active">
								</div>
								<?php endif; ?>
							</li>
							<?php
								endforeach;
								endif;
							?>
						</ul>
					</nav>
				</div>
				<?php
					endforeach;
					endif;
				?>
			</div>
		</div>
	</div>
</div>