<?php
/**
 * Plugin Name: MCP Abilities - Rank Math
 * Plugin URI: https://github.com/bjornfix/mcp-abilities-rankmath
 * Description: Rank Math SEO abilities for MCP. Get and update meta descriptions, titles, focus keywords, and other SEO settings.
 * Version: 1.1.3
 * Author: Devenia
 * Author URI: https://devenia.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.9
 * Requires PHP: 8.0
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
 * Allowed Rank Math redirection status values.
 */
function mcp_rankmath_allowed_redirection_statuses(): array {
	return array( 'active', 'inactive' );
}

/**
 * Allowed Rank Math redirection comparison types.
 */
function mcp_rankmath_allowed_redirection_comparisons(): array {
	return array( 'exact', 'contains', 'start', 'end', 'regex' );
}

/**
 * Allowed Rank Math redirection header codes.
 */
function mcp_rankmath_allowed_redirection_headers(): array {
	return array( 301, 302, 307, 308, 410, 451 );
}

/**
 * Allowed Rank Math option name prefixes.
 */
function mcp_rankmath_allowed_option_prefixes(): array {
	return array(
		'rank_math_',
		'rank-math-',
	);
}

/**
 * Allowed SQL LIKE patterns for Rank Math option names.
 *
 * @return array
 */
function mcp_rankmath_allowed_option_like_patterns(): array {
	return array(
		'rank_math_%',
		'rank-math-%',
	);
}

/**
 * Check if an option name is allowed for Rank Math abilities.
 *
 * @param string $name Option name.
 * @return bool
 */
