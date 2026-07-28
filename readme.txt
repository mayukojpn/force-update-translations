=== Force Update Translations ===
Contributors: mayukojpn, nao, dartui, pedromendonca, casiepa, mekemoke, miyauchi, nekojonez, rocketmartue
Tags: translation
Requires at least: 4.7
Tested up to: 6.9
Requires PHP: 5.6
Stable tag: 0.6.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Apply WordPress.org theme and plugin translations to a site even if translations are not yet approved or language packs have not been released.

== Description ==

Apply WordPress.org theme and plugin translations to a site even if translations are not yet approved or language packs have not been released.

This plugin exports translations from translate.wordpress.org including Current, Waiting (suggestions), and Fuzzy strings, writes them into your site language directory, and generates Jed JSON files so JavaScript translations can apply as well.

== Plugin translation ==

1. Visit 'Plugins' in WordPress menu.
1. Under the plugin name, choose 'Update translation: Stable' or 'Development'.

== Theme translation ==

1. Activate the theme you want to get the translation files.
1. Visit 'Appearance' > 'Update translation' in the WordPress admin.
1. Click the 'Update translation' button.

== Settings ==

Visit 'Settings' > 'Force Update Translations' to choose the locale source (user or site language), protect forced translations from official language-pack overwrites, and bulk-update plugin translations.

== Changelog ==

To read the changelog for the latest the plugin release, please navigate to the <a href="https://github.com/mayukojpn/force-update-translations#changelog">GitHub</a>.

== Upgrade Notice ==

= 0.6.0 =
* Security fix for CVE-2025-58236. Update recommended.
