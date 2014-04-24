<?php

class Dashboard
{

	public $location = "form";
	
	// Returns the title we want to show on the dashboard
	// Format will append whatever is in this to:
	// 		Dashboard - title()
	function title(){
		return 'Form Management';
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
		?>
<div class="body">

<ol class="breadcrumb">
	<li><a href="?path=form/index">Form Management</a></li>
</ol>

<h1>Create and Manage Forms</h1>
Use this dashboard to create forms and manage forms. Select one of the options below to get started.

<p style="padding: 20px; text-align: center;">
	<button class="btn btn-success btn-lg" data-toggle="modal" data-target="#modal_results">Create New Form</button>
	<a href="?path=form/manage" class="btn btn-default btn-lg">Manage Existing Forms</a>
</p>

</div>

<div id="modal_results" class="modal fade" tabindex="-1" style="display: none;">
	<div class="modal-header">
		<h2 class="modal-title" id="modal_results_title">Create Mode</h2>
	</div>
	<div id="modal_results_body" class="modal-body">
		<p> The options below allow you to specify whether you want to create a form from <strong>an existing form</strong> or a <strong>blank form</strong>.  </p>
		<button class="btn btn-success btn-lg" data-toggle="modal" data-target="#modal_results">From Existing Form</button><br><br>
		<a href="?path=form/create" class="btn btn-success btn-lg">From Blank Form</a>
	</div>
	<div id="modal_results_footer" class="modal-footer">
		<button class="btn btn-default" data-dismiss="modal">Close</button>
	</div>
</div>

<?php
	}

}

?>