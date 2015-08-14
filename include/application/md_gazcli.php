<?php
/******************************
* verze 20130108
******************************/

$lang = $_SESSION['hs_lang'];
if($lang != 'cze') $lang = 'eng';

$labels = array();
$labels['cze']['unit']='Jednotka';
$labels['cze']['name']='Název';

$labels['eng']['unit']='Unit';
$labels['eng']['name']='Name';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="<?php echo substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')) . '/themes/' . MICKA_THEME; ?>/micka.css" />
<script language="javascript" src="scripts/ajax.js"></script>
<title>MICKA - Gazeteer</title>
<script>

function gazPoly(i){
  if((!opener)||(!opener.md_gazet1)){
    alert('Main window is closed !');
    window.close();
    return;
  }
  for(var j=0;j<(coord[i]).length;j++){
    if(j==0)opener.md_gazet1(coord[i][j], true);
    else opener.md_gazet1(coord[i][j], false);
  }  
}

function gazBbox(bbox){
  if(!opener){
    alert('Parent window was closed');
    return;
  }
  var mapFrame = opener.frames[0];
  // pro portal
  if(opener.portal){
  	opener.getGazBbox(bbox);
  }
  else if(mapFrame.epsg!=4326){
    var ajax = new HTTPRequest;
    ajax.get("/mapserv/php/transform.php?request=getProjected&mapcoords="+bbox+"&srs=EPSG:4326&srsout=EPSG:"+mapFrame.epsg, null, tBbox, true);
  }
  else{
    opener.getFindBbox(bbox);
    mapFrame.vyrez(bbox);
    //mapFrame.swapImage();
    //mapFrame.refreshmap();
  }  
}

function tBbox(s){
  var mapFrame = opener.frames[0];
  mapFrame.document.forms.mapserv.imgext.value=s.responseText.replace(/,/, ' ');
  mapFrame.swapImage();
  //mapFrame.refreshmap();
}

</script>
</head>
<body onload="javascript:focus();">
<h2>Gazetteer</h2>
<form>
	<input type="hidden" name="ak" value="md_gazcli">
<table>
<tr><td><?php echo $labels[$lang]['unit']; ?>:</td> 
<td>
<?php
class gazClient{
  var $url;
  var $xslName;
  var $typename;
  var $item;

  function __construct($url, $xslName, $typename, $item, $cp=""){
    if(!extension_loaded("xsl")){
      if(substr(PHP_OS,0,3)=="WIN") dl("php_xsl.dll");
      else dl("php_xsl.so");
    }
    $this->url = $url; 
    $this->xslName = $xslName;  
    $this->typename = $typename; 
    $this->item = $item; 
    $this->cp = $cp;
  }

  private function getDataByPost($params){
  // params = asociativni pole odpovidajici key=>val
    $content = http_build_query($params);
    $options = array('http'=>array(
      'method' => 'POST',
      'header' => "Content-type: application/x-www-form-urlencoded\r\n", 'content' => $content)
    );
    $context = stream_context_create($options);
    $val = file_get_contents($this->url, false, $context);
    return $val;
  }

  private function getDataByGet($params){
  	// params = asociativni pole odpovidajici key=>val
  	foreach($params as $key=>$val){
  		$s .= "&".$key."=".$val;
  	}
  	$val = file_get_contents($this->url."?".substr($s,1));
  	return $val;
  }
  
