Ext.ns('lconf');
<?php 
	$icons = AgaviConfig::get("modules.lconf.icons",array());
	$wizards =  AgaviConfig::get("modules.lconf.customDialogs",array());
	echo "lconf.wizards = ".json_encode($wizards);
?>
/***
 * TODO: Nearly 1000 lines of glorious code, split this class in smaller classes
 * e.g	 DitTreeNavigator
 * 		 DitTreeView
 * 		 DitTreeSearch
 */
lconf.ditTreeManager = function(parentId,loaderId) {	
	var dataUrl = loaderId;
	var ditPanelParent = Ext.getCmp(parentId);
	if(!ditPanelParent)
		throw(_("DIT Error: parentId ")+parentId+_(" is unknown"));
	
	var ditTreeLoader = Ext.extend(Ext.tree.TreeLoader,{
		
		dataUrl: dataUrl,

		createNode: function(attr) {
			var nodeAttr = attr;
			var i = 0;
			var objClass = attr.objectclass[i];
			var noIcon = false
			
			do {
				objClass = attr.objectclass[i];
				noIcon = false;
				// select appropriate icon
				switch(objClass) {
				<?php foreach($icons as $objectClass=>$icon) :?>
					
					case '<?php echo $objectClass;?>':
						nodeAttr.iconCls = '<?php echo $icon?>';
						break;
				<?php endforeach;?>

					default :
						noIcon = true;
						break;
				} 
			} while(noIcon && attr.objectclass[++i])
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
			if(attr["count"] && !attr["isLeaf"])
				shortened = shortened+"("+attr["count"]+")";
			return (withDN) ? shortened : shortened.replace(txtRegExp,"");
		}
	});
	
	var ditTree = Ext.extend(Ext.tree.TreePanel,{
	
		initEvents: function() {
			ditTree.superclass.initEvents.call(this);
			this.on("beforeclose",this.onClose);
			
			eventDispatcher.addCustomListener("filterChanged",function(filters) {
				this.loader.baseParams["filters"] = Ext.encode(filters);
				this.refreshNode(this.getRootNode(),true);
			},this)
			
			eventDispatcher.addCustomListener("refreshTree",function(node) {
				this.refreshNode(this.getRootNode(),true);
			},this);
			
			eventDispatcher.addCustomListener("searchDN",this.searchDN,this);
			
			eventDispatcher.addCustomListener("simpleSearch",function(snippet) {
				AppKit.log("!");
			},this);
			
			eventDispatcher.addCustomListener("aliasMode", function(node) {
				this.reloadFilters = this.loader.baseParams["filters"];
				this.loader.baseParams["filters"] = '{"ALIAS":"'+node.id+'"}';
				this.expandAllRecursive(null);
	
			},this);
		
			
			this.on("click",function(node) {eventDispatcher.fireCustomEvent("nodeSelected",node,this.id);});
			this.on("beforeNodeDrop",function(e) {e.dropStatus = true;this.nodeDropped(e);return false},this)
			this.on("contextmenu",function(node,e) {this.context(node,e)},this);
			this.on("beforeappend",function(tree,parent,node) {
				if(this.getNodeById(node.attributes.dn)) {
					var rnd = ((Math.floor((Math.random()*10000))+1000)%10000);
					node.attributes.dn = "*"+rnd+"*"+node.attributes.dn;
					node.attributes.id = node.attributes.dn; 
					node.id = node.attributes.id;
				}	
			},this);
			
			this.on("append",function(obj,parent,node) {
				
				if(node.attributes.match == "noMatch") {
					(function() {node.getUI().addClass('noMatch');}).defer(100)
					node.expand();
				}
			});
			
		},
		

		processDNForServer: function(dn) {
			dn = dn.replace("ALIAS=Alias of:","");
			dn = dn.replace(/^\*\d{4}\*/,"");
			return dn;
		},
		
		context: function(node,e) {
			e.preventDefault();
			var ctx = new Ext.menu.Menu({
				items: [{
					text: _('Refresh this part of the tree'),
					iconCls: 'silk-arrow-refresh',
					handler: this.refreshNode.createDelegate(this,[node,true]),
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
					hidden: !node.attributes.isAlias && !node.id.match(/\*\d{4}\*/),
					handler: this.jumpToRealNode.createDelegate(this,[node])	
				},{
					text: _('Display aliases to this node'),
					iconCls: 'silk-wand',
					hidden: node.attributes.isAlias || node.id.match(/\*\d{4}\*/),
					handler: function(btn) {
						eventDispatcher.fireCustomEvent("aliasMode",node);
					}
				},{
					text: _('Search/Replace'),
					iconCls: 'silk-zoom',
					handler: this.searchReplaceMgr.createDelegate(this),
					hidden: (node.parentNode),
					scope:this
				}]
			});
			ctx.showAt(e.getXY())
			
			
		},
		refreshCounter :  0,
		expandAllRecursive: function(node,cb) {
			node = node || this.getRootNode();
			if(node == this.getRootNode())
				this.refreshCounter = 0;
			node.reload();	
			node.on("load",function() {
				node.eachChild(function(newNode){
					this.expandAllRecursive(newNode,cb)
				});
				this.refreshCounter--;
				if(this.refreshCounter<1)
					if(cb)
						cb();
			},this,{single:true});
		},
		
		getExpandedSubnodes: function(node) {
			var expanded = {
				here : [],
				nextLevel: []
			}
			node.eachChild(function(subNode) {
				if(subNode.isExpanded()) {
					expanded.here.push(subNode.id);			
					expanded.nextLevel.push(this.getExpandedSubnodes(subNode));
				}
			},this);
			return expanded;
		},
		
		expandViaTreeObject: function(treeObj,finishFn) {
			Ext.each(treeObj.here,function(nodeId) {
				var node = this.getNodeById(nodeId);
				if(!node) {
					AppKit.log("Gnaaah!")
					return true;
				}
				var getNext = function() {
					if(!Ext.isEmpty(treeObj.nextLevel.length))
						if(finishFn)
							finishFn();	
					Ext.each(treeObj.nextLevel,function(next){
						this.expandViaTreeObject(next,finishFn);						
					},this,{single:true})
				}
				
				if(!node.isExpanded()) {
					node.on("expand",function(_node) {
						getNext.call(this);
					},this,{single:true});
					node.expand();
				} else {				
					getNext.call(this);	
				}		
			},this);		
		},
		
		refreshNode: function(node,preserveStructure,callback) {
			if(this.reloadFilters) {
				this.loader.baseParams["filters"] = this.reloadFilters;
				preserveStructure = false;
			}
			this.reloadFilters = false;
			
			if(!node)
				node = this.getRootNode();
			if(preserveStructure) {
				var	expandTree = this.getExpandedSubnodes(node);
			}
			if(node.attributes.isAlias) {
				var aliased = this.getAliasedNode(node);
				if(aliased) {
					aliased.reload();
					var aliasedExpandTree = this.getExpandedSubnodes(node);
				}
			}
			node.reload();
			if(preserveStructure) {
				this.on("load", function(elem) {
					this.expandViaTreeObject(expandTree,callback);
				},this,{single:true});
			}
			
		},
		
		searchReplaceMgr: function() {
			var form = new Ext.form.FormPanel({
				layout: 'form',
				borders: false,
				labelWidth:300,
				padding:5,
				items: [{
					xtype:'textfield',
					fieldLabel: _('Search RegExp:'),
					name: 'search',
					allowBlank: false
				},{
					xtype: 'textfield',
					fieldLabel: _('Attributes to include (comma-separated)'),
					name: 'fields',
					allowBlank: false
				},{
					xtype: 'textfield',
					fieldLabel: _('Replace String:'),
					name: 'replace',
					allowBlank: true
				}]	
			});
			var curid = Ext.id();
			
			var srWnd = new Ext.Window({
				modal:true,
				id : 'wnd_'+curid,
				autoDestroy:true,
				constrain:true,
				height:150,
				width:600,
				title: _("Search/Replace"),
				renderTo: Ext.getBody(),
				layout:'fit',
				items: form,
				buttons: [{
					text: _('Sissy mode (Just show me what would be done)'),
					handler :function() {
						var _bForm = form.getForm();
						if(!_bForm.isValid())
							return false;
						this.callSearchReplace(_bForm.getValues(),true);
					//	Ext.getCmp('wnd_'+curid).close();
					},
					scope:this
				},{
					text: _('Execute'),
					handler :function() {
						var _bForm = form.getForm();
						if(!_bForm.isValid())
							return false;
						this.callSearchReplace(_bForm.getValues());
						Ext.getCmp('wnd_'+curid).close();
					},
					scope:this				
				}]
			}).show();
		},
		
		callSearchReplace: function(values,SissyMode) {
			var mask = new Ext.LoadMask(Ext.getBody(),_("Please wait"));
			mask.show();
			Ext.Ajax.request({
				url: '<?php echo $ro->gen("lconf.data.searchreplace"); ?>',
				params: {
					search: values["search"],
					fields: values["fields"],
					replace: values["replace"],
					filters: lconf.getActiveFilters(),
					connectionId: this.connId,
					sissyMode: SissyMode
				},

				success: function(resp) {
					mask.hide();
					if(SissyMode)
						Ext.Msg.alert(_("Search/Replace"),_("The following changes would be made:<br/>")+resp.responseText);
					else if(resp.responseText  != 'success') {
						var error = Ext.decode(resp.responseText);
						var msg = "<div class='lconf_infobox'><ul>";
						Ext.each(error,function(err){
							msg += "<li>"+err+"</li>";
						});
						msg += "</ul></div>";
						Ext.Msg.alert(_('Search/Replace error'),_("The following errors were reported:<br/>"+msg));
					} else {
						Ext.Msg.alert(_("Success"),_("Seems like everything worked fine!"));
					}
					this.refreshNode();
				},
				failure: function() {
					mask.hide();
					err = (resp.responseText.length<50) ? resp.responseText : 'Internal Exception, please check your logs';
					Ext.Msg.alert(_("Error"),err);
				},
				scope:this
			});
		},

		
		removeNodes: function(nodes) {
			var dn = [];
			if(!Ext.isArray(nodes))
				nodes = [nodes];
			Ext.each(nodes,function(node) {
				var id = (node.attributes["aliasdn"] || node.id)
				dn.push(this.processDNForServer(id));
			},this);
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
		
		
		jumpToRealNode : function(alias) {
			var id = this.processDNForServer(alias.id);
			var node = this.getNodeById(id);
		
			if(!node)  {
			 	node = this.searchDN(id);
				return true;
			} 
			this.selectPath(node.getPath());
			this.expandPath(node.getPath());
		},
			
		searchDN : function(dn) {
			var baseDN = this.getRootNode().id;
			var dnNoBase = dn.substr(0,(dn.length-(baseDN.length+1)));
			var splitted = dnNoBase.split(",");
			var expandDescriptor = {
				here: baseDN,
				nextLevel: []
			}
			var curPos = expandDescriptor;
			var lastDN = baseDN;
			while(splitted.length) {
				lastDN =  splitted.pop()+","+lastDN
				curPos.nextLevel = {
					here: lastDN,
					nextLevel: []
				}
				curPos = curPos.nextLevel
			}
			var finishFN = function() {
				var node = this.getNodeById(dn);
				this.selectPath(node.getPath());
				eventDispatcher.fireCustomEvent("nodeSelected",node,this.id);
			}
			this.expandViaTreeObject(expandDescriptor,finishFN.createDelegate(this));
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
					height: Ext.getBody().getHeight()> 400 ? 400 : Ext.getBody().getHeight(),
					centered:true,
					stateful:false,
					shadow:false,
					autoScroll:true,
					constrain:true,
					modal:true,
					title: _('Create new entry'),
					layout:'fit',
					closeAction:'hide'
				});
			}
			
	
			this.wizardWindow.removeAll();
			this.wizardWindow.add(this.getNodeSelectionDialog());

			this.wizardWindow.doLayout();
			this.wizardWindow.show();
			this.wizardWindow.center();
			
		},
		
		updateWizard: function(view,sTry) { 			
			this.wizardWindow.removeAll();
			
			if(!sTry && !lconf.wizard) {
				if(sTry)
					Ext.msg.alert(_("Error"),_("View not found despite successful loading"));
				else 
					this.lazyloadWizard(view,this.updateWizard)
			} else {
				var wizard = new lconf.wizard({wizardView:view,parentNode : this.newNodeParent});
				wizard.setConnectionId(this.id);
				this.wizardWindow.add(wizard);
				this.wizardWindow.doLayout();
				this.wizardWindow.center();
			}
		},
		
		lazyloadWizard : function(view,fn,wnd) {
			wnd = wnd || this.wizardWindow;
			var pbar = new Ext.ProgressBar({
				width:200,
				autoHeight:true,
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
				height: Ext.getBody().getHeight()*0.9 > 400 ? 400 : Ext.getBody().getHeight()*0.9,
				autoScroll:true,
				constrain:true,
				items: [{
					height:Ext.getBody().getHeight()*0.9 > 400 ? 400 : Ext.getBody().getHeight()*0.9,
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
									"<em unselectable='on'><div class='{iconCls}' style='float:left;height:25px;width:25px;overflow:hidden'>&nbsp;</div>{description}</em>",
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
					handler: this.copyNode.createDelegate(this,[e.point,e.dropNode,e.target]),
					scope:this,
					iconCls: 'silk-arrow-divide'
				},{
					text: _('Move node here'),
					handler: this.copyNode.createDelegate(this,[e.point,e.dropNode,e.target,true]),
					scope:this,
					iconCls: 'silk-arrow-turn-left'
				},{
					text: _('Clone node <b>as subnode</b>'),
					handler: this.copyNode.createDelegate(this,["append",e.dropNode,e.target]),
					scope:this,
					hidden: !e.target.isLeaf(),
					iconCls: 'silk-arrow-divide'
				},{
					text: _('Move node  <b>as subnode</b>'),
					handler: this.copyNode.createDelegate(this,["append",e.dropNode,e.target,true]),
					scope:this,
					hidden: !e.target.isLeaf(),
					iconCls: 'silk-arrow-turn-left'
				},{
					text: _('Create alias here'),
					iconCls: 'silk-attach',
					hidden: e.dropNode.attributes.isAlias,
					handler: this.buildAlias.createDelegate(this,[e.point,e.dropNode,e.target])
				},{
					text: _('Cancel'),
					iconCls: 'silk-cancel'
				}]
			});
			ctx.showAt(e.rawEvent.getXY())
			
		},
		
		buildAlias: function(pos,from,to) {
			var toDN = to.id;
			if(pos != 'append')
				toDN = to.parentNode.id;
			
			if(from.parentNode.id == toDN) {
				Ext.Msg.alert(_("Error"),_("Target and source are the same"))
				return false;
			}
			
			var properties = [{
				"property" : "objectclass",
				"value" : "extensibleObject",
			},{
				"property" : "objectclass",
				"value" : "alias"
			},{
				"property" : "aliasedObjectName",
				"value" : from.id,
			}]
			Ext.Ajax.request({
				url: '<?php echo $ro->gen("lconf.data.modifynode");?>',
				params: {
					connectionId: this.connId,
					xaction: 'create',
					parentNode: toDN,
					properties: Ext.encode(properties)
				},
				failure:function(resp) {
					err = (resp.responseText.length<1024) ? resp.responseText : 'Internal Exception, please check your logs';
					Ext.Msg.alert(_("Error"),_("Couldn't create alias node:<br\>"+err));
				},
				success: function() {
					this.refreshNode(to.parentNode,true);
				},
				scope:this
			});
					
		},
		
		copyNode: function(pos,from,to,move) {
			
			var toDN = to.id;
			if(pos != 'append')
				toDN = to.parentNode.id;
				
			if(move && from.parentNode.id == toDN) {
				Ext.Msg.alert(_("Error"),_("Target and source are the same"))
				return false;
			}
			
			var copyParams = {
				targetDN: this.processDNForServer(toDN),
				sourceDN: this.processDNForServer(from.id)
			}
						
			Ext.Ajax.request({
				url: '<?php echo $ro->gen("lconf.data.modifynode");?>',
				params: {
					connectionId: this.connId,
					xaction: move ? 'move' :'clone' ,
					properties: Ext.encode(copyParams)
				},
				failure:function(resp) {
					err = (resp.responseText.length<1024) ? resp.responseText : 'Internal Exception, please check your logs';
					Ext.Msg.alert(_("Error"),_("Couldn't copy node:<br\>"+err));
				},
				success: function() {
					this.refreshNode(to.parentNode,true);
					this.refreshNode(from.parentNode,true);
				},
				scope:this
			});
		},
		
		initLoader: function() {
			this.loader = new ditTreeLoader({
								id:this.id,
								baseParams:{
									connectionId:this.id,
									filters: lconf.getActiveFilters()
								},
								listeners: {
									
									beforeload: function(obj,node,cbk) {
										if(node.id.match(/\*\d{4}\*/)) {
											this.jumpToRealNode(node);
											return false;
										}
									},
									scope: this
								}
								
							});
		},
		
		onClose: function() {
			Ext.Msg.confirm(this.title,_("Are you sure you want to close this connection?"),
				function(btn) {
					if(btn == 'yes') {
						eventDispatcher.fireCustomEvent("ConnectionClosed",this.id);
						this.destroy()
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
	var searchWindow = new Ext.Window({
		layout:'fit',
		constrain:true,
		closeAction:'hide',
		renderTo:Ext.getBody(),
	});
	var dnSearchField = new Ext.form.TextField({
		xtype:'textfield',
		value: 'Search keyword',
		enableKeyEvents: true,
		connId: false,
		listeners: {
			focus: function(e) {e.setValue("")},
			change: function(field,val) {
				if(!field.connId) {
					field.reset();
					return false;
				}
				if(val == "" || val == field.originalValue) {
					field.reset();
					return false
				}
				searchWindow.removeAll();
				searchWindow.add({
					xtype: 'simplesearchgrid',
					connId: field.connId,
					search: val
				});
				searchWindow.setTitle(val);
				searchWindow.doLayout();
				searchWindow.show();
				field.reset();
			},
			keypress: function(field,e) {
				if(e.getKey() == e["ENTER"]) {
					field.blur();
				}
				if(e.getKey() == e["ESC"]) 
					field.reset();
				
			},
			scope: this
		}
	});

	var ditTreeTabPanel = new Ext.TabPanel({
		autoDestroy: true,
		resizeTabs:true,
		
		fbar: new Ext.Toolbar({
			items:dnSearchField
		}),
		defaults : {
			closable: true
		},
		listeners: {
			tabchange: function(ac) {
			 	if(!ac.activeTab) {
			 		dnSearchField.connId = null;
			 		return false;
			 	}

				dnSearchField.connId = ac.activeTab.connId;
			}
		}
	});
	
	ditPanelParent.add(ditTreeTabPanel);
	ditPanelParent.doLayout();
	

	
	// init listener
	eventDispatcher.addCustomListener("ConnectionStarted",function(connObj) {
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
