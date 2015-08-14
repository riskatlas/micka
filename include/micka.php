<?php

/**
 * 
 * @version 20140911
 * 
 */
session_start();
if (!isset($_SESSION['hs__'])){
   session_regenerate_id();
   $_SESSION['hs__'] = true;
}


require MICKA_DIR . '/include/application/micka_config.php';
require PHPPRG_DIR . '/micka_lib.php';
require PHPPRG_DIR . '/micka_auth.php';
require PHPPRG_DIR . '/micka_main_lib.php';
require PHPPRG_DIR . '/MdRecord.php';

// změna jazyka
if (isset($_REQUEST['l']) && $_REQUEST['l'] != '') {
  $_SESSION['hs_lang'] = htmlspecialchars($_REQUEST['l']);
	require PHPPRG_DIR . '/redirect.php';
}
// callback
if(isset($_REQUEST['cb'])){
    $_SESSION['micka']['cb'] = htmlspecialchars($_REQUEST['cb']);
}

// výchozí skupiny pro nový záznam
$defaultEditGroup = DEFAULT_EDIT_GROUP != '' 
        ? getDefaultGroup(DEFAULT_EDIT_GROUP, MICKA_USER) 
        : MICKA_USER;
$defaultViewGroup = DEFAULT_VIEW_GROUP != '' 
        ? getDefaultGroup(DEFAULT_VIEW_GROUP, MICKA_USER) 
        : MICKA_USER;

