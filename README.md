# KitMage FluentCRM Tagger

KitMage FluentCRM Tagger adds a small set of frontend tools for working with the current logged-in user's FluentCRM tags.

It can:

- add or remove FluentCRM tags from ordinary URLs;
- render buttons that add or remove a tag and then redirect;
- show or hide content based on FluentCRM tag conditions; and
- redirect users based on FluentCRM tag conditions.

The plugin has no settings screen. Everything is configured with URL query parameters and WordPress shortcodes.

## Requirements

- WordPress 5.2 or later
- PHP 7.0 or later
- FluentCRM
- A FluentCRM contact associated with the logged-in WordPress user

All tag values are numeric FluentCRM **tag IDs**, not tag names.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **KitMage FluentCRM Tagger** in WordPress.
3. Confirm FluentCRM is active.
4. Confirm the WordPress users who will use these features have corresponding FluentCRM contacts.

## Feature reference

| Feature | Syntax |
| --- | --- |
| Add tag from URL | `?fcrm_tag=4` |
| Remove tag from URL | `?fcrm_untag=4` |
| Tag-action button | `[crm_tag_button ...]` |
| Conditional content | `[crm_restrict ...]...[/crm_restrict]` |
| Conditional redirect | `[crm_tag_redirect ...]` |

## Add or remove tags with URLs

Tag actions run for logged-in frontend visitors and affect only the current user's FluentCRM contact.

### Add a tag

```text
https://example.com/resources/?fcrm_tag=4
```

This adds FluentCRM tag `4`.

### Remove a tag

```text
https://example.com/preferences/?fcrm_untag=4
```

This removes FluentCRM tag `4`.

### Add and remove tags in one request

```text
https://example.com/welcome/?fcrm_tag=8&fcrm_untag=4
```

The add action runs first, followed by the remove action.

If both parameters contain the same tag ID, the tag will be removed at the end of the request.

### Existing query strings

If the destination already contains a query string, append the FluentCRM parameter with `&`:

```text
https://example.com/page/?source=email&fcrm_tag=4
```

### URL-action behavior

URL actions are silently ignored when:

- the visitor is logged out;
- the request is for a WordPress admin page;
- FluentCRM is unavailable;
- the current user has no FluentCRM contact; or
- the supplied tag ID is not a positive integer.

These URL parameters intentionally act as frontend actions on the **current logged-in user's own contact record**.

## Tag-action buttons

Use `[crm_tag_button]` to render a button that adds or removes a FluentCRM tag and then continues to another URL.

### Add a tag and continue

```text
[crm_tag_button text="Next Lesson" action="add" tag_id="12" url="/lesson-2/"]
```

### Remove a tag and continue

```text
[crm_tag_button text="Leave Program" action="remove" tag_id="12" url="/account/"]
```

### Add CSS classes

```text
[crm_tag_button text="Continue" action="add" tag_id="12" url="/next/" class="button button-primary"]
```

### Attributes

| Attribute | Required | Description |
| --- | --- | --- |
| `text` | No | Button label. Defaults to `Continue`. |
| `action` | Yes | `add` or `remove`. |
| `tag_id` | Yes | Numeric FluentCRM tag ID. |
| `url` | No | Destination. Defaults to `/`. |
| `class` | No | Space-separated CSS classes added to the button. |

### Internal destinations

For a site-relative destination:

```text
[crm_tag_button text="Continue" action="add" tag_id="12" url="/member-dashboard/"]
```

The plugin:

1. submits the button action;
2. updates the current contact's FluentCRM tag;
3. redirects the current tab to the destination.

### External destinations

For an external HTTP or HTTPS URL:

```text
[crm_tag_button text="Open Resource" action="add" tag_id="12" url="https://example.org/resource/"]
```

The external URL is opened in a new tab while the current tab processes the tag action and returns to the current page.

Opening the new tab is best-effort and remains subject to browser popup behavior.

### Button behavior

Tag-action buttons:

- render only for logged-in users;
- use a WordPress nonce for the tag-action request;
- prevent accidental double submission in JavaScript;
- display `Loading...` after submission; and
- fail silently if FluentCRM or the current contact is unavailable.

## Show or hide content by tag

Use `[crm_restrict]` to conditionally render enclosed content.

### Show content when a tag is present

```text
[crm_restrict tag_id="3"]
Download your member guide.
[/crm_restrict]
```

The default `mode` is `show`.

### Hide content when a tag is present

```text
[crm_restrict tag_id="3" mode="hide"]
This appears only when the user does not have tag 3.
[/crm_restrict]
```

### Fallback behavior

By default, restricted content is hidden when the plugin cannot evaluate the current contact.

You can change that with `fallback="show"`:

```text
[crm_restrict tag_id="12" fallback="show"]
Visible to tag 12 and when contact data cannot be checked.
[/crm_restrict]
```

The fallback value acts as the expression result **before** `mode` is applied.

For example:

```text
[crm_restrict tag_id="12" mode="hide" fallback="show"]
...
[/crm_restrict]
```

will hide the content when contact data is unavailable.

### Attributes

| Attribute | Default | Description |
| --- | --- | --- |
| `tag_id` | empty | Tag expression to evaluate. |
| `mode` | `show` | `show` renders matching content; `hide` renders nonmatching content. |
| `fallback` | `hide` | Expression result when the current contact cannot be evaluated. |

An empty `tag_id` does not restrict the content.

Nested shortcodes inside displayed content are processed normally.

## Tag expressions

Both `[crm_restrict]` and `[crm_tag_redirect]` support tag expressions.

| Operator | Meaning | Example |
| --- | --- | --- |
| `,` | OR | `3,4` |
| `+` | AND | `4+5` |
| `!` | NOT | `!6` |

