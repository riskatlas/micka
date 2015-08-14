<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * Lib_XML for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20121206
 *
 */

function applyTemplate($xmlSource, $xsltemplate) {
	$rs = FALSE;
	if (File_Exists (CSW_XSL . '/' . $xsltemplate)) {
		if(!extension_loaded("xsl")) {
			if(substr(PHP_OS,0,3)=="WIN") dl("php_xsl.dll");
			else dl("php_xsl.so");
		}
		$xp  = new XsltProcessor();
		$xml = new DomDocument;
		$xsl = new DomDocument;
		$xml->loadXML($xmlSource);
		$xsl->load(CSW_XSL . '/' . $xsltemplate);
		$xp->importStyleSheet($xsl);
		//$xp->setParameter("","lang",$lang);
		$xp->setParameter("","user",$_SESSION['u']);
		$rs = $xp->transformToXml($xml);
	}
	if ($rs === FALSE) {
		setMickaLog('applyTemplate === FALSE', 'ERROR', 'micka_lib_xml.php');
	}
	return $rs;
}

// kontrola uplnosti metadat
function mdControl($xmlSource){
  // podle jednoho validatoru
    if ($xmlSource == '') {
        return array();
    }
    include(PHPINC_DIR . "/../validator/resources/Validator.php");
    $validator = new Validator("gmd", MICKA_LANG);
    $validator->run($xmlSource);
    $a = $validator->asArray();
    for($i=0;$i<count($a);$i++){
       $d = explode('(',$a[$i]['description']);
       $a[$i]['description'] = $d[0];
    }
    return $a;
}

/**
 * Tvorba souboru XML
 *
 * @param string $user
 * @param string $uuid
 * @param string $xsltemplate
 * @param string $xml_from cache|data
 * @return string XML|FALSE
 */
function getXml($user, $uuid, $xsltemplate, $xml_from='cache') {
	setMickaLog("USER=$user, UUID=$uuid, XSL=$xsltemplate", 'DEBUG', 'micka_lib_xml.php (getXML)');
	$xml = FALSE;
	$in = array();
	if ($uuid != '') {
		$in[] = "_UUID_ = '$uuid'";
		$export = new MdExport($user);
		$export->xml_from = $xml_from;
		$xml_pom = $export->getXML($in, FALSE);
		setMickaLog('XML=' . $xml_pom, 'DEBUG', 'micka_lib_xml.php (getXML)');
		if ($xsltemplate != '' && $export->xml_from == 'data' && $xml_pom != '') {
			setMickaLog('applyTemplate', 'DEBUG', 'micka_lib_xml.php (getXml)');
			$xml_pom = applyTemplate($xml_pom, $xsltemplate);
			if ($xml_pom === FALSE) {
				setMickaLog('applyTemplate === FALSE', 'ERROR', 'micka_lib_xml.php (getXml)');
				return $xml;
			}
    }
		if ($xml_pom != '') {
			$xml = $xml_pom;
		}
	}
	return $xml;
}

function updateXml($user, $type, $value) {
	$rs = FALSE;
	$xsltemplate = 'micka2one19139.xsl';
	$recno = -1;
	$uuid = '';
	if ($type == 'uuid' && $value != '') {
		$uuid = $value;
	}
	elseif ($type == 'recno' && $value > -1) {
		$recno = $value;
		$uuid = getMdHeader($type, $value, 'uuid', array('single'));
		$type = 'uuid';
	}
	if ($type == 'uuid' && $uuid != '') {
		$xml = getXml($user, $uuid, $xsltemplate, 'data');
		if($xml === FALSE || $xml == '') {
			setMickaLog('$xml === FALSE', 'ERROR', 'micka_lib_xml.php (updateXml)');
		}
		else {
			$sql = array();
			array_push($sql, "UPDATE md SET pxml=%s WHERE uuid=%s", $xml, $uuid);
			$rs = _executeSql('update', $sql, array('all'));
		}
	}
	return $rs;
}

