<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * metadata record from MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20141022
 *
 */

class MdRecord {
	public $md_record = array();
	private $table_mode = 'md';
	private $table_md = 'md';
	private $table_md_values = 'md_values';
	private $report_valid_type = array('type' => 'html', 'short' => FALSE);
	private $sid = '';
	private $user;
	private $user_admin = FALSE; // Administrator
	private $user_groups;
	private $ora_pxml;
	private $del_tmp = TRUE; // mazání záznamů v tmp tabulkách
	private $record_importing = FALSE; // importovaný (harvestovaný) záznam, pořízen jinde
	private $stop_datestamp = FALSE; // nedávat automaticky datestamp pro MD a MS
    private $defaultValueMd = array(); // výchozí hodnoty pro nový záznam
	private $acl; // používat ACL (rozšířená práva)
	private $micka_acl = array(); // ACL (rozšířená práva)

	function  __construct() {
		$this->md_record['report_valid'] = '';
		$this->sid = session_id();
		$this->user = MICKA_USER;
		$this->user_groups = (isset($_SESSION['ms_groups']) && $_SESSION['ms_groups'] != '') ? $_SESSION['ms_groups'] : $this->user;
		$this->acl = (isset($_SESSION['micka']['acl']) && $_SESSION['micka']['acl'] != '') ? TRUE : FALSE;
		$this->setUserAdmin();
		$this->setMickaAcl();
        $this->defaultValueMd['edit_group'] = MICKA_USER;
        $this->defaultValueMd['view_group'] = MICKA_USER;

		//Fulltext
		$this->ora_pxml = DB_FULLTEXT == 'ORACLE-CONTEXT' ? TRUE : FALSE; 
		
		// problém s harvest
		//$this->user = MICKA_USER;
		//$this->user_groups = MICKA_USER_GROUPS;
		//$this->setUserAdmin(MICKA_USER_RIGHT);
	}

	private function setUserAdmin() {
		$this->user_admin = canAction('*');
	}
	
	private function setMickaAcl() {
		$resource = 'MDS';
		if ($this->acl === TRUE) {
			if (is_array($_SESSION['micka']['acl'][$resource]) === FALSE) {
				$this->acl == FALSE;
			}
			else {
				$this->micka_acl = $_SESSION['micka']['acl'];
			}
		}
		if ($this->acl === TRUE && count($this->micka_acl) == 0) {
			$this->acl == FALSE;
		}
	}



	private function setRecordRight() {
		$this->md_record['user_right'] = $this->getRecordRight();
		setMickaLog("user_right=" . $this->md_record['user_right'], 'DEBUG', 'MdRecord.setRecordRight');
	}

	private function setTableModeToMdRecord() {
		$this->md_record['table'] = $this->table_mode;
	}
	
	private function setMdsToMdRecord() {
		if ($this->md_record['md']['MAPCONTEXT'] != '') {
			$this->md_record['md']['MDS'] = 'MC';
		}
	}

	public function setDefaultValueMd($defaultValueMd) {
        if (array_key_exists('edit_group', $defaultValueMd) === TRUE && $defaultValueMd['edit_group'] != '') {
            $this->defaultValueMd['edit_group'] = $defaultValueMd['edit_group'];
        }
        if (array_key_exists('view_group', $defaultValueMd) === TRUE && $defaultValueMd['view_group'] != '') {
            $this->defaultValueMd['view_group'] = $defaultValueMd['view_group'];
        }
	}
    
	public function setReportValidType($type, $short=FALSE) {
		$this->report_valid_type = array('type' => $type, 'short' => $short);
	}
    
	public function setStopDatestamp($stop_datestamp) {
    	$this->stop_datestamp = $stop_datestamp === TRUE ? TRUE : FALSE;
	}
    
	private function isPrivilege($privilege) {
		$rs = FALSE;
		
		// administrator
		if ($this->user_admin === TRUE) {
			setMickaLog("privilege=$privilege, rs=1, administrator", 'DEBUG', 'MdRecord.isPrivilege.return');
			return TRUE;
		}

		// FIXME: upravit
		//if ($this->user == 'guest' && $this->table_mode == 'tmp' && $privilege == 'w') {
		if ($this->table_mode == 'tmp' && $privilege == 'w') {
			$rs = TRUE;
			setMickaLog("privilege=$privilege, rs=$rs", 'DEBUG', 'MdRecord.isPrivilege.return');
			return $rs;
		}
		
		if (canActionAcl('mds', $this->md_record['md']['MDS'], $privilege) === TRUE) {
				$rs = TRUE;
		}
		setMickaLog("privilege=$privilege, rs=$rs", 'DEBUG', 'MdRecord.isPrivilege.return');
		return $rs;
	}
	
	private function getRecordRight() {
		// administrátor
		$this->md_record['publisher'] = FALSE;
		$this->md_record['saver'] = FALSE;
		if (canActionAcl('mds', $this->md_record['md']['MDS'], 'w') === TRUE) {
			$this->md_record['saver'] = TRUE;
		}
		if ($this->user_admin === TRUE) {
			$this->md_record['publisher'] = TRUE;
			$this->md_record['saver'] = TRUE;
			return 'w';
		}
		
		// privilegia
		$privilege = 'x';
		if ($this->isPrivilege('w') === TRUE) {
			$privilege = 'w';
		}
		elseif ($this->isPrivilege('r') === TRUE) {
			$privilege = 'r';
		}
		setMickaLog("privilege=$privilege", 'DEBUG', 'MdRecord.getRecordRight');
		
		// publikovat
		if ($this->isPrivilege('p') === TRUE) {
			$this->md_record['publisher'] = TRUE;
			setMickaLog("publisher=$privilege", 'DEBUG', 'MdRecord.getRecordRight');
		}
		
		// vlastník
		if ($privilege == 'w' && $this->user == $this->md_record['md']['CREATE_USER']) {
			return 'w';
		}
		// edit_group
		if ($privilege == 'w' && $this->compareGroup($this->md_record['md']['EDIT_GROUP']) === TRUE) {
			return 'w';
		}
		// veřejný záznam
		if ($privilege == 'r' && $this->md_record['md']['DATA_TYPE'] > 0) {
			return 'r';
		}
		// view_group
		if ($privilege == 'r' && $this->compareGroup($this->md_record['md']['VIEW_GROUP']) === TRUE) {
			return 'r';
		}
		return 'x'; // žádná práva
	}

