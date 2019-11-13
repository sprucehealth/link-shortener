<?php
session_start();

// start google auth stuff
require_once "./google-api-php-client-2.2.2/vendor/autoload.php";
$client = new Google_Client();
$client->setAuthConfig("../../private/client_secret_1079714784527-ieo78jj5jdupur4p5smhuh9hrcsrj8r5.apps.googleusercontent.com.json");
$client->setAccessType("offline"); // used to create refresh token

/************************************************
 * If we have a code back from the OAuth 2.0 flow,
 * we need to exchange that with the
 * Google_Client::fetchAccessTokenWithAuthCode()
 * function. We store the resultant access token
 * bundle in the session, and redirect.
 ************************************************/
if (isset($_GET["code"])) {
	$token = $client->fetchAccessTokenWithAuthCode($_GET["code"]);

	// debug token contents (in case it's an error returned in json format or something else isn't working)
	// echo "<pre>".var_export($token, true)."</pre>";

	// store access token in client and session
	$client->setAccessToken($token);
	$_SESSION["token"] = $token;

	// store refresh token in client and session
	$refresh_token = $client->getRefreshToken();
	$_SESSION["refresh_token"] = $refresh_token;

	// redirect back to the main page
	header("Location: https://l.sprucehealth.com/admin.php");
	exit();
}
?>