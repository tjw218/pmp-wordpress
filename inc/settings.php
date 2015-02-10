<?php

/**
 * Register plugin settings
 *
 * @since 0.1
 */
function pmp_admin_init(){
	register_setting('pmp_settings_fields', 'pmp_settings', 'pmp_settings_validate');

	add_settings_section('pmp_main', null, null, 'pmp_settings');

	add_settings_field('pmp_api_url', 'API URL', 'pmp_api_url_input', 'pmp_settings', 'pmp_main');
	add_settings_field('pmp_client_id', 'Client ID', 'pmp_client_id_input', 'pmp_settings', 'pmp_main');
	add_settings_field('pmp_client_secret', 'Client Secret', 'pmp_client_secret_input', 'pmp_settings', 'pmp_main');
}
add_action('admin_init', 'pmp_admin_init');

/**
 * Input field for PMP API URL
 *
 * @since 0.1
 */
function pmp_api_url_input() {
	$options = get_option('pmp_settings');
	?>
		<input id="pmp_api_url" name="pmp_settings[pmp_api_url]" type="text" value="<?php echo $options['pmp_api_url']; ?>" />
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
	?>
		<input id="pmp_client_secret" name="pmp_settings[pmp_client_secret]" type="text" value="<?php echo $options['pmp_client_secret']; ?>" />
	<?php
}

/**
 * Field validations
 *
 * @since 0.1
 */
function pmp_settings_validate($input) {
	if (!empty($input['pmp_api_url']) && filter_var($input['pmp_api_url'], FILTER_VALIDATE_URL) == false) {
		add_settings_error('pmp_settings_fields', 'pmp_api_url_error', 'Please enter a valid PMP API URL.', 'error');
		$input['pmp_api_url'] = '';
	} else
		add_settings_error('pmp_settings_fields', 'pmp_settings_updated', 'PMP settings successfully updated!', 'updated');
	return $input;
}
