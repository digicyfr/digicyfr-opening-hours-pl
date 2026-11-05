# Contributing

Bug reports and pull requests are welcome at [GitHub](https://github.com/digicyfr/digicyfr-opening-hours-pl). Use synthetic schedules in issues and tests. Send security concerns privately as described in [SECURITY.md](SECURITY.md).

Target `main` for pull requests. Explain the user-visible change, update `CHANGELOG.md` when behavior changes, and keep all contributed code and assets compatible with GPL-2.0-or-later. Do not add telemetry or remote requests without explicit discussion and documentation.

Before opening a pull request, lint all PHP files with `php -l`, check `blocks/opening-hours/editor.js` with `node --check`, and run `python3 tools/release.py build` followed by `python3 tools/release.py verify`. The release manifest controls which files enter the ZIP. The repository CI repeats these checks. WordPress integration evidence and the remaining manual checks are in `tests/QA-0.1.0.md`.
