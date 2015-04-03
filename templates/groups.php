<div class="wrap">
	<h2>PMP Groups &amp; Permissions</h2>

	<div id="pmp-groups">
		<h3>Your groups</h3>

		<div id="pmp-groups-actions">
			<p class="submit">
				<input type="submit" name="pmp-create-group" id="pmp-create-group" class="button button-primary" value="Create new group">
			</p>
		</div>

		<?php foreach ($groups as $group) { ?>
			<div class="pmp-group-container"
				data-guid="<?php echo esc_attr($group->attributes->guid); ?>"
				data-title="<?php echo esc_attr($group->attributes->title); ?>"
				data-tags="<?php if (is_array($group->attributes->tags)) { echo esc_attr(join(',', $group->attributes->tags)); } ?>">

				<h3><?php echo $group->attributes->title; ?>
					<?php if ($group->attributes->guid == $default_group) { ?><span class="pmp-default-group">(default)</span><?php } ?></h3>
				<div class="pmp-group-actions">
					<ul>
						<li>
							<a class="pmp-group-modify" href="#">Modify</a>
						</li>
						<?php if ($group->attributes->guid !== $default_group) { ?>
						<li>
							<a class="pmp-group-default" href="#">Set as default</a>
						</li>
						<?php } ?>
					</ul>
				</div>
			</div>
		<?php } ?>
	</div>
</div>

<?php pmp_modal_underscore_template(); ?>

<script type="text/template" id="pmp-create-new-group-form-tmpl">
	<h2>Create a group</h2>
	<form id="pmp-group-create-form">
		<label>Title (required)</label>
		<input type="text" name="title" id="title" placeholder="Group title" required>

		<label>Tags</label>
		<input type="text" name="tags" id="tags" placeholder="Group tags">
	</form>
</script>

<script type="text/template" id="pmp-modify-group-form-tmpl">
	<h2>Modify group</h2>
	<form id="pmp-group-modify-form">
		<label>Title (required)</label>
		<input type="text" name="title" id="title" placeholder="Group title" required
			<% if (group.title) { %>value="<%= group.title %>"<% } %>>

		<label>Tags</label>
		<input type="text" name="tags" id="tags" placeholder="Group tags"
			<% if (group.tags) { %>value="<%= group.tags %>"<% } %>>
	</form>
</script>

<script type="text/template" id="pmp-default-group-form-tmpl">
	<div class="pmp-group-default-container">
		<h2>Set default group for new posts</h2>
		<p>Do you really want to set the group <strong>"<%= group.title %>"</strong> as the default group for all new posts?</p>
		<form id="pmp-group-default-form">
			<input type="hidden" name="guid" id="guid" value="<%= group.guid %>" >
		</form>
	</div>
</script>

<script type="text/javascript">
	var CREATORS = <?php echo json_encode(array_flip($creators)); ?>,
		AJAX_NONCE = '<?php echo wp_create_nonce('pmp_ajax_nonce'); ?>';
</script>
