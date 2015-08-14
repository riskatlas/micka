<?php
/**
 * Funkce pro hledání a obsluhu $_SESSION['micka']['search']
 * starý způsob, používá už pouze ak=md_search
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20140530
 * @authors		 DZ
 */

function getFoundsRecords($post, $user) {
	// ošetření vstupu XSS
	if (is_array($post)) {
		foreach ($post as $key => $value) {
			$post[$key] = htmlspecialchars($value);
		}
	}
	$rs = array();
	$rs['paginator']['records'] = 0;
	$restore = FALSE; // zjišťuje současný stav	hledání podle session a jeho zobrazení
	$exist_where = FALSE;
	$page_number = '';
	$form_view = -1;
	$export = FALSE;

	// řazení výsledku
	if (isset($post['sort0']) && isset($post['sort1'])) {
		setSortBy(trim($post['sort0']) . ',' . trim($post['sort1']));
	}
	
	// kontrola módů hledání
	if (isset($post['mode']) && ($post['mode'] == 'normal' || $post['mode'] == 'myrecords')) {
		$_SESSION['micka']['search']['mode'] = $post['mode'];
	}
	elseif (isset($post['mode']) && $post['mode'] == 'master') {
		$_SESSION['micka']['search']['mode'] = $post['mode'];
	}
	else {
		// nepodporovaný mód, vrátí nenalezeno
		if ($_SESSION['micka']['search']['mode'] == '') {
			return $rs;
		}
		else {
			$restore = TRUE;
		}
	}

	if (isset($_SESSION['micka']['search']['where']) && $_SESSION['micka']['search']['where'] != '') {
		$exist_where = true;
	}
	if ($_SESSION['micka']['search']['mode'] == 'master') {
		$fc = isset($post['fc']) ? $post['fc'] : ''; // feature katalog
		$form_view = isset($post['fv']) ? $post['fv'] : ''; // prvotní zobrazení, pokud nezadáno tak se hledá, jinak se jen zobrazí formulář
		if ($form_view == '') {
			$form_view = -1;
		}
		else {
			$form_view = 1;
		}
	}
	if ($restore) {
		if ($_SESSION['micka']['search']['mode'] == 'master') {
			$page_number = isset($_SESSION['micka']['search_m']['page']) ? $_SESSION['micka']['search_m']['page'] : 1;
		}
		else {
			$page_number = isset($_SESSION['micka']['search']['page']) ? $_SESSION['micka']['search']['page'] : 1;
		}
	}
	else {
		if (isset($post['pg'])) {
			$page_number = $post['pg'];
		}
	}
	if ($page_number == '') {
		$page_number = 1;
		setSessionMickaSearch($post);
	}
	// sestaveni dotazu pro hledani
	if ($_SESSION['micka']['search']['mode'] == 'myrecords') {
		$select_all = getSearchUser($user);
		$export = TRUE;
	}
	else {
		if ($_SESSION['micka']['search']['mode'] == 'normal' && $exist_where == FALSE) {
			$select_all = getSearchArrayNormal();
		}
		elseif ($form_view == -1 && $_SESSION['micka']['search']['mode'] == 'master') {
			$select_all = getSearchArrayMaster($fc);
		}
		if ($_SESSION['micka']['search']['mode'] == 'normal' && $exist_where === TRUE) {
			$select_all = $_SESSION['micka']['search']['where'];
			$page_number = 1;
			$export = TRUE;
		}
		elseif (($form_view == -1 && $_SESSION['micka']['search']['mode'] == 'master')
						|| $_SESSION['micka']['search']['mode'] == 'normal') {
			$export = TRUE;
		}
	}
	if ($export) {
		// získání dat
		require_once PHPPRG_DIR . '/MdExport.php';
		$ofs = ($page_number-1) * MAXRECORDS;
		if ($ofs > 0) {
			$ofs++;
		}
		$data = new MdExport(MICKA_USER, $ofs, MAXRECORDS, getSortBy($in='', $ret='string'));
		$data->page_number = $page_number;
		$data->only_public = FALSE;
		$data->xml_from = 'summary';
		$rs = $data->getData($select_all);
		if ($rs['paginator']['records'] > 0) {
				// ulozeni stranky pro navrat pri editaci
			if (isset($_SESSION['micka']['search']['mode']) && $_SESSION['micka']['search']['mode'] == 'master') {
				$_SESSION['micka']['search_m']['page'] = $page_number;
			}
			else {
				$_SESSION['micka']['search']['page'] = $page_number;
			}
		}
	}
	//$rs['data']
	//$rs['paginator']
	//$rs['akce']
	return $rs;
}

