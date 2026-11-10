# DayQuanta Date Difference for Forminator

Calculate nights and calendar days between two Forminator Datepicker fields,
with live results and server-side recalculation on submission.

**Version:** 1.0.1 · **WordPress:** 6.5+ · **PHP:** 8.2+ · **Dependency:** Forminator

## Features

- Connect two existing Datepicker fields to a Number or Hidden field.
- Choose nights, calendar days, or calendar days including the end date.
- Use the result in Forminator Calculation fields.
- Recalculate on the server instead of trusting a browser-supplied result.
- Configure one rule per form in **Forminator > DayQuanta**.
- Flag saved rules for review when their fields or date formats change.

For example, 10 June to 13 June produces 3 nights, 3 calendar days, or 4 days
with the inclusive mode. Both Datepicker fields must use the same date format.

## Installation

1. Install and activate Forminator.
2. Download `dayquanta-date-difference-for-forminator-1.0.1.zip` from a
   published GitHub release's assets.
3. In WordPress, open **Plugins > Add New > Upload Plugin**, upload the ZIP,
   and activate DayQuanta.
4. Create two Datepicker fields and one Number or Hidden field in your form.
5. Open **Forminator > DayQuanta**, connect the fields, and save a rule.

The `*-github.zip` handoff archive contains repository files. Extract it before
adding those files to GitHub. Build a separate installation ZIP with the command
below. See [the GitHub publishing guide in Italian](docs/PUBBLICAZIONE-GITHUB.md).

## Build an installation ZIP

Use Python 3.9 or newer; no third-party Python packages are required.
Run from the repository root:

```console
python tools/build_release.py --version 1.0.1
```

On macOS/Linux, use `python3` if needed. The command produces:

```text
dist/dayquanta-date-difference-for-forminator-1.0.1.zip
dist/dayquanta-date-difference-for-forminator-1.0.1.zip.sha256
```

The builder checks the plugin header, version constant, and WordPress stable tag.
Only files explicitly listed in [tools/release-files.txt](tools/release-files.txt)
enter the installation ZIP. GitHub metadata, local configuration, documentation,
and build tools stay outside that ZIP. The full GPL license is included.

## Development and checks

PHP, JavaScript, and CSS sources are committed directly. There is no Composer,
npm, or frontend compilation step. Runtime files live at the repository root,
in `src/`, and in `assets/`.

The GitHub Actions workflow runs PHP syntax checks on PHP 8.2, 8.3, 8.4, and 8.5,
JavaScript syntax checks, and installation-package generation on pushes and pull
requests. Syntax checks do not replace testing the plugin with WordPress and
Forminator. See [CONTRIBUTING.md](CONTRIBUTING.md) for the manual checks.

Pushing a matching version tag such as `v1.0.1` runs the checks and then creates
a **draft release** with the installation ZIP and SHA-256 checksum. Review and
publish the draft in GitHub. No WordPress.org deployment is configured.

The workflow uses the repository's built-in `GITHUB_TOKEN`; no personal access
token is needed. Third-party actions are pinned to commits, and Dependabot is
configured to propose action updates.

## Data and removal

DayQuanta keeps saved rule configuration when uninstalled so it is available
after reinstallation. It does not store its own form submissions. Forminator
continues to handle form submission storage.

## License

Copyright (C) 2026 Roberto Liccardo.

Licensed under **GNU GPL version 2 or, at your option, any later version**
(`GPL-2.0-or-later`), matching the existing plugin header. The complete GPL v2
text is in [LICENSE](LICENSE). The software is provided without warranty.

DayQuanta is an independent extension and is not affiliated with or endorsed
by WPMU DEV. Forminator and WordPress are not bundled with this repository.
