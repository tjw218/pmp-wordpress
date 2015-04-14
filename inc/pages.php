<?php

/**
 * Render the plugin's options page
 *
 * @since 0.1
 */
function pmp_options_page() {
	if (!current_user_can('manage_options'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	pmp_render_template('settings.php');
}

/**
 * Render the plugin's search page
 *
 * @since 0.1
 */
function pmp_search_page() {
	if (!current_user_can('edit_posts'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	pmp_render_template('search.php', array(
		'creators' => pmp_get_creators(),
		'profiles' => pmp_get_profiles()
	));
}

/**
 * Render the plugin's groups and permissions page
 *
 * @since 0.2
 */
function pmp_groups_page() {
	if (!current_user_can('manage_options'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	$sdk = new SDKWrapper();
	$pmp_users = $sdk->query2json('queryDocs', array(
		'profile' => 'user',
		'limit' => 9999
	));

	$pmp_groups = $sdk->query2json('queryDocs', array(
		'profile' => 'group',
		'writeable' => 'true',
		'limit' => 9999
	));

	$context = array(
		'creators' => pmp_get_creators(),
		'users' => $pmp_users,
		'groups' => $pmp_groups,
		'default_group' => get_option('pmp_default_group', false)
	);
	pmp_render_template('groups.php', $context);
}

/**
 * Render the plugin's series and properties page
 *
 * @since 0.2
 */
function pmp_series_properties_page() {
	if (!current_user_can('manage_options'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	$context = array(
		'creators' => pmp_get_creators(),
		'default_collection' => get_option('pmp_default_collection', false)
	);
	pmp_render_template('series_property.php', $context);
}
