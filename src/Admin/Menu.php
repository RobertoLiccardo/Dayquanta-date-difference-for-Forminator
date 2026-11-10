<?php
/**
 * WordPress menu entry point for the DayQuanta configuration screen.
 *
 * @package DMMF
 */

namespace DMMF\Admin;

final class Menu {

	/** @var RuleEditor */
	private $editor;

	/**
 	 * @param RuleEditor $editor DayQuanta configuration screen.
	 */
	public function __construct( RuleEditor $editor ) {
		$this->editor = $editor;
	}

	/**
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 30 );
	}

	/**
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'forminator',
			__( 'DayQuanta Date Difference', 'dayquanta-date-difference-for-forminator' ),
			__( 'DayQuanta', 'dayquanta-date-difference-for-forminator' ),
			'manage_options',
			'dayquanta-date-difference-for-forminator',
			array( $this, 'render_page' )
		);
	}

	/**
	 * @return void
	 */
	public function render_page() {
		$this->editor->render_page();
	}
}

