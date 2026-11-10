<?php
/**
 * Forminator transport adapter: validates submitted date ranges and overwrites
 * the browser-provided Number value with the server-calculated result.
 *
 * @package DMMF
 */

namespace DMMF\Integration;

use DMMF\Configuration\RuleRepository;
use DMMF\Domain\DateCalculator;
use InvalidArgumentException;

final class ForminatorSubmissionProcessor {

	/** @var RuleRepository */
	private $rules;

	/** @var ForminatorFormInspector */
	private $inspector;

	/** @var DateValueParser */
	private $parser;

	/** @var DateCalculator */
	private $calculator;

	/**
	 * @param RuleRepository           $rules Configured rule source.
	 * @param ForminatorFormInspector $inspector Read-only live Forminator boundary.
	 */
	public function __construct( RuleRepository $rules, ForminatorFormInspector $inspector ) {
		$this->rules      = $rules;
		$this->inspector  = $inspector;
		$this->parser     = new DateValueParser();
		$this->calculator = new DateCalculator();
	}

	/**
	 * Places the trusted result in Forminator's prepared submission data before
	 * it evaluates Calculation fields. Forminator invokes this integration point
	 * after sanitising the request and before it builds the entry field data.
	 *
	 * The same method is attached to the AJAX and normal-submit hooks. It does
	 * not alter Forminator files or rely on a browser-provided Number value.
	 *
	 * @param int $form_id Forminator form ID.
	 * @return void
	 */
	public function hydrate_prepared_data_before_calculation( $form_id ) {
		$rule = $this->rule_for_form( $form_id );
		if ( ! $rule || ! class_exists( 'Forminator_CForm_Front_Action' ) ) {
			return;
		}

		$prepared_data = \Forminator_CForm_Front_Action::$prepared_data;
		if ( ! is_array( $prepared_data ) ) {
			return;
		}
		$dates = $this->prepared_dates( $rule, $prepared_data );
		if ( ! $dates['start'] || ! $dates['end'] || $dates['end'] < $dates['start'] ) {
			return;
		}

		try {
			\Forminator_CForm_Front_Action::$prepared_data[ $rule['fields']['output'] ] = (string) $this->calculator->calculate( $dates['start'], $dates['end'], $rule['mode'] );
		} catch ( InvalidArgumentException $exception ) {
			return;
		}
	}

	/**
	 * Replaces a configured output token in native Forminator Calculation formulas
	 * with a value derived again from the submitted dates. Forminator still owns
	 * formula parsing, rounding and persistence; the browser cannot influence
	 * the trusted value substituted here.
	 *
	 * @param string|int|float $formula Forminator calculation formula.
	 * @param array            $prepared_data Sanitised Forminator request data.
	 * @param array            $field_settings Calculation field settings.
	 * @return string|int|float
	 */
	public function replace_calculation_formula_with_trusted_output( $formula, $prepared_data, $field_settings ) {
		if ( ! is_string( $formula ) || ! is_array( $prepared_data ) || ! is_array( $field_settings ) ) {
			return $formula;
		}

		$form_id = isset( $prepared_data['form_id'] ) ? absint( $prepared_data['form_id'] ) : 0;
		$rule    = $this->rule_for_form( $form_id );
		if ( ! $rule || false === strpos( $formula, '{' . $rule['fields']['output'] . '}' ) ) {
			return $formula;
		}

		$dates = $this->prepared_dates( $rule, $prepared_data );
		if ( ! $dates['start'] || ! $dates['end'] || $dates['end'] < $dates['start'] ) {
			return $formula;
		}

		try {
			$result = $this->calculator->calculate( $dates['start'], $dates['end'], $rule['mode'] );
		} catch ( InvalidArgumentException $exception ) {
			return $formula;
		}

		return str_replace( '{' . $rule['fields']['output'] . '}', (string) $result, $formula );
	}

	/**
	 * The same Forminator filter runs for its AJAX and normal form submissions.
	 *
	 * @param array $submit_errors Existing Forminator errors.
	 * @param int   $form_id Forminator form ID.
	 * @param array $field_data Sanitized form values.
	 * @return array
	 */
	public function validate( $submit_errors, $form_id, $field_data ) {
		$submit_errors = is_array( $submit_errors ) ? $submit_errors : array();
		$rule          = $this->rule_for_form( $form_id );
		if ( ! $rule ) {
			return $submit_errors;
		}

		$dates = $this->submitted_dates( $rule, $field_data );
		if ( ! $dates['start'] || ! $dates['end'] ) {
			// Required/format validation remains Forminator's responsibility.
			return $submit_errors;
		}

		if ( $dates['end'] < $dates['start'] ) {
			return $this->add_error(
				$submit_errors,
				$rule['fields']['end'],
				! empty( $rule['error_message'] ) ? $rule['error_message'] : __( 'The end date must be the same as or later than the start date.', 'dayquanta-date-difference-for-forminator' )
			);
		}

		return $submit_errors;
	}

