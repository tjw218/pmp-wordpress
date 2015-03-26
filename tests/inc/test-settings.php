<?php

class TestSettings extends WP_UnitTestCase {
	function test_pmp_admin_init() {
		pmp_admin_init();

		global $new_whitelist_options;
		$this->assertTrue(isset($new_whitelist_options['pmp_settings_fields']));

		global $wp_settings_sections;
		$section = array(
			'id' => 'pmp_main',
			'title' => null,
			'callback' => null
		);
		$this->assertTrue($wp_settings_sections['pmp_settings']['pmp_main'] == $section);

		global $wp_settings_fields;
		$fields = array(
			array(
				'id' => 'pmp_api_url',
				'title' => 'API URL',
				'callback' => 'pmp_api_url_input',
				'args' => null
			),
			array(
				'id' => 'pmp_client_id',
				'title' => 'Client ID',
				'callback' => 'pmp_client_id_input',
				'args' => null
			),
			array(
				'id' => 'pmp_client_secret',
				'title' => 'Client Secret',
				'callback' => 'pmp_client_secret_input',
				'args' => null
			)
		);

		foreach ($fields as $field) {
			$this->assertTrue($wp_settings_fields['pmp_settings']['pmp_main'][$field['id']] == $field);
		}
	}

	function test_pmp_api_url_input() {
		$expect = '/<input id="pmp_api_url" name="pmp_settings\[pmp_api_url\]" type="text"/';
		$this->expectOutputRegex($expect);
		pmp_api_url_input();
	}

	function test_pmp_client_id_input() {
		$expect = '/<input id="pmp_client_id" name="pmp_settings\[pmp_client_id\]" type="text"/';
		$this->expectOutputRegex($expect);
		pmp_client_id_input();
	}

	function test_pmp_client_secret_input() {
		$expect = '/<a href="#" id="pmp_client_secret_reset">Change client secret<\/a>/';
		$this->expectOutputRegex($expect);
		pmp_client_secret_input();
	}

	function test_pmp_settings_validate() {
		$options = get_option('pmp_settings');

		// Make sure the pmp_api_url is well-formed.
		$invalid_url_input = array(
			'pmp_api_url' => 'NOT_AN_URL'
		);
		$result = pmp_settings_validate($invalid_url_input);
		$this->assertEquals($result['pmp_api_url'], '');

		// If the pmp_client_secret option is set, but the input sent over the wire is blank,
		// don't empty pmp_client_secret.
		$client_secret_blank_input = array(
			'pmp_api_url' => $options['pmp_api_url'],
			'pmp_client_id' => $options['pmp_client_id']
		);
		$result = pmp_settings_validate($client_secret_blank_input);
		$this->assertEquals($result['pmp_client_secret'], $options['pmp_client_secret']);

		// Likewise, if the pmp_client_secret input is not blank, make sure the result
		// includes it.
		$client_secret_new_input = array_merge(array(
			'pmp_client_secret' => 'NEW_CLIENT_SECRET'
		), $client_secret_blank_input);
		$result = pmp_settings_validate($client_secret_new_input);
		$this->assertEquals($client_secret_new_input['pmp_client_secret'], $result['pmp_client_secret']);
	}
}
