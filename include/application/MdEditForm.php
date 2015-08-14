<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * Edit form for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20140226
 *
 */


class EditForm {
	private $form_values = array();
	private $form_data = array();
	private $md_first_lang = '';
	private $md_langs = array();
	private $md_langs_count = 0;
	private $code_list_array = array();
	private $button_label = array();
	private $tree_level = array();
	private $repeat = array();
	private $rb_level = array();
	private $tree_run = 0;
	private $recno;
	private $mds;
	private $profil;
	private $package;
	private $path_rew_old;
	private $path_rew_new;

	//private $debug = TRUE;
	private $debug = FALSE;
	
	function  __construct() {
		$this->md_first_lang = MICKA_LANG;
	}

	private function setFormValuesArray($md_values) {
		setMickaLog("data", 'DEBUG', 'MdRecord.setFormValuesArray');
		$rs = array();
		if (is_array($md_values) && count($md_values) > 0) {
			foreach($md_values as $row) {
				$md_path = getMdPath($row['MD_PATH']);
				if ($this->mds == 0 || $this->mds == 10) {
					if ($row['MD_ID'] == 5527 && strlen(trim($row['MD_VALUE'])) == 3) {
						$this->md_first_lang = trim($row['MD_VALUE']);
					}
				}
				$eval_text = '$rs' . $md_path . "['" . $row['LANG'] . "']" . "=\"" . gpc_addslashes($row['MD_VALUE']) . "\";";
				eval($eval_text);
			}
		}
		$this->sortMdLangs();
		$this->form_values = $rs;
		//my_print_r($rs);
	}
	

	private function sortMdLangs() {
		if (count($this->md_langs) > 1 && $this->md_langs[0] != $this->md_first_lang) {
			$key = array_search($this->md_first_lang, $this->md_langs);
			if ($key === FALSE) {
				$this->md_first_lang = MICKA_LANG;
				$key = array_search($this->md_first_lang, $this->md_langs);
			}
			if ($key !== FALSE) {
				array_splice($this->md_langs, $key, 1);
				array_unshift($this->md_langs, $this->md_first_lang);
			}
		}
	}
	
	private function setMdLangs($md_langs) {
		$this->md_langs = getMdLangs($md_langs);
		$this->md_langs_count = count($this->md_langs);
		if ($this->md_langs_count == 0) {
			setMickaLog("Not found MdLangs! lang=$md_langs!", 'ERROR', 'EditForm.setMdLangs');
		}
	}

	private function getPathRewNew($path_rew_old, $idx) {
		$rs = $path_rew_old;
		if ($path_rew_old == '') {
			return $rs;
		}
		$path_rew_old = substr($path_rew_old, 0, strlen($path_rew_old)-1);
		$pom = explode('_',$path_rew_old);
		array_pop($pom);
		array_push($pom, $idx);
		$rs = implode('_', $pom) . '_';
		return $rs;
	}

	private function getPathNotLast($path) {
		$rs = $path;
		if ($path == '') {
			return $rs;
		}
		$path_rew = substr($path, 0, strlen($path)-1);
		$pom = explode('_',$path_rew);
		array_pop($pom);
		$rs = implode('_', $pom);
		return $rs;
	}

