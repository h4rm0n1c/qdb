<?php
class User {
	//Database Row Data
	public $userid;
	public $username;
	public $password;
	public $email;
	public $isadmin;
	public $enabled;

	//Database var
	public static $db;

	public function __construct($userid = 1) {
		$this->userid = $userid;

		if(!isset(self::$db)) {
			self::$db = DbConnector::getInstance();
		}
	}

	//Load user row from database based on uid
	public function loadUser() {
		return $this->loadUserById($this->userid);
	}

	public function loadUserById($userid) {
		$userid = (int) $userid;
		if ($userid < 1) {
			return false;
		}

		$result = db_prepared_query("SELECT userid, username, `password`, email, isadmin, enabled FROM qdbusers WHERE userid = ? LIMIT 1", 'i', array($userid));
		if(self::$db->getNumRows($result) < 1) {
			return false;
		}

		$array = self::$db->fetchArray($result);
		if (isset($array['enabled']) && (int) $array['enabled'] !== 1) {
			return false;
		}
		$this->loadUserFromRow($array);
		return true;
	}

	private function loadUserFromRow($array) {
		$this->userid = (int) $array['userid'];
		$this->username = $array['username'];
		$this->password = $array['password'];
		$this->email = $array['email'];
		$this->isadmin = (int) $array['isadmin'];
		$this->enabled = isset($array['enabled']) ? (int) $array['enabled'] : 1;
	}

	public static function hashPassword($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}

	private function isLegacyMd5Hash($hash) {
		return preg_match('/^[a-f0-9]{32}$/i', $hash) === 1;
	}

	private function updatePasswordHash($userid, $hash) {
		$userid = (int) $userid;
		db_prepared_query("UPDATE qdbusers SET `password` = ? WHERE userid = ?", 'si', array($hash, $userid));
	}

	public function addUser($username, $password, $email, $isadmin) {
		$username = trim($username);
		$email = trim($email);
		$isadmin = (int) $isadmin === 1 ? 1 : 0;
		$hash = self::hashPassword($password);

		db_prepared_query("INSERT INTO qdbusers (username, `password`, email, isadmin, enabled) VALUES (?, ?, ?, ?, 1)", 'sssi', array($username, $hash, $email, $isadmin));
	}

	public function updateUser($username = '', $password = '', $email = '', $isadmin = 0, $id = '') {
		if($id == '') {
			$id = $this->userid;
		}
		$id = (int) $id;
		$username = trim($username);
		$email = trim($email);
		$isadmin = (int) $isadmin === 1 ? 1 : 0;
		if ($id === 1) {
			$isadmin = 1;
		}

		if(trim($password) == '') {
			db_prepared_query("UPDATE qdbusers SET `username` = ?, `email` = ?, `isadmin` = ? WHERE userid = ?", 'ssii', array($username, $email, $isadmin, $id));
		} else {
			$hash = self::hashPassword($password);
			db_prepared_query("UPDATE qdbusers SET `username` = ?, `password` = ?, `email` = ?, `isadmin` = ? WHERE userid = ?", 'sssii', array($username, $hash, $email, $isadmin, $id));
		}
	}

	public function lookupUser($user = '', $pass = '') {
		$user = trim($user);
		$result = db_prepared_query("SELECT userid, username, `password`, email, isadmin, enabled FROM qdbusers WHERE username = ? LIMIT 1", 's', array($user));
		if (self::$db->getNumRows($result) > 0) {
			$array = self::$db->fetchArray($result);
			if (isset($array['enabled']) && (int) $array['enabled'] !== 1) {
				return false;
			}
			if (!hash_equals($array['password'], $pass)) {
				return false;
			}
			$this->loadUserFromRow($array);
			return true;
		}
		else {
			return false;
		}
	}

	public function authenticatePassword($user = '', $pass = '') {
		$user = trim($user);
		$result = db_prepared_query("SELECT userid, username, `password`, email, isadmin, enabled FROM qdbusers WHERE username = ? LIMIT 1", 's', array($user));
		if (self::$db->getNumRows($result) < 1) {
			return false;
		}

		$array = self::$db->fetchArray($result);
		if (isset($array['enabled']) && (int) $array['enabled'] !== 1) {
			return false;
		}
		$storedHash = $array['password'];

		if (password_verify($pass, $storedHash)) {
			$this->loadUserFromRow($array);
			if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
				$newHash = self::hashPassword($pass);
				$this->updatePasswordHash($this->userid, $newHash);
				$this->password = $newHash;
			}
			return true;
		}

		if ($this->isLegacyMd5Hash($storedHash) && hash_equals(strtolower($storedHash), md5($pass))) {
			$this->loadUserFromRow($array);
			$newHash = self::hashPassword($pass);
			$this->updatePasswordHash($this->userid, $newHash);
			$this->password = $newHash;
			return true;
		}

		return false;
	}

	public function unameExists($user = '', $exclude = 0) {
		$user = trim($user);
		$exclude = (int) $exclude;
		$result = db_prepared_query("SELECT userid FROM qdbusers WHERE username = ? AND userid != ?", 'si', array($user, $exclude));
		return (self::$db->getNumRows($result) > 0);
	}

	public function getAllUsers($select_options = false) {
		$users = array();

		if($select_options == true) {
			$query = "SELECT userid,username FROM qdbusers";
		} else {
			$query = "SELECT userid,username,email,enabled FROM qdbusers";
		}

		$result = db_prepared_query($query);

		while($row = self::$db->fetchArray($result)) {
			if($select_options == true) {
				$users[$row['userid']] = $row['username'];
			} else {
				$users[] = $row;
			}
		}

		return $users;
	}
}
?>
