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

		do_action('pmp_before_push', $post_id);

		if ($action == 'publish') {
			wp_publish_post($post_id);
			remove_action('save_post', 'pmp_push_to_pmp');
			remove_action('edit_post', 'pmp_push_to_pmp');
		}

		// TODO: if the action is update, get the store guid and look up the
		// doc in the API to update.

		$post = get_post($post_id);
		$author = get_user_by('id', $post->post_author);
		$sdk = new SDKWrapper();

		$obj = new \StdClass();
		$obj->attributes = (object) array(
			'title' => $post->post_title,
			'contentencoded' => $post->post_content,
			'description' => strip_tags($post->post_content),
			'teaser' => $post->post_excerpt,
			'byline' => $author->display_name,
		);

		$default_series = get_option('pmp_default_series', false);
		if (!empty($default_series)) {
			// TODO: set the default series when pushing to PMP
		}

		$default_property = get_option('pmp_default_property', false);
		if (!empty($default_property)) {
			// TODO: set the default property when pushing to PMP
		}

		$default_group = get_option('pmp_default_group', false);
		if (!empty($default_group)) {
			// TODO: set the default group when pushing to PMP
		}

		$doc = $sdk->newDoc('story', $obj);

		// TODO: save the doc and store the guid + other meta as we do
		// when creating a draft or publish post from the search page
	}

	do_action('pmp_after_push', $post_id);
}
add_action('save_post', 'pmp_push_to_pmp');
add_action('edit_post', 'pmp_push_to_pmp');

if (!function_exists('var_log')) {
	/**
	 * Log anything in a human-friendly format.
	 *
	 * @param mixed $stuff the data structure to send to the error log.
	 * @since 0.2
	 */
	function var_log($stuff) { error_log(var_export($stuff, true)); }
}