	private function setCodeListArray($inspire=FALSE) {
		$sql = array();
		array_push($sql, "
			SELECT codelist.el_id, label.label_text, codelist.codelist_id, codelist.codelist_domain, codelist.codelist_name
			FROM (label INNER JOIN codelist ON label.label_join = codelist.codelist_id)
				LEFT JOIN codelist_my ON codelist.codelist_id = codelist_my.codelist_id
			WHERE label.label_type='CL' AND label.lang=%s  AND codelist_my.is_vis=1
		", MICKA_LANG);
		if ($inspire) {
			array_push($sql, 'AND codelist.inspire=1');
		}
		$records = _executeSql('select', $sql, array('all','='));
		foreach ($records as $row) {
			$el_id = $row['EL_ID'];
			$cl_id = $row['CODELIST_ID'];
			$this->code_list_array[$el_id][$cl_id]['t'] = $row['LABEL_TEXT'];
			$this->code_list_array[$el_id][$cl_id]['d'] = $row['CODELIST_DOMAIN'];
			$this->code_list_array[$el_id][$cl_id]['n'] = $row['CODELIST_NAME'];
		}
	}

	private function setButtonLabelArray() {
		$this->button_label = getLabelButton();
	}

	private function setTreeLevel($level_run, $level, $div=1, $insert=FALSE) {
		$key = -1;
		if (isset($this->tree_level[$level_run]) && count($this->tree_level[$level_run]) > 1) {
			end($this->tree_level[$level_run]);
			$key = key($this->tree_level[$level_run]);
		}
		if ($level > $key || $insert === TRUE) {
			$this->tree_level[$level_run][$level] = (isset($this->tree_level[$level_run][$level]))
							? $this->tree_level[$level_run][$level]+ $div
							: $div;
			//$this->tree_level[$this->tree_run][$level] += $div;
		}
	}

	private function getTreeLevel($level_run, $level) {
		$rs = array();
//my_print_r($this->tree_level[$this->tree_run]);
		if (isset($this->tree_level[$level_run]) && count($this->tree_level[$level_run]) > 0) {
			do {
				end($this->tree_level[$level_run]);
				$key = key($this->tree_level[$level_run]);
				$value = current($this->tree_level[$level_run]);
				if ($level <= $key) {
					if ($value > 0) {
						for ($i=0; $i < $value; $i++) {
							$rs[] = 1;
						}
					}
					array_pop($this->tree_level[$level_run]);
				}
			}
			while($level <= $key);
		}
//print_r($rs);
//my_print_r($this->tree_level[$this->tree_run]);
//echo "<hr>";
		return $rs;
	}

	private function getComboCodeList($from_codelist, $md_value) {
		$rs = '';
		if ($from_codelist == '') {
			setMickaLog("from_codelist=$from_codelist!", 'ERROR', 'EditForm.getComboCodeList');
			return $rs;
		}
		$from_codelist = (int) $from_codelist;
		$pom_from_codelist = ABS($from_codelist);
		//setMickaLog("from_codelist=$pom_from_codelist", 'DEBUG', 'EditForm.php (getComboCodeList)');
		if (array_key_exists($pom_from_codelist, $this->code_list_array)) {
			foreach ($this->code_list_array[$pom_from_codelist] as $pom_value) {
				//my_print_r($pom_value);
				if ($from_codelist == 1460 && $this->mds == 0 && $pom_value['d'] < 0) {
					continue;
				}
				if ($from_codelist == 1460 && $this->mds == 10 && $pom_value['d'] > 0) {
					continue;
				}
				foreach ($pom_value as $key=>$value) {
					if ($key == 't') {
						$pom_t = $value;
					}
					if ($key == 'd') {
						$pom_d = $value;
					}
					if ($key == 'n') {
						$pom_n = $value;
					}
				}
				if ($from_codelist > 0) {
					if ($md_value == $pom_n) {
						$rs .= '<option value="' . $pom_n . '" selected>' . $pom_t;
					}
					else {
						$rs .= '<option value="' . $pom_n . '">' . $pom_t;
					}
				}
				elseif ($from_codelist < 0) {
					if ($md_value == $pom_d) {
						$rs .= '<option value="' . $pom_d . '" selected>' . $pom_t;
					}
					else {
						$rs .= '<option value="' . $pom_d . '">' . $pom_t;
					}
				}
			}
		}
		return $rs;
	}

	private function getMdValue($md_path, $value_lang) {
		$rs = '';
		if ($md_path == '' || $value_lang == '') {
			setMickaLog("md_path=$md_path, value_lang=$value_lang", 'ERROR', 'EditForm.getMdValue');
		}
		else {
			$path = getMdPath($md_path) . "['" . $value_lang . "']";
			$eval_label = '$rs=isset($this->form_values' . $path . ') ? $this->form_values' . "$path : '';";
			//setMickaLog("eval_label=$eval_label", 'DEBUG', 'EditForm.php (getMdValue)');
			eval ($eval_label);
			//echo "$eval_label, rs=$rs<br>";
		}
		//setMickaLog("value=$rs", 'DEBUG', 'EditForm.php (getMdValue)');
		return $rs;
	}

	private function getFormCode($form_code, $from_codelist, $el_id, $value_lang, $md_path) {
		$rs = array();
		$rs['form_code'] = $form_code;
		$rs['value'] = $this->getMdValue($md_path, $value_lang);
		switch ($form_code) {
			case 'D' :
				if ($rs['value'] != '' && MICKA_LANG == 'cze' && strlen($rs['value']) > 4) {
					$rs['value'] = dateIso2Cz($rs['value']);
				}
				break;
			//case 'R' :
			//	break;
			case 'C' :
				$from_codelist = ($from_codelist != '') ? $from_codelist : $el_id;
				//setMickaLog("from_codelist=$from_codelist", 'DEBUG', 'EditForm.php (getFormCode)');
				$rs['value'] = $this->getComboCodeList($from_codelist, $rs['value']);
				break;
		}
		return $rs;
	}

	private function getButtonExe($button) {
		$rs = array();
		$rs['text'] = '';
		$rs['action'] = '';
		if ($button != '') {
			$pom = explode('|', $button);
			$rs['action'] = $pom[0];
			$button_id = $pom[1];
			$rs['text'] = isset($this->button_label[$button_id]) ? $this->button_label[$button_id] : '';
		}
		return $rs;
	}

	private function setRadioButton($level_run, $form_code, $level) {
		if ($form_code != 'R' && isset($this->rb_level[$level_run])) {
			if (count($this->rb_level[$level_run]) > 1) {
				do {
					end($this->rb_level[$level_run]);
					$key = key($this->rb_level[$level_run]);
					if ($level <= $key) {
						array_pop($this->rb_level[$level_run]);
					}
				}
				while($level <= $key);
			}
		}
	}

	private function getRadioButton($level_run, $level, $md_id, $md_path) {
		$rs = array();
		$rs['rb_id'] = '';
		if (isset($this->rb_level[$level_run]) === FALSE || count($this->rb_level[$level_run]) == 0) {
			$this->rb_level[$level_run][$level] = $md_id;
			$rs['rb_id'] = $md_id . '_' . $level_run;
		}
		elseif (count($this->rb_level[$level_run]) > 0){
			end($this->rb_level[$level_run]);
			$key = key($this->rb_level[$level_run]);
			$value = current($this->rb_level[$level_run]);
			if ($level == $key) {
				$rs['rb_id'] = $value . '_' . $level_run;
			}
			elseif ($level > $key) {
				$this->rb_level[$level_run][$level] = $md_id;
				$rs['rb_id'] = $md_id . '_' . $level_run;
			}
		}
		$this->setTreeLevel($level_run, $level, 1, TRUE);
		$rs['rb_checked'] = ($this->getIsData($md_path)) ? 1 : 0;
		return $rs;
	}

	private function getRepeatDiv($level_run, $level) {
		$rs = array();
		if (isset($this->tree_level[$level_run]) && count($this->tree_level[$level_run]) > 0) {
			do {
				end($this->tree_level[$level_run]);
				$key = key($this->tree_level[$level_run]);
				$value = current($this->tree_level[$level_run]);
				if ($level <= $key) {
					$rs[$key] = $value;
					array_pop($this->tree_level[$level_run]);
				}
			}
			while($level <= $key);
		}
		$rs = array_reverse($rs, TRUE);
		return $rs;
	}

	private function getRepeat($level_run, $level) {
		if ($this->debug) echo "getRepeat levet_run=$level_run, level=$level<br>";
		$rs = array();
		$rs_pom = array();
		$first = TRUE;
		if (isset($this->repeat[$level_run])&& count($this->repeat[$level_run]) > 0) {
			do {
				if ($this->debug) echo "getRepeat DO<br>";
				end($this->repeat[$level_run]);
				$key = key($this->repeat[$level_run]);
				$value = current($this->repeat[$level_run]);
				if ($level <= $value['level']) {
					$pom = array();
					$pom = explode('|', $value['repeat']);
					$div_end = $first ? $this->getRepeatDiv($level_run, $level) : $rs_pom;
					$count_repeat = count($pom);
					$idx = 0;
					foreach ($pom as $v) {
						if ($this->debug) echo "getRepeat FOREACH $idx<br>";
						$idx++;
						if (count($div_end) > 0) {
							if ($this->debug) {echo "getRepeat TREE_LEVEL=" . $this->tree_level[$level_run+1]; my_print_r($div_end);}
							$this->tree_level[$level_run+1] = $div_end;
							if ($this->debug) {echo "getRepeat TREE_LEVEL"; my_print_r($this->tree_level);}

						}
						$this->path_rew_old = $value['md_path'];
						$this->path_rew_new = $this->getPathRewNew($value['md_path'], $v);
						$this->getFormData($this->getMdTree($this->recno, $this->mds, $this->profil, $this->package, $value['md_id']), $this->getPathRewNew($value['md_path'], $v), $value['md_path_org'], $v);

						if ($count_repeat == $idx) {
							// poslední opakování, předat zbývající divy
							$l = $level_run + 1;
							if (isset($this->tree_level[$l]) && count($this->tree_level[$l]) > 0) {
								$rs_pom = array();
								$rs = array();
								do {
									end($this->tree_level[$l]);
									$div_key = key($this->tree_level[$l]);
									$div_value = current($this->tree_level[$l]);
									if ($level <= $div_key) {
										$rs_pom[$div_key] = $div_value;
										if ($div_value > 0) {
											if ($this->debug) {echo "getRepeat div_value $l"; my_print_r($this->tree_level);}
											for ($i=0; $i < $div_value; $i++) {
												$rs[] = 1;
											}
										}
										array_pop($this->tree_level[$l]);
									}
								}
								while($level <= $div_key);
							}
						}
						$rs_pom = count($rs_pom) > 0 ? array_reverse($rs_pom, TRUE) : $rs_pom;
					}
					unset($this->repeat[$level_run][$key]);
					unset($this->tree_level[$l]);
					$first = FALSE;
				}
			}
			while($level <= $value['level']);
		}
		if ($this->debug) echo "getRepeat RETURN<br>";
		return $rs;
	}

	private function setNewRepeat($level_run, $md_path, $md_path_org, $md_id, $level) {
		if ($this->debug) echo "setNewRepeat level_run=$level_run, md_path=$md_path, md_id=$md_id, level=$level, path_old=" . $this->path_rew_old . "<br>";
		if ($this->debug) my_print_r($this->tree_level);
		$rs = FALSE;
		$repeat_idx = array();
		if ($md_path == $this->path_rew_old) {
			if ($this->debug) echo "setNewRepeat RETURN 1<br>";
			return $rs;
		}
		if ($this->getPathNotLast($md_path) == $this->getPathNotLast($this->path_rew_old)) {
			//echo "setNewRepeat md_path="  . $this->getPathNotLast($md_path) . ", path_rew=" . $this->getPathNotLast($this->path_rew_old) . "<br>";
			if ($this->debug) echo "setNewRepeat RETURN 2<br>";
			return $rs;
		}
		if ($md_path == '' || $md_id == '') {
			setMickaLog("md_path=$md_path, value_lang=$md_id", 'ERROR', 'EditForm.getRepeatElemets');
		}
		else {
			$path = str_replace($md_id . '_0_', $md_id, $md_path);
			$path = getMdPath($path);
			$eval_label = '$repeat_idx=isset($this->form_values' . $path . ') ? array_keys($this->form_values' . "$path) : array();";
			//echo $eval_label . "<br>";
			eval ($eval_label);
			//echo $eval_label . "<br>";
			if (count($repeat_idx) > 1) {
				sort($repeat_idx, SORT_NUMERIC);
				//my_print_r($repeat_idx);
				$pom = '';
				/*
				for ($i =1; $i<count($repeat_idx); $i++) {
					$pom = ($pom == '') ? $i : $pom . '|' . $i;
				}
				*/
				foreach ($repeat_idx as $value) {
					$pom = ($pom == '') ? $value : $pom . '|' . $value;
				}
				$record = array();
				$record['md_id'] = $md_id;
				$record['repeat'] = $pom;
				$record['md_path'] = $md_path;
				$record['md_path_org'] = $md_path_org;
				$record['level'] = $level;
				//$record['level'][$level]['data'] = $repeat_idx;
				$this->repeat[$level_run][] = $record;

				$rs = TRUE;
			}
		}
		if ($this->debug) my_print_r($this->tree_level);
		if ($this->debug) echo "setNewRepeat RETURN<br>";
		return $rs;
	}

	private function getFormData($values, $path_rew_new='', $path_rew_old='', $repeat=0) {
		$this->tree_run++;
		if ($this->debug) echo "<hr>";
		if ($this->debug) echo "BEGIN getFormData level_run=" . $this->tree_run . ", path_rew_new=$path_rew_new, path_rew_old=$path_rew_old<br>";
		if ($this->debug) my_print_r($this->tree_level);
		if ($this->debug) my_print_r($this->repeat);
		if ($this->debug) echo "<hr>";
		$record = array();
		$repeat_first = TRUE;
//		my_print_r($this->md_langs);
		if (is_array($values) && count($values) > 0) {
			foreach ($values as $row) {
				foreach ($this->md_langs as $key=>$md_lang) {
					$end_div = array();
					if ($this->debug) echo "<strong>MD_ID="	. $row['MD_ID'] . ' [' . $this->md_langs[$key] . '] ' . $row['LABEL_TEXT'] . ", level=" . $row['MD_LEVEL'] . ", form_code=" . $row['FORM_CODE'] . "</strong><br>";
					if ($this->debug) {echo "11111"; my_print_r($this->tree_level);}
					if ($key > 0 && $row['MULTI_LANG'] == 0) {
					 continue;
					}
					if ($row['FORM_IGNORE'] == 1) {
						$this->setTreeLevel($this->tree_run, $row['MD_LEVEL'], 0);
						continue;
					}
					if ($key == 0) {
						$end_div = $this->getRepeat($this->tree_run, $row['MD_LEVEL']);
						if ($this->debug) {echo "end_div="; my_print_r($end_div);}
						$record['end_div'] = $this->getTreeLevel($this->tree_run, $row['MD_LEVEL']);
					}
					else {
						$record['end_div'] = array();
					}
					if (count($end_div) > 0 && $key == 0) {
						$record['end_div'] = array_merge($record['end_div'], $end_div);
					}
					$record['start_div'] = 1;
					if ($this->debug) {echo "22222"; my_print_r($this->tree_level);}
					if ($row['ONLY_VALUE'] == 0 && $key == 0) {
						$this->setTreeLevel($this->tree_run, $row['MD_LEVEL']);
					}
					if ($this->debug) {echo "33333"; my_print_r($this->tree_level);}
					$this->setRadioButton($this->tree_run, $row['FORM_CODE'], $row['MD_LEVEL']);
					$record['md_path'] = $row['MD_PATH'];
					if ($this->tree_run > 1 && $path_rew_old != '' && $path_rew_new != '') {
						$record['md_path'] = str_replace($path_rew_old, $path_rew_new, $record['md_path']);
					}
					$record['next_lang'] = ($key == 0) ? 0 : 1;
					$record['pack'] = $this->getFormPack($this->tree_run, $row['FORM_PACK'], $row['MD_LEVEL'], $record['md_path']);
					$record['md_id'] = $row['MD_ID'];
					
					if ($repeat_first === TRUE) {
						$record['repeat'] = $repeat;
						$repeat_first = FALSE;
					}
					else {
						$record['repeat'] = 0;
					}
					
					//$record['repeat'] = $repeat;
					
					$record['package_id'] = $row['PACKAGE_ID'];
					$record['el_id'] = $row['EL_ID'];
					$record['value_lang'] = ($row['MULTI_LANG'] == 1) ? $md_lang : 'xxx';
					$record['rb'] = 0;
					if ($row['FORM_CODE'] == 'R') {
						// RADIOBUTTON
						$pom = $this->getRadioButton($this->tree_run, $row['MD_LEVEL'], $row['MD_ID'], $record['md_path']);
						$record['form_code'] = 'R';
						$record['rb'] = 1;
						$record['rb_id'] = $pom['rb_id'];
						$record['rb_checked'] = $pom['rb_checked'];
					}
					if ($row['MD_LEFT'] == $row['MD_RIGHT']-1) {
						// DATA
						$pom = $this->getFormCode($row['FORM_CODE'], $row['FROM_CODELIST'], $row['EL_ID'], $record['value_lang'], $record['md_path']);
						$record['form_code'] = $pom['form_code'];
						$record['value'] = $pom['value'];
					}
					else {
						// LABEL
						$record['form_code'] = ($row['FORM_CODE'] == 'R') ? 'R' : 'L';
						$record['value'] = '';
					}
					$record['mandt_code'] = $row['MANDT_CODE'];
					$record['inspire_code'] = $row['INSPIRE_CODE'];
					$record['label'] = $row['LABEL_TEXT'];
					$record['help'] = $row['LABEL_HELP'];
					$record['max_nb'] = $row['MAX_NB'];
					
					// Opakování
					if ($row['MAX_NB'] == '' && $key == 0) {
					//if ($row['MAX_NB'] == '') {
						$this->setNewRepeat($this->tree_run, $record['md_path'],  $row['MD_PATH'], $row['MD_ID'], $row['MD_LEVEL']);
					}

					$pom = $this->getButtonExe(trim($row['BUTTON_EXE']));
					$record['button_text'] = $pom['text'];
					$record['button_action'] = $pom['action'];
					if ($row['ONLY_VALUE'] == 1) {
						$idx = count($this->form_data)-1;
						$this->form_data[$idx]['form_code'] = $record['form_code'];
						$this->form_data[$idx]['value'] = $record['value'];
						$this->form_data[$idx]['md_path'] = $record['md_path'];
						//$rs[$idx]['el_id'] = $record['el_id'];
					}
					else {
						array_push($this->form_data, $record);
					}
				}
			}
		}
		if ($this->debug) my_print_r($this->tree_level);
		if ($this->debug) echo "==================  E N D E  =====================================================<br>";
		$this->tree_run--;
	}

	private function getIsData($md_path) {
		$rs = FALSE;
		$path = getMdPath($md_path);
		$eval_label = '$rs=isset($this->form_values' . $path . ') ? TRUE : FALSE;';
		eval ($eval_label);
		return $rs;
	}

	private function getFormPack($level_run, $form_pack, $md_level, $md_path) {
		$rs = 0;
		if ($form_pack == 1) {
			$this->setTreeLevel($level_run, $md_level, 1, TRUE);
			// kontrola existence dat
			$rs = ($this->getIsData($md_path)) ? 2 : 1;
		}
		return $rs;
	}

	private function getMdTree($recno, $mds, $profil_id, $package_id, $md_id_start=-1) {
		setMickaLog("recno=$recno, mds=$mds, profil=$profil_id, package=$package_id, start=$md_id_start", 'DEBUG', 'EditForm.getMdTree.start');
		$rs = array();
		$sql = array();
		//$mds_package = $mds;
		if ($mds == 10) {
			$mds = 0;
		}
		array_push($sql,"
			SELECT elements.el_id,
						 elements.el_name,
						 elements.form_code,
						 elements.form_pack,
						 elements.el_short_name,
						 elements.from_codelist,
						 elements.only_value,
						 elements.form_ignore,
						 elements.multi_lang,
						 tree.md_id,
						 tree.md_left,
						 tree.md_right,
						 tree.md_level,
						 tree.mandt_code,
						 tree.md_path,
						 tree.max_nb,
						 tree.button_exe,
						 tree.package_id,
                         tree.inspire_code,
						 label.label_text,
						 label.label_help
			FROM (label INNER JOIN elements ON label.label_join = elements.el_id) INNER JOIN tree ON elements.el_id = tree.el_id
		");
		//array_push($sql, "WHERE label.label_type='EL' AND elements.form_ignore=0");
		array_push($sql, "WHERE label.label_type='EL'");
		array_push($sql, "AND tree.md_standard=%i", $mds);
		array_push($sql, "AND label.lang=%s", MICKA_LANG);
		if ($profil_id > -1) {
			array_push($sql, "AND tree.md_id IN(SELECT md_id FROM profil WHERE profil_id=%i)", $profil_id);
		}
		if ($md_id_start > -1) {
			$start_sql = array();
			array_push($start_sql, "SELECT md_left, md_right FROM tree WHERE md_standard=%i AND md_id=%i", $mds, $md_id_start);
			$pom =  _executeSql('select', $start_sql, array('all','='));
			if (count($pom) == 1) {
				$md_left = $pom[0]['MD_LEFT'];
				$md_right  = $pom[0]['MD_RIGHT'];
				array_push($sql, "AND tree.md_left>=%i  AND tree.md_right<=%i", $md_left, $md_right);
			}
			else {
				setMickaLog("Not found md_id=$md_id_start!", 'ERROR', 'EditForm.getMdTree');
			}
		}
		if ($profil_id == -1 && $package_id > -1 && $md_id_start == -1) {
			$start_sql = array();
			array_push($start_sql, "SELECT md_left, md_right FROM tree WHERE md_standard=%i AND md_id=", $mds);
			array_push($start_sql, "(SELECT md_id FROM packages WHERE md_standard=%i AND package_id=%i)", $mds, $package_id);
			$pom =  _executeSql('select', $start_sql, array('all','='));
			if (count($pom) == 1) {
				$md_left = $pom[0]['MD_LEFT'];
				$md_right  = $pom[0]['MD_RIGHT'];
				array_push($sql, "AND tree.md_left>=%i  AND tree.md_right<=%i", $md_left, $md_right);
			}
			else {
				setMickaLog("Not found left, right! Profil=$profil_id, package=$package_id.", 'ERROR', 'EditForm.getMdTree');
			}
		}
		if ($package_id > -1) {
			array_push($sql, "AND tree.package_id=%i", $package_id);
		}
		if ($mds == 1) {
			array_push($sql, "ORDER BY tree.md_level,tree.md_left");
		}
		else {
			array_push($sql, "ORDER BY tree.md_left");
		}
		if (count($sql) > 1) {
			$rs = _executeSql('select', $sql, array('all','='));
		}
		//my_print_r($rs);
		return $rs;
	}

	public function getEditFormEnd() {
		setMickaLog("get", 'DEBUG', 'EditForm.getEditFormEnd');
		$rs = array();
		if (isset($this->tree_level[1]) && count($this->tree_level[1]) > 0) {
			foreach ($this->tree_level[1] as $value) {
				if ($value > 0) {
					for ($i=0; $i < $value; $i++) {
						$rs[] = 1;
					}
				}
			}
		}
		return $rs;
	}

	public function getEditForm($mds, $recno, $md_langs, $profil, $package, $md_values) {
		setMickaLog("mds=$mds, recno=$recno, profil=$profil, package=$package", 'DEBUG', 'EditForm.getEditForm');
		$md_id_start = -1;
		$this->setMdLangs($md_langs);
		$this->setCodeListArray(getIsInspirePackage($mds, $profil));
		$this->setButtonLabelArray();
		$this->setFormValuesArray($md_values);
		if ($mds == 10 && $profil == -1 && $package == 1) {
			$md_id_start = 4752;
		}
		if ($mds == 0 && $profil == -1 && $package == 1) {
			$md_id_start = 1;
		}
		$this->recno = $recno;
		$this->mds = $mds;
		$this->profil = $profil;
		$this->package = $package;
		$this->getFormData($this->getMdTree($recno, $mds, $profil, $package, $md_id_start));
		$this->getRepeat(1, 1);
		return $this->form_data;
	}
}
?>
