<?php

class TestFunctions extends WP_UnitTestCase {
	function test_pmp_render_template() {
		$this->expectOutputRegex('/<h2>Search the Platform<\/h2>/');
		pmp_render_template('search.php', array(
			'PMP' => array(),
			'creators' => array(),
			'profiles' => array()
		));
	}

	function test_pmp_get_creators() {
		$creators = pmp_get_creators();
		$expected_creators = array(
			'APM' => '98bf597a-2a6f-446c-9b7e-d8ae60122f0d',
			'NPR' => '6140faf0-fb45-4a95-859a-070037fafa01',
			'PBS' => 'fc53c568-e939-4d9c-86ea-c2a2c70f1a99',
			'PRI' => '7a865268-c9de-4b27-a3c1-983adad90921',
			'PRX' => '609a539c-9177-4aa7-acde-c10b77a6a525'
		);

		$this->assertTrue(count($creators) == count($expected_creators));

		foreach (array_keys($expected_creators) as $key)
			$this->assertTrue(in_array($key, array_keys($creators)));

		foreach (array_values($expected_creators) as $val)
			$this->assertTrue(in_array($val, array_values($creators)));
	}

	function test_pmp_get_profiles() {
		$profiles = pmp_get_profiles();
		$expected_profiles = array(
			'Story' => 'story',
			'Audio' => 'audio',
			'Video' => 'video',
			'Image' => 'image',
			'Series' => 'series',
			'Episode' => 'episode'
		);

		$this->assertTrue(count($profiles) == count($expected_profiles));

		foreach (array_keys($expected_profiles) as $key)
			$this->assertTrue(in_array($key, array_keys($profiles)));

		foreach (array_values($expected_profiles) as $val)
			$this->assertTrue(in_array($val, array_values($profiles)));
	}

	function test_pmp_media_sideload_image() {
		$new_post = $this->factory->post->create();
		$url = 'http://publicmediaplatform.org/wp-content/uploads/logo1.png';
		$desc = 'Test description';

		$image_id = pmp_media_sideload_image($url, $new_post, $desc);
		$this->assertTrue(!is_wp_error($image_id));

		$attachment = get_post($image_id);
		$this->assertEquals($desc, $attachment->post_title);
	}

	function test_pmp_verify_settings() {
		// Since we're setting the pmp_settings in bootstrap.php, this
		// should return true
		$this->assertTrue(pmp_verify_settings());
	}

	function test_pmp_on_post_status_transition() {
		$sdk_wrapper = new SDKWrapper();

		// A test query that's all but guaranteed to return at least one result.
		$query = array(
			'text' => 'Obama',
			'limit' => 10,
			'profile' => 'story'
		);

		$editor = $this->factory->user->create();
		$user = get_user_by('id', $editor);
		$user->set_role('editor');
		wp_set_current_user($user->ID);

		$result = $sdk_wrapper->query2json('queryDocs', $query);
		$pmp_story = $result['items'][0];
		$_POST['post_data'] = addslashes(json_encode($pmp_story));

		// Create the story as a draft
		$ret = _pmp_create_post(true);

		$pmp_posts = pmp_get_pmp_posts();
		$pmp_post = $pmp_posts[0];
		$custom_fields = get_post_custom($pmp_post->ID);
		$date = date('Y-m-d H:i:s', strtotime($custom_fields['pmp_published'][0]));

		// Transition to published
		wp_publish_post($pmp_post->ID);
		$pmp_post_after_transition = get_post($pmp_post->ID);

		// The date should be the same as the original PMP published date, not the current date/time.
		$this->assertEquals($pmp_post_after_transition->post_date, $date);
	}

	function test_pmp_publish_and_push_to_pmp_button() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_push_to_pmp() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_post_is_mine() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_get_post_data_from_pmp_doc() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_get_post_meta_from_pmp_doc() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_var_log() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}
}
