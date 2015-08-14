<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * Tree for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20140223
 */

class Tree {
	private $TableTreeName     = "tree";
	private $FieldTreeID       = "MD_ID";
	private $FieldTreeIDParent = "PARENT_MD_ID";
	private $FieldTreeLeft     = "MD_LEFT";
	private $FieldTreeRight    = "MD_RIGHT";
	private $FieldTreeLevel    = "MD_LEVEL";
	private $FieldTreeOrder    = "METD_ORDER"; // smazat
	private $FieldTreeElmn     = "EL_ID";
	private $FieldTreeMandt    = "MANDT_CODE";
	private $FieldTreeMin      = "MIN_NB";
	private $FieldTreeMax      = "MAX_NB";
	private $FieldTreeStandard = "MD_STANDARD";
	private $FieldTreeButton   = "BUTTON_EXE";
	private $FieldTreePackage  = "PACKAGE_ID";
	private $FieldTreePath     = "MD_PATH";
	private $FieldTreeIgnore   = "TMP_UPDATE";

	private $TableElName       = "elements";
	private $FieldElId         = "EL_ID";
	private $FieldElName       = "EL_NAME";
	private $FieldElShortName  = "EL_SHORT_NAME";
	private $FieldElFormCode   = "FORM_CODE";
	private $FieldElChoice     = "CHOICE";
	private $FieldElCodeList   = "FROM_CODELIST";
	private $FieldElOnlyValue  = "ONLY_VALUE";
	private $FieldElFormIgnore = "FORM_IGNORE";
	private $FieldElFormPack   = "FORM_PACK";
	private $FieldElMultiLang  = "MULTI_LANG";

	private $TableLabelName    = "label";
	private $FieldLabelText    = "LABEL_TEXT";
	private $FieldLabelHelp    = "LABEL_HELP";
	private $FieldLabelJoin    = "LABEL_JOIN";
	private $FieldLabelLang    = "LANG";
	private $FieldLabelType    = "LABEL_TYPE";


	function _execute_query ($sql)	{
		if (empty($sql)) { 
			return false;
		}
    return _executeSql('select', array($sql), array('all'));
	}

	function _executemany_query ($sql)	{
		if (empty($sql)) {
			return false;
		}
    return _executeSql('select', array($sql), array('all'));
	}

	function _safe_set (&$var_true, $var_false = "")	{
		if (!isset ($var_true))
		{ $var_true = $var_false; }
	}

	function AddRootNode ($Standard=0)
	{
		$sql = "INSERT INTO " . $this->TableTreeName . "(" .
                   $this->FieldTreeID . ", " .
                   $this->FieldTreeIDParent . ", " .
                   $this->FieldTreeLeft . ", " .
                   $this->FieldTreeRight . ", " .
                   $this->FieldTreeLevel . ", " .
                   $this->FieldTreeElmn . ", " .
                   $this->FieldTreeMandt . ", " .
                   $this->FieldTreeMin . ", " .
                   $this->FieldTreeMax . ", " .
                   $this->FieldTreeStandard . ", " .
                   $this->FieldTreeIgnore  . ", " .
                   $this->FieldTreeCode . ") " .
					  " VALUES (0, 0, 1, 2, 0, 0, 'M',0,null,$Standard,0,null)";
		$this->_execute_query ($sql);
		return 10;
	}

	function AddNode ($IDNew,$IDParent,$Standard,$Elmn_ID,$Mandt_Code,$Min_NB,$Max_NB,$button_exe,$package_id,$Order = -1) {
		$sql_select = "SELECT ". $this->FieldTreeID . ", " .
                             $this->FieldTreeIDParent . ", " .
                             $this->FieldTreeLeft . ", " .
                             $this->FieldTreeRight . ", " .
                             $this->FieldTreeLevel . ", " .
                             $this->FieldTreePath .
                  " FROM " . $this->TableTreeName .
                  " WHERE " . $this->FieldTreeID . " = " . $IDParent .
                  " AND " . $this->FieldTreeStandard . " = " . $Standard;
		$rs_select = $this->_execute_query ($sql_select);
		if (($rs_select) && ($row_select = $rs_select))
		{
			$this->_safe_set ($row_select[$this->FieldTreeID], -1);
			$this->_safe_set ($row_select[$this->FieldTreeLeft], -1);
			$this->_safe_set ($row_select[$this->FieldTreeRight], -1);
			$this->_safe_set ($row_select[$this->FieldTreeLevel], -1);
			$left = $row_select[$this->FieldTreeLeft] + 1;
			$md_path = $row_select[$this->FieldTreePath];
			$sql_order ="SELECT ". $this->FieldTreeID . ", " .
                             $this->FieldTreeIDParent . ", " .
                             $this->FieldTreeLeft . ", " .
                             $this->FieldTreeRight . ", " .
                             $this->FieldTreeLevel . ", " .
                             $this->FieldTreeElmn . ", " .
                             $this->FieldTreeMandt . ", " .
                             $this->FieldTreeMin . ", " .
                             $this->FieldTreeMax .
             " FROM " . $this->TableTreeName .
						 " WHERE " . $this->FieldTreeIDParent . " = " . $IDParent .
             " AND " . $this->FieldTreeStandard . " = " . $Standard .
						 " ORDER BY " . $this->FieldTreeLeft . " DESC ";
			$rs_order = $this->_execute_query ($sql_order);
  		if (($rs_order) && ($row_order = $rs_order))
			{
				$this->_safe_set ($row_order[$this->FieldTreeRight], -1);
				$left = $row_order[$this->FieldTreeRight] + 1;
			}
			$right = $left + 1;
			$sql_update = "UPDATE " . $this->TableTreeName .
						  " SET " . $this->FieldTreeLeft . " = " . $this->FieldTreeLeft . " + 2" .
						  " WHERE " . $this->FieldTreeLeft . " >= " . $left .
              " AND " . $this->FieldTreeStandard . " = " . $Standard;
			$this->_execute_query ($sql_update);
			$sql_update = "UPDATE " . $this->TableTreeName .
						  " SET " . $this->FieldTreeRight . " = " . $this->FieldTreeRight . " + 2" .
						  " WHERE " . $this->FieldTreeRight . " >= " . $left .
              " AND " . $this->FieldTreeStandard . " = " . $Standard;
			$this->_execute_query ($sql_update);
      if ($IDNew == -1) {
        $sql="SELECT MAX(".$this->FieldTreeID.") AS id FROM " . $this->TableTreeName .
                " WHERE " . $this->FieldTreeStandard . " = " . $Standard;
        $result = $this->_execute_query ($sql);
  			$IDNew = $result['ID'] + 1;
      }
			$md_path .= $IDNew . '_0_';
//			$md_path = '';
	   	$sql_insert = "INSERT INTO " . $this->TableTreeName .
						  " (" . $this->FieldTreeID . ", " .
                     $this->FieldTreeIDParent . ", " .
                     $this->FieldTreeLeft . ", " .
                     $this->FieldTreeRight . ", " .
                     $this->FieldTreeLevel . ", " .
                     $this->FieldTreeMandt . ", " .
                     $this->FieldTreeMin . ", " .
                     $this->FieldTreeMax . ", " .
                	   $this->FieldTreeButton . ", " .
	                   $this->FieldTreePackage . ", " .
	                   $this->FieldTreePath . ", " .
                     $this->FieldTreeStandard . ", " .
                     $this->FieldTreeIgnore  . ", " .
                     $this->FieldTreeElmn . ") " .
						  " VALUES (" . $IDNew . ", " .
                            $IDParent . ", " .
                            $left . ", " .
                            $right . ", " .
                           ($row_select[$this->FieldTreeLevel] + 1) . ", " .
                            $Order . ", '" .
                            $Mandt_Code . "', " .
                            $Min_NB . ", " .
                            $Max_NB . ",'" .
                            $button_exe . "', " .
                            $package_id . ",'" .
                            $md_path . "', " .
                            $Standard . ", " .
                            0 . ", " .
                            $Elmn_ID . ")";
			$this->_execute_query ($sql_insert);
			return $IDNew;
		}
		else {
      return false;
    }
	}

