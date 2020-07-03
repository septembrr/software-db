<?php
// Import components
include "components/pwd.php";
include "components/connection.php";
include "components/init-actions.php";

$title = "Design Hub Software";
$description = "Find software used by the Design Hub at American River College.";

include "components/header.php";

?>
<div class="site-inner">
	<p><strong><em>Find the software you need for your project at the Design Hub</em></strong></p>
	<div class="resource-intro">
		<p>Is a piece of software missing from this list, or do you have one to suggest for purchase? <strong><a href="mailto:arcdhtime@gmail.com">Email us.</a></strong></p>
	</div>
	<br>
	<div class="resource-content">
		<div class="resource-form">
			<form method="post" role="form" action="">

				<?php $dropdown_count = 0;

				// Query filters to be used
				$filter_query = "SELECT 
					".$db_prefix."Categories.id,
					".$db_prefix."Categories.name,
					".$db_prefix."Categories.priority
				FROM ".$db_prefix."Categories 
				ORDER BY ".$db_prefix."Categories.priority;";

				// Loop through each filter
				if ($filter_result = $mysqli->query($filter_query)) {
					$num_filters = 0;
					$post_vars = [];
					while ($filter_row = $filter_result->fetch_assoc()) {
						// Assign name of filter section
						$filter_name = $filter_row['name'];
						$filter_name = str_replace(' ', '_', $filter_name);

						// For classes to manage open/close of filters
						++$dropdown_count;

						echo "<div class='resource-field ".$filter_name."'><fieldset>";
						echo "<legend class='legend-".$dropdown_count."' data-toggle='collapse' data-target='.options-list-".$dropdown_count."'>";
						echo $filter_row['name'];
						echo "</legend><div class='collapse options-list-".$dropdown_count."'>";

						$post_vars[$num_filters] = $filter_name;
						++$num_filters;
						
						// Assign variables to PHP Session
						if ($_POST["Reset"]) {
							unset($_SESSION[$filter_name]);
						} else if (isset($_POST[$filter_name])) {
							$_SESSION[$filter_name] = $_POST[$filter_name];
						} else {
							$_SESSION[$filter_name] = "";
						}

						$cat_tag_query = "SELECT
							".$db_prefix."Tags.name AS tag_name,
							".$db_prefix."Category_Tag.category_id 
							FROM ".$db_prefix."Tags 
							JOIN ".$db_prefix."Category_Tag on ".$db_prefix."Category_Tag.tag_id = ".$db_prefix."Tags.id 
							WHERE ".$db_prefix."Category_Tag.category_id = ".$filter_row['id']."
							ORDER BY ".$db_prefix."Category_Tag.priority";
							
						// Content of each checkbox
						if ($cat_tag_result = $mysqli->query($cat_tag_query)) {
							while ($cat_tag_row = $cat_tag_result->fetch_assoc()) {
								echo "<div class='field-choice'><input id ='" . $cat_tag_row['tag_name'] . "' type='checkbox' name='" . $filter_name . "[]' ";
								echo "value='".$cat_tag_row['tag_name']."'";
								foreach ($_SESSION[$filter_name] as $key => $value) {
									if ($value == $cat_tag_row['tag_name']) {
										echo " checked ";
									}
								}
								echo ">";
								echo "<label for='".$cat_tag_row['tag_name']."'>".$cat_tag_row['tag_name']."</label></div>";
							}
						}

						// End fieldset
						echo "</div></fieldset></div>";


					}
				}

				?>
				<div class="form-submit">
					<input type="submit" name="Search" value="Search">
				</div>
				<div class="form-reset">
					<input type="submit" name="Reset" value="Reset">
				</div>
			</form>
		</div>
		<?php

		// If search has been made
		if(isset($_POST["Search"]))
		{
			// Loop through possible filters
			// Assign filters chosen to $post_array	
			$post_array = [];
			for ($i = 0; $i < $num_filters; ++$i) {
				$post_array[$i] = $_POST[$post_vars[$i]];
			}
		}

		// If reset button has been pressed
		if(isset($_POST["Reset"])) {
			session_destroy();
		}

		// New Query
		$query = "SELECT 
				".$db_prefix."Software.id, 
				".$db_prefix."Software.name, 
				".$db_prefix."Software.url, 
				".$db_prefix."Software.status, 
				".$db_prefix."Software.min_seats, 
				".$db_prefix."Software.lic_start, 
				".$db_prefix."Software.lic_end, 
				".$db_prefix."Software.notes, 
				COUNT(DISTINCT(".$db_prefix."Categories.name)) AS result_count 
			FROM ".$db_prefix."Software ";

		$query .= "LEFT JOIN ".$db_prefix."Software_Tags on ".$db_prefix."Software_Tags.software_id = ".$db_prefix."Software.id 
			LEFT JOIN ".$db_prefix."Tags on ".$db_prefix."Software_Tags.tag_id = ".$db_prefix."Tags.id 
			LEFT JOIN ".$db_prefix."Category_Tag on ".$db_prefix."Category_Tag.tag_id = ".$db_prefix."Tags.id 
			LEFT JOIN ".$db_prefix."Categories on ".$db_prefix."Categories.id = ".$db_prefix."Category_Tag.category_id ";

		// If no other filters are selected
		if (strlen(implode($post_array)) == 0) {
			$query .= "WHERE ".$db_prefix."Software.valid = 1 GROUP BY ".$db_prefix."Software.name";
		} 
		// If additional tag filters are selected
		else {
			$query .= " WHERE (".$db_prefix."Software.valid = 1) AND (";
			$cat_count = 0;
			foreach($post_array as $filter_cat) {
				foreach($filter_cat as $filter_option) {
					$query .= $db_prefix."Tags.name = '" . $filter_option . "' OR ";
				}
				if (count($filter_cat) > 0) {
					++$cat_count;
				}
			}
			$query = rtrim($query, "OR ");

			$query .= ") GROUP BY ".$db_prefix."Software.name HAVING result_count = ";
			$query .= $cat_count;
		}

		if ($result = $mysqli->query($query)) {
			echo "<div class='table-wrap'>
			<div class='row drop-table-header'>
			<div class='col-md-4 table-header'><strong>Name</strong></div>
			<div class='col-md-7 table-header'><strong>Status</strong></div>
			<div class='col-md-1 table-header'><strong>Info</strong></div>
			</div>";

			$counter = 1;

			while ($row = $result->fetch_assoc()) {
				$name = $row["name"];
				$url = $row["url"];
				$status = $row["status"];
				$min_seats = $row["min_seats"];
				$lic_start = $row["lic_start"];
				$lic_end = $row["lic_end"];
				$comments = $row["notes"];
				$comments = nl2br($comments);
				$data_target = "#collapse" . $counter;
				$html_id = "collapse" . $counter;

				echo "<div class='row drop-table table-row' data-toggle='collapse' data-target=" . $data_target . ">
				<div class='col-md-4 table-item'>". $name . "</div>
				<div class='col-md-7 table-item'>" . $status . "</div>";

				echo "<div class='col-md-1 table-item'><div class='more-link'>More</div></div></div>
					<div id=" . $html_id . " class='row collapse'><div class='more_info'>";

				// Tags have whitespace attached
				$resource_id = $row["id"];

				if ($min_seats) {
					echo "<div><strong>Min Seats</strong></div>";
					echo "<div>".$min_seats."</div>";
				}
				if($lic_end || $lic_start) {
					echo "<div><strong>Start/End Dates</strong></div>";
					echo "<div>";
					echo ($lic_start) ? $lic_start : "?";
					echo " - ";
					echo ($lic_end) ? $lic_end : "?";
					echo "</div>";
				}
				if ($status) {
					echo "<div><strong>Status</strong></div>";
					echo "<div>".$status."</div>";
				}

				// Cost details
				$cost_query = "SELECT cost, freq, unit FROM ".$db_prefix."Cost WHERE software_id = ".$mysqli->real_escape_string($resource_id);
				if ($cost_result = $mysqli->query($cost_query)) {
					if ($cost_result->num_rows > 0) {
						echo "<div><strong>Cost</strong></div>";
						while ($cost_row = $cost_result->fetch_assoc()) {
							echo "<div>$".$cost_row['cost']." ".$cost_row['freq'];
							if ($cost_row['unit']) {
								echo ", per ".$cost_row['unit'];
							}
							echo "</div>";
						}
					}
				}

				// Query tags for display
				$res_info_query = " SELECT 
						".$db_prefix."Categories.name,
						GROUP_CONCAT(DISTINCT ".$db_prefix."Tags.name ORDER BY ".$db_prefix."Tags.name ASC SEPARATOR ', ') AS tags_list
					FROM ".$db_prefix."Tags 
					JOIN ".$db_prefix."Software_Tags on ".$db_prefix."Software_Tags.tag_id = ".$db_prefix."Tags.id 
					JOIN ".$db_prefix."Software on ".$db_prefix."Software.id = ".$db_prefix."Software_Tags.software_id  
					JOIN ".$db_prefix."Category_Tag on ".$db_prefix."Category_Tag.tag_id = ".$db_prefix."Tags.id 
					JOIN ".$db_prefix."Categories on ".$db_prefix."Categories.id = ".$db_prefix."Category_Tag.category_id 
					WHERE ".$db_prefix."Software.id = " . $mysqli->real_escape_string($resource_id) ."
					GROUP BY ".$db_prefix."Categories.name";

				// Output results of query depending on type of page to view
				if ($res_info_result = $mysqli->query($res_info_query)) {
					while ($res_info_row = $res_info_result->fetch_assoc()) {
						echo "<div><strong>".$res_info_row['name']."</strong></div>";
						echo "<div>".$res_info_row['tags_list']."</div>";
					}
				}

				if ($url) {
					echo "<div><strong>Website</strong></div>";
					echo "<div><a href='".$url."' target='_blank'>".$url."</a></div>";
				}
				if ($comments) {
					echo "<div><strong>Notes</strong></div>";
					echo "<div>".$comments."</div>";
				}

				echo "</div></div>";
				++$counter;
			}
			echo "</div>";
		} else {
			echo "<p class='no-results'>No software matches your search</p>";
		}
		?>
	</div>
</div>
<div style="text-align: center;" >
	<a class="transparent-button-black" href="login.php">Login</a>
</div>
<?php 

include "components/close-actions.php";
include "components/footer.php";
