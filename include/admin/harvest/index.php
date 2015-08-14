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
 * @version    20140521
 */

require PHPINC_DIR . '/admin/harvest/app/harvest_lib.php';
require PHPPRG_DIR . '/Harvest.php';

$harvestAction = isset($_REQUEST['harvestak']) && $_REQUEST['harvestak'] != '' 
		? htmlspecialchars($_REQUEST['harvestak'])
		: '';

$adminDataBox = array();
$adminDataBox['template'] = $harvestAction == 'edit' 
				? PHPINC_DIR . '/admin/harvest/templates/edit.latte'
				: PHPINC_DIR . '/admin/harvest/templates/list.latte';
$adminDataBox['label'] = 'Harvest';
$adminDataBox['data'] = adminHarvest($harvestAction);

