<?php

function pmp_register_assets() {
	/* Styles */
	wp_register_style(
		'pmp-common',
		PMP_PLUGIN_DIR_URI . '/assets/css/style.css'
	);

	wp_register_style(
		'pmp-chosen',
		PMP_PLUGIN_DIR_URI . '/assets/js/vendor/chosen/chosen.min.css'
	);

	/* PMP Scripts */
	$pmp_scripts = array(
		array(
			'pmp-utils',
			PMP_PLUGIN_DIR_URI . '/assets/js/pmp-utils.js',
			array('jquery'),
			PMP_VERSION,
			true
		),
		array(
			'pmp-common',
			PMP_PLUGIN_DIR_URI . '/assets/js/pmp-common.js',
			array('pmp-utils', 'underscore', 'backbone'),
			PMP_VERSION,
			true
		),
		array(
			'pmp-post',
			PMP_PLUGIN_DIR_URI . '/assets/js/pmp-post.js',
			array('pmp-common', 'pmp-chosen'),
			PMP_VERSION,
			true
		),
		array(
			'pmp-search',
			PMP_PLUGIN_DIR_URI . '/assets/js/pmp-search.js',
			array('pmp-common'),
			PMP_VERSION,
			true
		),
		array(
			'pmp-groups-menu',
			PMP_PLUGIN_DIR_URI . '/assets/js/pmp-groups-menu.js',
			array('pmp-common','pmp-typeahead'),
			PMP_VERSION,
			true
		),
		array(
			'pmp-collections-menu',
			PMP_PLUGIN_DIR_URI . '/assets/js/pmp-collections-menu.js',
			array('pmp-common'),
			PMP_VERSION,
			true
		),
		array(
			'pmp-options-menu',
			PMP_PLUGIN_DIR_URI . '/assets/js/pmp-options.js',
			array('jquery', 'underscore'),
			PMP_VERSION,
			true
		),
		array(
			'pmp-manage-searches',
			PMP_PLUGIN_DIR_URI . '/assets/js/pmp-manage-searches.js',
			array('pmp-common'),
			PMP_VERSION,
			true
		)
	);

	foreach ($pmp_scripts as $pmp_script)
		call_user_func_array('wp_register_script', $pmp_script);

	/* Vendor scripts */
	$vendor_scripts = array(
		array(
			'pmp-typeahead',
			PMP_PLUGIN_DIR_URI . '/assets/js/vendor/typeahead.js/dist/typeahead.bundle' . ((PMP_DEBUG)? '':'.min') . '.js',
			array('jquery'),
			PMP_VERSION,
			true
		),
		array(
			'pmp-chosen',
			PMP_PLUGIN_DIR_URI . '/assets/js/vendor/chosen/chosen.jquery.min.js',
			array('jquery'),
			PMP_VERSION,
			true
		)
	);

	foreach ($vendor_scripts as $vendor_script)
		call_user_func_array('wp_register_script', $vendor_script);
}
add_action('admin_enqueue_scripts', 'pmp_register_assets', 1);

/**
 * Enqueue styles and scripts for the search page
 *
 * @since 0.1
 */
function pmp_enqueue_assets() {
	if (isset($_GET['page'])) {
		$page = $_GET['page'];

		if ($page == 'pmp-search') {
			wp_enqueue_style('pmp-common');
			wp_enqueue_script('pmp-search');
		}

		if ($page == 'pmp-groups-menu') {
			wp_enqueue_style('pmp-common');
			wp_enqueue_script('pmp-groups-menu');
		}

		if (in_array($page, array('pmp-series-menu', 'pmp-properties-menu'))) {
			wp_enqueue_style('pmp-common');
			wp_enqueue_script('pmp-collections-menu');
		}

		if ($page == 'pmp-options-menu') {
			wp_enqueue_script('pmp-options-menu');
		}

		if ($page == 'pmp-manage-saved-searches') {
			wp_enqueue_style('pmp-common');
			wp_enqueue_script('pmp-manage-searches');
		}

		return;
	}

	$screen = get_current_screen();
	if ($screen->base == 'post' && $screen->post_type == 'post') {
		wp_enqueue_style('pmp-common');
		wp_enqueue_style('pmp-chosen');
		wp_enqueue_script('pmp-post');
	}
}
add_action('admin_enqueue_scripts', 'pmp_enqueue_assets', 2);

