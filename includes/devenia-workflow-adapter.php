<?php
/**
 * Optional Devenia Workflow integration owned by MCP Abilities - Rank Math.
 *
 * @package MCP_Abilities_RankMath
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MCP_RankMath_Devenia_Workflow_Adapter {
	const META_SEO_SYNC_SIGNATURE = '_devenia_workflow_rankmath_sync_signature';

	/**
	 * Register Rank Math hooks.
	 */
	public static function register(): void {
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_register_hooks' ), 20 );
	}

	/**
	 * Register hooks only when Rank Math is available.
	 */
	public static function maybe_register_hooks(): void {
		if ( ! self::is_active() || ! class_exists( 'Devenia_Workflow' ) ) {
			return;
		}

		add_filter( 'rank_math/opengraph/type', array( __CLASS__, 'filter_translated_posts_page_opengraph_type' ), 20 );
		add_filter( 'rank_math/frontend/canonical', array( __CLASS__, 'filter_translated_posts_page_canonical' ), 20 );
		add_filter( 'rank_math/frontend/title', array( __CLASS__, 'filter_translated_posts_page_seo_title' ), 20 );
		add_filter( 'rank_math/opengraph/facebook/title', array( __CLASS__, 'filter_translated_posts_page_seo_title' ), 20 );
		add_filter( 'rank_math/opengraph/twitter/title', array( __CLASS__, 'filter_translated_posts_page_seo_title' ), 20 );
		add_filter( 'rank_math/frontend/description', array( __CLASS__, 'filter_translated_posts_page_seo_description' ), 20 );
		add_filter( 'rank_math/opengraph/facebook/description', array( __CLASS__, 'filter_translated_posts_page_seo_description' ), 20 );
		add_filter( 'rank_math/opengraph/twitter/description', array( __CLASS__, 'filter_translated_posts_page_seo_description' ), 20 );
		add_filter( 'rank_math/frontend/title', array( __CLASS__, 'filter_author_archive_seo_title' ), 20 );
		add_filter( 'rank_math/opengraph/facebook/title', array( __CLASS__, 'filter_author_archive_seo_title' ), 20 );
		add_filter( 'rank_math/opengraph/twitter/title', array( __CLASS__, 'filter_author_archive_seo_title' ), 20 );
		add_filter( 'rank_math/frontend/description', array( __CLASS__, 'filter_author_archive_seo_description' ), 20 );
		add_filter( 'rank_math/opengraph/facebook/description', array( __CLASS__, 'filter_author_archive_seo_description' ), 20 );
		add_filter( 'rank_math/opengraph/twitter/description', array( __CLASS__, 'filter_author_archive_seo_description' ), 20 );
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'filter_translated_posts_page_json_ld' ), 99, 2 );
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'filter_author_archive_json_ld' ), 100, 2 );
		add_filter( 'rank_math/frontend/title', array( 'Devenia_Workflow', 'filter_translation_job_preview_seo_title' ), 100 );
		add_filter( 'rank_math/opengraph/facebook/title', array( 'Devenia_Workflow', 'filter_translation_job_preview_seo_title' ), 100 );
		add_filter( 'rank_math/opengraph/twitter/title', array( 'Devenia_Workflow', 'filter_translation_job_preview_seo_title' ), 100 );
		add_filter( 'rank_math/frontend/description', array( 'Devenia_Workflow', 'filter_translation_job_preview_seo_description' ), 100 );
		add_filter( 'rank_math/opengraph/facebook/description', array( 'Devenia_Workflow', 'filter_translation_job_preview_seo_description' ), 100 );
		add_filter( 'rank_math/opengraph/twitter/description', array( 'Devenia_Workflow', 'filter_translation_job_preview_seo_description' ), 100 );
		add_filter( 'rank_math/frontend/canonical', array( 'Devenia_Workflow', 'filter_translation_job_preview_canonical' ), 100 );
		add_action( 'devenia_workflow_translation_flush_sitemap_cache', array( __CLASS__, 'flush_sitemap_cache' ) );
		add_filter( 'devenia_workflow_translation_title_template_option_name', array( __CLASS__, 'title_template_option_name' ), 10, 2 );
		add_filter( 'devenia_workflow_translation_canonical_seo_surface', array( __CLASS__, 'canonical_seo_surface' ), 10, 2 );
		add_filter( 'devenia_workflow_translation_sync_seo_meta', array( __CLASS__, 'sync_seo_meta' ), 10, 4 );
		add_filter( 'devenia_workflow_translation_seo_meta_state', array( __CLASS__, 'seo_meta_state' ), 10, 2 );
		add_filter( 'devenia_workflow_translation_route_integrity_issues', array( __CLASS__, 'route_integrity_issues' ), 10, 4 );
		add_filter( 'devenia_workflow_repair_translation_self_redirects', array( __CLASS__, 'repair_translation_self_redirects' ), 10, 3 );
		add_filter( 'devenia_workflow_translation_repair_term_archive_self_redirects', array( __CLASS__, 'repair_term_archive_self_redirects' ), 10, 4 );
		add_filter( 'devenia_workflow_semantic_link_count_content', array( __CLASS__, 'filter_semantic_link_count_content' ), 10, 2 );
		add_filter( 'devenia_workflow_normalize_gutenberg_content_for_storage', array( __CLASS__, 'normalize_faq_saved_markup' ) );
		add_filter( 'devenia_workflow_gutenberg_content_safety', array( __CLASS__, 'gutenberg_content_safety' ), 10, 3 );
		add_filter( 'devenia_workflow_gutenberg_guardrails', 'mcp_rankmath_faq_link_guardrails', 10, 3 );
		add_filter( 'devenia_workflow_source_content_integrity_issue', array( __CLASS__, 'source_content_integrity_issue' ), 10, 3 );
	}

	public static function normalize_faq_saved_markup( string $content ): string {
		if ( false === strpos( $content, '<!-- wp:rank-math/faq-block' ) ) {
			return $content;
		}

		$blocks  = parse_blocks( $content );
		$changed = false;
		$blocks  = self::normalize_faq_blocks( $blocks, $changed );
		if ( ! $changed ) {
			return $content;
		}

		$serialized = serialize_blocks( $blocks );
		return is_string( $serialized ) && '' !== $serialized ? $serialized : $content;
	}

	/**
	 * @param array<int,array<string,mixed>> $blocks Parsed block tree.
	 * @return array<int,array<string,mixed>>
	 */
	private static function normalize_faq_blocks( array $blocks, bool &$changed ): array {
		foreach ( $blocks as &$block ) {
			$name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';
			if ( 'rank-math/faq-block' === $name ) {
				$attrs     = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
				$questions = isset( $attrs['questions'] ) && is_array( $attrs['questions'] ) ? $attrs['questions'] : array();
				if ( ! empty( $questions ) ) {
					$current_html = isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ? $block['innerHTML'] : '';
					$next_html    = self::faq_saved_html_from_attrs( $questions, $current_html );
					if ( '' !== $next_html && $next_html !== $current_html ) {
						$block['innerHTML']   = $next_html;
						$block['innerContent'] = array( $next_html );
						$changed              = true;
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::normalize_faq_blocks( $block['innerBlocks'], $changed );
			}
		}
		unset( $block );

		return $blocks;
	}

	/**
	 * Rebuild Rank Math FAQ static markup from the authoritative block attrs.
	 *
	 * @param array<int,mixed> $questions FAQ question records from the block comment.
	 */
	private static function faq_saved_html_from_attrs( array $questions, string $current_html ): string {
		$outer_open = '<div class="wp-block-rank-math-faq-block rank-math-block">';
		if ( preg_match( '/<div\b[^>]*\bwp-block-rank-math-faq-block\b[^>]*>/i', $current_html, $match ) ) {
			$outer_open = (string) $match[0];
		}

		$list_open = '<div class="rank-math-list">';
		if ( preg_match( '/<div\b[^>]*\brank-math-list\b[^>]*>/i', $current_html, $match ) ) {
			$list_open = (string) $match[0];
		}

		$items = '';
		foreach ( $questions as $question ) {
			if ( ! is_array( $question ) ) {
				continue;
			}
			if ( array_key_exists( 'visible', $question ) && false === (bool) $question['visible'] ) {
				continue;
			}

			$id      = isset( $question['id'] ) ? sanitize_html_class( (string) $question['id'] ) : '';
			$title   = isset( $question['title'] ) ? wp_kses_post( (string) $question['title'] ) : '';
			$content = isset( $question['content'] ) ? wp_kses_post( (string) $question['content'] ) : '';
			if ( '' === $id ) {
				$id = 'faq-question-' . substr( md5( wp_strip_all_tags( $title . '|' . $content ) ), 0, 10 );
			}

			$items .= '<div id="' . esc_attr( $id ) . '" class="rank-math-list-item">';
			$items .= '<h3 class="rank-math-question">' . $title . '</h3>';
			$items .= '<div class="rank-math-answer">' . $content . '</div>';
			$items .= '</div>';
		}

		if ( '' === $items ) {
			return '';
		}

		return $outer_open . $list_open . $items . '</div></div>';
	}

	/**
	 * Add Rank Math block visibility to the generic Gutenberg safety summary.
	 *
	 * @param array<string,mixed>            $safety Existing adapter safety payload.
	 * @param array<int,array<string,mixed>> $blocks Parsed block tree.
	 */
	public static function gutenberg_content_safety( array $safety, array $blocks, string $content ): array {
		unset( $content );

		$summary = isset( $safety['summary'] ) && is_array( $safety['summary'] ) ? $safety['summary'] : array();
		$summary['rank_math_faq_blocks'] = self::count_faq_blocks( $blocks );
		$safety['summary'] = $summary;

		return $safety;
	}

	/**
	 * @param array<int,array<string,mixed>> $blocks Parsed block tree.
	 */
	private static function count_faq_blocks( array $blocks ): int {
		$count = 0;
		foreach ( $blocks as $block ) {
			$name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';
			if ( 'rank-math/faq-block' === $name ) {
				$count++;
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$count += self::count_faq_blocks( $block['innerBlocks'] );
			}
		}
		return $count;
	}

	/**
	 * Provide the Rank Math title-template option for generic workflow title rendering.
	 */
	public static function title_template_option_name( string $option_name, string $template_key ): string {
		return '' !== $template_key ? 'rank-math-options-titles' : $option_name;
	}

	/** Return Rank Math's stored values through Workflow's generic SEO seam. */
	public static function canonical_seo_surface( array $surface, $post ): array {
		if ( ! $post instanceof WP_Post ) {
			return $surface;
		}
		$stored = array(
			'title'         => (string) get_post_meta( (int) $post->ID, 'rank_math_title', true ),
			'description'   => (string) get_post_meta( (int) $post->ID, 'rank_math_description', true ),
			'focus_keyword' => (string) get_post_meta( (int) $post->ID, 'rank_math_focus_keyword', true ),
		);
		foreach ( array( 'title', 'description' ) as $field ) {
			if ( '' !== trim( $stored[ $field ] ) ) {
				$surface[ $field ] = $stored[ $field ];
			}
		}
		$surface['focus_keyword'] = $stored['focus_keyword'];
		return $surface;
	}

	/** Mark Rank Math FAQ findings as source-integrity findings. */
	public static function source_content_integrity_issue( bool $is_integrity_issue, array $issue, $post ): bool {
		unset( $post );
		$code = sanitize_key( (string) ( $issue['code'] ?? '' ) );
		return $is_integrity_issue || str_starts_with( $code, 'rankmath_faq_' );
	}

	public static function filter_translated_posts_page_opengraph_type( string $type ): string {
		return Devenia_Workflow::is_translated_posts_page_request() ? 'website' : $type;
	}

	/**
	 * Keep Rank Math FAQ block attributes from inflating semantic link-count parity.
	 *
	 * Rank Math stores FAQ question/answer data in the Gutenberg block comment.
	 * The rendered FAQ HTML remains in post content, so only the JSON comment
	 * needs to be neutralized before the generic workflow counts visible links.
	 *
	 * @param string                    $content Raw Gutenberg content.
	 * @param array<int,array<string,mixed>> $blocks Parsed block tree.
	 */
	public static function filter_semantic_link_count_content( string $content, array $blocks = array() ): string {
		unset( $blocks );

		return (string) preg_replace(
			'/<!--\s+wp:rank-math\/faq-block\s+.*?-->/s',
			'<!-- wp:rank-math/faq-block -->',
			$content
		);
	}

	public static function filter_translated_posts_page_canonical( string $canonical ): string {
		if ( ! Devenia_Workflow::is_translated_posts_page_request() ) {
			return $canonical;
		}

		if ( method_exists( 'Devenia_Workflow', 'translated_posts_page_canonical_url' ) ) {
			$canonical_url = Devenia_Workflow::translated_posts_page_canonical_url();
			return $canonical_url ?: $canonical;
		}

		$base_url = Devenia_Workflow::translated_posts_page_base_url();
		return $base_url ?: $canonical;
	}

	public static function filter_translated_posts_page_seo_title( string $title ): string {
		if ( ! Devenia_Workflow::is_translated_posts_page_request() ) {
			return $title;
		}

		$post_title = trim( wp_strip_all_tags( get_the_title( get_queried_object_id() ) ) );
		if ( '' === $post_title ) {
			$post_title = __( 'Blog', 'mcp-abilities-rankmath' );
		}

		if ( method_exists( 'Devenia_Workflow', 'translated_posts_page_current_page' ) && method_exists( 'Devenia_Workflow', 'translated_posts_page_page_label' ) ) {
			$page = Devenia_Workflow::translated_posts_page_current_page();
			if ( $page > 1 ) {
				$page_label = Devenia_Workflow::translated_posts_page_page_label( Devenia_Workflow::frontend_language() );
				$post_title = sprintf(
					'%1$s - %2$s %3$d',
					$post_title,
					'' !== $page_label ? $page_label : __( 'Page', 'mcp-abilities-rankmath' ),
					$page
				);
			}
		}

		return Devenia_Workflow::title_from_template_option(
			'rank-math-options-titles',
			'pt_page_title',
			array(
				'title' => $post_title,
			),
			$post_title
		);
	}

	public static function filter_translated_posts_page_seo_description( string $description ): string {
		if ( ! Devenia_Workflow::is_translated_posts_page_request() ) {
			return $description;
		}

		$runtime_description = Devenia_Workflow::translated_posts_page_meta_description( Devenia_Workflow::frontend_language() );
		return '' !== $runtime_description ? $runtime_description : $description;
	}

	/**
	 * Use runtime author archive data for Rank Math title surfaces.
	 *
	 * @param mixed $title Existing SEO title.
	 * @return mixed
	 */
	public static function filter_author_archive_seo_title( $title ) {
		$base_title = Devenia_Workflow::current_author_archive_title( false );
		if ( '' === $base_title ) {
			return $title;
		}

		$seo_title = Devenia_Workflow::title_from_template_option(
			'rank-math-options-titles',
			'author_archive_title',
			array(
				'name'  => $base_title,
				'title' => $base_title,
			),
			$base_title
		);

		return '' !== trim( $seo_title ) ? $seo_title : $title;
	}

	/**
	 * Use runtime author archive data for Rank Math description surfaces.
	 */
	public static function filter_author_archive_seo_description( string $description ): string {
		$localized_description = Devenia_Workflow::current_author_archive_meta_description();

		return '' !== $localized_description ? $localized_description : $description;
	}

	/**
	 * Localize Rank Math JSON-LD entity IDs on translated author archives.
	 *
	 * @param array<string,mixed> $data Rank Math JSON-LD data.
	 * @param mixed               $jsonld Rank Math JSON-LD context object.
	 * @return array<string,mixed>
	 */
	public static function filter_author_archive_json_ld( array $data, $jsonld ): array {
		unset( $jsonld );

		$localized_base = Devenia_Workflow::current_localized_author_archive_url();
		if ( '' === $localized_base ) {
			return $data;
		}

		return self::localize_author_archive_json_ld_value(
			$data,
			trailingslashit( home_url( '/author/' ) ),
			trailingslashit( $localized_base )
		);
	}

	/**
	 * @param mixed $value JSON-LD value.
	 * @return mixed
	 */
	private static function localize_author_archive_json_ld_value( $value, string $source_base, string $localized_base ) {
		if ( is_string( $value ) ) {
			if ( $source_base === $value ) {
				return $localized_base;
			}
			if ( str_starts_with( $value, $source_base . '#' ) ) {
				return $localized_base . substr( $value, strlen( $source_base ) );
			}
			if ( preg_match( '#^' . preg_quote( $source_base, '#' ) . 'page/([0-9]+)/$#', $value, $match ) ) {
				return $localized_base . 'page/' . max( 1, absint( $match[1] ?? 1 ) ) . '/';
			}
			return $value;
		}
		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $child ) {
			$value[ $key ] = self::localize_author_archive_json_ld_value( $child, $source_base, $localized_base );
		}

		return $value;
	}

	/**
	 * Invalidate Rank Math sitemap cache after translation publishes.
	 */
	public static function flush_sitemap_cache(): void {
		if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
			\RankMath\Sitemap\Cache::invalidate_storage();
		}
	}

	/**
	 * @param array<string,mixed> $data Rank Math JSON-LD data.
	 * @param mixed               $jsonld Rank Math JSON-LD context object.
	 * @return array<string,mixed>
	 */
	public static function filter_translated_posts_page_json_ld( array $data, $jsonld ): array {
		if ( ! Devenia_Workflow::is_translated_posts_page_request() ) {
			return $data;
		}

		foreach ( $data as $key => $entity ) {
			if ( ! is_array( $entity ) ) {
				continue;
			}
			$type = $entity['@type'] ?? '';
			if ( is_array( $type ) ) {
				$type = reset( $type );
			}
			if ( in_array( $type, array( 'Article', 'BlogPosting', 'Person' ), true ) ) {
				unset( $data[ $key ] );
				continue;
			}
			if ( 'WebPage' === $type || 'CollectionPage' === $type ) {
				$data[ $key ]['@type'] = 'CollectionPage';
				unset( $data[ $key ]['datePublished'], $data[ $key ]['dateModified'], $data[ $key ]['primaryImageOfPage'] );
			}
		}

		return $data;
	}

	/**
	 * @param array<string,mixed>  $result Current sync result.
	 * @param array<string,array{operation:string,value:string}> $fields Canonical SEO field operations.
	 * @param array<string,mixed>  $context Adapter context.
	 */
	public static function sync_seo_meta( array $result, int $post_id, array $fields, array $context ): array {
		$updated = is_array( $result['updated'] ?? null ) ? $result['updated'] : array();
		$managed = false;
		foreach ( array( 'title' => 'rank_math_title', 'description' => 'rank_math_description', 'focus_keyword' => 'rank_math_focus_keyword' ) as $field => $meta_key ) {
			$instruction = isset( $fields[ $field ] ) && is_array( $fields[ $field ] ) ? $fields[ $field ] : array();
			$operation   = (string) ( $instruction['operation'] ?? '' );
			if ( ! in_array( $operation, array( 'set', 'delete', 'preserve' ), true ) ) {
				$result['success'] = false;
				$result['message'] = 'Canonical SEO Surface supplied an invalid field operation.';
				return $result;
			}
			if ( 'preserve' === $operation ) {
				continue;
			}
			$managed = true;
			if ( 'delete' === $operation ) {
				if ( metadata_exists( 'post', $post_id, $meta_key ) && delete_post_meta( $post_id, $meta_key ) ) {
					$updated[] = $meta_key;
				}
				continue;
			}
			$value = trim( (string) ( $instruction['value'] ?? '' ) );
			if ( '' === $value ) {
				$result['success'] = false;
				$result['message'] = 'Canonical SEO Surface set operations require a nonempty value.';
				return $result;
			}
			if ( 'description' === $field ) {
				update_post_meta( $post_id, $meta_key, sanitize_textarea_field( $value ) );
			} else {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
			}
			$updated[] = $meta_key;
		}

		if ( $managed ) {
			update_post_meta(
				$post_id,
				self::META_SEO_SYNC_SIGNATURE,
				self::seo_sync_signature_from_values(
					(string) get_post_meta( $post_id, 'rank_math_title', true ),
					(string) get_post_meta( $post_id, 'rank_math_description', true ),
					(string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ),
					(string) ( $context['content_hash'] ?? '' )
				)
			);
			clean_post_cache( $post_id );
		}
		$result['success']  = true;
		$result['updated']  = array_values( array_unique( $updated ) );
		$result['adapters'] = self::append_adapter( $result['adapters'] ?? array() );
		return $result;
	}

	/**
	 * @param array<string,mixed> $state Current SEO metadata state.
	 */
	public static function seo_meta_state( array $state, $post ): array {
		if ( ! $post instanceof WP_Post ) {
			return $state;
		}
		$post_id       = (int) $post->ID;
		$current_title = self::normalize_text( wp_strip_all_tags( get_the_title( $post ) ) );
		$seo_title     = self::normalize_text( (string) get_post_meta( $post_id, 'rank_math_title', true ) );
		$description   = self::normalize_text( (string) get_post_meta( $post_id, 'rank_math_description', true ) );
		$focus_keyword = self::normalize_text( (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ) );
		$stale_fields  = is_array( $state['stale_fields'] ?? null ) ? $state['stale_fields'] : array();
		$signature     = (string) get_post_meta( $post_id, self::META_SEO_SYNC_SIGNATURE, true );
		$signature_ok  = '' !== $signature && hash_equals( $signature, self::seo_sync_signature_from_values( $seo_title, $description, $focus_keyword, (string) ( $state['content_hash'] ?? '' ) ) );

		if ( '' !== $seo_title && '' !== $current_title && ! $signature_ok && ! self::seo_title_is_deliberate_custom_title( $seo_title, $description, $focus_keyword ) && ! self::seo_title_matches_post_title( $seo_title, $current_title ) ) {
			$stale_fields[] = 'rank_math_title';
		}

		$state['passed']            = empty( $stale_fields );
		$state['state']             = empty( $stale_fields ) ? self::seo_meta_current_state_name( $seo_title, $signature_ok ) : 'stale';
		$state['stale_fields']      = array_values( array_unique( $stale_fields ) );
		$state['has_custom_title']  = '' !== $seo_title;
		$state['has_description']   = '' !== $description;
		$state['has_focus_keyword'] = '' !== $focus_keyword;
		$state['tracked_signature'] = $signature_ok;
		$state['adapters']          = self::append_adapter( $state['adapters'] ?? array() );
		return $state;
	}

	/**
	 * @param array<int,array<string,mixed>> $issues Current route integrity issues.
	 * @param array<string,mixed>            $context Route context.
	 * @return array<int,array<string,mixed>>
	 */
	public static function route_integrity_issues( array $issues, int $translation_id, string $language, array $context ): array {
		unset( $language );

		$permalink = (string) ( $context['permalink'] ?? get_permalink( $translation_id ) );
		foreach ( self::self_redirects_for_url( $permalink ) as $redirect ) {
			$issues[] = array(
				'code'    => 'localized_permalink_self_redirect',
				'message' => 'An active Rank Math redirection source matches this translated page canonical URL and redirects back to the same URL.',
				'context' => $redirect,
			);
		}
		return $issues;
	}

	/**
	 * @param array<string,mixed> $result Current repair result.
	 * @return array<string,mixed>
	 */
	public static function repair_translation_self_redirects( array $result, int $translation_id, bool $dry_run ): array {
		$conflicts = self::self_redirects_for_url( (string) get_permalink( $translation_id ) );
		$result['adapters'] = self::append_adapter( $result['adapters'] ?? array() );
		if ( empty( $conflicts ) ) {
			return $result;
		}
		if ( $dry_run ) {
			$result['success']   = true;
			$result['changed']   = true;
			$result['dry_run']   = true;
			$result['conflicts'] = $conflicts;
			return $result;
		}

		$delete = self::delete_redirections( $conflicts );
		if ( empty( $delete['success'] ) ) {
			return array_merge( $result, $delete );
		}

		$result['success']       = true;
		$result['changed']       = ! empty( $delete['changed'] );
		$result['deleted_count'] = absint( $delete['deleted_count'] ?? 0 );
		$result['conflicts']     = $conflicts;
		return $result;
	}

	/**
	 * @param array<string,mixed> $result Current repair result.
	 * @param array<string,mixed> $context Localized term archive context.
	 * @return array<string,mixed>
	 */
	public static function repair_term_archive_self_redirects( array $result, string $url, array $context, bool $dry_run ): array {
		unset( $context );

		$conflicts = self::self_redirects_for_url( $url );
		$result['adapters'] = self::append_adapter( $result['adapters'] ?? array() );
		if ( empty( $conflicts ) ) {
			return $result;
		}
		if ( $dry_run ) {
			$result['success']   = true;
			$result['changed']   = true;
			$result['dry_run']   = true;
			$result['conflicts'] = $conflicts;
			return $result;
		}

		$delete = self::delete_redirections( $conflicts );
		if ( empty( $delete['success'] ) ) {
			return array_merge( $result, $delete );
		}

		$result['success']       = true;
		$result['changed']       = ! empty( $delete['changed'] );
		$result['deleted_count'] = absint( $delete['deleted_count'] ?? 0 );
		$result['conflicts']     = $conflicts;
		return $result;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private static function self_redirects_for_url( string $url ): array {
		$target_path = Devenia_Workflow::normalized_url_path( $url );
		if ( '' === $target_path ) {
			return array();
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rank_math_redirections';
		if ( ! self::redirections_table_exists( $table ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, sources, url_to, header_code, status FROM `' . esc_sql( $table ) . '` WHERE status = %s', 'active' ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$conflicts = array();
		foreach ( $rows as $row ) {
			$destination = isset( $row['url_to'] ) ? (string) $row['url_to'] : '';
			if ( Devenia_Workflow::normalized_url_path( $destination ) !== $target_path ) {
				continue;
			}
			foreach ( self::redirection_sources( $row['sources'] ?? array() ) as $source ) {
				if ( 'exact' !== (string) ( $source['comparison'] ?? 'exact' ) ) {
					continue;
				}
				$pattern = isset( $source['pattern'] ) ? (string) $source['pattern'] : '';
				if ( Devenia_Workflow::normalized_url_path( $pattern ) !== $target_path ) {
					continue;
				}
				$conflicts[] = array(
					'id'          => absint( $row['id'] ?? 0 ),
					'source'      => $pattern,
					'destination' => $destination,
					'path'        => $target_path,
					'header_code' => absint( $row['header_code'] ?? 0 ),
					'status'      => (string) ( $row['status'] ?? '' ),
				);
			}
		}
		return $conflicts;
	}

	/**
	 * @param array<int,array<string,mixed>> $conflicts Self-redirect conflicts.
	 * @return array<string,mixed>
	 */
	private static function delete_redirections( array $conflicts ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'rank_math_redirections';
		$ids   = array_values( array_filter( array_unique( array_map( 'absint', wp_list_pluck( $conflicts, 'id' ) ) ) ) );
		if ( empty( $ids ) || ! self::redirections_table_exists( $table ) ) {
			return array(
				'success' => false,
				'changed' => false,
				'message' => 'Rank Math self-redirect conflicts were detected, but no removable redirection IDs were available.',
			);
		}

		$deleted = 0;
		foreach ( $ids as $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$delete_result = $wpdb->query( $wpdb->prepare( 'DELETE FROM `' . esc_sql( $table ) . '` WHERE id = %d', $id ) );
			if ( false === $delete_result ) {
				return array(
					'success' => false,
					'changed' => false,
					'message' => 'Failed to delete Rank Math self-redirect conflicts.',
				);
			}
			$deleted += (int) $delete_result;
		}

		return array(
			'success'       => true,
			'changed'       => 0 < $deleted,
			'deleted_count' => $deleted,
		);
	}

	/**
	 * @param mixed $raw Raw DB value.
	 * @return array<int,array<string,mixed>>
	 */
	private static function redirection_sources( $raw ): array {
		$decoded = is_string( $raw ) ? maybe_unserialize( $raw ) : $raw;
		if ( is_string( $decoded ) ) {
			$json = json_decode( $decoded, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				$decoded = $json;
			}
		}
		return is_array( $decoded ) ? array_values( array_filter( $decoded, 'is_array' ) ) : array();
	}

	private static function redirections_table_exists( string $table ): bool {
		static $cache = array();
		if ( isset( $cache[ $table ] ) ) {
			return $cache[ $table ];
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cache[ $table ] = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table );
		return $cache[ $table ];
	}

	private static function seo_title_matches_post_title( string $seo_title, string $post_title ): bool {
		$seo_title  = self::normalize_text( $seo_title );
		$post_title = self::normalize_text( $post_title );
		if ( '' === $seo_title || '' === $post_title || false !== strpos( $seo_title, '%title%' ) ) {
			return true;
		}
		$seo_lower  = function_exists( 'mb_strtolower' ) ? mb_strtolower( $seo_title, 'UTF-8' ) : strtolower( $seo_title );
		$post_lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $post_title, 'UTF-8' ) : strtolower( $post_title );
		if ( $seo_lower === $post_lower || false !== strpos( $seo_lower, $post_lower ) ) {
			return true;
		}

		$site_name = self::normalize_text( get_bloginfo( 'name', 'display' ) );
		if ( '' === $site_name ) {
			return false;
		}

		return self::seo_title_comparison_key( $seo_title, $site_name ) === self::seo_title_comparison_key( $post_title, $site_name );
	}

	private static function seo_title_is_deliberate_custom_title( string $seo_title, string $description, string $focus_keyword ): bool {
		$seo_title = self::normalize_text( $seo_title );
		if ( '' === $seo_title || false !== strpos( $seo_title, '%title%' ) ) {
			return true;
		}

		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $seo_title, 'UTF-8' ) : strlen( $seo_title );
		return $length >= 18 && $length <= 90 && '' !== $description && '' !== $focus_keyword;
	}

	private static function seo_meta_current_state_name( string $seo_title, bool $signature_ok ): string {
		if ( '' === $seo_title ) {
			return 'default_title_pattern';
		}
		return $signature_ok ? 'current' : 'custom_title_reviewed';
	}

	private static function seo_sync_signature_from_values( string $title, string $description, string $focus_keyword, string $content_hash ): string {
		return hash(
			'sha256',
			self::normalize_text( $title ) . "\n" .
			self::normalize_text( $description ) . "\n" .
			self::normalize_text( $focus_keyword ) . "\n" .
			self::normalize_text( $content_hash )
		);
	}

	private static function seo_title_comparison_key( string $text, string $site_name ): string {
		$text      = self::normalize_text( $text );
		$site_name = self::normalize_text( $site_name );
		if ( '' !== $site_name ) {
			$text = preg_replace( '/\b' . preg_quote( $site_name, '/' ) . '\b/iu', ' ', $text );
		}
		$text = preg_replace( '/[|:·•\-–—]+/u', ' ', is_string( $text ) ? $text : '' );
		$text = preg_replace( '/\s+/u', ' ', is_string( $text ) ? $text : '' );
		$text = trim( is_string( $text ) ? $text : '' );
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
	}

	private static function normalize_text( string $text ): string {
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( is_string( $text ) ? $text : '' );
	}

	/**
	 * @param mixed $adapters Current adapter list.
	 * @return array<int,string>
	 */
	private static function append_adapter( $adapters ): array {
		$adapters   = is_array( $adapters ) ? $adapters : array();
		$adapters[] = 'rank_math';
		return array_values( array_unique( array_map( 'sanitize_key', $adapters ) ) );
	}

	/**
	 * @param array<int,string> $names Existing block names.
	 * @param array<int,string> $extra Extra block names.
	 * @return array<int,string>
	 */
	private static function merge_block_names( array $names, array $extra ): array {
		return array_values( array_unique( array_merge( array_map( 'strval', $names ), array_map( 'strval', $extra ) ) ) );
	}

	private static function is_active(): bool {
		return defined( 'RANK_MATH_VERSION' )
			|| class_exists( 'RankMath' )
			|| class_exists( '\RankMath\Helper' );
	}
}

MCP_RankMath_Devenia_Workflow_Adapter::register();
