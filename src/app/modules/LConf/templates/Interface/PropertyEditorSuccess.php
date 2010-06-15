//<script>
propertyManager = new function() {
	var grid = null;
	var activeDN =  null;

	this.getActiveDN = function() {
		return activeDN;
	}

	this.setActiveDN = function(dn) {
		activeDN = dn;
	}

	this.getEditor = function() {
		if(!grid) {
			grid = new gridPanel({mgr:this});
		} 
		return grid;
	}	
	
	this.removeRecord = function(record,records) {
		records.remove(record);
										
	}
	
	this.editProperty = function(grid,rowIndex,event) {
		var store = grid.getStore();
		var dn = this.getActiveDN();
		var connection = grid.connId;
		
		var formPanel = new Ext.Window({
			layout:'fit',
			width:500,
			title: 'Edit entry',
		});
		viewPropertyForm.call(this,formPanel,{
			records: store,
			connection : connection,
			dn: dn
		});
		formPanel.render(Ext.getBody());
		formPanel.show();
		
	}
	var getPropertyEditorRow = function(record,gen_id) {
		return new Ext.Panel({
			layout:'column',
			border:false,
			frame:false,
			bodyStyle:'background:none',
			items: [
				new Ext.form.TextField({
					columnWidth:0.3,
					xtype:'textfield',
					
					value: record.get('property'),
					id: 'EntryProperty_'+record.get('id')+'_'+gen_id
				}),
				new Ext.form.TextField({
					columnWidth:0.5,
					xtype:'textfield',
					value: record.get('value'),
					id: 'EntryValue_'+record.get('id')+'_'+gen_id
				}),
				new Ext.form.Checkbox({
					columnWidth:0.1,
					id: 'Delete_'+record.get('id')+'_'+gen_id,
					width:25
				}),
				new Ext.Container({
					layout:'fit',
					cls: 'x-form-item',
					bodyStyle:'padding: 5px 0px 0;',
					html: 'Remove',
					columnWidth:0.1
				})
				
			]
		})
	}

	var updateRowsfromForm = function(record,gen_id) {
		var inpField_pr = Ext.getCmp('EntryProperty_'+record.get('id')+'_'+gen_id);
		var inpField_val = Ext.getCmp('EntryValue_'+record.get('id')+'_'+gen_id);
		var inpField_del = Ext.getCmp('Delete_'+record.get('id')+'_'+gen_id);
		
		if(!(inpField_pr && inpField_val && inpField_del)) 
			return true;
		if(inpField_del.getValue()) {
			deletes.push(record);
		}
		record.set('property',inpField_pr.getValue());				
		record.set('value',inpField_val.getValue());
	}
	
	var viewPropertyForm = function(parent,options) {
		var gen_id = Ext.id();
		var records = options.records;
		var added = [];
		var deletes = [];
		var editEntryform =  new Ext.form.FormPanel({
			url: '',
			bodyStyle:'padding: 5px 5px 0;background:none',
			frame: false,
			border:false,
			height:300,
			autoscroll:true
		});

		
		records.each(function(record) {
			if(record.get('property') == 'dn') 
				return true;
			
			var columnLayout = getPropertyEditorRow(record,gen_id);
			editEntryform.add(columnLayout);
		},this); 
		

		Ext.apply(editEntryform, options);
		editEntryform.mgr = this;
		editEntryform.parent = parent;
		editEntryform.addButton('Add Field', function() {
			var newId =Ext.id();
			var emptyRecord = new (Ext.data.Record.create([{name: 'id'},{name:'property'},{name:'value'},{name:'isNew'}]))();
				emptyRecord.set('id',newId);
				emptyRecord.set('isNew',true);
			
			var row = getPropertyEditorRow(emptyRecord,gen_id);
			editEntryform.add(row);
			editEntryform.doLayout();
			added.push(emptyRecord);

		},this);		

		editEntryform.addButton('Save', function(btn,e) {
			// copy new values to the store

			records.each(function(record) {	
				updateRowsfromForm(record,gen_id);
			});
			Ext.each(added,function(record) {
				updateRowsfromForm(record,gen_id);
			})
			records.add(added);
			if(deletes.length) {
				Ext.Msg.confirm("Save changes", 
					"Are you sure you want to delete these properties?<br/>"+
					"This action is not reversable!",
					function(btn) {
						if(btn == 'yes') {
							records.remove(deletes);
							records.save();
						}
					}, this);
			} else {
				records.save();
			}
			
			//alert(records.save());
			editEntryform.parent.close();
		},editEntryform);
		
		parent.add(editEntryform);
	}


	
	var gridPanel = Ext.extend(Ext.grid.GridPanel,{

		minHeight: 200,
		
		construct: function(config) {
			Ext.apply(this,config);
			Ext.grid.PropertyGrid.prototype.call(this,config);
			this.initEvents();
		},

		connId: null,
		
		ds :  new Ext.data.JsonStore({
			proxy: new Ext.data.HttpProxy({
				api: {
					read :'<?php echo $ro->gen("lconf.data.propertyprovider");?>',
					create: String.format('<?php echo $ro->gen("lconf.data.modifyEntry");?>{0}',"propertyCreate"),
					update: String.format('<?php echo $ro->gen("lconf.data.modifyEntry");?>{0}',"alter"),
					destroy: String.format('<?php echo $ro->gen("lconf.data.modifyEntry");?>{0}',"propertyDelete"),
				},
			}),
			autoSave: false,
			storeId: 'propertiesStore',
			root: 'properties',
			idProperty: 'id',
			fields: ['id','property','value'],
			writer: new Ext.data.JsonWriter({
				encode:true,
				writeAllFields:true,
				autoSave:true
			})
		}),
		
		columns: [
			{id:'property',header:'Property',width:200,sortable:true,dataIndex:'property'},
			{id:'value',header:'Value',width:200,sortable:false,dataIndex:'value'}
		],
		
		initEvents: function(){
			eventDispatcher.addCustomListener("nodeSelected",this.viewProperties,this,{buffer:true});
			this.on("rowdblclick",this.mgr.editProperty,this.mgr);
			this.getStore().on("load", this.getDNFromRecord,this.mgr);
			this.getStore().on("exception",function(proxy,type,action,opt,resp,arg) {
				if(resp.status != '200')
					Ext.Msg.alert('Process failed!',resp.responseText);
			});
			this.getStore().on("save",function() {this.reload()},this.getStore());
		},
		
		getDNFromRecord : function(store,records,options) {
			var activeRecord;
			
			for(var index in records) {
				activeRecord = records[index];

				if(!activeRecord.get("property"))
					continue;
				
				if(activeRecord.get("property").toLowerCase() == "dn") {
					this.setActiveDN(activeRecord.get("value"));
					break;
				}
			}
		},
			
		viewProperties: function(selectedDN,connection) {		
			this.connId = connection;	
			this.getStore().setBaseParam('node',selectedDN.id);
			this.getStore().setBaseParam('connectionId',connection);
			this.getStore().load();
			
		},
	});


	
}

var propertyCmpId = '<?php echo $t["parentId"];?>';

var propertyParent = Ext.getCmp(propertyCmpId);
if(!propertyParent) 
	throw ("Error in PropertyEditor: Component "+propertyCmpId+" is unknown");

propertyParent.add(propertyManager.getEditor());
propertyParent.doLayout();

