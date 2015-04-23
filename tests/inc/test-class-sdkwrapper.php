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
		$this->markTestSkipped('Functional test of `prepQueryData` performed by `test_query2json`');
	}

	function test_href4guid() {
		$test_guid = 'test-guid-does-not-matter';
		$href = $this->sdk_wrapper->href4guid($test_guid);
		$this->assertTrue((bool) strpos($href, $test_guid));
		$this->assertTrue((bool) filter_var($href, FILTER_VALIDATE_URL));
	}

	function test_guid4href() {
		$test_guid = 'test-guid-does-not-matter';
		$test_href = 'http://testdomain.com/test/path/' . $test_guid;
		$guid = SDKWrapper::guid4href($test_href);
		$this->assertEquals($guid, $test_guid);
	}

	function test_commas2array() {
		$test_string = 'one, two, three, four';
		$test_array = SDKWrapper::commas2array($test_string);
		$this->assertEquals(count($test_array), 4);
	}
}
