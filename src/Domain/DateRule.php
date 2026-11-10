<?php
/**
 * Immutable description of one Date Difference rule configured by an admin.
 *
 * The current storage model allows one rule per Forminator form. The rule ID
 * is stable, so a later storage migration can support additional rules
 * without changing the calculator or submission adapter.
 *
 * @package DMMF
 */

namespace DMMF\Domain;

final class DateRule {

	const SCHEMA_VERSION = 1;

	/** @var array<string, mixed> */
	private $data;

	/**
	 * @param array<string, mixed> $data Already validated rule data.
	 */
	private function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Creates a rule only when its structural invariants are valid.
	 *
	 * @param array<string, mixed> $data Candidate rule data.
	 * @return self|null
	 */
	public static function from_array( array $data ) {
		$form_id = isset( $data['form_id'] ) && is_numeric( $data['form_id'] ) ? max( 0, (int) $data['form_id'] ) : 0;
		$fields  = isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : array();

		if ( ! $form_id || ! self::valid_field_id( $fields['start'] ?? null ) || ! self::valid_field_id( $fields['end'] ?? null ) || ! self::valid_field_id( $fields['output'] ?? null ) ) {
			return null;
		}

		if ( $fields['start'] === $fields['end'] || $fields['start'] === $fields['output'] || $fields['end'] === $fields['output'] ) {
			return null;
		}

		$operation = isset( $data['operation'] ) && 'calendar_days' === $data['operation'] ? 'calendar_days' : 'nights';
		$inclusive = 'calendar_days' === $operation && ! empty( $data['inclusive_end'] );
		$formats   = isset( $data['date_formats'] ) && is_array( $data['date_formats'] ) ? $data['date_formats'] : array();
		$output_type = isset( $data['output_type'] ) && 'hidden' === $data['output_type'] ? 'hidden' : 'number';
		$start_format = self::valid_date_format( $formats['start'] ?? null ) ? $formats['start'] : 'yy-mm-dd';
		$end_format   = self::valid_date_format( $formats['end'] ?? null ) ? $formats['end'] : 'yy-mm-dd';

		return new self(
			array(
				'schema_version' => self::SCHEMA_VERSION,
				'id'             => 'form-' . $form_id,
				'form_id'        => $form_id,
				'fields'         => array(
					'start'  => $fields['start'],
					'end'    => $fields['end'],
					'output' => $fields['output'],
				),
				'date_formats'   => array(
					'start' => $start_format,
					'end'   => $end_format,
				),
				'output_type'    => $output_type,
				'operation'      => $operation,
				'inclusive_end'  => $inclusive,
				'mode'           => self::calculation_mode( $operation, $inclusive ),
				'error_message'  => isset( $data['error_message'] ) && is_string( $data['error_message'] ) ? $data['error_message'] : '',
				'enabled'        => ! isset( $data['enabled'] ) || (bool) $data['enabled'],
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_array() {
		return $this->data;
	}

	/**
	 * @param mixed $field_id Candidate Forminator field identifier.
	 * @return bool
	 */
	private static function valid_field_id( $field_id ) {
		return is_string( $field_id ) && (bool) preg_match( '/^[a-z0-9][a-z0-9_-]*$/', $field_id );
	}

	/**
	 * Numeric picker formats supported by the parser and browser adapter.
	 *
	 * @param mixed $format Candidate jQuery UI date format.
	 * @return bool
	 */
	private static function valid_date_format( $format ) {
		return is_string( $format ) && in_array( $format, array( 'yy-mm-dd', 'yy/mm/dd', 'dd/mm/yy', 'mm/dd/yy', 'dd-mm-yy', 'mm-dd-yy' ), true );
	}

	/**
	 * @param string $operation Rule operation.
	 * @param bool   $inclusive Whether the end calendar day is counted.
	 * @return string
	 */
	private static function calculation_mode( $operation, $inclusive ) {
		if ( 'calendar_days' === $operation && $inclusive ) {
			return 'calendar_days_inclusive';
		}

		return 'calendar_days' === $operation ? 'calendar_days' : 'nights';
	}
}