	function AddNodeDown($IDNew,$IDParent,$Standard,$Elmn_ID,$Mandt_Code,$Min_NB,$Max_NB,$button_exe,$package_id) {
		$sql_select = "SELECT ". $this->FieldTreeID . ", " .
                             $this->FieldTreeIDParent . ", " .
                             $this->FieldTreeLeft . ", " .
                             $this->FieldTreeRight . ", " .
                             $this->FieldTreeLevel . ", " .
                             $this->FieldTreePath .
                  " FROM " . $this->TableTreeName .
                  " WHERE " . $this->FieldTreeID . " = " . $IDParent .
                  " AND " . $this->FieldTreeStandard . " = " . $Standard;
		$rs_select = $this->_execute_query ($sql_select);
		if (($rs_select) && ($row_select = $rs_select))
		{
			$this->_safe_set ($row_select[$this->FieldTreeID], -1);
			$this->_safe_set ($row_select[$this->FieldTreeLeft], -1);
			$this->_safe_set ($row_select[$this->FieldTreeRight], -1);
			$this->_safe_set ($row_select[$this->FieldTreeLevel], -1);
			$left  = $row_select[$this->FieldTreeLeft];
			$right = $row_select[$this->FieldTreeRight];
			$level = $row_select[$this->FieldTreeLevel];
			$md_path = $row_select[$this->FieldTreePath];
			$left  = $right;
			$right = $right + 1;
			$sql_update = "UPDATE " . $this->TableTreeName .
						  " SET " . $this->FieldTreeLeft . " = " . $this->FieldTreeLeft . " + 2" .
						  " WHERE " . $this->FieldTreeLeft . " >= " . $left .
              " AND " . $this->FieldTreeStandard . " = " . $Standard;
			$this->_execute_query ($sql_update);
			$sql_update = "UPDATE " . $this->TableTreeName .
						  " SET " . $this->FieldTreeRight . " = " . $this->FieldTreeRight . " + 2" .
						  " WHERE " . $this->FieldTreeRight . " >= " . $left .
              " AND " . $this->FieldTreeStandard . " = " . $Standard;
			$this->_execute_query ($sql_update);
/*
			$sql_update = "UPDATE " . $this->TableTreeName .
						  " SET " . $this->FieldTreeRight . " = " . $this->FieldTreeRight . " + 2" .
              " WHERE " . $this->FieldTreeID . " = " . $IDParent .
              " AND " . $this->FieldTreeStandard . " = " . $Standard;
			$this->_execute_query ($sql_update);
*/
      if ($IDNew == -1) {
        $sql="SELECT MAX(".$this->FieldTreeID.") AS id FROM " . $this->TableTreeName .
                " WHERE " . $this->FieldTreeStandard . " = " . $Standard;
        $result = $this->_execute_query ($sql);
  			$IDNew = $result['ID'] + 1;
      }
			$level = $level + 1;
			$md_path .= $IDNew . '_0_';
//			$md_path = '';
	   	$sql_insert = "INSERT INTO " . $this->TableTreeName .
						  " (" . $this->FieldTreeID . ", " .
                     $this->FieldTreeIDParent . ", " .
                     $this->FieldTreeLeft . ", " .
                     $this->FieldTreeRight . ", " .
                     $this->FieldTreeLevel . ", " .
                     $this->FieldTreeMandt . ", " .
                     $this->FieldTreeMin . ", " .
                     $this->FieldTreeMax . ", " .
                	   $this->FieldTreeButton . ", " .
	                   $this->FieldTreePackage . ", " .
	                   $this->FieldTreePath . ", " .
                     $this->FieldTreeStandard . ", " .
                     $this->FieldTreeIgnore  . ", " .
                     $this->FieldTreeElmn . ") " .
						  " VALUES (" . $IDNew . ", " .
                            $IDParent . ", " .
                            $left . ", " .
                            $right . ", " .
                            $level . ", " .
                            $Mandt_Code . "', " .
                            $Min_NB . ", " .
                            $Max_NB . ",'" .
                            $button_exe . "', " .
                            $package_id . ",'" .
                            $md_path . "', " .
                            $Standard . ", " .
                            0 . ", " .
                            $Elmn_ID . ")";
			$this->_execute_query ($sql_insert);
			return $IDNew;
		}
		else {
      return false;
    }
	}

	function EditNode($md_id,$mds,$el_id,$mandt_code,$min_nb,$max_nb,$button_exe,$package_id) {
		$sql = "UPDATE " . $this->TableTreeName . " SET " .
               $this->FieldTreeElmn .    "=$el_id, " .
               $this->FieldTreeMandt .   "='$mandt_code', " .
               $this->FieldTreeMin .     "=$min_nb, " .
               $this->FieldTreeMax .     "=$max_nb, " .
          	   $this->FieldTreeButton .  "='$button_exe', " .
	             $this->FieldTreePackage . "=$package_id"  .
					  " WHERE " . $this->FieldTreeStandard . "=$mds AND " . $this->FieldTreeID . "=$md_id";
		$this->_execute_query ($sql);
		return;
	}

	function SelectSubNode($APLang,$IDNode,$mds = 0) {
  		$sql_result = "SELECT " . $this->TableTreeName  . "." . $this->FieldTreeID . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeIDParent . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeLeft . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeRight . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeLevel . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeElmn . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMandt . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMin . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMax . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeStandard . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeButton . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreePackage . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreePath . ", " .
                                $this->TableElName    . "." . $this->FieldElId . ", " .
                                $this->TableElName    . "." . $this->FieldElName . ", " .
                                $this->TableElName    . "." . $this->FieldElShortName . ", " .
                                $this->TableElName    . "." . $this->FieldElFormCode . ", " .
                                $this->TableElName    . "." . $this->FieldElChoice . ", " .
                                $this->TableElName    . "." . $this->FieldElCodeList . ", " .
                                $this->TableElName    . "." . $this->FieldElOnlyValue . ", " .
                                $this->TableElName    . "." . $this->FieldElFormIgnore . ", " .
                                $this->TableElName    . "." . $this->FieldElFormPack . ", " .
                                $this->TableElName    . "." . $this->FieldElMultiLang . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelText . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelHelp . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelJoin .
                " FROM ($this->TableLabelName INNER JOIN $this->TableElName ON $this->TableLabelName" . "." . "$this->FieldLabelJoin = $this->TableElName" . "." . "$this->FieldElId) RIGHT JOIN $this->TableTreeName ON $this->TableElName" . "." . "$this->FieldElId = $this->TableTreeName" . "." . "$this->FieldTreeElmn" .
                " WHERE " .     $this->TableTreeName . "." . $this->FieldTreeID . "=" . $IDNode .
                " AND " .       $this->TableTreeName . "." . $this->FieldTreeStandard . " = " . $mds .
                " AND " .       $this->TableLabelName . "." . $this->FieldLabelLang . " = '" . $APLang . "'" .
                " AND " .       $this->TableLabelName . "." . $this->FieldLabelType . " = 'EL'" .
							  " ORDER BY " .  $this->TableTreeName . "." . $this->FieldTreeLeft .
                " LIMIT 1";
//							  " ORDER BY " .  $this->TableTreeName . "." . $this->FieldTreeLevel . "," . $this->TableTreeName . "." . $this->FieldTreeOrder .
			return $this->_executemany_query ($sql_result);
	}