function getSearchUser($user) {
	$rs = array();
	$rs[] = "_CREATE_USER_ = '$user'";
	return $rs;
}

function getSearchText($text, $phraze, $keyword) {
	$rs = array();
	if ($text == '') {
		return $rs;
	}
	// Přesné znění, ignoruje slovo kratší než 3 znaky
	if ($phraze == 1 && strlen($text) > 2) {
		if ($keyword == 1) { // V textu
			$rs[] = "% LIKE '%$text%'";
		}
		else { // Klicova slova
			$rs[] = "@keyword = '$text'";
		}
	}
	else {
		$pom = array();
		$pom = explode(' ', $text);
		$i = 0;
		// odstraní se slova kratší než 3 znaky a omezí pouze na 5 slov
		foreach ($pom as $word) {
			if ($i > 5) {
				break;
			}
			if (strlen($word) > 2) {
				$i++;
				// Nektere slovo
				if ($phraze == 2 && $keyword == 1) { // V textu
					if (count($rs) > 0) {
						$rs[] = "OR";
					}
					$rs[] = "% LIKE '%$word%'";
				}
				if ($phraze == 2 && $keyword != 1) { // Klicova slova
					if (count($rs) > 0) {
						$rs[] = "OR";
					}
					$rs[] = "@keyword = '$word'";
				}
				// Vsechna slova
				if ($phraze == 3 && $keyword == 1) { // V textu
					if (count($rs) > 0) {
						$rs[] = "AND";
					}
					$rs[] = "% LIKE '%$word%'";
				}
				if ($phraze == 3 && $keyword != 1) { // Klicova slova
					if (count($rs) > 0) {
						$rs[] = "AND";
					}
					else {
						$rs[] = "@keyword = '$word'";
					}
				}
			}
		}
	}
	//setMickaLog($rs, 'DEBUG', 'micka_lib.php (getSearchText)');
	return $rs;
}

function getSearchCategory($category) {
	$rs = array();
	if ($category != -1 && $category != '') {
		$rs[] = "@topic = '$category'";
	}
	return $rs;
}

function getSearchBBox($x1, $y1, $x2, $y2, $inside) {
	$rs = array();
	if ($x1 != 0 && $y1 != 0 && $x2 != 0 && $y2 != 0) {
		if ($inside == 'on') {
			//$rs[] = "_BBOX_1 = '$x1 $y1 $x2 $y2'";
			$rs[] = "_BBOX_ = '$x1 $y1 $x2 $y2 1'";
		}
		else {
			//$rs[] = "_BBOX_0 = '$x1 $y1 $x2 $y2'";
			$rs[] = "_BBOX_ = '$x1 $y1 $x2 $y2 0'";
		}
	}
	return $rs;
}

function getSearchMDS($mds) {
	$rs = array();
	if ($mds == '') {
		$mds = -1;
	}
	if ($mds > -1) {
		$rs[] = "_MDS_=$mds";
	}
	return $rs;
}

function getSearchLang($lang) {
	$rs = array();
	if (strlen($lang) == 3) {
		$rs[] = "_LANG_ = '$lang'";
	}
	return $rs;
}

function getSearchRange($range_b,$range_e) {
	$rs = array();
	if(strpos($range_b, '.')) {
		$range_b = dateCz2Iso($range_b);
	}
	if(strpos($range_e, '.')) {
		$range_e = dateCz2Iso($range_e);
	}
	$pom = timeWindow('',$range_b,$range_e);
	if ($pom[0] != '0000-00-00' && $pom[1] != '0000-00-00') {
		$rs[] = "_DATEB_ >= '$pom[0]'";
		$rs[] = "AND";
		$rs[] = "_DATEE_ <= '$pom[1]'";
	}
	return $rs;
}

