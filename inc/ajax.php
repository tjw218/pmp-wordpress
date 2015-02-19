<?php

include_once __DIR__ . '/models.php';

/**
 * Ajax search functionality
 *
 * @since 0.1
 */
function pmp_search() {
	check_ajax_referer('pmp_ajax_nonce', 'security');

	$settings = get_option('pmp_settings');

	$sdk = new SDKWrapper();

	$opts = array_merge(array(
		'profile' => 'story',
		'limit' => 10
	), $_POST['query']);

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

/**
 * Ajax function to create a draft post based on PMP story
 *
 * @since 0.1
 */
function pmp_draft_post() {
	check_ajax_referer('pmp_ajax_nonce', 'security');
	_pmp_create_post(true);
}
add_action('wp_ajax_pmp_draft_post', 'pmp_draft_post');

/**
 * Ajax function to publish a post based on PMP story
 *
 * @since 0.1
 */
function pmp_publish_post() {
	check_ajax_referer('pmp_ajax_nonce', 'security');
	_pmp_create_post();
}
add_action('wp_ajax_pmp_publish_post', 'pmp_publish_post');

function _pmp_create_post($draft=false) {
	$data = $_POST['post_data'];

	$post_data = array(
		'post_title' => $data['title'],
		'post_content' => $data['contentencoded'],
		'post_author' => 1,
		'post_status' => (!empty($draft))? 'draft' : 'publish'
	);

	$new_post = wp_insert_post($post_data);

	if (is_wp_error($new_post)) {
		print json_encode(array(
			"success" => false,
			"message" => $new_post->get_error_message()
		));
		wp_die();
	}

	$post_meta = array(
		'pmp_guid' => $data['guid'],
		'pmp_created' => $data['created'],
		'pmp_modified' => $data['modified']
	);

	foreach ($post_meta as $key => $value)
		update_post_meta($new_post, $key, $value);

	print json_encode(array(
		"success" => true,
		"data" => array(
			"edit_url" => html_entity_decode(get_edit_post_link($new_post))
		)
	));
	wp_die();
}