Ext.onReady(function() {
	Ext.ns("AppKit");
	AppKit.GridTreeEditorField = Ext.extend(Ext.Component, {
		
		constructor: function(cfg) {
			Ext.apply(this,cfg);
			Ext.Component.prototype.constructor.call(this,cfg);
			this.bindEditorEvents();

		},
		reactivateGrid: function() {
			if(this.grid)
				this.grid.resumeEvents();
		},
		bindEditorEvents: function() {
			this.on("hide",this.reactivateGrid,this);
			this.on("destroy",this.reactivateGrid,this);
		},
		types: {},
		editing: false,
		ignoreNoChange: true,
		value: "",
		startValue: "",
	
		field: {
			focus: function () {}
		},
	
		setValue: function (v) {
			this.value = value;
		},
		
		getValue: function () {
			return this.value;
		},
	
		reset: function () {},
	
		focus: function () {},
	
		realign: function () {},
		
		searchStartValue: function () {
		},
		
		cancelEdit: function (remainVisible) {
			if(Ext.EventObject.browserEvent.type == "mousewheel")
				return false; //ignore cancel on scroll
			this.hideEdit(remainVisible);
			//this.fireEvent("canceledit",this,this.getValue,this.startValue);
			return true;
		},
		
		completeEdit: function (remainVisible) {
			if(!this.editing) {
				return;
			}
			if(this.startValue == this.getValue() && this.ignoreNoChange) {
				this.hideEdit(remainVisible)
				return;
			}
			if(this.fireEvent("beforeComplete",this,this.getValue(),this.startValue) !== false) {
				var value = this.getValue();
				this.hideEdit(remainVisible);
				this.fireEvent("complete",this,value,this.startValue);
			}
		},
		
		hideEdit: function (remainVisible) {
			if(remainVisible !== true) {
				this.editing = false;
				if(this.p)		
					this.p.destroy()
				this.grid.resumeEvents();
			}
		},
		
		getTree: function () {
			var tree = new Ext.tree.TreePanel({
				animate: true,
				rootVisible: false,
				enableDD: false,
				containerScroll:true,
				autoScroll:true,
				layout:'fit',
				border: true,
				singleExpand: true,
				cls:'propertySelectorList',
			//	style: 'height:200px',
			
				root: {
					nodeType: 'async',
					autoScroll:true,
					
					draggable: false,
					loader: new Ext.tree.TreeLoader({
						url: this.url,
						baseParams: {
							"asTree": true,
							"field" : 'properties',
							"connectionId" : this.grid.connId || this.grid.store.baseParams.connectionId
						}
					})
				},
				listeners: {
					dblclick: function (node, e) {
						if(!node.isLeaf())
							return true;
						this.value = node.text;
						this.completeEdit();
						return false;	
					},
					scope:this
				}
			});
			tree.getRootNode().addListener("expand",function (root) {
				var toExpand = null;
				root.eachChild(function (child) {
					if(!child.attributes.objclasses)
						return true;
					for (var i=0; i< child.attributes.objclasses.length; i++) {
						var e = child.attributes.objclasses[i]; 
						if((e == "DEFAULT" && !toExpand) || this.types[e]) {
							toExpand = child;
							continue;
						}
					}
					return true;
				},this);
				if(toExpand) {
					toExpand.expand();
					toExpand.ensureVisible();
				}
			},this);
			tree.getRootNode().expand();
			return tree;
		},
		
		determineType: function () {
			var store = this.record.store;
			Ext.iterate(store.data.items,function (el) {
				if(el.id.split("_")[0].toLowerCase() == "objectclass") {
					this.types[el.data.value] = true;
				}
			},this)
		},
		
		startEdit: function (el) {
			this.editing = true;
			this.startValue = el.innerHTML;			
			this.determineType();
			this.p =  this.getTree();
			this.p.setPosition(Ext.EventObject.getPageX(),Ext.EventObject.getPageY());
			this.grid.suspendEvents();
			this.grid.el.addListener("click",function (ev,target) {
				var el = Ext.get(target);
				if(el.hasClass('x-tree-root-ct') || el.parent('.x-tree-root-ct')) {
					ev.stopEvent();
					return false;
				} else {
					this.cancelEdit();
					return true;
				}
			},this);
	
			this.p.render(el.parentNode);
			this.fireEvent("startedit",el.parentNode,this.startValue);
		},
		
		stopEdit: function () {
			if(this.p)
				this.p.destroy();
		},
		onDestroy: function () {
			this.hideEdit();
		}
	});
})