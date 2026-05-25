<?php
/*
File: global.php

Description: This file contains all the variables that are used extensively throughout the qdb.

Includes: none

Version: 1.0

Author: Harrison Mclean

License: Creative Commons
*/
if (!function_exists('qdb_env')) {
	function qdb_env($name, $default) {
		$value = getenv($name);
		return $value === false ? $default : $value;
	}
}

$qdb_config = array(
	'db_host' => qdb_env('QDB_DB_HOST', 'localhost'),
	'db_user' => qdb_env('QDB_DB_USER', 'qdb_user'),
	'db_pass' => qdb_env('QDB_DB_PASS', 'change_me'),
	'db_name' => qdb_env('QDB_DB_NAME', 'qdb_userold'),
	'session_cookie_path' => qdb_env('QDB_SESSION_COOKIE_PATH', '/hqdb/'),
	'session_cookie_secure' => filter_var(qdb_env('QDB_SESSION_COOKIE_SECURE', 'false'), FILTER_VALIDATE_BOOLEAN),
);

$local_config_file = __DIR__ . '/local_config.php';
if (file_exists($local_config_file) && !is_readable($local_config_file)) {
	trigger_error('qdb/local_config.php exists but is not readable by PHP. Check owner/group/permissions.', E_USER_ERROR);
}
if (is_readable($local_config_file)) {
	$local_config = require $local_config_file;
	if (is_array($local_config)) {
		$qdb_config = array_merge($qdb_config, $local_config);
	}
}

//Mysql Database Access Information
$user = $qdb_config['db_user'];
$pass = $qdb_config['db_pass'];
$db = $qdb_config['db_name'];
$host = $qdb_config['db_host'];
$qdb_session_cookie_path = $qdb_config['session_cookie_path'];
$qdb_session_cookie_secure = filter_var($qdb_config['session_cookie_secure'], FILTER_VALIDATE_BOOLEAN);

require_once __DIR__ . '/includes/dbconnector.php';
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/sentinel.php';

//License Content
$license = '
hQDB code &copy; 2005 h4rm0n1c<br />
Quotes &copy; Submitters';

//The Page banner
$banner_l = 'AjaxQDB';

//Connect to database, execute query, and return result.
//Now wraps around dbconnector class
function db_connect_query($sql) {
	global $host, $user, $pass, $db;
	if(!isset($GLOBALS['dbcon'])) {
		$GLOBALS['dbcon'] = DbConnector::getInstance();
	}

	return $GLOBALS['dbcon']->query($sql);
}

function db_prepared_query($sql, $types = '', $params = array()) {
	return DbConnector::getInstance()->preparedQuery($sql, $types, $params);
}
?>
