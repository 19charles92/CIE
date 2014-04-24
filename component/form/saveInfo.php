<?php

// 
// saveInfo.php 
// Charles Chavez
// 
// This script will take in the information for a specific form and insert it into the database.
// 

// This is a standalone file. Cannot be included in other files.
include "../../config.php";

// We need to make sure that this form exists.
if( isset($_GET['formID']) ){
	$formID = $_GET['formID'];
} else {
	// No form was provided...
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// Let's make sure this form exists.
$formData = site_queryCIE("SELECT * FROM ".$formID."_meta","query");
if( is_string($formData) ){
	// No table found...
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// Now that we have our forms, we're going to need some information
$publishedStatus	= site_queryCIE("SELECT published FROM masterform WHERE form_id=?",[$formID])[0]->published;
$metaInfo			= site_queryCIE("SELECT * FROM ".$formID."_meta","query");
$getData			= [];

// If the form hasn't been published, then don't accept any data.
if( $publishedStatus == "n" ){
	echo '<h1>Sorry, this form cannot accept any data at this time.</h1>';
	die();
}

// We need to take the information from each element from the form and store it into our data array.
// We are going to assume that if a element is missing, that it is an empty string.
for ($i=1; $i <= sizeof($metaInfo) ; $i++) { 

	// Remember, metaInfo is indexed at 0, while element_ starts at 1....

	// We have our elements ordered
	// All the elements follow a specific order except the checkboxes, so make sure that if the element is a checkbox, that we
	// add the "[]" to the end

	// Go in order...
	if( isset($_GET['element_'.$i]) ){
			if( $metaInfo[($i-1)]->element_type == "checkbox" ){
				$getData[] = implode(", ",$_GET['element_'.$i] );
			} else {
				// It exists, so save it to our array
				$getData[] = $_GET['element_'.$i];
			}
	} else {
		$getData[] = '';
	}
}

// Create our query string while we check!
$queryString = "INSERT INTO ".$formID." VALUES(";

// From here, we want to make sure that any fields that are required are filled out, if they're not, then deny the request.
foreach ($metaInfo as $key => $formElement) {
	// Check to see if a required field is empty
	if( $formElement->element_required == "yes"){
		// This element is required., so make sure the getData at the index is correct...
		if( trim($getData[$key]) == '' ){
			echo 'Field  at position '.$key.' is required. Script Stopped.';
			die();
		} else {
			$queryString .= "?, ";
		}
	} else {
		$queryString .= "?, ";
	}
}

// We passed, insert our data!
$queryString = substr($queryString, 0, -2).")";

site_queryCIE($queryString,$getData);

header("Location: formSubmitted.php");

?>