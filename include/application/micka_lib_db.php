<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * Funkce pro práci s databází
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20120807
 *
 */

/**
 * Provedení sql dotazu a předání nalezených záznamů v požadovaném tvaru
 *
 * <b>Příklady:</b><br>
 * první políčko výsledku<br>
 * <code>_executeSql('select', $sql, array('single'));</code>
 * celá tabulka do indexovaného pole<br>
 * <code>_executeSql('select', $sql, array('all'));</code>
 * celá tabulka do asociativního pole a klíčem je políčko ‚recno‘
 * <code>_executeSql('select', array($pom['sql']), array('assoc', 'recno,#,='));</code>
 * asociativního pole klíč ⇒ hodnota
 * <code>_executeSql('select', array($pom['sql']), array('pairs', 'recno', 'xmldata'));</code>
 * update, insert, delete
 * <code>_executeSql('update', $sql, array());</code>
 *
 * @param string $type 'select', 'update', 'insert', 'delete'
 * @param array $sql
 * @param array $fetch 'single', 'all', 'assoc', 'pairs'
 */
function _executeSql($type, $sql, $fetch, $upper=TRUE) {
	//setMickaLog("SQL TYPE=$type, fetch=" . $fetch[0], 'FUNCTION', 'micka_lib_db._executeSql.start');
	$rs = FALSE;
	if (count($sql) == 0) {
		setMickaLog("empty SQL", 'ERROR', 'micka_lib_db._executeSql');
		return $sql;
	}
	if (is_array($sql) && is_array($fetch) && $type == 'select') {
		$result = dibi::query($sql);
		setMickaLog(preg_replace("/\s{2,}/", ' ', dibi::$sql), 'SQL', 'micka_lib_db._executeSql.sql');
		//Debugger::log('[micka_lib_db._executeSql.select] ' . dibi::$sql, 'INFO');
		//Debugger::log('[micka_lib_db._executeSql.select] ' . print_r($sql, true), 'INFO');
		if ($fetch[0] == 'single') {
			$rs = trim($result->fetchSingle());
		}
		elseif ($fetch[0] == 'all') {
			//setMickaLog("FETCH ALL START", 'DEBUG', 'micka_lib_db._executeSql');
			$blob = 'OCI-Lob';
			//if (count($result) > 0) { // FIXME: ORACLE - nefunguje
				foreach ($result as $n => $row) {
					foreach ($row as $key => $value) {
						if (DB_DRIVER == 'oracle') {
							if ($value instanceof $blob) {
								$rs[$n][$key] = $value->load();
							}
							else {
								$rs[$n][$key] = is_string($value) ?  rtrim($value) : $value;
							}
						}
						else {
							$rs[$n][strtoupper($key)] = is_string($value) ?  rtrim($value) : $value;
						}
					}
				}
			//}
			//setMickaLog("FETCH ALL END", 'DEBUG', 'micka_lib_db._executeSql');
		}
		elseif ($fetch[0] == 'assoc') {
			$rs = setUpperColsName($result->fetchAssoc($fetch[1]));
		}
		elseif ($fetch[0] == 'pairs' && count($fetch) == 3) {
			if (DB_DRIVER == 'oracle') {
				$rs = $upper
					? setUpperColsName($result->fetchPairs(strtoupper($fetch[1]), strtoupper($fetch[2])))
					: $result->fetchPairs(strtoupper($fetch[1]), strtoupper($fetch[2]));
			}
			else {
				$rs = $upper ? setUpperColsName($result->fetchPairs($fetch[1],$fetch[2])) : $result->fetchPairs($fetch[1],$fetch[2]);
			}
		}
	}
	elseif(is_array($sql) && ($type == 'update' || $type == 'insert' || $type == 'delete')) {
		$rs = dibi::query($sql);
		setMickaLog(preg_replace("/\s{2,}/", ' ', dibi::$sql), 'SQL', 'micka_lib_db._executeSql.sql');
		//Debugger::log('[micka_lib_db._executeSql.update] ' . dibi::$sql, 'INFO');
	}
	elseif(is_array($sql) && $type == 'pxml') {
		//Debugger::log('[micka_lib_db._executeSql.pxml] ' . $sql[0], 'INFO');
		$rs = dibi::query($sql[0]);
		//Debugger::log('[micka_lib_db._executeSql.pxml] OK', 'INFO');
	}
	else {
		setMickaLog("unknow TYPE SQL", 'ERROR', 'micka_lib_db._executeSql');
	}
	unset($result);
	setMickaLog("", 'FUNCTION', 'micka_lib_db._executeSql.end');
	return $rs;
}

