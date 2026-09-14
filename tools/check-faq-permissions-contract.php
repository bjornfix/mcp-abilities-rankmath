<?php
/** Verify the FAQ report does not expose posts the caller cannot edit. */
define('ABSPATH',__DIR__.'/');
class WP_Post { public $ID=44; public $post_content='<!-- wp:rank-math/faq-block -->Private content'; }
class WP_Query { public $posts=array(44); public function __construct($args) {} }
function absint($n) { return abs((int)$n); }
function sanitize_key($s) { return $s; }
function get_post($id) { return new WP_Post(); }
function current_user_can($cap,...$args) { return false; }
function parse_blocks($content) { throw new RuntimeException('FAQ report read an inaccessible post.'); }
require dirname(__DIR__).'/includes/abilities-content.php';
$r=mcp_rankmath_audit_faq_links();
if ($r['findings'] || $r['scanned_count']!==0) { throw new RuntimeException('FAQ report exposed inaccessible posts.'); }
echo "PASS: FAQ report respects individual post permissions\n";
