<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * User edit for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20140523
 */

require PHPINC_DIR . '/admin/profils/app/profils_lib.php';

$profilAction = isset($_REQUEST['profil_ak']) && $_REQUEST['profil_ak'] != '' 
		? htmlspecialchars($_REQUEST['profil_ak'])
		: '';

$adminDataBox = array();
$adminDataBox['template'] = $profilAction == 'newp' 
				? PHPINC_DIR . '/admin/profils/templates/edit.latte'
				: PHPINC_DIR . '/admin/profils/templates/profils.latte';
//$adminDataBox['label'] = 'Správa profilů';
$adminDataBox['label'] = 'Managing profiles';
$adminDataBox['data'] = adminProfils($profilAction);

?>
