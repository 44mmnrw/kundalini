<?php
	/**
		* Модальное окно мобильного меню
	*/
	$theme_uri = get_template_directory_uri();
?>
<div class="modal-mobile-menu">
    <div class="modal-close">
        <img src="<?php echo esc_url($theme_uri . '/assets/img/modal-close-img.png'); ?>" alt="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
	</div>
    <div class="mobile-menu-inner">
        <div class="mobile-menu">
            <div class="mobile-menu__slide mobile-menu__slide_main active">
                <nav class="mobile-menu-main">
					<?php
						wp_nav_menu( array(
						'theme_location' => 'primary',
						'container' => false,
						'items_wrap' => '<ul>%3$s</ul>',
						'walker' => new Mobile_Menu_Walker()
						) );
					?>
				</nav>
			</div>
            <div class="mobile-menu__slide mobile-menu__slide_sub">
                <div class="mobile-menu-back">
                    <img src="<?php echo esc_url($theme_uri . '/assets/img/mobile-menu-back.png'); ?>" alt="<?php esc_attr_e('Назад', 'yoga'); ?>">
				</div>
                <div class="mobile-menu-switches">
					<?php
						// Получаем родительские термины таксономии
						$parent_terms = get_terms(array(
						'taxonomy' => 'practice-type',
						'parent' => 0,
						'hide_empty' => false,
						'orderby' => 'name',
						'order' => 'ASC'
						));
						
						if (!empty($parent_terms) && !is_wp_error($parent_terms)) :
						$first = true;
						foreach ($parent_terms as $index => $term) :
					?>
					<div class="mobile-menu-switches__item <?php echo $first ? 'active' : ''; ?>" data-target="<?php echo $index + 1; ?>">
						<span><?php echo esc_html($term->name); ?></span>
					</div>
					<?php
						$first = false;
						endforeach;
						endif;
					?>
				</div>
				
				<?php
					if (!empty($parent_terms) && !is_wp_error($parent_terms)) :
					foreach ($parent_terms as $index => $parent_term) :
					// Получаем дочерние термины для каждого родителя
					$child_terms = get_terms(array(
					'taxonomy' => 'practice-type',
					'parent' => $parent_term->term_id,
					'hide_empty' => false,
					'orderby' => 'name',
					'order' => 'ASC'
					));
				?>
				<nav class="mobile-menu-sub <?php echo $index === 0 ? 'active' : ''; ?>" data-target="<?php echo $index + 1; ?>">
					<ul>
						<li class="mobile-menu-sub-item">
							<a href="<?php echo get_term_link($parent_term); ?>">
								
							</a>
							<span>Все практики</span>
						</li>
						
						<?php if (!empty($child_terms) && !is_wp_error($child_terms)) : ?>
						<?php foreach ($child_terms as $child_term) : ?>
                        <li class="mobile-menu-sub-item">
                            <a href="<?php echo get_term_link($child_term); ?>">
							</a>
							<span><?php echo esc_html($child_term->name); ?></span>
						</li>
						<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</nav>
				<?php
					endforeach;
					endif;
				?>
			</div>
		</div>
        <div class="btn btn_alt modal-call_login">
            <span><?php echo esc_html(yoga_get_purchase_cta_text()); ?></span>
		</div>
	</div>
</div>