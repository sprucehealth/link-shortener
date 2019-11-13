<?php
require "admin_functions.php";

// halt if they're not logged in
if (!isset($user)) {
	header("Location: https://www.sprucehealth.com/");
	exit();
}

// set up variables for link listing parameters
$search_terms = "Not yet implemented";
$links_per_page = 100;
$start_offset = 0;

// if links per page specified, use it
if (isset($_GET["links_per_page"]) and is_int((int)$_GET["links_per_page"]) and $_GET["links_per_page"] > 0) {
	$links_per_page = (int)$_GET["links_per_page"];
}

// if start offset specified, use it
if (isset($_GET["start_offset"]) and is_int((int)$_GET["start_offset"]) and $_GET["start_offset"] > 0) {
	$start_offset = (int)$_GET["start_offset"];
}

// get count of matching links
$number_of_links = $db->query("select id from links")->num_rows;

// get current links
$links = $db->query("
	select
		l.id,
		l.path,
		l.target,
		l.notes,
		e.email as createdby,
		l.owner,
		coalesce(nullif(o.email,''), o.name) as owner_name,
		l.active,
		(select count(*) from hits where link_id = l.id) as hits,
        (select count(*) from links as dupes where dupes.path = l.path and dupes.id != l.id and dupes.active is true) as active_duplicate
	from
		links as l
		inner join entities as e on l.createdby = e.id
		inner join entities as o on l.owner = o.id
	order by l.path asc, l.created desc
	limit $links_per_page offset $start_offset
	");

// calculate how many pages of links this search yields and what page we're on currently
$pages = ceil($number_of_links / $links_per_page);
$current_page = floor($start_offset / $links_per_page) + 1;

// start page output
require "include_first.php";

?>
<h1>
	Links
</h1>
<p>
	Page:
		<?
		for ($i=1; $i <= $pages; $i++) {
			// calculate which offset linked page should use
			$link_offset = ($i - 1) * $links_per_page;

			// output page number
			if ($i == $current_page) echo "<strong>$i</strong> ";
			else echo "<a href=\"?links_per_page=$links_per_page&start_offset=$link_offset\">$i</a> ";
		}
		?><br />
	<span class="note">Note: Click any linked "hits" count for full link and hit information.</span>
</p>
<?
// if there are links, display them
if ($links->num_rows) {
	?>
	<table>
		<tr>
			<th>Spruce Link</th>
			<th>Target URL</th>
			<th>Hits</th>
			<th>Notes</th>
			<th>Created By</th>
			<th>Owner</th>
			<th>Active</th>
		</tr>
		<?
		while($row = $links->fetch_assoc()) {
			// make a display version of the URL target
			// if the target is really long, make a shorter ellipsized version
			if (strlen($row["target"]) > 255) {
				$target_display = htmlentities(substr($row["target"], 0, 255)) . "...";
			}
			else {
				$target_display = htmlentities($row["target"]);
			}
			?>
			<tr>
				<td><a href="<? echo $row["path"]; ?>"><? echo htmlentities($row["path"]); ?></a></td>
				<td class="break"><a href="<? echo $row["target"]; ?>"><? echo $target_display; ?></a></td>
				<td><a href="admin_link_info.php?id=<? echo $row["id"]; ?>"><? echo $row["hits"]; ?></a></td>
				<td style="width: 10%"><? echo htmlentities($row["notes"]); ?></td>
				<td><? echo htmlentities($row["createdby"]); ?></td>
				<td><? echo htmlentities($row["owner_name"]); ?></td>
				<td>
					<?
					// if user owns this link (or belongs to group that does) and they are not deactivated then show modifiable activity slider
					// also show slider if user is a superuser
					if (($user["accesslevel"] == 10 and ($row["owner"] == $user["id"] or in_array($row["owner"], $user["memberships"])))
						or $user["accesslevel"] == 100) {

						?>
						<label class="switch">
							<input type="checkbox" name="<? echo "active_".$row["id"] ?>"<? if ($row["active"]) echo " checked" ?><? if ($row["active_duplicate"] > 0) echo " disabled" ?>>
							<span class="slider round"></span>
						</label>
						<?
					}
					// otherwise, just show plain text of whether link is active or not
					else {
						echo $row["active"] ? "Yes" : "No";
					}
					?>
				</td>
			</tr>
			<?
		}
		?>
	</table>
	<?
}
// otherwise, say there aren't any links
else {
	?>
	<p>
		No matching links.
	</p>
	<?
}
?>
<p>
	Page:
		<?
		for ($i=1; $i <= $pages; $i++) {
			// calculate which offset linked page should use
			$link_offset = ($i - 1) * $links_per_page;

			// output page number
			if ($i == $current_page) echo "<strong>$i</strong> ";
			else echo "<a href=\"?links_per_page=$links_per_page&start_offset=$link_offset\">$i</a> ";
		}
		?>
</p>
<p>
	Links per page: <? echo $links_per_page ?>
</p>

<script>
	$( "input[type=checkbox]" )
	.change(function () {
		var link_id = $( this ).attr('name').substr(7);

		$.ajax({
			url: 'admin_link_modify_activity.php',
			type: 'get',
			data: "link_id=" + link_id + "&active=" + $( this ).prop('checked'),
			processData: false
		})
	});
</script>
<?
// end page output
require "include_last.php";
?>