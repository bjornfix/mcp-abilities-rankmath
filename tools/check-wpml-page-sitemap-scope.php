<?php
/** Behavioral regression for native multilingual page queries,without customer configuration. */
namespace RankMath {
 final class Helper {
  public static function get_settings($key){return true;}
  public static function get_post_meta($key,$id){return 228===$id?'https://example.org/canonical/':'';}
 }
}
namespace RankMath\Sitemap {
 final class Sitemap {
  public static function is_object_indexable($id){
   if($GLOBALS['language']!==$GLOBALS['caller_language']){throw new \RuntimeException('Eligibility leaked into another language scope');}
   return 227!==$id;
  }
 }
}
namespace RankMath\Sitemap\Providers {
 class Post_Type {
  protected function do_filter($key,$value,$types){return true;}
  protected function get_posts($type,$count,$offset){return ['native-parent-path'];}
 }
}
namespace {
 define('ABSPATH',__DIR__.'/');
 $legacy=($argv[2]??'stack')==='legacy';define('ICL_SITEPRESS_VERSION',$legacy?'4.8.0':'5.1.0');
 $GLOBALS['wpml_enabled']=true;$GLOBALS['wpml_handler']=true;$GLOBALS['language']='no';$GLOBALS['caller_language']='no';$GLOBALS['switch_stack']=[];$GLOBALS['legacy_original']=null;$GLOBALS['switch_log']=[];$GLOBALS['query_log']=[];$GLOBALS['other_filter_calls']=0;$GLOBALS['throw_next_query']=false;$GLOBALS['argument_filter_languages']=[];
 function absint($value){return abs((int)$value);}
 function post_type_exists($type){return $type==='page';}
 function get_option($name){return $name==='page_for_posts'?11:false;}
 function has_action($hook){return $GLOBALS['wpml_enabled']&&$GLOBALS['wpml_handler']&&$hook==='wpml_switch_language'?10:false;}
 function apply_filters($hook,$value,...$args){
  if($hook==='wpml_current_language'){return $GLOBALS['wpml_enabled']?$GLOBALS['language']:null;}
  if($hook==='mcp_rankmath/devenia_workflow/page_sitemap_query_args'){$GLOBALS['argument_filter_languages'][]=$GLOBALS['language'];}
  return $value;
 }
 function do_action($hook,$language){
  if($hook!=='wpml_switch_language'||!has_action($hook)){return;}
  $GLOBALS['switch_log'][]=$language;
  if(version_compare(ICL_SITEPRESS_VERSION,'5.0','>=')){
   if(null===$language){if(!$GLOBALS['switch_stack']){throw new \RuntimeException('Unexpected scope close');}$GLOBALS['language']=array_pop($GLOBALS['switch_stack']);}
   else{$GLOBALS['switch_stack'][]=$GLOBALS['language'];$GLOBALS['language']=$language;}
  }else{
   if(null===$GLOBALS['legacy_original']){$GLOBALS['legacy_original']=$GLOBALS['language'];}
   $GLOBALS['language']=null===$language?$GLOBALS['legacy_original']:$language;
  }
 }
 class WP_Post {
  public function __construct(public int $ID,public string $language,public string $post_status='publish',public string $post_password=''){}
 }
 $GLOBALS['fixture_posts']=[];
 for($id=1;$id<=205;$id++){$GLOBALS['fixture_posts'][]=new WP_Post($id,$id<=103?'no':'en');}
 for($id=206;$id<=214;$id++){$GLOBALS['fixture_posts'][]=new WP_Post($id,'en','trash');}
 for($id=215;$id<=223;$id++){$GLOBALS['fixture_posts'][]=new WP_Post($id,'no','draft');}
 foreach([[224,'private',''],[225,'future',''],[226,'publish','protected'],[227,'publish',''],[228,'publish','']] as [$id,$status,$password]){$GLOBALS['fixture_posts'][]=new WP_Post($id,'no',$status,$password);}
 class WP_Query {
  public array $posts;
  public function __construct(array $args){
   $GLOBALS['query_log'][]=['filters_active'=>empty($args['suppress_filters']),'language'=>$GLOBALS['language'],'args'=>$args];
   if($GLOBALS['throw_next_query']){$GLOBALS['throw_next_query']=false;throw new \RuntimeException('Expected query failure');}
   $posts=array_filter($GLOBALS['fixture_posts'],static function($p)use($args){
    if($p->post_status!==$args['post_status']||$p->post_password!==''){return false;}
    if(empty($args['suppress_filters'])){
     if($GLOBALS['wpml_enabled']&&$GLOBALS['language']!=='all'&&$p->language!==$GLOBALS['language']){return false;}
     // An independent WordPress filter must still exclude a restricted object.
     ++$GLOBALS['other_filter_calls'];if($p->ID===42){return false;}
    }
    return true;
   });
   usort($posts,static fn($a,$b)=>$b->ID<=>$a->ID);
   $this->posts=array_slice($posts,$args['offset'],$args['posts_per_page']);
  }
 }
 require $argv[1];$p=new MCP_RankMath_Devenia_Workflow_Page_Sitemap_Provider();$method=new \ReflectionMethod($p,'get_posts');$method->setAccessible(true);$checks=[];
 $check=static function($ok,$name)use(&$checks){$checks[$name]=(bool)$ok;};
 $expected=array_values(array_filter(range(205,1),static fn($id)=>!in_array($id,[11,42],true)));
 $collect=static function()use($method,$p){$a=$method->invoke($p,'page',200,0);$b=$method->invoke($p,'page',200,200);return [$a,$b,array_map(static fn($x)=>$x->ID,array_merge($a,$b))];};
 [$first,$second,$ids]=$collect();$check($ids===$expected,'Both languages have complete stable pagination without duplicates or excluded statuses');$check(count($first)===200&&count($second)===3,'Second sitemap slice remains populated');$check($GLOBALS['other_filter_calls']>0&&array_reduce($GLOBALS['query_log'],static fn($ok,$q)=>$ok&&$q['filters_active'],true),'Independent native WordPress filters remain active');$check($GLOBALS['language']==='no'&&$GLOBALS['switch_stack']===[],'Default caller language is restored');$check(count(array_filter($GLOBALS['argument_filter_languages'],static fn($lang)=>$lang!=='no'))===0,'Existing argument filters run in caller context');
 do_action('wpml_switch_language','en');$GLOBALS['caller_language']='en';$stack=$GLOBALS['switch_stack'];[$first,$second,$nested]=$collect();$check($nested===$expected&&$GLOBALS['language']==='en'&&$GLOBALS['switch_stack']===$stack,'Nested English caller scope is retained');do_action('wpml_switch_language',null);$GLOBALS['caller_language']='no';$check($GLOBALS['language']==='no','Outer caller can close its own scope');
 $GLOBALS['throw_next_query']=true;$thrown=false;try{$method->invoke($p,'page',200,0);}catch(\RuntimeException $e){$thrown=$e->getMessage()==='Expected query failure';}$check($thrown&&$GLOBALS['language']==='no'&&$GLOBALS['switch_stack']===[],'Exception closes only the owned language scope');
 do_action('wpml_switch_language','all');$GLOBALS['caller_language']='all';$before=count($GLOBALS['switch_log']);$stack=$GLOBALS['switch_stack'];[$first,$second,$all]=$collect();$check($all===$expected&&count($GLOBALS['switch_log'])===$before&&$GLOBALS['switch_stack']===$stack,'Existing all-language scope is not opened or closed again');do_action('wpml_switch_language',null);$GLOBALS['caller_language']='no';
 $GLOBALS['wpml_enabled']=false;$before=count($GLOBALS['switch_log']);[$first,$second,$plain]=$collect();$check($plain===$expected&&count($GLOBALS['switch_log'])===$before,'Site without WPML or customer settings keeps native filtered behavior');
 $before=count($GLOBALS['query_log']);$check($method->invoke($p,'post',200,0)===['native-parent-path']&&count($GLOBALS['query_log'])===$before,'Non-page provider uses the unchanged native parent path');
 $GLOBALS['wpml_enabled']=true;$GLOBALS['wpml_handler']=false;$before=count($GLOBALS['switch_log']);$method->invoke($p,'page',200,0);$check(count($GLOBALS['switch_log'])===$before&&$GLOBALS['language']==='no','Missing WPML action does not trigger a substitute or disable filters');
 $failed=array_keys(array_filter($checks,static fn($ok)=>!$ok));echo json_encode(['passed'=>!$failed,'mode'=>$legacy?'legacy4':'stack5','checks'=>$checks,'failed'=>$failed,'expected_objects'=>count($expected)],JSON_UNESCAPED_SLASHES).PHP_EOL;exit($failed?1:0);
}
