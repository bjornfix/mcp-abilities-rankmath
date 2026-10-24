<?php
/** Native WordPress queries keep filters and caller language scopes;no fixture posts or settings are written. */
if(!defined('ABSPATH')||!class_exists('RankMath\Sitemap\Providers\Post_Type')){throw new RuntimeException('WordPress and Rank Math required');}
require_once WP_PLUGIN_DIR.'/mcp-abilities-rankmath/includes/class-devenia-workflow-page-sitemap-provider.php';
$p=new MCP_RankMath_Devenia_Workflow_Page_Sitemap_Provider();$m=new ReflectionMethod($p,'get_posts');$m->setAccessible(true);$checks=[];$queries=[];$lang='no';$stack=[];$seen=[];
$args_filter=static function($args){$args['mcp_rankmath_scope_test']=true;return $args;};
$posts_filter=static function($posts,$query)use(&$queries,&$lang){if($query->get('mcp_rankmath_scope_test')){$queries[]=['filters_active'=>!$query->get('suppress_filters'),'language'=>$lang,'query_language'=>$query->get('lang')];}return $posts;};
add_filter('mcp_rankmath/devenia_workflow/page_sitemap_query_args',$args_filter);add_filter('posts_results',$posts_filter,10,2);
try{
 if(false!==has_action('wpml_switch_language')){throw new RuntimeException('This isolated baseline requires no active WPML handler');}
 $m->invoke($p,'page',5,0);$checks['No WPML or customer configuration uses native filters']=count($queries)>0&&array_reduce($queries,static fn($ok,$q)=>$ok&&$q['filters_active'],true);
 $current=static function()use(&$lang){return $lang;};$switch=static function($code)use(&$lang,&$stack,&$seen){$seen[]=$code;if(null===$code){if(!$stack){throw new RuntimeException('Unbalanced native scope close');}$lang=array_pop($stack);}else{$stack[]=$lang;if($code!=='all'){$lang=$code;}}};
 add_filter('wpml_current_language',$current);add_action('wpml_switch_language',$switch);
 try{
  $queries=[];$m->invoke($p,'page',5,0);$checks['Native frontend query keeps filters with explicit all-language argument']=count($queries)>0&&array_reduce($queries,static fn($ok,$q)=>$ok&&$q['filters_active']&&$q['language']==='no'&&$q['query_language']==='all',true);$checks['Norwegian caller restored']=$lang==='no'&&$stack===[];
  do_action('wpml_switch_language','en');$outer=$stack;$m->invoke($p,'page',5,0);$checks['English outer scope retained']=$lang==='en'&&$stack===$outer;do_action('wpml_switch_language',null);$checks['Outer scope closes normally']=$lang==='no'&&$stack===[];
  $throw=static function($posts,$query){if($query->get('mcp_rankmath_scope_test')){throw new RuntimeException('Expected native query exception');}return $posts;};add_filter('posts_pre_query',$throw,10,2);$thrown=false;try{$m->invoke($p,'page',5,0);}catch(RuntimeException $e){$thrown=$e->getMessage()==='Expected native query exception';}finally{remove_filter('posts_pre_query',$throw,10);}$checks['Query exception restores language']=$thrown&&$lang==='no'&&$stack===[];
 }finally{remove_filter('wpml_current_language',$current);remove_action('wpml_switch_language',$switch);}
}finally{remove_filter('posts_results',$posts_filter,10);remove_filter('mcp_rankmath/devenia_workflow/page_sitemap_query_args',$args_filter);}
$failed=array_keys(array_filter($checks,static fn($ok)=>!$ok));echo wp_json_encode(['passed'=>!$failed,'checks'=>$checks,'failed'=>$failed,'persistent_writes'=>0]).PHP_EOL;if($failed){throw new RuntimeException('Native scope regression failed');}
