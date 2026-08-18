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

		global $wpdb;

		$join_filter = $this->do_filter( 'sitemap/get_posts/join', '', $post_types );
		$where_filter = $this->do_filter( 'sitemap/get_posts/where', '', $post_types );
		$exclude_canonical = $this->do_filter( 'sitemap/exlude_posts_with_canonical_urls', false, array( $post_types ) );
		if ( $exclude_canonical ) {
			$join_filter .= " LEFT JOIN {$wpdb->postmeta} AS pm_canonical ON ( p.ID = pm_canonical.post_id AND pm_canonical.meta_key = 'rank_math_canonical_url' )";
			$where_filter .= ' AND pm_canonical.meta_value IS NULL';
		}

		$posts_page_id = absint( get_option( 'page_for_posts' ) );
		$sql = "
			SELECT l.ID, post_title, post_content, post_name, post_parent, post_author, post_modified_gmt, post_date, post_date_gmt, post_type
			FROM (
				SELECT DISTINCT p.ID FROM {$wpdb->posts} AS p
				{$join_filter}
				LEFT JOIN {$wpdb->postmeta} AS pm ON ( p.ID = pm.post_id AND pm.meta_key = 'rank_math_robots' )
				WHERE (
					( pm.meta_key = 'rank_math_robots' AND pm.meta_value NOT LIKE '%noindex%' ) OR
					pm.post_id IS NULL
				)
				AND p.post_type = %s AND p.post_status = 'publish' AND p.post_password = ''
				AND p.ID != %d
				{$where_filter}
				ORDER BY p.post_modified DESC, p.ID DESC LIMIT %d OFFSET %d
			)
			o JOIN {$wpdb->posts} l ON l.ID = o.ID
		";

		$posts = \RankMath\Helpers\DB::get_results(
			$wpdb->prepare( $sql, 'page', $posts_page_id, absint( $count ), absint( $offset ) )
		);
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
