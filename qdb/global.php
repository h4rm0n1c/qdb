<?php
/*
File: global.php

Description: This file contains all the variables that are used extensively throughout the qdb.

Includes: none

Version: 1.0

Author: Harrison Mclean

License: Creative Commons
*/
include './includes/dbconnector.php';
include './includes/user.php';
include './includes/sentinel.php';

//License Content
$license = '
hQDB code &copy; 2005 h4rm0n1c<br />
Quotes &copy; Submitters';

//The Page banner
$banner_l = 'AjaxQDB';

//Mysql Database Access Information
$user = "qdb_user";
$pass = "change_me";
$db = "qdb_userold";
$host = "localhost";

//Connect to database, execute query, and return result.
//Now wraps around dbconnector class
function db_connect_query($sql) {
	global $host, $user, $pass, $db;
	if(!isset($GLOBALS['dbcon'])) {
		$GLOBALS['dbcon'] = DbConnector::getInstance();
	}
	
	return $GLOBALS['dbcon']->query($sql);
}
?>