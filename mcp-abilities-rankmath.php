<?php
/**
 * Plugin Name: MCP Abilities - Rank Math
 * Plugin URI: https://github.com/bjornfix/mcp-abilities-rankmath
 * Description: Rank Math SEO abilities for MCP. Get and update meta descriptions, titles, focus keywords, and other SEO settings.
 * Version: 1.0.2
 * Author: Devenia
 * Author URI: https://devenia.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Requires Plugins: abilities-api
 *
 * @package MCP_Abilities_RankMath
 */

declare( strict_types=1 );

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if Abilities API is available.
 */
function mcp_rankmath_check_dependencies(): bool {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>MCP Abilities - Rank Math</strong> requires the <a href="https://github.com/WordPress/abilities-api">Abilities API</a> plugin to be installed and activated.</p></div>';
		} );
		return false;
	}
	return true;
}

/**
 * Check if Rank Math is active.
 */
function mcp_rankmath_is_active(): bool {
	return class_exists( 'RankMath' ) || defined( 'RANK_MATH_VERSION' );
}

/**
 * Get all Rank Math meta keys we support.
 */
function mcp_rankmath_get_meta_keys(): array {
	return array(
		'rank_math_title',
		'rank_math_description',
		'rank_math_focus_keyword',
		'rank_math_robots',
		'rank_math_canonical_url',
		'rank_math_primary_category',
		'rank_math_pillar_content',
		'rank_math_cornerstone_content',
	);
}

/**
 * Register Rank Math abilities.
 */
