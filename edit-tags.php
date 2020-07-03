<?php
// Import components
include "components/pwd.php";
include "components/connection.php";
include "components/init-actions.php";
include "components/pwd.php";

$title = "Edit Tags";
$description = "Edit tags for all software used by the Design Hub, for admin use only.";

include "components/header.php";
	
//include google api files
require_once 'google-sign-in-2.2.3/vendor/autoload.php';

// Confirm authentication
if (isset($_SESSION['access_token'])) {
	$access_token = $_SESSION['access_token'];
	$client = new Google_Client(['client_id' => $google_client_id]);  // Specify the CLIENT_ID of the app that accesses the backend
	$payload = $client->verifyIdToken($access_token);
	
	if ($payload) {
		$user_query = "SELECT DISTINCT ".$db_prefix."Users.google_id, ".$db_prefix."Users.role FROM ".$db_prefix."Users WHERE google_id = '" . $mysqli->real_escape_string($payload['sub']) . "' AND role = 'Admin'";
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

// GET VARIABLES
if (isset($_GET["cat_id"])) {
	$category_id = $mysqli->real_escape_string($_GET["cat_id"]);

	$name_query = "SELECT DISTINCT 
		".$db_prefix."Categories.name AS cat_name
		FROM ".$db_prefix."Categories
		WHERE ".$db_prefix."Categories.id = '" . $category_id . "'";
	if ($name_results = $mysqli->query($name_query)) {
		$name_row = $name_results->fetch_assoc();
		$category_name = $name_row['cat_name'];
	}
}

?>
<div class="site-inner">
	<h2>Edit <?= $category_name ?> Tags</h2>
	<p><strong><em>Review and edit the tags for this category below.</em></strong></p>
	<div class="resource-intro">
		<p>Groups of tags are assigned to a particular category. On this page, you can add, edit or delete the tags assigned to a certain category.</p>
		<div>
			<a class="transparent-button-black" href="http://power.arc.losrios.edu/~designhub/software_db/view.php"><i class="fas fa-angle-left"></i> Back to View All</a>
		</div>
	</div>
	<?php

	// UPDATE TAGS
	if (isset($_POST["Update_Tag"])) {

		// Build query for updating Tag
		$update_tag = "UPDATE 
			".$db_prefix."Tags
			JOIN ".$db_prefix."Category_Tag ON ".$db_prefix."Category_Tag.tag_id = ".$db_prefix."Tags.id 
			SET ".$db_prefix."Tags.name = 
				CASE ";

		foreach ($_POST as $key => $value) {
			if(is_numeric($key)) {
				$update_tag .= " WHEN ".$db_prefix."Tags.id = ".$mysqli->real_escape_string($key)." THEN '";
				$update_tag .= $mysqli->real_escape_string($value[0]) . "' ";
			}
		}

		$update_tag .= " ELSE ".$db_prefix."Tags.name END 
			WHERE ".$db_prefix."Category_Tag.category_id = " . $category_id;

		// Build query for updating priority
		$update_priority = "UPDATE 
			".$db_prefix."Category_Tag
			SET ".$db_prefix."Category_Tag.priority = 
				CASE ";

		foreach ($_POST as $key => $value) {
			if(is_numeric($key)) {
				$update_priority .= " WHEN ".$db_prefix."Category_Tag.tag_id = ".$mysqli->real_escape_string($key)." THEN '";
				$update_priority .= $mysqli->real_escape_string($value[1]) . "' ";
			}
		}

		$update_priority .= " ELSE ".$db_prefix."Category_Tag.priority END 
			WHERE ".$db_prefix."Category_Tag.category_id = " . $category_id;

		// Send queries
		if ($mysqli->query($update_tag) && $mysqli->query($update_priority)) {
			echo "<div class='update-alert'>Tags Updated.</div>";
		} else {
			echo "<div class='update-alert alert-error'>There was an error updating the tags.</div>";
		}
	}

	// REMOVE OR DELETE TAG
	if (isset($_POST["Delete"])) {

		$tag_to_delete = 0;
		// $tag_to_remove = 0;

		foreach ($_POST as $key => $value) {
			if(is_numeric($key)) {
				if (end($value) == 'Delete') {
					$tag_to_delete = $key;
				} 
			}
		}
		
		$delete_tag_query = "DELETE FROM ".$db_prefix."Tags WHERE ".$db_prefix."Tags.id = " . $mysqli->real_escape_string($tag_to_delete);
	
		// Send queries
		if ($tag_to_delete && $mysqli->query($delete_tag_query)) {
			echo "<div class='update-alert'>Tag deleted.</div>";
		} else if (!$tag_to_delete) {
			// do nothing
		} else {
			echo "<div class='update-alert alert-error'>There was an error deleting the tag.</div>";
		}
	}

	// ADDING NEW TAG
	if (isset($_POST["Add_Tag"])) {

		if (!empty($_POST['New_Tag_Name'])) {
			$add_tag_name_query = "INSERT into ".$db_prefix."Tags(name) values('".$mysqli->real_escape_string($_POST['New_Tag_Name'])."')";

			if ($mysqli->query($add_tag_name_query)) {
				$add_existing_to_cat_query = "INSERT into 
					".$db_prefix."Category_Tag(category_id, tag_id, priority) values ";
				if (!empty($_POST['New_Tag_Name'])) {
					$add_existing_to_cat_query .= "('".$category_id."','".$mysqli->insert_id."','".$mysqli->real_escape_string($_POST['New_Tag_Priority'])."'),";
				}
	
				$add_existing_to_cat_query = rtrim($add_existing_to_cat_query, ",");
	
				if ($mysqli->query($add_existing_to_cat_query)) {
					echo "<div class='update-alert'>Tag added to category.</div>";
				} else {
					echo "<div class='update-alert alert-error'>There was an error adding the new tag.</div>";
				}
			} else {
				echo "<div class='update-alert alert-error'>There was an error adding the new tag.</div>";
			}

		}

	}

	$query = "SELECT  
			".$db_prefix."Categories.name, 
			".$db_prefix."Tags.name AS tag_name, 
			".$db_prefix."Tags.id AS tag_id, 
			".$db_prefix."Category_Tag.priority   
		FROM ".$db_prefix."Categories
		JOIN ".$db_prefix."Category_Tag ON ".$db_prefix."Category_Tag.category_id = ".$db_prefix."Categories.id 
		JOIN ".$db_prefix."Tags on ".$db_prefix."Tags.id = ".$db_prefix."Category_Tag.tag_id 
		WHERE ".$db_prefix."Categories.id = " . $category_id . " 
		ORDER BY ".$db_prefix."Category_Tag.priority";

	$result = $mysqli->query($query);

	?>
	<div class="edit-form">
		<form method="post" role="form" action="">
			<div class="row">
				<div class="col-sm-4"><h3>Tag</h3></div>
				<div class="col-sm-4"><h3>Priority</h3></div>
				<div class="col-sm-4"><h3>Actions</h3></div>
			</div>
			<?php 
			while ($row = $result->fetch_assoc()) {
				echo "<div class='row'><div class='col-sm-4 field-choice'>";
				echo "<label class='hidden' for='".$row['tag_id']."'>Tag</label>";
				echo "<input id='".$row['tag_id']."' name='".$row['tag_id']."[]' value='".$row['tag_name']."' type='text' required/>";
				echo "</div>";

				echo "<div class='col-sm-4 field-choice'>
					<label class='hidden' for='priority-".$row['tag_id']."'>Priority</label>
					<input id='priority-".$row['tag_id']."' name='".$row['tag_id']."[]' value='".$row['priority']."' type='number' required/>
				</div>";

				echo "<div class='col-sm-4'><h4 class='hidden'>Actions</h4>
					<input type='hidden' name='Delete' value='1'/>
					<input onclick='return confirmDelete();' type='submit' class='transparent-button' name='".$row['tag_id']."[]' value='Delete'>
					</div>";
				echo "</div>";
			} ?>
			<input type="submit" name="Update_Tag" value="Update">
		</form>
	</div>

	<div>
		<h2>Add New Tag</h2>
		<div class="edit-form">
			<form method="post" role="form" action="">
				<div class="row">
					<div class="col-sm-6 field-choice">
						<label for="new-tag">Name</label>
						<input id="new-tag" name="New_Tag_Name" value=""  required />
					</div>
					<div class="col-sm-6 field-choice">
						<label for='new-priority'>Priority</label>
						<input id='new-priority' name='New_Tag_Priority' value='0' type='number' placeholder='0' />
					</div>
				</div>
				<input type="submit" name="Add_Tag" value="Add Tag">
			</form>
		</div>
	</div>

	<p>Logged in as: <?= $payload['email']; ?></p>
	<a class="transparent-button-black" href="logout.php">Sign out</a>

</div>
<script type="text/javascript">
	function confirmDelete() {
		return confirm('Are you sure you want to delete this tag? If you proceed, all references to this tag will be deleted.');
	}
</script>
<?php 

include "components/close-actions.php";
include "components/footer.php";