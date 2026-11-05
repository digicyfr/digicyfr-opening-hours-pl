# Opening Hours PL 0.1.0 release candidate

SHA-256 of `digicyfr-opening-hours-pl-0.1.0.zip`: `ab3a1b0f326387c652ee3bd27d7fadc011d8738abec18eb0ae9ad1dbae3e4ea9`.

This first version displays one location's administrator-entered weekly hours, dated exceptions and a temporary notice for seven site-local dates. The server-rendered block and shortcode share the same output. English and Polish strings are bundled. Uninstall removes the saved schedule.

Verified on WordPress 7.1 and PHP 8.3.33 with a fresh exact-ZIP install, activation/deactivation/reactivation, validation/DST and WordPress integration tests. After tagging, the exact public ZIP and sidecar were downloaded and verified against source. A fresh browser run passed invalid/valid `options.php` saves, seven-day preview, Block Inserter selection and insertion, publication and anonymous rendering of the block, and 390px settings and public layouts. [Dated QA and screenshots](tests/QA-0.1.0.md) record the evidence. The earlier WordPress.org Plugin Check result was collected on a prior runtime-identical candidate, not this published ZIP.

Before stable promotion, complete a manual screen-reader check. Where a full-page cache is used, set a short TTL or purge at local midnight. A disposable synthetic five-minute page-cache test showed that a cached page retained the prior date after local midnight until purged; a representative third-party or production cache remains untested. Automated axe checks found no plugin-specific violation, with some admin contrast checks inconclusive; [QA evidence](tests/QA-0.1.0.md) records the scope. Older WordPress/PHP versions and multisite network behavior were not tested. Holidays, Polish trading Sundays and current open/closed status are not calculated. Updates are manual ZIP installs.
