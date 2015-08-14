<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * MICKA_LIB_INSERT.PHP for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20101120
 * @authors		 DZ
 */

function deleteMd($user, $type, $value, $mode, $par=NULL) {
	setMickaLog('micka_lib_insert.php (deleteMd)', 'DEBUG', "user=$user, $type=$value, mode=$mode, par=$par");
	$rs = FALSE;
	// autorizace
	if ($user == 'guest' || !canAction()) {
		return $rs;
	}
	$record = getMdHeader($type, $value, $col='', $fetch=array('all','='));
	if (isset($record[0]['RECNO']) && $record[0]['RECNO'] > -1) {
		if (!getMdRight('edit', $user, $record[0]['DATA_TYPE'],	$record[0]['CREATE_USER'], $record[0]['EDIT_GROUP'], $record[0]['VIEW_GROUP'])) {
			return $rs;
		}
		else {
			$sql = array();
			if ($mode == 'all') {
				array_push($sql, 'DELETE FROM [md_values] WHERE [recno]=%i;', $record[0]['RECNO']);
				array_push($sql, 'DELETE FROM [md] WHERE [recno]=%i;', $record[0]['RECNO']);
			}
			elseif ($mode == 'value') {
				array_push($sql, 'DELETE FROM [md_values] WHERE [recno]=%i  AND md_id<>38;', $record[0]['RECNO']);
			}
			dibi::begin();
			try {
				dibi::query($sql);
				dibi::commit();
				$rs = TRUE;
			}
			catch(DibiException $e) {
				setMickaLog($e, 'ERROR', 'micka_lib_insert.php (deleteMd)');
				dibi::rollback();
			}
		}
	}
	//Debug::dump($rs);
	setMickaLog('micka_lib_insert.php (deleteMd)', 'DEBUG', "return=$rs");
	return $rs;
}

function insertMdValues($recno, $md_id, $md_value, $md_path, $lang, $package_id, $table='md_values') {
	$rs = FALSE;
	$sql = array();
	array_push($sql, "
		INSERT INTO [$table] (recno, md_id, md_value, md_path, lang, package_id)
		VALUES(%i, %i, %s, %s, %s, %i);
	", $recno, $md_id, $md_value, $md_path, $lang, $package_id);
	$result = _executeSql('insert', $sql, array());
	return $rs;
}

function updateMd($recno, $data, $user) {
	$rs = FALSE;
	$sql = array();
	$record = getMdHeader('recno', $recno, '', array('all','='));
	if (isset($record[0]['RECNO']) && $record[0]['RECNO'] > -1) {
		if (!getMdRight('view', $user, $record[0]['DATA_TYPE'],	$record[0]['CREATE_USER'], $record[0]['EDIT_GROUP'], $record[0]['VIEW_GROUP'])) {
			return $rs;
		}
	}
	if (count($data) == 0) {
		return $rs;
	}
	array_push($sql, 'UPDATE md SET', $data, 'WHERE recno=%i', $recno);
	$rs = _executeSql('update', $sql, array());
	//dibi::test($sql);
	return $rs;
}

function deleteMdValues($recno, $user, $params=array(), $harvest=FALSE) {
	if ($harvest === FALSE) {
		return TRUE;
	}
	$rs = FALSE;
	$sql = array();
	$record = getMdHeader('recno', $recno, '', array('all','='));
	/*
	if (isset($record[0]['RECNO']) && $record[0]['RECNO'] > -1) {
		if (!getMdRight('view', $user, $record[0]['DATA_TYPE'],	$record[0]['CREATE_USER'], $record[0]['EDIT_GROUP'], $record[0]['VIEW_GROUP'])) {
			return $rs;
		}
	}
	 * 
	 */
	array_push($sql, 'DELETE FROM md_values WHERE recno=%i', $recno);
	if (isset($params['lang']) && count($params['lang']) > 0) {
		array_push($sql, 'AND lang IN %in', $params['lang']);
	}

	$rs = _executeSql('delete', $sql, array());
	return $rs;
}

function updateMdGeom($recno, $x1, $y1, $x2, $y2, $table='md') {
	$sql = array();
	if (SPATIALDB) {
		/*
		array_push($sql, "UPDATE md SET the_geom=GeomFromText('MULTIPOLYGON(((");
		array_push($sql, "%f %f,", $x1, $y1);
		array_push($sql, "%f %f,", $x1, $y2);
		array_push($sql, "%f %f,", $x2, $y2);
		array_push($sql, "%f %f,", $x2, $y1);
		array_push($sql, "%f %f", $x1, $y1);
		array_push($sql, ")))',-1),");
		array_push($sql, "x1=%f, y1=%f, x2=%f, y2=%f", $x1, $y1, $x2, $y2);
		*/
		$sql[] = "
			UPDATE [$table] SET the_geom=GeomFromText('MULTIPOLYGON((($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1)))',-1),
		    x1=$x1, y1=$y1, x2=$x2, y2=$y2
		";
		array_push($sql, "WHERE recno=%i", $recno);
	}
	else {
		array_push($sql, "
			UPDATE [$table] SET x1=%f, y1=%f, x2=%f, y2=%f WHERE recno=%i
		", $x1, $y1, $x2, $y2, $recno);
	}
	$result = _executeSql('insert', $sql, array());
}

?>
