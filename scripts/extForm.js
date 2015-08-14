var _thesaurusWindow = null;

Ext.define("SearchForm",{
	f: null,
	qstr: "",
	tpl: '<tpl for="."><li style="height:22px;" class="x-boundlist-item" role="option">{label}</li></tpl>',
	
	config: {
		//title: "Nový postoj",
		frame: true,
		border: false,
		width: 700,
		//width: 380,
		//height: 450
		//xslPath: "/projects/catalogue-trunk/xsl"
	},

	constructor: function(config){
		this.initConfig(config);
		//Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
		
	    // --- XML cteni ---
	  	/*var xmlRecordDef = Ext.data.Record.create([
	      {name: 'name', mapping: '@name'},
	      {name: 'label', mapping: '/'}
	  	]);*/

	  	/*var xmlRecordDefCode = Ext.data.Record.create([
	      {name: 'name', mapping: '@code'},
	      {name: 'label', mapping: '/'}
	  	]);*/

	    /*var topicsReader = new Ext.data.XmlReader({
	      record: "topicCategory/value"
	    }, xmlRecordDef);*/
		//console.log(this.config.xslPath + '/codelists_'+/*HS.getLang()+*/'cze.xml');
		isGuest = (this.config.user=="guest");
		
		Ext.define('xmlModel', {
		    extend: 'Ext.data.Model',
		    fields: [
			    {name: "name", mapping: "@name"},
			    {name: "label", mapping: "/"}
			]
		});

		Ext.define('partyModel', {
		    extend: 'Ext.data.Model',
		    fields: [
			    {name: "id", mapping: "id"},
			    {name: "name", mapping: "name"}
			]
		});
		
		Ext.define('scaleModel', {
		    extend: 'Ext.data.Model',
		    fields: [
			    {name: "id", mapping: "id"},
			    {name: "value", mapping: "value"}
			]
		});

		var topicsStore = Ext.create("Ext.data.Store", {
		   model: "xmlModel",
		   proxy: {
			   type: "ajax",
			   url: "util/codelists.php",
			   extraParams: {language: lang},
			   reader: {
				   type: "xml",
				   root: "map",
				   record: "topicCategory/value"
			   }	   
	   	  }
	   });
		
		var typeStore = Ext.create("Ext.data.Store", {
			   model: "xmlModel",
			   proxy: {
				   type: "ajax",
				   url: "themes/default/codelists_"+lang+".xml",
				   extraParams: {language: lang},
				   reader: {
					   type: "xml",
					   root: "map",
					   record: "crType/value"
				   }	   
		   	  }
	   });

		var inspireStore = new Ext.data.Store({
			model: "xmlModel",
	    	proxy:{
	    		type: "ajax",
				url: "util/codelists.php",
				extraParams: {language: lang},
	    		reader: {
	    			type: 'xml',
	    			root: 'map',
	    			record: 'inspireKeywords/value'
	    		}		
    		}
	    });
		
		var serviceTypeStore = new Ext.data.Store({
			model: "xmlModel",
	    	proxy:{
	    		type: "ajax",
	    		url: "themes/default/codelists_"+lang+".xml",
				extraParams: {language: lang},
	    		reader: {
	    			type: 'xml',
	    			root: 'map',
	    			record: 'serviceType/value'
	    		}		
    		}
	    });
		
		var appTypeStore = new Ext.data.Store({
			model: "xmlModel",
	    	proxy:{
	    		type: "ajax",
	    		url: "themes/default/codelists_"+lang+".xml",
				extraParams: {language: lang},
	    		reader: {
	    			type: 'xml',
	    			root: 'map',
	    			record: 'applicationType/value'
	    		}		
    		}
	    });
		
		var dataTypeStore = new Ext.data.Store({
			model: "xmlModel",
	    	proxy:{
	    		type: "ajax",
	    		url: "themes/default/codelists_"+lang+".xml",
				extraParams: {language: lang},
	    		reader: {
	    			type: 'xml',
	    			root: 'map',
	    			record: 'dataType/value'
	    		}		
    		}
	    });
	
	
	    //--- seznam osob do combo boxu
	    var partyStore = new Ext.data.JsonStore({
	    	model: "partyModel",
	    	proxy: {
	    		type: "ajax",
	    		url: 'util/suggest.php',
	    		extraParams: {type: 'person'},
	    		reader:{
	    			type: 'json',
	    			root: 'records'
	    		}
	    	}	
	    });

	    //--- seznam osob do combo boxu
	    var mdPartyStore = new Ext.data.JsonStore({
	    	model: "partyModel",
	    	proxy: {
	    		type: "ajax",
	    		url: 'util/suggest.php',
	    		extraParams: {type: 'mdperson'},
	    		reader:{
	    			type: 'json',
	    			root: 'records'
	    		}
	    	}	
	    });

	    //--- seznam osob do combo boxu
	    var scaleStore = new Ext.data.JsonStore({
	    	model: "scaleModel",
	    	proxy: {
	    		type: "ajax",
	    		url: 'util/suggest.php',
	    		extraParams: {
	    			type: 'denom'
	    		},
	    		reader:{
	    			type: 'json',
	    			root: 'records'
	    		}
	    	}	
	    });

	    Ext.define('thesModel', {
		    extend: 'Ext.data.Model',
    		fields: [
				{name: 'id', mapping: 'uri'},
				{name: 'term', mapping: 'preferredLabel.string'},
				{name: 'definition', mapping: 'definition'}
			]
		});

	    var suggestionThes = function(obj, o){
	    	var q = o.params.query;
	    	if(!q) q = '.';
	    	var url = obj.proxy.extraParams.url;
	    	obj.proxy.extraParams.url = url.substring(0, url.indexOf("regex=")+6)+q;
	    }

		/*var gemetStore = new Ext.data.Store({
			model: "thesModel",
	    	proxy:{
	    		type: "ajax",
	    		url: 'thesaurus/proxy.php',
	    		extraParams: {url: 'http://www.eionet.europa.eu/gemet/getConceptsMatchingRegexByThesaurus?thesaurus_uri=http://www.eionet.europa.eu/gemet/concept/&language='+HS.getLang(2)+'&regex=' },
	    		reader: {
	    			type: 'json',
	    			root: 'results',
	    			idProperty: 'term'
	    		}		
    		}
	    });*/

		/*var cgsStore = new Ext.data.Store({
			model: "xmlModel",
	    	proxy:{
	    		type: "ajax",
	    		url: "themes/default/codelists_"+lang+".xml",
				extraParams: {language: lang},
	    		reader: {
	    			type: 'xml',
	    			root: 'map',
	    			record: 'geology/value'
	    		}		
    		}
	    });*/

		/*var oneGEStore = new Ext.data.Store({
			model: "thesModel",
	    	proxy:{
	    		type: "ajax",
	    		url: 'thesaurus/proxy.php',
	    		extraParams: {url: 'http://gemet.esdi-humboldt.cz/thesaurus/getConceptsMatchingRegexByThesaurus?thesaurus_uri=http://www.onegeology-europe.eu/concept/&language='+HS.getLang(2)+'&regex=' },
	    		reader: {
	    			type: 'json',
	    			root: 'results',
	    			idProperty: 'term'
	    		}		
    		}
	    });*/


	   // gemetStore.on('beforeload', suggestionThes, this, {});
	   // inspireStore.on('beforeload', suggestionThes, this, {});
	    //oneGEStore.on('beforeload', suggestionThes, this, {});
	    this.fillKeywords = function(result){
	    	this.f.getForm().setValues({'keywords': result.terms[HS.getLang(2)]});
	      	_thesaurusWindow.hide();
	      };


		   var thesaurusReader = new ThesaurusReader({
		    	lang: HS.getLang(2),
		    	outputLangs: [HS.getLang(2)],
		    	separator: '/',
		    	appPath: "thesaurus/",
		    	returnPath: false,
		    	returnInspire: true,
		    	handler: this.fillKeywords,
		    	scope: this,
		    	defaultThesaurus: 'INSPIRE',
		      	thesaurus: {
		      		'GEMET': {},
		    	   	'INSPIRE': {},
		        }
		     });

	    var keywordManager = new Ext.Panel({
	    	id:'gemet', 
	    	items: [thesaurusReader],
	    	border: false,
	    	layout: 'fit'
	    });

	    var kw = new Ext.form.TriggerField({
	 	   fieldLabel: HS.i18n('Keywords'),
	 	   width: 250,
	 	   emptyText: HS.i18n("Write or select from thesaurus"),
	 	   name:'keywords'
	    });

	    kw.onTriggerClick = function(a,b,c){
	 	   if(!_thesaurusWindow){
	 		   _thesaurusWindow = Ext.create('Ext.window.Window', {
	   				width:400,
	   				height:500,
	   				layout: 'fit',
	   				closeAction:'hide',
	   				title: 'Thesaurus',
	   				items: keywordManager
	   			});
	   		}
	    		_thesaurusWindow.show();
	    };
	    
	    // zpracovani ENTER z formularoveho pole
	    this.onEnter = function(f,e){
	        if(e.getKey() == e.ENTER){
	            this.parse();
	        }
	    }

		this.f = Ext.create('Ext.form.Panel', { 
			//title: "pokus",
			//stateful: true,
			//stateId: 'micka-form',
			id: "searchForm",
			frame: this.config.frame,
			width: this.config.width,
			height: this.config.height,
			defaultType: 'textfield',
			renderTo: this.config.renderTo,
			buttonAlign: "center",
			fieldDefaults: {
				labelAlign: 'top'
				//width: 250
			},
			padding: 5,
			standardSubmit: true,
			url: "index.php",
			baseParams: {ak: "find", request: "GetRecords"},
			items: [
					
					//,this.comboServiceType,
		        	
    		    	/**/
					{
		        		layout: "hbox",
		        		anchor: '100%',
		        		xtype: "container",
		        		items: [{
		        			flex: 1,
		        			xtype: "container",
		        			defaultWidth: 250,
		        			layout: "anchor",
		        		    items:[
							{
								name: 'text',
								xtype: 'textfield',
								width: 250,
								//value: initValues.text,
								selectOnFocus:true,
								fieldLabel: HS.i18n('Fulltext'),
								listeners: {specialkey: this.onEnter, scope: this},
								minChars: 3
							},
							{
								xtype: 'radiogroup',
								//fieldLabel: HS.i18n('Free text scope'),
								width: 250,
								autoHeight: true,
							    items: [
							        {boxLabel: HS.i18n('All'), name: 'ttype', inputValue: 'AnyText', checked: true},
							        {boxLabel: HS.i18n('Title'), name: 'ttype', inputValue: 'title'},
							        {boxLabel: HS.i18n('Abstract'), name: 'ttype', inputValue: 'abstract', width: 100}
							        //{boxLabel: HS.i18n('Lineage'), name: 'ttype', inputValue: 'Lineage'}
							    ]
							},
							{
		    		    		xtype: 'combo',
		    		    		width: 250,
		    		    		fieldLabel:HS.i18n('INSPIRE Theme'),
		        		    	id: "inspire-select",
		        		    	minChars: 3,
		        		    	name: 'inspire',
		        		    	typeAhead: true,
		        		    	store: inspireStore,
		        				displayField:'label',
		        				valueField: 'name',
		        		    	triggerAction: 'all'
		    		    	},
		        		        kw,
		        		    {
		    					xtype: 'combo',
		    					width: 250,
		    					autoload: true,
		    					fieldLabel:HS.i18n('Type'),
		    					id: 'type-select',
		    					displayField:'label',
		    					valueField: 'name',
		    					fieldCls: 'ux-pointer',
		    					name: 'type',
		    					store: typeStore,
		    					tpl: new Ext.XTemplate(this.tpl),
		    					listeners:{
		    						scope:this,
		    					    "change": this.changeType
		        		    	},
		        				editable: false
		    				},{
		    					xtype: 'combo',
		    					width: 250,
		    					autoload: true,
		    					fieldLabel:HS.i18n('Topic category'),
		    					id: 'topic-select',
		    					displayField:'label',
		    					valueField: 'name',
		    					forceSelection: true,
		    					minChars: 3,
		    					name: 'topic',
		    					typeAhead: true,
		    					store: topicsStore,
		    					triggerAction: 'all'
		    		    	},{
			        		    xtype: 'combo',
			        		    id: "scale-select",
			        		    width: 100,
			        		    fieldLabel:HS.i18n('Scale')+' 1',
			        		    forceSelection: true,
			        		    name: 'denominator',
				        		displayField: "value",
				        		valueField: "value",
				        		hidden: true,
			        		    store: scaleStore,
		    					tpl: new Ext.XTemplate('<tpl for=".">' + '<li style="height:22px;" class="x-boundlist-item" role="option">' + '{value}' + '</li></tpl>'),
		        				editable: false,
		        				fieldCls: 'ux-pointer'
		        		     },	{
		    		    		xtype: 'combo',
		    		    		width: 250,
		    		    		fieldLabel:HS.i18n('Service Type'),
		        		    	id: "service-type-select",
		        		    	//minChars: 3,
		        		    	name: 'stype',
		        		    	store: serviceTypeStore,
		        				displayField:'label',
		        				valueField: 'name',
		        				fieldCls: 'ux-pointer',
		        				//hidden: true,
		        				//listeners: {"change": this.changeValue },
		    					tpl: new Ext.XTemplate(this.tpl),
		        				editable: false
	        		    	},{
		    		    		xtype: 'combo',
		    		    		width: 250,
		    		    		fieldLabel:HS.i18n('Application Type'),
		        		    	id: "application-type-select",
		        		    	name: 'atype',
		        		    	store: appTypeStore,
		        				displayField:'label',
		        				valueField: 'name',
		        				fieldCls: 'ux-pointer',
		        				//hidden: true,
		    					tpl: new Ext.XTemplate(this.tpl),
		        				editable: false
	        		    	},/*{
		    		    		xtype: 'combo',
		    		    		width: 250,
		    		    		fieldLabel:HS.i18n('Access Type'),
		        		    	id: "data-type-select",
		        		    	name: 'dtype',
		        		    	store: dataTypeStore,
		        				displayField:'label',
		        				valueField: 'name',
		        				//hidden: true,
		        				fieldCls: 'ux-pointer',
		    					tpl: new Ext.XTemplate(this.tpl),
		        				editable: false
	        		    	}, */{
		    					name: 'person',
		    					xtype: 'combo',
		                		width: 250,
		    					fieldLabel: HS.i18n('Contact person'),
		    					store: partyStore,
		    					displayField:'name',
		    					valueField:'name',
		    					typeAhead: true,
		    					triggerAction: 'all',
		    					minChars: 3
		        		    },{
		    					name: 'mdperson',
		    					xtype: 'combo',
		                		width: 250,
		                		id: "mdperson-select",
		    					fieldLabel: HS.i18n('Metadata Contact person'),
		    					store: mdPartyStore,
		    					displayField:'name',
		    					valueField:'name',
		    					typeAhead: true,
		    					hidden: isGuest,
		    					triggerAction: 'all',
		    					minChars: 3
		        		    },
		        		    /*{
				        		xtype: 'combo',
				        		id: "constraint-select",
				        		width: 150,
				        		fieldLabel:HS.i18n('Access constraints'),
				        		name: 'constraint',
				        		displayField: "label",
				        		valueField: "name",
				        		store: {
				        			fields: ["name", "label"],
				        			data:[
				        			      {name:"", label:""}, 
				        			      {name: HS.i18n("No limitations"),  label:HS.i18n("No limitations")}, 
				        			      {name:".",  label:HS.i18n("Partial")}
				        			   ]
				        		},
				        		fieldCls: 'ux-pointer',
				        		mode:'local',
			    				tpl: new Ext.XTemplate('<tpl for=".">' + '<li style="height:22px;" class="x-boundlist-item" role="option">' + '{label}' + '</li></tpl>'),
			        			editable: false
			        		 }*/
		        		    
							{
		        		        xtype: 'checkboxgroup',
		        	            fieldLabel: HS.i18n('Metadata record status'),
		        	            //cls: 'x-check-group-alt',
		        	            hidden: isGuest,
		        	            columns: 2,
		        	            items: [
		   	        	            //{boxLabel: HS.i18n('For portal'), name: "pub2",inputValue: "1"},
			        	            {boxLabel: HS.i18n('Public'), name: "pub1",inputValue: "1"},
		        	                {boxLabel: HS.i18n('Private'), name: "pub0", inputValue: "1"}/*,
		        	                {boxLabel: HS.i18n('Semifinished'), name: "pubx", inputValue: "1"}*/
		        	            ]		        		    	
		        		    },{ 
		        		    	xtype: 'checkboxgroup',
		        		    	hidden: isGuest,
		        		    	columns: 2,
		        		    	items:[
		        		    	    {boxLabel: HS.i18n('For INSPIRE'),name: 'specification',	inputValue: '1'},
		        		    		{boxLabel: HS.i18n('My records only'),name: 'my',inputValue: '1'}/*,
		    		    		    {boxLabel: HS.i18n('Paid'), name: 'paid', inputValue: '1'}, 
		    	                	{boxLabel: HS.i18n('Free'),name: 'free', inputValue: '1'}*/
		        		    	]
							},	{
		        		    		xtype: 'combo',
		        		    		name: 'sortby',
		        		    		fieldLabel:HS.i18n('Order by'),
					        		displayField: "label",
					        		valueField: "name",
					        		store: {
					        			fields: ["name", "label"],
					        			data:[
					        			      {name: 'title', label: HS.i18n("Title")}, 
					        			      {name: 'date',  label: HS.i18n("Metadata update")}, 
					        			      {name: 'bbox',  label: HS.i18n("Extent similarity")}
					        			   ]
					        		},
					        		fieldCls: 'ux-pointer',
					        		mode:'local',
				    				tpl: new Ext.XTemplate(this.tpl),
				        			editable: false		        		    		
		        		    	}
		        		    ]
		        		}, {
			        		    //--- pravy sloupec ---
		        		    	flex:1, 
		        		    	xtype: "container", 
		        		    	layout: "anchor",
		        		    	items:[/*{
			    		    		xtype: 'combo',
			    		    		width: 250,
			    		    		fieldLabel:HS.i18n('Geoscientific theme'),
			    		    		editable: false,
			        		    	name: 'cgs',
			        		    	autoload: true,
			        		    	store: cgsStore,
			        				displayField:'label',
			        				valueField: 'name',
			        				fieldCls: 'ux-pointer',
			    					tpl: new Ext.XTemplate('<tpl for=".">' + '<li style="height:22px;" class="x-boundlist-item" role="option">' + '{label}' + '</li></tpl>'),
			        				editable: false
			    		    	},{
			    		    		xtype: 'combo',
			    		    		width: 250,
			    		    		fieldLabel:HS.i18n('GEMET'),
			        		    	forceSelection: false,
			        		    	name: 'gemet',
			        		    	//typeAhead: true,
			        		    	store: gemetStore,
			        		    	minChars: 3,
			        				displayField:'term',
			        				valueField: 'term',
			        		    	triggerAction: 'all',
			    		    	},{
			    		    		xtype: 'combo',
			    		    		width: 250,
			    		    		fieldLabel:HS.i18n('INSPIRE'),
			    		    		typeAhead: true,
			        		    	id: "inspire-select",
			        		    	minChars: 3,
			        		    	name: 'inspire',
			        		    	typeAhead: true,
			        		    	store: inspireStore,
			        				displayField:'label',
			        				valueField: 'name',
			        		    	triggerAction: 'all'
			    		    	},*//*{
		        		    		xtype: 'combo',
		        		    		width: 250,
		        		    		fieldLabel:HS.i18n('1GE'),
		            		    	forceSelection: false,
		            		    	name: 'oneGeo',
		            		    	minChars: 3,
		            		    	typeAhead: true,
		            		    	store: oneGEStore,
		            				displayField:'term',
		            				valueField: 'term',
		            		    	triggerAction: 'all',
		        		    	},*//*{
		        		    		xtype: 'checkboxgroup',
		        		    		fieldLabel: 'Public',
		        		    		//cls: 'x-check-group-alt',
		        		    		columns: 2,
		        		    		items: [
		        		    		    {boxLabel: 'Payed', name: 'payed', inputValue: '1'}, 
		        	                	{boxLabel: 'Free',name: 'free',inputValue: '1'} 
		        	                ]		        		    	
			        		    },*/
		        		    	 {
		        		    		xtype: 'checkbox',
			    		    		name: 'useMapext',
			    		    		checked: true,
				    				listeners:{
				    					scope:this,
				    					"change": this.changeMap
				        		    },
			    		    		boxLabel: HS.i18n('Use map extent')
		        		    	 },{
		        		    		 xtype: 'container',
		        		    		 id: 'map-control',
				        		     //disabled: true,
		        		    		 items:[{
					        			 xtype: 'container',
					        			 layout: 'border',
					        			 name: 'xxxx',
					        			 id: 'mapPanel',
					        			 height: 300
			        		    	 },	{ 
			        		    		 layout: 'hbox',
					        			 border: false,
					        			 frame: false,
					        			 items: [{
					        				xtype: 'text',
					        				text: HS.i18n('Extent')+': ' 
					        			 }, {
					        				 xtype: 'textfield',
					        				 width: 60,
					        				 name: 'x1'
					        			 },{
					        				 xtype: 'textfield',
					        				 width: 60,
					        				 name: 'y1'				        				 
					        			 },{
					        				 xtype: 'textfield',
					        				 width: 60,
					        				 name: 'x2'				        				 
					        			 },{
					        				 xtype: 'textfield',
					        				 width: 60,
					        				 name: 'y2'				        				 
					        			 }]
				        			 
				        		 }]
		        		    }]
		        		}]
					}
			],
			buttons:[{
			    	text: HS.i18n("Search"),
			    	scope: this,
			    	handler: this.parse 
			    },{
			    	text: HS.i18n("Clear"), 
			    	handler: this.clear, 
			    	scope: this
			}]
		});
		//this.f.center();
		//this.f.on("afterrender", this.initMap, this, {});
		this.initMap();
		this.afterRender(this,this);
		return this;
	},
	
	afterRender: function(a, opts){
		var values = this.getCookies();
		//this.clear();
		if(values.type){
			this.f.getForm().findField("type-select").getStore().load();			
		}
		if(values.topic){
			this.f.getForm().findField("topic-select").getStore().load();			
		}
		if(values.inspire){
			this.f.getForm().findField("inspire-select").getStore().load();			
		}
		if(values.atype){
			this.f.getForm().findField("application-type-select").getStore().load();
		}
		if(values.stype){
			this.f.getForm().findField("service-type-select").getStore().load();
		}
		if(values.denominator){
			this.f.getForm().findField("scale-select").getStore().load();
		}
		/*if(values.dtype){
			this.f.getForm().findField("data-type-select").getStore().load();
		}*/
		if(typeof(values.useMapext)=='undefined') values.useMapext = "";
		this.f.getForm().setValues(values);
		this.setMapExtent();
		this.changeMap(this, this.f.getForm().findField("useMapext").getValue(), null);
	},
	
	changeType: function(obj, newVal, oldVal){
		if (obj.getValue() === null) {
		    obj.reset();
		}
		Ext.get("service-type-select").hide();
		Ext.get("application-type-select").hide();
		//Ext.get("data-type-select").hide();
		Ext.get("scale-select").hide();
		Ext.get("topic-select").hide();
		switch(newVal){
			case "service":
				Ext.get("service-type-select").show();
				break;
			case "application":
				Ext.get("application-type-select").show();
				break;
			case "dataset":
			case "series":
				//Ext.get("data-type-select").show();
				Ext.get("scale-select").show();
				Ext.get("topic-select").show();
				break;
		}
		this.f.updateLayout();
	},
	
	changeMap: function(obj, newVal, oldVal){
		if(newVal) {
			Ext.getCmp('map-control').enable();
		}
		else {
			Ext.getCmp('map-control').disable();
		}
	},
	
	changeValue: function(obj, newVal, oldVal){
		if (obj.getValue() === null) {
		    obj.reset();
		}
	},	
	
	addQ: function(name, str, operator){
		if(!operator) var operator = "=";
	  	if(str!=undefined && Ext.String.trim(str.toString()).length>0){ 
	  		str = this.htmlspecialchars(Ext.String.trim(str.toString()));  		
	  		if(operator=="like") str = name+" like '*"+str+"*'";
	  	    else str = name+operator+"'"+str+"'";
	  		if(this.qstr){
	  			this.qstr += " and ";
	  		}
	  		this.qstr += str;
	  	}	
	},

	htmlspecialchars: function(s){
		s = s.replace(/&/g, '&amp;');
		s = s.replace(/</g, '&lt;').replace(/>/g, '&gt;');
		return s;
	},

	parse: function(a, e){
		var values = this.f.getForm().getValues();
		this.setCookies(values);
		this.qstr = "";
		values.text = Ext.String.trim(values.text);
		if(values.text){
    		if(values.ttype=="abstract"){
    	  		if(this.qstr) this.qstr += " and ";
    		    this.qstr = "(title like '*"+values.text+"*' OR abstract like '*"+values.text+"*')";
    		}
    		else this.addQ(values.ttype, values.text, "like");
		}
		switch (values.type){
  			case "service": 
    			this.addQ("type", values.type);
    			if(values.stype=="ESRI") {
    			    this.addQ("ServiceType", values.stype, "like");
    			}
    			else {
    			    this.addQ("ServiceType", values.stype);
    			}
    			break;
			case "application": 
    			this.addQ("type", values.type);
    			this.addQ("ServiceType", values.atype);	
    			break;
			case "dataset":
  		  		if(this.qstr){ 
  		  			this.qstr += " AND ";
  		  		}	
  				this.qstr += "(type=dataset OR type=nonGeographicDataset OR type=series OR type=tile)";
  				if(values.dtype=="data"){
  					this.addQ("Fees", "http", "like");
  				}
				else {
    			    this.addQ("LinkName", values.dtype, "like");
    			}
    				/*else if(values.dtype=="cd"){
    					this.addQ("AnyText", "cdRom");
    				}*/
  				if(values.denominator==-1) this.addQ("Denominator", 0, " > ");
  				else this.addQ("Denominator", values.denominator);
  				this.addQ("TopicCategory", values.topic);
				break;
			default:
			    this.addQ("type", values.type);
			    break;
		}
		this.addQ("subject", values.inspire);
		this.addQ("subject", values.keywords);
		if(values.useMapext) {
			this.addQ("BBOX", values.x1+" "+values.y1+" "+values.x2+" "+values.y2);
		}
		//this.addQ("subject", values.inspire);
		//this.addQ("subject", values.oneGeo);
		/*if(values.cgs){
	  		if(this.qstr) this.qstr += " AND ";
		    this.qstr += "subject like 'Czech Geological Survey*:"+values.cgs+"'";;
		}	*/
		this.addQ("IndividualName", values.person);
		if(values.constraint){
			if(values.constraint=="."){ 
				this.addQ("OtherConstraints", ".", "like");
				this.addQ("OtherConstraints", HS.i18n('No limitations'), " != ");
			}	
			else this.addQ("OtherConstraints", values.constraint);
		}
		if(values.free && !values.paid){
			this.addQ("ConditionApplyingToAccessAndUse", HS.i18n("no conditions apply"));
		}
		if(!values.free && values.paid){
			this.addQ("ConditionApplyingToAccessAndUse", HS.i18n("no conditions apply"), " != ");
		}
		if(!isGuest){ 
			this.addQ("MdIndividualName", values.mdperson);
			var c="";
			if(values.pub2 && values.pub1 && values.pub0 && values.pubx) c = ""; 
			else if ( values.pub2 &&  values.pub1 &&  values.pub0 && !values.pubx) c = "IsPublic>-1";
			else if ( values.pub2 &&  values.pub1 && !values.pub0 && !values.pubx) c = "IsPublic>0";
			else if ( values.pub2 && !values.pub1 && !values.pub0 && !values.pubx) c = "IsPublic=2";
			
			else if (!values.pub2 &&  values.pub1 &&  values.pub0 &&  values.pubx) c = "IsPublic<2";
			else if (!values.pub2 && !values.pub1 &&  values.pub0 &&  values.pubx) c = "IsPublic<1";
			else if (!values.pub2 && !values.pub1 && !values.pub0 &&  values.pubx) c = "IsPublic<0";

			else if ( values.pub2 && !values.pub1 && !values.pub0 && !values.pubx) c = "IsPublic=2";
			else if (!values.pub2 &&  values.pub1 && !values.pub0 && !values.pubx) c = "IsPublic=1";
			else if (!values.pub2 && !values.pub1 &&  values.pub0 && !values.pubx) c = "IsPublic=0";

			else{
				var c="";
				if(values.pub2){ 
					c = "IsPublic=2";
				}
				if(values.pub1){
					if(c) c += " OR ";
					c += "IsPublic=1";
				}
				if(values.pub0){
					if(c) c += " OR ";
					c += "IsPublic=0";
				}
				if(values.pubx){
					if(c) c += " OR ";
					c += "IsPublic=-1";
				}
				if(c.indexOf("OR")>0) c = "("+c+")";
			}
			if(c){
				if(this.qstr) this.qstr+= " and ";
				this.qstr+=c;
			}
			if(values.my) this.addQ("MdCreator", micka_user);
			if(values.specification) this.addQ("SpecificationTitle", "INSPIRE", "like");
		}
		window.location="?request=GetRecords&format=text/html&language="+lang+"&query="+encodeURIComponent(this.qstr)+"&sortby="+values.sortby;
	},
	
	getCookies: function(){
		var s = Ext.util.Cookies.get('micka');
		if(s){
			var data = Ext.decode(s);
			return data;
		}
		return {};
	},
	
	setCookies: function(data){
		var s = Ext.encode(data);
		Ext.util.Cookies.set('micka', s);		
	},
	
	clear: function(a, e){
		Ext.util.Cookies.clear('micka');
		this.f.getForm().reset();
		this.f.getForm().getFields().each(function(f){
			//console.log(f.name);
			f.setValue('');
		})
		this.setMapExtent();
	},
	
	initMap: function(){
		//var boxLayer = new OpenLayers.Layer.Vector("Box layer");
		this.map = new OpenLayers.Map({
	          div: "mapPanel",
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
				    )	                   
	              /*new OpenLayers.Layer.OSM("OpenStreetMap", null, {
	                  transitionEffect: "resize"
	              })*/ /*,
	              boxLayer*/
	          ],
	          center: new OpenLayers.LonLat(0, 0),
	          zoom: 1
	      });
		//box = new OpenLayers.Feature.Vector(b.toGeometry());
		//boxLayer.addFeatures(box);
	},
	
	setMapExtent: function(){
		var vals = this.getCookies();
		if(typeof(vals.x1) != "undefined" && vals.x1!=''){
			var b = new OpenLayers.Bounds(vals.x1, vals.y1, vals.x2, vals.y2);			
		}
		else if(hs_initext){
			var b = hs_initext.split(" ");
			var b = new OpenLayers.Bounds(b[0], b[1], b[2], b[3]);						
		}
		else {
			var b = new OpenLayers.Bounds(-179, -89, 179, 89);						
		}		
		b.transform("EPSG:4326", "EPSG:900913");
		this.map.zoomToExtent(b, true);
	},
	
	mapEvent: function(evt){
		//console.log(ble);
		var b = evt.object.getExtent().transform("EPSG:900913", "EPSG:4326").toArray();
		this.f.getForm().setValues({
			x1: Math.round(b[0]*1000)/1000,
			y1: Math.round(b[1]*1000)/1000,
			x2: Math.round(b[2]*1000)/1000,
			y2: Math.round(b[3]*1000)/1000
		});	
	}
	
});



Ext.application({
	name: 'ble',
	launch:function(){
		Ext.create("SearchForm", {
			renderTo: "formDiv",
			user: micka_user
		});
		
}});
