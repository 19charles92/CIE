<?php

/**
* 
*/
class Dashboard
{

	public $location = "data";

	// Returns the title we want to show on the dashboard
	// Format will append whatever is in this to:
	// 		Dashboard - title()
	function title(){
		return 'Data Management';
	}

	// Returns the current location of the file
	function location(){
		return $this->location;
	}

	// Returns an array with stylesheets to be loaded into the dashboard
	function style(){
		return [];
	}

	// Returns an array with scripts to be loaded into the dashboard
	function script(){
		return [];
	}

	function html()
	{

		echo <<<EOD
<div class="body">

<ol class="breadcrumb">
	<li><a href="#">Data Management</a></li>
</ol>

</div>
EOD;

	}
}

?>