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

		const VERSION = '0.9.1';

		/**
		 * Constructor
		 */
		protected function __construct() {
			$this->register_hook_callbacks();

			// initialize strings
			MG_UPC_Texts::init();

			$this->model = MG_List_Model::get_instance();

			$this->modules = array(
				'MG_UPC_Settings'            => MG_UPC_Settings::get_instance(),
				'MG_UPC_List_Types_Register' => MG_UPC_List_Types_Register::get_instance(),
				'MG_UPC_Cron'                => MG_UPC_Cron::get_instance(),
				'MG_UPC_List_Controller'     => MG_UPC_List_Controller::get_instance(),
				'MG_UPC_List_Page'           => MG_UPC_List_Page::get_instance(),
				'MG_UPC_List_Page_Settings'  => MG_UPC_List_Page_Settings::get_instance(),
				'MG_UPC_Database'            => MG_UPC_Database::get_instance(),
				'MG_UPC_Rest_API'            => MG_UPC_Rest_API::get_instance(),
				'MG_UPC_Buttons'             => MG_UPC_Buttons::get_instance(),
				'MG_UPC_Woocommerce'         => MG_UPC_Woocommerce::get_instance(),
				'MG_UPC_Shortcode'           => MG_UPC_Shortcode::get_instance(),
			);

			add_action( 'wp_ajax_nopriv_mg_upc_user', array( $this, 'ajax_user' ) );
			add_action( 'wp_ajax_mg_upc_user', array( $this, 'ajax_user' ) );
		}

		public function ajax_user() {
			$js_vars            = array();
			$js_vars['nonce']   = wp_create_nonce( 'wp_rest' );
			$js_vars['user_id'] = get_current_user_id();
			wp_send_json( $js_vars );
		}

		/*
		 * Static methods
		 */

		/**
		 * Enqueues CSS, JavaScript, etc
		 */
		public static function load_resources() {

			wp_register_script(
				'mg-user-post-collections',
				plugins_url( 'javascript/mg-upc-client/dist/main.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				self::VERSION,
				true
			);

			wp_register_script(
				'mg-user-post-collections-admin',
				plugins_url( 'javascript/mg-upc-client/dist/admin.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				self::VERSION,
				true
			);

			wp_register_style(
				'mg-user-post-collections',
				plugins_url( 'javascript/mg-upc-client/dist/css/styles.css', dirname( __FILE__ ) ),
				array(),
				self::VERSION,
				'all'
			);

			$sortable_url = plugins_url( 'javascript/Sortable.min.js', dirname( __FILE__ ) );
			//TODO: option to use cdn
			//$sortable_url = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
			$js_vars = array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			);

			$ajax_load = get_option( 'mg_upc_ajax_load', 'on' );
			if ( 'off' === $ajax_load ) {
				$js_vars['nonce']   = wp_create_nonce( 'wp_rest' );
				$js_vars['user_id'] = get_current_user_id();
			}
			if ( 'all' !== $ajax_load ) {
				$js_vars = array_merge(
					$js_vars,
					array(
						'root'         => esc_url_raw( rest_url() ),
						'types'        => MG_UPC_Helper::get_instance()->get_user_creatable_list_types(),
						'statuses'     => MG_UPC_Helper::get_instance()->get_list_statuses( false ),
						'sortable'     => $sortable_url,
						'shareButtons' => get_option( 'mg_upc_share_buttons_client', array( 'twitter', 'facebook', 'whatsapp', 'telegram', 'line', 'email' ) ),
					)
				);
			}

			wp_register_style(
				'mg-user-post-collections-admin',
				plugins_url( 'css/admin.css', dirname( __FILE__ ) ),
				array(),
				self::VERSION,
				'all'
			);

			if ( is_admin() ) {
				// Styles
				wp_enqueue_style( 'mg-user-post-collections-admin' );
				wp_enqueue_style( 'mg-user-post-collections' );
				// Scripts
				wp_enqueue_script( 'mg-user-post-collections-admin' );
				wp_localize_script(
					'mg-user-post-collections-admin',
					'MgUserPostCollections',
					$js_vars
				);
				wp_localize_script(
					'mg-user-post-collections-admin',
					'MgUpcTexts',
					MG_UPC_Texts::get_context_array( 'modal_client' )
				);
			} else {
				// Styles
				wp_enqueue_style( 'mg-user-post-collections' );
				// Scripts
				wp_enqueue_script( 'mg-user-post-collections' );
				wp_localize_script(
					'mg-user-post-collections',
					'MgUserPostCollections',
					$js_vars
				);
				wp_localize_script(
					'mg-user-post-collections',
					'MgUpcTexts',
					MG_UPC_Texts::get_context_array( 'modal_client' )
				);
			}
		}

		/**
		 * Clears caches of content generated by caching plugins like WP Super Cache
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
		 */
		public function deactivate() {
			foreach ( $this->modules as $module ) {
				$module->deactivate();
			}

			flush_rewrite_rules();
		}

		/**
		 * Register callbacks for actions and filters
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
		 */
		public function init() {
			foreach ( $this->modules as $module ) {
				$module->init();
			}
			if ( get_option( 'mg_upc_flush_rewrite', '0' ) === '1' ) {
				update_option( 'mg_upc_flush_rewrite', '0' );
				flush_rewrite_rules();
			}
		}

		/**
		 * Checks if the plugin was recently updated and upgrades if necessary
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