function getSearchAll($select_hledat, $select_category, $select_bbox, $select_mds, $select_lang, $select_range) {
	$rs = array();
	if (count($select_hledat) == 1) {
		$rs[] = $select_hledat[0];
	}
	elseif (count($select_hledat) > 1) {
		$rs[] = $select_hledat;
	}
	if (count($select_category) == 1) {
		if (count($rs) > 0) {
			$rs[] = "AND";
		}
		$rs[] = $select_category[0];
	}
	if (count($select_bbox) == 1) {
		if (count($rs) > 0) {
			$rs[] = "AND";
		}
		$rs[] = $select_bbox[0];
	}
	if (count($select_mds) == 1) {
		if (count($rs) > 0) {
			$rs[] = "AND";
		}
		$rs[] = $select_mds[0];
	}
	if (count($select_lang) == 1) {
		if (count($rs) > 0) {
			$rs[] = "AND";
		}
		$rs[] = $select_lang[0];
//		$rs = "{" . $select_lang . "}";
//		$rs = str_replace("_LANG_", "_LANGUAGE_", $rs);
	}
	if (count($select_range) > 0) {
		if (count($rs) > 0) {
			$rs[] = "AND";
		}
		$rs[] = $select_range;
	}
	return $rs;
}

function getSearchArrayNormal() {
	$search = getSessionMickaSearch();
	$select_hledat = getSearchText($search['te'], $search['ph'], $search['kw']);
	$select_category = getSearchCategory($search['ca']);
	$select_bbox = array();
	$select_mds = array();
	$select_lang = array();
	$select_range = array();
	/*
	$select_bbox = getSearchBBox($search['x1'], $search['y1'], $search['x2'], $search['y2'], $search['in']);
	$select_mds = getSearchMDS($search['st']);
	$select_lang = getSearchLang($search['la']);
	$select_range = getSearchRange($search['od'], $search['do']);
	*/
	$rs = getSearchAll($select_hledat,$select_category,$select_bbox,$select_mds,$select_lang, $select_range);
	return $rs;
}

function getSearchArrayMaster($fc) {
	$rs = '';
	$search = getDefaultMdSearch();
	$search['kw'] = isset($_SESSION['micka']['search_m']['keyword']) ? $_SESSION['micka']['search_m']['keyword'] : '';
	$search['ph'] = isset($_SESSION['micka']['search_m']['phraze']) ? $_SESSION['micka']['search_m']['phraze'] : '';
	if ($search['kw'] == '' && $search['ph'] == '') {
		$search['kw'] = 1;
		$search['ph'] = 1;
	}

	$select_hledat   = getSearchText($search['te'], $search['ph'], $search['kw']);
	$select_category = getSearchCategory($search['ca']);
	$select_mds      = array();
	if ($fc == 1) {
		//$search['mds'] = isset($_SESSION['micka']['search_m']['standard']) ? $_SESSION['micka']['search_m']['standard'] : '';
		$select_mds      = getSearchMDS(2);
	}
	$select_bbox     = array();
	$select_lang     = array();
	$select_range    = array();
	$rs = getSearchAll($select_hledat,$select_category,$select_bbox,$select_mds,$select_lang, $select_range);
	return $rs;
}

function setSessionMickaSearchDef() {
	$_SESSION['micka']['search']['where'] = '';
	$_SESSION['micka']['search']['keyword'] = (isset($_SESSION['micka']['search']['keyword']) && $_SESSION['micka']['search']['keyword'] != '') ? $_SESSION['micka']['search']['keyword'] : 1;
	$_SESSION['micka']['search']['phraze'] = (isset($_SESSION['micka']['search']['phraze']) && $_SESSION['micka']['search']['phraze'] != '') ? $_SESSION['micka']['search']['phraze'] : 1;
}

