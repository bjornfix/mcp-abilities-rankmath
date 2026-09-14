<?php
/** Verify the public content write callbacks against WordPress metadata semantics. */
declare(strict_types=1);
define('ABSPATH',__DIR__.'/');
define('RANK_MATH_VERSION','test');
$GLOBALS['abilities']=array();$GLOBALS['meta']=array();
function add_action(){} function add_filter(){}
function wp_register_ability($name,$definition){$GLOBALS['abilities'][$name]=$definition;}
function get_post($id){return $id===123?(object)array('ID'=>123,'post_title'=>'Fixture','post_type'=>'page'):null;}
function current_user_can($cap,...$args){return empty($GLOBALS['deny']);}
function get_permalink($id){return 'https://example.com/fixture/';}
function absint($n){return abs((int)$n);}
function wp_slash($v){return is_array($v)?array_map('wp_slash',$v):(is_string($v)?addslashes($v):$v);}
function wp_unslash($v){return is_array($v)?array_map('wp_unslash',$v):(is_string($v)?stripslashes($v):$v);}
function sanitize_text_field($v){return trim(preg_replace('/[\r\n\t ]+/',' ',preg_replace('/%[a-f0-9]{2}/i','',strip_tags($v))));}
function sanitize_textarea_field($v){return trim(preg_replace('/%[a-f0-9]{2}/i','',strip_tags($v)));}
function update_post_meta($id,$key,$value){$GLOBALS['meta'][$key]=wp_unslash($value);return true;}
function delete_post_meta($id,$key){unset($GLOBALS['meta'][$key]);return true;}
function get_post_meta($id,$key=null,$single=false){if($key===null)return array_map(fn($v)=>array($v),$GLOBALS['meta']);return $GLOBALS['meta'][$key]??'';}
function maybe_unserialize($v){return $v;}
require dirname(__DIR__).'/mcp-abilities-rankmath.php';
mcp_rankmath_register_content_abilities();
function check_write($condition,$message){if(!$condition){fwrite(STDERR,'FAIL: '.$message."\n");exit(1);}}
$update=$GLOBALS['abilities']['rankmath/update-meta']['execute_callback'];
$GLOBALS['meta']=array('rank_math_title'=>'Before','rank_math_robots'=>array('noindex'));
$before=$GLOBALS['meta'];
$r=$update(array('id'=>123,'seo_title'=>'Must not save','robots'=>array('index'),'clear_robots'=>true));
check_write(!$r['success']&&$GLOBALS['meta']===$before,'conflicting robots request must fail before changing another field');
$title='%date% | C:\\guides\\entry';$description="Read %date%\nPath C:\\guides\\entry";
$r=$update(array('id'=>123,'seo_title'=>$title,'seo_description'=>$description,'focus_keyword'=>'C:\\guides'));
check_write($r['success']&&get_post_meta(123,'rank_math_title',true)===$title,'title preserves Rank Math variables and literal backslashes');
check_write(get_post_meta(123,'rank_math_description',true)===$description,'description preserves variables, line breaks and backslashes');
check_write(get_post_meta(123,'rank_math_focus_keyword',true)==='C:\\guides','keyword preserves literal backslashes');
$schema=$GLOBALS['abilities']['rankmath/update-post-schema']['execute_callback'];
$before=$GLOBALS['meta'];
$r=$schema(array('id'=>123,'schemas'=>array('rank_math_schema_Article'=>array('name'=>'Must not save'),'invalid-key'=>'bad')));
check_write(!$r['success']&&$GLOBALS['meta']===$before,'invalid schema key must fail before any write');
$r=$schema(array('id'=>123,'schemas'=>array('rank_math_schema_Article'=>array('name'=>'Must not save')),'delete_keys'=>array('rank_math_schema_Old')));
check_write(!$r['success']&&$GLOBALS['meta']===$before,'unconfirmed schema deletion must fail before another schema write');
$r=$schema(array('id'=>123,'schemas'=>array('rank_math_schema_Article'=>array('name'=>'%date%','description'=>'C:\\guides\\entry'))));
check_write($r['success']&&$GLOBALS['meta']['rank_math_schema_Article']['description']==='C:\\guides\\entry','nested schema preserves literal backslashes');
check_write(strpos(mcp_rankmath_sanitize_template_text('%date<script>alert(1)</script>%'), '<')===false,'variable protection must not restore HTML tags');
$before=$GLOBALS['meta'];
$r=MCP_RankMath_Devenia_Workflow_Adapter::sync_seo_meta(array(),123,array('title'=>array('operation'=>'set','value'=>'Must not save'),'description'=>array('operation'=>'invalid'),'focus_keyword'=>array('operation'=>'preserve')),array());
check_write(!$r['success']&&$GLOBALS['meta']===$before,'Workflow rejects invalid field operations before writes');
echo "PASS: Rank Math content write contract\n";
