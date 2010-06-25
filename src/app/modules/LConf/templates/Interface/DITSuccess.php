Ext.ns('lconf');
<?php 
	$icons = AgaviConfig::get("modules.lconf.icons",array());
	$wizards =  AgaviConfig::get("modules.lconf.customDialogs",array());
	echo "lconf.wizards = ".json_encode($wizards);
?>

lconf.ditTreeManager = function(parentId,loaderId) {	
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
			var aliasString = "ALIAS=Alias of:";
			nodeAttr.text = this.getText(attr);
			nodeAttr.qtip = _("<b>ObjectClass:</b> ")+objClass+
							_("<br/><b>DN:</b> ")+attr["dn"]+
							_("<br/>Click to modify");
			nodeAttr.id = attr["dn"];
			nodeAttr.leaf = attr["isLeaf"] ? true :false;
			if(nodeAttr.id.substr(0,aliasString.length) == aliasString)
				nodeAttr.isAlias = true;
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
			e.preventDefault();
			var ctx = new Ext.menu.Menu({
				items: [{
					text: _('Refresh this part of the tree'),
					iconCls: 'silk-arrow-refresh',
					handler: this.refreshNode.createDelegate(this,[node]),
					scope: this,
					hidden: node.isLeaf()
				},{
					text: _('Create new node on same level'),
					iconCls: 'silk-add',
					handler: this.callNodeCreationWizard.createDelegate(this,[{node:node}]),
					scope: this,
					hidden: !(node.parentNode)
				},{
					text: _('Create new node as child'),
					iconCls: 'silk-sitemap',
					handler: this.callNodeCreationWizard.createDelegate(this,[{node:node,isChild:true}]),
					scope: this
				},{
					text: _('Remove <b>only this</b> node'),
					iconCls: 'silk-delete',
					handler: function() {
						Ext.Msg.confirm(_("Remove selected nodes"),_("Do you really want to delete this entry?<br/>")+
																  _("Subentries will be deleted, too!"),
							function(btn){
								if(btn == 'yes') {
									this.removeNodes([node]);
								}
							},this);
					},
					scope: this
				},{
					text: _('Remove <b>all selected</b> nodes'),
					iconCls: 'silk-cross',
					hidden:!(this.getSelectionModel().getSelectedNodes().length),
					handler: function() {
						Ext.Msg.confirm(_("Remove selected nodes"),_("Do you really want to delete the selected entries?<br/>")+
																  _("Subentries will be deleted, too!"),
							function(btn){
								if(btn == 'yes') {
									var toDelete = this.getSelectionModel().getSelectedNodes();
									this.removeNodes(toDelete);
								}
							},this);
					},
					scope: this		
				},{
					text: _('Jump to alias target'),
					iconCls: 'silk-arrow-redo',
					hidden: !node.attributes.isAlias,
					handler: this.jumpToRealNode.createDelegate(this,[node])	
				}]
			});
			ctx.showAt(e.getXY())
			
			
		},
		
		refreshNode: function(node) {
			if(node.attributes.isAlias) {
				var aliased = this.getAliasedNode(node);
				if(aliased)
					aliased.reload();
			}
			node.reload();
		},
				
		removeNodes: function(nodes) {
			var dn = [];
			if(!Ext.isArray(nodes))
				nodes = [nodes];
			Ext.each(nodes,function(node) {dn.push(node.attributes["aliasdn"] || node.id);});
			var updateNodes = this.getHighestAncestors(nodes);
			Ext.Ajax.request({
				url: '<?php echo $ro->gen("lconf.data.modifynode");?>',
				params: {
					properties: Ext.encode(dn), 
					xaction:'destroy',
					connectionId: this.connId
				},
				success: function(resp) {
					Ext.each(updateNodes,function(node) {
						if(node)
							this.refreshNode(node);				
					},this)
				},
				failure: function(resp) {
					err = (resp.responseText.length<50) ? resp.responseText : 'Internal Exception, please check your logs';
					Ext.Msg.alert(_("Error"),_("Couldn't remove Node:<br\>"+err));
				},
				scope: this
			});
		},
		
		/**
		 * Reduces a set of nodes to the highest ancestors of them.
		 * A set [A,B,C,D,E,F] with the followinf structure
		 * 
		 * X1--A-F
		 * 
		 * X2-|     |-D-E
		 *    |-B---|
		 *    |     |-C
		 * 
		 * Would return [X1,X2] (The parents of A/B)
		 * If the root node is reached, this node will be returned
		 * 
		 * Is used to determine which par
		 * 
		 * @param {Array} An array of Ext.tree.TreeNode
		 * @return {Array}
		 */
		getHighestAncestors: function(nodeSet) {
			var returnSet = [];
			for(var i=0;i < nodeSet.length;i++) {
				var node = nodeSet[i];
				var hasAncestor = false;
				for(var x=0;x < nodeSet.length;x++) {
					var checkNode = nodeSet[x];
					if(checkNode == node)
						continue;
					if(node.isAncestor(checkNode)) {
						hasAncestor = true;
						break;
					}
				}
				if(!hasAncestor) {
					if(node.parentNode)
						returnSet.push(node.parentNode);
					else // it's the root node
						returnSet.push(node);
				}
			}
			return Ext.unique(returnSet);
		},
		
		getAliasedNode: function(alias) {
			var id = alias.id.substr(("ALIAS=Alias of:").length);
			var node = this.getNodeById(id);
			return node;
		},
		
		jumpToRealNode : function(alias) {
			var node = this.getAliasedNode(alias);
			if(!node)  {
			 	node = this.searchNodeByDN(id);
				return true;
			} 
			this.selectPath(node.getPath());
			this.expandPath(node.getPath());
		},
			
		searchNodeByDN : function(id) {
			
		},
		
		callNodeCreationWizard : function(cfg) {
			var _parent = cfg.node;
			if(!cfg.isChild)
				_parent = _parent.parentNode;
			this.newNodeParent = _parent.id;
			if(!this.wizardWindow) {
				this.wizardWindow = new Ext.Window({
					width:800,
					id:'newNodeWizardWnd',
					renderTo: Ext.getBody(),
					autoHeight:true,
					stateful:false,
					minHeight:400,
					shadow:false,
					autoScroll:true,
					constrain:true,
					modal:true,
					title: _('Create new entry'),
					layout:'fit',
					closeAction:'hide'
				});
			}
			this.wizardWindow.show();
			this.wizardWindow.removeAll();
			this.wizardWindow.add(this.getNodeSelectionDialog());
			this.wizardWindow.doLayout();
			
		},
		
		updateWizard: function(view,sTry) { 			
			this.wizardWindow.removeAll();
			
			if(!sTry) {
				if(sTry)
					Ext.msg.alert(_("Error"),_("View not found despite successful loading"));
				else 
					this.lazyloadWizard(view,this.updateWizard)
			} else {
				var wizard = new lconf.wizards[view]({parentNode : this.newNodeParent});
				wizard.setConnectionId(this.id);
				this.wizardWindow.add(wizard);
				this.wizardWindow.doLayout();
			}
		},
		
		lazyloadWizard : function(view,fn,wnd) {
			wnd = wnd || this.wizardWindow;
			var pbar = new Ext.ProgressBar({
				width:200,
				height:400,
				text: _('Loading wizard')
			});
			wnd.add(pbar);
			pbar.wait({interval:100,increment:15,text: _('Loading wizard')});
			wnd.doLayout();
			
			Ext.Ajax.request({
				url: "<?php echo $ro->gen('lconf.ldapeditor.editorwizard')?>",
				success: function(resp) {
					wnd.removeAll(pbar);
					eval(resp.responseText);
					fn.call(this,view,true);
				},
				failure: function(resp) {
					err = (resp.responseText.length<50) ? resp.responseText : 'Internal Exception, please check your logs';
					Ext.Msg.alert(_("Error"),_("Couldn't load editor:<br\>"+err));
					pbar.hide();		
				},
				scope:this,
				params: {view: view}
			});
			
		},
		
		getNodeSelectionDialog: function() {
			return new Ext.Panel({
				borders:false,
				autoDestroy:false,
				margins: "3 3 3 3",
				autoHeight:true,	
				items: [{
					height:300,
					autoScroll:true,
					singleSelect:true,
					
					xtype:'listview',
					store: new Ext.data.JsonStore({
						autoDestroy:true,
						data: lconf.wizards,
						idProperty: 'view',
						fields: ['view','description','iconCls']
					}),
					listeners: {
						selectionchange: function(view, selections) {
							var selected = view.getSelectedRecords()[0];
							this.selectedWizard = selected;
						},
						scope:this
					},
					columns: [{
						tpl:new Ext.XTemplate("<tpl>",
								"<div style='width:100%;text-align:left;'>",
									"<em unselectable='on'><div class='{iconCls}' style='float:left;height:25px;width:25px'>&nbsp;</div>{description}</em>",
								"</div>"),
						header: _('Entry description'),
						dataIndex: 'description'							
					}]
				}], 
				buttons: [{
					text: _('Next &#187;'),
					handler: function(btn) {
						if(!this.selectedWizard)	{
							// Confirm if nothing is selected 
							Ext.Msg.confirm(
								_('Nothing selected'),
								_('You haven\'t selected anything yet, create a custom entry?'),
								function(btn) {
									if(btn == "yes")
										this.updateWizard('default');
								},this
							)
						 }else
							this.updateWizard(this.selectedWizard.id);
					},
					scope:this
				}]
			});
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
		tree.connId = connObj.id;
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

new lconf.ditTreeManager('<?php echo $t["parentId"];?>','<?php echo $ro->gen("lconf.data.directoryprovider")?>');
