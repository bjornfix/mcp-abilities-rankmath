<?php
/**
 * Plugin Name: MCP Abilities - Rank Math
 * Plugin URI: https://github.com/bjornfix/mcp-abilities-rankmath
 * Description: Rank Math SEO abilities for MCP. Get and update meta descriptions, titles, focus keywords, and other SEO settings.
 * Version: 1.1.11
 * Author: basicus
 * Author URI: https://profiles.wordpress.org/basicus/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.9
 * Tested up to: 7.0
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
		'rank_math_seo_score',
		'rank_math_primary_category',
		'rank_math_pillar_content',
		'rank_math_cornerstone_content',
	);
}

/**
 * Get the stored Rank Math SEO score for a post.
 *
 * Rank Math stores the dashboard score in post meta when a score has been
 * calculated. Missing or non-numeric values are returned as null so agents can
 * distinguish "not scored yet" from a real zero score.
 *
 * @param int $post_id Post ID.
 * @return int|null
 */
function mcp_rankmath_get_seo_score( int $post_id ): ?int {
	$score = get_post_meta( $post_id, 'rank_math_seo_score', true );
	if ( '' === $score || null === $score || ! is_numeric( $score ) ) {
		return null;
	}

	return max( 0, min( 100, (int) $score ) );
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
 * Normalize a redirection target/source to a root-relative URL path.
 *
 * @param string $url URL or path.
 * @return string
 */
function mcp_rankmath_normalized_redirection_path( string $url ): string {
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $path ) || '' === $path ) {
		return '';
	}

	return '/' . trim( $path, '/' ) . '/';
}

/**
 * Find exact sources that would redirect a path back to itself.
 *
 * @param array<int,array<string,string>> $sources Sources accepted by Rank Math.
 * @param string                          $destination Destination URL/path.
 * @return array<int,array<string,string>>
 */
function mcp_rankmath_self_redirect_sources( array $sources, string $destination ): array {
	$destination_path = mcp_rankmath_normalized_redirection_path( $destination );
	if ( '' === $destination_path ) {
		return array();
	}

	$conflicts = array();
	foreach ( $sources as $source ) {
		$comparison = isset( $source['comparison'] ) ? (string) $source['comparison'] : 'exact';
		if ( 'exact' !== $comparison ) {
			continue;
		}

		$pattern = isset( $source['pattern'] ) ? (string) $source['pattern'] : '';
		if ( '' !== $pattern && mcp_rankmath_normalized_redirection_path( $pattern ) === $destination_path ) {
			$conflicts[] = array(
				'pattern' => $pattern,
				'comparison' => $comparison,
				'destination_path' => $destination_path,
			);
		}
	}

	return $conflicts;
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
 * Get post types for content SEO audits.
 *
 * @param mixed $input Input value.
 * @return array<int,string>
 */
function mcp_rankmath_get_content_audit_post_types( $input ): array {
	$requested = is_array( $input ) ? $input : ( is_string( $input ) && '' !== $input ? array( $input ) : array() );
	if ( empty( $requested ) ) {
		$requested = array( 'post', 'page' );
	}

	return array_values(
		array_unique(
			array_filter(
				array_map( 'sanitize_key', $requested ),
				static function ( string $post_type ): bool {
					return '' !== $post_type && post_type_exists( $post_type );
				}
			)
		)
	);
}

/**
 * Get post statuses for content SEO audits.
 *
 * @param mixed $input Input value.
 * @return array<int,string>
 */
function mcp_rankmath_get_content_audit_statuses( $input ): array {
	$requested = is_array( $input ) ? $input : ( is_string( $input ) && '' !== $input ? array( $input ) : array() );
	if ( empty( $requested ) ) {
		$requested = array( 'publish' );
	}

	return array_values( array_unique( array_filter( array_map( 'sanitize_key', $requested ) ) ) );
}

/**
 * Return Rank Math schema-related meta for one post.
 *
 * @param int $post_id Post ID.
 * @return array<string,mixed>
 */
function mcp_rankmath_get_post_schema_meta( int $post_id ): array {
	$schema = array();
	foreach ( get_post_meta( $post_id ) as $key => $values ) {
		if ( ! str_starts_with( (string) $key, 'rank_math_schema_' ) && ! in_array( $key, array( 'rank_math_rich_snippet', 'rank_math_snippet_name', 'rank_math_snippet_desc', 'rank_math_snippet_shortcode' ), true ) ) {
			continue;
		}

		$schema[ $key ] = isset( $values[0] ) ? maybe_unserialize( $values[0] ) : '';
	}

	ksort( $schema );
	return $schema;
}

/**
 * Sanitize a schema meta value without destroying structured arrays.
 *
 * @param mixed $value Value from ability input.
 * @return mixed
 */
function mcp_rankmath_sanitize_schema_value( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'mcp_rankmath_sanitize_schema_value', $value );
	}

	if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
		return $value;
	}

	if ( is_string( $value ) ) {
		$decoded = json_decode( $value, true );
		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			return mcp_rankmath_sanitize_schema_value( $decoded );
		}

		return mcp_rankmath_sanitize_schema_string( $value );
	}

	return '';
}

