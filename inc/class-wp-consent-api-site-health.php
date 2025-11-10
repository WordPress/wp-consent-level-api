<?php // phpcs:ignore -- Ignore the wrong filename (class- prefix) & "\r\n" notice for some machines.
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

if ( ! class_exists( 'WP_Consent_API_Site_Health' ) ) {
	/**
	 * WP_Consent_API class.
	 */
	class WP_Consent_API_Site_Health {
		/**
		 * Instance.
		 *
		 * @since 1.0.0
		 *
		 * @var WP_Consent_API_Site_Health|null
		 */
		private static $instance;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( isset( self::$instance ) ) {
				// translators: %s the name of the PHP Class used.
				wp_die( esc_html( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'wp-consent-api' ), get_class( $this ) ) ) );
			}

			add_filter( 'site_status_tests', array( $this, 'consent_api_integration_check' ) );

			self::$instance = $this;
		}

		/**
		 * Attach the WP Consent API Site Health tests.
		 *
		 * @since 1.0.0
		 *
		 * @param array $tests The Site Health tests.
		 * @return array
		 */
		public function consent_api_integration_check( $tests ) {
			$tests['direct']['wp-consent-api'] = array(
				'label' => __( 'WP Consent API test', 'wp-consent-api' ),
				'test'  => array( $this, 'wp_consent_api_test' ),
			);

			return $tests;
		}

		/**
		 * Run the WP Consent API Site Health tests.
		 *
		 * @since 1.0.0
		 *
		 * @return array $result The WP Consent API Site Health tests results.
		 */
		public function wp_consent_api_test() {
			$plugins                      = get_option( 'active_plugins' );
			$not_registered               = array();
			$plugins_without_registration = false;

			foreach ( $plugins as $plugin ) {
				if ( ! consent_api_registered( $plugin ) ) {
					$not_registered[]             = $plugin;
					$plugins_without_registration = true;
				}
			}

			$result = array(
				'label'       => __( 'All plugins have declared to use the Consent API', 'wp-consent-api' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Compliance', 'wp-consent-api' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'All plugins have declared in their code that they are following the guidelines from the WP Consent API. When used in combination with a Cookie Management plugin, this will improve compliancy for your site.', 'wp-consent-api' )
				),
				'actions'     => '',
				'test'        => 'wp-consent-api',
			);

			if ( $plugins_without_registration ) {
				$result['status']      = 'recommended';
				$result['label']       = __( 'One or more plugins are not conforming to the Consent API.', 'wp-consent-api' );
				$result['description'] = '<span class="title">' . __( 'Not all plugins have declared to follow Consent API guidelines. Please contact the developer.', 'wp-consent-api' ) . '</span>';
				$result['actions']     = '<p>' . implode( '<p>', $not_registered );
			}

			return $result;
		}
	}
}
