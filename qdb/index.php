<?php
//mail("h4rm0n1c@gmail.com", "testing", "Testing php's mail capabilities");
/*
File: index.php

Description: This file is used to handle page requests and display the appropriate content based on the query string.

Includes: common.php, global.php

Version: 1.0

Author: Harrison Mclean

License: Creative Commons
*/

$gen_start = microtime(true);

include 'common.php';
include 'global.php';

admin_session();

$navigation = navigation();

$content = 'Content Is Missing';

$banner_l_sn = $banner_l;

$banner_l .= ' <font size="-1"><a href="./?admin">Admin</a></font>';

$banner_r = 'Query Was Sucessful';

$approved = count_approved();

$pending = count_pending();

$footer =  $approved.' quotes approved; '.$pending.' quotes pending; '.($approved+$pending).' total quotes; karma: '.count_karma();

switch ($_SERVER['QUERY_STRING']) {

case 'home':
	$content = index();
	$banner_r = "Quote Database Home";
break;

case 'latest':
	//Latest 50 quotes here
	$content = latest();
	$banner_r = "Latest 50 Quotes";
break;

case 'random':
	//Random 50 quotes here
	$content = random();
	$banner_r = "Random Quote";
break;

case 'random1':
	//Random >0 Quotes
	$content = random1($approved);
	$banner_r = "Random >0 Quote";
break;

case 'top2':
	//Top 100 quotes here
	$content = top100();
	$banner_r = "Top 100 Quotes";
break;

case 'top':
	//Top 50 quotes here
	$content = top50();
	$banner_r = "Top 50 Quotes";
break;

case 'bottom':
	//Bottom 50 quotes here
	$content = bottom();
	$banner_r = "Bottom 50 Quotes";
break;

case 'queue':
	//Submission queue here
	$content = queue($pending);
	$banner_r = "Submission Queue";
break;

case 'add':
	//Submit quotes here
	$content = add();
	$banner_r = "Add a Quote";
break;

case 'added':
	$content = added();
	$banner_r = "Quote Submitted";
break;

case 'admin':
	//Admin Section
	$content = admin($navigation);
	$banner_r = "Administration";
break;

case 'logout':
	$content = "Logged Out";
	$GLOBALS['sentinel']->logout();
	header("Location: ./");
break;

case 'globals':
	$content = "<pre>". print_r($GLOBALS,TRUE)."</pre>";
	$banner_r = "Global Variables";
break;

default:
	if($_SERVER['QUERY_STRING'] == ''){
		$banner_r = "Quote Database Home";
		$content = index();
		break;
	}
	
	if(qs_match('search')) {
		$content = search();
		$banner_r = "Search Results";
		break;
	}

	if(qs_match('browse')) {
		$content = browse($approved);
		$banner_r = "Browse Quotes";
		break;
	}
	
	if (qs_match('changepass')) {
		$content = changepass();
		$banner_r = "Change Password";
		break;
	}

	if(qs_match('adduser')) {
		$content = adduser();
		break;
	}

	if(qs_match('manageusers')) {
		$content = manageusers();
		$banner_r = "Manage Users";
		break;
	}

	else {
		$banner_r = 'Individual Quote';
		$content = single_quote();
		break;
	}
}

//Load Template
$fp = fopen('template.html', 'r');
$template = fread($fp, filesize('template.html'));
fclose($fp);

//Global content tags are defined here
$search = array('<content>','<banner_l>','<footer>','<navlink>','<robots>','<banner_r>','<sitename>','<pagename>','<license>');

$time = number_format((microtime(true) - $gen_start) * 1000, 0);

//The replacements for the global content tags are defined here
$replace = array($content, $banner_l, $footer, $navigation, '', $banner_r, $banner_l_sn, $banner_r, "Render time: ".$time." ms<br />".$license);

$template = str_replace($search, $replace, $template);

echo $template;
?>
