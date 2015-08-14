<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * našeptávač
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20140910
 * 
 */

session_start();
require '../include/application/micka_config.php';
require PHPPRG_DIR . '/micka_lib.php';
require PHPPRG_DIR . '/micka_auth.php';

$substring = DB_DRIVER == 'oracle' ? 'SUBSTR' : 'SUBSTRING';
$sql = array();
$org = array();
$md_id = array();
$rs = array();
$recno = '';
$orderBy = TRUE;

$query_lang = isset($_REQUEST['lang']) && $_REQUEST['lang'] != '' ? htmlspecialchars($_REQUEST['lang']) : '';
$creator = isset($_REQUEST['creator']) && $_REQUEST['creator'] != '' ? htmlspecialchars($_REQUEST['creator']) : '';
$query = isset($_REQUEST['query']) && $_REQUEST['query'] != '' ? htmlspecialchars($_REQUEST['query']) : '';
$contact_type = isset($_REQUEST['type']) && $_REQUEST['type'] != '' ? htmlspecialchars($_REQUEST['type']) : 'org';
$contact_role = isset($_REQUEST['role']) && $_REQUEST['role'] != '' ? htmlspecialchars($_REQUEST['role']) : '';

$user = MICKA_USER;
$admin = canAction('*');
$group = getMsGroups('get_groups');
$group = implode("','", array_keys($group));
$group = "'" . $group . "'";
if ($admin === TRUE) {
    $right = 'md.data_type IS NOT NULL';
} else {
    $right = $user == 'guest' 
            ? 'md.data_type>0'
            : "(md.create_user='$user' OR md.view_group IN($group) OR md.edit_group IN($group) OR md.data_type>0)";
}

