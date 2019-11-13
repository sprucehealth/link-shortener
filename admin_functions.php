<?php
session_start();

// include google auth stuff
require_once "./google-api-php-client-2.2.2/vendor/autoload.php";

// establish db connection and other page-start things
require "functions.php";

// set google auth stuff
$client = new Google_Client();
$client->setAuthConfig("../../private/client_secret_1079714784527-ieo78jj5jdupur4p5smhuh9hrcsrj8r5.apps.googleusercontent.com.json");
$client->setRedirectUri("https://l.sprucehealth.com/auth.php");
$client->setAccessType("offline"); // used to create refresh token
$client->setApprovalPrompt("force"); // also need this to make sure that google returns a refresh token
$client->addScope("profile");
$client->addScope("email");

// debug flag to expire the current token
if (isset($_GET["expirenow"])) {
	$_SESSION["token"] = "garbage";
}

// get and use the google oauth access token of the session, if present
if (!empty($_SESSION["token"])) {
	$token = $_SESSION["token"];
	$client->setAccessToken($token);

	$debugvar .= "aardvark ".var_export($client->isAccessTokenExpired(), true)."\\n"; // debug tracer
	// $debugvar .= "what it has: ".$client->getRefreshToken()."\\n"; // debug tracer
}

// if access token wasn't present in session or is expired, refresh it (if refresh token is present)
if ($client->isAccessTokenExpired() and !empty($_SESSION["refresh_token"])) {
	// get refresh token and set it
	$client->refreshToken($_SESSION["refresh_token"]);

	// get new access token and use it
	$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

	// store new access token in session variable
	$_SESSION["token"] = $client->getAccessToken();

	$debugvar .= "badger ".var_export($client->isAccessTokenExpired(), true)."\\n"; // debug tracer
}

// log out, if requested
// note: add "?logout" to the URL to log out
if (isset($_GET["logout"])) {
	unset($_SESSION["token"]);
	unset($_SESSION["refresh_token"]);
	session_unset();
	session_destroy();
	header("Location: https://l.sprucehealth.com/admin.php");
	exit();
}

// if user is logged in (via google oauth)
if (!$client->isAccessTokenExpired()) {
	// grab info of currently logged-in user
	// available keys in array will be like so:
	// "id": "116852075463130584610",
	// "email": "david@sprucehealth.com",
	// "verified_email": true,
	// "name": "David Craig",
	// "given_name": "David",
	// "family_name": "Craig",
	// "link": "https://plus.google.com/116852075463130584610",
	// "picture": "https://lh6.googleusercontent.com/-jjMn5WVT18Q/AAAAAAAAAAI/AAAAAAAAAC0/5rNm0I8wdRg/photo.jpg",
	// "locale": "en", 
	// "hd": "sprucehealth.com"
	$user_json = file_get_contents("https://www.googleapis.com/oauth2/v1/userinfo?access_token=".$client->getAccessToken()["access_token"]);
	$user = json_decode($user_json,true);

	// check for user in db
	// grab their group memberships while we're at it
	$email = $quote->$user["email"];
	$usercheck = $db->query("
		select
			e.id,
			e.accesslevel,
			m.group_id
		from
			entities as e
			left join memberships as m on e.id = m.entity_id
		where
			e.email=$email
		");

	// if user in db already, process their id and group membership info
	if ($usercheck->num_rows) {
		while($userrow = $usercheck->fetch_assoc()) {
			// set user id (probably overwriting whatever was in the return from google oauth)
			$user["id"] = $userrow["id"];

			// set user accesslevel
			$user["accesslevel"] = $userrow["accesslevel"];

			// add group id to list of memberships
			$user["memberships"][] = $userrow["group_id"];
		}
	}
	// else, add this new user to the db
	else {
		// add user to entities db
		// accesslevel will default to "10" ("0" = deactivated user; "10" = regular user; "100" = superuser)
		// type will default to "user"
		$name = $quote->$user["name"];
		$db->query("insert into entities (name, email) values ($name, $email)");

		// populate membership and other user information for use later in page
		$user["id"] = $db->insert_id;
		$user["memberships"][] = 1;
		$user["accesslevel"] = 10;

		// add user to "Everyone" group
		$entity_id_quoted = $quote->$user["id"];
		$db->query("insert into memberships (entity_id, group_id) values ($entity_id_quoted, '1')");
	}
}
// else, generate URL for logging in via google oauth
else {
	$authUrl = $client->createAuthUrl();
}
?>