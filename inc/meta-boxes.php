<?php

/**
 * The PMP meta box to end all PMP meta boxes.
 *
 * @since 0.3
 */
function pmp_mega_meta_box($post) {
	wp_nonce_field('pmp_mega_meta_box', 'pmp_mega_meta_box_nonce');

	$pmp_guid = get_post_meta($post->ID, 'pmp_guid', true);

	if (!empty($pmp_guid) && !pmp_post_is_mine($post->ID)) {
		pmp_subscribe_to_updates_markup($post);
	} else {
		pmp_last_modified_meta();
		pmp_publish_and_push_to_pmp_button();
	}
}

/**
 * Prints markup for the "Keep this post in sync with PMP" functionality
 *
 * @since 0.3
 */
function pmp_subscribe_to_updates_markup($post) {
	$checked = get_post_meta($post->ID, 'pmp_subscribe_to_updates', true);
?>
	<div id="pmp-subscribe-to-updates">
		<p>Keep this post in sync with the original from PMP.</p>
		<p>Note: updates will overwrite any changes made to this post.</p>
		<label for="pmp_subscribe_to_updates">
			<input <?php checked(in_array($checked, array('on', '')), true); ?> type="checkbox" name="pmp_subscribe_to_updates" /> Subscribe to updates for this post.
		</label>
	</div>
<?php
}

/**
 * Save the value of `pmp_subscribe_to_updates` post meta.
 *
 * @since 0.1
 */
function pmp_subscribe_to_update_save($post_id) {
	$pmp_guid = get_post_meta($post_id, 'pmp_guid', true);

	if (!empty($pmp_guid) && !pmp_post_is_mine($post_id)) {
		if (!isset($_POST['pmp_mega_meta_box_nonce']))
			return;

		if (!wp_verify_nonce($_POST['pmp_mega_meta_box_nonce'], 'pmp_mega_meta_box_nonce'))
			return;

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		if (!current_user_can('edit_post', $post_id))
			return;

		if (!isset($_POST['pmp_subscribe_to_updates']))
			$pmp_subscribe_to_updates = 'off';
		else
			$pmp_subscribe_to_updates = $_POST['pmp_subscribe_to_updates'];

		update_post_meta($post_id, 'pmp_subscribe_to_updates', $pmp_subscribe_to_updates);
	}
}
add_action('save_post', 'pmp_subscribe_to_update_save');
