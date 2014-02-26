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
if( isset($_POST['DANA']) && isset($_POST['AL']) && isset($_POST['action']) ){
	$action = trim($_POST['action']);
	$DANA = trim($_POST['DANA']);
	$AL = trim($_POST['AL']);
} else {
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// Let's check the info and see if there is anything missing.
if( $DANA == "" || $AL == "" || $action == "" ){
	// There is a variable missing, let's throw an error.
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// We're all set, let's do some database work!
// Depending on the action we are either going to delete a user or change the AL

// First we need to make sure that the user isn't trying to change their own permissions
if( $userObj->DANA == $DANA ){
	header("HTTP/1.0 403 Length Required");
	echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.<br>Additional: Cannot edit same user.";
	die();
}

if( $action == 'delete' ){
	// Let's remove this user.
	site_queryCIE("DELETE FROM user_level WHERE DANA=? ",[$DANA]);
	echo 'user deleted';
	die();
} else {
	// Update this user's access level
	site_queryCIE("UPDATE user_level SET access_level=? WHERE DANA=? ",[$AL,$DANA]);
	echo 'access level updated';
	die();
}

?>