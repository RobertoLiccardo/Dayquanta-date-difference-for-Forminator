<?php
/**
 * Plugin bootstrap and integration registration.
 *
 * @package DMMF
 */

namespace DMMF\Core;

use DMMF\Admin\Menu;
use DMMF\Admin\RuleEditor;
use DMMF\Configuration\RuleRepository;
use DMMF\Frontend\Assets;
use DMMF\Integration\ForminatorFormInspector;
use DMMF\Integration\ForminatorSubmissionProcessor;

final class Plugin {

	/**
	 * Starts DayQuanta Date Difference only when Forminator is available.
	 *
	 * @return void
	 */
	public static function boot() {
		if ( ! class_exists( 'Forminator_API' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'render_missing_dependency_notice' ) );
			return;
		}

		$rules     = new RuleRepository();
		$inspector = new ForminatorFormInspector();
		$editor    = new RuleEditor( $rules, $inspector );
		$processor = new ForminatorSubmissionProcessor( $rules, $inspector );
		$menu      = new Menu( $editor );
		$assets    = new Assets( $rules, $inspector );

		add_action( 'forminator_form_before_save_entry', array( $processor, 'hydrate_prepared_data_before_calculation' ), 10 );
		add_action( 'forminator_form_before_handle_submit', array( $processor, 'hydrate_prepared_data_before_calculation' ), 10 );
		add_filter( 'forminator_field_calculation_calculable_value', array( $processor, 'replace_calculation_formula_with_trusted_output' ), 20, 3 );
		add_filter( 'forminator_custom_form_submit_errors', array( $processor, 'validate' ), 20, 3 );
		add_filter( 'forminator_custom_form_submit_field_data', array( $processor, 'replace_calculated_value' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( $assets, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( DMMF_PLUGIN_FILE ), array( __CLASS__, 'add_dashboard_link' ) );

		$menu->register();
		$editor->register();
	}

	/**
	 * Adds a direct link to the configuration wizard from the Plugins screen.
	 *
	 * @param array<string, string> $links Existing actions for this plugin.
	 * @return array<string, string>
	 */
	public static function add_dashboard_link( array $links ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $links;
		}

		$dashboard_url = admin_url( 'admin.php?page=dayquanta-date-difference-for-forminator' );
		$dashboard_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $dashboard_url ),
			esc_html__( 'Dashboard', 'dayquanta-date-difference-for-forminator' )
		);

		array_unshift( $links, $dashboard_link );

		return $links;
	}

	/**
	 * @return void
	 */
	public static function render_missing_dependency_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'DayQuanta Date Difference for Forminator needs Forminator to be active. No form data has been changed.', 'dayquanta-date-difference-for-forminator' );
		echo '</p></div>';
	}
}

