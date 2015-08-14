<?php
include('include/application/Csw.php');
$csw = new Csw();

// description dokument
if(!$_GET['q'] && !$_GET['bbox'] && !$_GET['id']){ 
	$path = "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/";
	header("Content-type: application/xml");
	$lang = isset($_GET['language']) ? htmlspecialchars($_GET['language']) : "";
	if($lang!='cze') $lang = 'eng';
	$csw->xml->load(dirname(__FILE__)."/../cfg/cswConfig-$lang.xml");
	$csw->xsl->load(dirname(__FILE__)."/../include/xsl/openSearch.xsl");
	$csw->xp->importStyleSheet($csw->xsl);
	$csw->xp->setParameter('', 'path', $path);
	echo $csw->xp->transformToXML($csw->xml);
	exit;
}

$params['LANGUAGE'] = htmlspecialchars($_GET['language']); 
$params['q'] = htmlspecialchars($_GET['q']);
$params['DEBUG'] = htmlspecialchars($_GET['debug']);
$params['STARTPOSITION'] = htmlspecialchars($_GET['start']);
$params['FORMAT'] = trim(htmlspecialchars($_GET['format']));

if($params['q']){
	$params['q'] = preg_replace('/\s+/', ' ',trim($params['q']));
	//$tokens = explode(" ", $params['q']);
	if(DB_DRIVER=='oracle'){ //TODO doladit
		//$tokens = implode("* & *", $tokens);
		$params['CONSTRAINT'] .= "anytext like '".$params['q']."*'";
	}
	else{
		$tokens = explode(" ", $params['q']);
		foreach($tokens as $token){
			if($params['CONSTRAINT']!='') $params['CONSTRAINT'] .= " AND "; 
			$params['CONSTRAINT'] .= "anytext like '*".$token."*'";
		}
	}
}
if($_GET['id']) $params['CONSTRAINT'] = "identifier = '".htmlspecialchars($_GET['id'])."'";
if($_GET['bbox']){
	if($params['CONSTRAINT']) $params['CONSTRAINT'] .= " AND ";
	$box = str_replace(","," ",htmlspecialchars($_GET['bbox']));
	$params['CONSTRAINT'] .= "_BBOX_='".$box."'"; 
}
/*if($params['LANG']){
	if($params['CONSTRAINT']) $params['CONSTRAINT'] .= " AND ";
  $params['CONSTRAINT'] .= "_LANGUAGE_='".$params['LANG']."'";  
}*/
$params['STARTPOSITION'] = htmlspecialchars($_GET['start']);
if(!$params['STARTPOSITION'])$params['STARTPOSITION']=1;
$csw->headers = array();
switch ($params['FORMAT']){
	case 'rss': 
		$csw->headers[] = "Content-type: application/rss+xml";
		$params['OUTPUTSCHEMA'] = $csw->schemas['rss'];
		break; 
	case 'atom': 
		$csw->headers[] = "Content-type: application/xml";
		$params['OUTPUTSCHEMA'] = $csw->schemas['atom'];
		break; 
	case 'kml': 
		$csw->headers[] = "Content-Type: application/vnd.google-earth.kml+xml\n";
		$csw->headers[] = "Content-Disposition: filename=micka-open.kml";
		$params['OUTPUTSCHEMA'] = $csw->schemas['kml'];
		break; 
	case 'rdf': 
		$csw->headers[] = "Content-type: application/rdf+xml";
		$params['OUTPUTSCHEMA'] = $csw->schemas['rdf'];
		break;
	default: 
    	$csw->headers[] = "Content-type: text/html";
		$params['OUTPUTSCHEMA'] = $csw->schemas['os'];
		$params['FORMAT'] = 'html';
		break;
}

// constants
$params['SERVICE'] = 'CSW'; 
$params['VERSION'] = '2.0.2'; 
$params['REQUEST'] = 'GetRecords'; 
$params['CONSTRAINT_LANGUAGE'] = 'CQL_TEXT'; 
$params['ELEMENTSETNAME'] = 'summary'; 
$params['MAXRECORDS'] = 25;
$params['TYPENAMES'] = 'dummy';

$result = $csw->run($params);
$csw->setHeader();
echo $result;
