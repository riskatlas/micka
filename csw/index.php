<?php
session_start();
include('../include/application/Csw.php');
$csw = new Csw();

// pokud jsou entity
$params=array();
if($_SERVER['QUERY_STRING']){
	$input = explode("&", html_entity_decode($_SERVER['QUERY_STRING']));
	foreach($input as $pair){
		$kw = explode("=",$pair);
		$params[strtoupper($kw[0])] = htmlspecialchars($kw[1]);
	}
}

if (isset($_SERVER['PHP_AUTH_USER'])) {
  	$params['user'] = $_SERVER['PHP_AUTH_USER'];
  	$params['pwd']  = $_SERVER['PHP_AUTH_PW']; 
}

if($_REQUEST['url']){
    echo $csw->getDataFromURL($_REQUEST['url']);
    exit; 
}
else{
	if(!$params['OUTPUTSCHEMA']) $params['OUTPUTSCHEMA']="http://www.isotc211.org/2005/gmd"; //TODO docasne, pak nezavisle
	$params = $csw->dirtyParams($params);
}
	

// FIXME docany kvuli zpetne kompatibilite
//$params['LANGUAGE'] = $params['LANG'];

$result = $csw->run($params);
$csw->setHeader();
echo $result;
