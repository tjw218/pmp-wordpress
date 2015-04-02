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
			<div class="pmp-group-container">
				<h4><?php echo $group->attributes->title; ?></h4>
				<div class="pmp-group-actions">
					<ul>
						<li><a
							data-guid="<?php echo $group->attributes->guid; ?>"
							data-title="<?php echo $group->attributes->title; ?>"
							data-tags="<?php if (is_array($group->attributes->tags)) { echo join(',', $group->attributes->tags); } ?>"
							class="pmp-group-modify" href="#">Modify</a></li>
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

<script type="text/javascript">
	var CREATORS = <?php echo json_encode(array_flip($creators)); ?>,
		AJAX_NONCE = '<?php echo wp_create_nonce('pmp_ajax_nonce'); ?>';
</script>
