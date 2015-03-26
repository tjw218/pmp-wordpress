<?php

class TestMetaBoxes extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();

		$this->editor = $this->factory->user->create();
		$user = get_user_by('id', $this->editor);
		$user->set_role('editor');
		wp_set_current_user($user->ID);

		$this->subscribed = $this->factory->post->create();
		update_post_meta($this->subscribed, 'pmp_subscribe_to_updates', 'on');

		$this->not_subscribed = $this->factory->post->create();
		update_post_meta($this->not_subscribed, 'pmp_subscribe_to_updates', 'off');
	}

	function test_pmp_subscribe_to_updates_meta_box_subscribed() {
		$post = get_post($this->subscribed);
		$expect_checked = '/<input\s*checked=\'checked\'\s*type="checkbox"\s*name="pmp_subscribe_to_updates"\s*\/>/';
		$this->expectOutputRegex($expect_checked);
		pmp_subscribe_to_updates_meta_box($post);
	}

	function test_pmp_subscribe_to_updates_meta_box_not_subscribed() {
		$post = get_post($this->not_subscribed);
		$expect_unchecked = '/<input\s*type="checkbox"\s*name="pmp_subscribe_to_updates"\s*\/>/';
		$this->expectOutputRegex($expect_unchecked);
		pmp_subscribe_to_updates_meta_box($post);
	}

	function test_pmp_subscribe_to_update_meta_box_save() {
		$post = get_post($this->not_subscribed);

		$_POST['pmp_subscribe_to_updates_meta_box_nonce'] = wp_create_nonce('pmp_subscribe_to_updates_meta_box');
		$_POST['pmp_subscribe_to_updates'] = 'on';

		pmp_subscribe_to_update_meta_box_save($post->ID);

		$meta = get_post_meta($post->ID, 'pmp_subscribe_to_updates', true);
		$this->assertEquals('on', $meta);
	}
}