/*
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
 */



function setUpperColsName($in) {
	// NOTE: maximálně 3 zanoření pole
	// $in = array_change_key_case($in, CASE_UPPER);
	if (is_array($in) === FALSE) {
		return $in;
	}
	$arr = array();

	if (DB_DRIVER == 'oracle') {
		$blob = 'OCI-Lob';
		//setMickaLog("ORACLE", 'DEBUG', 'micka_lib_db.setUpperColsName');
		foreach($in as $k1 => $v1) {
			if((is_array($v1) || is_object($v1)) && ($v1 instanceof $blob) === FALSE) {
				foreach($v1 as $k2 => $v2) {
					if((is_array($v2) || is_object($v2)) && ($v2 instanceof $blob) === FALSE) {
						foreach($v2 as $k3 => $v3) {
							$arr[$k1][$k2][strtoupper($k3)] = is_string($v3) ? rtrim($v3) : $v3;
							$arr[$k1][$k2]['XMLDATA'] = ($v3 instanceof $blob) ? $v3->load() : rtrim($v3);
						}
					}
					else {
						$arr[$k1]['XMLDATA'] = ($v2 instanceof $blob) ? $v2->load() : rtrim($v2);
					}
				}
			}
			else {
				$arr[$k1] = ($v1 instanceof $blob) ? $v1->load() : rtrim($v1);
			}
		}
		$in = $arr;
	}
	else {
		foreach($in as $k1 => $v1) {
			if(is_array($v1) || is_object($v1)) {
				foreach($v1 as $k2 => $v2) {
					if(is_array($v2) || is_object($v2)) {
						foreach($v2 as $k3 => $v3) {
							$arr[$k1][$k2][strtoupper($k3)] = rtrim($v3);
						}
					}
					else {
						$arr[$k1][strtoupper($k2)] = rtrim($v2);
					}
				}
			}
			else {
				$arr[strtoupper($k1)] = rtrim($v1);
			}
		}
		$in = $arr;
	}
	return $in;
}

function setSqlLike ($field='', $value='', $znam='') {
	$rs = '';
	$replace = FALSE;
	if ($field != '' || $value != '') {
		if ($znam != '') {
			$znam = strtoupper($znam);
			if ($znam == 'LIKE' || $znam == '=' || $znam == '<' || $znam == '>' || $znam == '<=' || $znam == '>=') {
				$rs = "$field $znam $value";
			}
			else {
				$replace = TRUE;
			}
		}
		else {
			$replace = TRUE;
		}
	}
	if ($replace) {
		$rs = str_replace('@FIELD', $field, SQL_LIKE);
		$rs = str_replace('@VALUE', $value, $rs);
		$rs = str_replace('@UVALUE', mb_convert_case($value, MB_CASE_UPPER, MICKA_CHARSET), $rs);
	}
	if (DB_DRIVER == 'mssql' && $field == 'md_values.md_value') {
		$rs = str_replace('=', 'LIKE', $rs);
	}
	return $rs;
}

// Pro mssql
// Unicode data in a Unicode-only collation or ntext data cannot be sent to clients using DB-Library
// (such as ISQL) or ODBC version 3.7 or earlier.
function setNtext2Text($table, $col) {
	$pom = $table . $col;
	$rs = $table . $col;
	if (DB_DRIVER == 'mssql') {
		$rs = "CAST(CAST($pom AS NTEXT) COLLATE Czech_CI_AS AS TEXT) AS $col";
	}
	return $rs;
}


?>
