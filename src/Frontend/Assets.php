<?php
/**
 * Loads the browser-side adapter for all enabled DayQuanta rules.
 *
 * @package DMMF
 */

namespace DMMF\Frontend;

use DMMF\Configuration\RuleRepository;
use DMMF\Integration\ForminatorFormInspector;

final class Assets {

	/** @var RuleRepository */
	private $rules;

	/** @var ForminatorFormInspector */
	private $inspector;

	/**
	 * @param RuleRepository           $rules Rule source.
	 * @param ForminatorFormInspector $inspector Read-only live Forminator boundary.
	 */
	public function __construct( RuleRepository $rules, ForminatorFormInspector $inspector ) {
		$this->rules     = $rules;
		$this->inspector = $inspector;
	}

	/**
	 * @return void
	 */
	public function enqueue() {
		$rules = array_values( array_filter( $this->rules->all(), function ( $rule ) {
			return is_array( $rule ) && ! empty( $rule['enabled'] ) && $this->inspector->rule_matches_current_form( $rule );
		} ) );
		if ( ! $rules ) {
			return;
		}

		$asset_path    = DMMF_PLUGIN_DIR . 'assets/js/dayquanta-date-difference-for-forminator.js';
		$asset_version = is_readable( $asset_path ) ? (string) filemtime( $asset_path ) : DMMF_VERSION;

		wp_enqueue_script(
			'dmmf-date-math',
			plugins_url( 'assets/js/dayquanta-date-difference-for-forminator.js', DMMF_PLUGIN_FILE ),
			array(),
			$asset_version,
			true
		);

		wp_localize_script(
			'dmmf-date-math',
			'DMMFDateMath',
			array(
				'rules'    => $rules,
				'messages' => array(
					'endBeforeStart' => __( 'Choose an end date that is the same as or later than the start date.', 'dayquanta-date-difference-for-forminator' ),
				),
			)
		);
	}
}
