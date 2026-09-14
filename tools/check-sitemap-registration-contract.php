<?php
/** Verify native sitemap hooks do not depend on the optional Workflow plugin. */
define('ABSPATH', __DIR__.'/');
$GLOBALS['filters']=array();
function add_action() {}
function add_filter($hook, $callback) { $GLOBALS['filters'][$hook]=$callback; }
require dirname(__DIR__).'/includes/devenia-workflow-adapter.php';
MCP_RankMath_Devenia_Workflow_Adapter::maybe_register_hooks();
if ($GLOBALS['filters']) { throw new RuntimeException('Hooks registered without Rank Math.'); }
define('RANK_MATH_VERSION', 'test');
MCP_RankMath_Devenia_Workflow_Adapter::maybe_register_hooks();
$expected=array('rank_math/sitemap/exclude_post_type','rank_math/sitemap/providers');
if (array_keys($GLOBALS['filters']) !== $expected) { throw new RuntimeException('Native sitemap hooks missing or Workflow hooks registered without Workflow.'); }
echo "PASS: native sitemap registration without Workflow\n";
