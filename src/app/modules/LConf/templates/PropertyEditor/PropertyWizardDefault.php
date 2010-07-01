Ext.ns('lconf.wizards');
(function() {
	lconf.presetFields = Ext.decode('<?php echo $t["lconfPresets"]; ?>');
	
	lconf.wizard = Ext.extend(lconf.propertyManager,{
		url : '<?php echo $ro->gen("lconf.data.modifynode");?>',
		id : Ext.id("wizard"),
		root: 'properties',
		enableFb : true,
		noLoadOnSave: true,
		constructor: function(config) {
			Ext.apply(this,config);
			
			lconf.propertyManager.prototype.constructor.call(this,config);
			if(!lconf.editors)
				this.lazyLoadEditors();
			this.on("render", function() {
				var record = Ext.data.Record.create(['id','property','value']);
						
				this.getStore().on("exception",function(proxy,type,action,options,response) {
					
					if(response.status != '200')
						Ext.Msg.alert(_("Element could not be created"),_("An error occured: "+response.responseText));
					else if(response.status == 'success')
						Ext.Msg.alert(_("Element created"),_("Node created successfully.<br/>You can now close the window or create a similar object"));
				});
				this.getStore().removeListener("save");
				if(lconf.presetFields[this.wizardView]) {
					var properties = lconf.presetFields[this.wizardView];
					for(var property in properties) {
						this.getStore().add(new record({property:property,value:properties[property]}));
					}
				} else 
					this.getStore().add(new record());	
			},this);
			
			
		},
		height: 400,
		width:40
		
	});

})();
 