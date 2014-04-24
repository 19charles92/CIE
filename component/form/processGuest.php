<?php

// processGuest.php
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
// 6) Redirect user to formHTML
// 
// 

// Include System Configuration
include '../../config.php';

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

// createSession( $DANA )
// Takes in a DANA and creates a session for this DANA ID for this user.
function createSession( $DANA ){
	// Create the session ID
	$newSessionID = "";
	for ($i=0; $i < 20; $i++) { 
		$newSessionID .= substr("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", rand(0,61) ,1);
	}
	
	$sessionCheck = site_queryCIE("SELECT session_id FROM guest_session WHERE session_id=?",[$newSessionID]);
	
	// Make sure we have a unique session ID
	while( !empty($sessionCheck) ){
		$newSessionID = "";
		for ($i=0; $i < 20; $i++) { 
			$newSessionID .= substr("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", rand(0,61) ,1);
		}
		
		$sessionCheck = site_queryCIE("SELECT session_id FROM guest_session WHERE session_id=?",[$newSessionID]);	
	}

	// Add 2 hours to our current time
	$expires = time()+(2*60*60);

	// Now we have a unique new session id
	// Create our session with the provided $DANA
	site_queryCIE("INSERT INTO guest_session (session_id,DANA,expires) VALUES(?,?,?)",[$newSessionID,$DANA,$expires]);

	// Now create the browser session.
	$_SESSION['guest_session'] = $newSessionID;
}

// Start The Program
// First we're going to define some key settings
// We also need to pull out the form we're going to redirect to
if(isset($_GET['form'])){
	$form = $_GET['form'];
} else {
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request. Please close this window, refresh the page, and try again.";
	die();
}

$dashboardURL	= "http://localhost/CIE/component/form/formHTML.php?form=".$form;
$ticketURL		= "https://cas-test.nau.edu/cas/serviceValidate?service=http://localhost/CIE/component/form/processGuest.php?form=".$form;

// Make sure the user isn't already logged in...
if( isset($_SESSION['guest_session']) ){
	// They have a session, so check to see if that session is valid
	$checkSession = site_queryCIE("SELECT DANA FROM guest_session WHERE session_id=?",[$_SESSION['guest_session']]);

	if( !empty($checkSession) ){
		// If the session id does not return a blank DANA, then the user cannot log in
		header("Location: ".$dashboardURL);
		die();
	}
}

// Make sure there is a ticket
if( isset($_GET["ticket"]) ){
	$ticketParm	= $_GET["ticket"];
} else {
	// No ticket provided, kill the process.
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request. Please close this window, refresh the page, and try again.";
	die();
}


$DANA = parseTicket($ticketURL,$ticketParm);
$pass = true;

// If there is an error message, then redirect to login page with error message
if( $DANA == "tnr" ){
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request. Please close this window, refresh the page, and try again.";
	die();
	$pass = false;
}

// Make sure the first check was passed
if( $pass ){
	// If we recieve an error from our server, then redirect
	if( $DANA == "dnt" ){
		header("HTTP/1.0 411 Length Required");
		echo "<h1>411 Length Required</h1>Cannot Process Request. Please close this window, refresh the page, and try again.";
		die();
	} else {
		$pass = true;
	}
}

// Last, create our session for this user.
if( $pass ){
	// Create our session and then redirect to the dashboard.
	createSession( $DANA );
	header("Location: ".$dashboardURL."");
} else {
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request. Please close this window, refresh the page, and try again.";
	die();
}

?>