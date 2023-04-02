# Force Update Translations

## Description

Download WordPress theme/plugin translations and apply them to your site manually even if their language pack haven't been released or reviewed on translate.wordpress.org

> **Warning**: Currently this plugin downloads only strings from Development project instead of Stable for plugins. Please wait for an update or see <a href="https://github.com/mayukojpn/force-update-translations/issues/37">the issue on GitHub</a>.

> **Warning**: Currently this plugin is not able to generate the JSON files that is needed for JavaScript to consume some translations. Please wait for update or see <a href="https://github.com/mayukojpn/force-update-translations/issues/24">the issue on GitHub</a>.

## Usage

### Theme translation

Finally, updating theme translation files is now supported! To download the translation files for a theme:

1. Activate the theme you want to get the translation files.
1. Visit 'Appearance' > 'Update translation' in WordPress menu, or click 'Update translation' on theme details of current theme on 'Themes' page.

### Plugin translation

To download the translation files for a plugin:

1. Visit 'Plugins' in WordPress menu.
1. Click 'Update translation' under the name of the plugin for which you want to get the translation files.

## Changelog

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
