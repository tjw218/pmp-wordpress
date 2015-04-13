<div class="wrap">
	<h2>PMP Series &amp; Properties</h2>

	<div id="pmp-series-properties">
		<div id="pmp-series-properties-actions">
			<p class="submit">
				<input type="submit" name="pmp-create-series-property" id="pmp-create-series-property" class="button button-primary" value="Create new series or property">
			</p>
		</div>

		<div id="pmp-series-property-container">
			<span class="spinner"></span>
			<div id="pmp-series-properties-list"></div>
		</div>
	</div>
</div>

<?php pmp_modal_underscore_template(); ?>

<script type="text/template" id="pmp-create-new-series-property-form-tmpl">
	<h2>Create a series or property</h2>
	<form id="pmp-series-property-create-form">
		<label>Title (required)</label>
		<input type="text" name="title" id="title" placeholder="Series/property title" required>

		<label>Tags</label>
		<input type="text" name="tags" id="tags" placeholder="Series/property tags">
	</form>
</script>

<script type="text/template" id="pmp-modify-series-property-form-tmpl">
	<h2>Modify series/property</h2>
	<form id="pmp-series-property-modify-form">
		<label>Title (required)</label>
		<input type="text" name="title" id="title" placeholder="Series/property title" required
			<% if (series_property.get('attributes').title) { %>value="<%= series_property.get('attributes').title %>"<% } %>>

		<label>Tags</label>
		<input type="text" name="tags" id="tags" placeholder="Series/property tags"
			<% if (series_property.get('attributes').tags) { %>value="<%= series_property.get('attributes').tags %>"<% } %>>
	</form>
</script>

<script type="text/template" id="pmp-default-collection-form-tmpl">
	<div class="pmp-collection-default-container">
		<h2>Set default collection for new posts</h2>
		<p>Do you really want to set the collection <strong>"<%= collection.get('attributes').title %>"</strong> as the default collection for all new posts?</p>
		<form id="pmp-collection-default-form">
			<input type="hidden" name="guid" id="guid" value="<%= collection.get('attributes').guid %>" >
		</form>
	</div>
</script>

<script type="text/template" id="pmp-series-properties-items-tmpl">
	<% collection.each(function(series_property) { %>
		<div class="pmp-series-property-container">
			<h3><%= series_property.get('attributes').title %>
				<% if (series_property.get('attributes').guid == DEFAULT_COLLECTION) { %><span class="pmp-default-collection">(default)</span><% } %></h3>
			<div class="pmp-series-property-actions">
				<ul>
					<li>
						<a class="pmp-series-property-modify" data-guid="<%= series_property.get('attributes').guid %>" href="#">Modify</a>
					</li>
					<% if (series_property.get('attributes').guid !== DEFAULT_GROUP) { %>
					<li>
						<a class="pmp-collection-default" data-guid="<%= series_property.get('attributes').guid %>" href="#">Set as default</a>
					</li>
					<% } %>
				</ul>
			</div>
		</div>
	<% }); %>
</script>

<script type="text/javascript">
	var CREATORS = <?php echo json_encode(array_flip($creators)); ?>,
		AJAX_NONCE = '<?php echo wp_create_nonce('pmp_ajax_nonce'); ?>';
		DEFAULT_COLLECTION = '<?php echo $default_collection; ?>';
</script>
