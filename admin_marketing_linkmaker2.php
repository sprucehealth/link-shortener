<?php
require "admin_functions.php";

// halt if they're not logged in
if (!isset($user)) {
	header("Location: https://l.sprucehealth.com/admin.php");
	exit();
}

// handle ajax POST request to generate a link, if POST data present for such a request
if (isset($_POST["user_ios"]) and isset($_POST["user_android"]) and isset($_POST["user_desktop"])
	and isset($_POST["customurl_ios"]) and isset($_POST["customurl_android"]) and isset($_POST["customurl_desktop"])
	and isset($_POST["utm_source"]) and isset($_POST["utm_medium"]) and isset($_POST["utm_campaign"])
	and isset($_POST["utm_term"]) and isset($_POST["utm_content"])) {

	// start with base branch-link default
	$generatedlink = "https://b.sprucehealth.com/a/key_live_efa4orVqdwidlgkisKcXZkghqrcYN8Mw";

	// start assuming that there will be no failure to report
	$failflag = false;

	// start array of URL query parameters
	$queryparams = array();
	if (strlen($_POST["utm_source"])) $queryparams["utm_source"] = htmlentities($_POST["utm_source"]);
	if (strlen($_POST["utm_medium"])) $queryparams["utm_medium"] = htmlentities($_POST["utm_medium"]);
	if (strlen($_POST["utm_campaign"])) $queryparams["utm_campaign"] = htmlentities($_POST["utm_campaign"]);
	if (strlen($_POST["utm_term"])) $queryparams["utm_term"] = htmlentities($_POST["utm_term"]);
	if (strlen($_POST["utm_content"])) $queryparams["utm_content"] = htmlentities($_POST["utm_content"]);

	// /////////////////////////////////////
	// calculate iOS query param
	// /////////////////////////////////////
	// if user_ios == 1, then do not set the ios_url parameter (i.e., use the Branch default behavior of sending the user to the app store)
	// if user_ios == 2, send to mobile web app
	if ($_POST["user_ios"] == 2) $queryparams["\$ios_url"] = htmlentities("https://app.sprucehealth.com/");
	// if user_ios == 3, send to marketing website
	if ($_POST["user_ios"] == 3) $queryparams["\$ios_url"] = htmlentities("https://www.sprucehealth.com/");
	// if user_ios == 4, send to custom url
	if ($_POST["user_ios"] == 4) {
		// validate custom ios url
		if (filter_var($_POST["customurl_ios"], FILTER_VALIDATE_URL)) {
			$queryparams["\$ios_url"] = htmlentities($_POST["customurl_ios"]);
		}
		else $failflag = "Your custom iOS URL is invalid.";
	}

	// /////////////////////////////////////
	// calculate Android query param
	// /////////////////////////////////////
	// if user_android == 1, then do not set the android_url parameter (i.e., use the Branch default behavior of sending the user to the play store)
	// if user_android == 2, then do not set the android_url parameter, as this should never be the case, because android users can't use mobile web
	// if user_android == 3, send to marketing website
	if ($_POST["user_android"] == 3) $queryparams["\$android_url"] = htmlentities("https://www.sprucehealth.com/");
	// if user_android == 4, send to custom url
	if ($_POST["user_android"] == 4) {
		// validate custom android url
		if (filter_var($_POST["customurl_android"], FILTER_VALIDATE_URL)) {
			$queryparams["\$android_url"] = htmlentities($_POST["customurl_android"]);
		}
		else $failflag = "Your custom Android URL is invalid.";
	}

	// /////////////////////////////////////
	// calculate desktop (web) query param
	// /////////////////////////////////////
	// if user_desktop == 1, send to web app
	if ($_POST["user_desktop"] == 1) $queryparams["\$desktop_url"] = htmlentities("https://app.sprucehealth.com/");
	// if user_desktop == 2, then do not set the desktop_url parameter (i.e., use the Branch default behavior of sending the user to the marketing website)
	// if ($_POST["user_desktop"] == 2) $queryparams["\$desktop_url"] = htmlentities("https://www.sprucehealth.com/");
	// if user_desktop == 3, send to custom url
	if ($_POST["user_desktop"] == 3) {
		// validate custom desktop url
		if (filter_var($_POST["customurl_desktop"], FILTER_VALIDATE_URL)) {
			$queryparams["\$desktop_url"] = htmlentities($_POST["customurl_desktop"]);
		}
		else $failflag = "Your custom desktop (web) URL is invalid.";
	}

	// generate final link
	// first check for custom URL failure
	if ($failflag) {
		$generatedlink = $failflag;
	}
	// otherwise, finish generating the link
	else {
		// if query parameters are present, append them
		if (count($queryparams)) $generatedlink = $generatedlink . "?" . http_build_query($queryparams);
	}

	echo $generatedlink;

	// stop page processing (finish sending ajax response)
	exit();
}