	function SelectSubNodes($APLang,$IDNode = -1,$Level = -1,$mds = 0) {
		setMickaLog("lang=$APLang, node=$IDNode, level=$Level, mds=$mds", 'DEBUG', 'Tree.php (SelectSubNodes)');
		$sql_select = "SELECT ". $this->FieldTreeID . ", " .
                             $this->FieldTreeIDParent . ", " .
                             $this->FieldTreeLeft . ", " .
                             $this->FieldTreeRight . ", " .
                             $this->FieldTreeLevel . ", " .
                             $this->FieldTreeElmn . ", " .
                             $this->FieldTreeMandt . ", " .
                             $this->FieldTreeMin . ", " .
                             $this->FieldTreeMax .
                  " FROM " . $this->TableTreeName .
                  " WHERE " . $this->FieldTreeID . "=" . $IDNode .
                  " AND " . $this->FieldTreeStandard . "=" . $mds;
		$rs_select = $this->_execute_query ($sql_select);
		if (($rs_select) && ($row_select = $rs_select))
		{
			$this->_safe_set ($row_select[0][$this->FieldTreeID], -1);
			$this->_safe_set ($row_select[0][$this->FieldTreeLeft], -1);
			$this->_safe_set ($row_select[0][$this->FieldTreeRight], -1);
			$this->_safe_set ($row_select[0][$this->FieldTreeLevel], -1);
			if ($Level == -1)
			{
  		$sql_result = "SELECT " . $this->TableTreeName  . "." . $this->FieldTreeID . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeIDParent . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeLeft . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeRight . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeLevel . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeElmn . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMandt . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMin . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMax . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeStandard . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeButton . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreePackage . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreePath . ", " .
                                $this->TableElName    . "." . $this->FieldElId . ", " .
                                $this->TableElName    . "." . $this->FieldElName . ", " .
                                $this->TableElName    . "." . $this->FieldElShortName . ", " .
                                $this->TableElName    . "." . $this->FieldElFormCode . ", " .
                                $this->TableElName    . "." . $this->FieldElChoice . ", " .
                                $this->TableElName    . "." . $this->FieldElCodeList . ", " .
                                $this->TableElName    . "." . $this->FieldElOnlyValue . ", " .
                                $this->TableElName    . "." . $this->FieldElFormIgnore . ", " .
                                $this->TableElName    . "." . $this->FieldElFormPack . ", " .
                                $this->TableElName    . "." . $this->FieldElMultiLang . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelText . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelHelp . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelJoin .
                " FROM ($this->TableLabelName INNER JOIN $this->TableElName ON $this->TableLabelName" . "." . "$this->FieldLabelJoin = $this->TableElName" . "." . "$this->FieldElId) RIGHT JOIN $this->TableTreeName ON $this->TableElName" . "." . "$this->FieldElId = $this->TableTreeName" . "." . "$this->FieldTreeElmn" .
							  " WHERE " .     $this->TableTreeName . "." . $this->FieldTreeLeft . " > " .  $row_select[0][$this->FieldTreeLeft] .
							  " AND " .       $this->TableTreeName . "." . $this->FieldTreeRight . " < " . $row_select[0][$this->FieldTreeRight] .
                " AND " .       $this->TableTreeName . "." . $this->FieldTreeStandard . " = " . $mds .
                " AND " .       $this->TableLabelName . "." . $this->FieldLabelLang . " = '" . $APLang . "'" .
                " AND " .       $this->TableLabelName . "." . $this->FieldLabelType . " = 'EL'" .
							  " ORDER BY " .  $this->TableTreeName . "." . $this->FieldTreeLeft;
//							  " ORDER BY " .  $this->TableTreeName . "." . $this->FieldTreeLevel . "," . $this->TableTreeName . "." . $this->FieldTreeOrder;
			}
			else
			{
  		$sql_result = "SELECT " . $this->TableTreeName  . "." . $this->FieldTreeID . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeIDParent . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeLeft . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeRight . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeLevel . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeElmn . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMandt . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMin . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMax . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeStandard . ", " .
                                $this->TableElName    . "." . $this->FieldElId . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreePackage . ", " .
                                $this->TableElName    . "." . $this->FieldElName . ", " .
                                $this->TableElName    . "." . $this->FieldElShortName . ", " .
                                $this->TableElName    . "." . $this->FieldElFormCode . ", " .
                                $this->TableElName    . "." . $this->FieldElChoice . ", " .
                                $this->TableElName    . "." . $this->FieldElCodeList . ", " .
                                $this->TableElName    . "." . $this->FieldElOnlyValue . ", " .
                                $this->TableElName    . "." . $this->FieldElFormIgnore . ", " .
                                $this->TableElName    . "." . $this->FieldElFormPack . ", " .
                                $this->TableElName    . "." . $this->FieldElMultiLang . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelText . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelHelp . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelJoin .
                " FROM ($this->TableLabelName INNER JOIN $this->TableElName ON $this->TableLabelName" . "." . "$this->FieldLabelJoin = $this->TableElName" . "." . "$this->FieldElId) RIGHT JOIN $this->TableTreeName ON $this->TableElName" . "." . "$this->FieldElId = $this->TableTreeName" . "." . "$this->FieldTreeElmn" .
							  " WHERE " .     $this->TableTreeName . "." . $this->FieldTreeLeft . " > " .  $row_select[0][$this->FieldTreeLeft] .
							  " AND " .       $this->TableTreeName . "." . $this->FieldTreeRight . " < " . $row_select[0][$this->FieldTreeRight] .
							  " AND " .       $this->TableTreeName . "." . $this->FieldTreeLevel . " <= " . ($Level + $row_select[0][$this->FieldTreeLevel]) .
                " AND " .       $this->TableTreeName . "." . $this->FieldTreeStandard . " = " . $mds .
                " AND " .       $this->TableLabelName . "." . $this->FieldLabelLang . " = '" . $APLang . "'" .
                " AND " .       $this->TableLabelName . "." . $this->FieldLabelType . " = 'EL'" .
							  " ORDER BY " .  $this->TableTreeName . "." . $this->FieldTreeLeft;
//							  " ORDER BY " .  $this->TableTreeName . "." . $this->FieldTreeLevel . "," . $this->TableTreeName . "." . $this->FieldTreeOrder;
			}
			return $this->_executemany_query ($sql_result);
		}
		else
		{ return false; }
	}

