<?php 

// 
// Charles Chavez
// processForm.php
// 

// 
// This file process the data that is provided by the form GUI. It will take in the JavaScript Object and create
// two different tables in the database.
// 
// It will also update a form if the update parameter is set.
// 

// This is a standalone file. Cannot be included in other files.
include "../../config.php";

$userObj = hasAccess();

if( $userObj->access_level == -1 ){
	header("HTTP/1.0 403 Forbidden");
	echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.";
	die();
}

// First read the information from the request.
if( isset($_POST['dataObject']) && isset($_POST["form_name"]) && isset($_POST["form_description"]) ){
	$formObject = $_POST['dataObject'];
	$formName = $_POST['form_name'];
	$formDescription = $_POST['form_description'];

} else {
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// Let's check the form info and see if there is anything missing.
if( trim($formName) == "" || trim($formDescription) == "" ){
	// There is a variable missing, let's throw an error.
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// Now, let's decode this data.
$formObject = json_decode($formObject,true);

// Let's check to make sure the JSON is correct.
if( is_null( $formObject ) ){
	// If it's null, kill the processing right here.
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// This section will check to see if there is any missing data. If we encounter any problem, we will immediately
// cancel the request.
$hitError = false;
foreach ($formObject as $element ) {
	// Let's check the name.
	if( trim($element['element_name']) == "" ){
		$hitError = true;
	}

	// Let's check the description.
	if( trim($element['element_description']) == "" ){
		$hitError = true;
	}

	// Let's check the required status.
	if( trim($element['element_required']) == "" ){
		$hitError = true;
	}

	// Now, if this element type has options, then make sure those aren't empty and that there is at least 1.
	if( $element['element_type'] == "radio" || $element['element_type'] == "checkbox" || $element['element_type'] == "dropdown" ){
		// Make sure the size is > 0
		if( sizeof($element['element_option']) < 1 ){
			$hitError = true;
		}

		// Now check all the options!
		foreach ($element['element_option'] as $option) {
			if( trim($option) == "" ){
				$hitError = true;
				break; # End Checking all the options.
			} # End If
		} #End Option Foreach
	}

	// Now, if the flag has been tripped, break out of loop!
	if( $hitError ){
		break;
	}
}

// Now, if the information has not been sent correctly, throw error.
if( $hitError ){
	header("HTTP/1.0 400 Bad Request");
	echo "<h1>400 Bad Request</h1>Cannot Process Request";
	die();
}

// If we've made it this far, we should save the information the user has spent so much time making.
// We will be creating two different tables. 1) Table that will store the data of users. 2) Table that will store the settings for the form table.

// First we need to see if the request is to update the form instead of creating a new one.
if( isset( $_POST['update'] ) ){
	// It's set, so let's make sure it's toggled to true.
	if( $_POST['update'] == "true" ){
		// It is, so we need to set our update task to true.
		// Also, we need to grab the id of the form that we're going update.
		// If there isn't one provided, then return an error and halt the script's execution.
		$updateTask = "true";
		if( isset( $_POST['form_id'] ) ){
			// There is a form id set!
			$formToUpdate = $_POST['form_id'];
		} else {
			// There is no form provided, go ahead and kill the process.
			header("HTTP/1.0 411 Length Required");
			echo "<h1>411 Length Required</h1>Cannot Process Request";
			die();
		}
	}
} else {
	$updateTask = "";
}

$restrictedCondition = "";

// If there is an update available, make sure the form exists
// If it does exists, then drop both the current form AND the meta form
if( $updateTask == "true" ){
	// Let's check to see if the form exists
	$updateTableCheck = site_queryCIE("SELECT * FROM masterform WHERE form_id=?",[$formToUpdate]);
	if( is_string( $updateTableCheck ) ){
		header("HTTP/1.0 400 Bad Request");
		echo "<h1>400 Bad Request</h1>Cannot Process Request";
		die();
	} else {
		// We did find a table
		// First we need to make sure that the table isn't published. If it is, then kill the script here.
		if( $updateTableCheck[0]->published == "y" ){
			// Can't edit a published table
			header("HTTP/1.0 400 Bad Request");
			echo "<h1>400 Bad Request</h1>Cannot Process Request";
			die();
		} else {

			if( $updateTableCheck[0]->DANA == $userObj->DANA ){
				// Only the owner can edit a file

				// We need to make sure that table isn't restricted. If it is, save the current list and notify the program.
				$restrictedCheck = site_queryCIE("SELECT restriction FROM masterform WHERE form_id=?",[$formToUpdate]);
				$restrictedCheck = $restrictedCheck[0]->restriction;
				

				// If the restriction column isn't empty, then save whatever is in there.
				if( !empty($restrictedCheck) ){
					$restrictedConditiones = $restrictedCheck;
				}

				// Let's drop 'em!
				$queryString = "DROP TABLE ".$formToUpdate.", ".$formToUpdate."_meta";
				site_queryCIE($queryString,"query");

				// We also have to drop the entry in the masterform
				site_queryCIE("DELETE FROM masterform WHERE form_id=? ",[$formToUpdate]);
			} else {
				header("HTTP/1.0 403 Forbidden");
				echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.";
				die();
			}
		}
	}
}

// =========
// ========= Create table for storage.
// =========

// Find correct table name.
// Schema for table name: form_#
// Process: Query the bridge table master form and sort by form id column by descending order to find the latest form id. Then add one to that form.
// 			Will also only display 1 item because of out limit clause.
$latestFormID = site_queryCIE("SELECT form_id FROM masterform ORDER BY form_id DESC LIMIT 0,1",[]);

// Now, if we didn't get a result, we're going to use the index 0.
if( empty( $latestFormID ) ){
	// Use the form 0
	$formTableName = "form_0";
} else {
	// We are going to parse the latest table's name for an index.
	$temp_latestName = explode("_",$latestFormID[0]->form_id);
	$temp_latestName = intval($temp_latestName[1]);
	$temp_latestName++;

	$formTableName = "form_".$temp_latestName;
}

// But wait, if we're updating, just override the name we found with our current form selected.
if( $updateTask == "true" ){
	$formTableName = $formToUpdate;
}

// Now the new name is in $formTableName

// =========
// ========= Create meta table.
// =========

// Creates standard meta table.
$formTableMetaSQL = "CREATE TABLE ".$formTableName."_meta ( element_id int NOT NULL AUTO_INCREMENT, element_name VARCHAR(250), element_type VARCHAR(250), element_description VARCHAR(250), element_required VARCHAR(250), element_options VARCHAR(10000), PRIMARY KEY (element_id) );";
site_queryCIE($formTableMetaSQL,"query");

// Setup the layout of the info table.
// For each element that we have, we will lower case each item, replace spaces with underscores, and assign a type based on the element type.
// We also are going to add metadata about the information table to our meta table.
// text field: VARCHAR(250)
// text box: TEXT
// checkbox: VARCHAR(250)
// radio button: VARCHAR(250)
$formTableLayout = "(";

// This loop does two things at once:
//	* Creates the MySQL query that will create the information table
// 	* Inserts metadata of each element into the meta table.
foreach ($formObject as $element ){
	// Format name
	$formTableLayout .= "`".str_replace(" ", "_", strtolower(trim($element["element_name"])))."` ";

	// META: Create Query
	$metaQuery = "INSERT INTO ".$formTableName."_meta (element_name, element_type, element_description, element_required, element_options) VALUES('".str_replace(" ", "_", strtolower(trim($element["element_name"])))."', '".$element["element_type"]."', '".$element['element_description']."', '".$element['element_required']."'";

	// Add the proper type based on element type
	switch ($element["element_type"]) {
		case 'radio':
			$formTableLayout .= "VARCHAR(250), ";
			// Save the JSON object of the options.
			$metaQuery .= ", '".json_encode($element['element_option'],JSON_FORCE_OBJECT)."')";
			break;

		case 'checkbox':
			$formTableLayout .= "VARCHAR(250), ";
			$metaQuery .= ", '".json_encode($element['element_option'],JSON_FORCE_OBJECT)."')";
			break;

		case 'text_field':
			$formTableLayout .= "VARCHAR(250), ";
			$metaQuery .= ",'')";
			break;

		case 'text_area':
			$formTableLayout .= "TEXT, ";
			$metaQuery .= ",'')";
			break;
		
		default:
			# code...
			break;
	}

	// Insert data into meta table
	site_queryCIE($metaQuery,"query");

}

// We have to remove that last ", " part AND add the ending ");"
$formTableLayout = substr( $formTableLayout, 0, strlen( $formTableLayout )-2 ).")";

// Lastly, we're going to add everything together for table containing the data.
$formTableSQL = "CREATE TABLE ".$formTableName." ".$formTableLayout;

// Finish constructing our information table.
$createFormTable = site_queryCIE($formTableSQL,"query");

// =========
// ========= Create entry in masterform
// =========

$DANA = $userObj->DANA;

// Links both info table and meta table together.
// Also, create a temp name for this table.
$masterformSQL = "INSERT INTO masterform (DANA, form_id, created, form_id_meta, published,form_name,form_description) VALUES('".$DANA."','".$formTableName."','".time()."','".$formTableName."_meta','n','".$formName."','".$formDescription."')";
site_queryCIE($masterformSQL,"query");

// Now we have to make sure we insert the data for the restriction if there one...
// if( $restrictedCheck !== "" ){
// 	exec("php ")
// }

// All information is recorder, return the form ID.
header("HTTP/1.0 201 Created");
echo $formTableName;

?>