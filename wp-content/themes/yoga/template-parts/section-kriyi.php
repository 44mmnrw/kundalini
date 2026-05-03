<?php
	/**
		* Шаблон для дочерних терминов таксономии 'practice-type' - Крийи
	*/
	
	$current_term = get_queried_object();
	$parent_term = get_term($current_term->parent, 'practice-type');
	
	// Получаем соседние термины (того же уровня)
	$sibling_terms = get_terms(array(
    'taxonomy' => 'practice-type',
    'parent' => $current_term->parent,
    'hide_empty' => false,
    'exclude' => array($current_term->term_id),
    'orderby' => 'name',
    'order' => 'ASC'
	));
	
	// Получаем практики для текущего дочернего термина
	$practices = new WP_Query(array(
    'post_type' => 'practice',
    'tax_query' => array(
	array(
	'taxonomy' => 'practice-type',
	'field' => 'term_id',
	'terms' => $current_term->term_id,
	)
    ),
    'posts_per_page' => -1,
	));
	
	// Получаем количество найденных практик
	$practices_count = $practices->found_posts;
?>

<section class="section-kriyi" id="section-kriyi">
    <div class="container">
        <div class="row">
            <div class="kriyi-form">
                <form action="#">
                    <div class="kriyi-form-main">
                        <div class="form-search">
                            <div class="form-categories">
                                <div class="form-categories__value">
                                    <span data-target="<?php echo $parent_term->term_id; ?>">
                                        <?php echo $parent_term->name; ?>
									</span>
                                    <span class="active" data-target="<?php echo $current_term->term_id; ?>">
                                        <?php echo $current_term->name; ?>
									</span>
                                    <?php foreach ($sibling_terms as $sibling_term): ?>
									<span data-target="<?php echo $sibling_term->term_id; ?>">
										<?php echo $sibling_term->name; ?>
									</span>
                                    <?php endforeach; ?>
								</div>
							</div>
                            <input type="text" class="input" placeholder="Что ищете?" required>
                            <input type="submit" id="library-btn">
                            <label for="library-btn" class="form-search__btn">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/library-btn-arrow.png" class="active" alt="">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/library-btn-arrow_purple.png" alt="">
							</label>
                            <div class="form-search-list">
                                <!-- Здесь будет динамический список поиска -->
							</div>
                            <div class="form-cat-list">
                                <div class="form-cat-list__item" data-target="<?php echo $parent_term->term_id; ?>">
                                    <span><?php echo $parent_term->name; ?></span>
								</div>
                                <div class="form-cat-list__item active" data-target="<?php echo $current_term->term_id; ?>">
                                    <span><?php echo $current_term->name; ?></span>
								</div>
                                <?php foreach ($sibling_terms as $sibling_term): ?>
								<div class="form-cat-list__item" data-target="<?php echo $sibling_term->term_id; ?>">
									<span><?php echo $sibling_term->name; ?></span>
								</div>
                                <?php endforeach; ?>
							</div>
						</div>
                        <div class="filter-btn">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/filter-img.png" alt="" class="filter-btn__img filter-btn__img_main active">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/filter-close.png" alt="" class="filter-btn__img">
                            <span>1</span>
						</div>
					</div>
                    <div class="filter">
                        <!-- Фильтр по сложности -->
                        <div class="filter-item">
                            <div class="filter-item__main">
                                <span>По сложности</span>
							</div>
                            <div class="filter-item__list">
                                <?php
									$difficulty_terms = get_terms(array(
                                    'taxonomy' => 'practice-difficulty',
                                    'hide_empty' => false,
									));
									
									if (!empty($difficulty_terms) && !is_wp_error($difficulty_terms)) {
										$i = 1;
										foreach ($difficulty_terms as $term) {
											echo '<input type="checkbox" id="filter-dif_' . sprintf('%02d', $i) . '" name="practice-difficulty" value="' . esc_attr($term->term_id) . '">';
											echo '<label for="filter-dif_' . sprintf('%02d', $i) . '" class="checkbox-item">';
											echo '<div class="checkbox"></div>';
											echo '<span>' . esc_html(yoga_get_practice_difficulty_label($term)) . '</span>';
											echo '</label>';
											$i++;
										}
									}
								?>
							</div>               
						</div>
                        
                        <!-- Фильтр по продолжительности -->
                        <div class="filter-item">
                            <div class="filter-item__main">
                                <span>По продолжительности</span>
							</div>
                            <div class="filter-item__list">
                                <?php
									$duration_terms = get_terms(array(
                                    'taxonomy' => 'practice-duration',
                                    'hide_empty' => false,
                                    'orderby' => 'name',
                                    'order' => 'ASC'
									));
									
									if (!empty($duration_terms) && !is_wp_error($duration_terms)) {
										$i = 1;
										foreach ($duration_terms as $term) {
											echo '<input type="checkbox" id="filter-time_' . sprintf('%02d', $i) . '" name="practice-duration" value="' . esc_attr($term->term_id) . '">';
											echo '<label for="filter-time_' . sprintf('%02d', $i) . '" class="checkbox-item">';
											echo '<div class="checkbox"></div>';
											echo '<span>' . $term->name . '</span>';
											echo '</label>';
											$i++;
										}
									}
								?>
							</div>               
						</div>
                        
                        <!-- Фильтр по цели -->
                        <div class="filter-item">
                            <div class="filter-item__main">
                                <span>По цели</span>
							</div>
                            <div class="filter-item__list">
                                <?php
									$goal_terms = get_terms(array(
                                    'taxonomy' => 'practice-goal',
                                    'hide_empty' => false,
									));
									
									if (!empty($goal_terms) && !is_wp_error($goal_terms)) {
										$i = 1;
										foreach ($goal_terms as $term) {
											echo '<input type="checkbox" id="filter-goal_' . sprintf('%02d', $i) . '" name="practice-goal" value="' . esc_attr($term->term_id) . '">';
											echo '<label for="filter-goal_' . sprintf('%02d', $i) . '" class="checkbox-item">';
											echo '<div class="checkbox"></div>';
											echo '<span>' . $term->name . '</span>';
											echo '</label>';
											$i++;
										}
									}
								?>
							</div>               
						</div>
                        
                        <!-- Фильтр по типу -->
                        <div class="filter-item">
                            <div class="filter-item__main">
                                <span>По типу</span>
							</div>
                            <div class="filter-item__list">
                                <?php
									$type_terms = get_terms(array(
                                    'taxonomy' => 'practice-type',
                                    'hide_empty' => false,
                                    'exclude' => array($current_term->term_id, $parent_term->term_id),
									));
									
									if (!empty($type_terms) && !is_wp_error($type_terms)) {
										$i = 1;
										foreach ($type_terms as $term) {
											echo '<input type="checkbox" id="filter-type_' . sprintf('%02d', $i) . '" name="practice-type" value="' . esc_attr($term->term_id) . '">';
											echo '<label for="filter-type_' . sprintf('%02d', $i) . '" class="checkbox-item">';
											echo '<div class="checkbox"></div>';
											echo '<span>' . $term->name . '</span>';
											echo '</label>';
											$i++;
										}
									}
								?>
							</div>               
						</div>
                        
                        <input type="reset" id="filt-reset">
                        <label for="filt-reset" class="form-reset">
                            <div class="form-reset__icon">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/form-reset-icon.png" alt="" class="active">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/form-reset-icon_active.png" alt="">
							</div>
                            <span>Очистить</span>
						</label>
					</div>
                    
                    <div class="sorting">
                        <span class="sorting__result">
                            Найдено: <?php echo $practices_count; ?>
						</span>
                        <div class="sorting-item">
                            <div class="sorting-item__main">
                                <span>По популярности</span>
							</div>
                            <div class="sorting-item__list">
                                <div class="sorting-item__list-item active" data-target="popularity">
                                    <span>По популярности</span>
								</div>
                                <div class="sorting-item__list-item" data-target="newness">
                                    <span>По новизне</span>
								</div>
							</div>               
						</div>
					</div>
				</form>
			</div>
		</div>
        <div class="row">
            <div class="kriyi">
                <div class="kriyi__items">
                    <?php if ($practices->have_posts()): ?>
					<?php 
                        $count = 0;
                        while ($practices->have_posts()): $practices->the_post(); 
						$count++;
						// Получаем данные из ACF
						$practice_level = get_field('level') ?: 'Начинающий';
						$practice_description = get_field('short_description') ?: get_the_excerpt();
						$practice_image = get_field('image') ?: get_template_directory_uri() . '/assets/img/kriya-img_01.png';
						$user_id = get_current_user_id();
						$is_favorite = in_array(get_the_id(), get_user_meta($user_id, 'favorite_practices', true) ?: array());
						// Определяем классы для скрытия элементов после 10-го
						$hidden_class = ($count > 10) ? 'hidden' : '';
					?>
					
					<div class="kriyi-item <?php echo $hidden_class; ?>">
						<div class="kriyi-item__inner">
							<a href="<?php the_permalink(); ?>"></a>
							<span class="kriya-level">
								<?php echo esc_html($practice_level); ?>
							</span>
							<div class="kriya-info">
								<h3><?php the_title(); ?></h3>
								<p><?php echo esc_html($practice_description); ?></p>
							</div>
							<div class="kriya-media">
								<div class="kriya-img">
									<img src="<?php echo esc_url($practice_image); ?>" alt="<?php the_title(); ?>">
								</div>
								<div class="kriya-fav fav"  data-practice-id="<?php echo get_the_id(); ?>">
									<svg class="<?php echo !$is_favorite ? 'active' : ''; ?>" aria-hidden="true"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
									<svg class="<?php echo $is_favorite ? 'active' : ''; ?>" aria-hidden="true"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart-filled"></use></svg>
								</div>
								<div class="kriya-btn">
									<div class="kriya-btn__arrow">
										<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
										<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
									</div>   
								</div>
							</div>      
						</div>
					</div>
					
					<?php endwhile; ?>
					
					<!-- Дополнительные скрытые элементы -->
					<?php if ($practices_count > 10): ?>
					<?php $placeholder_is_favorite = false; ?>
					<?php for ($i = 0; $i < 2; $i++): ?>
					<div class="kriyi-item kriyi-item_last hidden">
						<div class="kriyi-item__inner">
							<a href="#"></a>
							<span class="kriya-level">Начинающий</span>
							<div class="kriya-info">
								<h3>Остальные крийи</h3>
								<p>Показать все доступные практики</p>
							</div>
							<div class="kriya-media">
								<div class="kriya-img">
									<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-img_01.png" alt="Остальные крийи">
								</div>
								<div class="kriya-fav">
									<svg class="<?php echo !$placeholder_is_favorite ? 'active' : ''; ?>" aria-hidden="true"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
									<svg class="<?php echo $placeholder_is_favorite ? 'active' : ''; ?>" aria-hidden="true"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart-filled"></use></svg>
								</div>
								<div class="kriya-btn">
									<div class="kriya-btn__arrow">
										<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
										<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
									</div>   
								</div>
							</div> 
						</div>
					</div>
					<?php endfor; ?>
					<?php endif; ?>
					
                    <?php else: ?>
					<p class="no-practices">В этой категории пока нет практик.</p>
                    <?php endif; ?>
                    <?php wp_reset_postdata(); ?>
				</div>
                
                <?php if ($practices_count > 10): ?>
                <div class="btn">
                    <span class="active">Показать еще</span>
                    <span>Свернуть</span>
				</div>
                <?php endif; ?>
			</div>
		</div>
	</div>
