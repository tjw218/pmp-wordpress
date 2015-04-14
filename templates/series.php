<div class="wrap">
	<h2>PMP Series</h2>

	<div id="pmp-series">
		<div id="pmp-series-actions">
			<p class="submit">
				<input type="submit" name="pmp-create-series" id="pmp-create-series" class="button button-primary" value="Create new series">
			</p>
		</div>

		<div id="pmp-series-container">
			<span class="spinner"></span>
			<div id="pmp-series-list"></div>
		</div>
	</div>
</div>

<?php pmp_modal_underscore_template(); ?>

<script type="text/template" id="pmp-create-new-series-form-tmpl">
	<h2>Create a series</h2>
	<form id="pmp-series-create-form">
		<label>Title (required)</label>
		<input type="text" name="title" id="title" placeholder="Series title" required>

		<label>Tags</label>
		<input type="text" name="tags" id="tags" placeholder="Series tags">
	</form>
</script>

<script type="text/template" id="pmp-modify-series-form-tmpl">
	<h2>Modify series</h2>
	<form id="pmp-series-modify-form">
		<label>Title (required)</label>
		<input type="text" name="title" id="title" placeholder="Series title" required
			<% if (series.get('attributes').title) { %>value="<%= series.get('attributes').title %>"<% } %>>

		<label>Tags</label>
		<input type="text" name="tags" id="tags" placeholder="Series tags"
			<% if (series.get('attributes').tags) { %>value="<%= series.get('attributes').tags %>"<% } %>>
	</form>
</script>

<script type="text/template" id="pmp-default-series-form-tmpl">
	<div class="pmp-series-default-container">
		<h2>Set default series for new posts</h2>
		<p>Do you really want to set the series<strong>"<%= series.get('attributes').title %>"</strong> as the default series for all new posts?</p>
		<form id="pmp-series-default-form">
			<input type="hidden" name="guid" id="guid" value="<%= series.get('attributes').guid %>" >
		</form>
	</div>
</script>

<script type="text/template" id="pmp-series-items-tmpl">
	<% collection.each(function(series) { %>
		<div class="pmp-series-container">
			<h3><%= series.get('attributes').title %>
				<% if (series.get('attributes').guid == DEFAULT_SERIES) { %><span class="pmp-default-series">(default)</span><% } %></h3>
			<div class="pmp-series-actions">
				<ul>
					<li>
						<a class="pmp-series-modify" data-guid="<%= series.get('attributes').guid %>" href="#">Modify</a>
					</li>
					<% if (series.get('attributes').guid !== DEFAULT_SERIES) { %>
					<li>
						<a class="pmp-series-default" data-guid="<%= series.get('attributes').guid %>" href="#">Set as default</a>
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
		DEFAULT_SERIES= '<?php echo $default_series; ?>';
</script>
