<?php
require_once __DIR__ . '/global.php';
require_once __DIR__ . '/includes/library.php';
//token();

header('Content-Type: application/json; charset=utf-8');

function vote_json_response($qid, $newscore, $error, $errormsg, $status = 200) {
	http_response_code($status);
	echo json_encode(array('qid' => $qid, 'newscore' => $newscore, 'error' => $error, 'errormsg' => $errormsg));
	exit;
}

function vote_json_error($qid, $errormsg, $status) {
	vote_json_response($qid, 'none', true, $errormsg, $status);
}

function vote_request_value($key) {
	if (isset($_POST[$key])) {
		return $_POST[$key];
	}
	if (isset($_GET[$key])) {
		return $_GET[$key];
	}
	return null;
}

$admin_actions = array('approve', 'reject', 'kill', 'unflag');
$public_actions = array('rox', 'sox', 'sux');
$qid_param = vote_request_value('qid');
$type = vote_request_value('type');

if ($qid_param === null || !ctype_digit((string) $qid_param) || (int) $qid_param <= 0) {
	vote_json_error(0, 'Invalid quote id', 400);
}
$qid = (int) $qid_param;

if ($type === null || (!in_array($type, $admin_actions, true) && !in_array($type, $public_actions, true))) {
	vote_json_error($qid, 'Invalid vote type specified', 400);
}

$is_admin_action = in_array($type, $admin_actions, true);
if ($is_admin_action) {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		vote_json_error($qid, 'Admin actions require POST', 405);
	}
	admin_session();
	if (!isset($_POST['csrf']) || !qdb_csrf_validate($_POST['csrf'])) {
		vote_json_error($qid, 'Invalid CSRF token', 403);
	}
}

//if ($_GET['token'] == $_SESSION['token']) {
//}

$newscore = 'none';
$error = false;
$errormsg = '';

switch($type) {
	case 'rox':
	case 'sox':
	case 'sux':
		$public_result = do_public_vote($type, $qid);
		if (is_array($public_result)) { vote_json_error($qid, $public_result['errormsg'], $public_result['status']); }
		$newscore = $public_result;
	break;

	case 'approve':
		$admin_result = do_admin('approve', $qid);
		if ($admin_result !== true) { vote_json_error($qid, $admin_result['errormsg'], $admin_result['status']); }
		$newscore = 'pending_';
	break;

	case 'reject':
		$admin_result = do_admin('kill', $qid);
		if ($admin_result !== true) { vote_json_error($qid, $admin_result['errormsg'], $admin_result['status']); }
		$newscore = 'pending_';
	break;

	case 'kill':
		$admin_result = do_admin('kill', $qid);
		if ($admin_result !== true) { vote_json_error($qid, $admin_result['errormsg'], $admin_result['status']); }
		$newscore = 'flagged_';
	break;

	case 'unflag':
		$admin_result = do_admin('unflag', $qid);
		if ($admin_result !== true) { vote_json_error($qid, $admin_result['errormsg'], $admin_result['status']); }
		$newscore = 'flagged_';
	break;

	default:
		vote_json_error($qid, 'Invalid vote type specified', 400);
	break;
}

if($newscore == 'false') {
	$error = true;
	$errormsg = 'You have already voted for this quote';
}

$jsarray = array('qid' => $qid + 0, 'newscore' => $newscore, 'error' => $error, 'errormsg' => $errormsg);

echo json_encode($jsarray);
?>
