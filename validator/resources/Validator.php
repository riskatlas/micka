<?php
function isEmail($email){
	//return filter_var($email, FILTER_VALIDATE_EMAIL);
	if (preg_match('~^[-a-z0-9!#$%&\'*+/=?^_`{|}\~]+(\.[-a-z0-9!#$%&\'*+/=?^_`{|}\~]+)*@([a-z0-9]([-a-z0-9]{0,61}[a-z0-9])?\.)+[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])$~i', $email)) {
		return "1";
	}
	else {
		return "";
	}
	/*
	// verze rozšířená o kontrolu existenci domény
	//
	// preg pattern for user name
	// http://tools.ietf.org/html/rfc2822#section-3.2.4
	$atext = "[a-z0-9\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]";
	$atom = "$atext+(\.$atext+)*";

	// preg pattern for domain
	// http://tools.ietf.org/html/rfc1034#section-3.5
	$dtext = "[a-z0-9]";
	$dpart = "$dtext+(-$dtext+)*";
	$domain = "$dpart+(\.$dpart+)+";

	if(preg_match("/^$atom@$domain$/i", $addres)) {
			list($username, $host)=split('@', $addres);
			if(checkdnsrr($host,'MX')) {
				return "1";
			}
	}
	return "";
	*/
}

function isRunning($url, $type, $d=""){
	// TODO jednoduche - vylepsit
	$type = strtoupper($type);
	@$s = file_get_contents($url);
	//file_put_contents('/var/www/projects/tmp/'.time().'.xml',$s);
	$result = false;
	if(substr($type,0,2)=='WM' && strpos($s, "Capability>")!==false) $result = true;
	else if($type=='CSW' && strpos($s, "ServiceType>")!==false) $result = true;
	else if($type=='WFS' && strpos($s, "FeatureTypeList>")!==false) $result = true;
	else if($type=='WCTS' && strpos($s, "SourceCRS>")!==false) $result = true;
	else if($type=='GMD' && strpos($s, "MD_Metadata>")!==false) $result = true;
	if($d && $result){
	    $result = new DOMDocument();
	    $result->loadXML($s);	    
	}
	return $result;
	
}


function json2array($json) {  
  $json = substr($json, strpos($json,'{')+1, strlen($json)); 
  $json = substr($json, 0, strrpos($json,'}')); 
  $json = preg_replace('/(^|,)([\\s\\t]*)([^:]*) (([\\s\\t]*)):(([\\s\\t]*))/s', '$1"$3"$4:', trim($json)); 
  return $json;
  return json_decode('{'.$json.'}', true); 
}  

function isGemet($keyword, $lang){
    $lcodes = array(
            "cze"=>"cs",
            "dan"=>"da",
            "eng"=>"en",
            "fin"=>"fi",
            "fre"=>"fr",
            "ger"=>"de",
            "hun"=>"hu",
            "ita"=>"it",
            "lav"=>"lv",
            "nor"=>"no",
            "pol"=>"pl",
            "por"=>"pt",
            "slo"=>"sk",
            "slv"=>"sl",
            "spa"=>"es",
            "swe"=>"sv"          
    );
	$s = file_get_contents("http://www.eionet.europa.eu/gemet/getConceptsMatchingRegexByThesaurus?thesaurus_uri=http://inspire.ec.europa.eu/theme/&language=".$lcodes[$lang]."&regex=".urlencode($keyword));
	if(trim($s)!='[]') {
	    return $keyword;
	}
	return "";
	/*$s = str_replace(array('\r\n', '"', 'string'), array(' ','\"', 'name'), $s);
	eval('$s="{results:'.$s.'}";');
	$s = json2array($s);
	return $s;
	return var_export($s,true);*/
}

class Validator{

  var $xp  = null;
  var $xml = null;
  var $xsl = null;

  function __construct($type="gmd", $lang='cze'){
    if(!in_array($type, array("gmd", "gmd_inspire", "wms", "csw"))) $type="gmd";
    $this->xml = new DomDocument;
    $this->xsl = new DomDocument;
    $this->xsl->load(dirname(__FILE__).'/'.$type.'.xsl');
    $this->xp = new XsltProcessor();
    $this->xp->registerPHPFunctions();
    $this->xp->importStyleSheet($this->xsl);
    $this->xp->setParameter("", "LANG", $lang);
    $this->msg = simplexml_load_file(dirname(__FILE__).'/labels-'.$lang.'.xml');
    $this->result = null;
    $this->XMLResult = null;
    $this->pass = 0;
    $this->fail = 0;
    $this->warn = 0;
    $this->notice = 0;
  }  

  
  
	function __destruct(){
		unset($this->xml); $this->xml=null; 
		unset($this->xsl); $this->xsl=null; 
		unset($this->xp);  $this->xp=null; 
	}

	function run($xmlString){
		$valid = @$this->xml->loadXML($xmlString);
		if($valid) {
			$this->result = $this->xp->transformToXML($this->xml);      
		}
		else{
			$this->result = "<errList><error>".(string)$this->msg->msg->notLoad."</error></errList>";
		}
		return $this->result; 
	}
  
	function asXML(){
		return $this->result;
	} 
	
