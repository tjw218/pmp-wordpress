<?php

include_once __DIR__ . '/models.php';

/**
 * Ajax search functionality
 *
 * @since 0.1
 */
function pmp_search() {
	$settings = get_option('pmp_settings');

	$sdk = new SDKWrapper();

	$opts = array_merge(array(
		'profile' => 'story',
		'limit' => 10
	), $_POST);
	unset($opts['action']);

	$result = $sdk->query2json('queryDocs', $opts);

	if (!$result) {
		header("HTTP/1.0 404 Not Found");
		print json_encode(array(
			"message" => "No results found.",
			"success" => false
		));
	} else {
		print json_encode(array(
			"data" => $result,
			"success" => true
		));
	}
	wp_die();
}
add_action('wp_ajax_pmp_search', 'pmp_search');
