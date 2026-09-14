<?php
/**
 * Verify native Rank Math image filters preserve HTML5 attribute values.
 * Run through WP-CLI eval-file. All fixtures and hook changes are request-local.
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'RankMath\\Image_Seo\\Add_Attributes' ) ) {
	throw new RuntimeException( 'WordPress and Rank Math Image SEO must be loaded.' );
}

$hooks = array( 'the_content', 'post_thumbnail_html', 'woocommerce_single_product_image_thumbnail_html' );
$native = new RankMath\Image_Seo\Add_Attributes();
$fixture = '<img src="https://example.com/consultation.webp?x=1&amp;y=2" alt="L&apos;accueil &amp; les &quot;questions&quot; &NotEqualTilde; العربية 日本語" data-note="Keep &amp;apos; literal" srcset="https://example.com/a.webp 400w, https://example.com/b.webp 800w" sizes="(max-width: 600px) 100vw, 50vw">';
$parse = static function ( string $html ): array {
	$processor = new WP_HTML_Tag_Processor( $html );
	if ( ! $processor->next_tag( 'IMG' ) ) {
		throw new RuntimeException( 'Expected an image in the filtered fixture.' );
	}
	$values = array();
	foreach ( $processor->get_attribute_names_with_prefix( '' ) as $name ) {
		$values[ $name ] = $processor->get_attribute( $name );
	}
	return $values;
};
$expected = $parse( $fixture );
$checks = 0;
foreach ( array( 'wp', 'rest_api_init' ) as $action ) {
	// Use the normal registration actions, then fixed request-local formats.
	do_action( $action );
	$native->is_alt = 'Generated alt';
	$native->is_title = 'Generated title';
	foreach ( $hooks as $hook ) {
		// Keep only this real native handler in each fixture's filter chain.
		remove_all_filters( $hook );
		add_filter( $hook, array( $native, 'add_img_attributes' ), 11, 2 );
	}
	if ( function_exists( 'mcp_rankmath_register_image_attribute_compatibility' ) ) {
		mcp_rankmath_register_image_attribute_compatibility();
		mcp_rankmath_register_image_attribute_compatibility();
	}
	foreach ( $hooks as $hook ) {
		$output = apply_filters( $hook, $fixture, 0 );
		$actual = $parse( $output );
		foreach ( $expected as $name => $value ) {
			if ( ( $actual[ $name ] ?? null ) !== $value ) {
				throw new RuntimeException( "$action / $hook changed existing $name: " . wp_json_encode( $actual[ $name ] ?? null ) );
			}
		}
		if ( 'Generated title' !== ( $actual['title'] ?? null ) ) {
			throw new RuntimeException( "$action / $hook did not add the native title." );
		}
		$filled = $parse( apply_filters( $hook, '<img src="https://example.com/missing.webp">', 0 ) );
		if ( 'Generated alt' !== ( $filled['alt'] ?? null ) || 'Generated title' !== ( $filled['title'] ?? null ) ) {
			throw new RuntimeException( "$action / $hook lost native missing-attribute filling." );
		}
		if ( $output !== apply_filters( $hook, $output, 0 ) ) {
			throw new RuntimeException( "$action / $hook is not idempotent." );
		}
		$checks++;
	}
}
if ( has_filter( 'attribute_escape', 'mcp_rankmath_escape_html5_image_attribute' ) ) {
	throw new RuntimeException( 'Image compatibility leaked into unrelated attribute escaping.' );
}
echo wp_json_encode( array( 'passed' => true, 'native_hook_cases' => $checks, 'database_writes' => 0 ) ) . "\n";
