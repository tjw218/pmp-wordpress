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
    $syncer = new PmpSyncer($this->pmp_story, $this->wp_post);
    $success = $syncer->pull();
    $this->assertTrue($success);

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
   * pull attachments
   */
  function test_pull_creating_attachments() {
    // first doctor-up the story a bit
    $this->pmp_story->items[0]->attributes->description = 'fake description';
    $this->pmp_story->items[0]->attributes->byline      = 'fake byline';
    unset($this->pmp_story->items[1]->attributes->description);
    unset($this->pmp_story->items[1]->attributes->byline);

    $syncer = new PmpSyncer($this->pmp_story, $this->wp_post);
    $success = $syncer->pull();
    $this->assertTrue($success);

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

}
