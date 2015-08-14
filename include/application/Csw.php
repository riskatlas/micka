<?php
/*******************************************************
 * OGC Catalog service server - CS-W 2.0.2
 * Help Service Remote Sensing 
 * verze 4.5.0  
 * 2012-02-28 
 *******************************************************/   
session_start();

define("CSW_TIMEOUT", 30);
define("HTTP_XML", "Content-type: application/xml; charset=utf-8");
define("HTTP_SOAP", "Content-type: application/xml+soap; charset=utf-8"); //TODO ověřit
define("HTTP_JSON", "Content-type: application/json; charset=utf-8");
define("HTTP_HTML", "Content-type: text/html; charset=utf-8");
define("HTTP_CSV", "Content-type: text/csv; charset=utf-8");
define("HTTP_KML", "Content-type: application/vnd.google-earth.kml+xml");

define("XML_HEADER", '<?xml version="1.0" encoding="UTF-8"?'.'>');
define("SOAP_HEADER", '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"><soap:Body>');
define("SOAP_FOOTER", '</soap:Body></soap:Envelope>');
define("CSW_MAXFILESIZE", 5000000);

if(!extension_loaded("xsl")){
  die("Configuration ERROR: XSL extension is not supported.");
}

include_once(dirname(__FILE__).'/micka_config.php');
include_once(PHPPRG_DIR.'/micka_main_lib.php');
include_once(PHPPRG_DIR.'/micka_lib_auth.php');
include_once(PHPPRG_DIR.'/micka_lib.php');
include_once(PHPPRG_DIR.'/MdExport.php');
include_once(PHPPRG_DIR.'/MdImport.php');
include_once(PHPPRG_DIR.'/MdRecord.php');
include_once(PHPPRG_DIR.'/micka_lib_insert.php');  
include_once(PHPPRG_DIR.'/micka_lib_php5.php');
include_once(AUTHLIB_FILE);

// pro XSLT - dotaz na metadata
function getMetadata($s){
	$csw = new Csw();
	$params["CONSTRAINT"] = $s;
	$params['CONSTRAINT_LANGUAGE'] = 'CQL';
	$params['TYPENAMES'] = 'gmd:MD_Metadata';
	$params['OUTPUTSCHEMA'] = $csw->schemas['gmd'];
	$params['SERVICE'] = 'CSW';
	$params['REQUEST'] = 'GetRecords';
	$params['VERSION'] = '2.0.2';
	$params['ISGET'] = true;
	$result = $csw->run($params);
	$dom = new DOMDocument();
	$dom->loadXML($result);
	return $dom;
}

// pro XSLT - dotaz na metadata
function getData($s){
    $dom = new DOMDocument();
    $result = file_get_contents($s);
    //file_put_contents("/temp/a.xml", $result);
    if($result) $dom->loadXML($result);
    return $dom;
}

// pro XSLT - extent mapy
function drawMapExtent($size, $x1, $y1, $x2, $y2){
    if(($x1 < $x2) && ($y1 < $y2)){
        $xyRatio = cos(($y1 + $y2)/2/180*pi());  
        $width = round($size * $xyRatio * ($x2 - $x1) / ($y2 - $y1));
        $wms = $GLOBALS['hs_wms']['eng'];
        $wms .= "&BBOX=$x1,$y1,$x2,$y2&WIDTH=$width&HEIGHT=$size&REQUEST=GetMap&SRS=EPSG:4326";       
        $bboxImg = $wms;
    }  
    return $bboxImg;
}

// pro XSLT - ceske datum
function drawDate($date, $lang){
	if($lang=='cze' && strpos($date,"-")>0){
		$pom = explode("-",$date);
		$s = "";
		foreach($pom as $token){
			$s = $token.".".$s;
		}
		$date = substr($s,0,-1);
	}
	return $date;
}

/**
 * OGC Catalogue service implementation 
 * 
 */
class Csw{

    var $xp  = null;
    var $xml = null;
    var $xsl = null;
    var $logText = "";
    var $logFile = "";
    var $params = null;
    var $requestType = null;
    var $input = "";
    var $inspire = null;
    var $headers = array(HTTP_XML);
    var $isXML = true;
    var $schemas = array(
        "csw"	=> "http://www.opengis.net/cat/csw/2.0.2",
        "gmd"	=> "http://www.isotc211.org/2005/gmd",
        "native"=> "native",
        "rss"	=> "http://www.georss.org/georss",
        "atom"	=> "http://www.w3.org/2005/Atom",
      	"kml"	=> "http://earth.google.com/kml/2.2",
      	"os"	=> "http://a9.com/-/spec/opensearch/1.1/",
      	"rdf"	=> "http://www.w3.org/1999/02/22-rdf-syntax-ns",
      	"oai_dc" => "http://www.openarchives.org/OAI/2.0/oai_dc/",
      	"oai_marc" => "http://www.openarchives.org/OAI/1.1/oai_marc",
      	"marc21" => "http://www.openarchives.org/OAI/2.0/",
        "dcat" => "http://www.w3.org/ns/dcat"
    );

  /**
   * CSW constructor
   *
   * @param string $logpath log file name with path (optional)
   */
    function __construct($logpath="", $inspire=false){
        $this->xml = new DomDocument;
        $this->xsl = new DomDocument;
        $this->xp = new XsltProcessor();
        $this->xp->registerPhpFunctions();
        $this->inspire = $inspire;
    	$logpath = CSW_LOG;
        if($logpath) $this->logFile = $logpath."/cswlog";
    }  

    function __destruct(){
        unset($this->xml); $this->xml=null; 
        unset($this->xsl); $this->xsl=null; 
        unset($this->xp);  $this->xp=null; 
    }
  
  	private function validip($ip) {
		if (!empty($ip) && ip2long($ip)!=-1) {
			$reserved_ips = array (
	 			array('0.0.0.0','2.255.255.255'),
	 			array('10.0.0.0','10.255.255.255'),
	 			array('127.0.0.0','127.255.255.255'),
	 			array('169.254.0.0','169.254.255.255'),
	 			array('172.16.0.0','172.31.255.255'),
	 			array('192.0.2.0','192.0.2.255'),
	 			array('192.168.0.0','192.168.255.255'),
	 			array('255.255.255.0','255.255.255.255')
	 		);
			foreach ($reserved_ips as $r) {
				$min = ip2long($r[0]);
				$max = ip2long($r[1]);
				if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;		
			}
			return true;
			} 
		else {
			return false;
		}
	}

