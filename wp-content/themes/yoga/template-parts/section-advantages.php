<!-- Секция преимуществ -->
<section class="section-advantages" id="section-advantages">
	<div class="container">
		<div class="row">
			<div class="advantages">
				<?php
                    // Проверяем, есть ли преимущества
                    if (have_rows('advantages')):
					$animation_classes = ['fadeInLeft', 'fadeInUp', 'fadeInRight'];
					$index = 0;
					
					// Цикл для вывода преимуществ
					while (have_rows('advantages')): the_row();
					$icon = get_sub_field('advantage_icon');
					$title = get_sub_field('advantage_title');
					$text = get_sub_field('advantage_text');
					
					// Определяем класс анимации (циклически)
					$animation_class = $animation_classes[$index % count($animation_classes)];
				?>
				
				<div class="advantages-item animated <?php echo $animation_class; ?> delay-1s">
					<?php if ($icon): ?>
					<img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($icon['alt']); ?>" class="advantages-item__icon">
					<?php endif; ?>
					
					<?php if ($title): ?>
					<h5><?php echo esc_html($title); ?></h5>
					<?php endif; ?>
					
					<?php if ($text): ?>
					<p><?php echo esc_html($text); ?></p>
					<?php endif; ?>
				</div>
				
				<?php
					$index++;
					endwhile;
                    else:
					// Выводим заглушку, если нет преимуществ
				?>
				<div class="advantages-item animated fadeInLeft delay-1s">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/advantages-icon_01-min.png" alt="" class="advantages-item__icon">
					<h5>Встроенный таймер</h5>
					<p>позволяет управлять временем практики не отвлекаясь</p>
				</div>
				<div class="advantages-item animated fadeInUp delay-1s">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/advantages-icon_02-min.png" alt="" class="advantages-item__icon">
					<h5>Аудиосопровождение</h5>
					<p>усиливает концентрацию и погружение в практику</p>
				</div>
				<div class="advantages-item animated fadeInRight delay-1s">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/advantages-icon_03-min.png" alt="" class="advantages-item__icon">
					<h5>Удобный поиск и фильтры</h5>
					<p>находите нужные крийи и медитации за пару кликов</p>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>