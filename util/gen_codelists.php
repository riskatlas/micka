<?php
/**
 * Generování xsl/codelists_lang.xml
 * 
 * @version    20111027
 */
session_start();
require '../include/application/micka_config.php';
require PHPPRG_DIR . '/micka_lib.php';
require PHPPRG_DIR . '/micka_auth.php';

$lang = $_REQUEST["lang"];
if(!$lang)$lang='cze';
$sql = array();
array_push($sql, "
	SELECT elements.el_name, codelist.codelist_name, codelist.codelist_domain, label.label_text
	FROM ((label INNER JOIN codelist ON label.label_join = codelist.codelist_id)
		INNER JOIN elements ON codelist.el_id = elements.el_id)
		LEFT JOIN codelist_my ON codelist.codelist_id = codelist_my.codelist_id
	WHERE label.label_type='CL' AND label.lang=%s AND codelist_my.is_vis=1 AND elements.el_name != ''
	ORDER BY elements.el_name, codelist.codelist_domain;
", $lang);
$result = _executeSql('select', $sql, array('all'));
$element = "***";
header("Content-type: application/xml");
echo "<?xml version='1.0' encoding='utf-8' ?"."><map>";
foreach ($result as $key => $row) {
  if($element != $row["EL_NAME"]) {
    if($element != '***') echo "</$element>";
    $element = $row["EL_NAME"];
    echo "<$element>";
  }
  echo "<value name=\"$row[CODELIST_NAME]\" code=\"$row[CODELIST_DOMAIN]\">$row[LABEL_TEXT]</value>";
}
echo "</$element></map>";
?>