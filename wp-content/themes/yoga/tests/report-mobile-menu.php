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
echo "PASS: all six primary sections, close and logout render as native buttons.\n";
