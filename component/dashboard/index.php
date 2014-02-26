<?php
// global $userObj;

class Dashboard
{
	
	public $location = "dashboard";

	// Returns the title we want to show on the dashboard
	// Format will append whatever is in this to:
	// 		Dashboard - title()
	function title(){
		return 'Home';
	}

	// Returns an array with stylesheets to be loaded into the dashboard
	function style(){
		return [];
	}

	// Returns an array with scripts to be loaded into the dashboard
	function script(){
		return [];
	}

	// Returns the current location of the file
	function location(){
		return $this->location;
	}

	function html()
	{
		// Grab the data info from the dashboard
		global $userObj;
		?>
<div class="body">

<ol class="breadcrumb">
	<li class="active">Dashboard Home</li>
</ol>

<h1> Welcome <strong><?php echo $userObj->DANA ?></strong>, </h1>

This dashboard will allow you to interact with the CIE database and form management system. Navigate through this application by using the links on the left. There will be a navigation area on the top of each page so can keep track of where you are.
<br><br> <p class="text-danger">This system will keep you logged in for up to 24 hours if you keep your browser open. Make sure to logout through the option in the menu.</p>

</div>
<?php
	}

}

?>