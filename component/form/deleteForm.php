<?php

// 
// Charles Chavez
// deleteForm.php
// 

// 
// This script either deletes or unlinks a form.
// How this is done:
// 		1) When a delete command is received, this script checks the published attribute to decide what to do next.
// 		
// 			1.1) If the form is not published, we have to delete the following:
// 				* form_ID
// 				* form_ID_meta
// 				* form_ID from masterform
// 
// 			1.2) If the form is published, we have to edit the record in the masterform
// 
// 

// This is a standalone file. Cannot be included in other files.
include "../../config.php";

$userObj = hasAccess();

if( $userObj->access_level == -1 ){
	header("HTTP/1.0 403 Length Required");
	echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.";
	die();
}

// Now, check to see what form ID has been provided
if( isset($_POST['formID']) ){
	// Assign the selected form to our variable.
	$selectedForm = trim($_POST['formID']);
} else {
	// No form was provided...
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// Now, let's check the published status of this form...
// Get the information about the form
$formInfo = site_queryCIE("SELECT * FROM masterform WHERE form_id=?",[$selectedForm]);

// But first we need to make sure that the user making this request is the author...
if( $userObj->DANA != $formInfo[0]->DANA ){
	// The user doesn't have the proper rights.
	header("HTTP/1.0 403 Forbidden");
	echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.";
	die();
}

if( $formInfo[0]->published == "y"){
	// This form has been published, so edit the masterform entry.
	site_queryCIE("UPDATE masterform SET unlinked='y' WHERE form_id=?",[$selectedForm]);
	echo 'Form unlinked';
} else {
	// This form hasn't been published, so go ahead and delete all the data for it.
	// Let's drop 'em!
	$queryString = "DROP TABLE ".$selectedForm.", ".$selectedForm."_meta";
	site_queryCIE($queryString,"query");

	// We also have to drop the entry in the masterform
	site_queryCIE("DELETE FROM masterform WHERE form_id=? ",[$selectedForm]);
	echo 'Form deleted';
}

?>