</section>

<script>
	jQuery(document).ready(function($) {
		// Обработчик кнопки "Показать еще/Свернуть"
		$('.btn').on('click', function() {
			$(this).toggleClass('active');
			$('.kriyi-item.hidden').toggleClass('hidden');
		});
		
		// Надежный обработчик избранного для страницы крий (без practice-notification).
		$(document).off('click', '.fav');
		$(document).on('click', '.fav', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var $fav = $(this);
			var practiceId = $fav.data('practice-id');
			if (!practiceId || typeof yoga_ajax === 'undefined') {
				return;
			}
			
			$('.practice-notification').remove();
			
			$.ajax({
				url: yoga_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'toggle_favorite_practice',
					practice_id: practiceId,
					security: yoga_ajax.nonce
				},
				success: function(response) {
					if (response && response.success) {
						$fav.find('img, svg').toggleClass('active');
						$('.practice-notification').remove();
						showFavoriteModalLocal((response.data && response.data.message) ? response.data.message : 'Избранное обновлено');
					} else if (response && response.data && response.data.message) {
						showFavoriteModalLocal(response.data.message);
					}
				},
				error: function(xhr) {
					if (xhr.status === 401) {
						showFavoriteModalLocal('Для добавления в избранное авторизуйтесь');
					} else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						showFavoriteModalLocal(xhr.responseJSON.data.message);
					} else {
						showFavoriteModalLocal('Избранное не обновлено');
					}
				}
			});
		});
		
		function showFavoriteModalLocal(message) {
			var $modal = $('.modal-default_favoritesucces');
			if (!$modal.length) {
				return;
			}
			$modal.find('.favorite-modal-message').text(message || 'Избранное обновлено');
			$('.overlay').addClass('active');
			$('.modal').removeClass('active');
			$('.modal-login').removeClass('active');
			$modal.addClass('active');
		}
		
	});
</script>