	function SelectPathNodes($APLang,$IDNode = -1,$mds = 0) {
		setMickaLog("lang=$APLang, node=$IDNode, mds=$mds", 'DEBUG', 'Tree.php (SelectPathNodes)');
		$sql_select = "SELECT ". $this->FieldTreeID . ", " .
                             $this->FieldTreeIDParent . ", " .
                             $this->FieldTreeLeft . ", " .
                             $this->FieldTreeRight . ", " .
                             $this->FieldTreeLevel . ", " .
                             $this->FieldTreeElmn . ", " .
                             $this->FieldTreeMandt . ", " .
                             $this->FieldTreeMin . ", " .
                             $this->FieldTreeMax .
                  " FROM " . $this->TableTreeName .
                  " WHERE " . $this->FieldTreeID . "=" . $IDNode .
                  " AND " . $this->FieldTreeStandard . "=" . $mds;
		$rs_select = $this->_execute_query($sql_select);
		if (($rs_select) && ($row_select = $rs_select))
		{
			$this->_safe_set ($row_select[0][$this->FieldTreeID], -1);
			$this->_safe_set ($row_select[0][$this->FieldTreeLeft], -1);
			$this->_safe_set ($row_select[0][$this->FieldTreeRight], -1);
			$this->_safe_set ($row_select[0][$this->FieldTreeLevel], -1);
  		$sql_result = "SELECT " . $this->TableTreeName  . "." . $this->FieldTreeID . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeIDParent . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeLeft . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeRight . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeLevel . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeElmn . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMandt . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMin . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeMax . ", " .
                                $this->TableTreeName  . "." . $this->FieldTreeStandard . ", " .
                                $this->TableElName    . "." . $this->FieldElId . ", " .
                                $this->TableElName    . "." . $this->FieldElName . ", " .
                                $this->TableElName    . "." . $this->FieldElShortName . ", " .
                                $this->TableElName    . "." . $this->FieldElFormCode . ", " .
                                $this->TableElName    . "." . $this->FieldElChoice . ", " .
                                $this->TableElName    . "." . $this->FieldElCodeList . ", " .
                                $this->TableElName    . "." . $this->FieldElOnlyValue . ", " .
                                $this->TableElName    . "." . $this->FieldElFormIgnore . ", " .
                                $this->TableElName    . "." . $this->FieldElFormPack . ", " .
                                $this->TableElName    . "." . $this->FieldElMultiLang . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelText . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelHelp . ", " .
                                $this->TableLabelName . "." . $this->FieldLabelJoin .
                " FROM ($this->TableLabelName INNER JOIN $this->TableElName ON $this->TableLabelName" . "." . "$this->FieldLabelJoin = $this->TableElName" . "." . "$this->FieldElId) RIGHT JOIN $this->TableTreeName ON $this->TableElName" . "." . "$this->FieldElId = $this->TableTreeName" . "." . "$this->FieldTreeElmn" .
							  " WHERE " .     $this->TableTreeName . "." . $this->FieldTreeLeft . " <= " .  $row_select[0][$this->FieldTreeLeft] .
							  " AND " .       $this->TableTreeName . "." . $this->FieldTreeRight . " >= " . $row_select[0][$this->FieldTreeRight] .
                " AND " .       $this->TableTreeName . "." . $this->FieldTreeStandard . " = " . $mds .
                " AND " .       $this->TableLabelName . "." . $this->FieldLabelLang . " = '" . $APLang . "'" .
                " AND " .       $this->TableLabelName . "." . $this->FieldLabelType . " = 'EL'" .
							  " ORDER BY " .  $this->TableTreeName . "." . $this->FieldTreeLevel . "," . $this->TableTreeName . "." . $this->FieldTreeLeft;
			return $this->_executemany_query($sql_result);
		}
		else {
      return false;
    }
	}

	function DeleteNode ($IDNode = -1, $mds = 0) {
		$sql_select = "SELECT " . $this->FieldTreeIDParent . " FROM " . $this->TableTreeName .
                  " WHERE " . $this->FieldTreeID . " = " . $IDNode .
		              " AND " . $this->FieldTreeStandard . " = " . $mds;
    $rs_select = $this->_execute_query ($sql_select);
  	$id_par  = $rs_select[$this->FieldTreeIDParent];

		$sql_select = "SELECT * FROM " . $this->TableTreeName .
                  " WHERE " . $this->FieldTreeID . " = " . $IDNode .
		              " AND " . $this->FieldTreeStandard . " = " . $mds;
    $rs_select = $this->_execute_query ($sql_select);
		if (($rs_select) && ($row_select = $rs_select))
		{
			$this->_safe_set ($row_select[$this->FieldTreeID], -1);
			$this->_safe_set ($row_select[$this->FieldTreeLeft], -1);
			$this->_safe_set ($row_select[$this->FieldTreeRight], -1);
			$delete_offset = $row_select[$this->FieldTreeRight] - $row_select[$this->FieldTreeLeft];

			$sql_delete = "DELETE FROM " . $this->TableTreeName .
						  " WHERE " . $this->FieldTreeLeft . " >= " . $row_select[$this->FieldTreeLeft] .
						  " AND " . $this->FieldTreeLeft . " <= " . $row_select[$this->FieldTreeRight] .
              " AND " . $this->FieldTreeStandard . " = " . $mds;
			$this->_execute_query ($sql_delete);

			$sql_update = "UPDATE " . $this->TableTreeName .
						  " SET " . $this->FieldTreeLeft . " = " . $this->FieldTreeLeft . " - " .  ($delete_offset + 1) .
						  " WHERE " . $this->FieldTreeLeft . " > " . $row_select[$this->FieldTreeRight] .
              " AND " . $this->FieldTreeStandard . " = " . $mds;
			$this->_execute_query ($sql_update);

			$sql_update = "UPDATE " . $this->TableTreeName .
						  " SET " . $this->FieldTreeRight . " = " . $this->FieldTreeRight . " - " .  ($delete_offset + 1) .
						  " WHERE " . $this->FieldTreeRight . " > " . $row_select[$this->FieldTreeRight] .
              " AND " . $this->FieldTreeStandard . " = " . $mds;
			$this->_execute_query ($sql_update);

			return true;
		}
		else
		{ return false; }
	}

