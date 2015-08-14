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

require PHPINC_DIR . '/admin/mdreplace/app/mdreplace_lib.php';

$mdReplaceAction = isset($_REQUEST['mdreplace_ak']) && $_REQUEST['mdreplace_ak'] != '' 
		? htmlspecialchars($_REQUEST['mdreplace_ak'])
		: '';

$adminDataBox = array();
$adminDataBox['template'] = PHPINC_DIR . '/admin/mdreplace/templates/mdReplace.latte';
$adminDataBox['label'] = 'Bulk edits';
$adminDataBox['data'] = adminMdReplace($mdReplaceAction);

?>
