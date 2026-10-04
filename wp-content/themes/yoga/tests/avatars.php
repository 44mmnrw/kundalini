<?php
/** Run without WordPress: php tests/avatars.php */
define('ABSPATH', __DIR__);
$GLOBALS['meta'] = [1 => ['user_avatar'=>101,'simple_local_avatar'=>['media_id'=>202]],2=>['simple_local_avatar'=>['media_id'=>202]],3=>['user_avatar'=>0,'simple_local_avatar'=>['media_id'=>202]],4=>['user_avatar'=>999]];
function add_action(...$args) {}
function add_filter(...$args) {}
function remove_action($hook,$callback,$priority=10) { $GLOBALS['removed'][]=$hook; }
function remove_filter($hook,$callback,$priority=10) { $GLOBALS['removed'][]=$hook; }
function get_user_meta($id,$key,$single=true) { return $GLOBALS['meta'][$id][$key] ?? ''; }
function metadata_exists($type,$id,$key) { return array_key_exists($key,$GLOBALS['meta'][$id] ?? []); }
function get_post_type($id) { return in_array($id,[101,202],true) ? 'attachment' : false; }
function wp_get_attachment_image_url($id,$size) { return '/avatar-'.$id.'.jpg'; }
class WP_User { public $ID=1; }
class WP_Comment { public $user_id=1; public $comment_author_email='user@example.test'; }
class WP_Post { public $post_author=1; }
function get_user_by($field,$value) { return $value==='user@example.test' ? new WP_User() : false; }
require dirname(__DIR__).'/inc/comments.php';
require dirname(__DIR__).'/inc/avatars.php';
function check($condition,$message) { if (!$condition) throw new RuntimeException($message); }
check(yoga_get_user_avatar_id(1)===101,'Primary avatar must win');
check(yoga_get_user_avatar_id(2)===202,'Legacy avatar must remain available');
check(yoga_get_user_avatar_id(3)===0,'Cleared avatar must not resurrect legacy avatar');
check(yoga_get_user_avatar_id(4)===0,'Deleted attachment must not be used');
check(yoga_load_existing_profile_avatar(null,'user_2')===202,'Admin field must show legacy avatar');
check(yoga_load_existing_profile_avatar(0,'user_3')===0,'Admin field must stay cleared');
foreach ([1,new WP_User(),new WP_Comment(),new WP_Post(),'user@example.test'] as $identity) {
 $args=yoga_get_unified_avatar_data(['size'=>60],$identity);
 check(($args['url'] ?? '')==='/avatar-101.jpg' && !empty($args['found_avatar']),'WordPress avatar API must use same image');
}
check(yoga_get_unified_avatar_data(['force_default'=>true],1)===['force_default'=>true],'Force default must be respected');
$GLOBALS['simple_local_avatars']=new stdClass();
yoga_retire_duplicate_avatar_controls();
foreach (['show_user_profile','edit_user_profile','personal_options_update','edit_user_profile_update','pre_get_avatar_data'] as $hook) check(in_array($hook,$GLOBALS['removed'],true),'Duplicate hook not removed: '.$hook);
echo "Passed: one editor control, unified WordPress avatars, legacy images preserved, explicit clearing and deleted attachments respected.\n";
