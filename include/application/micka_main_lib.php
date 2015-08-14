<?php
/**
 * 
 * version 20141003
 * 
 */

function setUrlSearch2Session() {
	$_SESSION['micka']['search']['url'] = $_SERVER['REQUEST_URI'];
    // někde není $_SERVER['REQUEST_URI']
    //$_SESSION['micka']['search']['url'] = $_SERVER["SCRIPT_NAME"] . '?' . $_SERVER["QUERY_STRING"];
}

function getUrlSearchFromSession() {
	return isset($_SESSION['micka']['search']['url']) ? $_SESSION['micka']['search']['url'] : '';
}

function setRecordsMatched2Session() {
	$_SESSION['micka']['search']['matched'] = isset($_SESSION['micka']['search']['xmlMatched']) 
		? $_SESSION['micka']['search']['xmlMatched'] 
		: 0;
}

function getRecordsMatchedFromSession() {
	return isset($_SESSION['micka']['search']['matched']) ? $_SESSION['micka']['search']['matched'] : 0;
}

function setRecordDetail2Session() {
	$protokol = ($_SERVER["HTTPS"] == 'on' ? "https" : "http");
	$serverPort = $_SERVER['SERVER_PORT'] == '80' ? '' : ':' . $_SERVER['SERVER_PORT'];
	$_SESSION['micka']['search']['detail'] = "$protokol://" . $_SERVER['SERVER_NAME'] . "$serverPort" . $_SERVER['REQUEST_URI'];
    // někde není $_SERVER['REQUEST_URI']
	//$_SESSION['micka']['search']['detail'] = "$protokol://" . $_SERVER['SERVER_NAME'] . "$serverPort" . $_SERVER["SCRIPT_NAME"] . '?' . $_SERVER["QUERY_STRING"];
}

function getRecordDetailFromSession() {
	return isset($_SESSION['micka']['search']['detail']) ? $_SESSION['micka']['search']['detail'] : '';
}

function setUrlEdit2Session($redirectUrl='') {
	$_SESSION['micka']['edit']['url'] = $redirectUrl != '' 
		? $redirectUrl
		: $_SERVER['REQUEST_URI'];
    // někde není $_SERVER['REQUEST_URI']
    /*
	$_SESSION['micka']['edit']['url'] = $redirectUrl != '' 
		? $redirectUrl
		: $_SERVER["SCRIPT_NAME"] . '?' . $_SERVER["QUERY_STRING"];
    */
}

function getUrlEditFromSession() {
	return isset($_SESSION['micka']['edit']['url']) ? $_SESSION['micka']['edit']['url'] : '';
}

function setEditId2Session($key, $value) {
	if ($key == 'recno' || $key == 'recnoTmp' || $key == 'profil' || $key == 'package' || $key == 'uuid') {
		$_SESSION['micka']['edit'][$key] = $value;
	}
}

function getEditValueFromSession($id, $typeId, $key) {
	$rs = '';
	if ($typeId == 'recno' && $id > -1) {
		if (isset($_SESSION['micka']['edit']['recno']) && isset($_SESSION['micka']['edit'][$key])) {
			if ($_SESSION['micka']['edit']['recno'] == $id) {
				$rs = $_SESSION['micka']['edit'][$key];
			}
		}
	}
	return $rs;
}

function unsetEditValue2Session() {
	unset($_SESSION['micka']['edit']);
}

function getMdDataType($label) {
    if (MD_DATA_TYPE != '') {
        //"0=>157,1=>158"
        $tmp = explode(',', MD_DATA_TYPE);
        foreach ($tmp as $row) {
            $md_data_type = explode('=>', $row);
            if (count($md_data_type)==2) {
                $rs[$md_data_type[0]] = $label[$md_data_type[1]];
            }
        }
    } else {
        $rs = array(0 => $label[157], 1 => $label[158]);
    }
    return $rs;
}

function getAction($request, $akDefault) {
	$rs = '';
	if (isset($request['ak']) && $request['ak'] != '') {
		$rs = htmlspecialchars($request['ak']);
	} elseif (isset($request['action']) && $request['action'] != '') {
		$rs = htmlspecialchars($request['action']);
	} elseif (isset($request['request']) && $request['request'] != '') {
			if (strtolower($request['request']) == 'getrecords') {
				$rs = 'find';
			} elseif (strtolower($request['request']) == 'getrecordbyid') {
				$rs = 'detail';
			}
			$_REQUEST['template'] = "micka2htmlList_";
	}
	if ($rs == '' && $akDefault != '') {
		$rs = $akDefault;
	}
	if ($rs == 'find') {
		setUrlSearch2Session();
		unsetEditValue2Session();
	}
	return $rs != '' ? strtolower($rs) : $rs;
}

function renderXml($ak, $xml) {
	if ($xml == '') {
		require PHPINC_DIR . '/templates/404_record.php';	
	} else {
		dibi::disconnect();
		if ($ak == '_sxml') {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: public"); 
			header("Content-Description: File Transfer");
			header('Content-Disposition: attachment; filename="metadata.xml"');
		}
		header("Content-type: application/xml");
	  echo $xml;
		exit;
	}
}

function actionXml($ak, $table, $uuid, $xsltemplate, $cache=TRUE) {
	$md_record = new MdRecord();
	$md_record->setTableMode($table);
	$record = $md_record->getMd('uuid', $uuid);
	$xml = isset($record['md']['PXML']) ? $record['md']['PXML'] : '';
	if ($cache === FALSE && $xml != '') {
		// xml z dat
		$xml = $md_record->getMdXmlData($uuid, $xsltemplate);
	}
	renderXml($ak, $xml);
}

