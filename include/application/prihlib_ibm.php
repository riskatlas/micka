<?php
/**
 * A class for communication with SSO implementation by IBM. The class communicate
 * with REST service "com.ibm.geoportal.sso-5.2.7.1".
 *
 * @version		20120313
 */
class IBMGeoportalSSO
{
		//*************************************************************************
		// private members
		//*************************************************************************

		// 0: bez logu 1,2 (test bez dotazu na server),3:log do souboru prihlib_ibm.log
		//private static $m_DebugLevel = 0;
		private static $m_DebugLevel = 3;
		private static $m_LeaseInterval = 0;
		private static $m_Token = '';
		private static $m_UserInfo = null;

		/**
		 * Read the response from SSO REST service.
		 * @private
		 * @static
		 * @param string
		 * @returns string
		 */
		private static function getSSOResponse($url)
		{
				if (self::$m_DebugLevel != 2) {
						self::logDebug('Get response from URL: ' . $url, 1);
						$response = file_get_contents($url);
						self::logDebug('Response: ' . $response, 1);
				} else {
						// Note: only for testing
						// >>>
						self::logDebug('Using debugging static JSON response.', 2);
						$response = '{"resultCode":"0","leaseInterval":1800,"userInfo":' .
								'{"email":"test@liferay.com","screenName":"test","firstName":"Test","lastName":"Test",' .
								'"roles":[{"roleName":"Administrator"},{"roleName":"MickaRead"},{"roleName":"User"}]}}';
						// <<<
				}
				return $response;
		}

		/**
		 * Converts the JSON string to the PHP array
		 * @private
		 * @static
		 * @param string
		 * @return array
		 */
		private static function json2array($json)
		{
				$json = substr($json, strpos($json, '{') + 1, strlen($json));
				$json = substr($json, 0, strrpos($json, '}'));
				$json = preg_replace('/(^|,)([\\s\\t]*)([^:]*) (([\\s\\t]*)):(([\\s\\t]*))/s', '$1"$3"$4:', trim($json));
				return json_decode('{'.$json.'}', true);
		}

		/**
		 * Logging debugg message
		 * @private
		 * @static
		 * @param string
		 * @param integer
		 */
		//private static function logDebug($msg, $debugLevel)
		public static function logDebug($msg, $debugLevel)
		{
				if (self::$m_DebugLevel >= $debugLevel) {
					//print($msg . "\n");
					$file = CSW_LOG . "/prihlib_ibm.log";
					$fh = fopen($file, 'at');
					fwrite($fh, date("d.m.Y H:i:s") . ' ' . $msg . "\n");
					fclose($fh);
				}
		}

		//*************************************************************************
		// public members
		//*************************************************************************

		/*
		 * Puvodni definice
		 *
		const GEOPORTAL_HOST = 'http://www.envirogrids.cz';
		const VALIDATE_URL = '/g4i-portlet/service/sso/validate/';
		const MICKA_ROLE_READ = 'MickaRead';
		const MICKA_ROLE_WRITE = 'MickaWrite';
		const MICKA_ROLE_ADMIN = 'MickaAdmin';
		*/

		const MICKA_ROLE_READ = 'mickaRead';
		const MICKA_ROLE_WRITE = 'mickaWrite';
		const MICKA_ROLE_PUBLISH = AUTH_PUBLISH;
		const MICKA_ROLE_ADMIN = 'mickaAdmin';

		/**
		 * Returns the groups of logged user
		 * @static
		 * @return string
		 */
		public static function getGroups()
		{
				$ms_groups = '';
				if (self::$m_UserInfo != null) {
						/*
						// starý způsob
						foreach (self::$m_UserInfo['roles'] as $role) {
								// $ms_groups .= $role['roleName'] . ' '; // PUV
								// nahrazení mezery v názvu role
								$ms_groups .= str_replace(' ', '_' , $role['roleName']) . ' ';
						}
						*/
						foreach (self::$m_UserInfo['groups'] as $row) {
							$ms_groups .= $row['code'] . ' ';
						}

						$ms_groups .= self::$m_UserInfo['screenName'];
				}
				return $ms_groups;
		}

