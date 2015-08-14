<?php
/*
Knihovna pro prihlasovani (projekty v XML)
verze 4.01, 20121026
Help Service Remote Sensing
*/

function prihlaseni($user, $heslo){
//pihl?n?ivatele
global $hlaska, $pwdfname;
   //kontrola existence souboru hesel
  if (!File_Exists($pwdfname)){
    $hlaska = "Nenalezen soubor hesel $pwdfname";
    return false;
  }
  //cteni souboru hesel
  $fHesla = fopen($pwdfname, "r");
  $ret = false;
  $hlaska = "Uzivatel neexistuje";
  while (!fEof($fHesla)){
    $row = fGetS($fHesla); 
    $pom = explode("|",$row);
    $pom1 = explode(",",array_shift($pom));      
    $hesla = array_merge($pom1,$pom);
    if ($hesla[0] == $user){
      $hlaska = "Neplatne heslo";
      $heslo = trim($heslo);
      $heslo = MD5($user.$heslo);
      if (trim($hesla[2]) == $heslo){
        $ret = true;
        $hlaska = "";
        session_regenerate_id();
        $_SESSION["u"] = $user;
        $_SESSION["ms_groups"] = trim($hesla[1]." ".$user);
				if (isset($hesla[3])) {
	        $_SESSION["ms_contact"]["party"] = $hesla[3];
				}
				else {
	        $_SESSION["ms_contact"]["party"] = '';
				}
				if (isset($hesla[4])) {
	        $_SESSION["ms_contact"]["address"] = $hesla[4];
				}
				else {
	        $_SESSION["ms_contact"]["address"] = '';
				}
				if (isset($hesla[5])) {
	        $_SESSION["ms_contact"]["email"] = $hesla[5];
				}
				else {
	        $_SESSION["ms_contact"]["email"] = '';
				}
      }
      break;
    }
  }
  fclose($fHesla);
  return $ret;
}


function readProj($xmlPath, $msgroups, $right){
  $xml = simplexml_load_file($xmlPath);
  $prj = Array();
  foreach($xml->projGroup as $group){
	  $groupName = (string) $group['name'];
    $prj[$groupName]["title"] = (string)$group->title;
	  foreach($group->project as $project){
  	  $currProj = Array();
      $currProj["script"] = (string)$group->script;
      $currProj["wparams"] = (string)$group->wparams;
      $currProj["wname"] = (string)$group->wname;
	  	$can = false;
	  	$prjName = (string) $project['name'];
	  	foreach($project->children() as $node){
			  $nodeName = $node->getName();
			  switch($nodeName){
			    case "users": 
            foreach($node->user as $usr){
              $u = (string)$usr;
              $r = (string)$usr["rights"];
              if((strpos(" ".$msgroups." ", $u))&&(strpos(".".$r,$right))) $can = true;
              else if(($u=='guest')&&(strpos(".".$r,$right))) $can = true;
              $currProj[$nodeName][$u] = $r; 
            }  
			      break;
			    // opakujici se adresare  
			    case "fileDir":
			      $dname = (string) $node['name'];
			      if(!$dname) $dname = "default";
			      $currProj[$nodeName][$dname] = (string)$node; 
					  break;
					// ostatni elementy  
					default: 
					  $currProj[$nodeName] = (string)$node;
					  break;
			  }
			}
      if($can){ 
        $prj[$groupName]["project"][$prjName] = $currProj;
      } 
		}
	} 
	return $prj;
}

function flatProj($groups){
//vytvori session promenne pro autorizaci
  $prj = Array();
  foreach($groups as $group){
    if($group["project"])
    while(list($key, $val)= each($group["project"])) $prj[$key] = $val;
  }
  return $prj;
}

function getProj(){
//ziskani seznamu projektu
  global $prjPath, $logovat;
  $_SESSION["hs_log"]=$logovat;
  //$xml = new XPath($prjPath.$_SESSION["lang"].".xml");
  $projects = readProj($prjPath.".xml", $_SESSION["ms_groups"], "r");
  //$xml->reset;
  if(!$projects) return false;
  $_SESSION["maplist"] = flatProj($projects);
  if(!count($_SESSION["maplist"])) return false;
  return $projects;
}

function resetMap(){
//vymazani query apod
  $queryfile = $this->mapa->web->imagepath."/".$_SESSION["sid"].".qy";
  if(file_exists($queryfile)) unlink($queryfile);
}

function getAllGroups($users=false,$sort=true){
  global $hlaska, $pwdfname;
   //kontrola existence souboru hesel
  if (!File_Exists($pwdfname)){
    $hlaska = "Nenalezen soubor hesel";
    return false;
  }
  //cteni souboru hesel
  $fHesla = fopen($pwdfname, "r");
  $groups = array();
  while (!fEof($fHesla)){
    $row = fGetS($fHesla); 
    $row = explode(",",$row);
    $group = array();
    if($row[1]) $group = explode(" ", $row[1]);
    if($users && $row[0]) $group[] = $row[0];  
    $groups = array_merge($groups,$group);     
  }
  fclose($fHesla);
  $groups = array_unique($groups);
  if($sort) asort($groups);
  return $groups;
}

?>
