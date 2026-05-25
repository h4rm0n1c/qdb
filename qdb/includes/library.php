<?php
//TODO: finish token support
function token() {
	if (!isset($_SESSION['token'])) {
		$_SESSION['token'] = md5(uniqid(rand(), TRUE));
	}
}

function count_vote($qID, $type) {
	$qID = (int) $qID;
	if ($qID < 1) {
		return 'false';
	}
	$type = (int) $type === 1 ? 1 : 0;
	$ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

	$result = db_prepared_query("SELECT vid FROM votes WHERE ip = ? AND qid = ? LIMIT 1", 'si', array($ip, $qID));
	if(mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		return 'false';
	}

	$delta = $type === 1 ? 1 : -1;
	db_prepared_query("UPDATE qdb SET rating = rating + ? WHERE id = ?", 'ii', array($delta, $qID));
	db_prepared_query("INSERT INTO votes (ip, qid) VALUES (?, ?)", 'si', array($ip, $qID));

	$result = db_prepared_query("SELECT rating FROM qdb WHERE id = ?", 'i', array($qID));
	$row = mysqli_fetch_assoc($result);
	return $row ? $row['rating'] : 'false';
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
	if($qid < 1) {
		return array('status' => 400, 'errormsg' => 'Invalid quote id');
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
	if($qid < 1) {
		return array('status' => 400, 'errormsg' => 'Invalid quote id');
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
	if($qid < 1) {
		return 'none';
	}
	db_prepared_query("UPDATE qdb SET flagged = 1 WHERE id = ?", 'i', array($qid));
	return 'none';
}

//Admin approve quote
function quote_approve($qid) {
	$qid = (int) $qid;
	if($qid < 1) {
		return false;
	}
	db_prepared_query("UPDATE qdb SET approved = 1 WHERE id = ?", 'i', array($qid));
	return true;
}

//Kills a selected quote (deletes) from the database
function quote_kill($qid) {
	$qid = (int) $qid;
	if($qid < 1) {
		return false;
	}
	db_prepared_query("DELETE FROM qdb WHERE id = ?", 'i', array($qid));
	return true;
}

//Unflags a quote that was flagged for review (sets the flagged field to 0)
function quote_unflag($qid) {
	$qid = (int) $qid;
	if($qid < 1) {
		return false;
	}
	db_prepared_query("UPDATE qdb SET flagged = 0 WHERE id = ?", 'i', array($qid));
	return true;
}
?>
