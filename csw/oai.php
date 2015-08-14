<?php
include('../include/application/Csw.php');

function oaiHeader($verb){
  $datestamp = date("Y-m-d\TH:i:s"); 
  return '<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
  <responseDate>'.$datestamp.'</responseDate>
  <request verb="'.$verb.'">http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'].'</request>';	
}

function error($code, $message){
	$datestamp = date("Y-m-d\TH:i:s"); 
	header("Content-type: application/xml");
	echo oaiHeader($_GET['verb']).'<error code="'.$code.'">'.$message.'</error></OAI-PMH>';
}

function identify(){
	header("Content-type: application/xml");
	echo oaiHeader($_GET['verb']);
	echo '<Identify>
<repositoryName>MICKA</repositoryName>
<baseURL>http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'].'</baseURL>
<protocolVersion>2.0</protocolVersion>
<adminEmail>kafka@email.cz</adminEmail>
<earliestDatestamp>2004-10-30</earliestDatestamp>
<deletedRecord>no</deletedRecord>
<granularity>YYYY-MM-DD</granularity>
</Identify></OAI-PMH>';
}

function listSets(){
    $sets = getSets(); 
	header("Content-type: application/xml");
	echo oaiHeader($_GET['verb']);
	echo "<ListSets>";
	foreach($sets as $set){
		echo "<set><setSpec>$set[id]</setSpec><setName>$set[source]</setName></set>";
	}
	echo "</ListSets></OAI-PMH>";
}

  function getSets(){
	$sql = array();
	$sql[] = 'SELECT * FROM harvest';
    array_push($sql, 'ORDER BY name');
    try {
        $rs = dibi::query($sql);
        $rs = _executeSql('select', $sql, array('all'));     
        foreach($rs as $row){
            $result[] =  Array(
              "id" => $row['NAME'],
              "source" => $row['SOURCE'],
              "type" => $row['TYPE'],
              "h_interval" => $row['H_INTERVAL'],
              "HarvestInterval" => $row['PERIOD'],
              "handlers" => $row['HANDLERS']
          ); 
        }

    }
	catch (DibiException $e) {
	    var_dump($e);
		$result = false;
	}
    return $result;   
  }

// --- main ---
$csw = new Csw();
$csw->headers[] = "Content-type: application/xml";
// --- zpracovani parametru ---
$input = array();
while(list($key,$val)=each($_GET)){
  $input[strtolower($key)] = htmlspecialchars(strtolower($val));
}

if($input['resumptiontoken']){ 
	$rt = explode("|", $input['resumptiontoken']);
    $input['verb'] = $rt[0];
    $input['set'] = $rt[1];
    $input['from'] = $rt[2];
    $input['until'] = $rt[3];
    $input['metadataprefix'] = $rt[4];
    $input['start'] = $rt[5];
}

if(!$input['from']) $input['from'] = "1900-01-01";

// --- zpracovani metadataPrefix
$csw->params['OUTPUTSCHEMA'] = $csw->schemas[$input['metadataprefix']];

if(!$csw->params['OUTPUTSCHEMA']){
	error('cannotDisseminateFormat','he metadata format identified by the value given for the "metadataPrefix" argument is not supported by the item or by the repository');
	exit;
}

$csw->params['QSTR'] = array();
$csw->params['REQUEST'] = 'GetRecords'; 

switch ($input['verb']){
    case 'listidentifiers':
      	$csw->params["VERB"] = "ListIdentifiers";
        $csw->params['QSTR'][] = "_DATESTAMP_ >= '".$input['from']."'";
        if($input['until']){ 
        	if($csw->params['QSTR']) $csw->params['QSTR'][] = "AND";
        	$csw->params['QSTR'][] = "_DATESTAMP_ <= '".$input['until']."'";
        }
        if($input['set']){
        	$csw->params['QSTR'][] = "AND";
        	$csw->params['QSTR'][] = "_SERVER_ = '".$input['set']."'";
        }
        $csw->params['ELEMENTSETNAME'] = 'brief';
        break;
    
    case 'listrecords':
        $csw->params["VERB"] = "ListRecords";
        $csw->params['QSTR'][] = "_DATESTAMP_ >= '".$input['from']."'";
        if($input['until']){ 
        	if($csw->params['QSTR']) $csw->params['QSTR'][] = "AND";
        	$csw->params['QSTR'][] = "_DATESTAMP_ <= '".$input['until']."'"; 
        }
        if($input['set']){
        	$csw->params['QSTR'][] = "AND";
        	$csw->params['QSTR'][] = "_SERVER_ = '".$input['set']."'";
        }
        $csw->params['ELEMENTSETNAME'] = 'summary';
        break;
    
    case 'getrecord':
      	$csw->params["VERB"] = "GetRecord";
      	$csw->params['REQUEST'] = 'GetRecords'; 
      	$csw->params['ELEMENTSETNAME'] = 'summary';
        $id = explode(":", $input['identifier']);
        $id = $id[count($id)-1];
        //$csw->params['ID'] = $id;
        $csw->params['QSTR'][] = "@identifier = '".$id."'";
        break;
    
    case 'identify':
        identify();
        exit;
        break;
  
    case 'listsets':
        listSets();
        exit;
        break;
    
    
    default:
        error("badVerb", 'Value of the "verb" argument is not a legal OAI-PMH verb, the "verb" argument is missing, or the "verb" argument is repeating');
        exit;
        break;
}

$csw->requestType=1;
$csw->params['SERVICE'] = 'CSW'; 
$csw->params['TYPENAMES'] = htmlspecialchars($input['metadataprefix']); 
$csw->params['VERSION'] = '2.0.2'; 
//$csw->params['CONSTRAINT_LANGUAGE'] = 'Filter'; 
$csw->params['MAXRECORDS'] = 50; 
$csw->params['SET'] = $input['set']; 
$csw->params['FROM'] = $input['from']; 
$csw->params['UNTIL'] = $input['until']; 
$csw->params['ID'] = $input['identifier']; 
$csw->params['DEBUG'] = $_GET['debuk'];
$csw->params['STARTPOSITION'] = $input['start'] ? $input['start'] : 1;
$csw->from = $input['from'];

$result = $csw->run($params, false);
$csw->setHeader();
echo $result;


