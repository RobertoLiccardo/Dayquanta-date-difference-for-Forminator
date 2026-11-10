<?php
/**
 * Read-only boundary around Forminator form and field structures.
 *
 * @package DMMF
 */

namespace DMMF\Integration;

use WP_Error;

final class ForminatorFormInspector {

	/**
	 * @return array<int, array{id:int,name:string}>
	 */
	public function forms() {
		$forms = \Forminator_API::get_forms( null, 1, 999, 'publish' );
		if ( $forms instanceof WP_Error || ! is_array( $forms ) ) {
			return array();
		}

		$result = array();
		foreach ( $forms as $form ) {
			if ( ! is_object( $form ) || empty( $form->id ) ) {
				continue;
			}

			$settings = isset( $form->settings ) && is_array( $form->settings ) ? $form->settings : array();
			/* translators: %d: Forminator form ID. */
			$fallback_name = sprintf( __( 'Form #%d', 'dayquanta-date-difference-for-forminator' ), absint( $form->id ) );
			$result[] = array(
				'id'   => absint( $form->id ),
				'name' => isset( $settings['formName'] ) && is_string( $settings['formName'] ) ? $settings['formName'] : $fallback_name,
			);
		}

		return $result;
	}

	/**
	 * @param int $form_id Forminator form ID.
	 * @return array<int, array{id:string,label:string,type:string,date_format:string}>
	 */
	public function compatible_fields( $form_id ) {
		$form = $this->form( $form_id );
		if ( ! $form ) {
			return array();
		}

		$fields = array();
		foreach ( $form->get_fields() as $field ) {
			$settings = $field->to_formatted_array();
			$id       = isset( $settings['element_id'] ) ? (string) $settings['element_id'] : '';
			$type     = isset( $settings['type'] ) ? (string) $settings['type'] : '';
			if ( ! $id || ! in_array( $type, array( 'date', 'number', 'hidden' ), true ) ) {
				continue;
			}

			$format = 'date' === $type && isset( $settings['date_format'] ) ? (string) $settings['date_format'] : '';
			if ( 'date' === $type && ! $this->is_supported_date_format( $format ) ) {
				continue;
			}

			$fields[] = array(
				'id'          => $id,
				'label'       => isset( $settings['field_label'] ) && is_string( $settings['field_label'] ) && '' !== $settings['field_label'] ? $settings['field_label'] : $id,
				'type'        => $type,
				'date_format' => $format,
			);
		}

		return $fields;
	}

	/**
	 * Confirms that every selected field exists in this exact form and is of a
	 * type supported by the current version.
	 *
	 * @param int    $form_id Forminator form ID.
	 * @param string $start Start field ID.
	 * @param string $end End field ID.
	 * @param string $output Number or Hidden output field ID.
	 * @return array<string, string>|null
	 */
	public function validate_rule_fields( $form_id, $start, $end, $output ) {
		$fields = $this->compatible_fields( $form_id );
		$index  = array();
		foreach ( $fields as $field ) {
			$index[ $field['id'] ] = $field;
		}

		if ( ! isset( $index[ $start ], $index[ $end ], $index[ $output ] ) || 'date' !== $index[ $start ]['type'] || 'date' !== $index[ $end ]['type'] || ! in_array( $index[ $output ]['type'], array( 'number', 'hidden' ), true ) ) {
			return null;
		}

		if ( $index[ $start ]['date_format'] !== $index[ $end ]['date_format'] ) {
			return null;
		}

		return array(
			'start'        => $index[ $start ]['date_format'],
			'end'          => $index[ $end ]['date_format'],
			'output_type'  => $index[ $output ]['type'],
		);
	}

	/**
	 * Detects the one configuration error that needs a dedicated admin message:
	 * the selected start and end Datepickers use different formats.
	 *
	 * @param int    $form_id Forminator form ID.
	 * @param string $start Start field ID.
	 * @param string $end End field ID.
	 * @return bool
	 */
	public function date_fields_have_different_formats( $form_id, $start, $end ) {
		$index = array();
		foreach ( $this->compatible_fields( $form_id ) as $field ) {
			$index[ $field['id'] ] = $field;
		}

		return isset( $index[ $start ], $index[ $end ] )
			&& 'date' === $index[ $start ]['type']
			&& 'date' === $index[ $end ]['type']
			&& $index[ $start ]['date_format'] !== $index[ $end ]['date_format'];
	}

	/**
	 * Confirms that a saved rule still agrees exactly with its live Forminator
	 * fields. A changed date format or output type makes the rule inactive until
	 * the administrator reviews and saves it again.
	 *
	 * @param array<string,mixed> $rule Saved DayQuanta Date Difference rule.
	 * @return bool
	 */
	public function rule_matches_current_form( array $rule ) {
		if ( empty( $rule['form_id'] ) || empty( $rule['fields'] ) || ! is_array( $rule['fields'] ) || empty( $rule['date_formats'] ) || ! is_array( $rule['date_formats'] ) ) {
			return false;
		}

		$details = $this->validate_rule_fields(
			$rule['form_id'],
			$rule['fields']['start'] ?? '',
			$rule['fields']['end'] ?? '',
			$rule['fields']['output'] ?? ''
		);

		return null !== $details
			&& isset( $rule['date_formats']['start'], $rule['date_formats']['end'], $rule['output_type'] )
			&& $details['start'] === $rule['date_formats']['start']
			&& $details['end'] === $rule['date_formats']['end']
			&& $details['output_type'] === $rule['output_type'];
	}

	/**
	 * @param int $form_id Forminator form ID.
	 * @return object|null
	 */
	private function form( $form_id ) {
		if ( 'publish' !== get_post_status( absint( $form_id ) ) ) {
			return null;
		}

		$form = \Forminator_API::get_form( absint( $form_id ) );
		return $form instanceof WP_Error ? null : $form;
	}

	/**
	 * @param string $format jQuery UI Datepicker format.
	 * @return bool
	 */
	private function is_supported_date_format( $format ) {
		return in_array( $format, array( 'yy-mm-dd', 'yy/mm/dd', 'dd/mm/yy', 'mm/dd/yy', 'dd-mm-yy', 'mm-dd-yy' ), true );
	}
}
