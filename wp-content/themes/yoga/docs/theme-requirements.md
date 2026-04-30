# Требования темы Yoga

Минимальные требования окружения:
- WordPress: 6.7+
- PHP: 8.1+

Интеграции и поведение при отсутствии:
- Advanced Custom Fields (ACF)
  - Используется для полей контента и страницы опций темы.
  - При отсутствии ACF тема работает с fallback-функциями (`get_field`, `the_field`, `have_rows`).
- WooCommerce
  - Используется для checkout, подписок и данных заказов.
  - Критичные AJAX-обработчики используют защитный гейт `yoga_require_woocommerce_for_ajax()`.

Рекомендации по обновлениям:
1. Обновлять сначала WordPress, затем плагины, затем тему.
2. Проверять `wp-content/debug.log` после каждого шага.
3. Для прода держать `WP_DEBUG_DISPLAY=false` и `WP_DEBUG_LOG=true`.
