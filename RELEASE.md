# Release process

Version `0.1.0` is a local release candidate. Work on `main`; freeze a `release/0.1.0` branch for the reviewed package. Tag `v0.1.0` from that branch only after all gates below pass. Publish the matching ZIP and `.zip.sha256` as release assets and on Digicyfr's download page. Do not generate a second ZIP by zipping the repository tree.

## Checks

1. Run PHP lint, editor JavaScript syntax, and `python3 tools/release.py build && python3 tools/release.py verify` (CI checks these).
2. Run `bash tests/run-wordpress-integration.sh dist/digicyfr-opening-hours-pl-0.1.0.zip` against a fresh disposable WordPress site.
3. Confirm interactive editor insertion, keyboard access, Polish UI, narrow viewport, and a manual screen-reader pass. Where a full-page cache is used, check the seven-day display after local midnight or configure a short TTL/purge. Record exact WordPress/PHP versions in `tests/QA-0.1.0.md`.
4. Confirm the public source link, release asset, Digicyfr page, changelog, license, support/security path, and manual update instructions. Download the public ZIP and compare its SHA-256 with the release asset before a clean install test.

The documented `Update URI` prevents a WordPress.org slug collision; version `0.1.0` has no automatic updater. WordPress.org submission needs a separate directory-specific review. See `tests/QA-0.1.0.md` for current evidence and open manual checks.
