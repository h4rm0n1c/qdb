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
	
	public function addUser($username, $password, $email, $isadmin) {
		self::$db->queryf("INSERT INTO qdbusers (username, `password`, email, isadmin) VALUES ('%s', '%s', '%s', '%d')", $username, md5($password), $email, $isadmin);
	}
	
	public function updateUser($username = '', $password = '', $email = '', $isadmin = 0, $id = '') {
		if($id == '') {
			$id = $this->userid;
		}
		if(trim($password) == '') {
			self::$db->queryf("UPDATE qdbusers SET `username` = '%s', `email` = '%s', `isadmin` = '%d' WHERE userid = '%d'", $username, $email, $isadmin, $id);
		} else {
			self::$db->queryf("UPDATE qdbusers SET `username` = '%s', `password` = '%s', `email` = '%s',  `isadmin` = '%d' WHERE userid = '%d'", $username, md5($password), $email, $isadmin, $id);
		}
	}
	
	public function lookupUser($user = '', $pass = '') {
		$result = self::$db->queryf("SELECT * FROM qdbusers WHERE username = '%s' AND password = '%s'", $user, $pass);
		if (self::$db->getNumRows($result) > 0) {
			$array = self::$db->fetchArray($result);
			$this->userid = $array['userid'];
			$this->username = $array['username'];
			$this->password = $array['password'];
			$this->email = $array['email'];
			$this->isadmin = $array['isadmin'];
			return true;
		}
		else {
			return false;
		}
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