	private function getIP() {
		if ($this->validip($_SERVER["HTTP_CLIENT_IP"])) {
 			return $_SERVER["HTTP_CLIENT_IP"];
 		}
		foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
			if ($this->validip(trim($ip))) {
				return $ip;
			}
		}
		if ($this->validip($_SERVER["HTTP_X_FORWARDED"])) { 
			return $_SERVER["HTTP_X_FORWARDED"];
		}
		elseif ($this->validip($_SERVER["HTTP_FORWARDED_FOR"])) {
			return $_SERVER["HTTP_FORWARDED_FOR"];
		} 
		elseif ($this->validip($_SERVER["HTTP_FORWARDED"])) {
			return $_SERVER["HTTP_FORWARDED"];
		} 
		elseif ($this->validip($_SERVER["HTTP_X_FORWARDED"])) {
			return $_SERVER["HTTP_X_FORWARDED"];
		} 
		else {
			return $_SERVER["REMOTE_ADDR"];
		}
	}
	
	
    function exception($code, $locator, $text){
        $errCode[0] = "NoApplicableCode";
        $errCode[1] = "OperationNotSupported";
        $errCode[2] = "MissingParameterValue";
        $errCode[3] = "InvalidParameterValue";
        $errCode[4] = "InvalidParameterName";
        $errCode[5] = "NonexistentType";
        $errCode[6] = "TransactionFailed";
      
        header(HTTP_XML);  
        $h = '<ows:ExceptionReport 
        xmlns:ows="http://www.opengis.net/ows/1.1" version="1.1.0"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
        xsi:schemaLocation="http://www.opengis.net/ows/1.1 http://schemas.opengis.net/ows/1.1.0/owsExceptionReport.xsd">'; 
        if($locator) $locator = ' locator="'.$locator.'"';
        $s = '<ows:Exception exceptionCode="'.$errCode[$code].'"'.$locator.'>';
        if($text) $s .= "<ows:ExceptionText>$text</ows:ExceptionText>";
        if(isset($this->params['SOAP'])){
        	echo XML_HEADER.SOAP_HEADER."
        	<soap:Fault>
             <soap:Code>
                <soap:Value>soapenv:Server</soap:Value>
             </soap:Code>
             <soap:Reason>
                <soap:Text xml:lang=\"en\">A server exception was encountered.</soap:Text>
             </soap:Reason>
             <soap:Detail>
        	".$h.$s."</ows:Exception></ows:ExceptionReport></soap:Detail></soap:Fault>".SOAP_FOOTER;
        }
        else echo XML_HEADER.$h.$s."</ows:Exception></ows:ExceptionReport>";
        $this->logText .= $s."\n";
        $this->saveLog();
        exit;
    }


  	// hack kvuli primemu pristupu pro CENIA pres POST
  	function dirtyParams($params){
  		while(list($key,$val) = each($params)){
  			$params[strtoupper($key)]=html_entity_decode($val);
  		}
	  	$params['SERVICE'] = 'CSW';
	  	$params['VERSION'] = '2.0.2';
	  	$params['CONSTRAINT_LANGUAGE'] = 'CQL';
	  	
	  	if(isset($params['QUERY'])){
	  		$params['CONSTRAINT'] = $params['QUERY'];
	  		unset($params['QUERY']);
	  		$params['REQUEST'] = 'GetRecords';
	  		$params['TYPENAMES'] = 'gmd:MD_Metadata';
	  		if(!$params['OUTPUTSCHEMA']) $params['OUTPUTSCHEMA'] = $this->schemas['gmd'];
	  		$params['ISGET'] = true;
	  		if(!isset($params['FORMAT']) || strpos($params['FORMAT'],'json')!==false){
	  			$params['FORMAT'] = "application/json";
	  			$params['ELEMENTSETNAME'] = 'full';
	  		}
	  		if(!$params['USER']) $params['USER'] = 'dummy';
	  		if(isset($params['START'])){
	  			$params['STARTPOSITION'] = intval($params['START']);
	  			if($params['FORMAT']=='application/json'){
	  				$params['STARTPOSITION']++;
	  			}
	  		}
	  		if(isset($params['LIMIT'])){
	  			$params['MAXRECORDS'] = intval($params['LIMIT']);
	  		}
	  	}
	  	// rss kanál
	  	else if($params['REQUEST']=='rss'){
	  		$dni = intval($params['DAYS']);
	  		unset($params['DAYS']);
	  		$params['REQUEST'] = 'GetRecords';
	  		$params['CONSTRAINT'] = "modified >= '".date("Y-m-d", time()-($dni*3600*24))."'";
	  		$params['TYPENAMES'] = 'gmd:MD_Metadata';
	  		$params['OUTPUTSCHEMA'] = 'http://www.georss.org/georss';
	  		if(isset($params['START'])){
	  			$params['STARTPOSITION'] = intval($params['START']);
	  		}
	  		if(isset($params['LIMIT'])){
	  			$params['MAXRECORDS'] = intval($params['LIMIT']);
	  		}
	  		if(!$params['USER']) $params['USER'] = 'dummy';
	  	}
	  	else if($params['ID'] && $params['FORMAT']){
	  		$params['TYPENAMES'] = 'gmd:MD_Metadata';
	  		$params['REQUEST'] = 'GetRecordById';
	  	}
	  	//var_dump($params);
  		return $params;
  	}
  	
  	function getDataFromURL($url, $language='eng'){
  		$s = file_get_contents($url); //TODO kontrola url
  		$this->params['LANGUAGE'] = $language;
  		if($s){
  			$this->xml->loadXML($s);
  			//echo $s;
  			//$s = $this->asHTML($this->xml, CATCLIENT_PATH."/xsl/iso2htmlFull.xsl");  // TODO - podle konfigurace
  			$s = $this->asHTML($this->xml, PHPPRG_DIR."/../xsl/iso2htmlFull_.xsl");  // TODO - podle konfigurace
  		}
  		if($s) return $s;
  		return "Metadata document not found!";  		
  	}
  
  function processParams($params){
  	$this->input = "";
  	if(!$params["ISGET"]){
   		$this->input = $GLOBALS["HTTP_RAW_POST_DATA"];
   		if (!$this->input) $this->input = file_get_contents('php://input', false, null, null, CSW_MAXFILESIZE); //TODO obslouzit chybu
  	}
  	
    // POST 
    if($this->input){
		$this->input = stripslashes($this->input);
		$this->xml->loadXML($this->input);
		$this->xsl->load(PHPPRG_DIR."/../xsl/filter2micka.xsl");
		$this->xp->importStyleSheet($this->xsl);
		$this->xp->setParameter("", "fulltext", DB_FULLTEXT);
		$processed = $this->xp->transformToXML($this->xml);
		$IDs = Array();
		$processed = html_entity_decode($processed);
		//$processed = str_replace("&amp;", "&", $processed);
		//echo $processed;
		eval($processed);
		$this->params = $params;
		$this->requestType=1;
    } 
    	
    // GET
	else if(count($params) > 0){
        // odstranění ošetření dat způsobeného direktivou magic_quotes_gpc
        if (get_magic_quotes_gpc()) {
    		$process = array(&$params);
    		while (list($key, $val) = each($process)) {
    			foreach ($val as $k => $v) {
    				unset($process[$key][$k]);
    				if (is_array($v)) {
    					$process[$key][($key < 5 ? $k : stripslashes($k))] = $v;
    					$process[] =& $process[$key][($key < 5 ? $k : stripslashes($k))];
    				}
    				else {
    					$process[$key][stripslashes($k)] = stripslashes($v);
    				}
    			}
    		}
    	}
    	foreach($params as $k => $v){
    		$params[$k] = urldecode($v);
    	}
      	$this->params = array_change_key_case($params, CASE_UPPER);
      	$this->params['CONSTRAINT']= html_entity_decode($this->params['CONSTRAINT']);
      	$this->requestType=0;
    }
    // prazdny dotaz 
    else{
       	$this->exception(0, "", "Missing request");
    }
    if(!$this->params['Q']) $this->params['Q'] = "";
  }

  function getParamL($name){
	   return str_replace("csw:", "", strtolower($this->params[$name]));
  }

  /**
   * Main Run method - runs the CSW server
   * 
   */
  function run($params, $processParams = true){
    $this->startTime = microtime(true);
    if($processParams) $this->processParams($params); 
      if($params['user'] || $this->params['TOKEN']){
    	prihlaseni(htmlspecialchars($params['user']), htmlspecialchars($params['pwd']), $this->params['TOKEN']);
    	getProj();
    	define("MICKA_USER", $params['user']);
    }
    $this->params['timestamp'] = gmdate("Y-m-d\TH:i:s");
    if(MICKA_URL){ 
    	$this->params['thisURL'] = MICKA_URL . "/csw/index.php";
    }
    else {
    	$this->params['thisURL'] = "http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'].$_SERVER['SCRIPT_NAME'];
    }
    $this->params['thisPath'] = dirname($this->params['thisURL']);
    if(!$this->params['CB']) $this->params['CB'] = "". $_SESSION["micka"]["cb"]; //
    if(!$this->params['LANGUAGE']) $this->params['LANGUAGE'] = MICKA_LANG;
    if($this->params['DEBUG']){
	    if($this->params['DEBUG']==1){
	      	var_dump($this->params);
	      	echo "<hr>";
	    }
    }
    else{
        if($this->params['SOAP']) $this->header = HTTP_SOAP;
    }
    $remoteIP = $this->getIP();
    $this->logText = date("Y-m-d\TH:i:s")."|".$_SESSION['u']."|".$_SERVER['REQUEST_METHOD']."|".$remoteIP."| |";
    //reset($this->params);
    //if($this->params['ID']) $this->logText .= "[ID=".$this->params['ID']."]";
    //else if($this->params['QSTR']) $this->logText .= @json_encode($this->params['QSTR']); //TODO zmenit
    $request = $this->getParamL('REQUEST');
    /*if($request=="rss") { 
    	return $this->rss(); 
    }*/
    if(!$this->params['SERVICE']) $this->exception(2, "SERVICE", "Missing 'SERVICE' parameter");
    if($this->getParamL('SERVICE')!='csw') $this->exception(3, "service", "Service MUST be CSW");
    if(!$request) $this->exception(2, "REQUEST", "Missing 'REQUEST' parameter");
    if($request!='getcapabilities'){
      if(!$this->params['VERSION']) $this->exception(2, "VERSION", "Missing 'VERSION' parameter");
      if($this->params['VERSION']!="2.0.2") $this->exception(3, "VERSION", "Only 2.0.2 version currently supported");
    }
    
    // trideni podle request
    switch ($request) {
      case 'getcapabilities': $result = $this->getCapabilities(); break;
      case 'describerecord': $result = $this->describeRecord(); break;
      case 'getrecords': $result = $this->getRecords(); break;
      case 'getrecordbyid': $result = $this->getRecordById(); break;
      case 'transaction': $result = $this->transaction(); break;
      case 'harvest': 
        prihlaseni(null, null);
        getProj();
        if(canAction('w')) $result = $this->harvest(true); break;
      case 'getharvest': $result = $this->harvest(false); break;
      default: $this->exception(3, "request", $this->params['REQUEST']." is not supported request value.");  	
    	break;
    }
    if($this->params['SOAP']) $result = SOAP_HEADER.$result.SOAP_FOOTER;
    if($this->isXML) $result = XML_HEADER.$result;
    $this->logText .= "|500|".(microtime(true)-$this->startTime);
    $this->saveLog();
    return $result;
  }


    function asJSON($xml, $head, $ext=false){
    	//echo $xml->saveXML();
        $this->xsl->load(PHPPRG_DIR."/../xsl/iso2json.xsl");
        $this->xp->importStyleSheet($this->xsl);
        $this->xp->setParameter('', 'lang', $this->params['LANGUAGE']);
        $output = $this->xp->transformtoXML($xml);
        //echo $output;
        eval($output);
        for($i=0; $i<count($json['records']); $i++){
        	$json['records'][$i]['abstract'] = html_entity_decode($json['records'][$i]['abstract']);
            if($ext){        	
            	$json['records'][$i]['public'] = intval($head[$i]['DATA_TYPE']);
            	$json['records'][$i]['creator'] = $head[$i]['CREATE_USER'];
            	$json['records'][$i]['updator'] = $head[$i]['LAST_UPDATE_USER'];
            	$json['records'][$i]['updated'] = $head[$i]['LAST_UPDATE_DATE'];
            	$json['records'][$i]['edit_group'] = $head[$i]['EDIT_GROUP'];
            	$json['records'][$i]['view_group'] = $head[$i]['VIEW_GROUP'];
            	$json['records'][$i]['valid'] = intval($head[$i]['VALID']);
            	$json['records'][$i]['mayedit'] = $head[$i]['edit'];
            	$json['records'][$i]['harvest_source']  = $head[$i]['harvest_source'];
            	$json['records'][$i]['harvest_title']  = $head[$i]['harvest_title'];
            }
        }
    	$this->headers[0] = HTTP_JSON;
    	if($json['next']>0) $json['next']--; // v json je index od 0
    	return json_encode($json);  	        
    }
    
    function asHTML($xml, $template){
    	//die($xml->saveXML());
        if(!$this->xsl->load($template)) die("html template $template not loaded.");
        $this->xp->importStyleSheet($this->xsl);
        if(!$this->params['LANGUAGE']) $this->params['LANGUAGE'] = MICKA_LANG;
        $this->xp->setParameter('', 'LANGUAGE', $this->params['LANGUAGE']);
        $this->xp->setParameter('', 'lang', $this->params['LANGUAGE']);
        $this->xp->setParameter('', 'user', $_SESSION['u']);
        $this->xp->setParameter('', 'theName', "default");
        $this->xp->setParameter('', 'server', $_SERVER['HTTP_HOST']);
        $output = $this->xp->transformToXML($xml);
        //$output = htmlspecialchars_decode($output);
        $output = str_replace("&amp;", "&", $output);
        $this->headers[] = HTTP_HTML;
     	return $output;  	        
    }

    function setHeader(){
        if(!$this->params['DEBUG']){
        	foreach($this->headers as $header) header($header);
        }
    }
  
    function getCapabilities(){
        $langs = array("cze", "eng");
        $accept = $this->getParamL('ACCEPTVERSIONS');
        if($accept && !strpos(".".$accept,"2.0.2")){
            $this->exception(3, "ACCEPTVERSIONS", "Only version 2.0.2 is supported now.");
        }    
        $lang = $this->getParamL('LANGUAGE');
        if(!in_array($lang, $langs)){
            $lang='eng';
        }    
        if(file_exists("../cfg/cswConfig-$lang.xml")){
            $this->xml->load("../cfg/cswConfig-$lang.xml");
        }    
        else $this->xml->load("../cfg/cswConfig-eng.xml");
        $this->xsl->load(PHPINC_DIR."/xsl/getCapabilities.xsl");
        $this->xp->importStyleSheet($this->xsl);
        $this->xp->setParameter('', 'thisURL', $this->params['thisURL']);
        $this->xp->setParameter('', 'LANG', $lang);
        $processed = $this->xp->transformToXML($this->xml);
        return $processed;
    }

    function describeRecord(){
        $this->xml->loadXML("<root/>");
        $this->xsl->load(PHPINC_DIR."/xsl/describeRecord.xsl");
        $this->xp->importStyleSheet($this->xsl);
        //$xp->setParameter('', 'thisURL', "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
        $processed = $this->xp->transformToXML($this->xml);
        //header("Content-type: application/xml");
        //$processed = file_get_contents(PHPPRG_DIR."/../xsl/describeRecord.xml");
        return $processed;
    }
  
  function getRecords(){
    if(!$this->params['TYPENAMES']) $this->exception(2, "TYPENAMES", "Missing 'TYPENAMES' parameter");
    if(!$this->requestType){
      	if($this->params['CONSTRAINT']){
        	if(!$this->params['CONSTRAINT_LANGUAGE']) $this->exception(2, "CONSTRAINT_LANGUAGE", "Missing 'CONSTRAINT_LANGUAGE' parameter");      
      	} 
      	$qstr = $this->cql2sql($this->params['CONSTRAINT']); //TODO upravit CQL dotazy
      	$qstr = $qstr[0];
    }  
    else $qstr=$this->params['QSTR'];
    $this->logText .= @json_encode($qstr);
    $typeNames = $this->getParamL('TYPENAMES');

    if(($typeNames!='native')&&($this->params['OUTPUTSCHEMA'])){
      	if(!in_array($this->params['OUTPUTSCHEMA'], $this->schemas)){
        	  $this->exception(3, "OUTPUTSCHEMA", $this->params['OUTPUTSCHEMA']." is not valid value.");
      	}	
      /*if(($typeNames=="csw:record")&&($this->params['OUTPUTSCHEMA'] != $this->schemas['csw'])){
        	$this->exception(3, "OUTPUTSCHEMA", "Not valid value for typenanames ".$this->params['TYPENAMES']);
      } */
      if($this->params['OUTPUTSCHEMA']==$this->schemas['csw']) $typeNames = "csw:record";
      else if($this->params['OUTPUTSCHEMA']==$this->schemas['gmd']) $typeNames = "gmd:md_metadata";      
    }
    else {
        $this->params['OUTPUTSCHEMA'] = $this->schemas['csw'];
    }  
    
    //FIXME - dodelat zpracovani
    //if(!$qstr) $this->exception(2, "Constraint", "Empty request.");
    
    $flatParams = array();
    if(strpos($typeNames,"md_metadata")!==false){
    	$flatParams['MDS'] = 0;
    }
    $flatParams["hits"] = ($this->getParamL('RESULTTYPE')=='hits'); 
    $flatParams['extHeader'] = ($this->getParamL('EXTLIST')==1);
    
    // vyfiltrovat INSPIRE záznamy
    if($this->inspire){
    	if($qstr) $qstr[] = "And";
    	$qstr[] = "@type != 'application'";
    }
    if($this->params['DEBUG']){
    	var_dump($qstr);
    }
    
    $format = $this->params['FORMAT'];
    if(!$this->params['STARTPOSITION']) $this->params['STARTPOSITION']=1;
    $this->params['SORTORDER'] = "ASC";
    if(!$this->params['SORTBY']){ 
        $this->params['SORTBY']="";
    }  
    else {
        $sortby = $this->params['SORTBY'];
        if(strpos($this->params['SORTBY'], ':')){
          $pom = explode(":", $this->params['SORTBY']);
          if($pom[1]=='D') $this->params['SORTORDER'] = "DESC"; 
          $sortby = $pom[0].",".$this->params['SORTORDER'];
        }
    }  
    if(!$this->params['MAXRECORDS']) $this->params['MAXRECORDS']= MAXRECORDS;
    if($this->params['MAXRECORDS']>LIMITMAXRECORDS) $this->params['MAXRECORDS'] = $this->params['MAXRECORDS']= LIMITMAXRECORDS;
    
    //---- dotaz do Micky ------------------------------------------------------
  	$export = new MdExport($_SESSION['u'], $this->params['STARTPOSITION'], $this->params['MAXRECORDS'], $sortby);
  	//$export->xml_from = 'data'; // z puvodnich dat
  	//if($qstr=='{}') $qstr=array(); //TODO hack pro url dotazy - dosud neni udelano zpracovani
  	//if(strpos($format, 'html')!==false || $this->params['OUTPUTSCHEMA']==$this->schemas['os']){ //TODO udalat jednotne
  		$xmlstr = $export->getXML($qstr, $flatParams, true, true);
  	//}
  	//else list($xmlstr, $head) = $export->getXML($qstr, $flatParams);
    //-------------------------------------------------------------------------- 
    
  	//header(HTTP_HTML);
  	//die($xmlstr);
  	
    if($xmlstr==-1) $this->exception(3, "Filter", "Invalid filter: ".$qstr);

    $resultType = $this->getParamL('RESULTTYPE');
    if(!$resultType) $resultType = 'results'; 
       
    switch($resultType){
      case 'hits': $sablona='micka2cat_hits'; break;
      case 'validate': $sablona='micka2cat_hits'; break;
      case 'results':
        switch ($this->params['OUTPUTSCHEMA']){
          case $this->schemas['csw']: $sablona="micka2cat_dc"; break;
          case $this->schemas['gmd']: $sablona = "micka2cat_19139"; break;
          case $this->schemas['native']: $sablona = "micka2native"; break;
          case $this->schemas['rss']: $sablona="micka2osrss"; $format=""; break;
          case $this->schemas['atom']: $sablona="micka2atom"; $format=""; break;
          case $this->schemas['kml']: $sablona="micka2kml"; $format="kml"; break;
          case $this->schemas['os']: $sablona="micka2os"; $format=""; break;
          case $this->schemas['rdf']: $sablona="micka2rdf"; $format=""; break;
          case $this->schemas['dcat']: $sablona="micka2dcat"; $format=""; break;
          case $this->schemas['oai_dc']: $sablona="micka2oai_dc"; $format=""; break;
          case $this->schemas['oai_marc']: $sablona="micka2oai_marc"; $format=""; break;
          case $this->schemas['marc21']: $sablona="micka2marc21"; $format=""; break;
          default: $this->exception(3, "OUTPUTSCHEMA", $this->params['OUTPUTSCHEMA']); break; 
        }
        break;  
      default:
      	$this->exception(3, "RESULTTYPE", $this->params['RESULTTYPE']); break;   
    }

  //---vyber brief / summary / full
  if($sablona && $resultType=='results' ){ 
    switch ($this->getParamL('ELEMENTSETNAME')){
      case 'brief': $sablona .= '_brief'; break;
      case 'summary': $sablona .= '_summary'; break;
      case 'full': $sablona .= '_full'; break; 
      default: 
      	$sablona .= '_summary'; 
      	$this->params['ELEMENTSETNAME'] = 'summary';
      break;
    }  
    $version = $this->params['VERSION'];
    if($version=="2.0.0") $sablona .= "200";
  }  
  $this->params['CATCLIENT_PATH'] = CATCLIENT_PATH;
  $this->params['lang'] = $this->params['LANGUAGE'] ? $this->params['LANGUAGE'] : MICKA_LANG;
  
  //echo $this->params['FORMAT']; exit;
  if(strpos($format, 'html')!==false){
  		$this->headers[0] = HTTP_HTML;
  		$this->isXML = false;
  		$sablona = 'micka2htmlList';
  }
  else if(strpos($format, 'csv')!==false){
      $this->headers[0] = HTTP_CSV;
      $this->isXML = false;
  }
  else if(strpos($format, 'kml')!==false){
		$this->headers[0] = "Content-Type: application/vnd.google-earth.kml+xml\n";
		$this->headers[1] = "Content-Disposition: filename=micka-open.kml";
  }
  
  
  if($this->params['TEMPLATE']){
  		$sablona = $this->params['TEMPLATE'];
  }
  //---cekani na vzdalene servery
  if($this->params['HOPCOUNT']>0){ 
    file_put_contents(CSW_TMP."/$cascadeID-local.xml" ,$xmlstr);
    $status = false;
    $timestop = time()+CSW_TIMEOUT; // za jak dlouho to ma chcipnout
    if(!class_exists('CswClient')){
    	include(PHPPRG_DIR.'/CswClient.php');
    }
	$client = new CswClient();
    while(!$status){
      $status = true;
      reset($cswlist);
      while(list($name, $csw) = each ($cswlist)){
      	// TODO - tady dodelat
        $result = CSW_TMP."/$cascadeID-$name.xml";
        if(!file_exists($result)) $status = false;
        if($timestop<time()) $status = true; // aby to neviselo
      }
      sleep(1);
    }
    $this->xml->load(PHPINC_DIR.'/csw/cservers.xml');
    $this->xsl->load(PHPINC_DIR."/../xsl/cascade.xsl");
    $this->xp->importStyleSheet($this->xsl);
    $this->xp->setParameter('', 'cascadeId', CSW_TMP."/".$cascadeID);
    
    /*if($status>0){
      while(list($key, $val) = each ($_SESSION["cswlist"])){
        @unlink(CSW_TMP."/$id-$key.htm");
      }
    }*/
  }
  //---prevod XML do katalogu
    else{
      	$this->xml->loadXML($xmlstr);   
     	$this->xsl->load(PHPPRG_DIR."/../xsl/$sablona.xsl");
      	$this->xp->importStyleSheet($this->xsl);
    }    

    $this->params['root'] = "csw:GetRecordsResponse";
    $this->params['REQUESTID'] = $this->params['REQUESTID']."";
    $this->params['CONSTRAINT'] = urlencode($this->params['CONSTRAINT']);
    $this->setXSLParams($this->params); 
    
    // --- JSON ---
    if(strpos($format, 'json')!==false){
        $processed = $this->xp->transformToDoc($this->xml);
        $output = $this->asJSON($processed, $head, $flatParams['extHeader']);
        $this->isXML = false;
    }
    // --- HTML ---
    else if(strpos($format, 'html')!==false){
        $output =$this->xp->transformToXML($this->xml);
        //$output = htmlspecialchars_decode($output);
        $output = str_replace("&amp;", "&", $output);
    }
    // --- XML ---
    else {
        $output =$this->xp->transformToXML($this->xml);
    }
    
    return $output;
  }

    function getRecordById(){
        $qstr = ""; 
        if(!$this->params['ID']) $this->exception(2, "ID", ""); 
        $ids = explode(",", $this->params['ID']);
        foreach($ids as $id) if($id){
        	if($qstr) $qstr .= ",";
        	$qstr .= "'".urldecode($id)."'";
        }
        if($this->params['DEBUG']==1) var_dump($this->params['ID']);
    
        //---- dotaz do Micky ------------------------------------------------------
    	$export = new MdExport($_SESSION['u'], 0, 25, $this->params['SORTBY']);
    	//$export->xml_from = 'data'; // z puvodnich dat
    	//if($this->params['FORMAT']=='text/html'){
    		$xmlstr = $export->getXML(array(), array("ID" =>"($qstr)"), true, true);
    	//}
    	//else {
    	//	list($xmlstr, $head) = $export->getXML(array(), array("ID" =>"($qstr)"));
    	//}	
        //-------------------------------------------------------------------------- 
        if($xmlstr==-1) $this->exception(3, "Filter", "Invalid filter: ".$xmlstr); 
    
        $sablona = "micka2cat_19139";
        if($this->params['OUTPUTSCHEMA']){
            if(!in_array($this->params['OUTPUTSCHEMA'], $this->schemas)){
                $this->exception(3, "OUTPUTSCHEMA", $this->params['OUTPUTSCHEMA']." is not valid value.");
            } 
            switch ($this->params['OUTPUTSCHEMA']){
                case $this->schemas["gmd"]:
                    $sablona = "micka2cat_19139";
                    break;
                case $this->schemas["atom"]:
                    $sablona = "micka2atom";
                    break;
                case $this->schemas["dcat"]:
                    $sablona = "micka2dcat";
                    break;
                default:
                    $sablona = "micka2cat_dc";
                    break;
            }     
        }  
        
        switch ($this->getParamL('ELEMENTSETNAME')){ //TODO - nefunguje v sablone
          case 'brief': 
              $sablona .= '_brief'; break;
          case 'summary': 
              $sablona .= '_summary'; break;
          case 'full':  
          default: 
          	$sablona .= '_full'; // podle standardu summary 
          	$this->params['ELEMENTSETNAME'] = "full"; 
          	break;
        }  
		
        $this->logText .= "[ID=".$qstr."]";
        
        //TODO - kaskadovani
        //---prevod XML do katalogu
        $this->xml->loadXML($xmlstr);   

        $this->xsl->load(PHPPRG_DIR."/../xsl/$sablona.xsl");
        $this->xp->importStyleSheet($this->xsl); 
         
        $this->xp->setParameter('', 'requestId', $this->params['REQUESTID']);
        $this->xp->setParameter('', 'thisPath', dirname($this->params['thisURL']));
        $this->xp->setParameter('', 'LANGUAGE', $this->params['LANGUAGE']);
        $this->xp->setParameter('', 'ID', $this->params['ID']);
        $this->xp->setParameter('', 'version', $this->params['VERSION']);
        $this->xp->setParameter('', 'root', "csw:GetRecordByIdResponse");
        $this->xp->setParameter('', 'elementSet', $this->getParamL('ELEMENTSETNAME'));
        $this->xp->setParameter('', 'user', $_SESSION["u"]);
        
        // --- HTML ---
        if($this->params['FORMAT']=='text/html'){
            //$processed = $this->xp->transformToDoc($this->xml);
            //echo CATCLIENT_PATH."/xsl/iso2htmlFull.xsl"; exit;
            if($params['TEMPLATE']) $sablona = $params['TEMPLATE'];
            else $sablona = "iso2htmlFull_";
            //die(PHPPRG_DIR);
            $output = $this->asHTML($this->xml, PHPPRG_DIR."/../xsl/$sablona.xsl");  // TODO - podle konfigurace 
            $this->isXML = false;     
        }
        // --- XML ---
        else {
            $output = $this->xp->transformToXML($this->xml);
        }
        return $output;
    }
  
  function harvest($io = true){
    //var_dump($this->params);
    include(PHPPRG_DIR.'/Harvest.php'); 
    include(PHPPRG_DIR.'/CswClient.php'); 
    $cswFrom = new CSWClient(); 	
    $harvestor = new Harvest($this, $cswFrom);
    // jen vrati hodnoty - nad ramec standardu
    if($io == false){
        $result = $harvestor->getParameters($this->params['ID']);
        header("Content-type: application/json");
        echo json_encode($result);
        exit;
    }
    // implicitni hodnota
    if(!$this->params['RESOURCETYPE']){
        $this->params['RESOURCETYPE'] = "csw/2.0.2";
    }     
    //--- save to database ---
    if($this->params['HANDLERS']){
      if(!$this->params['ID']) $this->params['ID'] = $this->params['SOURCE'];
      if($this->params['HARVESTINTERVAL']!=''){
	      $result = $harvestor->setParameters(
	      	$this->params['ID'], 
	      	$this->params['SOURCE'], 
	      	$this->params['RESOURCETYPE'], 
	      	$this->params['HANDLERS'],
	      	$this->params['HARVESTINTERVAL'],
	      	"" // TODO tam muze byt filter
	      );
      }    
	  else{
	  	//TODO poslat hned uzivateli 
       	$result = $harvestor->runResource(array(
       	  	'source'=>$this->params['SOURCE'],
       	  	'name'=>'instant',
       		'type'=>$this->params['RESOURCETYPE']
       	));
        $result =  $this->updateResponse($result, "Update");
        $h = explode("|", $this->params['HANDLERS']);
	    file_put_contents($h[0],$result); //FIXME - toto je docasne 
	  }
	  $this->logText .= "HARVEST";
	  // XML verze
	  if(count($_GET)==0){ // quick and dirty
      	$this->xsl->load(PHPPRG_DIR."/../xsl/HarvestResponse.xsl");
      	$this->xp->importStyleSheet($this->xsl); 
      	$this->xp->setParameter('', 'timestamp', gmdate("Y-m-d\TH:i:s")); // svetovy cas 
      	$processed = $this->xp->transformToXML($this->xml);
	  }
	  // navic - JSON
	  else {
	    header("Content-type: application/json");
	  	echo json_encode($result);
	  	exit;
	  }
      return $processed;
    }
    
    //--- runs immediately ---
    else{
       $result = $harvestor->runResource(array(
       	  	'source'=>$this->params['SOURCE'],
       	  	'name'=>'blee',
       		'type'=>$this->params['RESOURCETYPE']
       ));
       return $this->updateResponse($result, "Update");
	}
  }
  
  	private function setXSLParams($params){
    	$this->xp->setParameter('', $params);
    }
  
  private function setRssParams($dni){
    if(!$this->params['LANGUAGE']) $this->params['LANGUAGE'] = 'cze';
    $this->xp->setParameter('', 'lang', $this->params['LANGUAGE']);
    $this->xp->setParameter('', 'days', $dni);
    //$this->xp->setParameter('', 'url', "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])); 
  }
  
  /*function rss(){
    $dni = $this->params['DAYS'];
    if(!$dni) $dni = 7;
    $cas = date("Y-m-d", time()-($dni*3600*24));  
    $request = "csw:GetRecords";
    //$requestId = "micka";
    //$resultType = "results";
    //$typeNames = "rss";
    //$debug = "0";
      
    $qstr[] = "_DATESTAMP_ >= '$cas'";
    
	$export = new MdExport($_SESSION['u'], $this->params['STARTPOSITION'], $this->params['MAXRECORDS'], $this->params['SORTBY']);
	list($xmlstr, $head) = $export->getXML($qstr, array());

    if($this->params['DEBUG']==2){
    	echo $xmlstr; return; 
    }
    $this->xml->loadXML($xmlstr);   
    $this->xsl->load(PHPPRG_DIR."/../xsl/micka2rss.xsl");
    $this->xp->importStyleSheet($this->xsl);
    $this->setParams($this->params);
    $rss = $this->xp->transformToXML($this->xml);
    return $rss; 
  }*/

	/*
  // NOTE: pokud bude funkce potřeba, je nutno přepsat dotaz a zpracování SQL
	function parties(){
  	// TODO - nejak zabalit, neni prilis ciste
  	$query = "";
	if($_REQUEST['creator']) $query = " AND md.create_user='".$_REQUEST['creator']."'";
	if($_REQUEST['query']){
		$query .= " AND " . setSqlLike ('md_values.md_value', "'%" . $_REQUEST['query'] . "%'");
	}
	$sql="SELECT DISTINCT md_values.md_value 
		FROM md INNER JOIN md_values ON md.recno = md_values.recno
		WHERE md_values.md_id IN (187,5029) AND md.data_code<2 AND md.data_access='1'
		$query 
		ORDER BY md_values.md_value";
	$result = $GLOBALS['db']->Execute($sql);
	if ($result) {
		$records = 0;
		while (!$result->EOF) {
			$records++;
			if ($records > 1) {
				$rs .= ', ';
    		}
			$rs .= '{id: \''.$records.'\', name: \'' . $result->fields["MD_VALUE"] . '\'}';
			$result->MoveNext();
		}
	}
	else {
		echo "SQL ERROR: ";
		echo $sql . '<br>';
	}
	$rs = '{numresults:' . $records . ', records:[' . $rs . ']}';
	echo $rs;
  }
	*/

  function saveLog(){
    if(!$this->logFile) return;
    $logfile = fopen($this->logFile.gmdate("-Y-m"), "a"); 
    fwrite($logfile, $this->logText."\n");  
    fclose($logfile);
  }
  
  function updateResponse($result, $action){
  	$success = "";
  	$numSuccess = 0;
  	$errIds = array();
  	$errReport = "";
  	if($result['error']) $this->exception($result['error'][0], $result['error'][1], $result['error'][2]); 
  	foreach($result as $record){
 	  if($record['ok']){
 	  	$numSuccess++;
  		$success .= "<csw:BriefRecord><dc:identifier>$record[uuid]</dc:identifier><dc:title>$record[title]</dc:title></csw:BriefRecord>";
 	  }
  	  else{
  		$errIds[] = $record['uuid'];
  		$errReport .= $record['report']."\n\n";
      }
  	}
  	if($numSuccess==0){
  	  $this->exception(6, "records IDs: ".implode(",", $errIds), $errReport);
  	}
  	if($action=='Insert') $action = "Inserte";
   $s ='<csw:TransactionResponse
   xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" 
   xmlns:dc="http://purl.org/dc/elements/1.1/" 
   xmlns:gml="http://www.opengis.net/gml" 
   xmlns:ogc="http://www.opengis.net/ogc" 
   xmlns:ows="http://www.opengis.net/ows" 
   xmlns:xlink="http://www.w3.org/1999/xlink" 
   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" > 
   <csw:TransactionSummary>
  		<csw:total'.$action.'d>'.$numSuccess.'</csw:total'.$action.'d>
   </csw:TransactionSummary>';  
   if($action!='Delete'){
   		$s .= '<csw:InsertResult>'.$success.'</csw:InsertResult>';
   }		
   return $s.'</csw:TransactionResponse>';
  }
  
  /**
   * Performs Trasaction (save/update data in underlying database)
   *
   */
  function transaction(){
    if(!canAction('w')) $this->exception(1, "Transaction", "You don't have permission to transaction.");
    $this->logText .= strtoupper($this->params['REQTYPE']);
    switch(strtolower($this->params['REQTYPE'])){
      case "csw:delete": 
          return $this->updateResponse($this->delete(), "Delete"); 
          break;
      case "csw:update": 
          return $this->updateResponse(
              $this->update('', 
                  $this->params['GROUP_EDIT'], 
                  $this->params['GROUP_READ'],
                  $this->params['IS_PUBLIC'],
                  false, 'update'), 
              "Update"); 
          break; 
      case "csw:insert": 
          return $this->updateResponse(
              $this->update('', 
                  $this->params['GROUP_EDIT'], 
                  $this->params['GROUP_READ'],
                  $this->params['IS_PUBLIC'],
                  false, 'insert'), 
              "Insert"); 
          break; 
      default: 
          $this->exception(3, $this->params['REQTYPE'], "Not supported transaction type."); 
          break;
    }
    return false;           
  }

  /**
   * Inserts or updates record in the underlying database
   *
   * @param string $nodeName Identifier of server which data comes from (used for harvesting)
   * @param string $viewGroup Name of the group for viewing (used for CENIA filters)
   * @param boolean $stopOnError If set true, insert no record if error occurs. Otherwise attempts to insert at least valid elements. 
   * @return array Associative array with update results (both successful and failed records)
   */
  function update($nodeName='', $editGroup='', $viewGroup='', $public=0, $stopOnError=true, $overwrite='all'){
    $importer = new MetadataImport($this->params['DEBUG']);
    $md = $importer->xml2array($this->xml, PHPPRG_DIR."/../xsl/update2micka.xsl");
    
    $c = new MdImport();
  	$c->setDataType($public); // nastavení veřejného záznamu
  	if($editGroup){
		$c->group_e = $editGroup; 
	}
  	if($viewGroup){
		$c->group_v = $viewGroup; 
	}
  	$c->stop_error = $stopOnError; // pokud dojde k chybě při importu pokračuje 
  	$c->server_name = $nodeName; // jméno serveru ze kterého se importuje
  	$c->setReportValidType('array', true); // formát validace
    $result = $c->dataToMd($md, $overwrite); 
    if($this->params['DEBUG']==1) var_dump($result);
    return $result;
  }
  
  private function delete(){
  	$export = new MdExport($usr);
  	$data = $export->getData(array($this->params['QSTR'])); //TODO zaznamy apod ...
    $c = new MdImport();
    $result = $c->dataToMd($data,'delete');
    if($this->params['DEBUG']==1) var_dump($result);
    return $result;
  }
  
  //--- vypujceno z importMetadata class - uz nekompatibilni !!!
  function writeNode($path, $node, $idx){
  	$s = "";
  	//atributy
    if(($node->nodeType!=XML_TEXT_NODE)&&($node->hasAttribute('codeListValue'))&&(trim($node->getAttribute('codeListValue'))!="")){
    	$s = $path."['".$node->nodeName."'][0]['@']='".addslashes(trim($node->getAttribute('codeListValue')))."';\n";
    }
  	else if($node->hasChildNodes()){
      $nodes = $node->childNodes;
      $lastNode = '';
      $lastLangs = Array();
      $j = 0;
      for($i=0;$i<$nodes->length;$i++){
        if($nodes->item($i)->nodeName!="#text"){
          $lang = '';
          if($nodes->item($i)->hasAttribute('lang')) $lang = $nodes->item($i)->getAttribute('lang'); 
          if($nodes->item($i)->nodeName==$lastNode){
            // pro nativni data ++ opicarny kvuli keywords
    	    if($lang){
    	      if(in_array($lang, $lastLangs)){
 			  	$j++;
   			  	$lastLangs = Array();
    	      }	
   			}
      	  	else $j++;
      	  }
      	  else $j=0;
      	  if($lang) $lastLangs[] = $lang;
      	  $lastNode=$nodes->item($i)->nodeName;
      	} 
      	if(!$path) $s .= $this->writeNode( "\$md", $nodes->item($i), $j);
        else $s .= $this->writeNode( $path."['".$node->nodeName."'][$idx]", $nodes->item($i), $j);
      }
    } 
    // konec vetve - text
    else if(trim($node->nodeValue)!=""){
   	  if($node->parentNode->hasAttribute('lang')){
   		$lang = $node->parentNode->getAttribute('lang');
   		$path .= "['@".$lang."']"; 
   	  }
      else if($node->parentNode->hasAttribute('locale')) 
       	$path .= "['@".substr($node->parentNode->getAttribute('locale'),7)."']"; //FIXME - preklad do kodu jazyka
      else $path .= "['@']";  
      $s = $path."='".addslashes(trim($node->nodeValue))."';\n";
    } 
    return $s;
  }
  
  /*
  * Prevod CQL do vnitrniho formatu micky
  *
  * @param $cql - retezec dotazu
  * @return     - pole pro micku    
  */    
  function cql2sql($cql){ // TODO vylepsit dotaz
    $cql = str_replace("*", "%", $cql);
    
    $in  = array('csw:', 'gmd:','"', 'anytext', 'AnyText', 'Anytext',
    	'modified', 'language', 'TempExtent_begin', 'TempExtent_end',
    	'HierarchyLevelName', 'hierarchyLevelName', "type='featureCatalogue'",
      	'type', 'HierarchyLevel', 'ServiceType', 'identifier', 'TopicCategory', 'title', 
      	'abstract', 'RevisionDate', 'creator', 'mayedit', 'groups', 'OrganisationName', 'subject',
    	'Degree', 'SpecificationTitle', 'Server', 'ResourceLanguage', "uuidRef", "ParentIdentifier", "ResourceIdentifier",
    	"IsPublic", "MdCreator", "Denominator", "MdIndividualName", "IndividualName",
    	"FcIdentifier", "OtherConstraints", "ConditionApplyingToAccessAndUse", "Fees", "Protocol",
    	"TempExtent_begin", "TempExtent_end", "BBOX", "ThesaurusName", "BBSpan", "LinkName", 
        "ResponsiblePartyRole", "Linkage", "MetadataRole", "MetadataContact", "ContactCountry"            
      	
    );
    
    $out = array('', '', '', '%', '%', '%',
    	'_DATESTAMP_', '_LANGUAGE_', '_DATEB_', '_DATEE_', 
    	'@hlname', '@hlname', '_MDS_=2',
    	'@type', '@type', '@stype', '_UUID_', '@topic', '@title', 
      	'@abstract', '@date', '_CREATE_USER_', '_MAYEDIT_', '_GROUPS_', '@contact', '@keyword',
    	'@sp.degree', "@sp.title", "_SERVER_", '@rlanguage', '@uuidref', "@parent", "@resourceid",
    	"_DATA_TYPE_", "_CREATE_USER_", "@denom", "@mdinnaco", "@innaco", 
    	"@fcid", "@otherc", "@ausec", "@fees", "@protocol",
    	"_DATEB_", "_DATEE_", "_BBOX_", "@thesaurus", "_BBSPAN_", "@lname",
        "@role", "@linkage", "@mdrole", "@md_contact", "@country"
    );
        
    $cql = str_replace($in, $out, $cql);
    
    if (DB_DRIVER == 'oracle' && DB_FULLTEXT == 'ORACLE-CONTEXT'){
    	$cql = str_replace(
    		array("@title", "@abstract"),
    		array("//gmd:identificationInfo/*/gmd:citation/*/gmd:title", "//gmd:identificationInfo/*/gmd:abstract"),
    		$cql
    	);
    }
    return array($this->cql2sql_($cql));
  }
  
  /*
  * Prevod CQL do vnitrniho formatu micky - vnitrni rekurzivni volani z cql2sql
  *
  * @param $s - podretezec dotazu 
  * @return  - pole pro micku   
  */    
    private function cql2sql_($s){
      	$i = 0;
      	$tnum = 0;
      	$tokens = array();
      	
      	while($i<strlen($s)){
    	    $ch = substr($s,$i,1);
      		if($ch=="'"){
        		$pos = strpos($s, "'", $i+1);
        		$tokens[$tnum] .= substr($s,$i,$pos-$i+1);
        		$i=$pos;
      		}	
    	    else if($ch=='('){
    	        $pos = strpos($s, ")", $i); 
    	        $tokens[$tnum]=$this->cql2sql_(substr($s,$i+1,$pos-$i-1)); 
    	        //$tnum++;
    	        $i=$pos;
    	    }
    	    else if(strtoupper(substr($s,$i,5))==' AND '){
    	    	$tnum++;
    	    	$tokens[$tnum] = 'AND';
    	    	$tnum++;
    	    	$i += 4;
    	    }
      	    else if(strtoupper(substr($s,$i,5))==' AND('){
    	    	$tnum++;
    	    	$tokens[$tnum] = 'AND';
    	    	$tnum++;
    	    	$i += 3;
    	    }
    	    else if(strtoupper(substr($s,$i,4))==' OR '){
    	    	$tnum++;
    	    	$tokens[$tnum] = 'OR';
    	    	$tnum++;
    	    	$i += 3;
    	    }
      	    else if(strtoupper(substr($s,$i,4))==' OR('){
    	    	$tnum++;
    	    	$tokens[$tnum] = 'OR';
    	    	$tnum++;
    	    	$i += 2;
    	    }
    	    else if(strtoupper(substr($s,$i,4))=='NOT '){
    	    	$tnum++;
    	    	$tokens[$tnum] = 'NOT';
    	    	$tnum++;
    	    	$i += 3;
    	    }
      	    else if(strtoupper(substr($s,$i,4))=='NOT('){
    	    	$tnum++;
    	    	$tokens[$tnum] = 'NOT';
    	    	$tnum++;
    	    	$i += 2;
    	    }
    	    else { 
            	if (!isset($tokens[$tnum])) $tokens[$tnum]="";
            	$tokens[$tnum] .= $ch;
        	}	
        	$i++; 
      	}
      	
      	for($i=0;$i<count($tokens);$i++){
        		if(is_string($tokens[$i])){
        		    $op = "";
        		    if(strpos($tokens[$i],">=")) $op = ">=" ;
        		    else if(strpos($tokens[$i],"<=")) $op = "<=" ;
        		    else if(strpos($tokens[$i],"!=")) $op = "!=" ;
        		    else if(strpos($tokens[$i],"<")) $op = "<" ;
        		    else if(strpos($tokens[$i],">")) $op = ">" ;
        		    else if(strpos($tokens[$i],"=")) $op = "=" ;
        		    $op = trim($op);
          		    if($op){
              			$pom = explode($op, $tokens[$i]);
              			$pom[0] = trim($pom[0]);
              			$pom[1] = trim($pom[1]);
              			if(substr($pom[1],0,1)!= "'" && $pom[1]!='null') $pom[1] = "'" . $pom[1];  			
              			if(substr($pom[1],-1) != "'" && $pom[1]!='null') $pom[1] .= "'";  
              			$tokens[$i] = implode(" $op ",$pom);
          		  }
          		  else $tokens[$i] = trim($tokens[$i]);			
        		}
      	}
      	
      	return $tokens;  		
    }
} // class
