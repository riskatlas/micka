<?php
/**
 * Funkce pro práci s datumem
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka
 * @category   Metadata
 * @version    20101109
 *
 */

/**
 * Převod datumu z formátu ISO do CZ formátu
 *
 * @param string $datum 2010-10-01
 * @return string 01.10.2010
 */
function dateIso2Cz($datum) {
	$pom = explode('-', $datum);
	if (count($pom) == 2) {
		$m = (int)$pom[1];
		$datum = $m . '.' . $pom[0];
	}
	if (count($pom) == 3) {
		$d = (int)$pom[2];
		$m = (int)$pom[1];
		$datum = $d . '.' . $m . '.' . $pom[0];
	}
	return $datum;
}

/**
 * Převod datumu z CZ formátu do formátu ISO
 *
 * @param string $datum 1.10.2010
 * @return string 2010-10-01
 */
function dateCz2Iso($datum) {
	$pom = explode('.', $datum);
	if (count($pom)==2) {
		$m = $pom[0];
		$m = ($m < 10  && strlen($m) == 1) ? "0$m" : $m ;
		$datum = $pom[1] . '-' . $m;
	}
	if (count($pom) == 3) {
		$d = (int)$pom[0];
		$d = ($d < 10 && strlen($d) == 1) ? "0$d" : $d ;
		$m = (int)$pom[1];
		$m = ($m < 10  && strlen($m) == 1) ? "0$m" : $m ;
		$datum = $pom[2] . '-' . $m . '-' . $d;
	}
	return $datum;
}

function setDateFormat($in) {
	if (DB_DRIVER == 'mssql') {
		$in = str_replace('-', '', $in);
	}
	return $in;
}

function getNewDate() {
	return setDateFormat(Date("Y-m-d"));
}

/*
 * Kontrola, zda je rok přestupný
 */
function isBissextile($year){
	if (($year % 4 == 0) && ($year % 100 != 0) && ($year % 1000 != 0)) return true;
	elseif ($year % 400 == 0) return true;
	elseif (( $year % 1000 == 0) && ($year % 4000 != 0)) return true;
	else return false;
}

function extendDate($date, $mode){
	$months = array( 1 => 31,
	2 => 28,
	3 => 31,
	4 => 30,
	5 => 31,
	6 => 30,
	7 => 31,
	8 => 31,
	9 => 30,
	10 => 31,
	11 => 30,
	12 => 31);
	// přestupný rok (bissextile)
	$monthsBis = array( 1 => 31,
	2 => 29,
	3 => 31,
	4 => 30,
	5 => 31,
	6 => 30,
	7 => 31,
	8 => 31,
	9 => 30,
	10 => 31,
	11 => 30,
	12 => 31) ;

	if(strpos($date, ' ')) { // pokud obsahuje i čas TODO: místo mezery může být i T
	$date = substr($date,0,strpos($date, ' '));
	}
	if(strpos($date, '-')) {
		list($year, $month, $day) = explode("-", $date);
		$day = ($day < 10 && strlen($day) == 1) ? "0$day" : $day ;
		$month = ($month < 10  && strlen($month) == 1) ? "0$month" : $month ;
	}
	else {
		$dateLen = strlen($date);
		switch ($dateLen) {
			case 8: // YYYYMMDD
				$month = substr($date,4,2);
				$day = substr($date,6,2);
			case 4: // YYYY
				$year = substr($date,0,4);
				break;
			default:
				return $date; // špatný formát
				break;
		}
	}
	if(!$month){
		if($mode) $month='12'; else $month='01';
	}
	if(!$day){
		if($mode) {
			if (isBissextile($year)) {
				$day=$monthsBis[(int)$month];
			}
			else {
				$day=$months[(int)$month];
			}
		}
		else {
			$day='01';
		}
	}
	return "$year-$month-$day";
}

/*
 * Vytvoření časového rozsahu od $date1 do $date2
 * možné zadání:
 * 	$date:	okamžik, $date1 a $date2 se ignorují
 * 	$date1:	datum od
 * 	$date2:	datum do
 */
function timeWindow($date, $date1, $date2){
	if(!$date && !$date1 && !$date2){
		return array('0000-00-00','0000-00-00');
	}
	if(($date1)||($date2)){
		if($date1) {
			$date1 = extendDate($date1, false);
		}
		else {
			$date1 = extendDate('0001', false);
		}
		if($date2) {
			$date2 = extendDate($date2, true);
		}
		else {
			$date2 = extendDate('9999', true);
		}
	}
	else{
		$date1 = extendDate($date, false);
		$date2 = extendDate($date, true);
	}
	if (!isValidDateIso($date1) || !isValidDateIso($date2)) {
		$date1 = '0000-00-00';
		$date2 = '0000-00-00';
	}
	return Array($date1, $date2);

}

/*
 * Kontrola, zda je datum ve formátu ISO 8601, varianta s pomlčkou
 * http://en.wikipedia.org/wiki/ISO_8601
 * YYYY
 * YYYY-MM
 * YYYY-MM-DD
 */
function isValidDateIso($date){
	$dateLen = strlen($date);
	switch ($dateLen) {
		case 4: // pouze rok YYYY
			if (preg_match('/[0-9]{4}/', $date)){
				return true;
			}
			else {
				return false;
			}
		case 7: // YYYY-MM
			if (preg_match('/[0-9]{4}\-[0-9]{2}/', $date)){
				if (substr($date,5,2) < 13) {
					return true;
				}
				else {
					return false;
				}
			}
			else {
				return false;
			}
		case 10: // YYYY-MM-DD
			if (preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2}/', $date)){
				list($year, $month, $day) = explode("-", $date);
				if (checkDate($month, $day, $year)) {
					return true;
				}
				else {
					return false;
				}
			}
			else {
				return false;
			}
		default: // špatný tvar datumu
			return false;
	}
}
?>