	// TODO upravit ORDER
	function MoveNode ($mds, $IDNode = -1, $IDParent = -1, $Order = -1)	{
		$sql_select = "SELECT * FROM " . $this->TableTreeName .
		              " WHERE " . $this->FieldTreeID . " = " . $IDNode .
                  " AND " . $this->FieldTreeStandard . " = " . $mds;

		$rs_select = $this->_execute_query ($sql_select);
		if (($rs_select) && ($row_select = $rs_select)) {
			$this->_safe_set ($row_select[$this->FieldTreeID], -1);
			$this->_safe_set ($row_select[$this->FieldTreeLeft], -1);
			$this->_safe_set ($row_select[$this->FieldTreeRight], -1);
			$this->_safe_set ($row_select[$this->FieldTreeLevel], -1);
			$delete_offset = $row_select[$this->FieldTreeRight] - $row_select[$this->FieldTreeLeft];


			$sql_select_parent = "SELECT * FROM " . $this->TableTreeName .
			                     " WHERE " . $this->FieldTreeID . " = " . $IDParent .
                           " AND " . $this->FieldTreeStandard . " = " . $mds;

			$rs_select_parent = $this->_execute_query ($sql_select_parent);
			if (($rs_select_parent) && ($row_select_parent = $rs_select_parent)) {
				$this->_safe_set ($row_select_parent[$this->FieldTreeID], -1);
				$this->_safe_set ($row_select_parent[$this->FieldTreeLeft], -1);
				$this->_safe_set ($row_select_parent[$this->FieldTreeRight], -1);
				$this->_safe_set ($row_select_parent[$this->FieldTreeLevel], -1);

				$left = $row_select_parent[$this->FieldTreeLeft] + 1;

				//Set node tree as ignore
				$sql_ignore = "UPDATE " . $this->TableTreeName .
							  " SET " . $this->FieldTreeIgnore . " = 1" .
							  " WHERE " . $this->FieldTreeLeft . " >= " . $row_select[$this->FieldTreeLeft] .
							  " AND " . $this->FieldTreeRight . " <= " . $row_select[$this->FieldTreeRight] .
                " AND " . $this->FieldTreeStandard . " = " . $mds;
				$this->_execute_query ($sql_ignore);

				// Update Order (set order = order +1 where order>$Order)
				if ($Order == -1) {
					$sql_order = "SELECT * FROM " . $this->TableTreeName .
								 " WHERE " . $this->FieldTreeIDParent . " = " . $IDParent .
                 " AND " . $this->FieldTreeStandard . " = " . $mds .
								 " ORDER BY " . $this->FieldTreeLeft . " DESC " .
								 " LIMIT 1";
					$rs_order = $this->_execute_query ($sql_order);
					if (($rs_order) && ($row_order = $rs_order)) {
						$this->_safe_set ($row_order[$this->FieldTreeOrder], 0);
						$Order = $row_order[$this->FieldTreeOrder] + 1;
					}
					else {
						$Order = 1;
					}
				}

				$sql_update = "UPDATE " . $this->TableTreeName .
							  " SET " . $this->FieldTreeOrder . " = " . $this->FieldTreeOrder . " + 1" .
							  " WHERE " . $this->FieldTreeIDParent . " = " . $IDParent .
							  " AND " . $this->FieldTreeOrder . " >= " . $Order .
                " AND " . $this->FieldTreeStandard . " = " . $mds;
				$this->_execute_query ($sql_update);

				$sql_order = "SELECT * FROM " . $this->TableTreeName .
							 " WHERE " . $this->FieldTreeIDParent . " = " . $IDParent .
							 " AND " . $this->FieldTreeOrder  . " <= " . $Order .
               " AND " . $this->FieldTreeStandard . " = " . $mds .
							 " ORDER BY " . $this->FieldTreeOrder . " DESC " .
							 " LIMIT 1";
				$rs_order = $this->_execute_query ($sql_order);
				if (($rs_order) && ($row_order = $rs_order))
				{
					$this->_safe_set ($row_order[$this->FieldTreeRight], -1);
					$left = $row_order[$this->FieldTreeRight] + 1;
				}

				$child_offset = $row_select[$this->FieldTreeRight] - $row_select[$this->FieldTreeLeft] + 1;

				// Update FieldTreeLeft
				if ($left < $row_select[$this->FieldTreeLeft]) { // Move to left
					$sql_update = "UPDATE " . $this->TableTreeName .
								  " SET " . $this->FieldTreeLeft . " = " . $this->FieldTreeLeft . " + (" . $child_offset . ")" .
								  " WHERE " . $this->FieldTreeLeft . " >= " . $left .
								  " AND " . $this->FieldTreeLeft . " <= " . $row_select[$this->FieldTreeLeft] .
								  " AND " . $this->FieldTreeIgnore . " = 0" .
                  " AND " . $this->FieldTreeStandard . " = " . $mds;
				}
				else { // Move to right
					$sql_update = "UPDATE " . $this->TableTreeName .
								  " SET " . $this->FieldTreeLeft . " = " . $this->FieldTreeLeft . " - " . $child_offset .
								  " WHERE " . $this->FieldTreeLeft . " <= " . $left .
								  " AND " . $this->FieldTreeLeft . " >= " . $row_select[$this->FieldTreeLeft] .
								  " AND " . $this->FieldTreeIgnore . " = 0" .
                  " AND " . $this->FieldTreeStandard . " = " . $mds;
				}
				$this->_execute_query ($sql_update);

				// Update FieldTreeRight
				if ($left < $row_select[$this->FieldTreeLeft]) // Move to left
				{
					$sql_update = "UPDATE " . $this->TableTreeName .
								  " SET " . $this->FieldTreeRight . " = " . $this->FieldTreeRight . " + (" . $child_offset . ")" .
								  " WHERE " . $this->FieldTreeRight . " >= " . $left .
								  " AND " . $this->FieldTreeRight . " <= " . $row_select[$this->FieldTreeRight] .
								  " AND " . $this->FieldTreeIgnore . " = 0" .
                  " AND " . $this->FieldTreeStandard . " = " . $mds;
				}
				else // Move to right
				{
					$sql_update = "UPDATE " . $this->TableTreeName .
								  " SET " . $this->FieldTreeRight . " = " . $this->FieldTreeRight . " - " . $child_offset .
								  " WHERE " . $this->FieldTreeRight . " < " . $left .
								  " AND " . $this->FieldTreeRight . " >= " . $row_select[$this->FieldTreeRight] .
								  " AND " . $this->FieldTreeIgnore . " = 0" .
                  " AND " . $this->FieldTreeStandard . " = " . $mds;
				}
				$this->_execute_query ($sql_update);

				$level_difference = $row_select_parent[$this->FieldTreeLevel] - $row_select[$this->FieldTreeLevel] + 1;
				$new_offset = $row_select[$this->FieldTreeLeft] - $left;
                if ($left > $row_select[$this->FieldTreeLeft]) // i.e. move to right
                { $new_offset += $child_offset; }

				//Update new tree left
				$sql_update = "UPDATE " . $this->TableTreeName .
							  " SET " . $this->FieldTreeLeft . " = " . $this->FieldTreeLeft . " - (" . $new_offset . "), " .
							  $this->FieldTreeRight . " = " . $this->FieldTreeRight . " - (" . $new_offset . ")," .
							  "$this->FieldTreeLevel = $this->FieldTreeLevel + $level_difference" .
							  " WHERE " . $this->FieldTreeLeft . " >= " . $row_select[$this->FieldTreeLeft] .
							  " AND " . $this->FieldTreeRight . " <= " . $row_select[$this->FieldTreeRight] .
							  " AND " . $this->FieldTreeIgnore . " = 1" .
                " AND " . $this->FieldTreeStandard . " = " . $mds;
				$this->_execute_query ($sql_update);

				//Remove ignore statis from node tree
				$sql_ignore = "UPDATE " . $this->TableTreeName .
							  " SET " . $this->FieldTreeIgnore . " = 0" .
							  " WHERE " . $this->FieldTreeLeft . " >= " . ($row_select[$this->FieldTreeLeft] - $new_offset) .
							  " AND " . $this->FieldTreeRight . " <= " . ($row_select[$this->FieldTreeRight] - $new_offset) .
							  " AND " . $this->FieldTreeIgnore . " = 1" .
                " AND " . $this->FieldTreeStandard . " = " . $mds;
				$this->_execute_query ($sql_ignore);

				//Update insert root FieldTree
				$sql_update = "UPDATE " . $this->TableTreeName . " SET " . $this->FieldTreeIDParent . " = " . $IDParent . ", " .
							  $this->FieldTreeOrder . " = " . $Order . " WHERE " . $this->FieldTreeID . " = " . $IDNode .
                  " AND " . $this->FieldTreeStandard . " = " . $mds;
				$this->_execute_query ($sql_update);

				return true;
			}
			else {
				return false;
			}
			return true;
		}
		else {
			return false;
		}
	}
	function ShiftNode ($mds,$IDNode,$code)	{
    // select parent_md_id
    if ($code == 'd') {
  		$sql_select = "SELECT * FROM " . $this->TableTreeName .
  		              " WHERE " . $this->FieldTreeID . " = " . $IDNode .
                    " AND " . $this->FieldTreeStandard . " = " . $mds;
  		$rs_select = $this->_execute_query ($sql_select);
    	$id_par  = $rs_select[$this->FieldTreeIDParent];
      $order_s = $rs_select[$this->FieldTreeOrder];
  		$left_s  = $rs_select[$this->FieldTreeLeft];
  		$right_s = $rs_select[$this->FieldTreeRight];
      // select 2. md_id
  		$sql_select = "SELECT * FROM " . $this->TableTreeName .
  		              " WHERE " . $this->FieldTreeIDParent . " = " . $id_par .
                    " AND " . $this->FieldTreeStandard . " = " . $mds .
                    " AND " . $this->FieldTreeOrder . " > " . $order_s .
    							  " ORDER BY " . $this->FieldTreeOrder .
                    " LIMIT 1";
  		$rs_select = $this->_execute_query ($sql_select);
      $order_d = $rs_select[$this->FieldTreeOrder];
  		$left_d  = $rs_select[$this->FieldTreeLeft];
  		$right_d = $rs_select[$this->FieldTreeRight];
    }
    if ($code == 'u') {
  		$sql_select = "SELECT * FROM " . $this->TableTreeName .
  		              " WHERE " . $this->FieldTreeID . " = " . $IDNode .
                    " AND " . $this->FieldTreeStandard . " = " . $mds;
  		$rs_select = $this->_execute_query ($sql_select);
    	$id_par  = $rs_select[$this->FieldTreeIDParent];
      $order_d = $rs_select[$this->FieldTreeOrder];
  		$left_d  = $rs_select[$this->FieldTreeLeft];
  		$right_d = $rs_select[$this->FieldTreeRight];
      // select 2. md_id
  		$sql_select = "SELECT * FROM " . $this->TableTreeName .
  		              " WHERE " . $this->FieldTreeIDParent . " = " . $id_par .
                    " AND " . $this->FieldTreeStandard . " = " . $mds .
                    " AND " . $this->FieldTreeOrder . " = " . $order_d . "-1 " .
    							  " ORDER BY " . $this->FieldTreeOrder .
                    " LIMIT 1";
  		$rs_select = $this->_execute_query ($sql_select);
      $order_s = $rs_select[$this->FieldTreeOrder];
  		$left_s  = $rs_select[$this->FieldTreeLeft];
  		$right_s = $rs_select[$this->FieldTreeRight];
    }

		$sql_ignore = "UPDATE " . $this->TableTreeName .
  							  " SET " . $this->FieldTreeIgnore . " = 0" .
			       		  " WHERE " . $this->FieldTreeIgnore . " = 1";
		$this->_execute_query ($sql_ignore);
		$sql_ignore = "UPDATE " . $this->TableTreeName .
			       		  " SET " . $this->FieldTreeIgnore . " = 1" .
					        " WHERE " . $this->FieldTreeLeft . " >= " . $left_s .
					        " AND " . $this->FieldTreeRight . " <= " . $right_s .
                  " AND " . $this->FieldTreeStandard . " = " . $mds;
		$this->_execute_query ($sql_ignore);

		$sql_update = "UPDATE " . $this->TableTreeName .
								  " SET " . $this->FieldTreeOrder . " = " . $this->FieldTreeOrder . "-1 " .
					        " WHERE " . $this->FieldTreeLeft . " = " . $left_d .
					        " AND " . $this->FieldTreeRight . " = " . $right_d .
                  " AND " . $this->FieldTreeStandard . " = " . $mds;
		$this->_execute_query ($sql_update);
    $dif = $left_d - $left_s;
		$sql_update = "UPDATE " . $this->TableTreeName .
								  " SET " . $this->FieldTreeLeft . " = " . $this->FieldTreeLeft . " - " . $dif . ", " .
								            $this->FieldTreeRight . " = " . $this->FieldTreeRight . " - " . $dif .
					        " WHERE " . $this->FieldTreeLeft . " >= " . $left_d .
					        " AND " . $this->FieldTreeRight . " <= " . $right_d .
                  " AND " . $this->FieldTreeStandard . " = " . $mds;
		$this->_execute_query ($sql_update);

		$sql_update = "UPDATE " . $this->TableTreeName .
								  " SET " . $this->FieldTreeOrder . " = " . $this->FieldTreeOrder . "+1 " .
		              " WHERE " . $this->FieldTreeID . " = " . $IDNode .
                  " AND " . $this->FieldTreeStandard . " = " . $mds;
		$this->_execute_query ($sql_update);
    $dif = ($right_d - $dif) + 1;
    $dif = $dif - $left_s;
		$sql_update = "UPDATE " . $this->TableTreeName .
								  " SET " . $this->FieldTreeLeft . " = " . $this->FieldTreeLeft . " + " . $dif . ", " .
								            $this->FieldTreeRight . " = " . $this->FieldTreeRight . " + " . $dif .
			       		  " WHERE " . $this->FieldTreeIgnore . " = 1";
		$this->_execute_query ($sql_update);

		$sql_ignore = "UPDATE " . $this->TableTreeName .
  							  " SET " . $this->FieldTreeIgnore . " = 0" .
			       		  " WHERE " . $this->FieldTreeIgnore . " = 1";
		$this->_execute_query ($sql_ignore);
		$this->sort_order($id_par,$mds);
	}

