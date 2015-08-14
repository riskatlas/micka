<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * summary records
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20141014
 */

require PHPINC_DIR . '/admin/mdsummary/app/mdsummary_lib.php';

$mdReplaceAction = isset($_REQUEST['mdsummary_ak']) && $_REQUEST['mdsummary_ak'] != '' 
		? htmlspecialchars($_REQUEST['mdsummary_ak'])
		: '';

$adminDataBox = array();
$adminDataBox['template'] = PHPINC_DIR . '/admin/mdsummary/templates/mdSummary.latte';
$adminDataBox['label'] = 'Summary metadata records';
$mdSummaryAction = '';
$adminDataBox['data'] = adminMdSummary($mdSummaryAction);

?>
