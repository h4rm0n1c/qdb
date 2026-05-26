<?php
/*
File: common.php

Description: This file contains all the commonly used functions for the qdb.

Includes: Too damn many

Version: 1.0

Author: Harrison Mclean

License: Creative Commons
*/

//Common functions
function db_escape($value) {
	DbConnector::getInstance();
	return mysqli_real_escape_string(DbConnector::$link, $value);
}

function qdb_h($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function qdb_text_to_html($value) {
	$value = str_replace(array("\r\n", "\r"), "\n", (string) $value);
	return nl2br(qdb_h($value), false);
}

function qdb_render_legacy_html($value) {
	return (string) $value;
}

function qdb_legacy_html_to_text($value) {
	$value = preg_replace('/<br\s*\/?>/i', "\n", (string) $value);
	return html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_quote_result($result) {
	$return_string = '';
	$quote_result = 0;

	//Interpolating quote colour vars
	$greys_counter = 0;
	$div_id = "white";
	
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		if ($greys_counter & 1) {
				$div_id = "grey";
		}
		else {
				$div_id = "white";
		}
		
		$return_string .= '
<div class="'.$div_id.'">
<p class="quote">
	<a href="?'.$row['id'].'" title="PermaLink"><b>#'.$row['id'].'</b></a> <a href="#" onclick="return rox('.$row['id'].');" class="qa">+</a>(<span id="score'.$row['id'].'">'.$row['rating'].'</span>)<a href="#" onclick="return sox('.$row['id'].');" class="qa">-</a> <a href="#" onclick="return sux('.$row['id'].');" class="qa">[X]</a>
</p>
<p class="qt">
	'.qdb_render_legacy_html($row['quote']).'<br />';
		if ($row['comment'] != "") {
			$return_string .= '<i>Comment:</i> '.qdb_text_to_html($row['comment'])."<br />";
		}
$return_string .= '</p>
</div>
';
		$quote_result = 1;
		$greys_counter++;
	}
	
	if ($quote_result == 1) {
		return $return_string;
	}
	else {
		return "";
	}
}

function qdb_normalize_search_term($value, &$error) {
	$term = trim(mquotes((string) $value));
	$term = preg_replace('/\s+/', ' ', $term);
	$length = function_exists('mb_strlen') ? mb_strlen($term, 'UTF-8') : strlen($term);

	if($term === '') {
		$error = '';
		return '';
	}
	if($length < 2) {
		$error = 'Search must be at least 2 characters.';
		return '';
	}
	if($length > 100) {
		$error = 'Search must be 100 characters or fewer.';
		return '';
	}

	$error = '';
	return $term;
}

//Returns true (1) when the supplied quote id is pending (Approved field is set to 0)
function isquotepending($id) {
	$id = (int) $id;
	if($id < 1) {
		return 0;
	}
	$result = db_prepared_query("SELECT id FROM qdb WHERE approved = 0 AND id = ? LIMIT 1", 'i', array($id));
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		return 1;
	}
	return 0;
}


//Returns the number of approved quotes (Approved field is set to 1)
function count_approved() {
	$result = db_prepared_query("SELECT COUNT(*) AS count FROM qdb WHERE approved = 1");
	$row = mysqli_fetch_assoc($result);
	return $row['count'];
}

//Returns the number of pending quotes (Approved field is set to 0)
function count_pending() {
	$result = db_prepared_query("SELECT COUNT(*) AS count FROM qdb WHERE approved = 0");
	$row = mysqli_fetch_assoc($result);
	return $row['count'];
}

//Returns the average score of approved quotes (karma)
function count_karma() {
	$result = db_prepared_query("SELECT AVG(`rating`) AS avg_rating FROM qdb WHERE approved = 1");
	$row = mysqli_fetch_assoc($result);
	return $row['avg_rating'];
}

//Returns a string containing a single html formatted quote if it is approved
function single_quote() {
	$query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
	$quote_id = ctype_digit($query_string) ? (int) $query_string : 0;
	if($quote_id < 1) {
		return '<b>The Specified Quote either does not exist or has been Rejected by the Moderators</b>';
	}

	if (!isquotepending($quote_id)) {
		$result = db_prepared_query("SELECT * FROM qdb WHERE id = ?", 'i', array($quote_id));
		$quote = format_quote_result($result);
		if ($quote == '') {
			return '<b>The Specified Quote either does not exist or has been Rejected by the Moderators</b>';
		}
		else {
			return $quote;
		}
	}
	else {
		return "Quote <a href='./?$quote_id'>#".$quote_id."</a> is pending Moderation.";
	}
}

//
function navigation() {
$nav = '
	<a href="./?home">Home</a> | 
	<a href="./?latest">Latest</a> | 
	<a href="./?browse">Browse</a> | 
	<a href="./?random">Random</a> <a href="./?random1">&gt;0</a> | 
	<a href="./?top">Top 50</a><a href="./?top2">-100</a> | 
	<a href="./?bottom">Bottom 50</a> | 
	<a href="./?queue">Queue</a> | 
	<a href="./?add"><b>Add Quote</b></a> | 
	<a href="./?search">Search</a>';
	
	if(checklogin()) {
		$nav = '<a href="./?admin">Admin</a> | ' . $nav . ' | <a href="./?logout">Logout</a>';
	}
	
	return $nav;
}


