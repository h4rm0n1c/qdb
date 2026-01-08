<?php
////////////////////////////////////////////////////////////////////////////////////////
// Class: DbConnector
// Purpose: Connect to a database, MySQL version
// THIS IS A SINGLETON, PLEASE USE DbConnector::getInstance() to get the instance of this class
///////////////////////////////////////////////////////////////////////////////////////

class DbConnector {

	public $theQuery;
	public static $link;
	private $args;
	
	private static $instance;
	
	public function __clone() {
		trigger_error('Clone of DbConnector class is not allowed.', E_USER_ERROR);
	}
	
	// The singleton method
	public static function getInstance()  {
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
		
		// db settings
		/*$user = "qdb_user";
		$pass = "change_me";
		$db = "qdb_userold";
		$host = "localhost";*/
		global $user, $pass, $db, $host;
	
		// Connect to the database
		if(!isset(self::$link)) {
			self::$link = mysql_connect($host, $user, $pass) or trigger_error("MySQL Connection failed: ".mysql_error());
		}
		
		mysql_select_db($db) or trigger_error(mysql_error());
		return self::$instance;
	}

	//*** Function: __construct, Purpose: do nothing for singleton ***
	private function __construct()
	{
	}
	
	//*** Function: query, Purpose: Execute a database query ***
	public function query($query) {
		$this->theQuery = $query;
		$result = mysql_query($query, self::$link);
		
		if($result === false) {
			trigger_error("MySQL Query error: ".mysql_error());
		} else {
			return $result;
		}
	}
	
	private function queryf_callback($match) {
		switch ($match[1]) {
			case '%d': //Decimal Integer
				return (int) array_shift($this->args);
			case '%s': //String
				if(get_magic_quotes_gpc()) {
					return array_shift($this->args);
				}
				return mysql_escape_string(array_shift($this->args));
			case '%%': //%
				return '%';
			case '%f': //FLOAT
				return (float) array_shift($this->args);
			case '%b': //BLOB
				if(get_magic_quotes_gpc()) {
					return array_shift($this->args);
				}
				return mysql_escape_string(array_shift($this->args));
		}
	}
	
	//*** Function: queryf, Purpose: Database query, printf style ***
	/*
	 *	%d = decimal integer
	 *	%s = string
	 *	%% = %
	 *	%f = float
	 *	%b = binary data (BLOB), don't encase in single quotes!
	 */
	public function queryf($query) {
		$this->args = func_get_args();
		array_shift($this->args);
		
		$query = preg_replace_callback('/(%d|%s|%%|%f|%b)/', array($this, "queryf_callback"), $query);
		$this->args = "";
		return $this->query($query);
	}
	
	public function getInsertId() {
		return mysql_insert_id(self::$link);
	}
	
	//*** Function: getQuery, Purpose: Returns the last database query, for debugging ***
	public function getQuery() {
		return $this->theQuery;
	}
	
	//*** Function: getNumRows, Purpose: Return row count, MySQL version ***
	public function getNumRows($result) {
		return mysql_num_rows($result);
	}
	
	//*** Function: fetchArray, Purpose: Get array of query results ***
	public function fetchArray($result) {
		return mysql_fetch_assoc($result);
	}
	
	public function fetchAssoc($result) {
		return mysql_fetch_array($result);
	}
	
	//*** Function: __destroy, Purpose: Close the connection ***
	public function __destroy() {
		mysql_close(self::$link);
	}
	
}
?>