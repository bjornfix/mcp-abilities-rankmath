<?php
/** Behavioral page-count/pagination regression; isolated native-interface fixtures only. */
namespace RankMath {
 final class Helper {
  public static function get_settings($key){return true;}
  public static function get_post_meta($key,$id){return in_array($id,$GLOBALS['canonical_ids'],true)?'https://example.org/canonical/':'';}
 }
}
namespace RankMath\Sitemap {
 final class Sitemap {
  public static function is_object_indexable($id){return !in_array($id,$GLOBALS['noindex_ids'],true);}
 }
}
namespace RankMath\Sitemap\Providers {
 class Post_Type {
  public function get_post_type_count($type){return $type==='page'?$GLOBALS['native_sql_count']:17;}
  protected function get_posts($type,$count,$offset){return ['native-parent-path'];}
  protected function do_filter($key,$default,$types){return $GLOBALS['exclude_canonical'];}
 }
}
namespace {
 define('ABSPATH',__DIR__.'/');
 function absint($n){return abs((int)$n);}
 function wp_json_encode($v){return json_encode($v);}
 function get_option($key){return $key==='page_for_posts'?$GLOBALS['posts_page']:false;}
 function has_action($hook){return false;}
 function post_type_exists($type){return $type==='page';}
 function apply_filters($hook,$value,...$rest){
  if($hook==='wpml_current_language'){return null;}
  if($hook==='mcp_rankmath/devenia_workflow/page_sitemap_query_args'){$value['fixture_visibility']=$GLOBALS['hidden_ids'];}
  return $value;
 }
 function do_action($hook,...$args){}
 class WP_Post {
  public string $filter='raw';
  public function __construct(public int $ID,public int $post_modified,public string $post_status='publish',public string $post_password=''){}
 }
 class WP_Query {
  public array $posts;
  public function __construct(array $args){
   ++$GLOBALS['query_count'];$GLOBALS['queries'][]=$args;
   if($GLOBALS['throw_next']){$GLOBALS['throw_next']=false;throw new \RuntimeException('Expected query exception');}
   if(!empty($args['suppress_filters'])){throw new \RuntimeException('Native filters were suppressed');}
   $posts=array_values(array_filter($GLOBALS['fixture'],static fn($p)=>$p->post_status==='publish'&&$p->post_password===''&&!in_array($p->ID,$args['fixture_visibility'],true)));
   usort($posts,static fn($a,$b)=>($b->post_modified<=>$a->post_modified)?:($b->ID<=>$a->ID));
   $this->posts=array_slice($posts,$args['offset'],$args['posts_per_page']);
  }
 }
 require $argv[1];
 $checks=[];
 $check=static function($ok,$name)use(&$checks){$checks[$name]=(bool)$ok;};
 function reset_fixture($count):void{
  $GLOBALS['fixture']=[];for($id=1;$id<=$count;++$id){$GLOBALS['fixture'][]=new WP_Post($id,$id%7);}
  $GLOBALS['noindex_ids']=[];$GLOBALS['canonical_ids']=[];$GLOBALS['hidden_ids']=[];$GLOBALS['posts_page']=0;$GLOBALS['exclude_canonical']=false;$GLOBALS['native_sql_count']=$count;$GLOBALS['query_count']=0;$GLOBALS['queries']=[];$GLOBALS['throw_next']=false;
 }
 function expected_ids():array{
  $posts=array_filter($GLOBALS['fixture'],static fn($p)=>$p->post_status==='publish'&&$p->post_password===''&&!in_array($p->ID,$GLOBALS['hidden_ids'],true)&&!in_array($p->ID,$GLOBALS['noindex_ids'],true)&&(!$GLOBALS['exclude_canonical']||!in_array($p->ID,$GLOBALS['canonical_ids'],true))&&$p->ID!==$GLOBALS['posts_page']);
  usort($posts,static fn($a,$b)=>($b->post_modified<=>$a->post_modified)?:($b->ID<=>$a->ID));return array_values(array_unique(array_map(static fn($p)=>$p->ID,$posts)));
 }
 function provider(){return new MCP_RankMath_Devenia_Workflow_Page_Sitemap_Provider();}
 function slice($p,$count,$offset):array{$m=new \ReflectionMethod($p,'get_posts');$m->setAccessible(true);return $m->invoke($p,'page',$count,$offset);}
 function ids($posts):array{return array_map(static fn($p)=>$p->ID,$posts);}
 reset_fixture(201);$GLOBALS['noindex_ids']=[3,4,5];$p=provider();$count=$p->get_post_type_count('page');$first=slice($p,200,0);$second=slice($p,200,200);
 $check($count===198&&count($first)===198&&$second===[],'SQL201 versus eligible198 counts the emitted population');
 $check((int)ceil($count/200)===1,'A198page population advertises one nonempty child');
 $check(ids($first)===expected_ids(),'Current semantic exclusions and modified-ID ordering preserved');
 $query_count_before_reuse=$GLOBALS['query_count'];$p->get_post_type_count('page');slice($p,20,0);slice($p,20,20);$check($GLOBALS['query_count']===$query_count_before_reuse,'Count and every slice reuse one request-local population');
 foreach([0,199,200,201,400]as$n){
  reset_fixture($n);$p=provider();$total=$p->get_post_type_count('page');$out=[];$nonempty=true;
  for($offset=0;$offset<$total;$offset+=200){$part=slice($p,200,$offset);$nonempty=$nonempty&&count($part)>0;$out=array_merge($out,ids($part));}
  $check($total===$n&&$out===expected_ids()&&count(array_unique($out))===$n&&$nonempty,'Boundary'.$n.' has exact count,no duplicate,hole or empty advertised slice');
 }
 reset_fixture(410);$GLOBALS['noindex_ids']=[3,4,205];$GLOBALS['canonical_ids']=[6,206];$GLOBALS['exclude_canonical']=true;$GLOBALS['posts_page']=8;$GLOBALS['hidden_ids']=[9];$GLOBALS['fixture'][]=new WP_Post(411,9,'private');$GLOBALS['fixture'][]=new WP_Post(412,9,'draft');$GLOBALS['fixture'][]=new WP_Post(413,9,'publish','protected');$GLOBALS['fixture'][]=new WP_Post(10,3);$p=provider();$expected=expected_ids();$count=$p->get_post_type_count('page');$all=array_merge(ids(slice($p,200,0)),ids(slice($p,200,200)),ids(slice($p,200,400)));$check($count===count($expected)&&$all===$expected,'Canonical,noindex,posts-page,status,password,independent filters and duplicated raw IDs agree');
 $GLOBALS['hidden_ids']=[9,10];$check($p->get_post_type_count('page')===count(expected_ids()),'Changed effective query arguments cannot reuse stale population');
 $check($p->get_post_type_count('post')===17,'Other post-type counts delegate to native provider');$m=new \ReflectionMethod($p,'get_posts');$m->setAccessible(true);$check($m->invoke($p,'post',2,0)===['native-parent-path'],'Other post-type slices delegate to native provider');
 reset_fixture(201);$p=provider();$GLOBALS['throw_next']=true;$thrown=false;try{$p->get_post_type_count('page');}catch(\RuntimeException$e){$thrown=$e->getMessage()==='Expected query exception';}$check($thrown&&$p->get_post_type_count('page')===201,'Failed population scan is not cached as partial or empty success');
 $check(array_reduce($GLOBALS['queries'],static fn($ok,$a)=>$ok&&$a['suppress_filters']===false&&$a['orderby']===['modified'=>'DESC','ID'=>'DESC'],true),'Native filtering and deterministic query order remain enabled');
 $failed=array_keys(array_filter($checks,static fn($v)=>!$v));echo json_encode(['passed'=>!$failed,'checks'=>$checks,'failed'=>$failed],JSON_UNESCAPED_SLASHES).PHP_EOL;exit($failed?1:0);
}