//Returns the latest 50 approved quotes
function latest() {
	return format_quote_result(db_prepared_query("SELECT * FROM qdb WHERE approved = 1 ORDER BY id DESC LIMIT 51"));
}

function queue($pending) {

	$return_data = "<blockquote><tt>* <b>This is the submission queue, no it's not finished yet...</b></tt></blockquote><br />";

	if($pending > 0) {
		$return_data .= format_quote_result(db_prepared_query("SELECT * FROM qdb WHERE approved = 0 ORDER BY rand() ASC LIMIT 50"));
	} else {
		$return_data .= "No Pending Quotes Found";
	}
	
	return $return_data;
}

//Returns a string containing the content (html) for the add quote page.
function add() {
/*return '<div align="left">
       <form method="post" action="?added" onsubmit="return validateadd();">
        <center>
		<table border="0" cellpadding="2" cellspacing="0">
		<tr><td colspan="2">
		<textarea cols="98" rows="10" name="quote" class="basicinput" tabindex="1" id="quotetext"></textarea></center>
		<tr><td><input type="submit" value="Add Quote" class="basicsubmit">&nbsp;<input type="reset" value="Reset" class="basicsubmit"></td>
		<td align="right">Comment: <input type="text" size="40" maxlength="127" name="comment" class="basicinput" tabindex="2"></td>
		</tr>
		<tr><td colspan="2">&nbsp;</tr></td>
		<tr><td colspan="2"><b>Tips for approval</b>: Keep it short, Trim any useless parts, avoid inside jokes, remove any trailing laughs.</tr></td>
		</center>
		</table>
       </form>
	</div>';*/
	return "Sorry! Add Quote Functionality has been disabled, since this database is only meant to be added to by the bash dumper script!";
}

//Handles the data submitted by the add quote page
function added() {
	return "Sorry! Add Quote Functionality has been disabled, since this database is only meant to be added to by the bash dumper script!";
}

//Returns 50 randomly selected, approved quotes
//NOTE: Limited to one quote because of the CPU load truly randomised quotes create
function random() {
	return format_quote_result(db_prepared_query("SELECT * FROM qdb AS r1 JOIN (SELECT (RAND() * (SELECT MAX(id) FROM qdb)) AS rid) AS r2 WHERE r1.id >= r2.rid AND approved = 1 ORDER BY r1.id ASC LIMIT 1"));
}

//Returns 50 randomly selected, approved quotes with a score greater than zero
//NOTE: Limited to one quote because of the CPU load truly randomised quotes create
function random1($approved) {
	return format_quote_result(db_prepared_query("SELECT * FROM qdb AS r1 JOIN (SELECT (RAND() * (SELECT MAX(id) FROM qdb)) AS rid) AS r2 WHERE r1.id >= r2.rid AND approved = 1 AND rating > 0 ORDER BY r1.id ASC LIMIT 1"));
}

//Returns the top 100 rated quotes (ordered by each quote's rating field)
function top100() {
	return format_quote_result(db_prepared_query("SELECT * FROM qdb WHERE approved = 1 AND rating > 0 ORDER BY rating desc LIMIT 100"));
}

//Returns the top 50 rated quotes (ordered by each quote's rating field)
function top50() {
	return format_quote_result(db_prepared_query("SELECT * FROM qdb WHERE approved = 1 AND rating > 0 ORDER BY rating desc LIMIT 50"));
}

//Returns the bottom 50 rated quotes (ordered by each quote's rating field)
function bottom() {
	return format_quote_result(db_prepared_query("SELECT * FROM qdb WHERE approved = 1 AND rating < 1 ORDER BY rating asc LIMIT 50"));
}


