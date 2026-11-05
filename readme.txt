=== Digicyfr Opening Hours PL ===
Tags: opening hours, business, poland, schedule, shortcode
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Show one location's owner-entered weekly hours, dated exceptions, and a temporary notice for the next seven local dates.

== Description ==

Enter up to four intervals per weekday, manually add exceptions for particular dates, and show the next seven dates with the Opening Hours PL block or [digicyfr_opening_hours] shortcode. English and Polish strings are bundled. The plugin uses the WordPress site timezone and does not automatically infer holidays, trading Sundays, or whether the location is open now.

No external service or account is needed. See README.md for data limits, caching, and privacy details.

Source code and issue tracker: https://github.com/digicyfr/digicyfr-opening-hours-pl

== Installation ==

1. Upload the plugin ZIP under Plugins > Add New > Upload Plugin and activate it.
2. Open Settings > Opening Hours PL and save your weekly hours. Add exceptions and a notice if needed.
3. Add the Opening Hours PL block or [digicyfr_opening_hours] shortcode to a page.

== Frequently Asked Questions ==

= How do dated exceptions work? =

One line per date, such as `2026-12-25 | closed | Holiday` or `2026-12-24 | 09:00-12:00 | Short day`. The exception replaces that date's regular hours.

= Does the plugin know Polish public holidays or trading Sundays? =

No. Enter exceptions manually.

= What happens on uninstall? =

The plugin's schedule option is removed. Deactivation retains it.

== Changelog ==

= 0.1.0 =

* Initial candidate with weekly intervals, dated exceptions, inclusive notices, seven-day block/shortcode output, and Polish/English strings.
