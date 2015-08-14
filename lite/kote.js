// JavaScript Document
var mickaURL = '..';

if(!String.trim) String.prototype.trim = function() { return this.replace(/^\s+|\s+$/, ''); };

function getFindBbox(bbox){
  pom=bbox.replace(/,/, " ").split(" ");
  document.forms[0]['xmin'].value=Math.round(pom[0]*100)/100;
  document.forms[0]['ymin'].value=Math.round(pom[1]*100)/100;
  document.forms[0]['xmax'].value=Math.round(pom[2]*100)/100;
  document.forms[0]['ymax'].value=Math.round(pom[3]*100)/100;
  checkFields();
}

function getWindowWidth() {  
	if(typeof(window.innerWidth) == 'number' ){ 
		var w = window.innerWidth;
	}	 
	else if(document.documentElement && document.documentElement.clientWidth){
	 	var w = document.documentElement.clientWidth;
	} 	 
    else if(document.body && document.body.clientWidth){ 
    	var w = document.body.clientWidth;
    }	 
	return w; 
} 
function showHelp(e){
	if(!e) e=window.event;
	var o = this.parentNode.parentNode.getElementsByTagName("DIV");
	if(!o || !o[0]) return;
	o[0].style.display='block';
	var x = e.clientX+15;
	var w = getWindowWidth() - 360;
	if(x>w) x = w;
    o[0].style.left=x+'px';
    /*o[0].style.top=(
    	e.clientY+15+(document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop)
    )+'px';*/
    o[0].style.top=e.clientY+15+'px';
}

function hideHelp(){
	var o = this.parentNode.parentNode.getElementsByTagName("DIV");
	if(!o || !o[0]) return; 
	o[0].style.display='none';
}

function init(){
  duplicateTools();
  var helps = getElementsByClassName('help');
  for(var i=0;i<helps.length;i++){
  	helps[i].onclick=showHelp;
    helps[i].onmouseout=hideHelp;
  }  
  
  var tt = document.getElementById("timeModeSel");
  if(tt) tt.onchange=timeMode;
  var act = document.getElementById("akce");
  if(act) act.onchange=actionMode;
  
  var dates = getElementsByClassName('date');
  for(i in dates){
	dates[i].onclick = koteDatePicker;
  }   
  
  var mand = getElementsByClassName('mandatory');
  for(i in mand){
	mand[i].onkeyup = checkFields;
	mand[i].onchange = checkFields;
  }
  

  var validator = document.getElementById("owsValidator");
  if(validator){
	  validator.scrollIntoView();
	  var titles = getElementsByClassName('title');
	  for(i in titles){
		  if(titles[i].id.substr(0,3)=='VAL'){ 
			  titles[i].onclick = zoomField;
			  titles[i].style.cursor='pointer';
		  }
	  }
  }
  
  var idField = document.forms[0].fileIdentifier_0_TXT;
  if(!idField.value) getUUID();
  idField.onchange = checkUUID;
  checkFields();
}

var checkFields = function(){
	var	mand = getElementsByClassName('mandatory');
	//console.log('changed...');
	for(i in mand){
		var err=false;
		if(typeof(mand[i].value) != 'undefined' && mand[i].value.trim()=='') var err = true;
		if(err){ 
      		if(mand[i].className.indexOf('missing')==-1) mand[i].className += " missing";
    	}  
		else mand[i].className = mand[i].className.replace(/ missing/g,"");
	}
}

var zoomField = function(){
	var id = this.id.substr(4);
	var fi = document.getElementById('V-'+id);
	if(fi) fi.scrollIntoView();
}

var getUUID = function(){
	var ajax = new HTTPRequest;
	ajax.get(mickaURL+"/lite/uuid.php", null, writeId, true);
}

var showThesaurus = function(url, isService){
	url += "/lite/thesaurus.html?path=true&services=true&lang=cze";
	if(isService=='false') url += "&inspire=true";
	window.open(url, 'gmt', 'toolbar=no,location=no,directories=no,status=no,menubar=no,width=400,height=500,resizable=yes,scrollbars=yes'); 
}

var liteThesaurus = function(r){
	if(!r.uri){
		var elm = document.forms[0].inspire;
	}
	else if(r.uri.indexOf("inspire.jrc.it")>-1){
		var elm = document.forms[0].inspire;
	}
	else {
		//var elm = document.getElementById('gemet');
		var elm = document.forms[0].gemet;
		var gver = document.getElementById('gemetCit');
		var gdate = document.getElementById('gemetDate');
		var pos = r.version.lastIndexOf(',');
		gver.value = r.version.substring(0,pos);
		gdate.value = r.version.substring(pos+1);
	}	
	if(elm.value) elm.value += "\n";
	elm.value += r.terms['cze'];
	checkFields();
}

