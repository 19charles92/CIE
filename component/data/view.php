<?php

class Dashboard
{
	
	public $location = "data";

	// Returns the title we want to show on the dashboard
	// Format will append whatever is in this to:
	// 		Dashboard - title()
	function title(){
		return 'View Form Data';
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
		if( isset($_GET['formID']) ){
			$formID = trim($_GET['formID']);
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

		// Let's us select the rest of the information we need.
		$masterForm = site_queryCIE("SELECT * FROM masterform WHERE form_id=?",[$formID]);
		$masterForm = $masterForm[0];

		$formData = site_queryCIE("SELECT * FROM ".$formID."","query");
		?>

<div class="body">

<ol class="breadcrumb">
	<li><a href="?path=data/index">Data Management</a></li>
	<li class="active">View Data</li>
</ol>

<h3>Now Viewing: <?php echo $masterForm->form_name ?></h3>
<p>
	This is the information that is currently in the selected form.
</p> 
<a href="?path=data/index" class="btn btn-default">Go Back</a><br><br>
<table id="currentForms" class="table tablesorter table-striped">
	<thead>
		<tr>
			<?php

			// We have to print the name of each column of this form here...
			// We are also going to save the names of each column so we can call them later...
			$columnName = [];
			foreach ($formMetaInfo as $column) {
				echo '<th>'.ucwords(str_replace("_", " ", $column->element_name)).'</th>';
				$columnName[] = $column->element_name;
			}

			?>
		</tr>
	</thead>
	<tbody>
<?php

// Iterate through each entry in the form
foreach ($formData as $form) {

	echo '<tr>';
	// For each entry, we are going to loop through each column that was found in the meta table.
	for ($i=0; $i < sizeof( $columnName ) ; $i++) { 
		echo '<td>'.$form->$columnName[$i].'</td>';
	}
	echo '</tr>';
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