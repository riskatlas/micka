<?php
/**
 * Dolování dat z Micky
 * ======================================
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20141107
 * @authors    DZ
 */
class MdExport
{
	public $xml_from = 'cache'; // zdroj xml - cache nebo data
	public $error = 1; // pokud dojde k chybě, bude -1
	//public $uuid = ''; // hledání pouze dle uuid, jednodušší dotaz
	public $sql_language_ex = '';
	public $page_number = 1;
	public $only_public = FALSE;
	public $only_private = FALSE;
	private $query_in = array(); // pole s dotazem
	private $query_out_md = array();
	private $query_out_value = array();
	private $query_status = TRUE;
	private $query_error = array('code' => '', 'element' => '');
	private $sql_final = array();
	private $table_mode = 'md';
	private $table_md = 'md';
	private $table_md_values = 'md_values';
	private $sql_operation = '';
	private $sql_mds = '';
	private $sql_uuid = '';
	private $sql_md_params = array();
	private $sql_md = '';
	private $sql_or = '';
	private $search_uuid = FALSE;
	private $rs_xml = '';
	private $sid = '';
	private $type_mapping = array();
	private $may_edit = 0; //true
	private $may_records = FALSE;
	private $ext_header = FALSE;
	private $hits = FALSE;
	private $user;
	private $user_admin = FALSE; // Administrator
	private $bbox = null; // bbox z dotazu
	private $maxRecords; // maximální počet záznamů
	private $useOrderByXmlPath; // používat XML Path v SQL pro řazení
	private $sortBy;

