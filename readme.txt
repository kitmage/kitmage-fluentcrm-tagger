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

= Restricting content =

Use the `crm_restrict` shortcode to conditionally display enclosed content based
on the current contact's tags:

`[crm_restrict tag_id="3,4+5,!6"]Secret content[/crm_restrict]`

The expression operators are:

* `,` means OR: `3,4` matches contacts with tag 3 or tag 4.
* `+` means AND: `4+5` matches contacts with both tags.
* `!` means NOT: `!6` matches contacts without tag 6.

The `mode` attribute defaults to `show`. Set `mode="hide"` to hide the content
when the expression matches instead. The optional `fallback` attribute controls
what happens when the visitor is logged out, FluentCRM is unavailable, or no
contact exists. Its default is `hide`; set `fallback="show"` to treat those cases
as a match.

An empty `tag_id` expression always renders the enclosed content. Nested
shortcodes in rendered content are processed normally.

= Redirecting contacts =

Use `crm_tag_redirect` to redirect a logged-in contact when their tags match an
expression:

`[crm_tag_redirect tag_id="3,4&5,!6" destination="/contact/reach/reach-confirmation/"]`

This shortcode uses the same expression rules as `crm_restrict`; both `&` and
`+` may be used for AND. The required `destination` may be a site-relative path
or an HTTP(S) URL. The optional `status` is `302` by default and may be set to
`301`.

The shortcode prevents a redirect when the current URL is already the
destination. It uses a safe HTTP redirect if headers are still available. If
page output has already started, it returns a JavaScript redirect with a
`noscript` refresh and link fallback. Place the shortcode as early as possible
in the page content or template to maximize the chance of an HTTP redirect.

Only numeric tag IDs are supported. Parentheses and nested expression logic are
not supported. External HTTP(S) destinations must also be permitted by
WordPress's safe redirect host policy.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **KitMage FluentCRM Tagger** in the WordPress Plugins screen.
3. Make sure FluentCRM is active and logged-in WordPress users are represented as FluentCRM contacts.
4. Add `fcrm_tag` or `fcrm_untag` with a positive FluentCRM tag ID to a frontend URL.
5. Optionally wrap content in a `crm_restrict` shortcode to show or hide it by tag expression.
6. Optionally add a `crm_tag_redirect` shortcode to redirect contacts whose tags match.

== Changelog ==

= 1.0.0 =
* Initial release.
* Add the `crm_restrict` conditional-content shortcode.
* Add the `crm_tag_redirect` conditional-redirect shortcode.