	private function ar($xml){
		$tests = array();
		if($xml->error) {
			$tests[] = array(
				'code' => "file" ,
				"value" => (string) $xml->error,
				'err' => "STOP"
			);  
		}
		$passAll = true;
 		foreach ($xml->test as $t){
		  	$test = array();
			$test['code'] = (string) $t['code'];
			$test['level'] = (string) $t['level'];
			$test['description'] = (string) $t->description;
			$test['xpath'] = (string) $t->xpath;
			$test['value'] = trim((string) $t->value);
			$test['pass'] = (boolean) $t->pass;
			$test['deepPass'] = $test['pass'];
			$test['err'] = trim((string) $t->err);
			if($t->test){
				$test['tests'] = $this->ar($t);
				// hledani neproslych podtestu
				if($test['deepPass']){
					foreach($test['tests'] as $t){
						if(!$t['deepPass']){
							$test['deepPass'] = false;
							break;
						}
					}
				}
			}
			$tests[] = $test;
			if( $test['level'] != 'i'){
				if($test['pass']) $this->pass++; 
				else {
	        		if($test['level']=='c') $this->warn++;
	        		elseif($test['level']=='n') $this->notice++;
	        		else $this->fail++;          
	      		}
			}
  		}
  		return $tests;
  	}
  
    private function createHTML($result){
  	    $output = "";
		foreach($result as $row){
			// vyhodi informativni vety
			if($row['level']=='i') continue;
			if($row['pass']){ 
				$class = "pass"; 
			}
			else {
				if($row['level']=='c'){ 
					$class="warning";
					$warnings++;
				}	 
				else if($row['level']=='n'){
				    $class="notice";
				    $notices++;				    
				}
				else {
					$class = "fail";
					$fails++;
				}
				if(!$row['err']) $row['err'] = (string)$this->msg->msg->mv;
				if($row['xpath']) $row['err'] .= " (" . $row['xpath'] . ")";
			}
			$output .= "<div class='row'><div class='hd ".$class."'>(" . $row['code'] . ")</div>";
			$output .= "<div class='msgs'><div class='title' id='VAL-".$row['code']."'>" . $row['description'] . "</div>";
			if($row['value']) $output .= "<div class='value'>" . $row['value'] . "</div>";
			if($row['err']) $output .= "<div class='msg-".$class."'>" . $row['err'] . "</div>";
			$output .= "</div></div>";
			if(isset($row['tests']) && $row['tests']) {
				$output .= "<div class='row' style='margin-left:20px;'>"; 
				$output .= $this->createHTML($row['tests']);
				$output .= "</div>";
			}
		}
		return $output;
	} 	 
  	 	
  	function asHTML($short=false){
  		$result = $this->asArray($short);
		$output = '<div id="owsValidator"><h2><a class="go-back" style="float:right;" href="javascript:history.go(-1);" title="'.(string)$this->msg->msg->back.'"></a>'.$this->title.'</h2>';
  		if($short && $this->fail==0 && $this->warn==0) $output .= '<div class="msg-ok">'.(string) $this->msg->msg->ok.'</div>';
		$output .= $this->createHTML($result);
		$output .= "<div style='clear:both; border-bottom:1px solid #909090; margin: 10px 0px 8px 0px;'></div>
		  <div class='row valid-legend' style='height:16px;'>
		  	<div style='float:right'>".(string)$this->msg->msg->version. 
		  	": ".$this->version."</div>
		  	<span class='pass'>".(string)$this->msg->msg->pass. 
		  	": <b>".$this->pass."</b> </span> 
		  	<span class='fail'>".(string)$this->msg->msg->fail.
		  	": <b>".$this->fail."</b> </span> 
		  	<span class='warning'> ".(string)$this->msg->msg->warning.
		  	": <b>".$this->warn."</b> </span>
		  	<span class='notice'> ".(string)$this->msg->msg->notice.
		  	": <b>".$this->notice."</b> </span>
		  	</div></div>";  		
		return $output;
  	}

  	function asHTMLSmall($short=false, $showTitle=true){
  		$result = $this->asArray($short);
  		$output = '<div id="owsValidator">';
  		if($showTitle) $output .= '<h2>'.$this->title.'</h2>';
  		if($short && $this->fail==0 && $this->warn==0) $output .= '<div class="msg-ok">'.(string) $this->msg->msg->ok.'</div>';
  		$output .= $this->createHTML($result) . "</div>";	
		return $output;
  	}

  	function asArray($short=false){
  		try{
  			@$xml = new SimpleXMLElement($this->result);
  			$this->title = $xml['title'];
  			$this->version = $xml['version'];
  			$tests = $this->ar($xml);
  		}
  		catch (Exception $e){
			$tests[] = array(
				'code' => "XML",
				"value" => "XML formát dat je nekompatibilní se schématem.",
				'err' => "STOP"
			);   				
  		}
  		if($short){
  			$t1 = array();
  			foreach($tests as $test){
  				if(!$test['deepPass']) $t1[] = $test;
  			}
  			return $t1;
  		}
   		return $tests;
  	}
  	
    function getPass(){
        $result = $this->asArray();
        $primary = 0;
        foreach($result as $row){
        	if($row['code']=='primary'){
        		if($row['pass']) $primary=1;
        		break;
        	}
        }
        return array(
            "pass" => $this->pass,
            "fail" => $this->fail,
            "warn" => $this->warn,
            "notice" => $this->notice,
            "primary" => $primary
        );
    }
    
	function asJSON(){
		return json_encode($this->asArray());
	}
  
} // class
