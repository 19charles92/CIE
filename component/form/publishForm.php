<?php

// 
// Charles Chavez
// publishForm.php
// 

// 
// This script will publish a form.
// 

// This is a standalone file. Cannot be included in other files.
include "../../config.php";

$userObj = hasAccess();

if( $userObj->access_level == -1 ){
	header("HTTP/1.0 403 Length Required");
	echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.";
	die();
}

// First check to see if the request has provided a form id
if( isset($_POST['formID']) ){
	$formID=$_POST['formID'];
} else {
	// No id found, kill script
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// Now we have to make sure the owner is the only one editing this information.
$accessForm = site_queryCIE("SELECT * FROM masterform WHERE form_id=? AND DANA=? ",[$formID,$userObj->DANA]);

if( empty($accessForm) ){
	header("HTTP/1.0 403 Length Required");
	echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.";
	die();
} else {
	// There is access, go ahead and edit this.
	site_queryCIE("UPDATE masterform SET published='y' WHERE form_id=?",[$formID]);
	echo "Form published.";
}

?>