<?php
include_once __DIR__ . '/../PMPSyncerTestCase.php';

/**
 * Test pulling from PMP docs to Wordpress posts
 *
 * todo: mock out some of these dependencies
 */
class TestPmpSyncerPull extends PMP_SyncerTestCase {

  /**
   * pull a story
   */
  function test_pull_story() {
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertTrue($syncer->pull());

    $id = $this->wp_post->ID;
    $post = get_post($id);
    $attrs = $this->pmp_story->attributes;
    $links = $this->pmp_story->links;
    $this->assertEquals('draft', $post->post_status);
    $this->assertEquals('post', $post->post_type);
    $this->assertEquals(0, $post->post_parent);
    $this->assertEquals($attrs->title, $post->post_title);
    $this->assertEquals(strtotime($attrs->published), strtotime($post->post_date));
    $this->assertEquals(strtotime($attrs->published), strtotime($post->post_date_gmt));
    $this->assertContains($attrs->contentencoded, $post->post_content);
    $this->assertEquals($attrs->teaser, $post->post_excerpt);
    $this->assertEquals($attrs->guid,      get_post_meta($id, 'pmp_guid',      true));
    $this->assertEquals($attrs->created,   get_post_meta($id, 'pmp_created',   true));
    $this->assertEquals($attrs->modified,  get_post_meta($id, 'pmp_modified',  true));
    $this->assertEquals($attrs->published, get_post_meta($id, 'pmp_published', true));
    $this->assertEquals($attrs->byline,    get_post_meta($id, 'pmp_byline',    true));
    $this->assertEquals('no',              get_post_meta($id, 'pmp_writeable', true));
    $this->assertEquals('on',              get_post_meta($id, 'pmp_subscribe_to_updates', true));

    // count attachments (images and audio)
    $images = new WP_Query(array('post_parent' => $id, 'post_status' => 'any', 'post_type' => 'attachment'));
    $this->assertCount(1, $images->posts);
    $audios = new WP_Query(array('post_parent' => $id, 'post_status' => 'any', 'post_type' => 'pmp_attachment'));
    $this->assertCount(1, $audios->posts);
  }

  /**
   * create a new post, when one doesn't exist
   */
  function test_pull_create() {
    update_post_meta($this->wp_post->ID, 'pmp_guid', 'foobar');
    $syncer = new PmpPost($this->pmp_story, null);
    $this->assertTrue($syncer->pull());

    // should have created a draft post with attachments
    $stories = new WP_Query(array('post_status' => 'draft', 'meta_key' => 'pmp_guid', 'meta_value' => $this->pmp_story->attributes->guid));
    $this->assertCount(1, $stories->posts);
    $post = $stories->posts[0];
    $images = new WP_Query(array('post_parent' => $post->ID, 'post_status' => 'any', 'post_type' => 'attachment'));
    $this->assertCount(1, $images->posts);
    $audios = new WP_Query(array('post_parent' => $post->ID, 'post_status' => 'any', 'post_type' => 'pmp_attachment'));
    $this->assertCount(1, $audios->posts);

    // pull as published
    $this->assertTrue($syncer->pull('publish'));
    $stories = new WP_Query(array('post_status' => 'publish', 'meta_key' => 'pmp_guid', 'meta_value' => $this->pmp_story->attributes->guid));
    $this->assertCount(1, $stories->posts);
  }

  /**
   * delete a post, when you lose access upstream
   */
  function test_pull_delete() {
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertTrue($syncer->pull());
    $this->assertCount(2, $syncer->attachment_syncers);

    // make sure these posts all get cleaned up
    $id = $this->wp_post->ID;
    $attach_id1 = $syncer->attachment_syncers[0]->post->ID;
    $attach_id2 = $syncer->attachment_syncers[1]->post->ID;

    // make it look like the top-level doc disappeared
    update_post_meta($id, 'pmp_guid', 'foobar');
    $syncer = PmpPost::fromPost($this->wp_post);
    $this->assertTrue($syncer->pull());
    $this->assertNull($syncer->post);
    $this->assertCount(2, $syncer->attachment_syncers);
    $this->assertNull(get_post($id));
    $this->assertNull(get_post($attach_id1));
    $this->assertNull(get_post($attach_id2));
  }

