<?php
/** Native WPML translation of the public Rank Math search-title template only. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Keep native String Translation permissions as well as settings permission. */
function mcp_rankmath_search_translation_allowed(): bool {
	return current_user_can( 'manage_options' ) && ( current_user_can( 'wpml_manage_string_translation' ) || current_user_can( 'manage_translations' ) );
}

/** Read one fixed public string; never inventory arbitrary admin-option strings. */
function mcp_rankmath_search_title_translation_state( string $target_language ): array {
	global $sitepress;
	if ( ! mcp_rankmath_search_translation_allowed() ) { throw new RuntimeException( 'Native translation permission required.' ); }
	if ( ! is_object( $sitepress ) || ! is_callable( array( $sitepress, 'get_active_languages' ) ) || ! class_exists( '\WPML\Container' ) ) { throw new RuntimeException( 'Native WPML required.' ); }
	$languages = $sitepress->get_active_languages();
	$source_language = (string) $sitepress->get_default_language();
	if ( ! isset( $languages[ $target_language ] ) || $target_language === $source_language ) { throw new RuntimeException( 'Select an active secondary language.' ); }
	$options = get_option( 'rank-math-options-titles', array() );
	$value = $options['search_title'] ?? null;
	if ( ! is_string( $value ) || '' === $value || strlen( $value ) > 2000 ) { throw new RuntimeException( 'Public search title unavailable.' ); }
	$domain = 'admin_texts_rank-math-options-titles';
	$name = '[rank-math-options-titles]search_title';
	$factory = \WPML\Container::make( 'WPML_ST_String_Factory' );
	$id = (int) $factory->get_string_id( $value, $domain, $name );
	if ( ! $id ) {
		$existing = $factory->find_by_name( $name );
		if ( $existing->exists() ) { throw new RuntimeException( 'Existing native public string does not match the current source.', 11 ); }
		$source = array( 'id' => 0, 'language' => $source_language, 'value' => $value );
		return array( 'registered' => false, 'source' => $source, 'source_hash' => hash( 'sha256', (string) wp_json_encode( $source ) ), 'target_language' => $target_language, 'target' => null, 'target_hash' => hash( 'sha256', (string) wp_json_encode( null ) ) );
	}
	$string = $factory->find_by_id( $id );
	if ( ! $string->exists() || $string->get_context() !== $domain || $string->get_name() !== $name || $string->get_value() !== $value || $string->get_language() !== $source_language ) { throw new RuntimeException( 'Native registered source mismatch.', 11 ); }
	$target = null;
	foreach ( $string->get_translations() as $translation ) {
		$translation = (array) $translation;
		if ( ( $translation['language'] ?? '' ) === $target_language ) {
			$target = array( 'value' => (string) ( $translation['value'] ?? '' ), 'status' => (int) ( $translation['status'] ?? 0 ) );
		}
	}
	$source = array( 'id' => $id, 'language' => $source_language, 'value' => $value );
	return array( 'registered' => true, 'source' => $source, 'source_hash' => hash( 'sha256', (string) wp_json_encode( $source ) ), 'target_language' => $target_language, 'target' => $target, 'target_hash' => hash( 'sha256', (string) wp_json_encode( $target ) ) );
}

/** Return selected public text only; refuse unavailable native registration. */
function mcp_rankmath_get_search_title_translation( array $input = array() ) {
	try { return array( 'success' => true ) + mcp_rankmath_search_title_translation_state( (string) ( $input['target_language'] ?? '' ) ); }
	catch ( Throwable $error ) { return new WP_Error( 11 === $error->getCode() ? 'rankmath_search_title_source_conflict' : 'rankmath_search_translation_unavailable', 11 === $error->getCode() ? 'An existing registered public search-title string conflicts with the current default-language source. Preserve it and review the selected native string first.' : 'The public search-title translation is unavailable or denied. No alternate storage query is used.' ); }
}