function mainEdit($get) {
	//function mainEdit($recno, $uuid, $package, $profil, $copy=TRUE) {
	$recno = (isset($get['recno']) && $get['recno'] != '') ? htmlspecialchars($get['recno']) : -1;
	$uuid = (isset($get['uuid']) && $get['uuid'] != '') ? htmlspecialchars($get['uuid']) : '';
	if ($recno == -1 && $uuid == '' && isset($get['id'])) {
		$uuid = $get['id'] != '' ? htmlspecialchars($get['id']) : '';
	}
	$package = (isset($get['package']) && $get['package'] != '') ? htmlspecialchars($get['package']) : '';
	$profil = (isset($get['profil']) && $get['profil'] != '') ? htmlspecialchars($get['profil']) : '';
	$copy = (isset($get['copy']) && $get['copy'] == FALSE) ? FALSE : TRUE;
	
	$rs = array();
	$rs['akce'] = 'error';
	$rs['ok'] = FALSE;
	$rs['report'] = '';
	$rs['data'] = '';

	$md_record = new MdRecord();

	if ($uuid != '') {
		if ($copy === FALSE) {
			$md_record->setTableMode('tmp');
		}
		$record = $md_record->getMd('uuid', $uuid);
		if ($record['report'] != 'ok') {
			$rs['report'] = $record['report'];
			require PHPINC_DIR . '/templates/404_record.php';
			return $rs;
		}
		if ($record['right'] != 'w') {
			$rs['report'] = 'Not rights';
			require PHPINC_DIR . '/templates/403.php';
			return $rs;
		}
		$recno = $record['md']['RECNO'];
	}
	
	if ($recno > -1) {
		$recnoSession = getEditValueFromSession($recno, 'recno', 'recnoTmp');
		if ($recnoSession != '' && $recnoSession != -1) {
			$copy = FALSE;
			if ($profil == '') {
				$profil = getEditValueFromSession($recno, 'recno', 'profil');
			}
			if ($package == '') {
				$package = getEditValueFromSession($recno, 'recno', 'package');
			}
			$recno = $recnoSession;
		}
		if ($copy === TRUE) {
			$pom = $md_record->copyRecordToTmp($recno);
			$recnoTmp = $pom['recno_tmp'];
			setEditId2Session('recno', $recno);
			setEditId2Session('recnoTmp', $recnoTmp);
			setUrlEdit2Session();
		} else {
			$recnoTmp = $recno;
		}
		if ($recnoTmp == -1) {
			$rs['report'] = $pom['report'];
			require PHPINC_DIR . '/templates/403.php';
			return $rs;
		}
		if ($package == '' && $profil == '') {
			$package = 1;
			$profil = START_PROFIL;
		}
		$md_record->setTableMode('tmp');
        //echo $profil; FIXME?
		$record = $md_record->getMdValues($recnoTmp, $value_lang='xxx', $profil, $package);
		if ($record['report'] != 'ok' || $record['user_right'] != 'w') {
			Debugger::log('[micka_main_lib.mainEdit] ' . "Recno=$recnoTmp, " . $record['report'], 'ERROR');
			$rs['report'] = $record['report'];
			require PHPINC_DIR . '/templates/403.php';
			return $rs;
		}
		$rs['publisher'] = $record['publisher'];
		$rs['saver'] = $record['saver'];
		$rs['hierarchy'] = array_key_exists('hierarchy', $record) ? $record['hierarchy'] : '';
		$rs['data']['md']['RECNO'] = $recnoTmp;
		$rs['data']['md']['MD_STANDARD'] = $record['md']['MD_STANDARD'];
		$rs['data']['md']['LANG'] = $record['md']['LANG'];
		$rs['data']['md']['TITLE'] = $record['md']['TITLE'];
		$rs['data']['md']['UUID'] = $record['md']['UUID'];
		$rs['data']['md']['PXML'] = $record['md']['PXML'];
		$rs['data']['md']['DATA_TYPE'] = $record['md']['DATA_TYPE'];
		$rs['data']['md']['VIEW_GROUP'] = $record['md']['VIEW_GROUP'];
		$rs['data']['md']['EDIT_GROUP'] = $record['md']['EDIT_GROUP'];

        if ($record['md']['MD_STANDARD'] == 0 || $record['md']['MD_STANDARD'] == 10) {
            foreach ($record['md_values'] as $value) {
                if ($value['MD_ID'] == 11 || $value['MD_ID'] == 5063) {
                    if ($value['LANG'] == MICKA_LANG) {
                        $rs['data']['md']['TITLE'] = $value['MD_VALUE'];
                        break;
                    }
                }
            }
        }
        
		if(isset($record['template']) && $record['template'] != '') {
			// micka LITE
			$rs['data']['md']['PXML'] = $record['md']['PXML'];
			$rs['akce'] = 'lite';
			$rs['template'] = $record['template'];
			$rs['profil'] = $record['profil'];
			$rs['ok'] = TRUE;
			return $rs;
		}
		
		$rs['data']['package'] = $record['md_value_package'];
		if ($record['md_value_profil'] == -1 && $record['md']['MD_STANDARD'] == 0) {
			$rs['data']['profil'] = 0;
		} elseif ($record['md_value_profil'] == -1 && $record['md']['MD_STANDARD'] == 10) {
			$rs['data']['profil'] = 100;
		} else {
			$rs['data']['profil'] = $record['md_value_profil'];
		}
		$rs['data']['keywords_uri'] = $md_record->getKeywordsUri($recnoTmp);
		require PHPPRG_DIR . '/MdEditForm.php';
		$form = new EditForm();
		$rs['data']['md_values'] = $form->getEditForm(
						$record['md']['MD_STANDARD'],
						$recnoTmp,
						$record['md']['LANG'],
						$record['md_value_profil'],
						$record['md_value_package'],
						$record['md_values']);
		$rs['data']['md_values_end'] = $form->getEditFormEnd();
		$rs['akce'] = 'edit';
		return $rs;
	} else {
		Debugger::log('[micka_main_lib.mainEdit] ' . 'Not complete input!', 'ERROR');
		$rs['report'] = 'Not complete input!';
		require PHPINC_DIR . '/templates/404_record.php';
		return $rs;
	}
}