//Returns the browse quotes page as a html formatted string
function browse($approved) {
	//Get the current browse page
	$approved = (int) $approved;
	$browsepage = isset($_GET['p']) ? (int) $_GET['p'] : 0;

	//If no page was specified
	if ($browsepage < 1) {
		if (isset($_GET['browse']) && $_GET['browse'] != '') {
			$browsepage = (int) $_GET['browse'];
		}
		else {
			$browsepage = 1;
		}
	}

	//Init a blank string, so I can just append any output to it.
	$return_string = '';
	
	//Get all approved quotes, and tell me how many there are.
	$rowcount = $approved;

	//Calculate the number of pages
	$pagecount = ceil($rowcount / 50);
	if($pagecount < 1) {
		$pagecount = 1;
	}
	if($browsepage < 1) {
		$browsepage = 1;
	}
	if($browsepage > $pagecount) {
		$browsepage = $pagecount;
	}

	//Init the string for storing the page links, I do this so I can put one at the top of the page
	//and one at the bottom of the page.
	$page_nav_string = '<!--Browse Page Navigation Start-->'."\n".'<center>'."\n".'<font class="qt">';

	if ($browsepage != 1) {
		$page_nav_string .= '<a href="./?browse&p=1" class="qa">Start</a> ';
	}

	if ($browsepage > 10) {
		$page_nav_string .= '<a href="./?browse&p='.($browsepage - 10).'" class="qa">-10</a> ';
	} 

	if ($browsepage != 1) {
		$page_nav_string .= '<a href="./?browse&p='.($browsepage - 1).'" class="qa">&lt;</a> ';
	}

	//Run a loop to generate all the page links
	for ($i = 1; $i <= $pagecount; $i++) {
		$padded = str_pad($i, 2, '0', STR_PAD_LEFT);
		if ($i > $browsepage + 5 || $i < $browsepage - 5) {
		}
		else {
			if ($i == $browsepage) {
				$page_nav_string .= '<font class="qa">'.$padded.'</font>';
			}
			else {
				$page_nav_string .= '<a href="./?browse&p='.$i.'" class="qa">'.$padded.'</a>';
			}
			if ($i != $pagecount && $i != $browsepage + 5) { 
				$page_nav_string .= '-';
			}
		}
	}
	
	if ($browsepage != $pagecount) {
		$page_nav_string .= ' <a href="./?browse&p='.($browsepage + 1).'" class="qa">&gt;</a> ';
	}
	
	if ($browsepage < $pagecount - 10) {
		$page_nav_string .= '<a href="./?browse&p='.($browsepage + 10).'" class="qa">+10</a> ';
	} 
	
	if ($browsepage != $pagecount) {
		$page_nav_string .= '<a href="./?browse&p='.$pagecount.'" class="qa">End</a> ';
	}
	
	//Finishing tags
	$page_nav_string .= '</font>'."\n";

	$page_dropdown = "";

	//Page Select Drop Down List
	$page_dropdown .='<form action="./?browse" name="page">'."\n".'Page: <select name="browse" onchange="javascript: document.page.submit()">'."\n";

	//Loop to create list options
	
	for ($i = 1; $i <= $pagecount; $i++) {
		if ($i == $browsepage) {
			$page_dropdown .= '<option value="'.$i.'" selected>'.$i.'</option>'."\n";
		}
		else {
			$page_dropdown .= '<option value="'.$i.'">'.$i.'</option>'."\n";
		}
	}

	$page_dropdown .= '</select>'."\n".'</form>'."\n".'</center>'."\n".'<!--Browse Page Navigation End-->'."\n";

	//Quote offset is the number of the requested page minus one, times the number of quotes per page.
	$offset = ($browsepage - 1) * 50;

	//Retrieve quotes based on quote offset
	$return_string .= format_quote_result(db_prepared_query("SELECT * FROM qdb WHERE approved = 1 ORDER BY id asc LIMIT ?, 50", 'i', array($offset)));

	//Dump it all back to the function that called me
	return $page_nav_string.$page_dropdown.$return_string.$page_nav_string;
}

function buildOptions($optionArray, $selected = '') {
	$return_string = "";
	foreach($optionArray as $value => $title) {
		if($selected != '' && $value == $selected) {
			$return_string .= '<option value="'.$value.'" selected>'.$title.'</option>';
		} else {
			$return_string .= '<option value="'.$value.'">'.$title.'</option>';
		}
	}
	return $return_string;
}

