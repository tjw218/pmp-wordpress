<?php

class TestAjax extends WP_Ajax_UnitTestCase {
	function setUp() {
		$settings = get_option('pmp_settings');

		if (empty($settings['pmp_api_url']) || empty($settings['pmp_client_id']) || empty($settings['pmp_client_secret']))
			$this->skip = true;
		else
			$this->skip = false;

		// A test query that's all but guaranteed to return at least one result.
		$this->query = array(
			'text' => 'Obama',
			'limit' => 10,
			'profile' => 'story'
		);
	}

	function test_pmp_search() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		$_POST['query'] = $this->query;

		try {
			$this->_handleAjax("pmp_search");
		} catch (WPAjaxDieContinueException $e) {
			$result = json_decode($this->_last_response);
			$this->assertTrue($result['success']);
		}
	}

	function test_pmp_draft_post() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_publish_post() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test__pmp_create_post() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}
		$this->markTestIncomplete('This test has not been implemented yet.');
	}
}
