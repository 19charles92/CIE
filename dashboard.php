<?php

// 
// dashboard.php
// Charles Chavez
// 
// This dashboard will display all the relevant information the current user has access to.
// Based on the user level, there will be different options presented to users.
// 
// The dashboard 
include 'config.php';

isLoggedIn("login.php?error=dnt");

// The dashboard will load in PHP files based on the path parameter from the URL
// If there is no path parameter provided, load the default dashboard home.
$pathToLoad = "";

// Let's do all the loading!
if( !isset($_GET['path']) ){
	$pathToLoad = "component/dashboard/index.php";
} else {
	$pathToLoad = "component/".$_GET['path'].".php";

	if( !file_exists($pathToLoad) )
		$pathToLoad = "component/dashboard/index.php";
}

include $pathToLoad;

// Provide a user object for users
$userObj = hasAccess();

// Create a dashboard instance
$Dashboard = new Dashboard();

?>

<!DOCTYPE html>
<html>
<head>
	<title> Dashboard - <?php echo $Dashboard->title() ?> </title>

	<script src="./js/jquery-1.9.1.js"></script>
	<script src="./js/jquery-ui-1.10.3.custom.min.js"></script>
	<script src="./js/bootstrap.min.js"></script>

	<?php
	// Manage the included stylesheets
	foreach( $Dashboard->script() as $script ){
		echo '<script src="./js/'.$script.'.js"></script>';
	}
	?>

	<link href="./css/bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="./css/main.css" rel="stylesheet" media="screen">
	<?php
	// Manage the included stylesheets
	foreach( $Dashboard->style() as $stylesheet ){
		echo '<link rel="stylesheet" type="text/css" href="./css/'.$stylesheet.'.css" rel="stylesheet">';
	}
	?>
</head>
<body>

<div class="wrap">

	<div class="mainFrame">
		
		<div class="leftSection">
			<div style="position: fixed;">
				<div class="logo">CIE<br>Dashboard</div>
				<div class="menu">
					<a id="dashboard" href="?path=dashboard/index"> Dashboard Home <span class="glyphicon glyphicon-home" style="margin: 0 0 0 5px;"></span> </a>
					<a id="data" href="?path=data/index"> Data Management <span class="glyphicon glyphicon-inbox" style="margin: 0 0 0 5px;"></span></a>
					<a id="form" href="?path=form/index"> Form Management <span class="glyphicon glyphicon-list-alt" style="margin: 0 0 0 5px;"></span></a>
					<?php if( $userObj->access_level == 1 ){ ?>
						<a id="user" href="?path=user/index"> User Management <span class="glyphicon glyphicon-user" style="margin: 0 0 0 5px;"></span></a>
					<?php } ?>
					<a href="logout.php"> Logout <span class="glyphicon glyphicon-exclamation-sign" style="margin: 0 0 0 5px;"></span></a>
				</div>
			</div>
		</div>

		<div class="rightSection">
			<?php $Dashboard->html() ?>
		</div>

	</div>

	<script type="text/javascript">
		function updateMenu( menuItem ){
			document.getElementById(menuItem).className = "selected";
			document.getElementById(menuItem).onclick = function(){ return false };
		}
		updateMenu("<?PHP echo $Dashboard->location() ?>");
	</script>

</div>

</body>
</html>