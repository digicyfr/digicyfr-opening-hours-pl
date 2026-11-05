# Opening Hours PL 0.1.0 local QA record

Updated: 2026-11-05. This is a local self-hosted release candidate, not a public release.

## Exact artifact

- ZIP: `dist/digicyfr-opening-hours-pl-0.1.0.zip` (13 runtime/documentation files, one plugin root, mode 0644)
- SHA-256: `ab3a1b0f326387c652ee3bd27d7fadc011d8738abec18eb0ae9ad1dbae3e4ea9`
- Standalone `tools/release.py build` and `verify` passed, matching the manifest and source byte for byte. Compared with the prior `e333b07f` candidate, only bundled `README.md` and `readme.txt` changed; runtime files are byte-identical.

## Checks passed

- PHP 8.3.33 syntax for plugin and test PHP, JavaScript syntax, block JSON parse, and Polish MO load.
- WordPress Coding Standards 3.4.1 `WordPress` ruleset for runtime PHP: zero errors and warnings.
- Current exact ZIP installed, activated, deactivated, and reactivated in a fresh disposable WordPress 7.1/PHP 8.3.33 site on 2026-11-05.
- Current exact-ZIP integration passed again on 2026-11-05: no-option versus valid all-closed state; invalid initial save leaves option absent; Settings API error; two Monday intervals, adjacent intervals, split overnight hours; rejection of overlap, malformed/equal/overnight times, duplicate exception dates, and inverted notice dates without losing prior data; dated hours/closed replacements; inclusive notice endpoints; hostile label and notice escaping; block/shortcode parity; administrator preview and contributor page denial; Polish public/admin translations; option removal on uninstall.
- DST fixtures in `Europe/Warsaw` starting 27 March and 23 October 2026 each produced seven successive site-local dates across the transition. A wall-clock interval remained `02:00–03:00` over the repeated autumn hour. A fixed `+01:00` setting remained fixed and triggered the settings warning.
- Chromium normal `options.php` save on the runtime-identical prior ZIP: an invalid first exception left the schedule unconfigured and showed one visible error; a valid all-closed save persisted; Monday intervals and a dated exception persisted. At 390px viewport, the document scroll width was 390px and the notice textarea right edge was 378px.
- Independent Chromium retest of the runtime-identical prior ZIP: 390px document scroll width 390px; Tab from Monday first start reached first end; invalid `17:00–09:00` save showed one error naming Monday; valid `09:00–17:00` save showed the expected Monday in the preview. The mobile screenshot was visually inspected.
- Additional keyboard-only path on the runtime-identical prior ZIP in a fresh disposable site: Tab progressed from the first Monday field through all weekly fields, exceptions, notice controls and dates to Save (67 keypresses, including Chromium's internal date-field segments); Enter submitted an all-closed schedule with a visible success notice and seven-day Closed preview. The 390px document width remained 390px.
- Independent runtime review passed on the prior `e333b07f` ZIP. The current ZIP changed only bundled documentation, then passed fresh integration and editor insertion checks.
- Runtime-identical documentation-only `5436a702` ZIP interactive editor insertion passed in Chromium on WordPress 7.1/PHP 8.3.33. After closing the new-page pattern dialog, the Block Inserter found **Opening Hours PL**; clicking it inserted `digicyfr/opening-hours`, with no block error or page JavaScript error.

## Plugin Check

WordPress.org Plugin Check ran against the runtime-identical prior ZIP installed in WordPress 7.1. Its only findings were `plugin_updater_detected` (error: unique `Update URI` is prohibited for a WordPress.org-hosted plugin) and `load_plugin_textdomainFound` (warning: WordPress.org-hosted plugins do not need bundled translation loading). This package is planned for Digicyfr self-hosted distribution, where the unique Update URI and bundled Polish catalog are intentional. WordPress.org submission would need a channel-specific review.

## Scope and remaining checks

- The integration fixture uses real `update_option()`, which bypasses the `options.php` nonce, capability and unslash steps; the Chromium save exercises the actual nonce-protected `options.php` path. No forged contributor POST or screen-reader run was performed. Capability denial was checked at the settings page; WordPress core enforces the Settings API capability and nonce on POST.
- No upgrade migration exists yet because 0.1.0 is the first version. Older WordPress/PHP versions and multisite network behavior were not tested. Uninstall multisite code follows the same pattern used by plugin #1 but was not exercised on a multisite fixture.
- Interactive editor insertion passed on a runtime-identical ZIP before the final documentation-only rebuild. A full screen-reader run remains a pre-publication check. A full-page cache rollover was documented but not tested with a cache plugin.
