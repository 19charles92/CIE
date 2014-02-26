<?php

// This is a standalone file. Cannot be included in other files.
include "../../config.php";

$userObj = hasAccess();

if( $userObj->access_level == -1 || $userObj->access_level > 1 ){
	header("HTTP/1.0 403 Length Required");
	echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.";
	die();
}

// Now, grab the data
if( isset($_POST['DANA']) && isset($_POST['AL']) ){
	$DANA = trim($_POST['DANA']);
	$AL = trim($_POST['AL']);
} else {
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// Let's check the info and see if there is anything missing.
if( $DANA == "" || $AL == "" ){
	// There is a variable missing, let's throw an error.
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// We're all set, let's do some database work!
$isUserIn = site_queryCIE("SELECT DANA FROM user_level WHERE DANA=?",[$DANA]);

if( empty($isUserIn) ){
	// The user doesn't exists, add them to the table
	site_queryCIE("INSERT INTO user_level (DANA,access_level) VALUES(?,?)",[$DANA,$AL]);
	echo 'user added';
} else {
	// User already exists
	echo 'user exists';
}

?>