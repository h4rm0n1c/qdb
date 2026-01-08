<?php
class Msg {
	private static $messages = array();
	private static $instance;
	
	private function __construct() {
	}
	
	public static function getInstance() {
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
		
		return self::$instance;
	}
	
	// insert add message function here
	public static function addMsg($msg, $colour) {
	}
	
	public static function displayMsgs() {
		if(Msg::checkMsgs()) {
			$msgOutput = '<blockquote><tt>';
			foreach(self::$messages as $message) {
				$msgOutput .= '* <b>'.$message.'</b><br />';
			}
			$msgOutput .= '</tt></blockquote>';
			return $msgOutput;
		} else {
			return '';
		}
	}
	
	public static function checkMsgs() {
		if(count(self::$messages) > 0) {
			return true;
		}
		return false;
	}
}
?>