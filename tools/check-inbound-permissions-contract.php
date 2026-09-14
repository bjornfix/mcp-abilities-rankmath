<?php
/** An inbound-link report must not reveal a post outside the caller's scope. */
define('ABSPATH',__DIR__.'/');
function add_action() {} function add_filter() {}
function sanitize_key($s) { return $s; }
function post_type_exists($s) { return true; }
function absint($n) { return abs((int)$n); }
function get_post($id) { return (object)array('ID'=>$id); }
function current_user_can($cap,...$args) { return false; }
function get_permalink($id) { throw new RuntimeException('Inbound report inspected an inaccessible target.'); }
require dirname(__DIR__).'/mcp-abilities-rankmath.php';
$r=mcp_rankmath_build_inbound_link_graph(array('post_types'=>array('page'),'target_post_id'=>44));
if ($r['success']) { throw new RuntimeException('Inbound report accepted an inaccessible target.'); }
echo "PASS: inbound report checks target permissions\n";
