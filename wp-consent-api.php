<?php  // phpcs:ignore WordPress.Files.Filename.InvalidClassFileName
/**
 * This file is part of WP Consent API.
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
 *
 * @wordpress-plugin
 * Plugin Name:       WP Consent API
 * Plugin URI:        https://wordpress.org/plugins/wp-consent-api
 * Description:       Consent Level API to read and register the current consent level for cookie management and improving compliance with privacy laws.
 * Version:           2.0.0
 * Author:            WordPress Contributors
 * Author URI:        https://github.com/WordPress/wp-consent-level-api
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * License:           GPL2+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 */

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you cannot directly access this file.' );
}

if ( ! class_exists( 'WP_Consent_API' ) ) {
	/**
	 * WP_Consent_API class.
	 */
	class WP_Consent_API {
		/**
		 * Instance.
		 *
		 * @since 1.0.0
		 *
		 * @var WP_Consent_API|null
		 */
		private static $instance;

		/**
		 * Config.
		 *
		 * @var WP_Consent_API_Config
		 */
		public static $config;


		/**
		 * Site Health Checks.
		 *
		 * @var WP_Consent_API_Site_Health
		 */
		public static $site_health;

		/**
		 * Cookie info
		 *
		 * @var WP_Consent_API_Cookie_Info
		 */
		public static $cookie_info;

		/**
		 * Instantiate the class.
		 *
		 * @since 1.0.0
		 *
		 * @return WP_Consent_API
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Initialize the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function init(): void {
			self::get_instance();
		}

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function __construct() {
			$this->setup_constants();
			$this->includes();

			self::$config      = new WP_Consent_API_Config();
			self::$site_health = new WP_Consent_API_Site_Health();
			self::$cookie_info = new WP_Consent_API_Cookie_Info();
		}

		/**
		 * Define Consent API related constants.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function setup_constants() {
			define( 'WP_CONSENT_API_URL', plugin_dir_url( __FILE__ ) );
			define( 'WP_CONSENT_API_PATH', plugin_dir_path( __FILE__ ) );
			define( 'WP_CONSENT_API_PLUGIN', plugin_basename( __FILE__ ) );
			define( 'WP_CONSENT_API_VERSION', '2.0.0' );
			define( 'WP_CONSENT_API_PLUGIN_FILE', __FILE__ );
		}

		/**
		 * Include the extra plugin files.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function includes() {
			require_once WP_CONSENT_API_PATH . 'inc/class-wp-consent-api-config.php';
			require_once WP_CONSENT_API_PATH . 'inc/class-wp-consent-api-cookie-info.php';
			require_once WP_CONSENT_API_PATH . 'inc/class-wp-consent-api-site-health.php';
			require_once WP_CONSENT_API_PATH . 'inc/api-functions.php';
			require_once WP_CONSENT_API_PATH . 'inc/wordpress-comments-functions.php';
		}
	}

	/**
	 * Load the plugins main class.
	 */
	add_action( 'plugins_loaded', array( WP_Consent_API::class, 'init' ), 9 );
}