/** Preview or update one native translation without changing Rank Math settings. */
function mcp_rankmath_update_search_title_translation( array $input = array() ) {
	try {
		$before = mcp_rankmath_search_title_translation_state( (string) ( $input['target_language'] ?? '' ) );
		$value = $input['value'] ?? null;
		if ( ! is_string( $value ) || '' === trim( $value ) || strlen( $value ) > 2000 || wp_strip_all_tags( $value ) !== $value ) { return new WP_Error( 'rankmath_search_translation_invalid', 'Supply a plain-text title template of at most 2000 bytes.' ); }
		preg_match_all( '/%[^%\s]+%/', $before['source']['value'], $source_tokens );
		preg_match_all( '/%[^%\s]+%/', $value, $target_tokens );
		sort( $source_tokens[0] ); sort( $target_tokens[0] );
		if ( $source_tokens[0] !== $target_tokens[0] ) { return new WP_Error( 'rankmath_search_translation_tokens', 'Preserve every native Rank Math placeholder exactly.' ); }
		if ( ! defined( 'ICL_TM_COMPLETE' ) || ! defined( 'ICL_TM_WAITING_FOR_TRANSLATOR' ) || ! defined( 'ICL_TM_IN_PROGRESS' ) ) { throw new RuntimeException( 'Native lifecycle unavailable.' ); }
		if ( $before['target'] && in_array( $before['target']['status'], array( ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS ), true ) ) { return new WP_Error( 'rankmath_search_translation_job_active', 'Preserve the waiting or in-progress native translation and review its job first.' ); }
		if ( true !== ( $input['confirm'] ?? false ) ) { return array( 'success' => true, 'applied' => false, 'before' => $before, 'proposed_value' => $value, 'registration_required' => ! $before['registered'] ); }
		if ( ( $input['expected_source_hash'] ?? '' ) !== $before['source_hash'] || ( $input['expected_target_hash'] ?? '' ) !== $before['target_hash'] ) { return new WP_Error( 'rankmath_search_translation_stale', 'The selected source or target changed after review.' ); }
		if ( ! $before['registered'] ) {
			if ( true !== ( $input['register_if_missing'] ?? false ) ) { return new WP_Error( 'rankmath_search_title_registration_required', 'The fixed public search-title string is not registered. Preview and explicitly select register_if_missing to register only this current source through native WPML.' ); }
			if ( ! has_action( 'wpml_register_single_string' ) ) { throw new RuntimeException( 'Native registration API unavailable.' ); }
			do_action( 'wpml_register_single_string', 'admin_texts_rank-math-options-titles', '[rank-math-options-titles]search_title', $before['source']['value'], false, $before['source']['language'] );
			$factory = \WPML\Container::make( 'WPML_ST_String_Factory' );
			$factory->clear_string_id_cache();
			$registered = mcp_rankmath_search_title_translation_state( $before['target_language'] );
			if ( ! $registered['registered'] || $registered['source']['value'] !== $before['source']['value'] || $registered['source']['language'] !== $before['source']['language'] || null !== $registered['target'] ) { throw new RuntimeException( 'Native registration did not preserve the selected source and empty target. Re-read before retrying.' ); }
			$before = $registered;
		}
		if ( $before['target'] && $before['target']['value'] === $value && ICL_TM_COMPLETE === $before['target']['status'] ) { return array( 'success' => true, 'applied' => false, 'unchanged' => true, 'after' => $before ); }
		if ( ! function_exists( 'icl_add_string_translation' ) ) { throw new RuntimeException( 'Native writer unavailable.' ); }
		$id = icl_add_string_translation( $before['source']['id'], $before['target_language'], $value, ICL_TM_COMPLETE );
		if ( ! $id ) { throw new RuntimeException( 'Native write not confirmed.' ); }
		$after = mcp_rankmath_search_title_translation_state( $before['target_language'] );
		return array( 'success' => $before['source_hash'] === $after['source_hash'] && null !== $after['target'] && $value === $after['target']['value'] && ICL_TM_COMPLETE === $after['target']['status'], 'applied' => true, 'before' => $before, 'after' => $after, 'verification_required' => true );
	} catch ( Throwable $error ) { return new WP_Error( 'rankmath_search_translation_failed', 'The native public search-title operation failed or was denied. Re-read the selected translation before retrying; no alternate write is used.' ); }
}

/** Register a fixed public-text Interface, separate from generic admin options. */
function mcp_rankmath_register_search_translation_abilities(): void {
	$lang = array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 7 );
	$common = array(
		'category' => 'site',
		'output_schema' => array( 'type' => 'object', 'properties' => array( 'success' => array( 'type' => 'boolean' ) ) ),
		'permission_callback' => 'mcp_rankmath_search_translation_allowed',
	);
	$read = $common;
	$read['label'] = 'Get Search Title Translation';
	$read['description'] = 'Read only the registered public Rank Math search-title source and one active secondary-language translation. No other admin-option strings are exposed.';
	$read['input_schema'] = array( 'type' => 'object', 'properties' => array( 'target_language' => $lang ), 'required' => array( 'target_language' ), 'additionalProperties' => false );
	$read['execute_callback'] = 'mcp_rankmath_get_search_title_translation';
	$read['meta'] = array( 'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ) );
	wp_register_ability( 'rankmath/get-search-title-translation', $read );
	$write = $common;
	$hash = array( 'type' => 'string', 'pattern' => '^[a-f0-9]{64}$' );
	$write['label'] = 'Update Search Title Translation';
	$write['description'] = 'Preview or complete the registered public search-title translation with native WPML permissions, source/target hashes and unchanged placeholders. Explicit register_if_missing can register only this fixed public source if absent. Existing source settings, other strings and active jobs are preserved.';
	$write['input_schema'] = array( 'type' => 'object', 'properties' => array(
		'target_language' => $lang,
		'value' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 2000 ),
		'confirm' => array( 'type' => 'boolean', 'default' => false ),
		'register_if_missing' => array( 'type' => 'boolean', 'default' => false ),
		'expected_source_hash' => $hash,
		'expected_target_hash' => $hash,
	), 'required' => array( 'target_language', 'value' ), 'additionalProperties' => false );
	$write['execute_callback'] = 'mcp_rankmath_update_search_title_translation';
	$write['meta'] = array( 'annotations' => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ) );
	wp_register_ability( 'rankmath/update-search-title-translation', $write );
}
