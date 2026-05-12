<?php
	// Получаем родительскую рубрику "blog"
	$blog_category = get_category_by_slug('blog');
	$radio_items = array();
	
	if ($blog_category) {
		// Получаем дочерние рубрики
		$child_categories = get_categories(array(
        'parent' => $blog_category->term_id,
        'hide_empty' => false
		));
		
		// Формируем массив для радио-кнопок
		foreach ($child_categories as $category) {
			$radio_items[] = array(
            'category_name' => $category->name,
            'category_slug' => $category->slug
			);
		}
	}
// Всегда короткий URL блога: иначе POST на /category/blog/ даёт 301 на /blog/ без query — теряются s и фильтр.
$form_action = home_url('/blog/');
$blog_search_value = '';
if (isset($_GET['s']) && is_string($_GET['s'])) {
	$blog_search_value = sanitize_text_field(wp_unslash($_GET['s']));
}
if ($blog_search_value === '') {
	$blog_search_value = get_search_query();
}
?>

<section class="section-blog-form animated fadeIn slow delay-200ms" id="section-blog-form">
    <div class="container">
        <div class="row">
            <div class="blog-form">
                <form action="<?php echo esc_url($form_action); ?>" method="get">
                    <div class="blog-radios">
						<?php if (!empty($radio_items)) : ?>
						<!-- Кнопка "Все статьи" -->
						<label class="active">
							<input type="radio" name="category" value="" checked> 
							<span>Все статьи</span>
						</label>
						
						<?php foreach ($radio_items as $index => $item) : ?>
						<label>
							<input type="radio" name="category" value="<?php echo esc_attr($item['category_slug']); ?>"> 
							<span>
								<?php echo esc_html($item['category_name']); ?>
							</span>
						</label>
						<?php endforeach; ?>
						<?php endif; ?>
					</div>
                    <div class="blog-search">
                        <input type="text" name="s" class="input" placeholder="Что ищете?" value="<?php echo esc_attr($blog_search_value); ?>" required>
                        <label class="blog-search__btn">
                            <input type="submit">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/search-btn-icon.png" alt="" class="active">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/search-btn-icon_active.png" alt="">
						</label>
                        <label class="blog-search__delete-btn">
                            <input type="reset">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/close-search-btn-icon.png" alt="" class="active">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/close-search-btn-icon.png" alt="">
						</label>
					</div>
				</form>
			</div>
		</div>
	</div>
</section>

