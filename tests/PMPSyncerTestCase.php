<?php
require_once __DIR__ . '/../inc/class-pmpsyncer.php';

/**
 * Abstract class for PMP Syncer utility testing
 *
 * todo: mock out some of these dependencies
 */
class PMP_SyncerTestCase extends WP_UnitTestCase {

  private static $_sdk_wrapper;
  private static $_pmp_story_guid = '753d0442-7133-4507-974e-65639ba6535c';
  private static $_pmp_story;

  /**
   * Setup test prerequisites once
   */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();

    // must have pmp settings defined
    $settings = get_option('pmp_settings');
    if (empty($settings['pmp_api_url']) || empty($settings['pmp_client_id']) || empty($settings['pmp_client_secret'])) {
      self::$_sdk_wrapper = false;
    }
    else {
      self::$_sdk_wrapper = new SDKWrapper();
      self::$_pmp_story = self::$_sdk_wrapper->fetchDoc(self::$_pmp_story_guid);
    }
  }

  /**
   * Assign instance variables for fixtures
   */
  public function setUp() {
    parent::setUp();
    if (self::$_sdk_wrapper) {
      $this->pmp_story = clone self::$_pmp_story;
      $this->wp_post = $this->reset_post(null, $this->pmp_story->attributes->guid, $this->pmp_story->attributes->modified);
    }
    else {
      $this->markTestSkipped('This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
    }
  }

  /**
   * Reset a wordpress post back to a "known" state
   */
  public function reset_post($id, $guid, $modified, $parent_id = null) {
    if (is_null($id)) {
      $id = wp_insert_post(array('post_title' => 'foo', 'post_content' => 'bar'));
    }
    $id = is_integer($id) ? $id : $id->ID;

    // update to initial state
    wp_update_post(array(
      'ID' => $id,
      'post_parent' => $parent_id,
      'post_status' => $parent_id ? 'inherit' : 'draft',
      'post_type' => $parent_id ? 'attachment' : 'post',
      'post_title' => 'foo',
      'post_content' => 'bar',
    ));
    update_post_meta($id, 'pmp_guid', $guid);
    update_post_meta($id, 'pmp_modified', $modified);
    return get_post($id);
  }

}
