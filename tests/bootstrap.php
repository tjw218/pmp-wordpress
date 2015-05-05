<?php

$wp_tests_dir = getenv('WP_TESTS_DIR');
require_once $wp_tests_dir . '/includes/functions.php';

function _manually_load_environment() {
	$plugins_to_active = array(basename(dirname(__DIR__)) . "/plugin.php");

	// allow explicitly setting the plugin slug
	if (getenv('WP_PLUGIN_SLUG')) {
		$plugins_to_active = array(getenv('WP_PLUGIN_SLUG') . "/plugin.php");
	}

	update_option('active_plugins', $plugins_to_active);

	$pmp_creds = array(
		'pmp_api_url' => getenv('PMP_API_URL'),
		'pmp_client_id' => getenv('PMP_CLIENT_ID'),
		'pmp_client_secret' => getenv('PMP_CLIENT_SECRET')
	);
	update_option('pmp_settings', $pmp_creds);

	$GLOBALS['pmp_stash'] = array();
}
tests_add_filter('muplugins_loaded', '_manually_load_environment');

require $wp_tests_dir . '/includes/bootstrap.php';