	public function getLabelNode($IDParent, $mds=0) {
		setMickaLog("parent=$IDParent, mds=$mds", 'DEBUG', 'Tree.php (getLabelNode)');
		$rs = array();
		if ($mds == 10) {
			$mds = 0;
		}
		$rs_node = $this->SelectPathNodes(MICKA_LANG, $IDParent, $mds);
		if ($rs_node)	{
			foreach ($rs_node as $row) {
				$pom['md_id'] = $row["MD_ID"];
				$pom['label'] = $row["EL_NAME"];
				array_push($rs, $pom);
			}
		}
		return $rs;
	}

	public function getListNodes($IDParent, $mds=0) {
		setMickaLog("parent=$IDParent, mds=$mds", 'DEBUG', 'Tree.php (getListNodes)');
		$rs = array();
		if ($mds == 10) {
			$mds = 0;
		}
		$rs_nodes = $this->SelectSubNodes(MICKA_LANG, $IDParent, 1, $mds);
		if ($rs_nodes)	{
			foreach ($rs_nodes as $row) {
				$pom['md_id']       = $row["MD_ID"];
				$pom['metd_order']  = $row["METD_ORDER"];
				$pom['md_left']     = $row["MD_LEFT"];
				$pom['md_right']    = $row["MD_RIGHT"];
				$pom['el_name']     = $row["EL_NAME"];
				$pom['label']       = $row["LABEL_TEXT"];
				$pom['mandt_code']  = $row["MANDT_CODE"];
				$pom['min_nb']      = $row["MIN_NB"];
				$pom['max_nb']      = $row["MAX_NB"];
				$pom['form_code']   = $row["FORM_CODE"];
				$pom['choice']      = $row["CHOICE"];
				$pom['codelist']    = $row["FROM_CODELIST"];
				$pom['only_val']    = $row["ONLY_VALUE"];
				$pom['form_ignore'] = $row["FORM_IGNORE"];
				$pom['form_pack']   = $row["FORM_PACK"];
				$pom['multi_lang']  = $row["MULTI_LANG"];
				$pom['package_id']  = $row["PACKAGE_ID"];
				array_push($rs, $pom);
			}
		}
		return $rs;
	}