function mainEditLite($record) {
	$rs = array();
	$rs['akce'] = 'error';
	$rs['ok'] = FALSE;
	$rs['report'] = 'error xml';
	$rs['data'] = '';

	if (is_array($record) === FALSE) {
		return $rs;
	}

	$params['template'] = $record['template'];
	$params['profil'] = $record['profil'];
	$params['recno'] = $record['data']['md']['RECNO'];
	$params['mds'] = $record['data']['md']['MD_STANDARD'];
	$rs['data']['profil'] = $record['profil'];
	$rs['data']['md'] = $record['data']['md'];
	//$rs['data']['md']['package'] = (isset($record['data']['package']) && $record['data']['package'] > 0) ? $record['data']['package'] : 1;
	//$rs['lite']['title_profil'] = getLabelProfil($record['profil']);
	$xml = $record['data']['md']['PXML'];
	
	if ($xml == '') {
		// nový záznam
		//$xml = file_get_contents(WWW_DIR . "/lite/resources/priklad_inspire.xml");
		//$xml = '<gmd:MD_Metadata xmlns:gmd="http://www.isotc211.org/2005/gmd" />';
	}
	if ($xml != '' && $params['template'] != '') {
		$template = WWW_DIR . '/lite/resources/' . $params['template'] . '.xsl';
		//$template_hlp = WWW_DIR . '/lite/resources/_kote-micka-hlp.xsl';
		require_once PHPPRG_DIR . '/CswClient.php';
		require_once WWW_DIR . '/lite/resources/Kote.php';
		//require_once WWW_DIR . '/validator/resources/Validator.php';
		$cswClient = new CSWClient();
		$params_lite = array();
		$params_lite['alabel'] = 'Administrace'; // label Administrace
		$params_lite['plabel'] = 'Veřejný'; // label Public
		$params_lite['recno'] = $params['recno'];
		$params_lite['uuid'] = $record['data']['md']['UUID'];
		$params_lite['data_type'] = $record['data']['md']['DATA_TYPE'];
		$params_lite['publisher'] = $record['publisher'] === TRUE ? 1 : 0;
		$params_lite['saver'] = $record['saver'] === TRUE ? 1 : 0;
		$params_lite['select_profil'] = $params['profil'];
		$params_lite['mds'] = $params['mds'];
		$params_lite['lang'] = MICKA_LANG;
		// FIXME : opravit pro umístění v root
		$params_lite['mickaURL'] = dirname($_SERVER['SCRIPT_NAME'])== '\\' ? '' : dirname($_SERVER['SCRIPT_NAME']);
		$rs['lite'] = $cswClient->processTemplate($xml, $template, $params_lite);
		$rs['publisher'] = $record['publisher'];
		$rs['saver'] = $record['saver'];
		$rs['akce'] = 'lite';
		$rs['ok'] = TRUE;
	}
    //Debugger::dump($rs);
	return $rs;
}

function mainCancel() {
	$md_record = new MdRecord();
	$md_record->deleteTmpRecords();
	unset($md_record);
	$redirectUrl = getUrlSearchFromSession();
	require PHPPRG_DIR . '/redirect.php';
}

function mainNavigation($ak, $label, $id='???') {
	$rs = array();
	switch ($ak) {
		case 'home':
			$rs[] = array('url' => '', 'label' => $label[193], 'value' => '');
			break;
		case 'cookbook':
			$rs[] = array('url' => '', 'label' => $label[194], 'value' => '');
			break;
		case 'search':
			$rs[] = array('url' => '', 'label' => $label[1], 'value' => '');
			break;
		case 'find':
			$rs[] = array('url' => '?ak=search', 'label' => $label[1], 'value' => '');
			$rs[] = array('url' => '', 'label' => $label[110], 'value' => getRecordsMatchedFromSession());
			break;
		case 'detail':
			$rs[] = array('url' => '?ak=search', 'label' => $label[1], 'value' => '');
			$rs[] = array('url' => getUrlSearchFromSession(),
					'label' => $label[110], 'value' => getRecordsMatchedFromSession());
			$rs[] = array('url' => '', 'label' => $label[190], 'value' => '');
			break;
		case 'detailall':
			$rs[] = array('url' => '?ak=search', 'label' => $label[1], 'value' => '');
			$rs[] = array('url' => getUrlSearchFromSession(),
					'label' => $label[110], 'value' => getRecordsMatchedFromSession());
			$rs[] = array('url' => getRecordDetailFromSession(), 'label' => $label[190], 'value' => '');
			$rs[] = array('url' => '', 'label' => $label[191], 'value' => '');
			break;
		case 'edit':
			$rs[] = array('url' => '?ak=search', 'label' => $label[1], 'value' => '');
			$rs[] = array('url' => getUrlSearchFromSession(),
					'label' => $label[110], 'value' => getRecordsMatchedFromSession());
			$rs[] = array('url' => '', 'label' => $label[21], 'value' => '');
			break;
		case 'valid':
			$rs[] = array('url' => '?ak=search', 'label' => $label[1], 'value' => '');
			$rs[] = array('url' => getUrlSearchFromSession(),
					'label' => $label[110], 'value' => getRecordsMatchedFromSession());
			$rs[] = array('url' => '', 'label' => $label[186], 'value' => '');
			break;
		case 'about':
			$rs[] = array('url' => '', 'label' => $label[28], 'value' => '');
			break;
		case 'contact':
			$rs[] = array('url' => '', 'label' => $label[195], 'value' => '');
			break;
		case 'help':
			$rs[] = array('url' => '', 'label' => $label[15], 'value' => '');
			break;
		case 'new':
			$rs[] = array('url' => '', 'label' => $label[3], 'value' => '');
			break;
		case 'admin':
			if ($id != 'default') {
				$rs[] = array('url' => '?ak=admin', 'label' => $label[18], 'value' => '');
				$rs[] = array('url' => '', 'label' => $id, 'value' => '');
			}
			else {
				$rs[] = array('url' => '', 'label' => $label[18], 'value' => '');
			}
			break;
		default:
			break;
	}
	return $rs;
}

