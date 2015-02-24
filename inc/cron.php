<?php

/**
 * Query for posts with `pmp_guid` -- an indication that the post was pulled from PMP
 *
 * @since 0.1
 */
function pmp_get_pmp_posts() {
	$args = array(
		'relation' => 'OR',
		'meta_query' => array(
			'key' => 'pmp_guid',
			'compare' => 'EXISTS'
		)
	);
	$meta_query = new WP_Meta_Query($args);

	$query = new WP_Query(array(
		'meta_query' => $meta_query,
		'posts_per_page' => -1,
		'post_status' => 'any'
	));

	return $query->posts;
}

/**
 * For each PMP post in the WP database, fetch the corresponding Doc from PMP and check if
 * the WP post differs from the PMP Doc. If it does differ, update the post in the WP database.
 *
 * @since 0.1
 */
function pmp_get_updates() {
	$posts = pmp_get_pmp_posts();

	$sdk = new SDKWrapper();

	foreach ($posts as $post) {
		$custom_fields = get_post_custom($post->ID);

		if (empty($custom_fields['pmp_subscribe_to_updates']))
			$subscribe_to_updates = 'on';
		else
			$custom_fields['pmp_subscribe_to_updates'][0];

		if ($subscribe_to_updates == 'on')
			$subscribed = true;
		else
			$subscribed = false;

		if ($subscribed) {
			$guid = $custom_fields['pmp_guid'][0];
			$doc = $sdk->fetchDoc($guid);
			if (!empty($doc) && pmp_needs_update($post, $doc))
				pmp_update_post($post, $doc);
		}
	}
}

/**
 * Compare the md5 hash of a WP post and PMP Doc to determine whether or not the WP post is different
 * from PMP and therefore needs updating.
 *
 * @since 0.1
 */
function pmp_needs_update($wp_post, $pmp_doc) {
	$post_md5 = get_post_meta($wp_post->ID, 'pmp_md5', true);
	if (pmp_document_md5($pmp_doc->attributes) !== $post_md5)
		return true;
	return false;
}

/**
 * Update an existing WP post which was originally pulled from PMP with the Doc data from PMP.
 *
 * @since 0.1
 */
function pmp_update_post($wp_post, $pmp_doc) {
	$data = (array) $pmp_doc->attributes;

	$post_data = array(
		'ID' => $wp_post->ID,
		'post_title' => $data['title'],
		'post_content' => $data['contentencoded']
	);

	$updated_post = wp_update_post($post_data);

	if (is_wp_error($updated_post))
		return $updated_post;

	$post_meta = array(
		'pmp_guid' => $data['guid'],
		'pmp_created' => $data['created'],
		'pmp_modified' => $data['modified'],
		'pmp_md5' => pmp_document_md5($data)
	);

	foreach ($post_meta as $key => $value)
		update_post_meta($updated_post, $key, $value);
}