	function __construct($user, $startPosition=0, $maxRecords='', $sortBy='') {
		// nastavení výchozích hodnot
		$this->sid = session_id();
		$this->sql_final[0] = '';
		// stránkování
		if ($startPosition == '') {
			$startPosition = 0;
		}
		if ($startPosition > 0) {
			$startPosition--;
		}
		$this->startPosition = $startPosition;
		$this->setMaxRecords($maxRecords);
		$this->nextRecord = 0;

		// řazení
		// 'title,ASC'  [0] => title [1] => ASC
		$sortBy = str_replace('|', ',', $sortBy); // pokud je ještě oddělovač |
		if ($sortBy == '' || $sortBy == ',') {
			$sortBy = SORT_BY;
		}
		$this->sortBy = getSortBy($sortBy, $ret='array');

		// user
		$this->user = trim($user) == '' ? 'guest' : $user;
		
		// admin
		$this->setUserAdmin();
		
		// FULLTEXT pro ORDER BY
		$this->useOrderByXmlPath = substr(MICKA_VERSION, 0, 1) == 4 ? FALSE : TRUE;
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

	private function setUserAdmin() {
		$this->user_admin = canAction('*');
	}
	
	private function setMaxRecords($maxRecords) {
		if (substr(MICKA_VERSION, 0, 1) == 4) {
			if ($maxRecords == '' || $maxRecords > LIMIT_PG_EXP) {
				$maxRecords = LIMIT_PG_EXP;
			}
		} else {
			if ($maxRecords == '') {
				$maxRecords = MAXRECORDS;
			}
			elseif ($maxRecords > LIMITMAXRECORDS) {
				$maxRecords = LIMITMAXRECORDS;
			}
		}
		$this->maxRecords = $maxRecords;
	}
	
	private function setQueryError($element) {
		$this->query_status = FALSE;
		$this->query_error['element'] = $this->query_error['element'] == '' 
						? $element 
						: $this->query_error['element'] . ', ' . $element;
	}

	private function setMayEdit($edit) {
		$this->may_edit = $edit == 1 || $edit == TRUE ? 1 : 0;
	}
	
	private function setMayRecords($create_user) {
		$this->may_records = strpos($create_user, "'" . $this->user . "'") == FALSE ? FALSE : TRUE;
	}
	
	private function set2FileLog($data) {
		$fileName = CSW_LOG . '/' . $this->user . '-' . md5(uniqid(rand(), true)) . '.log';
		$fh = fopen($fileName, 'wt');
		if (is_array($data)) {
			fwrite($fh, print_r($data, TRUE));
		} else {
			fwrite($fh, $data);
		}
		fclose($fh);
	}

	private function isSimpleQuery() {
		$rs = TRUE;
		$con_and = 0;
		$con_or = 0;
		
		foreach ($this->query_in as $value) {
			if (is_array($value)) {
				$rs = FALSE;
				break;
			} else {
				if (strtoupper($value) == 'AND') {
					$con_and++;
				} elseif (strtoupper($value) == 'OR') {
					$con_or++;
				}
			}
		}
		if ($con_and > 0 && $con_or > 0) {
			$rs = FALSE;
		}
		//$rs = FALSE;
		return $rs;
	}

	private function setSqlMd() {
		$this->sql_md = '';
		if (count($this->sql_md_params) > 0) {
			foreach ($this->sql_md_params as $key => $value) {
				$this->sql_md .= $value . ' AND ';
			}
		}
		$this->sql_md .= $this->getRight();
	}
	
	private function setSqlOr($in) {
		if (count($in) > 0) {
			foreach ($in as $key => $value) {
				if ($value != 'OR') {
					$this->sql_or .= '(' . $value . ')';
				}
				else {
					$this->sql_or .= ' ' . $value . ' ';
				}
			}
			$this->sql_or = $this->sql_or == '' ? $this->sql_or : '(' . $this->sql_or . ') AND ';
		}
	}

	private function setMdParams($in) {
		// Vypnuto
		return $in;
		$rs_empty = FALSE;
		$rs_key = array();
		$pom = array();
		if (is_array($in) === TRUE && is_array($in[0]) === TRUE && count($in[0]) > 0) {
			foreach ($in[0] as $key => $value) {
				if (is_array($value) === FALSE) {
					$value = trim($value);
					if ($value[0] == '_') {
						$this->sql_md_params[] = $this->pracParserData($value, FALSE);
						array_pop($rs_key);
					}
					else {
						$rs_key[] = $key;
					}
				}
				elseif ($key == 0 && is_array($value) === TRUE && count($value) == 3 && $value[1] == 'OR') {
					foreach ($value as $row) {
						$pom[] = $this->pracParserData($row, FALSE);
					}
					array_pop($rs_key);
					$rs_empty = TRUE;
				}
				else {
					$rs_key[] = $key;
				}
			}
		}
		if (count($rs_key) > 0 || $rs_empty === TRUE) {
			$rs = array();
			foreach ($rs_key as $value) {
				$rs[] = $in[0][$value];
			}
			$in = $rs;
			$in_first = trim(strtoupper($in[0]));
			if ($in_first == 'AND') {
				array_shift($in);
			}
		}
		$this->setSqlOr($pom);
		return $in;
	}
	
	private function setQueryIn($query) {
		$this->query_in = $query;
		if (count($query) == 1) {
			if(isset($query[0]) && is_array($query[0]) && count($query[0]) > 0) {
				$this->query_in = $query[0];
			}
		}
	}


	private function setFlatParams($params) {
		$this->sql_mds = '';
		foreach ($params as $key => $value) {
			/*
			if ($key == 'MDS') {
				if ($value == 0) {
					$this->sql_mds .= 'md.md_standard IN (0,10) AND ';
				}
				elseif ($value == 1 || $value== 2 || $value == 10) {
					$this->sql_mds .= "md.md_standard=$value  AND ";
				}
			}
			elseif ($key == 'VALID') {
			 */
			if ($key == 'VALID') {
				$this->sql_mds .= "md.valid $value  AND ";
			}
			elseif ($key == 'ID') {
				$this->search_uuid = TRUE;
				$this->sql_uuid = $value;
			}
			elseif ($key == 'extHeader') {
				$this->ext_header = $value === TRUE || strtoupper($value) == 'TRUE' ? TRUE : FALSE;
			}
			elseif ($key == 'hits') {
				$this->hits = $value === TRUE || strtoupper($value) == 'TRUE' ? TRUE : FALSE;
			}
		}
	}
	
	private function getHarvestor($server_name) {
		$rs = array();
		$rs['harvest_source'] = '';
		$rs['harvest_title'] = '';
		if ($server_name != '') {
			$sql = array();
			array_push($sql, 'SELECT harvest.source, md.title FROM harvest JOIN md ON md.uuid = harvest.name WHERE harvest.name=%s', $server_name);
			$data = _executeSql('select', $sql, array('all'));
			if (is_array($data) && count($data) == 1) {
				$rs['harvest_source'] = $data[0]['SOURCE'];
				$rs['harvest_title'] = $data[0]['TITLE'];
			}
		}
		return $rs;
	}

	private function setMdHeader($recno_arr) {
		$sql = array();
		array_push($sql, "
			SELECT recno, uuid, lang, last_update_date, x1, x2, y1, y2 FROM $this->table_md WHERE recno IN (%i)
		", $recno_arr);
		$result = _executeSql('select', $sql, array('all'));
		if (is_array($result) && count($result) > 0) {
			foreach ($result as $row) {
				$recno = $row['RECNO'];
				$this->md[$recno]['uuid'] = $row['UUID'];
				$this->md[$recno]['lang'] = $row['LANG'];
				if (DB_DRIVER == 'mssql2005' && is_object($row['LAST_UPDATE_DATE'])) {
					$this->md[$recno]['updated'] = $row['LAST_UPDATE_DATE']->format('Y-m-d');
				}
				else {
					$this->md[$recno]['updated'] = $row['LAST_UPDATE_DATE'];
				}
				$this->md[$recno]['x1'] = str_replace(",", ".", $row['X1']);
				$this->md[$recno]['x2'] = str_replace(",", ".", $row['X2']);
				$this->md[$recno]['y1'] = str_replace(",", ".", $row['Y1']);
				$this->md[$recno]['y2'] = str_replace(",", ".", $row['Y2']);
			}
		}
	}

	private function getIdElements() {
		$rs = array();
		$sql[] = "SELECT	tree.md_id, tree.md_standard, elements.el_name
							FROM elements JOIN tree ON (elements.el_id = tree.el_id)";
		$result = _executeSql('select', $sql, array('all'));
		foreach ($result as $row) {
			$mds = $row['MD_STANDARD'];
			$id = $row['MD_ID'];
			$rs[$mds][$id] = $row['EL_NAME'];
		}
		return $rs;
	}

	/*
	 * Tvorba XML
	 * --------------------------------------------------------------------------------------------
	 * $rs_xml:			XML
	 * $value_arr:	sestavené pole, stromová struktura metadat
	 * $hl:				 	hlavička záznamu (informace z tabulky MD - uuid, x1,y1,x2,y2)
	 * $pom_arr:		zásobník použitých elementů, používá se pro ukončování
	 * $level:			úroveň zanoření v poli (počítají se pouze elementy, ne číselné indexy)
	 * $level_pom:	předcházející úroveň zanoření, určuje počet uzavíracích elementů ze zásobníku
	 * $i:					určuje, jestli je to první hodnota se stejným levelem
	 * 								0: první hodnota
	 * 								1: další
	 * LANG:				!xxx není určen jazyk, jinak třeba !cze
	 */
	private function printMDXML($value_arr, $level = 0, $el_end = '', $pom_multi = 0) {
		$level++;
		$i = 0;
		foreach ($value_arr as $key => $item) {
			if ($level == 1) { // načtení hlavičky záznamu
				$this->recno_in = $key;
			}
			if (is_numeric($key) && $level > 1 ) { // opakovaná položka
				$this->opak = $key; //pořadí opakování
				if ($key > 0) {
					$key_arr = array_keys($value_arr); // 0 nemusí být první!
					if ($key > $key_arr[0]) {
						$pom_multi = 1;
					}
				}
			}
			if (!is_numeric($key) && $key{0} != '!') { // elementy
				if (isset($this->level_pom) && $this->level_pom > $level) { // ukončující elementy
					while ($this->level_pom > $level) {
						$this->rs_xml .= "</".end($this->pom_arr).">";
						$this->level_pom = $this->level_pom - 2;
						array_pop($this->pom_arr);
					}
				}
				if ($pom_multi) { // ukončující a nový element pro opakované položky
					$this->rs_xml .= "</".end($this->pom_arr).">";
					$this->rs_xml .= "<".end($this->pom_arr).">";
					$pom_multi = 0;
				}
				if ($key == 'MD_Metadata') { // zápis hlavičky záznamu do XML
					$recno_in = $this->recno_in;
					$this->rs_xml .= "<" . $key . " uuid=\"" . $this->md[$recno_in]['uuid'] . "\"" .
																	" langs=\"".getCountLang($this->md[$recno_in]['lang'])."\"" .
																	" updated=\"".$this->md[$recno_in]['updated']."\"" .
																	" x1=\"".$this->md[$recno_in]['x1']."\"" .
																	" x2=\"".$this->md[$recno_in]['x2']."\"" .
																	" y1=\"".$this->md[$recno_in]['y1']."\"" .
																	" y2=\"".$this->md[$recno_in]['y2']."\"" .	">";
					$this->rs_xml .= getMdOtherLangs($this->md[$recno_in]['lang'], 'xxx');
				}
				elseif ($key == 'metadata') { // zápis hlavičky záznamu do XML
					$recno_in = $this->recno_in;
					$this->rs_xml .= "<" . $key . " uuid=\"" . $this->md[$recno_in]['uuid'] . "\"" .
																	" langs=\"".getCountLang($this->md[$recno_in]['lang'])."\"" .
																	" updated=\"".$this->md[$recno_in]['updated']."\"" .
																	" x1=\"".$this->md[$recno_in]['x1']."\"" .
																	" x2=\"".$this->md[$recno_in]['x2']."\"" .
																	" y1=\"".$this->md[$recno_in]['y1']."\"" .
																	" y2=\"".$this->md[$recno_in]['y2']."\"" .	">";
				}
				elseif ($key == 'featureCatalogue') { // zápis hlavičky záznamu do XML
					$recno_in = $this->recno_in;
					$this->rs_xml .= "<" . $key . " uuid=\"" . $this->md[$recno_in]['uuid'] . "\"" . ">";
				}
				else { // nový element
					$this->rs_xml .= "<$key>";
				}
				$el_end  = $key;
				$this->pom_arr[] = $key; // zásobník elementů (používá se na ukončování)
			}
			if (is_array($item)) {
				$this->printMDXML($item, $level, $el_end, $pom_multi);
			}
			else {
				$this->level_pom = $level;
				if ($key{0} == '!' && strlen($key) == 4) { // hodnota LANG
					if ($i == 0) { // první hodnota se stejným levelem
						if ($pom_multi) { // ukončující a nový element pro opakované položky
							$this->rs_xml .= "</".end($this->pom_arr).">";
							$this->rs_xml .= "<".end($this->pom_arr).">";
							$pom_multi = 0;
						}
						if (substr($key,1,3) != 'xxx') {
							$of = strlen($this->rs_xml)-(strlen(end($this->pom_arr))+2);	// odstranění posledního elementu
							$pom1 = trim(substr($this->rs_xml,$of));								// bude přidán s parametrem LANG
							if ($pom1 == "<".end($this->pom_arr).">") {
								$this->rs_xml = substr($this->rs_xml,0,strlen($this->rs_xml)-(strlen(end($this->pom_arr))+2));
							}
							$por_i = '';
							// TODO: zobecnit, nemusí to být jen keyword
							if (end($this->pom_arr) == 'keyword') {
								$por_i = ' i="' . $this->opak . '"';
							}
							$this->rs_xml .= "<" . end($this->pom_arr) . "$por_i lang='" . substr($key,1,3) . "'>" . htmlspecialchars($item); // přidání LANG
						}
						else {
							$this->rs_xml .= htmlspecialchars($item); // zápis hodnoty
						}
						$i = 1;
					}
					else {	// další hodnota se stejným levelem, nebude se odstraňovat poslední element, zopakuje se
						if (substr($key,1,3) != 'xxx') {
							$por_i = '';
							if (end($this->pom_arr) == 'keyword') {
								$por_i = ' i="' . $this->opak . '"';
							}
							$this->rs_xml .= "</" . end($this->pom_arr) . ">";
							$this->rs_xml .= "<" . end($this->pom_arr) . "$por_i lang='" . substr($key,1,3) . "'>";
						}
						else {
							$this->rs_xml .= "</" . end($this->pom_arr) . ">";
							$this->rs_xml .= "<" . end($this->pom_arr) . ">";
						}
						$this->rs_xml .= htmlspecialchars($item); // zápis hodnoty
					}
				}
			}
		} //end foreach
		$level--;
		if ($level == 0) { // Ukončovací elementy
			if (isset($this->pom_arr) && count($this->pom_arr) > 0) {
				$this->pom_arr = array_reverse($this->pom_arr); // Výpis zásobníku v opačném pořadí
				foreach ($this->pom_arr as $value) {
					$this->rs_xml .= "</$value>";
				}
			}
		}
	}
	
	private function setTypeMapping() {
		$this->type_mapping = array(
			'abstract' => '5061,4',
			'contact' => '5029,187',
			'coupling' => '5529',
			'CRS_ID' => '1157',
			'date' => '5077,14',
			'datestamp' => '44',
			'datetype' => '974,5090',
			'denom' => '99',
			'format' => '8,4742',
			'hlname' => '123',
			'keyword' => '5,4920,88',
			'language' => '5527,39,12',
			'lineage' => '52',
			'linkage' => '47',
			'md_contact' => '153',
			'operatesid' => '5905',
			'operateson' => '5831',
			'operatesonid' => '5906',
			'parent' => '121',
			'resourceid' => '185,5079',
			'role' => '5089,5039,1047,190',
			'sp.date' => '4455,4079,2951,2387,3139,928,4643,3703,3327,4267,3515,2575,2199,3891,2763',
			'sp.degree' => '2505,3445,913,2693,2317,2881,2129,4573,4385,4009,3633,3257,4197,3821,3069',
			'sp.dtype' => '3326,3702,2950,4454,4266,3890,2574,4078,2762,2198,4642,2386,3514,5546,3138,927',
			'sp.title' => '2341,4409,3657,4597,3281,4221,3093,3845,2905,2529,914,4033,2153,3469,2717',
			'stype' => '5124',
			'title' => '11,5063',
			'topic' => '362,5292',
			'type' => '623,7',
			'uuidref' => '5541'
		);
	}

	private function getTypeMapping($mapping) {
        /*
         * NOT
         * SELECT md.recno FROM md WHERE (SELECT count(*) FROM md_values WHERE md.recno=md_values.recno AND md_id=12)=0
         */
		$rs = '';
		$no = '';
		$sql = array();
		if ($this->sql_operation == 'NOT') {
			$no = 'NOT ';
		}
		if ($mapping != '') {
			if (count($this->type_mapping) == 0) {
				$sql[] = "
					SELECT md_id, md_mapping FROM tree WHERE md_mapping IS NOT NULL ORDER BY md_mapping
				";
				$dbres = _executeSql('select', $sql, array('all'));
				foreach ($dbres as $row) {
					$mapp = trim($row['MD_MAPPING']);
					if (array_key_exists($mapp, $this->type_mapping)) {
						$this->type_mapping[$mapp] .= ',' . $row['MD_ID'];
					}
					else {
						$this->type_mapping[$mapp] = $row['MD_ID'];
					}
				}
				// FIXME
				if ($mapping == 'operatesonid') {
					$this->type_mapping[$mapp] = '5906';
				}
			}
			if (isset($this->type_mapping[$mapping]) && $this->type_mapping[$mapping] != '') {
				if (strpos($this->type_mapping[$mapping], ',') === FALSE) {
					$rs = 'md_values.md_id=' . $this->type_mapping[$mapping] . " AND $no";
				}
				else {
					$rs = 'md_values.md_id IN (' . $this->type_mapping[$mapping] . ") AND $no";
				}
				
			}
		}
		return $rs;
	}

	private function walkInputArray($in) {
		foreach ($in as $field) {
			if (is_array($field)) {
				$this->walkInputArray($field);
			}
			else {
				if ($field{0} == '%' || $field{0} == '@' || $field{0} == '_' || $field{0} == '/') {
					$this->pracParserData($field);
				}
			}
		}
	}

	private function parserSearchText($data, $con) {
		$in = $data;
		$i = (stripos($data, '% LIKE')); // nerozlišuje velikost písmen
		if ($i === FALSE) {
			$i = (stripos($data, '% ='));
		}
		if ($i === FALSE) {
			//echo "EL:" . setSqlLike('md_path_el',$elpath) . "<br>";
		}
		else {
//			$pol = explode(' ', $in);
//			$elpath = $pol[0];
//			if ($elpath == '%') {
				$pos0 = stripos($data, '% LIKE');
				$pos1 = strpos($data, "'", $pos0);
				$pos2 = strpos($data, "'", $pos1+1);
				$pom_like = substr($data, $pos1, ($pos2-$pos1)+1);
				if (str_replace(' ', '', $pom_like) == "'%'") {
					$pom_like = "'%'";
				}
				$pom_b = substr($data, 0, $pos0);
				$pom_e = substr($data, $pos2+1);
				if ($pom_like != "'%'") {
					if (DB_DRIVER == 'oracle' && DB_FULLTEXT == 'ORACLE-CONTEXT') {
						$pom_like = str_replace("'", '', $pom_like);
						$pom_like = preg_replace('/\s+/', ' ',trim($pom_like));
						$pom_like = str_replace("&", '\&', $pom_like);
						$pom_like = str_replace("=", '\=', $pom_like);
						//$pom_like = str_replace(" ", '% & %', $pom_like);
						if ($pom_like[0] == '%') {
							$pom_like = mb_substr($pom_like, 1);
						}
						$pom_like = str_replace(" ", '% & ', $pom_like);
						$pom_like = str_replace("-", '\-', $pom_like);
						$pom_like = str_replace("_", '\_', $pom_like);
						$pom_like = str_replace("/", '', $pom_like);
						$data = "contains(pxml, '" . $pom_like . "', 1) > 0";
						$ora_md = TRUE;
					} else {
						$data = $pom_b . setSqlLike('md_values.md_value',$pom_like) . $pom_e;
					}
				} else {
					$data = '';
				}
		}
		if ($data != '') {
			$rs = array();
			$rs['con'] = $con;
			$rs['sql'] = $data;
			$this->query_out_value[] = $rs;
		}
		if ($data == '') {
			$this->setQueryError($in);
		}
		return $data;
	}

	private function parserMdMapping($data, $con) {
		$pos1 = strpos($data, '@');
		$pos2 = strpos($data, ' ', $pos1);
		if ($pos1 === FALSE || $pos2 === FALSE) {
			$this->setQueryError($data);
			return $data;
		}
		$mapping = trim(substr($data, $pos1+1, $pos2-($pos1+1)));
        if ($mapping == 'keyword' && stripos($data, '|') !== FALSE) {
            $data = $this->parserThesaurusKeyword($data);
            if ($data != '') {
                $rs = array();
                $rs['con'] = $con;
                $rs['sql'] = $data;
                $this->query_out_value[] = $rs;
            }
            return $data;
        } elseif ($mapping == 'innaco' && stripos($data, ':') !== FALSE) {
            $data = $this->parserIndividualNameContact($data);
            if ($data != '') {
                $rs = array();
                $rs['con'] = $con;
                $rs['sql'] = $data;
                $this->query_out_value[] = $rs;
            }
            return $data;
        } elseif ($mapping == 'mdinnaco' && stripos($data, ':') !== FALSE) {
            $data = $this->parserMdIndividualNameContact($data);
            if ($data != '') {
                $rs = array();
                $rs['con'] = $con;
                $rs['sql'] = $data;
                $this->query_out_value[] = $rs;
            }
            return $data;
        }
		$type = $this->getTypeMapping($mapping);
        //echo $type; exit;
		$pos0 = stripos($data, 'LIKE');
		if ($pos0 === FALSE) {
			$data = str_replace("@$mapping", $type . 'md_values.md_value', $data);
			if (strpos($data, "= ''") !== FALSE || strpos($data, "=''") !== FALSE) {
				$data = "SELECT md.recno, md.last_update_date, md.title FROM md LEFT JOIN md_values ON $type md.recno=md_values.recno WHERE md_values.md_value IS NULL";
			}
			if (DB_DRIVER == 'mssql') {
				if (substr_count($data,'>') == 0 && substr_count($data,'<') == 0) {
					//pokud je jenom =
					$data = str_replace('=', 'LIKE', $data);
				}
			}
		} else {
			$pos1 = strpos($data, "'", $pos0);
			$pos2 = strpos($data, "'", $pos1+1);
			$pom_like = substr($data, $pos1, ($pos2-$pos1)+1);
			$pom_b = substr($data, 0, $pos0);
			$pom_e = substr($data, $pos2+1);
			if ($pom_like == "'.%'") { // odstranit?
				$data = '(' . $pom_b . 'md_values.md_value IS NOT NULL)';
			} elseif ($pom_like != "'%'") {
				$data = $pom_b . setSqlLike('md_values.md_value',$pom_like) . $pom_e;
			}
			$data = str_replace("@$mapping", $type, $data);
		}
		if ($mapping == 'denom') {
            if (stripos($data, 'null') !== FALSE) {
                $data = str_replace(" AND md_values.md_value = null", '', $data);
                $data = str_replace('=', '!=', $data);
                //$data = $mapping;
            } else {
                switch (DB_DRIVER) {
                    case 'oracle':
                        $data = str_replace("md_values.md_value", 'TO_NUMBER(md_values.md_value)', $data);
                        break;
                    case 'postgre':
                        $maska = '999999999'; // max. velikost měřítka
                        $pos1 = strpos($data, "'");
                        $pos2 = strrpos($data, "'");
                        $mapping = trim(substr($data, $pos1+1, $pos2-($pos1+1)));
                        $data = str_replace("md_values.md_value", "TO_NUMBER(md_values.md_value, '$maska')", $data);
                        break;
                }
            }
		}
		if (substr_count($data,"'%'") > 0) {
			$data = str_replace("=", '', $data);
			$data = str_replace("'%'", ' IS NOT NULL', $data);
		} elseif (substr_count($data,"'% '") > 0) {
			$data = str_replace("=", '', $data);
			$data = str_replace("'% '", ' IS NOT NULL', $data);
		} elseif (substr_count($data,"'%%'") > 0) {
			$data = str_replace("=", '', $data);
			$data = str_replace("'%%'", ' IS NOT NULL', $data);
		}
		if ($data != '') {
            if (stripos($data, 'null') !== FALSE) {
                $data = str_replace(" AND md_values.md_value = null", '', $data);
                $data = str_replace('=', '!=', $data);
                //$data = $mapping;
            }
            if (stripos($data, '!=') !== FALSE) {
                $data = "SELECT DISTINCT md.recno, md.last_update_date, md.title FROM md WHERE (SELECT count(*) FROM md_values WHERE md.recno=md_values.recno AND $data)=0";
    			$data = str_replace("!=", '=', $data);
            }
			$rs = array();
			$rs['con'] = $con;
			$rs['sql'] = $data;
			$this->query_out_value[] = $rs;
		}
		return $data;
	}

	private function parserXmlPath($data, $con) {
		$ftext_path = trim(substr($data, 0, strpos($data, ' ', 0)));
		$pos1 = strpos($data, "'", 0);
		$pos2 = strpos($data, "'", $pos1+1);
		$ftext_value = substr($data, $pos1+1, ($pos2-$pos1)-1);
		$ftext_value = str_replace("-", '\-', $ftext_value);
		$ftext_value = str_replace("_", '\_', $ftext_value);
		if (DB_DRIVER == 'oracle') {
			$data = "(contains(pxml, '$ftext_value INPATH ($ftext_path)' )>0)";
		}
		if ($data != '') {
			$rs = array();
			$rs['con'] = $con;
			$rs['sql'] = $data;
			$this->query_out_value[] = $rs;
		}
		return $data;
	}

	private function parserMdField($data, $con) {
		$rs = array();
		$rs['con'] = $con;
        $dataExplode = explode(' ', $data);
        $dataField = $data;
        if (count($dataExplode) > 1) {
            $dataField = $dataExplode[0];
        }
		$field = ltrim(substr($data, 0, strrpos($dataField, '_') + 1));
		//echo "FIELD=$field<br>";
		switch ($field) {
			case '_LANGUAGE_':
				$rs['sql'] = $this->parserMdFieldLanguage($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			case '_LANG_':
				$rs['sql'] = $this->parserMdFieldLang($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			case '_BBOX_':
				$rs['sql'] = $this->parserMdFieldBbox($data);
				if ($rs['sql'] != '') {
					$this->query_out_md[] = $rs;
				}
				break;
			case '_BBSPAN_':
				$rs['sql'] = $this->parserMdFieldBbspan($data);
				if ($rs['sql'] != '') {
					$this->query_out_md[] = $rs;
				}
				break;
			case '_VALID_':
				$rs['sql'] = $this->parserMdFieldValid($data);
				if ($rs['sql'] != '') {
					$this->query_out_md[] = $rs;
				}
				break;
			case '_PRIM_':
				$rs['sql'] = $this->parserMdFieldPrim($data);
				if ($rs['sql'] != '') {
					$this->query_out_md[] = $rs;
				}
				break;
			case '_MDS_':
				$rs['sql'] = $this->parserMdFieldMds($data);
				if ($rs['sql'] != '') {
					$this->query_out_md[] = $rs;
				}
				break;
			case '_UUID_':
				$rs['sql'] = $this->parserMdFieldUuid($data);
				if ($rs['sql'] != '') {
					$this->query_out_md[] = $rs;
				}
				break;
			case '_MAYEDIT_':
				$rs['sql'] = $this->parserMdFieldMayedit($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			case '_GROUPS_':
				$rs['sql'] = $this->parserMdFieldGroups($data);
				if ($rs['sql'] != '') {
					$this->query_out_md[] = $rs;
				}
				break;
			case '_CREATE_USER_':
				$rs['sql'] = $this->parserMdFieldCreateUser($data);
				if ($rs['sql'] != '') {
					$this->query_out_md[] = $rs;
				}
				break;
			case '_DATEB_':
				$rs['sql'] = $this->parserMdFieldDateb($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			case '_DATEE_':
				$rs['sql'] = $this->parserMdFieldDatee($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			case '_DATESTAMP_':
				$rs['sql'] = $this->parserMdFieldDatestamp($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			case '_RDATE_':
				$rs['sql'] = $this->parserMdFieldRdate($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			case '_CDATE_':
				$rs['sql'] = $this->parserMdFieldCdate($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			case '_PDATE_':
				$rs['sql'] = $this->parserMdFieldPdate($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			case '_SERVER_':
				$rs['sql'] = $this->parserMdFieldServer($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			case '_DATA_TYPE_':
				$rs['sql'] = $this->parserMdFieldDataType($data);
				if ($rs['sql'] != '') {
					$this->query_out_md[] = $rs;
				}
				break;
			case '_DUP_':
				$rs['sql'] = $this->parserMdFieldDup($data);
				if ($rs['sql'] != '') {
					$this->query_out_value[] = $rs;
				}
				break;
			default:
				$this->setQueryError($data);
				$rs['sql'] = '';
				break;
		}
		return $rs['sql'];
	}

	private function parserMdFieldLanguage($data) {
		$x = 0;
		$pos0 = 0;
		$pos0 = strpos($data, '_LANGUAGE_', $pos0);
		$pos1 = strpos($data, "'", $pos0);
		$pos2 = strpos($data, "'", $pos1+2);
		$pom_l = substr($data, $pos1+1, $pos2-($pos1+1));
		$pom_b = substr($data, 0, $pos1+1);
		$pom_e = substr($data, $pos2);
		$data = $pom_b . "%$pom_l%" . $pom_e;
		$x++;
		$pos0 = $pos0+12;
		$data = str_replace('_LANGUAGE_ =', 'md.lang LIKE ', $data);
		$this->sql_language_ex = $data;
		return $data;
	}
	
	private function parserMdFieldLang($data) {
		$data = str_replace('_LANG_', 'md_values.lang', $data);
		$sql_lang = "($data OR md_values.lang='xxx') AND";
		return $data;
	}
	
	private function parserMdFieldBbox($data) {
		$inside = 0; // Zatím jen uvnitř
		
		$pos0 = strpos($data, '_BBOX_');
		$pos1 = strpos($data, "'", $pos0);
		$pos2 = strpos($data, "'", $pos1+2);
		if ($pos0 === FALSE || $pos1 === FALSE || $pos2 === FALSE) {
			$box = array();
		} else {
			$pom_box = substr($data, $pos1+1, $pos2-($pos1+1));
			$box = explode(' ',$pom_box);
		}
		if (count($box) > 3) {
			$x1 = $box[0];
			$y1 = $box[1];
			$x2 = $box[2];
			$y2 = $box[3];
			$this->bbox = array($x1, $y1, $x2, $y2); // pro dalsi dotazy
			if (isset($box[4])) {
				$inside = $box[4];
			};
			// podpora prostorovych dotazu
			switch (SPATIALDB) {
				case "postgis":
					// ogc:Within
					if ($inside == 1) {
						$pom_bbox = " md.the_geom @ GeomFromText('POLYGON(($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1))',-1)";
					}
					// ogc:Within - v opacnem poradi
					else if ($inside == 11) {
						$pom_bbox = " GeomFromText('POLYGON(($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1))',-1) @ md.the_geom";
					}
					// ogc:Intersects
					else {
						$pom_bbox = " md.the_geom && GeomFromText('POLYGON(($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1))',-1) AND Intersects(GeomFromText('POLYGON(($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1))',-1),md.the_geom)";
					}
					break;
				case "postgis2":
					// ogc:Within
					if ($inside == 1) {
						$pom_bbox = " md.the_geom @ ST_GeomFromText('MULTIPOLYGON((($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1)))',0)";
					}
					// ogc:Within - v opacnem poradi
					else if ($inside == 11) {
						$pom_bbox = " ST_GeomFromText('MULTIPOLYGON((($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1)))',0) @ md.the_geom";
					}
					// ogc:Intersects
					else {
						$pom_bbox = " md.the_geom && ST_GeomFromText('MULTIPOLYGON((($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1)))',0) AND ST_Intersects(ST_GeomFromText('MULTIPOLYGON((($x1 $y1,$x1 $y2,$x2 $y2,$x2 $y1,$x1 $y1)))',0),md.the_geom)";
					}
					break;
				// jen se sloupcu tabulky MD
				default: 
					// ogc:Within
					if ($inside == 1) {
						$pom_bbox = " $x1 <= x1 AND $x2 >= x2 AND $y1 <= y1 AND $y2 >= y2";
					}
					// ogc:Within - v opacnem poradi
					else if ($inside == 11) {
						$pom_bbox = " $x1 >= x1 AND $x2 <= x2 AND $y1 >= y1 AND $y2 <= y2";
					}
					// ogc:Intersects
					else {
						$pom_bbox = " $x2 >= x1 AND $x1 <= x2 AND $y2 >= y1 AND $y1 <= y2";
					}
					break;
			}
		} else {
			$this->setQueryError($data);
			return $data;
		}
		$pom_b = substr($data, 0, $pos0);
		$pom_e = substr($data, $pos2+1);
		$data = $pom_b . $pom_bbox . $pom_e;
		return $data;
	}
	
	private function parserMdFieldBbspan($data) {
		$rs = "";
		if($this->bbox) {
			$dx = $this->bbox[2] - $this->bbox[0];
			$bbspan = explode("=", $data);
			$bbspan = explode(",", trim(str_replace("'", "", $bbspan[1])));
			// kdyby byl nahodou vyrez=0
			if($dx > 0){
				$rs = "(x2-x1)/$dx > $bbspan[0] AND (x2-x1)/$dx < $bbspan[1]";
			}
		}
		return $rs;
	}
	
	private function parserMdFieldUuid($data) {
		return str_replace('_UUID_', 'md.uuid', $data);;
	}
	
	private function parserMdFieldValid($data) {
		return str_replace('_VALID_', 'md.valid', $data);;
	}
	
	private function parserMdFieldPrim($data) {
		return $in = str_replace('_PRIM_', 'md.prim', $data);;
	}
	
	private function parserMdFieldMds($data) {
		$data = str_replace('_MDS_ = 0', '_MDS_ IN(0,10)', $data);
		$data = str_replace('_MDS_', 'md.md_standard', $data);
		return $data;
	}
	
	private function parserIndividualNameContact($data) {
	    $pom = explode("'", $data);
        if (is_array($pom) && count($pom) > 1) {
            $iname = explode(':', trim($pom[1]));
        } else {
            return '';
        }
        if (is_array($iname) && count($iname) == 2) {
            $individualName = trim($iname[0]);
            $RoleCode = trim($iname[1]);
        } else {
            return '';
        }
    	if (DB_DRIVER == 'oracle') {
            $rs = "
                SELECT DISTINCT md.recno, md.last_update_date, md.title
                FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,27)=SUBSTR(m.md_path, 1,27) AND md_values.recno=m.recno)
                WHERE md_values.md_id=186 AND m.md_id=1047 AND md_values.md_value='$individualName' AND m.md_value='$RoleCode'
                #WHEREMD#
                UNION
                SELECT DISTINCT md.recno, md.last_update_date, md.title
                FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,32)=SUBSTR(m.md_path, 1,32) AND md_values.recno=m.recno)
                WHERE md_values.md_id=5028 AND m.md_id=5038 AND md_values.md_value='$individualName' AND m.md_value='$RoleCode'
            ";
            
        } else {
            $rs = "
                SELECT DISTINCT md.recno, md.last_update_date, md.title
                FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,27)=substring(m.md_path, 1,27) AND md_values.recno=m.recno)
                WHERE md_values.md_id=186 AND m.md_id=1047 AND md_values.md_value='$individualName' AND m.md_value='$RoleCode'
                #WHEREMD#
                UNION
                SELECT DISTINCT md.recno, md.last_update_date, md.title
                FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,32)=substring(m.md_path, 1,32) AND md_values.recno=m.recno)
                WHERE md_values.md_id=5028 AND m.md_id=5038 AND md_values.md_value='$individualName' AND m.md_value='$RoleCode'
            ";
        }
		return $rs;
	}
    
	private function parserMdIndividualNameContact($data) {
	    $pom = explode("'", $data);
        if (is_array($pom) && count($pom) > 1) {
            $iname = explode(':', trim($pom[1]));
        } else {
            return '';
        }
        if (is_array($iname) && count($iname) == 2) {
            $individualName = trim($iname[0]);
            $RoleCode = trim($iname[1]);
        } else {
            return '';
        }
		if (DB_DRIVER == 'oracle') {
            $rs = "
                SELECT DISTINCT md.recno, md.last_update_date, md.title
                FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,17)=SUBSTR(m.md_path, 1,17) AND md_values.recno=m.recno)
                WHERE md_values.md_id=152 AND m.md_id=992 AND md_values.md_value='$individualName' AND m.md_value='$RoleCode'
                #WHEREMD#
            ";
        } else {
            $rs = "
                SELECT DISTINCT md.recno, md.last_update_date, md.title
                FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,17)=substring(m.md_path, 1,17) AND md_values.recno=m.recno)
                WHERE md_values.md_id=152 AND m.md_id=992 AND md_values.md_value='$individualName' AND m.md_value='$RoleCode'
                #WHEREMD#
            ";
        }
		return $rs;
	}
    