//Returns the search page as a html formatted string
function search() {
	$quote = (isset($_GET['search'])? $_GET['search'] : '');
	$order = (isset($_GET['order'])? $_GET['order'] : 'rating');
	$sort = (isset($_GET['sort'])? $_GET['sort'] : 'desc');
	$number = (isset($_GET['show'])? $_GET['show'] : '50');
	$approved = (isset($_GET['approved'])? $_GET['approved'] : '1');
	$search_error = '';
	$quote_string = '';
	$search_term = qdb_normalize_search_term($quote, $search_error);

	$orderopts = array('rating' => 'Score', 'id' => 'Number');
	$sortopts = array('asc' => 'Ascending', 'desc' => 'Descending');
	$numberopts = array('10' => 10, '25' => 25, '50' => 50);
	$approveopts = array('1' => 'Approved', '0' => 'All');

	$return_string = "";

	$return_string .= '
	<center>
		<form method="get" action="./?search">
			<table cellpadding="2" cellspacing="0">
				<tr>
					<td align="right">
						Keywords:
					</td>
					<td>
						<input type="text" name="search" size="28" class="basicinput" value="'.qdb_h($quote).'">
					</td>
					<td valign="top">
						<input type="submit" class="basicsubmit" value="Search">
					</td>
				</tr>
				<tr>
					<td align="right">
						Sort by:
					</td>
					<td colspan="2">
						<select name="order" size="1">
							'.buildOptions($orderopts, $order).'
						</select>
						<select name="sort" size="1">
							'.buildOptions($sortopts, $sort).'
						</select>
					</td>
				</tr>
				<tr>
					<td align="right">
						Display:
					</td>
					<td colspan="2">
						<select name="show" size="1">
							'.buildOptions($numberopts, $number).'
						</select>
						<select name="approved" size="1">
							'.buildOptions($approveopts, $approved).'
						</select>
					</td>
				</tr>
			</table>
		</form>
	</center>
';

	if ($search_error !== '') {
		$return_string .= '<b>'.qdb_h($search_error).'</b>';
	}
	else if ($search_term != '') {
		if ($order == '' || ($order != 'rating' && $order != 'id')) {
			$order = "rating";
		}
		if ($sort == '' || ($sort != "desc" && $sort != "asc")) {
			$sort = 'desc';
		}
		$number = (int) $number;
		if ($number < 10 || $number > 50) {
			$number = 50;
		}
		if ($approved == '1' || $approved == '') {
			$approved_sql = " AND approved = 1";
		}
		else if ($approved == '0') {
			$approved_sql = "";
		}
		else {
			$approved_sql = " AND approved = 1";
		}
		$sql = "SELECT * FROM qdb WHERE MATCH (`quote`) AGAINST (? IN BOOLEAN MODE)".$approved_sql." ORDER BY `".$order."` ".$sort." ,MATCH (`quote`) AGAINST(? IN BOOLEAN MODE) DESC LIMIT " . $number;

		$quote_string = format_quote_result(db_prepared_query($sql, 'ss', array($search_term, $search_term)));
	}

	if($quote_string == "" && $search_term != '' && $search_error === '') {
		$return_string .= "<b>Search found nothing</b>";
	} else {
		$return_string .= $quote_string;
	}
	
	return $return_string;
}



//Returns the homepage as a html formatted string
function index() {
$return_string = "
<style type='text/css'>#content {padding-left: 0px; padding-top: 16px;}</style>
<table cellpadding='0' cellspacing='3' style='line-height: 21px;' border='0'>
	<tr>
		<td width='50%' valign='top'>
			This is the AjaxQDB quote management system.<br />
			AjaxQDB has features comparable to those of bash.org, maybe even better.<br />
			At the moment it is still in development and will be so until I decide to get of my ass and do somthing with it.
			<hr />
			<ul style='list-style-type: square;'>
				<li><a href='http://qdb.us'>Qdb.us Quote Database</a></li>
				<li><a href='http://bash.org'>Bash.org Quote Database</a></li>
				<li><a href='http://www.gardenvarietygeek.com'>Garden Variety Geek</a></li>
			</ul>
		</td>
		<td WIDTH='1' BGCOLOR='#3F7FFF' rowspan='2'>
			<FONT SIZE='1' COLOR='#3F7FFF'>|</FONT>
		</td>
		<td width='50%' valign='top'>
			".news()."
		</td>
	</tr>
	<tr>
		<td></td>
		<td>".listadmins()."</td>
	</tr>
</table>";
return $return_string;
}

//Returns the admin login page as a html formatted string
function admin() {
	global $navigation;
	$login = <<<EOF
<center>
	<form method="post" action="?admin">
	User Name: <input type="text" name="username" size="15" class="basicinput"><br />
	<p>
	Password:&nbsp;&nbsp; <input type="password" name="password" size="15" class="basicinput"><br />
	</p>
	<input type="submit" name="login" class="basicsubmit" value="Login">
	</form>
</center>
EOF;
	if(Sentinel::isLoggedIn()) {
		return adminpanel();
	} else if(isset($_POST['login'])) {
		if($GLOBALS['sentinel']->authenticate($_POST['username'], $_POST['password'])) {
			return adminpanel();
		} else {
			return $login;
		}
	} else {
		return $login;
	}
}

//Assembles the admin panel from different functions and returns the output as a html formatted string
function adminpanel() {
	global $navigation;
	$links = '<a href="./?home">Home</a> | ';
	$return_string = '<script type="text/javascript">window.qdbCsrfToken = '.json_encode(qdb_csrf_token()).';</script>';

	if(issuperadmin()) {
		$links .= '<a href="#adduser">Add User</a> | <a href="#manageusers">Manage Users</a> | ';
		$return_string .= admin_adduser();
		$return_string .= admin_manageusers();
	}
	$links .= '<a href="#changepass">Change Password</a> | <a href="#news">News</a> | <a href="#pending">Pending Quotes</a> | <a href="#flagged">Flagged Quotes</a>';
	$return_string .= admin_changepass();
	$return_string .= admin_news();
	$return_string .= admin_pending();
	$return_string .= admin_flagged();
	
	$links .= ' | <a href="./?logout">Logout</a>';
	$navigation = $links;
	return $return_string;
}

