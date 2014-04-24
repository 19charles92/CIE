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

// We also need to check to see if this form is restricted.
// If it is, just return an error and kill the script
$restrictedCheck = site_queryCIE("SELECT restriction FROM masterform WHERE form_id=?",[$formID]);
$restrictedCheck = $restrictedCheck[0]->restriction;
$authentication_dana = "";

// If the restriction column isn't empty, then let's see if the user is allowed to submit data.
if( !empty($restrictedCheck) ){
	// In here, check to see if the user is logged in.
	if( isset( $_SESSION["guest_session"] ) ){
		// They have a session, so check to see if that session is valid
		$checkSession = site_queryCIE("SELECT DANA, expires FROM guest_session WHERE session_id=?",[$_SESSION['guest_session']]);

		if( empty($checkSession) ){
			// If the session id does not return a blank DANA, then the user cannot view this form.
			?>
			<h2>This form requires you to sign in through the CAS system.</h2>
			<?php
			die();
		} else {
			// Now we have to check to see if the user is in the approved list.
			// var_dump($restrictedCheck);

			// Save the user's DANA so it can be used later...
			$sessionExpires = $checkSession[0]->expires;
			$authentication_dana = $checkSession[0]->DANA;

			// Check to see whether this form only allows specific number of users.
			if( $restrictedCheck == "[]" ){
				// This form does not require a specific DANA to view the form. Continue with processing.
			} else {
				// Only specific DANAs are allowed.
				// First let's create an array of the DANAs allowed
				$restrictedList = explode(",", str_replace("[", "", str_replace("]","", strtolower($restrictedCheck) ) ) );
				if( in_array(strtolower($authentication_dana), $restrictedList) ){
					// We're good!
				} else {
					?>
					<h2>Sorry, you don't have access to view this form.</h2>
					<?php
					die();
				}
			}
		}
	} else {
		// They're not logged in. Show them a link that will log them in through the CAS system and prevent them from viewing the rest of the form.
		?>
		<h2>This form requires you to sign in through the CAS system.</h2>
		<?php
		die();
	}
}

// We need to take the information from each element from the form and store it into our data array.
// We are going to assume that if a element is missing, that it is an empty string.
for ($i=1; $i <= sizeof($metaInfo) ; $i++) { 

	// Remember, metaInfo is indexed at 0, while element_ starts at 1....

	// We have our elements ordered
	// All the elements follow a specific order except the checkboxes, so make sure that if the element is a checkbox, that we
	// add the "[]" to the end

	// Skip the _::DANA value
	if( $metaInfo[($i-1)]->element_name == "_::DANA" ){
		continue;
	}

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

	// Skip the _::DANA value
	if( $formElement->element_name == "_::DANA" ){
		continue;
	}

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

// If the form is restricted, then add the DANA id to the form submission
if( $authentication_dana !== "" ){
	// There is a dana, so add it to the query and the get data
	$queryString .= "?, ";
	$getData[] = $authentication_dana;
}

// We passed, insert our data!
$queryString = substr($queryString, 0, -2).")";

site_queryCIE($queryString,$getData);

header("Location: formSubmitted.php");
?>