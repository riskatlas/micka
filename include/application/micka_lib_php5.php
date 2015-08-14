<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * PHP5Lib for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20140819
 */

//==============================================================================
//
//==============================================================================

define("BACKURL", "<p> <a href=\"javascript:history.back();\">&lt;&lt; back</a> </p>");

class MetadataImport{

  	var $xsl_files = Array(
		"esri" => "esri2micka.xsl",
		"isvs" => "midas2micka.xsl",
		"native" => "native2micka.xsl",
		"iso" => "iso2micka.xsl",
		"wmc" => "wmc.xsl"
	);
  	
	var $xml = null;
	var $kwList = array();
	var $debug = false;
	private $table_mode = 'md'; // používat tabulku MD nebo TMP

  // konstruktor
  function __construct($debug=false){
    if(!extension_loaded("xsl")){
      if(substr(PHP_OS,0,3)=="WIN") dl("php_xsl.dll");
      else dl("php_xsl.so");
    }
    $this->debug = $debug;
  }

	/**
	 * Uložení do dat nebo do tmp tabulky
	 *
	 * @param string $mode 'md'|'tmp'
	 */
	public function setTableMode($mode) {
		$this->table_mode = $mode == 'md' ? 'md' : 'tmp';
	}

  private function extractImage($xml, $imgFileName){
    $thumb = $xml->getElementsByTagName('Thumbnail');
    $obr = $thumb->item(0);
    if(!$obr) return false;
    $png = $obr->getElementsByTagName('Data')->item(0)->nodeValue;
    file_put_contents($imgFileName, base64_decode($png));
    return true;
  }

