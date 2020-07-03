<?php
// Import components
include "components/pwd.php";
include "components/connection.php";
include "components/init-actions.php";

$title = "View All Records";
$description = "See records of all software used by the Design Hub, for admin use only.";

include "components/header.php";

//include google api files
require_once 'google-sign-in-2.2.3/vendor/autoload.php';

// Confirm authentication
if (isset($_SESSION['access_token'])) {
	$access_token = $_SESSION['access_token'];
	$client = new Google_Client(['client_id' => $google_client_id]);  // Specify the CLIENT_ID of the app that accesses the backend
	$payload = $client->verifyIdToken($access_token);

	if ($payload) {
		$user_query = "SELECT DISTINCT ".$db_prefix."Users.google_id FROM ".$db_prefix."Users WHERE google_id = '" . $mysqli->real_escape_string($payload['sub']) . "'";
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
	
	<p><strong><em>View all records below to add, edit, or delete.</em></strong></p>
	<div class="resource-intro">
		<p>To edit categories or tags, first click to edit a record.</p>
		<a class="transparent-button-black" href="/~designhub/software_db/search.php"><i class="fas fa-angle-left"></i> Back to Search</a>
	</div>


	<?php

	if(isset($_POST["Delete"])) {
		// Delete record query using Transactions
		$mysqli->autocommit(FALSE);

		// Delete associations with Tags
		$delete_query = "DELETE from ".$db_prefix."Software_Tags where software_id = " . $mysqli->real_escape_string($_POST["resource_id"]);
		$mysqli->query($delete_query);
		
		// Delete from Cost
		$delete_query = "DELETE from ".$db_prefix."Cost WHERE software_id = " . $mysqli->real_escape_string($_POST["resource_id"]);
		$mysqli->query($delete_query);
		
		// Delete Software
		$delete_query = "DELETE from ".$db_prefix."Software where id = " . $mysqli->real_escape_string($_POST["resource_id"]);
		$mysqli->query($delete_query);

		// Commit changes
		if ($mysqli->commit()) {
			echo "<div class='update-alert'>Record deleted.</div>";
		} else {
			echo "<div class='update-alert alert-error'>There was an issue deleting the record.</div>";
		}
	}

	$query = "SELECT 
			".$db_prefix."Software.id,
			".$db_prefix."Software.name as resource_name,
			".$db_prefix."Software.status,
			".$db_prefix."Software.valid
		FROM ".$db_prefix."Software;";

	?>

	<table class="records-table">
		<thead>
			<tr>
				<th>Valid?</th>
				<th>Name</th>
				<th>Status</th>
				<th>Edit</th>
				<th>Delete</th>
			</tr>
		</thead>
		<tbody>
			<?php if ($result = $mysqli->query($query)) {
				while ($row = $result->fetch_assoc()) {
					echo '<tr>';
						echo ($row['valid']) ? '<th>Yes</th>' : '<th>No</th>';
						echo '<th>' . $row['resource_name'] . '</th>';
						echo '<th>' . $row['status'] . '</th>';
						echo "<th><a href='edit.php?id=".$row["id"]."&Edit=edit'>EDIT</a></th>";
						echo '<th>';
						if (isset($_SESSION['role']) && ($_SESSION['role'] == 'Admin')) {
							echo '<div class="single-button-form"><form method="post" role="form" action="">
								<input type="hidden" name="resource_id" value="'.$row["id"].'" />
								<input type="hidden" name="loc_id" value="'.$row["loc_id"].'" />
								<input onclick="return confirmDelete();" type="submit" name="Delete" value="Delete" />
								</form></div></th>';
						} else {
							echo '<em>Admin Only</em>';
						}
						echo '</th>';
					echo '</tr>';
				}
			} ?>
		</tbody>
	</table>
	<br>
	<a class="transparent-button-black" href="edit.php?Add_Record=Add">Add Record</a>

	<?php
	if (isset($_SESSION['role']) && ($_SESSION['role'] == 'Admin')) {
		?>
		<div class="mt-5">
			<h2>Users</h2>
			<div class="edit-form">
				<p>Add or delete users from the user admin panel.</p>
				<a href="view-users.php" class="transparent-button-black">Edit</a>
			</div>
		</div>
		<?php
	}
	?>

	<p>Logged in as: <?= $payload['email']; ?></p>
	<a class="transparent-button-black" href="logout.php">Sign out</a>
</div>
<script type="text/javascript">
	function confirmDelete() {
		return confirm('Are you sure you want to delete this record?');
	}
</script>

<?php 

include "components/close-actions.php";
include "components/footer.php";