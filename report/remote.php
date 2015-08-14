<?php 
// test vzdalenych katalogu

require("cfg.php");

$s = file_get_contents(CSWURL . "?service=CSW&version=2.0.2&request=GetHarvest");
$cats = json_decode($s, true);

for($i=0; $i<count($cats); $i++){
  $cat = $cats[$i];
  // dotaz do vzdaleneho koatalogu
  $s = file_get_contents(CLIENTURL . "?serviceURL=".$cat["source"]."&limit=1&format=json&detail=brief&query=".urlencode($cat["filter"]));
	$result = json_decode($s, true);
	$cats[$i]['remote'] = $result["matched"];
	
	//dotaz do vlastniho
	$s = file_get_contents(CSWURL . "?query=server=".$cat['id']."&limit=1");
	$result = json_decode($s, true);
	$cats[$i]['local'] = $result["matched"];
}

header("Content-type: application/json");
echo json_encode($cats);