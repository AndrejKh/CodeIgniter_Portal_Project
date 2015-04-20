<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
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
	if (isset($user) && isset($user['userName'])) {
?>
	<script>
		$(function() {
			YodaPortal.extend('user', {
				userName: '<?=$user['userName']?>',
			});
		});
	</script>
<?php
	}
?>
</head>
<body>
