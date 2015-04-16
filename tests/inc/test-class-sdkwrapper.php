<?php

class TestSDKWrapper extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();

		$settings = get_option('pmp_settings');

		if (empty($settings['pmp_api_url']) || empty($settings['pmp_client_id']) || empty($settings['pmp_client_secret']))
			$this->sdk_wrapper = false;
		else {
			$this->sdk_wrapper = new SDKWrapper();

			// A test query that's all but guaranteed to return at least one result.
			$this->query = array(
				'text' => 'Obama',
				'limit' => 10,
				'profile' => 'story'
			);
		}
	}

	function test_query2json() {
		if (empty($this->sdk_wrapper)) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		/**
		 * The SDKWrapper proxies calls to \Pmp\Sdk member functions.
		 */
		$results = $this->sdk_wrapper->queryDocs($this->query);
		$this->assertTrue(!empty($results));

		/**
		 * The `query2json` function should return a data structure that produces
		 * no errors when passed to `json_encode`.
		 */
		$json_data = json_encode($this->sdk_wrapper->query2json('queryDocs', $this->query));
		$this->assertEquals(json_last_error(), JSON_ERROR_NONE);
	}

	function test_prepFetchData() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_prepQueryData() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_href4guid() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_guid4href() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_commas2array() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}
}
