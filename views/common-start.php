<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Yoda Portal</title>
	<link rel="stylesheet" href="<?=base_url('/fw/bootstrap/css/bootstrap.min.css')?>">
	<link rel="stylesheet" href="<?=base_url('/fw/select2/select2.css')?>">
	<link rel="stylesheet" href="<?=base_url('/fw/select2/select2-bootstrap.min.css')?>">
<?php
	if (isset($style_includes)) {
		foreach ($style_includes as $include) {
?>
	<link rel="stylesheet" href="<?=base_url($include)?>" />
<?php
		}
	}
?>
	<script src="<?=base_url('/fw/jquery/js/jquery.min.js')?>"></script>
	<script src="<?=base_url('/fw/bootstrap/js/bootstrap.min.js')?>"></script>
	<script src="<?=base_url('/fw/select2/select2.min.js')?>"></script>
	<script src="<?=base_url('/js/yoda-portal.js')?>"></script>
<?php
	if (isset($script_includes)) {
		foreach ($script_includes as $include) {
?>
	<script src="<?=base_url($include)?>"></script>
<?php
		}
	}
	if (isset($user) && isset($user['username'])) {
?>
	<script>
		$(function() {
			YodaPortal.extend('user', {
				username: '<?=$user['username']?>',
			});
		});
	</script>
<?php
	}
?>
</head>
<body>

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

	foreach ($YODA_MODULES as $moduleName => $module) {
		$active = (isset($activeModule) && $activeModule === $moduleName);
?>
				<li class="<?=$active ? 'active' : ''?>">
					<a href="<?=base_url($moduleName)?>">
						<?=htmlentities($module['label'])?>
					</a>
				</li>
<?php
	}
?>
			</ul>
			<?php if (isset($user) && isset($user['username'])) { ?>
			<div class="navbar-form navbar-right">
				<a class="logout" href="<?=base_url('user/logout')?>">Log out <?=$user['username']?></a>
			</div>
			<?php } else { ?>
			<div class="navbar-form navbar-right">
					<a class="btn btn-primary" href="/user/login">Sign in</a>
			</div>
			<?php } ?>
		</div>
	</div>
</nav>
<div class="container page">
<div id="messages"></div>
