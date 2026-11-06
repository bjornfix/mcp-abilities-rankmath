<?php
/** Native option saves against an isolated publication checkpoint. */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI || wp_using_ext_object_cache() ) {
	throw new RuntimeException( 'Use WP-CLI with the native request-local cache.' );
}
require_once $args[0] . '/static-publication.php';
global $wpdb;
$original_table = $wpdb->options;
$origin = array( 'home' => home_url(), 'siteurl' => site_url() );
$table = 'rankmath_public_options_' . bin2hex( random_bytes( 8 ) );
$checks = array();
$check = static function ( bool $ok, string $name ) use ( &$checks ): void {
	$checks[] = array( 'check' => $name, 'passed' => $ok );
};
$pending = static function () use ( $wpdb ): array {
	$values = $wpdb->get_col( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id", $wpdb->esc_like( 'static_publication_pending_event_' ) . '%' ) );
	return array_map( 'maybe_unserialize', $values );
};
if ( false === $wpdb->query( "CREATE TEMPORARY TABLE {$table} LIKE {$original_table}" ) ) {
	throw new RuntimeException( 'Could not create the disposable options table.' );
}
$http_calls = 0;
$no_http = static function () use ( &$http_calls ) {
	++$http_calls;
	return new WP_Error( 'fixture_http_forbidden' );
};
add_filter( 'pre_http_request', $no_http );
try {
	$wpdb->options = $table;
	wp_cache_init();
	foreach ( $origin as $name => $value ) { add_option( $name, $value, '', false ); }
	$owner = new ReflectionClass( 'Static_Publication' );
	$save = $owner->getMethod( 'save_outbox' );
	$save->setAccessible( true );
	$read = $owner->getMethod( 'outbox' );
	$read->setAccessible( true );
	$baseline = array( 'desired_revision' => 11, 'delivered_revision' => 7, 'retained_context' => str_repeat( 'preserved-', 1000 ) );
	$save->invoke( null, $baseline );
	$checkpoint = get_option( 'static_publication_outbox_v2' );
	$before = array( 'email' => 'old@example.org', 'knowledgegraph_name' => 'Fixture publisher', 'private_sentinel' => 'never-export-this-option-value' );
	$current = array_replace( $before, array( 'email' => 'hello@example.org' ) );
	add_option( 'rank-math-options-titles', $before, '', false );
	update_option( 'rank-math-options-titles', $current );
	$check( array() === $pending(), 'The already saved pre-integration change has no fabricated publication event' );
	require_once $args[1] . '/mcp-abilities-rankmath.php';
	$names = apply_filters( 'static_publication_public_options', array( 'another_public_owner', 'rank-math-options-titles' ) );
	$check( in_array( 'another_public_owner', $names, true ) && 1 === count( array_keys( $names, 'rank-math-options-titles', true ) ), 'Public option declarations preserve other owners without duplicate names' );
	$check( in_array( 'rank-math-options-titles', apply_filters( 'static_publication_public_options', array() ), true ), 'The adapter declares the exact native publisher/title option' );
	$check( false === update_option( 'rank-math-options-titles', $current ) && array() === $pending(), 'Saving the unchanged contact value does not manufacture a change or flip values' );
	Static_Publication::record_public_option_change( 'rank-math-options-titles', $before, $current );
	$events = $pending();
	$check( 1 === count( $events ) && $current === get_option( 'rank-math-options-titles' ), 'The existing owner records the real earlier change without rewriting its saved value' );
	$event = $events[0] ?? array();
	$check( 'public_option_changed' === ( $event['reason'] ?? '' ) && 'rank-math-options-titles' === ( $event['option'] ?? '' ) && ! str_contains( serialize( $events ), 'never-export-this-option-value' ) && ! str_contains( serialize( $events ), 'hello@example.org' ), 'Publication records option identity, never public/private option values' );
	$current['knowledgegraph_name'] = 'Another fixture publisher';
	$check( update_option( 'rank-math-options-titles', $current ) && 2 === count( $pending() ), 'An ordinary native save of another publisher field uses the same Interface' );
	$check( false === update_option( 'rank-math-options-titles', $current ) && 2 === count( $pending() ), 'An ordinary unchanged native save remains a no-op' );
	add_option( 'rank-math-options-general', array( 'private_fixture' => true ), '', false );
	$check( 2 === count( $pending() ), 'Unrelated Rank Math operational options remain outside the declaration' );
	$check( delete_option( 'rank-math-options-titles' ) && 3 === count( $pending() ), 'Native deletion records the declared option' );
	$check( add_option( 'rank-math-options-titles', $current, '', false ) && 4 === count( $pending() ), 'Native addition records the declared option' );
	$current['email'] = 'contact@example.org';
	$check( update_option( 'rank-math-options-titles', $current ) && 5 === count( $pending() ), 'Native contact update records the declared option' );
	$check( false !== wp_get_scheduled_event( 'static_publication_dispatch' ), 'The ordinary WordPress dispatch event is scheduled' );
	$check( $checkpoint === get_option( 'static_publication_outbox_v2' ), 'Native saves preserve the prior checkpoint until the ordinary worker runs' );
	do_action( 'static_publication_dispatch' );
	$state = $read->invoke( null );
	$check( 16 === ( $state['desired_revision'] ?? null ) && 7 === ( $state['delivered_revision'] ?? null ) && $baseline['retained_context'] === ( $state['retained_context'] ?? null ), 'Ordinary native dispatch advances relevant revisions while preserving prior delivered work' );
	$check( array() === $pending(), 'Ordinary dispatch acknowledges only the recorded pending events' );
	$check( 0 === $http_calls, 'The disposable unconfigured fixture makes no network request' );
} finally {
	remove_filter( 'pre_http_request', $no_http );
	$wpdb->options = $original_table;
	wp_cache_init();
	$wpdb->query( "DROP TEMPORARY TABLE IF EXISTS {$table}" );
}
$failed = array_values( array_filter( $checks, static fn( $row ) => ! $row['passed'] ) );
echo wp_json_encode( array( 'status' => $failed ? 'failed' : 'passed', 'checks' => $checks, 'count' => count( $checks ), 'failed' => $failed, 'disposable_table_removed' => true ) ) . PHP_EOL;
if ( $failed ) { WP_CLI::halt( 1 ); }
