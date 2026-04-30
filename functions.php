<?php
// open database connection
// this link will be used for the rest of the page. this is implemented via a function that can be called to establish the db as needed
function get_my_db() {
	static $db;

	if (!$db) {
		require "../../private/dblogin.inc";
	}

	return $db;
}
$db = get_my_db();

// set database connection character set to UTF-8
$db->set_charset("utf8");

// use this helper class to prep a string for db insertion with quotes and escaping
// in query, instead of saying "field=$var", say "field=$quote->$var"
class quotefordb {
	function __get($value)
	{
		$db = get_my_db();
		return "'".$db->real_escape_string($value)."'";
	}
}

// create a quote helper var for use later on page that includes this document
$quote = new quotefordb;

// create a variable to hold debug information to be output later
$debugvar = "";

// function that accepts a URL query string (e.g., "?foo=bar&cat=dog")
// and returns an array of its various parameters
// note: this function is useful because the native php $_GET array and parse_str() function both handle potential repeat parameters suboptimally (by overwriting earlier values)
function proper_parse_str($str) {
	# result array
	$arr = array();

	# split on outer delimiter
	$pairs = explode('&', $str);

	# loop through each pair
	foreach ($pairs as $i) {
		# split into name and value
		# note: these will still be URL-encoded
		list($name, $value) = explode('=', $i, 2);

		# store the value in an array, using name as the key
		if (strlen($name) > 0) {
			$arr[$name][] = $value;
		}

		/* old way of doing the value storage (single values stored as scalars instead of everything being stored as arrays)
		# if name already exists
		if (isset($arr[$name])) {
			# stick multiple values into an array
			# if array already exists for this parameter, simply append the new value
			if (is_array($arr[$name])) {
				$arr[$name][] = $value;
			}
			# else, create an array for this parameter and replace the existing scalar entry with it
			else {
				$arr[$name] = array($arr[$name], $value);
			}
		}
		# otherwise, simply stick it in a scalar
		else {
			$arr[$name] = $value;
		}
		*/
	}

	# return result array
 	return $arr;
}
?>
