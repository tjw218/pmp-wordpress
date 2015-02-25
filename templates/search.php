<div id="pmp-search-page" class="wrap">
	<h2>Search the Platform</h2>

	<form id="pmp-search-form">
		<input name="text" placeholder="Enter keywords" type="text"></input>
		<span id="pmp-show-advanced"><a href="#">Show advanced options</a></span>
		<div id="pmp-advanced-search">
			<div class="left">
				<!-- Creator search (editable dropdown w/ 5 partners) -->
				<label for="profile">Content creator:</label>
				<select name="creator">
					<option value="">Any</option>
					<?php foreach ($creators as $name => $guid) { ?>
					<option value="<?php echo $guid; ?>"><?php echo $name; ?></option>
					<?php } ?>
				</select>

				<!-- Profile search (static dropdown) -->
				<label for="profile">Content profile:</label>
				<select disabled name="profile">
					<?php foreach ($profiles as $name => $value) { ?>
					<option <?php if ($value == 'story') { ?>selected="selected"<?php } ?> value="<? echo $value; ?>"><? echo $name; ?></option>
					<?php } ?>
				</select>

				<!-- Has search (e.g., has image) (static dropdown) -->
				<div id="pmp-content-has-search">
					<label for="has">Find content that contains:</label>
					<select name="has">
						<option value="">Any media</option>
						<option value="image">Image</option>
						<option value="audio">Audio</option>
						<option value="video">Video</option>
					</select>
				</div>
			</div>
			<div class="right">
				<!-- Collection search (text-field) -->
				<label for="collection">Search by collection GUID:</label>
				<input type="text" name="collection" placeholder="Search by collection GUID"></input>

				<!-- Tags search (text-field) -->
				<label for="tag">Search by tag (comma separated list):</label>
				<input type="text" name="tag" placeholder="Search by tag"></input>

				<!-- GUID search -->
				<label for="guid">Search by GUID:</label>
				<input type="text" name="guid" placeholder="Search by GUID"></input>
			</div>
		</div>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Search"></input>
			<span class="spinner"></span>
		</p>
	</form>

	<div id="pmp-search-results"></div>
</div>

<script type="text/template" id="pmp-search-result-tmpl">
	<div class="pmp-search-result">
		<h3 class="pmp-title"><%= title %></h3>
		<div class="pmp-result-details">
			<% if (typeof byline != 'undefined') { %><div class="pmp-byline">By <%= byline %></div><% } %>
			<% if (typeof creator != 'undefined') { %><div class="pmp-creator"><%= creator %></div><% } %>
			<% if (typeof teaser != 'undefined') { %>
				<div class="pmp-teaser">
					<% if (image) { %><img class="pmp-image" src="<%= image %>" /><% } %>
					<%= teaser %>
				</div>
			<% } else if (image) { %><img class="pmp-image" src="<%= image %>" /><% } %>
		</div>
		<div class="pmp-result-actions">
		  <ul>
			<li><a class="pmp-draft-action" href="#">Create draft</a></li>
			<li><a class="pmp-publish-action" href="#">Publish</a></li>
		  </ul>
		</div>
	</div>
</script>

<script type="text/template" id="pmp-search-results-pagination-tmpl">
	<div id="pmp-search-results-pagination">
		<a href="#" class="disabled prev button button-primary">Previous</a>
		<a href="#" class="disabled next button button-primary">Next</a>
		<p class="pmp-page-count">Page <span class="pmp-page"></span> of <span class="pmp-total-pages"></span></p>
	</div>
</script>

<script type="text/template" id="pmp-modal-tmpl">
	<div class="pmp-modal-header">
		<div class="pmp-modal-close"><span class="close">&#10005;</span></div>
	</div>
	<div class="pmp-modal-content"><% if (message) { %><%= message %><% } %></div>
	<div class="pmp-modal-actions">
		<% _.each(actions, function(v, k) { %>
			<a href="#" class="<%= k %> button button-primary"><%= k %></a>
		<% }); %>
	</div>
</script>

<script type="text/javascript">
	var CREATORS = <?php echo json_encode(array_flip($creators)); ?>,
		AJAX_NONCE = '<?php echo wp_create_nonce('pmp_ajax_nonce'); ?>';
</script>
