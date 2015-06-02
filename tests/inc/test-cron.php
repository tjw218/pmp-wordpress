<?php

class TestCron extends WP_UnitTestCase {
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

			$result = $this->sdk_wrapper->queryDocs($this->query);
			$this->pmp_story = $result->items()->first();
			$_POST['post_data'] = addslashes(json_encode($this->pmp_story));
			$ret = _pmp_create_post();
		}
	}

	function test_pmp_get_pmp_posts() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		$posts = pmp_get_pmp_posts();
		$this->assertTrue(count($posts) == 1);
	}

	function test_pmp_get_updates() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		$success = true;
		try {
			pmp_get_updates();
		} catch (Exception $e) {
			$success = false;
		}
		$this->assertTrue($success);
	}

	function test_pmp_needs_update() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		$pmp_posts = pmp_get_pmp_posts();
		$pmp_post = $pmp_posts[0];

		// Using the story we originally fetched to determine if the WP Post
		// needs updated. They should be the same, so $ret should be false.
		$ret = pmp_needs_update($pmp_post, $this->pmp_story);
		$this->assertTrue(!$ret);
	}

	function test_pmp_update_post() {
		if ($this->skip) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		$pmp_posts = pmp_get_pmp_posts();
		$pmp_post = $pmp_posts[0];

		$success = true;
		try {
			$ret = pmp_update_post($pmp_post, $this->pmp_story);
		} catch (Exception $e) {
			$success = false;
		}
		$this->assertTrue($success);
		$this->assertTrue(!is_wp_error($ret));
	}

	function test_pmp_import_for_saved_queries() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}
}
