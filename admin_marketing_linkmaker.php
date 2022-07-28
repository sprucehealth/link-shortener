<?php
require "admin_functions.php";

// halt if they're not logged in
if (!isset($user)) {
	header("Location: https://www.sprucehealth.com/");
	exit();
}

// handle ajax POST request to generate a link, if POST data present for such a request
if (isset($_POST["behavior_mobile"]) and isset($_POST["behavior_desktop"]) and isset($_POST["customurl"])
	and isset($_POST["utm_source"]) and isset($_POST["utm_medium"]) and isset($_POST["utm_campaign"])
	and isset($_POST["utm_term"]) and isset($_POST["utm_content"])) {

	// start with blank generated link
	$generatedlink = "";

	// record base branch-link default
	$basebranchlink = "https://bnc.spruce.app/a/key_live_efa4orVqdwidlgkisKcXZkghqrcYN8Mw";

	// start array of URL query parameters
	$queryparams = array();
	if (strlen($_POST["utm_source"])) $queryparams["utm_source"] = $_POST["utm_source"];
	if (strlen($_POST["utm_medium"])) $queryparams["utm_medium"] = $_POST["utm_medium"];
	if (strlen($_POST["utm_campaign"])) $queryparams["utm_campaign"] = $_POST["utm_campaign"];
	if (strlen($_POST["utm_term"])) $queryparams["utm_term"] = $_POST["utm_term"];
	if (strlen($_POST["utm_content"])) $queryparams["utm_content"] = $_POST["utm_content"];

	// if mobile behavior is "send to app store"
	if ($_POST["behavior_mobile"] == "true") {
		// if desktop behavior is "send to web app"
		if ($_POST["behavior_desktop"] == "true") {
			// use branch link with no desktop_url appended

			// start with base branch link
			$generatedlink = $basebranchlink;

			// if query parameters present, append them
			if (count($queryparams)) $generatedlink = $generatedlink . "?" . http_build_query($queryparams);
		}
		// if desktop behavior is "send to custom url"
		else {
			// use branch link with desktop_url appended

			// start with base branch link
			$generatedlink = $basebranchlink;

			// add $desktop_url query parameter if customurl is present and is a valid URL
			if (filter_var($_POST["customurl"], FILTER_VALIDATE_URL)) $queryparams["\$desktop_url"] = $_POST["customurl"];

			// if query parameters present, append them
			if (count($queryparams)) $generatedlink = $generatedlink . "?" . http_build_query($queryparams);
		}
	}
	// if mobile behavior is "send to custom url"
	else {
		// generate fully custom url
		
		// if customurl is present and is a valid URL, use it
		if (filter_var($_POST["customurl"], FILTER_VALIDATE_URL)) {
			$generatedlink = $_POST["customurl"];

			// if query parameters present, append them
			// note: right now, this will screw up if the custom URL already has a query string
			if (count($queryparams)) $generatedlink = $generatedlink . "?" . http_build_query($queryparams);
		}
		// else, report an error
		else {
			$generatedlink = "Your custom URL is invalid.";
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
	Marketing Linkmaker
</h1>

<p>
	The marketing linkmaker.
</p>

<div style="max-width: 800px;">
	<table style="margin: 1em 0;">
		<tr>
			<th colspan="2" style="text-align: left;">
				Mobile Behavior
			</th>
		</tr>
		<tr>
			<td>
				<label class="switch">
					<input type="checkbox" name="behavior_mobile" checked>
					<span class="slider round"></span>
				</label>
			</td>
			<td>
				<p style="font-weight: bold;">
					Send to App Store
				</p>
				<p>
					<span style="font-weight: bold;">Enabled:</span> Link visitors on mobile platforms will be sent to the mobile app store. Any UTM parameters that are present will stay associated with them if they make a Spruce account.
				</p>
				<p>
					<span style="font-weight: bold;">Disabled:</span> Link visitors on mobile platforms will be sent to the custom URL specified below. Any UTM parameters will NOT stay associated with them if they make a Spruce account.
				</p>
				<p>
					<span class="note">Note: If this is disabled, desktop visitors must also be sent to the custom URL.</span>
				</p>
			</td>
		</tr>
	</table>

	<table style="margin: 1em 0;">
		<tr>
			<th colspan="2" style="text-align: left;">
				Desktop Behavior
			</th>
		</tr>
		<tr>
			<td>
				<label class="switch">
					<input type="checkbox" name="behavior_desktop" checked>
					<span class="slider round"></span>
				</label>
			</td>
			<td>
				<p style="font-weight: bold;">
					Send to Web App
				</p>
				<p>
					<span style="font-weight: bold;">Enabled:</span> Link visitors on desktop platforms will be sent to the web app entrance page (https://app.sprucehealth.com/). Any UTM parameters that are present will stay associated with them if they make a Spruce account.
				</p>
				<p>
					<span style="font-weight: bold;">Disabled:</span> Link visitors on desktop platforms will be sent to the custom URL specified below. If this URL is within the Spruce marketing site (https://www.sprucehealth.com/), any UTM parameters that are present will stay associated with them if they make a Spruce account. Otherwise, any UTM parameters are likely to be lost by the time of account creation.
				</p>
			</td>
		</tr>
	</table>

	<table style="width: 100%; margin: 1em 0; display: none;" name="customurl_container">
		<tr>
			<th style="text-align: left;">
				Custom URL
			</th>
		</tr>
		<tr>
			<td>
				<input type="text" name="customurl" style="width: 100%;" />
				<p>
					<span class="note">Note: If both sliders above are off, do not include a query string in your URL (e.g., anything with a "?" character)</span>
				</p>
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

	<input type="submit" value="Generate Link" id="submitbutton" />

	<table style="width: 100%; margin: 1em 0; display: none;" name="generatedlink_container">
		<tr>
			<th style="text-align: left;">
				Generated Link
			</th>
		</tr>
		<tr>
			<td>
				<p id="generatedlink">
				</p>
				<input type="submit" value="Copy Link" id="copybutton" />
			</td>
		</tr>
	</table>
</div>

<script>
	// handle various slider changes
	$( "input[type=checkbox]" )
	.change(function () {
		// if the mobile-behavior slider has become unchecked
		if ($( this ).attr('name') == 'behavior_mobile' && !$( this ).prop('checked')) {
			// also uncheck the desktop-behavior slider
			$( "input[name='behavior_desktop']" ).prop('checked', false);

			// disable the desktop-behavior slider (make it read-only)
			$( "input[name='behavior_desktop']" ).prop('disabled', true);

			// show the custom-URL container
			$( "[name='customurl_container']" ).show();
		}

		// if the mobile-behavior slider has become checked
		if ($( this ).attr('name') == 'behavior_mobile' && $( this ).prop('checked')) {
			// enable the desktop-behavior slider
			$( "input[name='behavior_desktop']" ).prop('disabled', false);
		}

		// if the desktop-behavior slider has become unchecked
		if ($( this ).attr('name') == 'behavior_desktop' && !$( this ).prop('checked')) {
			// show the custom-URL container
			$( "[name='customurl_container']" ).show();
		}

		// if the desktop-behavior slider has become checked
		if ($( this ).attr('name') == 'behavior_desktop' && $( this ).prop('checked')) {
			// hide the custom-URL container
			$( "[name='customurl_container']" ).hide();
		}
	});

	// handle request to generate a link
	$( "#submitbutton" ).click(function () {
		$.post(
			// post data to the page to process in PHP because I don't feel like doing it in javascript
			'admin_marketing_linkmaker.php',
			// put data into an object to send in post action
			{
				behavior_mobile: $( "input[name='behavior_mobile']" ).prop('checked'),
				behavior_desktop: $( "input[name='behavior_desktop']" ).prop('checked'),
				customurl: $( "[name='customurl']" ).val(),
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
		)
	});

	// handle request to copy a link
	$( "#copybutton" ).click(function () {
		navigator.clipboard.writeText($( "#generatedlink" ).text());
	});
</script>
<?
// end page output
require "include_last.php";
?>