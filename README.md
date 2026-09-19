# KitMage FluentCRM Tagger

KitMage FluentCRM Tagger connects logged-in WordPress users to their FluentCRM contact record. With it, you can:

- add or remove a FluentCRM tag when someone follows a link;
- render Smart Links-style buttons that tag/untag and then redirect;
- show or hide page content based on a contact's tags; and
- redirect contacts based on their tags.

The plugin has no settings screen. You configure it with URL parameters and WordPress shortcodes.

## Requirements

- WordPress 5.2 or later
- PHP 7.0 or later
- FluentCRM
- A FluentCRM contact associated with each WordPress user who will use the links or shortcodes

All tag values in the examples are numeric FluentCRM tag IDs, not tag names. Replace the example domain, paths, and IDs with your own values.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. In WordPress, go to **Plugins > Installed Plugins**.
3. Activate **KitMage FluentCRM Tagger**.
4. Confirm that FluentCRM is active and that the WordPress users involved have corresponding FluentCRM contacts.

## Add and remove tags with links

Tag-action links work on frontend pages for logged-in users. They update only the FluentCRM contact associated with the user who follows the link.

### Add a tag

Use the `fcrm_tag` query parameter to add a tag. This example adds tag `4`:

```text
https://example.com/resources/?fcrm_tag=4
```

You could use this as the destination of a button that lets a member opt into a topic:

```html
<a href="https://example.com/resources/?fcrm_tag=4">Follow product updates</a>
```

### Remove a tag

Use the `fcrm_untag` query parameter to remove a tag. This example removes tag `4`:

```text
https://example.com/preferences/?fcrm_untag=4
```

For example:

```html
<a href="https://example.com/preferences/?fcrm_untag=4">Stop product updates</a>
```

### Add and remove tags in one visit

Include both parameters to change two tags at once:

```text
https://example.com/welcome/?fcrm_tag=8&fcrm_untag=4
```

The plugin adds tag `8` first and then removes tag `4`. If both parameters contain the same ID, that tag will be removed at the end of the request.

> **Important:** Visiting one of these URLs changes contact data. Share action links only with people who should be able to perform that action on their own contact record.

Tag actions are ignored when the visitor is logged out, the request is for a WordPress admin page, FluentCRM is unavailable, the user has no FluentCRM contact, or the tag ID is not a positive number.

## Tag-action buttons

The plugin also includes the Aspen Smart Links button behavior. Existing crm_tag_button shortcode syntax can be used without changing your page content.

Add a tag and continue to another page:

    [crm_tag_button text="Next Lesson" action="add" tag_id="12" url="/lesson-2/"]

Remove a tag:

    [crm_tag_button text="Leave Program" action="remove" tag_id="12" url="/account/"]

You may add one or more CSS classes with the class attribute:

    [crm_tag_button text="Continue" action="add" tag_id="12" url="/next/" class="button button-primary"]

For internal URLs, the tag action is processed and the current tab redirects to the destination. For external HTTP/HTTPS URLs, the external destination is opened in a new tab while the current tab processes the tag action and returns to the current page.

Button actions are nonce-protected, only render for logged-in users, prevent double-submission in JavaScript, and fail silently if FluentCRM or the current contact is unavailable.

Migration note: the shortcode name, asl_* request fields, AspenSmartLinks JavaScript object, and aspen_smart_links_tag_action / aspen_smart_links_handle_tag_action hooks are preserved for compatibility. The standalone Smart Links user-meta fallback and automatic contact-creation behavior are intentionally not included; FluentCRM remains the source of truth in this plugin.

## Show or hide content by tag

Wrap content in the `[crm_restrict]` shortcode to control whether it appears. Shortcodes can be added in a Shortcode block, the classic editor, or another area that processes WordPress shortcodes.

### Show content to contacts with a tag

This content appears only when the current contact has tag `3`:

```text
[crm_restrict tag_id="3"]
Download your member guide.
[/crm_restrict]
```

### Hide content from contacts with a tag

Set `mode="hide"` to reverse the result. This prompt appears only when the contact does **not** have tag `3`:

```text
[crm_restrict tag_id="3" mode="hide"]
Join the member program to unlock the guide.
[/crm_restrict]
```

The default mode is `show`.

### Combine tag conditions

Use these operators in `tag_id`:

