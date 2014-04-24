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
		return ['form/bootstrap-modal-bs3patch','form/bootstrap-modal'];
	}

	// Returns an array with scripts to be loaded into the dashboard
	function script(){
		return ['bootstrap-modalmanager','bootstrap-modal'];
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

		// Information about the form
		$queryString = "SELECT * FROM masterform WHERE form_id='".$formID."'";
		$formInformation = site_queryCIE($queryString,"query");
		$currentForm = $formInformation[0];

		if( $currentForm->unlinked == "y" ){
			echo '<div style="padding: 10px 20px;">No form selected. <br><a href="?path=form/manage">Go Back</a></div>';
			return;
		}

		// If the form is already published, then allow the user to see a "View Code" instead of "Publish Form"
		// Also, change the "Delete Form" to "Unlink Form"

		$formOptions = [];

		// Assign a default button
		$formOptions[0] = '<a href="?path=form/edit&id='.$formID.'" class="btn btn-success"> Edit Form </a>';

		if( $currentForm->published == "y" ){
			// Form has been published
			$formOptions[0] = '<a href="#" class="btn btn-success" disabled="disabled"> Edit Form </a>';
			$formOptions[1] = '<button class="btn btn-primary" onclick="viewCode()">View Code</button>';
			$formOptions[2] = '<button class="btn btn-warning" onclick="deleteRequest(\'Unlink\')"> Unlink Form </button>';
		} else {
			// Form hasn't been published
			$formOptions[1] = '<button class="btn btn-primary" onclick="publishForm()">Publish Form</button>';
			$formOptions[2] = '<button class="btn btn-danger" onclick="deleteRequest(\'Delete\')"> Delete Form </button>';
		}

		?>

<div class="body" style="position: relative;">

<ol class="breadcrumb">
	<li><a href="?path=form/index">Form Management</a></li>
	<li><a href="?path=form/manage">Manage Forms</a></li>
	<li class="active">View Form</li>
</ol>

<h3>Now Viewing: <strong><?php echo $currentForm->form_name ?></strong> </h3>
<a class="btn btn-default" href="?path=form/manage">Go back to Manage</a><br><br>

<?php
// Take care of forms.

if( isset($_GET['msg']) ){
	$errorMsg = $_GET['msg'];
} else {
	$errorMsg = '';
}

if( $errorMsg == "10" ){
	?>
	<div class="alert alert-success alert-dismissable">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		Form published!
	</div>
	<?php 
} elseif( $errorMsg == "11" ){
	?>
	<div class="alert alert-warning alert-dismissable">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<strong>Warning!</strong> The form could not be published. Please try again. If this issue continues, contact an administrator.
	</div>
	<?php 
}
?>

<div class="panel panel-default">
	<div class="panel-heading">Form Information</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-sm-3"> <strong>Author</strong> </div>
			<div class="col-sm-9"> <?php echo $currentForm->DANA ?> </div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-3"> <strong>Created</strong> </div>
			<div class="col-sm-9"> <?php echo date("m/d/Y",$currentForm->created) ?> </div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-3"> <strong>Description</strong> </div>
			<div class="col-sm-9"> <?php echo $currentForm->form_description ?> </div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-3"> <strong>Published Status</strong> </div>
			<div class="col-sm-9"> <?php if( $currentForm->published == "y" ){ echo "Yes"; } else { echo "No"; } ?> </div>
		</div>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading">Preview This Form</div>
	<div class="panel-body">
		<p> You can preview how your form is going to display once it has been published. </p>
		<button class="btn btn-success" onclick="showHTMLPreview()"> Preview Form </button>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading">Form Options</div>
	<div class="panel-body">
		<p> Edit the form with the following options: </p>
		<?php
		// Print out all the available form options
		foreach ($formOptions as $formOption) {
			echo $formOption.' ';
		}
		?>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading">Form Access</div>
	<div class="panel-body">
		<p>
			You can force this form to only be accessible by the users you specify.<br><br>
			To enable this feature, just hit "enable" below. This feature can only be enabled <span style="font-weight: bold;">BEFORE</span> a form is published. <br><br>
			Once enabled, you will always be able to change the access of the form. This includes adding and removing DANAs to the allowed list.<br><br>
			Enabling this feature will prompt users to sign in through CAS before being allowed to view the form. The user's DANA ID will then be associated with their individual submission.
			</p>
		<button class="btn btn-success" onclick="enableFormAccess()"> Enable </button>
	</div>
</div>

<div id="modal_results" class=" modal fade" tabindex="-1" data-backdrop="static" data-keyboard="false" style="display: none;">
	<div class="modal-header">
		<h2 class="modal-title" id="modal_results_title"></h2>
	</div>
	<div id="modal_results_body" class="modal-body">
	</div>
	<div id="modal_results_footer" class="modal-footer">
	</div>
</div>

</div>

<script type="text/javascript">

$.fn.modal.defaults.spinner = $.fn.modalmanager.defaults.spinner = 
    '<div class="loading-spinner" style="width: 200px; margin-left: -100px;">' +
        '<div class="progress progress-striped active">' +
            '<div class="progress-bar" style="width: 100%;"></div>' +
        '</div>' +
    '</div>';

// This function resets
function resetResultModal(){
	var modalTitle = document.getElementById("modal_results_title");
	var modalBody = document.getElementById("modal_results_body");
	var modalFooter = document.getElementById("modal_results_footer");

	modalTitle.innerHTML = "";
	modalBody.innerHTML = "";
	modalFooter.innerHTML = "";

	// Also, remove the "container" class element if it's there.
	$("#modal_results").removeClass('container');

}

// This function will display a prompt for the user to enable 

// Will show the HTML preview of the current form in the results modal.
function showHTMLPreview(){

	// First reset the modal.
	resetResultModal();

	// Make it wide!
	$("#modal_results").addClass('container');

	// Now setup the variables
	var modalTitle = document.getElementById("modal_results_title");
	var modalBody = document.getElementById("modal_results_body");
	var modalFooter = document.getElementById("modal_results_footer");

	// Now call some AJAX
	$.ajax({
		url: '/CIE/component/form/formHTML.php',
		type: 'GET',
		dataType: 'html',
		data: {form: '<?php echo $formID ?>',iframe:"false"},
	})
	.done(function( response ) {
		$("#modal_results").modal("show");

		modalTitle.innerHTML = "<span class=\"text-success\">Preview</span>";
		modalBody.innerHTML = '<div style="height: 350px; overflow-y: scroll;">'+response+'</div>';
		modalFooter.innerHTML = '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
	})
	.fail(function() {

		$("#modal_results").modal("show");

		modalTitle.innerHTML = "<span class=\"text-danger\">Error</span>";
		modalBody.innerHTML = "Sorry, could not display a preview.";
		modalFooter.innerHTML = '<button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>';
	});
}

// Publishes this form
function publishForm(){
	// First ask the user if they want to publish the form.
	ifPublish = confirm("Once you publish a form, you cannot go back. You will also lose the ability to edit and delete this form. \n\nHit 'OK' to publish form. ");

	if( ifPublish ){
		// They have decided to publish. Call the AJAX request.
		$.ajax({
			url: '/CIE/component/form/publishForm.php',
			type: 'POST',
			dataType: 'html',
			data: {formID:'<?php echo $formID ?>'},
		})
		.done(function( result ) {
			window.location.href = "?path=form/view&id=<?php echo $formID ?>&msg=10";
		})
		.fail(function( result ) {
			window.location.href = "?path=form/view&id=<?php echo $formID ?>&msg=11";
		})
	}

}

// This will provide the user with the code they need for using the form.
function viewCode(){
	// First, reset the modal.
	resetResultModal();

	// Now setup the variables
	var modalTitle = document.getElementById("modal_results_title");
	var modalBody = document.getElementById("modal_results_body");
	var modalFooter = document.getElementById("modal_results_footer");

	// Now add the appropriate information
	modalTitle.innerHTML = "Source Code to Insert Form"
	modalBody.innerHTML = '<pre><code>&lt;iframe src="http://localhost/CIE/component/form/formHTML.php?form=<?php echo $formID ?>" scrolling="no" style="width: 100%; height: 800px;"&gt;Your browser does not support iFrames.&lt;/iframe&gt;</code></pre>';
	modalFooter.innerHTML = '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';

	$("#modal_results").modal("show");
}

// This function will prompt the user to ask if they want to proceed with their delete request
function deleteRequest( keyword ){
	// We first want to reset out results modal.
	resetResultModal();

	// Now setup the edit variables
	var modalTitle = document.getElementById("modal_results_title");
	var modalBody = document.getElementById("modal_results_body");
	var modalFooter = document.getElementById("modal_results_footer");

	// Write the text
	modalTitle.innerHTML = "<span class='text-danger'>"+keyword+" Form</span>";
	modalBody.innerHTML = "Are you sure you want to "+keyword+" this form? Once you confirm, this action <strong>cannot</strong> be undone. All data associated with this form will be deleted.";
	modalFooter.innerHTML = '<button type="button" class="btn btn-success" data-dismiss="modal">Go Back</button> <button type="button" class="btn btn-danger" onclick="sendDeleteRequest()">'+keyword+'</button>';

	$("#modal_results").modal("show");

}

function sendDeleteRequest(){

	// Make the results modal load
	$("#modal_results").modal("loading")

	// Now setup the edit variables
	var modalTitle = document.getElementById("modal_results_title");
	var modalBody = document.getElementById("modal_results_body");
	var modalFooter = document.getElementById("modal_results_footer");

	$.ajax({
		url: '/CIE/component/form/deleteForm.php',
		type: 'POST',
		dataType: 'HTML',
		data: {formID: '<?PHP echo $formID ?>'},
	})
	.done(function() {
		// The form was successfully deleted.
		modalTitle.innerHTML = "Action successfully performed"
		modalBody.innerHTML = "This form has now been removed. Please hit okay to go to the manage page."
		modalFooter.innerHTML = '<a href="?path=form/manage" class="btn btn-default">Okay</a>'

		// Now stop loading the modal
		$("#modal_results").modal("loading")

	})
	.fail(function() {
		// The form was successfully deleted.
		modalTitle.innerHTML = '<span class="text-danger">Action could not be performed</span>'
		modalBody.innerHTML = "Could not remove form. This could be because your account lacks the permission to perform this action. Press okay to be redirected back to the manage page."
		modalFooter.innerHTML = '<a href="?path=form/manage" class="btn btn-default">Okay</a>'

		// Now stop loading the modal
		$("#modal_results").modal("loading")
	});
	
}

</script>
		<?php 
	}

}

?>