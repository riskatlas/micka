<?php
define('SQL_LIKE', "@FIELD ILIKE @VALUE");

function setXmldata2Pxml($table, $recno) {
	$sql = "UPDATE $table SET pxml = XMLPARSE(DOCUMENT xmldata) WHERE recno=$recno";
	_executeSql('pxml', array($sql), array('all'));
}

/*
function setMdPxml($table, $recno, $xml) {
	$sql = array();
	$xml = str_replace("'", "&#39;", $xml);
	$sql[] = "UPDATE " . $table . " SET pxml=XMLPARSE(DOCUMENT '$xml') WHERE recno=$recno";
	_executeSql('pxml', $sql, array('all'));
}
*/
