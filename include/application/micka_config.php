<?php
/**
 * Načtení konfigurace z cfg/config.ini
 *
 * Zde není třeba nic nastavovat, konfiguraci provádět v config.ini
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20140606
 *
 */

define("MICKA_VERSION", "5.003");

function setMickaPath($path) {
	if (preg_match('[%wwwDir%]', $path)) {
		$path = str_replace('%wwwDir%', WWW_DIR, $path);
	}
	return $path;
}

/**
 * Jazyk aplikace dle iso
 * http://www.loc.gov/standards/iso639-2/php/code_list.php
 * 
 * @param string $lang
 * @param array $app_langs
 */
function getIsoLang($lang, $app_langs) {
	// překlady jazyků
	$lang_code = array('cs'=>'cze','cz'=>'cze','en'=>'eng','sk'=>'slo');
	if (array_key_exists($lang, $lang_code)) {
		$lang = $lang_code[$lang];
	}
	if (strlen($lang) != 3 || array_search($lang, $app_langs) === FALSE) {
		$lang = $app_langs[0]; // nastaven 1. jazyk z config.ini
	}
	$_SESSION['hs_lang'] = $lang;
	return $lang;
}


// možnost vypnout přihlašování do DB
$dbconnect = isset($dbconnect) && $dbconnect === FALSE ? FALSE : TRUE;
$database = array();


// Nastavení adresářové struktury MicKy
define('WWW_DIR', dirname(__FILE__) . '/../..');
//define('WWW_DIR', MICKA_DIRw ? MICKA_DIR : dirname(__FILE__) . '/../..');
define('PHPCFG_DIR', WWW_DIR . '/cfg');
define('PHPINC_DIR', WWW_DIR . '/include');
define('PHPLIB_DIR', WWW_DIR . '/include/library');
define('PHPPRG_DIR', WWW_DIR . '/include/application');
define('CSW_TMP', WWW_DIR . '/include/temp');
define('CSW_LOG', WWW_DIR . '/include/logs');
define("CSW_XSL", WWW_DIR . "/include/xsl");


// Nette 
require PHPLIB_DIR . '/Nette/loader.php';

// Nastavení logování chyb PHP
// DEVELOPMENT: chyby se zobrazují rovnou na stránce
// PRODUCTION: logování chyb do zadaného souboru
Debugger::enable(Debugger::PRODUCTION, CSW_LOG);

