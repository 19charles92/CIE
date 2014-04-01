<?php
// global $userObj;

class Dashboard
{
	
	public $location = "user";

	// Returns the title we want to show on the dashboard
	// Format will append whatever is in this to:
	// 		Dashboard - title()
	function title(){
		return 'Home';
	}

	// Returns an array with stylesheets to be loaded into the dashboard
	function style(){
		return ['tables','form/bootstrap-modal-bs3patch','form/bootstrap-modal'];
	}

	// Returns an array with scripts to be loaded into the dashboard
	function script(){
		return ['bootstrap-modalmanager','bootstrap-modal','jquery.tablesorter.min'];
	}

	// Returns the current location of the file
	function location(){
		return $this->location;
	}

	function html()
	{
		// Query the database for the description
		$access_levels = site_queryCIE("SELECT * FROM user_level_lk",[]);

		// Grab the data info from the dashboard
		global $userObj;

		?>
<div class="body">

<ol class="breadcrumb">
	<li><a href="?path=user/index">User Access Management</a></li>
</ol>

<?php

if( $userObj->access_level > 1 ){
	?>
	<div class="alert alert-danger"> <span class="glyphicon glyphicon-ban-circle"></span> Access Denied.</div>
	<?php
	return;
}

?>

<h1> Manage Application Access </h1>

<p> Manage who has access to this application below. Removing access to a user does not remove their forms.</p>

<div class="btn-group btn-group-lg">
	<a class="btn btn-default" disabled="disabled"> Quick Links </a>
	<a href="#addUser" class="btn btn-default"> Add User </a>
	<a href="#viewUsers" class="btn btn-default"> Add Manage Users </a>
</div>

<h2 id="addUser" style="color: #576675;"> Add User </h2>
<p> Add a user here. All that is needed is a DANA ID and the desired user level. </p>
	<form id="addUserForm" class="form-horizontal" role="form" onsubmit="return false">

		<div class="form-group">
			<label for="DANA" class="control-label col-md-2">DANA</label>
			<div class="col-md-5"><input type="text" class="form-control" id="DANA" placeholder="DANA ID"></div>
			<div class="col-md-5"><button id="addUserButton" type="submit" class="btn btn-primary col-md-12" disabled="disabled" onclick="return addUserPrompt()"> Add User </button></div>
		</div>

		<div class="form-group">
			<label class="control-label col-md-2" for="access_level">Access Level</label>
			<div class="col-md-5">
				<select id="access_level" class="form-control" size="2">
					<?php

					foreach ($access_levels as $level) {
						echo '<option>'.$level->access_level.' - '.$level->description.'</option>';
					}

					?>
				</select>
			</div>
		</div>


	</form>
	<br>
	<br>
	<br>

<h2 id="viewUsers" style="color: #576675;"> View Current Users </h2>

<table id="currentForms" class="table tablesorter table-striped">
	<thead>
		<tr>
			<th width="25%">User</th>
			<th width="25%">Access Level</th>
			<th width="50%">Options</th>
		</tr>
	</thead>
	<tbody>
	<?php

	// Query The Database for all the users...
	$usersInSystem = site_queryCIE("SELECT user_level.dana,user_level.access_level,user_level_lk.description FROM user_level INNER JOIN user_level_lk ON user_level.access_level=user_level_lk.access_level",[]);

	// Now print each user
	foreach ($usersInSystem as $user) {

		$JSONObject = '{"DANA":"'.$user->dana.'","access_level":"'.$user->access_level.'","description":"'.$user->description.'"}';

		?>
		<tr>
			<td><?php echo $user->dana ?></td>
			<td><?php echo $user->access_level ?> - <?php echo $user->description ?></td>
			<td>
				<button class="btn btn-primary btn-sm" onclick='changeALPrompt(<?php echo $JSONObject ?>)'><span class="glyphicon glyphicon-pencil"></span> Change Access Level</button>
				<button class="btn btn-danger btn-sm" onclick='revokePromt(<?php echo $JSONObject ?>)'><span class="glyphicon glyphicon-trash"></span> Revoke Access</button>
			</td>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>

<div id="modal_results" class="modal fade" tabindex="-1" data-backdrop="static" data-keyboard="false" style="display: none;">
	<div class="modal-header">
		<h2 class="modal-title"></h2>
	</div>
	<div class="modal-body">
	</div>
	<div class="modal-footer">
	</div>
</div>

<script type="text/javascript">
$(document).ready(function(){
	$("#currentForms").tablesorter({
		headers: {2:{sorter:false}}
	});
});

$.fn.modal.defaults.spinner = $.fn.modalmanager.defaults.spinner = 
    '<div class="loading-spinner" style="width: 200px; margin-left: -100px;">' +
        '<div class="progress progress-striped active">' +
            '<div class="progress-bar" style="width: 100%;"></div>' +
        '</div>' +
    '</div>';

// Asks the user to verify that they want to add an authorized user.
function addUserPrompt(){
	// Grab the values
	var form_DANA = document.getElementById("DANA").value;
	var form_AL = document.getElementById("access_level").value.substr(4);

	titleTarget		= document.getElementById("modal_results").getElementsByClassName("modal-title")[0];
	messageTarget	= document.getElementById("modal_results").getElementsByClassName("modal-body")[0];
	optionsTarget	= document.getElementById("modal_results").getElementsByClassName("modal-footer")[0];

	titleTarget.innerHTML = "Confirm New User";
	messageTarget.innerHTML = "Are you sure you want to add <strong>"+form_DANA+"</strong> as the role: <span class=\"text-warning\">"+form_AL+"</span>?";
	optionsTarget.innerHTML = "<button class=\"btn btn-success\" onclick=\"addUser()\"> Yes, add user. </button><button class=\"btn btn-default\" data-dismiss=\"modal\"> No, cancel request. </button>";

	$("#modal_results").modal("show");

}

// Send this to the PHP script!
function addUser(){

	// Modal is loading...
	$("#modal_results").modal("loading");

	// Grab the values
	var form_DANA = document.getElementById("DANA").value;
	var form_AL = document.getElementById("access_level").value.substr(0,1);

	// Grab the data targets
	titleTarget		= document.getElementById("modal_results").getElementsByClassName("modal-title")[0];
	messageTarget	= document.getElementById("modal_results").getElementsByClassName("modal-body")[0];
	optionsTarget	= document.getElementById("modal_results").getElementsByClassName("modal-footer")[0];

	// Send the data to the PHP script
	$.ajax({
		url: '/CIE/component/user/addUser.php',
		type: 'POST',
		dataType: 'html',
		data: {DANA: form_DANA,AL:form_AL},
	})
	.done(function( response ) {
		if( response == "user added" ){
			// The user was added
			titleTarget.innerHTML = "<span class=\"text-success\">User Added</span>"
			messageTarget.innerHTML = "The user has been added to this application."
			optionsTarget.innerHTML = "<button class=\"btn btn-default\" onclick=\"location.reload()\">Go Back</button>"
			$("#modal_results").modal("loading");
		} else {
			// There was an error...
			titleTarget.innerHTML = "<span class=\"text-warning\">Warning</span>"
			messageTarget.innerHTML = "The user is already in the system. They were NOT added to the application."
			optionsTarget.innerHTML = "<button class=\"btn btn-default\" onclick=\"location.reload()\">Refresh Application</button>"
			$("#modal_results").modal("loading");
		}
	})
	.fail(function() {
		titleTarget.innerHTML = "<span class=\"text-danger\">Error</span>"
		messageTarget.innerHTML = "There was an error in processing your request. Please reload this page."
		optionsTarget.innerHTML = "<button class=\"btn btn-default\" onclick=\"location.reload()\">Refresh Application</button>"
		$("#modal_results").modal("loading");
	});
	

}

// Add action listeners to add user form
$("#DANA").bind("change paste keyup", function(){allowSubmit(this)} );
$("#access_level").bind("change paste keyup", function(){allowSubmit(this)} );

function allowSubmit( pointer ){

	// Check the two fields
	if( document.getElementById("access_level").value != "" && document.getElementById("DANA").value != "" ){
		// Allow user to submit
		// alert("S")
		$("#addUserButton").removeAttr("disabled")
	} else {
		$("#addUserButton").attr("disabled","disabled")
	}

}

function revokeAccess( User ){

	// Modal is loading...
	$("#modal_results").modal("loading");

	// Grab the data targets
	titleTarget		= document.getElementById("modal_results").getElementsByClassName("modal-title")[0];
	messageTarget	= document.getElementById("modal_results").getElementsByClassName("modal-body")[0];
	optionsTarget	= document.getElementById("modal_results").getElementsByClassName("modal-footer")[0];

	// Let's delete this user!
	$.ajax({
		url: '/CIE/component/user/manageUser.php',
		type: 'POST',
		dataType: 'html',
		data: {DANA: User['DANA'], AL:User['access_level'], action:"delete"},
	})
	.done(function(response) {
		if( response == "user deleted" ){
			// The user was deleted
			titleTarget.innerHTML = "<span class=\"text-success\">User Removed</span>"
			messageTarget.innerHTML = "The user has been removed from this application."
			optionsTarget.innerHTML = "<button class=\"btn btn-default\" onclick=\"location.reload()\">Go Back</button>"
			$("#modal_results").modal("loading");
		} else {
			// There was an error...
			titleTarget.innerHTML = "<span class=\"text-danger\">Error</span>"
			messageTarget.innerHTML = "There was an error in processing your request. Please reload this page."
			optionsTarget.innerHTML = "<button class=\"btn btn-default\" onclick=\"location.reload()\">Refresh Application</button>"
			$("#modal_results").modal("loading");
		}
	})
	.fail(function() {
		titleTarget.innerHTML = "<span class=\"text-danger\">Error</span>"
		messageTarget.innerHTML = "There was an error in processing your request. Please reload this page."
		optionsTarget.innerHTML = "<button class=\"btn btn-default\" onclick=\"location.reload()\">Refresh Application</button>"
		$("#modal_results").modal("loading");
	});
	
}

function changeAL( User ){

	// Modal is loading...
	$("#modal_results").modal("loading");

	// Grab the data targets
	titleTarget		= document.getElementById("modal_results").getElementsByClassName("modal-title")[0];
	messageTarget	= document.getElementById("modal_results").getElementsByClassName("modal-body")[0];
	optionsTarget	= document.getElementById("modal_results").getElementsByClassName("modal-footer")[0];

	// Grab the new UserLevel from the prompt
	newPrompt = document.getElementById("change_AL").value;

	// Let's update this user!
	$.ajax({
		url: '/CIE/component/user/manageUser.php',
		type: 'POST',
		dataType: 'html',
		data: {DANA: User['DANA'], AL:newPrompt, action:"update"},
	})
	.done(function(response) {
		if( response == "access level updated" ){
			// The user was deleted
			titleTarget.innerHTML = "<span class=\"text-success\">User Level Updated</span>"
			messageTarget.innerHTML = "The user's access level has been updated."
			optionsTarget.innerHTML = "<button class=\"btn btn-default\" onclick=\"location.reload()\">Go Back</button>"
			$("#modal_results").modal("loading");
		} else {
			// There was an error...
			titleTarget.innerHTML = "<span class=\"text-danger\">Error</span>"
			messageTarget.innerHTML = "There was an error in processing your request. Please reload this page."
			optionsTarget.innerHTML = "<button class=\"btn btn-default\" onclick=\"location.reload()\">Refresh Application</button>"
			$("#modal_results").modal("loading");
		}
	})
	.fail(function() {
		titleTarget.innerHTML = "<span class=\"text-danger\">Error</span>"
		messageTarget.innerHTML = "There was an error in processing your request. Please reload this page."
		optionsTarget.innerHTML = "<button class=\"btn btn-default\" onclick=\"location.reload()\">Refresh Application</button>"
		$("#modal_results").modal("loading");
	});
	
}

function revokePromt( User ){
	titleTarget		= document.getElementById("modal_results").getElementsByClassName("modal-title")[0];
	messageTarget	= document.getElementById("modal_results").getElementsByClassName("modal-body")[0];
	optionsTarget	= document.getElementById("modal_results").getElementsByClassName("modal-footer")[0];

	titleTarget.innerHTML = "Confirm Access Revoke";
	messageTarget.innerHTML = "Are you sure you want to revoke access to <strong>"+User['DANA']+"</strong>?";
	optionsTarget.innerHTML = "<button class=\"btn btn-warning\" onclick='revokeAccess("+JSON.stringify(User)+")'> Yes, revoke access. </button><button class=\"btn btn-default\" data-dismiss=\"modal\"> No </button>";	

	$("#modal_results").modal("show");
}

function changeALPrompt( User ){
	titleTarget		= document.getElementById("modal_results").getElementsByClassName("modal-title")[0];
	messageTarget	= document.getElementById("modal_results").getElementsByClassName("modal-body")[0];
	optionsTarget	= document.getElementById("modal_results").getElementsByClassName("modal-footer")[0];

	titleTarget.innerHTML = "Change User Access Level";
	messageTarget.innerHTML = "The user <strong>"+User['DANA']+"</strong> currently has an access level of <strong>"+User['access_level']+" - "+User['description']+"</strong>.<br> <h3>Choose a new user level</h3>";

	messageString = '<form class="form-horizontal" role="form"><div class="form-group"><label class="control-label col-md-3" for="change_AL">Access Level</label><div class="col-md-9"><select id="change_AL" class="form-control" size="2">'
					<?php

					foreach ($access_levels as $level) {
						echo 'messageString += messageTarget.innerHTML+"<option>'.$level->access_level.' - '.$level->description.'</option>";';
					}

					?>
	messageString += '</select></div></div></form>';

	messageTarget.innerHTML += messageString;

	optionsTarget.innerHTML = "<button class=\"btn btn-warning\" onclick='changeAL("+JSON.stringify(User)+")'> Update Access Level </button><button class=\"btn btn-default\" data-dismiss=\"modal\"> Cancel </button>";	

	$("#modal_results").modal("show");
}

</script>

</div>
<?php
	}

}

?>