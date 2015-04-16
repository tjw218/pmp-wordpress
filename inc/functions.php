<?php

/**
 * Render a template by specifying a filename and context.
 *
 * @param (string) $template -- the filename of the template to render.
 * @param (array) $context -- associative array of values used within the template.
 *
 * @since 0.1
 */
function pmp_render_template($template, $context=false) {
	if (!empty($context))
		extract($context);

	include PMP_TEMPLATE_DIR . '/' . $template;
}

/**
 * Return a hash where keys are creator names and values are their respective GUIDs.
 *
 * @since 0.1
 */
function pmp_get_creators() {
	return array(
		'APM' => '98bf597a-2a6f-446c-9b7e-d8ae60122f0d',
		'NPR' => '6140faf0-fb45-4a95-859a-070037fafa01',
		'PBS' => 'fc53c568-e939-4d9c-86ea-c2a2c70f1a99',
		'PRI' => '7a865268-c9de-4b27-a3c1-983adad90921',
		'PRX' => '609a539c-9177-4aa7-acde-c10b77a6a525'
	);
}

/**
 * Return a has where keys are content type names and values are respective profile aliases.
 *
 * @since 0.1
 */
function pmp_get_profiles() {
	return array(
		'Story' => 'story',
		'Audio' => 'audio',
		'Video' => 'video',
		'Image' => 'image',
		'Series' => 'series',
		'Episode' => 'episode'
	);
}

/**
 * Similar to `media_sideload_image` except that it simply returns the attachment's ID on success
 *
 * @param (string) $file the url of the image to download and attach to the post
 * @param (integer) $post_id the post ID to attach the image to
 * @param (string) $desc an optional description for the image
 *
 * @since 0.1
 */
