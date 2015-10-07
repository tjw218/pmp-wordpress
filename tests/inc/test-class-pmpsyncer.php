<?php
include_once __DIR__ . '/../../inc/class-pmpsyncer.php';

/**
 * Test syncing between Wordpress-Posts and PMP-documents
 *
 * todo: mock out some of these dependencies
 */
class TestPmpSyncer extends WP_UnitTestCase {

  const PMP_GUID = '753d0442-7133-4507-974e-65639ba6535c';
  private static $sdk_wrapper;
  private static $pmp_story;
  private static $wp_post;

  /**
   * init test data
   */
  static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    $settings = get_option('pmp_settings');
    if (empty($settings['pmp_api_url']) || empty($settings['pmp_client_id']) || empty($settings['pmp_client_secret'])) {
      self::$sdk_wrapper = false;
    }
    else {
      self::$sdk_wrapper = new SDKWrapper();
      self::$pmp_story = self::$sdk_wrapper->fetchDoc(self::PMP_GUID);
      self::$wp_post = get_post(wp_insert_post(array('post_title' => 'foo', 'post_content' => 'bar')));
    }
  }

  /**
   * return to known state before each test
   */
  function setUp() {
    parent::setUp();
    $this->setupWordpressPost(self::$wp_post->ID, self::PMP_GUID, self::$pmp_story->attributes->modified);
  }

  /**
   * helper to return a post to a known state
   */
  function setupWordpressPost($id, $guid, $modified, $parent_id = null) {
    wp_update_post(array(
      'ID' => $id,
      'post_parent' => $parent_id,
      'post_type' => $parent_id ? 'attachment' : 'post',
      'post_title' => 'foo',
      'post_content' => 'bar',
    ));
    update_post_meta($id, 'pmp_guid', $guid);
    update_post_meta($id, 'pmp_modified', $modified);
  }

  /**
   * basic constructor
   */
  function test_constructor() {
    try {
      $syncer = new PmpSyncer(null, null);
      $this->fail('Expected constructor to throw exception');
    } catch (Exception $e) {
      $this->assertRegExp('/must exist first/i', $e->getMessage());
    }

    // story only
    $syncer = new PmpSyncer(self::$pmp_story, null);
    $this->assertEquals(self::PMP_GUID, $syncer->doc->attributes->guid);
    $this->assertNull($syncer->post);
    $this->assertArrayHasKey('pmp_guid', $syncer->post_meta);
    $this->assertNull($syncer->post_meta['pmp_guid']);
    $this->assertCount(2, $syncer->attachment_syncers);

    // post only
    $syncer = new PmpSyncer(null, self::$wp_post);
    $this->assertNull($syncer->doc);
    $this->assertEquals(self::$wp_post->ID, $syncer->post->ID);
    $this->assertArrayHasKey('pmp_guid', $syncer->post_meta);
    $this->assertEquals(self::PMP_GUID, $syncer->post_meta['pmp_guid']);
    $this->assertCount(0, $syncer->attachment_syncers);

    // both items
    $syncer = new PmpSyncer(self::$pmp_story, self::$wp_post);
    $this->assertEquals(self::PMP_GUID, $syncer->doc->attributes->guid);
    $this->assertEquals(self::$wp_post->ID, $syncer->post->ID);
    $this->assertArrayHasKey('pmp_guid', $syncer->post_meta);
    $this->assertEquals(self::PMP_GUID, $syncer->post_meta['pmp_guid']);
    $this->assertCount(2, $syncer->attachment_syncers);
  }

  /**
   * lookup post from pmp document
   */
  function test_from_doc() {
    $syncer = PmpSyncer::fromDoc(self::$pmp_story);
    $this->assertNotNull($syncer->doc);
    $this->assertNotNull($syncer->post);
    $this->assertEquals(self::$wp_post->ID, $syncer->post->ID);

    // post doesn't exist
    update_post_meta(self::$wp_post->ID, 'pmp_guid', 'foobar');
    $syncer = PmpSyncer::fromDoc(self::$pmp_story);
    $this->assertNull($syncer->post);

    // top-level vs attachments
    update_post_meta(self::$wp_post->ID, 'pmp_guid', self::PMP_GUID);
    $syncer = PmpSyncer::fromDoc(self::$pmp_story, false);
    $this->assertNull($syncer->post);
    wp_update_post(array('ID' => self::$wp_post->ID, 'post_parent' => 9999));
    $syncer = PmpSyncer::fromDoc(self::$pmp_story, false);
    $this->assertNotNull($syncer->post);
  }

  /**
   * lookup pmp document from a post
   */
  function test_from_post() {
    $syncer = PmpSyncer::fromPost(self::$wp_post);
    $this->assertNotNull($syncer->doc);
    $this->assertNotNull($syncer->post);
    $this->assertEquals(self::PMP_GUID, $syncer->doc->attributes->guid);

    // post isn't in the pmp yet
    update_post_meta(self::$wp_post->ID, 'pmp_guid', null);
    $syncer = PmpSyncer::fromPost(self::$wp_post);
    $this->assertNull($syncer->doc);

    // lost access (403 forbidden) to this pmp document
    update_post_meta(self::$wp_post->ID, 'pmp_guid', 'some-guid-i-cannot-see');
    $syncer = PmpSyncer::fromPost(self::$wp_post);
    $this->assertNull($syncer->doc);
  }

  /**
   * check for modifications (local or remote)
   */
  function test_is_modified() {
    $syncer = new PmpSyncer(self::$pmp_story, self::$wp_post);
    $this->assertCount(2, $syncer->attachment_syncers);

    // initially, will look modified due to lack of local attachments
    $this->assertTrue($syncer->attachment_syncers[0]->is_modified());
    $this->assertTrue($syncer->attachment_syncers[1]->is_modified());
    $this->assertTrue($syncer->is_modified());

    // creating attachments fixes that
    $item1 = self::$pmp_story->items[0];
    $item2 = self::$pmp_story->items[1];
    $attach1 = wp_insert_post(array('post_title' => 'foo', 'post_content' => 'bar'));
    $attach2 = wp_insert_post(array('post_title' => 'foo', 'post_content' => 'bar'));
    $this->setupWordpressPost($attach1, $item1->attributes->guid, $item1->attributes->modified, self::$wp_post->ID);
    $this->setupWordpressPost($attach2, $item2->attributes->guid, $item2->attributes->modified, self::$wp_post->ID);

    // make sure attachments load as expected
    $syncer = new PmpSyncer(self::$pmp_story, self::$wp_post);
    $this->assertNotNull($syncer->attachment_syncers[0]->post);
    $this->assertNotNull($syncer->attachment_syncers[1]->post);
    $this->assertEmpty($syncer->attachment_syncers[0]->attachment_syncers);
    $this->assertEmpty($syncer->attachment_syncers[1]->attachment_syncers);

    // now things look sync'd
    $this->assertFalse($syncer->attachment_syncers[0]->is_modified());
    $this->assertFalse($syncer->attachment_syncers[1]->is_modified());
    $this->assertFalse($syncer->is_modified());
  }

}
