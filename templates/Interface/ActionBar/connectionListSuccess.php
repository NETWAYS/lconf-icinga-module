<script type='text/javascript'>
/**
 * connectionManager class for LDAP 
 * 
 */
(function() {
	Ext.ns("LDAP.actionBar");
	LDAP.actionBar.connectionManager = Ext.extend(Ext.util.Observable, { 
		connections: {},
		
		constructor : function(config) {
			this.filter = ['global','own'];
			this.eventId = config.eventId;
			this.storeURL = config.storeURL;
			this.id = AppKit.Ext.genRandomId('connManager');
			this.addEvents([config.eventId]);
			if(!Ext.getCmp(config.parentid)) 
				throw("Error in connectionManager: parentid unknown");
			
			this.parent = Ext.getCmp(config.parentid);
			this.superclass.constructor.call(config);	
			this.init();	
		},
		

		getStore : function() {
			if(!this.dStore) {
				this.dStore = new Ext.data.JsonStore({
					url: this.storeURL,
					root:'connections',
					storeId : 'storeConnManager',
					fields: ['id','connectionName','connectionDescription','host','bindDN','authType','TLS','scope'],				
					baseParams: {
						"scope[]": this.filter
					}
				});
			}
			return this.dStore;
		},
		
		getTemplate : function() {
			if(!this.tpl) {
				this.tpl = new Ext.XTemplate(
					'<tpl for=".">',
						'<div class="cronk-preview" id="{id}">',
							'<div class="thumb" ><img ext:qtip="{connectionDescription}" src="images/cronks/world.png" /></div>',
							'<span class="X-editable">{connectionName}</span>',
						'</div>',
					'</tpl>');
			}
			return this.tpl;
		},

		
		getView : function(createNew) {
			if(!this.dView || createNew) {	
				this.dView = new Ext.DataView({
					id: 'view-'+this.id,
					store: this.getStore(),
					tpl: this.getTemplate(),
					autoHeight:true,
					overClass:'x-view-over',
					multiSelect: false,
					itemSelector:'div.cronk-preview',
					emptyText: 'unnamed',
					cls: 'cronk-data-view'
				}); 
			}
			return this.dView;
		},

		update : function() {
			this.dStore.load();
		},
		
		init : function() {
			var view = this.getView();
			var store = this.getStore();
			view.on("contextmenu",this.handleContext,this);
			view.on("dblclick",function (dView,index,node,_e) {this.startConnect(index,node)},this);
			
			// Notify parent that the component is ready to be drawn
			store.on("load",function() {
					eventDispatcher.fireCustomEvent(this.eventId,view,this);
				},this);
			store.load();
			
			eventDispatcher.addCustomListener("ConnectionClosed",
							function(id) {
								this.closeConnection(id,true)
							},this);
			
		},
		
		// handles creation and display of the context menu
	    handleContext : function(dView,index,node,_e)	{
			_e.preventDefault();
			if(!this.ctxMenu)
				this.ctxMenu = {}
			if(!this.ctxMenu[index]) {
				this.ctxMenu[index] = new Ext.menu.Menu({
					items:[{
						text: 'Connect',
						id: this.id+'-connect-'+index,
						iconCls: 'silk-database-go',
						handler : function() {this.startConnect(index,node);},
						scope:this
					},{
						text: 'View',
						id: this.id+'-edit'+index,
						iconCls: 'silk-database',
						handler : function() {this.viewDetails(index,node);},
						scope:this
					}]
				});
			}	
			this.ctxMenu[index].showAt(_e.getXY());
		},

		viewDetails : function(index,node) {
			var record = this.getStore().getAt(index);
			var tpl = this.getDetailTemplate();
			
			var detailWindow = new Ext.Window({
				title: record.get('connectionName'),
				autoDestroy: true,
				closable: true,
				modal: true,
				defaultType: 'field',
				
			});
			detailWindow.render(Ext.getBody());
			detailWindow.doLayout();
			tpl.overwrite(detailWindow.body,record.data);
			detailWindow.show();

			
		},

		getDetailTemplate: function() {
			if(!this.detailTpl) {
				this.detailTpl = new Ext.XTemplate(
					'<table style="margin:10px" cellpadding="0" cellspacing="0">',
						'<tr><td>ID</td><td>{id}</td></tr>',
						'<tr><td>Name</td><td>{connectionName}</td></tr>',
						'<tr><td colspan="2">Description</td></tr>',
						'<tr><td colspan="2">',
							'<div style="background-color:white;border:1px solid black;height:75px;width:100%;overflow:auto">',
								'{connectionDescription}',
							'</div>',
						'</td></tr>',
						'<tr><td>BindDN</td><td> {bindDN}</td></tr>',
						'<tr><td>BaseDN</td><td> {baseDN}</td></tr>',
						'<tr><td>Host</td><td> {host}</td></tr>',
						'<tr><td>Port</td><td> {port}</td><tr>',
						'<tr><td>Authentification type </td><td>  {host}</td><tr>',
						'<tr><td>Uses TLS </td></td> {TLS}</td></tr>',
					'</table>'
					);
			}
			return this.detailTpl;
		},
		isConnected: function(connName) {
			if(this.connections[connName])
				return true
			else
				return false		
		},
		
		startConnect: function(index, node) {
			var record = this.dStore.getAt(index);
			var connName = record.get('connectionName');
			if(this.isConnected(connName)) {
				AppKit.Ext.notifyMessage(connName, 'Connection is already open!');
				return false;
			}
		    AppKit.Ext.notifyMessage(connName, 'Connecting...');
			Ext.Ajax.request({
				url: '<? echo $ro->gen("LDAP.data.connect")?>',
				success: this.onConnectionSuccess,
				failure: this.onConnectionFailure,
				params: {connectionId : record.get('id')},
				scope: this,
				// custom parameter
				record: record,
				connName: connName
			});
		
		},
		
		onConnectionSuccess: function(response,params) {		
			var responseJSON = Ext.decode(response.responseText);
			if(!responseJSON["ConnectionID"]) {
				AppKit.Ext.notifyMessage(params.connName, 'No connectionID returned!');
				return false;
			}
			AppKit.Ext.notifyMessage(params.connName, 'Connected!');

			// Tell the dispatcher to spread that the connection is open
			eventDispatcher.fireCustomEvent("ConnectionStarted",{
										id:responseJSON["ConnectionID"],
										rootNode: responseJSON["RootNode"],
										connectionName:params.connName
									});
			// save connectionId to avoid duplicates
			this.connections[params.connName] = responseJSON["ConnectionID"];
		},

		onConnectionFailure: function(response, params) {
			AppKit.Ext.notifyMessage(params.connName, 
					'Couldn\'t connect!<br/>'+
					response.responseText
			);
		},
		getConnectionNameById: function(id) {
			for(var index in this.connections) {
				if(this.connections[index] == id) {
					return index;	
				}
			};
		},
		closeConnection: function(connName,isId) {
			if(isId) 
				connName = this.getConnectionNameById(connName);
			
			AppKit.Ext.notifyMessage('Closing connection',connName);
			this.connections[connName] = null;
		}
	});
	
	new LDAP.actionBar.connectionManager({
			storeURL: '<?php echo $ro->gen("LDAP.data.connectionListing");?>',
			eventId: '<?php echo $t["eventId"]; ?>',
			parentid: '<?php echo $t["parentid"]; ?>'				
	});
}) ()
</script>