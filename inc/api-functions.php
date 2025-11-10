<?php
/**
 * This file is part of WP Consent API.
 *
 * Copyright 2020 Rogier Lankhorst and the WordPress Core Privacy team.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/.
 *
 * @package wordpress/consent-api
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Enqueues scripts for the API for the site frontend.
 *
 * We need to ensure this script fires in correct order:
 * 1) all plugins, themes, etc
 * 2) this script
 * 3) consent management script
 *
 * This way we can ensure that plugins can use the JavaScript hooks
 * Consent management plugin should declare dependency on API js
 * API js should load as last, so we give a very high priority.
 *
 * @return void
 */
function wp_consent_api_enqueue_assets(): void {
	$minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	wp_enqueue_script( 'wp-consent-api', WP_CONSENT_API_URL . "assets/js/wp-consent-api$minified.js", array(), WP_CONSENT_API_VERSION, true );

	// We can pass a default or static consent type to the javascript.
	$consent_type = wp_get_consent_type();

	// When the consenttype (optin or optout) can be set dynamically, we can tell
	// plugins to wait in the javascript until the consenttype has been determined.
	$waitfor_consent_hook = apply_filters( 'wp_consent_api_waitfor_consent_hook', false );

	// The cookie expiration for the front-end consent cookies.
	$expiration = wp_consent_api_cookie_expiration();
	$prefix     = WP_Consent_API::$config->consent_cookie_prefix();

	wp_localize_script(
		'wp-consent-api',
		'consent_api',
		array(
			'consent_type'         => $consent_type,
			'waitfor_consent_hook' => $waitfor_consent_hook,
			'cookie_expiration'    => $expiration,
			'cookie_prefix'        => $prefix,
			'services'             => WP_Consent_API::$cookie_info->get_service_info(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'wp_consent_api_enqueue_assets', PHP_INT_MAX - 100 );

/**
 * Enqueue style for back-end  to show wp-consent-api unregister plugins list in a better style in site health check page.
 *
 * @param string $hook Hook name.
 */
function wp_consent_api_enqueue_admin_assets( $hook ): void {
    if ( 'site-health.php' !== $hook ) {
	    return;
    }

	wp_enqueue_style( 'wp-consent-api-css', WP_CONSENT_API_URL . 'assets/css/wp-consent-api.css', array(), WP_CONSENT_API_VERSION );

}
add_action( 'admin_enqueue_scripts', 'wp_consent_api_enqueue_admin_assets' );

/**
 * Validates consent type.
 *
 * @since 1.0.0
 *
 * @param string $consent_type A consent type.
 *
 * @return string The validated consent type, or `false`.
 */
function wp_validate_consent_type( string $consent_type ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	if ( in_array( $consent_type, WP_Consent_API::$config->consent_types(), true ) ) {
		return $consent_type;
	}

	return '';
}

/**
 * Validates consent value.
 *
 * @since 1.0.0
 *
 * @param string $value A consent value.
 *
 * @return string The validated consent type, or false.
 */
function wp_validate_consent_value( string $value ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	if ( in_array( $value, WP_Consent_API::$config->consent_values(), true ) ) {
		return $value;
	}
	return '';
}

/**
 * Validates consent category.
 *
 * @param string $category A consent category.
 *
 * @return string The validated category, default functional
 *@since 1.0.0
 *
 */
function wp_validate_consent_category( string $category ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	if ( in_array( $category, WP_Consent_API::$config->consent_categories(), true ) ) {
		return $category;
	}

	return 'functional';
}
/**
 * Validates consent service.
 * On wide adoption this should validate strictly against registered services. Right now, we fallback to a default sanitizer.
 *
 * @param string $service A consent service.
 *
 * @return string The validated service
 * @since 1.0.8
 *
 */
function wp_validate_consent_service( string $service ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	//check if this service is listed in the registered services list
	$services = WP_Consent_API::$cookie_info->get_services();
	if ( in_array($service, $services, true ) ) {
		return $service;
	}

	//as not all services will be registered yet, we'll sanitize loosely currently.
	return sanitize_text_field( $service );
}

/**
 * Retrieves active consent type.
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_get_consent_type(): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	return apply_filters( 'wp_get_consent_type', '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is intended for Core.
}

/**
 * Filterable, to allow for use in combination with consent_type
 * return value of wp_consent$level cookie (false, deny or allow)
 *
 * @since 1.0.0
 *
 * @param string      $category     The consent category.
 * @param ?string $requested_by Plugin name e.g. complianz-gdpr/complianz-gdpr.php. This can be used to disable consent for a plugin specifically.
 *
 * @return bool
 */
function wp_has_consent( string $category, ?string $requested_by = null ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	$consent_type     = wp_get_consent_type();
	$category             = wp_validate_consent_category( $category );
	$prefix           = WP_Consent_API::$config->consent_cookie_prefix();
	$cookie_name      = "{$prefix}_{$category}";

	if ( ! $consent_type ) {
		// If consent_type is not set, there's no consent management, we should
		// return true to activate all cookies.
		$has_consent = true;
	} elseif ( strpos( $consent_type, 'optout' ) !== false && ( ! isset( $_COOKIE[ $cookie_name ] ) ) ) {
		// If it's opt out and no cookie is set or it's false, we should also return true.
		$has_consent = true;
	} elseif ( isset( $_COOKIE[ $cookie_name ] ) && 'allow' === $_COOKIE[ $cookie_name ] ) {
		// All other situations, return only true if value is allow.
		$has_consent = true;
	} else {
		$has_consent = false;
	}

	return apply_filters( 'wp_has_consent', $has_consent, $category, $requested_by ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is intended for Core.
}

/**
 * Check if a specific service has consent
 *
 * @since 1.0.8
 *
 * @param string      $service      The service name.
 * @param string|bool $requested_by Plugin name e.g. complianz-gdpr/complianz-gdpr.php. This can be used to disable consent for a plugin specifically.
 *
 * @return bool
 */
function wp_has_service_consent( $service, $requested_by = false ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	$service = wp_validate_consent_service( $service );

	// If service is not valid, return false
	if ( empty( $service ) ) {
		return false;
	}

	$prefix = WP_Consent_API::$config->consent_cookie_prefix();
	$cookie_name = "{$prefix}_consented_services";

	// Check if it's in the consented services cookie
	$consented_services = array();
	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		$consented_services_json = stripslashes( $_COOKIE[ $cookie_name ] );
		$consented_services = json_decode( $consented_services_json, true );

		if ( ! is_array( $consented_services ) ) {
			$consented_services = array();
		}
	}

	if ( ! isset( $consented_services[ $service ] ) ) {
		// Default to the category
		$category = WP_Consent_API::$cookie_info->get_service_category( $service );
		return wp_has_consent( $category, $requested_by );
	}

	$has_consent = (bool) $consented_services[ $service ];

	return apply_filters( 'wp_has_service_consent', $has_consent, $service, $requested_by ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is intended for Core.
}

/**
 * Check if a specific service is denied
 *
 * @since 1.0.8
 *
 * @param string      $service      The service name.
 * @param ?string $requested_by Plugin name e.g. complianz-gdpr/complianz-gdpr.php. This can be used to disable consent for a plugin specifically.
 *
 * @return bool
 */
function wp_is_service_denied( string $service, ?string $requested_by = null ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	$service = wp_validate_consent_service( $service );

	// If service is not valid, return false
	if ( empty( $service ) ) {
		return false;
	}

	$prefix = WP_Consent_API::$config->consent_cookie_prefix();
	$cookie_name = "{$prefix}_consented_services";

	// Check if it's in the consented services cookie
	$consented_services = array();
	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		$consented_services_json = stripslashes( $_COOKIE[ $cookie_name ] );
		$consented_services = json_decode( $consented_services_json, true );

		if ( ! is_array( $consented_services ) ) {
			$consented_services = array();
		}
	}

	if ( ! isset( $consented_services[ $service ] ) ) {
		return false;
	}

	$is_denied = ! (bool) $consented_services[ $service ];

	return apply_filters( 'wp_is_service_denied', $is_denied, $service, $requested_by ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is intended for Core.
}

/**
 * Set consent for a specific service
 *
 * @since 1.0.8
 *
 * @param string $service   The service name.
 * @param bool   $consented Whether consent is given (true) or denied (false).
 *
 * @return void
 */
function wp_set_service_consent( string $service, bool $consented ): void { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	$service = wp_validate_consent_service( $service );

	// If service is not valid, bail
	if ( empty( $service ) ) {
		return;
	}

	$prefix = WP_Consent_API::$config->consent_cookie_prefix();
	$cookie_name = "{$prefix}_consented_services";
	$expiration = wp_consent_api_cookie_expiration() * DAY_IN_SECONDS;

	// Get existing services
	$consented_services = array();
	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		$consented_services_json = stripslashes( $_COOKIE[ $cookie_name ] );
		$consented_services = json_decode( $consented_services_json, true );

		if ( ! is_array( $consented_services ) ) {
			$consented_services = array();
		}
	}

	$previous_value = $consented_services[ $service ] ?? null;
	$consented = (bool) $consented;
	$consented_services[ $service ] = $consented;

	setcookie( $cookie_name, wp_json_encode( $consented_services ), time() + $expiration, '/' );

	// Don't trigger event if nothing changed
	if ( $previous_value === $consented ) {
		return;
	}

	do_action( 'wp_consent_api_status_change_service', $service, $consented ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is intended for Core.
}

/**
 * Retrieves cookie expiration.
 *
 * @return int Expiration in days.
 */
function wp_consent_api_cookie_expiration(): int { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	return apply_filters( 'wp_consent_api_cookie_expiration', WP_Consent_API::$config->cookie_expiration_days() );
}

/**
 * Set accepted consent category.
 *
 * @since 1.0.0
 *
 * @param string $category The consent category.
 * @param string $value    The value (either 'allow' or 'deny').
 *
 * @return void
 */
function wp_set_consent( string $category, string $value ): void { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	$value    = apply_filters( 'wp_set_consent_value', $value ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is intended for Core.

	$expiration = wp_consent_api_cookie_expiration() * DAY_IN_SECONDS;
	$category       = wp_validate_consent_category( $category );
	$value      = wp_validate_consent_value( $value );
	$prefix     = WP_Consent_API::$config->consent_cookie_prefix();

	setcookie( "{$prefix}_{$category}", $value, time() + $expiration, '/' );
}

/**
 * Check if a plugin is registered for the WP Consent API.
 *
 * @since 1.0.0
 *
 * @param string $plugin The plugin basename.
 *
 * @return bool $registered
 */
function consent_api_registered( $plugin ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	// We consider this plugin to comply ;).
	if ( strpos( $plugin, 'wp-consent-api.php' ) !== false ) {
		return true;
	}

	return apply_filters( "wp_consent_api_registered_{$plugin}", false );
}

/**
 * Wrapper function for the registration of a cookie with WordPress.
 *
 * @param string      $name                    The name of the cookie.
 * @param string      $plugin_or_service       Plugin or service that sets cookie (e.g. Google Maps).
 * @param string      $category                One of 'functional', 'preferences', 'statistics-anonymous', 'statistics', or 'marketing'.
 * @param string      $expires                 Time until the cookie expires.
 * @param string      $function                What the cookie is meant to do (e.g. 'Store a unique User ID').
 * @param string      $collected_personal_data Type of personal data that is collected. If no personal data is collected, set to false.
 * @param bool        $member_cookie           Whether the cookie is relevant for members of the site only.
 * @param bool        $administrator_cookie    Whether the cookie is relevant for administrators only.
 * @param string      $type                    One of 'HTTP', 'LOCALSTORAGE', or 'API'.
 * @param string|bool $domain             Optional. Domain on which the cookie is set. Defaults to the current site URL.
 */
function wp_add_cookie_info( $name, $plugin_or_service, $category, $expires, $function, $collected_personal_data = '', $member_cookie = false, $administrator_cookie = false, $type = 'HTTP', $domain = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	WP_Consent_API::$cookie_info->add_cookie_info( $name, $plugin_or_service, $category, $expires, $function, $collected_personal_data, $member_cookie, $administrator_cookie, $type, $domain );
}

/**
 * Wrapper function to get cookie info for one specific cookie, or for all cookies registered.
 *
 * @param string|bool $name Optional. The cookie name. Default false, which returns all cookies.
 *
 * @return array
 */
function wp_get_cookie_info( $name = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	return WP_Consent_API::$cookie_info->get_cookie_info( $name );
}

/**
 * Wrapper function to set a cookie, taking into account actual user consent, if supported by plugins
 *
 * @param string $name
 * @param string $value
 * @param string $consent_category Functional, preferences, statistics-anonymous, statistics, marketing.
 * @param int    $expires
 * @param string $path
 * @param string $domain
 * @param bool   $secure
 * @param bool   $httponly
 *
 * @return void
 */
function wp_set_cookie( $name, $value = '', $consent_category = '', $expires = 0, $path = '', $domain = '', $secure = false, $httponly = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is intended for Core.
	$name  = sanitize_text_field( $name );
	$value = sanitize_text_field( $value );

	$expires = apply_filters( 'wp_setcookie_expires', intval( $expires ), $name, $value );
	$path    = apply_filters( 'wp_setcookie_path', sanitize_text_field( $path ), $name, $value );
	$domain  = apply_filters( 'wp_setcookie_domain', sanitize_text_field( $domain ), $name, $value );

	$consent_category = apply_filters( 'wp_setcookie_category', wp_validate_consent_category( $consent_category ), $name, $value );
	if ( empty( $consent_category ) ) {
		_doing_it_wrong( 'wp_setcookie', esc_html__( 'Missing consent category. A functional, preferences, statistics-anonymous, statistics or marketing category should be passed when using wp_setcookie.', 'wp-consent-api' ), '1.0.0' );
	}

	if ( wp_has_consent( $consent_category ) ) {
		setcookie( $name, $value, $expires, $path, $domain, $secure, $httponly );
	}
}
