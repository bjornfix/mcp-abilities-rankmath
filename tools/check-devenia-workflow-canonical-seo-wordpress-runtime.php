<?php
/**
 * Real WordPress contract for Canonical SEO Surface and the Rank Math Adapter.
 *
 * Run with: wp eval-file tools/check-canonical-seo-surface-wordpress-runtime.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! class_exists( 'Devenia_Workflow' ) || ! class_exists( 'MCP_RankMath_Devenia_Workflow_Adapter' ) ) {
	throw new RuntimeException( 'Devenia Workflow and the Rank Math-owned Adapter must be active.' );
}

$reflector = new ReflectionClass( 'Devenia_Workflow' );
$call      = static function ( string $method, ...$args ) use ( $reflector ) {
	$target = $reflector->getMethod( $method );
	$target->setAccessible( true );
	return $target->invokeArgs( null, $args );
};

$post_id = 0;
try {
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'draft',
			'post_title'   => 'Canonical SEO runtime fixture',
			'post_excerpt' => 'Derived runtime description.',
			'post_content' => '<!-- wp:paragraph --><p>Canonical SEO runtime content.</p><!-- /wp:paragraph -->',
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( $post_id->get_error_message() );
	}
	$post_id = (int) $post_id;
	foreach ( array( 'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword' ) as $meta_key ) {
		if ( ! MCP_RankMath_Devenia_Workflow_Adapter::source_publication_meta_affects_surface( false, $meta_key, $post_id ) ) {
			throw new RuntimeException( 'Canonical Rank Math metadata did not declare source-publication ownership: ' . $meta_key );
		}
	}
	if ( MCP_RankMath_Devenia_Workflow_Adapter::source_publication_meta_affects_surface( false, '_canonical_seo_unrelated_fixture', $post_id ) ) {
		throw new RuntimeException( 'Unrelated metadata entered canonical source-publication authority.' );
	}
	update_post_meta( $post_id, 'rank_math_focus_keyword', 'stale-focus' );
	update_post_meta( $post_id, '_canonical_seo_unrelated_fixture', 'must-survive' );

	$complete = $call(
		'sync_translation_seo_meta',
		$post_id,
		array(
			'_canonical_seo_surface' => array(
				'title'         => 'Approved complete title',
				'description'   => 'Approved complete description.',
				'focus_keyword' => '',
			),
		),
		'Canonical SEO runtime fixture',
		'Derived runtime description.',
		'<!-- wp:paragraph --><p>Canonical SEO runtime content.</p><!-- /wp:paragraph -->'
	);
	if (
		empty( $complete['success'] )
		|| 'complete_replace' !== (string) ( $complete['surface_mode'] ?? '' )
		|| metadata_exists( 'post', $post_id, 'rank_math_focus_keyword' )
		|| 'Approved complete title' !== (string) get_post_meta( $post_id, 'rank_math_title', true )
		|| 'Approved complete description.' !== (string) get_post_meta( $post_id, 'rank_math_description', true )
		|| 'must-survive' !== (string) get_post_meta( $post_id, '_canonical_seo_unrelated_fixture', true )
	) {
		throw new RuntimeException( 'Complete-replace did not delete only the stale canonical-empty focus field: ' . wp_json_encode( $complete ) );
	}

	update_post_meta( $post_id, 'rank_math_focus_keyword', 'preserved-focus' );
	$patch_missing = $call(
		'sync_translation_seo_meta',
		$post_id,
		array( 'seo' => array( 'title' => 'Patched title' ) ),
		'Canonical SEO runtime fixture',
		'Derived runtime description.',
		'<!-- wp:paragraph --><p>Canonical SEO runtime content.</p><!-- /wp:paragraph -->'
	);
	if (
		empty( $patch_missing['success'] )
		|| 'patch_derive' !== (string) ( $patch_missing['surface_mode'] ?? '' )
		|| 'preserve' !== (string) ( $patch_missing['operations']['focus_keyword']['operation'] ?? '' )
		|| 'preserved-focus' !== (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true )
	) {
		throw new RuntimeException( 'Patch/derive treated an absent focus field as deletion authority: ' . wp_json_encode( $patch_missing ) );
	}
	$content_hash = hash(
		'sha256',
		wp_strip_all_tags( 'Canonical SEO runtime fixture' ) . "\n" .
		wp_strip_all_tags( 'Derived runtime description.' ) . "\n" .
		trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( '<!-- wp:paragraph --><p>Canonical SEO runtime content.</p><!-- /wp:paragraph -->' ) ) )
	);
	$state = MCP_RankMath_Devenia_Workflow_Adapter::seo_meta_state(
		array( 'content_hash' => $content_hash, 'stale_fields' => array() ),
		get_post( $post_id )
	);
	if ( empty( $state['tracked_signature'] ) ) {
		throw new RuntimeException( 'Patch/derive signature was not computed from the preserved final stored focus value: ' . wp_json_encode( $state ) );
	}

	$patch_delete = $call(
		'sync_translation_seo_meta',
		$post_id,
		array( 'seo' => array( 'focus_keyword' => '' ) ),
		'Canonical SEO runtime fixture',
		'Derived runtime description.',
		'<!-- wp:paragraph --><p>Canonical SEO runtime content.</p><!-- /wp:paragraph -->'
	);
	if ( empty( $patch_delete['success'] ) || 'delete' !== (string) ( $patch_delete['operations']['focus_keyword']['operation'] ?? '' ) || metadata_exists( 'post', $post_id, 'rank_math_focus_keyword' ) ) {
		throw new RuntimeException( 'Explicit empty patch focus did not perform its bounded deletion: ' . wp_json_encode( $patch_delete ) );
	}

	update_post_meta( $post_id, 'rank_math_focus_keyword', 'stale-alias-focus' );
	$patch_alias_precedence = $call(
		'sync_translation_seo_meta',
		$post_id,
		array( 'seo' => array( 'focus_keyword' => '', 'keyword' => 'must-not-win' ) ),
		'Canonical SEO runtime fixture',
		'Derived runtime description.',
		'<!-- wp:paragraph --><p>Canonical SEO runtime content.</p><!-- /wp:paragraph -->'
	);
	$resolved_alias = $call(
		'canonical_seo_input_field',
		array( 'focus_keyword' => '', 'keyword' => 'must-not-win' ),
		array( 'focus_keyword', 'keyword' )
	);
	$resolved_title_alias = $call(
		'canonical_seo_input_field',
		array( 'seo_title' => '', 'title' => 'must-not-win' ),
		array( 'seo_title', 'title' )
	);
	if (
		empty( $patch_alias_precedence['success'] )
		|| 'delete' !== (string) ( $patch_alias_precedence['operations']['focus_keyword']['operation'] ?? '' )
		|| metadata_exists( 'post', $post_id, 'rank_math_focus_keyword' )
		|| true !== ( $resolved_alias['present'] ?? null )
		|| 'focus_keyword' !== (string) ( $resolved_alias['key'] ?? '' )
		|| '' !== (string) ( $resolved_alias['value'] ?? '' )
		|| true !== ( $resolved_title_alias['present'] ?? null )
		|| 'seo_title' !== (string) ( $resolved_title_alias['key'] ?? '' )
		|| '' !== (string) ( $resolved_title_alias['value'] ?? '' )
	) {
		throw new RuntimeException( 'Explicit empty canonical fields did not outrank populated lower-precedence aliases: ' . wp_json_encode( array( 'patch' => $patch_alias_precedence, 'resolved_alias' => $resolved_alias, 'resolved_title_alias' => $resolved_title_alias ) ) );
	}

	$patch_set = $call(
		'sync_translation_seo_meta',
		$post_id,
		array( 'seo' => array( 'focus_keyword' => 'canonical focus' ) ),
		'Canonical SEO runtime fixture',
		'Derived runtime description.',
		'<!-- wp:paragraph --><p>Canonical SEO runtime content.</p><!-- /wp:paragraph -->'
	);
	if ( empty( $patch_set['success'] ) || 'set' !== (string) ( $patch_set['operations']['focus_keyword']['operation'] ?? '' ) || 'canonical focus' !== (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ) ) {
		throw new RuntimeException( 'Nonempty patch focus did not set its exact value: ' . wp_json_encode( $patch_set ) );
	}

	$missing_surface = $call( 'canonical_seo_surface_for_translation_job', array(), 'Fixture title', 'Fixture excerpt', 'Fixture content' );
	$empty_surface   = $call( 'canonical_seo_surface_for_translation_job', array( 'seo' => array( 'focus_keyword' => '' ) ), 'Fixture title', 'Fixture excerpt', 'Fixture content' );
	$alias_surface   = $call( 'canonical_seo_surface_for_translation_job', array( 'seo' => array( 'focus_keyword' => '', 'keyword' => 'must-not-win' ) ), 'Fixture title', 'Fixture excerpt', 'Fixture content' );
	$title_alias_surface = $call( 'canonical_seo_surface_for_translation_job', array( 'seo' => array( 'seo_title' => '', 'title' => 'must-not-win' ) ), 'Fixture title', 'Fixture excerpt', 'Fixture content' );
	$set_surface     = $call( 'canonical_seo_surface_for_translation_job', array( 'seo' => array( 'focus_keyword' => 'fixture focus' ) ), 'Fixture title', 'Fixture excerpt', 'Fixture content' );
	$base_manifest   = array( 'schema_version' => 2, 'job_id' => 'runtime', 'content' => array( 'title' => 'Fixture title' ) );
	$missing_revision = $call( 'translation_job_surface_revision', array_merge( $base_manifest, array( 'seo' => $missing_surface ) ) );
	$empty_revision   = $call( 'translation_job_surface_revision', array_merge( $base_manifest, array( 'seo' => $empty_surface ) ) );
	$set_revision     = $call( 'translation_job_surface_revision', array_merge( $base_manifest, array( 'seo' => $set_surface ) ) );
	if ( $missing_surface !== $empty_surface || $alias_surface !== $empty_surface || 'Fixture title' !== (string) ( $title_alias_surface['title'] ?? '' ) || $missing_revision !== $empty_revision || $set_revision === $empty_revision ) {
		throw new RuntimeException( 'Complete artifact SEO identities are not canonical: missing and empty must match, nonempty must change the surface revision.' );
	}

	if ( 'must-survive' !== (string) get_post_meta( $post_id, '_canonical_seo_unrelated_fixture', true ) ) {
		throw new RuntimeException( 'Canonical SEO operations deleted metadata outside their owned Rank Math keys.' );
	}

	echo wp_json_encode(
		array(
			'success' => true,
			'complete_empty_deleted_stale_focus' => true,
			'patch_missing_preserved_focus_and_signature' => true,
				'patch_empty_deleted_focus' => true,
				'empty_canonical_alias_outranked_populated_legacy_alias' => true,
			'patch_nonempty_set_focus' => true,
			'complete_missing_equals_empty_identity' => true,
			'complete_nonempty_changes_surface_revision' => true,
			'unrelated_metadata_preserved' => true,
		)
	) . PHP_EOL;
} finally {
	if ( $post_id > 0 ) {
		wp_delete_post( $post_id, true );
	}
}
