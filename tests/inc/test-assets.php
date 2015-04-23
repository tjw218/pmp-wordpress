<?php

class TestAssets extends WP_UnitTestCase {
	function test_pmp_enqueue_assets_pmp_search() {
		// The page variable must be set for `pmp_enqueue_assets` to work
		$_GET['page'] = 'pmp-search';

		pmp_enqueue_assets();

		global $wp_styles, $wp_scripts;
		$this->assertTrue(!empty($wp_styles->registered['pmp-common']));
		$this->assertTrue(!empty($wp_scripts->registered['pmp-search']));
	}

	function test_pmp_enqueue_assets_pmp_groups_menu() {
		// The page variable must be set for `pmp_enqueue_assets` to work
		$_GET['page'] = 'pmp-groups-menu';

		pmp_enqueue_assets();

		global $wp_styles, $wp_scripts;
		$this->assertTrue(!empty($wp_styles->registered['pmp-common']));
		$this->assertTrue(!empty($wp_scripts->registered['pmp-groups-menu']));
	}

	function test_pmp_enqueue_assets_pmp_series_properties_menu() {
		// The page variable must be set for `pmp_enqueue_assets` to work
		$_GET['page'] = 'pmp-series-menu';

		pmp_enqueue_assets();

		global $wp_styles, $wp_scripts;
		$this->assertTrue(!empty($wp_styles->registered['pmp-common']));
		$this->assertTrue(!empty($wp_scripts->registered['pmp-collections-menu']));
	}

	function test_pmp_enqueue_assets_pmp_options_menu() {
		// The page variable must be set for `pmp_enqueue_assets` to work
		$_GET['page'] = 'pmp-options-menu';

		pmp_enqueue_assets();

		global $wp_styles, $wp_scripts;
		$this->assertTrue(!empty($wp_scripts->registered['pmp-options-menu']));
	}

	function test_pmp_modal_underscore_template() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_json_obj() {
		$pmp_obj = pmp_json_obj(array('test_key' => 'test value'));

		$this->assertEquals($pmp_obj['test_key'], 'test value');
		$this->assertTrue(!empty($pmp_obj['creators']));
		$this->assertTrue(!empty($pmp_obj['ajax_nonce']));
	}

}
