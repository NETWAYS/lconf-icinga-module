//<script>
	/*****			 Action Menu             ****/
	var panelId = "<?php echo $t["parentid"] ?>";
	var filterPanelId = "<?php echo $t['_panelIds']['filter'];?>";
	var connectionPanelId = "<?php echo $t['_panelIds']['connections'];?>";
	var schemaId = "<?php echo $t['_panelIds']['schema'];?>";

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
	<?php foreach($t["_menuPoints"] as $navPoint) :?>
		panelComponent.add(<?php echo json_encode($navPoint["jsExtParams"]); ?>);
	<?php endforeach;?>
		panelComponent.doLayout();
	}
	var cmpPanel = null;
	
	// Init the panels
	<?php foreach($t["_menuPoints"] as $navPoint) :?>
	cmpPanel = panelComponent.getComponent('<?php echo $navPoint['jsExtParams']["id"]; ?>');
	if(cmpPanel)  {
		cmpPanel.addListener("expand",function() {
					initActionPanelProc.call(this,'<?php echo $navPoint['init']["route"]; ?>');
					},cmpPanel);
	}
	<?php endforeach;?>
