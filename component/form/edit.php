<?php

class Dashboard
{
	
	public $location = "form";

	// Returns the title we want to show on the dashboard
	// Format will append whatever is in this to:
	// 		Dashboard - title()
	function title(){
		return 'View Form';
	}

	// Returns the current location of the file
	function location(){
		return $this->location;
	}

	// Returns an array with stylesheets to be loaded into the dashboard
	function style(){
		return ['tables','form/createForm','form/bootstrap-modal-bs3patch','form/bootstrap-modal'];
	}

	// Returns an array with scripts to be loaded into the dashboard
	function script(){
		return ['bootstrap-modalmanager','bootstrap-modal','jquery.tablesorter.min'];
	}
	
	function html()
	{

		// Get Information from URL string
		if( isset($_GET['id']) ){
			$formID = trim($_GET['id']);
		} else {
			echo 'No form selected. <br><a href="?path=form/manage">Go Back</a>';
			return;
		}

		$metaForm = $formID."_meta";

		// First, let's retrieve the form JSON object
		$queryString = " SELECT * FROM ".$metaForm;
		$formMetaInfo = site_queryCIE($queryString,[]);

		// Check to see if this is a true result
		if( !is_array($formMetaInfo) ){
			echo '<div style="padding: 10px 20px;">No form selected. <br><a href="?path=form/manage">Go Back</a></div>';
			return;
		}

		// Unlinked Check
		$queryString = "SELECT * FROM masterform WHERE form_id='".$formID."'";
		$formInformation = site_queryCIE($queryString,"query");
		$currentForm = $formInformation[0];
		if( $currentForm->unlinked == "y" ){
			echo '<div style="padding: 10px 20px;">No form selected. <br><a href="?path=form/manage">Go Back</a></div>';
			return;
		}
		unset($formInformation,$currentForm);

		// Now let's build our object
		$JSONObject = "{";
		$tempCounter = 0;
		foreach ($formMetaInfo as $element) {
			
			// Skip any element that has a _::DANA element name
			if( $element->element_name == "_::DANA" ){
				continue;
			}

			// Add Item ID
			$JSONObject .= '"'.$tempCounter.'":';

			// Create new object bracket
			$JSONObject .= '{';

			// Let's add the element type!
			$JSONObject .= '"element_type":"'.$element->element_type.'",';

			// Add element name
			$JSONObject .= '"element_name":"'.ucwords(str_replace("_", " ", $element->element_name)).'",';

			// Add element description
			$JSONObject .= '"element_description":"'.$element->element_description.'",';
			
			// Add element required
			$JSONObject .= '"element_required":"'.$element->element_required.'"';

			// Add element options
			if( !empty($element->element_options) ){
				$JSONObject .= ',"element_option":'.$element->element_options.'';
			}

			$JSONObject .= "},";

			$tempCounter++;
		}

		$JSONObject = substr($JSONObject, 0,strlen($JSONObject)-1)."}";

		// Information about the form
		$queryString = "SELECT * FROM masterform WHERE form_id='".$formID."'";
		$formInformation = site_queryCIE($queryString,"query");
		$currentForm = $formInformation[0];

		// We are going to save the start state of the JSON object so we can later compare it to see if the user has edited the form.
		$startJSONState = $JSONObject;

		?>

<div class="body" style="position: relative;">

<ol class="breadcrumb">
	<li><a href="?path=form/index">Form Management</a></li>
	<li><a href="?path=form/manage">Manage Forms</a></li>
	<li><a href="?path=form/view&id=<?php echo $formID ?>">View Form: <?php echo $currentForm->form_name ?></a></li>
	<li class="active">Edit Form</li>
</ol>

<h3>Now Editing: <strong><?php echo $currentForm->form_name ?></strong></h3>

<div class="createWrap">

	<!-- This section allows users to add a new component to the form -->
	<div style="position: relative; z-index: 100;">
		<div class="navbar navbar-default" role="navigation" style="background: white;">
			
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
					<span class="sr-only">Toggle navigation</span>
				</button>
				<span class="navbar-brand" href="#">Controls</span>
			</div>

			<form id="newField" class="navbar-form navbar-left">
				<div class="btn-group">
					<button type="button" name="text_field" class="btn btn-default" disabled="disabled"><span style="display: inline-block; line-height:24px;">Add</span></button>
					<button type="button" name="text_field" class="btn btn-default"><img src="./images/formGUIs/form_input_text.png"/></button>
					<button type="button" name="text_area" class="btn btn-default"><img src="./images/formGUIs/form_input_textarea.png"/></button>
					<button type="button" name="radio" class="btn btn-default"><img src="./images/formGUIs/form_input_radio.png"/></button>
					<button type="button" name="checkbox" class="btn btn-default"><img src="./images/formGUIs/form_input_checkbox.png"/></button>
				</div>
			</form>

			<form id="review" class="navbar-form navbar-right">
				<div class="btn-group">
					<a href="?path=form/view&id=<?php echo $formID ?>" class="btn btn-default">Done Editing</a>
					<button type="button" name="text_field" class="btn btn-success" onclick="reviewForm()">Save</button>
				</div>
			</form>

		</div>
	</div>

	<div id="warning"></div>
	
	<div id="sortable"></div>

</div>

<!-- This modal provides options for the user when they hit the review button -->
<div id="modal_formReview" class="modal fade" tabindex="-1" data-keyboard="false" style="display: none;">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h2 class="modal-title">Review successful,</h2>
	</div>
	<div class="modal-body">
		This form has been completed without any errors detected.<br><br>
		<strong>To save</strong>, press the button below.<br><br>
		To <strong>continue editing</strong>, select the button below or close this dialog box. You can get back to this screen by clicking on "Review" in the controls section.
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Continue Editing</button>
		<button id="modal_saveFormButton" type="button" class="btn btn-success" onclick="addInfo()">Save Form (Does NOT publish on website)</button>
	</div>
</div>

<!-- This modal shows the user that the form is being submitted -->
<div id="modal_saveForm" class="modal fade" tabindex="-1" data-backdrop="static" data-keyboard="false" style="display: none;">
	<div class="modal-header">
		<h2 class="modal-title">Saving Form...</h2>
	</div>
	<div class="modal-body">
		Please wait while we save your form. This might take a few minutes.
		<div class="progress progress-striped active">
			<div class="progress-bar"  role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
			<span class="sr-only">Loading</span>
			</div>
		</div>
	</div>
</div>

<!-- This modal asks the user for additional information to submit a form -->
<div id="modal_addInfo" class="modal fade" tabindex="-1" data-backdrop="static" data-keyboard="false" style="display: none;">
	<div class="modal-header">
		<h2 class="modal-title">Update Info</h2>
	</div>
	<div class="modal-body">

		<div id="addInfo_error" class="alert alert-danger" style="display: none;"></div>

		Would you like to update either the form name or the form description?
		<div class="form-group col-md-12" style="padding: 15px 0 0 0;"> 
			<label for="form_name" class="control-label">Form Name</label> 
			<br> 
				<input type="text" class="form-control" id="form_name" placeholder="Ex: Online Application For..." value="<?php echo $currentForm->form_name ?>"> 
		</div>
		<div class="form-group col-md-12" style="padding: 15px 0 0 0;"> 
			<label class="control-label" for="form_description">Form Description</label> 
			<br> 
				<textarea type="text" class="form-control" id="form_description" placeholder="Ex: Application for CIE award of..."><?php echo $currentForm->form_description ?></textarea>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
		<button id="modal_saveFormButton" type="button" class="btn btn-success" onclick="saveForm()">Save Form (Does NOT publish on website)</button>
	</div>

</div>

<div id="modal_results" class="modal fade" tabindex="-1" data-backdrop="static" data-keyboard="false" style="display: none;">
	<div class="modal-header">
		<h2 class="modal-title" id="modal_results_title"></h2>
	</div>
	<div id="modal_results_body" class="modal-body">
	</div>
	<div id="modal_results_footer" class="modal-footer">
	</div>
</div>

</div>

<script src="./js/form/editor-gui.js"></script>
<script type="text/javascript">

// Load after everything is done loading
$( document ).ready( function(){ main('<?php echo $JSONObject; ?>');visual_collapse("hide") } )

var startJSONState = '<?php echo $startJSONState ?>';
var hasSubmitted = false;

// Check to see if the user has edited anything before leaving...
window.onbeforeunload = function(){
	if( startJSONState == dataExport() ){
		// They haven't changed anything, let them leave.
	} else {
		if( hasSubmitted ){

		} else {
			return "You have unsaved work. Do you wish to leave? (No work will be saved)"
		}
	}
}


// This function will override the usual save function. It sends an extra parameter to the processForm.php
function saveForm(){
		// If we are checking the form data for completion, then call addInfo(true)
	var form_name = getElement("form_name");
	var form_description = getElement("form_description");

	// Pass Flag
	var errors = ['',''];
	var noErrors = true;

	// Clear everything out!
	$("#addInfo_error").html("");
	$("#addInfo_error").hide();

	// Check each value
	if( form_name.value === "" ){
		$("#form_name").parent().addClass("has-error");
		errors[0] = "Please fill out the name field.";
	} else {
		$("#form_name").parent().removeClass("has-error");
	}

	if( form_description.value === "" ){
		$("#form_description").parent().addClass("has-error");
		errors[1] = "Please fill out the description field.";
	} else {
		$("#form_description").parent().removeClass("has-error");
	}

	// Add the errors if there are any...
	for( var i = 0; i < errors.length; i++ ){
		if( errors[i] === '' ){
			continue;
		} else {
			noErrors = false;
			$("#addInfo_error").append(errors[i]+"<br>");
		}
	}

	// If there are no errors, submit the form
	if( noErrors ){
		updateForm()
	} else {
		// There are errors, show them!
		$("#addInfo_error").show();
	}
}

function updateForm(){

	// Show loading status...
	// In this case, we want to disable the old modal and put up a new one!
	$("#modal_formReview").modal("loading");
	$("#modal_saveFormButton").attr({
		disabled: 'dsiabled'
	});

	// Now display the new modal.
	$("#modal_saveForm").modal("show");


	// Grab the state
	var dataObj = dataExport();

	// Also grab the form details
	var form_name = getElement("form_name").value;
	var form_description = getElement("form_description").value;

	$.ajax({
		url: '/CIE/component/form/processForm.php',
		type: 'POST',
		dataType: 'html',
		data: {dataObject: dataObj,form_name: form_name, form_description: form_description,form_id:"<?php echo $formID ?>",update:"true"},
	})
	.done(function(response) {
		// We're going to redirect them to the form manager that is open to the current form.
		$("#modal_saveForm").modal("hide");
		$("#modal_formReview").modal("hide");
		$("#modal_addInfo").modal("hide");
		$("#modal_results").modal("show");

		$("#modal_results_title").html("<span class=\"text-success\">Form Saved</span>");
		$("#modal_results_body").html("This form has been saved!<br>Press <strong>\"continue\"</strong> to view your form. ");
		$("#modal_results_footer").html("<button type=\"button\" class=\"btn btn-primary\" onclick=\"window.location.href = '?path=form/edit&id="+response+"'\">Continue</button>");

		// Also allow them to leave without a prompt.
		hasSubmitted = true;
		
	})
	.fail(function() {
		// The form could not be saved.
		// Alert User through result modal.
		// Allow the user to save their work so they don't loose it!
		$("#modal_saveForm").modal("hide");
		$("#modal_formReview").modal("hide");
		$("#modal_addInfo").modal("hide");
		$("#modal_results").modal("show");
		
		$("#modal_results_title").html("<span class=\"text-danger\">Error</span>");
		$("#modal_results_body").html("There was an error with your save request. As such, this form cannot be processed. Below is a copy of your current work.<br><br><div class=\"alert alert-danger\"><strong> Please save the information below to a document on your own computer. It cannot be saved to the database and the data will be lost once this page is reloaded. <br><br> Please speak to an administrator to resolve the issue.</strong></div><pre>"+dataExport()+"</pre>");
		$("#modal_results_footer").html("<button type=\"button\" class=\"btn btn-default\" onclick=\"forceReload('Did you save the copy of your work?')\">Refresh This App</button>");
	});
}

</script>
		<?php 
	}

}

?>