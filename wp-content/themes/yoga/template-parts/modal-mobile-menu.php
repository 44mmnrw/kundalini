<?php
	/**
	 * Модальное окно мобильного меню (Figma pop_up 582:10836).
	 * Иконки закрытия / назад / стрелки — sprite.svg (единый спрайт).
	 */
	$theme_uri   = get_template_directory_uri();
	$sprite_href = esc_url($theme_uri . '/assets/svg/sprite.svg');
?>
<div class="modal-mobile-menu">
    <div class="modal-close">
		<svg class="modal-close__icon" viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false">
			<use href="<?php echo $sprite_href; ?>#lk-modal-close" width="100%" height="100%"></use>
		</svg>
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
                <div class="mobile-menu-back" role="button" tabindex="0" aria-label="<?php esc_attr_e('Назад', 'yoga'); ?>">
					<svg class="mobile-menu-back__icon" viewBox="0 0 9 16" width="9" height="16" aria-hidden="true" focusable="false">
						<use href="<?php echo $sprite_href; ?>#lk-library-chevron" width="100%" height="100%"></use>
					</svg>
				</div>
                <div class="mobile-menu-switches">
					<?php
						// Родительские термины practice-type; «в разработке» — как в modal-menu.php
						$parent_terms = get_terms(array(
							'taxonomy'   => 'practice-type',
							'parent'     => 0,
							'hide_empty' => false,
							'orderby'    => 'name',
							'order'      => 'ASC',
						));
						$parent_terms = (!empty($parent_terms) && !is_wp_error($parent_terms)) ? $parent_terms : array();

						$active_switch_idx = 0;
						foreach ($parent_terms as $idx => $t_switch) {
							if (function_exists('yoga_is_practice_type_term_available') && yoga_is_practice_type_term_available($t_switch)) {
								$active_switch_idx = (int) $idx;
								break;
							}
						}

						foreach ($parent_terms as $index => $term) :
							$pt_available   = function_exists('yoga_is_practice_type_term_available') ? yoga_is_practice_type_term_available($term) : true;
							$is_tab_active  = ((int) $index === $active_switch_idx);
							$tab_classes    = 'mobile-menu-switches__item';
							if ($is_tab_active) {
								$tab_classes .= ' active';
							}
							if (!$pt_available) {
								$tab_classes .= ' mobile-menu-switches__item_unavailable';
							}
					?>
					<div class="<?php echo esc_attr($tab_classes); ?>" data-target="<?php echo (int) $index + 1; ?>"<?php echo $pt_available ? '' : ' data-unavailable="1" aria-disabled="true"'; ?>>
						<span><?php echo esc_html($term->name); ?></span>
						<?php if (!$pt_available) : ?>
						<span class="mobile-menu-switch-unavailable"><?php esc_html_e('в разработке', 'yoga'); ?></span>
						<?php endif; ?>
					</div>
					<?php
						endforeach;
					?>
				</div>
				
				<?php foreach ($parent_terms as $index => $parent_term) : ?>
					<?php
					$pt_available = function_exists('yoga_is_practice_type_term_available') ? yoga_is_practice_type_term_available($parent_term) : true;
					$child_terms  = get_terms(array(
						'taxonomy'   => 'practice-type',
						'parent'     => $parent_term->term_id,
						'hide_empty' => false,
						'orderby'    => 'name',
						'order'      => 'ASC',
					));
					$sub_active = ((int) $index === $active_switch_idx);
					$sub_class  = 'mobile-menu-sub' . ($sub_active ? ' active' : '') . (!$pt_available ? ' mobile-menu-sub_unavailable' : '');
					?>
				<nav class="<?php echo esc_attr(trim($sub_class)); ?>" data-target="<?php echo (int) $index + 1; ?>">
					<ul>
						<li class="mobile-menu-sub-item<?php echo $pt_available ? '' : ' mobile-menu-sub-item_unavailable'; ?>">
							<?php if ($pt_available) : ?>
							<a href="<?php echo esc_url(get_term_link($parent_term)); ?>"></a>
							<?php endif; ?>
							<span><?php esc_html_e('Все практики', 'yoga'); ?></span>
							<span class="mobile-menu-sub-item__chevron" aria-hidden="true">
								<svg class="mobile-menu-sub-item__chevron-svg" viewBox="0 0 20 20" width="20" height="20" focusable="false">
									<use href="<?php echo $sprite_href; ?>#slick-arrow" width="100%" height="100%"></use>
								</svg>
							</span>
						</li>
						
						<?php if (!empty($child_terms) && !is_wp_error($child_terms)) : ?>
						<?php foreach ($child_terms as $child_term) : ?>
                        <li class="mobile-menu-sub-item<?php echo $pt_available ? '' : ' mobile-menu-sub-item_unavailable'; ?>">
							<?php if ($pt_available) : ?>
                            <a href="<?php echo esc_url(get_term_link($child_term)); ?>"></a>
							<?php endif; ?>
							<span><?php echo esc_html($child_term->name); ?></span>
							<span class="mobile-menu-sub-item__chevron" aria-hidden="true">
								<svg class="mobile-menu-sub-item__chevron-svg" viewBox="0 0 20 20" width="20" height="20" focusable="false">
									<use href="<?php echo $sprite_href; ?>#slick-arrow" width="100%" height="100%"></use>
								</svg>
							</span>
						</li>
						<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</nav>
				<?php endforeach; ?>
			</div>
		</div>
        <div class="btn btn_alt modal-call_login">
            <span><?php echo esc_html(yoga_get_purchase_cta_text()); ?></span>
		</div>
	</div>
</div>