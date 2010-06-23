//<script>
	/*****			 Action Menu             ****/
	var panelId = "<?php echo $t["parentid"] ?>";


	// Initial setup-functions for the action panel
	initActionPanelProc = function(cmpRoute) {
		var cmpPanel = this;
		// if connectionManager is already set up, update and stop
		if(cmpPanel.panelManager) {
			cmpPanel.panelManager.update();
			return true;
		}

		// if its the initial setup, create a new updater which loads the LConf_connManager class	
		var updater = cmpPanel.getUpdater();
		var eventId = Ext.id(null,'CmpReady');
		updater.setDefaultUrl({
			url: cmpRoute,
			scripts: true,
			params: {
				filter: 'own;global',
				eventId: eventId,
				parentid: cmpPanel.id
			}
		});
		updater.refresh();
	
		//listener that will be fired when the loaded component is ready
		eventDispatcher.addCustomListener(eventId,function(cmp,cmpManager) {
										this.add(cmp);
										this.panelManager = cmpManager;
										this.doLayout();
									},cmpPanel);

		return true;
		
	};
	
		
	// Create Accordeon panel entries
	var panelComponent;
	if(panelComponent = Ext.getCmp(panelId)) {
		// Connection list
		panelComponent.add(
			new Ext.Panel({
				title: _('Connections'),
				id: 'lconf.pl_connections',
				listeners: {
					render: function(r) {initActionPanelProc.call(r,'<?php echo $ro->gen('lconf.actionbar.connmanager'); ?>')}
				}
			})
		);
	
		panelComponent.doLayout();
	}
	var cmpPanel = null;

	
	

