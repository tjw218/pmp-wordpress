<div class="wrap">
	<h2>PMP Groups &amp; Permissions</h2>

	<div id="pmp-groups">
	<?php foreach ($groups as $group) { ?>
		<div><?php echo $group->attributes->title; ?></div>
	<?php } ?>
	</div>
</div>