// udela duplikovatka
var duplicateTools = function(){
  var dupl = getElementsByClassName('duplicate');
  for(i in dupl){ 
    dupl[i].innerHTML = ""; // TODO prepsat usporneji
  	var elm = document.createElement('span');
  	elm.className = 'plus';
  	elm.title = 'duplikovat';
  	elm.onclick = md_duplicateNode;
  	elm.innerHTML="&nbsp;";
	dupl[i].appendChild(elm);
	
 	elm = document.createElement('span');
  	elm.className = 'minus';
  	elm.title = 'smazat';
  	elm.onclick = md_removeNode;
  	elm.innerHTML="&nbsp;";
	dupl[i].appendChild(elm);
  }		
}

var md_duplicateNode = function(){
  var dold = this.parentNode.parentNode;
  var dnew=dold.cloneNode(true);
  var dalsi = dold.nextSibling;
  if(dalsi==null) dold.parentNode.appendChild(dnew); 
  else dold.parentNode.insertBefore(dnew,dalsi);
  var pos = dold.id.substring(0,dold.id.length-2).lastIndexOf("_");
  var pom0 = dold.id.substring(0,pos); 
  var elementy = md_getSimilarNodes(dold.parentNode, pom0);
  md_removeDuplicates(dnew);
  for(var i=0;i<elementy.length;i++) md_setName(elementy[i], pom0+"_"+i+"_");

  // --- vycisteni ---
  var nody = flatNodes(dnew, "INPUT");
  for(var i=0;i<nody.length;i++) if(nody[i].type=="text") nody[i].value = "";

  nody = flatNodes(dnew, "SELECT");
  for(var i=0;i<nody.length;i++) nody[i].selectedIndex=0;

  nody = flatNodes(dnew, "TEXTAREA");
  for(var i=0;i<nody.length;i++) nody[i].value="";

  //var d = getMyNodes(dnew, "DIV");
  //if(d[0]) d[0].style.display='block';
  
  init();
  window.scrollBy(0,dold.clientHeight);
  return dnew;
}

function md_removeDuplicates(obj){
  if(obj.hasChildNodes()){
    var i=0;
    while(i<obj.childNodes.length){
      var smazano = 0;
      if(obj.childNodes[i].nodeName=="DIV"){
        if(obj.childNodes[i].id){
          var pos = obj.childNodes[i].id.lastIndexOf("_");
          var pom = obj.childNodes[i].id.substring(0,pos);
          var pos = pom.lastIndexOf("_");
          var pom = pom.substring(0,pos);
          var podobne = md_getSimilar(obj, pom);
          if(podobne.length>1){
            smazano=1;
            for(var j=1;j<podobne.length;j++){ 
              obj.removeChild(podobne[j]); 
            }
          } 
        }
        if(smazano==0) md_removeDuplicates(obj.childNodes[i]);
      }
      i++; 
    }
  }
}

function md_setName(obj, id){
  var re = RegExp(obj.id, "g");
  var inputs = flatNodes(obj, "INPUT");
  for(var i=0;i<inputs.length;i++){
    inputs[i].name = inputs[i].name.replace(re,id);
  }
  var inputs = flatNodes(obj, "SELECT");
  for(var i=0;i<inputs.length;i++){
    inputs[i].name = inputs[i].name.replace(re,id);
  }
  var inputs = flatNodes(obj, "TEXTAREA");
  for(var i=0;i<inputs.length;i++){
    inputs[i].name = inputs[i].name.replace(re,id);
  }
  var inputs = flatNodes(obj, "A");
  for(var i=0;i<inputs.length;i++){
    inputs[i].href = inputs[i].href.replace(re,id);
  }
  obj.id = id;
}

function flatNodes(epom, nodename){
  var newList = new Array();
  if(epom.hasChildNodes()){
    for(var i=0; i<epom.childNodes.length; i++){
      if(epom.childNodes[i].nodeName==nodename) newList.push(epom.childNodes[i]);
      else {
        var pom = flatNodes(epom.childNodes[i], nodename);
        for(var j=0; j<pom.length; j++) newList.push(pom[j]);
      }
    }
  }
  return newList;
}

