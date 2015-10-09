<?php

/**
 * A class for syncing PMP-documents to WP-posts
 *
 * @since 0.4
 */
class PmpSyncer {

  public $doc;
  public $post;
  public $post_meta;

  // child attachments (PMP items)
  public $attachment_syncers;

  // parent post (PMP collection/parent/something)
  public $parent_syncer;

  /**
   * Initialize the sync process for a document-to-post
   *
   * @param $pmp_doc - a CollectionDocJson instance (or null)
   * @param $wp_post - a WP_Post instance (or null)
   * @param $parent_syncer - optional parent of this post (for attachments)
   */
  public function __construct($pmp_doc, $wp_post, $parent_syncer = null) {
    $this->doc = $pmp_doc;
    $this->post = $wp_post;
    if (!$pmp_doc && !$wp_post) {
      throw new RuntimeException('The PMP doc or WP post must exist first!');
    }
    $this->post_meta = $this->load_pmp_post_meta();
    $this->parent_syncer = $parent_syncer;

    // only top level posts get child attachments (only nest 1-level deep)
    if (empty($this->parent_syncer)) {
      $this->attachment_syncers = $this->load_attachment_syncers();
    }
  }

  /**
   * Init when you only know the PMP doc
   *
   * @param $pmp_doc - a CollectionDocJson instance
   * @return a new PmpSyncer
   */
  public static function fromDoc($pmp_doc) {
    $args = array(
      'posts_per_page' => 1,
      'post_parent'    => 0, // only top-level (no attachments)
      'post_type'      => 'any',
      'post_status'    => 'any',
      'meta_key'       => 'pmp_guid',
      'meta_value'     => $pmp_doc->attributes->guid,
    );

    // run search, and return new syncer
    $query = new WP_Query($args);
    $posts = $query->posts;
    return new self($pmp_doc, empty($posts) ? null : $posts[0]);
  }

  /**
   * Init when you only know the WP post
   *
   * @param $wp_post - a WP_Post instance
   * @return a new PmpSyncer
   */
  public static function fromPost($wp_post) {
    if ($wp_post->post_parent > 0) {
      throw new RuntimeException('fromPost really only works for top-level posts');
    }
    $sdk = new SDKWrapper();
    $guid = get_post_meta($wp_post->ID, 'pmp_guid', true);
    if ($guid) {
      $doc = $sdk->fetchDoc($guid);
      return new self($doc, $wp_post); // doc might be null
    }
    else {
      return new self(null, $wp_post);
    }
  }

