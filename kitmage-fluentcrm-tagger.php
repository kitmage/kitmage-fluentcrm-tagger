<?php
/**
 * Plugin Name:       KitMage FluentCRM Tagger
 * Description:       Adds or removes FluentCRM tags for logged-in contacts using URL query parameters.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            KitMage
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kitmage-fluentcrm-tagger
 *
 * @package KitMage_FluentCRM_Tagger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies URL-requested FluentCRM tag actions to the current contact.
 *
 * Add a tag with `?fcrm_tag=4` and remove a tag with `?fcrm_untag=4`.
 * When both parameters are present, the add action runs before the remove action.
 *
 * @return void
 */
function kitmage_fluentcrm_tagger_handle_url_action() {
	// These actions are only intended for authenticated frontend visitors.
	if ( is_admin() || ! is_user_logged_in() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The query parameters intentionally provide a user-facing URL action.
	if ( empty( $_GET['fcrm_tag'] ) && empty( $_GET['fcrm_untag'] ) ) {
		return;
	}

	if ( ! function_exists( 'fluentcrm_get_current_contact' ) ) {
		return;
	}

	$contact = fluentcrm_get_current_contact();

	if ( ! $contact ) {
		return;
	}

	try {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See the URL-action explanation above.
		$tag_id = isset( $_GET['fcrm_tag'] ) && is_scalar( $_GET['fcrm_tag'] )
			? absint( wp_unslash( $_GET['fcrm_tag'] ) )
			: 0;

		if ( $tag_id > 0 ) {
			$contact->attachTags( array( $tag_id ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See the URL-action explanation above.
		$untag_id = isset( $_GET['fcrm_untag'] ) && is_scalar( $_GET['fcrm_untag'] )
			? absint( wp_unslash( $_GET['fcrm_untag'] ) )
			: 0;

		if ( $untag_id > 0 ) {
			$contact->detachTags( array( $untag_id ) );
		}
	} catch ( Throwable $exception ) {
		// FluentCRM failures must not interrupt the frontend request.
		return;
	}
}
add_action( 'template_redirect', 'kitmage_fluentcrm_tagger_handle_url_action', 1 );
