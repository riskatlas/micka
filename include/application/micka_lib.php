<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * Lib for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20140829
 *
 */

require PHPPRG_DIR . '/micka_lib_db.php';
require PHPPRG_DIR . '/micka_lib_date.php';
require PHPPRG_DIR . '/micka_lib_xml.php';

function setMickaLog($message, $level, $modul) {
	
}

/**
 * Přidání prázné hodnoty s indexem -1 na začátek pole,
 * používají SELECT Boxy ve formulářích
 *
 * @param array $arr
 * @return array
 */
function setRowZero($arr){
	if (is_array($arr)) {
		$pom[-1] = '';
		$rs = $pom + $arr;
	}
	else {
		$rs = $arr;
	}
	return $rs;
}

/**
 * Převod md_path na "pole"
 *
 * příklad
 * in: '0_0_44_0'
 * out: '[0][0][44][0]'
 *
 * @param string $md_path hodnota z tabulky md_values.md_path
 * @return string
 */
function getMdPath($md_path) {
	$rs = '';
	if (substr($md_path, strlen($md_path)-1) == '_') {
		// odstranění posledního podtržítka
		$md_path = substr($md_path, 0, strlen($md_path)-1);
	}
	$rs = str_replace("_", "][", $md_path);
	$rs = '[' . $rs . ']';
	return $rs;
}

/**
 * Určení počtu jazyků
 * 
 * @param string $langs hodnoty oddělené | (pipe)
 * @return number
 */
function getCountLang($langs) {
	return substr_count($langs,'|') + 1;
}

/**
 * pole se seznamem použitých jazyků
 *
 * @param mixed $md_langs seznam jazyků
 * @return array
 */
function getMdLangs($md_langs) {
	//setMickaLog("md_langs=$md_langs", 'DEBUG', 'micka_lib.getMdLangs.start');
	$rs = array();
	if (is_array($md_langs)) {
		$rs = $md_langs;
	}
	else {
		$rs = explode('|',$md_langs);
	}
	//setMickaLog($rs, 'DEBUG', 'micka_lib.getMdLangs.return');
	return $rs;
}

function getUniqueMdLangs($langs, $md_langs) {
	$langs = getMdLangs($langs);
	$md_langs = getMdLangs($md_langs);
	$md_langs = array_merge($md_langs, $langs);
	$md_langs = array_unique($md_langs);
	return implode('|', $md_langs);
}

function getMdOtherLangs($langs, $main_lang, $format='xml') {
	//setMickaLog("LANGS=$langs, main_lang=$main_lang, format=$format", 'DEBUG', 'micka_lib.getMdOtherLangs.start');
	$rs = '';
	$langs = getMdLangs($langs);
	if (count($langs) > 1 && $format == 'xml') {
		foreach ($langs as $value) {
			if ($value != $main_lang) {
				$rs .= "<lang>$value</lang>";
			}
		}
		if ($rs != '') {
			$rs = '<langs>' . $rs . '</langs>';
		}
	}
	//setMickaLog($rs, 'DEBUG', 'micka_lib.getMdOtherLangs.return');
	return $rs;
}

/**
 * Výběr textů z tabulky LABEL
 * 
 * @param string $table 
 * @param unknown_type $lang
 * @param unknown_type $type
 * @param unknown_type $join
 * @param unknown_type $order
 * @param unknown_type $key
 */
