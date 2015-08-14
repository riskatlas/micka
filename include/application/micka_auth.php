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
 * @version    20130403
 */

require PHPPRG_DIR . '/micka_lib_auth.php';
require AUTHLIB_FILE;

// login
if (isset($_REQUEST['user'])) {
  if (prihlaseni($_REQUEST['user'], isset($_REQUEST['pwd']) ?  $_REQUEST['pwd'] : '') === FALSE ) {
		Debugger::log('[micka_auth.php] ' . 'LOGIN error, ' . $_REQUEST['user'] . ' invalid username or password', 'INFO');
		if ($_SESSION['hs_lang'] == 'cze') {
			setFlashMessage('Chybné přihlášení: Neznámé uživatelské jméno ' . $_REQUEST['user'] . ' nebo špatné heslo.', 'error');
		} else {
			setFlashMessage('Login error: Invalid username ' . $_REQUEST['user'] . ' or password.', 'error');
		}
		if (!prihlaseni('guest', '')) {
			if(isset($hlaska) && $hlaska != '') {
				Debugger::log('[micka_auth.php] ' . "hlaska: $hlaska", 'INFO');
			}
			Debugger::log('[micka_auth.php] ' . 'Not available for guest.', 'INFO');
			require PHPINC_DIR . '/templates/403.php';
		}
		require PHPPRG_DIR . '/redirect.php';
  }
  getProj();
	require PHPPRG_DIR . '/redirect.php';
} else
	if (isset($_SESSION["u"]) === FALSE || empty($_SESSION['ms_groups'])) {
		// guest
		if (!prihlaseni('guest', '')) {
			if(isset($hlaska) && $hlaska != '') {
				Debugger::log('[micka_auth.php] ' . "hlaska: $hlaska", 'INFO');
			}
			Debugger::log('[micka_auth.php] ' . 'Not available for guest.', 'INFO');
			require PHPINC_DIR . '/templates/403.php';
		}
		getProj();
		//require PHPPRG_DIR . '/redirect.php';
	}

if(!canMap(MICKA_PROJECT)) { // kontrola, zda uzivatel smi k projektu
	Debugger::log('[micka_auth.php] ' . 'Not right to project ' . MICKA_PROJECT, 'INFO');
	require PHPINC_DIR . '/templates/403.php';
}

if ($_SESSION["u"] == '') {
	Debugger::log('[micka_auth.php] ' . 'LOGIN error, Not right to Micka.', 'INFO');
	require PHPINC_DIR . '/templates/403.php';
} else {
	define('MICKA_USER', $_SESSION['u']);
	define('MICKA_USER_GROUPS', $_SESSION['ms_groups']);
	define('MICKA_USER_RIGHT', $_SESSION['maplist'][MICKA_PROJECT]['users'][MICKA_USER]); // FIXME: neřeší skupiny!!!
}

