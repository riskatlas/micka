<?php 
$dbconnect = FALSE;
require 'include/application/micka_config.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <META HTTP-EQUIV="Content-Type" CONTENT="text/html;charset=UTF-8">
  <title>THESAURUS</title>
  <link rel="stylesheet" type="text/css" href="<?php echo EXTJS_PATH; ?>/resources/css/ext-all.css" />
  <link rel="stylesheet" type="text/css" href="<?php echo EXTJS_PATH; ?>/resources/css/ext-all-gray.css" />
  <!-- <script type="text/javascript" src="<?php echo EXTJS_PATH; ?>/adapter/ext/ext-base.js"></script> -->
  <script type="text/javascript" src="<?php echo EXTJS_PATH; ?>/ext-all-debug.js"></script>
  <script type="text/javascript" src="thesaurus/HS.js"></script>  
  <script type="text/javascript" src="thesaurus/thesaur.js"></script>      
  <script type="text/javascript" src="thesaurus/translations.js"></script>      
  <script type="text/javascript" src="thesaurus/InspireServiceReader.js"></script>      
  <script src="/wwwlibs/jquery/jquery-1.10.2.min.js"></script>
  <style>
  	.thes-term {color:#C00000; font-weight: bold; margin:2px; font-size:12px; text-align:left; width:320px;display: inline-block; overflow: hidden}
  	.thes-description div div {font-family: arial,tahoma; font-size:11px; padding:4px; color:#505050}
  	.thes-link a span {color:#0040D0}
    	.list-group-item {padding: 5px 10px; display: block; color: black; text-decoration: none; border-bottom: 1px solid #EEE}
    	.list-group-item:hover {background: #EFEFEF; }
        .hrow {font-weight:bold; color: #00A19A}
        .level-2 {padding-left: 20px;}
        .panel-heading {background: #E8E8E8; border-bottom: 1px #D8D8D8 solid; padding: 3px;}
    #result {
        position: absolute;
        top: 0px;
        left: 410px;
    }
    #result h2 {
        color: #50A19A;
    }
    #result table {
        border: 1px solid #EEE;
        border-collapse: collapse;
        margin-right: 10px;
    }
    #result table th, #result table td {
        border: 1px solid #EEE;
        text-align: left;
        padding: 5px;    
    }
  </style>

<script type="text/javascript">
window.focus(); 

  var getURLParams = function(){
     var params = window.location.href;
     var result = {};
     if(params.indexOf("?") > -1 ){
       params = params.substr(params.indexOf("?")+1).toLowerCase();
       params = params.split("&");
       for(var i=0;i<params.length;i++){
         var pom = params[i].split("=");
         result[pom[0]] = pom[1];
       }
     }
     return result;
  }

var params = getURLParams();
HS.setLang(HS.getLastLangCode());
var langs = null;

var processResult = function(data){
    var terms = [];
    for(var l in data.terms) terms[HS.getCodeFromLanguage(l)] = data.terms[l];
    data.terms = terms;
    if(opener){
        opener.focus();
        opener.fromThesaurus(data);
      //close();
    }
    else {
        console.log(data);
        var el = document.getElementById('result');
        var html= "<h2>Selected term</h2><table>";
        html += "<tr><th>id</th><td>"+data.uri+"</td><tr>";
        html += "<tr><th>term</th><td>"+data.terms[HS.getLastLangCode()]+"</td><tr>";
        html += "<tr><th>source</th><td>"+ data.version+"</td><tr>";
        html += "<tr><th>definition</th><td>"+ "</td><tr>";
        el.innerHTML = html+ "</table>";
    }      
}

Ext.onReady(function(){
  if(opener){
      if(opener.langs){
        if(opener.langs.indexOf("|")>-1) langs = opener.langs.split("|");
        else langs = [opener.langs];
      }
      else langs = [opener.lang];
  }
  else {
    langs = [params.lang];
  } 
  langs2 = [];
  for(var i=0;i<langs.length;i++) langs2.push(HS.getCodeFromLanguage(langs[i],2));
  var noServices = (params.services=='true') ? false : true;
 
  var gemet = new ThesaurusReader({
  	  appPath: 'thesaurus/',
      lang: HS.getLang(2),
      outputLangs: langs2, 
      separator: '/',
      returnPath: (params.path=='true'),
      defaultThesaurus: 'INSPIRE',
      thesaurus: {
    	    'GEMET': {},
    	   	'INSPIRE': {},
	   	    '1GE': {
  	   	          url: 		 "http://gemet.esdi-humboldt.cz/thesaurus/",
	   		      concept:   "http://www.onegeology-europe.eu/concept/",
			      //theme:     "http://www.onegeology-europe.eu/concept/",        
			      group:   	 null,
			      supergroup: null,
			      firstClick: false
		    },
            'CGS': {
            	url:        "http://gemet.esdi-humboldt.cz/thesaurus/",
            	concept:    "http://www.geology.cz/concept/",
            	theme:      "http://www.geology.cz/theme/", 
                returnPath: false,       
            	group:   	null,
            	supergroup: null,
            	firstClick: false
            }            
	},   
    handler: processResult
  });
  
  var services = new InspireServiceReader({
  	lang: HS.getLang(2),
  	outputLangs: langs2, 
  	handler: processResult,
  	serviceUrl: 'thesaurus/'
  }); 

  var keywordManager = new Ext.TabPanel({
    height: 500, width:400,
    activeTab: 0,
    items: [
            {id:'gemet', title: 'GEMET / INSPIRE / 1GE', items: [gemet], layout: 'fit'},
            {id:'inspire', title: 'M4EU', html: ''
                + '<div class="panel-heading"><select class="form-control" id="sel"></select></div>'
                + '<div class="list-group" id="codelist"></div>',
                layout: 'fit', autoScroll: true,
                listeners: {
                    render: function(){
                        kw.drawMenu({
                            lang: 'en',
                            id: 'sel'  
                        });

                    	kw.drawCodelist({
                            uri: 'Minerals4EU',
                            lang: 'en',
                            id: 'codelist'  
                        });
                    }
                }
            },
            {id:'services', title: HS.i18n('InspireServices'), items: [services], layout: 'fit', disabled: noServices}
      ]
  }) 
  keywordManager.render('thesDiv');
  //b = keywordManager.getItem('gemet');
  //b.doLayout(); 
  Ext.QuickTips.init(); 
});
  

var kw = {
        proxy: "util/keywords.php",
        codelist: null,
        result: null,
        lang: HS.getLang(3),
        langs: opener.langs,
    
        drawMenu: function(config){
            var el;
            var a = $.ajax({
                url: this.proxy+'?lang='+kw.lang, 
                dataType: 'json',
                success: function(data){
                    var d = $("#"+config.id);
                    for(i in data.values){
                        el = $('<option value="'+data.values[i].id+'" title="'+data.values[i].definition[kw.lang]+'">'+data.values[i].label[kw.lang]+'</option>');
                        d.append(el);
                    }
                    d.on('change', function(e){;
                        kw.drawCodelist({
                            uri: e.target.value,
                            lang: kw.lang,
                            id: 'codelist'  
                        });
                    });
                }    
            });
        } ,   

        		
        drawRow: function(d, row, cls){
            if(row.level){
                cls = ' level-'+ row.level
            }
            var el = $('<a href="#" class="list-group-item'+cls+'" data-toggle="tooltip" title="'+row.definition[kw.lang]+'">'+row.label[kw.lang]+'</a>');
            el.on('click', {id: row.id, title: row.label[kw.lang]}, function(e){
                if(opener){
                    kw.returnTerms(e.data.id, langs2);
                }
                 //samostatne volani bez micky
                 else {
                     var el = document.getElementById('result');
                     var html= "<h2>Selected term</h2><table>";
                     html += "<tr><th>id</th><td>"+e.data.id+"</td><tr>";
                     html += "<tr><th>term</th><td>"+e.data.title+"</td><tr>";
                     html += "<tr><th>source</th><td>"+ codelist.register.label[kw.lang] + ' ' +codelist.register.version + ', '+ codelist.register.publication+"</td><tr>";
                     html += "<tr><th>definition</th><td>"+ row.definition[kw.lang]+"</td><tr>";
                     el.innerHTML = html+ "</table>";
                 }   
                                          
            });
            d.append(el);            
        },
        		
        drawCodelist: function(config){
            var a = $.ajax({
                url: this.proxy+"?lang="+HS.getLang(3)+"&uri="+encodeURIComponent(config.uri), 
                dataType: 'json',
                success: function(data){
                	codelist = data.codelist;
                    var d = $("#"+config.id);
                    d.text('');
                    kw.drawRow(d, codelist, ' hrow');
                    for(i in data.values){
                        kw.drawRow(d, data.values[i], '');
                    }
                }    
            });
        },
        
        returnTerms: function(id, langs){
            console.log(kw.langs);
            var a = $.ajax({
                url: this.proxy+"?lang="+kw.langs+"&uri="+encodeURIComponent(id), 
                dataType: 'json',
                success: function(data){
                    opener.focus();
                    var terms = new Array();
                    opener.fromThesaurus({
                        uri: data.codelist.id, 
                        terms: data.codelist.label, 
                        version: codelist.register.label[kw.lang] + ' ' +codelist.register.version + ', '+ codelist.register.publication
                    });
                }    
            });
        }    
                
    };

    
    $(function(){
        kw.drawMenu({
            lang: 'en',
            id: 'sel'  
        });

    	kw.drawCodelist({
            uri: 'Minerals4EU',
            lang: 'en',
            id: 'codelist'  
        });
     });   

  
</script>
</head>
<body>
<div id='thesDiv'></div>
<script>
  if(!opener){
    document.write('<div id="result"></div>');  
  }
</script>
</body>
</html>
