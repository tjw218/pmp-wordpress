<?php

/**
 * A class for syncing PMP-documents to WP-posts
 *
 * @since 0.4
 */
abstract class PmpSyncer {

  public $doc;
  public $post;
  public $post_meta;

  /**
   * Generic initializer for syncing PMP docs
   *
   * @param $pmp_doc - a CollectionDocJson instance (or null)
   * @param $wp_post - a WP_Post instance (or null)
   */
  public function __construct(\Pmp\Sdk\CollectionDocJson $pmp_doc = null, WP_Post $wp_post = null) {
    $this->doc = $pmp_doc;
    $this->post = $wp_post;
    if (!$pmp_doc && !$wp_post) {
      throw new RuntimeException('The PMP doc or WP post must exist first!');
    }
    $this->load_pmp_post_meta();
  }

  /**
   * Load PMP-related metadata from the wordpress database
   */
  protected function load_pmp_post_meta() {
    $this->post_meta = array();

    // look for any meta fields starting with "pmp_"
    if ($this->post) {
      $all_meta = get_post_meta($this->post->ID);
      foreach ($all_meta as $key => $value) {
        if (preg_match('/^pmp_/', $key)) {
          $this->post_meta[$key] = (is_array($value) && count($value) == 1) ? $value[0] : $value;
        }
      }
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
    return ($doc_modified != $post_modified);
  }

  /**
   * Get upstream changes to this doc
   *
   * @return boolean success
   */
  public function pull() {
    if (!$this->doc) {
      if ($this->delete_post()) {
        $this->post = null;
        $this->post_meta = array();
        return true;
      }
      else {
        return false;
      }
    }

    // create the post/attachment, if it doesn't exist yet
    if (!$this->post) {
      if (!$this->insert_post()) {
        return false; // failed to create
      }
    }

    // now sync pmp data (don't short-circuit, to make sure pmp_guid is set!)
    $data_success = $this->pull_post_data();
    $meta_success = $this->pull_post_metadata();
    return ($data_success && $meta_success);
  }

  /**
   * Handle inserting a new wp-post
   *
   * @return boolean success
   */
  protected function insert_post() {
    $data = array(
      'post_title'   => "draft pmp-pulled content: {$this->doc->attributes->guid}",
      'post_content' => "draft pmp-pulled content: {$this->doc->attributes->guid}",
      'post_author'  => get_current_user_id(), // TODO: often null
      'post_type'    => 'post',
      'post_status'  => 'auto-draft',
    );
    $id_or_error = wp_insert_post($data, true);
    if (is_wp_error($id_or_error)) {
      var_log("insert_post ERROR for [{$this->doc->attributes->guid}] - {$id_or_error->get_error_message()}");
      return false;
    }
    else {
      pmp_debug("  -- creating new post for doc[{$this->doc->attributes->guid}]");
      $this->post = get_post($id_or_error);
      return true;
    }
  }

  /**
   * Handle deleting a wp-post
   *
   * @return boolean success
   */
  protected function delete_post() {
    pmp_debug("  -- deleting stale post[{$this->post->ID}]");
    wp_delete_post($this->post->ID, true);
    return true;
  }

  /**
   * Generic logic for pulling PMP data into the post
   *
   * @return boolean success
   */
  protected function pull_post_data() {
    $data = array('ID' => $this->post->ID);
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

    // default to draft posts
    if ($this->post->post_status == 'auto-draft') {
      $data['post_status'] = 'draft';
    }

    // set published date, even for draft/pending posts
    $data['post_date']     = date('Y-m-d H:i:s', strtotime($this->doc->attributes->published));
    $data['post_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime($this->doc->attributes->published));
    if (in_array($this->post->post_status, array('pending', 'draft', 'auto-draft'))) {
      $data['edit_date'] = true;
    }

    // save changes
    $id_or_error = wp_update_post($data, true);
    if (is_wp_error($id_or_error)) {
      var_log("pull_post_data ERROR for [{$this->doc->attributes->guid}] - {$id_or_error->get_error_message()}");
      return false;
    }
    $this->post = get_post($id_or_error);
    return true;
  }

  /**
   * Generic logic for pulling PMP data into the post_meta
   *
   * @return boolean success
   */
  protected function pull_post_metadata() {
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
    return true;
  }

}
