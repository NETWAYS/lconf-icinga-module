Ext.ns('lconf.wizards');
(function() {

	lconf.wizards.Default = Ext.extend(lconf.propertyManager,{
		url : '<?php echo $ro->gen("lconf.data.modifynode");?>',
		id : Ext.id("defaultWizard"),
		root: 'properties',
		enableFb : true,
		constructor: function(config) {
			Ext.apply(this,config);
			
			lconf.propertyManager.prototype.constructor.call(this,config);
			if(!lconf.editors)
				this.lazyLoadEditors();
			this.on("render", function() {
				var record = Ext.data.Record.create(['id','property','value']);
				this.getStore().add(new record());			
				this.getStore().on("save",function() {
					Ext.Msg.alert(_("Element created"),_("Node created successfully.<br/>You can now close the window or create a similar object"));
				});
			},this);
			
		},
		height: 400,
		width:40
		
	});

})();
 