// načtení INI souboru
if (file_exists(PHPCFG_DIR .  "/config.ini")) {
	$configIni = new ConfigIniAdapter;
	$config = $configIni->load(PHPCFG_DIR . "/config.ini");
}
else {
	// bez ini souboru nelze spustit
	Debugger::log('[micka_config.php] ' . 'ERROR, not found CONFIG.INI!', 'ERROR');
	require PHPINC_DIR . '/templates/500.php';
}
//Debugger::dump($config);
// zpracování INI souboru
if (is_array($config) && count($config) > 0) {

	

	// sekce php
	$config['php']['timeZone'] = isset($config['php']['timeZone']) === FALSE 
					? 'Europe/Prague' 
					: $config['php']['timeZone'];
	date_default_timezone_set($config['php']['timeZone']);
	
	// sekce path
	define("EXTJS_PATH", isset($config['path']['extjs']) 
					? setMickaPath($config['path']['extjs']) 
					: '');
	define("CATCLIENT_PATH", isset($config['path']['catClientPath']) 
					? setMickaPath($config['path']['catClientPath']) 
					: '');
	define("OPENLAYERS_PATH", isset($config['path']['openLayersPath']) 
					? setMickaPath($config['path']['openLayersPath']) 
					: '');
	
	// sekce micka
	define("MICKA_THEME", isset($config['micka']['theme']) 
					? setMickaPath($config['micka']['theme']) 
					: 'default');
	define("MICKA_URL", isset($config['micka']['mickaURL']) 
					? setMickaPath($config['micka']['mickaURL']) 
					: '');
	define("MICKA_PROJECT", isset($config['micka']['project']) 
					? setMickaPath($config['micka']['project']) 
					: 'micka');
	define("MICKA_LANGS_STR", isset($config['micka']['mickaLangs']) 
					? setMickaPath($config['micka']['mickaLangs']) 
					: 'eng');
	// nastavení jazyka micky
	if (array_key_exists('hs_lang', $_SESSION) === FALSE) {
		$_SESSION['hs_lang'] = '';
	}
	$micka_langs_arr = explode(',', MICKA_LANGS_STR);
	if (array_key_exists('language', $_GET) === TRUE) {
		define('MICKA_LANG', getIsoLang(htmlspecialchars($_GET['language']), explode(',', MICKA_LANGS_STR)));
	} else {
		define('MICKA_LANG', getIsoLang($_SESSION['hs_lang'], explode(',', MICKA_LANGS_STR)));
	}
	define("MICKA_CHARSET", isset($config['micka']['mickaCharset']) 
					? setMickaPath($config['micka']['mickaCharset']) 
					: 'UTF-8');
	define("START_PROFIL", isset($config['micka']['startProfil']) 
					? setMickaPath($config['micka']['startProfil']) 
					: '0');
	define("MAXRECORDS", isset($config['micka']['maxRecords']) 
					? setMickaPath($config['micka']['maxRecords']) 
					: '25');
	define("LIMITMAXRECORDS", isset($config['micka']['limitMaxRecords']) 
					? setMickaPath($config['micka']['limitMaxRecords']) 
					: '75');
	define("SORT_BY", isset($config['micka']['sortBy']) 
					? setMickaPath($config['micka']['sortBy']) 
					: 'title,ASC');
	define("MD_TIME_LOCK", isset($config['micka']['timeLock']) 
					? setMickaPath($config['micka']['timeLock']) 
					: '60');
	$akDefault = isset($config['micka']['defaultAction']) 
					? setMickaPath($config['micka']['defaultAction']) 
					: '';
	define("VALIDATOR", isset($config['micka']['validator']) && $config['micka']['validator'] == 1
					? TRUE 
					: FALSE);
	define("IMPORT_VALID", isset($config['micka']['importReport']) && $config['micka']['importReport'] == 1
					? TRUE 
					: FALSE);
	define("FORM_PUBLIC", isset($config['micka']['formPublic']) && $config['micka']['formPublic'] == 1
					? TRUE 
					: FALSE);
	
	// sekce auth
	define('AUTHLIB_FILE', setMickaPath($config['auth']['authFile']));
	$prjPath = isset($config['auth']['prjFile']) ? setMickaPath($config['auth']['prjFile']) : '';
	$pwdfname = isset($config['auth']['pwdFile']) ? setMickaPath($config['auth']['pwdFile']) : '';
	define("AUTH_PUBLISH", isset($config['auth']['publish']) && $config['auth']['publish'] != ''
					? $config['auth']['publish']
					: '');
	define("FORM_SIGN", isset($config['auth']['formSign']) && $config['auth']['formSign'] == ''
					? FALSE 
					: TRUE);
	define("DEFAULT_EDIT_GROUP", isset($config['auth']['defaultEditGroup']) && $config['auth']['defaultEditGroup'] != ''
					? $config['auth']['defaultEditGroup']
					: '');
	define("DEFAULT_VIEW_GROUP", isset($config['auth']['defaultViewGroup']) && $config['auth']['defaultViewGroup'] != ''
					? $config['auth']['defaultViewGroup']
					: '');

	// sekce map
	$hs_wms["cze"] = isset($config['map']['hs_wms_cze']) && $config['map']['hs_wms_cze'] != ''
					? $config['map']['hs_wms_cze']
					: '';
	$hs_wms["eng"] = isset($config['map']['hs_wms_eng']) && $config['map']['hs_wms_eng'] != '' 
					? $config['map']['hs_wms_eng']
					: '';
	$hs_initext = isset($config['map']['hs_initext']) && $config['map']['hs_initext'] != '' 
					? $config['map']['hs_initext'] 
					: '';
	$wms_client_def = isset($config['map']['wmsClient']) && $config['map']['wmsClient'] != '' 
					? $config['map']['wmsClient']
					: '';
	$wms_client_cze = isset($config['map']['wmsClientCze']) && $config['map']['wmsClientCze'] != '' 
					? $config['map']['wmsClientCze']
					: '';
	$wms_client_eng = isset($config['map']['wmsClientEng']) && $config['map']['wmsClientEng'] != '' 
					? $config['map']['wmsClientEng']
					: '';
	
	// volitelné sekce
	if (isset($config['micka']['optionSections']) && $config['micka']['optionSections'] != '') {
		foreach (explode(',', $config['micka']['optionSections']) as $value) {
			// sekce HSRS
			if (trim($value) == 'hsrs') {
				if (isset($config['hsrs']['ladenka']) && $config['hsrs']['ladenka'] == 1) {
					error_reporting(E_ALL ^ E_NOTICE);
					//Debugger::$strictMode = TRUE;
					Debugger::enable(Debugger::DEVELOPMENT, CSW_LOG);
				}
			}
			// sekce LIFERAY
			if (trim($value) == 'liferay') {
				define("LIFERAY_VALIDATE_URL", isset($config['liferay']['userValidateUrl'])
								? $config['liferay']['userValidateUrl'] 
								: '');
				//TODO
				//pathData = /web/guest/my/data
				//pathService = /web/guest/my/services
			}
			// sekce datatype
			if (trim($value) == 'datatype') {
				$md_data_type = isset($config['datatype']['mdDataType'])
								? $config['datatype']['mdDataType'] 
								: '';
			}
		}
	}
    define("MD_DATA_TYPE", isset($md_data_type)
                    ? $md_data_type 
                    : '');

	// sekce database
	if ($dbconnect === TRUE) {
		define("DB_FULLTEXT", isset($config['database']['dbFulltext']) && $config['database']['dbFulltext'] != ''
						? $config['database']['dbFulltext']
						: '');
		define("SPATIALDB", isset($config['database']['spatialDb']) && $config['database']['spatialDb'] != ''
						? $config['database']['spatialDb']
						: '');
		define("TMPTABLE_PREFIX", isset($config['database']['tmpTablePrefix']) && $config['database']['tmpTablePrefix'] != ''
						? $config['database']['tmpTablePrefix']
						: 'edit');
		define("DB_DRIVER", isset($config['database']['driver']) && $config['database']['driver'] != ''
						? $config['database']['driver']
						: '');
		$database['driver'] = DB_DRIVER;
		$database['profiler'] = Debugger::$productionMode === TRUE ? FALSE : TRUE;
		//$database['profiler'] = TRUE;
		if (isset($config['database']['host']) && $config['database']['host'] != '') {
			$database['host'] = $config['database']['host'];
		}
		if (isset($config['database']['lazy']) && $config['database']['lazy'] != '') {
			$database['lazy'] = $config['database']['lazy'];
		}
		if (isset($config['database']['persistent']) && $config['database']['persistent'] != '') {
			$database['persistent'] = $config['database']['persistent'];
		}
		if (isset($config['database']['password']) && $config['database']['password'] != '') {
			$database['password'] = $config['database']['password'];
		}
		if (DB_DRIVER == 'mssql2005') {
			$database['username'] = $config['database']['user'];
			$database['database'] = $config['database']['database'];
			$database['charset'] = $config['database']['charset'];
		} elseif (DB_DRIVER == 'oracle') {
			$database['username'] = $config['database']['user'];
			$database['database'] = $config['database']['database'];
			$database['charset'] = $config['database']['charset'];
		} elseif (DB_DRIVER == 'postgre') {
			$database['port'] = $config['database']['port'];
			$database['user'] = $config['database']['user'];
			$database['dbname'] = $config['database']['database'];
			$database['charset'] = $config['database']['charset'];
		} else {
			$dbconnect === FALSE;
		}
	}
	// připojení DB
	if ($dbconnect === TRUE) {
		require PHPLIB_DIR . '/dibi/dibi.php';
		try {
			dibi::connect($database);
		}
		catch (DibiException $e) {
			Debugger::log('[micka_config.php] ' . get_class($e) . ': ' . $e->getMessage() . "\n", 'ERROR');
			require PHPINC_DIR . '/templates/500.php';
		}
	}
	unset($database);
	unset($config);
	if ($dbconnect === TRUE) {
		require PHPPRG_DIR . '/micka_lib_db_' . DB_DRIVER . '.php';
	}
}