	private function parserMdFieldMayedit($data) {
		$this->setMayEdit(1);
		// TODO: upravit indexy skupin
		$group = array();
		$group = getMsGroups('get_groups');
		$group = implode("','", array_keys($group));
		$group = "'" . $group . "'";
		$data = "(create_user='$this->user' OR edit_group IN($group))";
		return $data;
	}
	
	private function parserMdFieldGroups($data) {
		if ($data == "_GROUPS_ = '_mine'") {
			$group = array();
			$group = getMsGroups('get_groups');
			$group = implode("','", array_keys($group));
			$group = "'" . $group . "'";
			$data = "(create_user='$this->user' OR view_group IN($group) OR edit_group IN($group))";
		} else {
			$group = str_replace('_GROUPS_ = ', '', $data);
			$group = str_replace(",", "','", $group);
			if ($this->user_admin === TRUE) {
				$this->setMayEdit(1);
				$data = "(view_group IN($group) OR edit_group IN($group))";
			} else {
				//$in = "(create_user='$this->user' OR view_group IN($group) OR edit_group IN($group))";
				$rs = "
					(
					SELECT DISTINCT md.recno, md.last_update_date, md.title FROM md JOIN md_values ON md.recno=md_values.recno 
					WHERE" . $this->getRight(FALSE) . " 
					)
					INTERSECT
					(
					SELECT DISTINCT md.recno, md.last_update_date, md.title
					FROM md JOIN md_values ON md.recno=md_values.recno 
					WHERE view_group IN($group) OR edit_group IN($group)
					)
				";
			}
		}
		return $rs;
	}
	