	private function compareGroup($group) {
		$ms_groups = str_replace(' ', '|', '|' . $this->user_groups . '|');
		if (strpos($ms_groups, '|' . trim($group) . '|') === FALSE) {
			return FALSE;
		}
		else {
			return TRUE;
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

	public function setRecordImporting($value) {
		$this->record_importing = $value === TRUE ? TRUE : FALSE;
	}
	
	public function getReportValid() {
		return $this->md_record['report_valid'];
	}
	
	private function getNewRecno($table) {
		$sql = array();
		$sql[] = "SELECT MAX(recno)+1 FROM $table";
		$rs = _executeSql('select', $sql, array('single'));
		if ($rs == '') {
			$rs = 1;
		}
		return $rs;
	}

	private function setMdValuesFileIdentifier($recno, $mds, $value, $update_md=TRUE)  {
		//echo "recno=$recno, mds=$mds, value=$value<br>";
		$rs = FALSE;
		if ($recno > 1 && $value == '' && $mds == '') {
			setMickaLog("Not data!", 'ERROR', 'MdRecord.setMdFileIdentifier');
			return $rs;
		}
		if ($mds == 0 || $mds == 10) {
			$data = array();
			$data[0]['recno'] = $recno;
			$data[0]['md_value'] = $value;
			$data[0]['md_id'] = '38';
			$data[0]['md_path'] = '0_0_38_0';
			$data[0]['lang'] = 'xxx';
			$data[0]['package_id'] = '0';
			$this->setMdValues($data, $update_md);
			$rs = TRUE;
		}
		return $rs;
	}

	private function setMdValuesMainLanguage($recno, $mds, $value, $update_md)  {
		$rs = FALSE;
		if ($recno > 1 && $value == '') {
			setMickaLog("Not data!", 'ERROR', 'MdRecord.setMdMainLanguage');
			return $rs;
		}
		if ($mds == 0 || $mds == 10) {
			$data = array();
			$data[0]['recno'] = $recno;
			$data[0]['md_value'] = $value;
			$data[0]['md_id'] = '5527';
			$data[0]['md_path'] = '0_0_39_0_5527_0';
			$data[0]['lang'] = 'xxx';
			$data[0]['package_id'] = '0';
			$this->setMdValues($data, $update_md);
			$rs = TRUE;
		}
		return $rs;
	}

	private function setMdValuesDateStamp($recno, $mds, $update=FALSE)  {
		setMickaLog("recno=$recno, mds=$mds", 'DEBUG', 'MdRecord.setMdValuesDateStamp');
		$rs = FALSE;
		if ($recno > 1 && $mds === '') {
			setMickaLog("MDS empty!", 'ERROR', 'MdRecord.setMdValuesDateStamp');
			return $rs;
		}
		if ($mds == 0 || $mds == 10) {
			if ($this->stop_datestamp === TRUE) {
				$rs = TRUE;
				return $rs;
			}
			if ($update === TRUE) {
				// smazání stávajícíh dat
				$sql = array();
				array_push($sql, "DELETE FROM $this->table_md_values WHERE recno=%i AND md_id=44", $recno);
				$result = _executeSql('delete', $sql, array('all'));
			}
			$data = array();
			$data[0]['recno'] = $recno;
			$data[0]['md_value'] = Date("Y-m-d");
			$data[0]['md_id'] = '44';
			$data[0]['md_path'] = '0_0_44_0';
			$data[0]['lang'] = 'xxx';
			$data[0]['package_id'] = '0';
			$this->setMdValues($data, $updt_md=FALSE);
			$rs = TRUE;
		}
		return $rs;
	}

	private function setHierarchy() {
		$this->md_record['hierarchy'] = '';
		if ($this->md_record['md']['MD_STANDARD'] == 10 && count($this->md_record['md_values']) > 0) {
			foreach ($this->md_record['md_values'] as $key => $value) {
				if ($value['MD_ID'] == 623) {
					$this->md_record['hierarchy'] = $value['MD_VALUE'];
					break;
				}
			}
		}
	}
	
	private function getDataFromMd($where_col, $where_val) {
		setMickaLog("get md where $where_col=$where_val", 'DEBUG', 'MdRecord.getDataFromMd');
		$rs = FALSE;
		$ok = FALSE;
		$sql = array();
		
		$table = $this->table_md;
		$col_sid = $this->table_mode == 'tmp' ? ', ' . $this->table_md . '.sid' : '';
		if ($where_col == 'recno' && $where_val > 0) {
			$ok = TRUE;
			$where = "WHERE $table.recno=%i";
		}
		if ($where_col == 'uuid' && $where_val != '') {
			$ok = TRUE;
			$where = "WHERE $table.uuid=%s";
		}
		if ($ok === TRUE) {
			array_push($sql, "
				SELECT $table.recno, $table.uuid, $table.md_standard, $table.lang, $table.data_type, $table.create_user, $table.create_date,
							 $table.last_update_user, $table.last_update_date, $table.edit_group, $table.view_group, $table.x1,
							 $table.y1, $table.x2, $table.y2, $table.the_geom, $table.range_begin, $table.range_end, $table.md_update, $table.title,
							 $table.server_name, $table.valid, $table.prim, " . setNtext2Text('', "$table.xmldata") . ' AS pxml' 
						. ", standard.md_standard_short_name AS mds, 
							(SELECT md_value FROM md_values WHERE md_id=123 AND md_value='MapContext' AND recno=$table.recno) AS mapcontext"
						.	$col_sid
			);
			array_push($sql, "FROM $table JOIN standard ON $table.md_standard=standard.md_standard");
			array_push($sql, $where, $where_val);
		}
		if (count($sql) > 0 && $this->table_mode == 'tmp') {
			array_push($sql, "AND $table.sid=%s", $this->sid);
		}
		if (count($sql) > 1) {
			$record = _executeSql('select', $sql, array('all'));
		}
		else {
			return $rs;
		}
		if (is_array($record) && isset($record[0]['RECNO']) && $record[0]['RECNO'] > 0) {
			$this->md_record['md'] = $record[0];
			$this->setTableModeToMdRecord();
			$this->setMdsToMdRecord();
			$rs = TRUE;
		}
		//my_print_r($this->md_record);
		return $rs;
	}

	private function getDataFromMdValues($recno, $value_lang='xxx', $profil_id=-1, $package_id=-1) {
		setMickaLog("recno=$recno, lang=$value_lang, profil=$profil_id, package=$package_id", 'DEBUG', 'MdRecord.getRecordsFromMdValues');
		$rs = FALSE;
		$this->md_record['md_values'] = array();
		$sql = array();
		array_push($sql, "SELECT recno,md_id," . setNtext2Text('', 'md_value') . ",md_path,lang,package_id FROM $this->table_md_values WHERE recno=%i", $recno);

		if ($profil_id > -1) {
			array_push($sql, "AND md_id IN(SELECT md_id FROM profil WHERE profil_id=%i)", $profil_id);
		}
		if ($package_id > -1) {
			array_push($sql, "AND package_id=%i", $package_id);
		}
		if ($value_lang != 'xxx') {
			array_push($sql, "AND (lang='xxx' OR lang=%s)", $value_lang);
		}
		array_push($sql, "ORDER BY md_path");
		if (count($sql) > 1) {
			$values = _executeSql('select', $sql, array('all','='));
			if (is_array($values) && count($values) > 0) {
				$this->md_record['md_values'] = $values;
				$rs = TRUE;
			}
		}
		return $rs;
	}
    
    public function getKeywordsUri($recno) {
        $rs = array();
        $sql = array();
		array_push($sql, "SELECT md_value, md_path FROM $this->table_md_values WHERE recno=%i AND lang='uri'", $recno);
		if (count($sql) > 1) {
			$rs = _executeSql('select', $sql, array('pairs', 'md_path', 'md_value'));
		}
        return $rs;
    }

	public function getMdXmlData($uuid, $xsltemplate='micka2one19139.xsl') {
		setMickaLog("UUID=$uuid, XSL=$xsltemplate", 'DEBUG', 'MdRecord.getMdXmlData');
		require_once PHPPRG_DIR . '/MdExport.php';
		$rs = '';
		if ($uuid != '') {
			//$in = array();
			//$in[] = "_UUID_ = '$uuid'";
			$export = new MdExport($this->user);
			if ($this->table_mode == 'tmp') {
				$export->setTableMode('tmp');
				$xml = $export->getXmlTmpMd($uuid);
			}
			else {
				$in = array();
				$params = array();
				$in[] = "_UUID_ = '$uuid'"; // TODO: použít params
				$export->xml_from = 'data';
				$pom = $export->getXML($in, $params, FALSE);
				$xml = $pom[0];
			}

			//setMickaLog('XML=' . $xml_pom, 'DEBUG', 'micka_lib_xml.php (getXML)');
			if ($xsltemplate == '' && $xml != '') {
				$rs = $xml;
			}
			elseif ($xsltemplate != '' && $xml != '') {
				setMickaLog("applyTemplate $xsltemplate", 'DEBUG', 'MdRecord.getMdXmlData');
				$xml = applyTemplate($xml, $xsltemplate);
				if ($xml === FALSE) {
					setMickaLog('applyTemplate === FALSE', 'ERROR', 'MdRecord.getMdXmlData');
				}
				else {
					if ($xml != '') {
						$rs = $xml;
					}
				}
			}
		}
		if ($rs == '') {
			setMickaLog("XML empty", 'ERROR', 'MdRecord.getMdXmlData');
		}
		return $rs;
	}
	
	public function updateOnlyXmlData($uuid, $recno=-1) {
		$rs = FALSE;
		if ($recno == -1 || $recno == '' ) {
			return $rs;
		}
		// xmldata
		if (DB_DRIVER == 'oracle') {
			$xml = $this->getMdXmlData($uuid);
			if ($xml != '') {
				$conn = dibi::getConnection()->driver->getResource();
				setXmlToClob($conn, $this->table_md, 'xmldata', "uuid='$uuid'", $xml);
				$rs = TRUE;
			}
		} elseif (DB_DRIVER == 'postgre') {
			$xml = $this->getMdXmlData($uuid);
			if ($xml != '') {
				$data['xmldata'] = $xml;
				$this->setMd($recno, $data);
				$rs = TRUE;
			}
		} elseif (DB_DRIVER == 'mssql2005') {
			$xml = $this->getMdXmlData($uuid);
			if ($xml != '') {
				$data['xmldata'] = $xml;
				$this->setMd($recno, $data);
				$rs = TRUE;
			}
		}
		setXmldata2Pxml('md', $recno);
		return $rs;
	}

	public function updateOnlyValidData($recno, $xml) {
		$rs = FALSE;
		$validator = new Validator();
		$data['valid'] = 0;
		$data['prim'] = 0;
		if (VALIDATOR == 1) {
			$validator->run($xml);
			$vResult = $validator->getPass();
			if($vResult){
				if($vResult['fail'] > 0) $data['valid'] = 0;
				else if($vResult['warn'] > 0) $data['valid'] = 1;
				else $data['valid'] = 2;
				$data['prim'] = $vResult['primary'];
			}
			$this->setMd($recno, $data);
			$rs = TRUE;
		}
		return $rs;
	}

	private function getGeom($x1, $y1, $x2, $y2, $poly, $dc_geom) {
		setMickaLog("x1=$x1, y1=$y1, x2=$x2, y2=$y2, poly=$poly, dc_geom=$dc_geom", 'DEBUG', 'MdRecord.getGeom');
		$rs = array();
		$rs['x1'] = NULL;
		$rs['x2'] = NULL;
		$rs['y1'] = NULL;
		$rs['y2'] = NULL;
		$rs['the_geom'] = NULL;
		if ($x1 != '' && $x2 != '' && $y1 != '' && $y2 !='') {
			if (SPATIALDB) {
				$rs['x1'] = array('%f', $x1);
				$rs['x2'] = array('%f', $x2);
				$rs['y1'] = array('%f', $y1);
				$rs['y2'] = array('%f', $y2);
				switch (SPATIALDB){
					case "postgis2":
						$rs['the_geom'] =  array('%sql', "ST_GeomFromText('MULTIPOLYGON((($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1)))',0)");
						break;
					default:	
						$rs['the_geom'] =  array('%sql', "GeomFromText('MULTIPOLYGON((($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1)))',-1)");
						break;
				}	
			}
			else {
				$rs['x1'] = array('%f', $x1);
				$rs['x2'] = array('%f', $x2);
				$rs['y1'] = array('%f', $y1);
				$rs['y2'] = array('%f', $y2);
			}
		}
		elseif($poly != '') {
			$pom = str_replace("MULTIPOLYGON(((", "", $poly);
			$pom = str_replace(")", "", $pom);
			$pom = str_replace("(", "", $pom);
			$apoly = explode(",", $pom);
			$pom = explode(" ", $apoly[0]);
			$x2 = $pom[0];
			$x1 = $x2;
			$y2 = $pom[1];
			$y1 = $y2;
			foreach ($apoly as $bod) {
				$pom = explode(" ", $bod);
				$x1 = min($x1, $pom[0]);
				$x2 = max($x2, $pom[0]);
				$y1 = min($y1, $pom[1]);
				$y2 = max($y2, $pom[1]);
			}
			$rs['x1'] = array('%f', $x1);
			$rs['x2'] = array('%f', $x2);
			$rs['y1'] = array('%f', $y1);
			$rs['y2'] = array('%f', $y2);
			if (SPATIALDB) {
				switch (SPATIALDB){
					case "postgis2":
						$rs['the_geom'] = array('%sql', "ST_GeomFromText('$poly',0)");
						break;
					default:	
						$rs['the_geom'] = array('%sql', "GeomFromText('$poly',-1)");
						break;
				}
			}
		}
		elseif($dc_geom != '') {
			$pom = explode(';',$dc_geom);
			if (count($pom) == 4) {
				foreach ($pom as $value) {
					if (strpos('a'.$value,'westlimit:') > 0) {
						$x1 =ltrim(strstr($value,":"),":");
					}
					elseif (strpos('a'.$value,'eastlimit:') > 0) {
						$x2 = ltrim(strstr($value,":"),":");
					}
					elseif (strpos('a'.$value,'southlimit:') > 0) {
						$y1 = ltrim(strstr($value,":"),":");
					}
					elseif (strpos('a'.$value,'northlimit:') > 0) {
						$y2 = ltrim(strstr($value,":"),":");
					}
					if ($x1 != '' && $x2 != '' && $y1 != '' && $y2 !='') {
						$rs['x1'] = array('%f', $x1);
						$rs['x2'] = array('%f', $x2);
						$rs['y1'] = array('%f', $y1);
						$rs['y2'] = array('%f', $y2);
						if (SPATIALDB) {
							switch (SPATIALDB){
								case "postgis2":
									$rs['the_geom'] =  array('%sql', "ST_GeomFromText('MULTIPOLYGON((($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1)))',0)");
									break;
								default:	
									$rs['the_geom'] =  array('%sql', "GeomFromText('MULTIPOLYGON((($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1)))',-1)");
									break;
							}
							
						}
					}
				}
			}
		}
		return $rs;
	}

	private function setMd($recno, $data) {
		setMickaLog("update recno=$recno", 'DEBUG', 'MdRecord.setMd');
		$rs = FALSE;
		if (array_key_exists('data_type', $data) === TRUE) {
			if ($this->isPrivilege('p') === FALSE) {
				unset($data['data_type']);
			}
			//$data['data_type'] = $this->isPrivilege('p') === FALSE ? 0 : $data['data_type'];
		}
		if ($recno < 1 || count($data) == 0) {
			setMickaLog("Empty data, recno=$recno", 'ERROR', 'MdRecord.setMd');
			return $rs;
		}
		if (array_key_exists('md_standard', $data) === TRUE) {
            // md_standard po vytvoření neměnit
			unset($data['md_standard']);
		}
		$sql = array();
		array_push($sql, "UPDATE $this->table_md SET %a", $data);
		array_push($sql, "WHERE recno=%i", $recno);
		if (count($sql) > 0) {
			$rs = _executeSql('update', $sql, array('all'));
		}
		return $rs;
	}
	
	public function updateMdFromImport($recno, $data) {
		return $this->setMd($recno, $data);
	}

	public function updateTableMd($where_col, $where_value) {
		$md = $this->getMd($where_col, $where_value);
		if ($md['report'] != 'ok') {
			$rs['report'] = $md['report'];
			return $rs;
		}
		if ($this->md_record['user_right'] != 'w') {
			$rs['report'] = 'Not right';
			return $rs;
		}
		$this->updateMd($this->md_record['md']['RECNO']);
	}
	
	public function updateMdDataType($where_col, $where_value, $data_type) {
		$md = $this->getMd($where_col, $where_value);
		if ($md['report'] != 'ok') {
			$rs['report'] = $md['report'];
			return $rs;
		}
		if ($this->md_record['user_right'] != 'w') {
			$rs['report'] = 'Not right';
			return $rs;
		}
		$data['data_type'] = $data_type;
		$this->setMd($this->md_record['md']['RECNO'], $data);
		$rs['report'] = 'OK';
		return $rs;
	}
	
	public function updateMdGroups($uuid, $edit_group, $view_group) {
		setMickaLog("update uuid=$uuid, edit_group=$edit_group, view_group=$view_group, grp=" . MICKA_USER_GROUPS, 'DEBUG', 'MdRecord.updateMdGroups');
		$data = array();
		$md = $this->getMd($where_col='uuid', $uuid);
		if ($md['report'] != 'ok') {
			$rs['report'] = $md['report'];
			return $rs;
		}
		if ($this->md_record['user_right'] != 'w') {
			$rs['report'] = 'Not right';
			return $rs;
		}
		if ($edit_group != '') {
			$data['edit_group'] = $edit_group;
		}
		if ($view_group != '') {
			$data['view_group'] = $view_group;
		}
		if (count($data) > 0) {
			$this->setMd($this->md_record['md']['RECNO'], $data);
			$rs['report'] = 'OK';
		}
		else {
			$rs['report'] = 'unknow group';
		}
		return $rs;
	}
	
	private function updateMd($recno) {
		setMickaLog("updateMd recno=$recno", 'DEBUG', 'MdRecord.updateMd');
		$rs = array();
		$data = array();
		$data['last_update_user'] = $this->user;
		$data['last_update_date'] =  DB_DRIVER == 'mssql2005' ? str_replace('-', '', getNewDate()) : getNewDate();
		// geometrie
		// title
		$sql = array();
		$md_id =array();
		if ($this->md_record['md']['MD_STANDARD'] == 0) {
			$md_id = array(497,498,499,500,503);
			$md_id[] = 11; //title
			$md_id[] = 5527; //lang
		}
		elseif ($this->md_record['md']['MD_STANDARD'] == 10) {
			$md_id = array(5133,5134,5135,5136,5140);
			$md_id[] = 5063; //title
			$md_id[] = 5527; //lang
		}
		elseif ($this->md_record['md']['MD_STANDARD'] == 1) {
			$md_id = array(14);
			$md_id[] = 11; //title
		}
		elseif ($this->md_record['md']['MD_STANDARD'] == 2) {
			$md_id[] = 11; //title
		}
		array_push($sql, "
			SELECT md_id, " . setNtext2Text('', 'md_value') . ", lang FROM $this->table_md_values WHERE recno=%i
		", $this->md_record['md']['RECNO']);
		array_push($sql, "
			AND md_id IN %in
		", $md_id);
		array_push($sql, "ORDER BY md_id DESC");
		$record = _executeSql('select', $sql, array('all'));
		if (is_array($record) && count($record) > 0) {
			$x1 = '';
			$y1 = '';
			$x2 = '';
			$y2 = '';
			$poly = '';
			$dc_geom = '';
			$lang_main = '';
			$title = '';
			$title_lang_main = '';
			foreach($record as $row) {
				switch ($this->md_record['md']['MD_STANDARD']){
					case 0:
					case 10:
						if ($row['MD_ID'] == 5527) {
							$lang_main = $row['MD_VALUE'];
						}
						if ($row['MD_ID'] == 497 || $row['MD_ID'] == 5133) {
							$x1 = $row['MD_VALUE'];
						}
						if ($row['MD_ID'] == 498 || $row['MD_ID'] == 5134) {
							$x2 = $row['MD_VALUE'];
						}
						if ($row['MD_ID'] == 499 || $row['MD_ID'] == 5135) {
							$y1 = $row['MD_VALUE'];
						}
						if ($row['MD_ID'] == 500 || $row['MD_ID'] == 5136) {
							$y2 = $row['MD_VALUE'];
						}
						if ($row['MD_ID'] == 503 || $row['MD_ID'] == 5140) {
							$poly = $row['MD_VALUE'];
						}
						if ($row['MD_ID'] == 11 || $row['MD_ID'] == 5063) {
							$title = $row['MD_VALUE'];
							if ($lang_main == $row['LANG']) {
								$title_lang_main = $row['MD_VALUE'];
							}
						}
						break;
					case 1:
						if ($row['MD_ID'] == 14) {
							$dc_geom = $row['MD_VALUE'];
						}
						if ($row['MD_ID'] == 11) {
							$title = $row['MD_VALUE'];
						}
						break;
					case 2:
						if ($row['MD_ID'] == 11) {
							$title = $row['MD_VALUE'];
						}
						break;
				}
			}
			$data = $data +  $this->getGeom($x1, $y1, $x2, $y2, $poly, $dc_geom);
			$title = $title_lang_main != '' ? $title_lang_main : $title;
			$data['title'] = ($title != '') ? $title : NULL;
		}
		$this->setMd($recno, $data);
		// update datestamp v md_values
		if ($this->md_record['md']['MD_STANDARD'] == 0 || $this->md_record['md']['MD_STANDARD'] == 10) {
			$this->setMdValuesDateStamp($this->md_record['md']['RECNO'], $this->md_record['md']['MD_STANDARD'], $update=TRUE);
		}

		// xmldata
		$xml = $this->getMdXmlData($this->md_record['md']['UUID']);
		if ($xml != '') {
			if (DB_DRIVER == 'oracle') {
				$conn = dibi::getConnection()->driver->getResource();
				setXmlToClob($conn, $this->table_md, 'xmldata', "recno=$recno", $xml);
				//$data['pxml%sql'] = "XMLType('$xml')";
				//$data['xmldata'] = $xml;
			} elseif (DB_DRIVER == 'mssql2005') {
					// XMLDATA
					$data['xmldata'] = $xml;
			} elseif (DB_DRIVER == 'postgre') {
					// XMLDATA
					$data['xmldata'] = $xml;
			} elseif (DB_DRIVER == 'postgre_pxml') {
				$xml = str_replace("'", "&#39;", $xml);
				$data['pxml%sql'] = "XMLPARSE(DOCUMENT '$xml')";
			} else {
				$data['pxml'] = $xml;
			}
		}
		
		// --- validace XML ve vestavenem validatoru ---
		$data['valid'] = 0;
		$data['prim'] = 0;
		if (VALIDATOR == 1) {
			require_once WWW_DIR . '/validator/resources/Validator.php';
			$validator = new Validator();
			$validator->run($xml);
			$vResult = $validator->getPass();
			switch ($this->report_valid_type['type']) {
				case 'xml':
					$this->md_record['report_valid'] = $validator->asXML($this->report_valid_type['short']);
					break;
				case 'json':
					$this->md_record['report_valid'] = $validator->asJSON();
					break;
				case 'array':
					$this->md_record['report_valid'] = $validator->asArray($this->report_valid_type['short']);
					break;
				case 'html':
					$this->md_record['report_valid'] = $validator->asHTML($this->report_valid_type['short']);
					break;
				case 'htmlsmall':
					$this->md_record['report_valid'] = $validator->asHTMLSmall($this->report_valid_type['short']);
					break;
				default:
					$this->md_record['report_valid'] = $validator->asHTML();
					break;
			}
			if($vResult){
				if($vResult['fail'] > 0) $data['valid'] = 0;
				else if($vResult['warn'] > 0) $data['valid'] = 1;
				else $data['valid'] = 2;
				$data['prim'] = $vResult['primary'];
			}
			setMickaLog("VALIDACE valid=" . $data['valid'], 'DEBUG', 'MdRecord.updateMd');
		}
		$this->setMd($recno, $data);
		setXmldata2Pxml($this->table_md, $recno);
		return $rs;
	}

	private function newRecord($data, $lang_main, $import) {
		$sql = array();
		$new_uuid = FALSE;
		$data['recno'] = $this->getNewRecno($this->table_md);
		if (array_key_exists('uuid', $data) === FALSE) {
			$data['uuid'] = getUuid();
			$new_uuid = TRUE;
		}
		$mds = $data['md_standard'];
		$data['md_standard'] = array('%i'=>$data['md_standard']);
		$data['data_type'] = array_key_exists('data_type', $data) ? $data['data_type'] : -1;
		$data['data_type'] = $this->isPrivilege('p') === FALSE ? -1 : $data['data_type'];
		$data['create_user'] = $this->user;
		$data['create_date'] = DB_DRIVER == 'mssql2005' ? str_replace('-', '', getNewDate()) : getNewDate();
		if ($this->table_mode == 'tmp') {
			$data['sid'] = $this->sid;
		}
		array_push($sql, "INSERT INTO $this->table_md", $data);
		$result = _executeSql('insert', $sql, array('all'));
		$this->md_record['report'] = 'ok';
		$this->md_record['md']['RECNO'] = $data['recno'];
		$this->md_record['md']['UUID'] = $data['uuid'];
		$this->md_record['md']['MD_STANDARD'] = $mds;
		$this->md_record['user_right'] = 'w';
		if ($new_uuid) {
			$this->setMdValuesFileIdentifier($data['recno'], $mds, $data['uuid'], FALSE);
		}
		if ($import === FALSE) {
			$this->setMdValuesMainLanguage($data['recno'], $mds, $lang_main, FALSE);
			$this->setMdValuesDateStamp($data['recno'], $mds);
		}
		$rs = $data['recno'];
		return $rs;
	}

	public function setNewRecord($data, $lang_main, $import=FALSE) {
		$this->setTableMode('tmp');
		$this->deleteTmpRecords();
		return $this->newRecord($data, $lang_main, $import);
	}

	public function setNewRecordMd($data, $lang_main, $import=FALSE) {
		$this->setTableMode('md');
		return $this->newRecord($data, $lang_main, $import);
	}

	private function setNewMd($data) {
		$sql = array();
		$this->setTableMode('md');
		$data['recno'] = $this->getNewRecno($this->table_md);
		$data['uuid'] = array_key_exists('uuid', $data) ? $data['uuid'] : getUuid();
		$data['data_type'] = 0;
		$data['create_user'] = $this->user;
		$data['create_date'] =DB_DRIVER == 'mssql2005' ? str_replace('-', '', getNewDate()) : getNewDate();

		if ($this->ora_pxml === TRUE) {
			$pxml = '<dummy></dummy>';
			array_push($sql, "INSERT INTO $this->table_md (recno, uuid, md_standard, lang, data_type, create_user, create_date, pxml)");
			array_push($sql, "VALUES (%f, %s, %i, %s, %i, %s, %s , XMLType(%s))", 
						$data['recno'], $data['uuid'], $data['md_standard'],$data['lang'], $data['data_type'], $data['create_user'], $data['create_date'], $pxml);
		}
		else {
			array_push($sql, "INSERT INTO $this->table_md", $data);
		}

		$result = _executeSql('insert', $sql, array('all'));
		$this->md_record['report'] = 'ok';
		$this->md_record['md']['RECNO'] = $data['recno'];
		$this->md_record['md']['UUID'] = $data['uuid'];
		$this->md_record['md']['MD_STANDARD'] = $data['md_standard'];
		$this->md_record['user_right'] = 'w';
		$rs = $data['recno'];
		return $rs;
	}

	public function setMdValues($data, $updt_md=TRUE) {
		$rs = FALSE;
		$update_md = FALSE;
		$this->stop_datestamp = FALSE;
		if (is_array($data) && count($data) > 0) {
			foreach($data as $record) {
				if ($record['recno'] == $this->md_record['md']['RECNO']  && count($record) == 6 && $this->md_record['user_right'] == 'w') {
					if ($record['md_id'] == 44  && $record['md_value'] != '') {
						$this->stop_datestamp = TRUE;
					}
					$update_md = TRUE;
					$sql = array();
					if (DB_DRIVER == 'oracle') {
						// ořez pro ORACLE
						$record['md_value'] = strlen($record['md_value']) > 2000 ? mb_substr($record['md_value'], 0, 2000) : $record['md_value']; 
					}
					array_push($sql, "INSERT INTO $this->table_md_values", $record);
					$result = _executeSql('insert', $sql, array('all'));
				}
				else {
					setMickaLog("Not input data", 'ERROR', 'MdRecord.setMdValues');
				}
			}
			if ($update_md === TRUE && $updt_md === TRUE) {
				$this->updateMd($this->md_record['md']['RECNO']);
			}
		}
		else {
			setMickaLog("Empty data", 'ERROR', 'MdRecord.setMdValues');
		}
		return $rs;
	}

	public function setTmpMdValues($form_data) {
		$rs = array();
		$rs['ok'] = FALSE;
		$rs['report'] = '';
		$this->setTableMode('tmp');
		//my_print_r($form_data);
		$uuid = ($form_data['uuid'] != '') ? htmlspecialchars($form_data['uuid']) : '';
		$recno = ($form_data['recno'] != '') ? htmlspecialchars($form_data['recno']) : -1;
		$block = ($form_data['block'] != '') ? htmlspecialchars($form_data['block']) : -1;
		$nextblock = ($form_data['nextblock'] != '') ? htmlspecialchars($form_data['nextblock']) : -1;
		$profil = ($form_data['profil'] != '') ? htmlspecialchars($form_data['profil']) : -1;
		$nexprofil = ($form_data['nextprofil'] != '') ? htmlspecialchars($form_data['nextprofil']) : -1;
		$mds = ($form_data['mds'] != '') ? htmlspecialchars($form_data['mds']) : -1;
		//$data_type = (isset($form_data['public']) && $form_data['public'] == 'on') ? 1 : 0;
		$data_type = isset($form_data['data_type']) ? htmlspecialchars($form_data['data_type']) : -1;
        $edit_group = isset($form_data['edit_group']) ? htmlspecialchars($form_data['edit_group']) : '';
        $view_group = isset($form_data['view_group']) ? htmlspecialchars($form_data['view_group']) : '';
		$ende = array_key_exists('ende', $form_data) ? $form_data['ende'] : 0;
		if ($recno < 1 || $ende == 0 || count($form_data) < 6 || $mds < 0) {
			setMickaLog("recno=$recno, mds=$mds, ende=$ende, count=" . count($form_data), 'ERROR', 'MdRecord.setTmpMdValues');
			$rs['report'] = 'SYSTEM ERROR, not complete input data!';
			return $rs;
		}
		// kontrola přístupových práv
		$md = $this->getMd($where_col='recno', $where_value=$recno);
		if ($md['report'] != 'ok') {
			$rs['report'] = $md['report'];
			return $rs;
		}
		unset($md);
		
		if ($this->md_record['user_right'] != 'w') {
			$rs['report'] = 'No rights';
			return $rs;
		}
		//my_print_r($this->md_record);
		if ($this->md_record['md']['UUID'] != $uuid) {
			$rs['report'] = 'Error, save other record';
			setMickaLog("Error, save other record. recno=$recno, uuid=$uuid, uuid dle recno=" . $this->md_record['md']['UUID'], 'ERROR', 'MdRecord.setTmpMdValues');
			return $rs;
		}

		// Administrace záznamu
		$data = array();
		$data['data_type'] = $data_type;
        if ($edit_group != '') {
            $data['edit_group'] = $edit_group;
        }
        if ($view_group != '') {
            $data['view_group'] = $view_group;
        }
		$this->setMd($recno, $data);

		// Smazání starých dat
		$pom = getProfilPackages($mds, $profil, $block);
		$this->deleteMdValues($recno, $mds, $pom['profil'], $pom['package']);
		// Vložení nových dat
		$insert_values = array();
		foreach ($form_data as $key => $value) {
			// Přeskočení nedatových záznamů
			if ( $key == 'recno' ||
				 $key == 'uuid' ||
				 $key == 'ak' ||
				 $key == 'w' ||
				 $key == 'iframe' ||
				 $key == 'mds' ||
				 $key == 'block' ||
				 $key == 'nextblock' ||
				 $key == 'profil' ||
				 $key == 'nextprofil' ||
				 $key == 'lang_its' ||
				 $key == 'public' ||
				 $key == 'data_type' ||
				 $key == 'edit_group' ||
				 $key == 'view_group' ||
				 $key == 'ende') {
				continue;
			}
			if ($value != '') {
				if (strpos($key, 'RB_') !== FALSE) {
					continue;
				}
				$pom = explode('|', $key);
				//form_code|lang|package_id|md_path
				if (count($pom) != 4) {
					setMickaLog("BAD key=$key", 'ERROR', 'MdRecord.setTmpMdValues');
					continue;
				}
	      if ($pom[0] == 'R') {
					// RadioButton se neukládá
	        continue;
				}
	      if ($pom[0] == 'D' && MICKA_LANG == 'cze') {
					// Převod na ISO datum
					$value = dateCz2Iso($value);
				}
				$data = array();
				$data['recno'] = $recno;
				$data['md_value'] = trim($value);
				$data['md_id'] = getMdIdFromMdPath($pom[3]);
				$data['md_path'] = $pom[3];
				$data['lang'] = $pom[1];
				$data['package_id'] = $pom[2];
				if ($data['recno'] != '' &&
						$data['md_value'] != '' &&
						$data['md_id'] != '' &&
						$data['md_path'] != '' &&
						$data['lang'] != '' &&
						$data['package_id'] != '') {
					array_push($insert_values, $data);
				}
				else {
					setMickaLog("Incomplet insert data", 'ERROR', 'MdRecord.setTmpMdValues');
				}
			}
		}
		$this->setMdValues($insert_values);
		// aktualizace tabulky MD
		//$this->updateTableMd('recno', $recno);
		$rs['ok'] = TRUE;
		return $rs;
	}


	public function getMd($where_col, $where_value) {
		setMickaLog("Search md where $where_col=$where_value", 'DEBUG', 'MdRecord.getMd');
		$rs = array();
		if ($this->getDataFromMd($where_col, $where_value) === FALSE) {
			setMickaLog("Record not found", 'ERROR', 'MdRecord.getMd');
			$rs['report'] = 'Record not found';
			return $rs;
		}
		$this->setRecordRight();
		$rs['right'] = $this->md_record['user_right'];
		if ($this->md_record['user_right'] == 'x') {
			setMickaLog("Not rights", 'ERROR', 'MdRecord.getMd');
			$rs['report'] = 'Not rights';
			return $rs;
		}
		$rs['report'] = 'ok';
		$rs['table'] = $this->md_record['table'];
		$rs['md'] = $this->md_record['md'];
		setMickaLog("Return OK", 'DEBUG', 'MdRecord.getMd');
		return $rs;
	}

	public function getMdValues($recno, $value_lang='xxx', $profil_id=-1, $package_id=-1) {
		setMickaLog("recno=$recno, value_lang=$value_lang, profil_id=$profil_id, package_id=$package_id, table_mode=$this->table_mode", 'DEBUG', 'MdRecord.getMdValues');
		$rs = array();
		$md = $this->getMd($where_col='recno', $recno);
		if ($md['report'] != 'ok') {
			$rs['report'] = $md['report'];
			return $rs;
		}
		unset($md);
		$pom = getProfilExists($this->md_record['md']['MD_STANDARD'], $profil_id);
		if ($pom['akce'] == 'lite' && $pom['template'] != '') {
			$rs = $this->md_record;
			$rs['report'] = 'ok';
			$rs['profil'] = $pom['profil'];
			$rs['template'] = $pom['template'];
			return $rs;
		}
		else {
			$profil_id = $pom['profil'];
		}
		$pom = getProfilPackages($this->md_record['md']['MD_STANDARD'], $profil_id, $package_id);
		$this->md_record['md_value_package'] = $pom['package'];
		$this->md_record['md_value_profil'] = $pom['profil'];
		$this->getDataFromMdValues($recno, $value_lang='xxx', $pom['profil'], $pom['package']);
		$this->setHierarchy();
		$rs = $this->md_record;
		$rs['report'] = 'ok';
		//my_print_r($rs);
		return $rs;
	}


	private function deleteMd($recno) {
		$rs = FALSE;
		$sql = array();
		if ($recno > 0) {
			array_push($sql, "DELETE FROM $this->table_md WHERE recno=%i", $recno);
		}
		if (count($sql) > 0) {
			$rs = _executeSql('delete', $sql, array('all'));
		}
		else {
			setMickaLog("delete recno=$recno, table_mode=$this->table_mode, SID=$$this->sid", 'ERROR', 'MdRecord.deleteMd');
		}
		return $rs;
	}

	private function deleteMdValues($recno, $mds, $profil_id, $package_id, $value_lang=array()) {
		$sql = array();
		array_push($sql, "DELETE FROM $this->table_md_values WHERE recno=%i", $recno);
		if ($profil_id > -1) {
			array_push($sql, "AND md_id IN(SELECT md_id FROM profil WHERE profil_id=%i)", $profil_id);
		}
		if ($package_id > -1) {
			array_push($sql, "AND package_id=%i", $package_id);
		}
		if ($mds == 0 || $mds == 10) {
			array_push($sql, "AND md_id<>38");
		}
		//if ($mds == 2) {
			// pouze pro mazání přes AJAX
			//array_push($sql, "AND md_id NOT IN(12,13,14,15,16,17,18,19,20,21,24,25,26,27,108)");
		//}
		if (count($value_lang) > 0) {
			array_push($sql, 'AND lang IN %in', $value_lang);
		}
		return _executeSql('delete', $sql, array('all'));
	}

	public function deleteMdIdFromMdValues($recno, $langs, $md_id_array) {
		$sql = array();
		if ($langs == '') {
			return FALSE;
		}
		$langs_array = getMdLangs($langs);
		$langs_array[] = 'xxx';
		if(is_array($md_id_array) && count($md_id_array)> 0) {
			array_push($sql, "DELETE FROM $this->table_md_values WHERE recno=%i", $recno);
			array_push($sql, "AND lang IN %in", $langs_array);
			array_push($sql, "AND md_id IN %in", $md_id_array);
			return _executeSql('delete', $sql, array('all'));
		}
		else {
			return FALSE;
		}
	}

	public function deleteMdValuesBeforeImport($recno) {
		$sql = array();
		if ($recno < 1) {
			return FALSE;
		}
		array_push($sql, "DELETE FROM $this->table_md_values WHERE recno=%i", $recno);
		array_push($sql, "AND md_id<>38");
		return _executeSql('delete', $sql, array('all'));
	}

	private function deleteMdImage() {
		// TODO mazání náhledů
		return $rs;
	}

	public function deleteTmpRecords() {
		$sql = array();
		$this->setTableMode('tmp');
		if ($this->del_tmp === TRUE && $this->sid != '') {
			array_push($sql, "DELETE FROM $this->table_md_values WHERE recno IN (SELECT recno FROM $this->table_md WHERE sid=%s)", $this->sid);
			$result = _executeSql('delete', $sql, array('all'));
			$sql = array();
			array_push($sql, "DELETE FROM $this->table_md WHERE sid=%s", $this->sid);
			$result = _executeSql('delete', $sql, array('all'));
		}
		//$this->deleteMdImage($uuid);
	}

	public function deleteMdRecords($where_col, $where_value) {
		setMickaLog("DELETE metadata where $where_col=$where_value", 'DEBUG', 'MdRecord.deleteMdRecords');
		$rs = array();
		$md = $this->getMd($where_col, $where_value);
		if ($md['report'] != 'ok') {
			$rs['report'] = $md['report'];
			setMickaLog($this->user . ": DELETE metadata $where_col=$where_value, " . $md['report'], 'ERROR', 'MdRecord.deleteMdRecords');
			return $rs;
		}
		unset($md);
		// kontrola přístupových práv
		if ($this->md_record['user_right'] != 'w') {
			$rs['report'] = 'Not right';
			setMickaLog($this->user . ": DELETE metadata $where_col=$where_value, " . $rs['report'], 'ERROR', 'MdRecord.deleteMdRecords');
			return $rs;
		}
		$recno = isset($this->md_record['md']['RECNO']) ? $this->md_record['md']['RECNO'] : -1;
		$uuid = isset($this->md_record['md']['UUID']) ? $this->md_record['md']['UUID'] : '';
		if ($recno > 0) {
			$this->deleteMdValues($recno, $mds=100, $profil_id=-1, $package_id=-1);
			$this->deleteMd($recno);
			//$this->deleteMdImage($uuid);
            $this->deleteHarvestRecords($uuid);
			$rs['report'] = 'ok';
			return $rs;
		}
		else {
			$rs['report'] = "System error (recno=$recno)";
			setMickaLog($this->user . ": DELETE metadata $where_col=$where_value, " . $md['report'], 'ERROR', 'MdRecord.deleteMdRecords');
			return $rs;
		}
	}
	
	/**
	 * DELETE harvestovaných záznamů
	 * 
	 * smažou se všechny záznamy dle server_name, nekontrolují se práva k záznamům
	 * 
	 */
	public function deleteHarvestRecords($server_name) {
		$rs = array();
		$rs['ok'] = FALSE;
		$rs['report'] = '';
        if ($server_name == '') {
            $rs['report'] = 'server_name is empty';
        } else {
            $sql = array();
            array_push($sql, 'SELECT COUNT(name) FROM harvest WHERE name=%s', $server_name);
            $harvest_count = _executeSql('select', $sql, array('single'));
            if ($harvest_count != '' && $harvest_count > 0) {
                // table harvest
                $sql = array();
                array_push($sql, 'DELETE FROM harvest WHERE name=%s', $server_name);
                _executeSql('delete', $sql, array('all'));
				// table md_values
				$sql = array();
				array_push($sql, "DELETE FROM md_values WHERE recno IN (SELECT recno FROM md WHERE create_user='harvest' AND server_name=%s)", $server_name);
				_executeSql('delete', $sql, array('all'));
				// TODO images
				// table md
				$sql = array();
				array_push($sql, "DELETE FROM md WHERE create_user='harvest' AND server_name=%s)", $server_name);
				_executeSql('delete', $sql, array('all'));
			
				$rs['ok'] = TRUE;
				$rs['report'] = 'ok';
			}
        }
		return $rs;
	}

	public function copyRecordToTmp($recno, $new_record=FALSE) {
		setMickaLog("Copy record recno=$recno to TMP", 'DEBUG', 'MdRecord.copyRecordToTmp');
		$rs = array();
		$rs['ok'] = FALSE;
		$rs['recno_tmp'] = -1;
		$rs['report'] = '';
		if ($recno == '' || $recno < 1) {
			setMickaLog("Record not exist", 'ERROR', 'MdRecord.copyRecordToTmp');
			$rs['report'] = 'Record not exist';
			return $rs;
		}
		$this->setTableMode('md');
		$md = $this->getMd('recno', $recno);
		if ($md['report'] != 'ok' || $this->md_record['md']['RECNO'] != $recno) {
			setMickaLog($md['report'], 'ERROR', 'MdRecord.copyRecordToTmp');
			$rs['report'] = $md['report'];
			return $rs;
		}
		if ($this->md_record['user_right'] == 'r') {
			setMickaLog("Not edit rights", 'ERROR', 'MdRecord.copyRecordToTmp');
			$rs['report'] = 'Not edit rights';
			return $rs;
		}
		$mds = isset($md['md']['MD_STANDARD']) ? $md['md']['MD_STANDARD'] : '';
		$this->deleteTmpRecords();
		$sql = array();
		$recno_tmp = $this->getNewRecno($this->table_md);
		$sid = $this->sid;
        $tmp_table_md = TMPTABLE_PREFIX . '_md';
        $tmp_table_md_values = TMPTABLE_PREFIX . '_md_values';
		if ($new_record === TRUE) {
			// nový záznam kopií stávajícího
			$uuid = getUuid();
			$create_user = $this->user;
			$create_date = DB_DRIVER == 'mssql2005' ? str_replace('-', '', getNewDate()) : getNewDate();
			$edit_group = $this->defaultValueMd['edit_group'];
			$view_group = $this->defaultValueMd['view_group'];
			array_push($sql, "
				INSERT INTO $tmp_table_md (sid,recno,uuid,md_standard,lang,data_type,create_user,create_date,edit_group,view_group,x1,y1,x2,y2,the_geom,range_begin,range_end,md_update,title,server_name,pxml,valid)
				SELECT %s,%i,'$uuid',md_standard,lang,0,'$create_user','$create_date','$edit_group','$view_group',x1,y1,x2,y2,the_geom,range_begin,range_end,md_update,title,server_name,pxml,valid
				FROM md WHERE recno=%i
			", $this->sid, $recno_tmp, $recno);
		} else {
			array_push($sql, "
				INSERT INTO $tmp_table_md (sid,recno,uuid,md_standard,lang,data_type,create_user,create_date,edit_group,view_group,x1,y1,x2,y2,the_geom,range_begin,range_end,md_update,title,server_name,xmldata,pxml,valid)
				SELECT %s,%i,uuid,md_standard,lang,data_type,create_user,create_date,edit_group,view_group,x1,y1,x2,y2,the_geom,range_begin,range_end,md_update,title,server_name,xmldata,pxml,valid
				FROM md WHERE recno=%i
			", $this->sid, $recno_tmp, $recno);
		}
		$result = _executeSql('insert', $sql, array('all'));
		$sql = array();
		array_push($sql, "
			INSERT INTO $tmp_table_md_values (recno, md_id, md_value, md_path, lang , package_id)
			SELECT %i, md_id, md_value, md_path, lang , package_id FROM md_values WHERE recno=%i
		", $recno_tmp, $recno);
		$result = _executeSql('insert', $sql, array('all'));
		if ($new_record) {
			// nové uuid do md_values
			if ($mds == 0 || $mds == 10) {
				$sql = array();
				array_push($sql, "DELETE FROM $tmp_table_md_values WHERE recno=%i AND md_id=38", $recno_tmp);
				$result = _executeSql('delete', $sql, array('all'));
				$this->setTableMode('tmp');
				$this->getMd('recno', $recno_tmp);
				$this->setMdValuesFileIdentifier($recno_tmp, $mds, $uuid);
			}
		}
		$rs['recno_tmp'] = $recno_tmp;
		$rs['report'] = 'ok';
		$rs['ok'] = TRUE;
		return $rs;
	}

	public function copyTmpRecordToMd() {
		setMickaLog("Copy record to TMP", 'DEBUG', 'MdRecord.copyTmpRecordToMd');
		$rs = array();
		if (isset($this->md_record['md']) === FALSE || count($this->md_record['md']['RECNO']) < 1) {
			setMickaLog("Record not found", 'ERROR', 'MdRecord.copyTmpRecordToMp');
			$rs['report'] = 'Record not found';
			return $rs;
		}
		if ($this->md_record['user_right'] != 'w' || $this->user == 'guest') {
			setMickaLog("Not rights", 'ERROR', 'MdRecord.copyTmpRecordToMp');
			$rs['report'] = 'Not rights';
			return $rs;
		}
		// data z tmp
		$recno_tmp = $this->md_record['md']['RECNO'];
		$this->setTableMode('tmp');
		$md = $this->getMd($where_col='recno', $where_value=$recno_tmp);
		$data_tmp = array();
		$data_tmp  = $this->md_record['md'];
		// data z md
		$this->setTableMode('md');
        $tmp_table_md_values = TMPTABLE_PREFIX . '_md_values';
		$md = $this->getMd($where_col='uuid', $where_value=$data_tmp['UUID']);
		if ($md['report'] == 'Record not found') {
			// Nový záznam
			setMickaLog("New record to MD", 'DEBUG', 'MdRecord.copyTmpRecordToMp');
			$data['uuid'] = $data_tmp['UUID'];
			$data['md_standard'] = $data_tmp['MD_STANDARD'];
			$data['lang'] = 'eng';
			// FIXME: optimalizace
			$data_md = array();
			$recno = $this->setNewMd($data);
			$data_md['lang'] = $data_tmp['LANG'];
			$data_md['data_type'] = $data_tmp['DATA_TYPE'];
			$data_md['last_update_user'] = $this->user;
			$data_md['last_update_date'] = DB_DRIVER == 'mssql2005' ? str_replace('-', '', getNewDate()) : getNewDate();
			$data_md['edit_group'] = ($data_tmp['EDIT_GROUP'] != '') ? $data_tmp['EDIT_GROUP'] : $this->user;
			$data_md['view_group'] = ($data_tmp['VIEW_GROUP'] != '') ? $data_tmp['VIEW_GROUP'] : $this->user;
			$data_md['x1'] = ($data_tmp['X1'] != '') ? $data_tmp['X1'] : NULL;
			$data_md['y1'] = ($data_tmp['Y1'] != '') ? $data_tmp['Y1'] : NULL;
			$data_md['x2'] = ($data_tmp['X2'] != '') ? $data_tmp['X2'] : NULL;
			$data_md['y2'] = ($data_tmp['Y2'] != '') ? $data_tmp['Y2'] : NULL;
			$data_md['the_geom'] = ($data_tmp['THE_GEOM'] != '') ? $data_tmp['THE_GEOM'] : NULL;
			$data_md['range_begin'] = ($data_tmp['RANGE_BEGIN'] != '') ? $data_tmp['RANGE_BEGIN']->format('Y-m-d') : NULL;
			$data_md['range_end'] = ($data_tmp['RANGE_END'] != '') ? $data_tmp['RANGE_END']->format('Y-m-d') : NULL;
			$data_md['md_update'] = ($data_tmp['MD_UPDATE'] != '') ? $data_tmp['MD_UPDATE'] : NULL;
			$data_md['title'] = ($data_tmp['TITLE'] != '') ? $data_tmp['TITLE'] : NULL;
			$data_md['valid'] = $data_tmp['VALID'];
			$data_md['prim'] = $data_tmp['PRIM'];
			if (DB_DRIVER == 'oracle') {
				$xml = $data_tmp['PXML'] != '' ? $data_tmp['PXML'] : NULL;
				if ($xml != '') {
					$conn = dibi::getConnection()->driver->getResource();
					setXmlToClob($conn, $this->table_md, 'xmldata', "recno=$recno", $xml);
				}
			} elseif (DB_DRIVER == 'postgre') {
				//$data_md['pxml%sql'] = ($data_tmp['PXML'] != '') ? "XMLPARSE(DOCUMENT '" . $data_tmp['PXML'] . "')" : NULL;
				$data_md['xmldata'] = ($data_tmp['PXML'] != '') ? $data_tmp['PXML'] : NULL;
			} elseif (DB_DRIVER == 'mssql2005') {
				$data_md['xmldata'] = ($data_tmp['PXML'] != '') ? $data_tmp['PXML'] : NULL;
			} else {
				$data_md['pxml'] = ($data_tmp['PXML'] != '') ? $data_tmp['PXML'] : NULL;
			}
			$this->setMd($recno, $data_md);
			setXmldata2Pxml($this->table_md, $recno);
			$sql = array();
			array_push($sql, "
				INSERT INTO md_values (recno, md_id, md_value, md_path, lang , package_id)
				SELECT %i, md_id, md_value, md_path, lang , package_id FROM $tmp_table_md_values WHERE recno=%i
			", $recno, $recno_tmp);
			$result = _executeSql('insert', $sql, array('all'));
		} elseif ($md['report'] == 'ok' && $md['right'] == 'w') {
			// Update záznamu
			$data_md = array();
			$recno = $md['md']['RECNO'];
			$data_md['lang'] = $data_tmp['LANG'];
			$data_md['data_type'] = $data_tmp['DATA_TYPE'];
			$data_md['last_update_user'] = $this->user;
			$data_md['last_update_date'] = DB_DRIVER == 'mssql2005' ? str_replace('-', '', getNewDate()) : getNewDate();
			$data_md['edit_group'] = ($data_tmp['EDIT_GROUP'] != '') ? $data_tmp['EDIT_GROUP'] : $this->user;
			$data_md['view_group'] = ($data_tmp['VIEW_GROUP'] != '') ? $data_tmp['VIEW_GROUP'] : $this->user;
			$data_md['x1'] = ($data_tmp['X1'] != '') ? $data_tmp['X1'] : NULL;
			$data_md['y1'] = ($data_tmp['Y1'] != '') ? $data_tmp['Y1'] : NULL;
			$data_md['x2'] = ($data_tmp['X2'] != '') ? $data_tmp['X2'] : NULL;
			$data_md['y2'] = ($data_tmp['Y2'] != '') ? $data_tmp['Y2'] : NULL;
			$data_md['the_geom'] = ($data_tmp['THE_GEOM'] != '') ? $data_tmp['THE_GEOM'] : NULL;
			$data_md['range_begin'] = ($data_tmp['RANGE_BEGIN'] != '') ? $data_tmp['RANGE_BEGIN']->format('Y-m-d') : NULL;
			$data_md['range_end'] = ($data_tmp['RANGE_END'] != '') ? $data_tmp['RANGE_END']->format('Y-m-d') : NULL;
			$data_md['md_update'] = ($data_tmp['MD_UPDATE'] != '') ? $data_tmp['MD_UPDATE'] : NULL;
			$data_md['title'] = ($data_tmp['TITLE'] != '') ? $data_tmp['TITLE'] : NULL;
			$data_md['valid'] = $data_tmp['VALID'];
			$data_md['prim'] = $data_tmp['PRIM'];
			if (DB_DRIVER == 'oracle') {
				//$data_md['pxml%sql'] = ($data_tmp['PXML'] != '') ? "XMLType('" . $data_tmp['PXML'] . "')" : NULL;
				$xml = $data_tmp['PXML'] != '' ? $data_tmp['PXML'] : NULL;
				if ($xml != '') {
					$conn = dibi::getConnection()->driver->getResource();
					setXmlToClob($conn, $this->table_md, 'xmldata', "recno=$recno", $xml);
				}
			} elseif (DB_DRIVER == 'postgre') {
				//$data_md['pxml%sql'] = ($data_tmp['PXML'] != '') ? "XMLPARSE(DOCUMENT '" . $data_tmp['PXML'] . "')" : NULL;
				$data_md['xmldata'] = ($data_tmp['PXML'] != '') ? $data_tmp['PXML'] : NULL;
			} elseif (DB_DRIVER == 'mssql2005') {
				$data_md['xmldata'] = ($data_tmp['PXML'] != '') ? $data_tmp['PXML'] : NULL;
			} else {
				//$data_md['pxml'] = ($data_tmp['PXML'] != '') ? $data_tmp['PXML'] : NULL;
			}
			$this->setMd($recno, $data_md);
			setXmldata2Pxml($this->table_md, $recno);
			$this->deleteMdValues($recno, 100, -1, -1, array());
			$sql = array();
			array_push($sql, "
				INSERT INTO md_values (recno, md_id, md_value, md_path, lang , package_id)
				SELECT %i, md_id, md_value, md_path, lang , package_id FROM $tmp_table_md_values WHERE recno=%i
			", $recno, $recno_tmp);
			$result = _executeSql('insert', $sql, array('all'));
		} else {
			$rs['report'] = $md['report'];
			return $rs;
		}
		unset($md);
		$rs['report'] = 'ok';
		return $rs;
	}

	public function setRecordAdmin($form_akce, $form_data) {
		$rs = 'error';
		if ($form_data['recno'] == '') {
			return $rs;
		}
		$del_lang = array();
		$recno = $form_data['recno'];
		$record = $this->getMdValues($recno, 'xxx', 0, 0);

		if ($record['report'] != 'ok' || $this->md_record['md']['RECNO'] != $recno) {
			setMickaLog($record['report'], 'ERROR', 'MdRecord.setRecordAdmin');
			//$rs['report'] = $md['report'];
			return $rs;
		}
		if ($this->md_record['user_right'] == 'r') {
			setMickaLog("Not edit rights", 'ERROR', 'MdRecord.setRecordAdmin');
			//$rs['report'] = 'Not rights';
			return $rs;
		}

		if (isset($record['md']['RECNO']) && $record['md']['RECNO'] > -1) {
			// jazyk metadat
			$md_lang = '';
			if ($record['md']['MD_STANDARD'] == 0 || $record['md']['MD_STANDARD'] == 10) {
				foreach ($record['md_values'] as $row) {
					if ($row['MD_ID'] == 5527) {
						$md_lang = $row['MD_VALUE'];
						break;
					}
				}
			}
			if ($md_lang != '' && strpos($form_data['lang'], $md_lang) === FALSE) {
				$form_data['lang'] = $form_data['lang'] == '' ? $md_lang : $md_lang . '|' . $form_data['lang'];
			}

			$langs = explode('|', $form_data['lang']);
			$orig_lang = explode('|', $record['md']['LANG']);
			foreach($orig_lang as $kl => $hod) {
				$key = array_search($hod, $langs);
				if ($key === false) {
					$del_lang[] = $hod;
				}
			}
			if ($form_akce == 'form' && count($del_lang) > 0) {
				$_SESSION['micka']['rec_admin']['recno'] = $form_data['recno'];
				$_SESSION['micka']['rec_admin']['edit_group'] = $form_data['edit_group'];
				$_SESSION['micka']['rec_admin']['view_group'] = $form_data['view_group'];
				//$_SESSION['micka']['rec_admin']['data_type'] = $form_data['data_type'];
				$_SESSION['micka']['rec_admin']['lang'] = $form_data['lang'];
				return 'warning';
			}
			elseif ($form_akce == 'warning' && count($del_lang) > 0) {
				$this->deleteMdValues($recno, -1, -1, -1, $del_lang);
			}
			if ($this->setMd($recno, $form_data)) {
				$rs = 'ok';
			}
		}
		return $rs;
	}

}
?>
