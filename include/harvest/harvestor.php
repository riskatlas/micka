<?php
/*************************************************************************
 * Harvesting do Micky - verze 3.0
 * HSRS 2012-01-05
 * 
 * Parametry 
 * @outer   zda pouzit vnejsi zdroje (jakykoliv retezec) 
 * @begin   pocatectni datum (ISO format) implicitine posledni den
 * @end     konecne datum (ISO format) implicitne posledni den
 * @node    z jakeho serveru - impcitne vsechny     
 * @debug   zda debug mode 
 *
 ************************************************************************/    
date_default_timezone_set('Europe/Prague');
set_time_limit(3600);

define('HARVEST_EMAIL', 'kafka@email.cz');
define("HARVEST_EMAIL_SERVER", "mail@cenia.cz");
define('HARVEST_LOG', '/var/www/html/php/micka/include/logs/harvest.log');
define('HARVEST_TITLE', 'Národní geoportál - výsledky harvestování');
define('HARVEST_RSS_URL', 'http://geoportal.gov.cz/php/wmc/reports');
define('HARVEST_RSS_DIR', '/var/www/html/php/wmc/reports');

chdir(dirname(__FILE__));
include('../application/Csw.php');
require(PHPPRG_DIR . "/CswClient.php");
require(PHPPRG_DIR . "/Harvest.php");
require_once(PHPPRG_DIR . "/MdRecord.php");
//require(PHPPRG_DIR . "/micka_lib_auth.php");

for($i=0;$i<$argc;$i++){
	$pom = explode("=", $argv[$i]);
	$params[$pom[0]]=$pom[1];
}


// --- misto prihlaseni bude rovnou  struktura - dost derave
$_SESSION['u'] = "harvest";
$_SESSION['maplist']['micka']['users']['harvest'] = "rwp*";
$_SESSION['ms_groups'] = "harvest";

// ------------------------------------------------



$cswFrom = new CSWClient();
$cswTo = new Csw(CSW_LOG);
$harvestor = new Harvest($cswTo, $cswFrom, $params['to'], $params['log'], $params['delete']);
$results = $harvestor->runAll();
$harvestor->writeRSS($results);
$report = "";
foreach($results as $rec){
	$report .= $rec['uuid'].":".$rec['title']."\n";
	if($rec['report']){ 
		$report .= str_replace(
			array("['","']"), 
			array("/",""),
			$rec['report']
		);	
	}
}

echo $report;
//echo $cswTo->updateResponse($results,'update');


