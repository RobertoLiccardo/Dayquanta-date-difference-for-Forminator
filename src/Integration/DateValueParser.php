<?php
/**
 * Converts the fixed ISO Datepicker format used by the POC into date-only values.
 *
 * @package DMMF
 */

namespace DMMF\Integration;

use DateTimeImmutable;
use DateTimeZone;

final class DateValueParser {

	/**
	 * @param mixed  $value Submitted Forminator date value.
	 * @param string $format Numeric jQuery UI date format from the configured field.
	 * @return DateTimeImmutable|null
	 */
	public function parse( $value, $format = 'yy-mm-dd' ) {
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$php_format = $this->php_format( $format );
		if ( ! $php_format ) {
			return null;
		}

		$date   = DateTimeImmutable::createFromFormat( '!' . $php_format, trim( (string) $value ), new DateTimeZone( 'UTC' ) );
		$errors = DateTimeImmutable::getLastErrors();
		if ( ! $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) ) {
			return null;
		}

		return $date;
	}

	/**
	 * Kept as a clear name for the POC fixture and old test calls.
	 *
	 * @param mixed $value Date in ISO year-month-day form.
	 * @return DateTimeImmutable|null
	 */
	public function parse_iso_date( $value ) {
		return $this->parse( $value, 'yy-mm-dd' );
	}

	/**
	 * The configuration screen intentionally exposes only unambiguous numeric Datepicker
	 * formats. Month-name formats can be added later with locale-aware parsing.
	 *
	 * @param string $format jQuery UI Datepicker format.
	 * @return string|null PHP DateTime format.
	 */
	private function php_format( $format ) {
		$formats = array(
			'yy-mm-dd' => 'Y-m-d',
			'yy/mm/dd' => 'Y/m/d',
			'dd/mm/yy' => 'd/m/Y',
			'mm/dd/yy' => 'm/d/Y',
			'dd-mm-yy' => 'd-m-Y',
			'mm-dd-yy' => 'm-d-Y',
		);

		return isset( $formats[ $format ] ) ? $formats[ $format ] : null;
	}
}