// start page output
require "include_first.php";

?>
<h1>
	Marketing Linkmaker 2.0
</h1>

<p>
	The marketing linkmaker, version 2.0.
</p>

<div style="max-width: 800px;">
	<table style="margin: 1em 0; width: 100%">
		<tr>
			<th colspan="2" style="text-align: left;">
				If user is on iOS...
			</th>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_ios" value="1">
					<span>&nbsp;</span>
				</label>
			</td>
			<td style="width: 100%;">
				<p>
					Send to App Store or open app if already installed<br />
					<span class="note">Note: Recommended for patients, or providers who already have an account</span>
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_ios" value="2" checked="checked">
					<span>&nbsp;</span>
				</label>
			</td>
			<td>
				<p>
					Send to mobile web app<br />
					<span class="note">Note: Recommended if the audience is potential new orgs that haven't yet signed up</span>
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_ios" value="3">
					<span>&nbsp;</span>
				</label>
			</td>
			<td>
				<p>
					Send to the marketing website
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_ios" value="4">
					<span>&nbsp;</span>
				</label>
			</td>
			<td>
				<p>
					Send to a custom URL:
					<input type="text" name="customurl_ios" style="width: 100%;" /><br />
					<span class="note">Note: Any UTM parameters are likely to be lost by the time of account creation if you send users to a custom URL that isn't part of the marketing website</span>
				</p>
			</td>
		</tr>
	</table>

	<table style="margin: 1em 0; width: 100%">
		<tr>
			<th colspan="2" style="text-align: left;">
				If user is on Android...
			</th>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_android" value="1" checked="checked">
					<span>&nbsp;</span>
				</label>
			</td>
			<td style="width: 100%;">
				<p>
					Send to Google Play Store or open app if already installed
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_android" value="2" disabled="disabled">
					<span>&nbsp;</span>
				</label>
			</td>
			<td>
				<p>
					<s>Send to mobile web app</s><br />
					<span class="note">Note: This is disabled, as we don't allow Android users to use the mobile web app at all</span>
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_android" value="3">
					<span>&nbsp;</span>
				</label>
			</td>
			<td>
				<p>
					Send to the marketing website
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_android" value="4">
					<span>&nbsp;</span>
				</label>
			</td>
			<td>
				<p>
					Send to a custom URL:<br/>
					<input type="text" name="customurl_android" style="width: 100%;" /><br />
					<span class="note">Note: Any UTM parameters are likely to be lost by the time of account creation if you send users to a custom URL that isn't part of the marketing website</span>
				</p>
			</td>
		</tr>
	</table>

	<table style="margin: 1em 0; width: 100%">
		<tr>
			<th colspan="2" style="text-align: left;">
				If user is on desktop (web)...
			</th>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_desktop" value="1" checked="checked">
					<span>&nbsp;</span>
				</label>
			</td>
			<td style="width: 100%;">
				<p>
					Send to the web app
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_desktop" value="2">
					<span>&nbsp;</span>
				</label>
			</td>
			<td>
				<p>
					Send to the marketing website
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<label class="niceradio">
					<input type="radio" name="user_desktop" value="3">
					<span>&nbsp;</span>
				</label>
			</td>
			<td>
				<p>
					Send to a custom URL:<br />
					<input type="text" name="customurl_desktop" style="width: 100%;" /><br />
					<span class="note">Note: Any UTM parameters are likely to be lost by the time of account creation if you send users to a custom URL that isn't part of the marketing website.</span>
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
	// handle request to generate a link
	$( "#submitbutton" ).click(function () {
		$.post(
			// post data to the page to process in PHP because I don't feel like doing it in javascript
			'admin_marketing_linkmaker2.php',
			// put data into an object to send in post action
			{
				user_ios: $( "input[name='user_ios']:checked" ).val(),
				user_android: $( "input[name='user_android']:checked" ).val(),
				user_desktop: $( "input[name='user_desktop']:checked" ).val(),
				customurl_ios: $( "[name='customurl_ios']" ).val(),
				customurl_android: $( "[name='customurl_android']" ).val(),
				customurl_desktop: $( "[name='customurl_desktop']" ).val(),
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
