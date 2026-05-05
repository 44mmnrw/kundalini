<section class="section-tariffs section-tariffs_single" id="section-tariffs">
  <div class="container">
    <div class="row">
      <div class="tariffs">
        <?php
          // Получаем данные из ACF
          $tariffs_title = get_field('tariffs_title', get_the_ID()) ?: 'тарифы';
          $tariffs_periods = get_field('tariffs_periods', get_the_ID()) ?: get_field('tariffs_periods', 'options');
          
          // Получаем продукты из категории тарифов
          $tariff_products = wc_get_products(array(
            'status' => 'publish',
            'limit' => -1,
            'category' => ['tariffs'],
            'orderby' => 'menu_order',
            'order' => 'ASC'
          ));
        ?>
        
        <?php if ($tariffs_periods) : ?>
        <div class="switches wow fadeIn delay-200ms">
          <?php 
            $period_index = 1;
            foreach ($tariffs_periods as $period) : 
              $period_name = $period['period_name'] ?? '';
              $period_slug = $period['period_slug'] ?? '';
              $is_active = $period['period_active'] ?? false;
          ?>
          <div class="switches-item <?php echo $is_active ? 'active' : ''; ?>" data-target="<?php echo esc_attr($period_index); ?>">
            <span><?php echo esc_html($period_name); ?></span>
          </div>
          <?php 
            $period_index++;
            endforeach; 
          ?>
        </div>
        <?php endif; ?>
        
        <?php if ($tariff_products) : ?>
        <div class="tariffs-items wow fadeIn delay-200ms">
          <?php 
            // Создаем слайды для каждого периода
            $period_index = 1;
            foreach ($tariffs_periods as $period) : 
              $period_slug = $period['period_slug'] ?? '';
              $is_active = $period['period_active'] ?? false;
          ?>
          <div class="tariffs-items__slide <?php echo $is_active ? 'active' : ''; ?>" data-target="<?php echo esc_attr($period_index); ?>">
            <?php $tariff_card_index = 0; ?>
            <?php foreach ($tariff_products as $product) : 
              $product_id = $product->get_id();
              $product_name = $product->get_name();
              $product_description = $product->get_short_description();
              $is_highlighted = get_field('tariff_highlighted', $product_id) ?? false;
              
              // Получаем правильную вариацию или продукт для периода
              $current_product_id = $product_id;
              $current_price = '';
              $current_price_text = '';
              
              if ($product->is_type('variable')) {
                $variations = $product->get_available_variations();
                foreach ($variations as $variation) {
                  $variation_period = get_field('price_period', $variation['variation_id']);
                  if ($variation_period === $period_slug) {
                    $current_product_id = $variation['variation_id'];
                    $current_price = $variation['display_price'];
                    $current_price_text = get_field('price_text', $variation['variation_id']);
                    break;
                  }
                }
              } else {
                $product_period = get_field('price_period', $product_id);
                if ($product_period === $period_slug) {
                  $current_price = $product->get_price();
                  $current_price_text = get_field('price_text', $product_id);
                }
              }
              
              // Пропускаем продукты без цены для этого периода
              if (empty($current_price)) continue;
              $current_price_text = trim((string) $current_price_text);
              $current_price_text = preg_replace('/^\/?\s*в\s+/ui', '/ ', $current_price_text);
              $current_price_text = preg_replace('/^\/\s*/u', '/ ', $current_price_text);
              if ($current_price_text === '/') {
                $current_price_text = '';
              }
              $tariff_card_index++;
              $tariff_visual_index = (($tariff_card_index - 1) % 4) + 1;
              
              $tariff_features = get_field('tariff_features', $product_id);
            ?>
            <div class="tariff tariff_visual_<?php echo esc_attr($tariff_visual_index); ?> <?php echo $is_highlighted ? 'tariff_highlighted' : ''; ?>">
              <div class="tariff__bg" aria-hidden="true"></div>
              
              <div class="tariff__top">
                <h3><?php echo esc_html($product_name); ?></h3>
                <p><?php echo esc_html($product_description); ?></p>
              </div>
              
              <div class="tariff__center">
                <div class="tariff-price">
                  <span>
                    <b><?php echo number_format((float) $current_price, 0, '', '.'); ?> ₽</b><?php echo esc_html($current_price_text); ?>
                  </span>
                </div>
                
                <?php if ($tariff_features) : ?>
                <ul class="check-list">
                  <?php foreach ($tariff_features as $feature) : 
                    $feature_text = is_array($feature) ? ($feature['feature_text'] ?? '') : $feature;
                  ?>
                  <?php if ($feature_text) : ?>
                  <li>
                    <span class="check-list__icon" aria-hidden="true">
                      <svg aria-hidden="true">
                        <use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#tariff-check"></use>
                      </svg>
                    </span>
                    <span class="check-list__text"><?php echo esc_html($feature_text); ?></span>
                  </li>
                  <?php endif; ?>
                  <?php endforeach; ?>
                </ul>
                <?php endif; ?>
              </div>
              
              <form class="cart" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post" enctype='multipart/form-data'>
                <?php 
                  // Скрытое поле для добавления в корзину
                  if ($product->is_type('variable') && $current_product_id != $product_id) {
                    // Для вариаций
                ?>
                <input type="hidden" name="add-to-cart" value="<?php echo absint($product_id); ?>">
                <input type="hidden" name="variation_id" value="<?php echo absint($current_product_id); ?>">
                <input type="hidden" name="attribute_pa_period" value="<?php echo esc_attr($period_slug); ?>">
                <?php
                  } else {
                    // Для простых продуктов
                ?>
                <input type="hidden" name="add-to-cart" value="<?php echo absint($current_product_id); ?>">
                <?php
                  }
                  
                  // Добавляем nonce для безопасности
                  wp_nonce_field('woocommerce-add-to-cart', 'woocommerce-add-to-cart-nonce');
                ?>
                
                <button type="submit" class="btn btn_icon single_add_to_cart_button">
                  <span><?php echo esc_html(yoga_get_purchase_cta_text()); ?></span>
                  <div class="btn-icon">
                    <svg class="btn-icon-arrow btn-icon-arrow_black active" aria-hidden="true" focusable="false">
                      <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                    </svg>
                    <svg class="btn-icon-arrow btn-icon-arrow_green" aria-hidden="true" focusable="false">
                      <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                    </svg>
                  </div>
                </button>
              </form>
            </div>
            <?php endforeach; ?>
          </div>
          <?php 
            $period_index++;
            endforeach; 
          ?>
        </div>
        <?php else : ?>
        <div class="tariffs-items wow fadeIn delay-200ms">
          <p>Тарифы временно недоступны</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>