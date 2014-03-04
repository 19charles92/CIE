<?php

// 
// formHTML.php 
// Charles Chavez
// 
// This form will convert a database form into useable HTML.
// 

// This is a standalone file. Cannot be included in other files.
include "../../config.php";

// $userObj = hasAccess();

// // Make sure the user is logged in
// if( $userObj->access_level == -1 ){
// 	header("HTTP/1.0 403 Length Required");
// 	echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.";
// 	die();
// }

// Because this file is providing the HTML, we need to provide the proper headings.
// We can turn off the HTML header by providing the argument for iframe=false

$iframe = "true";

if( isset( $_GET['iframe'] ) ){
	if( $_GET['iframe'] == "false" ){
		$iframe = $_GET['iframe'];
	}
}

// If the iframe is requested, then show the headers
if( $iframe == "true" ){

?>

<!DOCTYPE html>
<html>
<head>
	<title> Dashboard </title>

	<script src="http://localhost/CIE/js/jquery-1.9.1.js"></script>
	<script src="http://localhost/CIE/js/jquery-ui-1.10.3.custom.min.js"></script>
	<script src="http://localhost/CIE/js/bootstrap.min.js"></script>

	<link href="http://localhost/CIE/css/bootstrap.min.css" rel="stylesheet" media="screen">

	<style type="text/css">
	HTML, Body {
		background: transparent;
	}
	</style>

</head>
<body>

<?php

}

// Now, let's select the form we need.
$selectedForm = "";

if( isset($_GET['form']) ){
	// Assign the selected form to our variable.
	$selectedForm = trim($_GET['form']);
} else {
	// No form was provided...
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// Now, let's query the database for this form...
$formData = site_queryCIE("SELECT * FROM ".$selectedForm."_meta","query");

if( is_string($formData) ){
	// No table found...
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

// Get the information about the form...
$formInfo = site_queryCIE("SELECT form_description FROM masterform WHERE form_id=?",[$selectedForm]);

// Let's output the form info...
?>

<p> <?php echo $formInfo[0]->form_description ?> </p>

<div style="padding: 0 20px;">
<?php

echo '<form method="get" action="?" class="form-horizontal">';

foreach ($formData as $elementForm ) {

	// Variable that will display the input for the elementFor
	$formInput = "";
	$formClass = "";

	// Check to see what type of element we have...
	if( $elementForm->element_type == "text_field" ){
		// Let's display a text field
		$formInput = '<input class="form-control" type="text" name="element_'.$elementForm->element_id.'" id="element_'.$elementForm->element_id.'" placeholder="'.$elementForm->element_description.'" >';

	} elseif ( $elementForm->element_type == "text_area" ) {
		// Let's display a text area
		$formInput = '<textarea class="form-control" name="element_'.$elementForm->element_id.'" id="element_'.$elementForm->element_id.'" placeholder="'.$elementForm->element_description.'" rows="3"></textarea>';
		
	} elseif( $elementForm->element_type == "radio" ){
		// Provide the correct class form
		$formClass = 'class="radio"';

		// String we're going to build up
		$formInput = '<div class="radio">';

		// We have to parse out the information from the JSON object which contains the options
		$options = json_decode( $elementForm->element_options );
		foreach( $options as $option ){
			$formInput .= '<label><input type="radio" name="element_'.$elementForm->element_id.'" value="'.$option.'" >'.$option.'</label><br>';
		}

		// Add the help text
		$formInput .= "</div><span class=\"help-block\">".$elementForm->element_description."</span>";

	} elseif( $elementForm->element_type == "checkbox" ){
		// Provide the correct class form
		$formClass = 'class="checkbox"';

		// String we're going to build up
		$formInput = '<div class="checkbox">';

		// We have to parse out the information from the JSON object which contains the options
		$options = json_decode( $elementForm->element_options );
		foreach( $options as $option ){
			$formInput .= '<label><input type="checkbox" name="element_'.$elementForm->element_id.'" value="'.$option.'" >'.$option.'</label><br>';
		}

		// Add the help text
		$formInput .= "</div><span class=\"help-block\">".$elementForm->element_description."</span>";
	}

	?>
	<div class="form-group">
		<label class="col-xs-3 control-label" style="text-transform:capitalize;" for="<?php echo 'element_'.$elementForm->element_id; ?>"><?php echo str_replace("_", " ", $elementForm->element_name) ?></label>
		<div class="col-xs-9"> <?php echo $formInput ?> </div>
	</div>
	<?php
}

?>
</div>

<div class="form-group">
	<div class="col-xs-offset-3 col-sm-9"><input class="btn btn-success" type="submit" value="submit"></div>
</div>

</form>

<?php

// Finish the iframe section
if( $iframe == "true" ){
?>

</body>
</html>
<?php } ?>