<?php

// establish db connection and other page-start things
require "functions.php";

// to start with, assume no path specified and set default forwarding url to main spruce page
$path = "";
$truepath = "";
$pathmatches = [];
$redirect_url = "https://www.sprucehealth.com/";

// define the regex for allowable paths
// this regex specifies one or more allowable path characters, followed optionally by a plus sign and one or more allowable characters
// example matches: "path", "path+unique_modifier", "other-p4th+other-un1que-modifier"
$re = "/^[a-zA-Z0-9_-]+(\+[a-zA-Z0-9_-]+)*$/";

// check for presence and length of path, and use it if it exists
// the preg_match function will store the full match (e.g., "path+modifier") at index 0 and the substring match (e.g., "+modifier") at index 1
// note: raw path from server variable will include starting "/" (e.g., "/path"), so need to cut that off before evaluating
// note: also makes sure full path (minus leading slash) is 511 or fewer characters, which is db field size limit for recording true path
if (isset($_SERVER['REQUEST_URI']) and strlen($_SERVER['REQUEST_URI']) < 513 and preg_match($re, substr($_SERVER['REQUEST_URI'], 1), $pathmatches) == 1) {
	// record true path
	$truepath = $pathmatches[0];

	// start by assuming that no path modifier (e.g., "+modifier") is present, so path and true path are the same
	$path = $truepath;

	// if a path modifier is present, record base path separately
	if (count($pathmatches) == 2) {
		$path = substr($truepath, 0, strlen($truepath) - strlen($pathmatches[1]));
	}

	// prepare variables for database use
	$path = $quote->$path;
	$truepath = $quote->$truepath;

	// find first active forwarding URL associated with path (there should only be one)
	$findurl = $db->query("select id, target from links where path=$path and active is true limit 1");

	// if URL found, use it
	while($row = $findurl->fetch_assoc()) {
		$redirect_url = $row["target"];

		// record hit in db
		$link_id = $quote->$row["id"];
		$user_agent = $quote->$_SERVER["HTTP_USER_AGENT"];
		$ip = $quote->$_SERVER["REMOTE_ADDR"];
		$hostname = $quote->{gethostbyaddr($_SERVER["REMOTE_ADDR"])};

		// get geo location information
		// note: this could also be done with geolite2 local db (see test.php for example) if requests go over 1000/day and ipinfo.io stops working
		// ipinfo.io returns result like:
		// "ip": "136.24.176.171",
		// "hostname": "171.176.24.136.in-addr.arpa",
		// "city": "San Francisco",
		// "region": "California",
		// "country": "US",
		// "loc": "37.7771,-122.4060",
		// "postal": "94103",
		// "org": "AS19165 Webpass Inc."
		$geourl = "https://ipinfo.io/".$_SERVER["REMOTE_ADDR"]."/json";
		$geoinfo = json_decode(file_get_contents($geourl));

		// prepare geo variables for database entry (if they exist)
		$city = isset($geoinfo->city) ? $quote->{$geoinfo->city} : "''";
		$region = isset($geoinfo->region) ? $quote->{$geoinfo->region} : "''";
		$country = isset($geoinfo->country) ? $quote->{$geoinfo->country} : "''";
		$loc = isset($geoinfo->loc) ? $quote->{$geoinfo->loc} : "''";
		$postal = isset($geoinfo->postal) ? $quote->{$geoinfo->postal} : "''";

		$db->query("insert into hits (link_id, truepath, user_agent, ip, hostname, city, region, country, loc, postal) values ($link_id, $truepath, $user_agent, $ip, $hostname, $city, $region, $country, $loc, $postal)");
	}
}

// do forwarding
header("Location: $redirect_url");
exit();
?>