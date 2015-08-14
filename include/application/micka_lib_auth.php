<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * AuthLib for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20121026
 */



function canActionAcl($type, $resource, $privilege) {
	$rs = FALSE;
	
	if (isset($_SESSION['micka']['acl']) === TRUE) {
		if (array_key_exists($resource, $_SESSION['micka']['acl'][$type])) {
			if (strpos($_SESSION['micka']['acl'][$type][$resource], $privilege) !== FALSE) {
				$rs = TRUE;
			}
		}
	} else {
		// starý způsob
		$rs = canAction($privilege);
	}
	return $rs;
}

function canAction($right='w') {
  if (!$_SESSION['maplist']) {
		return FALSE;
	}
  $pom = explode(" ", $_SESSION['ms_groups']);
  foreach($pom as $group) {
		if (!array_key_exists($group, $_SESSION['maplist'][MICKA_PROJECT]['users'])) {
			continue;
		}
		if (strpos('.' . $_SESSION['maplist'][MICKA_PROJECT]['users'][$group], $right)) {
			return TRUE;
		}
	}
	return FALSE;
}

function canMap($mapa){
  if (!$mapa) return false;
  if(!$_SESSION["maplist"])return false;
  reset($_SESSION["maplist"]);
  while(list($key, $value) = each($_SESSION["maplist"])) {
    if($key == $mapa) return true;
  }
  return false;
}
