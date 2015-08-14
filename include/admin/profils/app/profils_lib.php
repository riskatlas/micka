<?php
function adminProfils($profilAction) {
	require PHPINC_DIR . '/admin/profils/app/Profil.php';
	$rs = array();
	$NTree = new Tree();

	$mds = isset($_GET['mds']) ? htmlspecialchars($_GET['mds']) : 0;
	$mdid = isset($_GET['mdid']) ? htmlspecialchars($_GET['mdid']) : 0;
	$profil = isset($_GET['p']) ? htmlspecialchars($_GET['p']) : '';

	switch ($profilAction) {
	//==========================================================================
		case 'listp': // profil save
	//==========================================================================
			$pr = getMdProfils(MICKA_LANG, $mds);
			
			$template = new FileTemplate();
			$template->setFile(PHPINC_DIR . '/admin/profils/templates/list.latte');
            /* pro PHP 5.3, v PHP 5.2 nefunguje
			$template->onPrepareFilters[] = function($template) {
					$template->registerFilter(new LatteFilter());
			};
             */
            // pro PHP 5.2
            $template->registerHelperLoader('TemplateHelpers::loader');
            $template->registerFilter(new LatteFilter);
            
			$template->basePath = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
			$template->themePath = $template->basePath . '/themes/' . MICKA_THEME;
			$template->label = getLabelAllAP();
			$template->MICKA_USER = MICKA_USER;
			$template->tree_view = $NTree->getListProfil($profil);
			$template->profil_names = $pr[$profil];
			dibi::disconnect();
			//Debugger::dump($template->tree_view); exit;
			$template->render();
			exit;
			break;
	//==========================================================================
		case 'newp':
	//==========================================================================
			$rs['mds'] = $mds;
			$rs['select_true'] = array(0=>'No', 1=>'Yes');
			$rs['profil_names'] = $NTree->getProfilNames($mds, $profil);
			$rs['lite_templates'] = $NTree->getLiteTemplates();
			$rs['copy_profils'] = $NTree->getProfils($mds, TRUE);
			break;
	//==============================================================================
		case 'delpro': // smazani podřízené větve z profilu
	//==============================================================================
			$mdid_ch = isset($_GET['mdid_ch']) ? htmlspecialchars($_GET['mdid_ch']) : 0;
			$mdid_ch	= $mdid_ch == 0 ? -1 : $mdid_ch;
			$profil	= $profil == '' ? -1 : $profil;
			$NTree->deleteMdidFromProfil($mds, $mdid_ch, $profil);
	//==============================================================================
		case 'addpro': // přidání větve do profilu
	//==============================================================================
			if ($profilAction == 'addpro') {
				$mdid_ch = isset($_GET['mdid_ch']) ? htmlspecialchars($_GET['mdid_ch']) : 0;
				$mdid_ch	= $mdid_ch == 0 ? -1 : $mdid_ch;
				$profil	= $profil == '' ? -1 : $profil;
				$NTree->checkProfilMD($profil, $mdid_ch);
			}
	//==========================================================================
		case 'setp':
	//==========================================================================
			if ($profilAction == 'setp') {
				$NTree->setProfilNames($_POST);
			}
	//==========================================================================
		case 'delp':
	//==========================================================================
			if ($profilAction == 'delp') {
				$NTree->delProfilNames($profil);
			}
	//==========================================================================
		case 'change_standard': // change standard
	//==========================================================================
			if (isset($_POST['mds'])) {
				$mds = ($_POST['mds'] == 0 || $_POST['mds'] == 10 || $_POST['mds'] || $_POST['mds'] == 1 || $_POST['mds'] == 2)
					? $_POST['mds']
					: 0;
				$redirectUrl = substr(htmlspecialchars($_SERVER['PHP_SELF']), 0, strrpos($_SERVER['PHP_SELF'], '/')) . '?ak=admin&adm_ak=profils&mds=' . $mds;
				require PHPPRG_DIR . '/redirect.php';
			}
	//==========================================================================
		default:
	//==========================================================================
			$labelnode = $NTree->getLabelNode($mdid, $mds);
			$listnodes = $NTree->getListNodes($mdid, $mds);
			$profily = $NTree->getProfilPath($mds);
			if ($mds == 0 || $mds == 10) {
				$del_profil  = $NTree->deleteMDprofilPath($profily);
				$del_mdid    = $NTree->deleteMDidPath($listnodes);
				if ($del_profil != '' && $del_mdid != '') {
					$sel_profily = $NTree->checkProfilSelect($del_mdid,$del_profil);
				}
			}
			$rs['mds'] = $mds;
			$rs['mdid'] = $mdid;
			$rs['form']['standard'] = $NTree->getMdStandardAll();
			$rs['form']['profil_names'] = $NTree->getProfils($mds);
			$rs['form']['label_node'] = $labelnode;
			$rs['form']['list_nodes'] = $listnodes;
			$rs['form']['list_profil'] = $profily;
			$rs['form']['SelectProfil'] = $sel_profily;
	}
	//Debugger::dump($rs); exit;
	//Debugger::dump($rs['form']); exit;
	return $rs;
}

?>
