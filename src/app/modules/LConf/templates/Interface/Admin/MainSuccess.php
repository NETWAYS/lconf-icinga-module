<script type='text/javascript'>
Ext.Msg.minWidth = 200;
Ext.onReady(function() {
	Ext.ns("lconf.Admin");
		
	lconf.Admin.connectionTbar = function() {
		var tbar = new Ext.Toolbar({
			items: [{
				text:_('New connection'),
				iconCls: 'icinga-icon-add',
				handler: lconf.Admin.addUserPanel
			}, {
				text:_('Remove connections'),
				iconCls: 'icinga-icon-cancel',
				handler: lconf.Admin.removeSelected
			}]
		});
		return tbar;		
	}
	
	/**
	 * The connection listing dataview
	 * 
	 * @return Ext.DataView 
	 * */
	lconf.Admin.connectionList = new function() {
		var recordSkeleton = Ext.data.Record.create([
			'connection_id','connection_name','connection_description','connection_binddn',
			'connection_bindpass','connection_host','connection_port','connection_basedn','connection_tls', 
			'connection_ldaps','connection_default'
		]);
		
		this.addConnection = function(values) {
			var record = new recordSkeleton(values);	
			if(!values.connection_ldaps)
				values.connection_ldaps = false;
			this.dStore.add(record);
		}
		
		this.testConnection = function(values) {
			if(this.ld_mask)
				this.ld_mask.hide();
			
			this.ld_mask = new Ext.LoadMask(Ext.getBody(), {msg:_("Please wait...")});
			this.ld_mask.show();
			Ext.Ajax.request({
				url:"<?php echo $ro->gen('lconf.data.connect')?>",
				params: {
					testOnly:true,
					connection: Ext.encode(values)
				},
				success: function(response) {
					if(this.ld_mask)
						this.ld_mask.hide();			
					Ext.MessageBox.alert(_("Success"),_("Connecting succeeded!"));
				},
				failure: function(response) {
					if(this.ld_mask)
						this.ld_mask.hide();
					Ext.Msg.minWidth = 500;
					Ext.MessageBox.alert(_("Error"),_("Connecting failed!<br/><br/>")+response.responseText);
					Ext.Msg.minWidth = 200;
				},
				scope:this
			});
		}
		
		this.restProxy = new Ext.data.HttpProxy({
			method:'GET',
			restful:true,
			url: "<?php echo $ro->gen('lconf.data.connectionlisting'); ?>"
		});
		
		this.dStore = new Ext.data.JsonStore({
			autoLoad:true,
			autoDestroy:false,
			root:'result',
			listeners: {
				// Check for errors
				exception : function(prox,type,action,options,response,arg) {
					if(this.ld_mask)
						this.ld_mask.hide();
					if(response.status == 200)
						return true;
					response = Ext.decode(response.responseText);
					if(response.error.length > 100)
						response.error = _("A critical error occured, please check your logs");
					Ext.Msg.alert("Error", response.error);
				},
				save: function(store) {
					if(this.ld_mask)
						this.ld_mask.hide();
					store.load();
				}, 
				
				beforesave: function() {
					if(this.ld_mask)
						this.ld_mask.hide();
					this.ld_mask = new Ext.LoadMask(Ext.getBody(), {msg:_("Please wait...")});
					this.ld_mask.show();	
					
				},
				scope: this
			},
			autoDestroy:true,
			fields: [
				'connection_id','connection_name','connection_description','connection_binddn',
				'connection_bindpass','connection_host','connection_port','connection_basedn','connection_tls',
				'connection_ldaps','connection_default'
			],
			writer:new Ext.data.JsonWriter({encode: true}),
			idProperty:'connection_id',
			root: 'connections',
			proxy: this.restProxy
		})
		
		this.tpl =new Ext.XTemplate(
			'<tpl for=".">',
				'<div class="ldap-connection" ext:qtip="{connection_description}" id="conn_{connection_id}">',
					'<div class="thumb"></div>',
					'<span class="X-editable"><b>{connection_name}</b></span><br/>',					
					'<span class="X-editable">',
					'<tpl if="connection_ldaps == true">ldaps://</tpl>',
					'{connection_host}:{connection_port}</span><br/>',
				
				'</div>',

			'</tpl>'
		);
		
		this.dView = new Ext.DataView({
			store:this.dStore,
			tpl:this.tpl,
			multiSelect: true,
			overClass:'x-view-over',
			selectedClass:'x-view-select',
			itemSelector: 'div.ldap-connection',
			emptyText: _('No connections defined yet'),
			listeners: {
				click : function(dview,index,htmlNode,event) {
					var record = dview.getStore().getAt(index);
					var m = new Ext.menu.Menu({
						renderTo:document.body,
						autoShow :true,
						autoDestroy:true,
						items: [{
							text:_('Edit'),
							iconCls:'icinga-icon-application-edit',
							handler: lconf.Admin.addUserPanel.createCallback(record.data)
						}, {
							text:_('Manage access'),
							iconCls:'icinga-icon-user',
							handler: function() {
								var conn_id = record.get("connection_id");
								var wnd = lconf.Admin.PrincipalEditor(conn_id);

								wnd.show();
							},
							hidden: <?php echo AgaviContext::getInstance()->getUser()->hasCredentials("lconf.admin") ? 'false' : 'true' ?>
						}/*, {
							text:_('Mark as default'),
							iconCls:'icinga-icon-accept',
							handler: function() {
								var conn_id = record.get("connection_id");
								record.set('connection_default',true);
								record.store.save();
							}
						}*/]
					}).showAt(event.getXY());
					
				}
			}
		});
	}
	
	lconf.Admin.removeSelected = function() {
		var records = lconf.Admin.connectionList.dView.getSelectedRecords();
		if(records.length == 0) {
			Ext.MessageBox.alert(_("Error"),_("No connection selected"));
			return false;
		}
		Ext.MessageBox.confirm(
			_("Delete selected connections"),
			_("Do you really want to delete this ")+records.length+_(" connection(s)?"),
			function(btn) {
				if(btn != "yes")
					return false;
				//'this' are the records	

				var store = this[0].store;
				store.remove(this);
				
				store.save();
			},
			records
		);
	}
	
	/**
	 * Displays the user interface panel for adding connections
	 * 
	 * @return void
	 */
	lconf.Admin.addUserPanel = function(defaults) {
		if(!defaults)
			defaults = {}
		var _id = Ext.id(null,"userPanel");
		var wnd = new Ext.Window({
			modal:true,
			width:500,
			height: Ext.getBody().getHeight()*0.9 > 650 ? 650 : Ext.getBody().getHeight()*0.9,
			autoScroll:true,
			autoShow:true,
			layout:'form',
			padding:5,
			id: 'wnd_'+_id,
			monitorValid:true,
			title:_('Create new connection'),
			renderTo:document.body,
			items: [{
				xtype: 'form',
				border:false,
				id: _id,
				bodyStyle:'background:none',
				items: [
				{
					xtype:'hidden',
					name: 'connection_id',
					value: defaults['connection_id'] || -1
				},
				{	
					xtype:'fieldset',
					title: _('General details'),
					defaults: {
						xtype:'textfield'
					},
					items: [{
						fieldLabel: _('Connection Name'),
						name: 'connection_name',
						value: defaults['connection_name'] || '',
						anchor:'95%',
						allowBlank:false
					}, {
						xtype:'textarea',
						fieldLabel: _('Connection Description'),
						name: 'connection_description',
						value: defaults['connection_description'] || '',
						anchor: '95%',
						height: 100
					}]
				},{
					xtype:'fieldset',
					title:_('Authorization'),
					defaults: {
						xtype:'textfield'
					},
					items: [{
						fieldLabel:_('Bind DN'),
						name: 'connection_binddn',
						value: defaults['connection_binddn'] || '',
						anchor:'70%',
						allowBlank:false
					},{
						fieldLabel:_('Bind Pass'),	
						name: 'connection_bindpass',
						value: defaults['connection_bindpass'] || '',
						anchor:'70%',
						inputType:'password'
					}]
				},{
					xtype:'fieldset',
					title:_('Connection Details'),
					defaults: {
						xtype:'textfield'
					},
					items: [{
			
						xtype:'textfield',
						fieldLabel:_('Host'),
						name: 'connection_host',
						value: defaults['connection_host'] || 'localhost',
						layout:'form',
						anchor:'70%'
					},{
						xtype:'numberfield',
						fieldLabel:_('Port'),		
						name: 'connection_port',
						value: defaults['connection_port'] || '389',
						defaultValue: 389,				
						layout:'form',
						anchor:'70%'
					},{
						fieldLabel:_('Root DN'),
						name: 'connection_basedn',
						value: defaults['connection_basedn'] || '',
						anchor:'70%'
					}, {
						xtype:'checkbox',
						name: 'connection_tls',
						checked: defaults['connection_tls'] || false,
						fieldLabel: _('Use TLS')
						
					},{
						xtype:'checkbox',
						name: 'connection_ldaps',
						checked: defaults['connection_ldaps'] || false,
						fieldLabel: _('Enable SSL (ldaps://)')
						
					}]
				}]
			}],
			buttons: [{
				text: _('Check connection'),
				parentId: _id,
				handler: function(btn,e) {
					var form = Ext.getCmp(btn.parentId);
					if(!form.getForm().isValid()) 
						return false;
					var values = form.getForm().getValues();
					lconf.Admin.connectionList.testConnection(values);
					
				}
			}, {
				text: _('Save'),
				formBind:true,
				parentId: _id,
				handler: function(btn,e) {
					var form = Ext.getCmp(btn.parentId);
					if(!form.getForm().isValid()) 
						return false;
					var values = form.getForm().getValues();
					lconf.Admin.connectionList.addConnection(values);
					Ext.getCmp('wnd_'+btn.parentId).close();
				}
			}]
		});
		wnd.show();	
	}
	
	lconf.Admin.container = new Ext.Panel({
		layout:'border',
		id: 'view-container',
		items: [{
			title: _('Connections'),
			region: 'center',
			id: 'connection-frame',
			layout: 'fit',
			margins:'5 5 5 5',
			cls: false,
			autoScroll:true,
			tbar: lconf.Admin.connectionTbar(),
			items: lconf.Admin.connectionList.dView
		}]
	});
	
	<?php echo $t["js_editWindow"]; ?>	
	
	AppKit.util.Layout.getCenter().add(lconf.Admin.container);
	AppKit.util.Layout.doLayout();

})
</script>