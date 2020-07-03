<?php
// Import components
include "components/pwd.php";
include "components/connection.php";
include "components/init-actions.php";

$title = "Login";
$description = "Sign in to edit and add records to the Design Hub Software Database.";

include "components/header.php";
?>
<div class="site-inner">
	<p><strong><em>Log in below using your Los Rios google account.</em></strong></p>
	<div class="resource-intro">
		<p>If you are not yet authorized, contact the Design Hub for access.</p>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="g-signin2" onclick="clickLogin()" data-onsuccess="onSignIn" data-theme="dark"></div>		
		</div>
	</div>
</div>
<script type="text/javascript">
	var clicked = false;

	function clickLogin() {
		clicked = true;
	}

	function onSignIn(googleUser) {
		if (clicked) {
			var profile = googleUser.getBasicProfile();
			var id_token = googleUser.getAuthResponse().id_token;

			var xhr = new XMLHttpRequest();
			xhr.open('POST', 'http://power.arc.losrios.edu/~designhub/software_db/auth.php');
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.send('idtoken=' + id_token);
			xhr.onload = function() {
				if (xhr.responseText == 'Invalid') {
					var auth2 = gapi.auth2.getAuthInstance();
					auth2.signOut();
				} else {
					//console.log(xhr.responseText);
					document.location.reload();
				}
			};
		} else {
			var auth2 = gapi.auth2.getAuthInstance();
			auth2.signOut();
		}
	}
</script>

<?php 

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
	header("Location: http://power.arc.losrios.edu/~designhub/software_db/view.php");
	exit();
}
?>
<?php 

include "components/close-actions.php";
include "components/footer.php";