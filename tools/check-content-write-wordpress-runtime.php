<?php
/** Verify metadata writes through registered native WordPress abilities. */
if (!defined('ABSPATH') || !function_exists('wp_get_ability')) { throw new RuntimeException('WordPress Abilities API required.'); }
$user = get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
if (!$user) { throw new RuntimeException('An administrator is required.'); }
wp_set_current_user((int)$user[0]);
$post_id = wp_insert_post(array('post_type'=>'page','post_status'=>'draft','post_title'=>'Rank Math metadata fixture'),true);
if (is_wp_error($post_id)) { throw new RuntimeException($post_id->get_error_message()); }
$run = static function($name,$input) use ($post_id) {
    $ability=wp_get_ability($name);
    if (!$ability) { throw new RuntimeException('Missing ability '.$name); }
    $r=$ability->execute(array_merge(array('id'=>$post_id),$input));
    if (is_wp_error($r)) { throw new RuntimeException($r->get_error_message()); }
    return $r;
};
$check = static function($ok,$message) { if (!$ok) { throw new RuntimeException($message); } };
try {
    update_post_meta($post_id,'rank_math_title','Before');
    $before=get_post_meta($post_id);
    $r=$run('rankmath/update-meta',array('title'=>'Must not save','robots'=>array('index'),'clear_robots'=>true));
    $check(!$r['success'] && get_post_meta($post_id)===$before,'Conflicting request changed metadata.');
    $title='%date% | C:\\guides\\entry';
    $r=$run('rankmath/update-meta',array('title'=>$title,'description'=>$title,"keyword"=>'C:\\guides'));
    $check($r['success'] && get_post_meta($post_id,'rank_math_title',true)===$title && get_post_meta($post_id,'rank_math_description',true)===$title && get_post_meta($post_id,'rank_math_focus_keyword',true)==='C:\\guides','Metadata changed variables or backslashes.');
    $before=get_post_meta($post_id);
    $r=$run('rankmath/update-post-schema',array('schemas'=>array('rank_math_schema_Article'=>array('name'=>'Must not save'),'invalid-key'=>'bad')));
    $check(!$r['success'] && get_post_meta($post_id)===$before,'Invalid schema request changed metadata.');
    $r=$run('rankmath/update-post-schema',array('schemas'=>array('rank_math_schema_Article'=>array('name'=>'Must not save')),'delete_keys'=>array('rank_math_schema_Old')));
    $check(!$r['success'] && get_post_meta($post_id)===$before,'Unconfirmed deletion changed metadata.');
    $value=array('name'=>'%date%','description'=>'C:\\guides\\entry');
    $r=$run('rankmath/update-post-schema',array('schemas'=>array('rank_math_schema_Article'=>$value)));
    $check($r['success'] && get_post_meta($post_id,'rank_math_schema_Article',true)===$value,'Schema changed variables or backslashes.');
    update_post_meta($post_id,'rank_math_robots',array('noindex'));
    $r=$run('rankmath/update-meta',array('clear_robots'=>true));
    $check($r['success'] && !metadata_exists('post',$post_id,'rank_math_robots'),'Robots override not deleted.');
    $fields=array('title'=>array('operation'=>'set','value'=>$title),'description'=>array('operation'=>'set','value'=>$title),'focus_keyword'=>array('operation'=>'preserve'));
    $r=MCP_RankMath_Devenia_Workflow_Adapter::sync_seo_meta(array(),$post_id,$fields,array());
    $check($r['success'] && get_post_meta($post_id,'rank_math_title',true)===$title && get_post_meta($post_id,'rank_math_description',true)===$title,'Workflow changed variables or backslashes.');
    $before=get_post_meta($post_id);
    $fields['description']['operation']='invalid';
    $r=MCP_RankMath_Devenia_Workflow_Adapter::sync_seo_meta(array(),$post_id,$fields,array());
    $check(!$r['success'] && get_post_meta($post_id)===$before,'Workflow partially saved an invalid request.');
    wp_update_post(array('ID'=>$post_id,'post_content'=>'<!-- wp:rank-math/faq-block {"questions":[]} --><div>Private fixture</div><!-- /wp:rank-math/faq-block -->'));
    $deny=static function($caps,$cap,$uid,$args) use ($post_id) { return 'edit_post'===$cap && (int)($args[0]??0)===$post_id ? array('do_not_allow') : $caps; };
    add_filter('map_meta_cap',$deny,999,4);
    try {
        $r=wp_get_ability('rankmath/audit-faq-links')->execute(array('statuses'=>array('draft')));
        $check(!is_wp_error($r),'FAQ audit failed.');
        $inbound=wp_get_ability('rankmath/get-inbound-links')->execute(array('target_post_id'=>$post_id));
        $check(!is_wp_error($inbound) && !$inbound['success'],'Inbound report accepted inaccessible target.');
        $graph=wp_get_ability('rankmath/get-inbound-links')->execute(array('post_types'=>array('page'),'post_statuses'=>array('draft'),'min_count'=>0,'include_menus'=>false));
        $check(!is_wp_error($graph) && $graph['success'],'Inbound graph failed.');
        foreach ($graph['items'] as $item) {
            $check((int)$item['target_post_id']!==$post_id,'Inbound graph exposed inaccessible target.');
            foreach ($item['sources']??array() as $source) { $check((int)$source['id']!==$post_id,'Inbound graph exposed inaccessible source.'); }
        }
        foreach ($r['findings'] as $finding) { $check((int)$finding['post_id']!==$post_id,'FAQ exposed inaccessible draft.'); }
    } finally { remove_filter('map_meta_cap',$deny,999); }
    echo "PASS: native Rank Math content writes\n";
} finally { wp_delete_post($post_id,true); }