	/**
	 * Replaces a forged Number value after validation and before Forminator saves
	 * the entry. The browser output is therefore only a display convenience.
	 *
	 * @param array $field_data Forminator field records.
	 * @param int   $form_id Forminator form ID.
	 * @return array
	 */
	public function replace_calculated_value( $field_data, $form_id ) {
		$field_data = is_array( $field_data ) ? $field_data : array();
		$rule       = $this->rule_for_form( $form_id );
		if ( ! $rule ) {
			return $field_data;
		}

		$dates = $this->submitted_dates( $rule, $field_data );
		if ( ! $dates['start'] || ! $dates['end'] || $dates['end'] < $dates['start'] ) {
			return $field_data;
		}

		try {
			$result = $this->calculator->calculate( $dates['start'], $dates['end'], $rule['mode'] );
		} catch ( InvalidArgumentException $exception ) {
			return $field_data;
		}

		$output_found = false;
		foreach ( $field_data as $key => $field ) {
			if ( is_array( $field ) && isset( $field['name'] ) && $rule['fields']['output'] === $field['name'] ) {
				$field_data[ $key ]['value'] = (string) $result;
				$output_found               = true;
			}
		}

		/*
		 * Forminator deliberately excludes an empty, non-required Number field
		 * from field_data_array. The plugin must still save a server-calculated
		 * value if JavaScript was disabled, delayed, or tampered with.
		 */
		if ( ! $output_found ) {
			$field_data[] = array(
				'name'       => $rule['fields']['output'],
				'value'      => (string) $result,
				'field_type' => isset( $rule['output_type'] ) ? $rule['output_type'] : 'number',
			);
		}

		return $field_data;
	}

	/**
	 * @param int $form_id Forminator form ID.
	 * @return array<string, mixed>|null
	 */
	private function rule_for_form( $form_id ) {
		$rule = $this->rules->get_for_form( $form_id );
		return $rule && ! empty( $rule['enabled'] ) && $this->inspector->rule_matches_current_form( $rule ) ? $rule : null;
	}

	/**
	 * @param array<string, mixed> $rule Configured rule.
	 * @param array                $field_data Forminator field records.
	 * @return array{start: \DateTimeImmutable|null, end: \DateTimeImmutable|null}
	 */
	private function submitted_dates( array $rule, array $field_data ) {
		$indexed = array();
		foreach ( $field_data as $field ) {
			if ( is_array( $field ) && isset( $field['name'] ) ) {
				$indexed[ (string) $field['name'] ] = $field;
			}
		}

		$start_value = isset( $indexed[ $rule['fields']['start'] ]['value'] ) ? $indexed[ $rule['fields']['start'] ]['value'] : null;
		$end_value   = isset( $indexed[ $rule['fields']['end'] ]['value'] ) ? $indexed[ $rule['fields']['end'] ]['value'] : null;

		return array(
			'start' => $this->parser->parse( $start_value, $rule['date_formats']['start'] ?? 'yy-mm-dd' ),
			'end'   => $this->parser->parse( $end_value, $rule['date_formats']['end'] ?? 'yy-mm-dd' ),
		);
	}

	/**
	 * @param array<string, mixed> $rule Configured rule.
	 * @param array                $prepared_data Sanitised Forminator request data.
	 * @return array{start: \DateTimeImmutable|null, end: \DateTimeImmutable|null}
	 */
	private function prepared_dates( array $rule, array $prepared_data ) {
		$start_value = isset( $prepared_data[ $rule['fields']['start'] ] ) && is_scalar( $prepared_data[ $rule['fields']['start'] ] ) ? $prepared_data[ $rule['fields']['start'] ] : null;
		$end_value   = isset( $prepared_data[ $rule['fields']['end'] ] ) && is_scalar( $prepared_data[ $rule['fields']['end'] ] ) ? $prepared_data[ $rule['fields']['end'] ] : null;

		return array(
			'start' => $this->parser->parse( $start_value, $rule['date_formats']['start'] ?? 'yy-mm-dd' ),
			'end'   => $this->parser->parse( $end_value, $rule['date_formats']['end'] ?? 'yy-mm-dd' ),
		);
	}

	/**
	 * @param array  $errors Existing errors.
	 * @param string $field_name Forminator field ID.
	 * @param string $message Error message.
	 * @return array
	 */
	private function add_error( array $errors, $field_name, $message ) {
		foreach ( $errors as $error ) {
			if ( is_array( $error ) && isset( $error[ $field_name ] ) ) {
				return $errors;
			}
		}

		$errors[] = array( $field_name => $message );
		return $errors;
	}
}
