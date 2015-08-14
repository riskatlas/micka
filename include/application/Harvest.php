<?php

class Harvest{

  var $types = array(
    "WMS" => "http://www.opengis.net/wms",
  	"WFS-1.1" => "http://www.opengis.net/wfs",
    "MD_Metadata" => "http://www.isotc211.org/schemas/2005/gmd/",
    "CSW-2.0.2" => "csw/2.0.2"
  );
  var $importer = null;
  var $mdImport = null;

 /**************************************************
 * Harvest constructor
 * 
 * @param debug		debug mode
 ***************************************************/

  function __construct($server, $client, $endDate=null, $loglevel=0, $delete=false, $mailParams=null){
    $this->loglevel  = $loglevel;
    $this->logstr 	 = "";
    $this->serverCSW = $server;
    $this->clientCSW = $client;
    libxml_use_internal_errors(true);
    $this->endDate   = $endDate;
    $this->endDate 	 = str_replace("T", " ", $this->endDate);
    $this->getDeleted = ($delete==1);
    $this->mailParams = $mailParams;
  }

  function log($msg=false,$level=0){
    if(!$this->loglevel || $this->loglevel<$level) return;
  	if(!$msg){
  			$s = "--------------------------------------------------------------------------------";
  	}
  	else if(gettype($msg)=='string'){
  		  $s = $msg . "\n";
  	}
		else {
			 $s = var_export($msg, true);
		}
		$s .= "\n";
		if(defined("HARVEST_LOG")){
        	file_put_contents(HARVEST_LOG, $s, FILE_APPEND);
    	}
		else {
        echo $s;
    }  
  }

 
 /**************************************************
 * runs harvesting - typically once per day etc
 * 
 * @param serviceURL	URL CSW serveru
 ***************************************************/
  function getSourcesToUpdate(){
    //TODO platform specific ??!!!!
    
		if (DB_DRIVER == 'postgre') {
			// TODO dodelat skupiny jako v ORACLE
			if($this->endDate){
				$sql = "SELECT * FROM harvest WHERE h_interval > 0 AND active=1 AND (updated IS NULL OR ('".$this->endDate."' - updated) > (h_interval * interval '1 hour'))";
			}
			else {
				$sql = "SELECT * FROM harvest WHERE h_interval > 0 AND active=1 AND (updated IS NULL OR (now() - updated) > (h_interval * interval '1 hour'))";
			}
		}
  	elseif (DB_DRIVER == 'oci8' || DB_DRIVER == 'oracle') {
			if($this->endDate){
				$sql = "SELECT harvest.*,md1.dataupd, md0.view_group FROM harvest
				LEFT JOIN md md0 on trim(harvest.name) = trim(md0.uuid) 
				LEFT JOIN (SELECT trim(server_name) sn, max(last_update_date) dataupd from md where create_user='harvest' group by server_name) md1 ON md1.sn = trim(harvest.name)		
				WHERE h_interval > 0 AND active=1 AND (updated IS NULL OR ((TO_TIMESTAMP('" . $this->endDate . "', 'YYYY-MM-DD')) - updated) >=  NUMTODSINTERVAL(h_interval, 'HOUR'))";
			}
			else {
				$sql = "SELECT harvest.*,md1.dataupd, md0.view_group FROM harvest
				LEFT JOIN md md0 on trim(harvest.name) = trim(md0.uuid) 
				LEFT JOIN (SELECT trim(server_name) sn, max(last_update_date) dataupd from md where create_user='harvest' group by server_name) md1 ON md1.sn = trim(harvest.name)		
				WHERE h_interval > 0 AND active=1 AND (updated IS NULL OR ((updated + NUMTODSINTERVAL(h_interval-2,'HOUR'))  <=  SYSTIMESTAMP))";
			}
  	}
    $rs = setUpperColsName(dibi::query($sql));
    $sources =  $rs->fetchAll();
    $output=array();
    for($i=0; $i<count($sources); $i++){
    	while(list($key,$val)=each($sources[$i])){
    		$output[$i][strtolower($key)] = trim($val);
    	}
    	$pom = explode(" ", $output[$i]['updated']);
    	$output[$i]['updated'] = $pom[0]; 
    }
    return $output; 
  }

  
 /**************************************************
 * reads remote directory (apache list)
 * 
 * @param serviceURL	URL CSW serveru
 ***************************************************/
  function listDir($url, $ext=""){
    $len = strlen($ext);
    $s = file_get_contents($url);
    if(!$s) return false;
    preg_match_all("/(a href\=\")([^\?\"]*)(\")/i", $s, $matches);
    $list= array(); 
    foreach($matches as $f){
      if(substr($f,-$len)==$ext) $list[] = $f;
    }
    return $list;
  }
    
