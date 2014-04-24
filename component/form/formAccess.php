<?php

// 
// formAccess.php
// 
// This file manages form access to a specific form.
// 
// It can perform the following actions:
// 	* Toggle a form between restricted and not restricted
// 	* Update the restriction field of a specific form
// 

// This is a standalone file. Cannot be included in other files.
include "../../config.php";

// ============ Section ============
// Make sure that the user toggling this function is logged in.
$userObj = hasAccess();

if( $userObj->access_level == -1 ){
	header("HTTP/1.0 403 Forbidden");
	echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.";
	die();
}

// ============ Section ============
// Identify the type of request
$requestType = "";

if( isset($_GET['requestType']) ){
	$requestType = $_GET['requestType'];
} else {
	// We need to know the request type, so if there is not one provided, kill the script.
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request.";
	die();
}

// ============ Section ============
// We need to identify what specific form we are going to update.
$requestForm = "";

if( isset($_GET['requestForm']) ){
	$requestForm = $_GET['requestForm'];
} else {
	// We need to know the request form, so if there is not one provided, kill the script.
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request.";
	die();
}

// ============ Section ============
// Make sure that the selected form exists.
$requestForm = site_queryCIE("SELECT form_id FROM masterform WHERE form_id=?",[$requestForm]);

// Expected result from $selectedForm is an array with an object that contains form_id
if( empty($requestForm) ){
	// There is no form by that ID. Kill the script.
	header("HTTP/1.0 400 Bad Request");
	echo "<h1>400 Bad Request</h1>Cannot complete this request.";
	die();
} else {
	// The form exists, so re-assign the result to the $selectedForm variable.
	// This is to make this variable contain the form name instead an array with an object...
	$requestForm = $requestForm[0]->form_id;
}

// ============ Section ============
// If the request type is to "toggle", then we want to change whether the form is restricted or not.
// Process:
// 	* Check to see what the current status of the form is [Restricted or Not restricted]
// 		* If the form is restricted, then remove the _::DANA element from the requestedForm and requestForm_meta, and empty out the restriction column from the masterform at form_id=requestedForm.
// 			* Restricted Form => Not Restricted Form
// 		* If the form is NOT restricted, then add the _::DANA element from the requestedForm and requestForm_meta, and add a blank array to the restriction column from the masterform at form_id=requestedForm.
// 			* Not Restricted Form => Restricted Form
// 
if( $requestType == "toggle" ){

	// Before we continue, we have to make sure that this form has NOT been published.
	$formPublishedStatus = site_queryCIE("SELECT published FROM masterform WHERE form_id=?",[$requestForm]);

	if( $formPublishedStatus[0]->published == "y" ){
		// We can't change the status of the form access after a form has been published.
		header("HTTP/1.0 400 Bad Request");
		echo "<h1>400 Bad Request</h1>Cannot complete this request.";
		die();
	}

	// Let's check what the current status of the selected form is.
	$formCurrentStatus = site_queryCIE("SELECT restriction FROM masterform WHERE form_id=?",[$requestForm]);
	
	// There are two possibilities for $formCurrentStatus:
	// 	* Empty, which means that this form is not restricted
	// 	* Not empty, which means that this form is restricted
	if( $formCurrentStatus[0]->restriction == "" ){
		// Toggle form to not restricted to restricted

		// Add empty array, [], to masterform
		site_queryCIE("UPDATE masterform SET restriction=? WHERE form_id=?",["[]",$requestForm]);

		// Add the _::DANA element to $requestForm and $requestForm_meta
		$tableQuery = "ALTER TABLE ".$requestForm." ADD `_::DANA` varchar(10)";
		site_queryCIE($tableQuery,"query");

		$tableQuery = "INSERT INTO ".$requestForm."_meta (element_name,element_type,element_description,element_required,element_options) VALUES(?,'','','','')";
		site_queryCIE($tableQuery,["_::DANA"]);

		echo "Form changed to restricted";
	} else {
		// Toggle form to not restricted to restricted

		// Remove anything in the restriction column
		site_queryCIE("UPDATE masterform SET restriction=? WHERE form_id=?",["",$requestForm]);

		// Add the _::DANA element to $requestForm and $requestForm_meta
		$tableQuery = "ALTER TABLE ".$requestForm." DROP COLUMN `_::DANA`";
		site_queryCIE($tableQuery,"query");

		$tableQuery = "DELETE FROM ".$requestForm."_meta WHERE element_name=?";
		site_queryCIE($tableQuery,["_::DANA"]);

		// Restart the auto increment count
		// To do this, we just need to count how many records there are in the table_meta
		$newSeed = site_queryCIE("SELECT COUNT(element_id) FROM ".$requestForm."_meta","query");
		$toAccess = "COUNT(element_id)";
		$newSeed = intval( $newSeed[0]->$toAccess );
		
		site_queryCIE("ALTER TABLE `".$requestForm."_meta` auto_increment = ".$newSeed."","query");

		echo "Form changed to not restricted";
	}

}

// ============ Section ============
// If the request type is to "modify", then we want to modify the user access list of the form.
// Process:
// 	* Take the newList parameter and insert it over the existing list in masterform
if( $requestType == "modify" ){

	// The restriction column can only be changed if the current form has form access enabled.
	// To determine this, we can say that if the form currently has an empty restriction column, then it does not have form access enabled.
	// Thus, we should cancel the operation.
	$formCurrentStatus = site_queryCIE("SELECT restriction FROM masterform WHERE form_id=?",[$requestForm]);

	if( $formCurrentStatus[0]->restriction == "" ){
		// The form does NOT have form access enabled, kill the script.
		header("HTTP/1.0 400 Bad Request");
		echo "<h1>400 Bad Request</h1>Cannot complete this request.";
		die();
	}

	// Identify the new list of names
	if( isset($_GET['newList']) ){
		// Save the parameter to a variable
		$newList = $_GET['newList'];
	} else {
		// No new list provided, kill the script
		header("HTTP/1.0 411 Length Required");
		echo "<h1>411 Length Required</h1>Cannot Process Request.";
		die();
	}

	// Now we have to parse our newList
	// The expected format for our list is:
	// [DANA,...,DANA]

	// Let's split up our string into an array using the "," delimiter
	$newList = explode(",", $newList);

	// Now let's clean up our new array.
	// We are going to add it to a string that will contain all the cleaned up DANAs
	foreach ($newList as $key => $DANA) {

		// Trim up each dana
		$newList[$key] = trim($DANA);

		// Remove any entry that is empty
		if($newList[$key] == ""){
			unset($newList[$key]);
		}
	}

	// Our finished newList that is properly formatted.
	$newList = "[".implode(",", $newList)."]";

	// Update our restriction field for our selected form
	site_queryCIE("UPDATE masterform SET restriction=? WHERE form_id=?",[$newList,$requestForm]);

	echo 'List updated for form.';

}

?>