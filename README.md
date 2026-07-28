# Force Update Translations

## Description

Apply WordPress.org theme and plugin translations to a site even if translations are not yet approved or language packs have not been released.

This plugin exports Current + Waiting (suggestions) + Fuzzy strings from translate.wordpress.org, writes them into `WP_LANG_DIR`, and generates Jed JSON / `.l10n.php` so PHP and JavaScript strings can both apply.

## Usage

### Plugin translation

1. Visit **Plugins**.
1. Under a WordPress.org plugin, choose **Update translation: Stable** or **Development**.
1. The link marked `(current)` shows which source is installed locally.

### Theme translation

1. Activate the theme you want to update.
1. Visit **Appearance → Update translation**.
1. Click **Update translation**.

### Settings

Visit **Settings → Force Update Translations** to:

- Choose whether downloads use the **user language** or the **site language**
- Enable/disable protection against official language-pack overwrites
- Bulk-update translations for installed WordPress.org plugins

## Changelog

= 0.6.4 - 2026-07-28 =
* Feature: Settings screen for locale source (user vs site language)
* Feature: Protect forced translations from being overwritten by official language packs
* Feature: Bulk update for installed WordPress.org plugins

= 0.6.3 - 2026-07-28 =
* Feature: Choose Stable or Development as the translation source when updating plugin translations
* Feature: Show whether installed plugin translations came from Stable or Development

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