function getMyNodes(epom, nodename){
  var newList = new Array();
  for(var i=0; i<epom.childNodes.length; i++){
    if(epom.childNodes[i].nodeName==nodename) newList.push(epom.childNodes[i]);
  }
  return newList;
}


var md_removeNode = function(){
	if(!confirm("Smazat ?")) return;
	var toDel = this.parentNode.parentNode;
	var cont = toDel.parentNode;
	var pos = toDel.id.lastIndexOf("_");
    var pom = toDel.id.substring(0,pos);
    pos = pom.lastIndexOf("_");
    pom = pom.substring(0,pos);
	var elementy = md_getSimilarNodes(cont, pom);
	// odstraneni elementu
	if(elementy.length>1){ 
		cont.removeChild(toDel);
	}
	// vymazani obsahu pro prvni
	else {
	  var d = elementy[0];
	  var nody = flatNodes(d, "INPUT");
	  for(var i=0;i<nody.length;i++) if(nody[i].type=="text") nody[i].value = "";
	
	  nody = flatNodes(d, "SELECT");
	  for(var i=0;i<nody.length;i++) nody[i].selectedIndex=0;
	
	  nody = flatNodes(d, "TEXTAREA");
	  for(var i=0;i<nody.length;i++) nody[i].value="";		
	}	
	elementy = md_getSimilarNodes(cont, pom[0]);
	for(var i=0;i<elementy.length;i++) md_setName(elementy[i], pom[0]+"_"+i+"_"); 
	checkFields();
};

function md_getSimilarNodes(obj, str){
  var elementy = obj.childNodes;
  var elSim = new Array();
  var pm = "";
  for(var i=0;i<elementy.length;i++) if(elementy[i].id){
    var pos = elementy[i].id.substring(0,elementy[i].id.length-2).lastIndexOf("_");
    var pom0 = elementy[i].id.substring(0,pos); 
    if(pom0==str) elSim.push(elementy[i]);
  } 
  return elSim;
}

function md_getSimilar(obj, str){
  var elementy = obj.childNodes;
  var elSim = new Array();
  var pm = "";
  for(var i=0;i<elementy.length;i++) if(elementy[i].id){
    var pom = elementy[i].id.split("_");
    if(pom[0]==str) elSim.push(elementy[i]);
  } 
  return elSim;
}

function timeMode(){
  var t1 = document.getElementById("timeSpan");
  var t2 = document.getElementById("timeInstant");
  if(this.value==1){
    t1.style.display='none';
    t2.style.display='';
  }
  else{
    t1.style.display='';
    t2.style.display='none';
  }
}

function actionMode(){
  if(document.forms[0].action.value=="save"){
    document.getElementById("heslo").style.display='block';
    self.scrollTo(0, 1000);
  }
  else document.getElementById("heslo").style.display='none';
}

function submitCheck(){
  return true;
}

function writeId(r){
	document.forms[0].fileIdentifier_0_TXT.value = r.responseText;
	checkUUID();
}

function getElementsByClassName(classname, node) {
	if(!node) node = document.getElementsByTagName("body")[0];
	var a = [];
	var re = new RegExp('\\b' + classname + '\\b');
	var els = node.getElementsByTagName("*");
	for(var i=0,j=els.length; i<j; i++)
	if(re.test(els[i].className))a.push(els[i]);
	return a;
}

var koteDatePicker = function(){
	monthArrayLong = new Array('1 / ', '2 / ', '3 / ', '4 / ', '5 / ', '6 / ', '7 /', '8 / ', '9 / ', '10 / ', '11 / ', '12 / ');
  	datePickerClose = " X ";
  	if(lang=='cze'){
    	dayArrayShort = new Array('Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So');
    	datePickerToday = "Dnes";
    	displayDatePicker(this.name,false,'ymd','-');
  	}	 
  	else{
    	displayDatePicker(this.name,false,'ymd','-');
  	}  
}

var getFormats = function(obj){
	currentObj = obj;
	var w = window.open(mickaURL + '/md_lists.php?type=formats&lang='+lang, 'format','width=400,height=500,scrollbars=yes');
}

var formats1 = function(data){
	if(typeof(data)=="object"){
		var lang = document.forms[0].mdlang.value;
		currentObj.previousSibling.firstChild.value = data[lang];
	}
	else {
		currentObj.previousSibling.firstChild.value = data;
	}
	checkFields();
}