 /**************************************************
 * runs harvesting - for periodical batch processing
 * 
 ***************************************************/
  function runAll(){
    $sources = $this->getSourcesToUpdate($endDate);
    $this->log("********************** Harvest: ". date("Y-m-d H:i:s") . " ****************************");
    $this->log($sources,2);
    $this->log(null,2);
    $results = Array();
    for($i=0;$i<count($sources);$i++){
    	$sources[$i]['results'] = $this->runResource($sources[$i]);
    }
    return $sources;
  }

  /**************************************************
 * harvests one source 
 * 
 * @param resource  associative array of resource parameters
 ***************************************************/
  function runResource($resource){
    $this->log("RESOURCE: ".$resource['name']. " ". $resource['source']);
	$this->serverCSW->params['DEBUG']=$debug;
	$this->importer = new MetadataImport();
    $this->mdImport = new MdImport();
    $this->mdImport->setTableMode('md');
  	$this->mdImport->stop_error = false; // pokud dojde k chyb� p�i importu pokra�uje 
    $type = trim($resource['type']);
    switch($type){
        case $this->types['WMS']: 
            $result = $this->runOWS($resource['source'], 'wms'); 
            break;
        case $this->types['WFS-1.1']: 
            $result = $this->runOWS($resource['source'], 'wfs'); 
            break;
        case $this->types['CSW-2.0.2']: 
            $result = $this->runCSW($resource); //docasne
            //$result = array();
            if($this->getDeleted){ 
            	 $result = array_merge($result, $this->compare($resource));
            }
            break;
        case $this->types['MD_Metadata']: 
            //if(substr($resource['source'],-4)=='.xml') 
            $result = $this->runFiles($resource);
            //else $result = $this->runCSW($resource); 
            break;
        default: 
            $result = array('error'=>array(5, "ResourceType", "$type is not valid resource type"));   
    }
    $this->log();
    // pokud neni chyba, nastavi se, ze byl updatovany
    if(!$result['error']) $this->setUpdated($resource['name'], json_encode($result));
    //TODO zatim mail za kazdy zdroj
    // zasle jen tehdy, pokud byly zm�ny
    if(count($result)){
        $this->processHandler($resource, $result);
    }    
    return $result;
  }
  
  private function runOWS($url, $template){
  	@$s = file_get_contents($url);
    if(!$s) return array('error'=>array(3, 'Source', "Resource on ".htmlentities($url)." not found found"));      
    $csw = $this->serverCSW;
  	$csw->xml->loadXML($s);
    $md = $this->importer->xml2array($csw->xml, PHPPRG_DIR."/../xsl/$template.xsl");
    $result = $this->mdImport->dataToMd($md,'update');
    return $result; 
  }
  
