<?php
/**
 * Regression contract: the public adapter must preserve the current site's
 * own Rank Math identity while serving llms.txt.
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

function add_action() {}
function add_filter( $hook, $callback ) {
	$GLOBALS['registered_filters'][ (string) $hook ][] = $callback;
}

require dirname( __DIR__ ) . '/mcp-abilities-rankmath.php';

$title_filters = $GLOBALS['registered_filters']['option_rank-math-options-titles'] ?? array();
if ( array() !== $title_filters ) {
	fwrite( STDERR, "FAIL: public plugin intercepts the site's Rank Math title settings.\n" );
	exit( 1 );
}

echo "PASS: the plugin registers no Rank Math title-option override.\n";
