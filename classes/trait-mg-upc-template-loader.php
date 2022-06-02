<?php

if ( ! trait_exists( 'MG_UPC_Template_Loader' ) ) {

	/**
	 * trait to load template
	 */
	trait MG_UPC_Template_Loader {

		/**
		 * Render a template
		 *
		 * Allows parent/child themes to override the markup by placing the a file named basename( $path ) in their root folder,
		 * and also allows plugins or themes to override the markup by a filter. Themes might prefer that method if they place their templates
		 * in sub-directories to avoid cluttering the root folder. In both cases, the theme/plugin will have access to the variables so they can
		 * fully customize the output.
		 *
		 * @mvc @model
		 *
		 * @param string $path The path to the template, relative to the plugin's `views` folder
		 * @param array $variables An array of variables to pass into the template's scope, indexed with the variable name so that it can be extract()-ed
		 * @param string $require 'once' to use require_once() | 'always' to use require()
		 *
		 * @param bool $print
		 *
		 * @return string
		 */
		protected static function render_template( $path, $variables = array(), $require = 'once', $print = false ) {
			do_action( 'mg_upc_render_template_pre', $path, $variables );

			$template_path = locate_template( basename( $path ) );
			if ( ! $template_path ) {
				$template_path = dirname( __DIR__ ) . '/views/' . $path;
			}
			$template_path = apply_filters( 'mg_upc_template_path', $template_path );

			$template_content = '';

			if ( is_file( $template_path ) ) {
				extract( $variables );
				if ( ! $print ) {
					ob_start();
				}

				if ( 'always' === $require ) {
					require $template_path;
				} else {
					require_once $template_path;
				}

				if ( ! $print ) {
					$template_content = apply_filters(
						'mg_upc_template_content',
						ob_get_clean(),
						$path,
						$template_path,
						$variables
					);
				}
			}

			do_action(
				'mg_upc_render_template_post',
				$path,
				$variables,
				$template_path,
				$template_content
			);

			return $template_content;
		}
	}
}