function mainPageTitle($navigation, $title) {
	$rs = '';
	$idx = count($navigation)-1;
	if ($idx > -1) {
		$rs = $navigation[$idx]['label'];
	}
	if ($title != '') {
		$rs .= ' ' . $title;
	}
	return $rs;
}

function mainSave($post) {
	$rs = array();
	$rs['akce'] = 'error';
	$rs['ok'] = FALSE;
	$rs['report'] = '';
	$rs['data'] = '';
	$recno = isset($post['recno']) ? htmlspecialchars($_POST['recno']) : -1;
	$uuid = ($post['uuid'] != '') ? htmlspecialchars($post['uuid']) : '';
	$block = ($post['block'] != '') ? htmlspecialchars($post['block']) : -2;
	$nextblock = ($post['nextblock'] != '') ? htmlspecialchars($post['nextblock']) : -2;
	$profil = ($post['profil'] != '') ? htmlspecialchars($post['profil']) : -2;
	$nexprofil = ($post['nextprofil'] != '') ? htmlspecialchars($post['nextprofil']) : -2;
	$mds = ($post['mds'] != '') ? htmlspecialchars($post['mds']) : -2;
	$data_type = isset($post['data_type']) ? htmlspecialchars($post['data_type']) : -1;
	$edit_group = isset($post['edit_group']) ? htmlspecialchars($post['edit_group']) : '';
	$view_group = isset($post['view_group']) ? htmlspecialchars($post['view_group']) : '';
	$ende = array_key_exists('ende', $post) ? htmlspecialchars($post['ende']) : 0;
	if ($recno == '' || $recno < 1 || $ende == 0 || count($post) < 6 || $mds < 0) {
		Debugger::log('[micka_main_lib.mainSave] ' . "Not complete input data! recno=$recno, mds=$mds, ende=$ende, count=" . count($post), 'ERROR');
		require PHPINC_DIR . '/templates/404_record.php';
	}

	// odstranění ošetření dat způsobeného direktivou magic_quotes_gpc
	if (get_magic_quotes_gpc()) {
		$process = array(&$post);
		while (list($key, $val) = each($process)) {
			foreach ($val as $k => $v) {
				unset($process[$key][$k]);
				if (is_array($v)) {
					$process[$key][($key < 5 ? $k : stripslashes($k))] = $v;
					$process[] =& $process[$key][($key < 5 ? $k : stripslashes($k))];
				}
				else {
					$process[$key][stripslashes($k)] = stripslashes($v);
				}
			}
		}
	}

	
	if (array_key_exists('fileIdentifier_0_TXT', $post)) {
		// Micka Lite
		require(PHPPRG_DIR . '/CswClient.php');
		require(WWW_DIR . '/lite/resources/Kote.php');
		require PHPPRG_DIR . '/micka_lib_php5.php';
		require PHPPRG_DIR . '/MdImport.php';
		$cswClient = new CSWClient();
		$input = Kote::processForm(beforeSaveRecord($post));
		$params = Array('datestamp'=>date('Y-m-d'), 'lang'=>'cze');
		$xmlstring = $cswClient->processTemplate($input, WWW_DIR . '/lite/resources/kote2iso.xsl', $params);

		$importer = new MetadataImport();
		$importer->setTableMode('tmp');

		$md = array();
		$md['file_type'] = 'WMS';
		$md['edit_group'] = MICKA_USER;
		$md['view_group'] = MICKA_USER;
		$md['mds'] = 0;
		$md['lang'] = 'cze';
		$lang_main = 'cze';
		$md['update_type'] = 'lite';

		$report = $importer->import(
						$xmlstring,
						$md['file_type'],
						MICKA_USER,
						$md['edit_group'],
						$md['view_group'],
						$md['mds']=0, // co to je?
						$md['lang'], // co to je?
						$lang_main,
						$params=false,
						$md['update_type']
		);
		$md_record = new MdRecord();
		$md_record->setTableMode('tmp');
		$record = $md_record->getMd('recno', $recno);
		// Administrace záznamu
		if ($record['right'] == 'w') {
			$data = array();
			$data['data_type'] = $data_type;
            if ($edit_group != '') {
                $data['edit_group'] = $edit_group;
            }
            if ($view_group != '') {
                $data['view_group'] = $view_group;
            }
			$md_record->updateMdFromImport($recno, $data);
		}
	} else {
		// Micka
		$md_record = new MdRecord();
		$pom = $md_record->setTmpMdValues($post);
		if ($pom['ok'] === FALSE) {
			$rs['report'] = $pom['report'];
			return $rs;
		}
		$record = $md_record->getMd('recno', $recno);
	}
	if ($nextblock == -1) {
		// -1: Ukončit editaci
		$rs['data'] = $record['md'];
		$md_record->copyTmpRecordToMd();
		$md_record->deleteTmpRecords();
		$rs['akce'] = 'search';
		$rs['ok'] = TRUE;
	} elseif ($nextblock == -2) {
		// -2: Uložení do MD a pokračování v editaci
		$md_record->copyTmpRecordToMd();
	} elseif ($nextblock == -20 || $nextblock == -22) {
		// -20: průběžné uložení a xml
		// -22: průběžné uložení a nabídka uložení xml
		$rs['akce'] = $nextblock == -22 ? 'sxml' : 'xml';
		$rs['data'] = $record['md']['PXML'];
		return $rs;
	} elseif ($nextblock == -21) {
		// -21: průběžné uložení a validace
		$rs['akce'] = 'valid';
		$rs['data'] = $record['md']['UUID'];
		return $rs;
	} elseif ($nextblock == -19) {
		// -19: validace
		$rs['valid'] = TRUE;
	}
	if ($nextblock != -1) {
		// průběžné uložení, jiný profil
		if ($nexprofil > -1 && $nexprofil != '-19') {
			$profil = $nexprofil;
		}
		if ($nextblock > -1) {
			$block = $nextblock;
		}
		setEditId2Session('profil', $profil);
		setEditId2Session('package', $block);
		$rs['akce'] = 'edit';
		$rs['ok'] = TRUE;
		$rs['data']['recno'] = $recno;
		$rs['data']['uuid'] = '';
		$rs['data']['package'] = $block;
		$rs['data']['profil'] = $profil;
	}
	if ($rs['akce'] == 'edit') {
		$redirectUrl = getUrlEditFromSession();
	} else {
		unsetEditValue2Session();
		$redirectUrl = getUrlSearchFromSession();
	}
	//return $rs;
	return $redirectUrl;
}

