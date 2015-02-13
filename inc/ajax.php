<?php

/**
 * Ajax search functionality
 *
 * @since 0.1
 */
function pmp_search() {
	$settings = get_option('pmp_settings');

	try {
		$sdk = new \Pmp\Sdk(
			$settings['pmp_api_url'], $settings['pmp_client_id'], $settings['pmp_client_secret']);
	}
	catch (\Pmp\Sdk\Exception\HostException $e) {
		echo "Invalid API host specified: $e";
	}
	catch (\Pmp\Sdk\Exception\AuthException $e) {
		echo "Bad client credentials: $e";
	}

	$data = array_merge(array('limit' => 10), $_POST);
	unset($data['action']);

	$result = $sdk->queryDocs($data);

	if (!$result) {
		print json_encode(array(
			"message" => "Got 0 results for my search - doh!",
			"success" => false
		));
	} else {
		print json_encode(array(
			"items" => $result->items(),
			"success" => true
		));
	}
	wp_die();
}
add_action('wp_ajax_pmp_search', 'pmp_search');
