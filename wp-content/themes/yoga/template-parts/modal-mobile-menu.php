<?php
	/**
	 * Модальное окно мобильного меню (Figma pop_up 582:10836).
	 * Иконки закрытия / назад / стрелки — sprite.svg (единый спрайт).
	 */
	$theme_uri   = get_template_directory_uri();
	$sprite_href = esc_url($theme_uri . '/assets/svg/sprite.svg');
	$mobile_header_tariff = is_user_logged_in() && function_exists('get_current_user_tariff') ? get_current_user_tariff() : false;
	$mobile_header_tariff_label = is_array($mobile_header_tariff) && !empty($mobile_header_tariff['product_name']) ? (string) $mobile_header_tariff['product_name'] : __('Подписка не активна', 'yoga');
	$mobile_header_urls = function_exists('yoga_lk_sidebar_secondary_nav_urls') ? yoga_lk_sidebar_secondary_nav_urls() : array_fill_keys(array('tariffs'), home_url('/'));
?><div class="modal-mobile-menu">
    <div class="modal-close">
		<svg class="modal-close__icon" viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false">
			<use href="<?php echo $sprite_href; ?>#lk-modal-close" width="100%" height="100%"></use>
		</svg>
    </div>
    <div class="mobile-menu-inner">
		<?php if (is_user_logged_in()) : ?>
		<div class="mobile-header-popup__head">
			<a class="mobile-header-popup__rate" href="<?php echo esc_url($mobile_header_urls['tariffs']); ?>"><?php echo esc_html($mobile_header_tariff_label); ?></a>
		</div>
		<?php endif; ?>
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
            <div class="mobile-menu__slide mobile-menu__slide_sub mobile-library-menu">
                <button class="mobile-menu-back" type="button" aria-label="<?php esc_attr_e('Назад', 'yoga'); ?>">
					<svg class="mobile-menu-back__icon" viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false">
						<use href="<?php echo $sprite_href; ?>#password-recovery-back" width="100%" height="100%"></use>
					</svg>
				</button>
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
						$library_parent_order = array('кундалини' => 10, 'kundalini' => 10, 'хатха' => 20, 'hatha' => 20);
						usort($parent_terms, static function ($a, $b) use ($library_parent_order) {
							$a_key = function_exists('mb_strtolower') ? mb_strtolower($a->name, 'UTF-8') : strtolower($a->name);
							$b_key = function_exists('mb_strtolower') ? mb_strtolower($b->name, 'UTF-8') : strtolower($b->name);
							return ($library_parent_order[$a_key] ?? 999) <=> ($library_parent_order[$b_key] ?? 999);
						});

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
					<button type="button" class="<?php echo esc_attr($tab_classes); ?>" data-target="<?php echo (int) $index + 1; ?>"<?php echo $pt_available ? '' : ' data-unavailable="1" aria-disabled="true"'; ?>>
						<span><?php echo esc_html($term->name); ?></span>
						<?php if (!$pt_available) : ?>
						<span class="mobile-menu-switch-unavailable"><?php esc_html_e('в разработке', 'yoga'); ?></span>
						<?php endif; ?>
					</button>
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
					if (!empty($child_terms) && !is_wp_error($child_terms)) {
						$library_child_order = array(
							'разминки' => 10,
							'крийи' => 20,
							'шабды' => 30,
							'медитации' => 40,
							'мантры' => 50,
							'пранаямы' => 60,
							'теория философии кундалини-йоги' => 70,
						);
						usort($child_terms, static function ($a, $b) use ($library_child_order) {
							$a_key = function_exists('mb_strtolower') ? mb_strtolower($a->name, 'UTF-8') : strtolower($a->name);
							$b_key = function_exists('mb_strtolower') ? mb_strtolower($b->name, 'UTF-8') : strtolower($b->name);
							$a_order = $library_child_order[$a_key] ?? 999;
							$b_order = $library_child_order[$b_key] ?? 999;
							return $a_order === $b_order ? strnatcasecmp($a->name, $b->name) : $a_order <=> $b_order;
						});
					}
					$sub_active = ((int) $index === $active_switch_idx);
					$sub_class  = 'mobile-menu-sub' . ($sub_active ? ' active' : '') . (!$pt_available ? ' mobile-menu-sub_unavailable' : '');
					?>
				<nav class="<?php echo esc_attr(trim($sub_class)); ?>" data-target="<?php echo (int) $index + 1; ?>">
					<ul>
						<li class="mobile-menu-sub-item<?php echo $pt_available ? '' : ' mobile-menu-sub-item_unavailable'; ?>">
							<<?php echo $pt_available ? 'a href="' . esc_url(get_term_link($parent_term)) . '"' : 'span'; ?> class="mobile-menu-sub-item__link">
							<span><?php esc_html_e('Все практики', 'yoga'); ?></span>
							<span class="mobile-menu-sub-item__chevron" aria-hidden="true">
								<svg class="mobile-menu-sub-item__chevron-svg" viewBox="0 0 205.8 205.8" width="26" height="26" focusable="false">
									<use href="<?php echo $sprite_href; ?>#hundreds-practices-arrow" width="100%" height="100%"></use>
								</svg>
							</span>
							</<?php echo $pt_available ? 'a' : 'span'; ?>>
						</li>
						
						<?php if (!empty($child_terms) && !is_wp_error($child_terms)) : ?>
						<?php foreach ($child_terms as $child_term) : ?>
                        <li class="mobile-menu-sub-item<?php echo $pt_available ? '' : ' mobile-menu-sub-item_unavailable'; ?>">
							<<?php echo $pt_available ? 'a href="' . esc_url(get_term_link($child_term)) . '"' : 'span'; ?> class="mobile-menu-sub-item__link">
							<span><?php echo esc_html($child_term->name); ?></span>
							<span class="mobile-menu-sub-item__chevron" aria-hidden="true">
								<svg class="mobile-menu-sub-item__chevron-svg" viewBox="0 0 205.8 205.8" width="26" height="26" focusable="false">
									<use href="<?php echo $sprite_href; ?>#hundreds-practices-arrow" width="100%" height="100%"></use>
								</svg>
							</span>
							</<?php echo $pt_available ? 'a' : 'span'; ?>>
						</li>
						<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</nav>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if (is_user_logged_in()) : ?>
		<button type="button" class="mobile-header-popup__logout modal-call modal-call_logout">
			<span><?php esc_html_e('Выйти', 'yoga'); ?></span>
			<svg aria-hidden="true" focusable="false"><use href="<?php echo $sprite_href; ?>#mobile-menu-logout"></use></svg>
		</button>
		<?php endif; ?>
		<?php
		$has_paid_tariff = is_user_logged_in() && get_current_user_tariff();
		if (!$has_paid_tariff && !is_user_logged_in()) :
			$tariffs_term = get_term_by('slug', 'tariffs', 'product_cat');
			$tariffs_url = home_url('/product-category/tariffs/');
			if ($tariffs_term && !is_wp_error($tariffs_term)) {
				$term_link = get_term_link($tariffs_term);
				if (!is_wp_error($term_link)) {
					$tariffs_url = $term_link;
				}
			}
			$cta_text = esc_html(yoga_get_purchase_cta_text());
			?>
		<div class="btn btn_alt modal-call_login">
			<span><?php echo $cta_text; ?></span>
		</div>
			<?php
		endif;
		?>
	</div>
</div>