function formats1x(data){
  var inputs = flatNodes(md_elem, "TEXTAREA");
  if(inputs.length>0){
    for(var i=0;i<inputs.length;i++){
      	if(typeof(data)=="object"){
      		var lang = inputs[i].name.substr(1,3);
      		var f = data[lang];
      		if(!f) continue;
      	}
      	else f = data;
      	if(md_addMode)inputs[i].value += f; 
      	else inputs[i].value = f;
    }   
  }
  else{
  	var inputs = flatNodes(md_elem, "INPUT");
    	for(var i=0;i<inputs.length;i++){
      	if(inputs[i].type=='text'){
        	if(typeof(data)=="object"){
      			var lang = inputs[i].id.substr(inputs[i].id.length-3);
      			var f = data[lang];
      			if(!f) continue;
      		}
      		else f = data;
        	if(md_addMode)inputs[i].value += f; 
        	else inputs[i].value = f;
      	}   
    }   
  }
}


var getProtocols = function(obj){
	currentObj = obj;
 	var w = window.open(mickaURL + '/md_lists.php?type=protocol&lang='+lang, 'gmt', 'toolbar=no,location=no,directories=no,status=no,menubar=no,width=400,height=500,resizable=yes,scrollbars=yes'); 
}

var getParent = function(obj){
	currentObj = obj;
 	var w = window.open(mickaURL + '/md_search.php?lang='+lang, 'parent', 'toolbar=no,location=no,directories=no,status=no,menubar=no,width=500,height=500,resizable=yes,scrollbars=yes');
}

var getUseLim = function(obj){
	currentObj = obj;
 	var w = window.open(mickaURL + '/md_lists.php?type=uselim&multi=1&lang='+lang, 'gmt', 'toolbar=no,location=no,directories=no,status=no,menubar=no,width=400,height=500,resizable=yes,scrollbars=yes'); 
}

var getAccess = function(obj){
	currentObj = obj;
 	var w = window.open(mickaURL + '/md_lists.php?type=oconstraint&multi=1&lang='+lang, 'gmt', 'toolbar=no,location=no,directories=no,status=no,menubar=no,width=400,height=500,resizable=yes,scrollbars=yes'); 
}

var find_parent1 = function(data){
	var theField = currentObj.previousSibling.firstChild;
	//operatesOn
	if(theField.name.substr(0,10)=="operatesOn"){
		var loc = window.location.href.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');
		loc = loc.replace(/\/lite/,'');
		theField.value = loc+"/csw/index.php?SERVICE=CSW&VERSION=2.0.2&REQUEST=GetRecordById&OUTPUTSCHEMA=http://www.isotc211.org/2005/gmd&ID="+data.uuid;
		var fields = flatNodes(theField.parentNode.parentNode, "INPUT");
		fields[1].value = data.uuid;
		fields[2].value = data.title;
	}
	// parentID
	else {
		theField.value = data.uuid;
	}	
}

var getParty = function(obj){
	currentObj = obj;
	var w = window.open(mickaURL + '/md_contacts.php?lang='+lang, 'gmt', 'toolbar=no,location=no,directories=no,status=no,menubar=no,width=400,height=500,resizable=yes,scrollbars=yes'); 
}

var kontakt1 = function (osoba, org, orgEng, fce, phone, fax, ulice, mesto, admin, psc, zeme, email, url){
	var container = currentObj.parentNode.parentNode;
	var inputs = flatNodes(container, "INPUT");
	for(var i=0;i<inputs.length;i++){
		if(inputs[i].name.indexOf("organisationName")>-1 && inputs[i].name.indexOf("TXTeng")>-1) inputs[i].value = orgEng;
		else if(inputs[i].name.indexOf("organisationName")>-1) inputs[i].value = org;
		else if(inputs[i].name.indexOf("person")>-1) inputs[i].value = osoba;
		else if(inputs[i].name.indexOf("deliveryPoint")>-1) inputs[i].value = ulice;
		else if(inputs[i].name.indexOf("city")>-1) inputs[i].value = mesto;
		else if(inputs[i].name.indexOf("postalCode")>-1) inputs[i].value = psc;
		else if(inputs[i].name.indexOf("phone")>-1) inputs[i].value = phone;
		else if(inputs[i].name.indexOf("email")>-1) inputs[i].value = email;
		else if(inputs[i].name.indexOf("www")>-1) inputs[i].value = url;
	}
	checkFields();
}

var checkUUID = function(){
	var ask = document.getElementById('ask-uuid');
	if(!window.confirm(ask.innerHTML)){
		 document.forms[0].fileIdentifier_0_TXT.value = document.forms[0].uuid.value;
	}
	
}

currentObj = null;