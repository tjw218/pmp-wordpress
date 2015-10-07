<?php
include_once __DIR__ . '/../../inc/class-pmpsyncer.php';

/**
 * Test syncing between Wordpress-Posts and PMP-documents
 *
 * todo: mock out some of these dependencies
 */
class TestPmpSyncer extends WP_UnitTestCase {

  /**
   * init test data
   */
  function setUp() {
    parent::setUp();
    $settings = get_option('pmp_settings');
    if (empty($settings['pmp_api_url']) || empty($settings['pmp_client_id']) || empty($settings['pmp_client_secret'])) {
      $this->skip = true;
    }
    else {
      $this->skip = false;
      $this->pmp_guid = '753d0442-7133-4507-974e-65639ba6535c';
      $this->sdk_wrapper = new SDKWrapper();
      $this->pmp_story = $this->sdk_wrapper->fetchDoc($this->pmp_guid);
      $this->wp_post = get_post(wp_insert_post(array('post_title' => 'foo', 'post_content' => 'bar')));
      update_post_meta($this->wp_post->ID, 'pmp_guid', $this->pmp_guid);
    }
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
    $syncer = new PmpSyncer($this->pmp_story, null);
    $this->assertEquals($this->pmp_guid, $syncer->doc->attributes->guid);
    $this->assertNull($syncer->post);
    $this->assertArrayHasKey('pmp_guid', $syncer->post_meta);
    $this->assertNull($syncer->post_meta['pmp_guid']);
    $this->assertCount(2, $syncer->attachment_syncers);

    // post only
    $syncer = new PmpSyncer(null, $this->wp_post);
    $this->assertNull($syncer->doc);
    $this->assertEquals($this->wp_post->ID, $syncer->post->ID);
    $this->assertArrayHasKey('pmp_guid', $syncer->post_meta);
    $this->assertEquals($this->pmp_guid, $syncer->post_meta['pmp_guid']);
    $this->assertCount(0, $syncer->attachment_syncers);

    // both items
    $syncer = new PmpSyncer($this->pmp_story, $this->wp_post);
    $this->assertEquals($this->pmp_guid, $syncer->doc->attributes->guid);
    $this->assertEquals($this->wp_post->ID, $syncer->post->ID);
    $this->assertArrayHasKey('pmp_guid', $syncer->post_meta);
    $this->assertEquals($this->pmp_guid, $syncer->post_meta['pmp_guid']);
    $this->assertCount(2, $syncer->attachment_syncers);
  }

  /**
   * lookup post from pmp document
   */
  function test_from_doc() {
    $syncer = PmpSyncer::fromDoc($this->pmp_story);
    $this->assertNotNull($syncer->doc);
    $this->assertNotNull($syncer->post);
    $this->assertEquals($this->wp_post->ID, $syncer->post->ID);

    // post doesn't exist
    update_post_meta($this->wp_post->ID, 'pmp_guid', 'foobar');
    $syncer = PmpSyncer::fromDoc($this->pmp_story);
    $this->assertNull($syncer->post);

    // top-level vs attachments
    update_post_meta($this->wp_post->ID, 'pmp_guid', $this->pmp_guid);
    $syncer = PmpSyncer::fromDoc($this->pmp_story, false);
    $this->assertNull($syncer->post);
    wp_update_post(array('ID' => $this->wp_post->ID, 'post_parent' => 9999));
    $syncer = PmpSyncer::fromDoc($this->pmp_story, false);
    $this->assertNotNull($syncer->post);
  }

  /**
   * lookup pmp document from a post
   */
  function test_from_post() {
    $syncer = PmpSyncer::fromPost($this->wp_post);
    $this->assertNotNull($syncer->doc);
    $this->assertNotNull($syncer->post);
    $this->assertEquals($this->pmp_guid, $syncer->doc->attributes->guid);

    // post isn't in the pmp yet
    update_post_meta($this->wp_post->ID, 'pmp_guid', null);
    $syncer = PmpSyncer::fromPost($this->wp_post);
    $this->assertNull($syncer->doc);

    // lost access (403 forbidden) to this pmp document
    update_post_meta($this->wp_post->ID, 'pmp_guid', 'some-guid-i-cannot-see');
    $syncer = PmpSyncer::fromPost($this->wp_post);
    $this->assertNull($syncer->doc);
  }

}
