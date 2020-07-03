<?php
// Import components
include "components/pwd.php";
include "components/connection.php";
include "components/init-actions.php";

if(isset($_GET["Edit"])) {
	$title = "Edit Record";
} else {
	$title = "Add Record";
}
$description = "Edit record of software used by the Design Hub, for admin use only.";

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
	<p><strong><em>Make Edits to the Record Below</em></strong></p>
	<div class="resource-intro">
		<p>Review and edit the information for this record below.</p>
		<div>
			<a class="transparent-button-black" href="http://power.arc.losrios.edu/~designhub/software_db/view.php"><i class="fas fa-angle-left"></i> Back to View All</a>
		</div>
	</div>


	<?php

	// RECORD ID VARIABLE
	if (isset($_GET["id"])) {
		$record_id = $mysqli->real_escape_string($_GET["id"]);
	}

	// UPDATING A RECORD
	if(isset($_POST["Update"])) {
		// Update record query using Transactions
		$mysqli->autocommit(FALSE);

		// Update text and other simple fields
		$update_query = "UPDATE ".$db_prefix."Software SET 
			".$db_prefix."Software.name = '".$mysqli->real_escape_string($_POST["record_name"])."', 
			".$db_prefix."Software.url = '".$mysqli->real_escape_string($_POST["record_url"])."', 
			".$db_prefix."Software.status = '".$mysqli->real_escape_string($_POST["record_status"])."',";
		if($_POST["record_min_seats"] || strlen($_POST["record_min_seats"])>0) $update_query .= $db_prefix."Software.min_seats = ".$mysqli->real_escape_string($_POST["record_min_seats"]).",";
		if($_POST["record_lic_start"]) $update_query .= $db_prefix."Software.lic_start = '".$mysqli->real_escape_string($_POST["record_lic_start"])."',";
		if($_POST["record_lic_end"]) $update_query .= $db_prefix."Software.lic_end = '".$mysqli->real_escape_string($_POST["record_lic_end"])."',";
		$update_query .= $db_prefix."Software.valid = '".$mysqli->real_escape_string($_POST["valid"])."', 
			".$db_prefix."Software.version = '".$mysqli->real_escape_string($_POST["record_version"])."', 
			".$db_prefix."Software.notes = '".$mysqli->real_escape_string($_POST["record_notes"])."' 
			WHERE ".$db_prefix."Software.id = " . $record_id;
		$mysqli->query($update_query);

		// Query to remove all tags
		$delete_query = "DELETE from ".$db_prefix."Software_Tags WHERE 
			".$db_prefix."Software_Tags.software_id = ". $record_id;
		$mysqli->query($delete_query);

		// Query to add all updated tags
		if (isset($_POST["tags"])) {
			$add_query = "INSERT into ".$db_prefix."Software_Tags(software_id, tag_id) VALUES ";
			foreach ($_POST["tags"] as $key => $value) {
				$add_query .= "(".$record_id.", ".$value."), ";
			}
			$add_query = rtrim($add_query, ", ");

			$mysqli->query($add_query);
		}

		// Query to remove all cost fields
		$delete_query = "DELETE FROM ".$db_prefix."Cost WHERE software_id = ". $record_id;
		$mysqli->query($delete_query);

		// Add query if cost is set
		if ($_POST["record_cost"]) {
			$add_query = "INSERT into ".$db_prefix."Cost(software_id, cost, freq, unit) VALUES ";
			for ($i = 0; $i < count($_POST["record_cost"]); $i++) {
				$add_query .= "(".$record_id.", ".$mysqli->real_escape_string($_POST["record_cost"][$i]).", '".$mysqli->real_escape_string($_POST["record_freq"][$i])."', '".$mysqli->real_escape_string($_POST["record_unit"][$i])."' ), ";
			}
			$add_query = rtrim($add_query, ", ");
			$add_query .= ";";
			$mysqli->query($add_query);
		}

		// Send queries
		if ($mysqli->commit()) {
			echo "<div class='update-alert'>Record Updated.</div>";
		} else {
			echo "<div class='update-alert alert-error'>There was an error updating the record.</div>";
		}

	}

	// ADDING A RECORD - AFTER FORM SUBMISSION
	if(isset($_POST["Add"])) {
		// Add record query using Transactions
		$mysqli->autocommit(FALSE);

		$add_res_query = "INSERT INTO
			".$db_prefix."Software (
				name,
				url,
				valid,
				status,";
		if($_POST["record_min_seats"] || strlen($_POST["record_min_seats"])>0) $add_res_query .= "min_seats,";
		if($_POST["record_lic_start"]) $add_res_query .= "lic_start,";
		if($_POST["record_lic_end"]) $add_res_query .= "lic_end,";
		$add_res_query .= "
				notes,
				version
			)
			VALUES (
				('".$mysqli->real_escape_string($_POST["record_name"])."'), 
				('".$mysqli->real_escape_string($_POST["record_url"])."'), 
				('".$mysqli->real_escape_string($_POST["valid"])."'), 
				('".$mysqli->real_escape_string($_POST["record_status"])."'),";
		if($_POST["record_min_seats"] || strlen($_POST["record_min_seats"])>0) $add_res_query .= "('".$mysqli->real_escape_string($_POST["record_min_seats"])."'),";
		if($_POST["record_lic_start"]) $add_res_query .= "('".$mysqli->real_escape_string($_POST["record_lic_start"])."'),";
		if($_POST["record_lic_end"]) $add_res_query .= "('".$mysqli->real_escape_string($_POST["record_lic_end"])."'),";
		$add_res_query .= "
				('".$mysqli->real_escape_string($_POST["record_notes"])."'),
				('".$mysqli->real_escape_string($_POST["record_version"])."')
			);";
		$mysqli->query($add_res_query);
		
		// Add query if tags are set
		if ($_POST["tags"]) {
			$add_res_query = "INSERT into ".$db_prefix."Software_Tags(software_id, tag_id) VALUES ";
			foreach ($_POST["tags"] as $key => $value) {
				$add_res_query .= "(LAST_INSERT_ID(), ".$mysqli->real_escape_string($value)."), ";
			}
			$add_res_query = rtrim($add_res_query, ", ");
			$add_res_query .= ";";

			$mysqli->query($add_res_query);
		}

		// Add query if cost is set
		if ($_POST["record_cost"]) {
			$add_res_query = "INSERT into ".$db_prefix."Cost(software_id, cost, freq, unit) VALUES ";
			for ($i = 0; $i < count($_POST["record_cost"]); $i++) {
				$add_res_query .= "(LAST_INSERT_ID(), ".$mysqli->real_escape_string($_POST["record_cost"][$i]).", '".$mysqli->real_escape_string($_POST["record_freq"][$i])."', '".$mysqli->real_escape_string($_POST["record_unit"][$i])."' ), ";
			}
			$add_res_query = rtrim($add_res_query, ", ");
			$add_res_query .= ";";
			$mysqli->query($add_res_query);
		}

		if ($mysqli->commit()) {
			echo "<div class='update-alert'>Record added. <strong><a href='view.php'>Back to all records.</a></strong></div>";
		} else {
			echo "<div class='update-alert alert-error'>There was an error adding the record.</div>";
		}
	}

	// EDITING A RECORD
	if(isset($_GET["Edit"])) {

		// Query data for output
		$query = "SELECT  
				".$db_prefix."Software.id,
				".$db_prefix."Software.name,
				".$db_prefix."Software.url,
				".$db_prefix."Software.valid,
				".$db_prefix."Software.status,
				".$db_prefix."Software.min_seats,
				".$db_prefix."Software.lic_start, 
				".$db_prefix."Software.lic_end, 
				".$db_prefix."Software.notes, 
				".$db_prefix."Software.version, 
				".$db_prefix."Tags.id AS tag_id,
				".$db_prefix."Tags.name AS tag_name 
			FROM ".$db_prefix."Software 
			LEFT JOIN ".$db_prefix."Software_Tags on ".$db_prefix."Software.id = ".$db_prefix."Software_Tags.software_id 
			LEFT JOIN ".$db_prefix."Tags on ".$db_prefix."Software_Tags.tag_id = ".$db_prefix."Tags.id 
			WHERE ".$db_prefix."Software.id = " . $record_id;

		$result = $mysqli->query($query);
		$first_row = $result->fetch_assoc();

		$tags_cloud = array();
		$tags_cloud[$first_row['tag_id']] = $first_row['tag_name'];
		while ($result_row = $result->fetch_assoc()) {
			$tags_cloud[$result_row['tag_id']] = $result_row['tag_name'];
		}
	}

	?>
	<div class="edit-form">
		<form method="post" role="form" action="">
			<h4>General Information</h4>
			<div class="row">
				<div class="col-sm-3 resource-field">
					<fieldset>
						<legend>Approved?</legend>
						<?php
						if (isset($_SESSION['role']) && ($_SESSION['role'] == 'Admin')) {
							?>
							<div class='field-choice'>
								<input class="form-control" id="yes" type="radio" name="valid" value="1"
								<?php if ($first_row['valid']){ echo ' checked ';}?> >
								<label for="yes">Yes</label>
							</div>
							<div class='field-choice'>
								<input class="form-control" id="no" type="radio" name="valid" value="0"
								<?php if (!$first_row['valid']){ echo ' checked ';}?> >
								<label for="no">No</label>
							</div>
							<?php
						} else {
							?>
							<input id="valid" type="hidden" name="valid" value="0" / >
							<div class='field-choice'>Only admins can approve resources for posting.</div>
							<?php
						}?>
					</fieldset>
				</div>
				<div class="col-sm-9 field-choice">
					<label for="record_name">Name*</label>
					<input id="record_name" class="form-control" name="record_name" value="<?= $first_row['name'] ?>" type="text" required/>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-4 field-choice">
					<label for="record_url">URL</label>
					<input id="record_url" class="form-control" name="record_url" value="<?= $first_row['url'] ?>" type="url" />
				</div>
				<div class="col-sm-4 field-choice">
					<label for="record_status">Status*</label>
					<input id="record_status" class="form-control" name="record_status" value="<?= $first_row['status'] ?>" type="text" required />
				</div>
				<div class="col-sm-4 field-choice">
					<label for="record_min_seats">Min Seats</label>
					<input id="record_min_seats" class="form-control" name="record_min_seats" value="<?= $first_row['min_seats'] ?>" type="number" />
				</div>
			</div>
			<div class="row">
				<div class="col-sm-4 field-choice">
					<label for="record_version">Version</label>
					<input id="record_version" class="form-control" name="record_version" value="<?= $first_row['version'] ?>" type="text" />
				</div>
				<div class="col-sm-4 field-choice">
					<label for="record_lic_start">License Start Date</label>
					<input id="record_lic_start" class="form-control" name="record_lic_start" value="<?= $first_row['lic_start'] ?>" type="date" />
				</div>
				<div class="col-sm-4 field-choice">
					<label for="record_lic_end">License End Date</label>
					<input id="record_lic_end" class="form-control" name="record_lic_end" value="<?= $first_row['lic_end'] ?>" type="date" />
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12 field-choice">
					<label for="record_notes">Notes</label>
					<textarea id="record_notes" class="form-control" name="record_notes" rows="5"/><?= $first_row['notes'] ?></textarea>
				</div>
			</div>
			<h4 id="cost-section">Cost</h4>
			<?php 

			if (isset($_GET["id"])) {
				$cost_query = "SELECT cost, freq, unit FROM ".$db_prefix."Cost WHERE software_id = " . $record_id;
	
				if ($cost_result = $mysqli->query($cost_query)) {
					$i=0;
					while ($cost_row = $cost_result->fetch_assoc()) {
						?>
						<div class="row" id="cost-<?= $i ?>">
							<div class="col-sm-3 field-choice">
								<label for="record_cost">Cost</label>
								<div class="input-group mb-3">
									<div class="input-group-prepend">
										<span class="input-group-text">$</span>
									</div>
									<input id="record_cost" class="form-control" name="record_cost[]" value="<?= $cost_row['cost'] ?>" type="number" step="0.01" />
								</div>
							</div>
							<div class="col-sm-3 field-choice">
								<label for="record_freq">Payment Frequency</label>
								<select id="record_freq" class="form-control" name="record_freq[]">
									<option></option>
									<option value="One-Time" <?php if ($cost_row['freq'] == "One-Time") echo "selected"; ?> >One-Time</option>
									<option value="Monthly" <?php if ($cost_row['freq'] == "Monthly") echo "selected"; ?>>Monthly</option>
									<option value="Yearly" <?php if ($cost_row['freq'] == "Yearly") echo "selected"; ?>>Yearly</option>
								</select>
							</div>
							<div class="col-sm-4 field-choice">
								<label for="record_unit">Unit</label>
								<div class="input-group mb-3">
									<div class="input-group-prepend">
										<span class="input-group-text">per</span>
									</div>
									<input id="record_unit" class="form-control" name="record_unit[]" value="<?= $cost_row['unit'] ?>" type="text" />
								</div>
							</div>
							<div class="col-sm-2">
								<label>Action</label>
								<div class="input-group">
									<a class="remove-cost" href=""><button type="button" class="btn btn-danger">Delete Cost</button></a>
								</div>
							</div>
						</div>
						<?php
						$i++;
					}
				}
			}
			?>
			<div class="row" id="add-cost-row">
				<div class="col-lg-12">
					<a id="add-cost" href=""><button type="button" class="btn btn-success">+ Add Cost</button></a>
				</div>
			</div>
			
			<h4>Categories & Tags</h4>
			<?php
			// Only Admins can edit category
			if (isset($_SESSION['role']) && ($_SESSION['role'] == 'Admin')) {
				?>
				<div class="edit-link">
					<a href="edit-categories.php">Edit Categories</a>
				</div>
				<?php
			} else {
				echo '<div>Contact an admin to make changes to the categories and tags available.</div>';
			} ?>
			<div class="tag-boxes">
				<?php 

				// Display Tags for the Resource, Grouped by Category
				$cat_query = "SELECT DISTINCT
					".$db_prefix."Categories.name, 
					".$db_prefix."Categories.id  
					FROM ".$db_prefix."Categories ";
				$cat_query .= " ORDER BY ".$db_prefix."Categories.priority";

				$tags_query = "";

				if ($cat_result = $mysqli->query($cat_query)) {
					

					while ($cat_row = $cat_result->fetch_assoc()) {
						echo "<div class='resource-field'><fieldset>";
						echo "<legend>".$cat_row['name']."</legend>";

						$tags_query = "SELECT
							".$db_prefix."Tags.id AS tag_id, 
							".$db_prefix."Tags.name AS tag_name
							FROM ".$db_prefix."Tags 
							JOIN ".$db_prefix."Category_Tag on ".$db_prefix."Category_Tag.tag_id = ".$db_prefix."Tags.id 
							JOIN ".$db_prefix."Categories on ".$db_prefix."Categories.id = ".$db_prefix."Category_Tag.category_id";
						$tags_query .= " AND ".$db_prefix."Categories.id = ".$cat_row['id']."
							ORDER BY ".$db_prefix."Category_Tag.priority, ".$db_prefix."Tags.name";

						if ($tags_result = $mysqli->query($tags_query)) {
							while ($tags_row = $tags_result->fetch_assoc()) {
								// Content of each checkbox
								echo "<div class='field-choice'>
									<input id ='" . $tags_row['tag_name'] . "' type='checkbox' name='tags[]' ";
								echo "value='".$tags_row['tag_id']."'";
								if ($tags_cloud[$tags_row['tag_id']]) {
									echo " checked ";
								}
								echo "><label for='".$tags_row['tag_name']."'>".$tags_row['tag_name']."</label></div>";
							}
						}

						// Only admins can edit tags
						if (isset($_SESSION['role']) && ($_SESSION['role'] == 'Admin')) {
							echo "<div class='edit-link'>";
							echo "<a href='edit-tags.php?cat_id=".$cat_row['id'];
							echo "'>Edit Tags</a>";
							echo "</div>";
						}
						echo "</fieldset></div>";
					}
				}
				?>
			</div>
			<?php 
			if(isset($_GET["Edit"])) {
				echo '<input type="submit" name="Update" value="Update">';
			} else {
				echo '<input type="submit" name="Add" value="Add Record">';
			}
			?>
		</form>
	</div>
	<p>Logged in as: <?= $payload['email']; ?></p>
	<a class="transparent-button-black" href="logout.php">Sign out</a>
</div>
<script src="js/edit-actions.js"></script>
<?php 

include "components/close-actions.php";
include "components/footer.php";