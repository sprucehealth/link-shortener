<?php
// make sure UTF-8 character set is specified for the page output
header('Content-Type: text/html; charset=utf-8');

// output debug var to console
if (isset($debugvar)) echo "<script>console.log('".$debugvar."');</script>";
?>

<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="stylesheet" type="text/css" href="style.css">
		<title><?php echo $pagetitle ?? 'Spruce Link Shortener' ?></title>
		<script src="jquery-3-4-1-min.js"></script>
	</head>

	<body>
