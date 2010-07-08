Ext.ns("lconf.Admin");

lconf.Admin.PrincipalEditor = function(connection_id) {
	
	/**
	 * Excludes records selected in store.sourceStore from this store
	 * 
	 * @param {Ext.data.Store} store
	 */
	this.excludeSelectedRecords = function(store) {
		var primary = store.idProperty;
		// sourcestore is an id
		if(Ext.isString(store.sourceStore))
			store.sourceStore = Ext.StoreMgr.lookup(store.sourceStore);

		store.each(function(record) {
			var index = store.sourceStore.find(primary,record.get(primary));
			if(index != -1)
				store.sourceStore.removeAt(index);
		})
	}
	
	/**
	 * There are always to pairs of stores: Available users/groups and selected users/groups
	 */
	 
	this.groupStore = new Ext.data.JsonStore({
		autoDestroy: true,
		storeId: 'groupListStore',
		sourceStore: 'groupListSelectedStore',
		idProperty: 'role_id',
		autoLoad:true,
		remoteSort: true,
		url: '<? echo $ro->gen("appkit.data.groups")?>',
		fields: [
			'role_id',
			'role_name'
		],
		listeners: {
			// function to filter out already selected values from the available view
			load:this.excludeSelectedRecords,
			scope:this
		}
	})
	
	this.selectedGroupsStore = new Ext.data.JsonStore({
		autoDestroy:false,
		autoSave: false,
		storeId: 'groupListSelectedStore',
		idProperty: 'role_id',
		sourceStore: this.groupStore,
		autoLoad:true,
		root:'groups',
		url: '<? echo $ro->gen("lconf.data.principals") ?>',
		baseParams: {
			target: 'groups',
			connection_id: connection_id
		},
		fields: [
			'role_id',
			'role_name'
		],
		writer: new Ext.data.JsonWriter({
			encode:true
		}),
		proxy: new Ext.data.HttpProxy({
			url:'<? echo $ro->gen("lconf.data.principals") ?>',
		}),
		listeners: {
			// function to filter out already selected values from the available view
			load:this.excludeSelectedRecords,
			scope:this
		}
	})
	
	
	this.userStore = new Ext.data.JsonStore({
		autoDestroy: true,
		autoLoad:true,
		storeId: 'userListStore',
		sourceStore: 'userListSelectedStore',
		totalProperty: 'totalCount',
		root: 'users',
		idProperty: 'user_id',
		url: '<? echo $ro->gen("appkit.data.users")?>',
		remoteSort: true,
		baseParams: {
			hideDisabled: false
		},
		fields: [
			'user_id',
			'user_name',
		],
		listeners: {
			// function to filter out already selected values from the available view
			load:this.excludeSelectedRecords,
			scope:this
		}
	})
	
	
	this.selectedUsersStore = new Ext.data.JsonStore({
		autoDestroy:false,
		autoSave: false,
		storeId: 'userListSelectedStore',
		sourceStore: this.userStore,
		idProperty: 'user_id',
		autoLoad:true,
		root:'users',
		url: '<? echo $ro->gen("lconf.data.principals") ?>',
		baseParams: {
			target: 'users',
			connection_id: connection_id
		},
		fields: [
			'user_id',
			'user_name'
		],
		writer: new Ext.data.JsonWriter({
			encode:true
		}),
		proxy: new Ext.data.HttpProxy({
			url:'<? echo $ro->gen("lconf.data.principals") ?>',
		}),
		listeners: {
			// function to filter out already selected values from the available view
			load: this.excludeSelectedRecords
		}
	})
	
	
	this.getPrincipalTabbar = function(connection_id) {
		var usersTab = lconf.Admin.itemGranter({
			targetStore: this.selectedUsersStore,
			store: this.userStore,	
			title: _('Users'),
			id: connection_id+"_userPanel",
			columns:[
				{header:_('Id'),name:'user_id'},
				{header:_('User'),name:'user_name'}
			],
			targetColumns:[
				{header:_('Id'),name:'user_id',dataIndex:'user_id'},
				{header:_('User'),name:'user_name',dataIndex:'user_name'}
			]
		})
		var groupTab = lconf.Admin.itemGranter({
			targetStore: this.selectedGroupsStore,
			store: this.groupStore,	
			title: _('Groups'),
			id: connection_id+"_groupPanel",
			columns: [
				{header:_('Id'),name:'role_id'},
				{header:_('Group'),name:'role_name'}
			],
			targetColumns:[
				{header:_('Id'),name:'role_id',dataIndex:'role_id'},
				{header:_('Group'),name:'role_name',dataIndex:'role_name'}
			]
		})
		return new Ext.TabPanel({
			activeTab: 0,
			items: [
				usersTab,
				groupTab
			]
		})
		
	}
	var id = Ext.id;
	
	return new Ext.Window({
		layout:'hbox',
		width:700,
		autoScroll:true,
		height: Ext.getBody().getHeight()*0.9 > 500 ? 500 : Ext.getBody().getHeight()*0.9,
		modal:true,
		id: "wnd_"+id,
		items: this.getPrincipalTabbar(),
		buttons: [{
			text:_('Save changes'),
			handler: function(btn) {
				this.selectedUsersStore.save();
				this.selectedGroupsStore.save();
				(function() {Ext.getCmp("wnd_"+id).close()}).defer(200);
			},
			scope: this
		}]
	})
	
}