  private function recurse($array, $array1){
      foreach ($array1 as $key => $value){
        // create new key in $array, if it is empty or not an array
        if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key]))){
          $array[$key] = array();
        }

        // overwrite the value in the base array
        if (is_array($value)){
          $value = $this->recurse($array[$key], $value);
        }
        $array[$key] = $value;
      }
      return $array;
  }
  
  private function array_replace_recursive($array, $array1){
    // handle the arguments, merge one by one
    $args = func_get_args();
    $array = $args[0];
    if (!is_array($array)){
      return $array;
    }
    for ($i = 1; $i < count($args); $i++){
      if (is_array($args[$i])){
        $array = $this->recurse($array, $args[$i]);
      }
    }
    return $array;
  }
  
  private function writeNode($path, $node, $idx){
  	$s = "";
  	$locales = Array(
  	  "#locale-en" => "eng",
  	  "#locale-fr" => "fre",
  	  "#locale-de" => "ger",
  	  "#locale-it" => "ita",
  	  "#locale-lv" => "lav",
  	  "#locale-eng" => "eng",
  	  "#locale-cze" => "cze",
  	  "locale-eng" => "eng", //chyba v Micce
  	  "locale_eng" => "eng", //chyba v Micce
  	  "locale-cze" => "cze", //chyba v Micce
  	  "locale_cze" => "cze", //chyba v Micce
  	  "#locale_eng" => "eng", //chyba v Micce
  	  "#locale_cze" => "cze" //chyba v Micce
  	  );
  	if($this->langCodes) $locales = $this->langCodes;
  	//atributy
    if(($node->nodeType==XML_ELEMENT_NODE)&&($node->hasAttribute('xlink:href'))){
  	  	$s .= $path."['".$node->nodeName."'][$idx]['href'][0]['@']='".$node->getAttribute('xlink:href')."';\n";
  	}
    if(($node->nodeType==XML_ELEMENT_NODE)&&($node->hasAttribute('xlink:title'))){
  	  	$s .= $path."['".$node->nodeName."'][$idx]['title'][0]['@']='".$node->getAttribute('xlink:title')."';\n";
  	}
  	if(($node->nodeType==XML_ELEMENT_NODE)&&($node->hasAttribute('uuidref'))){
  	  	$s .= $path."['".$node->nodeName."'][$idx]['uuidref'][0]['@']='".$node->getAttribute('uuidref')."';\n";    
    }
    if(($node->nodeType==XML_ELEMENT_NODE)&&($node->hasAttribute('src'))){
  	  	$s .= $path."['".$node->nodeName."'][$idx]['@']='".$node->getAttribute('src')."';\n";
  	}
  	
    // omezení na FC
    if($node->nodeType==XML_ELEMENT_NODE && $node->hasAttribute('id') && $node->nodeName=='featureCatalogue'){
    	$s .= $path."['".$node->nodeName."'][$idx]['id'][0]['@']='".trim($node->getAttribute('id'))."';\n";
    }
    
    // existuje-li codeListValue, uz se dal nevyhodnocuje
    if(($node->nodeType==XML_ELEMENT_NODE)&&($node->hasAttribute('codeListValue'))){
    	$s .= $path."['".$node->nodeName."'][0]['@']='".addslashes(trim($node->getAttribute('codeListValue')))."'; \n";
    } 
    // locales - taky nejde dal
  	else if(strpos($node->nodeName, 'textGroup')!==false){
  		foreach($node->childNodes as $ch){  
	  		if($ch->nodeType == XML_ELEMENT_NODE && $ch->hasAttribute('locale')){
	  			$locale = $ch->getAttribute('locale');
	  			$path .= "['@".$locales[$locale]."']";
				  $s = $path."='".addslashes(trim($ch->nodeValue))."';\n";
	  			break;
	  		}
  		}	
  	}
    else if($node->hasChildNodes()){
  	  //atributy TODO - zobecnit
  	  if($node->hasAttribute('uom') && trim($node->getAttribute('uom'))!=""){
  	  	$s .= $path."['".$node->nodeName."'][$idx]['uom'][0]['@']='".$node->getAttribute('uom')."';\n";
  	  }
  	  if($node->hasAttribute('xlink:href')){
  	  	$s .= $path."['".$node->nodeName."'][$idx]['href'][0]['@']='".$node->getAttribute('xlink:href')."';\n";
  	  }
  	  if($node->hasAttribute('uuidref')){
  	  	$s .= $path."['".$node->nodeName."'][$idx]['uuidref'][0]['@']='".$node->getAttribute('uuidref')."';\n";
  	  }
  	  $nodes = $node->childNodes;
      $lastNode= "";
      $j = 0;
      for($i=0;$i<$nodes->length;$i++){
        if($nodes->item($i)->nodeType==XML_ELEMENT_NODE){
      	  if($nodes->item($i)->nodeName==$lastNode)$j++;
      	  else $j=0;
      	  $lastNode=$nodes->item($i)->nodeName;
      	}  
      	if(!$path) $s .= $this->writeNode("\$md", $nodes->item($i), $j);
        else $s .= $this->writeNode($path."['".$node->nodeName."'][$idx]", $nodes->item($i), $j);
      }
    } 
    // konec vetve - prazdny element - odmazava z databaze tam kde byly prazdne hodnoty
    else if($node->nodeType!=XML_TEXT_NODE){
        if($node->nodeType!=XML_COMMENT_NODE) $s .= $path."['@']='';\n";
    }
    // konec vetve - text
    else if($node->nodeType==XML_TEXT_NODE && ($node->nodeValue==" " || trim($node->nodeValue))){
      if($node->parentNode->hasAttribute('locale')){ 
        $locale = $node->parentNode->getAttribute('locale');  
        $path .= "['@".$locales[$locale]."']"; 
      }
      // pro native
      else if($node->parentNode->hasAttribute('lang')){
        $path .= "['@".$node->parentNode->getAttribute('lang')."']";
      }  
      else $path .= "['@']";  
      $s = $path."='".addslashes(trim($node->nodeValue))."';\n";
    }
    // prazdne XML elementy
    return $s;
  }
  
  function xml2array($xml, $template){
    $xp  = new XsltProcessor();
    $xsl = new DomDocument;
    $xsl->load($template);
    $xp->importStyleSheet($xsl);
    $dom = $xp->transformToDoc($xml);
    //--- ladeni ---
    //header('Content-type: application/xml');
    //echo $dom->saveXML(); exit;
    // ---
  	$this->xml = $xml;
  	//--- vyreseni locales ---
  	$locales = $this->xml->getElementsByTagNameNS("http://www.isotc211.org/2005/gmd", "PT_Locale");

  	foreach($locales as $locale){
  		if($locale->hasAttributes()){
  			$langCode = $locale->getElementsByTagNameNS("http://www.isotc211.org/2005/gmd", "LanguageCode");
  			$this->langCodes['#'.$locale->getAttribute('id')] = $langCode->item(0)->getAttribute('codeListValue');
  			$this->langCodes[$locale->getAttribute('id')] = $langCode->item(0)->getAttribute('codeListValue'); // DOCASNE kvuli ruznym chybam ve starych XML
  		}	
  	}
  	if($dom->documentElement){
  	    $data = $this->writeNode("", $dom->documentElement, 0);
  	}    
    if(substr($data,0,3)!='$md') return array(); // pokud jsou prazdne
    
    $data = str_replace(
      array("['language'][0]['gco:CharacterString']", "['MD_Identifier']", "ns1:" ) , 
      array("['language'][0]['LanguageCode']", "['RS_Identifier']", "gco:" ), 
      $data); //kvuli portalu INSPIRE

    // quick and dirty patch for distance
    $data = str_replace(
       Array("['gco:Distance'][0]['uom']", "['gco:Distance']"),
       Array("['uom'][0]['uomName']", "['value']"), 
       $data
    );
    // DEBUG
    //echo "<pre>$data</pre>"; exit;  
      
    $data = str_replace(array("gmd:","gmi:"), "", $data); //FIXME udelat pres sablony
    //$data = str_replace("csw:", "", $data); //FIXME udelat pres sablony
    $data = str_replace("'false'", "0", $data);
    $data = str_replace("['language'][0]['gco:CharacterString']" , "['language'][0]['LanguageCode']", $data); //kvuli portalu INSPIRE
    $elim = Array("'gco:CharacterString'", "'gco:Date'", "'gco:DateTime'", "'gco:Decimal'", "'gco:Integer'", "'gco:Boolean'",    
     "'gco:LocalName'", "'URL'", "'gco:Real'", "'gco:Record'", "'gco:RecordType'", "'LocalisedCharacterString'", "gml:", "srv:",  "gco:", //"'gmx:Anchor'",
    "['PT_FreeText'][0]", "[][0]", "'DCPList'", "['gts:TM_PeriodDuration'][0]", "['Polygon'][0]['exterior'][0]['LinearRing'][0]['posList'][0]",
            "'gmx:MimeFileType'");
    $data= str_replace($elim , "", $data);
    $data= str_replace("['serviceType'][0]" , "['serviceType'][0]['LocalName'][0]", $data);  
    $data= str_replace(
      array(	
        "['begin'][0]['TimeInstant'][0]['timePosition'][0]", 
        "['end'][0]['TimeInstant'][0]['timePosition'][0]",
      	"['MD_Identifier']",
      	"'false'", "'true'", // predpokladam, ze jde o boolean
      	"MI_Metadata"
      ),
      array(
        "['beginPosition'][0]", 
        "['endPosition'][0]",
      	"['RS_Identifier']",
      	"0", "1",
      	"MD_Metadata"
      ), 
      $data
    );    
    $data= str_replace("['RS_Identifier'][0]['code'][0]['gmx:Anchor'][0]['href'][0]" , "['RS_Identifier'][0]['code'][0]", $data);  
    
    //*** pro DC
    $data = str_replace(
      	Array("csw:Record","dc:","dct:abstract", "[][0]"),
      	Array("metadata","","description",""), 
      	$data
    );
    
    /*if($this->debug)  echo "<pre>". $data . "</pre>"; */
    
    //---------------------------------------
  	if (MICKA_CHARSET != 'UTF-8') {
  	  $data = iconv('UTF-8', MICKA_CHARSET . '//TRANSLIT', $data);
  	}
  	//echo "data=".$data;
    eval($data);
    // odstraneni Locale a dateTime
    for($i=0; $i<count($md['MD_Metadata']); $i++){
    	unset($md['MD_Metadata'][$i]['locale']);
    	if(isset($md['MD_Metadata'][$i]['dateStamp']['@']) && strpos($md['MD_Metadata'][$i]['dateStamp']['@'], 'T')){
            $pom = explode('T',$md['MD_Metadata'][$i]['dateStamp']['@']); // FIXME quick hack
            $md['MD_Metadata'][$i]['dateStamp']['@'] = $pom[0]; 
        }

        // zpracovani polygonu
        for($j=0; $j<count($md['MD_Metadata'][$i]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement']); $j++){
      	    if($md['MD_Metadata'][$i]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][$j]['EX_BoundingPolygon'][0]['polygon'][0]['@']){
      		    $geom = explode(" ",$md['MD_Metadata'][$i]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][$j]['EX_BoundingPolygon'][0]['polygon'][0]['@']);
      		    $result="";
      		    for($k=0; $k<count($geom); $k=$k+2){
      			    if($result) $result .=",";
      			    $result .= $geom[$k]." ".$geom[$k+1];
      		    }
      		    $md['MD_Metadata'][$i]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][$j]['EX_BoundingPolygon'][0]['polygon'][0]['@'] = "MULTIPOLYGON(((".$result.")))";
      	    }
        }
        
            // doplnění překladu INSPIRE
        // --- multiligvalni klic. slova
        $lang = $md['MD_Metadata'][$i]["language"][0]["LanguageCode"][0]['@'];
        if(!$lang) $lang='eng';
        if($md['MD_Metadata'][$i]['identificationInfo'][0]['SV_ServiceIdentification']){
            $this->multiKeywords($md['MD_Metadata'][$i]['identificationInfo'][0]['SV_ServiceIdentification'][0]['descriptiveKeywords'], $lang);
        }
        else {
            $this->multiKeywords($md['MD_Metadata'][$i]['identificationInfo'][0]['MD_DataIdentification'][0]['descriptiveKeywords'], $lang);
        }
                
    }
    //var_dump($md);
    return $md;
  }

	/**
 	* Nacteni klic slov z thesauru XML 
 	*  
 	* @param string - jazyk, ze ktereho se preklada
 	* @param string - cesta v XML
 	* 
 	*/
  function readCodelists($lang, $class){
		$xml = simplexml_load_file( PHPINC_DIR . "/xsl/codelists_$lang.xml");
		if(!$xml) return; // potlaceni chybejiciho coedlistu
		foreach($xml->$class->value as $keyword){
			$k = (string) $keyword['name']; // misto labelu hodnota
		    if($k != ''){
				$this->kwList[$lang][$class][$k] = (string) $keyword['code'];
			}	
		}		
	}
	
	/**
 	* Preklad klicoveho slova podle thesauru
 	*  
 	* @param string - klicove slovo
 	* @param string - jazyk, ze ktereho se preklada
 	* @param string - cesta v XML
 	* 
 	* @return string - preklad klic. slova
 	*/
	function translateKeyword($keyword, $lang, $xpath="inspireKeywords"){
		if(!$this->kwList[$lang][$xpath]){
			$this->readCodelists($lang, $xpath);
		}
		return $this->kwList[$lang][$xpath][$keyword];		
	}
	
	function multiKeywords(&$keywords, $lang){
		//var_dump($keywords);
		//kdyz anglictina, nic se nedeje
		if($lang=='eng') return;
		// --- cyklus pres thesaury
		for($i=0; $i<count($keywords); $i++){
			$thesaurusName = $keywords[$i]['MD_Keywords'][0]['thesaurusName'][0]['CI_Citation'][0]['title'][0]['@'];
			// --- jen INSPIRE zatím 
			if(strpos($thesaurusName, 'INSPIRE')!==false){
				// --- cyklus pres klíčová slova
				for($j=0; $j<count($keywords[$i]['MD_Keywords'][0]['keyword']); $j++){
					$engKeyword = $this->translateKeyword($keywords[$i]['MD_Keywords'][0]['keyword'][$j]['@'], $lang);
					if($engKeyword){
						$keywords[$i]['MD_Keywords'][0]['keyword'][$j]['@eng'] = $engKeyword;
					}
				}
			}
		}
	}
  
  function import($xmlString, $type, $user,$group_e,$group_v,$mds,$langs,$lang_main, $params=false,$updateType="", $md_rec="", $fc="", $public=0){
    /*---------------------------------------------------------------------
      Import jednoho XML dokumentu

		  $xmlString    obsah xml souboru
		  $format       format souboru ()
		  $user         prihlaseny uzivatel
		  $group_e   	  skupina pro editaci
		  $group_v      skupina pro prohlizeni
		  $mds  		  standard metadat
		  $langs 	  	  seznam pouzitych jazyku
			$public		  zda bude zaznam verejny
    ---------------------------------------------------------------------*/
		$mod = 'all'; // mod pro import, all importuje vse, neco jineho preskakuji uuid
		$id = "1";          // identifikator DS - pouze jedna
		$rs = -1;
		//---------------------------------------------------------------------
    $xp  = new XsltProcessor();
    $xml = new DomDocument;
    $xsl = new DomDocument;
    $OK = false;
	$esri = FALSE;

    if(!$xml->loadXML($xmlString)) die('Bad xml format');
    
    //--- import kote etc (19139)
    if(!$OK){
      $root = $xml->getElementsByTagNameNS("http://www.isotc211.org/2005/gmd", "MD_Metadata");
      if($root->item(0)){
        /*$isValid = $xml->schemaValidate("http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/gmd/gmd.xsd");
        if(!$isValid){
      		echo "WARNING! Imported XML file is not valid.";
      		var_dump(libxml_get_errors());
        }*/
        $xslName = PHPINC_DIR ."/xsl/". $this->xsl_files["iso"];
        $OK=true;
      }
    }
   
      //--- import kote etc (19139) - ISO 19115-2
    if(!$OK){
      $root = $xml->getElementsByTagNameNS("http://www.isotc211.org/2005/gmi", "MI_Metadata");
      if($root->item(0)){
        /*$isValid = $xml->schemaValidate("http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/gmd/gmd.xsd");
        if(!$isValid){
      		echo "WARNING! Imported XML file is not valid.";
      		var_dump(libxml_get_errors());
        }*/
        $xslName = PHPINC_DIR ."/xsl/". $this->xsl_files["iso"];
        $OK=true;
      }
    }
    
    //--- import nativni
    if(!$OK){
      $root = $xml->getElementsByTagName("results");
      if($root->item(0)){
      	$featureCatalogue = $xml->getElementsByTagName("featureCatalogue");
        if($featureCatalogue){
        	$fc = strval($featureCatalogue->item(0)->getAttribute('uuid'));
        }
        $xslName = PHPINC_DIR ."/xsl/". $this->xsl_files["native"];
        $OK=true;
      }
    }

    //--- kontrola, zda je ESRI
    if(!$OK){
	    $root = $xml->getElementsByTagName("Esri");
	    if($root->item(0)){
	      if($fc != '') {
		      $xslName = PHPINC_DIR ."/xsl/esri2fc.xsl";
		      $lang_fc = $langs;
	      }
	      else $xslName = PHPINC_DIR ."/xsl/".$this->xsl_files["esri"];
	      $OK = true;
	      $esri = true;
	    }
    }    
    
    //--- import ISVS/MIDAS
    if(!$OK){
      $root = $xml->getElementsByTagName("METAIS");
      if($root->item(0)){
        $xslName = PHPINC_DIR ."/xsl/". $this->xsl_files["isvs"];
        $OK=true;
      }
    }

    //--- import WMC
    if(!$OK){
      $root = $xml->getElementsByTagNameNS("http://www.opengis.net/context", "ViewContext");
      if($root->item(0)){
        $xslName = PHPINC_DIR ."/xsl/". $this->xsl_files["wmc"];
        $OK=true; 
      }
    }
    
    //--- zde pribudou dalsi typy pro import
    if(!$OK) {
      $rs = array();
      $rs[0]['ok'] = 0;
      $rs[0]['report'] = "Bad metadata document format!";
      return $rs;
    }
    
    $md = $this->xml2array($xml, $xslName);
    $lang=$md['MD_Metadata'][0]["language"][0]["LanguageCode"][0]['@'];
    if($lang=='fra') $lang="fre"; // kvuli 1GE
    if ($lang == '' && $lang_main != '') $lang = $lang_main;
    $md['MD_Metadata'][0]["language"][0]["LanguageCode"][0]['@'] = $lang;
    if (strpos($langs,$lang) === false && $lang != '') $langs = $lang . "|" . $langs; // kontrola, zda je jazyk zaznamu v seznamu pouzitych jazyku

    if($params){
      	$md = $this->array_replace_recursive($md,$params);
    }
           
    if($fc != '') {
    	$updateType = 'fc|' . $fc;
    }
    else {
      $uuid = $md['MD_Metadata'][0]['fileIdentifier'][0]['@'];
      // import obrazku z ESRI dokumentu
      if($esri){
        if($this->extractImage($xml, "graphics/$uuid.png")){
          if($ser) $pom = 'SV_ServiceIdentification'; else $pom = 'MD_DataIdentification';
          $md['MD_Metadata'][0]['identificationInfo'][0][$pom][0]['graphicOverview'][0]['MD_BrowseGraphic'][0]['fileName'][0] = "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF'])."/graphics/$uuid.png";        
          $md['MD_Metadata'][0]['identificationInfo'][0][$pom][0]['graphicOverview'][0]['MD_BrowseGraphic'][0]['fileDescription'][0] = "náhled";        
        }
      }
		}
   	if ($md_rec != '') {
    	$updateType = 'update|' . $md_rec;
   	}

		// ulozeni dat
		$c = new MdImport();
		$c->setTableMode($this->table_mode);
		$c->mds = $mds;
		if($group_e) $c->group_e = $group_e;
		if($group_v) $c->group_v = $group_v;
		//$c->setReportValidType('htmlsmall', false); // formát validace
		$c->setDataType($public); // nastavení veřejného záznamu
		$c->lang = $lang; // pokud není v datech, použije se toto nastavení jazyka
		//$c->stop_error = true; // pokud dojde k chybě při importu nepokračuje
		$c->stop_error = false; // pokud dojde k chybě při importu pokračuje
		$rs = $c->dataToMd($md, $updateType);
		//var_dump($rs);
    return $rs;
  }

  function importService($url, $service, $user, $group_e, $group_v, $mds, $langs, $lang='eng', $public=0, $updateType=''){
    /*---------------------------------------------------------------------
	Import metadat ze sluzby

      		$filename   nazev xml souboru
		  	$service    nazev typu sluzby (WMS, WFS, WCS, CSW, ...) podporovano zatim WMS
			$user       prihlaseny uzivatel
			$group_e   	skupina pro editaci
			$$group_v   skupina pro prohlizeni
			$mds  		standard metadat
			$langs    	seznam pouzitych jazyku
    		$lang		jazyk zaznamu
			$public		zda bude zaznam verejny
    ---------------------------------------------------------------------*/
    //$mod = 'all'; // mod pro import, all importuje vse, neco jineho preskakuji uuid
    $id = "1";                // identifikator zaznamu
    $rs = -1;
    //-----------------------------------------------

    $url = trim(htmlspecialchars_decode($url));
  	if (strpos($langs,$lang) === false) $langs = $lang . "|" . $langs; // kontrola, zda je jazyk zaznamu v seznamu pouzitych jazyku

    if(!strpos(strtolower(".".$url), "http")) $url = "http://".$url;
    if(!strpos(strtolower($url), "service=")){
      if(!strpos($url, "?")) $url .= "?"; else $url .= "&";
      $url .= "SERVICE=".$service;
    }
    if(!strpos(strtolower($url), "getcapabilities")) $url .= "&REQUEST=GetCapabilities";
    //echo "input url= <a href='$url'>$url</a>"; //TODO potom dat do reportu
    $xp  = new XsltProcessor();
    $xml = new DomDocument;
    $xsl = new DomDocument;
    @$s = file_get_contents($url);
    if(!$s) exit("<br>No data/connection! " . BACKURL );
    //TODO QUICK HACK - udleat pres sablony nebo DOM 
    if(strpos($s,'exception')) exit("<br><br>Exception: ". $s ." " . BACKURL);
    if(!@$xml->loadXML($s)) exit("<br><br>Not valid service!  " . BACKURL);
    $xslName = PHPINC_DIR."/xsl/".strtolower($service).".xsl"; // vyber sablony
    $md = $this->xml2array($xml, $xslName);
    if(!$md['MD_Metadata'][0]["language"][0]["LanguageCode"][0]['@']) {
    	$md['MD_Metadata'][0]["language"][0]["LanguageCode"][0]['@'] = $lang;
    }
    $url1 = $md["MD_Metadata"][0]["distributionInfo"][0]["MD_Distribution"][0]["transferOptions"][0]["MD_DigitalTransferOptions"][0]["onLine"][0]["CI_OnlineResource"][0]["linkage"][0]["@"];
	//var_dump($md);
    // --- vyhledani duplicitniho zaznamu ---
    if($updateType == "all"){
    	require PHPPRG_DIR . '/MdExport.php';
    	$export = new MdExport($_SESSION['u'], 1, 10, null); 
    	$ddata = $export->getdata(array(
    		"@linkage = '".$url1."'",
    		"And",
    		"@type = 'service'"
    			
    	));
		// nalezen záznam
		if(count($ddata["data"])>0){
			if(count($ddata["data"]) > 1){ 
				echo 'More records found with this URL.';
				foreach ($ddata["data"] as $row) echo "<br>". $row['uuid'].": ". $row['title'];
				echo '<br>The record will be added as new.';
			}
			else{
				echo "found record: <b>".$ddata["data"][0]['uuid']."</b> ".$ddata["data"][0]['title'] ;
				$md['MD_Metadata'][0]["fileIdentifier"][0]['@'] = $ddata["data"][0]['uuid'];
			}
		}
    }
	// ulozeni dat
	$c = new MdImport();
	$c->setTableMode($this->table_mode);
	$c->mds = $mds; 
	if($group_e) $c->group_e = $group_e; 
	if($group_v) $c->group_v = $group_v; 
	$c->setDataType($public); // nastavení veřejného záznamu
	$c->lang = $lang; // pokud není v datech, použije se toto nastavení jazyka
	//$c->stop_error = true; // pokud dojde k chybě při importu pokračuje 
	$c->stop_error = false; // pokud dojde k chybě při importu pokračuje 
	$rs = $c->dataToMd($md,'update');
	return $rs;
  } // konec funkce importService

} // konec class MetadataImport




