<?php

// /////////////////////////////////////////////////////////////// //
// --------------------------------------------------------------- //
// use this block on its own to test geoip2.phar usage
// --------------------------------------------------------------- //

// require "geoip2.phar";

// use GeoIp2\Database\Reader;

// // This creates the Reader object, which should be reused across
// // lookups.
// $reader = new Reader('/home/spruce/www/l/GeoLite2-City.mmdb');

// // Replace "city" with the appropriate method for your database, e.g.,
// // "country".
// $record = $reader->city('128.101.101.101');

// print($record->country->isoCode . "\n"); // 'US'
// print($record->country->name . "\n"); // 'United States'
// print($record->country->names['zh-CN'] . "\n"); // '美国'

// print($record->mostSpecificSubdivision->name . "\n"); // 'Minnesota'
// print($record->mostSpecificSubdivision->isoCode . "\n"); // 'MN'

// print($record->city->name . "\n"); // 'Minneapolis'

// print($record->postal->code . "\n"); // '55455'

// print($record->location->latitude . "\n"); // 44.9733
// print($record->location->longitude . "\n"); // -93.2323
// /////////////////////////////////////////////////////////////// //


// /////////////////////////////////////////////////////////////// //
// --------------------------------------------------------------- //
// use this block on its own to test attribution calls
// --------------------------------------------------------------- //

// establish db connection and other page-start things
require "functions.php";

// set up various variables to send to attribution logging system
// also check for did cookie (has the backend seen this device before)
$requestUrl   = 'https://l.sprucehealth.com' . $_SERVER['REQUEST_URI'];
$cookieHeader = $_SERVER['HTTP_COOKIE'] ?? '';
$hasDid       = isset($_COOKIE['did']) && $_COOKIE['did'] !== '';

// send attribution data to attribution logging system
spruceReportAttribution($requestUrl, $cookieHeader);

if ($hasDid) {
	echo "did was present";
}
else echo "did was not present";



        $parsed = parse_url($requestUrl);
        if (!is_array($parsed) || empty($parsed['host'])) {
            error_log('spruceReportAttribution: unparseable requestUrl');
            return;
        }
        $hostname  = $parsed['host'];
        $pathname  = $parsed['path'] ?? '/';
        $visitorIp = $_SERVER['REMOTE_ADDR'] ?? '';

        echo "<p>".$hostname."<p>".$pathname."<p>".$visitorIp;

// /////////////////////////////////////////////////////////////// //






// $data = array(
//     'foo' => 'bar',
//     'baz' => 'boom',
//     'cow' => 'milk',
//     'null' => null,
//     'php' => 'hypertext processor'
// );

// $data = array();

// echo "[".http_build_query($data)."]";





/*
require "functions.php";

// header('Content-Type: text/html; charset=utf-8');
		$geourl = "https://ipinfo.io/125.235.233.139/json";
		$geoinfo = json_decode(file_get_contents($geourl));

		// prepare geo variables for database entry (if they exist)
		$city = isset($geoinfo->city) ? $quote->{$geoinfo->city} : "''";
		$region = isset($geoinfo->region) ? $quote->{$geoinfo->region} : "''";

$db->query("insert into hits (city, region) values ($city, $region)");

echo "city: $city <p />region: $region";
*/

?>