function mainDelete($get) {
	$recno = (isset($get['recno']) && $get['recno'] != '') ? htmlspecialchars($get['recno']) : -1;
	$uuid = (isset($get['uuid']) && $get['uuid'] != '') ? htmlspecialchars($get['uuid']) : '';
	$id = (isset($_REQUEST['id']) && $_REQUEST['id'] != '') ? htmlspecialchars($_REQUEST['id']) : '';

	if ($recno < 0 && $uuid == '' & $id == '') {
		Debugger::log('[micka_main_lib.mainDelete] ' . "Not complete input data! recno=$recno, uuid=$uuid, id=$id", 'ERROR');
		require PHPINC_DIR . '/templates/404_record.php';
	}
	
	$rs = array();
	$rs['akce'] = 'error';
	$rs['ok'] = FALSE;
	$rs['report'] = '';
	$rs['data'] = '';
	
	if ($id != '') {
		$md_record = new MdRecord();
		$id_arr = explode(",", $id);
		foreach ($id_arr as $key => $value) {
			$uuid = trim($value);
			if ($uuid != '') {
				$where_col = 'uuid';
				$where_value = $uuid;
				$del_report = $md_record->deleteMdRecords($where_col, $where_value);
				$rs['report'][$uuid] = $del_report['report'];
			}
		}
		$rs['akce'] = 'json';
	} else {
		$where_col = $recno > 0 ? 'recno' : 'uuid';
		$where_value = $recno > 0 ? $recno : $uuid;
		$md_record = new MdRecord();
		$del_report = $md_record->deleteMdRecords($where_col, $where_value);
		if ($del_report['report'] != 'ok') {
			$rs['report'] = $del_report['report'];
			Debugger::log('[micka_main_lib.mainDelete] ' . $del_report['report'], 'ERROR');
			require PHPINC_DIR . '/templates/404_record.php';
			return $rs;
		}
		if ($where_col == 'recno') {
			$rs['akce'] = 'search';
		} else {
			$rs['akce'] = 'window_close';
		}
	}
	if ($rs['akce'] == 'search') {
		$redirectUrl = getUrlSearchFromSession();
	} else {
		$redirectUrl = '';
	}
	//return $rs;
	return $redirectUrl;
}

function mainValid($uuid) {
	$rs = array();
	$rs['akce'] = 'error';
	$rs['ok'] = FALSE;
	$rs['report'] = '';
	$rs['data'] = '';

	if ($uuid == '') {
		//$rs['report'] = 'Identifier not found!';
		//return $rs;
		require PHPINC_DIR . '/templates/404_record.php';
	}
	
	$table = 'md';
	$md_record = new MdRecord();
	$md_record->setTableMode($table);
	$record = $md_record->getMd('uuid', $uuid);
	if (is_array($record) && count($record) > 0) {
		if ($record['report'] == 'ok') {
			$rs['akce'] = '_valid';
			require_once WWW_DIR . '/validator/resources/Validator.php';
			$lang_valid = MICKA_LANG == 'cze' ? 'cze' : 'eng';
			$validator = new Validator('gmd', $lang_valid);
			$validator->run($record['md']['PXML']);
			$rs['data'] = $validator->asHTML();
			$rs['ok'] = TRUE;
		}
		else {
			//$rs['akce'] = 'error';
			//$rs['report'] = $record['report'];
			require PHPINC_DIR . '/templates/404_record.php';
		}
	}
	return $rs['data'];
}

function mainCopy($recnoSource, $defaultValueMd=array()) {
	$rs = array();
	$rs['akce'] = 'error';
	$rs['ok'] = FALSE;
	$rs['report'] = '';
	$rs['data'] = '';
    

    $defaultEditGroup = array_key_exists('edit_group', $defaultValue) === TRUE ? $defaultValue['edit_group'] : MICKA_USER;
    $defaultViewGroup = array_key_exists('view_group', $defaultValue) === TRUE ? $defaultValue['view_group'] : MICKA_USER;

	$md_record = new MdRecord();
    $md_record->setDefaultValueMd($defaultValueMd);
	$copy = $md_record->copyRecordToTmp($recnoSource,  $new_record=TRUE);
	if ($copy['recno_tmp'] < 0 || $copy['report'] != 'ok') {
		$rs['report'] = $copy['report'];
		//return $rs;
		require PHPINC_DIR . '/templates/404_record.php';
	} else {
		$rs['akce'] = 'edit';
		$rs['ok'] = TRUE;
		$rs['data'] = $copy['recno_tmp'];
		unset ($md_record);
		//return $rs;
		setEditId2Session('recno', 'new');
		setEditId2Session('recnoTmp', $copy['recno_tmp']);
		$redirectUrl = substr(htmlspecialchars($_SERVER['PHP_SELF']), 0, strrpos($_SERVER['PHP_SELF'], '/')) . '?ak=edit&recno=new';
		setUrlEdit2Session($redirectUrl);
		return $redirectUrl;
	}
}