/**
 * Sanitize one schema string while preserving documented Rank Math variables.
 *
 * WordPress text sanitizers remove percent-encoded octets. That corrupts
 * variables such as %date% because "%da" is interpreted as an encoded byte.
 * Protect complete Rank Math variable tokens before sanitizing and restore
 * only the exact tokens that were present in the input.
 *
 * @param string $value Schema string.
 * @return string
 */
function mcp_rankmath_sanitize_schema_string( string $value ): string {
	$variables = array();
	$protected = preg_replace_callback(
		'/%[A-Za-z][^%\r\n]*%/',
		static function ( array $matches ) use ( &$variables ): string {
			$placeholder               = '__MCP_RANKMATH_SCHEMA_VARIABLE_' . count( $variables ) . '__';
			$variables[ $placeholder ] = $matches[0];
			return $placeholder;
		},
		$value
	);

	if ( ! is_string( $protected ) ) {
		return '';
	}

	$sanitized = sanitize_textarea_field( $protected );
	return strtr( $sanitized, $variables );
}

/**
 * Validate a Rank Math schema meta key without lowercasing it.
 *
 * Rank Math schema keys may contain case-sensitive schema names such as
 * rank_math_schema_Article. sanitize_key() would silently change those keys.
 *
 * @param string $key Meta key.
 * @return bool
 */
function mcp_rankmath_is_schema_meta_key( string $key ): bool {
	return 1 === preg_match( '/^rank_math_schema_[A-Za-z0-9_-]+$/', $key );
}

/**
 * Get Rank Math primary term data for one taxonomy.
 *
 * @param int    $post_id Post ID.
 * @param string $taxonomy Taxonomy name.
 * @return array<string,mixed>
 */
function mcp_rankmath_get_primary_term_data( int $post_id, string $taxonomy ): array {
	$taxonomy = sanitize_key( $taxonomy );
	if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
		return array( 'success' => false, 'message' => 'Taxonomy not found.' );
	}

	if ( ! is_object_in_taxonomy( get_post_type( $post_id ), $taxonomy ) ) {
		return array( 'success' => false, 'message' => 'Taxonomy is not assigned to this post type.' );
	}

	$meta_key        = 'rank_math_primary_' . $taxonomy;
	$primary_term_id = absint( get_post_meta( $post_id, $meta_key, true ) );
	$terms          = get_the_terms( $post_id, $taxonomy );
	$terms          = is_array( $terms ) ? $terms : array();
	$term_items     = array();
	$primary_term   = null;

	foreach ( $terms as $term ) {
		$term_url = get_term_link( $term );
		$item = array(
			'id'   => (int) $term->term_id,
			'name' => $term->name,
			'slug' => $term->slug,
			'url'  => is_wp_error( $term_url ) ? '' : $term_url,
		);
		$term_items[] = $item;
		if ( $primary_term_id === (int) $term->term_id ) {
			$primary_term = $item;
		}
	}

	return array(
		'success'         => true,
		'post_id'         => $post_id,
		'taxonomy'        => $taxonomy,
		'primary_meta_key' => $meta_key,
		'primary_term_id' => $primary_term_id,
		'primary_term'    => $primary_term,
		'terms'           => $term_items,
	);
}

