# Force Update Translations

## Description

Apply WordPress.org theme and plugin translations to a site even if translations are not yet approved or language packs have not been released.

## Usage

### Theme translation

Finally, updating theme translation files is now supported! To download the translation files for a theme:

1. Activate the theme you want to get the translation files.
1. Visit 'Appearance' > 'Update translation' in WordPress menu, or click 'Update translation' on theme details of current theme on 'Themes' page.

### Plugin translation

To download the translation files for a plugin:

1. Visit 'Plugins' in WordPress menu.
1. Click 'Update translation' under the name of the plugin for which you want to get the translation files. Translations are fetched from the Stable project, falling back to Development. Use the arrow next to the link to pick one explicitly.

## Changelog

= 1.0.0 - 2026-10-07 =
* Feature: Generate JSON files so JavaScript translations are applied (fixes #24)
* Feature: Generate `.l10n.php` files on WordPress 6.5 and later (performant translations)
* Feature: Plugin translations are fetched from the Stable project, falling back to Development; either can be chosen explicitly (fixes #37)
* Feature: The success notice shows which project the translation came from
* Improvement: The translation file cache is invalidated after download
* Improvement: Admin notices are shown reliably after a plugin translation update
* Update: Requires at least WordPress 5.0
* Credits: Stable/Development source selection and JSON / `.l10n.php` generation by @hiroshisatoy

= 0.6.2 - 2025-12-22 =
* Fix: Added plugin headers required for WordPress.org language pack compatibility

= 0.6.1 - 2025-12-22 =
* Update: Lowered PHP requirement to 5.6 to maximize the reach of the security update
* Update: Readme and translator comment corrections

= 0.6.0 - 2025-12-17 =
* Security: Fixed CSRF vulnerability (CVE-2025-58236)
* Security: Added nonce verification and permission checks for translation updates
* Security: Improved input validation and path traversal protection
* Improvement: PHP 8.2 compatibility enhancements
* Improvement: Code quality improvements (PHPDoc, visibility declarations)
* Update: Synchronized GlotPress locales library to latest upstream version
* Credits: Vulnerability discovered by @nblirwn (Patchstack Alliance), security patch implemented by @rocket-martue

= 0.5 =
* Child theme support. props @pedro-mendonca

= 0.4 =
* Bug fix for fresh installed WP. props @Dartui

= 0.3.2 & 0.3.3 =
* Update tested up to versions.

= 0.3.1 =
* Update locales.php and add WP.org variants support. props @pedro-mendonca

= 0.3.0 =
* Added theme translation support.

= 0.2.5 =
* Tested up to WP 5.5.
* Minor grammar correction. Props @ePascalC
* Added plugin icon. Props @mekemoke

= 0.2.4 =
* Tested up to WP 5.2.2 props @pedro-mendonca
* Check if if user Locale isn't 'en_US' props @pedro-mendonca

= 0.2.3 =
* Add Multisite support. props @pedro-mendonca

= 0.2.2 =
* Check if plugin exists in WordPress.org plugin directory. props @pedro-mendonca

= 0.2.1 =
* Make target locale switchable by user setting. Thanks for reporting @Dartui
* Improve escaping. Thanks for reporting @miya0001

= 0.2 =
* Export only Current/Waiting/Fuzzy translations. props @naokomc
* Capitalize plugin name.

## Development

```bash
composer install
deno task format   # phpcbf (WordPress Coding Standards)
deno task lint     # phpcs
```