function pmp_media_sideload_image($file, $post_id, $desc=null) {
	if (!empty($file)) {
		// Set variables for storage, fix file filename for query strings.
		preg_match('/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches);
		$file_array = array();
		$file_array['name'] = basename($matches[0]);

		// Download file to temp location.
		$file_array['tmp_name'] = download_url($file);

		// If error storing temporarily, return the error.
		if (is_wp_error($file_array['tmp_name'])) {
			return $file_array['tmp_name'];
		}

		// Do the validation and storage stuff.
		$id = media_handle_sideload($file_array, $post_id, $desc);

		// If error storing permanently, unlink.
		if (is_wp_error($id)) {
			@unlink($file_array['tmp_name']);
		}

		return $id;
	}
}

/**
 * Verify that we have all settings required to successfully query the PMP API.
 *
 * @since 0.1
 */
function pmp_verify_settings() {
	$options = get_option('pmp_settings');
	return (
		!empty($options['pmp_api_url']) &&
		!empty($options['pmp_client_id']) &&
		!empty($options['pmp_client_secret'])
	);
}

/**
 * Verify that a post's publish date is set according to data retrieved from the PMP API
 * when a draft post transitions to published post.
 *
 * @since 0.2
 */
function pmp_on_post_status_transition($new_status, $old_status, $post) {
	if ($old_status == 'draft' && $new_status == 'publish') {
		$custom_fields = get_post_custom($post->ID);

		if (!empty($custom_fields['pmp_guid'][0]) && !empty($custom_fields['pmp_published'][0])) {
			$post_data = array(
				'ID' => $post->ID,
				'post_date' => date('Y-m-d H:i:s', strtotime($custom_fields['pmp_published'][0]))
			);

			$updated_post = wp_update_post($post_data);
		}
	}
}
add_action('transition_post_status',  'pmp_on_post_status_transition', 10, 3 );

/**
 * Add a "Publish and push to PMP" button the post publish actions meta box.
 *
 * @since 0.2
 */
function pmp_publish_and_push_to_pmp_button() {
	global $post;

	if (!pmp_post_is_mine($post->ID))
		return;

	$message = ($post->post_status == 'publish')? 'Update' : 'Publish';
?>
	<div id="pmp-publish-actions">
		<input type="submit"
			name="pmp_<?php echo strtolower($message); ?>_push"
			id="pmp-<?php echo strtolower($message); ?>-push"
			class="button button-primary button-large" value="<?php echo $message; ?> and push to PMP">
	</div>
<?php
}
add_action('post_submitbox_start', 'pmp_publish_and_push_to_pmp_button');

/**
 * Push content to PMP when user clicks "Publish and push to PMP" or "Update and push to PMP"
 *
 * @since 0.2
 */
function pmp_push_to_pmp($post_id) {
	if (isset($_POST['pmp_publish_push']))
		$action = 'publish';
	else if (isset($_POST['pmp_update_push']))
		$action = 'update';

	if (!empty($action)) {
		if (wp_is_post_revision($post_id))
			return;

		$post = get_post($post_id);
		$author = get_user_by('id', $post->post_author);

		if ($action == 'publish' && $post->post_status != 'publish') {
			wp_publish_post($post_id);
			return;
		}

		if ($action == 'update')
			$pmp_guid = get_post_meta($post_id, 'pmp_guid', true);

		do_action('pmp_before_push', $post_id);

		$sdk = new SDKWrapper();

		$obj = new \StdClass();
		$obj->attributes = (object) array(
			'title' => $post->post_title,
			'contentencoded' => $post->post_content,
			'description' => strip_tags($post->post_content),
			'teaser' => $post->post_excerpt,
			'byline' => $author->display_name,
		);

		$obj->links = new \StdClass();

		// Build out the collection array
		$obj->links->collection = array();

		$default_series = get_option('pmp_default_series', false);
		if (!empty($default_series))
			$obj->links->collection[] = (object) array('href' => $sdk->href4guid($default_series));

		$default_property = get_option('pmp_default_property', false);
		if (!empty($default_property))
			$obj->links->collection[] = (object) array('href' => $sdk->href4guid($default_property));

		// Build out the permissions group profile array
		$default_group = get_option('pmp_default_group', false);
		if (!empty($default_group))
			$obj->links->permission[] = (object) array('href' => $sdk->href4guid($default_group));

		if (!empty($pmp_guid)) {
			$doc = $sdk->fetchDoc($pmp_guid);
			$doc->attributes = (object) array_merge((array) $doc->attributes, (array) $obj->attributes);
		} else
			$doc = $sdk->newDoc('story', $obj);

		$doc->attributes->itags = array_merge((array) $doc->attributes->itags, array('wp_pmp_push'));

		$doc->save();

		$post_meta = pmp_get_post_meta_from_pmp_doc($doc);
		foreach ($post_meta as $key => $value)
			update_post_meta($post_id, $key, $value);

		do_action('pmp_after_push', $post_id);
	}
}
add_action('save_post', 'pmp_push_to_pmp');

/**
 * Find out if your PMP API user is the owner of a given post/PMP Doc
 *
 * @since 0.2
 */
function pmp_post_is_mine($post_id) {
	$pmp_guid = get_post_meta($post_id, 'pmp_guid', true);

	if (!empty($pmp_guid)) {
		$pmp_owner = get_post_meta($post_id, 'pmp_owner', true);
		if (!empty($pmp_owner)) {
			$sdk = new SDKWrapper();
			$me = $sdk->fetchUser('me');
			return ($pmp_owner == $me->attributes->guid);
		}
	}

	return true;
}

/**
 * Build an associatvie array of post data from a PMP Doc suitable for use with wp_insert_post or
 * wp_update_post.
 *
 * @since 0.2
 */
function pmp_get_post_data_from_pmp_doc($pmp_doc) {
	$data = json_decode(json_encode($pmp_doc), true);

	$post_data = array(
		'post_title' => $data['attributes']['title'],
		'post_content' => $data['attributes']['contentencoded'],
		'post_excerpt' => $data['attributes']['teaser'],
		'post_date' => date('Y-m-d H:i:s', strtotime($data['attributes']['published']))
	);

	return $post_data;
}

/**
 * Build an associative array of post meta based on a PMP Doc suitable for use in saving post meta.
 *
 * @since 0.2
 */
function pmp_get_post_meta_from_pmp_doc($pmp_doc) {
	$data = json_decode(json_encode($pmp_doc), true);

	$post_meta = array(
		'pmp_guid' => $data['attributes']['guid'],
		'pmp_created' => $data['attributes']['created'],
		'pmp_modified' => $data['attributes']['modified'],
		'pmp_byline' => $data['attributes']['byline'],
		'pmp_published' => $data['attributes']['published'],
		'pmp_owner' => SDKWrapper::guid4href($data['links']['owner'][0]['href'])
	);

	return $post_meta;
}

if (!function_exists('var_log')) {
	/**
	 * Log anything in a human-friendly format.
	 *
	 * @param mixed $stuff the data structure to send to the error log.
	 * @since 0.2
	 */
	function var_log($stuff) { error_log(var_export($stuff, true)); }
}
