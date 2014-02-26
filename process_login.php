<?php

// process_login.php
// Charles Chavez
// 
// This script takes in a ticket for the CAS system and tries to authenticate it. Once a ticket is authenticated
// it will take one of three options:
// 		* Create a valid session for user and redirect to dashboard.
// 		* Redirect to login with invalid permission error.
// 		* Redirect to login with improper ticket error.
// 
// Program Process:
// 
// 1) Retrieve ticket from URL bar
// 		1.1) If NO ticket, then redirect to login without an error.
// 2) Parse ticket.
// 3) Take result from parse and take one of two options.
// 		3.1) If parse returns fail result, then redirect to login with an invalid ticket error.
// 		3.2) If parse returns a DANA ID result, then go on to next step.
// 4) Parse DANA ID - Check against database to authenticate DANA
// 		4.1) The DANA is NOT in our database, redirect to login with improper access level error.
// 		4.2) The DANA is in our database, go on to next step
// 5) Create a new session with the provided DANA ID
// 6) Redirect user to dashboard
// 
// 

// Include System Configuration
include 'config.php';

// parseTicket( $url, $ticket )
// The CAS System uses an XML file that has namespaces associated with it.
// Namespaces complicate the parsing of the XML file
// The function parseTicket returns either the string "ticket <ticket> is not valid" or a DANA ID
function parseTicket( $url, $ticket ){
	// Get the string from the CAS System
	$rawFeed = file_get_contents($url."&ticket=".$ticket);
	// This removes any extra encoding from the feed
	$rawFeed = substr($rawFeed,0,strpos($rawFeed,"</cas:serviceResponse>"))."</cas:serviceResponse>";

	$rawFeed = str_replace("cas:", "", $rawFeed);
	// Convert our feed into an XML element, so we can grab the information much more easily.
	$xml = new SimpleXMLElement($rawFeed);
	$rawFeed = $xml->asXML();

	if( strpos($rawFeed,"not recognized") ){
		// tnr = ticket not recognized
		return 'tnr';
	} else {
		// Return ID
		return $xml->authenticationSuccess->user;
	}
}

// checkDANA( $DANA )
// This method takes a DANA ID and checks it against our database.
function checkDANA( $DANA ){
	$DANACheck = site_queryCIE("select DANA from user_level WHERE DANA=?",[$DANA]);

	if( empty($DANACheck) ){
		// The DANA id isn't in the table!
		// dnt = dana not in table
		return 'dnt';
	} else {
		return $DANACheck[0]->DANA;
	}

}

// createSession( $DANA )
// Takes in a DANA and creates a session for this DANA ID for this user.
function createSession( $DANA ){
	// Create the session ID
	$newSessionID = "";
	for ($i=0; $i < 10; $i++) { 
		$newSessionID .= substr("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", rand(0,61) ,1);
	}
	
	$sessionCheck = site_queryCIE("select session_id from session WHERE session_id=?",[$newSessionID]);
	
	// Make sure we have a unique session ID
	while( !empty($sessionCheck) ){
		$newSessionID = "";
		for ($i=0; $i < 10; $i++) { 
			$newSessionID .= substr("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", rand(0,61) ,1);
		}
		
		$sessionCheck = site_queryCIE("select session_id from session WHERE session_id=?",[$newSessionID]);	
	}

	// Now we have a unique new session id
	// Create our session with the provided $DANA
	site_queryCIE("INSERT INTO session (session_id,last_action,DANA) VALUES(?,?,?)",[$newSessionID,time(),$DANA]);

	// Now create the browser session.
	$_SESSION['CIE_session'] = $newSessionID;
}

// Start The Program
// First we're going to define some key settings
$loginURL		= "login.php";
$dashboardURL	= "dashboard.php";
$ticketURL		= "https://cas-test.nau.edu/cas/serviceValidate?service=http://localhost/CIE/process_login.php";

// Make sure there is a ticket
if( isset($_GET["ticket"]) ){
	$ticketParm	= $_GET["ticket"];
} else {
	// No ticket provided, redirect to login page.
	header("Location: ".$loginURL);
}


$DANA = parseTicket($ticketURL,$ticketParm);
$pass = true;

// If there is an error message, then redirect to login page with error message
if( $DANA == "tnr" ){
	header("Location: ".$loginURL."?error=tnr");
	$pass = false;
}

// Now let's see if that DANA ID is in our access_level
$DANA = checkDANA($DANA);

// Make sure the first check was passed
if( $pass ){
	// If we recieve an error from our server, then redirect
	if( $DANA == "dnt" ){
		header("Location: ".$loginURL."?error=dnt");
		$pass = false;
	} else {
		$pass = true;
	}
}

// Last, create our session for this user.
if( $pass ){
	// Create our session and then redirect to the dashboard.
	createSession( $DANA );
	header("Location: ".$dashboardURL."");
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title> Processing </title>

	<script src="./JavaScript/jquery-ui-1.10.3.custom/js/jquery-1.9.1.js"></script>
	<script src="./JavaScript/jquery-ui-1.10.3.custom/js/jquery-ui-1.10.3.custom.min.js"></script>
	<link href="./JavaScript/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
	<script src="./JavaScript/bootstrap/js/bootstrap.min.js"></script>

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
		padding: 10px 14px;
		background: #FFE473;
		color: #000;
		font-size: 28px;
		text-align: right;
	}

	.mainFrame .rightSection {
		min-height: 150px;
		margin: 0 0 0 200px;
	}

	.mainFrame .body {
		padding: 30px 25px; text-align: center;
	}

	</style>

</head>
<body>

<div style="max-width: 1000px; min-width: 800px; margin: 40px auto;">

	<div class="mainFrame">

		<div class="leftSection"> Authentication Screen </div>

		<div class="rightSection">
			<div class="body">
				<div class="alert alert-warning">Verying your account</div>
				<img src="images/loading.gif">
			</div>
		</div>
	</div>

</div>

</body>
</html>