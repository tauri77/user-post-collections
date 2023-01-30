<?php
/*
Plugin Name: User post collections
Plugin URI:  https://galetto.info/user-post-collections
Description: Allows users to create their post collections.
Version:     0.8.32
Author:      Mauricio Galetto
Author URI:  https://galetto.info/
Text Domain: user-post-collections
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

if ( ! defined( 'MG_UPC_PLUGIN_FILE' ) ) {
	define( 'MG_UPC_PLUGIN_FILE', __FILE__ );
}

define( 'MG_UPC_NAME', 'UserPostCollections' );
define( 'MG_UPC_REQUIRED_PHP_VERSION', '7.0' );  // because of get_called_class()
define( 'MG_UPC_REQUIRED_WP_VERSION', '4.9.6' );   // because of wp_privacy_anonymize_ip()

/** @global User_Post_Collections|null $mg_upc */
$GLOBALS['mg_upc'] = null;

/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function mg_upc_requirements_met() {
	global $wp_version;
	//require_once( ABSPATH . '/wp-admin/includes/plugin.php' );		// to get is_plugin_active() early

	if ( version_compare( PHP_VERSION, MG_UPC_REQUIRED_PHP_VERSION, '<' ) ) {
		return false;
	}

	if ( version_compare( $wp_version, MG_UPC_REQUIRED_WP_VERSION, '<' ) ) {
		return false;
	}

	return true;
}

/**
 * Prints an error that the system requirements weren't met.
 */
function mg_upc_requirements_error() {
	require_once dirname( __FILE__ ) . '/templates/requirements-error.php';
}

/*
 * Check requirements and load main class
 * The main program needs to be in a separate file that only gets loaded if the plugin requirements are met. Otherwise older PHP installations could crash when trying to parse it.
 */
if ( mg_upc_requirements_met() ) {

	require_once __DIR__ . '/includes/utils.php';
	require_once __DIR__ . '/includes/template-functions.php';
	require_once __DIR__ . '/includes/template-hooks.php';
	require_once __DIR__ . '/includes/mg-upc-cache.php';
	require_once __DIR__ . '/includes/mg-upc-list-type.php';
	require_once __DIR__ . '/includes/list-types.php';
	require_once __DIR__ . '/includes/mg-upc-helper.php';
	require_once __DIR__ . '/includes/mg-upc-texts.php';
	require_once __DIR__ . '/includes/mg-upc-settings-api.php';
	require_once __DIR__ . '/includes/themes-helper.php';

	require_once __DIR__ . '/classes/Exceptions/mg-upc-item-exist-exception.php';
	require_once __DIR__ . '/classes/Exceptions/mg-upc-invalid-field-exception.php';
	require_once __DIR__ . '/classes/Exceptions/mg-upc-item-not-found-exception.php';
	require_once __DIR__ . '/classes/Exceptions/mg-upc-required-field-exception.php';

	require_once __DIR__ . '/classes/mg-upc-module.php';

	require_once __DIR__ . '/alt-models/mg-list-model.php';
	require_once __DIR__ . '/alt-models/mg-list-items-model.php';
	require_once __DIR__ . '/alt-models/mg-list-votes-model.php';

	require_once __DIR__ . '/controllers/mg-list-page-alt.php';
	require_once __DIR__ . '/controllers/mg-upc-list-controller.php';
	require_once __DIR__ . '/controllers/mg-upc-rest-list-controller.php';
	require_once __DIR__ . '/controllers/mg-upc-rest-list-items-controller.php';
	require_once __DIR__ . '/controllers/mg-upc-buttons.php';
	require_once __DIR__ . '/controllers/mg-upc-woocommerce.php';
	require_once __DIR__ . '/controllers/mg-upc-cron.php';

	require_once __DIR__ . '/classes/user-post-collections.php';
	require_once __DIR__ . '/classes/mg-upc-list-types-register.php';
	require_once __DIR__ . '/includes/admin-notice-helper/admin-notice-helper.php';
	require_once __DIR__ . '/classes/mg-upc-settings.php';
	require_once __DIR__ . '/classes/mg-upc-rest-api.php';

	require_once __DIR__ . '/classes/mg-upc-database.php';

	if ( class_exists( 'User_Post_Collections' ) ) {
		/** @global User_Post_Collections $mg_upc */
		$GLOBALS['mg_upc'] = User_Post_Collections::get_instance();

		register_activation_hook( __FILE__, array( $GLOBALS['mg_upc'], 'activate' ) );
		register_deactivation_hook( __FILE__, array( $GLOBALS['mg_upc'], 'deactivate' ) );
		do_action( 'mg_upc_loaded' );
	}
} else {
	add_action( 'admin_notices', 'mg_upc_requirements_error' );
}
