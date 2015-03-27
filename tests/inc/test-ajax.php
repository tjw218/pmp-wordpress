<?php

class TestAjax extends WP_Ajax_UnitTestCase {
	function setUp() {
		$settings = get_option('pmp_settings');

		if (empty($settings['pmp_api_url']) || empty($settings['pmp_client_id']) || empty($settings['pmp_client_secret']))
			$this->skip = true;
		else {
			$this->skip = false;
			$this->sdk_wrapper = new SDKWrapper();
		}

		// A test query that's all but guaranteed to return at least one result.
		$this->query = array(
			'text' => 'Obama',
			'limit' => 10,
			'profile' => 'story'
		);

		parent::setUp();
	}

	function test_pmp_search() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		$_POST['query'] = json_encode($this->query);
		$_POST['security'] = wp_create_nonce('pmp_ajax_nonce');

		try {
			$this->_handleAjax("pmp_search");
		} catch (WPAjaxDieContinueException $e) {
			$result = json_decode($this->_last_response, true);
			$this->assertTrue($result['success']);
		}
	}

	function test_pmp_draft_post() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		$result = $this->sdk_wrapper->query2json('queryDocs', $this->query);
		$pmp_story = $result['items'][0];

		$_POST['post_data'] = json_encode($pmp_story);
		$_POST['security'] = wp_create_nonce('pmp_ajax_nonce');

		try {
			$this->_handleAjax("pmp_draft_post");
		} catch (WPAjaxDieContinueException $e) {
			$result = json_decode($this->_last_response, true);
			$this->assertTrue($result['success']);
		}
	}

	function test_pmp_publish_post() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		$result = $this->sdk_wrapper->query2json('queryDocs', $this->query);
		$pmp_story = $result['items'][0];

		$_POST['post_data'] = json_encode($pmp_story);
		$_POST['security'] = wp_create_nonce('pmp_ajax_nonce');

		try {
			$this->_handleAjax("pmp_draft_post");
		} catch (WPAjaxDieContinueException $e) {
			$result = json_decode($this->_last_response, true);
			$this->assertTrue($result['success']);
		}
	}

	function test__pmp_create_post() {
		$this->markTestSkipped(
			'Functional test of `_pmp_create_post` performed by `test_pmp_draft_post` and `test_pmp_publish_post`');
	}
}