  function getData($qstr){
  	if($this->cp) $qstr = iconv("UTF-8", $this->cp, $qstr);
    $xp = new XsltProcessor();
    $xml = new DomDocument;
    $xsl = new DomDocument;
    $query["SERVICE"]="WFS";    
    $query["VERSION"]="1.0.0";
    $query["REQUEST"] = "GetFeature";
    $query["TYPENAME"] = $this->typename;
    $query["Filter"]="<ogc:Filter><ogc:PropertyIsLike wildCard=\"*\" singleChar=\"@\" escape=\"\\\" matchCase=\"false\"><ogc:PropertyName>".$this->item."</ogc:PropertyName><ogc:Literal>$qstr</ogc:Literal></ogc:PropertyIsLike></ogc:Filter>";
    $query["Filter"]=urlencode("<Filter><PropertyIsLike wildCard=\"*\" singleChar=\"@\" escape=\"\\\" matchCase=\"false\"><PropertyName>".$this->item."</PropertyName><Literal>$qstr</Literal></PropertyIsLike></Filter>");
    //highlight_string($query["Filter"]); echo "<hr>";
    $s = $this->getDataByGet($query);
    //highlight_string($s); //exit;
    $xml->loadXML($s);
    $xsl->load($this->xslName);
    $xp->importStyleSheet($xsl);
    return $xp->transformToXML($xml);
  }
} // end of class gazClient

//--- precte seznam serveru do select
$wfsList = new DomDocument;
$wfsList->load(PHPINC_DIR."/gazet/wfs_servers.xml");
$servers = $wfsList->getElementsByTagName("server");
echo "<select name='wfs'>";
foreach($servers as $server){
  if(isset($_REQUEST["wfs"]) && $server->getAttribute("name")==$_REQUEST["wfs"]) $sel=" selected"; else $sel="";
  echo "<option value=".$server->getAttribute("name").$sel.">".$server->getAttribute("label")."</option>";
}
echo "</select>";
?>
</td></tr>
	<tr><td><?php echo $labels[$lang]['name'];?>:</td><td><input name='query' value="<?php echo isset($_REQUEST["query"]) ? $_REQUEST["query"] : ''; ?>">
<input type="hidden" name="simple" value="<?php echo isset($_REQUEST["simple"]) ? $_REQUEST["simple"] : ''; ?>">
<input type='submit' value='OK'></td></tr></table>
</form>
<?php
//--- vlastni zpracovani dotazu ---
if(isset($_REQUEST["query"]) && $_REQUEST["query"]){
  echo "<br><table class='odp'>";
  if($_REQUEST["simple"]){
    foreach($servers as $server){
      if($server->getAttribute("name")==$_REQUEST["wfs"]){
        $gazet = new gazClient($server->getAttribute("href"), PHPINC_DIR."/gazet/".$server->getAttribute("xslb"), $server->getAttribute("typeName"), $server->getAttribute("propertyName"), $server->getAttribute("cp"));
        break;
      }
    }
    $s = $gazet->getData("*".$_REQUEST["query"]."*");
    if(!trim($s)) echo "not found";
  }
  else {
    foreach($servers as $server){
      if($server->getAttribute("name")==$_REQUEST["wfs"]){
        $gazet = new gazClient($server->getAttribute("href"), PHPINC_DIR."/gazet/".$server->getAttribute("xsl"), $server->getAttribute("typeName"), $server->getAttribute("propertyName"), $server->getAttribute("cp"));
        break;
      }
    }
  
  //zpracovani gazeteeru
    $s = $gazet->getData("*".$_REQUEST["query"]."*");
    $rows = explode ("|", $s);
    $s = "";
    $sour = "";
    for($i=0;$i<(count($rows)-1); $i++){
      $pom = explode(":", $rows[$i]);
      $s .= "<a href=\"javascript:gazPoly($i);\">$pom[0]</a><br>";
      $j = 0;
      $sour .= "coord[$i] = new Array();";
      while(strlen($pom[1])>1024){
        $sour .= "coord[$i][$j]='".substr($pom[1],0,1024)."';";
        $pom[1]=substr($pom[1],1024,999999);
        $j++;
      }
      $sour .= "coord[$i][$j]='".$pom[1]."';";
    }
  
  
  echo "<script>
  var coord =new Array();
  $sour
  </script>";
  } 
  echo $s;

  echo "</table>";
}
?>

</body>
</html>

<?php exit; ?>