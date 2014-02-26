<?php

class Dashboard
{
	
	public $location = "form";

	// Returns the title we want to show on the dashboard
	// Format will append whatever is in this to:
	// 		Dashboard - title()
	function title(){
		return ' Manage Forms';
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

		// Now let's build our object
		$JSONObject = "{";
		$tempCounter = 0;
		foreach ($formMetaInfo as $element) {
			
			// Add Item ID
			$JSONObject .= '"'.$tempCounter.'":';

			// Create new object bracket
			$JSONObject .= '{';

			// Let's add the element type!
			$JSONObject .= '"element_type":"'.$element->element_type.'",';

			// Add element name
			$JSONObject .= '"element_name":"'.$element->element_name.'",';

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

		?>

<div class="body">

<ol class="breadcrumb">
	<li><a href="?path=form/index">Form Management</a></li>
	<li><a href="?path=form/manage">Manage Forms</a></li>
	<li class="active">Edit Form</li>
</ol>

<h2>Edit A Form</h2>
<p>
	Edit your form down here.
</p> 

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
		This form has been completed without any errors detected. Currently, <span class="text-warning">this form has not been saved.</span><br><br>
		<strong>To save</strong>, press the button below. You will be able to preview this form once you save. You can manage existing forms from the dashboard by navigating to "Form Management" and clicking on the "Manage Existing Forms" button.<br><br>
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
		<h2 class="modal-title">Additional Information Needed</h2>
	</div>
	<div class="modal-body">

		<div id="addInfo_error" class="alert alert-danger" style="display: none;"></div>

		Please fill out the following information.
		<div class="form-group col-md-12" style="padding: 15px 0 0 0;"> 
			<label for="form_name" class="control-label">Form Name</label> 
			<br> 
				<input type="text" class="form-control" id="form_name" placeholder="Ex: Online Application For..."> 
		</div>
		<div class="form-group col-md-12" style="padding: 15px 0 0 0;"> 
			<label class="control-label" for="form_description">Form Description</label> 
			<br> 
				<textarea type="text" class="form-control" id="form_description" placeholder="Ex: Application for CIE award of..."></textarea>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
		<button id="modal_saveFormButton" type="button" class="btn btn-success" onclick="addInfo(true)">Save Form (Does NOT publish on website)</button>
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
	main('<?php echo $JSONObject; ?>')
</script>
		<?php 
	}

}

?>