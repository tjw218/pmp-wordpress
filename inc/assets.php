<?php

/**
 * Enqueue styles and scripts for the search page
 *
 * @since 0.1
 */
function pmp_enqueue_assets() {
	if (isset($_GET['page']) && $_GET['page'] == 'pmp-search') {
		wp_enqueue_style('pmp-search', PMP_PLUGIN_DIR_URI . '/assets/css/style.css');
		wp_enqueue_script(
			'pmp-search', PMP_PLUGIN_DIR_URI . '/assets/js/pmp.js',
			array('jquery', 'underscore', 'backbone'), PMP_VERSION, true);
	}
}
add_action('admin_enqueue_scripts', 'pmp_enqueue_assets');
