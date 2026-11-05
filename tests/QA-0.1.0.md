# Opening Hours PL 0.1.0 QA record

Updated: 2026-11-05. Version 0.1.0 is a stable self-hosted GitHub release. This QA record distinguishes automated checks from untested human and representative-site checks.

## Release decision (2026-11-05)

The published ZIP passed exact-artifact installation, integration, DST fixtures, normal browser settings/public-block flows, keyboard and 390px checks, plugin-owned axe-core scans, and a synthetic cache-midnight rollover test. Digicyfr accepted browser automation for this release decision. Human screen-reader, inconclusive admin contrast, representative production cache/site, older WordPress/PHP and multisite checks remain untested; no WCAG conformance claim is made. The stable label does not change the tagged source, protected release branch or ZIP bytes.

## Exact artifact

- ZIP: [public v0.1.0 asset](https://github.com/digicyfr/digicyfr-opening-hours-pl/releases/download/v0.1.0/digicyfr-opening-hours-pl-0.1.0.zip) (13 runtime/documentation files, one plugin root, mode 0644)
- SHA-256: `ab3a1b0f326387c652ee3bd27d7fadc011d8738abec18eb0ae9ad1dbae3e4ea9`
- The public ZIP was downloaded with its SHA-256 sidecar and verified on 2026-11-05. Standalone `tools/release.py verify --archive` matched the manifest and current source byte for byte; the downloaded ZIP also matched local `dist` bytes. Compared with the prior `e333b07f` candidate, only bundled `README.md` and `readme.txt` changed; runtime files are byte-identical.

## Post-tag browser check on the exact public ZIP (2026-11-05)

The verified public asset was installed on a new disposable WordPress 7.1/PHP 8.3.33/MariaDB 11.4.13 site with timezone `Europe/Warsaw`. All page content, hours and notices below are synthetic.

- A real administrator `options.php` first save with an invalid exception date displayed a visible Settings API error and kept the seven-day preview unconfigured. A valid save persisted two Monday intervals (`09:00–12:00`, `13:00–17:00`), a next-day exception (`12:00–15:00`), and an inclusive temporary notice. The preview showed seven site-local dates and the saved exception and notice. A later invalid Monday interval (`17:00–09:00`) displayed an error naming Monday while the prior saved values and preview remained intact. [Unconfigured](evidence/0.1.0/unconfigured-settings.png), [invalid first save](evidence/0.1.0/invalid-first-save.png), [valid preview](evidence/0.1.0/valid-seven-day-preview.png), [invalid edit retained schedule](evidence/0.1.0/invalid-edit-preserved-schedule.png).
- In the actual block editor, the Block Inserter found and inserted **Opening Hours PL**. Publishing the page stored `<!-- wp:digicyfr/opening-hours /-->`; an anonymous public browser request rendered the seven-row schedule, exception and notice. [Editor insertion](evidence/0.1.0/editor-block-inserted.png) and [published page](evidence/0.1.0/published-block.png).
- At a 390px viewport, the settings document width was 390px and the notice textarea ended at 378px. The public page also stayed 390px wide, with all seven rows visible. [Mobile settings](evidence/0.1.0/settings-390px.png) and [mobile public block](evidence/0.1.0/published-block-390px.png). No browser script errors occurred in the completed checks.
- Both disposable containers and volumes were removed. The public ZIP, sidecar, release branch and tag were not changed by this documentation update.

## Exact-ZIP automated accessibility and cache checks (2026-11-05)

On another fresh WordPress 7.1/PHP 8.3.33 site, Playwright Chromium with axe-core 4.10.3 scanned WCAG 2 A/AA, 2.1 AA and 2.2 AA rules. The [machine-readable axe report](evidence/0.1.0/axe-audit-2026-11-05.json) contains the full findings.

- The plugin's public seven-day table, admin form and admin preview had **zero axe violations**. The full public page had one `list` violation in the Twenty Twenty-Five navigation; the same rule and selector failed on a WordPress core post without the block. The WordPress core General Settings page and the plugin admin page had no axe violations.
- Axe marked admin text/controls and preview text contrast **incomplete** (62 nodes in the form; 22 in the preview), as it did for 46 nodes on the WordPress core General Settings page. These nodes require manual visual or assistive-technology review; zero violations is not a contrast pass claim.
- Tab from Monday's first start field reached its end field, 56 weekly interval labels were present, and the browser accessibility tree exposed the table caption, Date, Hours and the synthetic notice. The settings and public pages each fit a 390px document width and had no page JavaScript errors.
- A separate [synthetic cache rollover report](evidence/0.1.0/synthetic-cache-midnight-2026-11-05.json) used a disposable test-only WordPress MU page-cache plugin with a five-minute transient TTL. A fixed site offset aligned local 23:59 to the next real UTC minute. The first request was a cache MISS on 2026-11-04, the next was a HIT, and after local midnight a HIT still served the 2026-11-04 seven-day table. Purging the test cache caused a MISS and a new table starting 2026-11-05. This confirms the documented need to expire or purge full-page cache at local midnight. It is a synthetic cache model; a third-party or production cache was not tested.
- This automated scan is not a human NVDA/VoiceOver test. The disposable containers and volumes were removed; the published ZIP was unchanged.

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
- Interactive editor insertion also passed on the exact public ZIP after tagging. A full human screen-reader run remains untested, and automated checks do not replace it. The synthetic page-cache rollover above passed; a representative third-party or production full-page cache remains untested.