//Returns the moderator "panel" for moderating pending quotes, moderators can either approve or deny a quote
function admin_pending() {
	$return_string = '<br />
		<fieldset id="pending">
		<legend>Pending Quotes <a href="#top" >[Top]</a></legend>
		<p>Showing up to 50 pending quotes.</p>
		<div style="max-height: 400px; overflow: auto; border: 1px solid #000000; margin-bottom: 10px; padding: 5px;">
	';
	$result = db_prepared_query("SELECT * FROM qdb WHERE approved = 0 ORDER BY id ASC LIMIT 50");

	while ( $row = mysqli_fetch_array($result, MYSQLI_ASSOC) ) {
		$return_string .= '<div id="pending_'.$row['id'].'" class="pending"><p class="quote"><b>#'.$row['id'].'</b>&nbsp;('.$row['rating'].')&nbsp;<a href="#" onclick="return approve('.$row['id'].');" class="qa">[Approve]</a>&nbsp;<a href="#" onclick="return reject('.$row['id'].');" class="qa">[Reject]</a>';
		$return_string .= "<p class='qt'>".qdb_render_legacy_html($row["quote"])."</p></div>";
	}

	$return_string .= '</div>';
	$return_string .= '</fieldset>';

	return $return_string;
}

//Returns the moderator "panel" for moderating quotes flagged for review, moderators can unflag or kill the quote
function admin_flagged() {

	$return_string = '<br />
	<fieldset id="flagged">
		<legend>Flagged Quotes <a href="#top" >[Top]</a></legend>
		<div style="max-height: 400px; overflow: auto; border: 1px solid #000000; padding: 5px;">
	';

	$adminid = (int) $_SESSION['userid'];
	$result = db_prepared_query("SELECT * FROM qdb WHERE flagged = 1 and approved = 1 AND modid = ?", 'i', array($adminid));

	while ( $row = mysqli_fetch_array($result, MYSQLI_ASSOC) ) {
		$return_string .= '<div id="flagged_'.$row['id'].'" class="flagged"><p class="quote"><b>#'.$row['id'].'</b>&nbsp;<a href="#" onclick="return kill('.$row['id'].');" class="qa">[Kill]</a>'."\n".'<a href="#" onclick="return unflag('.$row['id'].');" class="qa">[UnFlag]</a>'."\n".'</p>';
		$return_string .= '<p class="qt">'.qdb_render_legacy_html($row["quote"]).'</p></div>';
	}

	$return_string .= '</div></fieldset>';

	return $return_string;
}

//Returns the moderator "panel" to allow a moderator to change their password
function admin_changepass() {
	$return_string = '<br /><fieldset id="changepass"><legend>Change Password <a href="#top" >[Top]</a></legend><table width="100%" cellpadding="0"><tr><td><form action="./?changepass" method="post">'.qdb_csrf_hidden_input().'<table align="left"><tr><td align="right">New password: <input type="password" name="pass" size="28" class="basicinput"></td></tr><tr><td align="right">Repeat Password: <input type="password" name="passchk" size="28" class="basicinput"></td></tr><tr><td align="right"><input type="submit" name="submit" class="basicsubmit" value="Change Password"></td></tr></table></form></td></tr></table>';
	$return_string .= '<p>When you have changed your password, you will be automatically logged out, then just log back in with your new password.<br />
	Passwords must be between 6 and 20 characters long and can only be alphanumeric, in uppercase and/or lowercase (0-9 A-Z a-z)</p></fieldset>';
	return $return_string;
}