function setSessionMickaSearch($in) {
	if (isset($_SESSION['micka']['search']['mode']) && $_SESSION['micka']['search']['mode'] == 'normal') {
		// prvotni hledani, ulozeni parametru hledani do session
		$_SESSION['micka']['search']['text'] = array_key_exists('text', $in) ? trim($in['text']) : '';
		$_SESSION['micka']['search']['keyword'] = array_key_exists('keyword', $in) ? $in['keyword'] : '';
		if ($_SESSION['micka']['search']['keyword'] == '') {
			$_SESSION['micka']['search']['keyword'] = 1;
		}
		$_SESSION['micka']['search']['phraze'] = array_key_exists('tvar', $in) ? $in['tvar'] : '';
		if ($_SESSION['micka']['search']['phraze'] == '') {
			$_SESSION['micka']['search']['phraze'] = 1;
		}
		$_SESSION['micka']['search']['category'] = array_key_exists('category', $in) ? $in['category'] : '';
		//$_SESSION['micka']['search']['xmin'] = array_key_exists('xmin', $in) ? $in['xmin'] : '';
		//$_SESSION['micka']['search']['ymin'] = array_key_exists('ymin', $in) ? $in['ymin'] : '';
		//$_SESSION['micka']['search']['xmax'] = array_key_exists('xmax', $in) ? $in['xmax'] : '';
		//$_SESSION['micka']['search']['ymax'] = array_key_exists('ymax', $in) ? $in['ymax'] : '';
		//$_SESSION['micka']['search']['inside'] = array_key_exists('inside', $in) ? $in['inside'] : '';
		//$_SESSION['micka']['search']['tmin'] = array_key_exists('tmin', $in) ? $in['tmin'] : '';
		//$_SESSION['micka']['search']['tmax'] = array_key_exists('tmax', $in) ? $in['tmax'] : '';
		//$_SESSION['micka']['search']['standard'] = array_key_exists('standard', $in) ? $in['standard'] : '';
		//$_SESSION['micka']['search']['languages'] = array_key_exists('languages', $in) ? $in['languages'] : '';
	}
	if (isset($_SESSION['micka']['search']['mode']) && $_SESSION['micka']['search']['mode'] == 'master') {
		$_SESSION['micka']['search_m']['text'] = array_key_exists('text', $in) ? trim($in['text']) : '';
		$_SESSION['micka']['search_m']['keyword'] = array_key_exists('keyword', $in) ? $in['keyword'] : '';
		if ($_SESSION['micka']['search_m']['keyword'] == '') {
			$_SESSION['micka']['search_m']['keyword'] = 1;
		}
		$_SESSION['micka']['search_m']['phraze'] = array_key_exists('tvar', $in) ? $in['tvar'] : '';
		if ($_SESSION['micka']['search_m']['phraze'] == '') {
			$_SESSION['micka']['search_m']['phraze'] = 1;
		}
		$_SESSION['micka']['search_m']['category'] = array_key_exists('category', $in) ? $in['category'] : '';
		$_SESSION['micka']['search_m']['standard'] = array_key_exists('standard', $in) ? $in['standard'] : '';
	}
}

function setSessionMickaSearchTemp() {
	if (isset($_SESSION['micka']['search']['mode']) && $_SESSION['micka']['search']['mode'] != 'master') {
		$_SESSION['micka']['search']['tempmode'] = $_SESSION['micka']['search']['mode'];
	}
}

function setSessionMickaSearchWhere($uuid) {
	if ($uuid != '') {
		$_SESSION['micka']['search']['where'] = "_UUID_ = '$uuid'";
	}
}

function setSessionMickaSearchPage($page) {
	if ($page > 0) {
		$_SESSION['micka']['search']['page'] = $page;
	}
}

function getSessionMickaSearch() {
	$rs['te'] = isset($_SESSION['micka']['search']['text']) ? $_SESSION['micka']['search']['text'] : $rs['te'] = '';
	$rs['kw'] = isset($_SESSION['micka']['search']['keyword']) ? $_SESSION['micka']['search']['keyword'] : $rs['kw'] = '';
	$rs['ph'] = isset($_SESSION['micka']['search']['phraze']) ? $_SESSION['micka']['search']['phraze'] : $rs['ph'] = '';
	$rs['ca'] = isset($_SESSION['micka']['search']['category']) ? $_SESSION['micka']['search']['category'] : $rs['ca'] = '';
	$rs['x1'] = isset($_SESSION['micka']['search']['xmin']) ? $_SESSION['micka']['search']['xmin'] : $rs['x1'] = '';
	$rs['y1'] = isset($_SESSION['micka']['search']['ymin']) ? $_SESSION['micka']['search']['ymin'] : $rs['y1'] = '';
	$rs['x2'] = isset($_SESSION['micka']['search']['xmax']) ? $_SESSION['micka']['search']['xmax'] : $rs['x2'] = '';
	$rs['y2'] = isset($_SESSION['micka']['search']['ymax']) ? $_SESSION['micka']['search']['ymax'] : $rs['y2'] = '';
	$rs['in'] = isset($_SESSION['micka']['search']['inside']) ? $_SESSION['micka']['search']['inside'] : $rs['in'] = '';
	$rs['od'] = isset($_SESSION['micka']['search']['tmin']) ? $_SESSION['micka']['search']['tmin'] : $rs['od'] = '';
	$rs['do'] = isset($_SESSION['micka']['search']['tmax']) ? $_SESSION['micka']['search']['tmax'] : $rs['do'] = '';
	$rs['st'] = isset($_SESSION['micka']['search']['standard']) ? $_SESSION['micka']['search']['standard'] : $rs['st'] = '';
	$rs['la'] = isset($_SESSION['micka']['search']['languages']) ? $_SESSION['micka']['search']['languages'] : $rs['la'] = '';

	if ($rs['kw'] == '' && $rs['ph'] == '') {
		$_SESSION['micka']['search']['keyword'] = 1;
		$_SESSION['micka']['search']['phraze'] = 1;
		$rs['kw'] = 1;
		$rs['ph'] = 1;
	}
	return $rs;
}

