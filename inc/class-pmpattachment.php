<?php

include_once __DIR__ . '/class-pmpsyncer.php';

/**
 * A class for syncing PMP-documents to WP-posts
 *
 * @since 0.4
 */
class PmpAttachment extends PmpSyncer {

  // parent post (PMP collection/parent/something)
  public $parent_syncer;

  /**
   * Initialize the sync process for a document-to-post
   *
   * @param $pmp_doc - a CollectionDocJson instance (or null)
   * @param $wp_post - a WP_Post instance (or null)
   * @param $parent_syncer - parent of this post/doc
   */
  public function __construct(\Pmp\Sdk\CollectionDocJson $pmp_doc = null, WP_Post $wp_post = null, PmpSyncer $parent_syncer) {
    parent::__construct($pmp_doc, $wp_post);
    if ($wp_post && $wp_post->post_parent < 1) {
      throw new RuntimeException('PmpPost is only for child attachment WP_Posts');
    }
    $this->parent_syncer = $parent_syncer;
  }

  /**
   * SPECIAL CASE: if the image-url changes, nuke the attachment
   *
   * @return boolean success
   */
  public function pull() {
    if ($this->doc && $this->post && $this->doc->getProfileAlias() == 'image') {
      $enclosure = SdkWrapper::getImageEnclosure($this->doc);
      if (!isset($this->post_meta['pmp_image_url']) || $this->post_meta['pmp_image_url'] != $enclosure->href) {
        pmp_debug("  -- refreshing attachment[{$this->post->ID}] guid[{$this->doc->attributes->guid}]");
        wp_delete_attachment($this->post->ID, true);
        $this->post = null;
        $this->post_meta = array();
      }
    }
    return parent::pull();
  }

  /**
   * Attachments are created slightly differently (especially for images)
   *
   * @return boolean success
   */
  protected function insert_post() {
    if ($this->doc->getProfileAlias() == 'image') {
      $enclosure = SdkWrapper::getImageEnclosure($this->doc);
      $id_or_error = pmp_media_sideload_image($enclosure->href, $this->parent_syncer->post->ID);
    }
    else {
      $data = array(
        'post_title'   => "draft pmp-pulled content: {$this->doc->attributes->guid}",
        'post_content' => "draft pmp-pulled content: {$this->doc->attributes->guid}",
        'post_author'  => get_current_user_id(), // TODO: often null
        'post_type'    => 'pmp_attachment',
        'post_status'  => 'inherit',
        'post_parent'  => $this->parent_syncer->post->ID,
      );
      $id_or_error = wp_insert_post($data, true);
    }

    // handle errors
    if (is_wp_error($id_or_error)) {
      var_log("insert_post ERROR for attachment [{$this->doc->attributes->guid}] - {$id_or_error->get_error_message()}");
      return false;
    }
    else {
      pmp_debug("  -- creating new attachment for doc[{$this->doc->attributes->guid}]");
      $this->post = get_post($id_or_error);
      return true;
    }
  }

  /**
   * Delete attachment (and associated files)
   *
   * @return boolean success
   */
  protected function delete_post() {
    if ($this->post->post_type == 'attachment') {
      pmp_debug("  -- deleting stale attachment[{$this->post->ID}]");
      wp_delete_attachment($this->post->ID, true);
    }
    else {
      pmp_debug("  -- deleting stale {$this->post->post_type}[{$this->post->ID}]");
      wp_delete_post($this->post->ID, true);
    }
    return true;
  }

  /**
   * Pull changes for this attachment
   *
   * @return boolean success
   */
  protected function pull_post_data() {
    $data = array('ID' => $this->post->ID);
    $data['post_title'] = $this->doc->attributes->title;
    $data['post_date'] = date('Y-m-d H:i:s', strtotime($this->doc->attributes->published));
    $data['post_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime($this->doc->attributes->published));
    if (isset($this->doc->attributes->description)) {
      $data['post_excerpt'] = $this->doc->attributes->description;
    }
    else {
      $data['post_excerpt'] = '';
    }

    // handle errors
    $id_or_error = wp_update_post($data, true);
    if (is_wp_error($id_or_error)) {
      var_log("pull_post_data ERROR for {$this->post->post_type}[{$this->doc->attributes->guid}] - {$id_or_error->get_error_message()}");
      return false;
    }
    $this->post = get_post($id_or_error);
    return true;
  }

  /**
   * Additional image/audio/video (attachment) metadata
   *
   * @return boolean success
   */
  protected function pull_post_metadata() {
    if (!parent::pull_post_metadata()) {
      return false;
    }

    // special metadatas
    if ($this->doc->getProfileAlias() == 'image') {
      $enclosure = SdkWrapper::getImageEnclosure($this->doc);
      $this->post_meta['pmp_image_url'] = $enclosure->href;
      $this->post_meta['_wp_attachment_image_alt'] = $this->doc->attributes->title;
      update_post_meta($this->post->ID, 'pmp_image_url', $enclosure->href);
      update_post_meta($this->post->ID, '_wp_attachment_image_alt', $this->doc->attributes->title);
    }
    else if ($this->doc->getProfileAlias() == 'audio') {
      $url = SdkWrapper::getPlayableUrl($this->doc);
      if ($url) {
        $shortcode = '[audio src="' . $url . '"]';
        $this->post_meta['pmp_audio_url'] = $url;
        $this->post_meta['pmp_audio_shortcode'] = $shortcode;
        update_post_meta($this->post->ID, 'pmp_audio_url', $url);
        update_post_meta($this->post->ID, 'pmp_audio_shortcode', $shortcode);
        return true;
      }
      else {
        unset($this->post_meta['pmp_audio_url']);
        unset($this->post_meta['pmp_audio_shortcode']);
        update_post_meta($this->post->ID, 'pmp_audio_url', null);
        update_post_meta($this->post->ID, 'pmp_audio_shortcode', null);
        return false;
      }
    }
    return true;
  }

}
