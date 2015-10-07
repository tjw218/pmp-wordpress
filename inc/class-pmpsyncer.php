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
  public $attachment_syncers;

  /**
   * Initialize the sync process for a document-to-post
   */
  public function __construct($pmp_doc, $wp_post, $top_level = true) {
    $this->doc = $pmp_doc;
    $this->post = $wp_post;
    if (!$pmp_doc && !$wp_post) {
      throw new RuntimeException('The PMP doc or WP post must exist first!');
    }
    $this->post_meta = $this->load_pmp_post_meta();

    // only top level posts get child attachments
    if ($top_level) {
      $this->attachment_syncers = $this->load_attachment_syncers();
    }
    else {
      $this->attachment_syncers = false;
    }
  }

  /**
   * Init when you only know the PMP doc
   */
  public static function fromDoc($pmp_doc, $top_level = true) {
    $args = array(
      'posts_per_page' => 1,
      'post_type'      => 'any',
      'post_status'    => 'any',
      'meta_key'       => 'pmp_guid',
      'meta_value'     => $pmp_doc->attributes->guid,
    );

    // search exclusively either parent or child posts
    if ($top_level) {
      $args['post_parent'] = 0;
    }
    else {
      $args['post_parent__not_in'] = array(0);
    }

    // run search, and return new syncer
    $query = new WP_Query($args);
    $posts = $query->posts;
    return new self($pmp_doc, empty($posts) ? null : $posts[0]);
  }

  /**
   * Init when you only know the WP post
   */
  public static function fromPost($wp_post) {
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
   */
  public function is_modified() {
    $doc_modified = $this->doc ? $this->doc->attributes->modified : null;
    $post_modified = $this->post_meta['pmp_modified'];

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
   * Push local changes to the PMP
   */
  public function push() {
    if (!$this->post) {
      throw new RuntimeException('No WP post specified!');
    }

  }

  /**
   * Get upstream changes to this doc
   */
  public function pull() {
    if (!$this->doc) {
      throw new RuntimeException('No PMP doc specified!');
    }
    $guid = $this->doc->attributes->guid;

    // create the post, if it doesn't exist yet
    if (!$this->post) {
      $id_or_error = wp_insert_post(array(
        'post_title'   => "draft pmp-pulled content: $guid",
        'post_content' => "draft pmp-pulled content: $guid",
        'post_status'  => $this->attachment_syncers ? 'draft' : 'inherit',
        'post_type'    => $this->attachment_syncers ? 'post'  : 'attachment',
      ), true);
      if (is_wp_error($id_or_error)) {
        var_log("wp_insert_post ERROR for [$guid] - {$id_or_error->get_error_message()}");
        return false;
      }
      $this->post = get_post($id_or_error);
    }

    // sync post metadata
    $this->post_meta['pmp_guid']      = $this->doc->attributes->guid;
    $this->post_meta['pmp_created']   = $this->doc->attributes->created;
    $this->post_meta['pmp_modified']  = $this->doc->attributes->modified;
    $this->post_meta['pmp_published'] = $this->doc->attributes->published;
    $this->post_meta['pmp_writeable'] = ($this->doc->scope == 'write');
    $this->post_meta['pmp_byline']    = $this->doc->attributes->byline;
    foreach ($this->post_meta as $key => $val) {
      update_post_meta($this->post->ID, $key, $val);
    }

    // sync post attachments
    if ($this->attachment_syncers) {
      foreach ($this->attachment_syncers as $syncer) {
        $syncer->pull();
      }
    }

    // sync primary post data
    $data = array('ID' => $this->post->ID);
    $data['post_title'] = $this->doc->attributes->title;
    $data['post_date'] = date('Y-m-d H:i:s', strtotime($this->doc->attributes->published));
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
    // TODO: audio shortcodes in the post_content
    $id_or_error = wp_update_post($data, true);
    if (is_wp_error($id_or_error)) {
      var_log("wp_update_post ERROR for [$guid] - {$id_or_error->get_error_message()}");
      return false;
    }

    // it worked!
    return true;
  }

  /**
   * Load PMP-related metadata from the wordpress database
   */
  protected function load_pmp_post_meta() {
    $meta = array(
      'pmp_guid'                 => null,
      'pmp_created'              => null,
      'pmp_modified'             => null,
      'pmp_published'            => null,
      'pmp_writeable'            => null,
      'pmp_byline'               => null,
      'pmp_subscribe_to_updates' => null,
    );

    // load metadata, and flatten arrays
    if ($this->post) {
      $all_meta = get_post_meta($this->post->ID);
      foreach ($meta as $field => $val) {
        if (empty($all_meta[$field])) {
          $meta[$field] = null;
        }
        else {
          if (is_array($all_meta[$field]) && count($all_meta[$field]) === 1) {
            $meta[$field] = $all_meta[$field][0];
          }
          else {
            $meta[$field] = $all_meta[$field];
          }
        }
      }
    }

    // subscribe_to_updates actually defaults to "on"
    if (empty($meta['pmp_subscribe_to_updates']) || $meta['pmp_subscribe_to_updates'] !== 'off') {
      $meta['pmp_subscribe_to_updates'] = 'on';
    }
    return $meta;
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
        'post_type'      => 'attachment',
        'post_status'    => 'any',
        'post_parent'    => $this->post->ID,
      ));
      foreach ($query->posts as $child_post) {
        $guid = get_post_meta($child_post->ID, 'pmp_guid', true);
        if ($guid && isset($pmp_guids_to_docs[$guid])) {
          $syncers[] = new self($pmp_guids_to_docs[$guid], $child_post, false);
          unset($pmp_guids_to_docs[$guid]);
        }
        else {
          $syncers[] = new self(null, $child_post, false); // not in the pmp
        }
      }
    }

    // finally, pmp documents that aren't local yet
    foreach ($pmp_guids_to_docs as $guid => $item) {
      $syncers[] = new self($item, null, false);
    }
    return $syncers;
  }

}