  /**
   * pull attachments
   */
  function test_pull_creating_attachments() {
    // first doctor-up the story a bit
    $this->pmp_story->items[0]->attributes->description = 'fake description';
    $this->pmp_story->items[0]->attributes->byline      = 'fake byline';
    unset($this->pmp_story->items[1]->attributes->description);
    unset($this->pmp_story->items[1]->attributes->byline);

    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertTrue($syncer->pull());

    $id1 = $syncer->attachment_syncers[0]->post->ID;
    $id2 = $syncer->attachment_syncers[1]->post->ID;
    $attach1 = get_post($id1);
    $attach2 = get_post($id2);
    $attrs1 = $syncer->attachment_syncers[0]->doc->attributes;
    $attrs2 = $syncer->attachment_syncers[1]->doc->attributes;
    $links1 = $syncer->attachment_syncers[0]->doc->links;
    $links2 = $syncer->attachment_syncers[1]->doc->links;

    // 1st attachment (image)
    $this->assertEquals('inherit', $attach1->post_status);
    $this->assertEquals('attachment', $attach1->post_type);
    $this->assertEquals($this->wp_post->ID, $attach1->post_parent);
    $this->assertEquals($attrs1->title, $attach1->post_title);
    $this->assertEquals(strtotime($attrs1->published), strtotime($attach1->post_date));
    $this->assertEquals(strtotime($attrs1->published), strtotime($attach1->post_date_gmt));
    $this->assertEquals($attrs1->description, $attach1->post_excerpt);
    $this->assertEquals($attrs1->guid,      get_post_meta($id1, 'pmp_guid',      true));
    $this->assertEquals($attrs1->created,   get_post_meta($id1, 'pmp_created',   true));
    $this->assertEquals($attrs1->modified,  get_post_meta($id1, 'pmp_modified',  true));
    $this->assertEquals($attrs1->published, get_post_meta($id1, 'pmp_published', true));
    $this->assertEquals($attrs1->byline,    get_post_meta($id1, 'pmp_byline',    true));
    $this->assertEquals('no',               get_post_meta($id1, 'pmp_writeable', true));
    $this->assertEquals('on',               get_post_meta($id1, 'pmp_subscribe_to_updates', true));

    // image enclosure
    $image_url = get_post_meta($id1, 'pmp_image_url', true);
    $image_alt = get_post_meta($id1, '_wp_attachment_image_alt', true);
    $this->assertRegExp('/^http(s):\/\//', $image_url);
    $this->assertRegExp('/(\.jpg$)/',      $image_url);
    $this->assertEquals($attrs1->title,    $image_alt);

    // 2nd attachment (audio)
    $this->assertEquals('inherit', $attach2->post_status);
    $this->assertEquals('pmp_attachment', $attach2->post_type);
    $this->assertEquals($this->wp_post->ID, $attach2->post_parent);
    $this->assertEquals($attrs2->title, $attach2->post_title);
    $this->assertEquals(strtotime($attrs2->published), strtotime($attach2->post_date));
    $this->assertEquals(strtotime($attrs2->published), strtotime($attach2->post_date_gmt));
    $this->assertEquals('', $attach2->post_excerpt);
    $this->assertEquals($attrs2->guid,      get_post_meta($id2, 'pmp_guid',      true));
    $this->assertEquals($attrs2->created,   get_post_meta($id2, 'pmp_created',   true));
    $this->assertEquals($attrs2->modified,  get_post_meta($id2, 'pmp_modified',  true));
    $this->assertEquals($attrs2->published, get_post_meta($id2, 'pmp_published', true));
    $this->assertEquals('',                 get_post_meta($id2, 'pmp_byline',    true));
    $this->assertEquals('no',               get_post_meta($id2, 'pmp_writeable', true));
    $this->assertEquals('on',               get_post_meta($id2, 'pmp_subscribe_to_updates', true));

    // audio enclosure
    $audio_url       = get_post_meta($id2, 'pmp_audio_url', true);
    $audio_shortcode = get_post_meta($id2, 'pmp_audio_shortcode', true);
    $this->assertRegExp('/^http(s):\/\//', $audio_url);
    $this->assertRegExp('/(\.mp3$)/',      $audio_url);
    $this->assertRegExp('/^\[audio src=/', $audio_shortcode);

    // attachments embedded in the top-level post
    $post = get_post($this->wp_post->ID);
    $this->assertEquals($id1, get_post_meta($post->ID, '_thumbnail_id', true));
    $this->assertContains($audio_shortcode, $post->post_content);
  }

