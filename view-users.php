<?php
// Import components
include "components/pwd.php";
include "components/connection.php";
include "components/init-actions.php";

$title = "View Users";
$description = "This page allows the admin to see all users on the site.";

include "components/header.php";

//include google api files
require_once 'google-sign-in-2.2.3/vendor/autoload.php';

// Confirm authentication
if (isset($_SESSION['access_token'])) {
	$access_token = $_SESSION['access_token'];
	$client = new Google_Client(['client_id' => $google_client_id]);  // Specify the CLIENT_ID of the app that accesses the backend
	$payload = $client->verifyIdToken($access_token);

	if ($payload) {
		$user_query = "SELECT DISTINCT users.google_id, users.role FROM users WHERE google_id = '" . $mysqli->real_escape_string($payload['sub']) . "' AND role = 'Admin'";
		if ($user_result = $mysqli->query($user_query)) {
			if ($user_result->num_rows > 0) {
				// take no action if user is logged in
			} else {
				header("Location: login.php");
				exit();
			}
		}
	} else {
		header("Location: login.php");
		exit();
	}
} else {
	header("Location: login.php");
	exit();
}

?>
<div class="site-inner">
	
	<p><strong><em>View all users below to add, edit, or delete.</em></strong></p>
	<div class="resource-intro">
		<p>Put more info here on the process, if needed.</p>
	</div>
	<div>
		<a class="transparent-button-black" href="/~designhub/software_db/view.php"><i class="fas fa-angle-left"></i> Back to Software List</a>
	</div>


	<?php

	if(isset($_POST["Delete"])) {
		$delete_user_query = "DELETE from ".$db_prefix."Users where id = " . $mysqli->real_escape_string($_POST["user_id"]);

		if ($mysqli->query($delete_user_query)) {
			echo "<div class='update-alert'>User deleted.</div>";
		} else {
			echo "<div class='update-alert alert-error'>There was an error deleting the user.</div>";
		}
	}

	if(isset($_POST["Add_User"])) {
		$add_user_query = "INSERT IGNORE INTO ".$db_prefix."Users(name, email, role) 
			values(
				'".$mysqli->real_escape_string($_POST["user_fullname"])."', 
				'".$mysqli->real_escape_string($_POST["user_email"])."', 
				'".$mysqli->real_escape_string($_POST["user_role"])."')";

		if($mysqli->query($add_user_query)) {
			echo "<div class='update-alert'>User added.</div>";
		} else {
			echo "<div class='update-alert alert-error'>User could not be added.</div>";
		}
	}

	$query = "SELECT DISTINCT 
			".$db_prefix."Users.id,
			".$db_prefix."Users.name,
			".$db_prefix."Users.email, 
			".$db_prefix."Users.role 
		FROM ".$db_prefix."Users";
	?>

	<table class="records-table">
		<thead>
			<tr>
				<th>Name</th>
				<th>Email</th>
				<th>Role</th>
				<th>Delete</th>
			</tr>
		</thead>
		<tbody>
			<?php if ($result = $mysqli->query($query)) {
				while ($row = $result->fetch_assoc()) {
					echo '<tr>';
						echo '<th>' . $row['name'] . '</th>';
						echo '<th>' . $row['email'] . '</th>';
						echo '<th>' . $row['role'] . '</th>';
						echo '<th><div class="single-button-form"><form method="post" role="form" action="">
							<input type="hidden" name="user_id" value="'.$row["id"].'" />
							<input onclick="return confirmDelete();" type="submit" name="Delete" value="Delete" />
						</form></div></th>'; 
					echo '</tr>';
				}
			} ?>
		</tbody>
	</table>

	<div class="mt-5">
		<h2>Add New User</h2>
		<div class="edit-form">
			<form method="post" role="form" action="">
				<div class="row">
					<div class="col-sm-4 field-choice">
						<label for="user_fullname">Name</label>
						<input id="user_fullname" name="user_fullname" value="" type="text" required/>
					</div>
					<div class="col-sm-4 field-choice">
						<label for="user_email">Email</label>
						<input id="user_email" name="user_email" value="" type="text" required/>
					</div>
					<div class="col-sm-4 field-choice">
						<label for="user_role">User Role</label>
						<select id="user_role" name="user_role">
							<option value="Editor">Editor</option>
							<option value="Admin">Admin</option>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12 field-choice">
						<input type="submit" name="Add_User" value="Add User">
					</div>
				</div>
			</form>
		</div>
	</div>

	<p>Logged in as: <?= $payload['email']; ?></p>
	<a class="transparent-button-black" href="logout.php">Sign out</a>
</div>
<script type="text/javascript">
	function confirmDelete() {
		return confirm('Are you sure you want to delete this user?');
	}
</script>

<?php
include "components/close-actions.php";
include "components/footer.php";