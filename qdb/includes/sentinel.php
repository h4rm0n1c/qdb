<?php
//Singleton
define ("COOKIE_NAME", 'hqdb');
define("COOKIE_PATH", '/hqdb/');

class Sentinel {
	public static $user;
	public static $loggedin;
	
	public static $db;
	
	public static $instance;
	
	public function __clone() {
		trigger_error('Clone of Sentinel class is not allowed.', E_USER_ERROR);
	}

	// The singleton method
	public static function getInstance()  {
		if(!isset(self::$db)) {
			self::$db = DbConnector::getInstance();
		}
		
		if(!isset(self::$user)) {
			self::$user = new User();
		}
		
		if(!isset(self::$loggedin)) {
			self::$loggedin = false;
		}
	
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
			
			session_name('hqdb');
			//session_set_cookie_params(0, '/hqdb/');
			session_start();
			if(self::$instance->authenticate() == true) {
				header("Cache-control: private");
				header("Pragma: private");
			}
			else {
				header("Cache-control: public");
				header("Pragma: public");
			}
		}
		
		return self::$instance;
	}

	public function __construct(){}
	
	public static function isLoggedIn() {
		return self::$loggedin;
	}
	
	public function logout() {
		if(isset($_COOKIE['hqdb'])) {
			setcookie('hqdb','', time()-(3600 * 24));
		}
		
		session_destroy();
		self::$loggedin = false;
		return true;
	}
	
	public function isAdmin() {
		if(self::$user->isadmin == 1) {
			return true;
		}
		return false;
	}
	
	public function authenticate($user = '', $pass = '') {
		//if user is logged in
		if (isset($_SESSION['username']) && isset($_SESSION['password']) && $user == '' && $pass == '') {
			if(self::$user->lookupUser($_SESSION['username'], $_SESSION['password'])) {
				self::$loggedin = true;
				return true;
			}
			else {
				self::$loggedin = false;
				return false;
			}
		} else {
			//look up user in database and set session vars if authentic
			if(self::$user->lookupUser($user, md5($pass))) {
				$_SESSION['userid'] = self::$user->userid;
				$_SESSION['username'] = self::$user->username;
				$_SESSION['password'] = self::$user->password;
				$_SESSION['isadmin'] = self::$user->isadmin;
				self::$loggedin = true;
				return true;
			}
			else {
				self::$loggedin = false;
				return false;
			}
		}
	}
}

function admin_session() {
	if(!isset($GLOBALS['sentinel'])) {
		$GLOBALS['sentinel'] = Sentinel::getInstance();
	}
}

//If the current user is an Administrator (their isadmin field is set to 1)
function issuperadmin() {
	return $GLOBALS['sentinel']->isAdmin();
}

//Checks to see if the user is logged in, returns 1 if they are, 0 if they are not.
function checklogin() {
	return Sentinel::isLoggedIn();
}
?>