<!DOCTYPE html>
<html lang="en-US" xml:lang="en" xmlns:msxsl="urn:schemas-microsoft-com:xslt" xmlns:igxlib="urn:igxlibns">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="<?= $description ?>">
	<meta http-equiv="Content-Type" content="text/html">
	<meta name="google-signin-scope" content="profile email">
	<meta name="google-signin-client_id" content="<?= $google_client_id ?>">
	<title><?= $title ?></title>
	<script src="http://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
	<link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="css/styles.css">
	<script src="https://apis.google.com/js/platform.js" async defer></script>
	<link rel='icon' href='images/favicon.png' type='image/x-icon'>
	<script src="https://kit.fontawesome.com/8773fdbabc.js"></script>
</head>
<body>
	<header>
		<div class="header-logo">
			<img src="images/arc-logo.svg" alt="american river college logo" />
		</div>
		<div class="header-content">
			<h1><?= $title ?></h1>
		</div>
	</header>