| Operator | Meaning | Example |
| --- | --- | --- |
| `,` | OR | `3,4` matches tag `3` or tag `4` |
| `+` | AND | `4+5` matches contacts with both tag `4` and tag `5` |
| `!` | NOT | `!6` matches contacts that do not have tag `6` |

You can combine the operators. This example appears when a contact has tag `3`, has both tags `4` and `5`, **or** does not have tag `6`:

```text
[crm_restrict tag_id="3,4+5,!6"]
Content for the matching audience.
[/crm_restrict]
```

Each comma-separated group is an alternative. Every `+`-separated condition within a group must match. Parentheses and nested expressions are not supported.

### Choose what happens when contact data is unavailable

By default, restricted content stays hidden when the visitor is logged out, FluentCRM is unavailable, or the current user has no FluentCRM contact. Set `fallback="show"` to display it in those cases:

```text
[crm_restrict tag_id="12" fallback="show"]
This is visible to contacts with tag 12 and to visitors whose contact data cannot be checked.
[/crm_restrict]
```

The fallback value acts as the expression result before `mode` is applied. For example, `mode="hide" fallback="show"` hides the content when contact data is unavailable.

An empty `tag_id` does not restrict content. Any shortcodes inside content that is displayed are processed normally.

## Redirect contacts by tag

Use `[crm_tag_redirect]` to send a logged-in contact to another location when their tags match. Place this shortcode as early as possible in the page content or template.

### Redirect to a page on your site

This example redirects contacts with tag `3` to a confirmation page:

```text
[crm_tag_redirect tag_id="3" destination="/contact/reach/reach-confirmation/"]
```

A destination beginning with a single `/` is resolved relative to your WordPress home URL.

### Redirect using multiple conditions

Redirect contacts who have both tag `4` and tag `5`, or who do not have tag `6`:

```text
[crm_tag_redirect tag_id="4+5,!6" destination="/member-dashboard/"]
```

Redirect expressions use the same `,`, `+`, and `!` rules as `[crm_restrict]`. The `&` character is also accepted as AND, although `+` is safer in shortcode content:

```text
[crm_tag_redirect tag_id="4&5" destination="/member-dashboard/"]
```

### Redirect to a full URL

You may provide an HTTP or HTTPS URL:

```text
[crm_tag_redirect tag_id="9" destination="https://members.example.com/start/"]
```

External destinations are subject to WordPress's safe redirect host policy, so use a host that your WordPress installation allows.

### Make the redirect permanent

Redirects use HTTP status `302` by default. Use `status="301"` only when the redirect should be permanently cached:

```text
[crm_tag_redirect tag_id="9" destination="/new-home/" status="301"]
```

The shortcode does nothing for logged-out visitors, missing contacts, nonmatching tags, missing attributes, or a destination that is already the current page. When possible, it sends a normal HTTP redirect. If page output has already begun, it returns a JavaScript redirect with a no-JavaScript fallback instead.

## Common setup patterns

### Opt in, then reveal content

1. Add an opt-in link to a page:

   ```html
   <a href="https://example.com/course/?fcrm_tag=20">Unlock the course</a>
   ```

2. Restrict the course content to tag `20`:

   ```text
   [crm_restrict tag_id="20"]
   Welcome to the course.
   [/crm_restrict]
   ```

After a logged-in contact follows the link, the page loads with tag `20` attached and the restricted content can appear.

### Route different audiences

Add a redirect at the start of a general landing page:

```text
[crm_tag_redirect tag_id="30" destination="/customers/"]
[crm_tag_redirect tag_id="31" destination="/partners/"]
```

Contacts with tag `30` go to the customer page. Contacts who do not match the first rule can then be evaluated by the second rule.

## Troubleshooting

- **A link does not update a tag:** Confirm that the visitor is logged in, FluentCRM is active, the user has a FluentCRM contact, and the URL contains a positive numeric tag ID.
- **Restricted content never appears:** Check the contact's assigned tags and verify that you used tag IDs rather than names. The default behavior is to hide content if contact data is unavailable.
- **A redirect does not run:** Confirm that `tag_id` and `destination` are present, the contact matches the expression, and the destination is allowed by WordPress. Put the shortcode earlier in the page to improve the chance of an HTTP redirect.
- **A URL already has query parameters:** Add the first plugin parameter with `&` instead of another `?`, for example `https://example.com/page/?source=email&fcrm_tag=4`.

## License

KitMage FluentCRM Tagger is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).