	/**************************************************
	* runs harvesting from CSW  - for imports etc...
	* 
	* @param resource  associate array of resource parameters
	***************************************************/
  	private function runCSW($resource){
      	$startPosition = 1;
      	$result = array();
      	$filter = trim($resource['filter']);
    	do {
    	  	$this->clientCSW->setParams("debug=0|ElementSetName=full|typeNames=gmd:MD_Metadata|maxRecords=25|startPosition=$startPosition");
      		$fromDate = $resource['updated'];
      		if(!$fromDate) $fromDate = "1950-01-01";
      		$fromDate = explode(" ",$fromDate);
      		$fromDate = $fromDate[0];
      		$query = "modified>=".$fromDate;
          	if($filter){ 
          		$query .= " AND ".$filter;
          	}
      		if($this->endDate){
      			$query .= " AND modified < '".$this->endDate."'";
      		}
    	    $this->clientCSW->prepareRequest($query);
          	$tryCount = 0;
          	// 3 pokusy, pokud zustava chyba, vraci ji
          	do {			    
            	$s = $this->clientCSW->runRequest($resource['source'], "harvest", "", "", "");
          		$this->log();
           		$this->log('QUERY: ' . $query,1);
                $this->log('Source XML:',2);
           		$this->log($s,2); 
          		libxml_clear_errors();
          		$this->serverCSW->xml->loadXML($s); 
          		$err = libxml_get_errors();
          		if(count($err)) {
            		$this->log('Record saving error:');
            		$this->log($err);
                  	$this->log($s);
            		if($tryCount>2){
                    	return array('error'=>array(3, 'Source', "Invalid source at ".htmlentities($resource['source'])));
                	}            
                	$tryCount++;
                	$this->log("trying to resend request $tryCount ...");
                	sleep(1);
            	}
          	} while (count($err));
      		$res = $this->serverCSW->xml->getElementsByTagNameNS($this->serverCSW->schemas['csw'], "SearchResults"); 
            // kdyz nenasel element
            if($res->length==0){
                $this->log('csw:SearchResults element not found.');
                return array('error'=>array(3, 'csw:SearchResults', "Invalid CSW response ".htmlentities($resource['source'])));         
            }   
      		//$startPosition = intval($res->item(0)->getAttribute("nextRecord"));
      		$numResults = intval($res->item(0)->getAttribute("numberOfRecordsMatched"));
      		$numReturned = intval($res->item(0)->getAttribute("numberOfRecordsReturned"));
       		if(!$numResults) return $result;
            if($numReturned>0) $startPosition += $numReturned;
            if($startPosition > $numResults) $startPosition = 0;
      		$this->serverCSW->params['REQTYPE']="csw:update";
      		$this->serverCSW->params['DEBUG'] = 1;
      		$result1 = $this->serverCSW->update($resource['name'], $resource["edit_group"], $resource["view_group"], 1);
      		$result = array_merge($result, $result1);
     		$this->log("Import result:");
     		$this->log($result1);
  	  } while ($startPosition > 1);
      return $result;  
  }

  /**************************************************
 * runs harvesting from remote files  - for imports etc...
 * 
 * @param resource  associative array of resource parameters
 * @return associative array of update responses 
 ***************************************************/
  private function runFiles($resource){
    $startPosition = 1;
	$this->mdImport->setDataType(1); // nastaven� ve�ejn�ho z�znamu
  	$this->mdImport->server_name = 'file'; // jm�no serveru ze kter�ho se importuje
  	$path = '';
    // more files in remote directory 
    if(substr($resource['source'],-5)=="*.xml"){
      $path = substr($resource['source'],0,-5);
      $files = $this->listDir($resource['source'], '.xml');
      if(!$files) {}// vyhodit chybu
    }
    // one file
    else $files[]=$resource['source'];  
    $xml = new DomDocument;
    $result = array();
    foreach($files as $file){
      @$s = file_get_contents($path.$file, false, null, null, CSW_MAXFILESIZE);
      if(!$s) return array('error'=>array(3, 'Source', "Resource on ".htmlentities($path.$file)." not found found od invalid"));      
      $xml->loadXML($s);
	  //$this->cswTo->params['REQTYPE']="csw:update";
      $md = $this->importer->xml2array($xml, PHPPRG_DIR."/../xsl/iso2micka.xsl");
      //var_dump($md);
      $result += $this->mdImport->dataToMd($md,'update');       
    }    
    return $result;
  }
  
