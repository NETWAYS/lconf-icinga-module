<?php 
	$icons = AgaviConfig::get("de.icinga.ldap",array());
	if(!empty($icons))
		$icons = $icons["icons"];	
?>
ditTreeManager = function(parentId,loaderId) {	
	
	var dataUrl = loaderId;
	var ditPanelParent = Ext.getCmp(parentId);
	if(!ditPanelParent)
		throw("DIT Error: parentId "+parentId+" is unknown");
	
	var ditTreeLoader = Ext.extend(Ext.tree.TreeLoader,{
		
		dataUrl: dataUrl,
		
		createNode: function(attr) {
			var nodeAttr = attr;
			var objClass = attr.objectclass[0];
			
			// select appropriate icon
			switch(objClass) {
			<?php foreach($icons as $objectClass=>$icon) :?>
				
				case '<?php echo $objectClass;?>':
					nodeAttr.iconCls = '<?php echo $icon?>';
					break;
			<?php endforeach;?>
			} 
		
			nodeAttr.text = this.getText(attr);
			nodeAttr.qtip ="<b>ObjectClass:</b> "+objClass+
							"<br/><b>DN:</b> "+attr["dn"]+
							"<br/>Click to modify";
			nodeAttr.id = attr["dn"];
			nodeAttr.leaf = attr["isLeaf"] ? true :false;

			return Ext.tree.TreeLoader.prototype.createNode.call(this,nodeAttr);
		},
		
		constructor: function(config) {
			Ext.tree.TreeLoader.prototype.constructor.call(this,config);
		},
		
		getText: function(attr,withDN) {
			var comma = 1;
			var txtLength = (attr["dn"].length-attr["parent"].length)-comma; 
			var txtRegExp = /^.*?\=/;
			var shortened = attr["dn"].substr(0,txtLength);
			return (withDN) ? shortened : shortened.replace(txtRegExp,"");
		}
	});
	
	var ditTree = Ext.extend(Ext.tree.TreePanel,{
		constructor: function(config) {
			Ext.apply(this,config);
			Ext.tree.TreePanel.superclass.constructor.call(this,config);
			this.initEvents();
			this.initLoader();
		},
		
		initEvents: function() {
			this.on("beforeclose",this.onClose);
			this.on("click",function(node) {eventDispatcher.fireCustomEvent("nodeSelected",node,this.id);});
		},
		
		initLoader: function() {
			this.loader = new ditTreeLoader({
								id:this.id,
								baseParams:{connectionId:this.id}
							});
		},
		
		onClose: function() {
			Ext.Msg.confirm(this.title,"Are you sure you want to close this connection?",
				function(btn) {
					if(btn == 'yes') {
						eventDispatcher.fireCustomEvent("ConnectionClosed",this.id);
						this.destroy()
					}
				},
				this /*scope*/);
			return false;
		},
		useArrows:true,
		autoScroll:true,
		animate:false,
		enableDD: false,
		containerScroll:true,
		minSize:500,
		border:false,
		cls: 'none',

		root: {
			nodeType: 'async',
			disabled:true,
			editable:false,
			text: 'Root DSE',
			leaf:true

		},
	});

	var ditTreeTabPanel = new Ext.TabPanel({
		autoDestroy: true,
		resizeTabs:true,
		defaults : {
			closable: true
		}
	});
	
	ditPanelParent.add(ditTreeTabPanel);
	ditPanelParent.doLayout();
	
	// init listener
	eventDispatcher.addCustomListener("ConnectionStarted",function(connObj) {
		var tree = new ditTree({
						id:connObj.id,
						title:connObj.connectionName
					});
		ditTreeTabPanel.add(tree);	
		ditTreeTabPanel.setActiveTab(connObj.id);
		ditTreeTabPanel.doLayout();
		
		tree.setRootNode(new Ext.tree.AsyncTreeNode({
							id:connObj.rootNode,
							leaf:false,
							iconCls:'silk-world',
							text: connObj.rootNode
						}));
	},this);

}

new ditTreeManager('<?php echo $t["parentId"];?>','<?php echo $ro->gen("lconf.data.directoryprovider")?>');
