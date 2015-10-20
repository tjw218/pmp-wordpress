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
      $syncer = new PmpPost(null, null);
      $this->fail('Expected constructor to throw exception');
    } catch (Exception $e) {
      $this->assertRegExp('/must exist first/i', $e->getMessage());
    }

    // story only
    $syncer = new PmpPost($this->pmp_story, null);
    $this->assertEquals($this->pmp_story->attributes->guid, $syncer->doc->attributes->guid);
    $this->assertNull($syncer->post);
    $this->assertArrayNotHasKey('pmp_guid', $syncer->post_meta);
    $this->assertCount(2, $syncer->attachment_syncers);

    // post only
    $syncer = new PmpPost(null, $this->wp_post);
    $this->assertNull($syncer->doc);
    $this->assertEquals($this->wp_post->ID, $syncer->post->ID);
    $this->assertArrayHasKey('pmp_guid', $syncer->post_meta);
    $this->assertEquals($this->pmp_story->attributes->guid, $syncer->post_meta['pmp_guid']);
    $this->assertCount(0, $syncer->attachment_syncers);

    // both items
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
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
    $syncer = PmpPost::fromDoc($this->pmp_story);
    $this->assertNotNull($syncer->doc);
    $this->assertNotNull($syncer->post);
    $this->assertEquals($this->wp_post->ID, $syncer->post->ID);

    // post doesn't exist
    update_post_meta($this->wp_post->ID, 'pmp_guid', 'foobar');
    $syncer = PmpPost::fromDoc($this->pmp_story);
    $this->assertNull($syncer->post);

    // top-level vs attachments
    update_post_meta($this->wp_post->ID, 'pmp_guid', $this->pmp_story->attributes->guid);
    wp_update_post(array('ID' => $this->wp_post->ID, 'post_parent' => 9999));
    $syncer = PmpPost::fromDoc($this->pmp_story);
    $this->assertNull($syncer->post);
  }


  /**
   * lookup pmp document from a post
   */
  function test_from_post() {
    $syncer = PmpPost::fromPost($this->wp_post);
    $this->assertNotNull($syncer->doc);
    $this->assertNotNull($syncer->post);
    $this->assertEquals($this->pmp_story->attributes->guid, $syncer->doc->attributes->guid);

    // post isn't in the pmp yet
    update_post_meta($this->wp_post->ID, 'pmp_guid', null);
    $syncer = PmpPost::fromPost($this->wp_post);
    $this->assertNull($syncer->doc);

    // lost access (403 forbidden) to this pmp document
    update_post_meta($this->wp_post->ID, 'pmp_guid', 'some-guid-i-cannot-see');
    $syncer = PmpPost::fromPost($this->wp_post);
    $this->assertNull($syncer->doc);
  }


  /**
   * check for modifications (local or remote)
   */
  function test_is_modified() {
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
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
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertNotNull($syncer->attachment_syncers[0]->post);
    $this->assertNotNull($syncer->attachment_syncers[1]->post);
    $this->assertNotEmpty($syncer->attachment_syncers[0]->parent_syncer);
    $this->assertNotEmpty($syncer->attachment_syncers[1]->parent_syncer);

    // now things look sync'd
    $this->assertFalse($syncer->attachment_syncers[0]->is_modified());
    $this->assertFalse($syncer->attachment_syncers[1]->is_modified());
    $this->assertFalse($syncer->is_modified());
  }

  /**
   * did a doc originate from this wordpress site?
   */
  function test_is_local() {
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertFalse($syncer->is_local());

    // should unset pmp_owner
    update_post_meta($this->wp_post->ID, 'pmp_owner', 'somebody-else');
    $this->assertNotEmpty(get_post_meta($this->wp_post->ID, 'pmp_owner', true));
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertEmpty(get_post_meta($this->wp_post->ID, 'pmp_owner', true));
    $this->assertArrayNotHasKey('pmp_owner', $syncer->post_meta);
    $this->assertArrayNotHasKey('pmp_last_pushed', $syncer->post_meta);
    $this->assertFalse($syncer->is_local());

    // unset the pmp_owner, but set the pmp_last_pushed
    update_post_meta($this->wp_post->ID, 'pmp_owner', pmp_get_my_guid());
    $this->assertNotEmpty(get_post_meta($this->wp_post->ID, 'pmp_owner', true));
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertEmpty(get_post_meta($this->wp_post->ID, 'pmp_owner', true));
    $this->assertArrayNotHasKey('pmp_owner', $syncer->post_meta);
    $this->assertEquals($this->pmp_story->attributes->modified, $syncer->post_meta['pmp_last_pushed']);
    $this->assertTrue($syncer->is_local());

    // local-only docs
    $syncer = new PmpPost(null, $this->wp_post);
    $this->assertTrue($syncer->is_local());
  }

  /**
   * automatic updates to posts
   */
  function test_is_subscribed_to_updates() {
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertEmpty(get_post_meta($this->wp_post->ID, 'pmp_subscribe_to_updates', true));
    $this->assertArrayNotHasKey('pmp_subscribe_to_updates', $syncer->post_meta);
    $this->assertTrue($syncer->is_subscribed_to_updates());

    // explicitly set to "off"
    update_post_meta($this->wp_post->ID, 'pmp_subscribe_to_updates', 'off');
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertFalse($syncer->is_subscribed_to_updates());

    // anything but "off" means "on"
    update_post_meta($this->wp_post->ID, 'pmp_subscribe_to_updates', 'this-means-on');
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertTrue($syncer->is_subscribed_to_updates());
  }

  /**
   * check push-ability
   */
  function test_is_writeable() {
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertFalse($syncer->is_writeable());

    // i can always push new stories (even with a guid)
    $syncer = new PmpPost(null, $this->wp_post);
    $this->assertTrue($syncer->is_writeable());

    // or an actual writeable doc
    $this->pmp_story->scope = 'write';
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertTrue($syncer->is_writeable());
  }

}
