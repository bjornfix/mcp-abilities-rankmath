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
		$posts_page_id = absint( get_option( 'page_for_posts' ) );
		$wanted = absint( $offset ) + max( 1, absint( $count ) );
		$batch_size = max( 1, absint( $count ) );
		$scan_offset = 0;
		$eligible = array();
		while ( count( $eligible ) < $wanted ) {
			$args = apply_filters(
				'mcp_rankmath/devenia_workflow/page_sitemap_query_args',
				array(
					'post_type' => 'page',
					'post_status' => 'publish',
					'has_password' => false,
					'posts_per_page' => $batch_size,
					'offset' => $scan_offset,
					'orderby' => array( 'modified' => 'DESC', 'ID' => 'DESC' ),
					'no_found_rows' => true,
					'ignore_sticky_posts' => true,
					'update_post_term_cache' => false,
					'suppress_filters' => false,
				),
				$batch_size,
				$scan_offset
			);
			$query = new WP_Query( is_array( $args ) ? $args : array() );
			$batch = array_values( array_filter( (array) $query->posts, static fn( $post ): bool => $post instanceof WP_Post ) );
			if ( empty( $batch ) ) {
				break;
			}
			$scan_offset += count( $batch );
			foreach ( $batch as $post ) {
				$post_id = (int) $post->ID;
				if ( $post_id === $posts_page_id || ! \RankMath\Sitemap\Sitemap::is_object_indexable( $post_id ) ) {
					continue;
				}
				if ( $exclude_canonical && '' !== (string) \RankMath\Helper::get_post_meta( 'canonical_url', $post_id ) ) {
					continue;
				}
				$eligible[] = $post;
			}
			if ( count( $batch ) < $batch_size ) {
				break;
			}
		}

		$posts = array_slice( $eligible, absint( $offset ), max( 1, absint( $count ) ) );
		foreach ( $posts as $post ) {
			$post->post_status = 'publish';
			$post->filter = 'sample';
		}

		return $posts;
	}
}
