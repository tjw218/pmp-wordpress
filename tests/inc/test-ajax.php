<?php

class TestAjax extends WP_Ajax_UnitTestCase {
	function setUp() {
		parent::setUp();

		$settings = get_option('pmp_settings');

		if (empty($settings['pmp_api_url']) || empty($settings['pmp_client_id']) || empty($settings['pmp_client_secret']))
			$this->skip = true;
		else {
			$this->skip = false;
			$this->sdk_wrapper = new SDKWrapper();

			// A test query that's all but guaranteed to return at least one result.
			$this->query = array(
				'text' => 'Obama',
				'limit' => 10,
				'profile' => 'story'
			);

			$this->editor = $this->factory->user->create();
			$user = get_user_by('id', $this->editor);
			$user->set_role('editor');
			wp_set_current_user($user->ID);
		}
	}

	function test_pmp_search() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		$_POST['query'] = addslashes(json_encode($this->query));
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

		$_POST['post_data'] = addslashes(json_encode($pmp_story));
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

		$_POST['post_data'] = addslashes(json_encode($pmp_story));
		$_POST['security'] = wp_create_nonce('pmp_ajax_nonce');

		try {
			$this->_handleAjax("pmp_draft_post");
		} catch (WPAjaxDieContinueException $e) {
			$result = json_decode($this->_last_response, true);
			$this->assertTrue($result['success']);
		}
	}

	function test_pmp_create_group() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_modify_group() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_default_group() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_save_users() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_create_collection() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_modify_collection() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_default_collection() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test__pmp_create_doc() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test__pmp_modify_doc() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test__pmp_ajax_create_post() {
		$this->markTestSkipped(
			'Functional test of `_pmp_ajax_create_post` performed by `test_pmp_draft_post` and `test_pmp_publish_post`');
	}

	function test__pmp_create_post() {
		$this->markTestSkipped(
			'Functional test of `_pmp_create_post` performed by `test_pmp_draft_post` and `test_pmp_publish_post`');
	}
}
