<?php
/**
 * Plugin Name: Restrict Content Pro AvaTax
 * Plugin URL: https://skilledcode.com/plugins/restrict-content-pro-avalara
 * Description: Avatax add-on for Restrict Content Pro
 * Version: 0.0.1
 * Author: Tanner Moushey
 * Author URI: https://skilledcode.com
 * Text Domain: rcp-avatax
 * Domain Path: languages
 */

if ( !defined( 'RCP_AVATAX_PLUGIN_DIR' ) ) {
	define( 'RCP_AVATAX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( !defined( 'RCP_AVATAX_PLUGIN_URL' ) ) {
	define( 'RCP_AVATAX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( !defined( 'RCP_AVATAX_PLUGIN_FILE' ) ) {
	define( 'RCP_AVATAX_PLUGIN_FILE', __FILE__ );
}
if ( !defined( 'RCP_AVATAX_PLUGIN_VERSION' ) ) {
	define( 'RCP_AVATAX_PLUGIN_VERSION', '0.0.1' );
}
if ( ! defined( 'CAL_GREGORIAN' ) ) {
	define( 'CAL_GREGORIAN', 1 );
}

require_once( RCP_AVATAX_PLUGIN_DIR . 'vendor/autoloader.php' );

/**
 * Load plugin text domain for translations.
 *
 * @return void
 */
function rcp_avatax_load_textdomain() {

	// Set filter for plugin's languages directory
	$rcp_lang_dir = dirname( plugin_basename( RCP_AVATAX_PLUGIN_FILE ) ) . '/languages/';
	$rcp_lang_dir = apply_filters( 'rcp_avatax_languages_directory', $rcp_lang_dir );


	// Traditional WordPress plugin locale filter

	$get_locale = get_locale();

	if ( function_exists( 'rcp_compare_wp_version' ) && rcp_compare_wp_version( 4.7 ) ) {
		$get_locale = get_user_locale();
	}

	/**
	 * Defines the plugin language locale used in RCP.
	 *
	 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
	 *                  otherwise uses `get_locale()`.
	 */
	$locale        = apply_filters( 'plugin_locale',  $get_locale, 'rcp-avatax' );
	$mofile        = sprintf( '%1$s-%2$s.mo', 'rcp-avatax', $locale );

	// Setup paths to current locale file
	$mofile_local  = $rcp_lang_dir . $mofile;
	$mofile_global = WP_LANG_DIR . '/rcp-avatax/' . $mofile;

	if ( file_exists( $mofile_global ) ) {
		// Look in global /wp-content/languages/rcp folder
		load_textdomain( 'rcp-avatax', $mofile_global );
	} elseif ( file_exists( $mofile_local ) ) {
		// Look in local /wp-content/plugins/easy-digital-downloads/languages/ folder
		load_textdomain( 'rcp-avatax', $mofile_local );
	} else {
		// Load the default language files
		load_plugin_textdomain( 'rcp-avatax', false, $rcp_lang_dir );
	}

}
add_action( 'init', 'rcp_avatax_load_textdomain' );

/*******************************************
* requirement checks
*******************************************/

if( version_compare( PHP_VERSION, '5.3', '<' ) ) {


} else {

}