function getLabel($table, $micka_lang, $type, $join, $order, $fetch, $upper=TRUE) {
	$sql = array();
	if ($table == 'label') {
		array_push($sql, '
			SELECT label_text, label_help, label_type, label_join, lang 
			FROM label 
		');
	}
	elseif ($table == 'standard') {
		array_push($sql, '
			SELECT label.label_text, standard.md_standard
			FROM standard INNER JOIN label ON standard.md_standard = label.label_join
		');
	}
	elseif ($table == 'codelist') {
		array_push($sql, '
			SELECT label.label_text, codelist.codelist_domain, codelist.codelist_name
			FROM (label INNER JOIN codelist ON label.label_join = codelist.codelist_id)
				LEFT JOIN codelist_my ON codelist.codelist_id = codelist_my.codelist_id
		');
	}
	array_push($sql, 'WHERE lang=%s', $micka_lang); 
	if ($join > -1) {
		if ($table == 'codelist') {
			array_push($sql, 'AND codelist.el_id=%i AND codelist_my.is_vis=1', $join);
		}
		else {
			array_push($sql, 'AND label.label_join=%i', $join);
		}
	}
	if (is_array($type)) {
		if (count($type) == 1) {
			array_push($sql, 'AND label.label_type=%s', $type);
		}
		elseif (count($type) > 1) {
			array_push($sql, 'AND label.label_type IN %in', $type);
		} 
	}
	if ($table == 'standard') {
		array_push($sql, '
            AND standard.is_vis=1
		');
	}
	array_push($sql, 'ORDER BY %sql', $order);
	return _executeSql('select', $sql, $fetch, $upper);
}

/**
 * Získání dat z tabulky MD
 *
 * @param string $type 'uuid', 'recno', podle čeho se hledá
 * @param <type> $value hledaná hodnota
 * @param string $col pouze vybrané sloupce
 * @param <type> $fetch formát výsledku
 * @return array
 */
function getMdHeader($type, $value, $col='', $fetch=array('all','='), $table='md') {
	$rs = array();
	$sql = array();
	if ($col == '') {
		$col = 'recno, uuid, md_standard, lang, data_type, create_user, edit_group, view_group, x1 ,x2, y1, y2, title';
	}
	if ($col == '*') {
		array_push($sql, "SELECT * FROM $table");
	}
	else {
		array_push($sql, "SELECT $col FROM $table");
	}
	if ($type == 'recno' && $value > -1) {
		array_push($sql, 'WHERE recno=%i', $value);
	}
	if ($type == 'uuid' && $value != '') {
		array_push($sql, 'WHERE uuid=%s', $value);
	}
	if (count($sql) > 1) {
		$rs = _executeSql('select', $sql, $fetch);
	}
	return $rs;
}

function getMdValues($recno, $mds, $micka_lang, $type_id=NULL, $id=NULL) {
	if ($recno == '' || $mds == '' || $micka_lang == '') {
		return FALSE;
	}
	$rs = array();
	$sql = array();
	$mds_tmp = $mds;
	if ($mds == 10) {
		$mds_tmp = 0;
	}
	array_push($sql, "
		SELECT " . setNtext2Text('md_values.', 'md_value') . ",
					md_values.md_id,
					md_values.md_path,
					md_values.lang,
					elements.form_code,
					elements.el_id,
					elements.from_codelist
		FROM (elements RIGHT JOIN tree ON elements.el_id = tree.el_id) RIGHT JOIN md_values ON tree.md_id = md_values.md_id
		WHERE md_values.recno=%i AND tree.md_id=md_values.md_id AND tree.md_standard=%i
	", $recno, $mds_tmp);
	if ($micka_lang != 'xxx') {
			array_push($sql, "AND (md_values.lang='xxx' OR md_values.lang=%s)", $micka_lang);
	}
	if ($type_id == 'profil' && $id > 0) {
		array_push($sql, "AND md_id IN (SELECT md_id FROM profil WHERE profil_id=%i)", $id);
	}
	if ($type_id == 'package' && $id > 0) {
		array_push($sql, "AND md_values.package_id=%i)", $id);
	}
	array_push($sql, 'ORDER BY md_values.md_path, md_values.lang');
	$result = _executeSql('select', $sql, array('all'));
	if ($result !== FALSE) {
		if (count($result) > 0) {
			foreach ($result as $n => $row) {
				$value = $row['MD_VALUE'];
				if ($row['FORM_CODE'] == 'D' && $micka_lang == 'cze') {
					$value = dateIso2Cz($value);
				}
				$md_path = getMdPath($row['MD_PATH']);
				if ($type_id == 'no_lang') {
					$eval_text = '$rs' . $md_path . "=\"" . $value . "\";";
				}
				else {
					$eval_text = '$rs' . $md_path . "['" . $row['LANG'] . "']" . "=\"" . $value . "\";";
				}
				eval($eval_text);
			}
		}
	}
	return $rs;
}

function getMickaAdmin($ak_group, $ak_action=NULL, $fetch=array('single')) {
	$rs = FALSE;
	$sql = array();
	array_push($sql, "SELECT ak_value, ak_date, ak_group, ak_action, ak_note FROM micka_admin");
	if ($ak_group != '' && $ak_action == NULL) {
		array_push($sql, "WHERE ak_group=%s ORDER BY ak_date DESC", $ak_group);
	}
	elseif ($ak_group != '' && $ak_action != '') {
		array_push($sql, "WHERE ak_group=%s AND ak_action=%s ORDER BY ak_date DESC", $ak_group, $ak_action);
	}
	if (count($sql)>1) {
		$rs = _executeSql('select', $sql, $fetch);
	}
	return $rs;
}

function getDbVersion() {
	$rs = 0;
	$pom = getMickaAdmin('DB'); // Aktuální verze databáze
	if (settype($pom, 'float') && $pom > 0) {
		$rs = $pom;
	}
	return $rs;
}

function getDbHistory() {
	$rs = array();
	// pouze administrator
	if (canAction('*')) {
		$rs = getMickaAdmin('DB', NULL, array('all')); // Aktuální verze databáze
	}
	return $rs;
}

function getPrgHistory() {
	$rs = array();
	// pouze administrator
	if (canAction('*')) {
		$rs = getMickaAdmin('PRG', NULL, array('all')); // Aktuální verze databáze
}
	return $rs;
}

function getLabelAllAP() {
	return getLabel('label', MICKA_LANG, array('AP'), -1, 'label.label_text', array('pairs','label_join','label_text'));
}

function getSelectCategory() {
	return setRowZero(getLabel('codelist', MICKA_LANG, array('CL'), 410, 'label.label_text', array('pairs','codelist_name','label_text'), FALSE));
}

function getSelectStandards() {
	return setRowZero(getLabel('standard', MICKA_LANG, array('SD'), -1, 'standard.md_standard_order', array('pairs','md_standard','label_text')));
}

function getRadioStandards($mode) {
	$rs = getLabel('standard', MICKA_LANG, array('SD'), -1, 'standard.md_standard_order', array('pairs','md_standard','label_text'));
	$rs[99] = getLabel('label', MICKA_LANG, array('SD'), 99, 'label_text', array('single'));
	// NOTE příprava na acl
	if ($mode == 'acl') {
		$resource = 'MDS';
		$pom = array();
		if (isset($_SESSION['micka']['acl'][$resource])
				&& is_array($_SESSION['micka']['acl'][$resource])
				&& count($_SESSION['micka']['acl'][$resource]) > 0) {
			foreach ($_SESSION['micka']['acl'][$resource] as $key => $value) {
				if (strpos($value, 'w') === FALSE) {
				}
				else {
					if ($key == 'MD') {
						$pom[0] = $rs[0];
					}
					elseif ($key == 'MS') {
						$pom[10] = $rs[10];
					}
					elseif ($key == 'DC') {
						$pom[1] = $rs[1];
					}
					elseif ($key == 'FC') {
						$pom[2] = $rs[2];
					}
				}
			}
			$pom[99] = $rs[99];
			$rs = $pom;
		}
	}
	return $rs;
}

function getSelectMdLangs() {
	return setRowZero(getLabel('codelist', MICKA_LANG, array('CL'), 390, 'label.label_text', array('pairs','codelist_domain','label_text'), FALSE));
}

function getRadioMdLangs() {
	return getLabel('codelist', MICKA_LANG, array('CL'), 390, 'label.label_text', array('pairs','codelist_domain','label_text'), FALSE);
}

function getLabelButton() {
	return getLabel('label', MICKA_LANG, array('BT'), -1, 'label.label_text', array('pairs','label_join','label_text'));
}

function getLabelStandard($mds) {
	return getLabel('standard', MICKA_LANG, array('SD'), $mds, 'standard.md_standard_order', array('single'));
}

function getLabelProfil($profil_id) {
	return getLabel('label', MICKA_LANG, array('PN'), $profil_id, 'label.label_text', array('single'));
}

function getLabelEl($mds) {
	$rs = FALSE;
	$sql = array();
	if ($mds == 10) {
		$mds = 0;
	}
	$where_in = '';
	if ($mds == 0) {
		$where_in = ' AND tree.md_id IN (4,5061,84,4919,82,127,80,5027,122,14,494,497,498,499,500,495,5132,5133,5134,5135,5136,
									490,5,106,8,96,97,79,361,364,51,107,312,311,5063,88,4920)';
	}
	array_push($sql, "SELECT tree.md_id, label.label_text, label.label_help
		FROM (label INNER JOIN elements ON label.label_join = elements.el_id) INNER JOIN tree ON elements.el_id = tree.el_id
    WHERE label.lang=%s AND label.label_type='EL' AND tree.md_standard=%i $where_in",
		MICKA_LANG, $mds);
	$result = _executeSql('select', $sql, array('all'));
	foreach ($result as $row) {
		$pom = $row['MD_ID'];
		$rs[$pom]['label'] = $row['LABEL_TEXT'];
		$rs[$pom]['help'] = $row['LABEL_HELP'];
	}
	//Debug::dump($rs);
	return $rs;
}

function getNewMdLangs($md_lang) {
	$rs = array();
	$langs = getRadioMdLangs(MICKA_LANG);
	foreach ($langs as $key => $value) {
		if ($key != $md_lang) {
			$rs[$key] = $value;
		}
	}
	return $rs;
}

function getMsGroups($type, $group=NULL) {
	if ($type == 'is_set' && $group != NULL) {
		$pom = '|' . $_SESSION["ms_groups"] . '|';
		$pom = str_replace(' ', '|', $pom);
		$rs = strpos($pom, '|' . trim($group) . '|');
		if ($rs !== FALSE) {
			$rs = TRUE;
		}
	} elseif ($type == 'get_groups') {
		$rs = array();
		if (isset($_SESSION['group_names']) && is_array($_SESSION['group_names']) && count($_SESSION['group_names']) > 0) {
			$rs = $_SESSION['group_names'];
		} else {
			$pom = explode(" ", $_SESSION["ms_groups"]);
			natcasesort($pom);
			foreach($pom as $ms_group) {
				$rs[$ms_group] = $ms_group;
			}
		}
        // přidání použitých skupin u záznamu
        if ($group != NULL && $group != '') {
            $pom = explode("|", $group);
            foreach($pom as $key => $value) {
                if ($value != '' && array_key_exists($value, $rs) === FALSE) {
                    $rs[$value] = $value;
                }
            }
        }
	} else {
		$rs = FALSE;
	}
	return $rs;
}

function getExistGroup($group) {
	$rs = FALSE;
	if ($group == '') {
		return $rs;
	}
	if (strpos(AUTHLIB_FILE, 'prihlib_ibm.php') !== FALSE) {
		$rs = groupExist($group);
	}
	else {
		$rs = getMsGroups('is_set', $group);
	}
	return $rs;
}

function getMdGroup($user) {
	$rs = FALSE;
	$pom = explode(" ", $_SESSION["ms_groups"]);
	foreach($pom as $group) {
		$rs = $group;
		if ($group == $user) {
			break;
		}
	}
	return $rs;
}
function getDefaultGroup($group, $defGroup) {
    $rs = $defGroup;
    if (getExistGroup($group) === TRUE) {
        $rs = $group;
    }
    return $rs;
}

function getMdRight($type, $user, $data_type, $create_user, $edit_group, $view_group) {
	setMickaLog("type=$type, user=$user, data_type=$data_type, create=$create_user, edit=$edit_group, view=$view_group", 'ERROR', 'getMdRight.start');
	$rs = FALSE;
	if (canAction('*')) {
		// root - superuživatel, správce projektu, může vše
		setMickaLog('TRUE', 'ERROR', 'getMdRight.root');
		return TRUE;
	}
	else {
		setMickaLog('FALSE', 'ERROR', 'getMdRight.root');
	}
	if ($type == 'edit' && $user != 'guest') {
		if (getMsGroups('is_set', $edit_group) || $user == $create_user) {
			return TRUE;
		}
	}
	if ($type == 'view') {
		if ($user == $create_user) {
			return TRUE;
		}
		elseif (getMsGroups('is_set', $edit_group)) {
			return TRUE;
		}
		elseif (getMsGroups('is_set', $view_group) && $data_type > -1) {
			return TRUE;
		}
		elseif ($data_type > 0) {
			return TRUE;
		}
	}
	return $rs;
}

function getWmsList($rs) {
	if ($rs != '') {
		if (substr_count($rs,'WMS') == 0) {
			$rs = '';
		}
	}
	return $rs;
}

function getUuid() {
	include_once(PHPLIB_DIR . "/Uuid/Uuid.php");
	$uuid = new UUID();
	$uuid->generate();
	return $uuid->toRFC4122String();
}

function getHsWms($micka_lang, $hs_wms) {
	return array_key_exists($micka_lang, $hs_wms) ? $hs_wms[$micka_lang] : $hs_wms['eng'];
}

function getLabelCodeList($value, $el_id) {
	$rs = $value;
	if ($value != '' && $el_id != '') {
		$sql = array();
		array_push($sql, "
			SELECT label.label_text
			FROM codelist INNER JOIN label ON codelist.codelist_id = label.label_join
			WHERE codelist.el_id=%i AND label.label_type='CL' AND label.lang=%s AND codelist.codelist_name=%s
		", $el_id, MICKA_LANG, $value);
		$result = _executeSql('select', $sql, array('single'));
		if ($result != '') {
			$rs = $result;
		}
	}
	return $rs;
}

function getHyperLink($hle) {
	$hle = trim($hle);
	$rs = $hle;
	/*
	if (preg_match('[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}', $hle)) {
		$rs = '<a href="mailto:' . $hle . '">' . $hle . '</a>';
	}
	else {
		if (preg_match('(http|https|ftp)\://',$hle) == 1) {
			$rs = '<a href="' . $hle;
			if (isset($_SESSION['GlogSessionId'])) {
				if(strpos($rs, '?')) $rs .= '&'; else $rs .= '?';
				$rs .= 'gsid=' . $_SESSION['GlogSessionId'];
			}
			$rs .= '" target="_blank">' . $hle . '</a>';
		}
	}
	*/
	return $rs;
}

function isUuidExist($uuid, $harvest=TRUE) {
	if ($harvest === FALSE) {
		return FALSE;
	}
	$rs = FALSE;
	$sql = array();
	if ($uuid != '') {
		array_push($sql, "SELECT COUNT(recno) AS soucet FROM md WHERE uuid=%s", $uuid);
		$result = _executeSql('select', $sql, array('single'));
		if ($result > 0) {
			$rs = TRUE;
		}
	}
	return $rs;
}

function getMdProfils($micka_lang, $mds=0) {
	$sql = array();
	array_push($sql, "
		SELECT profil_id, CASE WHEN label_text IS NULL THEN profil_name ELSE label_text END AS label_text
		FROM profil_names z LEFT JOIN (SELECT label_join,label_text FROM label WHERE lang=%s AND label_type='PN') s
		ON z.profil_id=s.label_join
		WHERE md_standard=%i AND is_vis=1
	", $micka_lang, $mds);
	//array_push($sql, "AND edit_lite_template IS NULL");
	array_push($sql, "ORDER BY profil_order");
	return _executeSql('select', $sql, array('pairs', 'profil_id', 'label_text'));
}

function getMdPackages($micka_lang, $mds, $profil, $pairs=FALSE) {
	$rs = array();
	if ($micka_lang === '' || $mds === '' || $profil === '') {
		Debugger::log('[micka_lib.getMdPackages] ' . "LANG=$micka_lang, MDS=$mds, PROFIL=$profil", 'ERROR');
		return $rs;
	}
	$is_packages = 0;
	$sql = array();
	if ($mds == 0 || $mds == 10) {
		array_push($sql, '
			SELECT is_packages FROM profil_names WHERE md_standard=%i AND profil_id=%i
		', $mds, $profil);
		$is_packages = _executeSql('select', $sql, array('single'));
	} else {
		return $rs;
	}
	if ($is_packages == 0) {
		return $rs;
	}
	if ($mds == 10) {
		$mds = 0;
	}
	$sql = array();
	array_push($sql, "
		SELECT packages.package_id, label.label_text
    FROM packages INNER JOIN label ON packages.package_id=label.label_join
		WHERE label.label_type='MB' AND packages.md_standard=%i AND label.lang=%s
		ORDER BY packages.package_order
	", $mds, $micka_lang);
	if ($pairs) {
		$rs = _executeSql('select', $sql, array('pairs', 'package_id', 'label_text'));
	} else {
		$rs = _executeSql('select', $sql, array('all'));
	}
	return $rs;
}

function getProfilExists($mds, $profil) {
	setMickaLog("BEGIN: mds=$mds, profil=$profil, START_PROFIL=" . START_PROFIL, 'DEBUG', 'micka_lib.getProfilExists');
	$rs = array();
	$rs['akce'] = 'micka';
	$rs['template'] = '';

///*
	if ($profil < 0 || $profil === '') {
		$profil = START_PROFIL;
	}
 //*/
/*
	if (isset($_SESSION['micka']['edit']['mode']) && $_SESSION['micka']['edit']['mode'] == 'window') {
		$rs['profil'] = WINDOW_PROFIL;
		if (isset($_SESSION['micka']['edit']['profil']) && $_SESSION['micka']['edit']['profil'] > 0) {
			$profil = $_SESSION['micka']['edit']['profil'];
			if ($mds == 0 && $profil == 0) {
				$profil = WINDOW_PROFIL;
			}
			elseif ($mds == 10 && $profil == 100) {
				$profil = WINDOW_PROFIL;
			}
		}
	}
	else {
		$rs['profil'] = START_PROFIL;
	}
*/
	if ($mds == 10 && $profil < 100) {
		$profil = $profil + 100;
	}
	$rs['profil'] = $profil;
	$sql = array();
	array_push($sql, "
		SELECT profil_id, edit_lite_template FROM profil_names
		WHERE md_standard=%i AND profil_id=%i AND is_vis=1
	", $mds, $profil);
	$rs_profil = _executeSql('select', $sql, array('all'));
	if (is_array($rs_profil) && count($rs_profil) > 0) {
		if ($rs_profil[0]['EDIT_LITE_TEMPLATE'] == '') {
			$rs['profil'] = $profil;
		}
		if ($rs_profil[0]['EDIT_LITE_TEMPLATE'] != '') {
			$rs['profil'] = $profil;
			$rs['akce'] = 'lite';
			$rs['template'] = $rs_profil[0]['EDIT_LITE_TEMPLATE'];
		}
	}
	else {
		setMickaLog("RS_PROFIL: profil not found", 'ERROR', 'micka_lib.getProfilExists');
	}
	setMickaLog("RETURN: profil=" . $rs['profil'] . ", akce=" . $rs['akce'] . ", template=" . $rs['template'] , 'DEBUG', 'micka_lib.getProfilExists');
	return $rs;
}

function getProfilPackages($mds, $profil, $block) {
	setMickaLog("mds=$mds, profil=$profil, package=$block", 'DEBUG', 'micka_lib.getProfilPackages');
	//echo "getProfilPackages(mds=$mds, profil=$profil, package=$block)";
	$rs = array();
	$rs['profil'] = -1;
	$rs['package'] = -1;
	$yes_profil = FALSE;
	switch ($mds) {
		case 0:
			if ($profil > 0 && $profil < 100) {
				$yes_profil = TRUE;
			}
		case 10:
			if ($mds == 10 && $profil < 100) {
				$profil = $profil + 100;
			}
			if ($mds == 10 && $profil > 100) {
				$yes_profil = TRUE;
			}
			$packages = getMdPackages(MICKA_LANG, $mds, $profil);
			if (count($packages) > 0 && $block > -1) {
				if ($yes_profil === TRUE) {
					$rs['profil'] = $profil;
					$rs['package'] = $block;
				}
				else {
					$rs['package'] = $block;
				}
			}
			else {
					$rs['profil'] = $profil;
			}
			break;
	}
	return $rs;
}

function getIsInspirePackage($mds, $profil) {
	$rs = FALSE;
	$sql = array();
	if ($mds != '' && $profil != '') {
		array_push($sql, "
			SELECT is_inspire FROM profil_names WHERE md_standard=%i AND profil_id=%i
		", $mds, $profil);
		if (_executeSql('select', $sql, array('single')) == 1) {
			$rs = TRUE;
		}
	}
	return $rs;
}

function getMdIdFromMdPath($md_path) {
	$rs = '';
	$md_path = substr($md_path,0,strlen($md_path)-1);
	$pom = explode('_',$md_path);
	if (count($pom) > 1) {
		$idx = count($pom) - 2;
		$rs = $pom[$idx];
	}
	if ($rs == '') {
		setMickaLog("BAD md_path=$md_path", 'ERROR', 'micka_lib.getMdIdFromMdPath');
	}
	return $rs;
}

function mdTranslation($lang, $terms='') {
	$sql = array();
	array_push($sql, "
		SELECT el_name, label_text
		FROM elements LEFT JOIN label ON (el_id=label_join AND lang=%s AND label_type='EL')
	", $lang);
	if ($terms) {
		$s = "";
		$termlist = explode(',',$terms);
		foreach($termlist as $term) {
			$s .= ",'".trim($term)."'";
		}
		array_push($sql, "WHERE el_name IN (%sql)", substr($s,1));
	}
	$result = _executeSql('select', $sql, array('all'));
	$list = array();
	if (is_array($result) && count($result) > 0) {
		foreach ($result as $row) {
			$list[trim($row["EL_NAME"])] = trim($row["LABEL_TEXT"]);
		}
	}
	return $list;
}

function labelTranslation($lang, $term) {
	$rs = $term;
	if ($term == 'Record exists, import cancelled. No update rights.') {
		if ($lang == 'cze') $rs = 'Záznam existuje, import zrušen. Nemáte právo aktualizovat.';
	}
	elseif ($term == 'The metadata record already exists. It will be replaced with the new one when you save it.') {
		if ($lang == 'cze') $rs = 'Metadatový záznam již existuje. Bude nahrazen novým, jestliže jej uložíte.';
	}
	elseif ($term == 'Record exists, import cancelled.') {
		if ($lang == 'cze') $rs = 'Záznam existuje, import zrušen.';
	}
	elseif ($term == 'unknow error in MD') {
		if ($lang == 'cze') $rs = 'neznámá chyba v MD';
	}
	elseif ($term == 'guest not right') {
		if ($lang == 'cze') $rs = 'Tento uživatel nemá potřebná oprávnění na tuto akci.';
	}
	elseif ($term == 'ERROR (path)') {
		if ($lang == 'cze') $rs = 'Chyba';
	}
	elseif ($term == 'No update rights.') {
		if ($lang == 'cze') $rs = 'Nemáte práva aktualizovat.';
	}
	elseif ($term == 'No edit rights.') {
		if ($lang == 'cze') $rs = 'Nemáte práva editovat.';
	}
	elseif ($term == 'Bad number format.') {
		if ($lang == 'cze') $rs = 'Špatný formát čísla.';
	}
	elseif ($term == 'Input is not complete.') {
		if ($lang == 'cze') $rs = 'Vstupní data nejsou úplná.';
	}
	elseif ($term == 'Input is emty.') {
		if ($lang == 'cze') $rs = 'Prázdná vstuní data.';
	}
	elseif ($term == 'Bad metadata document format!') {
		if ($lang == 'cze') $rs = 'Špatný formát metadat!';
	}
	elseif ($term == 'Bad xml format!') {
		if ($lang == 'cze') $rs = 'Špatný formát XML!';
	}
	elseif ($term == 'The record will be added as new.') {
		if ($lang == 'cze') $rs = 'Záznam bude uložen jako nový.';
	}
	elseif ($term == 'Record does not exist.') {
		if ($lang == 'cze') $rs = 'Záznam neexistuje.';
	}
	//setMickaLog("$lang=$rs", 'DEBUG', 'micka_lib.labelTranslation');
	return $rs;
}

function gpc_addslashes($str) {
	//	(get_magic_quotes_gpc() ? $str : addslashes($str));
	$str = addslashes($str);
	$str = str_replace('\\\'',"'", $str);
	$str = str_replace('$','\$', $str);
	return $str;
}

function getPaginator($sql, $limit_find, $page_number=1) {
	$rs = array();
	$rs['records'] = 0;
	$rs['pages'] = 0;
	if ($limit_find < 10) {
		$limit_find = 20;
	}
	if ($sql != '') {
		$records = _executeSql('select', array($sql), array('single'));
		if ($records > 0) {
			$rs['records'] = $records;
			$rs['pages'] = Ceil($records/$limit_find);
		}
	}
	if ($rs['records'] > 0) {
		// vypocet pocatecni a koncove stranky (snazime se vypsat vzdy 10 odkazu)
		$rs['start_page'] = $page_number-5;
		$rs['end_page'] = $page_number+5;

		// oprava nekorektnich hodnot (zaporne nebo prilis velke)
		if($rs['start_page'] < 1) $rs['end_page'] += Abs($rs['start_page']) + 1;
		if($rs['end_page'] > $rs['pages']) {
			$rs['start_page'] = $rs['start_page'] - ($rs['end_page']-$rs['pages']);
			$rs['end_page'] = $rs['pages'];
		}
		if($rs['start_page'] < 1) {
			$rs['start_page'] = 1;
		}
		for($x = $rs['start_page'];$x <= $rs['end_page'];$x++) {
			$rs['view_pages'][] = $x;
		}
		if ($page_number > $rs['end_page']) {
			$rs['page_number'] = $rs['end_page'];
			setSessionMickaSearchPage($rs['page_number']);
		}
		else {
			$rs['page_number'] = $page_number;
		}
	}
	return $rs;
}

function my_print_r($arr, $label='') {
    $fileName = CSW_LOG . '/my_print_r.log';
    $fh = fopen($fileName, 'at');
    if (is_array($arr)) {
        fwrite($fh, $label . "\n");
        fwrite($fh, print_r($arr, TRUE));
    } else {
        fwrite($fh, '[' . $label . '] ' . $arr . "\n");
    }
    fclose($fh);
}

function beforeSaveRecord($data) {
	if (is_array($data)) {
		// odstranit přidané údaje
		if (array_key_exists('ak', $data)) unset($data['ak']);
		if (array_key_exists('w', $data)) unset($data['w']);
		if (array_key_exists('iframe', $data)) unset($data['iframe']);
		if (array_key_exists('block', $data)) unset($data['block']);
		if (array_key_exists('nextblock', $data)) unset($data['nextblock']);
		if (array_key_exists('profil', $data)) unset($data['profil']);
		if (array_key_exists('nextprofil', $data)) unset($data['nextprofil']);
		if (array_key_exists('recno', $data)) unset($data['recno']);
		if (array_key_exists('uuid', $data)) unset($data['uuid']);
		if (array_key_exists('mds', $data)) unset($data['mds']);
		if (array_key_exists('public', $data)) unset($data['public']);
		if (array_key_exists('ende', $data)) unset($data['ende']);
	}
	return $data;
}

function getSortBy($in='', $ret='array') {
	if ($in != '') {
		$sort_by = $in;
	}
	else {
		$sort_by = isset($_SESSION['micka']['search']['sort_by']) && $_SESSION['micka']['search']['sort_by'] != ''
			? $sort_by = $_SESSION['micka']['search']['sort_by']
			: $sort_by = SORT_BY;
	}
	$pom = explode(',', trim($sort_by));
	$pom0 = isset($pom[0]) && $pom[0] != '' ? $pom[0] : 'recno';
	$pom1 = isset($pom[1]) && $pom[1] != '' ? $pom[1] : 'ASC';
	$rs = array();
	if ($pom0 == 'date') {
		$pom0 = 'last_update_date';
	}
	switch ($pom0) {
		case 'recno':
		case 'title':
		case 'last_update_date':
		case 'bbox':
			$rs[0] = $pom0;
			break;
		default:
			$pom0 = 'recno';
			break;
	}
	$rs[1] = $pom1 == 'ASC' || $pom1 == 'DESC' ? $pom1 : 'ASC';
	if ($ret == 'string') {
		return $rs[0] . ',' . $rs[1];
	}
	return $rs;
}

function setFlashMessage($message, $type = 'info') {
	if ($message != '') {
		$_SESSION['micka']['flash'][] = array('type' => $type, 'message' => $message);
	}
}

function getFlashMessage() {
	$rs = isset($_SESSION['micka']['flash']) && count($_SESSION['micka']['flash']) > 0
					? $_SESSION['micka']['flash']
					: array();
	unset($_SESSION['micka']['flash']);
	return $rs;
}