//Does the actual password changing for admin_changepass()
function changepass() {


	if (checklogin()) {
		if (!qdb_csrf_validate(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
			header('Refresh: 3;URL=./?admin');
			return '<b>Error</b>: Invalid security token, please try again.';
		}
		$pass = $_POST['pass'];
		$passchk = $_POST['passchk'];
		if ($pass == $passchk) {
			//if the password is not alphanumeric, is made of spaces or is not between 6 and 20 characters in length, error out
			if (preg_match("/^[a-zA-Z0-9]+$/",$pass)&&!trim($pass)==''&&!(strlen($pass)<6||strlen($pass)>20)) {
				$userid = (int) $_SESSION['userid'];
				$pass = User::hashPassword($pass);
				db_prepared_query("UPDATE qdbusers SET password = ? WHERE userid = ? LIMIT 1", 'si', array($pass, $userid));
				setcookie(COOKIE_NAME, "", array(
					'expires' => time() - 100,
					'path' => COOKIE_PATH,
					'secure' => isset($GLOBALS['qdb_session_cookie_secure']) ? (bool) $GLOBALS['qdb_session_cookie_secure'] : false,
					'httponly' => true,
					'samesite' => 'Lax',
				));
				header("Location: ./?admin");
			}
			else {
				header('Refresh: 3;URL=./?admin');
				$return_string = '<b>Error</b>: Password is invalid, please specify a valid password.';
				return $return_string;
			}		
		}
		else {
		header('Refresh: 3;URL=./?admin');
		$return_string = '<b>Error</b>: Passwords entered do not match each-other';
		return $return_string;
		}
	}
	else
	{
		header('Refresh: 0;URL=./');
		return "Stop trying to hack, you suck.";
	}
}

//Returns the admin "panel" that allows an administrator to add new moderators or administrators
function admin_adduser() {
	$return_string = '
		<fieldset id="adduser">
			<legend>Add User <a href="#top" >[Top]</a></legend>
			<table width="100%" cellpadding="0">
				<tr>
					<td>
						<form action="./?adduser" method="post">
							'.qdb_csrf_hidden_input().'
							<table align="left">
								<tr>
									<td align="right">
										Username: <input type="text" name="newuser" size="28" class="basicinput">
									</td>
								</tr>
								<tr>
									<td align="right">
										Email: <input type="text" name="email" size="28" class="basicinput">
									</td>
								</tr>
								<tr>
									<td align="right">
										Is Admin: <select name="isadmin"><option value="0">No</option><option value="1">Yes</option></select>
									</td>
								</tr>
								<tr>
									<td align="right">
										Temporary Password: <input type="password" name="newpass" size="28" class="basicinput">
									</td>
								</tr>
								<tr>
									<td align="right">
										Repeat Temporary Password: <input type="password" name="newpasschk" size="28" class="basicinput">
									</td>
								</tr>
								<tr>
									<td>
										Give this to the invited user out-of-band; they should change it after first login.
									</td>
								</tr>
								<tr>
									<td align="right">
										<input type="submit" name="submit" class="basicsubmit" value="Add User">
									</td>
								</tr>
							</table>
						</form>
					</td>
				</tr>
			</table>
		</fieldset>';
	return $return_string;

}

//The actual function that does the user adding for admin_adduser()
function adduser() {
	if(checklogin()){
		if(issuperadmin()) {
			if (!qdb_csrf_validate(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
				header('Refresh: 3;URL=./?admin');
				return '<b>Error</b>: Invalid security token, please try again.';
			}
			$nUSER = trim($_POST['newuser']);
			$nEMAIL = trim($_POST['email']);
			$nISADMIN = isset($_POST['isadmin']) && (int) $_POST['isadmin'] === 1 ? 1 : 0;
			$nPASSWORD = isset($_POST['newpass']) ? $_POST['newpass'] : '';
			$nPASSWORDCHK = isset($_POST['newpasschk']) ? $_POST['newpasschk'] : '';
			if($nUSER === ''){
				header('Refresh: 3;URL=./?admin');
				return '<b>Error</b>: No Username Set';
			}
			elseif($nEMAIL === ''){
				header('Refresh: 3;URL=./?admin');
				return '<b>Error</b>: No Email Set';
			}
			elseif($nPASSWORD === '' || $nPASSWORDCHK === ''){
				header('Refresh: 3;URL=./?admin');
				return '<b>Error</b>: Temporary password is required';
			}
			elseif($nPASSWORD !== $nPASSWORDCHK){
				header('Refresh: 3;URL=./?admin');
				return '<b>Error</b>: Temporary passwords entered do not match each-other';
			}
			elseif(strlen($nPASSWORD) < 8 || strlen($nPASSWORD) > 128){
				header('Refresh: 3;URL=./?admin');
				return '<b>Error</b>: Temporary password must be between 8 and 128 characters long';
			}
			elseif(Sentinel::$user->unameExists($nUSER)){
				header('Refresh: 3;URL=./?admin');
				return '<b>Error</b>: Username already exists';
			}
			else{
				Sentinel::$user->addUser($nUSER, $nPASSWORD, $nEMAIL, $nISADMIN);
				header('Refresh: 3;URL=./?admin');
				return 'Username: '.qdb_h($nUSER).' was added successfully';
			}
		}
	}
}

function admin_manageusers($message = '', $is_error = false) {
	if(!checklogin() || !issuperadmin()) {
		return '';
	}

	$users = Sentinel::$user->getAllUsers();
	$return_string = '<br /><fieldset id="manageusers"><legend>Manage Users <a href="#top" >[Top]</a></legend>';
	if($message !== '') {
		$return_string .= '<p>'.($is_error ? '<b>Error</b>: ' : '').qdb_h($message).'</p>';
	}
	$return_string .= '<p>No delete action is available. Disable an account to retire it.</p>';
	$return_string .= '<form action="./?manageusers" method="post">'.qdb_csrf_hidden_input();

	foreach($users as $userrow) {
		$userid = (int) $userrow['userid'];
		$isadmin = (int) $userrow['isadmin'];
		$enabled = (int) $userrow['enabled'];
		$return_string .= '<div class="user-admin-card">';
		$return_string .= '<b>User #'.$userid.'</b>';
		$return_string .= '<table cellpadding="2" cellspacing="0">';
		$return_string .= '<tr><td align="right">Username:</td><td><input type="text" name="username['.$userid.']" size="24" class="basicinput" value="'.qdb_h($userrow['username']).'"></td></tr>';
		$return_string .= '<tr><td align="right">Email:</td><td><input type="text" name="email['.$userid.']" size="32" class="basicinput" value="'.qdb_h($userrow['email']).'"></td></tr>';
		if($userid === 1) {
			$return_string .= '<tr><td align="right">Admin:</td><td>Yes<input type="hidden" name="isadmin['.$userid.']" value="1"></td></tr>';
			$return_string .= '<tr><td align="right">Enabled:</td><td>Yes<input type="hidden" name="enabled['.$userid.']" value="1"></td></tr>';
		}
		else {
			$return_string .= '<tr><td align="right">Admin:</td><td><select name="isadmin['.$userid.']"><option value="0"'.($isadmin === 0 ? ' selected' : '').'>No</option><option value="1"'.($isadmin === 1 ? ' selected' : '').'>Yes</option></select></td></tr>';
			$return_string .= '<tr><td align="right">Enabled:</td><td><select name="enabled['.$userid.']"><option value="0"'.($enabled === 0 ? ' selected' : '').'>No</option><option value="1"'.($enabled === 1 ? ' selected' : '').'>Yes</option></select></td></tr>';
		}
		$return_string .= '<tr><td align="right">Reset temporary password:</td><td><input type="password" name="resetpass['.$userid.']" size="24" class="basicinput"></td></tr>';
		$return_string .= '<tr><td align="right">Repeat temporary password:</td><td><input type="password" name="resetpasschk['.$userid.']" size="24" class="basicinput"></td></tr>';
		$return_string .= '<tr><td></td><td><button type="submit" name="save_user" class="basicsubmit" value="'.$userid.'">Save</button></td></tr>';
		$return_string .= '</table>';
		$return_string .= '</div>';
	}

	$return_string .= '</form></fieldset>';
	return $return_string;
}

function manageusers() {
	if(!checklogin() || !issuperadmin()) {
		header('Refresh: 0;URL=./');
		return 'Stop trying to hack, you suck.';
	}

	if(!isset($_POST['save_user'])) {
		return admin_manageusers();
	}

	if(!qdb_csrf_validate(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
		return admin_manageusers('Invalid security token, please try again.', true);
	}

	$userid = isset($_POST['save_user']) ? (int) $_POST['save_user'] : 0;
	$username = isset($_POST['username'][$userid]) ? trim($_POST['username'][$userid]) : '';
	$email = isset($_POST['email'][$userid]) ? trim($_POST['email'][$userid]) : '';
	$isadmin = isset($_POST['isadmin'][$userid]) && (int) $_POST['isadmin'][$userid] === 1 ? 1 : 0;
	$enabled = isset($_POST['enabled'][$userid]) && (int) $_POST['enabled'][$userid] === 1 ? 1 : 0;
	$resetpass = isset($_POST['resetpass'][$userid]) ? $_POST['resetpass'][$userid] : '';
	$resetpasschk = isset($_POST['resetpasschk'][$userid]) ? $_POST['resetpasschk'][$userid] : '';

	if($userid < 1 || Sentinel::$user->getUserById($userid) === false) {
		return admin_manageusers('User not found.', true);
	}
	if($userid === 1 && ($isadmin !== 1 || $enabled !== 1)) {
		return admin_manageusers('The bootstrap super-admin cannot be demoted or disabled.', true);
	}
	if($username === '') {
		return admin_manageusers('Username is required.', true);
	}
	if($email === '') {
		return admin_manageusers('Email is required.', true);
	}
	if(Sentinel::$user->unameExists($username, $userid)) {
		return admin_manageusers('Username already exists.', true);
	}
	if(($resetpass !== '' || $resetpasschk !== '') && ($resetpass === '' || $resetpasschk === '')) {
		return admin_manageusers('Both temporary password fields are required when resetting a password.', true);
	}
	if($resetpass !== '' && $resetpass !== $resetpasschk) {
		return admin_manageusers('Temporary passwords entered do not match each-other.', true);
	}
	if($resetpass !== '' && (strlen($resetpass) < 8 || strlen($resetpass) > 128)) {
		return admin_manageusers('Temporary password must be between 8 and 128 characters long.', true);
	}
	if($userid === 1 && (int) $_SESSION['userid'] !== 1) {
		return admin_manageusers('Only the bootstrap super-admin can reset the bootstrap super-admin password.', true);
	}

	Sentinel::$user->updateUser($username, '', $email, $isadmin, $userid, $enabled);
	if($resetpass !== '') {
		Sentinel::$user->resetPassword($userid, $resetpass);
	}

	return admin_manageusers('User updated successfully.');
}

//List the administrators and the moderators (the administrator has a userid of 1, same thing as the "super-admin")
//Moderators are users that do not have a userid of 1
//This function is used on the home page under the news column
function listadmins() {
	$return_string = "<table valign='bottom'><tr><td>";
	$admin = db_prepared_query("SELECT email, username FROM qdbusers WHERE enabled = 1 AND isadmin = 1 ORDER BY userid ASC");
	if(mysqli_num_rows($admin) > 0) {
		$return_string .= "Administrators: ";

		while ($row = mysqli_fetch_array($admin, MYSQLI_ASSOC)) {
			$return_string .= '<a href="'.qdb_h('mailto:'.$row['email']).'">'.qdb_h($row['username']).'</a> ';
		}
		$return_string .= '<br />';
	}
	mysqli_free_result($admin);

	$mods = db_prepared_query("SELECT email, username FROM qdbusers WHERE enabled = 1 AND isadmin != 1 ORDER BY userid ASC");
	if(mysqli_num_rows($mods) > 0) {
		$return_string .= "Moderators: ";

		while ($row = mysqli_fetch_array($mods, MYSQLI_ASSOC)) {
			$return_string .= '<a href="'.qdb_h('mailto:'.$row['email']).'">'.qdb_h($row['username']).'</a>';
		}
		$return_string .= '<br />';
	}
	mysqli_free_result($mods);

	$return_string .= '</td></tr></table>';
	
	return $return_string;
}

//Returns the latest 3 news posts as a html formatted string
function news() {
	$return_string = "";
	$sql = "SELECT qdbnews.postdate, qdbnews.post, qdbusers.username
		FROM qdbnews
		INNER JOIN qdbusers ON qdbnews.userid = qdbusers.userid
		ORDER BY qdbnews.postid DESC
		LIMIT 3";
	$result = db_prepared_query($sql);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$return_string .= '<div class="news">';
		$postdate = (string) $row['postdate'];
		if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $postdate)) {
			$postdate = explode('-', $postdate);
			$postdate = $postdate[2].'-'.$postdate[1].'-'.$postdate[0];
		}
		else {
			$postdate = qdb_h($postdate);
		}

		$return_string .= '<b>'.$postdate.'</b> By: '.qdb_h($row['username']).'<br /><p>'.qdb_render_legacy_html($row['post']).'</p></div>';
	}
	mysqli_free_result($result);

	return $return_string;
}

