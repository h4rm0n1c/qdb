<?php
include './global.php';
include './includes/library.php';
//token();

$admin_actions = array('approve', 'reject', 'kill', 'unflag');
$public_actions = array('rox', 'sox', 'sux');
$request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

if (!isset($request['qid']) || !ctype_digit((string) $request['qid'])) { die('Error: quote id not specified'); } else { $qid = (int) $request['qid']; }
if (!isset($request['type'])) { die('Error: vote type not specified'); } else { $type = $request['type']; }

$is_admin_action = in_array($type, $admin_actions, true);
if (!$is_admin_action) {
	jsHeader();
	if (!in_array($type, $public_actions, true)) { die('Error: Invalid vote type Specified'); }
	if (!isset($_GET['callback'])) { die('Error: callback not specified'); }
} else {
	header('Content-type: application/json');
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		echo json_encode(array('qid' => $qid, 'newscore' => 'none', 'error' => true, 'errormsg' => 'Admin actions require POST'));
		exit;
	}
	admin_session();
	if (!isset($_POST['csrf']) || !qdb_csrf_validate($_POST['csrf'])) {
		http_response_code(403);
		echo json_encode(array('qid' => $qid, 'newscore' => 'none', 'error' => true, 'errormsg' => 'Invalid CSRF token'));
		exit;
	}
}

//if ($_GET['token'] == $_SESSION['token']) {
//}

$newscore = 'none';
$error = false;
$errormsg = '';

switch($type) {
	case 'rox':
		$newscore = count_vote($qid, 1);
	break;
	
	case 'sox':
		$newscore = count_vote($qid, 0);
	break;
	
	case 'sux':
		quote_sux($qid);
	break;

	case 'approve':
		do_admin('approve', $qid);
		$newscore = 'pending_';
	break;
	
	case 'reject':
		do_admin('kill', $qid);
		$newscore = 'pending_';
	break;
	
	case 'kill':
		do_admin('kill', $qid);
		$newscore = 'flagged_';
	break;
	
	case 'unflag':
		do_admin('unflag', $qid);
		$newscore = 'flagged_';
	break;

	default:
		die('Error: Invalid vote type Specified');
	break;
}

if($newscore == 'false') {
	$error = true;
	$errormsg = 'You have already voted for this quote';
}

$jsarray = array('qid' => $qid + 0, 'newscore' => $newscore, 'error' => $error, 'errormsg' => $errormsg);

if ($is_admin_action) {
	echo json_encode($jsarray);
} else {
	echo jsCallback(json_encode($jsarray));
}
?>
