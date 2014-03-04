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

		// If the form is already published, then allow the user to see a "View Code" instead of "Publish Form"
		// Also, change the "Delete Form" to "Unlink Form"

		$formOptions = [];

		// Assign a default button
		$formOptions[0] = '<a href="?path=form/edit&id='.$formID.'?>" class="btn btn-success"> Edit Form </a>';

		if( $currentForm->published == "y" ){
			// Form has been published
			$formOptions[1] = '<button class="btn btn-primary" onclick="viewCode()">View Code</button>';
			$formOptions[2] = '<button class="btn btn-warning"> Unlink Form </button>';
		} else {
			// Form hasn't been published
			$formOptions[1] = '<button class="btn btn-primary" onclick="publishForm()">Publish Form</button>';
			$formOptions[2] = '<button class="btn btn-danger"> Delete Form </button>';
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
		modalBody.innerHTML = response;
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
	ifPublish = confirm("Once you publish a form, you cannot go back. You will also lose the ability to delete this form. \n\nHit 'OK' to publish form. ");

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

</script>
		<?php 
	}

}

?>