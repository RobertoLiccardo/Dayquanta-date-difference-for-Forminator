=== DayQuanta Date Difference for Forminator ===
Contributors: robertoliccardo
Tags: forminator, date calculator, date difference, datepicker, booking forms
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.2
Requires Plugins: forminator
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Calculate secure date differences in Forminator forms. Turn two Datepicker fields into nights or calendar days for bookings and quotes.

== Description ==

DayQuanta Date Difference for Forminator adds reliable date difference calculations to Forminator forms. Connect two existing Datepicker fields to a Number or Hidden field, then calculate nights or calendar days for bookings, rentals, appointments, parking, service periods, and quotes.

Visitors see the result update as they choose dates. On every submission, the plugin performs the calculation again on the server, so a value changed in the browser is never trusted. The calculated result can also be used by Forminator Calculation fields.

Create one Date Difference rule for each Forminator form. Choose Nights, Calendar Days, or Calendar Days including the end date, and set a clear message when the end date is before the start date.

The plugin does not edit WordPress or Forminator files. Create the fields in Forminator, then connect them in Forminator > DayQuanta.

DayQuanta Date Difference for Forminator is an independent extension for Forminator and is not affiliated with or endorsed by WPMU DEV.

== Screenshots ==

1. Configure a Date Difference rule by selecting a Forminator form, two Datepicker fields, and the result field.
2. Review configured Date Difference rules and see their current status.
3. Visitors select a check-in and check-out date and immediately see the calculated number of nights.

== Installation ==

1. Upload the plugin ZIP in Plugins > Add New > Upload Plugin.
2. Activate DayQuanta Date Difference for Forminator and make sure Forminator is active.
3. In Forminator, create a form with two Datepicker fields and one Number or Hidden field.
4. Open Forminator > DayQuanta, choose the form and connect the fields.
5. Choose the calculation mode and save the rule.

The two selected Datepicker fields must use the same date format.

== Frequently Asked Questions ==

= Does this plugin modify Forminator? =

No. It uses Forminator's normal plugin integration points and does not modify Forminator or WordPress files.

= Which Forminator fields can I use? =

Choose two supported Forminator Datepicker fields with the same format. The result can be written to an existing Number or Hidden field in the same form.

= Which date calculations are available? =

Choose Nights, Calendar Days, or Calendar Days including the end date. For example, a stay from 10 June to 13 June is 3 nights, 3 calendar days, or 4 calendar days including both dates.

= Is the calculation secure? =

Yes. The browser updates the visible result for convenience, but the server calculates it again on submission. A value altered in the browser is not trusted.

= What happens if I change a field after saving the rule? =

If a configured field is removed, changes type, changes date format, or its form is no longer published, the rule is marked Needs review and does not run until you review and save it again.

= What happens when I uninstall the plugin? =

The rule configuration is kept so it is available if you reactivate the plugin. The plugin does not store its own form submissions.

= Does it work with AJAX-enabled Forminator forms? =

Yes. The visible result updates in the browser, and the server validates and recalculates it when Forminator receives the submission.

== Changelog ==

= 1.0.1 =
* Renamed the Forminator submenu to DayQuanta for clearer navigation.

= 1.0.0 =
* First public release.
* Secure Date Difference rules for Forminator Datepicker fields.
* Live result updates and server-side recalculation on submission.
* Nights and calendar-day calculation modes.
* Dashboard link from the WordPress Plugins screen to the configuration wizard.

== Upgrade Notice ==

= 1.0.1 =
The Forminator submenu is now labelled DayQuanta.

= 1.0.0 =
* First public release of DayQuanta Date Difference for Forminator.
