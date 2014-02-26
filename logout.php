<?php

// 
// logout.php
// Charles Chavez
// 
// This script log outs any active user.
// 

include 'config.php';

isLoggedIn("login.php?error=dnt");

$userObj = hasAccess();

// Remove User's Session
site_queryCIE("DELETE FROM session WHERE DANA=?",[$userObj->DANA]);

// Unset the session variable
unset($_SESSION['CIE_session']);

header("Location: /CIE/");

?>