<?php
require "admin_functions.php";

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
	");

// get group entities
$groups = $db->query("
	select
		id,
		name
	from
		entities
	where type = 'group'
	");

// start page output
require "include_first.php";
?>

<?
// if user not logged in, show login link only
if (!isset($user)) {
	?>
	<div class="request">
		<a class="login" href="<?= $authUrl ?>">Log in with Google</a>
	</div>
	<?
}
// otherwise, show the rest of the page
else {
	?>
	<p>
		Hi, <? echo $user["name"] ?>. You are logged in.
	</p>
	<hr>
	<?
	// if they're not deactivated, show the form to add a new link
	if ($user["accesslevel"] > 0) {
		?>
		<h1>
			Add a Link
		</h1>
		<form action="admin_link_add.php" method="post">
			<p>
				<strong>Spruce link:</strong> https://l.sprucehealth.com/
				<input type="text" name="path"><br />
				<span class="note">Note: Limited to uppercase, lowercase, numeric, hyphen, and underscore characters.</span>
			</p>
			<p>
				<strong>URL target:</strong>
				<input type="text" name="target" size="100">
			</p>
			<p>
				<strong>Notes:</strong>
				<input type="text" name="notes" size="100" placeholder="Optional...">
			</p>
			<p>
				<strong>Owner:</strong>
				<select name="owner">
					<option value="<? echo $user["id"] ?>" selected>Me (<? echo htmlentities($user["email"]) ?>)</option>
					<?
					while($group = $groups->fetch_assoc()) {
						?>
						<option value="<? echo $group["id"] ?>"><? echo $group["name"] ?></option>
						<?
					}
					?>
				</select><br />
				<span class="note">Note: Ownership determines who can modify a link once it's been created.</span>
			</p>
			<input type="submit" value="Add Link">
		</form>
		<hr>
		<h1>
			Bulk Add
		</h1>
		<form action="admin_link_add.php" method="post" enctype="multipart/form-data">
			<p>
				<strong>CSV file:</strong>
				<input type="file" name="csvfile" accept=".csv"><br />
				<span class="note">Note: First row will be ignored as column names.</span>
			</p>
			<p>
				<input type="submit" value="Add Links">
			</p>
		</form>
		<hr>
		<?
	}
	?>
	<h1>
		Links
	</h1>
	<p>
		<strong>Total links: <? echo $links->num_rows ?></strong>
	</p>
	<p>
		<a href="admin_link_list.php">View links</a>
	</p>
	<hr>
	<?
	// if the user is a superuser, show users and options for changing userlevel
	if ($user["accesslevel"] == 100) {
		// get users
		$users = $db->query("
			select
				id,
				name,
				email,
				accesslevel
			from
				entities
			where
				type = 'user'
			order by
				name asc
			");
		?>
		<h1>
			Users
		</h1>
		<table>
			<tr>
				<th>Name</th>
				<th>Email</th>
				<th>Access Level</th>
			</tr>
			<?
			while($row = $users->fetch_assoc()) {
				?>
				<tr>
					<td><? echo $row["name"] ?></td>
					<td><? echo $row["email"] ?></td>
					<td>
						<label class="niceradio">
							<input type="radio" name="user_<? echo $row["id"] ?>" value="0"<? if ($row["accesslevel"] == "0") echo " checked" ?><? if ($user["id"] == $row["id"]) echo " disabled" ?>>
							<span>Deactivated</span>
						</label>
						<label class="niceradio">
							<input type="radio" name="user_<? echo $row["id"] ?>" value="10"<? if ($row["accesslevel"] == "10") echo " checked" ?><? if ($user["id"] == $row["id"]) echo " disabled" ?>>
							<span>User</span>
						</label>
						<label class="niceradio">
							<input type="radio" name="user_<? echo $row["id"] ?>" value="100"<? if ($row["accesslevel"] == "100") echo " checked" ?><? if ($user["id"] == $row["id"]) echo " disabled" ?>>
							<span>Superuser</span>
						</label>
					</td>
				</tr>
				<?
			}
			?>
		</table>
		<hr>
		<?
	}
	?>
	<p>
		<a href="admin.php?logout">Log out</a>
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

		$( ".niceradio input" )
		.change(function () {
			var user_id = $( this ).attr('name').substr(5);
			var accesslevel = $( this ).val();

			$.ajax({
				url: 'admin_user_modify_accesslevel.php',
				type: 'get',
				data: "user_id=" + user_id + "&accesslevel=" + accesslevel,
				success: function(result) {
      				console.log(result);
      			},
				processData: false
			})
		});
	</script>
	<?
}

// end page output
require "include_last.php";
?>
