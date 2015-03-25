<?php

/**
 * Enqueue styles and scripts for the search page
 *
 * @since 0.1
 */
function pmp_enqueue_assets() {
	if (isset($_GET['page'])) {
		$page = $_GET['page'];

		if ($page == 'pmp-search') {
			wp_enqueue_style('pmp-search', PMP_PLUGIN_DIR_URI . '/assets/css/style.css');
			wp_enqueue_script(
				'pmp-search', PMP_PLUGIN_DIR_URI . '/assets/js/pmp.js',
				array('jquery', 'underscore', 'backbone'), PMP_VERSION, true);
		}

		if ($page == 'pmp-options-menu') {
			wp_enqueue_script(
				'pmp-options-menu', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-options.js',
				array('jquery', 'underscore'), PMP_VERSION, true);
		}
	}
}
add_action('admin_enqueue_scripts', 'pmp_enqueue_assets');