  /**
   * parsuje ISO 8601 duration a vraci dny
  */
  private function parseDuration($duration){
  	$value = '';
    for($i=0;$i<strlen($duration);$i++){
      $ch = substr($duration,$i,1);
      switch($ch){
        case 'P': $value=""; break;
        case 'Y': $y = $value; $value=""; break;
        case 'M': $m = $value; $value=""; break;
        case 'W': $w = $value; $value=""; break;
        case 'D': $d = $value; $value=""; break;
        case 'H': $h = $value; $value=""; break;
        default: $value .= $ch;
      }
    } 
    return ((intval($y)*365.25 + intval($m)*30.51 + intval($w)*7 + intval($d))*24 + intval($h));
  }
  
  
 /**************************************************
 * sets harvest parameters
 * 
 * @param name		Unique name of the source
 * @param source	Source path
 * @param type		Type of the source (URIs listed in CSW specification)
 * @param period	Harvesting Interval (days, values e.g. 5.2 etc..)
 * @params filter 	Additional filter 
 ****************************************************/
  function setParameters($name, $source, $type, $handlers, $period, $filter, $active, $overwrite=true){
  	if(!$name) return array('status'=>'fail', 'error'=>'ID is missing.'); 
  	$exists = false;
  	$result = true;
  	$interval = $this->parseDuration($period); 
  	$arr = array(
  		'name' => $name,
  		'source'  => $source,
  		'type'  => $type,
  		'handlers'  => $handlers,
  		'h_interval'  => $interval,
  		'period' => $period,
  		'filter'  => $filter,
  		'active' => $active
  	);
  	//mazani
    if($interval < 0){
      $sql = "DELETE FROM harvest WHERE name=%s";
      $arr['action'] = 'delete';
      try {
          $rs = dibi::query($sql, $name);
        	$arr['status'] = 'OK';
        	return $arr;
        }
  		catch (DibiException $e) {
        	$arr['status'] = 'fail';
        	$arr['error'] = $e;
        	return $arr;
   		}
    }
    // vložení
    $sql = "SELECT COUNT(*) FROM harvest WHERE name=%s";
    $rs = dibi::fetchSingle($sql, $name);
		if ($rs > 0) {
			if(!$overwrite){
				$arr['status'] = 'fail';
				$arr['error']  = 'duplicate key';
				return $arr;
			}
      		$exists = true;
		}
		try {
	    if($exists){
				dibi::query('UPDATE harvest SET ', $arr, 'WHERE name=%s', $name);
			  //$sql = "UPDATE harvest SET name='$name', source='$source', $type='$type', interval='$interval'";
			}
			else{
				$arr['create_user'] = MICKA_USER;
				dibi::query('INSERT INTO harvest', $arr);
			}
        	$arr['status'] = 'OK';
		}
		catch (DibiException $e) {
			$this->log();
			$this->log($e);
      $arr['status'] = 'fail';
      $arr['error'] = $e;
		}
    return $arr;
  }

/**************************************************
 * sets last update datum in harvest table
 * 
 * @param name		Unique name of the source

 ****************************************************/
  function setUpdated($name, $result){
  	try{
  		if($this->endDate) {
        if (DB_DRIVER == 'oci8' || DB_DRIVER == 'oracle') {
            dibi::query("UPDATE harvest SET updated=TO_TIMESTAMP('".$this->endDate."', 'YYYY-MM-DD'), result=%s WHERE name=%s", $result, $name);
        }
        else {
            dibi::query("UPDATE harvest SET updated='".$this->endDate."', result=%s WHERE name=%s", $result, $name);
        }
			}
  		else {
  		  // ORACLE
				if (DB_DRIVER == 'oci8' || DB_DRIVER == 'oracle') {
					dibi::query("UPDATE harvest SET updated=SYSTIMESTAMP WHERE name=%s", $name);
				}
				// ostatni
				else {
					dibi::query('UPDATE harvest SET updated=now() WHERE name=%s', $name);
				}
			}
  	}
  	catch (DibiException $e) {
  		$this->log();
  		$this->log($e);
  		$result = false;
  	}
    return $result;	
  }
  
 /**************************************************
 * gets harvest parameters
 * 
 * @param name		Unique name of the source, if ommited, all records are returned
 ****************************************************/
  function getParameters($name=null){
	$sql = array();
	$sql[] = 'SELECT * FROM harvest';
    if ($name) array_push($sql, 'WHERE name=%s', $name);
    if(!canAction('*')) {
    	if ($name) array_push($sql, 'AND create_user=%s', MICKA_USER);
    	else array_push($sql, 'WHERE create_user=%s', MICKA_USER);
    }
    array_push($sql, 'ORDER BY name');
    try {
        $rs = dibi::query($sql);
        $rs = _executeSql('select', $sql, array('all'));
        //$result =  $rs->fetchAll();       
        foreach($rs as $row){
            $result[] =  Array(
              "id" => $row['NAME'],
              "source" => $row['SOURCE'],
              "type" => $row['TYPE'],
              "h_interval" => $row['H_INTERVAL'],
              "HarvestInterval" => $row['PERIOD'],
              "handlers" => $row['HANDLERS'],
              "filter" => $row['FILTER'],
              "active" => $row['ACTIVE'],
              "updated" => $row['UPDATED']
          ); 
        }

    }
		catch (DibiException $e) {
		    var_dump($e);
			  $result = false;
		}
    return $result;   
  }

