<div id="pmp-search-page" class="wrap">
	<h2>Search the Platform</h2>

	<form id="pmp-search-form">
		<input name="text" placeholder="Enter keywords" type="text"></input>
		<span id="pmp-show-advanced"><a href="#">Show advanced options</a></span>
		<div id="pmp-advanced-search">
			<!-- Collection search (text-field) -->
			<input type="text" name="collection" placeholder="Search by collection"></input>

			<!-- Creator search (editable dropdown w/ 5 partners) -->
			<label for="profile">Search by content creator</label>
			<select name="creator">
				<option></option>
			</select>

			<!-- Profile search (static dropdown) -->
			<label for="profile">Search by content profile</label>
			<select name="profile">
				<option></option>
			</select>

			<!-- Has search (e.g., has image) (static dropdown) -->
			<label for="profile">Find content that has:</label>
			<select name="has">
				<option></option>
			</select>

			<!-- Tags search (text-field) -->
			<input type="text" name="tags" placeholder="Search by tag"></input>

			<!-- GUID search -->
			<input type="text" name="guid" placeholder="Search by GUID"></input>

		</div>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Search"></input>
		</p>
	</form>

	<div id="pmp-search-results"></div>
</div>

<script type="text/template" id="pmp-search-result-tmpl">
	<div class="pmp-search-result">
		<h3 class="pmp-title"><%= title %></h3>
		<div class="pmp-result-details">
			<% if (typeof byline != 'undefined') { %><div class="pmp-byline">By <%= byline %></div><% } %>
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
