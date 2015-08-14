<?php
session_start();
if (!isset($_SESSION['hs__'])){
   session_regenerate_id();
   $_SESSION['hs__'] = true;
}
define('WWW_DIR', dirname(__FILE__));
require 'include/application/micka_config.php';
Debugger::enable(Debugger::PRODUCTION, CSW_LOG);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="themes/default/mickaMap.css">
<script type="text/javascript" src="scripts/overlib_mini.js"></script>
<script language="javascript" src="scripts/hs_dmap.js"></script>
<script language="javascript" src="scripts/micka_dmap.js"></script>
<script language="javascript" src="scripts/wz_jsgraphics.js"></script>
<script language="javascript">

HS_RESIZE=false;
HS_RIGHT=24;
HS_BOTTOM=45;
HS_TIPSOFF=true;
ol_fgcolor="#FFFFE0";
ol_bgcolor="#FFB000";
hs_initext="<?php echo $hs_initext; ?>";
scales=new Array(1000,2000,3000,5000,10000,15000,20000,30000,40000);
epsg='4326';

var hs_msg = {
  'eng':{
  	zoomin: 'Zoom in',
  	zoomout: 'Zoom out',
  	zoomall: 'Zoom all',
  	pan: 'Pan',
  	drawrect: 'Draw Extent on the Map',
  	poly: 'Draw Polygon',
  	ext: 'Get Extent from the Map Extent'
  },
  'cze':{
  	zoomin: 'Zvětšit',
  	zoomout: 'Změnšit',
  	zoomall: 'Celá mapa',
  	pan: 'Posunout',
  	drawrect: 'Zadání výřezu v mapě',
  	poly: 'Nakreslení polygonu',
  	ext: 'Získání rozsahu z rozsahu mapy'
  }		
};

<?php
	$lang = 'cze';
	if(isset($_REQUEST['lang'])) {
		$lang = $_REQUEST['lang'];
	}
  else {
		$lang = $_SESSION['hs_lang'];
	}
	if ($lang == '') {
		$micka_langs_arr = explode(',', MICKA_LANGS_STR);
		$lang = $micka_langs_arr[0];
	}
  if($lang == 'slo') {
		$lang = 'cze';
	}
  if($lang != 'cze') {
		$lang = 'eng';
	}
  echo "var lang='$lang';\n";
  $wms = $hs_wms[$lang];
  if(!$wms) $wms = $hs_wms['eng'];
  echo "var wms = '$wms&request=GetMap'"; 
?>;
var HS_STATIC = true;

</script>
</head>

<body onload="self.focus();">

<!-- hlavni div-->
<div id="hsMap" style="position:relative; left:0px; top:0px; border:0px; width:100%">

<!--lista -->
<form name="mapserv" method="GET" style="margin:0;">
<div class="lista" style="height:20px;">    
<div style="float:left; width:150px;">
<script language="javascript">


addButton("i","themes/default/img/zoomin.gif",hs_msg[lang].zoomin,2,1,bZoomIn,mZoom);
addButton("o","themes/default/img/zoomout.gif",hs_msg[lang].zoomout,2,1,bZoomOut,mZoom);
addButton("p","themes/default/img/pan.gif",hs_msg[lang].pan,-1,1,bPan,mPan,mZoomIn,mZoomOut);
addButton("r","themes/default/img/rect.gif",hs_msg[lang].drawrect,2,2,bZoomIn,mRect);
if(!parent.getFindBbox) addButton("a","themes/default/img/poly.gif",hs_msg[lang].poly,5,1,bMeasure,mPoly);

function setLayer(o){
}

</script>

</div>
<div style="float:right;">
<script type="text/javascript">
  document.write('<a class="abut1" href="javascript:mRect(document.mapserv.imgext.value);"><img src="themes/default/img/maprect.gif" onmouseover="helpShow(\''+hs_msg[lang].ext+'\');" onmouseout="nd();"></a>');
  document.write('<a class="abut1" href="javascript:vyrez(hs_initext);"><img src="themes/default/img/zoomall.gif" onmouseover="helpShow(\''+hs_msg[lang].zoomall+'\');" onmouseout="nd();"></a>');
</script>  
</div><br>
</div>

<!--mapovy ram -->
  <input type='hidden' name='butt' value='0'>
  <input type='hidden' name='zoomdir' value='1'>
  <input type='hidden' name='imgxy' value=''>
  <input type='hidden' name='imgbox' value=''>
  <input type='hidden' name='imgext' value=''>
  <input type='hidden' name='mode' value='browse'>

  <input type='hidden' name='layers' value=''>
  <input type='hidden' name='zoomsize' value='2'>
  <input type='hidden' name='pin' value=''>
  <input type='hidden' name='mapsize' value='336 225'>
  <input type='hidden' name='scale' value=''>
  <input type='hidden' name='savequery' value=''>
  <input type='hidden' name='project' value='micka_map'>
  <input type='hidden' name='imagemap' value=''>
  <script>mapFrame('themes/default/img/1px.gif', 336, 225);</script>

<!-- konec mapframe-->

<!-- spodni lista -->

</form>

</div> <!-- konec bloku hsmap-->

<!-- pomocne objekty -->
<div id="overDiv"></div>

<script>
hsStart('[qlayer]', '336 225');
butt(0);
vyrez(hs_initext);
</script>

</body>
</html>
