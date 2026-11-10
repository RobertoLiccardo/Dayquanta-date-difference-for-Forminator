<?php
/**
 * Plugin Name: DayQuanta Date Difference for Forminator
 * Description: Calculate secure date differences in Forminator forms. Turn two Datepicker fields into nights or calendar days for bookings and quotes.
 * Version: 1.0.1
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Requires Plugins: forminator
 * Author: Roberto Liccardo
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dayquanta-date-difference-for-forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'DMMF_VERSION' ) ) {
	return;
}

define( 'DMMF_VERSION', '1.0.1' );
define( 'DMMF_PLUGIN_FILE', __FILE__ );
define( 'DMMF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'DMMF\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$file = DMMF_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		DMMF\Core\Plugin::boot();
	},
	20
);

