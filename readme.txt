=== Force Update Translations ===
Contributors: mayukojpn, nao, dartui, pedromendonca, casiepa, mekemoke, miyauchi, nekojonez, rocketmartue
Tags: translation
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.6.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Download WordPress theme/plugin translations and apply them to your site manually even if their language pack haven't been released or reviewed on translate.wordpress.org

== Description ==

Download WordPress theme/plugin translations and apply them to your site manually even if their language pack haven't been released or reviewed on translate.wordpress.org

**Note about Translation Playground:**
The [Translation Playground](https://make.wordpress.org/polyglots/2023/04/19/wp-translation-playground/) is now available for quick translation testing. However, if you need to test translations on your actual site, this plugin may remain the practical solution.

⚠️ Warning ⚠️ Currently this plugin downloads only strings from Development project instead of Stable for plugins. Please wait for an update or see <a href="https://github.com/mayukojpn/force-update-translations/issues/37">the issue on GitHub</a>.

⚠️ Warning ⚠️ Currently this plugin is not able to generate the JSON files that is needed for JavaScript to consume some translations. Please wait for update or see <a href="https://github.com/mayukojpn/force-update-translations/issues/24">the issue on GitHub</a>.

== Theme translation ==

To download the translation files for a theme:

1. Activate the theme you want to get the translation files.
1. Visit 'Appearance' > 'Update translation' in WordPress menu, or click 'Update translation' on theme details of current theme on 'Themes' page.
1. Click the 'Update Translations' button.

== Plugin translation ==

To download the translation files for a plugin:

1. Visit 'Plugins' in WordPress menu.
1. Click 'Update translation' under the name of the plugin for which you want to get the translation files.

== Screenshots ==

1. "Update translation" link will be shown in your plugins list.

== Changelog ==

To read the changelog for the latest the plugin release, please navigate to the <a href="https://github.com/mayukojpn/force-update-translations#changelog">GitHub</a>.

== Upgrade Notice ==

= 0.6.0 =
* Security fix for CVE-2025-58236. Update recommended.