/**
 * Decode a Rank Math redirection source column.
 *
 * @param mixed $raw Raw DB value.
 * @return array<int,array<string,mixed>>
 */
function mcp_rankmath_decode_redirection_sources( $raw ): array {
	if ( is_array( $raw ) ) {
		return $raw;
	}

	if ( ! is_string( $raw ) || '' === $raw ) {
		return array();
	}

	$unserialized = maybe_unserialize( $raw );
	if ( is_array( $unserialized ) ) {
		return $unserialized;
	}

	$decoded = json_decode( $raw, true );
	return is_array( $decoded ) ? $decoded : array();
}

/**
 * Check whether one redirection source matches a URL path.
 *
 * @param array<string,mixed> $source Source object.
 * @param string              $path Normalized path.
 * @return bool
 */
function mcp_rankmath_redirection_source_matches_path( array $source, string $path ): bool {
	$pattern = isset( $source['pattern'] ) ? (string) $source['pattern'] : '';
	if ( '' === $pattern ) {
		return false;
	}

	$comparison = isset( $source['comparison'] ) ? (string) $source['comparison'] : 'exact';
	$ignore     = isset( $source['ignore'] ) ? (string) $source['ignore'] : '';
	$case_fold  = 'case' === $ignore || ! empty( $source['ignore_case'] );
	$haystack   = $case_fold ? strtolower( $path ) : $path;
	$needle     = $case_fold ? strtolower( $pattern ) : $pattern;

	if ( 'regex' !== $comparison ) {
		$needle_path = mcp_rankmath_normalized_redirection_path( $needle );
		if ( '' !== $needle_path ) {
			$needle = $case_fold ? strtolower( $needle_path ) : $needle_path;
		}
	}

	switch ( $comparison ) {
		case 'contains':
			return '' !== $needle && false !== strpos( $haystack, $needle );
		case 'start':
			return '' !== $needle && str_starts_with( $haystack, $needle );
		case 'end':
			return '' !== $needle && str_ends_with( untrailingslashit( $haystack ), untrailingslashit( $needle ) );
		case 'regex':
			$flags = $case_fold ? 'i' : '';
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Rank Math stores user regex patterns; invalid patterns should fail closed.
			$result = @preg_match( '#' . str_replace( '#', '\#', $pattern ) . '#' . $flags, $path );
			return 1 === $result;
		case 'exact':
		default:
			return untrailingslashit( $haystack ) === untrailingslashit( $needle );
	}
}

/**
 * Parse XML sitemap loc values.
 *
 * @param string $body XML body.
 * @return array<int,string>
 */
function mcp_rankmath_parse_sitemap_locs( string $body ): array {
	if ( '' === trim( $body ) || ! function_exists( 'simplexml_load_string' ) ) {
		return array();
	}

	$previous = libxml_use_internal_errors( true );
	$xml      = simplexml_load_string( $body );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( false === $xml ) {
		return array();
	}

	$locs = array();
	foreach ( $xml->xpath( '//*[local-name()="loc"]' ) ?: array() as $loc ) {
		$url = esc_url_raw( (string) $loc );
		if ( '' !== $url ) {
			$locs[] = $url;
		}
	}

	return array_values( array_unique( $locs ) );
}

/**
 * Normalize an internal URL to a comparable path.
 *
 * Query strings and fragments are intentionally ignored. The workflow question
 * is whether a content object is linked at all, not whether each tracking
 * variant has a separate inbound edge.
 *
 * @param string $url URL or root-relative path.
 * @return string Normalized path, or empty string for non-site URLs.
 */
