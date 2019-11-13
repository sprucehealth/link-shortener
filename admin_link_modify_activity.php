<?php
require "admin_functions.php";

// halt if they're not logged in
if (!isset($user)) {
	header("Location: https://www.sprucehealth.com/");
	exit();
}

// check if link id and status have been specified and are valid
if (isset($_GET["link_id"]) and isset($_GET["active"])
	and is_numeric($_GET["link_id"]) and ($_GET["active"] == "true" or $_GET["active"] == "false")) {

	// get info of specified link
	// including figuring out if there is already an active link that shares this specified link's path (an "active_duplicate"); there should only be one active link per path at a time
	$link_id = $quote->$_GET["link_id"];

	$link = $db->query("
		select
			l.owner,
		    (select count(*) from links as dupes where dupes.path = l.path and dupes.id != l.id and dupes.active is true) as active_duplicate
		from
			links as l
		where
			id = $link_id
		");

	// fail if specified link not found
	// shouldn't actually have an adverse effect to run rest of script in this case, but being thorough
	if (!$link->num_rows) {
		exit();
	}

	while($linkrow = $link->fetch_assoc()) {
		// record link owner ID
		$link_owner = $linkrow["owner"];

		// fail if trying to activate a link that has an active_duplicate
		if ($_GET["active"] == "true" and $linkrow["active_duplicate"] > 0) {
			exit();
		}
	}

	// if user owns this link (or belongs to group that does), and they are not deactivated, then okay to update link
	// also okay to update link if user is a superuser
	if (($user["accesslevel"] == 10 and ($link_owner == $user["id"] or in_array($link_owner, $user["memberships"])))
		or $user["accesslevel"] == 100) {

		// update link status
		$activity = $_GET["active"]; // don't quote this one or it fails

		$updatelink = "
			update links
			set
			active = $activity
			where id = $link_id
		";

		// run the update query and
		$db->query($updatelink);

		// if the update was successful, record the change in the link change audit table
		// only records a change if a row was actually changed
		if($db->affected_rows) {
			// use activity boolean to determine change_type
			$change_type = $activity == "true" ? "'activated'" : "'deactivated'";

			// get user id
			$user_id = $quote->$user["id"];

			// add audit record
			$db->query("insert into link_modifications (link_id, modifier_entity_id, change_type) values ($link_id, $user_id, $change_type)");
		}
	}
}
?>