<?php

class TestPages extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();

		$this->admin = $this->factory->user->create();
		$user = get_user_by('id', $this->admin);
		$user->set_role('administrator');
		wp_set_current_user($user->ID);
	}

	function test_pmp_options_page() {
		// TODO: This test could be better if it checked for the presence of the settings form
		// element and verified the inputs that should be there, are.
		$expect = '/<h2>PMP Settings<\/h2>/';
		$this->expectOutputRegex($expect);
		pmp_options_page();
	}

	function test_pmp_search_page() {
		// TODO: This could stand to verify that more of the necessary bits are
		// printed to the page. For example, the search form, the javascript templates,
		// the necessary javascript globals, etc.
		$expect = '/<h2>Search the Platform<\/h2>/';
		$this->expectOutputRegex($expect);
		pmp_search_page();
	}
}