  /**
   * Does this Post look like the upstream Doc?
   *
   * @return boolean post or doc is modified
   */
  public function is_modified() {
    $doc_modified = $this->doc ? $this->doc->attributes->modified : null;
    $post_modified = isset($this->post_meta['pmp_modified']) ? $this->post_meta['pmp_modified'] : null;

    // check this doc, then child items/attachments
    if ($doc_modified != $post_modified) {
      return true;
    }
    if ($this->attachment_syncers) {
      foreach ($this->attachment_syncers as $syncer) {
        if ($syncer->is_modified()) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Get upstream changes to this doc
   *
   * @param $post_status the status you'd like to set for the post
   * @return boolean success
   */
  public function pull($post_status = 'draft') {
    if (!$this->doc) {
      if ($this->post->post_type == 'attachment') {
        pmp_debug("  -- deleting stale attachment[{$this->post->ID}]");
        wp_delete_attachment($this->post->ID, true);
      }
      else {
        pmp_debug("  -- deleting stale post[{$this->post->ID}]");
        wp_delete_post($this->post->ID, true);
      }
      $this->post = null;
      $this->post_meta = array();
      return true;
    }

    // SPECIAL CASE: if the image-url changes, nuke the attachment
    if ($this->doc->getProfileAlias() == 'image' && $this->post) {
      $enclosure = SdkWrapper::getImageEnclosure($this->doc);
      if (!isset($this->post_meta['pmp_image_url']) || $this->post_meta['pmp_image_url'] != $enclosure->href) {
        pmp_debug("  -- refreshing attachment[{$this->post->ID}] guid[{$this->doc->attributes->guid}]");
        wp_delete_attachment($this->post->ID, true);
        $this->post = null;
        $this->post_meta = array();
      }
    }

    // create the post/attachment, if it doesn't exist yet
    if (!$this->post) {
      $id_or_error = $this->insert_post();
      if (is_wp_error($id_or_error)) {
        var_log("insert_post ERROR for [{$this->doc->attributes->guid}] - {$id_or_error->get_error_message()}");
        return false;
      }
      $this->post = get_post($id_or_error);
    }

    // now sync pmp metadata
    $this->post_meta['pmp_guid']      = $this->doc->attributes->guid;
    $this->post_meta['pmp_created']   = $this->doc->attributes->created;
    $this->post_meta['pmp_modified']  = $this->doc->attributes->modified;
    $this->post_meta['pmp_published'] = $this->doc->attributes->published;
    $this->post_meta['pmp_writeable'] = ($this->doc->scope == 'write') ? 'yes' : 'no';
    $this->post_meta['pmp_byline']    = isset($this->doc->attributes->byline) ? $this->doc->attributes->byline : null;
    $this->post_meta['pmp_subscribe_to_updates'] = 'on'; // default
    foreach ($this->post_meta as $key => $val) {
      update_post_meta($this->post->ID, $key, $val);
    }

    // handle primary data separately, for attachments-vs-posts
    if ($this->parent_syncer) {
      return $this->pull_attachment();
    }
    else {
      return $this->pull_top_level_post($post_status);
    }
  }

  /**
   * Handle inserting a new wp-post
   *
   * @return a post id or WP error object
   */
  protected function insert_post() {
    $data = array(
      'post_title'   => "draft pmp-pulled content: {$this->doc->attributes->guid}",
      'post_content' => "draft pmp-pulled content: {$this->doc->attributes->guid}",
      'post_author'  => get_current_user_id(), // TODO: often null
    );

    // images/attachments are different than top-level posts
    if ($this->parent_syncer) {
      if ($this->doc->getProfileAlias() == 'image') {
        $enclosure = SdkWrapper::getImageEnclosure($this->doc);
        return pmp_media_sideload_image($enclosure->href, $this->parent_syncer->post->ID);
      }
      else {
        $data['post_type']   = 'pmp_attachment';
        $data['post_status'] = 'inherit';
        $data['post_parent'] = $this->parent_syncer->post->ID;
        return wp_insert_post($data, true);
      }
    }
    else {
      $data['post_type']   = 'post'; // top-level!
      $data['post_status'] = 'auto-draft';
      return wp_insert_post($data, true);
    }
  }

  /**
   * Pull changes for a top-level post (usually a profile=story doc)
   *
   * @param $post_status the status you'd like to set on the post
   * @return boolean success
   */
  protected function pull_top_level_post($post_status) {
    $data = array('ID' => $this->post->ID);
    $data['post_status'] = $post_status;
    $data['post_title'] = $this->doc->attributes->title;
    if (isset($this->doc->attributes->teaser)) {
      $data['post_excerpt'] = $this->doc->attributes->teaser;
    }
    if (isset($this->doc->attributes->contentencoded)) {
      $data['post_content'] = $this->doc->attributes->contentencoded;
    }
    else if (isset($this->doc->attributes->description)) {
      $data['post_content'] = $this->doc->attributes->description;
    }
    else {
      $data['post_content'] = '';
    }

    // set published date, even for draft/pending posts
    $data['post_date']     = date('Y-m-d H:i:s', strtotime($this->doc->attributes->published));
    $data['post_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime($this->doc->attributes->published));
    if (in_array($this->post->post_status, array('pending', 'draft', 'auto-draft'))) {
      $data['edit_date'] = true;
    }

    // sync children NOW, so we can embed attachments
    foreach ($this->attachment_syncers as $syncer) {
      $success = $syncer->pull();
      if ($success) {
        if (isset($syncer->post_meta['pmp_audio_shortcode'])) {
          $data['post_content'] = $syncer->post_meta['pmp_audio_shortcode'] . "\n" . $data['post_content'];
        }
        elseif (isset($syncer->post_meta['pmp_image_url']) && !has_post_thumbnail($this->post->ID)) {
          update_post_meta($this->post->ID, '_thumbnail_id', $syncer->post->ID);
        }
      }
    }

    // save changes
    $id_or_error = wp_update_post($data, true);
    if (is_wp_error($id_or_error)) {
      var_log("pull_top_level_post ERROR for [{$this->doc->attributes->guid}] - {$id_or_error->get_error_message()}");
      return false;
    }
    $this->post = get_post($id_or_error);
    return true;
  }

  /**
   * Pull changes for an attachment
   */
  protected function pull_attachment() {
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
    $id_or_error = wp_update_post($data, true);
    if (is_wp_error($id_or_error)) {
      var_log("pull_attachment ERROR for [{$this->doc->attributes->guid}] - {$id_or_error->get_error_message()}");
      return false;
    }
    $this->post = get_post($id_or_error);

    // process additional metadata
    if ($this->doc->getProfileAlias() == 'image') {
      return $this->pull_image_metadata();
    }
    else if ($this->doc->getProfileAlias() == 'audio') {
      return $this->pull_audio_metadata();
    }
    else {
      return true; // TODO: video
    }
  }

  /**
   * Pull changes for an image attachment
   *
   * @return boolean success
   */
  protected function pull_image_metadata() {
    $enclosure = SdkWrapper::getImageEnclosure($this->doc);
    $this->post_meta['pmp_image_url'] = $enclosure->href;
    $this->post_meta['_wp_attachment_image_alt'] = $this->doc->attributes->title;
    update_post_meta($this->post->ID, 'pmp_image_url', $enclosure->href);
    update_post_meta($this->post->ID, '_wp_attachment_image_alt', $this->doc->attributes->title);
    return true;
  }

  /**
   * Pull changes for an audio attachment
   *
   * @return boolean success
   */
  protected function pull_audio_metadata() {
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

  /**
   * Load PMP-related metadata from the wordpress database
   */
  protected function load_pmp_post_meta() {
    $pmp_meta = array();

    // look for any meta fields starting with "pmp_"
    if ($this->post) {
      $all_meta = get_post_meta($this->post->ID);
      foreach ($all_meta as $key => $value) {
        if (preg_match('/^pmp_/', $key)) {
          $pmp_meta[$key] = (is_array($value) && count($value) == 1) ? $value[0] : $value;
        }
      }
    }
    return $pmp_meta;
  }

  /**
   * Get child items/attachments of this post
   */
  protected function load_attachment_syncers() {
    $syncers = array();

    // first, the pmp items
    $pmp_guids_to_docs = array();
    if ($this->doc) {
      foreach ($this->doc->items() as $item) {
        if (in_array($item->getProfileAlias(), array('audio', 'image', 'video'))) {
          $pmp_guids_to_docs[$item->attributes->guid] = $item;
        }
      }
    }

    // now, query for post attachments
    if ($this->post) {
      $query = new WP_Query(array(
        'posts_per_page' => -1,
        'post_type'      => array('attachment', 'pmp_attachment'),
        'post_status'    => 'any',
        'post_parent'    => $this->post->ID,
      ));
      foreach ($query->posts as $child_post) {
        $guid = get_post_meta($child_post->ID, 'pmp_guid', true);
        if ($guid && isset($pmp_guids_to_docs[$guid])) {
          $syncers[] = new self($pmp_guids_to_docs[$guid], $child_post, $this);
          unset($pmp_guids_to_docs[$guid]);
        }
        else {
          $syncers[] = new self(null, $child_post, $this); // not in the pmp
        }
      }
    }

    // finally, pmp documents that aren't local yet
    foreach ($pmp_guids_to_docs as $guid => $item) {
      $syncers[] = new self($item, null, $this);
    }
    return $syncers;
  }

}
