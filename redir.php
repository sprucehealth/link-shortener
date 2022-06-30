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
// note: updated 10/20/2021 to use parse_url() to retrieve path fragment, so that any present query parameters don't muck things up
if (isset($_SERVER['REQUEST_URI']) and strlen($_SERVER['REQUEST_URI']) < 513 and preg_match($re, substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1), $pathmatches) == 1) {
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
		// get redirect target URL
		$redirect_url = $row["target"];

		// assume incoming query string is empty
		$incoming_query_string = "";

		// if incoming query string is present, process it for use in redirection
		if (isset($_SERVER['QUERY_STRING']) and strlen($_SERVER['QUERY_STRING']) > 0) {
			// get raw incoming query string
			$incoming_query_string = $_SERVER['QUERY_STRING'];

			// if target URL already has a query string, deal with that
			// note: this is just a straight-up search for the "?" character, as parse_url() returns null for
			//       valid URLs that contain "?" with nothing after it, even though such URLs would act strangely
			//       if you were later to append another "?"
			if (strpos($redirect_url, "?") !== false) {
				// get component pieces of target URL
				$target_url_pieces = parse_url($redirect_url);

				// process target URL query string and incoming query string into arrays
				$target_url_query_array = proper_parse_str($target_url_pieces["query"]);
				$incoming_query_array = proper_parse_str($incoming_query_string);

				// step through incoming query items and add each one to the target URL query items
				foreach ($incoming_query_array as $param => $value_array) {
					// if the current incoming query parameter already exists in the target URL query items
					// note: isset() does not return true for array keys that correspond to a null value, while array_key_exists() does
					if (array_key_exists($param, $target_url_query_array)) {
						// rename incoming parameter before adding its values to the target URL query items
						$target_url_query_array["outside_".$param] = $value_array;
					}
					// else, just add the current incoming parameter's values directly to the target URL query items
					else {
						$target_url_query_array[$param] = $value_array;
					}
				}

				// build a final new query string from the reconciled incoming and target-URL query parameters
				$target_url_pieces["query"] = "";
				foreach ($target_url_query_array as $param => $value_array) {
					foreach ($value_array as $value) {
						// if this is not the first parameter, start with an "&" separator
						if (strlen($target_url_pieces["query"]) > 0) $target_url_pieces["query"] .= "&";

						// add the current parameter and its current value
						// note: these will still be URL-encoded from proper_parse_str()
						$target_url_pieces["query"] .= $param;
						$target_url_pieces["query"] .= "=";
						$target_url_pieces["query"] .= $value;
					}
				}

				// rebuild target URL with reconciled query parameters
				// note: will drop username, password, and port, if those were in the original target URL; can add if needed at some point
				$redirect_url =
					$target_url_pieces["scheme"]."://"
					.$target_url_pieces["host"]
					.$target_url_pieces["path"]
					."?".$target_url_pieces["query"]
					.(isset($target_url_pieces["fragment"]) ? "#" : "").$target_url_pieces["fragment"];
			}
			// otherwise, just append the incoming query string directly to the target URL
			else {
				$redirect_url = $redirect_url . "?" . $incoming_query_string;
			}
		}

		// record hit in db
		$link_id = $quote->$row["id"];
		$user_agent = $quote->$_SERVER["HTTP_USER_AGENT"];
		$ip = $quote->$_SERVER["REMOTE_ADDR"];
		$hostname = $quote->{gethostbyaddr($_SERVER["REMOTE_ADDR"])};
		$incoming_query_string = $quote->$incoming_query_string;

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
		$geoinfo = json_decode(@file_get_contents($geourl)); // "@" suppressess error if requests too many for the day already

		// prepare geo variables for database entry (if they exist)
		$city = isset($geoinfo->city) ? $quote->{$geoinfo->city} : "''";
		$region = isset($geoinfo->region) ? $quote->{$geoinfo->region} : "''";
		$country = isset($geoinfo->country) ? $quote->{$geoinfo->country} : "''";
		$loc = isset($geoinfo->loc) ? $quote->{$geoinfo->loc} : "''";
		$postal = isset($geoinfo->postal) ? $quote->{$geoinfo->postal} : "''";

		$db->query("insert into hits (link_id, truepath, incoming_query_string, user_agent, ip, hostname, city, region, country, loc, postal) values ($link_id, $truepath, $incoming_query_string, $user_agent, $ip, $hostname, $city, $region, $country, $loc, $postal)");
	}
}

// do forwarding
header("Location: $redirect_url");
exit();
?>
