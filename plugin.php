<?php
/**
 * Plugin Name: WordPress PMP
 * Plugin URI: TKTK
 * Description: Integrate PMP.io with WordPress
 * Author: Ryan Nagle (Investigative News Network)
 * Version: 0.1
 * Author URI: http://nerds.investigativenewsnetwork.org/
 * License: TKTK
 */

/**
 * Plugin set up and init
 *
 * @since 0.1
 */
function pmp_init() {
	define('PMP_PLUGIN_DIR', __DIR__);
	define('PMP_TEMPLATE_DIR', PMP_PLUGIN_DIR . '/templates');

	$includes = array(
		'lib/functions.php',
		'inc/settings.php'
	);

	foreach ($includes as $include)
		include_once PMP_PLUGIN_DIR . '/' . $include;
}
add_action('widgets_init', 'pmp_init');

/**
 * Register the plugin's menu and options page
 *
 * @since 0.1
 */
function pmp_plugin_menu() {
	$page_title = 'PMP Settings';
	$menu_title = 'PMP Settings';
	$capability = 'manage_options';
	$menu_slug = 'pmp-options-menu';
	$function = 'pmp_options_page';

	add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);
}
add_action('admin_menu', 'pmp_plugin_menu');

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
