<?php
include_once __DIR__ . '/../PMPSyncerTestCase.php';

/**
 * Test pushing from Wordpress posts to PMP docs
 *
 * todo: mock out some of these dependencies
 */
class TestPmpSyncerPush extends PMP_SyncerTestCase {

  /**
   * push a post
   */
  function test_push_post() {
    $syncer = new PmpPost(null, $this->local_post);
    $this->assertTrue($syncer->push());

    // the parent post
    $this->assertEquals('my post title', $syncer->doc->attributes->title);
    $this->assertEquals('my post excerpt', $syncer->doc->attributes->teaser);
    $this->assertEquals('my html encoded post content', trim(preg_replace('/\s+/', ' ', $syncer->doc->attributes->description)));
    $this->assertEquals('my <p>html encoded</p> post content', $syncer->doc->attributes->contentencoded);
    $this->assertContains('pmp-wordpress', $syncer->doc->attributes->itags);
    $this->assertContains('pmp-wordpress-test-content', $syncer->doc->attributes->itags);
    $this->assertContains("post-id-{$syncer->post->ID}", $syncer->doc->attributes->itags);
    $this->assertEquals('1999-12-31T12:12:12+00:00', $syncer->doc->attributes->published);
    $this->assertEquals('admin', $syncer->doc->attributes->byline);
    $this->assertCount(1, $syncer->doc->links->profile);
    $this->assertRegexp('/profiles\/story$/', $syncer->doc->links->profile[0]->href);
    $this->assertCount(1, $syncer->doc->links->alternate);
    $this->assertRegexp("/^http.*\?p={$syncer->post->ID}$/", $syncer->doc->links->alternate[0]->href);
    $this->assertObjectNotHasAttribute('collection', $syncer->doc->links);

    // attached image(s)
    $this->assertCount(2, $syncer->doc->links->item);
    $this->assertCount(2, $syncer->doc->items);
    $this->assertContains('urn:collectiondoc:image', $syncer->doc->links->item[0]->rels);
    $this->assertContains('urn:collectiondoc:image:featured', $syncer->doc->links->item[0]->rels);
    $this->assertContains('urn:collectiondoc:image', $syncer->doc->links->item[1]->rels);

    // check everything on the first image
    $image = $syncer->doc->items[0];
    $this->assertEquals('real-alt-text', $image->attributes->title);
    $this->assertObjectNotHasAttribute('description', $image->attributes);
    $this->assertObjectNotHasAttribute('byline', $image->attributes);
    $this->assertRegexp('/profiles\/image$/', $image->links->profile[0]->href);
    $this->assertCount(1, $image->links->alternate);
    $this->assertRegexp("/^http.*\?attachment_id={$syncer->attachment_syncers[0]->post->ID}$/", $image->links->alternate[0]->href);
    $this->assertCount(3, $image->links->enclosure);
    foreach ($image->links->enclosure as $encl) {
      $this->assertEquals('image/jpeg', $encl->type);
      $this->assertInternalType('integer', $encl->meta->height);
      $this->assertInternalType('integer', $encl->meta->width);
      $this->assertTrue(in_array($encl->meta->crop, array('square', 'small', 'medium', 'large', 'primary')));
    }

    // second image should have different attributes
    $image = $syncer->doc->items[1];
    $this->assertRegexp('/profiles\/image$/', $image->links->profile[0]->href);
    $this->assertEquals('canola', $image->attributes->title);
    $this->assertEquals('my-excerpt', $image->attributes->description);
    $this->assertEquals('my-byline', $image->attributes->byline);
  }

}