 /**************************************************
 * deletes record
 * 
 * @param name		Unique name of the source, if not found returns false
 ****************************************************/
  function delete($name){
		$result = false;
        if($name) {
			$result = true;
			try {
				dibi::query('DELETE FROM harvest WHERE name=%s; 
				        DELETE FROM md_values WHERE recno IN (SELECT recno FROM md WHERE server_name=%s);
				        DELETE FROM md WHERE server_name=%s;', $name, $name, $name);
			}
			catch (DibiException $e) {
				$result = false;
			}
		}
    return $result;  
  }
  
 /**************************************************
 * lists supported types
 * 
 * @return	list of supported source types
 ****************************************************/
  function getTypes(){
    return $this->types;  
  }
  
  
 /**************************************************
 * process handlers
 * 
 * @return	list of supported source types
 ****************************************************/
  function processHandlers($resources, $results){
  	 for($i=0; $i<count($resources);$i++){
        // omezeni jen na nejake vysledky
        if(count($results[$i])>0){  
            $this->processHandler($resources[$i], $results[$i]);
        }    
  	 }  
  }

  // processes one service handlers
  function processHandler($resource, $result){
      $handlers = explode("|",$resource['handlers']);     
      $message = $this->printResultHTML($resource, $result);
      foreach($handlers as $handler){
      	$handler = explode(":", $handler);
      	switch ($handler[0]){
      		case 'mailto':
      			$rm = $this->mailHTML(
      				$handler[1],
      				"Harvest " . $resource['name'],
      				$message
      			);
      			if(!$rm) echo "MAIL ERROR!";
      			break;
      		case 'ftp':
      			break; 
      		default:
      			break;
      	}
      }  
  }
  
  function mail($to, $subject = '(No subject)', $message = '', $header = '') {
      $header_ = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=UTF-8' . "\r\n";
      return mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header_ . $header);
  }

