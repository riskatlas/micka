/******************************
 * MICKA 5.000
 * 2012-11-19
 * javascript
 * Help Service Remote Sensing  
******************************/

MD_COLLAPSE = "collapse.gif";
MD_EXPAND   = "expand.gif";
MD_EXTENT_PRECISION = 1000;
var md_mapApp = getBbox;
var md_pageOffset = 0;
var messages = {};
var confirmLeave = false;
var micka = {};

String.prototype.trim = function() { return this.replace(/^\s+|\s+$/, ''); };

var eventParser = {
	getEvent: function(e){
		if (!e) e = window.event;
		return e;
	},
		
	getEventTarget: function(e) {
		if (!this.getEvent(e).target) this.getEvent(e).target = this.getEvent(e).srcElement;
		return this.getEvent(e).target;
	},
	
	eraseEvent: function(e) {
		if (e) e.stopPropagation()
    else	window.event.cancelBubble = true;
	},
	
	stopEvent: function(e){
		if(e.preventDefault){
			e.preventDefault();
			e.stopPropagation();
		}
    else e.returnValue = false;
	}
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

function md_pridej(obj){
  var dold = obj.parentNode;
  var dnew=dold.cloneNode(true);
  var dalsi = dold.nextSibling;
  if(dalsi==null) dold.parentNode.appendChild(dnew); 
  else dold.parentNode.insertBefore(dnew,dalsi);
  var pom = dold.id.split("_");
  var elementy = md_getSimilar(dold.parentNode, pom[0]);
  md_removeDuplicates(dnew);
  //for(var i=(parseInt(pom[1])+1);i<elementy.length;i++) md_setName(elementy[i], pom[0]+"_"+i+"_");
  for(var i=0;i<elementy.length;i++) md_setName(elementy[i], pom[0]+"_"+i+"_");

  // --- vycisteni ---
  var nody = flatNodes(dnew, "INPUT");
  for(var i=0;i<nody.length;i++) if(nody[i].type=="text") nody[i].value = "";

  nody = flatNodes(dnew, "SELECT");
  for(var i=0;i<nody.length;i++) nody[i].selectedIndex=0;

  nody = flatNodes(dnew, "TEXTAREA");
  for(var i=0;i<nody.length;i++) nody[i].value="";

  var d = getMyNodes(dnew, "DIV");
  if(d[0]) d[0].style.display='block';
  
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
          var pom=obj.childNodes[i].id.split("_");
          var podobne = md_getSimilar(obj, pom[0]);
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
	  //if (inputs[i].type=="radio") console.log("raido",inputs[i].name);
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

function md_smaz(obj){
  if(!confirm(messages.del + " ?")) return;
  var toDel = obj.parentNode;
  var cont = toDel.parentNode;
  var pom = toDel.id.split("_");
  var elementy = md_getSimilar(cont, pom[0]);
  if(elementy.length>1) cont.removeChild(toDel);
  var elementy = md_getSimilar(cont, pom[0]);
  for(var i=0;i<elementy.length;i++) md_setName(elementy[i], pom[0]+"_"+i+"_");
}

function md_menu(akce,recno,profil){
  if(!parent.frames.mdMenu) return false;
  if(typeof(recno)!="number")recno='';
  s = 'micka_menu.php?ak='+akce+'&recno='+recno+'&prof='+profil;
  if(parent.frames.mdMenu.location.href.indexOf(s)<0)
    parent.frames.mdMenu.location.href=s;
}

function md_unload(e){
  	if(document.getElementById("md_inpform") && confirmLeave){  
  		return messages.leave + ' ?';
  	}
}

function elementInViewport(el) {
    var rect = el.getBoundingClientRect()

    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= window.innerHeight &&
        rect.right <= window.innerWidth 
        )
}

function checkMenu(){
  //var a = document.getElementsByTagName('a');
  //for(i=0;i<a.length;i++) a[i].onclick=md_unload;
}

function elm(name){
  if(document.getElementById) return document.getElementById(name);
  else if(document.all) return document.all[name];
  else return document.layers[name];
}


function md_scroll(el){
  if(typeof el == 'string'){
      var el = document.getElementById(el);
  }
  if(el){
	  var e = el;
	  while(e=e.parentNode){
		  if(e.tagName=='BODY') break;
		  if(e.style.display=='none'){
			  e.style.display='block';
		  }
	  }
	  el.scrollIntoView(true);
	  window.scrollBy(0,-50); //velikost hlavicky
	  el.parentNode.style.background="#FFFFA0"; //TODO do stylu
	  setTimeout(function(){
		  el.parentNode.style.background="";
	  }, 1000);
  }
}

function md_expand(obj){
  var rf = flatNodes(obj.parentNode.parentNode,'INPUT');
  if(!rf.length) rf = [rf];
  for(var i=0;i<rf.length;i++){
   	if((rf[i].type=='radio')&&(obj.name=rf[i].name)){
   	  var d = rf[i].parentNode.childNodes;
   	  for(var j=0;j<d.length;j++){
   	    if(d[j].nodeName=='DIV'){
        	if(rf[i]==obj){ 
        	 	var toClose = d[j]; 
        	}
        	else if(d[j].style.display!='none') {
        	  var data = '';
   	   		  var inputs = flatNodes(d[j],'INPUT');
   	  	    for(var k=0; k<inputs.length; k++) if(inputs[k].type=='text') data += inputs[k].value;
   	  	    var selects = flatNodes(d[j],'SELECT');
   	  	    for(var k=0; k<selects.length; k++) data += selects[k].value;
   	  	    var texts = flatNodes(d[j],'TEXTAREA');
   	  	    for(var k=0; k<texts.length; k++) data += texts[k].value;
   	  	    if(data){
   	  	    	var c = window.confirm(messages.del + " ?");
   	  	    	if(!c){ 
   	  	    		rf[i].click();
   	  	    		return false;
   	  	    	}	
    				  for(var k=0; k<inputs.length; k++) if(inputs[k].type=="text") inputs[k].value = "";
    				  for(var k=0; k<texts.length; k++)  texts[k].value = "";
    				  for(var k=0; k<selects.length; k++) selects[k].selectedIndex=0;  	    	
   	  	    }  	
        	  d[j].style.display='none';
        	}	
        }
      }	  
    }
  }
  toClose.style.display='block';
  return false;
}

function md_dexpand(obj){
  var id=obj.id.substr(2);
  var o = document.getElementById("PB"+id);
  var d = getMyNodes(obj.parentNode, "DIV");
  o = d[0];
  var src = obj.src.substring(0, obj.src.lastIndexOf("/")+1);
  if(o){
    if(o.style.display=='block'){
      o.style.display='none';
      obj.src = src+MD_EXPAND;
    }  
    else {
      o.style.display='block'; 
      obj.src = src+MD_COLLAPSE;
    }
  }
}


function clickMenu(block, target){
  var me = window;
  confirmLeave = false;
  if(parent && parent.frames.main){
  	var me = parent.frames.main;
  } 
  if(block=="cancel"){
	  me.location="?ak=storno";
	  return;
  }
  if(block==-19){ 
	  md_pageOffset = me.document.body.scrollTop;
  }	  
  else{ 
	  md_pageOffset = 0;
  }
  me.confirmLeave = false;
  if(block==-20) me.location = "?ak=mdview&ak1=cancel&recno="+me.document.forms['md_inpform'].recno.value;
  else {
  	me.document.forms['md_inpform'].target = "";
  	if(typeof(target) != 'undefined'){
  		me.document.forms['md_inpform'].target = target;
  	}
  	me.document.forms['md_inpform'].nextblock.value=block;
  	me.document.forms['md_inpform'].submit();
  }
}

function clickLink(block, target){
	if(block==-1){
		scroll(0,0);
	}
	document.forms['md_inpform'].target='';
 	document.forms['md_inpform'].nextblock.value=block;
 	if(target){
 		document.forms['md_inpform'].target=target;
 	}	
 	document.forms['md_inpform'].submit();
}

function clickProfil(id_profil, id_block){
	confirmLeave = false;
	document.forms['md_inpform'].target='';
	if (id_profil > -1) {
		document.forms['md_inpform'].nextblock.value=document.forms['md_inpform'].block.value;
		document.forms['md_inpform'].nextprofil.value = id_profil;
	}
	if (id_block > -1) {
		document.forms['md_inpform'].nextblock.value = id_block;
		document.forms['md_inpform'].nextprofil.value = document.forms['md_inpform'].profil.value;
	}
	document.forms['md_inpform'].submit();
}

function selProfil(obj){
	confirmLeave = false;
	document.forms['md_inpform'].target='';
	document.forms['md_inpform'].nextblock.value=document.forms['md_inpform'].block.value;
	document.forms['md_inpform'].nextprofil.value=obj.value;
	document.forms['md_inpform'].submit();
}

function chVal(e){
  if(this.value=='') return true;
  if(this.className=='N'){
    if(isNaN(this.value)){
      alert('Bad number!');
      return false;
    }
    else return true;
  }
  else if(this.className=='D'){
    if(lang=='cze'){
      var r = /^(((0?[1-9]|[12][0-9]|3[01])\.)?((0?[1-9]|1[0-2])\.)?)((18|19|20|99)\d{2})$/
      var msg = 'Špatný formát data. Musí být RRRR nebo MM.RRRR nebo DD.MM.RRRR';      
    }  
    else //if(lang=='en')
    {
      var r = /^((18|19|20|99)\d{2})(-(0?[1-9]|1[0-2])(-(0?[1-9]|[12][0-9]|3[01]))?)?$/
      var msg = 'Bad date format: YYYY or YYYY-MM or YYYY-MM-DD allowed.';
    }
    if(r.exec(this.value)) return true;
    else{
      alert(msg);
      return false;
    }
  }

}

function chTextArea(e){
   if(this.value.length>2000){
	 alert('Maximum 2000 characters.');
	 this.value = this.value.substr(0, 2000);
	 return false;
   }
}

function start(){
	var inpForm = document.getElementById("md_inpform");
	if(inpForm){
		confirmLeave = true;
		window.onbeforeunload = md_unload;
	}	 
	var inputs = document.getElementsByTagName("input");
	if(inputs.length>0) for(i=0;i<inputs.length;i++){
		//inputs[i].onkeyup=chVal;
		inputs[i].onblur=chVal;
	}
	var ta = document.getElementsByTagName("textarea");
	if(ta.length>0) for(i=0;i<ta.length;i++){
		ta[i].onkeyup=chTextArea;
		ta[i].change=chTextArea;
	}
	/*if(inpForm){
		confirmLeave = true;
		window.onbeforeunload = stopEdit
	}	 */
	if(parent && parent.frames.mdMenu){ 
		window.scrollTo(0, parent.frames.mdMenu.md_pageOffset);
	}
	// rodicovsky element
	var parent = document.getElementById("50");
	if(parent && parent.value){
		document.getElementById("parent-text").innerHTML = "...";
		var ajax = new HTTPRequest;
		ajax.get("csw/index.php?format=json&query=identifier%3D"+parent.value, "", drawParent, false); 
	}
}

var drawParent = function(r){
	if(r.readyState == 4){
		eval("result="+r.responseText);
		if(result.records && result.records[0]){
			document.getElementById("parent-text").innerHTML = result.records[0].title;
		}
	}  
}

/*function stopEdit(e){
	if(confirmLeave) return 'You should stop editing before leaving !!!';  
}*/

function md_delrec(id){
  if(confirm(messages.del + ' ?')) this.location=("?ak=delete&recno="+id);
}

/* editovani */
function getMyNodes(epom, nodename){
  var newList = new Array();
  for(var i=0; i<epom.childNodes.length; i++){
    if(epom.childNodes[i].nodeName==nodename) newList.push(epom.childNodes[i]);
  }
  return newList;
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

function md_dexpand1(obj){
  var divs = flatNodes(obj, "DIV"); 
  var imgs = getMyNodes(obj, "IMG");
  if(divs.length>0) divs[0].style.display='block';
  if(imgs.length>0) imgs[0].src = imgs[0].src.substring(0, imgs[0].src.lastIndexOf("/")+1)+ MD_COLLAPSE; 
}

function kontakt(obj,type){
  md_elem=obj.parentNode;
  md_partyType=type;
  dialogWindow = openDialog("kontakty", "?ak=md_contacts", ",width=500,height=500,scrollbars=yes");
  md_dexpand1(md_elem);
}

function kontakt1(id, osoba, org, org_en, fce, fce_en, phone, fax, ulice, mesto, admin, psc, zeme, email, url){
	var inputs = flatNodes(md_elem, "INPUT"); 
	var selects = flatNodes(md_elem, "SELECT");
	for(i=0;i<inputs.length;i++){
		var v = inputs[i];
		var num = v.id.substr(0,4);
		//angl. organizace navíc
		if(v.id=="3760eng"){
			 v.value = org_en;
		}
		else if(v.id=="3770eng"){
			 v.value = fce_en;
		}
		else if(v.id=="7001"){
	        v.value = id;
	    }
	    else switch(num){
			case '3750': v.value = osoba; break;
			case '3760': v.value = org; break;
			case '3770': v.value = fce; break;
			case '4080': v.value = phone; break;
			case '4090': v.value = fax; break;
			case '3810': v.value = ulice; break;
			case '3820': v.value = mesto; break;
			case '3830': v.value = admin; break;
			case '3840': v.value = psc; break;
			case '3850': v.value = zeme; break;
			case '3860': v.value = email; break;
			case '3970': v.value = url; break;
		}
	}
	if(md_partyType!=null){
		for(i=0;i<selects.length;i++) if(selects[i].id=='3791'){
			selects[i].value = md_partyType; 
			break;
		}
	}
	if(dialogWindow!=null) dialogWindow.close();
}

function thes(obj){
  md_elem = obj.parentNode;
  md_dexpand1(md_elem);
  var dialogWindow = openDialog("kontakty", "", ",width=400,height=500,scrollbars=no"); 
  dialogWindow.focus();
  var services = 'true';
  if(document.forms[0].ftext) var path = 'false'; 
  else{ 
  	var path = 'true';
  	if(obj.parentNode.parentNode.id.indexOf('_4752_')>-1) services = 'true';
  }	
  if(!dialogWindow.processResult) dialogWindow.location="thesaurus.php?path="+path+"&services="+services+"&lang="+lang;
}

function fromThesaurus(data){
  if(!md_elem) return false;
  var last = -1;
  var vyplneno=0;
  var mainLang = langs.substring(0,3);
  var thesName = null; 
  if(data.version.indexOf(",")>0){
	  var version = data.version.split(",");
	  thesName = version[0]+','+version[1];
  }
  else {
	  var pos = data.version.lastIndexOf(" ");
	  var version = new Array();
	  version[0] = data.version.substring(0,pos);
	  version[1] = data.version.substring(++pos);
	  thesName = version[0]+','+version[1];
	  // hack kvuli odlisnostem mezi GEMET a INSPIRE - uz nebude potreba :(
	  if(data.version.substr(0,7)=="INSPIRE"){
		  version[0] = "GEMET - INSPIRE themes";
		  version[1] = "version 1.0";
	  }
  }
  //kontrola citace thesauru
  /*for(i=0;i<inputs.length;i++){
    //ve vyhl. formulari
    if(inputs[i].id=='ftext'){
      inputs[i].value = data.terms[lang];
  	  window.focus();
      return;
    }
    else{
    }
  }*/
	var currThesNode = null;
	var inputs = flatNodes(md_elem, "INPUT"); 
	var selects = flatNodes(md_elem, "SELECT"); 
  	for(i=0;i<inputs.length;i++){
	    if(inputs[i].id=='3600'+mainLang){
	    	if(inputs[i].value==''){
	    	  	var currThesNode = md_elem;
	    		break;
	    	}
	    }
	}
  	if(!currThesNode){
	  	var inp2 = flatNodes(md_elem.parentNode.parentNode, "INPUT");
	  	for(i=0;i<inp2.length;i++){
	  		if(inp2[i].id=='3600'+mainLang){
	  			if((inp2[i].value)==thesName){
	  				currThesNode = inp2[i].parentNode.parentNode.parentNode;
	  			}
	  		}
		}
  	}
	if(!currThesNode){
		if(!confirm(messages.thes)) return;
		var currThesNode = md_pridej(flatNodes(md_elem, "A")[1]);
	}
	var inputs = flatNodes(currThesNode, "INPUT"); 
	var selects = flatNodes(currThesNode, "SELECT"); 
	
  //vyplneni thesauru
  for(i=0;i<inputs.length;i++){
	  var ll = langs.split("|"); 
	  for(var j in ll){
		  if(inputs[i].id=='3600'+ll[j]) inputs[i].value=version[0]+','+version[1];
		  else if(inputs[i].id=='3940') inputs[i].value=version[version.length-1]; 
		  else if(inputs[i].id=='530'+ll[j]){
			  last = i;
			  if(inputs[i].value!="") vyplneno++;
		  }
	  } 
  } 
  // vyplneni kw
  if(vyplneno>0){
    var d = md_pridej(inputs[last]);
    inputs = flatNodes(d, "INPUT");
    if(!elementInViewport(d)){
    	d.scrollIntoView(false);
    }	
  } 
  // jsou-li termíny
  if(data.terms){
	  for(i=0;i<inputs.length;i++){
		  for(var l in data.terms) if(inputs[i].id=='530'+l){
			  inputs[i].value=data.terms[l];
		  }
	  }
  }
  //je-li uri
  if(data.uri) {
	  for(i=0;i<inputs.length;i++){
		  if(inputs[i].id=='530uri'){
			  inputs[i].value=data.uri; 
			  break;
		  } 
	  }
  }
  // tady přidání URI
  /*if(data.uri){
	    d = md_pridej(inputs[inputs.length-1]);
	    inputs = flatNodes(d, "INPUT");
	    if(!elementInViewport(d)){
	    	d.scrollIntoView(false);
	    }	
	    for(i=0;i<inputs.length;i++){
	        if(inputs[i].id.substring(0,3)=='530'){
	          inputs[i].value=data.uri;
	          break; // opsti po vyplneni prvi jazykove verze
	        }
	    }    
  }*/
  for(i=0;i<selects.length;i++){
    if(selects[i].id=='3950') selects[i].selectedIndex=2; // publication
  }
}

//verze2
function thes1(thesaurus, term_id, langs, terms, date, tdate){
  if(!md_elem) return false;
  var langs=langs.split(",");
  var terms=terms.split(",");
  var inputs = flatNodes(md_elem, "INPUT"); 
  var selects = flatNodes(md_elem, "SELECT"); 
  var last = -1;
  var vyplneno=0;
  for(i=0;i<inputs.length;i++){
    if(inputs[i].id=='ftext'){ // ve vyhled. formulari
      for(j=0;j<langs.length;j++){
        if(langs[j]==lang){
          inputs[i].value += terms[j]+" ";
          break;
        }
      } // mozno doplnit na anglictinu implicitne
      return;
    }  
    //zadavani
    else if(inputs[i].id=='3600') inputs[i].value=thesaurus; 
    else if(inputs[i].id=='3940') inputs[i].value=date; 
    else {
      //kontrola na prazdne hodnoty
      for(j=0;j<langs.length;j++) if(inputs[i].id=='530'+langs[j]){
        last = i;
        if(inputs[i].value!="") vyplneno++;
      }
    } 
  }
  if(vyplneno>0){
     var d = md_pridej(inputs[last]);
     inputs = flatNodes(d, "INPUT");
     if(!elementInViewport(d)){
     	d.scrollIntoView(false);
     }	
  }  
  for(i=0;i<inputs.length;i++)
    for(j=0;j<langs.length;j++) if(inputs[i].id=='530'+langs[j]){
      inputs[i].value=terms[j];
  }
  for(i=0;i<selects.length;i++){
    if(selects[i].id=='3951') selects[i].selectedIndex=tdate;
  }
}

function fc(obj){
  md_elem=obj.parentNode;
  dialogWindow = openDialog("kontakty", "?ak=md_fc", ",width=300,height=500,scrollbars=yes");
  md_dexpand1(md_elem);
}

function fc1(uuid, langs, names, lyrs){
  var lyrlist=lyrs.split(",");
  var inputs = flatNodes(md_elem, "INPUT"); 
  var fList=new Array();
  //---vyplneni nazvu a uuid
  if(langs.indexOf("|")>0){
    var langList=langs.split("|");
    var nameList=names.split("|");
  }  
  else{
    var langList = [langs];
    var nameList = [names];
  }  
  for(var i=0;i<inputs.length;i++){
    var v = inputs[i];
    if(v.id.substr(0,4)=='3600') for(var j=0; j<langList.length; j++){
      if(v.id==('3600'+langList[j])) v.value=nameList[j];
    }   
    else switch(v.id){
      case '2370': fList.push(v); break;
      case '2070': v.value=uuid;
    }
  }
  //---vyplneni vrstev
  for(i=1;i<fList.length;i++){ 
  	fList[i].parentNode.parentNode.removeChild(fList[i].parentNode);
  }	
  var f = fList[0];
  f.value=lyrlist[0];
  var inputs = getMyNodes(f.parentNode, "INPUT");
  for(var i=1;i<lyrlist.length;i++)if(lyrlist[i]!=""){
    var d = md_pridej(inputs[0]);
    inputs = getMyNodes(d, "INPUT"); 
    inputs[0].value=lyrlist[i]; 
  }
  if(dialogWindow!=null) dialogWindow.close();
}


function find_parent(obj){
  md_elem = obj.parentNode;
  //md_dexpand1(md_elem);
  dialogWindow = openDialog("find", "?ak=md_search", ",width=500,height=500,scrollbars=yes");
  dialogWindow.focus();
}

function find_parent1(data){
  // pro zavisle zdroje - pro sluzby (operatesOn)
  if(md_elem.id.substring(0,4)=='5120'){
    var inputs = flatNodes(md_elem, "INPUT");
      for(var i=0;i<inputs.length;i++){
        //
        if(data.idCode){
        	if(inputs[i].id.substr(0,4)=='3600'){ 
        		if(data.idNameSpace.substr(0,4)=="http") inputs[i].value=data.idNameSpace+"#"+data.idCode;
        		else inputs[i].value= data.idNameSpace+":"+data.idCode;
        	} 
        }
      	if(inputs[i].id.substr(0,4)=='3600') inputs[i].value=data.title;
      	else if(inputs[i].id.substr(0,4)=='3601') inputs[i].value=data.title;
      	else if(inputs[i].id.substr(0,4)=='6001') inputs[i].value=data.uuid;
      	else if(inputs[i].id.substr(0,4)=='3002'){ 
      		inputs[i].value=location.href.replace(/\\/g, '/').replace(/\/[^\/]*\/?$/, '')
      		+'/csw/index.php?SERVICE=CSW&VERSION=2.0.2&REQUEST=GetRecordById&OUTPUTSCHEMA=http://www.isotc211.org/2005/gmd&ID='+data.uuid+'#_'+data.uuid;
      	}	
      }
    return false;
  }
  // pro zavisle zdroje - pro sluzby (coupledResource) - nebude pouzito
  else if(md_elem.id.substring(0,4)=='5902'){
    var inputs = flatNodes(md_elem, "INPUT");
      for(var i=0;i<inputs.length;i++){
        //
        /*if(data.idCode){
        	if(inputs[i].id.substr(0,4)=='3583') inputs[i].value=data.idCode;
        	else if(inputs[i].id.substr(0,4)=='3584') inputs[i].value=data.idNameSpace;
        }*/
      	if(inputs[i].id.substr(0,4)=='3600') inputs[i].value=data.title;
      	else if(inputs[i].id.substr(0,4)=='3650') inputs[i].value=data.uuid;
      }
    return false; 
  }
  // pro ostatni
  var inputs = flatNodes(md_elem, "INPUT");
  for(var i=0;i<inputs.length;i++){
    if(inputs[i].type=='text'){
      inputs[i].value=data.uuid;
      break;
    }  
  }  
  // pro importni formulare
  if(md_elem.id=='fill-rec') var txt = document.getElementById("fill-rec-txt");
  else if(md_elem.id=='fill-fc') var txt = document.getElementById("fill-fc-txt");
  // pro data
  else var txt = document.getElementById("parent-text");
  txt.innerHTML=data.title;
}

function find_fc(obj){
  md_elem = obj.parentNode;
  dialogWindow = openDialog("find", "?ak=md_search&fc=1", ",width=500,height=500,scrollbars=yes"); 
  dialogWindow.focus();
}

function find_fc1(uuid, name){
  inputs = flatNodes(md_elem, "INPUT");
  for(var i=0;i<inputs.length;i++){
    if(inputs[i].type=='text') inputs[i].value=uuid;
    break;
  }  
  var txt = document.getElementById("parent_text");
  txt.innerHTML=name;
}

function find_record(obj){
  md_elem = obj.parentNode;
  dialogWindow = openDialog("find", "?ak=md_search", ",width=500,height=500,scrollbars=yes"); 
  dialogWindow.focus();
}

function roundBbox(bbox){
  for(var i=0;i<bbox.length;i++){
    pom = bbox[i].split(" ");
    bbox[i] = Math.round(pom[0]*MD_EXTENT_PRECISION)/MD_EXTENT_PRECISION+" "+Math.round(pom[1]*MD_EXTENT_PRECISION)/MD_EXTENT_PRECISION;
  }
  return bbox;
}

function getBbox(bbox, isPoly){
  if(md_elem==null)return false;
  var poly = flatNodes(md_elem, "TEXTAREA");
  if(poly)  poly=poly[0];
  var inputs = flatNodes(md_elem, 'INPUT');
  for(var i=0;i<inputs.length;i++){
    switch(inputs[i].id){
      case '3440': var x1 = inputs[i]; break;
      case '3450': var x2 = inputs[i]; break;
      case '3460': var y1 = inputs[i]; break;
      case '3470': var y2 = inputs[i]; break;
    }
  }
  var bbox1=roundBbox(bbox.split(","));
  if(isPoly){ // polygon
  	if(!poly){
      alert('polygon not defined in profile');
      return;
    } 
    var s = "";
    for(var i=0;i<bbox1.length;i++) s += ',' + bbox1[i];     
    poly.value = "MULTIPOLYGON((("+s.substr(1)+","+bbox1[0]+")))";
    inputs = flatNodes(poly.parentNode.parentNode.parentNode, "INPUT");
    for(var i=0;i<inputs.length; i++){
      if(inputs[i].type=="radio"){
        inputs[i].click();
        break;
      }  
    }
    //vymazani BBOX
    x1.value = '';
    x2.value = '';
    y1.value = '';
    y2.value = '';
  }
  else { // jen BBOX
    var pom = bbox.replace(/,/g, ' ').split(' ');
    for(var i=0;i<pom.length;i++) pom[i] = Math.round(pom[i]*MD_EXTENT_PRECISION)/MD_EXTENT_PRECISION;
    x1.value=pom[0];
    y1.value=pom[1];
    x2.value=pom[2];
    y2.value=pom[3];
    var e = getMyNodes(md_elem, "DIV");
    var r = flatNodes(e[0], "INPUT");
    console.log(e, r);
    r[0].click();
    //vymazani polygonu
    if(poly){
       poly.value = '';
    }    
  }
}


function openDialog(okno, url, win){
  var win = window.open(url, okno, "toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,copyhistory=no,"+win);
  win.focus();
  return win;
}

function mapa(obj){
  md_elem=obj.parentNode;
  md_mapApp = getBbox;
  //openDialog('micka_mapa','/mapserv/hsmap/hsmap.php?project=micka_map', 'width=360,height=270');
  openDialog('micka_mapa','mickaMap.php', 'width=360,height=270');
}

function uploadFile(obj){
  md_elem = obj.parentNode.parentNode;
  openDialog('upload', 'md_img_upload.php', 'width=400,height=200');
}

function uploadFile1(fileURL){
  inputs = flatNodes(md_elem, "INPUT"); 
  for(var i=0;i<inputs.length;i++){
    if(inputs[i].id=='490'){ 
      inputs[i].value = fileURL; 
      break;
    }
  }
  window.focus();
}

function swapi(o){
  var pom=o.src.lastIndexOf(".");
  if(o.src.charAt(pom-1)=="_")o.src=o.src.substr(0,pom-1)+"."+o.src.substr(pom+1,10);
  else o.src=o.src.substr(0,pom)+"_."+o.src.substr(pom+1,10);
}

function formats(obj){
  md_elem = obj.parentNode;
  md_addMode = false;
  openDialog('formats', '?ak=md_lists&type=formats&lang='+lang, 'width=400,height=500,scrollbars=yes');
}

function formats1(data){
	var inputs = flatNodes(md_elem, "TEXTAREA");
	if(inputs.length>0){
	    for(var i in inputs){
	      	if(typeof(data)=="object"){
	      		var lang = inputs[i].name.substr(1,3);
	      		var f = data[lang].value;
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
	      			var f = data[lang].value;
	      			if(!f) continue;
	      		}
	      		else f = data;
	        	if(md_addMode)inputs[i].value += f; 
	        	else inputs[i].value = f;
	      	}   
	    }   
	}
}

function protocols(obj){
    md_elem = obj.parentNode;
    md_addMode = false;
    openDialog('protocol', '?ak=md_lists&type=protocol&lang='+lang, 'width=400,height=500,scrollbars=yes');
}

function specif(obj){
    md_elem = obj.parentNode;
    md_addMode = false;
    openDialog('specif', '?ak=md_lists&type=specif&fc=specif1&multi=1&lang='+lang, 'width=400,height=500,scrollbars=yes');
}

function specif1(f){
	console.log(f);
	var inputs = flatNodes(md_elem.parentNode.parentNode, "INPUT");
	for(var i=0;i<inputs.length;i++){
		v = inputs[i];
		for(var l in f){
			if(v.id=='3600'+l){
				v.value = f[l].name;
			}	
			if(v.id=='1310'+l){
				v.value = f[l].expl;
			}	
			else if(v.id=='3940') v.value = f[l].publication;
		}	
	}	
	var sels = flatNodes(md_elem.parentNode, "SELECT");
	for(var i=0;i<sels.length;i++){
		if(sels[i].id=='3950'){
			sels[i].value='publication';
		}
	}
}

function crs(obj){
	md_elem = obj.parentNode;
	openDialog('crs', '?ak=md_crs', 'width=200,height=400,scrollbars=yes');
}

function dName(obj){
    md_elem = obj.parentNode;
    md_addMode = false;
    openDialog('dname', '?ak=md_lists&type=dname&lang='+lang, 'width=400,height=500,scrollbars=yes');
}

function crs1(f){
  var pom = f.split(",");
  var inputs = flatNodes(md_elem, "INPUT");
  for(var i=0;i<inputs.length;i++){
    v = inputs[i];
    switch(v.id){
      case '2070': v.value = pom[0]; break; 
      case '2081': v.value = pom[1]; break; 
    }     
  }
}

function dc_kontakt(obj){
  md_elem = obj.parentNode;
  dialogWindow = openDialog("kontakty", "ak=md_contacts&mds=DC", ",width=500,height=500");
}

function dc_kontakt1(osoba, org, fce, phone, fax, ulice, mesto, admin, psc, zeme, email, url){
  var inputs = flatNodes(md_elem, "INPUT");
  var s = osoba;
  if(s!="") s+= ", ";
  s += org;
  s += ", "+mesto;
  if(zeme.trim()!="") s+= ", "+zeme.trim();
  inputs[2].value = s;   
}

function dc_coverage(obj){
  md_elem = obj.parentNode;
  md_mapApp = dc_coverage1;
  openDialog('micka_mapa','mickaMap.php', 'width=360,height=270');
}

function dc_coverage1(s, b){
  var inputs = flatNodes(md_elem, 'INPUT');
  bbox = roundBbox(s.split(','));
  pom1 = bbox[0].split(' ');
  pom2 = bbox[1].split(' ');
  inputs[2].value = "westlimit:"+ pom1[0]+"; southlimit:"+pom1[1]+"; eastlimit:"+pom2[0]+"; northlimit:"+pom2[1];
}

function dc_subject(obj){
  md_elem = obj.parentNode;
  dialogWindow = openDialog("kontakty", "md_thes.php?standard=DC", ",width=300,height=500,scrollbars=yes"); 
}

function dc_subject1(thesaurus, term_id, langs, terms, date, tdate){
  if(!md_elem) return false;
  langs=langs.split(",");
  terms=terms.split(",");
  var inputs = flatNodes(md_elem, "INPUT"); 
  for(var i=0;i<inputs.length;i++){
    for(var j=0;j<langs.length;j++){
      if(inputs[i].id==('10003'+langs[j])){
        inputs[i].value=terms[j];
        break;
      }    
    } 
  }   
}

function dc_format(obj){
  md_elem = obj.parentNode;
  md_addMode = false;
  dialogWindow = openDialog("kontakty", "?ak=md_lists&standard=DC&type=formats&lang="+lang, ",width=400,height=500,scrollbars=yes"); 
}

function md_gazet(obj){
  md_elem = obj.parentNode.parentNode;
  dialogWindow = openDialog("kontakty", "?ak=md_gazcli", ",width=300,height=500,scrollbars=yes"); 
}

function md_gazet1(bbox, first){
  if(md_elem==null)return false;
  var poly = flatNodes(md_elem, "TEXTAREA");
  poly=poly[0];
  var inputs = flatNodes(md_elem, 'INPUT');
  for(var i=0;i<inputs.length;i++){
    switch(inputs[i].id){
      case '3440': var x1 = inputs[i]; break;
      case '3450': var x2 = inputs[i]; break;
      case '3460': var y1 = inputs[i]; break;
      case '3470': var y2 = inputs[i]; break;
    }
  }
  var bbox1=roundBbox(bbox.split(","));
  var s = "";
  if(first){ 
	  poly.value="";
	  inputs = flatNodes(poly.parentNode.parentNode.parentNode, "INPUT");
	  for(var i=0;i<inputs.length; i++){
	    if(inputs[i].type=="radio"){
	      inputs[i].click();
	      break;
	    }  
	  }
	  //vymazani BBOX
	  if(x1){
		  x1.value = '';
		  x2.value = '';
		  y1.value = '';
		  y2.value = '';
	  }
  }
  poly.value = poly.value.concat(bbox);

}

function importSelect(obj){
  var pom = document.getElementById('input_hide');
  if(obj.value.substr(0,4)=='ESRI') pom.style.display='';
  else pom.style.display='none';
  document.forms.newRecord.fc.value='';
  //document.getElementById('parent_text').innerHTML='';
}

function clearForm(){
  var fields = document.getElementsByTagName("INPUT");
  for(var i=0; i<fields.length;i++) if(fields[i].type=='text')fields[i].value='';
  var selects = document.getElementsByTagName("SELECT");
  for(i=0; i<selects.length;i++) selects[i].selectedIndex=0; 
  var texareas = document.getElementsByTagName("TEXTAREA");
  for(i=0; i<texareas.length;i++) texareas[i].value=''; 
  if(document.getElementById('results'))document.getElementById('results').innerHTML='';
  return false;
}

//vyplneni labelu v seznamu kontaktu
function fillLabel(o){
  if(o.value!="") return;
  var label=(document.forms[0].pers.value);
  var za = "";
  if (label!=""){
    var carka = label.lastIndexOf(",");
    if(carka>-1){za=label.substr(carka,99); label=label.substr(0,carka); }
    if(label.indexOf(" ")>-1){
      var jmena = label.split(" ");
      if(jmena.length>1){
        label = "";
        for(var i=jmena.length-1;i>=0;i--) label += jmena[i]+" ";
      }   
    }  
  }
  else label = document.forms[0].organisation.value;
  o.value=label+za;
}
 
function md_aform(obj,por,asnew){
  if(typeof(por) == 'undefined'){
    var pom = obj.parentNode.id.split('_');
    por = pom[1]; 
  }
  asnew = typeof(asnew) == 'undefined' ? 0 : asnew;
  var obsah = flatNodes(obj.parentNode, "DIV");
  if(obsah.length>0) var je = true;
  var el = document.getElementById('currentFeature');
  if(el){
    if(!window.confirm(messages.leave + ' ?')) return;
    var obrs = flatNodes(el.parentNode, "IMG");
    obrs[0].src = obrs[0].src.substring(0, obrs[0].src.lastIndexOf("/")+1) + MD_EXPAND; 
    el.parentNode.removeChild(el);
  } 
  if(je) return;  
  obj.src= obj.src.substring(0, obj.src.lastIndexOf("/")+1) + MD_COLLAPSE; 
  var container = document.createElement("div");
  container.id = 'currentFeature';
  obj.parentNode.appendChild(container);
  var url = "?ak=inmda&recno="+md_recno+"&por="+por+"&asnew="+asnew;
  var ajax = new HTTPRequest;
  ajax.get(url, "", md_drawFeature, false); 
}

function md_drawFeature(r){
  if(r.readyState == 4){
	  var el = document.getElementById('currentFeature');
	  if(el){
		  el.innerHTML = r.responseText+"<iframe name='featureFrame' style='display:none'></iframe>";
      //window.scrollTo(0, el.parentNode.offsetTop);
      //fc_initForm();
	  }
  }  
  else {
	  if(el) el.innerHTML = "<img src='themes/default/img/indicator.gif'>";
  }
}
  
function refreshFeature(por, label){
  var el = document.getElementById('currentFeature');
  if(!el){
    alert('Error: element not found!');
    return false;
  }  
  var spans = flatNodes(el.parentNode, "SPAN");
  spans[0].innerHTML = label;
  var obrs = flatNodes(el.parentNode, "IMG");
  obrs[0].src = obrs[0].src.substring(0, obrs[0].src.lastIndexOf("/")+1) + MD_EXPAND; 
  el.parentNode.id="12_"+por;
  el.parentNode.removeChild(el);
}


function fc_getId(obj){
  if(!obj) return -1; 
  var pom = obj.parentNode.id.split('_');
  return pom[1];
}

function fc_new(obj){
  var por = fc_getId(obj);
  //por = typeof(obj) == 'undefined' ? -1 : por;
  var newDiv = document.createElement("div");
  newDiv.id = "12_-1";
  newDiv.innerHTML="<img id=\"PA__0_\" onclick=\"md_aform(this);\" src=\"themes/default/img/expand.gif\"/><span class='f'>???</span><a href=\"javascript:void(0);\" onclick=\"fc_new(this);\"><img src='img/copy.gif'></a> <input class=\"b\" type=\"button\" onclick=\"fc_smaz(this);\" value=\"-\"/>";
  var obj = document.getElementById("addF");
  obj.parentNode.insertBefore(newDiv,obj);
  md_aform(newDiv.firstChild,por,1);
}

function fc_smaz(obj){
  if(!confirm(messages.del + ' ?')) return false;
  var por = obj.parentNode.id.split('_');
  var url = "?ak=mddela&recno="+md_recno+"&por="+por[1];
  var ajax = new HTTPRequest;
  ajax.get(url, "", fc_smaz1, false); 
  obj.parentNode.parentNode.removeChild(obj.parentNode); //pak presunout do fc_smaz1
}

function fc_smaz1(r){
  if(r.readyState == 4) {}
}

function fc_storno(){
  var el = document.getElementById('currentFeature');
  if(el){
    var obrs = flatNodes(el.parentNode, "IMG");
    obrs[0].src = obrs[0].src.substring(0, obrs[0].src.lastIndexOf("/")+1) + "MD_EXPAND"; 
    var pom = el.parentNode.id.split('_');
    if(pom[1]==-1)el.parentNode.parentNode.removeChild(el.parentNode);
    else el.parentNode.removeChild(el);
  }
}

function showMap(url){
  // TODO - do konfigurace
  var myURL = "http://geoportal.gov.cz/web/guest/map?wms="+url;
  //var myURL = "http://onegeology-europe.brgm.fr/geoportal/viewer.jsp?id=" + url; 
  //window.open(myURL, "wmswin", "width=550,height=700,dependent=yes,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,copyhist=no");
  var w = window.open(myURL, "portal", "");
  w.focus();
}

function md_datePicker(id){
  monthArrayLong = new Array('1 / ', '2 / ', '3 / ', '4 / ', '5 / ', '6 / ', '7 /', '8 / ', '9 / ', '10 / ', '11 / ', '12 / ');
  datePickerClose = " X ";
  if(lang=='cze'){
    dayArrayShort = new Array('Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So');
    datePickerToday = "Dnes";
  	displayDatePicker(id,false,'dmy','.');
  }	 
  else{
    displayDatePicker(id,false,'ymd','-');
  }  
}

var md_constraint = function(obj){
  md_elem = obj.parentNode;
  md_addMode = false;
  openDialog('protocol', '?ak=md_lists&type=uselim&multi=1&lang='+lang, 'width=400,height=500,scrollbars=yes');
}

var md_serviceType = function(obj){
  md_elem = obj.parentNode;
  md_addMode = false;
  openDialog('protocol', '?ak=md_lists&type=service&lang='+lang, 'width=400,height=500,scrollbars=yes');
}

var md_lineage = function(obj){
  md_elem = obj.parentNode;
  md_addMode = false;
  openDialog('protocol', '?ak=md_lists&type=lineage&multi=1&lang='+lang, 'width=400,height=500,scrollbars=yes');
}

var md_processStep = function(obj){
  md_elem = obj.parentNode;
  md_addMode = true;
  openDialog('protocol', '?ak=md_lists&type=steps&multi=1&lang='+lang, 'width=400,height=500,scrollbars=yes');
}

var oconstraint = function(obj){
  md_elem = obj.parentNode;
  md_addMode = false;
  openDialog('protocol', '?ak=md_lists&type=oconstraint&multi=1&lang='+lang, 'width=400,height=500,scrollbars=yes');
}

var changeSort = function(id, ordid, constraint, lang, recs){
	var o = document.getElementById(id);
	if(!o) return;
	var ord = document.getElementById(ordid);
	if(!ord) return;
	window.location="index.php?service=CSW&request=GetRecords&format=text/html&query="+ constraint + "&LANGUAGE=" + lang + "&MAXRECORDS=" + recs +"&sortby=" +o.value+":"+ord.value;
}

var showLogin = function(){
	var f = document.getElementById("loginForm");
	if(f){
		if(f.style.display=="inline-block"){
			f.style.display="none";
		}
		else{
			f.style.display="inline-block";
		} 	
	}

}

var checkId = function(o){
	var nody = flatNodes(o.parentNode.parentNode, "INPUT");
	if(nody[0].value!=''){
		ajax = new HTTPRequest;
		ajax.scope = o;		
		ajax.get("csw/?request=GetRecords&format=text/json&query=ResourceIdentifier%20like%20%27"+nody[0].value+"%27", null, checkIdBack, false);
	}
}

var checkIdBack = function(r){
	if(r.readyState == 4){
		var uuid = document.forms[0].uuid.value;
		eval("var data="+r.responseText);
		var dup = 0;
		if(data.matched == 0 || (data.matched == 1 && data.records[0].id == uuid)){
			ajax.scope.className="id-ok";
		}
		else {
			ajax.scope.className="id-fail";
			alert("ID již existuje");
		}
	}		  

}

var md_callBack = function(cb, uuid){
	if(cb.substring(0,6)=='opener'){
		var fn = cb.substring(7);
		ajax = new HTTPRequest;
		ajax.get("?ak=dummy&cb=", null, function(r){
			if(r.readyState == 4){
				if(!opener) {
					alert('Opener window is closed');
					return;
				}
				opener[fn](uuid);			
				window.close();
			}	
		}, false);
	}
}

micka.initMap=function(){
	micka.extent = new OpenLayers.Bounds();
	micka.extents =new Array();
	micka.flyr = new OpenLayers.Layer.Vector();
	micka.flyr.styleMap = new OpenLayers.StyleMap({
    	"default": new OpenLayers.Style({
    		fillOpacity: 0,
    		strokeColor: "#FF7F00"
        }),
        "select": new OpenLayers.Style({
        	strokeColor: "#3182BD",
        	fillOpacity: 0.5,
        	fillColor: "#3182BD"
        })
    })

	micka.overmap = new OpenLayers.Map({
        div: "overmap",
        projection: "EPSG:900913",
        theme: null,
        eventListeners: {
      	    "moveend": this.mapEvent,
            "zoomend": this.mapEvent,	
            scope: this
        },
        controls: [
            //new OpenLayers.Control.Attribution(),
           new OpenLayers.Control.Navigation({
                dragPanOptions: {
                    enableKinetic: true
                }
            }),
            new OpenLayers.Control.Zoom()
        ],
        layers: [
 				new OpenLayers.Layer.XYZ(
				        "OpenStreetMap", 
				        [
				            "http://otile1.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.png",
				            "http://otile2.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.png",
				            "http://otile3.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.png",
				            "http://otile4.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.png"
				        ],
				        {
				            attribution: "Data, imagery and map information provided by <a href='http://www.mapquest.com/'  target='_blank'>MapQuest</a>, <a href='http://www.openstreetmap.org/' target='_blank'>Open Street Map</a> and contributors, <a href='http://creativecommons.org/licenses/by-sa/2.0/' target='_blank'>CC-BY-SA</a>  <img src='http://developer.mapquest.com/content/osm/mq_logo.png' border='0'>",
				            transitionEffect: "resize"
				        }
				    ),	                   
            micka.flyr
        ],
        center: new OpenLayers.LonLat(0, 0),
        zoom: 0
    });
	
	// prochazi elementy
    var wgs = new OpenLayers.Projection("EPSG:4326");
	var meta = document.getElementsByTagName("META");
	for(var i=0; i<meta.length; i++){
		if(meta[i].getAttribute("itemprop")=="box"){
			b = new OpenLayers.Bounds(meta[i].getAttribute("content").split(" "));
			if(b.left){
				b.transform(wgs, micka.overmap.getProjection());
				micka.extent.extend(b);
				b = new OpenLayers.Feature.Vector(b.toGeometry());
				b.id = "r-"+meta[i].getAttribute("id").split("-")[1];
				micka.flyr.addFeatures(b);
			}	
		}
	}

	if(micka.extent.left){
		micka.highlightCtrl = new OpenLayers.Control.SelectFeature(micka.flyr, {
	        hover: true,
	        highlightOnly: true,
	        renderIntent: "select",
	        eventListeners: {
	            //beforefeaturehighlighted: micka.beforeHover,
	            featurehighlighted: micka.onHover,
	            featureunhighlighted: micka.onUnhover
	        }
	    });
		micka.overmap.addControl(micka.highlightCtrl);
		micka.highlightCtrl.activate();
		//nastavi extent mapy
		micka.overmap.zoomToExtent(micka.extent);
	}	
}

micka.unhover = function(o){
	var f = micka.flyr.getFeatureById(o.id);
	if(f){
		micka.highlightCtrl.unselect(f);
	}
}

micka.onHover = function(e){
	var div = document.getElementById(e.feature.id);
	div.style.background="#EDF5F8"; // TODO - nejak jinak
}

micka.onUnhover = function(e){
	var div = document.getElementById(e.feature.id);
	div.style.background="#FFFFFF";
}

micka.hover = function(o){
	var f = micka.flyr.getFeatureById(o.id);
	if(f){
		micka.highlightCtrl.select(f);
	}
}

