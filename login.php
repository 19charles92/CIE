<?php

// login.php
// Charles Chavez
// 
// This script facilitates the login to the CIE system through the CAS system
// If a visitor is already logged in, it will redirect to the dashboard.
// 


include 'config.php';

if( hasAccess()->access_level != -1 ){
	header("Location: dashboard.php");
	die();
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title> Login </title>

	<script src="./js/jquery-1.9.1.js"></script>
	<script src="./js/jquery-ui-1.10.3.custom.min.js"></script>
	<script src="./js/bootstrap.min.js"></script>

	<link href="./css/bootstrap.min.css" rel="stylesheet" media="screen">
	
	<style type="text/css">

	/*Stylesheet for the login section of the web app*/

	body, html {
		background: #fff url('images/background.png');
	}

	.mainFrame {
		background: #fff;
		-webkit-box-shadow: 0px 0px 3px 1px rgba(0,0,0,.1);
 		box-shadow:         0px 0px 3px 1px rgba(0,0,0,.1);
 		margin: 0 40px;
 		position: relative;
	}

	.mainFrame .leftSection {
		position: absolute;
		top: 0;
		bottom: 0;
		left: 0;
		width: 200px;
		background: #FFE473;
		color: #000;
		font-size: 22px;
		text-align: right;
	}

	.mainFrame .rightSection {
		min-height: 150px;
		margin: 0 0 0 200px;
	}

	.mainFrame .body {
		padding: 30px 25px; text-align: center;
	}

	.leftSection .logo {
		font-size: 28px;
		background: #404D55;
		text-align: right;
		padding: 10px 15px;
		width: 200px;
		color: white;
	}

	</style>

</head>
<body>

<div style="max-width: 1000px; min-width: 800px; margin: 40px auto;">

	<div class="mainFrame">

		<div class="leftSection">
		<div class="logo">CIE<br>Dashboard</div>
		<div style="padding: 15px;">Login Screen</div>
		</div>

		<div class="rightSection">
			<div class="body">

			<?php

			$errorText = "";

			// Check for errors!
			// The switch state provides the proper error messages.
			if( isset($_GET['error']) ){
				switch ($_GET['error']) {
					case 'tnr':
						$errorText = "The ticket provided was not recognized. Try to log in again.";
						break;
					case 'dnt':
						$errorText = "You do not have access privileges to this resource.";
						break;
					default:
						$errorText = "";
						break;
				}
			}

			// Display the dissmisable alert if there is one.
			if( $errorText != "" ){
			?>
			<div class="alert alert-danger alert-dismissable">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<strong>Warning!</strong>
				<?php
				echo $errorText;
				?>
			</div>
			<?php } ?>

			<div class="alert alert-info" style="text-align: left;"> Before you can continue, you must verify your account with the NAU CAS system. </div>

				<a href="http://cas-test.nau.edu/cas/login?service=http://localhost/CIE/process_login.php" type="button" class="btn-lg btn-primary">Login with NAU CAS system</a>
				<br>
				<br>
			</div>
		</div>
	</div>

</div>

<script type="text/javascript">

var save;

function parse( In ){
	var parseXml = In;

	if (typeof window.DOMParser != "undefined") {
	    parseXml = function(xmlStr) {
	        return ( new window.DOMParser() ).parseFromString(xmlStr, "text/xml");
	    };
	} else if (typeof window.ActiveXObject != "undefined" &&
	       new window.ActiveXObject("Microsoft.XMLDOM")) {
	    parseXml = function(xmlStr) {
	        var xmlDoc = new window.ActiveXObject("Microsoft.XMLDOM");
	        xmlDoc.async = "false";
	        xmlDoc.loadXML(xmlStr);
	        return xmlDoc;
	    };
	} else {
	    throw new Error("No XML parser found");
	}
}
</script>

</body>
</html>