	public function getProfilPath($mds=0) {
		setMickaLog("mds=$mds", 'DEBUG', 'Tree.php (getProfilPath)');
		$sql = array();
		$rs = array();
		$prof_edit = 3;
		if ($mds == 10) {
			$prof_edit = $prof_edit+100;
		}
		$sql[] = "
			SELECT profil_id, profil_name
			FROM profil_names
			WHERE md_standard=$mds AND profil_id>$prof_edit AND is_vis=1 AND edit_lite_template IS NULL
			ORDER BY profil_order";
		$result = _executeSql('select', $sql, array('all'));
		foreach ($result as $row) {
			$id = $row['PROFIL_ID'];
			$rs[$id] = $row['PROFIL_NAME'];
		}
		return $rs;
	}

	public function getProfils($mds=0, $copy=FALSE) {
		setMickaLog("mds=$mds", 'DEBUG', 'Tree.php (getProfils)');
		$sql = array();
		$rs = array();
		array_push($sql, "SELECT * FROM profil_names WHERE md_standard=%i AND profil_id NOT IN (0,100)", $mds);
		if ($copy) {
			array_push($sql, "AND edit_lite_template IS NULL");
		}
		array_push($sql, "ORDER BY profil_order");
		if ($copy) {
			$rs[0] = '';
			$result = _executeSql('select', $sql, array('all'));
			foreach ($result as $row) {
				$id = $row['PROFIL_ID'];
				$rs[$id] = $row['PROFIL_NAME'];
			}
			return $rs;
		}
		else {
			return _executeSql('select', $sql, array('all'));
		}
	}

	function deleteMDprofilPath($profily) {
		setMickaLog("BEGIN", 'DEBUG', 'Tree.php (deleteMDprofilPath)');
		foreach($profily as $key => $hodnota){
			$del_profil .= $key . ",";
		}
		if (strlen($del_profil) > 1) {
			if ($del_profil{strlen($del_profil)-1} == ',') {
				$del_profil = substr($del_profil,0,strlen($del_profil)-1);
			}
		}
		return $del_profil;
	}

	function deleteMDidPath($listnodes) {
		setMickaLog("BEGIN", 'DEBUG', 'Tree.php (deleteMDidPath)');
		foreach($listnodes as $key){
			$del_mdid .= $key['md_id'] . ",";
		}
		if (strlen($del_mdid) > 1) {
			if ($del_mdid{strlen($del_mdid)-1} == ',') {
				$del_mdid = substr($del_mdid,0,strlen($del_mdid)-1);
			}
		}
		return $del_mdid;
	}

	function checkProfilSelect($mdid, $profil) {
		setMickaLog("BEGIN", 'DEBUG', 'Tree.php (checkProfilSelect)');
		$sql = array();
		$rs = array();
		$sql[] = "SELECT profil_id,md_id FROM profil WHERE md_id IN ($mdid) AND profil_id IN ($profil)";
		$result = _executeSql('select', $sql, array('all'));
		foreach ($result as $row) {
			$mdid = $row['MD_ID'];
			$profil = $row['PROFIL_ID'];
			$rs[$mdid][$profil] = $row['MD_ID'];
		}
		return $rs;
	}

	function getMdStandardAll($em=0) {
		$rs = array();
		$rs[0] = 'ISO 19115';
		$rs[10] = 'ISO 19119';
		return $rs; // pro editaci profilů nejsou další standardy třeba

		$sql = array();
		$sql[]="SELECT md_standard, md_standard_name FROM standard ORDER BY md_standard_order";
		$result = _executeSql('select', $sql, array('all'));
		if ($em == 1) {
			$rs['-1'] = '';
		}
		foreach ($result as $row) {
			$pom = $row['MD_STANDARD'];
			$rs[$pom] = $row['MD_STANDARD_NAME'];
		}
		return $rs;
	}

	function deleteMdidFromProfil($mds, $mdid, $profil) {
		//echo "deleteMdidFromProfil: $mds, $mdid, $profil<br>";
		$sql = array();
		if ($mds == 10) {
			$mds = 0;
		}
		$sql[] = "SELECT MD_LEFT, MD_RIGHT FROM tree WHERE MD_ID=$mdid AND MD_STANDARD=$mds";
		$result = _executeSql('select', $sql, array('all'));
		$left = $result[0]['MD_LEFT'];
		$right = $result[0]['MD_RIGHT'];
		$sql = array();
		$sql[] = "
			DELETE FROM profil WHERE profil_id=$profil AND md_id IN (
			SELECT tree.md_id
			FROM tree
			WHERE MD_LEFT >= $left AND MD_RIGHT <= $right AND MD_STANDARD = $mds)
		";
		_executeSql('delete', $sql, array());
	}

	public function checkProfilMD($profil, $mdid) {
		$sql = array();
		array_push($sql, "SELECT md_path FROM tree WHERE md_id=%i AND md_standard=0", $mdid);
		$recordSet = _executeSql('select', $sql, array('all'));
		foreach ($recordSet as $row) {
			$ret = $row['MD_PATH'];
		}
		$Apom = explode('_',$ret);
		foreach ($Apom as $hodnota) {
			if ($hodnota > 0) {
				$sql = array();
				array_push($sql, "DELETE FROM profil WHERE md_id=%i AND profil_id=%i", $hodnota, $profil);
				_executeSql('delete', $sql, array());
				$sql = array();
				array_push($sql, "INSERT INTO profil (profil_id,md_id) VALUES (%i,%i)", $profil, $hodnota);
				_executeSql('insert', $sql, array());
			}
		}
	}

