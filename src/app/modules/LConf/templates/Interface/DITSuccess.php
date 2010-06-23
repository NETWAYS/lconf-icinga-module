<?php 
	$icons = AgaviConfig::get("modules.lconf.icons",array());

?>
ditTreeManager = function(parentId,loaderId) {	
	
	var dataUrl = loaderId;
	var ditPanelParent = Ext.getCmp(parentId);
	if(!ditPanelParent)
		throw(_("DIT Error: parentId ")+parentId+_(" is unknown"));
	
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
			nodeAttr.qtip = _("<b>ObjectClass:</b> ")+objClass+
							_("<br/><b>DN:</b> ")+attr["dn"]+
							_("<br/>Click to modify");
			nodeAttr.id = attr["dn"];
			nodeAttr.leaf = attr["isLeaf"] ? true :false;

			return Ext.tree.TreeLoader.prototype.createNode.call(this,nodeAttr);
		},
		
		constructor: function(config) {
			Ext.tree.TreeLoader.prototype.constructor.call(this,config);
		},
		
		getText: function(attr,withDN) {
			var comma = 1;
			var txtRegExp = /^.*?\=/;
			var shortened = attr["dn"].split(",")[0];
			return (withDN) ? shortened : shortened.replace(txtRegExp,"");
		}
	});
	
	var ditTree = Ext.extend(Ext.tree.TreePanel,{
	
		initEvents: function() {
			ditTree.superclass.initEvents.call(this);
			this.on("beforeclose",this.onClose);
			this.on("click",function(node) {eventDispatcher.fireCustomEvent("nodeSelected",node,this.id);});
			this.on("beforeNodeDrop",function(e) {e.dropStatus = true;this.nodeDropped(e);return false},this)
			this.on("contextmenu",function(node,e) {this.context(node,e)},this);
		},
		
		context: function(node,e) {
			var ctx = new Ext.menu.Menu({
				items: [{
					text: _('Create node on same level'),
					iconCls: 'silk-add',
					handler: this.callNodeCreationWizard.createDelegate(this,{node:node}),
					scope: this,
					hidden: !(node.parentNode)
				},{
					text: _('Create node as child'),
					iconCls: 'silk-sitemap',
					handler: this.callNodeCreationWizard.createDelegate(this,{node:node,isChild:true}),
					scope: this
				},{
					text: _('Jump to alias target'),
					iconCls: 'silk-arrow-redo',
					hidden: node.id.substr(0,5) != 'ALIAS',
					handler: this.jumpToRealNode.createDelegate(this,[node])	
				}]
			});
			ctx.showAt(e.getXY())
			
			
		},
		
		jumpToRealNode : function(alias) {
			var id = alias.id.substr(("ALIAS=Alias of:").length);
			AppKit.log(id);
			var node = this.getNodeById(id);
			if(!node)  {
			 	node = this.searchNodeByDN(id);
				return true;
			} 
			AppKit.log(node.getPath());
			this.selectPath(node.getPath());
			this.expandPath(node.getPath());
		},
			
		searchNodeByDN : function(id) {
			
		},
		
		callNodeCreationWizard : function(cfg) {
			if(!this.wizardWindow) {
				this.wizardWindow = new Ext.Window({
					width:800,
					renderTo:Ext.getBody(),
					modal:true,
					title: _('Create new entry'),
					layout:'card'
				});
			}
			this.wizardWindow.update();
		},
		
		nodeDropped: function(e) {
			var ctx = new Ext.menu.Menu({
				items: [{
					text: _('Clone node here'),
					iconCls: 'silk-arrow-divide'
				},{
					text: _('Move node here'),
					iconCls: 'silk-arrow-turn-left'
				},{
					text: _('Create alias here'),
					iconCls: 'silk-attach'
					
				},{
					text: _('Cancel'),
					iconCls: 'silk-cancel'
				}]
			});
			ctx.showAt(e.rawEvent.getXY())
			
		},
		
		initLoader: function() {
			this.loader = new ditTreeLoader({
								id:this.id,
								baseParams:{connectionId:this.id}
							});
		},
		
		onClose: function() {
			Ext.Msg.confirm(this.title,_("Are you sure you want to close this connection?"),
				function(btn) {
					if(btn == 'yes') {
						eventDispatcher.fireCustomEvent("ConnectionClosed",this.id);
						this.destroy()
						if(!ditTreeTabPanel.items.length)
							toolbar.setDisabled(true);
					}
				},
				this /*scope*/);
			return false;
		},
		
		initComponent: function() {
			ditTree.superclass.initComponent.call(this);
			this.initLoader();
			
		},
		
		selModel:new Ext.tree.MultiSelectionModel(),
		autoScroll:true,
		animate:false,
		containerScroll:true,
		minSize:500,
		border:false,
		
		enableDD: true,
		root: {
			nodeType: 'async',
			disabled:false,
			enableDD:false,
			draggable:false,
			editable:false,
			text: 'Root DSE',
			leaf:true

		},
	});
	var toolbar = new Ext.Toolbar(
	{
		disabled:true,
		items:[{
			text:_('Add'),
			iconCls:'silk-add',
			xtype:'button'

		}, {
			text:_('Remove'),
			iconCls:'silk-delete',
			xtype:'button'		
		}]
	});
	
	var ditTreeTabPanel = new Ext.TabPanel({
		bbar: toolbar,
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
		toolbar.setDisabled(false);
		var tree = new ditTree({
						enableDD:true,
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