Examples:

```text
3,4
```

Matches tag `3` **or** tag `4`.

```text
4+5
```

Matches contacts that have **both** tags `4` and `5`.

```text
4+!24
```

Matches contacts that have tag `4` and do **not** have tag `24`.

```text
3,4+5,!6
```

Matches any of these conditions:

- tag `3`;
- both tags `4` and `5`; or
- absence of tag `6`.

Each comma-separated group is an OR condition. Every `+`-separated term inside a group must match.

The `&` character is also accepted as AND internally, but `+` is recommended in shortcode attributes.

Parentheses and nested expressions are not supported.

## Redirect users by tag

Use `[crm_tag_redirect]` to redirect a logged-in user when their FluentCRM tags match an expression.

### Basic redirect

```text
[crm_tag_redirect tag_id="3" destination="/member-dashboard/"]
```

### Redirect using multiple conditions

```text
[crm_tag_redirect tag_id="4+5,!6" destination="/member-dashboard/"]
```

### Full URL

```text
[crm_tag_redirect tag_id="9" destination="https://members.example.com/start/"]
```

External destinations are subject to WordPress safe-redirect host rules.

### HTTP status

Redirects use HTTP `302` by default.

Use `status="301"` only when the redirect should be permanent:

```text
[crm_tag_redirect tag_id="9" destination="/new-home/" status="301"]
```

### Attributes

| Attribute | Default | Description |
| --- | --- | --- |
| `tag_id` | empty | Tag expression to evaluate. |
| `destination` | empty | Site-relative or HTTP/HTTPS destination. |
| `status` | `302` | `302` or `301`. |

The shortcode does nothing when:

- the visitor is logged out;
- the current FluentCRM contact is unavailable;
- the expression does not match;
- required attributes are missing; or
- the destination resolves to the current page.

When headers are still available, the plugin sends a normal HTTP redirect. If output has already started, it returns a JavaScript redirect with a no-JavaScript fallback.

## Common patterns

### Button that records progress

```text
[crm_tag_button text="Complete Lesson" action="add" tag_id="42" url="/lesson-2/"]
```

The user receives tag `42` and continues to the next lesson.

### Unlock content after an action

First add a tag:

```html
<a href="/course/?fcrm_tag=20">Unlock the course</a>
```

Then restrict the content:

```text
[crm_restrict tag_id="20"]
Welcome to the course.
[/crm_restrict]
```

### Show a completion message

```text
[crm_restrict tag_id="42"]
You completed this lesson.
[/crm_restrict]
```

### Require one tag and exclude another

```text
[crm_restrict tag_id="4+!24"]
This content requires tag 4 and excludes tag 24.
[/crm_restrict]
```

### Route different audiences

```text
[crm_tag_redirect tag_id="30" destination="/customers/"]
[crm_tag_redirect tag_id="31" destination="/partners/"]
```

The first matching redirect that executes will send the user to its destination.

## FluentCRM behavior

FluentCRM is the source of truth for this plugin.

The plugin does **not** maintain a secondary tag list in WordPress user meta and does **not** automatically create a FluentCRM contact when one is missing.

If the current WordPress user does not have an available FluentCRM contact, tag actions fail silently and conditional features use their documented fallback behavior.

## Aspen Smart Links compatibility

Version 1.1.0 incorporates the frontend Smart Links behavior directly into KitMage FluentCRM Tagger.

Existing Smart Links shortcode content using:

```text
[crm_tag_button ...]
```

can continue to use the same shortcode syntax.

For compatibility, the integrated button implementation also preserves:

- the `asl_action`, `asl_tag_id`, `asl_redirect`, and `_aspen_smart_links_nonce` request fields;
- the `AspenSmartLinks` JavaScript localization object;
- the `aspen_smart_links_handle_tag_action` filter; and
- the `aspen_smart_links_tag_action` action.

The standalone Aspen Smart Links user-meta fallback and automatic FluentCRM contact-creation behavior are not included.

After confirming your existing `[crm_tag_button]` usage works with KitMage FluentCRM Tagger, the standalone Aspen Smart Links plugin is no longer required for that functionality.

## Developer hooks

### `aspen_smart_links_handle_tag_action`

Allows another integration to take over a `[crm_tag_button]` tag action.

Return `null` to let KitMage FluentCRM Tagger handle the action normally. Return `true` or `false` to mark the action as externally handled.

Arguments:

```text
$handled
$user_id
$action
$tag_id
$context
```

### `aspen_smart_links_tag_action`

Runs after a `[crm_tag_button]` action has been processed.

Arguments:

```text
$user_id
$action
$tag_id
$result
$context
```

## Troubleshooting

**A URL does not add or remove a tag**

Confirm:

- the visitor is logged in;
- FluentCRM is active;
- the WordPress user has a corresponding FluentCRM contact; and
- the tag ID is a positive integer.

**A tag-action button does not appear**

`[crm_tag_button]` returns no output for logged-out visitors or when `action` / `tag_id` is invalid.

**The button tags the user but does not reach an external URL**

External destinations are opened with JavaScript and may be affected by browser popup restrictions.

**Restricted content never appears**

Check the contact's assigned FluentCRM tag IDs and verify the expression syntax. Tag names are not accepted.

**An AND expression does not work**

Use `+`:

```text
4+!24
```

rather than relying on `&` in shortcode content.

**A redirect does not run**

Confirm:

- `tag_id` and `destination` are present;
- the current contact matches the expression; and
- WordPress allows the destination host.

Place `[crm_tag_redirect]` as early as practical in the page content or template so an HTTP redirect can occur before output begins.

## License

KitMage FluentCRM Tagger is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).
