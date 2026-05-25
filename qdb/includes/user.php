<?php
class User {
	//Database Row Data
	public $userid;
	public $username;
	public $password;
	public $email;
	public $isadmin;

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
		$result = self::$db->query("SELECT * FROM qdbusers WHERE userid = '".$this->userid."'");
		if(self::$db->getNumRows($result) < 1) {
			return false;
		}

		$array = self::$db->fetchArray($result);

		$this->userid = $array['userid'];
		$this->username = $array['username'];
		$this->password = $array['password'];
		$this->email = $array['email'];
		$this->isadmin = $array['isadmin'];
		return true;
	}

	private function loadUserFromRow($array) {
		$this->userid = $array['userid'];
		$this->username = $array['username'];
		$this->password = $array['password'];
		$this->email = $array['email'];
		$this->isadmin = $array['isadmin'];
	}

	public static function hashPassword($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}

	private function isLegacyMd5Hash($hash) {
		return preg_match('/^[a-f0-9]{32}$/i', $hash) === 1;
	}

	private function updatePasswordHash($userid, $hash) {
		self::$db->queryf("UPDATE qdbusers SET `password` = '%s' WHERE userid = '%d'", $hash, $userid);
	}

	public function addUser($username, $password, $email, $isadmin) {
		self::$db->queryf("INSERT INTO qdbusers (username, `password`, email, isadmin) VALUES ('%s', '%s', '%s', '%d')", $username, self::hashPassword($password), $email, $isadmin);
	}

	public function updateUser($username = '', $password = '', $email = '', $isadmin = 0, $id = '') {
		if($id == '') {
			$id = $this->userid;
		}
		if(trim($password) == '') {
			self::$db->queryf("UPDATE qdbusers SET `username` = '%s', `email` = '%s', `isadmin` = '%d' WHERE userid = '%d'", $username, $email, $isadmin, $id);
		} else {
			self::$db->queryf("UPDATE qdbusers SET `username` = '%s', `password` = '%s', `email` = '%s',  `isadmin` = '%d' WHERE userid = '%d'", $username, self::hashPassword($password), $email, $isadmin, $id);
		}
	}

	public function lookupUser($user = '', $pass = '') {
		$result = self::$db->queryf("SELECT * FROM qdbusers WHERE username = '%s' LIMIT 1", $user);
		if (self::$db->getNumRows($result) > 0) {
			$array = self::$db->fetchArray($result);
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
		$result = self::$db->queryf("SELECT * FROM qdbusers WHERE username = '%s' LIMIT 1", $user);
		if (self::$db->getNumRows($result) < 1) {
			return false;
		}

		$array = self::$db->fetchArray($result);
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
		$result = self::$db->queryf("SELECT userid FROM qdbusers WHERE (username = '%s' AND userid != '%d')", $user, $exclude);
		return (self::$db->getNumRows($result) > 0);
	}

	public function getAllUsers($select_options = false) {
		$users = array();

		if($select_options == true) {
			$query = "SELECT userid,username FROM qdbusers";
		} else {
			$query = "SELECT userid,username,email,enabled FROM qdbusers";
		}

		$result = self::$db->query($query);

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