function mainInsert($post, $defaultValue=array()) {
	//my_print_r($post);
	$rs = array();
	$md = array();
	$rs['akce'] = 'error';
	$rs['ok'] = FALSE;
	$rs['report'] = '';
	$rs['data'] = '';
	$md_record = new MdRecord();
	//$md_record->deleteTmpRecords();
	if (count($post) == 0) {
			setMickaLog('INSERT ERROR, POST is empty!', 'ERROR', 'micka_main_lib.main_insert');
			$rs['report'] = 'Error, not complete data!';
			//return $rs;
			Debugger::log('[micka_main_lib.mainInsert] ' . 'POST is empty!', 'ERROR');
			require PHPINC_DIR . '/templates/404_record.php';
	}
    $defaultEditGroup = array_key_exists('edit_group', $defaultValue) === TRUE ? $defaultValue['edit_group'] : MICKA_USER;
    $defaultViewGroup = array_key_exists('view_group', $defaultValue) === TRUE ? $defaultValue['view_group'] : MICKA_USER;
	//	0:ISO 19115 | 10:ISO 19119 | 1:DC | 2:FC | 99:import
	$md['md_standard'] = (isset($post['standard']) && $post['standard'] != '') ? htmlspecialchars($post['standard']) : 0;
	// skupina pro prohlížení
	$md['view_group'] = (isset($post['group_v']) && $post['group_v'] != '') ? htmlspecialchars($post['group_v']) : $defaultEditGroup;
	// skupina s právem editace
	$md['edit_group'] = (isset($post['group_e']) && $post['group_e'] != '') ? htmlspecialchars($post['group_e']) : $defaultViewGroup;
	// hlavní jazyk záznamu
	$lang_main = (isset($post['lang_main']) && $post['lang_main'] != '') ? htmlspecialchars($post['lang_main']) : MICKA_LANG;
	// seznam dalších jazyků
	$md['lang'] = isset($post['languages']) ? htmlspecialchars(implode($post['languages'],"|")) : '';
	if ($md['lang'] == '' && $lang_main != '') {
		$md['lang'] = $lang_main;
	}
	
	// template s default hodnotami
	$template = '';
	if ($md['md_standard'] < 99) {
		if ($md['md_standard'] == 0) {
			$template = PHPCFG_DIR . '/templateMd.xml';
		} elseif ($md['md_standard'] == 10) {
			$template = PHPCFG_DIR . '/templateMs.xml';
		} elseif ($md['md_standard'] == 1) {
			$template = PHPCFG_DIR . '/templateDc.xml';
		} elseif ($md['md_standard'] == 2) {
			$template = PHPCFG_DIR . '/templateFc.xml';
		}
		if ($template != '' && file_exists($template)) {
			$md['file_type'] = 'ISO19139';
			$md['update_type'] = 'skip';
			$md['md_standard'] = 99;
		}
	}
	
	// IMPORT
	if ($md['md_standard'] == 99) {
		$md['file_type'] = (isset($post['fileType']) && $post['fileType'] != '') ? htmlspecialchars($post['fileType']) : 'ISO19139';
		$md['md_rec'] = (isset($post['md_rec']) && $post['md_rec'] != '') ? htmlspecialchars($post['md_rec']) : '';
		$md['fc'] = (isset($post['fc']) && $post['fc'] != '') ? htmlspecialchars($post['fc']) : '';
		$md['service_type'] = (isset($post['serviceType']) && $post['serviceType'] != '') ? htmlspecialchars($post['serviceType']) : 'WMS';
		$md['url'] = (isset($post['url']) && $post['url'] != '') ? htmlspecialchars($post['url']) : '';
		$md['url'] = ($md['url'] != '') ? str_replace('&amp;','&',$md['url']) : '';
		$md['update_type'] = (isset($post['updateType']) && $post['updateType'] != '') ? htmlspecialchars($post['updateType']) : 'skip';
	}
	
	if ($md['md_standard'] < 99) {
		// Vytvoření nového záznamu v tmp
			$recno = $md_record->setNewRecord($md, $lang_main);
			if ($recno > 0) {
				$rs['akce'] = 'edit';
				$rs['ok'] = TRUE;
				$rs['data'] = $recno;
				setEditId2Session('recno', 'new');
				setEditId2Session('recnoTmp', $recno);
				$redirectUrl = substr(htmlspecialchars($_SERVER['PHP_SELF']), 0, strrpos($_SERVER['PHP_SELF'], '/')) . '?ak=edit&recno=new';
				setUrlEdit2Session($redirectUrl);
			}
		//return $rs;
		return $redirectUrl;
	}
	
	if ($md['md_standard'] == 99) {
		// Import ze souboru nebo url
		require PHPPRG_DIR . '/micka_lib_php5.php';
		require PHPPRG_DIR . '/micka_lib_insert.php';
		require PHPPRG_DIR . '/MdImport.php';
		//$prevod = DomainCodes2List();
		$report = array();
		$importer = new MetadataImport();
		if (isset($_FILES['soubor']['tmp_name']) && $_FILES['soubor']['tmp_name'] != '' || $template != '') {
			if ( $_FILES['soubor']['tmp_name'] != '') {
				if (!file_exists(PHPINC_DIR . '/temp/upload/')) {
					mkdir(PHPINC_DIR . '/temp/upload/', 0777);
				}
				$tmpFileName =  PHPINC_DIR . '/temp/upload/' . md5(uniqid(rand(), true)) . '.xml';
				if (is_uploaded_file($_FILES['soubor']['tmp_name'])) {
					move_uploaded_file($_FILES['soubor']['tmp_name'], $tmpFileName);
				} else {
					$tmpFileName = '';
				}
			} else {
				$tmpFileName =  $template;
			}
			if ($tmpFileName != '') {
				//$prevod = DomainCodes2List();
				$xmlstring = file_get_contents($tmpFileName);
				$importer = new MetadataImport();
				$importer->setTableMode('tmp');
				$report = $importer->import(
								$xmlstring,
								$md['file_type'],
								MICKA_USER,
								$md['edit_group'],
								$md['view_group'],
								$md['mds']=0, // co to je?
								$md['lang'], // co to je?
								$lang_main,
								$params=false,
								$md['update_type'],
								$md['md_rec']
				);
			} else {
				$rs['report'] = "File error!";
				return $rs;
			}
		}
		elseif ($md['url'] != '') {
			setMickaLog($md['url'], 'INFO', 'micka_main_lib.main_insert.url');
			$importer->setTableMode('tmp');
			$report = $importer->importService(
							$md['url'],
							$md['service_type'],
							MICKA_USER,
							$md['edit_group'],
							$md['view_group'],
							$md['mds']=10, // co to je?
							$md['lang'], // co to je?
							$lang_main,
							$public=0,
							$md['update_type']
			);
		} else {
			// není zadán soubor ani url
			$rs['report'] = "Unknown import type!";
			return $rs;
		}
	}
	
	// zpracování reportu po importu
	if ($report[0]['ok'] == 1) {
		if (substr($report[0]['report'],0,11) == 'UUID exists') {
			$rs['report'] = "UUID exists, stop import!";
			$rs['akce'] = 'error';
			$rs['data'] = $report[0];
			return $rs;
		}
		else {
			$rs['ok'] = TRUE;
			$rs['report'] = $report[0]['report'];
			$md_record->setTableMode('tmp');
			$record = $md_record->getMd('uuid', $report[0]['uuid']);
			if (is_array($record) && count($record) > 1) {
				$rs['akce'] = IMPORT_VALID ? 'import' : 'edit';
				if ($rs['akce'] == 'edit') {
					$rs['data'] = $record['md']['RECNO'];
				}
				else {
					$rs['data']['md'] = $record['md'];
				}
			}
		}
	} else {
		$rs['report'] = $report[0]['report'];
		$rs['data'] = $report[0];
		return $rs;
	}
	
	if ($rs['akce'] == 'edit') {
		$redirectUrl = substr(htmlspecialchars($_SERVER['PHP_SELF']), 0, strrpos($_SERVER['PHP_SELF'], '/')) . '?ak=edit&recno=new';
		setUrlEdit2Session($redirectUrl);
	} else {
		// TODO: zatím jen editace
		$redirectUrl = substr(htmlspecialchars($_SERVER['PHP_SELF']), 0, strrpos($_SERVER['PHP_SELF'], '/')) . '?ak=edit&recno=new';
		setUrlEdit2Session($redirectUrl);
	}
	
	return $redirectUrl;
}

