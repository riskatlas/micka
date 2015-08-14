<?php
include('../include/application/Csw.php');
$csw = new Csw("", true);

// pokud jsou entity
$params=array();
if($_SERVER['QUERY_STRING']){
	$input = explode("&", html_entity_decode($_SERVER['QUERY_STRING']));
	foreach($input as $pair){
		$kw = explode("=",$pair);
		$params[$kw[0]] = htmlspecialchars($kw[1]);
	}
}

if (isset($_SERVER['PHP_AUTH_USER'])) {
  $_REQUEST['user'] = $_SERVER['PHP_AUTH_USER'];
  $_REQUEST['pwd']  = $_SERVER['PHP_AUTH_PW']; 
}

// hack kvuli primemu pristupu pro CENIA
if($_POST['query']){ 
	$params['query'] = $_POST['query'];
	$params = $_POST;
	$params['start']++; 
	if(!$_REQUEST['user']) $_REQUEST['user'] = 'guest'; 
}

echo $csw->run($params);