  /**
   * image attachment changes
   */
  function test_pull_images() {
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertTrue($syncer->pull());
    $attach_id = $syncer->attachment_syncers[0]->post->ID;

    // changing doc attributes updates attachment
    $syncer->attachment_syncers[0]->doc->attributes->title = 'foobar';
    $this->assertTrue($syncer->pull());
    $this->assertEquals($attach_id, $syncer->attachment_syncers[0]->post->ID);
    $this->assertEquals('foobar', $syncer->attachment_syncers[0]->post->post_title);
    $this->assertEquals($attach_id, get_post_meta($syncer->post->ID, '_thumbnail_id', true));

    // changing the enclosure href ends up nuking/recreating the attachment
    $syncer->attachment_syncers[0]->doc->links->enclosure[0]->href = 'http://placehold.it/350x150.jpg';
    $this->assertTrue($syncer->pull());
    $this->assertNotEquals($attach_id, $syncer->attachment_syncers[0]->post->ID);
    $this->assertEquals('foobar', $syncer->attachment_syncers[0]->post->post_title);
    $this->assertEquals($syncer->attachment_syncers[0]->post->ID, get_post_meta($syncer->post->ID, '_thumbnail_id', true));

    // invalid enclosures hrefs just end up deleting the image altogether
    $syncer->attachment_syncers[0]->doc->links->enclosure[0]->href = 'http://foo.bar';
    $this->assertTrue($syncer->pull());
    $this->assertFalse($syncer->attachment_syncers[0]->pull());
    $this->assertNull($syncer->attachment_syncers[0]->post);
    $images = new WP_Query(array('post_parent' => $syncer->post->ID, 'post_status' => 'any', 'post_type' => 'attachment'));
    $this->assertCount(0, $images->posts);
    $this->assertEquals('', get_post_meta($syncer->post->ID, '_thumbnail_id', true));

    // but we can always put it back
    $syncer->attachment_syncers[0]->doc->links->enclosure[0]->href = 'http://placehold.it/350x150.jpg';
    $this->assertTrue($syncer->pull());
    $this->assertEquals('foobar', $syncer->attachment_syncers[0]->post->post_title);
    $this->assertEquals($syncer->attachment_syncers[0]->post->ID, get_post_meta($syncer->post->ID, '_thumbnail_id', true));
    $last_image_id = $syncer->attachment_syncers[0]->post->ID;

    // or maybe the parent-story removed the image
    $this->pmp_story->links->item = array();
    $this->pmp_story->items = array();
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertCount(2, $syncer->attachment_syncers);
    $this->assertNull($syncer->attachment_syncers[0]->doc);
    $this->assertNotNull($syncer->attachment_syncers[0]->post);
    $this->assertTrue($syncer->pull());
    $this->assertNull($syncer->attachment_syncers[0]->post);
    $images = new WP_Query(array('post_parent' => $syncer->post->ID, 'post_status' => 'any', 'post_type' => 'attachment'));
    $this->assertCount(0, $images->posts);
    $this->assertEquals('', get_post_meta($syncer->post->ID, '_thumbnail_id', true));
  }

  /**
   * audio (post_type=pmp_attachment) changes
   */
  function test_pull_audio() {
    $syncer = new PmpPost($this->pmp_story, $this->wp_post);
    $this->assertTrue($syncer->pull());
    $attach_id = $syncer->attachment_syncers[1]->post->ID;
    $audio_url = $syncer->attachment_syncers[1]->post_meta['pmp_audio_url'];
    $shortcode = $syncer->attachment_syncers[1]->post_meta['pmp_audio_shortcode'];

    // changing doc attributes updates pmpattachment
    $syncer->attachment_syncers[1]->doc->attributes->title = 'foobar';
    $this->assertTrue($syncer->pull());
    $this->assertEquals($attach_id, $syncer->attachment_syncers[1]->post->ID);
    $this->assertEquals('foobar', $syncer->attachment_syncers[1]->post->post_title);
    $this->assertContains($shortcode, $syncer->post->post_content);

    // changing the enclosure href updates the shortcode
    $syncer->attachment_syncers[1]->doc->links->enclosure[0]->href = 'http://foobar.org/test.mp3';
    $this->assertTrue($syncer->pull());
    $this->assertEquals($attach_id, $syncer->attachment_syncers[1]->post->ID);
    $this->assertEquals('foobar', $syncer->attachment_syncers[1]->post->post_title);
    $this->assertNotEquals($audio_url, $syncer->attachment_syncers[1]->post_meta['pmp_audio_url']);
    $this->assertNotEquals($shortcode, $syncer->attachment_syncers[1]->post_meta['pmp_audio_shortcode']);
    $this->assertNotContains($shortcode, $syncer->post->post_content);
    $this->assertContains($syncer->attachment_syncers[1]->post_meta['pmp_audio_shortcode'], $syncer->post->post_content);

    // non-playable enclosures
    $syncer->attachment_syncers[1]->doc->links->enclosure[0]->href = 'http://foobar.org/test.foobar';
    $syncer->attachment_syncers[1]->doc->links->enclosure[0]->type = 'foobar';
    $this->assertTrue($syncer->pull());
    $this->assertEquals($attach_id, $syncer->attachment_syncers[1]->post->ID);
    $this->assertEquals('foobar', $syncer->attachment_syncers[1]->post->post_title);
    $this->assertEquals('', get_post_meta($attach_id, 'pmp_audio_url', true));
    $this->assertEquals('', get_post_meta($attach_id, 'pmp_audio_shortcode', true));
    $this->assertNotContains('[audio', $syncer->post->post_content);

    // delete stale pmp_attachments
    $syncer->attachment_syncers[1]->doc = null;
    $this->assertTrue($syncer->pull());
    $this->assertNull($syncer->attachment_syncers[1]->post);
    $this->assertNull(get_post($attach_id));
    $audios = new WP_Query(array('post_parent' => $syncer->post->ID, 'post_status' => 'any', 'post_type' => 'pmp_attachment'));
    $this->assertCount(0, $audios->posts);
    $this->assertNotContains('[audio', $syncer->post->post_content);
  }

}
