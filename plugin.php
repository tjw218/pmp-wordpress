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

require __DIR__ . '/vendor/pmpsdk.phar';

/**
 * Plugin set up and init
 *
 * @since 0.1
 */
function pmp_init() {
	define('PMP_PLUGIN_DIR', __DIR__);
	define('PMP_PLUGIN_DIR_URI', plugins_url(basename(__DIR__), __DIR__));
	define('PMP_TEMPLATE_DIR', PMP_PLUGIN_DIR . '/templates');
	define('PMP_VERSION', 0.1);

	$includes = array(
		'inc/functions.php',
		'inc/settings.php',
		'inc/pages.php',
		'inc/assets.php',
		'inc/ajax.php',
		'inc/cron.php'
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
	$page_title = 'Public Media Platform';
	$menu_title = 'Public Media Platform';
	$capability = 'edit_posts';
	$menu_slug = 'pmp-search';
	$function = 'pmp_search_page';

	add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);

	$sub_menus = array(
		array(
			'page_title' => 'Search',
			'menu_title' => 'Search',
			'capability' => 'edit_posts',
			'menu_slug' => 'pmp-search',
			'function' => 'pmp_search_page'
		),
		array(
			'page_title' => 'Settings',
			'menu_title' => 'Settings',
			'capability' => 'manage_options',
			'menu_slug' => 'pmp-options-menu',
			'function' => 'pmp_options_page'
		)
	);

	foreach ($sub_menus as $sub_menu) {
		add_submenu_page(
			'pmp-search', $sub_menu['page_title'], $sub_menu['menu_title'],
			$sub_menu['capability'], $sub_menu['menu_slug'], $sub_menu['function']
		);
	}

}
add_action('admin_menu', 'pmp_plugin_menu');
