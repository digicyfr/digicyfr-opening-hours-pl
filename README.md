# Digicyfr Opening Hours PL

A free, GPL-licensed WordPress plugin for one location's owner-entered weekly opening hours, manually entered date exceptions, and a temporary notice. It renders the next seven dates in the WordPress site timezone through the **Opening Hours PL** block or `[digicyfr_opening_hours]` shortcode. The settings screen includes a saved-data preview. English and Polish translations are bundled.

**Po polsku:** Dla sklepu lub firmy usługowej z jedną lokalizacją: pokaż najbliższe siedem dni na podstawie własnego grafiku. Święta, niedziele handlowe i inne wyjątki trzeba wpisać ręcznie.

## Website guides and stable release

- [Opening Hours PL: English overview and installation](https://www.digicyfr.com/en/wordpress-plugins/digicyfr-opening-hours-pl)
- [Godziny otwarcia PL: opis i instalacja po polsku](https://www.digicyfr.com/pl/wordpress-plugins/digicyfr-opening-hours-pl)
- [Stable v0.1.0 release, ZIP and checksum](https://github.com/digicyfr/digicyfr-opening-hours-pl/releases/tag/v0.1.0)

<a href="https://www.digicyfr.com/pl/wordpress-plugins/digicyfr-opening-hours-pl"><img src="https://www.digicyfr.com/images/wordpress-plugins/digicyfr-opening-hours-pl.png" alt="Example seven-day opening hours block on a WordPress test page" width="520"></a>

_Example screenshot from a disposable test site._

## Install and use

1. In **Plugins → Add New → Upload Plugin**, upload the release ZIP and activate it. WordPress 7.1+ and PHP 8.3+ are required.
2. In **Settings → Opening Hours PL**, enter up to four intervals per weekday. Leave a day's rows blank for Closed. Enter times as `HH:MM`; `24:00` is allowed only as an end. Split overnight hours across two dates.
3. Optionally add dated exceptions, one line per date. Examples:

   ```text
   2026-12-24 | 09:00-12:00 | Wigilia
   2026-12-25 | closed | Święto
   ```

   A dated exception replaces all regular hours for that date. Maximum 366 exceptions; labels are plain text of at most 120 characters.
4. Optionally enable a notice with text and inclusive start/end dates. Notice text is plain text, at most 250 characters.
5. Place the block or shortcode on a page. Both use the same server renderer. The shortcode takes no attributes.

The schedule is **unconfigured** until the first valid save. A valid save with every day empty means the location is **Closed** every day. Invalid submissions leave the previous saved schedule intact and show a Settings API error.

The plugin uses the site's current timezone (shown on its settings page), including after a timezone change. A fixed UTC offset does not follow daylight-saving changes; choose an IANA city timezone such as `Europe/Warsaw` where appropriate. Hours remain entered wall-clock text; the plugin does not determine whether the location is open right now. Exceptions and holidays are entered manually. It does not make claims about Polish trading Sundays or legal compliance.

The seven-day display changes at the **local date rollover**. If a page cache is used, set a short TTL or purge the cached page at local midnight. A manual ZIP update can be installed from the Digicyfr plugin page. The unique Update URI prevents unrelated directory updates; there is no automatic update endpoint in version 0.1.0.

Only administrators with `manage_options` can save settings through WordPress's nonce-protected Settings API. No public settings endpoint, external lookup, telemetry, remote assets, or outbound calls are used. Deactivation retains data. Uninstall removes the plugin option on each site, including multisite; back up data first if needed.

## Develop and test

The [public source repository](https://github.com/digicyfr/digicyfr-opening-hours-pl) accepts bug reports and contributions. Report security issues privately using [SECURITY.md](https://github.com/digicyfr/digicyfr-opening-hours-pl/blob/main/SECURITY.md). `release-files.txt` controls the release ZIP. From this repository's root:

```bash
find . -type f -name '*.php' -not -path './dist/*' -print0 | xargs -0 -n 1 php -l
python3 tools/release.py build
python3 tools/release.py verify
tests/run-wordpress-integration.sh dist/digicyfr-opening-hours-pl-0.1.0.zip
```

License: GPL-2.0-or-later. See [LICENSE](LICENSE).

See the [release process](https://github.com/digicyfr/digicyfr-opening-hours-pl/blob/main/RELEASE.md) for the release gates.
