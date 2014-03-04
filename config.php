<?php

// 
// config.php
// Charles Chavez
// 
// This file contains all the important information for this web application
// 
// 

// Session Work
session_start();

// site_queryCIE
// Provides a mechanism to query CIE database
function site_queryCIE( $Query, $Bindings ){
// Example Query site_queryCIE("select dana from user_level where access_level = ?",[1]);

	try{
		// Create a new connection.
		// You'll probably want to replace hostname with localhost in the first parameter.
		// The PDO options we pass do the following:
		// PDO::ATTR_ERRMODE enables exceptions for errors.  This is optional but can be handy.
		// PDO::ATTR_PERSISTENT disables persistent connections, which can cause concurrency issues in certain cases.  See "Gotchas".
		// PDO::MYSQL_ATTR_INIT_COMMAND alerts the connection that we'll be passing UTF-8 data.  This may not be required depending on your configuration, but it'll save you headaches down the road if you're trying to store Unicode strings in your database.  See "Gotchas".
		$link = new PDO('mysql:host=127.0.0.1;dbname=cie',
						'root',
						'',
			array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
				PDO::ATTR_PERSISTENT => false, 
				PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8mb4'
				)
			);
		
		// How will our script execute?
		// If the $Bindings variables is set to "query", then just run a straight query
		if( $Bindings == "query" ){
			$result = $link->query($Query);
			$result = $result->fetchAll(PDO::FETCH_OBJ);
		} else {

			// $handle = $link->prepare('select dana from user_level where access_level = ?');
			$handle = $link->prepare($Query);	
			$handle->execute( $Bindings );
			
			// Using the fetchAll() method might be too resource-heavy if you're selecting a truly massive amount of rows.
			// If that's the case, you can use the fetch() method and loop through each result row one by one.
			// You can also return arrays and other things instead of objects.  See the PDO documentation for details.
			$result = $handle->fetchAll(PDO::FETCH_OBJ);
		}

		return $result;
	}
	catch(PDOException $ex){
		return ($ex->getMessage());
	}

}

// hasAccess()
// This method provides security checking for the web application
// It returns the access_level and the DANA ID of the visitor. It will return a -1 if the current visitor does not have a valid session.
function hasAccess(){

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

// Checks to see if the visitor is logged in. If not, then redirect to URL and kill the page execution.
function isLoggedIn( $URL ){

	if( hasAccess()->access_level == -1 ){
		header("Location: ".$URL);
		die();
	}
}

// Some Housekeeping...

// SESSION===========================================
// We want to delete any sessions that have "experied"
// Any session that has a last_action less than current time - X will be delete

$timeWindow = 28800; // The time of 8 hours
$timeCutOff = time() - $timeWindow; 
site_queryCIE("DELETE FROM session WHERE last_action<=? ",[$timeCutOff]);

?>