	function getListProfil($profil) {
		$rs = array();
		$sql = array();
		$sql[] = "
			SELECT elements.el_name, tree.md_level, tree.md_id, elements.el_id FROM (profil INNER JOIN tree ON profil.md_id = tree.md_id) INNER JOIN elements ON tree.el_id = elements.el_id WHERE (((tree.md_standard)=0) AND ((profil.profil_id)=$profil)) ORDER BY tree.md_left
		";
		$recordSet = _executeSql('select', $sql, array('all'));
		foreach ($recordSet as $row) {
			$pom = array();
			$pom['md_level'] = $row['MD_LEVEL'];
			$pom['el_name'] = $row['EL_NAME'];
			$pom['md_id'] = $row['MD_ID'];
			$pom['el_id'] = $row['EL_ID'];
			array_push($rs, $pom);
		}
		return $rs;
	}

	function getProfilNames($mds, $profil_id) {
		$rs = array();
		$rs['PROFIL_ID'] = -1;
		$rs['PROFIL_NAME'] = '';
		$rs['PROFIL_ORDER'] = -1;
		$rs['MD_STANDARD'] = $mds;
		$rs['IS_VIS'] = 1;
		$rs['IS_PACKAGES'] = 0;
		$rs['IS_INSPIRE'] = 0;
		$rs['EDIT_LITE_TEMPLATE'] = '';
		
		$sql = array();
		if (($profil_id > 10 && $profil_id < 100) || $profil_id > 110) {
			array_push($sql, "SELECT * FROM profil_names WHERE profil_id=%i", $profil_id);
		}
		if (count($sql) > 0) {
			$recordSet = _executeSql('select', $sql, array('all'));
			$rs = $recordSet[0];
		}
		return $rs;
	}

	public function getLiteTemplates() {
		$rs = array();
		$rs['no'] = '';
		$rs['kote-micka'] = 'kote-micka';
		return $rs;
	}

	public function setProfilNames($post) {
		//my_print_r($post);
		$sql = array();
		$rs = array();
		$rs['ok'] = 'false';
		$rs['report'] = 'error';
		$mds = ($post['mds'] == 0 || $post['mds'] == 10) ? $post['mds'] : -1;
		if ($mds == -1) {
			// error
			$rs['report'] = 'uknow MD STANDARD';
			return $rs;
		}
		$profil_id = isset($post['profil_id']) && $post['profil_id'] != '' ? htmlspecialchars($post['profil_id']) : '';
		$record['profil_name'] = isset($post['profil_name']) && $post['profil_name'] != '' ? htmlspecialchars($post['profil_name']) : '???';
		$record['is_vis'] = isset($post['is_vis']) && $post['is_vis'] == 1 ? 1 : 0;
		$record['is_packages'] = isset($post['is_packages']) && $post['is_packages'] == 1 ? 1 : 0;
		$record['is_inspire'] = isset($post['is_inspire']) && $post['is_inspire'] == 1 ? 1 : 0;
		$record['edit_lite_template'] = isset($post['edit_lite_template']) && $post['edit_lite_template'] != 'no' ? htmlspecialchars($post['edit_lite_template']) : NULL;
		if ($profil_id == '' || $profil_id == -1) {
			// nový záznam
			array_push($sql, "SELECT max(profil_id) FROM profil_names WHERE md_standard=%i", $mds);
			$id = _executeSql('select', $sql, array('single'));
			if ($mds == 0) {
				$record['profil_id'] = ($id == '' || $id < 10) ? 11 : $id+1;
			}
			else {
				$record['profil_id'] = ($id == '' || $id < 110) ? 111 : $id+1;
			}
			$record['profil_order'] = $record['profil_id'];
			$record['md_standard'] = $mds;
			$sql = array();
			array_push($sql, "INSERT INTO profil_names %v", $record);
			_executeSql('insert', $sql, array('all'));
			if ($mds == 0) {
				$record['md_standard'] = 10;
				$copy_profil = ($post['copy'] > 0 && $post['copy'] < 100) ? $post['copy'] : 0;
				if ($copy_profil > 0) {
					$sql = array();
					array_push($sql, "DELETE FROM profil WHERE profil_id=%i", $record['profil_id']);
					_executeSql('insert', $sql, array('all'));
					$sql = array();
					array_push($sql, "
						INSERT INTO profil (profil_id, md_id) SELECT %i, md_id FROM profil WHERE profil_id=%i
					", $record['profil_id'], $copy_profil);
					_executeSql('insert', $sql, array('all'));
					$sql = array();
					array_push($sql, "
						INSERT INTO profil (profil_id, md_id) SELECT %i, md_id FROM profil WHERE profil_id=%i
					", $record['profil_id']+100, $copy_profil+100);
					_executeSql('insert', $sql, array('all'));
				}
				$record['profil_id'] = $record['profil_id'] + 100;
			}
			else {
				$record['md_standard'] = 0;
				$copy_profil = ($post['copy'] > 100) ? $post['copy'] : 0;
				if ($copy_profil > 0) {
					$sql = array();
					array_push($sql, "DELETE FROM profil WHERE profil_id=%i", $record['profil_id']);
					_executeSql('insert', $sql, array('all'));
					$sql = array();
					array_push($sql, "
						INSERT INTO profil (profil_id, md_id) SELECT %i, md_id FROM profil WHERE profil_id=%i
					", $record['profil_id'], $copy_profil);
					_executeSql('insert', $sql, array('all'));
					$sql = array();
					array_push($sql, "
						INSERT INTO profil (profil_id, md_id) SELECT %i, md_id FROM profil WHERE profil_id=%i
					", $record['profil_id']-100, $copy_profil-100);
					_executeSql('insert', $sql, array('all'));
				}
				$record['profil_id'] = $record['profil_id'] - 100;
			}
			$record['profil_order'] = $record['profil_id'];
			$sql = array();
			array_push($sql, "INSERT INTO profil_names %v", $record);
			_executeSql('insert', $sql, array('all'));
		}
		else {
			// editace
			array_push($sql, "UPDATE profil_names SET %a", $record);
			array_push($sql, "WHERE profil_id=%i", $profil_id);
			_executeSql('insert', $sql, array('all'));
		}
		return $rs;
	}

	public function delProfilNames($profil_id) {
		$sql = array();
		$rs = array();
		$profil = 0;
		$rs['ok'] = 'false';
		$rs['report'] = 'error';

		if (($profil_id > 10 && $profil_id < 100) || $profil_id > 110) {
			$profil = $profil_id;
			array_push($sql, "DELETE FROM profil_names WHERE profil_id=%i", $profil_id);
		}
		if (count($sql) > 0) {
			$recordSet = _executeSql('delete', $sql, array('all'));
		}
		if ($profil > 110) {
			$sql = array();
			array_push($sql, "DELETE FROM profil_names WHERE profil_id=%i", $profil_id-100);
			$recordSet = _executeSql('delete', $sql, array('all'));
			$sql = array();
			array_push($sql, "DELETE FROM profil WHERE profil_id=%i", $profil_id-100);
			$recordSet = _executeSql('delete', $sql, array('all'));
		}
		if ($profil > 10 && $profil < 100) {
			$sql = array();
			array_push($sql, "DELETE FROM profil_names WHERE profil_id=%i", $profil_id+100);
			$recordSet = _executeSql('delete', $sql, array('all'));
			$sql = array();
			array_push($sql, "DELETE FROM profil WHERE profil_id=%i", $profil_id+100);
			$recordSet = _executeSql('delete', $sql, array('all'));
		}
		return $rs;
	}
}

?>