// akce
$ak = getAction($_REQUEST, $akDefault);
switch ($ak) {
//==============================================================================
	case 'find':
	case 'detail':
	case 'detailfc':
//==============================================================================
		require PHPPRG_DIR .  '/Csw.php';
		$csw = new Csw;
        $request = $_REQUEST;
		if ($ak == 'detail') {
			$record['data']['md']['UUID'] = isset($_REQUEST['uuid']) && $_REQUEST['uuid'] != ''
					? htmlspecialchars($_REQUEST['uuid']) 
					: '';
            if ($record['data']['md']['UUID'] != '') {
                $request['service'] = 'CSW';
                $request['request'] = 'GetRecordById';
                $request['version'] = '2.0.2';
                $request['id'] = $record['data']['md']['UUID'];
                $request['language'] = MICKA_LANG;
                $request['format'] = 'text/html';
            }
			setRecordDetail2Session();
		}
		$dataBox = isset($_REQUEST['url']) && $_REQUEST['url'] != ''
						? $csw->getDataFromURL($_REQUEST['url'], htmlspecialchars($_REQUEST['language']))
						: $csw->run($csw->dirtyParams($request));
		if ($ak == 'find') {
			setRecordsMatched2Session();
		}
		break;
//==============================================================================
	case 'detailall':
//==============================================================================
		$uuid  = isset($_GET['uuid']) ? htmlspecialchars($_GET['uuid']) : '';
		$record = mainDetailAll($uuid);
		$record['data']['md']['UUID'] = $uuid;
		break;
//==============================================================================
	case 'insert':
//==============================================================================
		if (canAction('w') === FALSE) {
			require PHPINC_DIR . '/templates/403.php';
		}
        $post = isset($_GET['standard']) && $_GET['standard'] > -1 ? $_GET : $_POST;
		$redirectUrl = mainInsert($post, array('edit_group' => $defaultEditGroup, 'view_group' => $defaultViewGroup));
		if (is_array($redirectUrl) === TRUE) {
			if (isset($redirectUrl['report']) === TRUE) {
				setFlashMessage($redirectUrl['report'], $type = 'error');
			}
			unsetEditValue2Session();
           	$redirectUrl = (isset($post['standard']) && $post['standard'] == 99) 
                    ? $_SERVER["SCRIPT_NAME"] . '?ak=new'
                    : getUrlSearchFromSession();
			//$redirectUrl = getUrlSearchFromSession();
		}
		require PHPPRG_DIR . '/redirect.php';
		break;
//==============================================================================
	case 'edit':
//==============================================================================
		if (canAction('w') === FALSE) {
			require PHPINC_DIR . '/templates/403.php';
		}
		$record = mainEdit($_GET);
		if ($record['akce'] == 'lite') {
			$record = mainEditLite($record);
            $dataBox = $record['lite'];
		}
		break;
//==============================================================================
	case 'save':
//==============================================================================
		if (canAction('w') === FALSE) {
			require PHPINC_DIR . '/templates/403.php';
		}
		$redirectUrl = mainSave($_POST);
		if (is_array($redirectUrl) === TRUE) {
			if (isset($redirectUrl['report']) === TRUE) {
				setFlashMessage($redirectUrl['report'], $type = 'error');
			}
			unsetEditValue2Session();
			$redirectUrl = getUrlSearchFromSession();
		}
		require PHPPRG_DIR . '/redirect.php';
		break;
//==============================================================================
	case 'delete':
//==============================================================================
		$redirectUrl = mainDelete($_GET);
		require PHPPRG_DIR . '/redirect.php';
		break;
//==============================================================================
	case 'valid':
//==============================================================================
		$uuid = isset($_GET['uuid']) ? htmlspecialchars($_GET['uuid']) : '';
		$dataBox = mainValid($uuid);
		$record['data']['md']['UUID'] = $uuid;
		break;
//==============================================================================
	case 'copy':
//==============================================================================
		$recno = isset($_GET['recno']) ? htmlspecialchars($_GET['recno']) : -1;
		$redirectUrl = mainCopy($recno, array('edit_group' => $defaultEditGroup, 'view_group' => $defaultViewGroup));
		require PHPPRG_DIR . '/redirect.php';
		break;



//==============================================================================
	case 'help': // Nápověda
//==============================================================================
		break;
//==============================================================================
	case 'about': // About
//==============================================================================
		break;
//==============================================================================
	case 'contact': // Kontakty
	case 'cookbook': 
	case 'home': 
//==============================================================================
		break;
	case 'i_edit': //Editace záznamu po importu
//==============================================================================
		break;
	case 'i_save': //Uložení záznamu po importu
//==============================================================================
		break;
	case '_insert': // Parametry nového záznamu
//==============================================================================
		break;
	case '_storno': // Storno se zavřením okna
//==============================================================================
		break;
	case 'storno': // Storno
//==============================================================================
		mainCancel();
		break;
//==============================================================================
	case 'new': // Formular pro nový záznam
//==============================================================================
		if (canAction('w') === FALSE) {
			require PHPINC_DIR . '/templates/403.php';
		}
//==============================================================================
	case 'privatize': // Privátní záznam
//==============================================================================
//==============================================================================
	case 'publish': // Zveřejnit záznam
//==============================================================================
		break;
//==============================================================================
	case 'setrights': // Nastavení práv
//==============================================================================
		break;
//==============================================================================
	case 'rec_admin': //Record admin - administrace záznamu
//==============================================================================
		$record = main_rec_admin($_POST);
		break;
//==============================================================================
	case '_save': //Uložení záznamu z okna
//==============================================================================
		break;
//==============================================================================
	case 'md_contacts':
//==============================================================================
		$cont_ak = (isset($_REQUEST['action']) && $_REQUEST['action'] != '') ? htmlspecialchars($_REQUEST['action']) : 'list';
		$cont_id = (isset($_REQUEST['id']) && $_REQUEST['id'] != '') ? htmlspecialchars($_REQUEST['id']) : 0;
		$mds = (isset($_REQUEST['mds']) && $_REQUEST['mds'] != '') ? htmlspecialchars($_REQUEST['mds']) : 'MD';
		require PHPPRG_DIR . '/Contacts.php';
		if ($mds != '') {
			$_SESSION['micka']['contact']['mds'] = $mds;
		}
		if ($mds == '' && isset($_SESSION['micka']['contact']['mds'])) {
			$mds = $_SESSION['micka']['contact']['mds'];
		}
		$md_contacts = new Contacts();
		$contacts = $md_contacts->actionContacts($cont_ak, $cont_id);
		break;
//==============================================================================
	case 'md_crs':
//==============================================================================
		require PHPPRG_DIR . '/md_crs.php';
		break;
//==============================================================================
	case 'md_fc':
//==============================================================================
		$recno = (isset($_GET['recno']) && $_GET['recno'] != '') ? htmlspecialchars($_GET['recno']) : -1;
		$akFc = $recno > -1 ? 'detail' : 'list';
		$fc = mainMdFc($akFc, $recno);
		break;
//==============================================================================
	case 'md_gazcli':
//==============================================================================
		require PHPPRG_DIR . '/md_gazcli.php';
		break;
//==============================================================================
	case 'md_lists':
//==============================================================================
		require PHPPRG_DIR . '/md_lists.php';
		break;
//==============================================================================
	case 'md_search':
//==============================================================================
		//$dataBox = mainMdSearch();
		$data = mainMdSearch();
		break;
//==============================================================================
	case 'xml': // Zobrazení XML
	case '_xml': // Zobrazení XML (samostatné editační okno, z tmp dat)
	case '_sxml': // Stažení XML (samostatné editační okno, z tmp dat)
//==============================================================================
		$table = ($ak[0] == '_') ? 'tmp' : 'md';
		$uuid = isset($_GET['uuid']) ? htmlspecialchars($_GET['uuid']) : '';
		$xsltemplate = isset($_GET['template']) ? htmlspecialchars($_GET['template']) : '';
		$cache = isset($_GET['cache']) && $_GET['cache'] == 'no' ? FALSE : TRUE;
		actionXml($ak, $table, $uuid, $xsltemplate, $cache);
		break;
//==============================================================================
	case 'admin':
//==============================================================================
		$admin_ak = isset($_REQUEST['adm_ak']) ? htmlspecialchars($_REQUEST['adm_ak']) : 'default';
		$data = mainAdmin($admin_ak);
		$record['data']['md']['UUID'] = $data['label'];
		break;
//==============================================================================
	case 'newsearch': // Vyhledavani
//==============================================================================
		$_SESSION['micka']['search'] = '';
	case 'search': // Vyhledavani
//==============================================================================
		break;
	case 'dummy': // Nic
//==============================================================================
        die("");
		break;
	default: // Platnost stránky vypršela
//==============================================================================
		require PHPINC_DIR . '/templates/410.php';
}

