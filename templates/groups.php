<div class="wrap">
	<h2>PMP Groups &amp; Permissions</h2>

	<div id="pmp-groups-actions">
		<p class="submit">
			<input type="submit" name="pmp-create-group" id="pmp-create-group" class="button button-primary" value="Create new group">
		</p>
	</div>

	<div id="pmp-groups">
	<?php foreach ($groups as $group) { ?>
		<div><?php echo $group->attributes->title; ?></div>
	<?php } ?>
	</div>
</div>
