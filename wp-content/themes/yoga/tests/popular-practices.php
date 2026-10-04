<?php
/** Standalone regression checks: php tests/popular-practices.php */
define('ABSPATH', __DIR__);
function add_action(...$args) {}
function add_filter(...$args) {}
function acf_get_valid_field($field) { return $field; }
require dirname(__DIR__) . '/inc/integrations/acf.php';
$field = yoga_configure_popular_practice_fields(['key'=>'field_popular_practices_items','sub_fields'=>[
 ['key'=>'field_practice_title','name'=>'practice_title','required'=>1],
 ['key'=>'field_practice_description','name'=>'practice_description'],
 ['key'=>'field_practice_image','name'=>'practice_image'],
 ['key'=>'field_practice_style','name'=>'practice_style','choices'=>['default'=>'Violet','pink'=>'Pink','green'=>'Green']],
 ['key'=>'field_practice_link','name'=>'practice_link'],
 ['key'=>'field_practice_animation','name'=>'practice_animation'],
]]);
$visible = array_values(array_map(fn($f)=>$f['name'],array_filter($field['sub_fields'],fn($f)=>yoga_hide_legacy_popular_practice_field($f)!==false)));
if ($visible !== ['practice_style','practice']) throw new RuntimeException('Unexpected fields');
$again = yoga_configure_popular_practice_fields($field);
if (count($again['sub_fields']) !== count($field['sub_fields'])) throw new RuntimeException('Duplicate selector');
function acf_add_local_field($field) { $GLOBALS['ajax_field']=$field; }
yoga_register_popular_practice_ajax_field();
if ($GLOBALS['ajax_field']['parent'] === 'field_popular_practices_items' || $GLOBALS['ajax_field']['post_type'] !== ['practice']) throw new RuntimeException('AJAX selector registration');
function get_field($name,$id=null) {
 if ($name==='popular_practices_title') return 'Popular';
 if ($name==='popular_practices_items') return $GLOBALS['items'];
 if ($name==='short_description') return '<b>Library summary</b>';
 return null;
}
function get_the_ID() { return 2; }
function get_post_type($id) { return 'practice'; }
function get_post_status($id) { return $id===99 ? 'draft' : 'publish'; }
function get_the_title($id) { return 'Library title '.$id; }
function get_the_excerpt($id) { return 'Excerpt'; }
function wp_strip_all_tags($s) { return strip_tags($s); }
function yoga_get_practice_card_image_url($id,$size) { return '/image-'.$id.'.jpg'; }
function get_permalink($id) { return '/practice/'.$id; }
function get_template_directory_uri() { return '/theme'; }
function esc_html($s) { return htmlspecialchars($s,ENT_QUOTES); }
function esc_attr($s) { return htmlspecialchars($s,ENT_QUOTES); }
function esc_url($s) { return $s; }
function wp_get_attachment_image_url($id,$size) { return '/attachment.jpg'; }
$GLOBALS['items']=[
 ['practice'=>7,'practice_style'=>'pink','practice_title'=>'OBSOLETE','practice_description'=>'OBSOLETE'],
 ['practice'=>(object)['ID'=>8],'practice_style'=>'green'],
 ['practice'=>99,'practice_style'=>'green'],
 ['practice_title'=>'Legacy title','practice_description'=>'Legacy description','practice_link'=>'/legacy'],
];
ob_start(); include dirname(__DIR__).'/template-parts/section-popular.php'; $html=ob_get_clean();
foreach (['Library title 7','Library title 8','Library summary','/image-7.jpg','/practice/7','popular-practice_pink','popular-practice_green','Legacy title'] as $expected) {
 if (strpos($html,$expected)===false) throw new RuntimeException('Missing '.$expected);
}
if (strpos($html,'OBSOLETE')!==false || strpos($html,'Library title 99')!==false) throw new RuntimeException('Stale or unpublished practice shown');
echo "Passed: visible admin fields, repeated schema loading, AJAX selector, current library data, colors, legacy cards, unpublished practice excluded\n";
