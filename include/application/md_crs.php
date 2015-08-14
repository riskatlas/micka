<?php
$values = file(PHPPRG_DIR . "/../dict/crs.txt");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="<?php echo substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')) . '/themes/' . MICKA_THEME; ?>/micka.css" />
<title>MICKA - CRS</title>
<script>

function kw(f){
  if((!opener)||(!opener.crs1)){
    alert('Main window is closed !');
    window.close();
    return;
  }
  opener.crs1(f);
  window.close();
}
</script>
</head>
<body onload="javascript:focus();">
<h2>CRS</h2>

<?php
reset($values);
foreach($values as $row){
  $val = explode("|", $row);
  echo "<a href=\"javascript:kw('$val[0]')\">$val[1]</a><br>";
}
?>

</body>
</html>

<?php exit; ?>