//==============================================================================
// R E N D E R
//==============================================================================
$recordTitle = '';
$template = new FileTemplate();
$template->registerHelperLoader('TemplateHelpers::loader');
$template->registerFilter(new LatteFilter);
$template->setFile(PHPINC_DIR . '/templates/micka.latte');

parse_str ($_SERVER['QUERY_STRING'], $url_params);
$template->urlParams = $url_params;
$template->basePath = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
$template->themePath = $template->basePath . '/themes/' . MICKA_THEME;
$template->extjsPath = EXTJS_PATH;
$template->openLayersPath = OPENLAYERS_PATH;
$template->label = getLabelAllAP();
$template->action = $ak;
$template->MICKA_LANG = MICKA_LANG;
$template->MICKA_USER = MICKA_USER;
$template->FORM_SIGN = FORM_SIGN;
$template->admin = canAction('*') ? 1 : 0;
$template->insertRight = canAction('w') ? 1 : 0;
$template->flashes = getFlashMessage();
//$template->flashes = array(0 => array('type' => 'error', 'message' => 'Chybka'));


$template->navigation = $ak == 'edit' || $ak == 'detail' || $ak == 'valid' || $ak == 'admin' || $ak == 'detailall'
				? mainNavigation($ak, $template->label, $record['data']['md']['UUID']) 
				: mainNavigation($ak, $template->label);
//Debugger::dump($template->navigation); exit;
//$template->usrBox = 'volitelný';
//$template->sortBox = 'sort';
//$template->labelBox = 'label';
$template->dataBox = isset($dataBox) ? $dataBox : '';
$template->mickaLangsArr = $micka_langs_arr;
//$template->footBox = 'patička';

