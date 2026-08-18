<?php
/**
 * Deterministic Rank Math page sitemap pagination for Devenia Workflow.
 *
 * @package MCP_Abilities_RankMath
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\\RankMath\\Sitemap\\Providers\\Post_Type' ) ) {
	return;
}

/**
 * Preserve Rank Math's native page sitemap behavior while making page
 * membership stable when many Workflow publications share one modified time.
 */
final class MCP_RankMath_Devenia_Workflow_Page_Sitemap_Provider extends \RankMath\Sitemap\Providers\Post_Type {
	/** Handle only pages; the Adapter excludes pages from Rank Math's native provider. */
	public function handles_type( $type ) {
		return 'page' === $type
			&& post_type_exists( 'page' )
			&& (bool) \RankMath\Helper::get_settings( 'sitemap.pt_page_sitemap' );
	}

	/**
	 * Retrieve one stable page slice.
	 *
	 * Rank Math orders only by post_modified. Workflow can publish many pages in
	 * one second, so OFFSET pagination then repeats some rows and omits others.
	 * ID is the stable final tie-break and does not change Rank Math's primary
	 * modified-time ordering.
	 *
	 * @param string $post_types Post type requested by Rank Math.
	 * @param int    $count      Number of posts in this sitemap slice.
	 * @param int    $offset     Starting offset for this sitemap slice.
	 * @return object[]
	 */
	protected function get_posts( $post_types, $count, $offset ) {
		if ( 'page' !== $post_types ) {
			return parent::get_posts( $post_types, $count, $offset );
		}

		$exclude_canonical = $this->do_filter( 'sitemap/exlude_posts_with_canonical_urls', false, array( $post_types ) );
		$meta_query = array(
			'relation' => 'OR',
			array(
				'key' => 'rank_math_robots',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key' => 'rank_math_robots',
				'value' => 'noindex',
				'compare' => 'NOT LIKE',
			),
		);
		if ( $exclude_canonical ) {
			$meta_query = array(
				'relation' => 'AND',
				$meta_query,
				array(
					'key' => 'rank_math_canonical_url',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		$posts_page_id = absint( get_option( 'page_for_posts' ) );
		$args = apply_filters(
			'mcp_rankmath/devenia_workflow/page_sitemap_query_args',
			array(
				'post_type' => 'page',
				'post_status' => 'publish',
				'has_password' => false,
				'post__not_in' => $posts_page_id ? array( $posts_page_id ) : array(),
				'posts_per_page' => max( 1, absint( $count ) ),
				'offset' => absint( $offset ),
				'orderby' => array( 'modified' => 'DESC', 'ID' => 'DESC' ),
				'no_found_rows' => true,
				'ignore_sticky_posts' => true,
				'update_post_term_cache' => false,
				'suppress_filters' => false,
				'meta_query' => $meta_query,
			),
			absint( $count ),
			absint( $offset )
		);
		$query = new WP_Query( is_array( $args ) ? $args : array() );
		$posts = array_values( array_filter( (array) $query->posts, static fn( $post ): bool => $post instanceof WP_Post ) );
		$post_ids = array();
		foreach ( $posts as $post ) {
			$post->post_status = 'publish';
			$post->filter = 'sample';
			$post_ids[] = (int) $post->ID;
		}
		update_meta_cache( 'post', $post_ids );

		return $posts;
	}
}
