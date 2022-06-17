=== Force Update Translations ===
Contributors: mayukojpn, nao, dartui, pedromendonca, casiepa, mekemoke, miyauchi, nekojonez
Tags: translation
Requires at least: 4.9
Tested up to: 6.0
Requires PHP: 5.2.4
Stable tag: 0.3.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Download WordPress theme/plugin translations and apply them to your site manually even if their language pack haven't been released or reviewed on translate.wordpress.org

== Description ==

Download WordPress theme/plugin translations and apply them to your site manually even if their language pack haven't been released or reviewed on translate.wordpress.org

⚠️ Warning ⚠️ Currently this plugin is not able to generate the JSON files that is needed for JavaScript to consume some translations. Please wait for update or join us on <a href="https://github.com/mayukojpn/force-update-translations">GitHub</a>.

== Theme translation ==

Finally, updating theme translation files is now supported! To download the translation files for a theme:

1. Activate the theme you want to get the translation files.
1. Visit 'Appearance' > 'Update translation' in WordPress menu, or click 'Update translation' on theme details of current theme on 'Themes' page.

== Plugin translation ==

To download the translation files for a plugin:

1. Visit 'Plugins' in WordPress menu.
1. Click 'Update translation' under the name of the plugin for which you want to get the translation files.


== Screenshots ==

1. "Update translation" link will be shown in your plugins list.

== Changelog ==

= 0.3.1 =
* Update locales.php and add WP.org variants support. props @pedromendonca

= 0.3.0 =
* Added theme translation support.

= 0.2.5 =
* Tested up to WP 5.5.
* Minor grammar correction. Props @casiepa
* Added plugin icon. Props @mekemoke

= 0.2.4 =
* Tested up to WP 5.2.2 props @pedromendonca
* Check if if user Locale isn't 'en_US' props @pedromendonca

= 0.2.3 =
* Add Multisite support. props @pedromendonca

= 0.2.2 =
* Check if plugin exists in WordPress.org plugin directory. props @pedromendonca

= 0.2.1 =
* Make target locale switchable by user setting. Thanks for reporting @dartui
* Improve escaping. Thanks for reporting @miyauchi

= 0.2 =
* Export only Current/Waiting/Fuzzy translations. props @nao
* Capitalize plugin name.

== Upgrade Notice ==

= 3.0.0 =
* Added theme translation support.
