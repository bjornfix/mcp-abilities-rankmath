<?php
/**
 * Keep native Rank Math Image SEO compatible with HTML5 attribute entities.
 *
 * @package MCP_Abilities_RankMath
 */

defined( 'ABSPATH' ) || exit;

/**
 * Escape attributes using the document's HTML5 entity set.
 *
 * Rank Math extracts encoded attribute strings and passes them to esc_attr().
 * WordPress's default HTML 4.01 entity set double-encodes valid HTML5 references
 * such as &apos; whenever Rank Math adds a missing alt or title. This filter
 * changes only that serialization call's entity set; other escape filters still
 * run normally and native Rank Math owns all image selection and title formats.
 *
 * @param string $escaped Default escaped attribute.
 * @param string $text    Attribute passed to esc_attr().
 * @return string
 */
function mcp_rankmath_escape_html5_image_attribute( $escaped, $text ): string {
	return htmlspecialchars(
		wp_check_invalid_utf8( $text ),
		ENT_QUOTES | ENT_HTML5,
		get_option( 'blog_charset', 'UTF-8' ),
		false
	);
}

/** Adapt the native image callbacks after Rank Math registers them. */
function mcp_rankmath_register_image_attribute_compatibility(): void {
	global $wp_filter;

	foreach ( array( 'the_content', 'post_thumbnail_html', 'woocommerce_single_product_image_thumbnail_html' ) as $hook ) {
		foreach ( $wp_filter[ $hook ]->callbacks[11] ?? array() as $entry ) {
			$callback = $entry['function'];
			if ( ! is_array( $callback ) || ! ( $callback[0] instanceof \RankMath\Image_Seo\Add_Attributes ) || 'add_img_attributes' !== $callback[1] ) {
				continue;
			}
			remove_filter( $hook, $callback, 11 );
			add_filter(
				$hook,
				static function ( $content, $post_id = null ) use ( $callback ) {
					add_filter( 'attribute_escape', 'mcp_rankmath_escape_html5_image_attribute', PHP_INT_MIN, 2 );
					try {
						return call_user_func( $callback, $content, $post_id );
					} finally {
						remove_filter( 'attribute_escape', 'mcp_rankmath_escape_html5_image_attribute', PHP_INT_MIN );
					}
				},
				11,
				$entry['accepted_args']
			);
		}
	}
}

add_action( 'wp', 'mcp_rankmath_register_image_attribute_compatibility', 10000 );
add_action( 'rest_api_init', 'mcp_rankmath_register_image_attribute_compatibility', 11 );
