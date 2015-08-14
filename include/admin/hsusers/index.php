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

require PHPINC_DIR . '/admin/hsusers/app/hs_sprava.php';

$hsUserAction = isset($_REQUEST['hsuserak']) && $_REQUEST['hsuserak'] != '' 
		? htmlspecialchars($_REQUEST['hsuserak'])
		: '';

$adminDataBox = array();
$adminDataBox['template'] = PHPINC_DIR . '/admin/hsusers/templates/hsUser.latte';
//$adminDataBox['label'] = 'Správa uživatelů';
$adminDataBox['label'] = 'Identity management';
$adminDataBox['data'] = hsSpravaUsers($hsUserAction);

?>
