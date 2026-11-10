# Contributing

Use an issue to describe a bug or propose a change. Include WordPress, PHP,
and Forminator versions, field types, date format, and steps to reproduce.
Use fictional example data when sharing forms or screenshots.

Submit changes in a pull request with a short explanation and relevant checks.
Keep PHP compatible with the minimum version declared in the plugin header.
If a runtime file is added or removed, update `tools/release-files.txt`.

Before releasing a change, test it on a separate WordPress site with Forminator:

1. Install and activate the generated ZIP and open **Forminator > DayQuanta**.
2. Connect two Datepicker fields with the same format to a Number or Hidden field.
3. Check nights, calendar days, inclusive days, and a same-day interval.
4. Check leap days, a daylight-saving transition, and an end date before the start.
5. Check a Forminator Calculation field that uses the calculated result.
6. Submit regular and AJAX-enabled forms; confirm server-side recalculation.
7. Change a configured field or its format and confirm the rule needs review.

GitHub syntax and packaging checks do not perform these integration checks.
No local database, credentials, or POC fixtures are needed to build the ZIP.

For a release, update the plugin header version, `DMMF_VERSION`, the stable tag
in `readme.txt`, the README, and the changelog. Build the ZIP with the expected
version before pushing a matching `vX.Y.Z` tag.

Contributions are distributed under the project's GPL-2.0-or-later license.