function mcp_rankmath_normalize_internal_link_path( string $url ): string {
	$url = trim( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5 ) );
	if ( '' === $url || str_starts_with( $url, '#' ) ) {
		return '';
	}

	$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
	if ( is_string( $scheme ) && in_array( strtolower( $scheme ), array( 'mailto', 'tel', 'sms', 'javascript', 'data' ), true ) ) {
		return '';
	}

	if ( str_starts_with( $url, '//' ) ) {
		$url = ( is_ssl() ? 'https:' : 'http:' ) . $url;
	}

	$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
	$url_host  = wp_parse_url( $url, PHP_URL_HOST );
	if ( is_string( $url_host ) && '' !== $url_host ) {
		$home_host_normalized = strtolower( preg_replace( '/^www\./', '', (string) $home_host ) );
		$url_host_normalized  = strtolower( preg_replace( '/^www\./', '', $url_host ) );
		if ( $home_host_normalized !== $url_host_normalized ) {
			return '';
		}
	}

	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $path ) || '' === $path ) {
		$path = '/';
	}

	$path = '/' . ltrim( rawurldecode( $path ), '/' );
	$path = preg_replace( '#/+#', '/', $path );
	if ( '/' === $path ) {
		return '/';
	}

	return trailingslashit( $path );
}

/**
 * Extract normalized internal link paths from post content.
 *
 * @param string $content Post content.
 * @return array<int,string>
 */
function mcp_rankmath_extract_internal_link_paths( string $content ): array {
	if ( '' === $content || ! preg_match_all( "/\\bhref\\s*=\\s*([\\\"'])(.*?)\\1/i", $content, $matches ) ) {
		return array();
	}

	$paths = array();
	foreach ( $matches[2] as $url ) {
		$path = mcp_rankmath_normalize_internal_link_path( (string) $url );
		if ( '' !== $path ) {
			$paths[] = $path;
		}
	}

	return array_values( array_unique( $paths ) );
}

/**
 * Get post types included in inbound-link scans.
 *
 * @param array<string,mixed> $input Ability input.
 * @return array<int,string>
 */
function mcp_rankmath_get_inbound_scan_post_types( array $input ): array {
	if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) && ! empty( $input['post_types'] ) ) {
		return array_values(
			array_unique(
				array_filter(
					array_map( 'sanitize_key', $input['post_types'] ),
					static function ( string $post_type ): bool {
						return '' !== $post_type && post_type_exists( $post_type );
					}
				)
			)
		);
	}

	$post_types = array_values( get_post_types( array( 'public' => true ), 'names' ) );
	foreach ( array( 'gp_elements', 'wp_block', 'wp_navigation' ) as $post_type ) {
		if ( post_type_exists( $post_type ) ) {
			$post_types[] = $post_type;
		}
	}

	return array_values( array_unique( array_diff( $post_types, array( 'attachment' ) ) ) );
}

/**
 * Build an inbound-link graph from content and navigation menu items.
 *
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>
 */
