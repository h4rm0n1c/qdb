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
			self::$link = mysqli_connect($host, $user, $pass, $db);
			if (!self::$link) {
				trigger_error("MySQL Connection failed: " . mysqli_connect_error());
			}
		}
		
		if (!mysqli_select_db(self::$link, $db)) {
			trigger_error(mysqli_error(self::$link));
		}
		return self::$instance;
	}

	//*** Function: __construct, Purpose: do nothing for singleton ***
	private function __construct()
	{
	}
	
	//*** Function: query, Purpose: Execute a database query ***
	public function query($query) {
		$this->theQuery = $query;
		$result = mysqli_query(self::$link, $query);
		
		if($result === false) {
			trigger_error("MySQL Query error: " . mysqli_error(self::$link));
		} else {
			return $result;
		}
	}

	public function preparedQuery($query, $types = '', $params = array()) {
		$this->theQuery = $query;
		$stmt = mysqli_prepare(self::$link, $query);

		if($stmt === false) {
			trigger_error("MySQL Prepare error: " . mysqli_error(self::$link));
			return false;
		}

		if($types !== '') {
			if(strlen($types) !== count($params)) {
				mysqli_stmt_close($stmt);
				trigger_error("MySQL Prepare error: parameter count does not match type string");
				return false;
			}

			$bind_args = array($types);
			foreach($params as $key => $value) {
				$bind_args[] = &$params[$key];
			}

			if(!call_user_func_array(array($stmt, 'bind_param'), $bind_args)) {
				$error = mysqli_stmt_error($stmt);
				mysqli_stmt_close($stmt);
				trigger_error("MySQL Bind error: " . $error);
				return false;
			}
		}

		if(!mysqli_stmt_execute($stmt)) {
			$error = mysqli_stmt_error($stmt);
			mysqli_stmt_close($stmt);
			trigger_error("MySQL Execute error: " . $error);
			return false;
		}

		if(mysqli_stmt_field_count($stmt) > 0) {
			$result = mysqli_stmt_get_result($stmt);
			if($result === false) {
				$error = mysqli_stmt_error($stmt);
				mysqli_stmt_close($stmt);
				trigger_error("MySQL Result error: " . $error);
				return false;
			}
			mysqli_stmt_close($stmt);
			return $result;
		}

		mysqli_stmt_close($stmt);
		return true;
	}

	private function queryf_callback($match) {
		switch ($match[1]) {
			case '%d': //Decimal Integer
				return (int) array_shift($this->args);
			case '%s': //String
				if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
					return array_shift($this->args);
				}
				return mysqli_real_escape_string(self::$link, array_shift($this->args));
			case '%%': //%
				return '%';
			case '%f': //FLOAT
				return (float) array_shift($this->args);
			case '%b': //BLOB
				if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
					return array_shift($this->args);
				}
				return mysqli_real_escape_string(self::$link, array_shift($this->args));
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
		return mysqli_insert_id(self::$link);
	}
	
	//*** Function: getQuery, Purpose: Returns the last database query, for debugging ***
	public function getQuery() {
		return $this->theQuery;
	}
	
	//*** Function: getNumRows, Purpose: Return row count, MySQL version ***
	public function getNumRows($result) {
		return mysqli_num_rows($result);
	}
	
	//*** Function: fetchArray, Purpose: Get array of query results ***
	public function fetchArray($result) {
		return mysqli_fetch_assoc($result);
	}
	
	public function fetchAssoc($result) {
		return mysqli_fetch_array($result);
	}
	
	//*** Function: __destroy, Purpose: Close the connection ***
	public function __destroy() {
		mysqli_close(self::$link);
	}
	
}
?>