if ($ak == 'about') {
	$template->about = array('verApp' => MICKA_VERSION, 'verDb' => getDbVersion());
} elseif ($ak == 'search') {
    $template->hs_initext = $hs_initext;
	/*
	$template->labelButton = getLabelButton();
	//$template->mickaSearch = getMickaSearchFromSession();
	$template->mickaCodeList = array(
			'mdCategory' => getSelectCategory(), 
			'mdStandard' => getSelectStandards(), 
			'mdLang' => getSelectMdLangs()
	);
	 * 
	 */
} elseif ($ak == 'new') {
    $template->mdStandard = getRadioStandards('acl');
    $template->usrGroups = getMsGroups('get_groups');
    $template->edit_group = $defaultEditGroup;
    $template->view_group = $defaultViewGroup;
    $template->mdLangs = getRadioMdLangs(MICKA_LANG);
} elseif ($ak == 'rec_admin') {
    $template->setFile(PHPINC_DIR . '/templates/mickaRecordAdmin.latte');
    $template->form_akce = $record['data']['form_akce'];
    $template->recno = $record['data']['RECNO'];
    $template->langs = getNewMdLangs($record['data']['md_lang']);
    $template->select_langs = explode('|', $record['data']['LANG']);
} elseif ($ak == 'contact' || $ak == 'cookbook' || $ak == 'home') {
    if (file_exists(WWW_DIR . '/themes/' . MICKA_THEME . '/templates/' . $ak . '_' . MICKA_LANG . '.html')) {
        $template->incTemplatePath = WWW_DIR . '/themes/' . MICKA_THEME . '/templates/' . $ak . '_' . MICKA_LANG . '.html';
    } elseif (MICKA_LANG == 'slo' && file_exists(WWW_DIR . '/themes/' . MICKA_THEME . '/templates/' . $ak . '_cze.html')) {
        $template->incTemplatePath = WWW_DIR . '/themes/' . MICKA_THEME . '/templates/' . $ak . '_cze.html';
    } else {
    $template->incTemplatePath = WWW_DIR . '/themes/' . MICKA_THEME . '/templates/' . $ak . '_eng.html';
    }
} elseif ($ak == 'md_contacts') {
    $template->setFile($cont_ak == 'list'
                ? PHPINC_DIR . '/templates/mickaContacts.latte'
                : PHPINC_DIR . '/templates/mickaContactsEdit.latte');
    $template->contacts = $contacts;
    $template->mds = $mds;
} elseif ($ak == 'md_fc') {
    $template->setFile(PHPINC_DIR . '/templates/md_fc.latte');
    $template->fc = $fc;
} elseif ($ak == 'md_search') {
    $template->setFile(PHPINC_DIR . '/templates/md_search.latte');
    $template->data = isset($data['data']['data']) && is_array($data['data']['data']) ? $data['data']['data'] : array();
    $template->paginator = isset($data['data']['paginator']) ? $data['data']['paginator'] : array();
    $template->search = isset($data['search']) ? $data['search'] : array();
    $template->mdCategory = getSelectCategory();
} elseif ($ak == 'admin') {
    $template->adminData = $data;
} elseif ($ak == 'detail') {
    $recordTitle = mainGetTitleRecord($record['data']['md']['UUID']);
} elseif ($ak == 'detailall') {
    $template->label_sd = isset($record['data']['head']['title']) && $record['data']['head']['title'] != ''
                ? $record['data']['head']['title']
                : getLabelStandard($record['data']['head']['mds']);
    $template->label_resource_type = getLabelResourceType($record);
    $template->values = $record['data']['data'];
    $template->rec = $record['data']['head'];
    $template->label_el = getLabelEl($record['data']['head']['mds']);
    $template->hs_wms = getHsWms(MICKA_LANG, $hs_wms);
    $recordTitle = $template->label_sd;
} elseif ($ak == 'edit') {
    //$template->form_public = $form_public;
    $template->publisher = $record['publisher'];
    $template->saver = $record['saver'];
    $template->edit_group = $record['data']['md']['EDIT_GROUP'];
    $template->view_group = $record['data']['md']['VIEW_GROUP'];
    $template->groups = getMsGroups('get_groups', $record['data']['md']['EDIT_GROUP'] . '|' . $record['data']['md']['VIEW_GROUP']);
    $template->hierarchy = isset($record['hierarchy']) ? $record['hierarchy'] : '';
    $template->mds = $record['data']['md']['MD_STANDARD'];
    $template->recno = $record['data']['md']['RECNO'];
    $template->uuid = $record['data']['md']['UUID'];
    $template->dataType = $record['data']['md']['DATA_TYPE'];
    $template->MdDataTypes = getMdDataType($template->label);
    $template->formData = isset($record['data']['md_values']) ? $record['data']['md_values'] : '';
    $template->keywordsDataUri = $record['data']['keywords_uri'];
    $template->formEnd = isset($record['data']['md_values_end']) ? $record['data']['md_values_end'] : '';
    $template->profils = getMdProfils(MICKA_LANG, $record['data']['md']['MD_STANDARD']);
    $template->packages = getMdPackages(MICKA_LANG, $record['data']['md']['MD_STANDARD'], $record['data']['profil']);
    $template->selectProfil = $record['data']['profil'];
    $template->langs = $record['data']['md']['LANG'];
    $template->selectPackage = (isset($record['data']['package']) && $record['data']['package'] > -1) 
                    ? $record['data']['package'] 
                    : 1;
    $template->title = $record['data']['md']['TITLE'];
    $template->titleProfil = getLabelProfil($record['data']['profil']);
    //$template->control = '';
    $template->mdControl = ($record['data']['md']['MD_STANDARD'] == 0 || $record['data']['md']['MD_STANDARD'] = 10) 
                    ? mdControl($record['data']['md']['PXML'])
                    : array();
    $recordTitle = $record['data']['md']['TITLE'];
}
$template->pageTitle = mainPageTitle($template->navigation, $recordTitle);
dibi::disconnect();
$template->render();
