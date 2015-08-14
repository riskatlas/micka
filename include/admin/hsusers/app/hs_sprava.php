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
 * @version    20141001
 */

function ctiHesla($soubor){
  $sez0 = file($soubor);
  foreach($sez0 as $row){
    $row = trim($row);
    if($row){
      $pom = explode("|",$row);
      $pom1 = explode(",",array_shift($pom));
      $seznam[trim($pom1[0])] = array_merge($pom1,$pom);     
    }  
  } 
  reset($seznam);
  return $seznam;
}

function vypis($pwdfname){
  return ctiHesla($pwdfname);
}

function checkFile($file) {
    $rs = FALSE;
    if (is_readable($file) === TRUE) {
        if (is_writable($file) === TRUE) {
            $rs = TRUE;
        }
    }
    return $rs;
}

function hsSpravaUsers($hsUserAction) {
	global $pwdfname;
	
	$chyba = '';
	$seznam = ctiHesla($pwdfname);
	$rs = array();
	$rs['action'] = $hsUserAction;
    $usr = isset($_REQUEST['usr']) ? htmlspecialchars($_REQUEST['usr']) : '';
    if($usr == 'guest') {
        $rs['data'] = 'Jméno guest nelze použít!';
        $rs['action'] = 'error';
        return $rs;
    }

	switch($hsUserAction) {
		case "edit":
			if ($usr != '' && isset($seznam[$usr])) {
				$rs['data'] = $seznam[$usr];
			} else {
				$rs['data'] = array('','','','','','');
			}
			break;
		case "save":
            if (checkFile($pwdfname) === FALSE) {
				//$rs['data'] = 'The password file is not writable!';
				$rs['data'] = 'Nelze zapisovat do souboru hesel!';
				$rs['action'] = 'error';
				return $rs;
            }
			// --- kontrola existence cloveka
			if($usr == '') {
				$rs['data'] = 'Musí být zadáno jméno!';
				$rs['action'] = 'error';
				return $rs;
			}
			if($_REQUEST['pwd1'] || $usr == 'guest') {
				if($_REQUEST['pwd1'] != $_REQUEST['pwd2']) { 
					$rs['data'] = 'Nesouhlasí hesla!';
					$rs['action'] = 'error';
					return $rs;
				}
				$mdkey = md5($usr.trim(htmlspecialchars($_REQUEST['pwd1'])));
			} else {
				if($usr != '' && $seznam[$usr][0]) {
					$mdkey = $seznam[$usr][2];
				} else {
					$rs['data'] = 'Pro nového uživatele musí být zadáno heslo!';
					$rs['action'] = 'error';
					return $rs;
				}
			}
			$seznam[$usr] = Array(
				trim(htmlspecialchars($_REQUEST['usr'])),
				trim(htmlspecialchars($_REQUEST['groups'])),
				$mdkey,
				trim(htmlspecialchars($_REQUEST['description'])),
				trim(htmlspecialchars($_REQUEST['address'])),
				trim(htmlspecialchars($_REQUEST['email']))
			);
			//---zde ulozeni
			$dataout = '';
			$data = array_values($seznam);
			foreach($data as $row) $dataout .= $row[0].",".$row[1].",".$row[2]."|".$row[3]."|".$row[4]."|".$row[5]."\n";
			file_put_contents($pwdfname, $dataout);
			$rs['data'] = vypis($pwdfname);
			$rs['action'] = '';
			break;

		case "delete":
            if (checkFile($pwdfname) === FALSE) {
				//$rs['data'] = 'The password file is not writable!';
				$rs['data'] = 'Nelze zapisovat do souboru hesel!';
				$rs['action'] = 'error';
				return $rs;
            }
    		// ---smazani zazmamu
			if($usr != '' && $seznam[$usr][0]) {
				if($usr == $_SESSION['u']) {
					$rs['data'] = 'Nemůžeš smazat sebe sama !!!';
					$rs['action'] = 'error';
					return $rs;
				}
				$dataout = '';
				$data = array_values($seznam);
				foreach($data as $row) if($row[0] != $usr) $dataout .= implode(",",$row)."\n";
				file_put_contents($pwdfname, $dataout);      
				vypis($pwdfname);
			} else {
				$rs['data'] = 'Špatný uživatel!';
				$rs['action'] = 'error';
				return $rs;
			}
			$rs['data'] = vypis($pwdfname);
			$rs['action'] = '';
			break;


		default:
			$rs['data'] = $seznam;
			break;     
	}
	return $rs;
}


?>