function mcp_rankmath_is_allowed_option_name( string $name ): bool {
	foreach ( mcp_rankmath_allowed_option_prefixes() as $prefix ) {
		if ( str_starts_with( $name, $prefix ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Get llms.txt brand overrides used for request-scoped heading output.
 *
 * @return array<string,string>
 */
function mcp_rankmath_get_llms_branding(): array {
	return array(
		'name'        => 'Log In',
		'description' => 'Everywhere!',
	);
}

/**
 * Check whether the current request targets the dynamic llms.txt endpoint.
 *
 * @return bool
 */
function mcp_rankmath_is_llms_request(): bool {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';
	if ( '' === $request_uri ) {
		return false;
	}

	$path = wp_parse_url( $request_uri, PHP_URL_PATH );
	return is_string( $path ) && '/llms.txt' === untrailingslashit( $path );
}

/**
 * Override Rank Math title settings for the llms.txt request only.
 *
 * Keeps sitewide entity/schema settings intact while allowing a site-branded
 * llms.txt heading.
 *
 * @param mixed $value Option value from get_option().
 * @return mixed
 */
function mcp_rankmath_filter_llms_title_settings( $value ) {
	if ( ! mcp_rankmath_is_llms_request() || ! is_array( $value ) ) {
		return $value;
	}

	$branding                        = mcp_rankmath_get_llms_branding();
	$value['knowledgegraph_name']    = $branding['name'];
	$value['organization_description'] = $branding['description'];

	return $value;
}

add_filter( 'option_rank-math-options-titles', 'mcp_rankmath_filter_llms_title_settings' );

/**
 * Get a Rank Math option as an array.
 *
 * @param string $name Option name.
 * @return array<string,mixed>
 */
function mcp_rankmath_get_option_array( string $name ): array {
	$value = get_option( $name, array() );
	return is_array( $value ) ? $value : array();
}

/**
 * Return the Rank Math titles settings array.
 *
 * @return array<string,mixed>
 */
function mcp_rankmath_get_titles_settings(): array {
	return mcp_rankmath_get_option_array( 'rank-math-options-titles' );
}

/**
 * Return the Rank Math general settings array.
 *
 * @return array<string,mixed>
 */
function mcp_rankmath_get_general_settings(): array {
	return mcp_rankmath_get_option_array( 'rank-math-options-general' );
}

/**
 * Return the Rank Math sitemap settings array.
 *
 * @return array<string,mixed>
 */
function mcp_rankmath_get_sitemap_settings(): array {
	return mcp_rankmath_get_option_array( 'rank-math-options-sitemap' );
}

/**
 * Get the preferred organization telephone from Rank Math title settings.
 *
 * @param array<string,mixed> $titles Titles settings.
 * @return string
 */
function mcp_rankmath_get_preferred_phone( array $titles ): string {
	$phone = isset( $titles['phone'] ) ? sanitize_text_field( (string) $titles['phone'] ) : '';
	if ( '' !== $phone ) {
		return $phone;
	}

	if ( isset( $titles['phone_numbers'] ) && is_array( $titles['phone_numbers'] ) ) {
		foreach ( $titles['phone_numbers'] as $phone_number ) {
			if ( ! is_array( $phone_number ) || empty( $phone_number['number'] ) ) {
				continue;
			}

			$phone = sanitize_text_field( (string) $phone_number['number'] );
			if ( '' !== $phone ) {
				return $phone;
			}
		}
	}

	return '';
}

/**
 * Check whether a schema node represents the site organization.
 *
 * @param array<string,mixed> $entity Schema node.
 * @return bool
 */
function mcp_rankmath_is_organization_entity( array $entity ): bool {
	$types = $entity['@type'] ?? array();
	if ( is_string( $types ) ) {
		$types = array( $types );
	}

	if ( is_array( $types ) && in_array( 'Organization', $types, true ) ) {
		return true;
	}

	$id = isset( $entity['@id'] ) ? (string) $entity['@id'] : '';
	return '' !== $id && home_url( '/#organization' ) === $id;
}

/**
 * Inject explicit contact details into organization schema nodes.
 *
 * Rank Math stores these fields in the titles settings, but its final
 * organization sanitization can strip them from the public JSON-LD graph.
 *
 * @param array<string,mixed> $entity Schema node.
 * @param array<string,mixed> $titles Titles settings.
 * @return array<string,mixed>
 */
function mcp_rankmath_add_organization_contact_fields( array $entity, array $titles ): array {
	if ( ! mcp_rankmath_is_organization_entity( $entity ) ) {
		return $entity;
	}

	$email = isset( $titles['email'] ) ? sanitize_email( (string) $titles['email'] ) : '';
	if ( '' !== $email ) {
		$entity['email'] = $email;
	}

	$phone = mcp_rankmath_get_preferred_phone( $titles );
	if ( '' !== $phone ) {
		$entity['telephone'] = $phone;
	}

	return $entity;
}

/**
 * Recursively walk final Rank Math schema data and restore organization contact fields.
 *
 * @param array<string,mixed> $data Validated JSON-LD data.
 * @return array<string,mixed>
 */
function mcp_rankmath_filter_validated_schema_organization_contact_fields( array $data ): array {
	$titles = mcp_rankmath_get_titles_settings();
	$email  = isset( $titles['email'] ) ? sanitize_email( (string) $titles['email'] ) : '';
	$phone  = mcp_rankmath_get_preferred_phone( $titles );

	if ( '' === $email && '' === $phone ) {
		return $data;
	}

	$walker = static function ( $value ) use ( &$walker, $titles ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$value = mcp_rankmath_add_organization_contact_fields( $value, $titles );

		foreach ( $value as $key => $child ) {
			if ( is_array( $child ) ) {
				$value[ $key ] = $walker( $child );
			}
		}

		return $value;
	};

	return $walker( $data );
}

add_filter( 'rank_math/schema/validated_data', 'mcp_rankmath_filter_validated_schema_organization_contact_fields', 20 );

/**
 * Build effective social profile URLs from Rank Math title settings.
 *
 * @param array<string,mixed> $titles Titles settings.
 * @return array<int,string>
 */
function mcp_rankmath_get_social_profiles_from_titles( array $titles ): array {
	$profiles = array();

	$facebook = isset( $titles['social_url_facebook'] ) ? esc_url_raw( (string) $titles['social_url_facebook'] ) : '';
	if ( '' !== $facebook ) {
		$profiles[] = $facebook;
	}

	$twitter = isset( $titles['twitter_author_names'] ) ? ltrim( sanitize_text_field( (string) $titles['twitter_author_names'] ), '@' ) : '';
	if ( '' !== $twitter ) {
		$profiles[] = 'https://twitter.com/' . $twitter;
	}

	$additional = isset( $titles['social_additional_profiles'] ) ? (string) $titles['social_additional_profiles'] : '';
	if ( '' !== trim( $additional ) ) {
		$lines = preg_split( '/\r\n|\r|\n/', $additional ) ?: array();
		foreach ( $lines as $line ) {
			$url = esc_url_raw( trim( (string) $line ) );
			if ( '' !== $url ) {
				$profiles[] = $url;
			}
		}
	}

	return array_values( array_unique( array_filter( $profiles ) ) );
}

/**
 * Normalize additional social profile input into Rank Math storage format.
 *
 * @param mixed $profiles Raw input.
 * @return string
 */
function mcp_rankmath_normalize_additional_profiles( $profiles ): string {
	if ( is_array( $profiles ) ) {
		$profiles = array_map(
			static function ( $item ): string {
				return esc_url_raw( trim( (string) $item ) );
			},
			$profiles
		);
		$profiles = array_values( array_filter( $profiles ) );
		return implode( "\n", $profiles );
	}

	return trim( (string) $profiles );
}

/**
 * Get available Rank Math modules with current status.
 *
 * @return array<int,array<string,mixed>>
 */
function mcp_rankmath_get_module_records(): array {
	if ( ! function_exists( 'rank_math' ) || ! isset( rank_math()->manager ) || ! is_object( rank_math()->manager ) ) {
		return array();
	}

	$records = array();
	foreach ( rank_math()->manager->modules as $id => $module ) {
		if ( ! is_object( $module ) || ! method_exists( $module, 'get' ) ) {
			continue;
		}

		$records[] = array(
			'id'           => (string) $id,
			'title'        => (string) $module->get( 'title' ),
			'description'  => wp_strip_all_tags( (string) $module->get( 'desc' ) ),
			'settings_url' => (string) $module->get( 'settings' ),
			'active'       => method_exists( $module, 'is_active' ) ? (bool) $module->is_active() : false,
			'disabled'     => method_exists( $module, 'is_disabled' ) ? (bool) $module->is_disabled() : false,
			'hidden'       => method_exists( $module, 'is_hidden' ) ? (bool) $module->is_hidden() : false,
			'upgradeable'  => method_exists( $module, 'is_upgradeable' ) ? (bool) $module->is_upgradeable() : false,
			'pro'          => method_exists( $module, 'is_pro_module' ) ? (bool) $module->is_pro_module() : false,
		);
	}

	return $records;
}

/**
 * Get rewrite rules as an array.
 *
 * @return array<string,string>
 */
function mcp_rankmath_get_rewrite_rules_array(): array {
	global $wp_rewrite;

	if ( ! isset( $wp_rewrite ) || ! is_object( $wp_rewrite ) ) {
		return array();
	}

	$rules = $wp_rewrite->wp_rewrite_rules();
	return is_array( $rules ) ? $rules : array();
}

/**
 * Find a rewrite rule for a known endpoint or custom regex.
 *
 * @param string $endpoint Endpoint identifier.
 * @param string $custom_regex Custom regex for endpoint=custom.
 * @return array<string,mixed>
 */
function mcp_rankmath_get_rewrite_status( string $endpoint = 'llms.txt', string $custom_regex = '' ): array {
	$rules = mcp_rankmath_get_rewrite_rules_array();
	if ( empty( $rules ) ) {
		return array(
			'endpoint'            => $endpoint,
			'searched_regex'      => $custom_regex,
			'rule_present'        => false,
			'matched_regex'       => '',
			'rule_target'         => '',
			'permalink_structure' => (string) get_option( 'permalink_structure', '' ),
			'message'             => 'WordPress rewrite rules are unavailable.',
		);
	}

	$search_regex = '';
	$target_hint  = '';
	if ( 'llms.txt' === $endpoint ) {
		$search_regex = '^llms\.txt$';
		$target_hint  = 'llms_txt=1';
	} elseif ( 'sitemap_index.xml' === $endpoint ) {
		$search_regex = '^sitemap_index\.xml$';
		$target_hint  = 'sitemap=1';
	} elseif ( 'custom' === $endpoint ) {
		$search_regex = $custom_regex;
	}

	$matched_regex = '';
	$matched_target = '';

	if ( '' !== $search_regex && isset( $rules[ $search_regex ] ) ) {
		$matched_regex  = $search_regex;
		$matched_target = (string) $rules[ $search_regex ];
	}

	if ( '' === $matched_regex && '' !== $target_hint ) {
		foreach ( $rules as $regex => $target ) {
			if ( false !== strpos( (string) $target, $target_hint ) ) {
				$matched_regex  = (string) $regex;
				$matched_target = (string) $target;
				break;
			}
		}
	}

	return array(
		'endpoint'            => $endpoint,
		'searched_regex'      => $search_regex,
		'rule_present'        => '' !== $matched_regex,
		'matched_regex'       => $matched_regex,
		'rule_target'         => $matched_target,
		'permalink_structure' => (string) get_option( 'permalink_structure', '' ),
		'message'             => '' !== $matched_regex ? 'Rewrite rule found.' : 'Rewrite rule not found.',
	);
}

/**
 * Fetch a local URL for preview/status inspection.
 *
 * @param string $path URL path.
 * @param int    $max_lines Max lines to keep in preview.
 * @return array<string,mixed>
 */
function mcp_rankmath_fetch_local_preview( string $path, int $max_lines = 20 ): array {
	$url      = home_url( $path );
	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 15,
			'redirection' => 3,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array(
			'url'          => $url,
			'status_code'  => 0,
			'content_type' => '',
			'preview'      => '',
			'lines'        => array(),
			'message'      => $response->get_error_message(),
		);
	}

	$body         = (string) wp_remote_retrieve_body( $response );
	$preview_lines = preg_split( '/\r\n|\r|\n/', $body ) ?: array();
	$preview_lines = array_slice( $preview_lines, 0, max( 1, $max_lines ) );

	return array(
		'url'          => $url,
		'status_code'  => (int) wp_remote_retrieve_response_code( $response ),
		'content_type' => (string) wp_remote_retrieve_header( $response, 'content-type' ),
		'preview'      => implode( "\n", $preview_lines ),
		'lines'        => $preview_lines,
		'message'      => 'Fetched preview successfully.',
	);
}

/**
 * Build publisher/entity status from Rank Math settings.
 *
 * @return array<string,mixed>
 */
function mcp_rankmath_get_schema_status_data(): array {
	$titles   = mcp_rankmath_get_titles_settings();
	$type     = isset( $titles['knowledgegraph_type'] ) ? sanitize_key( (string) $titles['knowledgegraph_type'] ) : 'person';
	$name     = isset( $titles['knowledgegraph_name'] ) && '' !== (string) $titles['knowledgegraph_name'] ? (string) $titles['knowledgegraph_name'] : get_bloginfo( 'name' );
	$website  = isset( $titles['website_name'] ) && '' !== (string) $titles['website_name'] ? (string) $titles['website_name'] : $name;
	$profiles = mcp_rankmath_get_social_profiles_from_titles( $titles );

	return array(
		'configured_knowledgegraph_type' => $type,
		'effective_publisher_type'       => 'company' === $type ? 'Organization' : 'Person',
		'effective_publisher_id'         => home_url( 'company' === $type ? '/#organization' : '/#person' ),
		'publisher_name'                 => $name,
		'website_name'                   => $website,
		'publisher_url'                  => isset( $titles['url'] ) ? (string) $titles['url'] : '',
		'organization_description'       => isset( $titles['organization_description'] ) ? (string) $titles['organization_description'] : '',
		'logo_url'                       => isset( $titles['knowledgegraph_logo'] ) ? (string) $titles['knowledgegraph_logo'] : '',
		'logo_id'                        => isset( $titles['knowledgegraph_logo_id'] ) ? (int) $titles['knowledgegraph_logo_id'] : 0,
		'local_seo_enabled'              => ! empty( $titles['local_seo'] ),
		'email'                          => isset( $titles['email'] ) ? (string) $titles['email'] : '',
		'phone'                          => isset( $titles['phone'] ) ? (string) $titles['phone'] : '',
		'phone_numbers'                  => isset( $titles['phone_numbers'] ) && is_array( $titles['phone_numbers'] ) ? $titles['phone_numbers'] : array(),
		'address'                        => isset( $titles['local_address'] ) && is_array( $titles['local_address'] ) ? $titles['local_address'] : array(),
		'address_format'                 => isset( $titles['local_address_format'] ) ? (string) $titles['local_address_format'] : '',
		'social_profiles'                => $profiles,
		'social_facebook'                => isset( $titles['social_url_facebook'] ) ? (string) $titles['social_url_facebook'] : '',
		'twitter_handle'                 => isset( $titles['twitter_author_names'] ) ? ltrim( (string) $titles['twitter_author_names'], '@' ) : '',
	);
}

/**
 * Build llms status data from Rank Math settings and live preview.
 *
 * @param int $preview_lines Number of preview lines to fetch.
 * @return array<string,mixed>
 */
function mcp_rankmath_get_llms_status_data( int $preview_lines = 12 ): array {
	$general       = mcp_rankmath_get_general_settings();
	$branding      = mcp_rankmath_get_llms_branding();
	$rewrite       = mcp_rankmath_get_rewrite_status( 'llms.txt' );
	$live_preview  = mcp_rankmath_fetch_local_preview( '/llms.txt', $preview_lines );
	$post_types    = isset( $general['llms_post_types'] ) && is_array( $general['llms_post_types'] ) ? array_values( $general['llms_post_types'] ) : array();
	$taxonomies    = isset( $general['llms_taxonomies'] ) && is_array( $general['llms_taxonomies'] ) ? array_values( $general['llms_taxonomies'] ) : array();

	return array(
		'module_active'      => class_exists( '\\RankMath\\Helper' ) && \RankMath\Helper::is_module_active( 'llms-txt' ),
		'route_url'          => home_url( '/llms.txt' ),
		'rewrite'            => $rewrite,
		'post_types'         => $post_types,
		'taxonomies'         => $taxonomies,
		'limit'              => isset( $general['llms_limit'] ) ? (int) $general['llms_limit'] : 100,
		'extra_content'      => isset( $general['llms_extra_content'] ) ? (string) $general['llms_extra_content'] : '',
		'header_name'        => $branding['name'],
		'header_description' => $branding['description'],
		'effective_heading'  => $branding['name'] . ': ' . $branding['description'],
		'sitemap_active'     => class_exists( '\\RankMath\\Helper' ) && \RankMath\Helper::is_module_active( 'sitemap' ),
		'live_preview'       => $live_preview,
	);
}

/**
 * Get enabled sitemap object types grouped by post type and taxonomy.
 *
 * @return array<string,array<int,string>>
 */
function mcp_rankmath_get_sitemap_enabled_items(): array {
	$sitemap = mcp_rankmath_get_sitemap_settings();
	$post_types = array();
	$taxonomies = array();

	foreach ( $sitemap as $key => $value ) {
		if ( 'on' !== $value && true !== $value ) {
			continue;
		}

		if ( 0 === strpos( (string) $key, 'pt_' ) && str_ends_with( (string) $key, '_sitemap' ) ) {
			$post_types[] = substr( (string) $key, 3, -8 );
			continue;
		}

		if ( 0 === strpos( (string) $key, 'tax_' ) && str_ends_with( (string) $key, '_sitemap' ) ) {
			$taxonomies[] = substr( (string) $key, 4, -8 );
		}
	}

	sort( $post_types );
	sort( $taxonomies );

	return array(
		'post_types' => $post_types,
		'taxonomies' => $taxonomies,
	);
}

/**
 * Get the current rewrite rules and llms.txt rule status.
 *
 * @return array<string,mixed>
 */
function mcp_rankmath_get_llms_rewrite_status(): array {
	global $wp_rewrite;

	if ( ! isset( $wp_rewrite ) || ! is_object( $wp_rewrite ) ) {
		return array(
			'rule_present' => false,
			'rule_target'  => '',
			'message'      => 'WordPress rewrite subsystem is not available.',
		);
	}

	$rules       = $wp_rewrite->wp_rewrite_rules();
	$rule_key    = '^llms\.txt$';
	$rule_target = '';

	if ( is_array( $rules ) && isset( $rules[ $rule_key ] ) ) {
		$rule_target = (string) $rules[ $rule_key ];
	}

	return array(
		'rule_present' => '' !== $rule_target,
		'rule_target'  => $rule_target,
		'message'      => '' !== $rule_target ? 'llms.txt rewrite rule is registered.' : 'llms.txt rewrite rule is missing from stored rewrite rules.',
	);
}

/**
 * Check if a table exists (cached per request).
 *
 * @param string $table Table name.
 * @return bool
 */
function mcp_rankmath_table_exists( string $table ): bool {
	static $cache = array();
	if ( isset( $cache[ $table ] ) ) {
		return $cache[ $table ];
	}

	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema check.
	$exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table );
	$cache[ $table ] = $exists;

	return $exists;
}

/**
 * Fetch a post and verify permissions.
 *
 * @param int    $post_id Post ID.
 * @param string $action  Action label for error messages.
 * @return array Result with success flag and post or message.
 */
function mcp_rankmath_get_post_or_error( int $post_id, string $action ): array {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return array(
			'success' => false,
			'message' => 'Post or page not found with ID: ' . $post_id,
		);
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return array(
			'success' => false,
			'message' => 'You do not have permission to ' . $action . ' this post.',
		);
	}

	return array(
		'success' => true,
		'post'    => $post,
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
	// RANK MATH - List Options
	// =========================================================================
	wp_register_ability(
		'rankmath/list-options',
		array(
			'label'               => 'List Rank Math Options',
			'description'         => 'List Rank Math option names stored in wp_options.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'limit'  => array( 'type' => 'integer', 'default' => 200 ),
					'offset' => array( 'type' => 'integer', 'default' => 0 ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'options' => array( 'type' => 'array' ),
					'count'   => array( 'type' => 'integer' ),
				),
			),
				'execute_callback'    => function ( array $input = array() ): array {
				global $wpdb;
				$limit  = min( 500, max( 1, (int) ( $input['limit'] ?? 200 ) ) );
				$offset = max( 0, (int) ( $input['offset'] ?? 0 ) );

				$like_patterns = mcp_rankmath_allowed_option_like_patterns();
				$legacy_like   = $like_patterns[0] ?? 'rank_math_%';
				$modern_like   = $like_patterns[1] ?? 'rank-math-%';

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE %s OR option_name LIKE %s ORDER BY option_name ASC LIMIT %d OFFSET %d',
						$legacy_like,
						$modern_like,
						$limit,
						$offset
					),
					ARRAY_A
				);

				$options = array_map( static function ( $row ) {
					return $row['option_name'];
				}, $rows ?: array() );

				return array(
					'success' => true,
					'options' => $options,
					'count'   => count( $options ),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - Get Options
	// =========================================================================
	wp_register_ability(
		'rankmath/get-options',
		array(
			'label'               => 'Get Rank Math Options',
			'description'         => 'Get Rank Math option values by name.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'options' ),
				'properties'           => array(
					'options' => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'string' ),
						'description' => 'Option names to fetch.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'options' => array( 'type' => 'object' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input = array() ): array {
				$names = isset( $input['options'] ) && is_array( $input['options'] ) ? $input['options'] : array();
				if ( empty( $names ) ) {
					return array( 'success' => false, 'message' => 'No option names provided.' );
				}

				$values = array();
				foreach ( $names as $name ) {
					$name = sanitize_text_field( $name );
					if ( ! mcp_rankmath_is_allowed_option_name( $name ) ) {
						continue;
					}
					$values[ $name ] = get_option( $name, null );
				}

					return array( 'success' => true, 'options' => $values );
				},
				'permission_callback' => function (): bool {
					return current_user_can( 'manage_options' );
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
	// RANK MATH - Update Options
	// =========================================================================
	wp_register_ability(
		'rankmath/update-options',
		array(
			'label'               => 'Update Rank Math Options',
			'description'         => 'Update Rank Math option values by name.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'options' ),
				'properties'           => array(
					'options' => array(
						'type'        => 'object',
						'description' => 'Map of option names to values.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'updated' => array( 'type' => 'array' ),
					'message' => array( 'type' => 'string' ),
				),
			),
				'execute_callback'    => function ( array $input = array() ): array {
				$options = isset( $input['options'] ) && is_array( $input['options'] ) ? $input['options'] : array();
				if ( empty( $options ) ) {
					return array( 'success' => false, 'message' => 'No options provided.' );
				}

				$updated = array();
				foreach ( $options as $name => $value ) {
					$name = sanitize_text_field( $name );
					if ( ! mcp_rankmath_is_allowed_option_name( $name ) ) {
						continue;
					}
					update_option( $name, $value );
					$updated[] = $name;
				}

					return array(
						'success' => true,
						'updated' => $updated,
						'message' => 'Options updated.',
					);
				},
				'permission_callback' => function (): bool {
					return current_user_can( 'manage_options' );
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
	// RANK MATH - Refresh LLMS Route
	// =========================================================================
	wp_register_ability(
		'rankmath/refresh-llms-route',
		array(
			'label'               => 'Refresh Rank Math llms.txt Route',
			'description'         => 'Checks whether the Rank Math llms.txt rewrite rule is present and flushes rewrite rules when needed.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'force_flush' => array(
						'type'        => 'boolean',
						'default'     => false,
						'description' => 'Flush rewrite rules even if the llms.txt rule already appears to exist.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'            => array( 'type' => 'boolean' ),
					'module_active'      => array( 'type' => 'boolean' ),
					'permalink_structure' => array( 'type' => 'string' ),
					'before'             => array( 'type' => 'object' ),
					'after'              => array( 'type' => 'object' ),
					'flushed'            => array( 'type' => 'boolean' ),
					'message'            => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input = array() ): array {
				if ( ! mcp_rankmath_is_active() ) {
					return array(
						'success' => false,
						'message' => 'Rank Math SEO plugin is not active.',
					);
				}

				if ( ! function_exists( 'flush_rewrite_rules' ) ) {
					return array(
						'success' => false,
						'message' => 'WordPress rewrite functions are unavailable.',
					);
				}

				$force_flush         = ! empty( $input['force_flush'] );
				$module_active       = class_exists( '\\RankMath\\Helper' ) && \RankMath\Helper::is_module_active( 'llms-txt' );
				$permalink_structure = (string) get_option( 'permalink_structure', '' );
				$before              = mcp_rankmath_get_llms_rewrite_status();
				$flushed             = false;

				if ( ! $module_active ) {
					return array(
						'success'             => false,
						'module_active'       => false,
						'permalink_structure' => $permalink_structure,
						'before'              => $before,
						'after'               => $before,
						'flushed'             => false,
						'message'             => 'Rank Math llms-txt module is not active.',
					);
				}

				if ( $force_flush || empty( $before['rule_present'] ) ) {
					flush_rewrite_rules( false );
					$flushed = true;
				}

				$after = mcp_rankmath_get_llms_rewrite_status();

				return array(
					'success'             => ! empty( $after['rule_present'] ),
					'module_active'       => true,
					'permalink_structure' => $permalink_structure,
					'before'              => $before,
					'after'               => $after,
					'flushed'             => $flushed,
					'message'             => ! empty( $after['rule_present'] ) ? 'llms.txt rewrite rule is available.' : 'llms.txt rewrite rule is still missing after refresh.',
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
		)
	);

	// =========================================================================
	// RANK MATH - Get Schema Status
	// =========================================================================
	wp_register_ability(
		'rankmath/get-schema-status',
		array(
			'label'               => 'Get Rank Math Schema Status',
			'description'         => 'Return effective global publisher/schema settings including publisher type, website name, logo, social profiles, and local SEO contact fields.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => new stdClass(),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'status'  => array( 'type' => 'object' ),
				),
			),
			'execute_callback'    => function (): array {
				if ( ! mcp_rankmath_is_active() ) {
					return array( 'success' => false, 'message' => 'Rank Math SEO plugin is not active.' );
				}

				return array(
					'success' => true,
					'status'  => mcp_rankmath_get_schema_status_data(),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - List Modules
	// =========================================================================
	wp_register_ability(
		'rankmath/list-modules',
		array(
			'label'               => 'List Rank Math Modules',
			'description'         => 'List Rank Math modules with active, disabled, and upgrade status.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => new stdClass(),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'modules' => array( 'type' => 'array' ),
					'count'   => array( 'type' => 'integer' ),
				),
			),
			'execute_callback'    => function (): array {
				if ( ! mcp_rankmath_is_active() ) {
					return array( 'success' => false, 'message' => 'Rank Math SEO plugin is not active.' );
				}

				$modules = mcp_rankmath_get_module_records();
				return array(
					'success' => true,
					'modules' => $modules,
					'count'   => count( $modules ),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - Update Modules
	// =========================================================================
	wp_register_ability(
		'rankmath/update-modules',
		array(
			'label'               => 'Update Rank Math Modules',
			'description'         => 'Enable or disable Rank Math modules by slug.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'enable'  => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'disable' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'updated' => array( 'type' => 'object' ),
					'modules' => array( 'type' => 'array' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input = array() ): array {
				if ( ! mcp_rankmath_is_active() || ! class_exists( '\\RankMath\\Helper' ) ) {
					return array( 'success' => false, 'message' => 'Rank Math SEO plugin is not active.' );
				}

				$enable  = isset( $input['enable'] ) && is_array( $input['enable'] ) ? array_map( 'sanitize_key', $input['enable'] ) : array();
				$disable = isset( $input['disable'] ) && is_array( $input['disable'] ) ? array_map( 'sanitize_key', $input['disable'] ) : array();

				if ( empty( $enable ) && empty( $disable ) ) {
					return array( 'success' => false, 'message' => 'No module changes provided.' );
				}

				$changes = array();
				foreach ( $enable as $module ) {
					if ( '' !== $module ) {
						$changes[ $module ] = 'on';
					}
				}
				foreach ( $disable as $module ) {
					if ( '' !== $module ) {
						$changes[ $module ] = 'off';
					}
				}

				\RankMath\Helper::update_modules( $changes );

				$route_modules = array_intersect( array_keys( $changes ), array( 'llms-txt', 'sitemap' ) );
				if ( ! empty( $route_modules ) && function_exists( 'flush_rewrite_rules' ) ) {
					flush_rewrite_rules( false );
				}

				if ( method_exists( '\\RankMath\\Helper', 'clear_cache' ) ) {
					\RankMath\Helper::clear_cache( 'mcp-rankmath-update-modules' );
				}

				return array(
					'success' => true,
					'updated' => array(
						'enable'  => array_values( $enable ),
						'disable' => array_values( $disable ),
					),
					'modules' => mcp_rankmath_get_module_records(),
					'message' => 'Rank Math modules updated.',
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
		)
	);

	// =========================================================================
	// RANK MATH - Get Rewrite Status
	// =========================================================================
	wp_register_ability(
		'rankmath/get-rewrite-status',
		array(
			'label'               => 'Get Rank Math Rewrite Status',
			'description'         => 'Inspect stored rewrite rules for llms.txt, sitemap_index.xml, or a custom regex.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'endpoint'     => array(
						'type'        => 'string',
						'enum'        => array( 'llms.txt', 'sitemap_index.xml', 'custom' ),
						'default'     => 'llms.txt',
					),
					'custom_regex' => array(
						'type'        => 'string',
						'description' => 'Used only when endpoint=custom.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'status'  => array( 'type' => 'object' ),
				),
			),
			'execute_callback'    => function ( array $input = array() ): array {
				$endpoint     = isset( $input['endpoint'] ) ? sanitize_text_field( (string) $input['endpoint'] ) : 'llms.txt';
				$custom_regex = isset( $input['custom_regex'] ) ? sanitize_text_field( (string) $input['custom_regex'] ) : '';

				return array(
					'success' => true,
					'status'  => mcp_rankmath_get_rewrite_status( $endpoint, $custom_regex ),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - Get LLMS Status
	// =========================================================================
	wp_register_ability(
		'rankmath/get-llms-status',
		array(
			'label'               => 'Get Rank Math llms.txt Status',
			'description'         => 'Return Rank Math llms.txt module state, settings, rewrite status, and a live preview.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'preview_lines' => array(
						'type'    => 'integer',
						'default' => 12,
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'status'  => array( 'type' => 'object' ),
				),
			),
			'execute_callback'    => function ( array $input = array() ): array {
				if ( ! mcp_rankmath_is_active() ) {
					return array( 'success' => false, 'message' => 'Rank Math SEO plugin is not active.' );
				}

				$preview_lines = isset( $input['preview_lines'] ) ? max( 1, min( 50, (int) $input['preview_lines'] ) ) : 12;

				return array(
					'success' => true,
					'status'  => mcp_rankmath_get_llms_status_data( $preview_lines ),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - Preview LLMS
	// =========================================================================
	wp_register_ability(
		'rankmath/preview-llms',
		array(
			'label'               => 'Preview Rank Math llms.txt',
			'description'         => 'Fetch the live llms.txt output and return the first lines for inspection.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'max_lines' => array(
						'type'    => 'integer',
						'default' => 40,
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'preview' => array( 'type' => 'object' ),
				),
			),
			'execute_callback'    => function ( array $input = array() ): array {
				$max_lines = isset( $input['max_lines'] ) ? max( 1, min( 200, (int) $input['max_lines'] ) ) : 40;

				return array(
					'success' => true,
					'preview' => mcp_rankmath_fetch_local_preview( '/llms.txt', $max_lines ),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - Update Publisher Profile
	// =========================================================================
	wp_register_ability(
		'rankmath/update-publisher-profile',
		array(
			'label'               => 'Update Rank Math Publisher Profile',
			'description'         => 'Safely update the global publisher/entity fields used by Rank Math schema and local SEO.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'knowledgegraph_type'    => array( 'type' => 'string', 'enum' => array( 'company', 'person' ) ),
					'knowledgegraph_name'    => array( 'type' => 'string' ),
					'website_name'           => array( 'type' => 'string' ),
					'organization_description' => array( 'type' => 'string' ),
					'url'                    => array( 'type' => 'string' ),
					'knowledgegraph_logo'    => array( 'type' => 'string' ),
					'knowledgegraph_logo_id' => array( 'type' => 'integer' ),
					'email'                  => array( 'type' => 'string' ),
					'phone'                  => array( 'type' => 'string' ),
					'local_address'          => array( 'type' => 'object' ),
					'local_address_format'   => array( 'type' => 'string' ),
					'local_seo'              => array( 'type' => 'boolean' ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'updated' => array( 'type' => 'array' ),
					'status'  => array( 'type' => 'object' ),
				),
			),
			'execute_callback'    => function ( array $input = array() ): array {
				$titles  = mcp_rankmath_get_titles_settings();
				$updated = array();

				$scalar_fields = array(
					'knowledgegraph_type',
					'knowledgegraph_name',
					'website_name',
					'organization_description',
					'local_address_format',
					'phone',
				);
				foreach ( $scalar_fields as $field ) {
					if ( array_key_exists( $field, $input ) ) {
						$titles[ $field ] = sanitize_text_field( (string) $input[ $field ] );
						$updated[]        = $field;
					}
				}

				if ( array_key_exists( 'url', $input ) ) {
					$titles['url'] = esc_url_raw( (string) $input['url'] );
					$updated[]     = 'url';
				}

				if ( array_key_exists( 'knowledgegraph_logo', $input ) ) {
					$titles['knowledgegraph_logo'] = esc_url_raw( (string) $input['knowledgegraph_logo'] );
					$updated[]                     = 'knowledgegraph_logo';
				}

				if ( array_key_exists( 'knowledgegraph_logo_id', $input ) ) {
					$titles['knowledgegraph_logo_id'] = absint( $input['knowledgegraph_logo_id'] );
					$updated[]                        = 'knowledgegraph_logo_id';
				}

				if ( array_key_exists( 'email', $input ) ) {
					$titles['email'] = sanitize_email( (string) $input['email'] );
					$updated[]       = 'email';
				}

				if ( array_key_exists( 'local_seo', $input ) ) {
					$titles['local_seo'] = ! empty( $input['local_seo'] ) ? 'on' : 'off';
					$updated[]           = 'local_seo';
				}

				if ( array_key_exists( 'local_address', $input ) && is_array( $input['local_address'] ) ) {
					$address = array();
					foreach ( $input['local_address'] as $key => $value ) {
						$address[ sanitize_key( (string) $key ) ] = sanitize_text_field( (string) $value );
					}
					$titles['local_address'] = $address;
					$updated[]               = 'local_address';
				}

				if ( empty( $updated ) ) {
					return array( 'success' => false, 'message' => 'No publisher profile fields provided.' );
				}

				update_option( 'rank-math-options-titles', $titles );

				return array(
					'success' => true,
					'updated' => $updated,
					'status'  => mcp_rankmath_get_schema_status_data(),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - Get Social Profiles
	// =========================================================================
	wp_register_ability(
		'rankmath/get-social-profiles',
		array(
			'label'               => 'Get Rank Math Social Profiles',
			'description'         => 'Return the global social profile fields that feed Rank Math sameAs output.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => new stdClass(),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'profiles' => array( 'type' => 'object' ),
				),
			),
			'execute_callback'    => function (): array {
				$titles = mcp_rankmath_get_titles_settings();
				return array(
					'success'  => true,
					'profiles' => array(
						'facebook_url'        => isset( $titles['social_url_facebook'] ) ? (string) $titles['social_url_facebook'] : '',
						'twitter_handle'      => isset( $titles['twitter_author_names'] ) ? ltrim( (string) $titles['twitter_author_names'], '@' ) : '',
						'additional_profiles' => isset( $titles['social_additional_profiles'] ) ? preg_split( '/\r\n|\r|\n/', (string) $titles['social_additional_profiles'] ) : array(),
						'effective_same_as'   => mcp_rankmath_get_social_profiles_from_titles( $titles ),
					),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - Update Social Profiles
	// =========================================================================
	wp_register_ability(
		'rankmath/update-social-profiles',
		array(
			'label'               => 'Update Rank Math Social Profiles',
			'description'         => 'Update the global social profile fields that feed Rank Math sameAs output.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'facebook_url'        => array( 'type' => 'string' ),
					'twitter_handle'      => array( 'type' => 'string' ),
					'additional_profiles' => array(
						'oneOf' => array(
							array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							array( 'type' => 'string' ),
						),
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'updated' => array( 'type' => 'array' ),
					'profiles' => array( 'type' => 'object' ),
				),
			),
			'execute_callback'    => function ( array $input = array() ): array {
				$titles  = mcp_rankmath_get_titles_settings();
				$updated = array();

				if ( array_key_exists( 'facebook_url', $input ) ) {
					$titles['social_url_facebook'] = esc_url_raw( (string) $input['facebook_url'] );
					$updated[]                     = 'facebook_url';
				}

				if ( array_key_exists( 'twitter_handle', $input ) ) {
					$titles['twitter_author_names'] = ltrim( sanitize_text_field( (string) $input['twitter_handle'] ), '@' );
					$updated[]                      = 'twitter_handle';
				}

				if ( array_key_exists( 'additional_profiles', $input ) ) {
					$titles['social_additional_profiles'] = mcp_rankmath_normalize_additional_profiles( $input['additional_profiles'] );
					$updated[]                            = 'additional_profiles';
				}

				if ( empty( $updated ) ) {
					return array( 'success' => false, 'message' => 'No social profile changes provided.' );
				}

				update_option( 'rank-math-options-titles', $titles );

				return array(
					'success' => true,
					'updated' => $updated,
					'profiles' => array(
						'facebook_url'        => isset( $titles['social_url_facebook'] ) ? (string) $titles['social_url_facebook'] : '',
						'twitter_handle'      => isset( $titles['twitter_author_names'] ) ? ltrim( (string) $titles['twitter_author_names'], '@' ) : '',
						'additional_profiles' => isset( $titles['social_additional_profiles'] ) ? preg_split( '/\r\n|\r|\n/', (string) $titles['social_additional_profiles'] ) : array(),
						'effective_same_as'   => mcp_rankmath_get_social_profiles_from_titles( $titles ),
					),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - Get Sitemap Status
	// =========================================================================
	wp_register_ability(
		'rankmath/get-sitemap-status',
		array(
			'label'               => 'Get Rank Math Sitemap Status',
			'description'         => 'Return sitemap module state, enabled object types, rewrite status, and a live sitemap index check.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => new stdClass(),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'status'  => array( 'type' => 'object' ),
				),
			),
			'execute_callback'    => function (): array {
				if ( ! mcp_rankmath_is_active() ) {
					return array( 'success' => false, 'message' => 'Rank Math SEO plugin is not active.' );
				}

				$sitemap         = mcp_rankmath_get_sitemap_settings();
				$enabled_objects = mcp_rankmath_get_sitemap_enabled_items();
				$preview         = mcp_rankmath_fetch_local_preview( '/sitemap_index.xml', 8 );

				return array(
					'success' => true,
					'status'  => array(
						'module_active'     => class_exists( '\\RankMath\\Helper' ) && \RankMath\Helper::is_module_active( 'sitemap' ),
						'route_url'         => home_url( '/sitemap_index.xml' ),
						'rewrite'           => mcp_rankmath_get_rewrite_status( 'sitemap_index.xml' ),
						'include_images'    => ! empty( $sitemap['include_images'] ),
						'links_per_sitemap' => isset( $sitemap['links_per_sitemap'] ) ? (int) $sitemap['links_per_sitemap'] : 0,
						'post_types'        => $enabled_objects['post_types'],
						'taxonomies'        => $enabled_objects['taxonomies'],
						'live_preview'      => $preview,
					),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
				$result  = mcp_rankmath_get_post_or_error( $post_id, 'access' );
				if ( empty( $result['success'] ) ) {
					return $result;
				}
				$post = $result['post'];

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
			'description'         => 'Update Rank Math SEO meta data for a post or page. Can update title, description, focus keyword, robots, canonical URL, and content flags. Also accepts title/description/keyword aliases for convenience.',
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
					'title'           => array(
						'type'        => 'string',
						'description' => 'Alias for seo_title.',
					),
					'seo_description' => array(
						'type'        => 'string',
						'description' => 'Meta description (recommended: 150-160 characters).',
					),
					'description'     => array(
						'type'        => 'string',
						'description' => 'Alias for seo_description.',
					),
					'focus_keyword'   => array(
						'type'        => 'string',
						'description' => 'Focus keyword(s). Separate multiple with commas.',
					),
					'keyword'         => array(
						'type'        => 'string',
						'description' => 'Alias for focus_keyword.',
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
				$result  = mcp_rankmath_get_post_or_error( $post_id, 'edit' );
				if ( empty( $result['success'] ) ) {
					return $result;
				}

				$updated         = array();
				$seo_title_input = $input['seo_title'] ?? $input['title'] ?? null;
				$seo_desc_input  = $input['seo_description'] ?? $input['description'] ?? null;
				$focus_input     = $input['focus_keyword'] ?? $input['keyword'] ?? null;

				// Update SEO title.
				if ( null !== $seo_title_input ) {
					update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( $seo_title_input ) );
					$updated[] = 'seo_title';
				}

				// Update SEO description.
				if ( null !== $seo_desc_input ) {
					update_post_meta( $post_id, 'rank_math_description', sanitize_textarea_field( $seo_desc_input ) );
					$updated[] = 'seo_description';
				}

				// Update focus keyword.
				if ( null !== $focus_input ) {
					update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( $focus_input ) );
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

	// =========================================================================
	// RANK MATH - List 404 Logs
	// =========================================================================
	wp_register_ability(
		'rankmath/list-404-logs',
		array(
			'label'               => 'List Rank Math 404 Logs',
			'description'         => 'List recent Rank Math 404 log entries (read-only).',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'per_page' => array( 'type' => 'integer', 'default' => 50 ),
					'page'     => array( 'type' => 'integer', 'default' => 1 ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'logs'    => array( 'type' => 'array' ),
					'total'   => array( 'type' => 'integer' ),
				),
			),
				'execute_callback'    => function ( array $input = array() ): array {
					global $wpdb;
					$table = $wpdb->prefix . 'rank_math_404_logs';

				if ( ! mcp_rankmath_table_exists( $table ) ) {
					return array( 'success' => false, 'message' => 'Rank Math 404 log table not found.' );
				}

				$per_page = min( 200, max( 1, (int) ( $input['per_page'] ?? 50 ) ) );
				$page     = max( 1, (int) ( $input['page'] ?? 1 ) );
				$offset   = ( $page - 1 ) * $per_page;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$rows = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT * FROM `' . esc_sql( $table ) . '` ORDER BY id DESC LIMIT %d OFFSET %d',
							$per_page,
							$offset
						),
						ARRAY_A
					);
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '`' );

					return array(
						'success' => true,
						'logs'    => $rows,
						'total'   => $total,
					);
				},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - Delete 404 Logs
	// =========================================================================
	wp_register_ability(
		'rankmath/delete-404-logs',
		array(
			'label'               => 'Delete Rank Math 404 Logs',
			'description'         => 'Delete specific Rank Math 404 log entries by ID.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'ids' ),
				'properties'           => array(
					'ids' => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'integer' ),
						'description' => 'Array of 404 log IDs to delete.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'deleted' => array( 'type' => 'integer' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				global $wpdb;
				$table = $wpdb->prefix . 'rank_math_404_logs';

				if ( ! mcp_rankmath_table_exists( $table ) ) {
					return array( 'success' => false, 'message' => 'Rank Math 404 log table not found.' );
				}

				$ids = array_filter( array_map( 'absint', $input['ids'] ?? array() ) );
				if ( empty( $ids ) ) {
					return array( 'success' => false, 'message' => 'No valid IDs provided.' );
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted = $wpdb->query(
					$wpdb->prepare(
						'DELETE FROM `' . esc_sql( $table ) . '` WHERE id IN (' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')',
						$ids
					)
				);

				return array(
					'success' => true,
					'deleted' => (int) $deleted,
					'message' => 'Deleted ' . (int) $deleted . ' log(s).',
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => false,
				),
			),
		)
	);

	// =========================================================================
	// RANK MATH - Clear 404 Logs
	// =========================================================================
	wp_register_ability(
		'rankmath/clear-404-logs',
		array(
			'label'               => 'Clear Rank Math 404 Logs',
			'description'         => 'Deletes all Rank Math 404 log entries.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'confirm' ),
				'properties'           => array(
					'confirm' => array(
						'type'        => 'boolean',
						'description' => 'Set true to confirm clearing all Rank Math 404 logs.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				global $wpdb;
				$table = $wpdb->prefix . 'rank_math_404_logs';

				if ( ! mcp_rankmath_table_exists( $table ) ) {
					return array( 'success' => false, 'message' => 'Rank Math 404 log table not found.' );
				}

				if ( empty( $input['confirm'] ) ) {
					return array( 'success' => false, 'message' => 'Confirmation required to clear 404 logs.' );
				}

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->query( 'DELETE FROM `' . esc_sql( $table ) . '`' );

				return array(
					'success' => true,
					'message' => 'Cleared Rank Math 404 logs.',
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => false,
				),
			),
		)
	);

	// =========================================================================
	// RANK MATH - List Redirections
	// =========================================================================
	wp_register_ability(
		'rankmath/list-redirections',
		array(
			'label'               => 'List Rank Math Redirections',
			'description'         => 'List Rank Math redirections (read-only).',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'per_page' => array( 'type' => 'integer', 'default' => 50 ),
					'page'     => array( 'type' => 'integer', 'default' => 1 ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'redirections' => array( 'type' => 'array' ),
					'total'       => array( 'type' => 'integer' ),
				),
			),
				'execute_callback'    => function ( array $input = array() ): array {
					global $wpdb;
					$table = $wpdb->prefix . 'rank_math_redirections';

				if ( ! mcp_rankmath_table_exists( $table ) ) {
					return array( 'success' => false, 'message' => 'Rank Math redirections table not found.' );
				}

				$per_page = min( 200, max( 1, (int) ( $input['per_page'] ?? 50 ) ) );
				$page     = max( 1, (int) ( $input['page'] ?? 1 ) );
				$offset   = ( $page - 1 ) * $per_page;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$rows = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT * FROM `' . esc_sql( $table ) . '` ORDER BY id DESC LIMIT %d OFFSET %d',
							$per_page,
							$offset
						),
						ARRAY_A
					);
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '`' );

					return array(
						'success'      => true,
						'redirections' => $rows,
						'total'        => $total,
					);
				},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
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
	// RANK MATH - Create Redirection
	// =========================================================================
	wp_register_ability(
		'rankmath/create-redirection',
		array(
			'label'               => 'Create Rank Math Redirection',
			'description'         => 'Create a Rank Math redirection with one or more sources.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'sources' ),
				'properties'           => array(
					'sources' => array(
						'type'        => 'array',
						'description' => 'Array of sources with pattern and comparison.',
						'items'       => array(
							'type'                 => 'object',
							'properties'           => array(
								'pattern'     => array( 'type' => 'string' ),
								'comparison'  => array(
									'type'    => 'string',
									'enum'    => mcp_rankmath_allowed_redirection_comparisons(),
									'default' => 'exact',
								),
								'ignore_case' => array( 'type' => 'boolean' ),
							),
							'required'             => array( 'pattern' ),
							'additionalProperties' => false,
						),
					),
					'destination' => array(
						'type'        => 'string',
						'description' => 'Target URL (relative or absolute). Optional for 410/451.',
					),
					'header_code' => array(
						'type'        => 'integer',
						'default'     => 301,
						'description' => 'HTTP status code (301, 302, 307, 308, 410, 451).',
					),
					'status'      => array(
						'type'        => 'string',
						'default'     => 'active',
						'description' => 'Status: active or inactive.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'id'      => array( 'type' => 'integer' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				if ( ! mcp_rankmath_is_active() ) {
					return array(
						'success' => false,
						'message' => 'Rank Math SEO plugin is not active.',
					);
				}

				if ( ! class_exists( 'RankMath\\Redirections\\Redirection' ) ) {
					return array(
						'success' => false,
						'message' => 'Rank Math redirections module is not available.',
					);
				}

				$header_code = isset( $input['header_code'] ) ? (int) $input['header_code'] : 301;
				if ( ! in_array( $header_code, mcp_rankmath_allowed_redirection_headers(), true ) ) {
					return array(
						'success' => false,
						'message' => 'Invalid header_code provided.',
					);
				}

				$status = isset( $input['status'] ) ? $input['status'] : 'active';
				if ( ! in_array( $status, mcp_rankmath_allowed_redirection_statuses(), true ) ) {
					return array(
						'success' => false,
						'message' => 'Invalid status provided.',
					);
				}

				$destination = isset( $input['destination'] ) ? (string) $input['destination'] : '';
				if ( empty( $destination ) && ! in_array( $header_code, array( 410, 451 ), true ) ) {
					return array(
						'success' => false,
						'message' => 'Destination is required for this header_code.',
					);
				}

				$sources_input = $input['sources'] ?? array();
				if ( empty( $sources_input ) || ! is_array( $sources_input ) ) {
					return array(
						'success' => false,
						'message' => 'At least one source is required.',
					);
				}

				$sources = array();
				foreach ( $sources_input as $source ) {
					if ( ! is_array( $source ) ) {
						continue;
					}
					$pattern = isset( $source['pattern'] ) ? trim( (string) $source['pattern'] ) : '';
					if ( '' === $pattern ) {
						continue;
					}
					$comparison = isset( $source['comparison'] ) ? $source['comparison'] : 'exact';
					if ( ! in_array( $comparison, mcp_rankmath_allowed_redirection_comparisons(), true ) ) {
						return array(
							'success' => false,
							'message' => 'Invalid comparison type: ' . $comparison,
						);
					}
					$sources[] = array(
						'pattern'    => $pattern,
						'comparison' => $comparison,
						'ignore'     => ! empty( $source['ignore_case'] ) ? 'case' : '',
					);
				}

				if ( empty( $sources ) ) {
					return array(
						'success' => false,
						'message' => 'No valid sources provided.',
					);
				}

				$redirection = \RankMath\Redirections\Redirection::from(
					array(
						'sources'     => $sources,
						'url_to'      => $destination,
						'header_code' => $header_code,
						'status'      => $status,
					)
				);

				if ( method_exists( $redirection, 'is_infinite_loop' ) && $redirection->is_infinite_loop() ) {
					return array(
						'success' => false,
						'message' => 'Redirection would create an infinite loop.',
					);
				}

				$redirection_id = $redirection->save();
				if ( empty( $redirection_id ) ) {
					return array(
						'success' => false,
						'message' => 'Failed to create redirection.',
					);
				}

				return array(
					'success' => true,
					'id'      => (int) $redirection_id,
					'message' => 'Redirection created.',
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
		)
	);

	// =========================================================================
	// RANK MATH - Delete Redirections
	// =========================================================================
	wp_register_ability(
		'rankmath/delete-redirections',
		array(
			'label'               => 'Delete Rank Math Redirections',
			'description'         => 'Delete Rank Math redirections by ID.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'ids' ),
				'properties'           => array(
					'ids' => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'integer' ),
						'description' => 'Array of redirection IDs to delete.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'deleted' => array( 'type' => 'integer' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				if ( ! mcp_rankmath_is_active() ) {
					return array(
						'success' => false,
						'message' => 'Rank Math SEO plugin is not active.',
					);
				}

				if ( ! class_exists( 'RankMath\\Redirections\\DB' ) ) {
					return array(
						'success' => false,
						'message' => 'Rank Math redirections module is not available.',
					);
				}

				$ids = array_filter( array_map( 'absint', $input['ids'] ?? array() ) );
				if ( empty( $ids ) ) {
					return array(
						'success' => false,
						'message' => 'No valid IDs provided.',
					);
				}

				$deleted = \RankMath\Redirections\DB::delete( $ids );

				return array(
					'success' => true,
					'deleted' => (int) $deleted,
					'message' => 'Deleted ' . (int) $deleted . ' redirection(s).',
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => false,
				),
			),
		)
	);
}
add_action( 'wp_abilities_api_init', 'mcp_register_rankmath_abilities' );
