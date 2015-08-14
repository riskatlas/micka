<?php
if(!extension_loaded("xsl")){
	die('ERROR. xsl extension is not loaded.');
}
session_start();
chdir(dirname(__FILE__));
//require '../cfg/config.php';
//require 'Wmc.php';
include ("../application/micka_config.php");
include(PHPPRG_DIR.'/micka_main_lib.php');
include(PHPPRG_DIR.'/micka_lib.php');
include(PHPPRG_DIR.'/micka_lib_auth.php');
include_once(PHPPRG_DIR.'/MdRecord.php');
include(PHPPRG_DIR.'/MdExport.php');
// ----------------------------------------------------------------------

class Alive{

    var $err = "";
    var $metadata = null;
    const WMS = "http://www.opengis.net/wms";
    const WFS = "http://www.opengis.net/wfs";
    const CSW = "http://www.opengis.net/cat/csw/2.0.2";
    const OWS = "http://www.opengis.net/ows";
    var $toProcess = array("WMS", "CSW", "view", "OGC:WMS");

    function __construct($mails = ""){
        $this->err = "";
        $this->mails = $mails;
        $this->xml = new DomDocument;
        $this->xsl = DomDocument::load(dirname(__FILE__).'/../xsl/alive.xsl'); //TODO - konfigurovat
        $this->xp = new XsltProcessor();
        $this->xp->importStyleSheet($this->xsl);
    }
    
    // cteni metadat z micky
    function getMetadata(){
        $this->metadata = array();
        $mdstart=1;
        try{
            do {
                $metadata = new MdExport($_SESSION['u'], $mdstart, 25, "");
                $metadata->only_public = TRUE;
                $flatParams = array();
                $q = array("@type = 'service'");
        	      list($xmlstr,$head) = $metadata->getXML($q, $flatParams);
                $this->xml->loadXML($xmlstr);
                $result = $this->xp->transformToXML($this->xml);
                //echo $result;
                eval($result);
                //echo "mdstart=$mdstart / $records\n";
                $this->metadata = array_merge($this->metadata, $md);
                $mdstart += $returned;
            } while ($records >= $mdstart);
        } catch (Exception $e){
            echo "catalogue not connected";
            var_dump($e);
            exit;
        }  
        return true;  
    }
    
    // kontrola jedne sluzby
    function check($metadata){
        $this->err = "";
        // --- uprava URL ---
        //--- otaznik na konci
        if(strpos($metadata['url'], '?')===false) {
            $metadata['url'] .= "?";
        }
        switch (strtoupper($metadata['stype'])){
            case 'WMS':
            case 'OGC:WMS':
            case 'VIEW':             
        	    $itype = "WMS";
                break;
            case 'CSW':   
            case 'OGC:CSW':
            case 'DISCOVERY':   
        	    $itype = "CSW"; 
                break;
            case 'WFS':   
            case 'OGC:WFS':
        	    $itype = "WFS";  
                break;
            default: 
                break;
        }           
                //--- je-li service
        if(strpos(strtolower($metadata['url']), 'service=')===false){  
        	if(substr($metadata['url'],-1)!='?' && substr($metadata['url'],-1)!='&') $metadata['url'] .= '&';
        	$metadata['url'] .= "SERVICE=".$itype;      
        }   
        //--- je-li request=
        if(strpos(strtolower($metadata['url']), 'request=')===false){  
        	 if(substr($metadata['url'],-1)!='?' && substr($metadata['url'],-1)!='&') $metadata['url'] .= '&';
        	 $metadata['url'] .= "REQUEST=GetCapabilities"; 
        }

       /*** 1. existuje spojeni ? ***/
       $context = stream_context_create(array('http'=>array('timeout' => 6.0)));
       if(!$fp = @fopen($metadata['url'], 'r', false, $context)){
            $this->err = "bad address or resource timeout";
            return 0;
        }    
        $str = "";
        while(!feof($fp)){
            $str .= fread($fp,4096);
        }    
        fclose($fp);
        
        /*** 2. GetCapabilities XML ? ***/
        $xml = DOMDocument::loadXML($str);
        if(!$xml){
            $this->err = "Not valid XML";
            return 0;
        }
        
        /*** 3. nalezeni vrstev pro WMS ***/
        switch ($itype){
            case 'WMS':
                $lyrs = $xml->getElementsByTagNameNS($this->WMS, 'Layer');
                if($lyrs->length==0){
                    $lyrs = $xml->getElementsByTagName('Layer');
                }
                if($lyrs->length==0){
                    $this->err = "Layer element not found";
                    return 0;
                }
                break;
                
            case 'CSW':
                $cap = $xml->getElementsByTagName('ServiceType');
                if($cap->length==0){
                    $this->err = "ServiceType element not found";
                    return 0;
                }
                $t = $cap->item(0)->nodeValue;
                if($t != 'CSW'){
                    $this->err = "ServiceType != CSW";
                    return 0;
                }
                break;
                
            case 'WFS':
                $lyrs = $xml->getElementsByTagNameNS($this->WFS, 'FeatureType');
                if($lyrs->length==0){
                    $lyrs = $xml->getElementsByTagName('FeatureType');
                }
                if($lyrs->length==0){
                    $this->err = "FeatureType element not found";
                    return 0;
                }
                break;
                                
            default:
            	$this->err = "Only WMS a CSW are supported";
            	return 0;                       
        }
        /*** 4. GetMap ? ***/
        //TODO   
        
        return 1;                                                     
    }
    
