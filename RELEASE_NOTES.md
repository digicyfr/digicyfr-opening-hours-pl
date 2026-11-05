# Opening Hours PL 0.1.0 release candidate

SHA-256 of `digicyfr-opening-hours-pl-0.1.0.zip`: `ab3a1b0f326387c652ee3bd27d7fadc011d8738abec18eb0ae9ad1dbae3e4ea9`.

This first version displays one location's administrator-entered weekly hours, dated exceptions and a temporary notice for seven site-local dates. The server-rendered block and shortcode share the same output. English and Polish strings are bundled. Uninstall removes the saved schedule.

Verified on WordPress 7.1 and PHP 8.3.33 with a fresh exact-ZIP install, activation/deactivation/reactivation, validation/DST and WordPress integration tests. Chromium Block Inserter selection and insertion passed on a runtime-identical ZIP before the final documentation-only rebuild. No plugin PHP, JavaScript, CSS or translation bytes changed between those packages.

Before public release, complete a manual screen-reader check. Where a full-page cache is used, set a short TTL or purge at local midnight; cache rollover with a cache plugin was not tested. Older WordPress/PHP versions and multisite network behavior were not tested. Holidays, Polish trading Sundays and current open/closed status are not calculated. Updates are manual ZIP installs. See `tests/QA-0.1.0.md` for full evidence.
