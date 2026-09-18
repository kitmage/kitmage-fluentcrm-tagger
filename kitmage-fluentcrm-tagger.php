<?php
/**
 * Plugin Name:       KitMage FluentCRM Tagger
 * Description:       Adds or removes FluentCRM tags for logged-in contacts using URL query parameters.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author: Mike@KitMage
 * Author URI: http://kitmage.com
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

/**
 * Renders shortcode content according to the current contact's FluentCRM tags.
 *
 * @param array       $attributes Shortcode attributes.
 * @param string|null $content    Enclosed shortcode content.
 * @return string
 */
function kitmage_fluentcrm_tagger_render_restricted_content( $attributes, $content = null ) {
	$attributes = shortcode_atts(
		array(
			'tag_id'   => '',
			'mode'     => 'show',
			'fallback' => 'hide',
		),
		$attributes,
		'crm_restrict'
	);

	$expression = trim( (string) $attributes['tag_id'] );
	$mode       = strtolower( trim( (string) $attributes['mode'] ) );
	$fallback   = strtolower( trim( (string) $attributes['fallback'] ) );

	if ( ! in_array( $mode, array( 'show', 'hide' ), true ) ) {
		$mode = 'show';
	}

	if ( ! in_array( $fallback, array( 'show', 'hide' ), true ) ) {
		$fallback = 'hide';
	}

	// An empty expression does not restrict the enclosed content.
	if ( '' === $expression ) {
		return do_shortcode( (string) $content );
	}

	$matches       = kitmage_fluentcrm_tagger_current_contact_matches( $expression, $fallback );
	$should_render = 'hide' === $mode ? ! $matches : $matches;

	return $should_render ? do_shortcode( (string) $content ) : '';
}
add_shortcode( 'crm_restrict', 'kitmage_fluentcrm_tagger_render_restricted_content' );

/**
 * Determines whether the current FluentCRM contact matches a tag expression.
 *
 * @param string $expression Tag expression to evaluate.
 * @param string $fallback   Whether an unavailable contact should match: show or hide.
 * @return bool
 */
function kitmage_fluentcrm_tagger_current_contact_matches( $expression, $fallback = 'hide' ) {
	$fallback_match = 'show' === $fallback;

	if ( ! is_user_logged_in() || ! function_exists( 'fluentcrm_get_current_contact' ) ) {
		return $fallback_match;
	}

	$contact = fluentcrm_get_current_contact();

	if ( ! $contact ) {
		return $fallback_match;
	}

	$tag_ids = array();

	try {
		// Explicitly load the relationship because FluentCRM does not always preload it.
		if ( method_exists( $contact, 'tags' ) ) {
			$tags = $contact->tags()->get();
		} elseif ( ! empty( $contact->tags ) ) {
			$tags = $contact->tags;
		} else {
			$tags = array();
		}

		if ( is_array( $tags ) || $tags instanceof Traversable ) {
			foreach ( $tags as $tag ) {
				if ( isset( $tag->id ) ) {
					$tag_ids[ (int) $tag->id ] = true;
				}
			}
		}
	} catch ( Throwable $exception ) {
		return $fallback_match;
	}

	return kitmage_fluentcrm_tagger_evaluate_expression( $expression, $tag_ids );
}

/**
 * Evaluates an OR/AND/NOT tag expression against a tag ID lookup table.
 *
 * Commas separate OR groups, plus signs combine AND terms, and an exclamation
 * mark negates a term. For example, `3,4+5,!6` matches tag 3, both tags 4 and
 * 5, or the absence of tag 6.
 *
 * @param string $expression Tag expression to evaluate.
 * @param array  $tag_ids    Tag IDs keyed by integer ID.
 * @return bool
 */
function kitmage_fluentcrm_tagger_evaluate_expression( $expression, array $tag_ids ) {
	$expression = html_entity_decode( (string) $expression, ENT_QUOTES, 'UTF-8' );
	$expression = preg_replace( '/\s+/', '', $expression );
	$expression = str_replace( '&', '+', $expression );

	if ( empty( $expression ) ) {
		return false;
	}

	$or_groups = array_filter( explode( ',', $expression ), 'strlen' );

	foreach ( $or_groups as $group ) {
		$and_terms = array_filter( explode( '+', $group ), 'strlen' );

		if ( empty( $and_terms ) ) {
			continue;
		}

		$group_matches = true;

		foreach ( $and_terms as $term ) {
			$negated = isset( $term[0] ) && '!' === $term[0];

			if ( $negated ) {
				$term = substr( $term, 1 );
			}

			if ( '' === $term || ! ctype_digit( $term ) ) {
				$group_matches = false;
				break;
			}

			$has_tag = isset( $tag_ids[ (int) $term ] );

			if ( $negated ) {
				$has_tag = ! $has_tag;
			}

			if ( ! $has_tag ) {
				$group_matches = false;
				break;
			}
		}

		if ( $group_matches ) {
			return true;
		}
	}

	return false;
}

/**
 * Redirects matching FluentCRM contacts to a configured destination.
 *
 * @param array $attributes Shortcode attributes.
 * @return string Redirect fallback markup, or an empty string.
 */
function kitmage_fluentcrm_tagger_render_redirect( $attributes ) {
	$attributes = shortcode_atts(
		array(
			'tag_id'      => '',
			'destination' => '',
			'status'      => '302',
		),
		$attributes,
		'crm_tag_redirect'
	);

	$expression  = trim( (string) $attributes['tag_id'] );
	$destination = trim( (string) $attributes['destination'] );
	$status      = 301 === (int) $attributes['status'] ? 301 : 302;

	if ( '' === $expression || '' === $destination || ! is_user_logged_in() ) {
		return '';
	}

	if ( ! kitmage_fluentcrm_tagger_current_contact_matches( $expression ) ) {
		return '';
	}

	// Site-relative paths are resolved against the WordPress home URL.
	if ( 0 === strpos( $destination, '/' ) && 0 !== strpos( $destination, '//' ) ) {
		$destination = home_url( $destination );
	}

	$destination = esc_url_raw( $destination, array( 'http', 'https' ) );

	if ( empty( $destination ) ) {
		return '';
	}

	$host        = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $host . $request_uri;
	$current_url = preg_replace( '#\?.*$#', '', $current_url );
	$target_url  = preg_replace( '#\?.*$#', '', $destination );

	if ( untrailingslashit( $current_url ) === untrailingslashit( $target_url ) ) {
		return '';
	}

	if ( ! headers_sent() ) {
		wp_safe_redirect( $destination, $status );
		exit;
	}

	$destination_json = wp_json_encode( $destination );

	if ( false === $destination_json ) {
		return '';
	}

	return sprintf(
		'<script>window.location.replace(%1$s);</script><noscript><meta http-equiv="refresh" content="%2$s"><p><a href="%3$s">%4$s</a></p></noscript>',
		$destination_json,
		esc_attr( '0;url=' . $destination ),
		esc_url( $destination ),
		esc_html__( 'Continue', 'kitmage-fluentcrm-tagger' )
	);
}
add_shortcode( 'crm_tag_redirect', 'kitmage_fluentcrm_tagger_render_redirect' );
