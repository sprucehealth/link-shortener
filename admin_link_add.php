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

// create array to hold submitted link info
$submittedlinks = array();

// if info for one single link has been submitted, record it
if (isset($_POST["path"]) and isset($_POST["target"]) and isset($_POST["owner"]) and isset($_POST["notes"])) {
	$submittedlinks[0]["path"] = $_POST["path"];
	$submittedlinks[0]["target"] = $_POST["target"];
	$submittedlinks[0]["owner"] = $_POST["owner"];
	$submittedlinks[0]["notes"] = $_POST["notes"];
}
// if CSV of link info has been submitted, process it
elseif (isset($_FILES["csvfile"]) and $_FILES["csvfile"]["error"] == 0) {
	// read uploaded file into array
	$csvlinks = array_map("str_getcsv", file($_FILES["csvfile"]["tmp_name"], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

	// process row uploaded CSV info and add to submittedlinks array
	// intentionally ignore first row (column names)
	for ($i=1; $i < count($csvlinks); $i++) {
		// row must have exactly four items; skip if it doesn't
		if (count($csvlinks[$i]) == 4) {
			// create array of current link info
			$csvlink["path"] = $csvlinks[$i][0];
			$csvlink["target"] = $csvlinks[$i][1];
			$csvlink["notes"] = $csvlinks[$i][2];
			$csvlink["owner"] = $csvlinks[$i][3];

			// add link to submittedlinks array
			$submittedlinks[] = $csvlink;
		}
	}
}

// process submitted link info and add each link to db if valid
foreach ($submittedlinks as $link) {
	// check if path is valid
	if (preg_match('/^[a-zA-Z0-9_-]+$/', $link["path"]) and strlen($link["path"]) < 256) {
		// check if target is valid
		if (filter_var($link["target"], FILTER_VALIDATE_URL) and strlen($link["target"]) < 2001) {
			// prep path, target, and notes for db use
			$path = $quote->$link["path"];
			$target = $quote->$link["target"];
			$notes = $quote->$link["notes"];

			// see if this path is already in use
			$pathcheck = $db->query("select id from links where path=$path and active is true");

			// if the path is not in active use, attempt to add the link to db
			if (!$pathcheck->num_rows) {
				// assign current user as creator of the link
				$createdby = $quote->$user["id"];

				// if owner is specified and is user's ID or one of their groups, then use it
				if ($link["owner"] == $user["id"] or in_array($link["owner"], $user["memberships"])) {
					$owner = $quote->$link["owner"];
				}
				// else, default owner is the creator
				else $owner = $createdby;

				// add new link to db and say successful if successful
				if ($db->query("insert into links (path, target, notes, createdby, owner) values ($path, $target, $notes, $createdby, $owner)") === true) {
					echo "Link for \"".htmlentities($link["path"])."\" added successfully.<br />";
				}
				else echo "Sorry, something unknown went wrong adding the link.<br />";
			}
			else echo "An active link with that path already exists.<br />";
		}
		else echo "Invalid URL target specified.<br />";
	}
	else echo "Invalid path specified.<br />";
}
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