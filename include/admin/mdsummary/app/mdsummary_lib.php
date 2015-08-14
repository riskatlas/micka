<?php
function getMdSummary() {
    $rs = array();
    $sql = array();
    $sql[] = 'SELECT recno,uuid,data_type,md_standard,create_user,edit_group,view_group,create_date,last_update_date,title FROM md ORDER BY recno';
    $dbrs = _executeSql('select', $sql, array('all'));
    if (is_array($dbrs) && count($dbrs) > 0) {
        $rs = $dbrs;
    }
    return $rs;
}

function adminMdSummary($mdSummaryAction) {
	$rs = array();
    $rs['found']['records'] = getMdSummary();
	return $rs;
}
?>
