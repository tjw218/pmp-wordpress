<?php
include_once __DIR__ . '/../PMPSyncerTestCase.php';

/**
 * Test syncing between Wordpress-Posts and PMP-documents
 *
 * todo: mock out some of these dependencies
 */
class TestPmpSyncer extends PMP_SyncerTestCase {

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
    $this->assertEquals($this->pmp_story->attributes->guid, $syncer->doc->attributes->guid);
    $this->assertNull($syncer->post);
    $this->assertArrayNotHasKey('pmp_guid', $syncer->post_meta);
    $this->assertCount(2, $syncer->attachment_syncers);

    // post only
    $syncer = new PmpSyncer(null, $this->wp_post);
    $this->assertNull($syncer->doc);
    $this->assertEquals($this->wp_post->ID, $syncer->post->ID);
    $this->assertArrayHasKey('pmp_guid', $syncer->post_meta);
    $this->assertEquals($this->pmp_story->attributes->guid, $syncer->post_meta['pmp_guid']);
    $this->assertCount(0, $syncer->attachment_syncers);

    // both items
    $syncer = new PmpSyncer($this->pmp_story, $this->wp_post);
    $this->assertEquals($this->pmp_story->attributes->guid, $syncer->doc->attributes->guid);
    $this->assertEquals($this->wp_post->ID, $syncer->post->ID);
    $this->assertArrayHasKey('pmp_guid', $syncer->post_meta);
    $this->assertEquals($this->pmp_story->attributes->guid, $syncer->post_meta['pmp_guid']);
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
    update_post_meta($this->wp_post->ID, 'pmp_guid', $this->pmp_story->attributes->guid);
    wp_update_post(array('ID' => $this->wp_post->ID, 'post_parent' => 9999));
    $syncer = PmpSyncer::fromDoc($this->pmp_story);
    $this->assertNull($syncer->post);
  }


  /**
   * lookup pmp document from a post
   */
  function test_from_post() {
    $syncer = PmpSyncer::fromPost($this->wp_post);
    $this->assertNotNull($syncer->doc);
    $this->assertNotNull($syncer->post);
    $this->assertEquals($this->pmp_story->attributes->guid, $syncer->doc->attributes->guid);

    // post isn't in the pmp yet
    update_post_meta($this->wp_post->ID, 'pmp_guid', null);
    $syncer = PmpSyncer::fromPost($this->wp_post);
    $this->assertNull($syncer->doc);

    // lost access (403 forbidden) to this pmp document
    update_post_meta($this->wp_post->ID, 'pmp_guid', 'some-guid-i-cannot-see');
    $syncer = PmpSyncer::fromPost($this->wp_post);
    $this->assertNull($syncer->doc);
  }


  /**
   * check for modifications (local or remote)
   */
  function test_is_modified() {
    $syncer = new PmpSyncer($this->pmp_story, $this->wp_post);
    $this->assertCount(2, $syncer->attachment_syncers);

    // initially, will look modified due to lack of local attachments
    $this->assertTrue($syncer->attachment_syncers[0]->is_modified());
    $this->assertTrue($syncer->attachment_syncers[1]->is_modified());
    $this->assertTrue($syncer->is_modified());

    // creating attachments fixes that
    $item1 = $this->pmp_story->items[0];
    $item2 = $this->pmp_story->items[1];
    $attach1 = $this->reset_post(null, $item1->attributes->guid, $item1->attributes->modified, $this->wp_post->ID);
    $attach2 = $this->reset_post(null, $item2->attributes->guid, $item2->attributes->modified, $this->wp_post->ID);

    // make sure attachments load as expected
    $syncer = new PmpSyncer($this->pmp_story, $this->wp_post);
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
