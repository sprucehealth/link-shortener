<?php
require "admin_functions.php";

// halt if they're not logged in or if numeric link ID not specified
if (!isset($user) or empty($_GET["id"]) or !is_numeric($_GET["id"])) {
	header("Location: https://www.sprucehealth.com/");
	exit();
}

// just to be safe, prep link id for db use
$id = $quote->$_GET["id"];

// get link info
$link = $db->query("
	select
		l.id,
		l.path,
		l.target,
		l.notes,
		l.created,
		e.email as createdby,
		coalesce(nullif(o.email,''), o.name) as owner_name,
		l.active
	from
		links as l
		inner join entities as e on l.createdby = e.id
		inner join entities as o on l.owner = o.id
	where
		l.id = $id
	");

// get hits info
$hits = $db->query("
	select
		id,
		link_id,
		truepath,
		visited,
		user_agent,
		ip,
		hostname,
		city,
		region,
		country,
		loc,
		postal
	from
		hits
	where
		link_id = $id
	order by visited desc
	");

// start page output
require "include_first.php";

// output link info (if link found)
if ($link->num_rows) {
	while($linkrow = $link->fetch_assoc()) {
		?>
		<h1>
			Link Info
		</h1>
		<table>
			<tr>
				<th>Link ID</th>
				<td><? echo htmlentities($linkrow["id"]); ?></td>
			</tr>
			<tr>
				<th>Path</th>
				<td><? echo htmlentities($linkrow["path"]); ?></td>
			</tr>
			<tr>
				<th>Spruce Link</th>
				<td class="break"><a href="<? echo $linkrow["path"]; ?>">https://l.sprucehealth.com/<? echo htmlentities($linkrow["path"]); ?></a></td>
			</tr>
			<tr>
				<th>Target URL</th>
				<td class="break"><a href="<? echo $linkrow["target"]; ?>"><? echo htmlentities($linkrow["target"]); ?></a></td>
			</tr>
			<tr>
				<th>Notes</th>
				<td><? echo htmlentities($linkrow["notes"]); ?></td>
			</tr>
			<tr>
				<th>Created By</th>
				<td><? echo htmlentities($linkrow["createdby"]); ?></td>
			</tr>
			<tr>
				<th>Owner</th>
				<td><? echo htmlentities($linkrow["owner_name"]); ?></td>
			</tr>
			<tr>
				<th>Created</th>
				<td><? echo htmlentities($linkrow["created"]); ?></td>
			</tr>
			<tr>
				<th>Active</th>
				<td><? echo $linkrow["active"] ? "Yes" : "No"; ?></td>
			</tr>
		</table>
		<h1>
			Hits
		</h1>
		<?
		// output hit info (if there are hits)
		if ($hits->num_rows) {
			?>
			<p>
				<strong>Total hits: <? echo htmlentities($hits->num_rows); ?></strong>
			</p>
			<table>
				<tr>
					<th>Hit ID</th>
					<th>True Path</th>
					<th>Visited</th>
					<th>User Agent</th>
					<th>IP</th>
					<th>Hostname</th>
					<th>City</th>
					<th>Region</th>
					<th>Country</th>
					<th>Latitude and Longitude</th>
					<th>Postal Code</th>
				</tr>
				<?
				while($hitrow = $hits->fetch_assoc()) {
					?>
					<tr>
						<td><? echo htmlentities($hitrow["id"]); ?></td>
						<td><? echo htmlentities($hitrow["truepath"]); ?></td>
						<td><? echo htmlentities($hitrow["visited"]); ?></td>
						<td><? echo htmlentities($hitrow["user_agent"]); ?></td>
						<td><? echo htmlentities($hitrow["ip"]); ?></td>
						<td><? echo htmlentities($hitrow["hostname"]); ?></td>
						<td><? echo htmlentities($hitrow["city"]); ?></td>
						<td><? echo htmlentities($hitrow["region"]); ?></td>
						<td><? echo htmlentities($hitrow["country"]); ?></td>
						<td><? echo htmlentities($hitrow["loc"]); ?></td>
						<td><? echo htmlentities($hitrow["postal"]); ?></td>
					</tr>
					<?
				}
				?>
			</table>
			<?
		}
		// else, say no hits
		else {
			?>
			<p>
				This link doesn't have any hits yet.
			</p>
			<?
		}
	}
}
// else, say no link found
else {
	?>
	<h1>
		Link Not Found
	</h1>
	<p>
		Sorry, no link with ID "<? echo htmlentities($_GET["id"]) ?>" was found.
	</p>
	<?
}

// end page output
require "include_last.php";
?>






















