<?php
/**
 * AspireUpdate - Update plugins and themes for WordPress.
 *
 * @package     aspire-update
 * @author      AspireUpdate
 * @copyright   AspireUpdate
 * @license     GPLv2
 *
 * Plugin Name:       AspireUpdate
 * Plugin URI:        https://aspirepress.org/
 * Description:       Update plugins and themes for WordPress.
 * Version:           0.6
 * Author:            AspirePress
 * Author URI:        https://docs.aspirepress.org/aspireupdate/
 * Requires at least: 5.3
 * Requires PHP:      7.4
 * Tested up to:      6.7
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Text Domain:       aspireupdate
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/aspirepress/aspireupdate
 * Primary Branch:    main
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! defined( 'AP_VERSION' ) ) {
	define( 'AP_VERSION', '0.6' );
}


add_action( 'plugins_loaded', 'define_constant' );
function define_constant() {
	if ( ! defined( 'AP_PATH' ) ) {
		define( 'AP_PATH', dirname( plugin_basename( __FILE__ ) ) );
	}
}

require_once __DIR__ . '/includes/autoload.php';

add_action( 'plugins_loaded', 'aspire_update' );
function aspire_update() {
	if ( ! defined( 'AP_RUN_TESTS' ) ) {
		new AspireUpdate\Controller();
	}
}

register_activation_hook( __FILE__, 'aspire_update_activation_hook' );
function aspire_update_activation_hook() {
	register_uninstall_hook( __FILE__, 'aspire_update_uninstall_hook' );
}

function aspire_update_uninstall_hook() {
	$admin_settings = AspireUpdate\Admin_Settings::get_instance();
	$admin_settings->delete_all_settings();
}

// Load and start translations updater.
add_action( 'init', 'aspireupdate_init_translations' );
function aspireupdate_init_translations() {
	require_once __DIR__ . '/vendor/afragen/autoloader/Autoloader.php';
	new Fragen\Autoloader( [ 'Fragen\\Translations_Updater' => __DIR__ . '/vendor/afragen/translations-updater/src/Translations_Updater' ] );
	$config = [
		'git'       => 'github',
		'type'      => 'plugin',
		'slug'      => 'aspireupdate',
		'version'   => AP_VERSION, // Current version of plugin|theme.
		'languages' => 'https://github.com/aspirepress/aspireupdate-translations',
		'branch'    => 'main',
	];

	( new \Fragen\Translations_Updater\Init() )->run( $config );
}
