<?php

/**
 * Register plugin settings
 *
 * @since 0.1
 */
function pmp_admin_init(){
	register_setting('pmp_settings_fields', 'pmp_settings', 'pmp_settings_validate');

	add_settings_section('pmp_main', 'API Credentials', null, 'pmp_settings');

	add_settings_field('pmp_user_title', 'Connected As', 'pmp_user_title_input', 'pmp_settings', 'pmp_main');
	add_settings_field('pmp_api_url', 'PMP Environment', 'pmp_api_url_input', 'pmp_settings', 'pmp_main');
	add_settings_field('pmp_client_id', 'Client ID', 'pmp_client_id_input', 'pmp_settings', 'pmp_main');
	add_settings_field('pmp_client_secret', 'Client Secret', 'pmp_client_secret_input', 'pmp_settings', 'pmp_main');

	add_settings_section('pmp_cron', 'Misc. options', null, 'pmp_settings');

	add_settings_field(
		'pmp_use_api_notifications', 'Allow PMP API to send content updates?',
		'pmp_use_api_notifications_input', 'pmp_settings', 'pmp_cron');
		
	// add http proxy input support
	add_settings_field(
		'pmp_http_proxy', 'HTTP Proxy',
		'pmp_http_proxy_input', 'pmp_settings', 'pmp_main');		
		
}
add_action('admin_init', 'pmp_admin_init');

/**
 * Input field for http proxy
 *
 * @since 0.?
 */
function pmp_http_proxy_input() {
	$options = get_option('pmp_settings');
	?>
		<input id="pmp_http_proxy" name="pmp_settings[pmp_http_proxy]" type="text" value="<?php echo $options['pmp_http_proxy']; ?>" />
	<?php    
}


/**
 * Input field for PMP API notifications on/off
 *
 * @since 0.3
 */
function pmp_use_api_notifications_input() {
	$options = get_option('pmp_settings');
	$setting = (isset($options['pmp_use_api_notifications']))? $options['pmp_use_api_notifications'] : false;
	?>
		<input id="pmp_use_api_notifications" type="checkbox"
			name="pmp_settings[pmp_use_api_notifications]"
			<?php echo checked($setting, 'on'); ?>>Enable</input>
	<p><em>Enabling this option allows the PMP API to push to your site as new story, audio, image, etc. updates become available.<em></p>
	<p><em>This may help improve performance of your site, especially if you have a large number of imported posts.</em></p>
<?php
}

/**
 * Input field for PMP API URL
 *
 * @since 0.1
 */
function pmp_api_url_input() {
	$options = get_option('pmp_settings');
	$is_production = empty($options['pmp_api_url']) || $options['pmp_api_url'] === 'https://api.pmp.io';
	$is_sandbox = !$is_production;
	?>
		<select id="pmp_api_url" name="pmp_settings[pmp_api_url]">
		  <option <?php echo $is_production ? 'selected' : '' ?> value="https://api.pmp.io">Production</option>
		  <option <?php echo $is_sandbox ? 'selected' : '' ?> value="https://api-sandbox.pmp.io">Sandbox</option>
		</select>
	<?php
}

/**
 * Input field for client ID
 *
 * @since 0.1
 */
function pmp_client_id_input() {
	$options = get_option('pmp_settings');
	?>
		<input id="pmp_client_id" name="pmp_settings[pmp_client_id]" type="text" value="<?php echo $options['pmp_client_id']; ?>" />
	<?php
}

/**
 * Input field for client secret
 *
 * @since 0.1
 */
function pmp_client_secret_input() {
	$options = get_option('pmp_settings');

	if (empty($options['pmp_client_secret'])) { ?>
		<input id="pmp_client_secret" name="pmp_settings[pmp_client_secret]" type="password" value="" />
	<?php } else { ?>
		<a href="#" id="pmp_client_secret_reset">Change client secret</a>
	<?php }
}
/**
 * Static field for currently connected user
 *
 * @since 0.3
 */
function pmp_user_title_input() {
	$options = get_option('pmp_settings');
	if (empty($options['pmp_api_url']) || empty($options['pmp_client_id']) || empty($options['pmp_client_secret'])) {
		echo '<p><em>Not connected</em></p>';
	}
	else {
		try {
			$sdk = new SDKWrapper();
			$me = $sdk->fetchUser('me');
			$title = $me->attributes->title;
			$link = pmp_get_support_link($me->attributes->guid);
			echo "<p><a target='_blank' href='$link'>$title</a></p>";
		}
		catch (\Pmp\Sdk\Exception\AuthException $e) {
			echo '<p style="color:#a94442"><b>Unable to connect - invalid Client-Id/Secret</b></p>';
		}
		catch (\Pmp\Sdk\Exception\HostException $e) {
			echo '<p style="color:#a94442"><b>Unable to connect - ' . $options['pmp_api_url'] . ' is unreachable</b></p>';
		}
	}
}

/**
 * Field validations
 *
 * @since 0.1
 */
function pmp_settings_validate($input) {
	$errors = false;
	$options = get_option('pmp_settings');

	if (empty($input['pmp_client_secret']) && !empty($options['pmp_client_secret']))
		$input['pmp_client_secret'] = $options['pmp_client_secret'];

	if (!empty($input['pmp_api_url']) && filter_var($input['pmp_api_url'], FILTER_VALIDATE_URL) == false) {
		add_settings_error('pmp_settings_fields', 'pmp_api_url_error', 'Please enter a valid PMP API URL.', 'error');
		$input['pmp_api_url'] = '';
		$errors = true;
	} else {
		add_settings_error('pmp_settings_fields', 'pmp_settings_updated', 'PMP settings successfully updated!', 'updated');
		$errors = true;
	}

	if (!empty($input['pmp_use_api_notifications']) && !isset($options['pmp_use_api_notifications'])) {
		foreach (pmp_get_topic_urls() as $topic_url) {
			$result = pmp_send_subscription_request('subscribe', $topic_url);
			if ($result !== true) {
				add_settings_error('pmp_settings_fields', 'pmp_notifications_subscribe_error', $result, 'error');
				$errors = true;
			}
		}
	} else if (empty($input['pmp_use_api_notifications']) && isset($options['pmp_use_api_notifications'])) {
		foreach (pmp_get_topic_urls() as $topic_url) {
			$result = pmp_send_subscription_request('unsubscribe', $topic_url);
			if ($result !== true) {
				add_settings_error('pmp_settings_fields', 'pmp_notifications_unsubscribe_error', $result, 'error');
				$errors = true;
			}
		}
	}

	if (empty($errors))
		pmp_update_my_guid_transient();

	return $input;
}