//Admin panel for news section, admins can submit and edit news posts
function admin_news() {
	$return_string = "";
	if (checklogin()) {
		if(isset($_POST['post_new'])) {
			if (!qdb_csrf_validate(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
				$return_string .= '<p><b>Error</b>: Invalid security token, please try again.</p>';
			}
			else if ($_POST['content'] != '') {
				$owner = (int) $_SESSION['userid'];
				$time = date("Y\-m\-d");
				$post = qdb_text_to_html(mquotes($_POST['content']));
				db_prepared_query("INSERT INTO qdbnews (postdate,post,userid) VALUES (?, ?, ?)", 'ssi', array($time, $post, $owner));
			}
		}
		else if(isset($_POST['post_edit'])) {
			if (!qdb_csrf_validate(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
				$return_string .= '<p><b>Error</b>: Invalid security token, please try again.</p>';
			}
			else {
				$postid = (int) @$_POST['selnews'];
				$post = qdb_text_to_html(mquotes($_POST['content']));
				db_prepared_query("UPDATE qdbnews SET post = ? WHERE postid = ?", 'si', array($post, $postid));
			}
		}
	}

	$return_string .= "<br /><fieldset id='news'><legend>News <a href='#top' >[Top]</a></legend><form name='news' action='./?admin' method='post'>".qdb_csrf_hidden_input()."<select name='selnews' onchange='javascript: document.news.submit()'>";

	$result = db_prepared_query("SELECT * FROM qdbnews ORDER BY postid DESC");

	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		if (isset($_POST['selnews'])) {
			if (@$_POST['selnews'] == $row['postid']) {
				$return_string .= '<option value="'.$row['postid'].'" selected>#'.$row['postid'].' '.$row['postdate'].'</option>';
				$newscontent = $row['post'];
			}
			else {
				$return_string .= '<option value="'.$row['postid'].'">#'.$row['postid'].' '.$row['postdate'].'</option>';
			}
		}
		else {
				$return_string .= '<option value="'.$row['postid'].'">#'.$row['postid'].' '.$row['postdate'].'</option>';
		}
	}

	$return_string .= "</select><br />";

	if (isset($newscontent)) {
		$return_string .= '<textarea wrap="virtual" name="content" cols="80" rows="10" class="basicinput">'.qdb_h(qdb_legacy_html_to_text($newscontent)).'</textarea><br /><br /><input type="submit" name="post_edit" value="Edit Post" class="basicsubmit" /> ';
	}
	else {
		$return_string .= '<textarea wrap="virtual" name="content" cols="80" rows="10" class="basicinput"></textarea><br /><br />';
	}

	$return_string .= '<input type="submit" name="update" value="Select" class="basicsubmit" /> <input type="submit" name="post_new" value="Post New" class="basicsubmit" />';

	$return_string .= "</form></fieldset>";
	return $return_string;
}

//match string to query string
function qs_match($string) {
	if(substr($_SERVER['QUERY_STRING'], 0, strlen($string)) == $string) {
		return true;
	}
	else {
		return false;
	}
}

function mquotes($tostrip) {
	if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
		return stripslashes($tostrip);
	}
	return $tostrip;
}
?>
