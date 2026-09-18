=== KitMage FluentCRM Tagger ===
Contributors: kitmage
Tags: fluentcrm, tags, automation, url
Requires at least: 5.2
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add or remove FluentCRM tags for logged-in contacts through simple URL parameters.

== Description ==

KitMage FluentCRM Tagger lets links apply a FluentCRM tag action to the currently
logged-in contact. It has no settings screen.

To add tag ID 4, link to any frontend page with this query parameter:

`https://example.com/some-page/?fcrm_tag=4`

To remove tag ID 4:

`https://example.com/some-page/?fcrm_untag=4`

Both parameters may be supplied in one request. The tag is added first, then the
tag specified by `fcrm_untag` is removed.

Requests are ignored when the visitor is not logged in, FluentCRM is unavailable,
or the current WordPress user has no FluentCRM contact.

Because visiting one of these URLs changes contact data, only share action links
with people who should be able to perform the corresponding action on their own
contact record.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **KitMage FluentCRM Tagger** in the WordPress Plugins screen.
3. Make sure FluentCRM is active and logged-in WordPress users are represented as FluentCRM contacts.
4. Add `fcrm_tag` or `fcrm_untag` with a positive FluentCRM tag ID to a frontend URL.

== Changelog ==

= 1.0.0 =
* Initial release.