function getDefaultMdSearch() {
	$rs['te'] = isset($_SESSION['micka']['search_m']['text']) ? $_SESSION['micka']['search_m']['text'] : '';
	$rs['ca'] = isset($_SESSION['micka']['search_m']['category']) ? $_SESSION['micka']['search_m']['category'] : '';
	//$rs['kw'] = 1;
	//$rs['ph'] = 1;
	return $rs;
}

// používá pouze md_fc.php
function getMdFc() {
	$rs = array();
	$sql[] = "
		SELECT  md.recno, md_values.md_value, md_values.lang, md.data_type, md.create_user, md.view_group, md.edit_group
		FROM (md INNER JOIN md_values ON md.recno = md_values.recno) INNER JOIN standard ON md.md_standard = standard.md_standard";
	$sql[] = "WHERE md.md_standard=2 AND md_values.md_id=11";
	$sql[] = "ORDER BY md_values.recno";
	$result = _executeSql('select', $sql, array('assoc', 'recno,#,='));
	if (is_array($result) && count($result) > 0) {
		foreach($result as $record) {
			if (getMdRight('view', MICKA_USER, $record[0]['DATA_TYPE'], $record[0]['CREATE_USER'], $record[0]['EDIT_GROUP'], $record[0]['VIEW_GROUP'])) {
				$title_micka = '';
				$title_eng = '';
				$title_random = '';
				foreach($record as $row) {
					$title_random = $row['MD_VALUE'];
					if($row['LANG'] == MICKA_LANG) {
						$title_micka = $row['MD_VALUE'];
					}
					if($row['LANG'] == 'eng') {
						$title_micka = $row['MD_VALUE'];
					}
					$recno = $row['RECNO'];
				}
				$title = $title_micka != '' ? $title_micka : '';
				$title = ($title == '' && $title_eng != '') ? $title_eng : '';
				$title = ($title == '' && $title_random != '') ? $title_random : '';
				if ($title != '' && $recno > 0) {
					$title = $title_micka;
					$pom['recno'] = $recno;
					$pom['title'] = $title;
					array_push($rs, $pom);
				}
			}
		}
	}
	return $rs;
}

function getMdFcDetail($recno) {
	$rs = array();
	$md_record = new MdRecord();
	$md_record->setTableMode('md');
	$values = $md_record->getMdValues($recno);
	//my_print_r($values);
	//$rs['langs'] = $values['md']['LANG'];
	$rs['uuid'] = $values['md']['UUID'];
	//$rs['titles'] = $values['md']['UUID'];
	$rs['langs'] = '';
	$rs['titles'] = '';
	$rs['hodnoty'] = array();
	$title_ap = '';
	$title = '';
	if (isset($values['md_values']) && count($values['md_values']) > 0) {
		foreach($values['md_values'] as $row) {
			if ($row['MD_ID'] == 11) {
				$rs['langs'] = $rs['langs'] == '' ? $row['LANG'] : '|' . $row['LANG'];
				$rs['titles'] = $rs['titles'] == '' ? $row['MD_VALUE'] : '|' . $row['MD_VALUE'];
				$title = $row['MD_VALUE'];
				if ($row['LANG'] == MICKA_LANG) {
					$title_ap = $row['MD_VALUE'];
				}
			}
			if ($row['MD_ID'] == 13) {

				$pom['kod'] = $row['MD_VALUE'];
				$pom['nazev'] = $row['MD_VALUE'];
				array_push($rs['hodnoty'], $pom);
			}
		}
	}
	$rs['title'] = $title_ap != '' ? $title_ap : $title;
	//my_print_r($rs);
	return $rs;
}

function setSortBy($sort_by) {
	$_SESSION['micka']['search']['sort_by'] = $sort_by;
}

?>
