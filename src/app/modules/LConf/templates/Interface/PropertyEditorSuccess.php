Ext.ns("lconf");

lconf.propertyManager = Ext.extend(Ext.grid.EditorGridPanel,{
	activeDN :  null,
	minHeight: 200,
	forceFit:true,
	constructor: function(config) {
		Ext.apply(this,config);
		this.initializeGridSettings();		
		Ext.grid.EditorGridPanel.prototype.constructor.call(this,config);
			
	},

	connId: null,
	initializeGridSettings: function() {
		
		this.ds =  new Ext.data.JsonStore({
			proxy: new Ext.data.HttpProxy({
				url : this.url,
				api: this.api
			}),
			autoSave: false,
			storeId: this.id+'_store',
			root: this.root,
			baseParams: {
				'connectionId' : this.connId				
			},
			idProperty: 'id',
			fields: ['id','property','value'],
			writer: new Ext.data.JsonWriter({
				encode:true,
				writeAllFields:true,
				autoSave:true
			})
		});
		
		this.colModel = new Ext.grid.ColumnModel({
			store:this.ds,
			isCellEditable : function(col,row) {
				if(this.store.getAt(row).id == 'dn')
					return false;
				return  Ext.grid.ColumnModel.prototype.isCellEditable.call(this,col,row);	
			}, 
			columns: [	
				{id:'property',header:'Property',width:300,sortable:true,dataIndex:'property',editor:Ext.form.TextField},
				{id:'value',header:'Value',width:400,sortable:false,dataIndex:'value',editor:Ext.form.TextField}
			]
		});

		this.fbar = new Ext.Toolbar({
			disabled:!this.enableFb,
			items:[{
				xtype: 'button',
				text: _('Add property'),
				iconCls: 'silk-add',
				handler: function() {
					var record = Ext.data.Record.create(['id','property','value']);
					this.getStore().add(new record());				
				},
				scope: this
			},{
				xtype: 'button',
				text: _('Remove properties'),
				iconCls: 'silk-delete',
				handler: this.clearSelected,
				scope: this
			},{
				xtype:'tbseparator',
			},{
				xtype:'button',	
				text: _('Save Changes'),
				iconCls: 'silk-disk',
				handler: function() {
					this.getStore().save();
				},
				scope:this
			}]
		});
		if(this.parentNode)
			this.ds.setBaseParam("parentNode",this.parentNode);
	},
	
	
	
	initEvents: function(){
		Ext.grid.EditorGridPanel.prototype.initEvents.call(this)
		eventDispatcher.addCustomListener("nodeSelected",this.viewProperties,this,{buffer:true});
		eventDispatcher.addCustomListener("ConnectionClosed",this.disable,this);
		eventDispatcher.addCustomListener("invalidNode",this.disable,this);
		
		this.getStore().on("add",function(store,rec,index) {this.getSelectionModel().selectLastRow();},this)
		this.getStore().on("load", this.getDNFromRecord,this);
		this.getStore().on("exception",function(proxy,type,action,opt,resp,arg) {
			if(resp.status != '200')
				Ext.Msg.alert('Process failed!',resp.responseText);
		});
		this.getStore().on("save",function() {this.reload()},this.getStore());
		this.on("beforeedit",this.setupEditor,this);
		
	},
	clearSelected: function() {
		this.getStore().remove(this.getSelectionModel().getSelections());
	},
			
	disable: function() {
		this.fbar.setDisabled(true);
		this.getStore().removeAll(false);
	},
	getDNFromRecord : function(store,records,options) {
		var activeRecord;
		
		for(var index in records) {
			activeRecord = records[index];
			// Check if we're on an alias node
			if(!activeRecord.get) {
				eventDispatcher.fireCustomEvent("invalidNode");
				break;
			}
			if(!activeRecord.get("property"))
				continue;
			
			if(activeRecord.get("property").toLowerCase() == "dn") {
				this.activeDN = activeRecord.get("value");
				break;
			}
		}
	},
	
	setConnectionId: function(connId) {
		this.getStore().setBaseParam('connectionId',connId);	
	},
	
	viewProperties: function(selectedDN,connection) {		
		this.connId = connection;	
		var id = selectedDN.attributes["aliasdn"] || selectedDN.id;
		this.getStore().setBaseParam('node', id);
		this.getStore().setBaseParam('connectionId',connection);
		this.getStore().load();
		this.fbar.setDisabled(false);
		if(!lconf.editors)
			this.lazyLoadEditors();
	},
	
	sm: new Ext.grid.RowSelectionModel(),
	
	
	/**
	 * Here's the magic: this function is triggered on beforeEdit and dynamically changes the Editor
	 */
	setupEditor: function(e) {
		var column = e.grid.getColumnModel().columns[e.column];			
		var editor = null;
		if(e.field == "property") {
			var editor = lconf.editors.editorFieldMgr.getEditorFieldForProperty("property");
		} else {
			var type = e.record.get("property").split("_")[0];
			var editor = lconf.editors.editorFieldMgr.getEditorFieldForProperty(type);
		}
		column.setEditor(editor);
	},
	
	lazyLoadEditors: function() {
		var route = '<?php echo $ro->gen("lconf.ldapeditor.editorfielddefinitions");?>';
		var layer = new Ext.LoadMask(Ext.getBody(),{msg:_('Loading editors...')});
		layer.show();
		Ext.Ajax.request({
			url: route,
			success: function(resp) {
				layer.hide();
				eval(resp.responseText);	
			},
			failure: function(resp) {
				err = (resp.responseText.length<50) ? resp.responseText : 'Internal Exception, please check your logs';
				Ext.MessageBox.alert(_("Error"),_("Couldn't load editor:<br\>"+err))
				layer.hide();
			}
		});
		
	}
});


var propertyCmpId = '<?php echo $t["parentId"];?>';

var propertyParent = Ext.getCmp(propertyCmpId);
if(!propertyParent) 
	throw ("Error in PropertyEditor: Component "+propertyCmpId+" is unknown");

propertyParent.add(new lconf.propertyManager({
	url: '<?php echo $ro->gen("lconf.data.modifyproperty");?>',
	api: {
		read :'<?php echo $ro->gen("lconf.data.propertyprovider");?>',			
	},
	root: 'properties',
	id: 'properties_grid'
}));
propertyParent.doLayout();

