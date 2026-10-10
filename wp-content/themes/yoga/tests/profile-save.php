<?php
// Isolated checks; never bootstrap WordPress or connect to the database.
class WP_User { public $ID = 7; public $user_email = 'old@example.com'; public $user_pass = 'hash'; }
class WP_Error { function __construct(public $code, public $message) {} function get_error_message(){return $this->message;} }
class JsonResult extends Error { function __construct(public $status){ parent::__construct(); } }
class ReachedWrite extends Error {}
function wp_unslash($v){return stripslashes($v);}
function sanitize_text_field($v){return trim(strip_tags($v));}
function sanitize_email($v){return trim($v);}
function is_email($v){return filter_var($v,FILTER_VALIDATE_EMAIL);}
function is_wp_error($v){return $v instanceof WP_Error;}
function yoga_get_russian_timezone_options(){return ['Europe/Moscow'=>'Moscow'];}
function wp_check_password($v,$hash,$id){return $v==='correct';}
function wp_verify_nonce(...$args){return true;}
function is_user_logged_in(){return true;}
function get_current_user_id(){return 7;}
function wp_parse_auth_cookie(...$args){return [];}
function get_user_by(...$args){return new WP_User;}
function yoga_frontend_session_can_change_credentials($user){return true;}
function username_exists($email){return 0;}
function email_exists($email){return $email==='taken@example.com' ? 9 : 0;}
function wp_send_json_error($message,$status){throw new JsonResult($status);}
function wp_update_user($data){throw new ReachedWrite;}
const MB_IN_BYTES=1048576;
function wp_check_filetype_and_ext(...$args){return ['type'=>'text/plain'];}
require dirname(__DIR__).'/inc/auth/profile-validation.php';
require dirname(__DIR__).'/inc/auth/profile-avatar.php';
$source=file_get_contents(dirname(__DIR__).'/functions.php');
$start=strpos($source,'function yoga_update_profile_ajax()');
$end=strpos($source,"add_action('wp_ajax_update_user_profile'",$start);
eval(substr($source,$start,$end-$start));
$valid=['nonce'=>'fixture','first_name'=>'Name','email'=>'old@example.com','timezone'=>'Europe/Moscow'];
$cases=[['first_name'=>''],['first_name'=>'  '],['email'=>''],['email'=>'bad'],['timezone'=>''],['timezone'=>'invalid'],['new_password'=>'abcdef'],['current_password'=>'wrong','new_password'=>'abcdef','repeat_password'=>'abcdef'],['current_password'=>'correct','new_password'=>'abcdef','repeat_password'=>'other'],['current_password'=>'correct','new_password'=>'123','repeat_password'=>'123'],['email'=>'taken@example.com']];
$checks=0;
foreach($cases as $case){
 $_POST=array_replace($valid,$case);
 $_FILES=['avatar'=>['error'=>UPLOAD_ERR_OK,'tmp_name'=>'fixture','size'=>1,'name'=>'photo.png']];
 try{yoga_update_profile_ajax();throw new Error('Missing rejection');}catch(JsonResult $result){if($result->status!==422)throw new Error('Wrong rejection');$checks++;}
}
// Invalid image and oversized upload fail before the first user write too.
foreach([1,11*MB_IN_BYTES] as $size){
 $_POST=$valid;$_FILES=['avatar'=>['error'=>UPLOAD_ERR_OK,'tmp_name'=>'fixture','size'=>$size,'name'=>'photo.png']];
 try{yoga_update_profile_ajax();throw new Error('Missing image rejection');}catch(JsonResult $r){if($r->status!==422)throw new Error('Wrong image rejection');$checks++;}
}
$_POST=$valid;$_FILES=[];
try{yoga_update_profile_ajax();throw new Error('Valid request never reached save');}catch(ReachedWrite $e){$checks++;}
$GLOBALS['avatar']=21;$GLOBALS['deleted']=[];
function yoga_get_user_avatar_id($id){return $GLOBALS['avatar'];}
function update_user_meta($id,$key,$value){$GLOBALS['avatar']=$value;}
function delete_user_meta($id,$key){if($key==='user_avatar')$GLOBALS['avatar']=0;}
function yoga_assign_avatar_to_folder($id){}
function get_post_type($id){return 'attachment';}
function wp_delete_attachment($id,$force){$GLOBALS['deleted'][]=$id;}
if(!yoga_save_profile_avatar(7,22,false)||$GLOBALS['avatar']!==22||$GLOBALS['deleted']!==[21])throw new Error('Avatar replacement failed');$checks++;
if(!yoga_save_profile_avatar(7,0,true)||$GLOBALS['avatar']!==0||$GLOBALS['deleted']!==[21,22])throw new Error('Avatar deletion failed');$checks++;
echo "PASS: $checks profile validation / avatar checks; no database or remote writes.\n";
