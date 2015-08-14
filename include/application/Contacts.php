<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * Contacts for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20131105
 *
 * Historie změn:
 * Datum			Autor		Popis změny
 */

class Contacts {
	private $user;
	private $user_guest;
	private $user_admin = FALSE;
	private $user_groups;

	function  __construct() {
		$this->user = MICKA_USER;
		$this->user_guest = $this->user == 'guest' ? TRUE : FALSE;

		$group = array();
		$group = getMsGroups('get_groups');
		$this->user_groups = implode("|", array_keys($group));
		//$this->user_groups = MICKA_USER_GROUPS;
		//$this->setUserAdmin(MICKA_USER_RIGHT);

		$this->setUserAdmin();
	}

	/**
	 * Je uživatel administrátor?
	 * @param string $right
	 */
	private function setUserAdmin() {
		if (canAction('*') === FALSE) {
			$this->user_admin = FALSE;
		}
		else {
			$this->user_admin = TRUE;
		}
	}

	/**
	 * Je uživatel Ve skupině?
	 * @param string $group
	 * @return bool
	 */
	private function compareGroup($group) {
		$ms_groups = '|' . $this->user_groups . '|';
		if (strpos($ms_groups, '|' . trim($group) . '|') === FALSE) {
			return FALSE;
		}
		else {
			return TRUE;
		}
	}


	/**
	 * Práva uživatele na kontakt
	 * @param string $user uživatel
	 * @param string $view skupina pro prohlížení
	 * @param string $edit skupina pro editaci
	 * @return string x|r|w
	 */
	private function getContactRight($user, $view, $edit) {
		if($this->user_admin) {
			return 'w';
		}
		if($this->user == $user) {
			return 'w';
		}
		if ($this->compareGroup($edit)) {
			return 'w';
		}
		if ($this->compareGroup($view)) {
			return 'r';
		}

		return 'x';
	}

	/**
	 * Další cont_id pro nový záznam 
	 * @return int
	 */
	private function getNewContId() {
		$sql = array();
		$sql[] = "SELECT MAX(cont_id)+1 FROM contacts";
		$rs = _executeSql('select', $sql, array('single'));
		if ($rs == '') {
			$rs = 1;
		}
		return $rs;
	}


	/**
	 * Vyhledání kontaktů
	 * @param integer $cont_id -1 nový záznam
	 * @return array
	 */
	public function getContacts($cont_id=0) {
		$rs = array();
		if ($cont_id == -1) {
			$rs = array();
			$rs['CONT_ID'] = -1;
			$rs['CONT_LABEL'] = NULL;
			$rs['CONT_PERSON'] = NULL;
			$rs['CONT_ORGANISATION'] = NULL;
			$rs['CONT_ORGAN_EN'] = NULL;
			$rs['CONT_FUNCTION'] = NULL;
			$rs['CONT_FUNCTION_EN'] = NULL;
			$rs['CONT_PHONE'] = NULL;
			$rs['CONT_FAX'] = NULL;
			$rs['CONT_POINT'] = NULL;
			$rs['CONT_CITY'] = NULL;
			$rs['CONT_ADMINAREA'] = NULL;
			$rs['CONT_POSTCODE'] = NULL;
			$rs['CONT_COUNTRY'] = NULL;
			$rs['CONT_EMAIL'] = NULL;
			$rs['CONT_URL'] = NULL;
			$rs['CONT_GROUP_VIEW'] = NULL;
			$rs['CONT_GROUP_EDIT'] = NULL;
			$rs['CONT_USER'] = NULL;
			return array(0 => $rs);
		}
		$sql = array();
		array_push($sql, 'SELECT * FROM contacts');
		if ($cont_id > 0) {
			array_push($sql, 'WHERE cont_id=%i', $cont_id);
		}
		array_push($sql, 'ORDER BY cont_label');
		$records = _executeSql('select', $sql, array('all'));
		if (is_array($records) && count($records) > 0) {
			foreach($records as $row) {
				$right = $this->getContactRight($row['CONT_USER'], $row['CONT_GROUP_VIEW'], $row['CONT_GROUP_EDIT']);
				if($right != 'x') {
					array_push($rs, $row + array('right'=>$right));
				}
			}
		}
		return $rs;
	}

