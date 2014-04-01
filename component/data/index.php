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
		return ['tables','form/createForm','form/bootstrap-modal-bs3patch','form/bootstrap-modal'];
	}

	// Returns an array with scripts to be loaded into the dashboard
	function script(){
		return ['bootstrap-modalmanager','bootstrap-modal','jquery.tablesorter.min'];
	}

	function html()
	{
		?>
<div class="body">

<ol class="breadcrumb">
	<li><a href="?path=data/index">Data Management</a></li>
</ol>

<h1>View Data</h1>
<p>View data that has been submitted through forms. Below you will see a list of forms that contain forms that you have created and forms created by other users. You also have the option to search for forms by using the "Search" button.</p>

<table id="currentForms" class="table tablesorter table-striped">
	<thead>
		<tr>
			<th>Author</th>
			<th>Form Name</th>
			<th>Created</th>
			<th>Description</th>
			<th>View Data</th>
		</tr>
	</thead>
	<tbody>
<?php

function users(){

	// Create a negative user
	$invalidUser = new stdClass();
	$invalidUser->access_level = "-1";
	$invalidUser->DANA = "";

	// Save the session to a variable
	$session = "";

	if( isset( $_SESSION['CIE_session'] ) )
		$session = $_SESSION['CIE_session'];
	else
		return $invalidUser;

	// There is a session present, let's check it!
	$access_level = site_queryCIE("SELECT access_level,DANA FROM user_level WHERE DANA = ( SELECT DANA FROM session WHERE session_id=? )",[$session]);
	
	// If there wasn't a record, then return -1
	// If there was, then return the access level
	// And update the last_action tag
	if( empty( $access_level ) ) {
		return $invalidUser;
	}
	else {
		site_queryCIE("UPDATE session SET last_action=? WHERE session_id=? ",[time(),$session]);
		return $access_level[0];
	}
}

$toFind = users()->DANA;

$forms = site_queryCIE("SELECT * FROM masterform WHERE DANA=? AND unlinked='n'",[$toFind]);

foreach ($forms as $form) {
	?>
		<tr>
			<td><?php echo $form->DANA ?></td>
			<td><?php echo $form->form_name ?></td>
			<td><?php echo date("m/d/Y",$form->created) ?></td>
			<td><?php echo $form->form_description ?></td>
			<td><a href="?path=data/view&formID=<?php echo $form->form_id ?>">View</a></td>
		</tr>
	<?php
}

?>
	</tbody>
</table>

<script type="text/javascript">
$(document).ready(function(){
	$("#currentForms").tablesorter();
});

</script>

</div>

<?php
	}

}

?>