<?php

function prihlaseni($user, $password) {
	$result = FALSE;
	
	if (1 == 1) {
		$_SESSION['u'] = 'hsrs';
		$_SESSION['ms_groups'] = 'hsrs admin';
		$_SESSION['group_names'] = array('hsrs' => 'hsrs', 'admin' => 'admin');
		$result = TRUE;
	}
	
	if ($result === FALSE) {
		// guest
		$_SESSION['u'] = 'guest';
		$_SESSION['ms_groups'] = 'guest';
		$_SESSION['maplist']['micka']['users']['guest'] = 'r';
		$result = TRUE;
	}
	return $result;
}

/**
 * Returns available projects for logged user
 * @returns array
 */
function getProj() {
	$projects = null;
	if (1 == 1) {
		$_SESSION['micka']['acl']['MDS'] = array('MD' => 'rw', 'MS' => 'rw', 'MC' => 'r', 'DC' => 'r', 'FC' => 'rw');
	}    
	return $projects;
}

/**
 * Reset map 
 */
function resetMap()
{
    $queryfile = $this->mapa->web->imagepath . '/' . $_SESSION['sid'] . '.qy';
    if (file_exists($queryfile)) {
        unlink($queryfile);
    }
}

function groupExist($group) {
	return IBMGeoportalSSO::groupExist($group);
}

// Reset $_SESSION['u']
unset($_SESSION['u']);
unset($_SESSION['ms_groups']);
unset($_SESSION['maplist']);

?>