switch ($contact_type) {
	case 'mdperson':
		//$md_id = array(152);
		$query_lang = '';
        array_push($sql, "
            SELECT md_values.recno, md_values.md_path, md_values.md_value, md_values.lang
            FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON($substring(md_values.md_path, 1,17)=$substring(m.md_path, 1,17) AND md_values.recno=m.recno)
            WHERE 
        ");
        if($creator != '') {
            if ($creator == $user) {
                array_push($sql, "AND md.create_user=%s", $creator);
            } else {
                array_push($sql, "AND md.create_user=%s AND (md.view_group IN($group) OR md.edit_group IN($group) OR md.data_type>0))", $creator);
            }
        } else {
            array_push($sql, "AND $right");
        }
        if($query != '') {
            array_push($sql, "AND " . setSqlLike('md_values.md_value', "'%" . $query . "%'"));
        }
        if($contact_role != '') {
            array_push($sql, "AND md_values.md_id=152 AND m.md_id=992 AND m.md_value=%s", $contact_role);
        } else {
            array_push($sql, "AND md_values.md_id=152 AND m.md_id=992 AND m.md_value IS NOT NULL");
        }
		break;
	case 'mdorg':
		$md_id = array(153);
        array_push($sql, "
            SELECT md_values.recno, md_values.md_path, md_values.md_value, md_values.lang
            FROM md INNER JOIN md_values ON md.recno = md_values.recno
            WHERE md_values.md_id IN %in AND data_type>0
    	", $md_id);
        if($query_lang != '') {
            array_push($sql, "AND md_values.lang=%s", $query_lang);
        }
        if($creator != '') {
            if ($creator == $user) {
                array_push($sql, "AND md.create_user=%s", $creator);
            } else {
                array_push($sql, "AND md.create_user=%s AND (md.view_group IN($group) OR md.edit_group IN($group) OR md.data_type>0))", $creator);
            }
        } else {
            array_push($sql, "AND $right");
        }
        if($query != '') {
            array_push($sql, " AND " . setSqlLike('md_values.md_value', "'%" . $query . "%'"));
        }
        array_push($sql, "ORDER BY md_values.md_value");
		break;
	case 'denom':
        $creator = '';
        $query = '';
        $orderBy = FALSE;
		$query_lang = '';
        $mask = DB_DRIVER == 'oracle' ? '' : ", '999999999'";
        array_push($sql, "
            SELECT md_values.md_value 
            FROM md JOIN md_values ON md.recno=md_values.recno 
            WHERE md_values.md_id=99 AND $right
            GROUP BY md_values.md_value 
            ORDER BY TO_NUMBER(md_value $mask)
        ");
        break;
	case 'country':
        $creator = '';
        $query = '';
        $orderBy = FALSE;
		$query_lang = '';
        $md_id = array(202,5046);
        $rs_md_id = _executeSql('select', "SELECT md_id FROM tree WHERE md_standard=0 AND md_mapping='country'", array('all'));
        if ($rs_md_id != '' && is_array($rs_md_id) === TRUE) {
            $md_id = array();
            foreach ($rs_md_id as $row) {
                $md_id[] = $row['MD_ID'];
            }
        }
        array_push($sql, "
            SELECT md_values.md_value 
            FROM md JOIN md_values ON md.recno=md_values.recno 
            WHERE md_values.md_id IN %in AND $right
            GROUP BY md_values.md_value 
            ORDER BY md_value
        ", $md_id);
        break;
	case 'keyword':
		$md_id = array(88,4920);
        array_push($sql, "
            SELECT md_values.recno, md_values.md_path, md_values.md_value, md_values.lang
            FROM md INNER JOIN md_values ON md.recno = md_values.recno
            WHERE md_values.md_id IN %in AND data_type=1
    	", $md_id);
        if($query_lang != '') {
            array_push($sql, "AND md_values.lang=%s", $query_lang);
        }
        if($creator != '') {
            array_push($sql, "AND md.create_user=%s", $creator);
        }
        if($query != '') {
            array_push($sql, " AND " . setSqlLike('md_values.md_value', "'%" . $query . "%'"));
        }
        array_push($sql, "ORDER BY md_values.md_value");
	    break;
    case 'person':
    case 'person':
	case 'org':
	default:
        $md_id_cont_md = $contact_type == 'person' ? 186 : 187;
        $md_id_cont_ms = $contact_type == 'person' ? 5028 : 5029;
        $md_id = array($md_id_cont_md, $md_id_cont_ms);
		$query_lang = '';
        array_push($sql, "
            SELECT md_values.recno, md_values.md_path, md_values.md_value, md_values.lang
            FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON($substring(md_values.md_path, 1,27)=$substring(m.md_path, 1,27) AND md_values.recno=m.recno)
            WHERE md_values.md_id=%i AND md.data_type>0
        ", $md_id_cont_md);
        if($creator != '') {
            if ($creator == $user) {
                array_push($sql, "AND md.create_user=%s", $creator);
            } else {
                array_push($sql, "AND md.create_user=%s AND (md.view_group IN($group) OR md.edit_group IN($group) OR md.data_type>0))", $creator);
            }
        } else {
            array_push($sql, "AND $right");
        }
        if($query != '') {
            array_push($sql, "AND " . setSqlLike('md_values.md_value', "'%" . $query . "%'"));
        }
        if($contact_role != '') {
            array_push($sql, "AND m.md_id=1047 AND m.md_value=%s", $contact_role);
        } else {
            array_push($sql, "AND m.md_id=1047 AND m.md_value IS NOT NULL");
        }
        array_push($sql, "
            UNION
            SELECT md_values.recno, md_values.md_path, md_values.md_value, md_values.lang
            FROM (md JOIN md_values ON md.recno=md_values.recno) LEFT JOIN md_values m ON($substring(md_values.md_path, 1,32)=$substring(m.md_path, 1,32) AND md_values.recno=m.recno)
            WHERE md_values.md_id=%i AND md.data_type>0
        ", $md_id_cont_md);
        if($creator != '') {
            if ($creator == $user) {
                array_push($sql, "AND md.create_user=%s", $creator);
            } else {
                array_push($sql, "AND md.create_user=%s AND (md.view_group IN($group) OR md.edit_group IN($group) OR md.data_type>0))", $creator);
            }
        } else {
            array_push($sql, "AND $right");
        }
        if($query != '') {
            array_push($sql, " AND " . setSqlLike('md_values.md_value', "'%" . $query . "%'"));
        }
        if($contact_role != '') {
            array_push($sql, "AND m.md_id=5038 AND m.md_value=%s ", $contact_role);
        } else {
            array_push($sql, "AND m.md_id=5038 AND m.md_value IS NOT NULL");
        }
        //array_push($sql, "ORDER BY md_values.md_value");
		break;
}

$result = _executeSql('select', $sql, array('all'));

$firs_record = TRUE;
$org_lang = '';
$org_eng = '';
$org_ost = '';
if ($query_lang == '') {
	$query_lang = 'eng';
}
if ($orderBy === FALSE) {
	foreach ($result as $key => $value) {
		$rs[] = array('id'=>($key+1),"value"=>$value['MD_VALUE']);
	}
    $rs = array('numresults'=>count($rs),'records'=>$rs);
    header("Content-type: application/json charset=\"utf-8\"");
    echo json_encode($rs); exit;
}

if (is_array($result) && count($result) > 0) {
	foreach ($result as $row) {
		if ($recno != $row['RECNO'] && $firs_record === FALSE) {
			if ($org_lang != '') {
				$org_name = $org_lang;
			}
			elseif ($org_eng != '') {
				$org_name = $org_eng;
			}
			else {
				$org_name = $org_ost;
			}
			$org[]=$org_name;
			$org_lang = '';
			$org_eng = '';
			$org_ost = '';
		}
		$org_ost = $row['MD_VALUE'];
		if ($query_lang == $row['LANG']) {
			$org_lang = $row['MD_VALUE'];
		}
		if ($row['LANG'] == 'eng') {
			$org_eng = $row['MD_VALUE'];
		}
		$firs_record = FALSE;
		$recno = $row['RECNO'];
	}
	if ($org_lang != '') {
		$org_name = $org_lang;
	}
	elseif ($org_eng != '') {
		$org_name = $org_eng;
	}
	else {
		$org_name = $org_ost;
	}
	$org[]=$org_name;
	$org_lang = '';
	$org_eng = '';
	$org_ost = '';
}

if ($org_lang != '' && $org_eng != '' && $org_ost != '') {
	if ($org_lang != '') {
		$org_name = $org_lang;
	}
	elseif ($org_eng != '') {
		$org_name = $org_eng;
	}
	else {
		$org_name = $org_ost;
	}
	$org[]= $org_name;
}

if (count($org) > 0) {
	$org = array_unique($org);
	
// NOTE: předpokládá se UTF-8
	setlocale(LC_ALL, 'cs_CZ.utf8');
	sort($org, SORT_LOCALE_STRING);
	
	for($i=0;$i<count($org);$i++) {
		$rs[] = array('id'=>($i+1),"name"=>$org[$i]);
	}
}
$rs = array('numresults'=>count($org),'records'=>$rs);
header("Content-type: application/json charset=\"utf-8\"");
echo json_encode($rs);