	private function parserMdFieldDateb($data) {
		$x = 0;
		$pos0 = 0;
		$pos0 = strpos($data, '_DATEB_', $pos0);
		$pos1 = strpos($data, "'", $pos0);
		$pos2 = strpos($data, "'", $pos1+2);
		$pom_date = substr($data, $pos1+1, $pos2-($pos1+1));
		$pom_b = substr($data, 0, $pos1+1);
		$pom_e = substr($data, $pos2);
		$date = timeWindow($pom_date,'','');
		$data = $pom_b . $date[0] . $pom_e;
		$x++;
		$pos0 = $pos0+7;
		$data = str_replace('_DATEB_', 'md.range_end', $data);
		$sql_date_b = "($data)";
		return $data;
	}
	
	private function parserMdFieldDatee($data) {
			$x = 0;
			$pos0 = 0;
			$pos0 = strpos($data, '_DATEE_', $pos0);
			$pos1 = strpos($data, "'", $pos0);
			$pos2 = strpos($data, "'", $pos1+2);
			$pom_date = substr($data, $pos1+1, $pos2-($pos1+1));
			$pom_b = substr($data, 0, $pos1+1);
			$pom_e = substr($data, $pos2);
			$date = timeWindow($pom_date,'','');
			$data = $pom_b . $date[1] . $pom_e;
			$x++;
			$pos0 = $pos0+7;
			$data = str_replace('_DATEE_', 'md.range_begin', $data);
			$sql_date_e = "($data)";
		return $data;
	}
	
	private function parserMdFieldDatestamp($data) {
		if (DB_DRIVER == 'oracle') {
			$tmp = explode(' ', $data);
			$sign = $tmp[1];
			$datum = $tmp[2];
			$data = 'md.last_update_date ' . $sign . ' TO_DATE(' . $datum . ",'YYYY-MM-DD')";
		} elseif (DB_DRIVER == 'mssql2005') {
            $data = str_replace('-', '', $data);
			$data = str_replace('_DATESTAMP_', 'md.last_update_date', $data);
		}
		else {
			$data = str_replace('_DATESTAMP_', 'md.last_update_date', $data);
		}
		$sql_datestamp = "($data) AND";
		return $data;
	}
	