function mainMdFc($akFc, $recno) {
	$rs = array();
	if ($akFc == 'list') {
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
	} elseif ($akFc == 'detail') {
		$md_record = new MdRecord();
		$md_record->setTableMode('md');
		$values = $md_record->getMdValues($recno);
		$rs['uuid'] = $values['md']['UUID'];
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
	}
	return $rs;
}

function mainAdmin($admin_ak) {
	if (canAction('*') === FALSE) {
		require PHPINC_DIR . '/templates/403.php';
	}
	$rs = array();
	$rs['template'] = 'default';
	$rs['label'] = 'default';
	if ($admin_ak == 'default') {
        /*
		//$rs['data'][] = array('action' => 'md_contacts', 'label' => 'Správa kontaktů');
		$rs['data'][] = array('action' => 'hsusers', 'label' => 'Správa uživatelů');
		$rs['data'][] = array('action' => 'profils', 'label' => 'Správa profilů');
		$rs['data'][] = array('action' => 'mdreplace', 'label' => 'Hromadná editace');
		$rs['data'][] = array('action' => 'harvest', 'label' => 'Harvest');
        */
		$rs['data'][] = array('action' => 'hsusers', 'label' => 'Identity management');
		$rs['data'][] = array('action' => 'profils', 'label' => 'Managing profiles');
		$rs['data'][] = array('action' => 'mdreplace', 'label' => 'Bulk edits');
		$rs['data'][] = array('action' => 'harvest', 'label' => 'Harvest');
		$rs['data'][] = array('action' => 'mdsummary', 'label' => 'Summary metadata records');
	}
	else {
		require PHPINC_DIR . '/admin/' . $admin_ak . '/index.php';
		$rs['template'] = $adminDataBox['template'];
		$rs['data'] = $adminDataBox['data'];
		$rs['label'] = $adminDataBox['label'];

	}
	//Debugger::dump($rs);
	return $rs;
}

function mainMdSearch() {
	/*
	 * Nová verze přes CSW
	$query = array('TEMPLATE' => 'micka2htmlList_', 'FORMAT' => 'text/html', 'QUERY' => '');
	require PHPPRG_DIR .  '/Csw.php';
	$csw = new Csw;
	$rs = $csw->run($csw->dirtyParams($query));
	 * 
	 */
	require PHPPRG_DIR . '/micka_lib_search.php';
    $post = array();
    if (isset($_GET['fc']) && $_GET['fc'] == 1) {
        $post['fc'] = 1;
    }
	$post['mode'] = 'master';
	$post['kw'] = '1';
	$post['tvar'] = '1';

	$post['text'] = isset($_POST['text']) && $_POST['text'] != '' ? $_POST['text'] :  '';
	$post['category'] = isset($_POST['category']) && $_POST['category'] != '' ? $_POST['category'] :  -1;
	$post['pg'] = isset($_GET['pg']) && $_GET['pg'] != '' ? $_GET['pg'] :  '';
	$rs = array();
	$rs['akce'] = 'search';
	$rs['ok'] = FALSE;
	$rs['report'] = '';
	$rs['data']['paginator']['records'] = 0;
	$founds = getFoundsRecords($post, MICKA_USER);
	if ($founds === -1) {
		$rs['akce'] = 'error';
		$rs['report'] = "500: System error";
	}
	if ($founds['paginator']['records'] > 0) {
		$rs['ok'] = TRUE;
		$rs['akce'] = 'founds';
		$rs['data'] = $founds;
	}
	$rs['search']['text'] = isset($_SESSION['micka']['search']['text'])  && $_SESSION['micka']['search']['text'] != '' ? $_SESSION['micka']['search']['text'] : '';
	$rs['search']['category'] = isset($_SESSION['micka']['search']['category'])  && $_SESSION['micka']['search']['category'] != '' ? $_SESSION['micka']['search']['category'] : '-1';
	//Debugger::dump($rs);
	//Debugger::dump($_SESSION['micka']);
	return $rs;
}

