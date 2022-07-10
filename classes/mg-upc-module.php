<?php

if ( ! class_exists( 'MG_UPC_Module' ) ) {

	/**
	 * Abstract class to define/implement base methods for all module classes
	 */
	abstract class MG_UPC_Module {

		private static $instances = array();

		/*
		 * Non-abstract methods
		 */

		/**
		 * Provides access to a single instance of a module using the singleton pattern
		 *
		 * @return static
		 */
		public static function get_instance() {
			$module = get_called_class();

			if ( ! isset( self::$instances[ $module ] ) ) {
				self::$instances[ $module ] = new $module();
			}

			return self::$instances[ $module ];
		}

		/*
		 * Abstract methods
		 */

		/**
		 * Constructor
		 */
		abstract protected function __construct();

		/**
		 * Prepares sites to use the plugin during single or network-wide activation
		 *
		 * @param bool $network_wide
		 */
		abstract public function activate( $network_wide );

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 */
		abstract public function deactivate();

		/**
		 * Register callbacks for actions and filters
		 */
		abstract public function register_hook_callbacks();

		/**
		 * Initializes variables
		 */
		abstract public function init();

		/**
		 * Checks if the plugin was recently updated and upgrades if necessary
		 *
		 * @param int|string $db_version
		 */
		abstract public function upgrade( $db_version = 0 );

	} // end MG_UPC_Module
}