	private function parserMdFieldRdate($data) {
		$tmp = explode(' ', $data);
		$sign = $tmp[1];
		$datum = $tmp[2];
		if (DB_DRIVER == 'oracle') {
			$rs = "
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,34) = SUBSTR(m.md_path, 1,34) AND md_values.recno = m.recno)
				WHERE md_values.md_id=5077 AND m.md_id=5090 AND md_values.md_value $sign $datum AND m.md_value = 'revision'
				UNION
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,26) = SUBSTR(m.md_path, 1,26) AND md_values.recno = m.recno)
				WHERE md_values.md_id=14 AND m.md_id=974 AND md_values.md_value $sign $datum AND m.md_value = 'revision'
			";
		} else {
			$rs = "
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,34) = substring(m.md_path, 1,34) AND md_values.recno = m.recno)
				WHERE md_values.md_id=5077 AND m.md_id=5090 AND md_values.md_value $sign $datum AND m.md_value = 'revision'
				UNION
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,26) = substring(m.md_path, 1,26) AND md_values.recno = m.recno)
				WHERE md_values.md_id=14 AND m.md_id=974 AND md_values.md_value $sign $datum AND m.md_value = 'revision'
			";
		}
		return $rs;
	}
	
	private function parserMdFieldCdate($data) {
		$tmp = explode(' ', $data);
		$sign = $tmp[1];
		$datum = $tmp[2];
		if (DB_DRIVER == 'oracle') {
			$rs = "
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,34) = SUBSTR(m.md_path, 1,34) AND md_values.recno = m.recno)
				WHERE md_values.md_id=5077 AND m.md_id=5090 AND md_values.md_value $sign $datum AND m.md_value = 'creation'
				UNION
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,26) = SUBSTR(m.md_path, 1,26) AND md_values.recno = m.recno)
				WHERE md_values.md_id=14 AND m.md_id=974 AND md_values.md_value $sign $datum AND m.md_value = 'creation'
			";
		} else {
			$rs = "
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,34) = substring(m.md_path, 1,34) AND md_values.recno = m.recno)
				WHERE md_values.md_id=5077 AND m.md_id=5090 AND md_values.md_value $sign $datum AND m.md_value = 'creation'
				UNION
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,26) = substring(m.md_path, 1,26) AND md_values.recno = m.recno)
				WHERE md_values.md_id=14 AND m.md_id=974 AND md_values.md_value $sign $datum AND m.md_value = 'creation'
			";
		}
		return $rs;
	}
	
	private function parserMdFieldPdate($data) {
		$tmp = explode(' ', $data);
		$sign = $tmp[1];
		$datum = $tmp[2];
		if (DB_DRIVER == 'oracle') {
			$rs = "
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,34) = SUBSTR(m.md_path, 1,34) AND md_values.recno = m.recno)
				WHERE md_values.md_id=5077 AND m.md_id=5090 AND md_values.md_value $sign $datum AND m.md_value = 'publication'
				UNION
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,26) = SUBSTR(m.md_path, 1,26) AND md_values.recno = m.recno)
				WHERE md_values.md_id=14 AND m.md_id=974 AND md_values.md_value $sign $datum AND m.md_value = 'publication'
			";
		} else {
			$rs = "
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,34) = substring(m.md_path, 1,34) AND md_values.recno = m.recno)
				WHERE md_values.md_id=5077 AND m.md_id=5090 AND md_values.md_value $sign $datum AND m.md_value = 'publication'
				UNION
				SELECT md.recno, md.last_update_date, md.title
				FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,26) = substring(m.md_path, 1,26) AND md_values.recno = m.recno)
				WHERE md_values.md_id=14 AND m.md_id=974 AND md_values.md_value $sign $datum AND m.md_value = 'publication'
			";
		}
		return $rs;
	}
	
	private function parserMdFieldCreateUser($data) {
		$this->setMayRecords($data);
		return str_replace('_CREATE_USER_', 'md.create_user', $data);
	}
	
	private function parserMdFieldServer($data) {
		return str_replace('_SERVER_', 'md.server_name', $data);
	}
	
	private function parserMdFieldDataType($data) {
		if ($data == "_DATA_TYPE_ = '0'") {
			$this->only_private = TRUE;
			$data = 'md.data_type=0';
			$this->setSqlMd();
		} elseif ($data == "_DATA_TYPE_ = '1'") {
			$data = 'md.data_type=1';
			$this->setSqlMd();
		} elseif ($data == "_DATA_TYPE_ = '-1'") {
			$data = 'md.data_type=-1';
			$this->setSqlMd();
		} else {
			$data = str_replace('_DATA_TYPE_', 'md.data_type', $data);
			$data = str_replace("'", '', $data);
			$this->setSqlMd();
		}
		return $data;
	}
	
	private function parserMdFieldDup($data) {
		$data = 'SELECT * FROM md_values WHERE md_id IN(185,5079) AND md_value IN(
			SELECT md_value FROM md_values WHERE md_id IN(185,5079) GROUP BY md_value HAVING COUNT(recno) > 1) 
			ORDER BY md_id, md_value, recno';
		return $data;
	}
	
	private function parserThesaurusKeyword($data) {
        $pom = explode("'", $data);
        if (is_array($pom) && count($pom) > 1) {
            $key_the = explode('|', trim($pom[1]));
        } else {
            return '';
        }
        if (is_array($key_the) && count($key_the) == 2) {
            $keyword = "'" . trim($key_the[1]) . "'";
            $thesaurus = "'" . trim($key_the[0]) . "'";
        } else {
            return '';
        }
		if (stripos($pom[0], 'LIKE') === FALSE) {
            $keyword = '= ' . $keyword;
            $thesaurus = '= ' . $thesaurus;
        } elseif (DB_DRIVER == 'postgre') {
            $keyword = ' ILIKE ' . $keyword;
            $thesaurus = ' ILIKE ' . $thesaurus;
        } else {
            $keyword = ' LIKE ' . $keyword;
            $thesaurus = ' LIKE ' . $thesaurus;
        }
		if (DB_DRIVER == 'oracle') {
            $keyword = mb_convert_case($keyword, MB_CASE_UPPER, MICKA_CHARSET);
            $thesaurus = mb_convert_case($thesaurus, MB_CASE_UPPER, MICKA_CHARSET);
			$rs = "
                SELECT DISTINCT md.recno, md.last_update_date, md.title
                FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,24)=SUBSTR(m.md_path, 1,24) AND md_values.recno=m.recno)
                WHERE md_values.md_id=88 AND m.md_id=1755 AND NLS_UPPER(md_values.md_value) $keyword AND NLS_UPPER(m.md_value) $thesaurus
                #WHEREMD#
                UNION
                SELECT DISTINCT md.recno, md.last_update_date, md.title
                FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(SUBSTR(md_values.md_path, 1,24)=SUBSTR(m.md_path, 1,24) AND md_values.recno=m.recno)
                WHERE md_values.md_id=4920 AND m.md_id=4925 AND NLS_UPPER(md_values.md_value) $keyword AND NLS_UPPER(m.md_value) $thesaurus
			";
		} else {
			$rs = "
                SELECT DISTINCT md.recno, md.last_update_date, md.title
                FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,24) = substring(m.md_path, 1,24) AND md_values.recno = m.recno)
                WHERE md_values.md_id=88 AND m.md_id=1755 AND md_values.md_value $keyword AND m.md_value $thesaurus
                #WHEREMD#
                UNION
                SELECT DISTINCT md.recno, md.last_update_date, md.title
                FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON(substring(md_values.md_path, 1,24) = substring(m.md_path, 1,26) AND md_values.recno = m.recno)
                WHERE md_values.md_id=4920 AND m.md_id=4925 AND md_values.md_value $keyword AND m.md_value $thesaurus
			";
		}
		return $rs;
	}
    
	private function parserData($data, $con) {
		$rs = '';
		if ($data{0} == '%') {
			$rs = $this->parserSearchText($data, $con);
		} elseif ($data{0} == '@') {
			$rs = $this->parserMdMapping($data, $con);
		} elseif ($data{0} == '/') {
			$rs = $this->parserXmlPath($data, $con);
		} elseif ($data{0} == '_') {
			$rs = $this->parserMdField($data, $con);
		}
		return $rs;
	}

	private function walkQueryInSimple() {
		$con = 'AND';
		foreach ($this->query_in as $field) {
			if ($field{0} == '%' || $field{0} == '@' || $field{0} == '_' || $field{0} == '/') {
				$this->parserData($field, $con);
			} else {
				$con = strtoupper($field);
				if ($con == 'AND' || $con == 'OR' || $con == 'NOT') {
					// OK TODO: NOT
				} else {
					$this->setQueryError($con);
				}
			}
		}
		//my_print_r($this->query_out_md);
		//my_print_r($this->query_out_value);
		if (count($this->query_out_md) > 0) {
			foreach ($this->query_out_md as $key => $value) {
				if ($key == 0) {
					$this->sql_md .= $value['sql'];
				} else {
					$this->sql_md .= ' ' . $value['con'] . ' (' . $value['sql'] . ')';
				}
			}
			$this->sql_md .= ' AND ';
		}
		//echo 'sql_md=' . $this->sql_md . '<br>';
        // DZTODO: ???
        switch (DB_DRIVER) {
            case 'postgre':
                $sql_smd = 'SELECT recno, last_update_date, COALESCE((xpath(\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/text()\', pxml, ARRAY\[ARRAY\[\'gmd\', \'http://www.isotc211.org/2005/gmd\'\]\]))\[1\]::text, title) AS title FROM md';
                break;
            case 'oracle':
                $sql_smd = 'SELECT recno, last_update_date,  COALESCE(extractValue(pxml,\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/node()\',\'xmlns:gmd=http://www.isotc211.org/2005/gmd\'), title) title FROM md';
                break;
            case 'mssql2005':
                $sql_smd = 'SELECT recno, last_update_date, title FROM md';
                break;
            default:
                $sql_smd = '';
                break;
        }
		$sql_s = 'SELECT DISTINCT md.recno, md.last_update_date, md.title FROM md JOIN md_values ON md.recno=md_values.recno';
		$this->sql_final[0] = '';
		if (count($this->query_out_value) > 0) {
			foreach ($this->query_out_value as $key => $value) {
				if ($key > 0) {
					if ($value['con'] == 'AND') {
						$this->sql_final[0] .= ' INTERSECT ';
					} elseif ($value['con'] == 'OR') {
						$this->sql_final[0] .= ' UNION ';
					} 
				}
				$this->sql_final[0] .= '(';
				if (strpos($value['sql'], 'SELECT') === FALSE) {
					if (strpos($value['sql'], 'md_values.') === FALSE) {
						$this->sql_final[0] .=  $sql_smd;
					} else {
						$this->sql_final[0] .= $sql_s;
					}
					$this->sql_final[0] .= ' WHERE ' . $this->sql_md . $value['sql'];
				} else {
                    $this->sql_final[0] .= $value['sql'];
                    if ($this->sql_md != '') {
                        $this->sql_final[0] .= ' AND ' . substr($this->sql_md, 0, -4);
                    }
				}
				$this->sql_final[0] .= ')';
			}
		} else {
			$this->sql_final[0] .= $sql_smd . ' WHERE ' . substr($this->sql_md, 0, -4);
		}
		$repMd = $this->sql_md != '' ? ' AND ' . substr($this->sql_md, 0, -4) : '';
		$this->sql_final = str_replace('#WHEREMD#', $repMd, $this->sql_final);
		//my_print_r($this->sql_final);
		//exit('exitus');
	}
	
	private function walkSqlArray($in) {
		foreach ($in as $field) {
			if (is_array($field)) {
				$bar = FALSE;
				$pom = is_array($field[0]) === TRUE || $field[0] == '' ? '' : strtoupper($field[0]);
				if ($pom == 'AND' || $pom == 'OR' || $pom == 'NOT') {
				}
				else {
					$this->sql_final[0] .= '(';
					$bar = TRUE;
				}
				$this->walkSqlArray($field);
				if ($bar) {
					$this->sql_final[0] .= ')';
				}
			} else {
				if ($field{0} == '%' || $field{0} == '@' || $field{0} == '_' || $field{0} == '/') {
					//$sql_row = $this->pracParserData($field);
					$sql_row = $this->parserData($field, NULL);
                    $sql_row = str_replace('#WHEREMD#', '', $sql_row);
					$grpBy = '';
					$sql_s = 'SELECT DISTINCT md.recno, md.last_update_date, md.title FROM md JOIN md_values ON md.recno=md_values.recno';
					if (strpos($sql_row, 'md_values.') === FALSE) {
                        switch (DB_DRIVER) {
                            case 'postgre':
                                $sql_s = 'SELECT recno, last_update_date, COALESCE((xpath(\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/text()\', pxml, ARRAY\[ARRAY\[\'gmd\', \'http://www.isotc211.org/2005/gmd\'\]\]))\[1\]::text, title) AS title FROM md';
                                break;
                            case 'oracle':
                                $sql_s = 'SELECT recno, last_update_date,  COALESCE(extractValue(pxml,\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/node()\',\'xmlns:gmd=http://www.isotc211.org/2005/gmd\'), title) title FROM md';
                                break;
                            case 'mssql2005':
                                $sql_s = 'SELECT recno, last_update_date, title FROM md';
                                break;
                            default:
                                $sql_s = '';
                                break;
                        }
					}
					$sql_row = strpos($sql_row, 'SELECT') === FALSE ? $sql_s . ' WHERE ' . $this->sql_md . $sql_row . $grpBy : $sql_row;
					//echo "<p>$sql_row</p>";
					if ($sql_row != '') {
						$this->sql_final[0] .= '(';
						//$this->sql_final[0] .= $field;
						$this->sql_final[0] .= $sql_row;
						if (DB_DRIVER == 'postgre') {
							//$this->sql_final[0] .= ' GROUP BY md.recno)';
							//$this->sql_final[0] .= ')';
						}
						$this->sql_final[0] .= ')';
					} else {
						$this->sql_final[0] .= ')';
					}
				} elseif (strtoupper($field) == 'AND') {
					$this->sql_operation = '';
					$this->sql_final[0] .= " INTERSECT ";
				} elseif (strtoupper($field) == 'OR') {
					$this->sql_operation = '';
					$this->sql_final[0] .= " UNION ";
				} elseif (strtoupper($field) == 'NOT') {
					$this->sql_operation = 'NOT';
					//$this->sql_final[0] .= " $field ";
				} else {
					$this->setQueryError($field);
				}
			}
		}
	}
	
	private function getRight($end_and=TRUE) {
		$group = array();
		$right = '';
		if ($this->user != 'guest') {
			$group = getMsGroups('get_groups');
		}
		if (count($group) == 0) {
			$this->user = 'guest';
		} else {
			//$group = implode("','", $group);
			$group = implode("','", array_keys($group));
			$group = "'" . $group . "'";
		}
		if ($this->may_edit == 1 || $this->may_records === TRUE) {
			$right = '';
		} elseif ($this->user_admin === TRUE) {
			//$right = '(md.data_type>-2)';
			$right = '';
		} elseif ($this->user == 'guest') {
			$right = '(md.data_type>0)';
		} else {
			// TODO: ujasnit
            /*
			if ($this->only_public) {
				$right = "(edit_group IN($group) OR (view_group IN ($group) AND data_type>-1) OR data_type>0)";
			} elseif ($this->only_private) {
				$right = "(edit_group IN($group) OR (view_group IN ($group) AND data_type>-1))";
			} else {
				$right = "(edit_group IN($group) OR (view_group IN ($group) AND data_type>-1) OR data_type>0)";
			}
            */
			if ($this->only_public) {
				$right = "(create_user='" . $this->user . "' OR edit_group IN($group) OR view_group IN ($group)) AND data_type>0";
			} elseif ($this->only_private) {
				$right = "(create_user='" . $this->user . "' OR edit_group IN($group) OR view_group IN ($group)) AND data_type<1";
			} else {
				$right = "(create_user='" . $this->user . "' OR edit_group IN($group) OR view_group IN ($group) OR data_type>0)";
			}
		}
		$right = $end_and === TRUE && $right != '' ? $right . ' AND ' : $right;
		return $right;
	}

	function getSql($type, $user, $ofs=0, $orderBy='' ) {
		if (DB_DRIVER != 'postgre' && $orderBy[0] == 'bbox') {
            $this->sortBy = getSortBy(SORT_BY, $ret='array');
			$orderBy = '';
        }
		if ($this->bbox == NULL && is_array($orderBy) && $orderBy[0] == 'bbox') {
            $this->sortBy = getSortBy(SORT_BY, $ret='array');
			$orderBy = '';
		}
		if ($orderBy == '') {
			$orderBy = $this->sortBy;
		}
		$sql = '';
		if ($type == '' || $user == '') {
			setMickaLog('type or user is null', 'WARNING', "MdExport.getSql");
			return $sql;
		}
		$sortBy = $orderBy[0] . ' ' . $orderBy[1];
		//echo "SORT BY: $sortBy<br>";
		//$sortBy = 'recno';
		// řazení dle bbox
		$selectBbox = '';
		if (is_array($this->bbox) && count($this->bbox) == 4 && is_array($orderBy) && $orderBy[0] == 'bbox') {
			list($x1, $y1, $x2, $y2) = $this->bbox;
			$a = ($x2-$x1)*($y2-$y1);
			//$selectBbox = "abs(x2 - x1 - $x2 + $x1) + abs(x1 + x2 - $x1 - $x2)/2 + abs(y2 - y1 - $y2 + $y1) + abs(y1 + y2 - $y1 - $y2)/2";
			$selectBbox = "greatest((x2-x1)*(y2-y1),$a)/least(greatest((x2-x1)*(y2-y1),0.00000000000001),$a)";
			$selectBbox = ", " . $selectBbox . " AS bbox";
		}
		$sortBy_mdpath = '';
		if ($this->xml_from == 'cache') {
			//$sortBy = 'recno';
			//$sql_spol['md_select'] =  "SELECT	recno, uuid, xmldata";
			$sql_spol['md_select'] =  "SELECT recno, uuid, md_standard, lang, data_type, create_user, create_date, last_update_user, last_update_date, edit_group, view_group, valid, prim, xmldata AS pxml, server_name";
			$sql_spol['md_from'] = " FROM md WHERE (recno IN (SELECT recno FROM(";
			$sql_spol['md_order'] = "";
			$sql_spol['md_count'] =  "
				SELECT 	count(DISTINCT recno) AS Celkem
			";
		} elseif ($this->xml_from == 'data') {
			$sortBy = 'recno';
			$sortBy_mdpath = ', md_values.md_path';
			$sql_spol['md_select'] =  "
				SELECT md_values.recno, md_values.md_path, " . setNtext2Text('md_values.', 'md_value') . ", md_values.lang, md.md_standard
			";
			$sql_spol['md_from'] = "
				FROM (md JOIN md_values ON md.recno = md_values.recno) JOIN standard ON md.md_standard = standard.md_standard
				WHERE (md_values.recno IN (SELECT recno FROM(
			";
			$sql_spol['md_order'] = " ORDER BY md_values.recno, md_values.md_path";
			$sql_spol['md_count'] =  "
				SELECT 	count(DISTINCT md.recno) AS Celkem
			";
		} else {
			$sql_spol['md_select'] =  "
				SELECT
								md.recno,
								md.uuid,
								md.md_standard,
								md.edit_group,
								md.create_user,
								md.valid,
								md.data_type,
								" . setNtext2Text('md_values.', 'md_value') . ",
								md_values.md_id,
								md_values.lang,
								standard.md_standard_short_name,
								md_summary.md_summary
			";
			$sql_spol['md_from'] = "
				FROM (md_values JOIN (md JOIN md_summary ON md.md_standard = md_summary.md_standard)
					ON (md_values.md_id = md_summary.md_id) AND (md_values.recno = md.recno))
					JOIN standard ON md_summary.md_standard = standard.md_standard
				WHERE (md_summary.md_summary IS NOT NULL AND md_values.recno IN (SELECT recno FROM(
			";
			$sql_spol['md_order'] = " ORDER BY md_values.recno, md_values.md_path";
			$sql_spol['md_count'] =  "
				SELECT 	count(DISTINCT md.recno) AS Celkem
			";
		}
		$sql_spol['md_in_end'] =  ")";
		$sql_spol['md_where_end'] =  ")";
		if ($type == 'count') {
			/*
			$sql_final = str_replace('SELECT DISTINCT md.recno, md.last_update_date, md.title',
							'SELECT DISTINCT md.recno', 
							$this->sql_final[0]);
			*/
			if ($this->useOrderByXmlPath === TRUE) {
				switch (DB_DRIVER) {
					case 'postgre':
						$sql_final = str_replace(
							'SELECT DISTINCT md.recno, md.last_update_date, md.title',
							'SELECT DISTINCT md.recno, md.last_update_date, COALESCE((xpath(\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/text()\', pxml, ARRAY\[ARRAY\[\'gmd\', \'http://www.isotc211.org/2005/gmd\'\]\]))\[1\]::text, title) AS title',
							$this->sql_final[0]
						);
						break;
					case 'oracle':
						$sql_final = str_replace(
							'SELECT DISTINCT md.recno, md.last_update_date, md.title',
							'SELECT DISTINCT md.recno, md.last_update_date,  COALESCE(extractValue(pxml,\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/node()\',\'xmlns:gmd=http://www.isotc211.org/2005/gmd\'), title) title', 
							$this->sql_final[0]
						);
						break;
					default:
						$sql_final = $this->sql_final[0];
						break;
				}
			} else {
				$sql_final = $this->sql_final[0];
			}
			$sql = "SELECT 	count(DISTINCT recno) AS Celkem FROM md WHERE (recno IN (SELECT recno FROM("
						. $sql_final
						. ') jojo))';
		} elseif ($type == 'sample') {
			// odhad pro oracle
			$sql = "SELECT 	count(recno) * 10000 AS Celkem FROM md SAMPLE (0.01) WHERE (recno IN (SELECT recno FROM("
						. $this->sql_final[0]
						. ') jojo))';
		} elseif ($type == 'find') {
			// stránkování podle typu databáze
			switch (DB_DRIVER) {
				case 'postgre':
					$sql_final = $this->sql_final[0];
					if ($this->useOrderByXmlPath === TRUE) {
						$sql_final = str_replace(
							'SELECT DISTINCT md.recno, md.last_update_date, md.title',
							'SELECT DISTINCT md.recno, md.last_update_date, COALESCE((xpath(\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/text()\', pxml, ARRAY\[ARRAY\[\'gmd\', \'http://www.isotc211.org/2005/gmd\'\]\]))\[1\]::text, title) AS title',
							$sql_final
						);
                        $sql_spol['md_select'] .= ', COALESCE((xpath(\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/text()\', pxml, ARRAY\[ARRAY\[\'gmd\', \'http://www.isotc211.org/2005/gmd\'\]\]))\[1\]::text, title) AS title';
					}
					$sql_final = str_replace('AS title', 'AS title ' . $selectBbox, $sql_final);
					$select_limit = " LIMIT " . $this->maxRecords . " OFFSET $ofs";
					$sql =  $sql_spol['md_select'] . $selectBbox
								. $sql_spol['md_from']
								. $sql_final
								. ") jojo ORDER BY $sortBy $select_limit)) ORDER BY $sortBy $sortBy_mdpath";
					break;
				case 'oracle':
					$from = $ofs + 1;
					$to   = $ofs + $this->maxRecords;
					$sql_ora = $this->sql_final[0];
					if ($this->useOrderByXmlPath === TRUE) {
						$sql_ora = str_replace(
                            'SELECT DISTINCT md.recno, md.last_update_date, md.title',
							'SELECT DISTINCT md.recno, md.last_update_date,  COALESCE(extractValue(pxml,\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/node()\',\'xmlns:gmd=http://www.isotc211.org/2005/gmd\'), title) title', 
							$this->sql_final[0]
                        );
                        $sql_spol['md_select'] .= ', COALESCE(extractValue(pxml,\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/node()\',\'xmlns:gmd=http://www.isotc211.org/2005/gmd\'), title) title';
                    }
					$sql =  $sql_spol['md_select']
								. $sql_spol['md_from']
								. 'SELECT t.*, ROWNUM AS rnum FROM (' . $sql_ora . " ORDER BY $sortBy" . ') t) WHERE rnum' . " BETWEEN $from AND $to"
								. '))'
								. " ORDER BY $sortBy $sortBy_mdpath";
					break;
				case 'mssql2005':
					$from = $ofs + 1;
					$to   = $ofs + $this->maxRecords;
					$sql_ora = $this->sql_final[0];
					$sql =  $sql_spol['md_select']
								. $sql_spol['md_from']
								. "SELECT ROW_NUMBER() OVER (ORDER BY $sortBy) AS rnum, recno, last_update_date, title FROM (" . $sql_ora . ') AS jo) AS jojo WHERE rnum' . " BETWEEN $from AND $to"
								. '))'
								. " ORDER BY $sortBy $sortBy_mdpath";
					break;
			}
		}
		setMickaLog(array($sql), 'DEBUG', "MdExport.getSql.return");
		return $sql;
	}

	private function setNumberOfRecords($startPosition, $founds) {
		$rs['Matched'] = 0;
		$rs['Return'] = 0;
		$rs['Next'] = 0;
		if ($founds > 0) {
			$rs['Matched'] = $founds;
		}
		if ($founds >= $this->maxRecords) {
			$rs['Return'] = $this->maxRecords;
			if (($startPosition -1) + $this->maxRecords >= $founds) {
				$rs['Return'] = $founds - ($startPosition - 1);
			}
			else {
				$rs['Next'] = $startPosition + $this->maxRecords;
			}
		}
		else {
			$rs['Return'] = $founds;
		}
		setMickaLog($rs, 'FUNCTION', "MdExport.setNumberOfRecords.return");
		return $rs;
	}

	private function setData2Micka($in) {
		$rs = array();
		if (!is_array($in)) {
			return $rs;
		}
		if (count($in) == 0) {
			return $rs;
		}
		$supr = canAction('*'); // root - superuživatel, spravce projektu
		foreach ($in as $recno => $data) {
			$abstract = '';
			$title = '';
			$wms47 = '';
			$wms49 = FALSE;
			$zaznam['recno'] = $recno;
			$zaznam['uuid'] = $data[0]['UUID'];
			$zaznam['edit'] = 0;
			$zaznam['title'] = '';
			$zaznam['abstract'] = '';
			$zaznam['category'] = '';
			$zaznam['zs'] = '';
			$zaznam['ident_code'] = '';
			$zaznam['ident_codesp'] = '';
			$zaznam['wms'] = '';
			$zaznam['servicetype'] = '';
			$zaznam['valid'] = $data[0]['VALID'];;
			//$zaznam['standard'] = $data[0]['MD_STANDARD'];
			$zaznam['standard'] = $data[0]['MD_STANDARD_SHORT_NAME'];
			$zaznam['hierarchy'] = '';
			$zaznam['name'] = $data[0]['CREATE_USER'];
			if (getMsGroups('is_set', $data[0]['EDIT_GROUP']) || $supr) {
				$zaznam['edit'] = 1;
			}
			if ($zaznam['name'] == $this->user && $this->user != 'guest') {
				$zaznam['edit'] = 1;
			}
			$zaznam['data_type'] = $data[0]['DATA_TYPE'];
			foreach ($data as $idx => $row) {
				$row['MD_SUMMARY'] = trim($row['MD_SUMMARY']);
				if ($row['MD_SUMMARY'] == 'title') {
					if ($row['LANG'] == 'xxx' || $row['LANG'] == MICKA_LANG) {
						$zaznam['title'] = $row['MD_VALUE'];
					}
					else {
						$title = $row['MD_VALUE'];
					}
				}
				elseif ($row['MD_SUMMARY'] == 'abstract') {
					if ($row['LANG'] == 'xxx' || $row['LANG'] == MICKA_LANG) {
						$zaznam['abstract'] = $row['MD_VALUE'];
					}
					else {
						$abstract = $row['MD_VALUE'];
					}
				}
				elseif ($row['MD_SUMMARY'] == 'contact') {
					$zaznam['zs'] = $row['MD_VALUE'];
				}
				elseif ($row['MD_SUMMARY'] == 'topic') {
					$zaznam['category'] = $row['MD_VALUE'];
				}
				elseif ($row['MD_SUMMARY'] == 'resourceid') {
					$zaznam['ident_code'] = $row['MD_VALUE'];
					$zaznam['ident_codesp'] = $row['MD_VALUE'];

				}
				elseif ($row['MD_SUMMARY'] == 'linkage') {
					$zaznam['wms'] = getWmsList($row['MD_VALUE']);
					$wms47 = $row['MD_VALUE'];
				}
				elseif ($row['MD_SUMMARY'] == 'protocol') {
					if (getWmsList($row['MD_VALUE']) != '') {
							$wms49 = true;
					}
				}
				elseif ($row['MD_SUMMARY'] == 'service') {
					$zaznam['servicetype'] = $row['MD_VALUE'];
				}
				elseif ($row['MD_SUMMARY'] == 'type') {
					$zaznam['hierarchy'] = $row['MD_VALUE'];
				}
			}
			if ($zaznam['abstract'] == '' && $abstract != '') {
				$zaznam['abstract'] = $abstract;
			}
			if ($zaznam['title'] == '' && $title != '') {
				$zaznam['title'] = $title;
			}
			if ($wms47 != '' && $wms49 == TRUE) {
				$zaznam['wms'] = $wms47;
			}
			array_push($rs, $zaznam);
		}
		return $rs;
	}

	private function setSqlEmptyIn() {
		if ($this->sql_or == '') {
			if (DB_DRIVER == 'postgre' && $this->useOrderByXmlPath === TRUE) {
				$this->sql_final[0] = 'SELECT recno, last_update_date, COALESCE((xpath(\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/text()\', pxml, ARRAY\[ARRAY\[\'gmd\', \'http://www.isotc211.org/2005/gmd\'\]\]))\[1\]::text, title) AS title FROM md';
			} elseif (DB_DRIVER == 'oracle' && $this->useOrderByXmlPath === TRUE) {
				//$this->sql_final[0] = 'SELECT recno, last_update_date, title FROM md';
				$this->sql_final[0] = 'SELECT md.recno, md.last_update_date,  COALESCE(extractValue(pxml,\'//gmd:identificationInfo/*/gmd:citation/*/gmd:title//gmd:LocalisedCharacterString[contains(@locale, "' . MICKA_LANG . '")]/node()\',\'xmlns:gmd=http://www.isotc211.org/2005/gmd\'), title) title FROM md';
			} else {
				$this->sql_final[0] = 'SELECT recno, last_update_date, title FROM md';
			}
			if ($this->sql_md != '') {
				$this->sql_final[0] .= ' WHERE ' . substr($this->sql_md, 0, -4);
			}
		} else {
			$this->sql_final[0] = 'SELECT DISTINCT md.recno, md.last_update_date, md.title FROM md JOIN md_values ON md.recno=md_values.recno'; 
			if ($this->sql_md == '') {
				$this->sql_final[0] .= ' WHERE ' . substr($this->sql_or, 0, -4);
			} else {
				$this->sql_final[0] .= ' WHERE ' . $this->sql_or;
			}
			if ($this->sql_md != '') {
				$this->sql_final[0] .= substr($this->sql_md, 0, -4);
			}
		}
	}
	
	private function setQuery($in) {
		setMickaLog($in, 'FUNCTION', "MdExport.setQuery.start");
		$rs = array();
		//$wms_service = Array([0] => Array([0] => "@type = 'service'", [1] => 'AND', [2] => "@stype = 'WMS'"));
		$this->setSqlMd();
		if (is_array($in)) {
			if (count($in) == 0 && $this->sql_uuid == '') {
				$this->setSqlEmptyIn();
			}
			elseif (count($in) == 1 && is_array($in[0]) === TRUE &&  count($in[0]) == 0 && $this->sql_uuid == '') {
				$this->setSqlEmptyIn();
			}
			elseif (count($in) == 0 && $this->sql_uuid != '') {
				// zjednodušený dotaz dle uuid
				$this->sql_final[0] = 'SELECT recno, last_update_date, title FROM md WHERE uuid IN ' . $this->sql_uuid;
			}
			else {
				if ($this->isSimpleQuery() === TRUE) {
					$this->walkQueryInSimple();
				} else {
					$this->walkSqlArray($in);
				}
			}
			//$this->setSqlMd();
			//Debugger::dump($this->sql_md);
			$sql = $this->getSql('count', $this->user, 0, $this->sortBy);
			// pro odhad na Oracle
			//$sql = $this->getSql('sample', $this->user, 0, 0, $this->maxRecords, $this->sortBy);
			if ($this->query_status === FALSE) {
				return -1;
			}
		}
		else {
			setMickaLog('Bad format query (not array)!', 'ERROR', 'MdExport.setQuery');
			return -1;
		}

		if ($sql == -1 || $sql == '') {
			setMickaLog('SQL == -1 or null', 'ERROR', 'MdExport.setQuery');
			return -1;
		}
		
		if ($this->search_uuid === TRUE) {
			setMickaLog('search UUID', 'DEBUG', "MdExport.setQuery.uuid");
			$rs['paginator']['records'] = 1;
			$rs['sql'] = "SELECT recno, uuid, md_standard, lang, data_type, create_user, create_date, last_update_user, last_update_date, edit_group, view_group, valid, xmldata AS pxml FROM md";
			//$rs['sql'] = "SELECT recno, uuid, xmldata FROM md";
			/*
			if (strpos($this->sql_uuid, ",") === FALSE) {
				$rs['sql'] .= " WHERE uuid='" . $this->sql_uuid . "'";
			}
			else {
				$this->sql_uuid = str_replace(',', "','", $this->sql_uuid);
				$rs['sql'] .= " WHERE uuid IN ('" . $this->sql_uuid . "')";
			}
			*/
			$rs['sql'] .= " WHERE " . $this->getRight() . " uuid IN " . $this->sql_uuid;
			return $rs;
		}
		$founds = getPaginator($sql, $this->maxRecords, $this->page_number);
		setMickaLog($founds, 'DEBUG', "MdExport.setQuery.founds");
		if ($founds == -1) {
			setMickaLog('$founds == -1', 'ERROR', 'MdExport.setQuery');
			$this->error = -1; //Error
		}
		else {
			$rs['paginator'] = $founds;
		}
		
		/*
		// Pokud je použit odhad pro Oracle
		if ($founds['records'] > 0 && $founds['records'] < 1000) {
			$sql = $this->getSql('count', $this->user, 0, 0, $this->maxRecords, $this->sortBy);
			$founds = getPaginator($sql, $this->maxRecords, $this->page_number);
			setMickaLog($founds, 'DEBUG', "MdExport.setQuery.founds_count");
			$rs['paginator'] = $founds;
		}
		 * 
		 */
		
		$sql = '';
		if ($founds['records'] > 0) {
			if ($founds['end_page'] < $this->page_number) {
				if ( ($this->startPosition - $this->maxRecords) > 0) {
					$this->startPosition = $this->startPosition - $this->maxRecords;
				}
			}
			$sql = $this->getSql('find', $this->user, $this->startPosition, $this->sortBy);
		}
		
		if ($sql == -1) {
			setMickaLog('SQL == -1', 'ERROR', 'MdExport.setQuery');
			return -1;
		}
		$rs['sql'] = $sql;
		return $rs;
	}

	public function getXML($in, $params, $result=TRUE, $only_xml=FALSE) {
		//Debugger::dump($in);exit;
		//Debugger::log('[MdExport.getXML.begin] ' . print_r($in, true), 'INFO');

		$this->rs_xml = '';
		$rs_type = $only_xml === TRUE ? 'xml' : 'array';
		$rs_md = array();
		
		$supr = canAction('*'); // root - superuživatel, spravce projektu
		$vysl = array();
		$recno_arr = array();
		if (is_array($in) === FALSE) {
			$in = array();
		}
		if (is_array($params) === FALSE) {
			$params = array();
		}
		$this->setFlatParams($params);
		$this->setQueryIn($in);
		$in = $this->query_in;
		$pom = $this->setQuery($this->setMdParams($in));
		
		if ($pom == -1) {
			setMickaLog('SQL == -1', 'ERROR', 'MdExport.getXML');
			// TODO: návrat chyby
			/*
			if ($this->query_status === FALSE) {
				my_print_r($this->query_error);
			}
			*/
			return -1;
		}
		
		if ($this->search_uuid === FALSE) { 
			$numberOfRecods = $this->setNumberOfRecords($this->startPosition+1, $pom['paginator']['records']);
		}
		
		if ($pom['paginator']['records'] > 0 && $pom['sql'] != '' && $this->hits === FALSE) {
			if ($this->xml_from == 'cache') {
				$vysl = _executeSql('select', array($pom['sql']), array('all'));
				//Debugger::log('[MdExport.getXML.vysl] ' . print_r($vysl, true), 'ERROR');
				$recno_arr =  array_keys($vysl);
				if ($this->search_uuid === TRUE) {
                    if (is_array($vysl) === FALSE && $vysl == '') {
                       $vysl = array();
                    }
					$numberOfRecods = $this->setNumberOfRecords($this->startPosition+1, count($vysl));
				}
			} else {
				$elements_label = $this->getIdElements(); // nacteni elementu pro prevod na kody
				$result_db = DB_DRIVER == 'oracle'
					? _executeSql('select', array($pom['sql']), array('assoc', 'RECNO,#,='))
					: _executeSql('select', array($pom['sql']), array('assoc', 'recno,#,='));;
				$eval_text = '';
				foreach ($result_db as $recno => $data) {
					$recno_arr[] = $recno; // seznam recno
					foreach ($data as $idx => $row) {
						if ($row['MD_PATH'] == '') {
							continue;
						}
						$mds = $row['MD_STANDARD'];
						if (($mds-10) > -1) {
							$mds = $mds-10;
						}
						$path_arr = explode('_', substr($row['MD_PATH'], 0, strlen($row['MD_PATH']) - 1));
						$eval_text_tmp = '$vysl[' . $recno . ']';
						foreach ($path_arr as $key=>$value) {
							if ($key%2 == 0) {
								$eval_text_tmp .= "['" . $elements_label[$mds][$value] . "']";
							}
							else {
								$eval_text_tmp .= '[' . $value . ']';
							}
						}
						$eval_text_tmp .= "['!" . $row['LANG'] . "']=" . '"' . gpc_addslashes($row['MD_VALUE']) . '";' . "\n";
						$eval_text .= $eval_text_tmp;
					}
				}
				eval ($eval_text);
			}
			$this->md = array();
			if (count($recno_arr) == 0) {
				// TODO: otestovat stav, kdy požaduji záznam vyšší, než je počet nalezených
				//$numberOfRecods['Return'] = 0;
			}
			elseif ($this->xml_from == 'data') {
				$this->setMdHeader($recno_arr);
			}
		}
		
		if ($result) {
			$this->rs_xml .= "<results numberOfRecordsMatched=\"".$numberOfRecods['Matched']."\" numberOfRecordsReturned=\"".$numberOfRecods['Return']."\" nextRecord=\"".$numberOfRecods['Next']."\" elementSet=\"brief\">";
		}
		if (is_array($vysl) && $this->hits === FALSE) {
			if ($this->xml_from == 'data') {
				$this->printMDXML($vysl);
			}
			if ($this->xml_from == 'cache') {
				foreach ($vysl as $key => $item) {
                    if (DB_DRIVER == 'mssql2005' && is_object($item['LAST_UPDATE_DATE'])) {
                         $item['CREATE_DATE'] = $item['CREATE_DATE']->format('Y-m-d');
                         $item['LAST_UPDATE_DATE'] = $item['LAST_UPDATE_DATE']->format('Y-m-d');
                    }
					$item['edit'] = 0;
					if (getMsGroups('is_set', $item['EDIT_GROUP']) || $supr) {
						$item['edit'] = 1;
					}
					if ($item['CREATE_USER'] == $this->user && $this->user != 'guest') {
						$item['edit'] = 1;
					}
					if ($this->ext_header === TRUE) {
						$item = $item + $this->getHarvestor($item['SERVER_NAME']);
					} else {
						$item['harvest_source'] = '';
						$item['harvest_title'] = '';
					}
					if ($rs_type == 'xml') {
						$this->rs_xml .= '<rec recno="' . $item['RECNO'] . '"' .
							' uuid="' . $item['UUID'] . '"' .
							' md_standard="' . $item['MD_STANDARD'] . '"' .
							' lang="' . $item['LANG'] . '"' .
							' data_type="' . $item['DATA_TYPE'] . '"' .
							' create_user="' . $item['CREATE_USER'] . '"' .
							' create_date="' . $item['CREATE_DATE'] . '"' .
							' last_update_user="' . $item['LAST_UPDATE_USER'] . '"' .
							' last_update_date="' . $item['LAST_UPDATE_DATE'] . '"' .
							' edit_group="' . $item['EDIT_GROUP'] . '"' .
							' view_group="' . $item['VIEW_GROUP'] . '"' .
							' valid="' . $item['VALID'] . '"' .
							' prim="' . $item['PRIM'] . '"' .
							' server_name="' . $item['SERVER_NAME'] . '"' .
							' harvest_source="' . $item['harvest_source'] . '"' .
							' harvest_title="' . $item['harvest_title'] . '"' .
							' edit="' . $item['edit'] . '">' .
							$item['PXML'] .
							"</rec>";
					}
					else {
						$this->rs_xml .= $item['PXML'];
						unset($item['PXML']);
						$rs_md[] = $item;
					}
				}
			}
		}
		if ($result) {
			$this->rs_xml .= "\n";
			$this->rs_xml .= "</results>";
		}
		//$this->set2FileLog(array($in, $pom['sql'], $this->rs_xml, $rs_md));
		$_SESSION['micka']['search']['xmlMatched'] = $numberOfRecods['Matched'];
		if ($rs_type == 'xml') {
			return $this->rs_xml;
		}
		return array($this->rs_xml, $rs_md);
	}

	public function getXmlTmpMd($uuid) {
		setMickaLog("uuid=$uuid", 'FUNCTION', "MdExport.getXmlTmpMd.start");

		$vysl = array();
		$recno_arr = array();

		$elements_label = $this->getIdElements(); // nacteni elementu pro prevod na kody

		$sql = array();
        $tmp_table_md = TMPTABLE_PREFIX . '_md';
        $tmp_table_md_values = TMPTABLE_PREFIX . '_md_values';
		array_push($sql, "
			SELECT $tmp_table_md_values.recno, $tmp_table_md_values.md_path, " . setNtext2Text($tmp_table_md_values . '.','md_value') . ", $tmp_table_md_values.lang, $tmp_table_md.md_standard
			FROM ($tmp_table_md JOIN $tmp_table_md_values ON $tmp_table_md.recno = $tmp_table_md_values.recno)
				JOIN standard ON $tmp_table_md.md_standard = standard.md_standard
			WHERE $tmp_table_md.sid=%s and $tmp_table_md.uuid=%s
			ORDER BY $tmp_table_md_values.md_path
		", $this->sid, $uuid);
		$result = DB_DRIVER == 'oracle'
			? _executeSql('select', array($sql), array('assoc', 'RECNO,#,='))
			: _executeSql('select', array($sql), array('assoc', 'recno,#,='));
		//$result = _executeSql('select', array($pom['sql']), array('assoc', 'recno,#,='));
		$eval_text = '';
		foreach ($result as $recno => $data) {
			$recno_arr[] = $recno; // seznam recno
			foreach ($data as $idx => $row) {
				if ($row['MD_PATH'] == '') {
					continue;
				}
				$mds = $row['MD_STANDARD'];
				if (($mds-10) > -1) {
					$mds = $mds-10;
				}
				$path_arr = explode('_', substr($row['MD_PATH'], 0, strlen($row['MD_PATH']) - 1));
				$eval_text_tmp = '$vysl[' . $recno . ']';
				foreach ($path_arr as $key=>$value) {
					if ($key%2 == 0) {
						$eval_text_tmp .= "['" . $elements_label[$mds][$value] . "']";
					}
					else {
						$eval_text_tmp .= '[' . $value . ']';
					}
				}
				$eval_text_tmp .= "['!" . $row['LANG'] . "']=" . '"' . gpc_addslashes($row['MD_VALUE']) . '";' . "\n";
				$eval_text .= $eval_text_tmp;
			}
		}
		eval ($eval_text);

		$this->md = array();
		$this->setMdHeader($recno_arr);

		$this->printMDXML($vysl);

		setMickaLog($this->rs_xml, 'DEBUG', "MdExport.getXmlTmpMd.return");
		return $this->rs_xml;
	}

	public function getData($in) {
		setMickaLog($in, 'FUNCTION', "MdExport.getData.start");

		$rs = array();
		$rs['paginator']['records'] = 0;
		$this->xml_from = 'summary';
		
		$this->setQueryIn($in);
		$in = $this->query_in;
		$pom = $this->setQuery($in);
		
		if ($pom == -1) {
			setMickaLog('SQL == -1', 'ERROR', 'MdExport.getData');
			// TODO: návrat chyby
			/*
			if ($this->query_status === FALSE) {
				//my_print_r($this->query_error);
			}
			*/
			return -1;
		}
		elseif ($pom['paginator']['records'] > 0) {
			$result = dibi::query($pom['sql']);
			$rs['data'] = DB_DRIVER == 'oracle' ? setUpperColsName($result->fetchAssoc('RECNO,#,=')) : setUpperColsName($result->fetchAssoc('recno,#,='));
			$rs['paginator'] = $pom['paginator'];
		}
		if (isset($rs['data']) && count($rs['data']) > 0) {
			$rs['data'] = $this->setData2Micka($rs['data']);
		}
		setMickaLog($rs, 'DEBUG', "MdExport.getData.return");
		return $rs;
	}
}

?>