  function mailHTMLo($to, $subject = '(No subject)', $message = '', $header = '') {
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
  
  function mailHTML($to, $subject = '(No subject)', $message = '') {
    $mail = new Mail;
    $mail->setFrom($this->mailParams['from'])
        ->addTo($to)
        ->setSubject($subject)
        ->setHtmlBody($message)
        ->addReplyTo($this->mailParams['reply']);
    if($this->mailParams['bcc']) $mail->addBcc($this->mailParams['bcc']);
    if($this->mailParams['smtp']) $mailer = new SmtpMailer(array('host' => $this->mailParams['smtp']));
    else  $mailer = new SendmailMailer();
    
    $mailer->send($mail);
  }
  
  function printValidArray($valid){
      $s = "<table>\n";
      foreach($valid as $row){
          if($row['level']=="i") continue;                                
          if($row['level']=="c") $class = "conditional";
          if($row['level']=="n") $class = "informative";
          else $class = "mandatory";
          if(!$row['deepPass']){
            $s .= "<tr><td>($row[code]) </td><td>$row[description]";
            $s .= "<br/>$row[xpath]";
            if(!$row['pass']){
                if($row['err']){
                    $s .= "<div class=\"$class\">$row[err]</div>";
                }
                else {
                    $s .= "<div class=\"$class\">Špatná nebo chybějící hodnota: $row[value]</div>";
                }
            }    
            if($row['tests']) $s .= $this->printValidArray($row['tests'] );
            $s .= "</td></tr>\n";
          }
      }
      $s .= "</table>\n";
      return $s;
  }
    
  function printResultHTML($resource, $result){
    // hlavicka
    $s = "<html><head><style>
    body {background: white; font-family:sans-serif; font-size:12px;}
    table {border-collapse:collapse;}
    .err {font-weight:bold; color:red}
    .ok {font-weight:bold; color:#008000}
    .mandatory {color: red}
    .conditional {color: #997C0E}
    .informative {color: #999}
    td, th {padding: 2px; vertical-align:top; }
    table.result, th.result, td.result {border: solid 1px #E0E0E0;}
	</style></head><body>";
    $s .= "<h1>" . HARVEST_TITLE . "</h1>";
    $s .= '<table class="result" style="background:#FFFFE8;"><tr><th>RESOURCE</th><td>'.trim($resource['name']).'</td></tr>';
    $s .= "<tr><th>ADDRESS</th><td>".trim($resource['source'])."</td></tr>";
    $s .= "<tr><th>TIME</th><td>".date("Y-m-d H:i:s")."</td></tr>\n";
    $s .= "</table><br/><br/><br/>";

    if($result['error']){
    	$s .= '<div style="color:red">'.$result['error'][2].'</div>';
    	return $s;
    }
    
    if(count($result)>0){
    	
        $s .= '<table class="result">';
        $s .= '<tr><th class="result">rec</th><th class="result">Result</th></tr>';
        $i = 0;
        foreach($result as $row){
          $i++;
          $s .= "<tr><td class=\"result\">$i</td><td class=\"result\">";
          //$s .= "<td class=''>$row[uuid]</td>";
          //$s .= "<td>$row[title]</td><td>";
          if($row['ok']==1) {
            $s  .= "<span class=\"ok\">IMPORTED</span> $row[uuid]: <b>$row[title]</b>";
          }   
          else if($row['ok']==2) {
            $s  .= "<span class=\"ok\">DELETED</span> $row[uuid]: <b>$row[title]</b>";
          }  
          else {
            $s.= "<span class=\"err\">ERROR:&nbsp;</span> $row[uuid]: <b>$row[title]</b>";
          }  
          //if($row['report']) $s .=  "<br>" . str_replace(array("['","']"), array("/",""),	$row['report']);
          //$s .=  "<br>" . print_r($row['valid'], true);
          $s .=  "<br/>" . $this->printValidArray($row['valid']);
          $s .= "</td></tr>\n";
        }
        $s .= "</table>";
        $s .= 'Význam barev: <span class="mandatory">povinné</span> 
        <span class="conditional">podmínečně povinné</span>
        <span class="informative">informativní</span>';
        $s .= "</body></html>"; 
    }
    else {
        $s .= "<p>No data to update.</p>";    
    }    
    return $s;
  }
    
  function writeRSS($data){
    if(count($data)==0) return;
  	$s = '<?xml version="1.0" encoding="utf-8"?'.'>'
  	. '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'
  	. '<channel><title>GEOPORTAL Harvest report</title>'
    . '<link>'.HARVEST_RSS_URL.'/harvest.rss</link>'
  	. '<pubDate>'.date("r").'</pubDate>';
  	foreach($data as $resource){
  		//var_dump($resource);
  	    $s .= "<item>
  	    	<title>$resource[source]</title>
  	    	<pubDate>".date("r")."</pubDate>
  	    	<guid isPermaLink=\"false\">$resource[name]:".date("U")."</guid>
  	    	<description><![CDATA[";
  	    if($resource['results']['error']){
  	    	$s .= $resource['results']['error'][1].": <span style=\"color:#FF0000;\">".$resource['results']['error'][2]."</span>";
  	    }
  	    else{
	  	    $s .= "<ul>";
	  	    foreach($resource['results'] as $result){
	            $s .= "<li><a href=\"http://geoportal.gov.cz/php/micka/csw/index.php?service=CSW&amp;version=2.0.2&amp;request=GetRecordById&amp;language=cze&amp;id=".urlencode($result[uuid])."&amp;format=text/html\">$result[uuid]: $result[title] </a> ";
	            if($result['ok']==1) $s.= "<span style=\"color:#008000\"><b>OK</b></span> ";
	            else if($result['ok']==2) $s.= "<span style=\"color:#008000\"><b>DELETED</b></span> ";
	            else $s.= "<span style=\"color:#FF0000\"><b>ERROR</b></span> ";
	            if($result['report']){
	            	$s .= "<pre>".$result['report']."</pre>";
	            }
	            $s .= "</li>"; 
	        }
	        $s.="</ul>";
	  	}
        $s .= "]]></description></item>";
  	}
  	$s .= '</channel></rss>';
  	file_put_contents(HARVEST_RSS_DIR."/harvest.rss", $s);
  }


  // porovnavani smazanych zaznamu - experiment
  function compare($resource){
    $startPosition = 1;
    $result = array();
    $this->log("SEARCHING DELETED");
    $filter = trim($resource['filter']);
  	do {
	  	$this->clientCSW->setParams("debug=0|ElementSetName=brief|typeNames=gmd:MD_Metadata|maxRecords=25|startPosition=$startPosition");
  		$fromDate = "1900-01-01";
  		$query = "modified >= '".$fromDate."'";
  	    if($filter){ 
        	$query .= " AND ".$filter;
        }
  		$this->clientCSW->prepareRequest($query);
  		
  		do {
  			$s = $this->clientCSW->runRequest($resource['source'], "harvest", "", "", "");
  			$this->log();
  			$this->log('QUERY TO DELETE: ' . $query,1);
  			$this->log('Source XML:',2);
  			$this->log($s,2);
  			libxml_clear_errors();
  			$this->serverCSW->xml->loadXML($s);
  			$err = libxml_get_errors();
  			if(count($err)) {
  				$this->log('Record saving error:');
  				$this->log($err);
  				$this->log($s);
  				if($tryCount>2){
  					return array('error'=>array(3, 'Source', "Invalid source at ".htmlentities($resource['source'])));
  				}
  				$tryCount++;
  				$this->log("trying to resend request $tryCount ...");
  				sleep(1);
  			}
  		} while (count($err));
  		
  		//$s = $this->clientCSW->runRequest($resource['source'], "harvest", "", "", "");
  		//$this->serverCSW->xml->loadXML($s);
  		$ids = $this->serverCSW->xml->getElementsByTagNameNS('http://www.isotc211.org/2005/gmd','fileIdentifier');
  		foreach ($ids as $id) {
    		//echo "nod='". trim($id->nodeValue), "'\n";
    		$result[] = trim($id->nodeValue);
		}
  		$res = $this->serverCSW->xml->getElementsByTagNameNS($this->serverCSW->schemas['csw'], "SearchResults");
  		//$startPosition = intval($res->item(0)->getAttribute("nextRecord"));
  		//$numberOfRecordsMatched = intval($res->item(0)->getAttribute("numberOfRecordsMatched"));
        //if($startPosition > $numberOfRecordsMatched) $startPosition = 0;
        if($res->length==0){
            $this->log('csw:SearchResults element not found.');
            return array('error'=>array(3, 'csw:SearchResults', "Invalid CSW response ".htmlentities($resource['source'])));         
        }   

      	$numResults = intval($res->item(0)->getAttribute("numberOfRecordsMatched"));
      	$numReturned = intval($res->item(0)->getAttribute("numberOfRecordsReturned"));
       	if(!$numResults) return $result;
        if($numReturned>0) $startPosition += $numReturned;
        if($startPosition > $numResults) $startPosition = 0;

  		if($this->loglevel>1){
  			$this->log();
  			$this->log('Zdrojove XML pro smazani:');
  			$this->log($s); 			
  		}
  		libxml_clear_errors();
		//if(!$startPosition) exit('Nenalezen atribut: nextRecord');
	  } while ($startPosition > 1);
	  $this->log("vet=".count($result));
	  // --- dotaz do databaze --- 
	  $sql = "SELECT uuid, title FROM md WHERE create_user='harvest' AND server_name=%s";
	  $ids=array();
	  try {
			$rs = setUpperColsName(dibi::query($sql, $resource['name']));
			$records =  $rs->fetchAll();
			foreach($records as $record){
				$uuid = trim($record['UUID']);
	  			$ids[] = $uuid;
	  			$titles[$uuid] = trim($record['TITLE']); 
			}
	  }
	  catch (DibiException $e) {
		  return array('error'=>array(6, "Database", "$sql is not valid SQL request"));
	  }
	  
	  // --- porovnani ---
	  $diff = array_diff($result, $ids);
	  $diff2 = implode("\n", $diff);
      $this->log("Chybejici vety ". count($diff) ." :");
      $this->log($diff2);

	  $diff = array_diff($ids,$result);
      if(count($diff)==0) {
        return array();
      }  
	  $diff1 = "'".implode("','",$diff)."'"; 
 	  //echo "vet=".count($result);
 	  //echo "db=".count($ids);
      $this->log($result,2);
      $this->log($diff,2);
      //var_dump($diff);
 	  //exit;
 	  //--- vymazani ---
	  try{
 	      $sql = "DELETE FROM md_values WHERE recno IN (SELECT recno FROM md WHERE create_user='harvest' AND uuid IN ($diff1))";
		  _executeSql('delete', array($sql), array('all'));
          $sql = "DELETE FROM md WHERE create_user = 'harvest' AND uuid IN ($diff1)";
          _executeSql('delete', array($sql), array('all'));
          //$rs = setUpperColsName(dibi::query($sql));
	 	  $result = array();
	 	  foreach($diff as $uuid){
	 	  	$result[] = array(
	 	  		"uuid" => $uuid,
	 	  		"title" => $titles[$uuid],
	 	  		"ok" => 2
	 	  	); 
	 	  }
          $this->log('DELETED');
          $this->log($result);
		  return $result;  

	  }
	  catch (DibiException $e){
	  	    return array('error'=>array(6, "Database", "$sql is not valid SQL request"));	 	  
	  }
  }

} // class end

