<?php
// Import components
include "components/pwd.php";
include "components/connection.php";
include "components/init-actions.php";

$title = "Edit Categories";
$description = "Edit categories of software used by the Design Hub, for admin use only.";

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

?>
<div class="site-inner">
	<p><strong><em>Make Edits to the Categories Below</em></strong></p>
	<div class="resource-intro">
		<p>Categories are groupings of tags. You can review, add and edit the overarching categories below.</p>
		<p>Categories are also used to populate the filter on the main search page.</p>
		<div>
			<a class="transparent-button-black" href="http://power.arc.losrios.edu/~designhub/software_db/view.php"><i class="fas fa-angle-left"></i> Back to View All</a>
		</div>
	</div>

	<?php

	// UPDATE CATEGORIES
	if (isset($_POST["Update_Category"])) {

		// Build query for updating Tag
		$update_cat = "UPDATE 
			".$db_prefix."Categories
			SET ".$db_prefix."Categories.name = 
				CASE ";

		foreach ($_POST as $key => $value) {
			if(is_numeric($key)) {
				$update_cat .= " WHEN ".$db_prefix."Categories.id = ".$mysqli->real_escape_string($key)." THEN '";
				$update_cat .= $mysqli->real_escape_string($value[0]) . "' ";
			}
		}

		$update_cat .= " ELSE ".$db_prefix."Categories.name END";

		// Build query for updating priority
		$update_priority = "UPDATE 
			".$db_prefix."Categories
			SET ".$db_prefix."Categories.priority = 
				CASE ";

		foreach ($_POST as $key => $value) {
			if(is_numeric($key)) {
				$update_priority .= " WHEN ".$db_prefix."Categories.id = ".$mysqli->real_escape_string($key)." THEN '";
				$update_priority .= $mysqli->real_escape_string($value[1]) . "' ";
			}
		}

		$update_priority .= " ELSE ".$db_prefix."Categories.priority END";

		// Send queries
		if ($mysqli->query($update_cat) && $mysqli->query($update_priority)) {
			echo "<div class='update-alert'>Categories Updated.</div>";
		} else {
			echo "<div class='update-alert alert-error'>There was an error updating the categories.</div>";
		}
	}

	// REMOVE OR DELETE CATEGORY
	if (isset($_POST["Delete"])) {

		$cat_to_delete = 0;

		foreach ($_POST as $key => $value) {
			if(is_numeric($key)) {
				if (end($value) == 'Delete') {
					$cat_to_delete = $key;
				} 
			}
		}

		$delete_query = "DELETE ".$db_prefix."Categories, ".$db_prefix."Category_Tag, ".$db_prefix."Software_Tags, ".$db_prefix."Tags  
			FROM ".$db_prefix."Categories
			LEFT JOIN ".$db_prefix."Category_Tag ON ".$db_prefix."Category_Tag.category_id = ".$db_prefix."Categories.id 
			LEFT JOIN ".$db_prefix."Software_Tags ON ".$db_prefix."Software_Tags.tag_id = ".$db_prefix."Category_Tag.tag_id  
			LEFT JOIN ".$db_prefix."Tags ON ".$db_prefix."Tags.id = ".$db_prefix."Category_Tag.tag_id 
			WHERE ".$db_prefix."Categories.id = " . $mysqli->real_escape_string($cat_to_delete);

		// Send queries
		if ($cat_to_delete && $mysqli->query($delete_query)) {
			echo "<div class='update-alert'>Category and all associated tags deleted.</div>";
		} else if (!$cat_to_delete && !$cat_to_remove) {
			// do nothing
		} else {
			echo "<div class='update-alert alert-error'>There was an error with the category.</div>";
		}
	}

	// ADDING NEW CATEGORY
	if (isset($_POST["Add_Cat"])) {

		if (!empty($_POST['New_Cat_Name'])) {
			$add_cat_name_query = "INSERT INTO ".$db_prefix."Categories(name) values('".$mysqli->real_escape_string($_POST['New_Cat_Name'])."')";
			
			if ($mysqli->query($add_cat_name_query)) {
				echo "<div class='update-alert'>New category added.</div>";
			} else {
				echo "<div class='update-alert alert-error'>There was an error adding the new category.</div>";
			}
		}

	}

	$query = "SELECT  
			".$db_prefix."Categories.id,
			".$db_prefix."Categories.name,
			".$db_prefix."Categories.priority  
		FROM ".$db_prefix."Categories
		ORDER BY ".$db_prefix."Categories.priority";

	$result = $mysqli->query($query);

	?>
	<h2>Edit Categories</h2>
	<div class="row">
		<div class="col-sm-12">
		</div>
	</div>
	<div class="edit-form">
		<form method="post" role="form" action="">
			<div class="row">
				<div class="col-sm-4"><h3>Category</h3></div>
				<div class="col-sm-4"><h3>Priority</h3></div>
				<div class="col-sm-4"><h3>Actions</h3></div>
			</div>
			<?php 
			while ($row = $result->fetch_assoc()) {
				echo "<div class='row'><div class='col-sm-4 field-choice'>";
				echo "<label class='hidden' for='".$row['id']."'>Category</label>";
				echo "<input id='".$row['id']."' name='".$row['id']."[]' value='".$row['name']."' type='text' required/>";
				echo "</div>";

				echo "<div class='col-sm-4 field-choice'>
					<label class='hidden' for='priority-".$row['id']."'>Priority</label>
					<input id='priority-".$row['id']."' name='".$row['id']."[]' value='".$row['priority']."' type='number' required/>
				</div>";

				echo "<div class='col-sm-4'><h4 class='hidden'>Actions</h4>
					<input type='hidden' name='Delete' value='1'/>
					<input onclick='return confirmDelete();' type='submit' class='transparent-button' name='".$row['id']."[]' value='Delete'>
					</div>";
				echo "</div>";
			} 
			?>
			<input type="submit" name="Update_Category" value="Update">
		</form>
	</div>

	<div>
		<h2>Add New Category</h2>
		<div class="edit-form">
			<form method="post" role="form" action="">
				<div class="row">
					<div class="col-sm-6 field-choice">
						<label for="new-cat">Name</label>
						<input id="new-cat" name="New_Cat_Name" value="" />
					</div>
				</div>
				<input type="submit" name="Add_Cat" value="Add Category">
			</form>
		</div>
	</div>
	<p>Logged in as: <?= $payload['email'] ?></p>
	<a class="transparent-button-black" href="logout.php">Sign out</a>
</div>
<script type="text/javascript">
	function confirmDelete() {
		return confirm('Are you sure you want to delete this category? If you proceed, this category and all associated tags will be removed from the site and all software records.');
	}
</script>
<?php 

include "components/close-actions.php";
include "components/footer.php";