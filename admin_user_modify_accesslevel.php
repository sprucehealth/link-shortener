<?php
require "admin_functions.php";

// halt if user id and accesslevel have not been specified or are not valid
if (!(isset($_GET["user_id"]) and isset($_GET["accesslevel"])
	and is_numeric($_GET["user_id"]) and ($_GET["accesslevel"] == "0" or $_GET["accesslevel"] == "10" or $_GET["accesslevel"] == "100"))) {

	exit();
}

// halt if user not logged in, if their accesslevel is not 100 (superuser), or if they're trying to modify their own accesslevel
if (!isset($user) or $user["accesslevel"] != 100 or $user["id"] == $_GET["user_id"]) {
	exit();
}

// output debug info
echo "user ID ".$_GET["user_id"]." and accesslevel ".$_GET["accesslevel"];

// get info of specified user
$user_id = $quote->$_GET["user_id"];

$finduser = $db->query("
	select
		id
	from
		entities
	where
		id = $user_id
		and type='user'
	");

// fail if specified user not found
// shouldn't actually have an adverse effect to run rest of script in this case, but being thorough
if (!$finduser->num_rows) {
	exit();
}

// update user
$accesslevel = $quote->$_GET["accesslevel"];

$updateuser = "
	update entities
	set
	accesslevel = $accesslevel
	where id = $user_id
";

// run the update query and
$db->query($updateuser);

// if the update was successful, record the change in the user change audit table
// only records a change if a row was actually changed
if($db->affected_rows) {
	// record change type
	$change_type = "'accesslevel changed to ".$_GET["accesslevel"]."'";

	// get id of user who is doing the modifying
	$modifier_entity_id = $quote->$user["id"];

	// add audit record
	$db->query("insert into user_modifications (user_id, modifier_entity_id, change_type) values ($user_id, $modifier_entity_id, $change_type)");
}

?>