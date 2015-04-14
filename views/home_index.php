<style>
.portal-chooser .row > div {
	text-align: center;
	font-size: 22px;
}

.portal-chooser a {
	text-shadow: 0 0 4px rgba(0,0,0,0.2);
}
.portal-chooser a span {
	text-shadow: 0 0 6px rgba(0,0,0,0.3);
}

.portal-chooser a:hover {
	text-decoration: none;
}

.portal-chooser .well {
	color: #000;
	transition: background-color 160ms;
}

.portal-chooser .well:hover {
	background-color: #eaeaea;
}

.portal-chooser .well > .glyphicon {
	vertical-align: middle;
	font-size: 80px;
	display: block;
}
</style>

<div class="container-fluid portal-chooser">
	<div class="row">
<?php

	global $YODA_MODULES; // FIXME FIXME FIXME FIXME FIXME FIXME FIXME

	foreach ($YODA_MODULES as $module_name => $module) {
?>
		<div class="col-xs-12 col-md-4">
			<a href="<?=base_url($module_name)?>">
				<div class="well">
					<span class="<?=$module['icon_class']?>" aria-hidden="true"></span>
					<?=$module['label']?>
				</div>
			</a>
		</div>
<?php
	}
?>
	</div>
</div>
<div class="jumbotron">
	<h1>Welcome to Yoda!</h1>
	<p>
		Yoda is a share-collaborate environment blah blah blah yadayada.
	</p>
	<a class="btn btn-default" href="#">Learn more</a>
</div>
<h1>Data!</h1>
<p>
Data data data data data data data.
</p>
