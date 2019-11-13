<?php
require "admin_functions.php";

// halt if they're not logged in
if (!isset($user)) {
	header("Location: https://www.sprucehealth.com/");
	exit();
}

// halt if they're a deactivated user
if ($user["accesslevel"] == 0) {
	exit();
}

// start page output
require "include_first.php";

// check if path and target have been specified and are valid
if (isset($_POST["path"]) and isset($_POST["target"])
	and filter_var($_POST["target"], FILTER_VALIDATE_URL) and preg_match('/^[a-zA-Z0-9_-]+$/', $_POST["path"])
	and strlen($_POST["path"]) < 256 and strlen($_POST["target"]) < 2001) {

	$path = $quote->$_POST["path"];
	$target = $quote->$_POST["target"];

	// see if this path is already in use
	$pathcheck = $db->query("select id from links where path=$path and active is true");

	if (!$pathcheck->num_rows) {
		// prep variables for db entry
		$createdby = $quote->$user["id"];

		// if owner is specified and is user's ID or one of their groups, then use it
		if (isset($_POST["owner"]) and ($_POST["owner"] == $user["id"] or in_array($_POST["owner"], $user["memberships"]))) {
			$owner = $quote->$_POST["owner"];
		}
		// else, default owner is the creator
		else {
			$owner = $createdby;
		}

		// if notes content exists, use it
		if (isset($_POST["notes"])) {
			$notes = $quote->$_POST["notes"];
		}
		else {
			$notes = "";
		}

		// add new link to db and say successful if successful
		if ($db->query("insert into links (path, target, notes, createdby, owner) values ($path, $target, $notes, $createdby, $owner)") === true) {
			echo "<p>Link for \"".htmlentities($_POST["path"])."\" added!</p>";
		}
		// if unsuccessful, say so
		else {
			echo "<p>Sorry, something unknown went wrong adding your link.</p>";
		}
	}
	else echo "<p>Sorry, an active Spruce link with that path already exists.</p>";
	
}
else echo "<p>Sorry, something was wrong with your desired Spruce link or URL target.</p>";
?>

<p>
	Redirecting you to the admin panel in a few seconds...
</p>
<script>
	var timer = setTimeout(function() {
		window.location='admin.php'
	}, 3000);
</script>
<?
// end page output
require "include_last.php";
?>