	/**
	 * Smazání kontaktu
	 * @param integer $cont_id
	 * @return boolean
	 */
	public function deleteContact($cont_id) {
		$rs = array();
		$rs['ok'] = FALSE;
		$rs['report'] = '';
		if ($this->user_guest) {
			$rs['report'] = "User '" . $this->user . "' not rights";
			return $rs;
		}
		if ($cont_id < 1) {
			$rs['report'] = "Bad input data! System error. Id=$cont_id";
			return $rs;
		}
		// kontrola práv
		$record = $this->getContacts($cont_id);
		if (array_key_exists('right', $record[0]) && $record[0]['right'] == 'w') {
			$sql = array();
			array_push($sql, 'DELETE FROM contacts WHERE cont_id=%i', $cont_id);
			$result = _executeSql('delete', $sql, array('all'));
			$rs['ok'] = TRUE;
			return $rs;
		}
		else {
			$rs['report'] = "Not found or not right to contact ($cont_id).";
			return $rs;
		}
	}


	/**
	 * Uložení kontaktu do tabulky [contacts]
	 * @param array $_POST
	 * @return array ('ok'=>FALSE|TRUE, 'report'='')
	 */
	public function setContact($post) {
		$rs = array();
		$rs['ok'] = FALSE;
		$rs['report'] = '';
		if ($this->user_guest) {
			$rs['report'] = "User '" . $this->user . "' not rights";
			return $rs;
		}
		$cont_id = isset($post['cont_id']) ? htmlspecialchars($post['cont_id']) : '';
		if ($cont_id == -1) {
			$mode = 'insert';
		}
		elseif ($cont_id > 0) {
			$mode = 'update';
		}
		else {
			$rs['report'] = 'Bad input data! System error.';
			return $rs;
		}
		$data['cont_person'] = (isset($post['pers']) && $post['pers'] != '')  ? htmlspecialchars($post['pers']) : NULL;
		$data['cont_organisation'] = (isset($post['organisation']) && $post['organisation'] != '')  ? htmlspecialchars($post['organisation']) : NULL;
		$data['cont_organ_en'] = (isset($post['organ_en']) && $post['organ_en'] != '')  ? htmlspecialchars($post['organ_en']) : NULL;
		$data['cont_label'] = (isset($post['label']) && $post['label'] != '')  ? htmlspecialchars($post['label']) : NULL;
		$data['cont_function'] = (isset($post['func']) && $post['func'] != '')  ? htmlspecialchars($post['func']) : NULL;
		$data['cont_function_en'] = (isset($post['func_en']) && $post['func_en'] != '')  ? htmlspecialchars($post['func_en']) : NULL;
		$data['cont_phone'] = (isset($post['phone']) && $post['phone'] != '')  ? htmlspecialchars($post['phone']) : NULL;
		$data['cont_fax'] = (isset($post['fax']) && $post['fax'] != '')  ? htmlspecialchars($post['fax']) : NULL;
		$data['cont_point'] = (isset($post['point']) && $post['point'] != '')  ? htmlspecialchars($post['point']) : NULL;
		$data['cont_city'] = (isset($post['city']) && $post['city'] != '')  ? htmlspecialchars($post['city']) : NULL;
		$data['cont_adminarea'] = (isset($post['adminarea']) && $post['adminarea'] != '')  ? htmlspecialchars($post['adminarea']) : NULL;
		$data['cont_postcode'] = (isset($post['postcode']) && $post['postcode'] != '')  ? htmlspecialchars($post['postcode']) : NULL;
		$data['cont_country'] = (isset($post['country']) && $post['country'] != '')  ? htmlspecialchars($post['country']) : NULL;
		$data['cont_email'] = (isset($post['email']) && $post['email'] != '')  ? htmlspecialchars($post['email']) : NULL;
		$data['cont_url'] = (isset($post['url']) && $post['url'] != '')  ? htmlspecialchars($post['url']) : NULL;
		$data['cont_group_edit'] = (isset($post['groups_e']) && $post['groups_e'] != '')  ? htmlspecialchars($post['groups_e']) : NULL;
		$data['cont_group_view'] = (isset($post['groups_v']) && $post['groups_v'] != '')  ? htmlspecialchars($post['groups_v']) : NULL;
		$data['cont_user'] = $this->user;
		$sql = array();
		if ($mode == 'insert') {
			$data['cont_id'] = array('%i', $this->getNewContId());
			array_push($sql, 'INSERT INTO contacts', $data);
		}
		if ($mode == 'update') {
			// kontrola práv
			$record = $this->getContacts($cont_id);
			if (array_key_exists('right', $record[0]) && $record[0]['right'] == 'w') {
				array_push($sql, 'UPDATE contacts SET', $data);
				array_push($sql, 'WHERE cont_id=%i', $cont_id);
			}
			else {
				$rs['report'] = "Not right to contact $cont_id.";
				return $rs;
			}
		}
		if (count($sql) > 0) {
			$result = _executeSql('update', $sql, array('all'));
			$rs['ok'] = TRUE;
		}
		return $rs;
	}

