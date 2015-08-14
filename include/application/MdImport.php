<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * ImportLib for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20140903
 *
 */

class MdImport {
	private $data_type = -1; // Typ záznamu. -1: rozpracováno, 0: neveřejný (privátní) záznam, 1: veřejný (public), -100 neměnit uložené
	private $md_lang;
	private $ini_lang;
	private $user = 'guest';
	private $tree_el = array();
	private $md_values = array();
	private $del_md_id = array(); // seznam md_id pro smazání dat při update
	private $md_head = array();
	private $action; // akce s daty
	private $micka_lite = FALSE;
	private $table_mode = 'md';
	private $table_md = 'md';
	private $table_md_values = 'md_values';
	private $report_valid_type = array('type' => 'json', 'short' => FALSE);
	
	public $sid = '';
	public $mds = 0; // metadatový standard
	public $group_e;
	public $group_v;
	public $server_name = 'local';
	public $stop_error = TRUE; // Zastavit import při chybě

	function __construct($data_type=-1, $mds=0) {
		$this->user = isset($_SESSION['u']) && $_SESSION['u'] != '' ? $_SESSION['u'] : 'guest';
        $this->group_e = DEFAULT_EDIT_GROUP != '' 
                ? getDefaultGroup(DEFAULT_EDIT_GROUP, $this->user) 
                : $this->user;
        $this->group_v = DEFAULT_VIEW_GROUP != '' 
                ? getDefaultGroup(DEFAULT_VIEW_GROUP, $this->user) 
                : $this->user;
		$this->sid = session_id();
		// výchozí jazyk je 1. z konfigurace
		$pom = explode(',', MICKA_LANGS_STR);
		$this->ini_lang = $pom[0];
		$this->md_lang = $pom[0];
		$this->setMdStandard($mds);
		$this->setDataType($data_type);
	}

	
	public function setMdStandard($mds) {
		switch ($mds) {
			case 0:
			case 1:
			case 2:
			case 10:
				$this->mds = $mds;
				break;
			default :
				$this->mds = 0;
		}
	}

	public function setTableMode($mode) {
		if ($mode == 'tmp') {
			$this->table_mode = $mode;
			$this->table_md = TMPTABLE_PREFIX . '_md';
			$this->table_md_values = TMPTABLE_PREFIX . '_md_values';
		}
		else {
			$this->table_mode = 'md';
			$this->table_md = 'md';
			$this->table_md_values = 'md_values';
		}
	}

	private function setMds() {
		if (key($this->md_values) == 'MD_Metadata') {
			$this->setMdStandard(0);
		}
		elseif (key($this->md_values) == 'metadata') {
			$this->setMdStandard(1);
		}
		elseif (key($this->md_values) == 'featureCatalogue') {
			$this->setMdStandard(2);
		}
	}

	public function setDataType($data_type) {
		switch ($data_type) {
			case -100:
				$dataType = -100;
				break;
			case -1:
			case 'process':
				$dataType = -1;
				break;
			case 0:
			case 'privat':
				$dataType = 0;
				break;
			case 'public':
			case 1:
				$dataType = 1;
				break;
			default:
				$dataType = -1;
				break;
		}
		$this->data_type = $dataType;
	}

	public function setMdLang($md_lang) {
		$this->md_lang = (strlen($md_lang) == 3) ? strtolower($md_lang) : $this->ini_lang;
	}

	public function setImportStopOnError($stop_error) {
		$this->stop_error = ($stop_error === TRUE || $stop_error === 'FALSE') ? $stop_error : TRUE;
	}

	public function setImportUser($user) {
		$this->user = $user;
	}

	private function setMdValues($md) {
		$this->md_values = $md;
	}
	
	
	public function setReportValidType($type, $short=FALSE) {
		$this->report_valid_type = array('type' => $type, 'short' => $short);
	}

	/**
	 * Výběr UUID z textu
	 *
	 * $data = 'urn:uuid:aa6ca480-a480-1a6c-adb7-74f3fba79557';
	 *
	 * @param string $data
	 * @return -1 nebo uuid
	 */
	private function getDcUuid($data) {
		$rs = -1;
		$data = trim($data);
		if (strpos('a' . $data,'urn:uuid:') > 0) {
			$rs = substr($data,9);
		}
		return $rs;
	}
	
	private	function addLogImport($fc, $text) {
		setMickaLog($text, 'DEBUG', "MdImport.$fc");
	}

