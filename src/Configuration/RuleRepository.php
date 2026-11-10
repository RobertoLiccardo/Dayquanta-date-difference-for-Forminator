<?php
/**
 * Versioned configuration storage for DayQuanta Date Difference rules.
 *
 * @package DMMF
 */

namespace DMMF\Configuration;

use DMMF\Domain\DateRule;

final class RuleRepository {

	const OPTION_NAME = 'dmmf_rules_v1';

	const LEGACY_POC_MIGRATION_OPTION = 'dmmf_legacy_poc_migrated_v1';

	/**
	 * Returns every valid stored rule, keyed by its Forminator form ID.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function all() {
		$stored = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}

		$rules = array();
		foreach ( $stored as $candidate ) {
			if ( ! is_array( $candidate ) ) {
				continue;
			}

			$rule = DateRule::from_array( $candidate );
			if ( $rule ) {
				$data                    = $rule->to_array();
				$rules[ $data['form_id'] ] = $data;
			}
		}

		return $rules;
	}

	/**
	 * @param int $form_id Forminator form ID.
	 * @return array<string, mixed>|null
	 */
	public function get_for_form( $form_id ) {
		$rules   = $this->all();
		$form_id = absint( $form_id );
		return isset( $rules[ $form_id ] ) ? $rules[ $form_id ] : null;
	}

	/**
	 * One rule is stored for each form. Re-saving the same form updates it.
	 *
	 * @param DateRule $rule Validated DayQuanta Date Difference rule.
	 * @return void
	 */
	public function save( DateRule $rule ) {
		$rules = $this->all();
		$data  = $rule->to_array();
		$rules[ $data['form_id'] ] = $data;
		update_option( self::OPTION_NAME, $rules, false );
	}

	/**
	 * @param int $form_id Forminator form ID.
	 * @return void
	 */
	public function delete_for_form( $form_id ) {
		$rules = $this->all();
		unset( $rules[ absint( $form_id ) ] );
		update_option( self::OPTION_NAME, $rules, false );
	}

	/**
	 * Preserves the reproducible POC when its legacy option already exists.
	 * The legacy option remains untouched because it belongs to development tools.
	 *
	 * @return void
	 */
	public function migrate_legacy_poc() {
		if ( get_option( self::LEGACY_POC_MIGRATION_OPTION, false ) ) {
			return;
		}

		$legacy = get_option( PocRuleRepository::OPTION_NAME, array() );
		if ( ! is_array( $legacy ) || empty( $legacy['form_id'] ) ) {
			return;
		}

		if ( $this->get_for_form( $legacy['form_id'] ) ) {
			update_option( self::LEGACY_POC_MIGRATION_OPTION, true, false );
			return;
		}

		$fields = isset( $legacy['fields'] ) && is_array( $legacy['fields'] ) ? $legacy['fields'] : array();
		$rule   = DateRule::from_array(
			array(
				'form_id'       => $legacy['form_id'],
				'fields'        => $fields,
				'date_formats'  => array( 'start' => 'yy-mm-dd', 'end' => 'yy-mm-dd' ),
				'operation'     => 'calendar_days_inclusive' === ( $legacy['mode'] ?? '' ) ? 'calendar_days' : 'nights',
				'inclusive_end' => 'calendar_days_inclusive' === ( $legacy['mode'] ?? '' ),
			)
		);

		if ( $rule ) {
			$this->save( $rule );
			update_option( self::LEGACY_POC_MIGRATION_OPTION, true, false );
		}
	}
}
