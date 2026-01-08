<?php
//domAjax server side library
function jsHeader() {
header('Content-type: text/javascript');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
}

function escapeString($data) {
	return str_replace(array("\r", "\n"), '\n', addslashes($data));
}

function jsCallback($jsdata) {
	return $_GET['callback']."(".$jsdata.");";
}

//TODO: finish token support
function token() {
	if (!isset($_SESSION['token'])) {
		$_SESSION['token'] = md5(uniqid(rand(), TRUE));
	}
}

function count_vote($qID, $type) {
	$ip = $_SERVER['REMOTE_ADDR'];
	$voted = false;
	$result = db_connect_query("SELECT * FROM votes WHERE ip = '$ip' AND qid = '$qID' ORDER BY vid");
	while($row = mysql_fetch_array($result)) {
		$voted = true;
	}
	if ($voted != true) {
		if ($type == 0) {
			$sql = "UPDATE qdb SET rating = rating - 1 WHERE id =".$qID;
			db_connect_query($sql);
		} else if ($type == 1) {
			$sql = "UPDATE qdb SET rating = rating + 1 WHERE id =".$qID;
			db_connect_query($sql);
		}
		
		$sql = "INSERT INTO votes SET ip = '$ip', qid = '$qID'";
		db_connect_query($sql);
		
		$sql = "SELECT rating FROM qdb WHERE id =".$qID;
		return mysql_result(db_connect_query($sql), 0, 'rating');
	}
	else {
		return 'false';
	}
}

function do_admin($type, $qid) {
	admin_session();
	if(checklogin()) {
		$type = 'quote_'.$type;
		$type($qid);
	} else {
		die('Error: Insufficient Credentials to preform requested operation');
	}
}

function quote_sux($qid) {
	$sql = "UPDATE qdb SET flagged = 1 WHERE id =".$qid;
	db_connect_query($sql);
}

//Admin approve quote
function quote_approve($qid) {
	$sql = "UPDATE qdb SET approved = 1 WHERE id = '$qid'";
	db_connect_query($sql);
}

//Kills a selected quote (deletes) from the database
function quote_kill($qid) {
	$sql = "DELETE FROM qdb WHERE id = '".$qid."';";
	db_connect_query($sql);
}

//Unflags a quote that was flagged for review (sets the flagged field to 0)
function quote_unflag($qid) {
	$sql = "UPDATE qdb SET flagged = 0 WHERE id =".$qid;
	db_connect_query($sql);
}
?>