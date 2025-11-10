<?php // phpcs:ignore WordPress.Files.Filename.InvalidClassFileName
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

if ( ! class_exists( 'WP_Consent_API_Cookie_Info' ) ) {
	/**
	 * A class implementing the WP Consent API cookie handling.
	 *
	 * @since 1.0.0
	 */
	class WP_Consent_API_Cookie_Info {
		/**
		 * An array of information about registered cookies.
		 *
		 * @var array
		 */
		public array $registered_cookies = [];

		/**
		 * The Singleton.
		 *
		 * @var self|null
		 */
		private static $instance;

		/**
		 * Creates a new instance.
		 */
		public function __construct() {
			if ( isset( self::$instance ) ) {
				// translators: %s the name of the PHP Class used.
				wp_die( esc_html( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'wp-consent-api' ), get_class( $this ) ) ) );
			}

			self::$instance = $this;
		}

		/**
		 * Retrieves the current instance.
		 *
		 * @return self
		 */
		public static function this() {
			return self::$instance;
		}

		/**
		 * Wrapper function for the registration of a cookie with WordPress.
		 *
		 * @param string $name                    The name of the cookie.
		 * @param string $plugin_or_service       Plugin or service that sets cookie (e.g. Google Maps).
		 * @param string $category                One of 'functional', 'preferences', 'statistics-anonymous', 'statistics', or 'marketing'.
		 * @param string $expires                 Time until the cookie expires.
		 * @param string $function                What the cookie is meant to do (e.g. 'Store a unique User ID').
		 * @param string $collected_personal_data Type of personal data that is collected. Only needs to be filled in if `$is_personal_data` is `true`.
		 * @param bool   $member_cookie           Whether the cookie is relevant for members of the site only.
		 * @param bool   $administrator_cookie    Whether the cookie is relevant for administrators only.
		 * @param string $type                    One of 'HTTP', 'LOCALSTORAGE', or 'API'.
		 * @param string $domain                  Optional. Domain on which the cookie is set. Defaults to the current site URL.
		 */
		public function add_cookie_info( string $name, string $plugin_or_service, string $category, string $expires, string $function, string $collected_personal_data = '', bool $member_cookie = false, bool $administrator_cookie = false, string $type = 'HTTP', string $domain = '' ): void {

			// If the domain is not passed, we assume it's first party, from this domain.
			if ( empty( $domain ) ) {
				$domain = site_url();
			}

			$this->registered_cookies[ $name ] = array(
				'plugin_or_service'     => sanitize_text_field( $plugin_or_service ),
				'category'              => wp_validate_consent_category( $category ),
				'expires'               => sanitize_text_field( $expires ),
				'function'              => sanitize_text_field( $function ),
				'collectedPersonalData' => sanitize_text_field( $collected_personal_data ),
				'memberCookie'          => (bool) $member_cookie,
				'administratorCookie'   => (bool) $administrator_cookie,
				'domain'                => esc_url_raw( $domain ),
				'type'                  => sanitize_text_field( $type ),
			);
		}

		/**
		 * Get list of registered services
		 *
		 * @param bool $skip_admin_cookies
		 *
		 * @return array
		 */
		public function get_services( bool $skip_admin_cookies = false ): array {
			$services = array();
			$cookies = $this->registered_cookies;

			//filter out all administratorCookie cookies
			if ( $skip_admin_cookies ) {
				$cookies = array_filter( $cookies, static function ( $cookie ) {
					return ! $cookie['administratorCookie'];
				} );
			}

			foreach ( $cookies as $cookie ) {
				$services[] = $cookie['plugin_or_service'] ?? 'general';
			}
			return array_unique( $services );
		}

		/**
		 * Get the category for this service
		 * As each service can have cookies with different categories, we check all categories, and return the one with most privacy impact
		 * e.g. if it has marketing and functional, we return marketing
		 *
		 * @param string $service
		 *
		 * @return string
		 */
		public function get_service_category( string $service ): string {
			$categories = [];
			foreach ( $this->registered_cookies as $cookie ) {
				if ( $cookie['plugin_or_service'] === $service ) {
					$categories[] = $cookie['category'] ?? 'marketing';
				}
			}
			$categories = array_unique( $categories );
			$available_categories = WP_Consent_API::$config->consent_categories();
			//reverse order of $available_categories
			$available_categories = array_reverse( $available_categories );

			//find the first category that is in the list of categories for this service.
			foreach ( $available_categories as $available_category ) {
				if ( in_array( $available_category, $categories, true ) ) {
					return $available_category;
				}
			}

			//nothing found, assume the worst.
			return 'marketing';
		}


		/**
		 * Get cookie info for one specific cookie, or for all cookies registered.
		 *
		 * @param string $name The name of the cookie.
		 *
		 * @return array
		 */
		public function get_cookie_info( string $name = '' ): array {
			if ( !empty( $name ) && isset( $this->registered_cookies[ $name ] ) ) {
				return $this->registered_cookies[ $name ];
			}

			return $this->registered_cookies;
		}

		/**
		 * List of services for use in clientside javascript part
		 *
		 * @return array
		 */
		public function get_service_info(): array {
			//skip admin cookies, as these are considered functional.
			$services = $this->get_services( true );

			$js_array = [];
			foreach ( $services as $service ) {
				$js_array[] = [
					'name' => $service,
					'category' => $this->get_service_category( $service )
				];
			}

			return $js_array;
		}
	}
}