		/**
		 * Returns the groups names of logged user
		 * @static
		 * @return string
		 */
		public static function getGroupsNames()
		{
				$groups_names = array();
				if (self::$m_UserInfo != null) {
					foreach (self::$m_UserInfo['groups'] as $row) {
						$code = $row['code'];
						$name = $row['name'];
						$groups_names[$code] = $name;
					}
					$name = self::$m_UserInfo['screenName'];
					$groups_names[$name] = $name;
				}
				return $groups_names;
		}

		/**
		 * Returns the language application of logged user
		 * @static
		 * @return string
		 */
		public static function getLanguage()
		{
				$app_lang = '';
				if (self::$m_UserInfo != null) {
						$app_lang = self::$m_UserInfo['language'];
				}
				switch ($app_lang) {
					case 'en':
						$app_lang = 'eng';
						break;
					case 'cz':
					case 'cs':
						$app_lang = 'cze';
						break;
					default:
						$app_lang = '';
						break;
				}
				return $app_lang;
		}

		/**
		 * Returns lease time for actual token (session).
		 * @static
		 * @returns integer
		 */
		public static function getLeaseInterval()
		{
				return self::$m_LeaseInterval;
		}

		/**
		 * Returns logged user's name.
		 * @static
		 * @returns integer
		 */
		public static function getUserName()
		{
				$userName = '';
				if (self::$m_UserInfo != null) {
						$userName = self::$m_UserInfo['screenName'];
				}
				return $userName;
		}

		/**
		 * Tests if logged user has assigned role.
		 * @static
		 * @param string	role's name which is tested
		 * @returns bool
		 */
		public static function hasUserRole($roleName)
		{
				$hasRole = false;
				if ($roleName != '' && self::$m_UserInfo != null) {
						foreach (self::$m_UserInfo['roles'] as $role) {
								$hasRole = ($role['roleName'] == $roleName);
								if ($hasRole) {
										break;
								}
						}
				}
				self::logDebug("Test user role: $roleName = $hasRole", 1);
				return $hasRole;
		}

		/**
		 * Tests if token is assigned.
		 * @static
		 * @returns bool
		 */
		public static function isValidToken()
		{
				return (self::$m_Token != '');
		}

		/**
		 * Sets debug level
		 * @static
		 * @param integer
		 */
		public static function setDebugLevel($debugLevel)
		{
				self::$m_DebugLevel = $debugLevel;
		}

		/**
		 * This method provide token validation. If parameter token is empty, previous
		 * assigned token is validated. If the token is valid, information about logged
		 * user is stored.
		 *
		 * @static
		 * @param string
		 * @returns bool
		 */
		public static function validateToken($token)
		{
				self::logDebug('validateToken: ' . $token, 3);

				$result = false;
				if (($token == null) || ($token == '')) {
						$token = self::$m_Token;
				}

				if ($token != '') {
						self::logDebug('Validate token: ' . $token, 1);
						//$url = self::GEOPORTAL_HOST . self::VALIDATE_URL . $token;
						$url = LIFERAY_VALIDATE_URL . '/sso/validate/' . $token;
						$responseJson = self::getSSOResponse($url);
						$response = self::json2array($responseJson);
						self::logDebug('responseJson: ' . $responseJson, 3);
						self::logDebug('response: ' . print_r($response, true), 3);

						if ($response['resultCode'] == '0') {
								$result = true;
								self::$m_Token = $token;
								self::$m_LeaseInterval = $response['leaseInterval'];
								self::$m_UserInfo = $response['userInfo'];
						}
						else {
								self::$m_Token = '';
								self::$m_UserInfo = null;
						}
				}
				return $result;
		}

