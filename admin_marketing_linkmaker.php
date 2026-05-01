<?php
require "admin_functions.php";

// halt if they're not logged in
if (!isset($user)) {
	header("Location: https://l.sprucehealth.com/admin.php");
	exit();
}

// handle ajax POST request to generate a link, if POST data is present for such a request
if (isset($_POST["utm_handling"]) and isset($_POST["url"])
	and isset($_POST["utm_source"]) and isset($_POST["utm_medium"]) and isset($_POST["utm_campaign"])
	and isset($_POST["utm_term"]) and isset($_POST["utm_content"])) {

	// start by assuming that their URL is invalid
	$generatedlink = "Enter a valid URL and your link will appear here.";

	// start array of URL query parameters
	// note: do NOT need to use htmlentities() here to encode values, as that encoding will happen via http_build_query() later
	$queryparams = array();
	// if the user specified industry-handling for UTMs
	if ($_POST["utm_handling"] == 2) {
		if (strlen($_POST["utm_source"])) $queryparams["utm_source"] = $_POST["utm_source"];
		if (strlen($_POST["utm_medium"])) $queryparams["utm_medium"] = $_POST["utm_medium"];
		if (strlen($_POST["utm_campaign"])) $queryparams["utm_campaign"] = $_POST["utm_campaign"];
		if (strlen($_POST["utm_term"])) $queryparams["utm_term"] = $_POST["utm_term"];
		if (strlen($_POST["utm_content"])) $queryparams["utm_content"] = $_POST["utm_content"];
	}
	// otherwise, use Spruce-specific camouflaged handling for UTMs
	else {
		if (strlen($_POST["utm_source"])) $queryparams["uso"] = $_POST["utm_source"];
		if (strlen($_POST["utm_medium"])) $queryparams["ume"] = $_POST["utm_medium"];
		if (strlen($_POST["utm_campaign"])) $queryparams["uca"] = $_POST["utm_campaign"];
		if (strlen($_POST["utm_term"])) $queryparams["ute"] = $_POST["utm_term"];
		if (strlen($_POST["utm_content"])) $queryparams["uco"] = $_POST["utm_content"];
	}

	// build the final URL if the submitted URL is valid
	if (filter_var($_POST["url"], FILTER_VALIDATE_URL)) {
		// start with the submitted URL
		$generatedlink = $_POST["url"];

		// if query parameters are present, append them
		if (count($queryparams)) {
			$generatedlink = $generatedlink . "?" . http_build_query($queryparams);
		}
	}

	echo $generatedlink;

	// stop page processing (finish sending ajax response)
	exit();
}

// start page output
require "include_first.php";

?>
<h1>
	Marketing Linkmaker 3.0
</h1>

<p>
	The marketing linkmaker, version 3.0.
</p>

<div style="max-width: 800px;">
	<table style="margin: 1em 0; width: 100%">
		<tr>
			<th colspan="2" style="text-align: left;">
				UTM Parameter Handling
			</th>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="utm_handling" value="1" checked="checked">
					<span>&nbsp;</span>
				</label>
			</td>
			<td>
				<p>
					Use camouflaged UTMs (e.g., "uca")
				</p>
				<p>
					<span class="note">Note: Default. These UTMs are recommended for links that point to Spruce properties, as they are less likely to be removed by privacy plugins and filters. However, they are unlikely to work outside of Spruce.</span>
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="utm_handling" value="2">
					<span>&nbsp;</span>
				</label>
			</td>
			<td style="width: 100%;">
				<p>
					Use industry-standard UTMs (e.g., "utm_campaign")
				</p>
				<p>
					<span class="note">Note: Recommended for links that point outside of Spruce, but likely to be removed by privacy plugins and filters.</span>
				</p>
			</td>
		</tr>
	</table>

	<table style="margin: 1em 0;">
		<tr>
			<th colspan="2" style="text-align: left;">
				Destination URL
			</th>
		</tr>
		<tr>
			<td>
				URL
			</td>
			<td style="width: 100%;">
				<input type="text" name="url" style="width: 100%;" />
			</td>
		</tr>
	</table>

	<table style="margin: 1em 0;">
		<tr>
			<th colspan="2" style="text-align: left;">
				UTM Parameters
			</th>
		</tr>
		<tr>
			<td>
				utm_source
			</td>
			<td style="width: 100%;">
				<input type="text" name="utm_source" style="width: 100%;" />
			</td>
		</tr>
		<tr>
			<td>
				utm_medium
			</td>
			<td>
				<input type="text" name="utm_medium" style="width: 100%;" />
			</td>
		</tr>
		<tr>
			<td>
				utm_campaign
			</td>
			<td>
				<input type="text" name="utm_campaign" style="width: 100%;" />
			</td>
		</tr>
		<tr>
			<td>
				utm_term
			</td>
			<td>
				<input type="text" name="utm_term" style="width: 100%;" />
			</td>
		</tr>
		<tr>
			<td>
				utm_content
			</td>
			<td>
				<input type="text" name="utm_content" style="width: 100%;" />
			</td>
		</tr>
	</table>

	<!-- <input type="submit" value="Generate Link" id="submitbutton" /> -->

	<table style="width: 100%; margin: 1em 0;" name="generatedlink_container">
		<tr>
			<th style="text-align: left;">
				Generated Link
			</th>
		</tr>
		<tr>
			<td>
				<p id="generatedlink" style="word-break: break-all;">
					Enter a valid URL and your link will appear here.
				</p>
				<input type="submit" value="Copy Link" id="copybutton" />
			</td>
		</tr>
	</table>
