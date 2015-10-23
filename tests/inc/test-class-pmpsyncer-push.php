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

    // re-fetch the doc, to make sure indexing has caught up
    sleep(1);
    $syncer->doc->load();

    // the parent post
    $story = $syncer->doc;
    $this->assertEquals('my post title', $story->attributes->title);
    $this->assertEquals('my post excerpt', $story->attributes->teaser);
    $this->assertEquals('here it is with content and some more content', $story->attributes->description);
    $this->assertStringStartsWith('<p>here it is with content</p><a', $story->attributes->contentencoded);
    $this->assertStringEndsWith('</a><p>and some more content</p><p>&nbsp;</p>', $story->attributes->contentencoded);
    $this->assertContains('pmp-wordpress', $story->attributes->itags);
    $this->assertContains('pmp-wordpress-test-content', $story->attributes->itags);
    $this->assertContains("post-id-{$syncer->post->ID}", $story->attributes->itags);
    $this->assertEquals('1999-12-31T12:12:12+00:00', $story->attributes->published);
    $this->assertEquals('admin', $story->attributes->byline);
    $this->assertObjectHasAttribute('tags', $story->attributes);
    $this->assertContains('foo', $story->attributes->tags);
    $this->assertContains('bar', $story->attributes->tags);
    $this->assertContains('and another one', $story->attributes->tags);
    $this->assertCount(1, $story->links->profile);
    $this->assertRegexp('/profiles\/story$/', $story->links->profile[0]->href);
    $this->assertCount(1, $story->links->alternate);
    $this->assertRegexp("/^http.*\?p={$syncer->post->ID}$/", $story->links->alternate[0]->href);
    $this->assertObjectNotHasAttribute('collection', $story->links);

    // attachments
    $this->assertCount(3, $story->links->item);
    $this->assertContains('urn:collectiondoc:image', $story->links->item[0]->rels);
    $this->assertContains('urn:collectiondoc:image:featured', $story->links->item[0]->rels);
    $this->assertContains('urn:collectiondoc:image', $story->links->item[1]->rels);
    $this->assertContains('urn:collectiondoc:audio', $story->links->item[2]->rels);
    $this->assertCount(3, $story->items);
    $this->assertNotNull($story->items[0]);
    $this->assertNotNull($story->items[1]);
    $this->assertNotNull($story->items[2]);

    // check everything on the first image
    $image = $story->items[0];
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
    $image = $story->items[1];
    $this->assertEquals('imagetest', $image->attributes->title);
    $this->assertEquals('my-excerpt', $image->attributes->description);
    $this->assertEquals('my-byline', $image->attributes->byline);
    $this->assertRegexp('/profiles\/image$/', $image->links->profile[0]->href);

    // and how about that audio attachment?
    $audio = $story->items[2];
    $this->assertEquals('mpthreetest', $audio->attributes->title);
    $this->assertObjectNotHasAttribute('description', $audio->attributes);
    $this->assertObjectNotHasAttribute('byline', $audio->attributes);
    $this->assertRegexp('/profiles\/audio$/', $audio->links->profile[0]->href);
    $this->assertCount(1, $audio->links->alternate);
    $this->assertRegexp("/^http.*\?attachment_id={$syncer->attachment_syncers[2]->post->ID}$/", $audio->links->alternate[0]->href);
    $this->assertCount(1, $audio->links->enclosure);
    $this->assertRegexp('/mpthreetest\.mp3$/', $audio->links->enclosure[0]->href);
    $this->assertEquals('audio/mpeg', $audio->links->enclosure[0]->type);
    $this->assertEquals(12, $audio->links->enclosure[0]->meta->duration);

    // check that audio tag gets stripped from description/contentencoded
    $this->assertNotContains('audio', $story->attributes->contentencoded);
    $this->assertNotContains('mpthreetest', $story->attributes->contentencoded);
    $this->assertNotContains('audio', $story->attributes->description);
    $this->assertNotContains('mpthreetest', $story->attributes->description);

    // but the embedded images are still there (for now)
    $this->assertContains('<img', $story->attributes->contentencoded);
    $this->assertContains('imagetest', $story->attributes->contentencoded);
    $this->assertNotContains('imagetest', $story->attributes->description);
  }

}