lconf.GridDropZone = function(grid, config) {
	this.grid = grid;
	lconf.GridDropZone.superclass.constructor.call(this, grid.view.scroller.dom, config);
};

Ext.extend(lconf.GridDropZone, Ext.dd.DropZone, {
	onContainerOver:function(dd, e, data) {
		return dd.grid !== this.grid ? this.dropAllowed : this.dropNotAllowed;
	},
	
	onContainerDrop:function(dd, e, data) {
		if(dd.grid !== this.grid) {
			// Move the records between the stores on drop
		
			Ext.each(data.selections,function(r) {
				var rec = r.copy();
				Ext.data.Record.id(rec);
				this.grid.store.add(rec);
				dd.grid.store.remove(r);
			},this)
			
			return true;
		} 
		return false;
	},
	containerScroll:true
});

lconf.Admin.itemGranter = function(config) {
	this.interface = null;
	Ext.apply(this,config);
	
	this.notifySelected = function(where) {
		if(where == "available")
			target = this.gridSelected;
		else 
			target = this.gridAvailable;

		if(target.getSelectionModel().hasSelection()) {
			target.getSelectionModel().clearSelections();
		}
	}
	
	this.gridAvailable =  new Ext.grid.GridPanel({
		title: _("Available"),
		store: this.store,
		colModel: new Ext.grid.ColumnModel({
			defaults: {
				width:100,
				sortable: true
			},
			columns: this.columns
		}),
		bbar: new Ext.PagingToolbar({
			pageSize: 25,
			store: this.store,
			displayInfo: true,
			displayMsg: _('Showing ')+' {0} - {1} '+_('of')+' {2}',
			emptyMsg: _('Nothing to display')
		}),

		width:325,
		height:400,
		enableDragDrop: true,		
		sm: new Ext.grid.RowSelectionModel({
			singleSelect:false,
		}),
		
		listeners: {
			render: function(grid) {
				this.dz = new lconf.GridDropZone(grid,{ddGroup:grid.ddGroup || 'GridDD'});
			}
			
		}
	});	
	
	
	this.gridSelected = new Ext.grid.GridPanel({
		title: _("Selected"),
		store: this.targetStore,
		colModel: new Ext.grid.ColumnModel({
			defaults: {
				width:100,
				sortable: true
			},
			columns: this.targetColumns
		}),
		width:325,
		
		height:400,
		enableDragDrop: true,
		sm: new Ext.grid.RowSelectionModel({
			singleSelect:false,
			
		}),
		
		listeners: {
			render: function(grid) {
				this.dz = new lconf.GridDropZone(grid,{ddGroup:grid.ddGroup || 'GridDD'});
			}
			
		}
	});	

	this.addSelectedItems = function(from,to) {
		// check selection
		if(!from.getSelectionModel().hasSelection())
			Ext.MessageBox.alert(_("Error"),_("You haven't selected anything."));
		Ext.each(from.getSelectionModel().getSelections(),function(r) {
			var rec = r.copy();
			Ext.data.Record.id(rec);
			to.getStore().add(rec);
			from.getStore().remove(r);
		});
	}

	this.buildInterface = function() {
		var available = this.gridAvailable;
		var selected =  this.gridSelected;
		this.interface = new Ext.Panel({
			layout:'table',
			height: Ext.getBody().getHeight()*0.9 > 500 ? 500 : Ext.getBody().getHeight()*0.9,
			title:this.title,
			width:700,
			layoutConfig: {
				columns:3,
				
			},
			defaults: {
				cellCls: 'middleAlign'
			},
			items: [
				available,
				{

					items:[{
						xtype:'button',
						text: '<<',
						width:50,
						handler: function() {this.addSelectedItems(selected,available)},
						scope: this
					},{
						xtype:'button',
						text: '>>',
						width:50,
						handler: function() {this.addSelectedItems(available,selected)},
						scope: this
					}]	
					
				},	
				selected
			]
		})		

	}
	
	this.buildInterface();
	return this.interface;
}