/**
 * Print the underscore template for the PMP.Modal view.
 *
 * @since 0.2
 */
function pmp_modal_underscore_template() { ?>
<script type="text/template" id="pmp-modal-tmpl">
	<div class="pmp-modal-header">
		<div class="pmp-modal-close"><span class="close">&#10005;</span></div>
	</div>
	<div class="pmp-modal-content"><% if (content) { %><%= content %><% } %></div>
	<div class="pmp-modal-actions">
		<span class="spinner"></span>
		<% _.each(actions, function(v, k) { %>
			<a href="#" class="<%= k %> button button-primary"><%= k %></a>
		<% }); %>
	</div>
</script><?php
}

/**
 * Print the underscore template for the SaveQueryModal views.
 *
 * @since 0.3
 */
function pmp_save_search_query_template($query_data=null) { ?>
<script type="text/template" id="pmp-save-query-tmpl">
	<div id="pmp-save-query-modal-inner">
		<h3><% if (typeof search_id !== 'undefined') { %>Edit<% } else { %>Save<% } %> the current query</h3>
		<form>
			<% if (typeof search_id !== 'undefined') { %><input type="hidden" name="search_id" value="<%= search_id %>" /><% } %>

			<div class="form-group">
				<label for="title">Please specify a title for your search query:</label>
				<input required type="text" name="title" placeholder="Enter a title for the current query" />
			</div>

			<div class="form-group">
				<label>Automatically:</label>
				<label for="query_auto_draft">
					<input id="query_auto_draft" type="radio" name="query_auto_create" value="draft" /> Create draft posts from results for this query
				</label>
				<label for="query_auto_publish">
					<input id="query_auto_publish" type="radio" name="query_auto_create" value="publish" /> Publish posts from results for this query
				</label>
				<label for="query_auto_nothing">
					<input id="query_auto_nothing" type="radio" name="query_auto_create" value="off" checked/> Do nothing with results for this query
				</label>
			</div>

			<div class="form-group">
				<label for="post_category">Categories:</label>
				<p>Choose categories for posts imported by this query</p>
				<div class="pmp-category-checklist">
					<ul>
						<?php
							if (!empty($query_data->options) && !empty($query_data->options->post_category))
								$selected_cats = $query_data->options->post_category;
							else
								$selected_cats = null;
							wp_category_checklist(null, null, $selected_cats);
						?>
					</ul>
				</div>
			</div>
			<?php
				if (!empty($query_data)) {
					$last_saved_search_cron = get_option(
						'pmp_last_saved_search_cron_' . sanitize_title($query_data->options->title), false);
				}
			?>
			<div class="form-group<?php if (!empty($last_saved_search_cron)) { ?> disabled<?php } ?>">
				<label for="initial_pull_limit">Initial pull limit:</label>
				<p>Specify the max number of posts to pull the first time this query runs.</p>
				<p>The default is 25, but can be up to 100.</p>
				<input type="text" name="initial_pull_limit" placeholder="25" <?php if (!empty($last_saved_search_cron)) { ?> disabled<?php } ?> />
			</div>
		</form>
	</div>
</script><?php
}

/**
 * Output the underscore template for the async select menu used on the post edit page
 *
 * @since 0.3
 */
function pmp_async_select_template() { ?>
	<script type="text/template" id="pmp-async-select-tmpl">
		<strong><%= type.charAt(0).toUpperCase() + type.slice(1) %></strong>
		<select name="pmp_<%= type %>_override<% if (multiSelect) { %>[]<% } %>" <% if (multiSelect) { %>multiple<% } %>>
			<% _.each(options, function(option, idx) { %>
				<% if (multiSelect && option.guid == '') { return; } %>
				<option <% if (option.selected) { %>selected="selected"<% } %> value="<%= option.guid %>">
					<%= option.title %>
					<% if (default_guid && option.guid == default_guid) { %>(default)<% } %>
				</option>
			<% }) %>
		</select>
	</script><?php
}

/**
 * Builds a PMP object with common attributes used throughout the plugin's javascript files.
 *
 * @since 0.2
 */
function pmp_json_obj($add=array()) {
	return array_merge(array(
		'creators' => array_flip(pmp_get_creators()),
		'ajax_nonce' => wp_create_nonce('pmp_ajax_nonce')
	), $add);
}
