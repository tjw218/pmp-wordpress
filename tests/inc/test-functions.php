<?php

class TestFunctions extends WP_UnitTestCase {
	function test_pmp_render_template() {
		$this->expectOutputRegex('/<h2>Search the Platform<\/h2>/');
		pmp_render_template('search.php', array(
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
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_verify_settings() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_on_post_status_transition() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}
}
