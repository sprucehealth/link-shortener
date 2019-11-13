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
?>