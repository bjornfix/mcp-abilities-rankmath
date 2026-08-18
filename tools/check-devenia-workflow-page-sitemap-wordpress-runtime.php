<?php
/**
 * Verify complete, duplicate-free Rank Math page sitemaps under Workflow.
 *
 * Run with: wp eval-file tools/check-devenia-workflow-page-sitemap-wordpress-runtime.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if (
	! class_exists( 'Devenia_Workflow' )
	|| ! class_exists( 'MCP_RankMath_Devenia_Workflow_Adapter' )
	|| ! class_exists( '\\RankMath\\Sitemap\\Sitemap' )
) {
	throw new RuntimeException( 'Devenia Workflow, the Rank Math Adapter, and Rank Math sitemaps must be active.' );
}

MCP_RankMath_Devenia_Workflow_Adapter::flush_sitemap_cache();

$fetch_xml = static function ( string $url ): string {
	$response = wp_remote_get(
		add_query_arg( 'workflow_sitemap_runtime', rawurlencode( wp_generate_uuid4() ), $url ),
		array( 'timeout' => 30, 'redirection' => 3, 'headers' => array( 'Cache-Control' => 'no-cache' ) )
	);
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		throw new RuntimeException( 'Could not fetch sitemap URL: ' . $url );
	}
	return (string) wp_remote_retrieve_body( $response );
};

$locs = static function ( string $xml ): array {
	if ( ! preg_match_all( '#<loc>([^<]+)</loc>#', $xml, $matches ) ) {
		return array();
	}
	return array_values(
		array_map(
			static fn( string $url ): string => html_entity_decode( trim( $url ), ENT_QUOTES | ENT_XML1, 'UTF-8' ),
			$matches[1]
		)
	);
};

$index_urls = $locs( $fetch_xml( home_url( '/sitemap_index.xml' ) ) );
$page_sitemaps = array_values(
	array_filter(
		$index_urls,
		static fn( string $url ): bool => 1 === preg_match( '#/page-sitemap[0-9]*\\.xml(?:\\?|$)#', $url )
	)
);
if ( empty( $page_sitemaps ) ) {
	throw new RuntimeException( 'Rank Math did not expose a page sitemap.' );
}

$actual = array();
foreach ( $page_sitemaps as $sitemap_url ) {
	if ( preg_match_all( '#<url>\\s*<loc>([^<]+)</loc>#', $fetch_xml( $sitemap_url ), $matches ) ) {
		foreach ( $matches[1] as $url ) {
			$actual[] = html_entity_decode( trim( (string) $url ), ENT_QUOTES | ENT_XML1, 'UTF-8' );
		}
	}
}

$expected = array();
$posts_page_id = absint( get_option( 'page_for_posts' ) );
$page_ids = get_posts(
	array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'orderby' => array( 'modified' => 'DESC', 'ID' => 'DESC' ),
		'no_found_rows' => true,
		'suppress_filters' => true,
	)
);
foreach ( $page_ids as $page_id ) {
	$page_id = absint( $page_id );
	$post = get_post( $page_id );
	if (
		$page_id === $posts_page_id
		|| ! $post instanceof WP_Post
		|| '' !== (string) $post->post_password
		|| ! \RankMath\Sitemap\Sitemap::is_object_indexable( $page_id )
	) {
		continue;
	}
	$url = (string) apply_filters( 'rank_math/sitemap/xml_post_url', get_permalink( $post ), $post );
	$canonical = (string) \RankMath\Helper::get_post_meta( 'canonical_url', $page_id );
	if ( '' === $url || ( '' !== $canonical && $canonical !== $url ) ) {
		continue;
	}
	$entry = apply_filters( 'rank_math/sitemap/entry', array( 'loc' => $url ), 'post', $post );
	if ( is_array( $entry ) && ! empty( $entry['loc'] ) ) {
		$expected[] = (string) $entry['loc'];
	}
}

$duplicates = array_filter( array_count_values( $actual ), static fn( int $count ): bool => $count > 1 );
$missing = array_values( array_diff( array_unique( $expected ), array_unique( $actual ) ) );
$unexpected = array_values( array_diff( array_unique( $actual ), array_unique( $expected ) ) );
if ( $duplicates || $missing || $unexpected ) {
	throw new RuntimeException(
		'Workflow page sitemap is incomplete: ' . wp_json_encode(
			array(
				'duplicates' => array_slice( $duplicates, 0, 10, true ),
				'missing' => array_slice( $missing, 0, 10 ),
				'unexpected' => array_slice( $unexpected, 0, 10 ),
			)
		)
	);
}

echo wp_json_encode(
	array(
		'success' => true,
		'page_sitemaps' => count( $page_sitemaps ),
		'eligible_pages' => count( $expected ),
		'unique_urls' => count( array_unique( $actual ) ),
		'duplicates' => 0,
		'missing' => 0,
	)
) . PHP_EOL;