		public static function groupExist($group) {
			self::logDebug('groupExist: ' . $group, 3);
			$url = LIFERAY_VALIDATE_URL . '/ug/verify/' . $group;
			$responseJson = self::getSSOResponse($url);
			$response = self::json2array($responseJson);
			self::logDebug('responseJson: ' . $responseJson, 3);
			//self::logDebug('response: ' . print_r($response, true), 3);
			return $response['success'] === TRUE ? TRUE : FALSE;
		}
}

/**
 * Function for logging or session validating.
 * @param string
 * @param string
 * @returns bool
 */
function prihlaseni($user, $password, $token="")
{
	if(!$token){
        if (isset($_COOKIE['JSESSIONID'])) {
		  $token = $_COOKIE['JSESSIONID'];
	   }
	   elseif (substr($user, 0, 4) == 'SID@') {
	       $token = substr($user, 4);
	   }
	}

	$result = FALSE;

	if ($token != '') {
		if (IBMGeoportalSSO::validateToken($token)) {
			$_SESSION['u'] = IBMGeoportalSSO::getUserName();
			$_SESSION['ms_groups'] = IBMGeoportalSSO::getGroups();
			$_SESSION['group_names'] = IBMGeoportalSSO::getGroupsNames();
			$_SESSION['micka']['jsid'] = $_COOKIE['JSESSIONID'];
			$_SESSION['hs_lang'] = IBMGeoportalSSO::getLanguage();
			$result = TRUE;
			IBMGeoportalSSO::logDebug("SESSION user:" . $_SESSION['u'] . ", groups:" . $_SESSION['ms_groups'], 3);
		}
	}

	if ($result === FALSE) {
		// guest
		//fwrite($fh, date("d.m.Y H:i:s"). "\n");
		//fwrite($fh, "result FALSE, user=guest" . "\n");
		IBMGeoportalSSO::logDebug('result FALSE, user: guest', 3);
		$_SESSION['u'] = 'guest';
		$_SESSION['ms_groups'] = 'guest';
		$_SESSION['maplist']['micka']['users']['guest'] = 'r';
		$_SESSION['micka']['jsid'] = $_COOKIE['JSESSIONID'];
		$result = TRUE;
	}
	return $result;
}

/**
 * Returns available projects for logged user
 * @returns array
 */
function getProj()
{
	$projects = null;
	if (IBMGeoportalSSO::isValidToken()) {
		$mickaProj = array();
		$mickaProj['title'] = 'Micka';
		$mickaProj['script'] = '';

		// výchozí práva
		$mickaProj['users'][$_SESSION['u']] = 'r';
		/*
		if (IBMGeoportalSSO::hasUserRole(IBMGeoportalSSO::MICKA_ROLE_READ)) {
				$mickaProj['users'][$_SESSION['u']] = 'r';
		}
		*/
		if (IBMGeoportalSSO::hasUserRole(IBMGeoportalSSO::MICKA_ROLE_WRITE)) {
				$mickaProj['users'][$_SESSION['u']] = 'rw';
		}
		if (IBMGeoportalSSO::hasUserRole(IBMGeoportalSSO::MICKA_ROLE_PUBLISH)) {
				$mickaProj['users'][$_SESSION['u']] = 'rwp';
		}
		if (IBMGeoportalSSO::hasUserRole(IBMGeoportalSSO::MICKA_ROLE_ADMIN)) {
				$mickaProj['users'][$_SESSION['u']] = 'rwp*';
		}

		// uživatel s právem r je jako guest
		if ($mickaProj['users'][$_SESSION['u']] == 'r') {
			unset($mickaProj['users'][$_SESSION['u']]);
			$_SESSION['u'] = 'guest';
			$mickaProj['users'][$_SESSION['u']] = 'r';
			IBMGeoportalSSO::logDebug('GETPROJ: right only r, change user to guest', 3);
		}
		$currProj['micka'] = $mickaProj;
		$_SESSION['maplist'] = $currProj;
		$projects['one'] = array('title' => 'Projects', 'project' => $currProj);
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