function mcp_register_rankmath_abilities(): void {
	if ( ! mcp_rankmath_check_dependencies() ) {
		return;
	}

	// =========================================================================
	// RANK MATH - Get SEO Meta
	// =========================================================================
	wp_register_ability(
		'rankmath/get-meta',
		array(
			'label'               => 'Get Rank Math SEO Meta',
			'description'         => 'Get Rank Math SEO meta data for a post or page. Returns title, description, focus keyword, robots, and other SEO settings.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'id' ),
				'properties'           => array(
					'id'   => array(
						'type'        => 'integer',
						'description' => 'Post or page ID.',
					),
					'type' => array(
						'type'        => 'string',
						'enum'        => array( 'post', 'page', 'any' ),
						'default'     => 'any',
						'description' => 'Post type to query (default: any).',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'       => array( 'type' => 'boolean' ),
					'id'            => array( 'type' => 'integer' ),
					'title'         => array( 'type' => 'string' ),
					'url'           => array( 'type' => 'string' ),
					'seo_title'     => array( 'type' => 'string' ),
					'seo_description' => array( 'type' => 'string' ),
					'focus_keyword' => array( 'type' => 'string' ),
					'robots'        => array( 'type' => 'array' ),
					'canonical_url' => array( 'type' => 'string' ),
					'is_pillar'     => array( 'type' => 'boolean' ),
					'is_cornerstone' => array( 'type' => 'boolean' ),
					'message'       => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				if ( ! mcp_rankmath_is_active() ) {
					return array(
						'success' => false,
						'message' => 'Rank Math SEO plugin is not active.',
					);
				}

				$post_id = (int) $input['id'];
				$post    = get_post( $post_id );

				if ( ! $post ) {
					return array(
						'success' => false,
						'message' => 'Post or page not found with ID: ' . $post_id,
					);
				}

				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return array(
						'success' => false,
						'message' => 'You do not have permission to access this post.',
					);
				}

				// Get all Rank Math meta.
				$seo_title     = get_post_meta( $post_id, 'rank_math_title', true );
				$seo_desc      = get_post_meta( $post_id, 'rank_math_description', true );
				$focus_keyword = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
				$robots        = get_post_meta( $post_id, 'rank_math_robots', true );
				$canonical     = get_post_meta( $post_id, 'rank_math_canonical_url', true );
				$is_pillar     = get_post_meta( $post_id, 'rank_math_pillar_content', true );
				$is_cornerstone = get_post_meta( $post_id, 'rank_math_cornerstone_content', true );

				return array(
					'success'         => true,
					'id'              => $post_id,
					'title'           => $post->post_title,
					'url'             => get_permalink( $post_id ),
					'post_type'       => $post->post_type,
					'seo_title'       => $seo_title ?: '',
					'seo_description' => $seo_desc ?: '',
					'focus_keyword'   => $focus_keyword ?: '',
					'robots'          => is_array( $robots ) ? $robots : array(),
					'canonical_url'   => $canonical ?: '',
					'is_pillar'       => $is_pillar === 'on',
					'is_cornerstone'  => $is_cornerstone === 'on',
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	// =========================================================================
	// RANK MATH - Update SEO Meta
	// =========================================================================
	wp_register_ability(
		'rankmath/update-meta',
		array(
			'label'               => 'Update Rank Math SEO Meta',
			'description'         => 'Update Rank Math SEO meta data for a post or page. Can update title, description, focus keyword, robots, canonical URL, and content flags.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'id' ),
				'properties'           => array(
					'id'              => array(
						'type'        => 'integer',
						'description' => 'Post or page ID.',
					),
					'seo_title'       => array(
						'type'        => 'string',
						'description' => 'Custom SEO title. Use variables like %title%, %sitename%, %sep%.',
					),
					'seo_description' => array(
						'type'        => 'string',
						'description' => 'Meta description (recommended: 150-160 characters).',
					),
					'focus_keyword'   => array(
						'type'        => 'string',
						'description' => 'Focus keyword(s). Separate multiple with commas.',
					),
					'robots'          => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'string' ),
						'description' => 'Robot meta tags: index, noindex, follow, nofollow, etc.',
					),
					'canonical_url'   => array(
						'type'        => 'string',
						'description' => 'Custom canonical URL (leave empty to use default).',
					),
					'is_pillar'       => array(
						'type'        => 'boolean',
						'description' => 'Mark as pillar content.',
					),
					'is_cornerstone'  => array(
						'type'        => 'boolean',
						'description' => 'Mark as cornerstone content.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'  => array( 'type' => 'boolean' ),
					'id'       => array( 'type' => 'integer' ),
					'updated'  => array( 'type' => 'array' ),
					'url'      => array( 'type' => 'string' ),
					'message'  => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				if ( ! mcp_rankmath_is_active() ) {
					return array(
						'success' => false,
						'message' => 'Rank Math SEO plugin is not active.',
					);
				}

				$post_id = (int) $input['id'];
				$post    = get_post( $post_id );

				if ( ! $post ) {
					return array(
						'success' => false,
						'message' => 'Post or page not found with ID: ' . $post_id,
					);
				}

				// Check edit permission for this specific post.
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return array(
						'success' => false,
						'message' => 'You do not have permission to edit this post.',
					);
				}

				$updated = array();

				// Update SEO title.
				if ( isset( $input['seo_title'] ) ) {
					update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( $input['seo_title'] ) );
					$updated[] = 'seo_title';
				}

				// Update SEO description.
				if ( isset( $input['seo_description'] ) ) {
					update_post_meta( $post_id, 'rank_math_description', sanitize_textarea_field( $input['seo_description'] ) );
					$updated[] = 'seo_description';
				}

				// Update focus keyword.
				if ( isset( $input['focus_keyword'] ) ) {
					update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( $input['focus_keyword'] ) );
					$updated[] = 'focus_keyword';
				}

				// Update robots.
				if ( isset( $input['robots'] ) && is_array( $input['robots'] ) ) {
					$allowed_robots = array( 'index', 'noindex', 'follow', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' );
					$robots = array_filter( $input['robots'], function( $r ) use ( $allowed_robots ) {
						return in_array( $r, $allowed_robots, true );
					});
					update_post_meta( $post_id, 'rank_math_robots', $robots );
					$updated[] = 'robots';
				}

				// Update canonical URL.
				if ( isset( $input['canonical_url'] ) ) {
					$canonical = esc_url_raw( $input['canonical_url'] );
					if ( empty( $input['canonical_url'] ) ) {
						delete_post_meta( $post_id, 'rank_math_canonical_url' );
					} else {
						update_post_meta( $post_id, 'rank_math_canonical_url', $canonical );
					}
					$updated[] = 'canonical_url';
				}

				// Update pillar content flag.
				if ( isset( $input['is_pillar'] ) ) {
					if ( $input['is_pillar'] ) {
						update_post_meta( $post_id, 'rank_math_pillar_content', 'on' );
					} else {
						delete_post_meta( $post_id, 'rank_math_pillar_content' );
					}
					$updated[] = 'is_pillar';
				}

				// Update cornerstone content flag.
				if ( isset( $input['is_cornerstone'] ) ) {
					if ( $input['is_cornerstone'] ) {
						update_post_meta( $post_id, 'rank_math_cornerstone_content', 'on' );
					} else {
						delete_post_meta( $post_id, 'rank_math_cornerstone_content' );
					}
					$updated[] = 'is_cornerstone';
				}

				if ( empty( $updated ) ) {
					return array(
						'success' => false,
						'message' => 'No fields provided to update.',
					);
				}

				return array(
					'success' => true,
					'id'      => $post_id,
					'updated' => $updated,
					'url'     => get_permalink( $post_id ),
					'message' => 'Updated ' . count( $updated ) . ' field(s): ' . implode( ', ', $updated ),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	// =========================================================================
	// RANK MATH - Bulk Get SEO Meta
	// =========================================================================
	wp_register_ability(
		'rankmath/bulk-get-meta',
		array(
			'label'               => 'Bulk Get Rank Math SEO Meta',
			'description'         => 'Get Rank Math SEO meta for multiple posts/pages. Useful for auditing SEO across content.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'post_type'    => array(
						'type'        => 'string',
						'default'     => 'any',
						'description' => 'Filter by post type: post, page, or any.',
					),
					'per_page'     => array(
						'type'        => 'integer',
						'default'     => 20,
						'minimum'     => 1,
						'maximum'     => 100,
						'description' => 'Number of items per page (max 100).',
					),
					'page'         => array(
						'type'        => 'integer',
						'default'     => 1,
						'minimum'     => 1,
						'description' => 'Page number.',
					),
					'missing_desc' => array(
						'type'        => 'boolean',
						'default'     => false,
						'description' => 'Only return posts missing meta description.',
					),
					'search'       => array(
						'type'        => 'string',
						'description' => 'Search in post titles.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'items'   => array( 'type' => 'array' ),
					'total'   => array( 'type' => 'integer' ),
					'page'    => array( 'type' => 'integer' ),
					'pages'   => array( 'type' => 'integer' ),
				),
			),
			'execute_callback'    => function ( array $input = array() ): array {
				if ( ! mcp_rankmath_is_active() ) {
					return array(
						'success' => false,
						'message' => 'Rank Math SEO plugin is not active.',
					);
				}

				$per_page     = isset( $input['per_page'] ) ? min( 100, max( 1, (int) $input['per_page'] ) ) : 20;
				$page         = isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1;
				$post_type    = isset( $input['post_type'] ) && $input['post_type'] !== 'any' ? $input['post_type'] : array( 'post', 'page' );
				$missing_desc = ! empty( $input['missing_desc'] );

				// When filtering for missing descriptions, fetch more and filter in PHP.
				$fetch_limit = $missing_desc ? $per_page * 5 : $per_page;

				$args = array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => $fetch_limit,
					'paged'          => $missing_desc ? 1 : $page,
					'orderby'        => 'modified',
					'order'          => 'DESC',
				);

				if ( ! current_user_can( 'edit_others_posts' ) ) {
					$current_user = wp_get_current_user();
					$args['author'] = $current_user->ID;
				}

				if ( ! empty( $input['search'] ) ) {
					$args['s'] = sanitize_text_field( $input['search'] );
				}

				$query = new WP_Query( $args );
				$items = array();

				foreach ( $query->posts as $post ) {
					if ( ! current_user_can( 'edit_post', $post->ID ) ) {
						continue;
					}

					$seo_desc = get_post_meta( $post->ID, 'rank_math_description', true );

					// Filter for missing descriptions if requested.
					if ( $missing_desc && ! empty( $seo_desc ) ) {
						continue;
					}

					$items[] = array(
						'id'              => $post->ID,
						'title'           => $post->post_title,
						'post_type'       => $post->post_type,
						'url'             => get_permalink( $post->ID ),
						'seo_title'       => get_post_meta( $post->ID, 'rank_math_title', true ) ?: '',
						'seo_description' => $seo_desc ?: '',
						'focus_keyword'   => get_post_meta( $post->ID, 'rank_math_focus_keyword', true ) ?: '',
					);

					// Stop when we have enough items.
					if ( count( $items ) >= $per_page ) {
						break;
					}
				}

				$total = $missing_desc ? count( $items ) : (int) $query->found_posts;
				$pages = $missing_desc ? 1 : (int) $query->max_num_pages;

				return array(
					'success' => true,
					'items'   => $items,
					'total'   => $total,
					'page'    => $missing_desc ? 1 : $page,
					'pages'   => $pages,
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);
}
add_action( 'wp_abilities_api_init', 'mcp_register_rankmath_abilities' );
