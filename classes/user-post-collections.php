<?php

if ( ! class_exists( 'User_Post_Collections' ) ) {

	/**
	 * Main / front controller class
	 *
	 */
	class User_Post_Collections extends MG_UPC_Module {

		protected $modules;

		/**
		 * @var MG_List_Model
		 */
		public $model;

		const VERSION    = '0.1.2';
		const PREFIX     = 'mg_upc_';
		const DEBUG_MODE = false;

		/**
		 * Constructor
		 *
		 * @mvc Controller
		 */
		protected function __construct() {
			$this->register_hook_callbacks();

			$this->model = MG_List_Model::get_instance();

			$this->modules = array(
				'MG_UPC_Settings'        => MG_UPC_Settings::get_instance(),
				'MG_UPC_Cron'            => MG_UPC_Cron::get_instance(),
				'MG_UPC_List_Controller' => MG_UPC_List_Controller::get_instance(),
				'MG_UPC_List_Page'       => MG_UPC_List_Page::get_instance(),
				'MG_UPC_Database'        => MG_UPC_Database::get_instance(),
				'MG_UPC_Rest_API'        => MG_UPC_Rest_API::get_instance(),
				'MG_UPC_Buttons'         => MG_UPC_Buttons::get_instance(),
				'MG_UPC_Woocommerce'     => MG_UPC_Woocommerce::get_instance(),
			);
		}

		/*
		 * Static methods
		 */

		/**
		 * Enqueues CSS, JavaScript, etc
		 *
		 * @mvc Controller
		 */
		public static function load_resources() {

			wp_register_script(
				self::PREFIX . 'mg-user-post-collections-client',
				plugins_url( 'javascript/mg-upc-client/dist/main.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				self::VERSION,
				true
			);

			wp_register_style(
				self::PREFIX . 'mg-user-post-collections-client',
				plugins_url( 'javascript/mg-upc-client/dist/css/styles.css', dirname( __FILE__ ) ),
				array(),
				self::VERSION,
				'all'
			);

			wp_localize_script(
				self::PREFIX . 'mg-user-post-collections-client',
				'MgUserPostCollections',
				array(
					'root'    => esc_url_raw( rest_url() ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
					'user_id' => get_current_user_id(),
					'types'   => MG_UPC_Helper::get_instance()->get_list_types(),
				)
			);

			wp_register_style(
				self::PREFIX . 'admin',
				plugins_url( 'css/admin.css', dirname( __FILE__ ) ),
				array(),
				self::VERSION,
				'all'
			);

			if ( is_admin() ) {
				wp_enqueue_style( self::PREFIX . 'admin' );
			} else {
				wp_enqueue_script( self::PREFIX . 'mg-user-post-collections-client' );
				wp_enqueue_style( self::PREFIX . 'mg-user-post-collections-client' );
			}
		}

		/**
		 * Clears caches of content generated by caching plugins like WP Super Cache
		 *
		 * @mvc Model
		 */
		protected static function clear_caching_plugins() {
			// WP Super Cache
			if ( function_exists( 'wp_cache_clear_cache' ) ) {
				wp_cache_clear_cache();
			}

			// W3 Total Cache
			if (
				class_exists( 'W3_Plugin_TotalCacheAdmin' ) &&
				function_exists( 'w3_instance' )
			) {
				$w3_total_cache = w3_instance( 'W3_Plugin_TotalCacheAdmin' );

				if ( method_exists( $w3_total_cache, 'flush_all' ) ) {
					$w3_total_cache->flush_all();
				}
			}
		}


		/*
		 * Instance methods
		 */

		/**
		 * Prepares sites to use the plugin during single or network-wide activation
		 *
		 * @mvc Controller
		 *
		 * @param bool $network_wide
		 */
		public function activate( $network_wide ) {
			if ( $network_wide && is_multisite() ) {
				$sites = get_sites();
				foreach ( $sites as $site ) {
					switch_to_blog( $site->id );
					$this->single_activate( $network_wide );
					restore_current_blog();
				}
			} else {
				$this->single_activate( $network_wide );
			}
		}

		/**
		 * Runs activation code on a new WPMS site when it's created
		 *
		 * @mvc Controller
		 *
		 * @param int $blog_id
		 */
		public function activate_new_site( $blog_id ) {
			switch_to_blog( $blog_id );
			$this->single_activate( true );
			restore_current_blog();
		}

		/**
		 * Prepares a single blog to use the plugin
		 *
		 * @mvc Controller
		 *
		 * @param bool $network_wide
		 */
		protected function single_activate( $network_wide ) {
			foreach ( $this->modules as $module ) {
				$module->activate( $network_wide );
			}

			flush_rewrite_rules();
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 *
		 * @mvc Controller
		 */
		public function deactivate() {
			foreach ( $this->modules as $module ) {
				$module->deactivate();
			}

			flush_rewrite_rules();
		}

		/**
		 * Register callbacks for actions and filters
		 *
		 * @mvc Controller
		 */
		public function register_hook_callbacks() {
			add_action( 'wp_enqueue_scripts', __CLASS__ . '::load_resources' );
			add_action( 'admin_enqueue_scripts', __CLASS__ . '::load_resources' );

			add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'init', array( $this, 'upgrade' ), 11 );
		}

		/**
		 * Initializes variables
		 *
		 * @mvc Controller
		 */
		public function init() {
			foreach ( $this->modules as $module ) {
				$module->init();
			}
		}

		/**
		 * Checks if the plugin was recently updated and upgrades if necessary
		 *
		 * @mvc Controller
		 *
		 * @param string|int $db_version
		 */
		public function upgrade( $db_version = 0 ) {
			$prev_version = get_option( 'mg_upc_db_version', '0' );
			if ( version_compare( $prev_version, self::VERSION, '==' ) ) {
				return;
			}

			foreach ( $this->modules as $module ) {
				$module->upgrade( $prev_version );
			}

			update_option( 'mg_upc_db_version', self::VERSION, true );

			self::clear_caching_plugins();
		}

	} // end User_Post_Collections
}
