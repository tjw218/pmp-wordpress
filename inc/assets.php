<?php

/**
 * Enqueue styles and scripts for the search page
 *
 * @since 0.1
 */
function pmp_enqueue_assets() {
	wp_register_script('pmp-common', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-common.js',
		array('jquery', 'underscore', 'backbone'), PMP_VERSION, true);

	wp_register_style('pmp-common', PMP_PLUGIN_DIR_URI . '/assets/css/style.css');

	if (isset($_GET['page'])) {
		$page = $_GET['page'];

		if ($page == 'pmp-search') {
			wp_enqueue_style('pmp-common');
			wp_enqueue_script(
				'pmp-search', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-search.js',
				array('pmp-common'), PMP_VERSION, true);
		}

		if ($page == 'pmp-groups-menu') {
			wp_enqueue_style('pmp-common');
			wp_enqueue_script(
				'pmp-groups-menu', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-groups-menu.js',
				array('pmp-common'), PMP_VERSION, true);
		}


		if ($page == 'pmp-series-properties-menu') {
			wp_enqueue_style('pmp-common');
			wp_enqueue_script(
				'pmp-series-properties-menu', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-series-properties-menu.js',
				array('pmp-common'), PMP_VERSION, true);
		}

		if ($page == 'pmp-options-menu') {
			wp_enqueue_script(
				'pmp-options-menu', PMP_PLUGIN_DIR_URI . '/assets/js/pmp-options.js',
				array('jquery', 'underscore'), PMP_VERSION, true);
		}
	}
}
add_action('admin_enqueue_scripts', 'pmp_enqueue_assets');

/**
 * Print the underscore template for the PMP.Modal view.
 *
 * @since 0.2
 */
function pmp_modal_underscore_template() { ?>
<script type="text/template" id="pmp-modal-tmpl">
	<div class="pmp-modal-header">
		<div class="pmp-modal-close"><span class="close">&#10005;</span></div>
	</div>
	<div class="pmp-modal-content"><% if (content) { %><%= content %><% } %></div>
	<div class="pmp-modal-actions">
		<span class="spinner"></span>
		<% _.each(actions, function(v, k) { %>
			<a href="#" class="<%= k %> button button-primary"><%= k %></a>
		<% }); %>
	</div>
</script><?php
}