	/**
	 * Vytvoření nového kontaktu podle stávajícího
	 * @param integer $cont_id
	 * @return array
	 */
	public function copyContact($cont_id) {
		$rs = array();
		$rs['ok'] = FALSE;
		if ($this->user_guest) {
			$rs['report'] = "User '" . $this->user . "' not rights";
			return $rs;
		}
		if ($cont_id > 0) {
			$record = $this->getContacts($cont_id);
			if (count($record) > 0 && array_key_exists('CONT_ID', $record[0]) && $record[0]['CONT_ID'] > 0) {
				$record[0]['CONT_ID'] = -1;
				$rs['contact'] = $record;
				$rs['ok'] = TRUE;
			}
		}
		return $rs;
	}
	
	
	public function actionContacts($action, $cont_id) {
		if (MICKA_USER == 'guest') {
			require PHPINC_DIR . '/templates/403.php';
		}
		

		$rs = array();
		
		switch ($action) {
			case 'new':
				$rs['groups'] = getMsGroups('get_groups');
				$rs['data'] = $this->getContacts(-1);
				break;
			case 'edit':
				$rs['groups'] = getMsGroups('get_groups');
				$rs['data'] = $this->getContacts($cont_id);
				break;
			case 'copy':
				$record = $this->copyContact($cont_id);
				if ($record['ok']) {
					$rs['groups'] = getMsGroups('get_groups');
					$rs['data'] = $record['contact'];
				} else {
					require PHPINC_DIR . '/templates/404_record.php';
				}
				break;
			case 'delete':
				$result = $this->deleteContact($cont_id);
				Debugger::dump($result);
				if($result['ok']) {
					$redirectUrl = substr(htmlspecialchars($_SERVER['PHP_SELF']), 0, strrpos($_SERVER['PHP_SELF'], '/')) . '?ak=md_contacts';
					require PHPPRG_DIR . '/redirect.php';
				} else {
					require PHPINC_DIR . '/templates/404_record.php';
				}
				break;
			case 'save':
				$result = $this->setContact($_POST);
				if($result['ok']) {
					$redirectUrl = substr(htmlspecialchars($_SERVER['PHP_SELF']), 0, strrpos($_SERVER['PHP_SELF'], '/')) . '?ak=md_contacts';
					require PHPPRG_DIR . '/redirect.php';
				} else {
					require PHPINC_DIR . '/templates/404_record.php';
				}
				break;
			default :
				$rs['data'] = $this->getContacts();
		}
		return $rs;
	}
}

