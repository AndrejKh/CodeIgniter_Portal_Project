<nav class="navbar navbar-default navbar-static-top">
	<div class="container">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="<?=base_url()?>">Yoda Portal</a>
		</div>
		<div id="navbar" class="collapse navbar-collapse">
			<ul class="nav navbar-nav">
<?php

global $YODA_MODULES; // FIXME.

	foreach ($YODA_MODULES as $module_name => $module) {
		$active = (isset($active_module) && $active_module === $module_name);
?>
				<li class="<?=$active ? 'active' : ''?>">
					<a href="<?=base_url($module_name)?>">
						<?=htmlentities($module['label'])?>
					</a>
				</li>
<?php
	}
?>
			</ul>
			<div class="navbar-form navbar-right">
				<button class="btn btn-primary">Log in</button>
			</div>
		</div>
	</div>
</nav>
<div class="container page">