function mcp_rankmath_build_inbound_link_graph( array $input ): array {
	$post_types       = mcp_rankmath_get_inbound_scan_post_types( $input );
	$post_statuses    = isset( $input['post_statuses'] ) && is_array( $input['post_statuses'] ) ? $input['post_statuses'] : array( 'publish' );
	$post_statuses    = array_values( array_unique( array_map( 'sanitize_key', $post_statuses ) ) );
	$include_sources  = array_key_exists( 'include_sources', $input ) ? (bool) $input['include_sources'] : true;
	$include_menus    = array_key_exists( 'include_menus', $input ) ? (bool) $input['include_menus'] : true;
	$target_post_id   = isset( $input['target_post_id'] ) ? absint( $input['target_post_id'] ) : 0;
	$target_url       = isset( $input['target_url'] ) ? esc_url_raw( (string) $input['target_url'] ) : '';
	$target_paths     = array();
	$target_post      = null;
	$all_target_paths = array();

	if ( $target_post_id > 0 ) {
		$target_post = get_post( $target_post_id );
		if ( ! $target_post ) {
			return array( 'success' => false, 'message' => 'Target post not found.' );
		}
		$target_path = mcp_rankmath_normalize_internal_link_path( get_permalink( $target_post_id ) );
		if ( '' !== $target_path ) {
			$target_paths[] = $target_path;
		}
	}

	if ( '' !== $target_url ) {
		$target_path = mcp_rankmath_normalize_internal_link_path( $target_url );
		if ( '' === $target_path ) {
			return array( 'success' => false, 'message' => 'Target URL is not an internal site URL.' );
		}
		$target_paths[] = $target_path;
	}

	$target_paths = array_values( array_unique( $target_paths ) );
	if ( empty( $target_paths ) ) {
		$target_query = new WP_Query(
			array(
				'post_type'              => $post_types,
				'post_status'            => $post_statuses,
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $target_query->posts as $post_id ) {
			$path = mcp_rankmath_normalize_internal_link_path( get_permalink( (int) $post_id ) );
			if ( '' !== $path ) {
				$all_target_paths[ $path ] = (int) $post_id;
			}
		}
	} else {
		foreach ( $target_paths as $path ) {
			$all_target_paths[ $path ] = $target_post_id;
		}
	}

	$counts  = array();
	$sources = array();
	foreach ( $all_target_paths as $path => $post_id ) {
		$counts[ $path ]  = 0;
		$sources[ $path ] = array();
	}

	$source_query = new WP_Query(
		array(
			'post_type'              => $post_types,
			'post_status'            => $post_statuses,
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$scanned_sources = 0;
	foreach ( $source_query->posts as $source_id ) {
		$source_id = (int) $source_id;
		$content   = (string) get_post_field( 'post_content', $source_id );
		$paths     = mcp_rankmath_extract_internal_link_paths( $content );
		if ( empty( $paths ) ) {
			continue;
		}
		++$scanned_sources;

		foreach ( $paths as $path ) {
			if ( ! array_key_exists( $path, $all_target_paths ) ) {
				continue;
			}
			if ( $source_id === (int) $all_target_paths[ $path ] ) {
				continue;
			}

			++$counts[ $path ];
			if ( $include_sources ) {
				$sources[ $path ][] = array(
					'source_type' => 'post',
					'id'          => $source_id,
					'title'       => get_the_title( $source_id ),
					'post_type'   => get_post_type( $source_id ),
					'url'         => get_permalink( $source_id ),
				);
			}
		}
	}

	if ( $include_menus ) {
		foreach ( wp_get_nav_menus() as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				$path = mcp_rankmath_normalize_internal_link_path( (string) $item->url );
				if ( '' === $path || ! array_key_exists( $path, $all_target_paths ) ) {
					continue;
				}

				++$counts[ $path ];
				if ( $include_sources ) {
					$sources[ $path ][] = array(
						'source_type' => 'nav_menu_item',
						'id'          => (int) $item->ID,
						'title'       => (string) $item->title,
						'menu_id'     => (int) $menu->term_id,
						'menu'        => (string) $menu->name,
						'url'         => (string) $item->url,
					);
				}
			}
		}
	}

	$min_count = isset( $input['min_count'] ) ? max( 0, (int) $input['min_count'] ) : ( empty( $target_paths ) ? 1 : 0 );
	$limit     = isset( $input['limit'] ) ? min( 500, max( 1, (int) $input['limit'] ) ) : 100;
	$items     = array();

	foreach ( $counts as $path => $count ) {
		if ( $count < $min_count ) {
			continue;
		}

		$post_id = (int) ( $all_target_paths[ $path ] ?? 0 );
		$item    = array(
			'target_post_id' => $post_id,
			'target_title'   => $post_id > 0 ? get_the_title( $post_id ) : '',
			'target_type'    => $post_id > 0 ? get_post_type( $post_id ) : '',
			'target_url'     => $post_id > 0 ? get_permalink( $post_id ) : home_url( $path ),
			'target_path'    => $path,
			'inbound_count'  => (int) $count,
		);

		if ( $include_sources ) {
			$item['sources'] = $sources[ $path ];
		}

		$items[] = $item;
	}

	usort(
		$items,
		static function ( array $a, array $b ): int {
			$count_compare = (int) $b['inbound_count'] <=> (int) $a['inbound_count'];
			if ( 0 !== $count_compare ) {
				return $count_compare;
			}
			return strcmp( (string) $a['target_path'], (string) $b['target_path'] );
		}
	);

	$items = array_slice( $items, 0, $limit );

	return array(
		'success'         => true,
		'items'           => $items,
		'count'           => count( $items ),
		'scanned_sources' => $scanned_sources,
		'post_types'      => $post_types,
		'post_statuses'   => $post_statuses,
		'target_paths'    => $target_paths,
	);
}

/**
 * Count inbound internal links for a known set of target posts.
 *
 * This keeps content audits local to the posts they are reporting instead of
 * relying on a top-N sitewide inbound graph.
 *
 * @param array<int,int> $post_ids Target post IDs.
 * @param array<string,mixed> $input Optional scan controls.
 * @return array<int,int>
 */
function mcp_rankmath_get_inbound_counts_for_posts( array $post_ids, array $input = array() ): array {
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	if ( empty( $post_ids ) ) {
		return array();
	}

	$post_types      = mcp_rankmath_get_inbound_scan_post_types( $input );
	$post_statuses   = isset( $input['post_statuses'] ) && is_array( $input['post_statuses'] ) ? $input['post_statuses'] : array( 'publish' );
	$post_statuses   = array_values( array_unique( array_map( 'sanitize_key', $post_statuses ) ) );
	$include_menus   = array_key_exists( 'include_menus', $input ) ? (bool) $input['include_menus'] : true;
	$target_by_path  = array();
	$counts          = array();

	foreach ( $post_ids as $post_id ) {
		$path = mcp_rankmath_normalize_internal_link_path( get_permalink( $post_id ) );
		if ( '' === $path ) {
			continue;
		}
		$target_by_path[ $path ] = $post_id;
		$counts[ $post_id ]      = 0;
	}

	if ( empty( $target_by_path ) ) {
		return $counts;
	}

	$source_query = new WP_Query(
		array(
			'post_type'              => $post_types,
			'post_status'            => $post_statuses,
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $source_query->posts as $source_id ) {
		$source_id = (int) $source_id;
		$content   = (string) get_post_field( 'post_content', $source_id );
		foreach ( mcp_rankmath_extract_internal_link_paths( $content ) as $path ) {
			if ( ! isset( $target_by_path[ $path ] ) ) {
				continue;
			}
			$target_id = (int) $target_by_path[ $path ];
			if ( $source_id === $target_id ) {
				continue;
			}
			++$counts[ $target_id ];
		}
	}

	if ( $include_menus ) {
		foreach ( wp_get_nav_menus() as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item ) {
				$path = mcp_rankmath_normalize_internal_link_path( (string) $item->url );
				if ( isset( $target_by_path[ $path ] ) ) {
					++$counts[ (int) $target_by_path[ $path ] ];
				}
			}
		}
	}

	return $counts;
}

require_once __DIR__ . '/includes/abilities-options.php';
require_once __DIR__ . '/includes/abilities-routes.php';
require_once __DIR__ . '/includes/abilities-site.php';
require_once __DIR__ . '/includes/abilities-content.php';
require_once __DIR__ . '/includes/abilities-logs-redirections.php';

/**
 * Register optional integration hooks for other workflow plugins.
 */
function mcp_rankmath_register_integration_hooks(): void {
	add_filter( 'ai_translation_workflow_gutenberg_guardrails', 'mcp_rankmath_faq_link_guardrails', 10, 3 );
	add_filter( 'ai_translation_workflow_semantic_link_count_content', 'mcp_rankmath_exclude_faq_links_from_semantic_count', 10, 2 );
}
add_action( 'plugins_loaded', 'mcp_rankmath_register_integration_hooks', 30 );

/**
 * Register Rank Math abilities.
 */
function mcp_register_rankmath_abilities(): void {
	if ( ! mcp_rankmath_check_dependencies() ) {
		return;
	}

	mcp_rankmath_register_option_abilities();
	mcp_rankmath_register_route_abilities();
	mcp_rankmath_register_site_abilities();
	mcp_rankmath_register_content_abilities();
	mcp_rankmath_register_log_redirection_abilities();
}
add_action( 'wp_abilities_api_init', 'mcp_register_rankmath_abilities' );
