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

	include_once PMP_TEMPLATE_DIR . '/' . $template;
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
