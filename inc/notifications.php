<?php

define('PMP_NOTIFICATIONS_SECRET', crypt(get_bloginfo('url'), wp_salt('auth')));
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

	$verify_token = hash('sha256', REQUEST_TIME);
	set_transient('pmp_verify_token', $verify_token, HOUR_IN_SECONDS);

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
			'hub.verify_token' => $verify_token
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

	$verify_token = get_transient('pmp_verify_token');

	if ($data['hub_verify_token'] == $verify_token &&
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
function pmp_do_notification_callback() {
	global $wpdb;

	$sdk = new SDKWrapper();
	$headers = getallheaders();
	$body = file_get_contents('php://input');
	$hash = hash_hmac('sha1', $body, PMP_NOTIFICATIONS_SECRET);

	$pmp_post_data = $wpdb->get_results("
		select meta_value as pmp_guid, post_id
		from $wpdb->postmeta where meta_key = 'pmp_guid'");
	$pmp_guids = array_map(function($x) { return $x->pmp_guid; }, $pmp_post_data);

	if ($headers['X-Hub-Signature'] == $hash) {
		$xml = simplexml_load_string($body);

		foreach ($xml->channel->item as $item) {
			$item_json = json_decode(json_encode($item));
			if ($idx = array_search($item_json->guid, $pmp_guids)) {
				$post = get_post($pmp_post_data[$idx]->post_id);
				// TODO: Fetching the doc seems silly if the RSS item actually
				// has all the appropriate data. However, the notifications docs
				// don't detail what information is sent over the wire, so
				// we can't make that assumption.
				$doc = $sdk->fetchDoc($pmp_post_data[$idx]->pmp_guid);
				if (!empty($doc)) {
					if (pmp_needs_update($post, $doc))
						pmp_update_post($post, $doc);
				} else
					wp_delete_post($post->ID, true);
			}
		}
	}
}
