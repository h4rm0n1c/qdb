<?php

$user = "qdb_user";
$pass = "change_me";
$db = "qdb_userold";
$host = "localhost";

include '../includes/dbconnector.php';



function db_con_query($sql) {
	global $host, $user, $pass, $db;
	if(!isset($GLOBALS['dbcon'])) {
		$GLOBALS['dbcon'] = DbConnector::getInstance();
	}
	
	return $GLOBALS['dbcon']->query($sql);
}

/*$mysql_host = "localhost";
$mysql_user = "qdb_user";
$mysql_pass = "qdb_user";
$mysql_db = "qdb_database";

function db_con_query($sql) {
	global $mysql_host, $mysql_user, $mysql_pass, $mysql_db;

	$link = mysql_connect($mysql_host, $mysql_user, $mysql_pass) or die("MySQL Connection failed:".mysql_error());

	mysql_select_db($mysql_db);

	$ret_res = mysql_query($sql) or die("Error: MySQL Query could not be completed:".mysql_error());

	mysql_close($link);

	return $ret_res;
}*/

function addquote($id, $quote, $score) {
	//$mod_id = mysql_result(db_con_query("SELECT userid FROM qdbusers ORDER BY rand() LIMIT 1"),0);
	$mod_id = 1;
	DbConnector::getInstance();
	$escaped_quote = mysqli_real_escape_string(DbConnector::$link, $quote);
	$sql = "INSERT INTO qdb (id, quote, rating, approved, modid) VALUES ('$id','".$escaped_quote."','$score','1', '$mod_id')";
	db_con_query($sql);
}

function updateScore($id, $score) {
	$sql = "UPDATE qdb SET rating = ".$score." WHERE id =".$id;
	db_con_query($sql);
}

function getBaseUrl() {
	$host = $_SERVER['HTTP_HOST'];
	$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\')."/";
	return "http://".$host.$uri;
}

$page = (isset($_GET['p']))?$_GET['p']:(isset($_GET['browse'])?$_GET['browse']:01);
if($page == '') {
$page = 1;
}
/*else if($page > 412){
echo "finished bash.org database dump";
exit();
}*/

$url = "http://www.bash.org/?browse&p=".$page;

/*$url = "http://www.bash.org/?latest";
*/
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.9) Gecko/20061206 Firefox/1.5.0.9');
$html = curl_exec($ch);

curl_close($ch);

//echo $url2;
//http://php-html.sourceforge.net/html2text.php

$html = preg_replace("/[\n\r]/", '', $html);

$num_matches = preg_match_all('/<p class="quote">(.*)<\/p><p class="qt">(.*)<\/p>/siU', $html, $matches);

if($num_matches <= 0) {
	echo "Error: No quotes found in source HTML!, exiting";
	exit();
}

$nextpage = 1 + $page;
$url2 = getBaseUrl().'?p='.$nextpage;
header('refresh: 5; url='.$url2);

$res = db_con_query("SELECT id FROM qdb");

$id_array = array();

while ($exist_id = mysqli_fetch_array($res, MYSQLI_NUM)) {
	$id_array[] = $exist_id[0];
}

$added = 0;
$rejected = 0;

$qs = "";

foreach ($matches[2] as $key => $item) {
	preg_match('/<a href="\?([0-9]*)" title=".*?<a href="\.\/\?.*?".*?<\/a>\((-?[0-9]*)\)/s', $matches[1][$key], $temp_idscore);
	list(, $id, $score) = $temp_idscore;
	if (!in_array($id,$id_array, true)) {
		addquote($id,$item,$score);
		//print_r(array('id'=>$id,'score'=>$score, 'quote'=>$item));
		$qs .= "DUMPED QUOTE #".$id." TO DATABASE<br />";
		$added++;
	}
	else {
		$qs .= "QUOTE #".$id." ALREADY IN DATABASE, BUT SCORE WAS UPDATED<br />";
		updatescore($id, $score);
		$rejected++;
	}
}

echo "<p>QUOTES ADDED: <b>".$added."</b> OR <b>".(($added / 50) * 100)."%</b><br />";
echo "QUOTES REJECTED: <b>".$rejected."</b> OR <b>".(($rejected / 50) * 100)."%</b></p>";
echo $qs;
//echo $html;
?>
