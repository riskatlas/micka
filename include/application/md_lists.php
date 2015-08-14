<?php
/**
 * version 20121119
 */

$title = '';

function getList($type, $lang, $withValues=false){
    if($type=='XXXspecif'){
        @$xml = simplexml_load_file(PHPPRG_DIR . "/../xsl/codelists_$lang.xml");
        if(!$xml) $xml = simplexml_load_file(PHPPRG_DIR . "/../xsl/codelists_eng.xml");
        echo "<h1>".(string) $xml->inspireKeywords['title']."</h1>";
        $list = $xml->xpath("//inspireKeywords/value");
        foreach ($list as $entry){
            echo "<a href=\"javascript:opener.specif1('INSPIRE Data Specification on ".$entry['code']." - Guidelines', '".$entry['publication']."'); window.close();\">".(string) $entry."</a><br>";
        }
        echo "<br><h1>".(string) $xml->serviceSpecifications['title']."</h1>";
        $list = $xml->xpath("//serviceSpecifications/value");
        foreach ($list as $entry){
            echo "<a href=\"javascript:opener.specif1('".$entry['name']."', '".$entry['publication']."'); window.close();\">".(string) $entry."</a><br>";
        }
        return;
    }
	@$xml = simplexml_load_file(PHPPRG_DIR . "/../dict/$type.xml");
	if(!$xml) die("list <b>$type</b> does not exist");
	// test jazyka
	$langBranch = $xml->xpath("//translation[@lang='".$lang."']");
	if(!$langBranch[0]) $lang='eng';
	$pageTitle = $xml->xpath("//translation[@lang='".$lang."']/title");
	echo "<h2>".$pageTitle[0]."</h2>";
	foreach ($xml->xpath("//translation[@lang='".$lang."']/group") as $list) {
    	echo "<h3>".$list->title.'</h3>';
    	foreach ($list->entry as $entry){
    		// pouzije primarne label, kdyz neni, tak hodnotu
    		$value = $entry['label'];
    		if(!$value) $value = (string) $entry;
    		if($withValues) echo "<a href=\"javascript:fillValues('".$type."','".$entry['id']."');\">".$value."</a><br>";
    		else echo "<a href=\"javascript:kw('".$entry['code']."');\">".(string) $entry."</a><br>";
    	}
	}
}

function getValues($type,$id){
	$xml = simplexml_load_file(PHPPRG_DIR . "/../dict/$type.xml");
	$result = array();
	foreach ($xml->xpath("//entry[@id='".$id."']") as $entry) {
		$lang = $entry->xpath("../../@lang");
		$parent =  $entry->xpath("..");
		$parent = $parent[0];
		$prefix = (string) $parent->prefix;
		//var_dump($parent);
		foreach($parent->attributes() as $k => $v) {
		    $result[(string) $lang[0]][(string) $k] = (string) $v;
		}
		if (isset($prefix)) {
			$result[(string) $lang[0]]['value'] = (string) $prefix . (string) $entry;
		} else {
			$result[(string) $lang[0]]['value'] = (string) $entry;
		}
		foreach($entry->attributes() as $k => $v) {
		    $result[(string) $lang[0]][(string) $k] = (string) $v; 
		}
		
	}
	return $result;
}

// vraci JSON multilingualni seznam
if(isset($_REQUEST['request']) && $_REQUEST['request'] == 'getValues') {
	$type = htmlspecialchars($_REQUEST['type']);
	$code = htmlspecialchars($_REQUEST['id']);
    header("Content-type: application/json; charset=utf-8");
	echo json_encode(getValues($type,$code));
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="<?php echo substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')) . '/themes/' . MICKA_THEME; ?>/micka.css" />
<title>MICKA - <?php echo $title; ?></title>
<script language="javascript" src="scripts/ajax.js"></script>
<script>
function fillValues(listType,code){
    var ajax = new HTTPRequest;
    ajax.get("index.php?ak=md_lists&request=getValues&type="+listType+"&id="+code, "", fillValuesResponse, false);
}

function fillValuesResponse(r){
	if(r.readyState == 4){
		eval("var k="+r.responseText);
		kw(k);
	}
}

function kw(f){
	<?php 
	    if($_REQUEST['fc']) echo "opener.".htmlspecialchars($_REQUEST['fc'])."(f);";
	    else echo "opener.formats1(f);";
	?>
  //window.close();
}
</script>
</head>
<body onload="javascript:focus();">
<?php
    $lang = htmlspecialchars($_REQUEST['lang']);
    if(!$lang) $lang='eng';
    if($_REQUEST['multi']==1) $multi = true;
    echo getList(htmlspecialchars($_REQUEST['type']), $lang, $multi);
?>
</body>
</html>
<?php exit; ?>