</div>

<script>
	function processMarketingLink() {
		// // grab the URL value first to check it
	    // var urlValue = $( "[name='url']" ).val();

	    // // if URL is empty or just whitespace, hide the generated-link container and stop
	    // if (!urlValue || urlValue.trim() === "") {
	    //     $( "[name='generatedlink_container']" ).hide();
	    //     return; // this exits the function early so no $.post happens
	    // }

	    $.post(
	        // post data to the page to process in PHP because I don't feel like doing it in javascript
	        'admin_marketing_linkmaker.php',
	        // put data into an object to send in post action
	        {
	            utm_handling: $( "input[name='utm_handling']:checked" ).val(),
	            url: $( "[name='url']" ).val(),
	            utm_source: $( "[name='utm_source']" ).val(),
	            utm_medium: $( "[name='utm_medium']" ).val(),
	            utm_campaign: $( "[name='utm_campaign']" ).val(),
	            utm_term: $( "[name='utm_term']" ).val(),
	            utm_content: $( "[name='utm_content']" ).val()
	        },
	        // process the returned data (write the generated URL to the page)
	        function (data, status) {
	            // show the generated-link container
	            $( "[name='generatedlink_container']" ).show();
	            
	            // write the generated link to the page
	            $( "#generatedlink" ).text(data);
	        }
	    );
	}
	
	// regenerate link if any input changes
	$( ".niceradio, input[type='text']" ).change(processMarketingLink);

	// handle request to copy a link
	$( "#copybutton" ).click(function () {
		navigator.clipboard.writeText($( "#generatedlink" ).text());
	});

	// // handle explicit request to generate link
	// $( "#submitbutton" ).click(processMarketingLink);

	// // handle request to generate a link
	// $( "#submitbutton" ).click(function () {
	// 	$.post(
	// 		// post data to the page to process in PHP because I don't feel like doing it in javascript
	// 		'admin_marketing_linkmaker.php',
	// 		// put data into an object to send in post action
	// 		{
	// 			utm_handling: $( "input[name='utm_handling']:checked" ).val(),
	// 			url: $( "[name='url']" ).val(),
	// 			utm_source: $( "[name='utm_source']" ).val(),
	// 			utm_medium: $( "[name='utm_medium']" ).val(),
	// 			utm_campaign: $( "[name='utm_campaign']" ).val(),
	// 			utm_term: $( "[name='utm_term']" ).val(),
	// 			utm_content: $( "[name='utm_content']" ).val()
	// 		},
	// 		// process the returned data (write the generated URL to the page)
	// 		function (data, status) {
	// 			// show the generated-link container
	// 			$( "[name='generatedlink_container']" ).show();

	// 			// write the generated link to the page
	// 			$( "#generatedlink" ).text(data);
	// 		}
	// 	)
	// });

	// // clear generated link if any radio input changes
	// $( ".niceradio" ).change(function() {
	// 	$( "[name='generatedlink_container']" ).hide();
	// 	$( "#generatedlink" ).text('');
	// });

	// // clear generated link if any text input changes
	// $( "input[type='text']" ).change(function() {
	// 	$( "[name='generatedlink_container']" ).hide();
	// 	$( "#generatedlink" ).text('');
	// });
</script>
<?
// end page output
require "include_last.php";
?>
