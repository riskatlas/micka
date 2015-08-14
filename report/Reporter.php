<?php

/*
* Vytvari reporty z MICKA pro CENIA
*
*
* header obsahuj etyto poloky:
* ["RECNO"]=>
    string(3) "653"
    ["UUID"]=>
    string(36) "4d2a29d5-665c-43e1-aa1a-29a3c0a80137"
    ["MD_STANDARD"]=>
    string(2) "10" 
    ["LANG"]=>
    string(3) "cze"
    ["DATA_TYPE"]=>
    string(1) "1"
    ["CREATE_USER"]=>
    string(7) "charvat"
    ["CREATE_DATE"]=>
    string(10) "2011-01-09"
    ["LAST_UPDATE_USER"]=>
    string(7) "charvat"
    ["LAST_UPDATE_DATE"]=>
    string(10) "2011-01-10"
    ["EDIT_GROUP"]=>
    string(7) "charvat"
    ["VIEW_GROUP"]=>
    string(7) "charvat"
    ["VALID"]=>
    NULL
    ["PRIM"]=>
    NULL
    ["SERVER_NAME"]=>
    NULL
    ["edit"]=>
    int(0)
  }
*/        
       
class Reporter{
    var $result;
    var $xml;
    var $cfg;
    var $codeList;
    
    /*
    * Konstruktor
    *
    */        
    function __construct(){
        $this->csw = new Csw();  
        $this->xml = new DomDocument();
        $this->codeList = new DomDocument();
        // konfigurak
        $this->cfg = simplexml_load_file("reporter.xml");
        if(!$this->cfg) die("ERROR: nenacten konfigurak");
    }
    
    /*
    * Shromaïuje vısledky pro jeden XML záznam
    *     
    * @xml DomElement - jeden MD_Metadata záznam  
    */        
    function collectResults($xml,$head){
        foreach($this->cfg->item as $item){
            // dotaz do hlavicky
            $podm = true;
            if($item['head']){
                eval('$podm=($'."head".$item['head'].");");
            }
            if($podm){
                $xpath = (string) $item['xpath'];
                $code = (string) $item['code'];
               	if(!$this->result[$code]){
  	                $this->result[$code]['count'] = 0;
  	                $this->result[$code]['label'] = (string) $item;
  	                $this->result[$code]['xpath'] = $xpath;
  	                $this->result[$code]['query'] = (string) $item['query'];
  	                $this->result[$code]['result'] = array();
  	            }
                if(substr($xpath, 0, 5)=="HEAD:"){
                	$node = strval($head[trim(substr($xpath, 5))]);
                	$this->result[$code]['result']["$node"]++;
                	$this->result[$code]['count']++;
                }
                else {
	                $result = $this->xp->query($xpath, $xml);
	                // prvotni vyplneni reportu neexistuje-li
	                // pocitani nodu    
	                foreach($result as $node){
	                    $this->result[$code]['result'][$node->nodeValue]++;
	                    $this->result[$code]['count']++;
	                    // break; ??? 
	                }
                }
            }
        }    
    }
    
    /*
    * Inicializuje XPath pro nove nactene XML
    *    
    */        
    function refreshXpath(){
        $this->xp = new DOMXPath($this->xml);
        $this->xp->registerNameSpace("gmd", "http://www.isotc211.org/2005/gmd");
        $this->xp->registerNameSpace("gco","http://www.isotc211.org/2005/gco");
        $this->xp->registerNameSpace("gml","http://www.opengis.net/gml");
        $this->xp->registerNameSpace("srv","http://www.isotc211.org/2005/srv");
    }   

    /*
    * Spusti vytvoreni reportu
    *
    * return array - aosciativni pole s vysledky   
    */        
    function run($qstring=""){
        // prochazi cele repository
        $start = 1;
        $step = 50;
        $numResults = 0;
        // zpracovani dotazu
        if($qstring){
            $query = $this->csw->cql2sql($qstring);
        }
        // prazdny dotaz
        else {
            $query = array();
        }
        do {
            $export = new MdExport($_SESSION['u'], $start, $step, "");
            list($xmlstr, $head) = $export->getXML($query, array());
            //var_dump($head); die;
            //echo $xmlstr; die();
            $this->xml->loadXML($xmlstr);
            $vety = $this->xml->getElementsByTagNameNS("http://www.isotc211.org/2005/gmd", "MD_Metadata");
            $numResults = $this->xml->documentElement->getAttribute("numberOfRecordsMatched");
            $this->refreshXpath();
            $i=0;
            foreach($vety as $veta){
                $this->collectResults($veta,$head[$i]);
                $i++;
            }
            $start += $step;
        } while($start <= $numResults); 
        return $this->result;
    }
    
    
}
