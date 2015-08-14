<?php

$langs = htmlspecialchars($_REQUEST["lang"]);
if(!$langs) {
    $langs = 'eng';
}

$langs = explode("|", $langs); 

foreach($langs as $lang){
    if(!in_array($lang, array('eng', 'cze'))) {
        $lang = "eng";
    }    
    $xml[$lang] = simplexml_load_file("../include/xsl/keywords_".$lang.".xml");
}
$id = htmlspecialchars($_REQUEST["uri"]);
$q = strtolower(htmlspecialchars($_REQUEST["query"]));

$out = array();
// codelist
if($id=='any'){
    foreach($xml[$langs[0]] as $row){
        $label = $row->register->registry->label;
        $lab = explode(" ",$label);
        $vals = array();
        $parent = (!$q || strpos(strtolower($row->label), $q)!==false);
        foreach($row->containeditems->value as $sub){
            if($parent || !$q || strpos(strtolower($sub->label), $q)!==false){
                $vals[] = array(        
                    "id"=>(string) $sub['id'],
                    "label"=> array($langs[0] => (string) $sub->label), 
                    "definition" =>array($langs[0] => (string) $sub->definition),
                    "level" => max((integer) $sub->level, 1) 
                );
            }            
        }
        if(count($vals)>0){
            $out["values"][] = array(
                    "id"=>(string) $row['id'],
                    "label"=>array($langs[0] => (string) $row->label." (".$lab[0].")"), 
                    "definition" =>array($langs[0] => (string) $row->definition)
            );
            $out["values"] = array_merge($out["values"],$vals);
        }
    }
}
// vraci podle uri
else if($id){   
    foreach($xml as $lang => $lxml){
        $result = $lxml->xpath("//*[@id='".$id."']");
        $lxml = $result[0]->containeditems->value;  
        $out["codelist"]["id"] = (string) $result[0]['id'];
        $out["codelist"]["label"][$lang] = (string) $result[0]->label;
        $out["codelist"]["definition"][$lang] =  (string) $result[0]->definition;
        $out["codelist"]["register"]["id"] = (string) $result[0]->register['id'];
        $out["codelist"]["register"]["label"][$lang] = (string) $result[0]->register->label;
        $out["codelist"]["register"]["version"] = (string) $result[0]->register->version;
        $out["codelist"]["register"]["publication"] = (string) $result[0]->register->publication;       
        $i=0;
        if($lxml){
            foreach($lxml as $row){
                $out["values"][$i]["id"] = (string) $row['id'];
                $out["values"][$i]["label"][$lang] = (string) $row->label;
                $out["values"][$i]["definition"][$lang] = (string) $row->definition;
                $out["values"][$i]["level"] = (integer) $row->level;        
                $i++;
            }
        }     
    }          
}
else {
    foreach($xml[$langs[0]] as $row){
        $label = $row->register->registry->label;
        $lab = explode(" ",$label);
        $out["values"][] = array(
                "id"=>(string) $row['id'],
                "label"=> array($lang =>(string) $row->label." (".$lab[0].")"), 
                "definition" =>array($lang =>(string) $row->definition)
        );
    }
}


header("Content-type: application/json; charset=utf-8");
echo json_encode($out);      