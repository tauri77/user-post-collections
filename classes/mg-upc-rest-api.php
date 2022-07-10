<?php


class MG_UPC_Rest_API extends MG_UPC_Module {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 * Create REST API end points
	 */
	public function rest_api_init() {
		$controller = new MG_UPC_REST_Lists_Controller();
		$controller->register_routes();

		$controller_items = new MG_UPC_REST_List_Items_Controller();
		$controller_items->register_routes();
	}

	public function activate( $network_wide ) {
	}

	public function deactivate() {
	}

	public function register_hook_callbacks() {
	}

	public function init() {
	}

	public function upgrade( $db_version = 0 ) {
	}
}
