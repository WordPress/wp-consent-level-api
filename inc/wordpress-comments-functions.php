<?php // phpcs:ignore -- Ignore the  "\r\n" notice for some machines.
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

/**
 * Instead of listening to the consent checkbox, this way we check for the "preferences" consent.
 *
 * The consent checkbox is removed if preferences is set, the comments cookies function gets
 * passed a $cookies_consent=true, so cookies can be set. Otherwise, it's false.
 */

/**
 * First we remove the default comment cookies action, and replace with our own
 * we add custom comment cookies action, which checks the consent, then calls the comment cookie function in wp
 */
function wp_consent_api_wordpress_comments_cookies() {
	// Remove default wp action.
	remove_action( 'set_comment_cookies', 'wp_set_comment_cookies', 10 );

	// Add our own custom action.
	add_action( 'set_comment_cookies', 'wp_consent_api_set_comment_cookies', 10, 3 );

	// Remove checkbox.
	add_filter( 'comment_form_default_fields', 'wp_consent_api_wordpress_comment_form_hide_cookies_consent' );
}
add_action( 'init', 'wp_consent_api_wordpress_comments_cookies' );

/**
 * Custom consent function, checking consent level to decide if cookies can be set.
 *
 * @since 1.0.0
 *
 * @param WP_Comment $comment         Comment object.
 * @param WP_User    $user            Comment author's user object. The user may not exist.
 * @param bool       $cookies_consent Comment author's consent to store cookies.
 */
function wp_consent_api_set_comment_cookies( $comment, $user, $cookies_consent ) {
	$cookies_consent = wp_has_consent( 'preferences' );
	wp_set_comment_cookies( $comment, $user, $cookies_consent );
}

/**
 * Remove consent checkbox.
 *
 * @since 1.0.0
 *
 * @param string[] $fields The comment fields.
 *
 * @return string[] $fields
 */
function wp_consent_api_wordpress_comment_form_hide_cookies_consent( $fields ) {
	unset( $fields['cookies'] );
	return $fields;
}
