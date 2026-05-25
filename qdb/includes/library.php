<?php
//TODO: finish token support
function token() {
	if (!isset($_SESSION['token'])) {
		$_SESSION['token'] = md5(uniqid(rand(), TRUE));
	}
}

function count_vote($qID, $type) {
	$qID = (int) $qID;
	$ip = $_SERVER['REMOTE_ADDR'];
	$voted = false;
	$result = db_connect_query("SELECT * FROM votes WHERE ip = '$ip' AND qid = '$qID' ORDER BY vid");
	while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
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
		$result = db_connect_query($sql);
		$row = mysqli_fetch_assoc($result);
		return $row['rating'];
	}
	else {
		return 'false';
	}
}

function do_public_vote($type, $qid) {
	$actions = array(
		'rox' => 'quote_rox',
		'sox' => 'quote_sox',
		'sux' => 'quote_sux',
	);
	$qid = (int) $qid;

	if(!isset($actions[$type])) {
		return array('status' => 400, 'errormsg' => 'Invalid vote type specified');
	}

	return $actions[$type]($qid);
}

function do_admin($type, $qid) {
	$actions = array(
		'approve' => 'quote_approve',
		'kill' => 'quote_kill',
		'unflag' => 'quote_unflag',
	);
	$qid = (int) $qid;

	admin_session();
	if(!checklogin()) {
		return array('status' => 403, 'errormsg' => 'Insufficient credentials to perform requested operation');
	}

	if(!isset($actions[$type])) {
		return array('status' => 400, 'errormsg' => 'Invalid admin operation');
	}

	$actions[$type]($qid);
	return true;
}

function quote_rox($qid) {
	return count_vote($qid, 1);
}

function quote_sox($qid) {
	return count_vote($qid, 0);
}

function quote_sux($qid) {
	$qid = (int) $qid;
	$sql = "UPDATE qdb SET flagged = 1 WHERE id =".$qid;
	db_connect_query($sql);
	return 'none';
}

//Admin approve quote
function quote_approve($qid) {
	$qid = (int) $qid;
	$sql = "UPDATE qdb SET approved = 1 WHERE id = '$qid'";
	db_connect_query($sql);
}

//Kills a selected quote (deletes) from the database
function quote_kill($qid) {
	$qid = (int) $qid;
	$sql = "DELETE FROM qdb WHERE id = '".$qid."';";
	db_connect_query($sql);
}

//Unflags a quote that was flagged for review (sets the flagged field to 0)
function quote_unflag($qid) {
	$qid = (int) $qid;
	$sql = "UPDATE qdb SET flagged = 0 WHERE id =".$qid;
	db_connect_query($sql);
}
?>
