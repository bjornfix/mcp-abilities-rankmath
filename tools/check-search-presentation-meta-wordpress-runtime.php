<?php
/** Verify native metadata authorization and the unchanged MCP write policy. */
declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'mcp_expose_validate_post_meta_write_policy' ) ) {
	throw new RuntimeException( 'Native WordPress and the MCP metadata guard are required.' );
}
$read_only = 'read-only' === ( $args[0] ?? '' );
$user_ids = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( ! $user_ids ) {
	throw new RuntimeException( 'An existing administrator is required.' );
}
$user_id = (int) $user_ids[0];
$previous_user = get_current_user_id();
$fixture_id = 0;
$scenario = 'both';
$checks = array();
$assert = static function ( bool $condition, string $message ) use ( &$checks ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	$checks[] = $message;
};
$snapshot = static function ( int $id ): string {
	$post = get_post( $id );
	return hash( 'sha256', wp_json_encode( array( $post, get_post_meta( $id ) ) ) );
};
$post_ids = $read_only ? array_map( 'intval', array_slice( $args, 1 ) ) : array();
$deny = static function ( array $caps, string $cap, int $uid, array $cap_args ) use ( &$scenario, &$post_ids, $user_id ): array {
	if ( $uid !== $user_id ) {
		return $caps;
	}
	if ( in_array( $scenario, array( 'no-page', 'neither' ), true ) && 'edit_post' === $cap && in_array( (int) ( $cap_args[0] ?? 0 ), $post_ids, true ) ) {
		return array( 'do_not_allow' );
	}
	if ( in_array( $scenario, array( 'no-seo', 'neither' ), true ) && 'rank_math_onpage_general' === $cap ) {
		return array( 'do_not_allow' );
	}
	return $caps;
};

try {
	wp_set_current_user( $user_id );
	$assert( user_can( $user_id, 'rank_math_onpage_general' ), 'Existing SEO capability is available without a role or capability grant.' );
	if ( ! $read_only ) {
		$fixture_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_author' => $user_id, 'post_title' => 'Search presentation authorization fixture' ), true );
		if ( is_wp_error( $fixture_id ) ) {
			throw new RuntimeException( $fixture_id->get_error_message() );
		}
		$post_ids = array( $fixture_id );
	}
	$assert( (bool) $post_ids, 'Explicit target posts are present.' );
	$before = array();
	foreach ( $post_ids as $post_id ) {
		$assert( null !== get_post( $post_id ) && user_can( $user_id, 'edit_post', $post_id ), 'Existing target-page capability is available.' );
		$before[ $post_id ] = $snapshot( $post_id );
	}
	add_filter( 'map_meta_cap', $deny, 999, 4 );
	foreach ( array( 'both', 'no-seo', 'no-page', 'neither' ) as $scenario ) {
		foreach ( $post_ids as $post_id ) {
			foreach ( array( 'rank_math_title', 'rank_math_description' ) as $key ) {
				$expected = 'both' === $scenario;
				$assert( $expected === current_user_can( 'edit_post_meta', $post_id, $key ), "$scenario / $post_id / $key: native capability matches the two-right policy." );
				$result = mcp_expose_validate_post_meta_write_policy( array( $key => 'Policy probe only' ), $post_id );
				$assert( $expected === ! is_wp_error( $result ), "$scenario / $post_id / $key: unchanged MCP guard agrees." );
			}
		}
	}
	$scenario = 'both';
	foreach ( $post_ids as $post_id ) {
		foreach ( array( 'rank_math_focus_keyword', 'rank_math_robots', 'rank_math_canonical_url' ) as $key ) {
			$assert( ! current_user_can( 'edit_post_meta', $post_id, $key ), "$post_id / $key: other protected key remains denied." );
			$assert( is_wp_error( mcp_expose_validate_post_meta_write_policy( array( $key => 'Policy probe only' ), $post_id ) ), "$post_id / $key: MCP guard preserves denial." );
		}
		$assert( current_user_can( 'edit_post_meta', $post_id, 'mcp_contract_plain_meta' ), "$post_id: ordinary unprotected metadata remains allowed." );
		$assert( ! is_wp_error( mcp_expose_validate_post_meta_write_policy( array( 'mcp_contract_plain_meta' => 'Policy probe only' ), $post_id ) ), "$post_id: MCP ordinary metadata policy is preserved." );
		wp_set_current_user( 0 );
		$assert( ! current_user_can( 'edit_post_meta', $post_id, 'rank_math_title' ), "$post_id: anonymous caller remains denied." );
		$assert( user_can( $user_id, 'edit_post_meta', $post_id, 'rank_math_title' ), "$post_id: explicit checked-user identity is independent of the current user." );
		wp_set_current_user( $user_id );
		$assert( $before[ $post_id ] === $snapshot( $post_id ), "$post_id: capability and guard probes did not mutate content or metadata." );
	}
	if ( ! $read_only ) {
		$ability = wp_get_ability( 'meta/update-post-meta' );
		$assert( null !== $ability, 'Existing generic WordPress metadata ability is registered.' );
		$values = array( 'rank_math_title' => 'Native title fixture', 'rank_math_description' => 'Native description fixture' );
		$result = $ability->execute( array( 'post_id' => $fixture_id, 'meta' => $values ) );
		$assert( ! is_wp_error( $result ) && true === ( $result['success'] ?? false ), 'Authorized generic metadata operation succeeds on the dev fixture.' );
		foreach ( $values as $key => $value ) {
			$assert( get_post_meta( $fixture_id, $key, true ) === $value, "$key: native saved readback matches." );
		}
		$saved = $snapshot( $fixture_id );
		foreach ( array( 'no-seo', 'no-page', 'neither', 'both' ) as $scenario ) {
			$input = array( 'rank_math_title' => 'Must not save', 'rank_math_description' => 'Must not save' );
			if ( 'both' === $scenario ) {
				$input['rank_math_focus_keyword'] = 'Must not save';
			}
			$result = $ability->execute( array( 'post_id' => $fixture_id, 'meta' => $input ) );
			$assert( is_wp_error( $result ) || false === ( $result['success'] ?? true ), "$scenario: unauthorized generic write is rejected." );
			$assert( $saved === $snapshot( $fixture_id ), "$scenario: rejection preserves all fixture data without partial writes." );
		}
	}
	$scenario = 'both';
} finally {
	remove_filter( 'map_meta_cap', $deny, 999 );
	wp_set_current_user( $previous_user );
	if ( $fixture_id > 0 ) {
		wp_delete_post( $fixture_id, true );
		$assert( null === get_post( $fixture_id ), 'The dev fixture was removed through native WordPress cleanup.' );
	}
}
echo 'RANKMATH_AUTH_PROOF=' . wp_json_encode( array( 'result' => 'PASS', 'read_only' => $read_only, 'posts' => $post_ids, 'user_id' => $user_id, 'checks' => $checks, 'production_writes' => 0 ) ) . PHP_EOL;
