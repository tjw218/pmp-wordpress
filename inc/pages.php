<?php

/**
 * Render the plugin's options page
 *
 * @since 0.1
 */
function pmp_options_page() {
	if (!current_user_can('manage_options'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	pmp_render_template('settings.php');
}

/**
 * Render the plugin's search page
 *
 * @since 0.1
 */
function pmp_search_page() {
	if (!current_user_can('edit_posts'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	pmp_render_template('search.php');
}