    function report(){
    }

    // kontrola vsech sluzeb - redukuje radky na vybrane typy sluzeb
    function checkAll($invalidate=false){
        $md = array();
        $pass = array();
        $fail = array();
        for($i=0; $i<count($this->metadata); $i++){
            $row = $this->metadata[$i];
            if(in_array($row['stype'], $this->toProcess)){
                $row['url'] = html_entity_decode($row['url']);
                
                //echo $row['url'];
                if(substr($row['url'],0,5)=='https'){
                    $row['status'] = -1;
                    $row['errMsg'] = 'Chráněná služba';
                }
                /***  hack kvuli CUZK ***/
                else if(strpos($metadata['url'], 'cuzk')){ 
                    $row['status'] = 1;
                    $row['errMsg'] = 'CUZK';
                }  
                else{
                    $row['status'] = $this->check($row);
                    $row['errMsg'] = $this->err; 
                }
                $this->metadata[$i] = $row;
                //if($row['status']>0) echo " OK\n";
                //else echo $row['errMsg']."\n";
                // pole k invalidaci
                if($row['status']>0)  $pass[] = $row['id'];
                else $fail[] = $row['id']; 
            }
        }
        $rs = main_data_type('publish', implode(",",$pass));
        $rs = main_data_type('privatize', implode(",",$fail));
    }

    function mailErrors(){
        if(!$this->mails) return;
        $report = "";
        foreach($this->metadata as $row){
            if($row['status'] == 0){
                $report .= "$row[id]: $row[title] <br/>$row[errMsg] ($row[url]) <br/><br/>";
            }
        }
        if($report){
            $this->mailHTML($this->mails, 'Geoportal - sluzby', $report, '');
        }    
    }

    function mail($to, $subject = '(No subject)', $message = '', $header = '') {
        $header_ = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=UTF-8' . "\r\n";
        return mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header_ . $header);
    }

    function mailHTML($to, $subject = '(No subject)', $message = '', $header = '') {
        $mime_boundary = "----geoportal----".md5(time());
        $header_  = 'MIME-Version: 1.0' . "\r\n"; 
        $header_ .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        //$header_ .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
        //$header_ .= "Content-Transfer-Encoding: 8bit\n\n";
        return mail(
          $to, 
          '=?UTF-8?B?'.base64_encode($subject).'?=', 
          //"--$mime_boundary\n"
          //  . "Content-Type: text/html; charset=UTF-8\n" 
          //  . "Content-Transfer-Encoding: 8bit\n\n"
             $message,
          //  . "--$mime_boundary\n", 
          $header_ . $header
        );
    }
    
}

// --- MAIN ---

// --- misto prihlaseni bude rovnou  struktura - dost derave
$_SESSION['u'] = "harvest";
$_SESSION['maplist']['micka']['users']['harvest'] = "rwp*";
$_SESSION['ms_groups'] = "harvest";

$alive = new Alive();
if(!$alive->getMetadata()) die('chyba');
$alive->checkAll();
//$alive->mailErrors();

?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<style>
   h1 {padding-left:50px; background: url(style/heartbeat.gif) no-repeat; }
  .pass {padding-left:20px; background: url(style/accept.png) no-repeat; margin:5px; }
  .fail {padding-left:20px; background: url(style/cross.png) no-repeat; margin:5px; }
  .warn {padding-left:20px; background: url(style/error.gif) no-repeat; margin:5px; }
  body {font-family: sans-serif; font-size:11pt}
</style>
</head>
<body>
<h1>INSPIRE Geoportal alive services</h1>
<?php

echo '<div style="border-bottom: 1px solid gray;">';
echo "Time: ".date('d.m.Y H:i:s')."</div>";

$i=1;
foreach($alive->metadata as $row){
    //echo $i++;
    if($row['status'] == 1){
        echo "<div class=\"pass\">".$i++." $row[stype] <a href=\"".MICKA_URL."/micka_main.php?ak=detail&uuid=$row[id]\" target=\"_blank\">$row[title]</a></div>";
    }
    else if($row['status'] == -1){
        echo "<div class='warn'>".$i++." $row[stype] <a href=\"".MICKA_URL."/micka_main.php?ak=detail&uuid=$row[id]\" target=\"_blank\">$row[title]</a><br/>";
        echo "$row[errMsg] (<a href=\"$row[url]\" target=\"_blank\">$row[url]</a>)</div>";
    }
    else{
        echo "<div class='fail'>".$i++." $row[stype] <a href=\"".MICKA_URL."/micka_main.php?ak=detail&uuid=$row[id]\" target=\"_blank\">$row[title]</a><br/>";
        echo "$row[errMsg] (<a href=\"$row[url]\" target=\"_blank\">$row[url]</a>)</div>";
    }
}
?>
</body>
</html>