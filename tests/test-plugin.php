<?php

class TestPluginCore extends WP_UnitTestCase {
	function test_pmp_init() {
		// Make sure our plugin init has run by checking that constants are defined
		$constants = array('PMP_PLUGIN_DIR', 'PMP_PLUGIN_DIR_URI', 'PMP_TEMPLATE_DIR', 'PMP_VERSION');

		foreach ($constants as $constant)
			$this->assertTrue(defined($constant));
	}

	function test_pmp_plugin_menu() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_add_meta_boxes() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_setup_cron_on_activation() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_pmp_hourly_cron() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}
}
