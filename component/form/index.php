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

	// Returns an array with stylesheets to be loaded into the dashboard
	function style(){
		return [];
	}

	// Returns the current location of the file
	function location(){
		return $this->location;
	}
	
	// Returns an array with scripts to be loaded into the dashboard
	function script(){
		return [];
	}

	function html()
	{
		?>
<div class="body">

<ol class="breadcrumb">
	<li class="active">Form Management</li>
</ol>

<h1>Create and Manage Forms</h1>
Use this dashboard to create forms and manage forms. Select one of the options below to get started.

<p style="padding: 20px; text-align: center;">
	<a href="?path=form/create" class="btn btn-success btn-lg">Create New Form</a>
	<a href="?path=form/manage" class="btn btn-default btn-lg">Manage Existing Forms</a>
</p>

</div>

<?php
	}

}

?>