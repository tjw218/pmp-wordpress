<?php

// TODO: fix these
define('PMP_NOTIFICATIONS_VERIFY_TOKEN', 'testverifytoken');
define('PMP_NOTIFICATIONS_SECRET', 'testsecret');

define('PMP_NOTIFICATIONS_HUB', 'notifications');
define('PMP_NOTIFICATIONS_TOPIC', 'topics/updated');

/**
 * Add '?pmp-notifications' as a valid query var
 *
 * @since 0.3
 */
function pmp_bless_notification_query_var() {
	add_rewrite_endpoint('pmp-notifications', EP_ALL);
}
add_action('init', 'pmp_bless_notification_query_var');

/**
 * Template redirect for PubSubHubBub operations
 *
 * If the request is POST, we're dealing with a notification.
 *
 * If the request is GET, we're being asked to verify a subscription.
 *
 * @since 0.3
 */
function pmp_notifications_template_redirect() {
	global $wp_query;

	if (!isset($wp_query->query_vars['pmp-notifications']))
		return false;

	if ($_SERVER['REQUEST_METHOD'] == 'POST')
		pmp_do_notification_callback($_POST);

	if ($_SERVER['REQUEST_METHOD'] == 'GET')
		pmp_subscription_verification($_GET);

	die();
}
add_action('template_redirect', 'pmp_notifications_template_redirect');

/**
 * When a user enables/disables PMP notifications service, send a subscription
 * request to the PMP notifications server.
 *
 * @param $mode string either 'subscribe' or 'unsubscribe'
 * @since 0.3
 */
function pmp_send_subscription_request($mode='subscribe', $hub=false) {
	$settings = get_option('pmp_settings');

	if (empty($hub))
		$hub = rtrim($settings['pmp_api_url'], '/') . '/' . PMP_NOTIFICATIONS_HUB;

	$sdk = new \Pmp\Sdk(
		$settings['pmp_api_url'],
		$settings['pmp_client_id'],
		$settings['pmp_client_secret']
	);

	$ret = wp_remote_post($hub, array(
		'method' => 'POST',
		'headers' => array(
			'Authorization' => 'Bearer ' . $sdk->home->getAccessToken()
		),
		'body' => array(
			'hub.callback' => get_bloginfo('url') . '/?pmp-notifications',
			'hub.mode' => $mode,
			'hub.topic' => $hub . '/' . PMP_NOTIFICATIONS_TOPIC,
			'hub.verify' => 'sync',
			'hub.secret' => PMP_NOTIFICATIONS_SECRET,
			'hub.verify_token' => PMP_NOTIFICATIONS_VERIFY_TOKEN
		)
	));

	if ($ret['response']['code'] == 204)
		return true;
	else
		return false;
}

/**
 * Handle the PMP notification hub sending subscription verification to the callback
 *
 * @since 0.3
 */
function pmp_subscription_verification($data) {
	if (isset($data['pmp-notifications']))
		unset($data['pmp-notifications']);

	$settings = get_option('pmp_settings');

	if (isset($settings['pmp_use_api_notifications']) && $settings['pmp_use_api_notifications'] == 'off')
		$mode = 'subscribe';
	else
		$mode = 'unsubscribe';

	$topic_url = implode('/', array(
		rtrim($settings['pmp_api_url'], '/'),
		PMP_NOTIFICATIONS_HUB,
		PMP_NOTIFICATIONS_TOPIC
	));

	if ($data['hub_verify_token'] == PMP_NOTIFICATIONS_VERIFY_TOKEN &&
		$data['hub_topic'] == $topic_url &&
		$data['hub_mode'] == $mode)
	{
		echo $data['hub_challenge'];
	}
}

/**
 * When the PMP notification hub sends an update, handle it
 *
 * @since 0.3
 */
function pmp_do_notification_callback() {}
