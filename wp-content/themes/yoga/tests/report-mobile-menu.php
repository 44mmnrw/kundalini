<?php
/** Render the real menu without WordPress; verify native keyboard controls. */
function is_user_logged_in() { return true; }
function get_current_user_id() { return 7; }
function home_url($path) { return '#'; }
function get_template_directory_uri() { return '/theme'; }
function __($v, ...$args) { return $v; }
function esc_html($v) { return htmlspecialchars($v); }
function esc_attr($v) { return htmlspecialchars($v); }
function esc_url($v) { return htmlspecialchars($v); }
function esc_html_e($v, ...$args) { echo esc_html($v); }
function esc_attr_e($v, ...$args) { echo esc_attr($v); }
function yoga_render_lk_menu_icon(...$args) {}
function get_current_user_tariff() { return false; }
function get_template_directory() { return dirname(__DIR__); }
function add_query_arg($key, $value, $url) { return $url; }
function apply_filters($hook, $value, ...$args) { return $value; }
function wp_strip_all_tags($value) { return strip_tags($value); }
function get_terms(...$args) { return array(); }
function is_wp_error($value) { return false; }
class Walker_Nav_Menu {}
$theme_source = file_get_contents(dirname(__DIR__) . '/functions.php');
$walker_start = strpos($theme_source, 'class Mobile_Menu_Walker extends');
$walker_end = strpos($theme_source, 'function theme_woocommerce_support()', $walker_start);
eval(substr($theme_source, $walker_start, $walker_end - $walker_start));
function wp_nav_menu($args) {
    $output = '<ul>';
    foreach (array('Библиотека', 'Блог') as $id => $title) {
        $item = (object) array('title' => $title, 'ID' => $id + 1, 'url' => '#blog', 'target' => '', 'xfn' => '');
        $args['walker']->start_el($output, $item);
        $args['walker']->end_el($output, $item);
    }
    echo $output . '</ul>';
}
ob_start(); include dirname(__DIR__) . '/template-parts/modal-mobile-menu-lk.php'; $html = ob_get_clean();
$document = new DOMDocument();
libxml_use_internal_errors(true);
$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
$xpath = new DOMXPath($document);
$items = $xpath->query('//button[contains(concat(" ",normalize-space(@class)," ")," sidebar-menu__item ")]');
if ($items->length !== 6) throw new RuntimeException('All six primary items must be native buttons');
foreach ($items as $item) if ($item->getAttribute('type') !== 'button') throw new RuntimeException('Menu item may submit a form');
foreach (array('modal-close', 'sidebar-exit') as $class) {
    if ($xpath->query('//button[contains(concat(" ",normalize-space(@class)," ")," ' . $class . ' ")]')->length !== 1) throw new RuntimeException('Missing keyboard button: ' . $class);
}
ob_start(); include dirname(__DIR__) . '/template-parts/modal-mobile-menu.php'; $public_html = ob_get_clean();
$document->loadHTML('<?xml encoding="utf-8" ?>' . $public_html);
$xpath = new DOMXPath($document);
$library = $xpath->query('//button[@class="mobile-menu-library-toggle"]');
if ($library->length !== 1 || $library[0]->getAttribute('aria-controls') !== 'yoga-mobile-library'
    || $xpath->query('//*[@id="yoga-mobile-library"]')->length !== 1) throw new RuntimeException('Public library needs a native button linked to the submenu');
if (($argv[1] ?? '') === '--fixture') {
    $source = file_get_contents(dirname(__DIR__) . '/assets/js/script.js');
    $slice = static function ($from, $to) use ($source) {
        $start = strpos($source, $from); $end = strpos($source, $to, $start);
        if ($start === false || $end === false) throw new RuntimeException('Production menu handler not found');
        return substr($source, $start, $end - $start);
    };
    $scripts = $slice("\tfunction closeYogaMobileMenus(", "\n\tfunction closeLkNotifications");
    $scripts .= $slice("\t$(document).on('click', '.body_main .burger'", "\n\t$('.switches-item').click");
    $scripts .= $slice("\t$('.overlay').click(function ()", "\n\tvar yogaSadhanaModalTrigger");
    // Include ALL closing/scroll handlers, including the former return-false conflict.
    $scripts .= $slice("\t$('.sidebar-menu__item').not('.sidebar-menu__item_exit').click", "\n\t$('.lk-gender-item').click");
    $scripts .= $slice("\t$('.mobile-menu-library-toggle').click", "\n\t$('.modal-call_delcomm').click");
    $fixture = '<!doctype html><meta charset="utf-8"><style>.modal-mobile-menu,.modal-mobile-menu-lk,.mobile-menu__slide,.lk-slide{display:none}.modal-mobile-menu.active,.modal-mobile-menu-lk.active,.mobile-menu__slide.active,.lk-slide.active{display:block}button{margin:4px}.header{padding:10px}</style><body class="body body_lk"><header class="header"><button type="button" class="burger" aria-expanded="false" aria-controls="yoga-mobile-menu-lk">Меню</button></header><div class="overlay"></div>' . $html . $public_html . '<div id="section-lk">';
    foreach (array(1,7,3,5,8,6) as $target) $fixture .= '<div class="lk-slide" data-target="' . $target . '">' . $target . '</div>';
    $fixture .= '</div><script>' . file_get_contents(dirname(__DIR__, 4) . '/wp-includes/js/jquery/jquery.min.js') . '</script><script>jQuery(function($){var yogaViewportBp={lg:1025};function closeLibraryFiltersScreen(){};' . $scripts . ';window.yogaMenuFixtureReady=true;});</script>';
    if (file_put_contents($argv[2], $fixture) === false) throw new RuntimeException('Cannot save browser fixture');
}
echo "PASS: LK buttons and public library button/controlled submenu.\n";