function main_rec_admin($post) {
	$rs = array();
	$rs['akce'] = 'error';
	$rs['ok'] = FALSE;
	$rs['report'] = '';
	$rs['data'] = '';

	//$recno = ($post['recno'] != '') ? htmlspecialchars($post['recno']) : -1;
	$recno = ($_REQUEST['recno'] != '') ? htmlspecialchars($_REQUEST['recno']) : -1;
	if ($recno == -1) {
		setMickaLog('Recno=-1, not edit', 'ERROR', 'micka_main_lib.main_rec_admin');
		$rs['report'] = 'Bad input! (recno -1)';
		return $rs;
	}

	$form_akce = isset($post['form_akce']) ? htmlspecialchars($post['form_akce']) : 'new';
	$md_record = new MdRecord();
	$md_record->setTableMode('tmp');

	if ($form_akce == 'new'){
		// editační formulář
		$record = $md_record->getMdValues($recno, 'xxx', 0, 0);
		if (isset($record['report']) && $record['report'] != 'ok') {
			setMickaLog("Recno=$recno, " . $record['report'], 'ERROR', 'micka_main_lib.main_rec_admin');
			$rs['report'] = $record['report'];
			return $rs;
		}
		$rs['ok'] = TRUE;
		$rs['akce'] = 'record_admin';
		$rs['data']['form_akce'] = 'new';
		$rs['data']['RECNO'] = $record['md']['RECNO'];
		//$rs['data']['DATA_TYPE'] = $record['md']['DATA_TYPE'];
		$rs['data']['LANG'] = $record['md']['LANG'];
		//$rs['data']['EDIT_GROUP'] = $record['md']['EDIT_GROUP'];
		//$rs['data']['VIEW_GROUP'] = $record['md']['VIEW_GROUP'];
		// jazyk metadat
		$rs['data']['md_lang'] = '';
		if ($record['md']['MD_STANDARD'] == 0 || $record['md']['MD_STANDARD'] == 10) {
			foreach ($record['md_values'] as $row) {
				if ($row['MD_ID'] == 5527) {
					$rs['data']['md_lang'] = $row['MD_VALUE'];
					break;
				}
			}
		}
	}
	elseif ($form_akce == 'form' && count($post) > 1) {
		$form_data['recno'] = $recno;
		//$form_data['edit_group'] = $post['edit_group'];
		//$form_data['view_group'] = $post['view_group'];
		//$form_data['data_type'] = isset($post['public']) ? 1 : 0;
		$form_data['lang'] = isset($post['rec_lang']) && count($post['rec_lang']) > 0 ? implode('|', $post['rec_lang']) : '';
		$rs['ok'] = TRUE;
		$rs['akce'] = 'record_admin';
		$rs['data']['form_akce'] = $md_record->setRecordAdmin($form_akce, $form_data);
		$rs['data']['RECNO'] = $recno;
		//$rs['data']['DATA_TYPE'] = '';
		$rs['data']['LANG'] = '';
		//$rs['data']['EDIT_GROUP'] = '';
		//$rs['data']['VIEW_GROUP'] = '';
	}
	elseif ($form_akce == 'form' && count($post) < 0) {
		setMickaLog('Not complete input!', 'ERROR', 'micka_main_lib.main_rec_admin');
		$rs['report'] = 'Not complete input!';
	}
	elseif ($form_akce == 'warning') {
		$recno = isset($post['recno']) ? htmlspecialchars($post['recno']) : -1;
		$form_data['recno'] = $_SESSION['micka']['rec_admin']['recno'];
		//$form_data['edit_group'] = $_SESSION['micka']['rec_admin']['edit_group'];
		//$form_data['view_group'] = $_SESSION['micka']['rec_admin']['view_group'];
		//$form_data['data_type'] = $_SESSION['micka']['rec_admin']['data_type'];
		$form_data['lang'] = $_SESSION['micka']['rec_admin']['lang'];
		unset($_SESSION['micka']['rec_admin']);
		$rs['ok'] = TRUE;
		$rs['akce'] = 'record_admin';
		$rs['data']['form_akce'] = $md_record->setRecordAdmin($form_akce, $form_data);
	}
	return $rs;
}

function mainDetailAll($uuid) {
	$mode = 'detailall';
	$rs = array();
	$rs['akce'] = 'error';
	$rs['ok'] = FALSE;
	$rs['report'] = '';
	$rs['data'] = '';

	require PHPPRG_DIR . '/MdDetail.php';
	$detail = new MdDetail();
	$summary = $detail->getMdRecord(MICKA_USER, $uuid, $mode);
	if ($summary['data'] === FALSE) {
		//setMickaLog("UUID '$uuid' not exist or not right", 'ERROR', 'micka_main_lib.main_detail.return');
		$rs['report'] = "UUID '$uuid' not exist or not right";
		require PHPINC_DIR . '/templates/404_record.php';
	} else {
		$rs['data'] = $summary;
		$rs['akce'] = $mode;
		$rs['ok'] = TRUE;
	}
	//Debugger::dump($rs); exit;
	return $rs;
}

function getLabelResourceType($data) {
	$rs = '';
	if (is_array($data) && isset($data['data']['data'])) {
		if (is_array($data['data']['data'])) {
			foreach ($data['data']['data'] as $row) {
				if ($row['id'] == 122) {
					$rs = $row['hodnota'];
					break;
				}
			}
		}
	}
	return $rs;
}

function mainGetTitleRecord($uuid, $table='md') {
	$rs = '';
	if ($uuid != '') {
		$record = mainDetailAll($uuid);
		$rs = isset($record['data']['head']['title']) && $record['data']['head']['title'] != ''
						? $record['data']['head']['title']
						: '';
	}
	return $rs;
}