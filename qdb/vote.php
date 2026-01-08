<?php
include './global.php';
include './includes/library.php';
jsHeader();
//token();

if (!isset($_GET['qid'])) { die('Error: quote id not specified'); } else { $qid = $_GET['qid']; }
if (!isset($_GET['type'])) { die('Error: vote type not specified'); } else { $type = $_GET['type']; }
if (!isset($_GET['callback'])) { die('Error: callback not specified'); }

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

echo jsCallback(json_encode($jsarray));
?>