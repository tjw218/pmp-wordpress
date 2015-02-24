<?php

class TestAssets extends WP_UnitTestCase {
	function test_pmp_enqueue_assets() {
		// The page variable must be set for `pmp_enqueue_assets` to work
		$_GET['page'] = 'pmp-search';

		pmp_enqueue_assets();

		global $wp_styles, $wp_scripts;
		$this->assertTrue(!empty($wp_styles->registered['pmp-search']));
		$this->assertTrue(!empty($wp_scripts->registered['pmp-search']));
	}
}
