<?php
function getValueFind($valueFind) {
    return str_replace('%', '', $valueFind);
}

function getPathEl($pathEl) {
    return str_replace(' ', '', $pathEl);
}

function replaceMdValue($mode, $replaceEl, $pathEl, $valueFind, $replaceAll, $valueReplace) {
	$rs = array();
	$rs['replaceRecords'] = 0;
    $rs['affectedRecords'] = 0;
    $rs['records'] = array();
	switch ($replaceEl) {
		case 'email':
			$md_id = array();
			$sql = array();
			$sql[] = 'SELECT md_id FROM tree WHERE ' . setSqlLike('md_path_el', "'%electronicMailAddress'");
			$row_md_id = _executeSql('select', $sql, array('all'));
			if (is_array($row_md_id) && count($row_md_id) > 0) {
				foreach ($row_md_id as $row) {
					$md_id[] = $row['MD_ID'];
				}
			} else {
				return $rs;
			}
			break;
		case 'name_md':
			$md_id = array(152);
			break;
		case 'name_data':
			$md_id = array(186,5028);
			break;
		case 'title':
			$md_id = array(11,5063);
			break;
		case 'any':
			$md_id = array();
			break;
		case 'optional':
			$sql = array();
			array_push($sql, 'SELECT md_id FROM tree WHERE md_path_el LIKE %s', $pathEl . '%');
			$row_md_id = _executeSql('select', $sql, array('all'));
			if (is_array($row_md_id) && count($row_md_id) > 0) {
				foreach ($row_md_id as $row) {
					$md_id[] = $row['MD_ID'];
				}
			} else {
				return $rs;
			}
			break;
		default:
			return $rs;
	}
    $sql_equality = '=';
    if (strpos($valueFind, '*') !== FALSE) {
        //$sql_equality = DB_DRIVER == 'postgre' ?  ' ILIKE ' : ' LIKE ';
        $sql_equality = ' LIKE ';
        $valueFind = str_replace('*', '%', $valueFind);
    }
	$sql = array();
    array_push($sql, 'SELECT DISTINCT md.recno, md.uuid, md.title, md.md_standard');
    array_push($sql, 'FROM md INNER JOIN md_values ON md.recno=md_values.recno');
    array_push($sql, 'WHERE md_values.md_value' . $sql_equality . '%s', $valueFind);
    if (count($md_id) > 0) {
        array_push($sql, 'AND md_values.md_id IN %in', $md_id);
    }
    array_push($sql, 'ORDER BY md.recno');
    $replaceRecords = _executeSql('select', $sql, array('all'));
    if ($mode == 'replace' && $valueReplace != '') {
        $sql = array();
        if ($replaceAll === TRUE) {
            array_push($sql, 'UPDATE md_values SET md_value=%s', $valueReplace);
        } else {
            array_push($sql, 'UPDATE md_values SET md_value=REPLACE(md_value,%s,%s)', getValueFind($valueFind), $valueReplace);
        }
        array_push($sql, 'WHERE md_values.md_value' . $sql_equality . '%s', $valueFind);
        if (count($md_id) > 0) {
            array_push($sql, 'AND md_id IN %in', $md_id);
        }
        $rs['affectedRecords'] = _executeSql('update', $sql, array('all'));

        // aktualizace XML
        $md_record = new MdRecord();
        foreach ($replaceRecords as $row) {
            //$result = $md_record->updateOnlyXmlData($row['UUID'], $row['RECNO']);
            $md_record->setStopDatestamp(FALSE);
            $result = $md_record->updateTableMd('uuid', $row['UUID']);
        }
    }
    if ($replaceRecords == '') {
        $replaceRecords = array();
    }
	$rs['replaceRecords'] = 0;
    $rs['records'] = $replaceRecords;
    //my_print_r($rs);
	return $rs;
}

function adminMdReplace($mdReplaceAction) {
    $replaceEl = isset($_POST['replace_el']) ? htmlspecialchars($_POST['replace_el']) : '';
    $pathEl = isset($_POST['path_el']) ? getPathEl(htmlspecialchars($_POST['path_el'])) : '';
    $valueFind = isset($_POST['value_find']) ? htmlspecialchars($_POST['value_find']) : '';
    $valueReplace = isset($_POST['value_replace']) ? htmlspecialchars($_POST['value_replace']) : '';
    $replaceAll = isset($_POST['replace_all']) && $_POST['replace_all'] == 'no' ? FALSE : TRUE;
	$rs = array();
	$rs['action'] = 'form';
	if ($mdReplaceAction == 'search') {
		if ($valueFind == '') {
			$rs['action'] = 'error';
			$rs['form']['error'] = 'Find What is empty!';
		} else {
			$rs['action'] = 'find';
			$rs['form']['path_el'] = $pathEl;
			$rs['form']['replace_el'] = $replaceEl;
			$rs['form']['value_find'] = $valueFind;
			$rs['form']['found'] = replaceMdValue('search', $replaceEl, $pathEl, $valueFind, $replaceAll, $valueReplace);
		}
		
	} elseif ($mdReplaceAction == 'replace') {
		if ($valueReplace == '') {
			$rs['action'] = 'error';
			$rs['form']['error'] = 'Replace With is empty!';
		} elseif ($valueFind == $valueReplace) {
			$rs['action'] = 'error';
			$rs['form']['error'] = 'Find What = Replace With!';
		} else {
			$rs['action'] = 'afterReplace';
			$rs['form']['path_el'] = $pathEl;
			$rs['form']['replace_el'] = $replaceEl;
			$rs['form']['value_find'] = $valueFind;
			$rs['form']['value_replace'] = $valueReplace;
			$rs['form']['found'] = replaceMdValue('replace', $replaceEl, $pathEl, $valueFind, $replaceAll, $valueReplace);
		}
		
	}
	return $rs;
}
?>
