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

	/** Request-local populations keyed by query and eligibility context. */
	private array $page_populations = array();

	/**
	 * Count the same unique eligible pages used by sitemap slices.
	 *
	 * @param string $post_types Post type requested by Rank Math.
	 * @return int
	 */
	public function get_post_type_count( $post_types ) {
		if ( 'page' !== $post_types ) {
			return parent::get_post_type_count( $post_types );
		}

		return count( $this->get_page_population() );
	}

	/**
	 * Retrieve one stable slice of the counted population.
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

		$posts = array_slice( $this->get_page_population(), absint( $offset ), max( 1, absint( $count ) ) );
		foreach ( $posts as $post ) {
			$post->post_status = 'publish';
			$post->filter = 'sample';
		}

		return $posts;
	}

	/**
	 * Read all eligible pages once per request and effective query context.
	 *
	 * Native eligibility and canonical rules are shared by the count and every
	 * slice. The first occurrence of each ID retains modified/ID query order.
	 * No persistent cache or site-specific identifiers are introduced.
	 *
	 * @return WP_Post[]
	 */
	private function get_page_population(): array {
		$exclude_canonical = (bool) $this->do_filter( 'sitemap/exlude_posts_with_canonical_urls', false, array( 'page' ) );
		$posts_page_id = absint( get_option( 'page_for_posts' ) );
		$batch_size = 200;
		$args = $this->get_page_query_args( $batch_size, 0 );
		$context = wp_json_encode(
			array( $args, $posts_page_id, $exclude_canonical, apply_filters( 'wpml_current_language', null ), false !== has_action( 'wpml_switch_language' ) )
		);
		$cache_key = is_string( $context ) ? hash( 'sha256', $context ) : null;
		if ( null !== $cache_key && isset( $this->page_populations[ $cache_key ] ) ) {
			return $this->page_populations[ $cache_key ];
		}

		$scan_offset = 0;
		$eligible = array();
		$seen = array();
		while ( true ) {
			$query = $this->query_pages_in_all_wpml_languages( $args );
			$batch = (array) $query->posts;
			if ( empty( $batch ) ) {
				break;
			}
			foreach ( $batch as $post ) {
				if ( ! $post instanceof WP_Post || isset( $seen[ $post->ID ] ) ) {
					continue;
				}
				$post_id = (int) $post->ID;
				$seen[ $post_id ] = true;
				if ( $post_id === $posts_page_id || ! \RankMath\Sitemap\Sitemap::is_object_indexable( $post_id ) ) {
					continue;
				}
				if ( $exclude_canonical && '' !== (string) \RankMath\Helper::get_post_meta( 'canonical_url', $post_id ) ) {
					continue;
				}
				$eligible[] = $post;
			}
			$scan_offset += count( $batch );
			if ( count( $batch ) < $batch_size ) {
				break;
			}
			$args = $this->get_page_query_args( $batch_size, $scan_offset );
		}

		if ( null !== $cache_key ) {
			$this->page_populations[ $cache_key ] = $eligible;
		}
		return $eligible;
	}

	/**
	 * Preserve the existing native query and its public argument filter.
	 *
	 * @param int $batch_size Query batch size.
	 * @param int $offset     Raw query offset.
	 * @return array<string,mixed>
	 */
	private function get_page_query_args( int $batch_size, int $offset ): array {
		$args = apply_filters(
			'mcp_rankmath/devenia_workflow/page_sitemap_query_args',
			array(
				'post_type' => 'page',
				'post_status' => 'publish',
				'has_password' => false,
				'posts_per_page' => $batch_size,
				'offset' => $offset,
				'orderby' => array( 'modified' => 'DESC', 'ID' => 'DESC' ),
				'no_found_rows' => true,
				'ignore_sticky_posts' => true,
				'update_post_term_cache' => false,
				'suppress_filters' => false,
			),
			$batch_size,
			$offset
		);

		return is_array( $args ) ? $args : array();
	}

	/**
	 * Use WPML's native all-language scope without suppressing query filters.
	 *
	 * The scope covers one query only. WPML 5 uses a stack and closes the
	 * current frame with null; the legacy API restores the prior language.
	 *
	 * @param array<string,mixed> $args Native page query arguments.
	 * @return WP_Query
	 */
	private function query_pages_in_all_wpml_languages( array $args ): WP_Query {
		$language = apply_filters( 'wpml_current_language', null );
		if ( ! is_string( $language ) || '' === $language || 'all' === $language || false === has_action( 'wpml_switch_language' ) ) {
			return new WP_Query( $args );
		}

		// WPML frontend rejects the global 'all' language; its native query filter
		// accepts the explicit per-query language without disabling other filters.
		if ( ! array_key_exists( 'lang', $args ) ) {
			$args['lang'] = 'all';
		}

		$uses_scope_stack = ! defined( 'ICL_SITEPRESS_VERSION' ) || version_compare( ICL_SITEPRESS_VERSION, '5.0', '>=' );
		do_action( 'wpml_switch_language', 'all' );
		try {
			return new WP_Query( $args );
		} finally {
			do_action( 'wpml_switch_language', $uses_scope_stack ? null : $language );
		}
	}
}