	private function setTitle($recno_in, $iso_lang) {
		$titleMd = '';
		switch ($iso_lang) {
		case 'MD':
			if(isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0])) {
				$lang = '@' . $this->md_head[$recno_in]['lang'];
				$titleMd = isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]["$lang"])
								? $this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]["$lang"]
								: '';
				if ($titleMd == '') {
					$titleMd = isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@'])
									? $this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@']
									: '';
				}
				if ($titleMd == '') {
					if (isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0])
									&& is_array($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0])) {
						foreach ($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0] as $key=>$value) {
							$titleMd = $value;
						}
					}
				}
			}
			elseif(isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@'])
						&& is_string($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@'])) {
				$titleMd = isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@'])
								? $this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@']
								: '';
			}
			break;
		case 'MS':
		case 'MC':
			if(isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0])) {
				$lang = '@' . $this->md_head[$recno_in]['lang'];
				$titleMd = isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]["$lang"])
								? $this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]["$lang"]
								: '';
				if ($titleMd == '') {
					$titleMd = isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@'])
									? $this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@']
									: '';
				}
				if ($titleMd == '') {
					if (isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0])
									&& is_array($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0])) {
						foreach ($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0] as $key=>$value) {
							$titleMd = $value;
						}
					}
				}
			}
			elseif(isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@'])
							&& is_string($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@'])) {
				$titleMd = isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@'])
								? $this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0]['@']
								: '';
			}
			break;
		case 'DC':
			$titleMd = isset($this->md_values['metadata'][$recno_in]['title'][0]['@']) ? $this->md_values['metadata'][$recno_in]['title'][0]['@'] : '';
			break;
		case 'FC':
			$titleMd = isset($this->md_values['featureCatalogue'][$recno_in]['name'][0]['@']) ? $this->md_values['featureCatalogue'][$recno_in]['name'][0]['@']  : '';
			break;
		default:
		}
		$this->md_head[$recno_in]['title'] = $titleMd != '' ? $titleMd : '';
	}
	
	private function setLangMd($recno_in, $iso_lang) {
		switch ($iso_lang) {
		case 'MD':
		case 'MS':
		case 'MC':
			if(isset($this->md_values['MD_Metadata'][$recno_in]['language'][0]['LanguageCode'])) {
				$this->md_head[$recno_in]['lang'] = isset($this->md_values['MD_Metadata'][$recno_in]['language'][0]['LanguageCode'][0]['@'])
								? $this->md_values['MD_Metadata'][$recno_in]['language'][0]['LanguageCode'][0]['@']
								: '';
			}
			else {
				$this->md_head[$recno_in]['lang'] = $this->md_lang;
			}
			break;
		case 'DC':
			$this->md_head[$recno_in]['lang'] = isset($this->md_values['metadata'][$recno_in]['language'][0]['@'])
							? $this->md_values['metadata'][$recno_in]['language'][0]['@']
							: $this->md_head[$recno_in]['lang'] = $this->md_lang;
			break;
		case 'FC':
			$this->md_head[$recno_in]['lang'] = $this->md_lang;
			break;
		default:
		}

		// kontrola kódu jazyka
		switch ($this->md_head[$recno_in]['lang']) {
			case 'en':
				$this->md_head[$recno_in]['lang'] = 'eng';
				break;
			case 'fra':
				$this->md_head[$recno_in]['lang'] = 'fre';
				break;
			default:
		}
	}

	private function setLangsMd($recno_in, $iso_lang) {
		$lang_change = FALSE;
		$md_lang = array();
		switch ($iso_lang) {
		case 'MD':
			if(isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0])
				&& is_array($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0])) {
				foreach ($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['MD_DataIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0] as $key=>$value) {
					if (substr($key,0,1) == '@') {
						$l = substr($key,1);
						if ($lang_change && $l != '') {
							// změna výchozího jazyka
							$this->md_head[$recno_in]['lang'] = $l;
							$this->md_values['MD_Metadata'][$recno_in]['language'][0]['LanguageCode'][0]['@'] = $l;
						}
						switch ($l) {
							case '':
								if ($value != '') {
									$l = $this->md_head[$recno_in]['lang'];
								}
								else {
									if ($this->micka_lite === FALSE) {
										$l = '';
										$lang_change = TRUE;
									}
								}
								break;
							case 'en':
								$l = 'eng';
								break;
							case 'fra':
								$l = 'fre';
								break;
							default:
						}
						if ($l != '') {
							$md_lang[] = $l;
						}
					}
				}
			}
			break;
		case 'MS':
		case 'MC':
			if(isset($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0])
				&& is_array($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0])) {
				foreach ($this->md_values['MD_Metadata'][$recno_in]['identificationInfo'][0]['SV_ServiceIdentification'][0]['citation'][0]['CI_Citation'][0]['title'][0] as $key=>$value) {
					if (substr($key,0,1) == '@') {
						$l = substr($key,1);
						if ($lang_change && $l != '') {
							// změna výchozího jazyka
							$this->md_head[$recno_in]['lang'] = $l;
							$this->md_values['MD_Metadata'][$recno_in]['language'][0]['LanguageCode'][0]['@'] = $l;
						}
						switch ($l) {
							case '':
								if ($value != '') {
									$l = $this->md_head[$recno_in]['lang'];
								}
								else {
									if ($this->micka_lite === FALSE) {
										$l = '';
										$lang_change = TRUE;
									}
								}
								break;
							case 'en':
								$l = 'eng';
								break;
							case 'fra':
								$l = 'fre';
								break;
							default:
						}
						if ($l != '') {
							$md_lang[] = $l;
						}
					}
				}
			}
			break;
		case 'DC':
			break;
		case 'FC':
			break;
		default:
		}
		$md_lang[] = $this->md_head[$recno_in]['lang'];
		$md_lang = array_unique($md_lang);
		$this->md_head[$recno_in]['langs'] = implode($md_lang, '|');
	}

	/*
	private function getUuidFromString($in) {
		$rs = '';
		$left = strpos($in, "'");
		$right = strrpos($in, "'");
		$rs = substr($in, $left+1, ($right-$left)-1);
		return $rs;
	}
	 */

	private function deleteRecord() {
		$rs = array();
		$report = array();
		$md_record = new MdRecord();
		$md_record->setTableMode('md');
		foreach ($this->md_head as $key => $row) {
			$report = $md_record->deleteMdRecords('uuid', $row['uuid']);
            if ($report['report'] == 'ok') {
                $this->setReport($key, 'info', '');
                $this->md_head[$key]['ok'] = 1;
            } else {
                $this->setReport($key, 'info', $report['report']);
                $this->md_head[$key]['ok'] = 0;
            }
		}
		return $rs;
	}

	private function deleteRights($data) {
		$rs = TRUE;
		foreach ($data['data'] as $key => $row) {
			$this->md_head[$key]['uuid'] = $row['uuid'];
			$this->setReport($key, 'info', 'ok');
			if ($row['edit'] == 0) {
				$this->setReport($key, 'info', labelTranslation(MICKA_LANG, 'No update rights.'));
				$rs = FALSE;
			}
		}
		return $rs;
	}
	
	private function deleteMd($data) {
		if (is_array($data) && array_key_exists('data', $data)) {
			if ($this->deleteRights($data) === TRUE) {
				$this->deleteRecord($data);
			}
		}
	}

	private function deleteTimeFromDate($date) {
		$pom = explode("T", $date);
		$rs = $pom[0];
		return $rs;
	}

	private function setReport($recno_in, $mode, $text) {
		$text = str_replace(
      array("['", "[0]", "']"),
      array("/","",""),
      $text 
    );
		if ($recno_in > -1) {
			switch ($mode) {
			case 'info':
			case 'error':
				$this->md_head[$recno_in]['report'] = isset($this->md_head[$recno_in]['report'])
								? $this->md_head[$recno_in]['report'] . "$text\n"
								: "$text\n";
				break;
			default:
			}
		}
	}
	
	private function setElementsData($data) {
		$rs = '';
		$rs = "'" . $data['MD_ID'] . '|' . $data['PACKAGE_ID'] . '|' . $data['MULTI_LANG'] . '|' . $data['FORM_CODE'] . "'";
		//$rs = $data['MD_ID'] . '|' . $data['PACKAGE_ID'] . '|' . $data['MULTI_LANG'] . '|' . $data['FORM_CODE'];
		return $rs;
	}
	
	private function getElementsData($path_el) {
		$rs = array();
		$data = '';
		$eval_text = '$data=isset($this->tree_el' . $path_el . "['_p_']" . ') ? $this->tree_el' . $path_el . "['_p_']" . " : '';";
		eval($eval_text);
		if ($data != '') {
			$pom = explode('|', $data);
			if (count($pom) == 4) {
				$rs['md_id'] = $pom[0];
				$rs['package_id'] = $pom[1];
				$rs['multi_lang'] = $pom[2];
				$rs['form_code'] = $pom[3];
			}
		}
		//my_print_r($rs);
		return $rs;
	}
	
	private function setSchemaMd() {
		$sql = array();
		$eval_text = '';
		array_push($sql, "
			SELECT	tree.md_id, tree.md_path_el, tree.package_id, tree.md_left, tree.md_right, elements.multi_lang, elements.form_code
  		FROM tree INNER JOIN elements ON tree.el_id = elements.el_id
      WHERE tree.md_standard=%i
		", $this->mds);
		$result = _executeSql('select', $sql, array('all'));
		if (count($result) < 1) {
			$this->tree_el = false;
		}
		foreach ($result as $row) {
			$el_path = $row['MD_PATH_EL'];
			$el_path = str_replace("/","']['",$el_path);
			$el_path = substr($el_path,2) . "']";
			$eval_text = '$this->tree_el' . $el_path . "['_p_']=" . $this->setElementsData($row) . ";";
			eval($eval_text);
	  }
		//my_print_r($this->tree_el ['MD_Metadata']['contact']);
	}

	private function setMdHead() {
		if (array_key_exists('MD_Metadata', $this->md_values)) {
			foreach ($this->md_values['MD_Metadata'] as $x=>$md) {
				$this->md_head[$x]['report'] = '';
				$this->md_head[$x]['iso'] = array_key_exists('SV_ServiceIdentification', $md['identificationInfo'][0]) ? 'MS' : 'MD';
				if ($this->md_head[$x]['iso'] == 'MS' 
								&& isset($this->md_values['MD_Metadata'][$x]['hierarchyLevelName'][0]['@'])
								&& $this->md_values['MD_Metadata'][$x]['hierarchyLevelName'][0]['@'] == 'MapContext') {
					//   /MD_Metadata/hierarchyLevelName
					$this->md_head[$x]['iso'] = 'MC';
				}
				// uuid
				$this->md_head[$x]['uuid'] = '';
				if (isset($this->md_values['MD_Metadata'][$x]['fileIdentifier'][0]['@']) && $this->md_values['MD_Metadata'][$x]['fileIdentifier'][0]['@'] != '') {
					$this->md_head[$x]['uuid'] = $this->md_values['MD_Metadata'][$x]['fileIdentifier'][0]['@'];
				}
				// geografický rozsah
				$this->md_head[$x]['bbox_x1'] = '';
				$this->md_head[$x]['bbox_x2'] = '';
				$this->md_head[$x]['bbox_y1'] = '';
				$this->md_head[$x]['bbox_y2'] = '';
				if (isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['westBoundLongitude'][0]['@'])) {
					/// MD_Metadata  / identificationInfo  / MD_DataIdentification  / extent  / EX_Extent  / geographicElement  / EX_GeographicBoundingBox
					$this->md_head[$x]['bbox_x1'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['westBoundLongitude'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['westBoundLongitude'][0]['@']
									: '';
					$this->md_head[$x]['bbox_x2'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['eastBoundLongitude'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['eastBoundLongitude'][0]['@']
									: '';
					$this->md_head[$x]['bbox_y1'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['southBoundLatitude'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['southBoundLatitude'][0]['@']
									: '';
					$this->md_head[$x]['bbox_y2'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['northBoundLatitude'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['northBoundLatitude'][0]['@']
									: '';
				}
				elseif(isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['westBoundLongitude'][0]['@'])) {
					$this->md_head[$x]['bbox_x1'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['westBoundLongitude'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['westBoundLongitude'][0]['@']
									: '';
					$this->md_head[$x]['bbox_x2'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['eastBoundLongitude'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['eastBoundLongitude'][0]['@']
									: '';
					$this->md_head[$x]['bbox_y1'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['southBoundLatitude'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['southBoundLatitude'][0]['@']
									: '';
					$this->md_head[$x]['bbox_y2'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['northBoundLatitude'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['geographicElement'][0]['EX_GeographicBoundingBox'][0]['northBoundLatitude'][0]['@']
									: '';
				}
				// časový rozsah
				$this->md_head[$x]['range_begin'] = '';
				$this->md_head[$x]['range_end'] = '';
				if(isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimePeriod'][0]['beginPosition'][0]['@'])) {
					$this->md_head[$x]['range_begin'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimePeriod'][0]['beginPosition'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimePeriod'][0]['beginPosition'][0]['@']
									: '';
					$this->md_head[$x]['range_end'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimePeriod'][0]['endPosition'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimePeriod'][0]['endPosition'][0]['@']
									: '';
				}
				elseif(isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimePeriod'][0]['beginPosition'][0]['@'])) {
					$this->md_head[$x]['range_begin'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimePeriod'][0]['beginPosition'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimePeriod'][0]['beginPosition'][0]['@']
									: '';
					$this->md_head[$x]['range_end'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimePeriod'][0]['endPosition'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimePeriod'][0]['endPosition'][0]['@']
									: '';
				}
				elseif(isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimeInstant'][0]['timePosition'][0]['@'])) {
					$this->md_head[$x]['range_begin'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimeInstant'][0]['timePosition'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimeInstant'][0]['timePosition'][0]['@']
									: '';
					$this->md_head[$x]['range_end'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimeInstant'][0]['timePosition'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['MD_DataIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimeInstant'][0]['timePosition'][0]['@']
									: '';
				}
				elseif(isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimeInstant'][0]['timePosition'][0]['@'])) {
					$this->md_head[$x]['range_begin'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimeInstant'][0]['timePosition'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimeInstant'][0]['timePosition'][0]['@']
									: '';
					$this->md_head[$x]['range_end'] = isset($this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimeInstant'][0]['timePosition'][0]['@'])
									? $this->md_values['MD_Metadata'][$x]['identificationInfo'][0]['SV_ServiceIdentification'][0]['extent'][0]['EX_Extent'][0]['temporalElement'][0]['EX_TemporalExtent'][0]['extent'][0]['TimeInstant'][0]['timePosition'][0]['@']
									: '';
				}
				if ($this->md_head[$x]['range_begin'] != '' || $this->md_head[$x]['range_end'] != '') {
					//odstranění času, pokud tam je
					$this->md_head[$x]['range_begin'] = $this->deleteTimeFromDate($this->md_head[$x]['range_begin']);
					$this->md_head[$x]['range_end'] = $this->deleteTimeFromDate($this->md_head[$x]['range_end']);

					// kontrola správnosti datumu
					if (!isValidDateIso($this->md_head[$x]['range_begin']) || !isValidDateIso($this->md_head[$x]['range_end'])) {
						$this->setReport($x, 'error', "WARNING: range_begin='".$this->md_head[$x]['range_begin']
							."' and range_end='".$this->md_head[$x]['range_end']."'");
  					$this->md_head[$x]['range_begin'] = '';
						$this->md_head[$x]['range_end'] = '';
					}
				}
				$this->md_head[$x]['recno'] = -1;
				$this->setLangMd($x, $this->md_head[$x]['iso']);
				$this->setLangsMd($x, $this->md_head[$x]['iso']);
				$this->setTitle($x, $this->md_head[$x]['iso']);
			}
		}
		if (array_key_exists('metadata', $this->md_values)) {
			foreach ($this->md_values['metadata'] as $x=>$md) {
				$this->md_head[$x]['report'] = '';
				$this->md_head[$x]['iso'] = 'DC';
				if(isset($this->md_values['metadata'][$x]['coverage'][0]['@']) && $this->md_values['metadata'][$x]['coverage'][0]['@'] != '') {
					// geografický rozsah
					$this->md_head[$x]['bbox_x1'] = '';
					$this->md_head[$x]['bbox_x2'] = '';
					$this->md_head[$x]['bbox_y1'] = '';
					$this->md_head[$x]['bbox_y2'] = '';
					$pom = DCGeom($this->md_values['metadata'][$x]['coverage'][0]['@']);
					if (count($pom) == 4) {
						$this->md_head[$x]['bbox_x1'] = (isset($pom['x1']) && trim($pom['x1']) != '') ? trim($pom['x1']) : '';
						$this->md_head[$x]['bbox_x2'] = (isset($pom['x2']) && trim($pom['x2']) != '') ? trim($pom['x2']) : '';
						$this->md_head[$x]['bbox_y1'] = (isset($pom['y1']) && trim($pom['y1']) != '') ? trim($pom['y1']) : '';
						$this->md_head[$x]['bbox_y2'] = (isset($pom['y2']) && trim($pom['y2']) != '') ? trim($pom['y2']) : '';
					}
				}
				// uuid
				$this->md_head[$x]['uuid'] = '';
				if (isset($this->md_values['metadata'][$x]['identifier']) && is_array($this->md_values['metadata'][$x]['identifier'])) {
					$z = -1;
					for ($y = 0; $y < count($this->md_values['metadata'][$x]['identifier']); $y++) {
						if (isset($this->md_values['metadata'][$x]['identifier'][$y]['@']) && $this->md_values['metadata'][$x]['identifier'][$y]['@'] != '') {
							$pom = $this->getDcUuid($this->md_values['metadata'][$x]['identifier'][$y]['@']);
							if ($pom != -1) {
								$this->md_head[$x]['uuid'] = $pom;
								$z = $y;
								break;
							}
						}
					}
					if ($z > -1) {
						//odstarnění uuid z pole
						array_splice($this->md_values['metadata'][$x]['identifier'],$z,1);
					}
				}
				// časový rozsah
				$this->md_head[$x]['range_begin'] = '';
				$this->md_head[$x]['range_end'] = '';
				$this->md_head[$x]['recno'] = -1;
				$this->setLangMd($x, $this->md_head[$x]['iso']);
				$this->setLangsMd($x, $this->md_head[$x]['iso']);
				$this->setTitle($x, $this->md_head[$x]['iso']);
			}
		}
		if (array_key_exists('featureCatalogue', $this->md_values)) {
			foreach ($this->md_values['featureCatalogue'] as $x=>$md) {
				$this->md_head[$x]['report'] = '';
				$this->md_head[$x]['iso'] = 'FC';
				// uuid
				$this->md_head[$x]['uuid'] = '';
				$this->md_head[$x]['recno'] = -1;
				$z = -1;
				for ($y = 0; $y < count($this->md_values['featureCatalogue'][$x]['id']); $y++) {
					if (isset($this->md_values['featureCatalogue'][$x]['id'][$y]['@']) && $this->md_values['featureCatalogue'][$x]['id'][$y]['@'] != '') {
						$this->md_head[$x]['uuid'] = $this->md_values['featureCatalogue'][$x]['id'][$y]['@'];
					}
				}
				if ($z > -1) {
					//odstarnění uuid z pole
					array_splice($this->md_values['featureCatalogue'][$x]['id'],$z,1);
				}
				$this->setLangMd($x, $this->md_head[$x]['iso']);
				$this->setLangsMd($x, $this->md_head[$x]['iso']);
				$this->setTitle($x, $this->md_head[$x]['iso']);
			}
		}
	}

	private function setArayMdValues($md_id, $md_path, $package_id, $md_value, $recno, $md_lang, $multi_lang, $form_code) {
		$rs = array();
		$rs['ok'] = FALSE;
		$rs['type'] = 'error';
		$rs['report'] = '?';

		if ($md_id == '') {
			$rs['report'] = 'ERROR(elements2mdid)';
			return $rs;
		}
		
		if ($md_value !== '') {
			$record = array();
			$record['md_id'] = $md_id;
			if (DB_DRIVER == 'oracle') {
				if (strLen($md_value) > 2000) {
					$md_value = subStr($md_value, 0, 1997) . '...';
					$rs['type'] = 'info';
					$rs['report'] = 'trim 2000';
				}
			}
			// kontrola typu dat
			if ($form_code == 'N') {
				if (is_numeric($md_value) === FALSE) {
					$rs['type'] = 'error';
					$rs['report'] = 'bad format number';
					return $rs;
				}
			}
            $md_value = str_replace('\"', '"', $md_value);
			$record['md_value'] = $md_value;
			$record['md_path'] = '0_' . $md_path;
			if ($multi_lang == 0) {
				$record['lang'] = 'xxx';
			}
			elseif ($multi_lang == 1 && $md_lang != 'xxx') {
				$record['lang'] = $md_lang;
			}
			else {
				$record['lang'] = $this->md_head[$recno]['lang'];
			}
			$record['package_id'] = $package_id;
			$this->md_values[$recno][]=$record;
		}
		$this->del_md_id[$recno][]=$md_id;
		$rs['ok'] = TRUE;
		return $rs;
	}

	private function processArrayMd($md, $level=0, $elements='', $path_el='', $recno_in='', $path_md='', $md_lang='xxx') {
		$level++;
		$el_pom = '';
	  foreach ($md as $key => $item) {
	    if ($level == 2) {
	      $recno_in = $key;
	    }
	    if ($level != 2) {
	      $el_pom = $elements;
	      $path_pom = $path_el;
	      $path_md_pom = $path_md;
	    }
	    if (is_array($item)) {
	      if ($level != 2) {
	        if(is_numeric($key)) {
	          $elements .= "[" . $key . "]";
	          $path_md .= $key . "_";
	        }
	        else {
	          $elements .= "['" . $key . "']";
	          $path_el .= "['" . $key . "']";
                $pom = $this->getElementsData($path_el);
	          $path_md .= $pom['md_id'] . "_";
	        }
	      }
	      $this->processArrayMd($item, $level, $elements, $path_el, $recno_in, $path_md, $md_lang);
	    }
	    else {
	    	if(is_numeric($key)) {
	        $elements .= "[" . $key . "]";
	        $path_md .= $key . "_";
	      }
	      else {
	        	if ($key{0} == '@') { // lang
							if (strlen($key) > 1) {
								$md_lang = substr($key,1);
							}
	        	}
	        	else {
			        $elements .= "['" . $key . "']";
			        $path_el .= "['" . $key . "']";
                    $pom = $this->getElementsData($path_el);
		          $path_md .= $pom['md_id'] . "_0_";
	        	}
	      }
	      if(substr_count($elements,"']['") > 1) {
					$this->setReport($recno_in, 'error', labelTranslation(MICKA_LANG, 'ERROR (path)') . " $elements = $item");
					$this->addLogImport('processArrayMd', labelTranslation(MICKA_LANG, 'ERROR (path)') . " $elements = $item");
	      }
	      else { // OK
					$pom = $this->getElementsData($path_el);
					if (count($pom) == 0) {
						$md_id = '';
						$md_package_id = '';
						$multi_lang = '';
						$form_code = '';
					}
					else {
						$md_id = $pom['md_id'];
						$md_package_id = $pom['package_id'];
						$multi_lang = $pom['multi_lang'];
						$form_code = $pom['form_code'];
					}
					$save = $this->setArayMdValues($md_id,$path_md,$md_package_id,$item,$recno_in,$md_lang,$multi_lang, $form_code);
					if ($save['ok'] !== TRUE) {
						$this->setReport($recno_in, $save['type'], $save['report'] . " $elements = $item");
					} 
	      }
	    	$md_lang = 'xxx';
	    }
	    if ($level == 2) {
	      $path_pom = $path_el;
	      $path_md_pom = $path_md;
	    }
	    $elements = $el_pom;
	    $path_el  = $path_pom;
	    $path_md  = $path_md_pom;
	  } //end foreach
	  $level--;
	}

	private function setRecordsValues() {
		$mode_md = FALSE;
		if (count($this->md_head) > 0) {
			$md_record = new MdRecord();
			$md_record->setReportValidType($this->report_valid_type['type'], $this->report_valid_type['short']);
			$md_record->setTableMode($this->table_mode);
			foreach ($this->md_head as $key => $md) {
				if ($this->table_mode == 'md' && canActionAcl('mds', $this->md_head[$key]['iso'], 'w') === FALSE) {
					$this->md_head[$key]['action'] = 'skip';
					$this->md_head[$key]['right'] = 'x';
					$this->setReport($key, 'error', labelTranslation(MICKA_LANG, 'guest not right'));
					continue;
				}
				$this->md_head[$key]['ok'] = 0;
				$md_record->setRecordImporting(FALSE);

				$this->addLogImport('setRecordsValues.MD', $md);

				// zaznam existuje
				if($md['uuid'] != '') {
					if ($this->micka_lite) {
						$md_record->setTableMode('tmp');
					}
					else {
						$md_record->setTableMode('md');
						$mode_md = TRUE;
					}
					$record = $md_record->getMd('uuid', $md['uuid']);

					if($mode_md === TRUE && $record['report'] == 'ok' && $record['right'] == 'w') {
						$this->setReport($key, 'error', "INFO: " . labelTranslation(MICKA_LANG, 'The metadata record already exists. It will be replaced with the new one when you save it.'));
					}

					if ($this->micka_lite) {
						$record_orig = $record;
					}
					else {
						$md_record->setTableMode($this->table_mode);
						$record_orig = $md_record->getMd('uuid', $md['uuid']);
					}

					$this->addLogImport('setRecordsValues.origMD', $record_orig);
					
					$this->addLogImport('setRecordsValues.table_mode', $this->table_mode);
					if ($this->table_mode == 'tmp' && canActionAcl('mds', $this->md_head[$key]['iso'], 'w') === FALSE) {
						if ($record_orig['report'] == 'ok' && $record_orig['report'] == 'ok') {
							$this->md_head[$key]['action'] = 'update';
							$this->md_head[$key]['recno'] = (isset($record_orig['md']['RECNO']) && $record_orig['md']['RECNO'] > 0) ? $record_orig['md']['RECNO'] : -1;
						}
						else {
							$this->md_head[$key]['action'] = 'insert';
						}
						$this->md_head[$key]['right'] = 'w';
					}
					elseif($record['report'] == 'ok' && $record['right'] != 'w') {
						$this->md_head[$key]['action'] = 'skip';
						$this->md_head[$key]['right'] = 'x';
						$this->setReport($key, 'error', labelTranslation(MICKA_LANG, 'Record exists, import cancelled. No update rights.'));
					}
					elseif($record['report'] == 'Not rights' && $record['right'] == 'x') {
						$this->md_head[$key]['action'] = 'skip';
						$this->md_head[$key]['right'] = 'x';
						$this->setReport($key, 'error', labelTranslation(MICKA_LANG, 'Record exists, import cancelled. No update rights.'));
					}
					elseif($record['report'] == 'ok' && $record['right'] == 'w') {
						if ($this->action == 'skip') {
							$this->md_head[$key]['action'] = 'skip';
							$this->md_head[$key]['right'] = 'w';
							$this->setReport($key, 'error', labelTranslation(MICKA_LANG, 'Record exists, import cancelled.'));
						}
						else {
							// update
							if($record_orig['report'] == 'Record not found') {
								// nový záznam v tmp
								$this->md_head[$key]['action'] = 'insert';
								$this->md_head[$key]['right'] = 'w';
							}
							else {
								$this->md_head[$key]['recno'] = (isset($record_orig['md']['RECNO']) && $record_orig['md']['RECNO'] > 0) ? $record_orig['md']['RECNO'] : -1;
								$this->md_head[$key]['action'] = 'update';
								$this->md_head[$key]['right'] = 'w';
								//$this->md_head[$key]['report'] = '';
								//$this->setReport($key, 'error', "INFO: " . labelTranslation(MICKA_LANG, 'The metadata record already exists. It will be replaced with the new one when you save it.'));
								$md['langs'] = $this->table_mode == 'tmp'
												? getUniqueMdLangs($md['langs'], $record_orig['md']['LANG'])
												: $md['langs'];
								$this->data_type = ($this->data_type == -100) ? $record_orig['md']['DATA_TYPE'] : $this->data_type;
							}
						}
					}
					elseif($record['report'] == 'Record not found') {
						$this->md_head[$key]['action'] = 'insert';
						$this->md_head[$key]['right'] = 'w';
					}
					else {
						$this->md_head[$key]['action'] = 'skip';
						$this->md_head[$key]['right'] = 'x';
						$this->setReport($key, 'error', labelTranslation(MICKA_LANG, 'unknow error in MD'));
					}
				}
				else {
					// Nový záznam
					$this->md_head[$key]['action'] = 'insert';
					$this->md_head[$key]['right'] = 'w';
				}
				// akce
				$data = array();
				switch ($md['iso']) {
					case 'MD':
						$data['md_standard'] = 0;
						break;
					case 'MS':
					case 'MC':
						$data['md_standard'] = 10;
						break;
					case 'DC':
						$data['md_standard'] = 1;
						break;
					case 'FC':
						$data['md_standard'] = 2;
						break;
					default :
						$data['md_standard'] = $this->mds;
				}
				$data['lang'] = $md['langs'];
				$data['server_name'] = $this->server_name;
				if ($this->md_head[$key]['action'] == 'insert') {
					// vytvoření záznamu v tabulce [md]
					if($md['uuid'] != '') {
						$data['uuid'] = $md['uuid'];
					}
					else {
						$data['uuid'] = getUuid();;
						$this->md_head[$key]['uuid'] = $data['uuid'];
					}
					$data['edit_group'] = $this->group_e != '' ? $this->group_e : $this->user;
					$data['view_group'] =  $this->group_v != '' ? $this->group_v : $this->user;
					$data['data_type'] = ($this->data_type == -100) ? -1 : $this->data_type;
					if ($this->table_mode == 'md') {
						$this->md_head[$key]['recno'] = $md_record->setNewRecordMd($data, $md['lang'], $import=TRUE);
					}
					else {
						$this->md_head[$key]['recno'] = $md_record->setNewRecord($data, $md['lang'], $import=TRUE);
					}
				}
				elseif ($this->md_head[$key]['action'] == 'update' && $this->md_head[$key]['recno'] > 0) {
					// Aktualizace [md]
					if ($this->micka_lite === TRUE) {
						if (array_key_exists('lang', $data) === TRUE) {
							unset($data['lang']);
						}
					}
					if ($this->micka_lite === FALSE) {
						$data['data_type'] = $this->data_type;
					}
					$md_record->updateMdFromImport($this->md_head[$key]['recno'], $data);
					// Smazání [md_values]
					if ($this->micka_lite) {
						$md_record->deleteMdIdFromMdValues($this->md_head[$key]['recno'], $md['langs'], $this->del_md_id[$key]);
					}
					else {
						$md_record->deleteMdValuesBeforeImport($this->md_head[$key]['recno']);
					}
				}
				if ($this->md_head[$key]['action'] == 'insert' || $this->md_head[$key]['action'] == 'update') {
					// Vložení nových hodnot
					if ($this->md_head[$key]['recno'] > 0) {
						$data = array();
						foreach ($this->md_values[$key] as $md_values) {
							$md_values['recno'] = $this->md_head[$key]['recno'];
							if ($this->micka_lite === TRUE && isset($record_orig['md']['LANG']) && $md_values['lang'] != 'xxx') {
								// pokud byl odstraněn nějaký jazyk, odstranit i data
								if (strpos($record_orig['md']['LANG'], $md_values['lang']) !== FALSE) {
									array_push($data, $md_values);
								}
							}
							else {
								array_push($data, $md_values);
							}
						}
						if ($this->micka_lite === FALSE) {
							// zachovat původní datestamp
							$md_record->setRecordImporting(TRUE);
						}
						$md_record->setMdValues($data);
						$this->md_head[$key]['ok'] = 1;
						$this->md_head[$key]['valid'] = $md_record->getReportValid();
					}
				}
			}
		}
	}

	/**
	 * Import metadat
	 * =====================
	 * @param array $data pole metadat pro uložení
	 * @param string $mode delete|all|skip|insert|update|fc
	 * @return array report
	 */
	public function dataToMd($data, $mode, $params=array()) {
		$this->addLogImport('dataToMd.start', "mode=$mode, public=" . $this->data_type);
		//$this->addLogImport('dataToMd DATA', $data);
		//Debugger::log('[MdImport.dataToMd.data] ' . print_r($data, true), 'INFO');
		$rs = array();
		if (is_array($data) === FALSE) {
			$rs[0]['report'] = 'input data is not array';
			$rs[0]['ok'] = 0;
			return $rs;
		}
		if (count($data) == 0) {
			$rs[0]['report'] = 'input data is empty';
			$rs[0]['ok'] = 0;
			return $rs;
		}
		if ($mode == 'lite') {
			$this->micka_lite = TRUE;
			$mode = 'all';
		}
		$this->action = $mode;
		$this->setMdValues($data);
		switch($mode) {
			case 'delete':
				$this->deleteMd($data);
				break;
			case 'skip': // pokud záznam existuje, nic neaktualizovat
			case 'all':
			case 'insert':
			case 'update':
			case 'fc':
				unset($data);
				$this->setMds();
				$this->setSchemaMd();
				$this->setMdHead();
				$this->processArrayMd($this->md_values);
				$this->setRecordsValues();
				break;
			default:
		}
		//my_print_r($this->md_head);
		//echo "<hr>";
		//my_print_r($this->md_values);
		//echo "<hr>";
		//my_print_r($this->del_md_id);
		$this->addLogImport('dataToMd.md_head', $this->md_head);
		if ($mode != 'delete') {
			foreach ($this->md_head as $key => $value) {
				setEditId2Session('recno', 'new');
				setEditId2Session('recnoTmp', $this->md_head[$key]['recno']);
				unset($this->md_head[$key]['iso']);
				unset($this->md_head[$key]['bbox_x1']);
				unset($this->md_head[$key]['bbox_x2']);
				unset($this->md_head[$key]['bbox_y1']);
				unset($this->md_head[$key]['bbox_y2']);
				unset($this->md_head[$key]['range_begin']);
				unset($this->md_head[$key]['range_end']);
				unset($this->md_head[$key]['recno']);
				unset($this->md_head[$key]['lang']);
				unset($this->md_head[$key]['langs']);
				unset($this->md_head[$key]['action']);
				unset($this->md_head[$key]['right']);
			}

			unset($this->md_values);
			unset($this->del_md_id);
			unset($this->tree_el);
		}
		$this->addLogImport('dataToMd.md_head.return', $this->md_head);
		return $this->md_head;
	}
}

