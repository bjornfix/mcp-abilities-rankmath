<?php
namespace WPML {
 if (class_exists(Container::class,false)) { throw new \RuntimeException('Use isolated --skip-plugins; never replace native WPML.'); }
 if(!class_exists(Container::class,false)){class Container { public static $factory; public static function make($name){if($name!=='WPML_ST_String_Factory'){throw new \RuntimeException('Unexpected dependency.');}return self::$factory;} }}
}
namespace {
 if(!defined('ABSPATH')||!function_exists('wp_get_ability')){throw new RuntimeException('WordPress required.');}
 foreach(array('ICL_TM_COMPLETE'=>10,'ICL_TM_WAITING_FOR_TRANSLATOR'=>1,'ICL_TM_IN_PROGRESS'=>2)as$key=>$value){if(!defined($key)){define($key,$value);}}
 $admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));wp_set_current_user((int)$admins[0]);
 $cap=static function($caps,$name){return in_array($name,array('wpml_manage_string_translation','manage_translations'),true)?array('read'):$caps;};add_filter('map_meta_cap',$cap,99,2);
 $old=get_option('rank-math-options-titles',null);$fixture=array('search_title'=>'Search %search_query% %page% %sep% %sitename%','private_sentinel'=>'never-return-this-option');update_option('rank-math-options-titles',$fixture);
 $GLOBALS['sitepress']=new class {public function get_active_languages(){return array('no'=>array(),'en'=>array());}public function get_default_language(){return 'no';}};
 $GLOBALS['search_native_target']=array('value'=>'Old %search_query% %page% %sep% %sitename%','status'=>ICL_TM_COMPLETE);$GLOBALS['search_native_writes']=0;$GLOBALS['search_native_registered']=true;
 $string=new class {public function exists(){return true;}public function get_context(){return 'admin_texts_rank-math-options-titles';}public function get_name(){return '[rank-math-options-titles]search_title';}public function get_value(){return get_option('rank-math-options-titles')['search_title'];}public function get_language(){return 'no';}public function get_translations(){return null===$GLOBALS['search_native_target']?array():array(array('language'=>'en')+$GLOBALS['search_native_target']);}};
 \WPML\Container::$factory=new class($string){private $string;public function __construct($s){$this->string=$s;}public function get_string_id($v,$domain,$name){if($domain!=='admin_texts_rank-math-options-titles'||$name!=='[rank-math-options-titles]search_title'){throw new RuntimeException('Unbounded admin text access.');}return $GLOBALS['search_native_registered']&&empty($GLOBALS['search_native_conflict'])?123:0;}public function find_by_name($name){return new class {public function exists(){return $GLOBALS['search_native_registered'];}};}public function clear_string_id_cache(){}public function find_by_id($id){return $this->string;}};
 if(function_exists('icl_add_string_translation')){throw new RuntimeException('Use --skip-plugins; preserve native WPML.');}
 if(!function_exists('icl_add_string_translation')){function icl_add_string_translation($id,$lang,$value,$status){if($id!==123||$lang!=='en'){throw new RuntimeException('Wrong native translation.');}$GLOBALS['search_native_writes']++;$GLOBALS['search_native_target']=array('value'=>$value,'status'=>$status);return 456;}}
 $assert=static function($ok,$why){if(!$ok){throw new RuntimeException($why);}};
 try {
  require_once '/var/www/dev.devenia.com/wp-content/plugins/mcp-abilities-rankmath/mcp-abilities-rankmath.php';
  $get=wp_get_ability('rankmath/get-search-title-translation');$write=wp_get_ability('rankmath/update-search-title-translation');$assert($get&&$write,'Abilities missing.');
  $before=$get->execute(array('target_language'=>'en'));$assert(!is_wp_error($before)&&$before['success']&&!str_contains(wp_json_encode($before),'never-return'),'Bounded read failed.');
  $params=array('target_language'=>'en','value'=>'You searched for %search_query% %page% %sep% %sitename%');
  $r=$write->execute($params);$assert(!is_wp_error($r)&&!$r['applied']&&!$GLOBALS['search_native_writes'],'Preview wrote.');
  $bad=$params;$bad['value']='Missing placeholders';$assert(is_wp_error($write->execute($bad))&&!$GLOBALS['search_native_writes'],'Placeholder loss accepted.');
  foreach(array('no','xx')as$lang){$assert(is_wp_error($get->execute(array('target_language'=>$lang))),'Source/inactive language accepted.');}
  $GLOBALS['search_native_target']['status']=ICL_TM_IN_PROGRESS;$assert(is_wp_error($write->execute($params))&&!$GLOBALS['search_native_writes'],'Active native job overwritten.');$GLOBALS['search_native_target']['status']=ICL_TM_COMPLETE;
  $params+=array('confirm'=>true,'expected_source_hash'=>$before['source_hash'],'expected_target_hash'=>$before['target_hash']);
  foreach(array('expected_source_hash','expected_target_hash')as$key){$bad=$params;$bad[$key]=str_repeat('0',64);$assert(is_wp_error($write->execute($bad))&&!$GLOBALS['search_native_writes'],'Stale source/target accepted.');}
  $bad=$params;$bad['field']='private_sentinel';$assert(is_wp_error($write->execute($bad))&&!$GLOBALS['search_native_writes'],'Arbitrary admin field accepted.');
  $r=$write->execute($params);$assert(!is_wp_error($r)&&$r['success']&&$r['applied']&&1===$GLOBALS['search_native_writes']&&get_option('rank-math-options-titles')===$fixture,'Native selected write or source preservation failed.');
  $after=$get->execute(array('target_language'=>'en'));$params['expected_target_hash']=$after['target_hash'];$r=$write->execute($params);$assert(!is_wp_error($r)&&$r['unchanged']&&1===$GLOBALS['search_native_writes'],'Idempotent retry wrote.');
  $GLOBALS['search_native_conflict']=true;$conflict=$get->execute(array('target_language'=>'en'));$assert(is_wp_error($conflict)&&$conflict->get_error_code()==='rankmath_search_title_source_conflict'&&1===$GLOBALS['search_native_writes'],'Conflicting existing public string was not preserved.');$GLOBALS['search_native_conflict']=false;
  $GLOBALS['search_native_registered']=false;$GLOBALS['search_native_target']=null;$missing=$get->execute(array('target_language'=>'en'));$assert(!is_wp_error($missing)&&!$missing['registered'],'Missing registration was hidden.');$params['expected_source_hash']=$missing['source_hash'];$params['expected_target_hash']=$missing['target_hash'];$assert(is_wp_error($write->execute($params))&&1===$GLOBALS['search_native_writes'],'Missing source registered without explicit selection.');
  $registration=static function($domain,$name,$value,$empty,$lang)use($fixture){if($domain!=='admin_texts_rank-math-options-titles'||$name!=='[rank-math-options-titles]search_title'||$value!==$fixture['search_title']||$empty||$lang!=='no'){throw new RuntimeException('Wrong public registration.');}$GLOBALS['search_native_registered']=true;};add_action('wpml_register_single_string',$registration,10,5);$params['register_if_missing']=true;$r=$write->execute($params);remove_action('wpml_register_single_string',$registration,10);$assert(!is_wp_error($r)&&$r['success']&&2===$GLOBALS['search_native_writes']&&get_option('rank-math-options-titles')===$fixture,'Explicit native public registration failed.');
  remove_filter('map_meta_cap',$cap,99);$deny=static function($caps,$name){return in_array($name,array('wpml_manage_string_translation','manage_translations'),true)?array('do_not_allow'):$caps;};add_filter('map_meta_cap',$deny,999,2);$assert(current_user_can('manage_options')&&is_wp_error($get->execute(array('target_language'=>'en'))),'Settings permission bypassed native WPML permission.');remove_filter('map_meta_cap',$deny,999);
  wp_set_current_user(0);$assert(is_wp_error($write->execute($params))&&2===$GLOBALS['search_native_writes'],'Anonymous write accepted.');
  echo "PASS: fixed public search-title scope, native WPML delegation, permissions, preview, placeholder preservation, stale/active-job refusal, source preservation and idempotent completion.\n";
 } finally {remove_filter('map_meta_cap',$cap,99);wp_set_current_user((int)$admins[0]);if(null===$old){delete_option('rank-math-options-titles');}else{update_option('rank-math-options-titles',$old);}}
}
