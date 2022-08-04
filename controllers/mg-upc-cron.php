<?php

if ( ! class_exists( 'MG_UPC_Cron' ) ) {

	/**
	 * Handles cron jobs and intervals
	 *
	 * Note: Because WP-Cron only fires hooks when HTTP requests are made, make sure that an external monitoring
	 *       service pings the site regularly to ensure hooks are fired frequently
	 */
	class MG_UPC_Cron extends MG_UPC_Module {

		/**
		 * Constructor
		 */
		protected function __construct() {
			$this->register_hook_callbacks();
		}

		/**
		 * Fires the maintenance cron job at a specific time of day
		 *
		 * @noinspection PhpUnused
		 */
		public static function fire_maintenance_at_time() {
			$hour     = (int) apply_filters( 'mg_upc_maintenance_hour', 1 );
			$hour_now = (int) gmdate( 'G' );

			if ( $hour_now >= $hour && $hour_now <= $hour + 1 ) {
				if ( ! get_transient( 'mg_upc_maintenance_timed_job' ) ) {
					if ( set_transient( 'mg_upc_maintenance_timed_job', true, 60 * 60 * 6 ) ) {
						$ret = $GLOBALS['mg_upc']->model->maintenance();
						if ( $ret['votes'] ) {
							mg_upc_add_notice( '[Cron User Post Collections] Old votes removes:' . $ret['votes'] );
						}
					}
				}
			}
		}

		/**
		 * Register callbacks for actions and filters
		 */
		public function register_hook_callbacks() {
			add_action( 'mg_upc_cron_maintenance', __CLASS__ . '::fire_maintenance_at_time' );

			add_action( 'init', array( $this, 'init' ) );
		}

		/**
		 * Prepares site to use the plugin during activation
		 *
		 * @param bool $network_wide
		 */
		public function activate( $network_wide ) {
			if ( wp_next_scheduled( 'mg_upc_cron_maintenance' ) === false ) {
				wp_schedule_event(
					time(),
					'hourly',
					'mg_upc_cron_maintenance'
				);
			}
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 */
		public function deactivate() {
			wp_clear_scheduled_hook( 'mg_upc_cron_maintenance' );
		}

		public function init() { }

		public function upgrade( $db_version = 0 ) { }

	} // end MG_UPC_Cron
}
