<?php
//Singleton
define ("COOKIE_NAME", 'hqdb');
if (!defined('COOKIE_PATH')) {
	define("COOKIE_PATH", isset($GLOBALS['qdb_session_cookie_path']) ? $GLOBALS['qdb_session_cookie_path'] : '/hqdb/');
}

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

			if (session_status() !== PHP_SESSION_ACTIVE) {
				session_name(COOKIE_NAME);
				session_set_cookie_params(array(
					'lifetime' => 0,
					'path' => COOKIE_PATH,
					'secure' => isset($GLOBALS['qdb_session_cookie_secure']) ? (bool) $GLOBALS['qdb_session_cookie_secure'] : false,
					'httponly' => true,
					'samesite' => 'Lax',
				));
				session_start();
			}
			$authenticated = self::$instance->authenticate();
			if($authenticated == true) {
				header("Cache-control: private");
				header("Pragma: private");
			}
			else {
				header("Cache-control: public");
				header("Pragma: public");
			}
			qdb_debug_auth_log('sentinel_after_auth', array('loggedin' => Sentinel::isLoggedIn(), 'authenticated' => (bool) $authenticated));
		}

		return self::$instance;
	}

	public function __construct(){}

	public static function isLoggedIn() {
		return self::$loggedin;
	}

	public function logout() {
		if(isset($_COOKIE['hqdb'])) {
			setcookie(COOKIE_NAME, '', array(
				'expires' => time() - (3600 * 24),
				'path' => COOKIE_PATH,
				'secure' => isset($GLOBALS['qdb_session_cookie_secure']) ? (bool) $GLOBALS['qdb_session_cookie_secure'] : false,
				'httponly' => true,
				'samesite' => 'Lax',
			));
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
		if ($user == '' && $pass == '') {
			if(isset($_SESSION['password'])) {
				unset($_SESSION['password']);
			}

			if(isset($_SESSION['userid']) && self::$user->loadUserById($_SESSION['userid'])) {
				$_SESSION['userid'] = self::$user->userid;
				$_SESSION['username'] = self::$user->username;
				$_SESSION['isadmin'] = self::$user->isadmin;
				self::$loggedin = true;
				return true;
			}
			else {
				self::$loggedin = false;
				return false;
			}
		} else {
			//look up user in database and set session vars if authentic
			if(self::$user->authenticatePassword($user, $pass)) {
				session_regenerate_id(true);
				if(isset($_SESSION['password'])) {
					unset($_SESSION['password']);
				}
				$_SESSION['userid'] = self::$user->userid;
				$_SESSION['username'] = self::$user->username;
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

function qdb_debug_auth_log($label, $extra = array()) {
	if (empty($GLOBALS['qdb_debug_auth'])) {
		return;
	}

	$sessionName = session_name();
	$sessionId = session_id();
	$cookieValue = isset($_COOKIE[$sessionName]) ? (string) $_COOKIE[$sessionName] : '';
	$sessionKeys = array();
	if (isset($_SESSION) && is_array($_SESSION)) {
		foreach (array('userid', 'username', 'isadmin', 'csrf_token', 'password') as $key) {
			$sessionKeys[$key] = array_key_exists($key, $_SESSION);
		}
	}

	$data = array(
		'label' => $label,
		'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
		'query' => isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '',
		'session_status' => session_status(),
		'session_name' => $sessionName,
		'session_id_present' => $sessionId !== '',
		'session_id_prefix' => $sessionId === '' ? '' : substr($sessionId, 0, 8),
		'cookie_present' => $cookieValue !== '',
		'cookie_prefix' => $cookieValue === '' ? '' : substr($cookieValue, 0, 8),
		'session_keys' => $sessionKeys,
		'post_keys' => isset($_POST) && is_array($_POST) ? array_keys($_POST) : array(),
	);

	if (class_exists('Sentinel', false) && isset(Sentinel::$loggedin)) {
		$data['sentinel_loggedin'] = Sentinel::isLoggedIn();
	}

	foreach ($extra as $key => $value) {
		$data[$key] = $value;
	}

	error_log('QDB_AUTH_DEBUG ' . json_encode($data));
}

function admin_session() {
	if(!isset($GLOBALS['sentinel'])) {
		$GLOBALS['sentinel'] = Sentinel::getInstance();
	}
}

function qdb_csrf_token() {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		admin_session();
	}

	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}

	return $_SESSION['csrf_token'];
}

function qdb_csrf_validate($token) {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		admin_session();
	}

	return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function qdb_csrf_hidden_input() {
	return '<input type="hidden" name="csrf" value="' . htmlspecialchars(qdb_csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
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
