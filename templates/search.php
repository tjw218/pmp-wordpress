<div id="pmp-search-page" class="wrap">
	<h2>Search the Platform</h2>

	<form id="pmp-search-form">
		<p class="submit">
			<input name="text" placeholder="Enter keywords" type="text">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Search">
		</p>
	</form>

	<div id="pmp-search-results"></div>
</div>

<script type="text/template" id="pmp-search-result-tmpl">
	<div class="pmp-search-result">
		<h3 class="pmp-title"><%= title %></h3>
		<div class="pmp-byline"><%= byline %></div>
	</div>
</script>
