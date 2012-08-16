Ext.ns("LConf.View").PropertyManagerPanel = Ext.extend(Ext.TabPanel,{
    activeTab: 0,
    store: null,
    initCfg: null,
    reloadOnSave: true,
    deferredRender: false,
    autoScroll:true,

    eventDispatcher: null,
    referenceEvents: true,
    getStore: function() {
        return this.store;
    },

    setConnectionId: function(id) {
        this.getStore().setConnection(id);
    },
    
    constructor: function(cfg) {
        Ext.apply(this,cfg || {});
        this.initCfg = cfg;
        cfg.autoScroll = true;
        this.setupFooter();
        Ext.TabPanel.prototype.constructor.call(this,cfg);
    },

    initComponent: function() {
        this.setupPropertyStore();
        Ext.TabPanel.prototype.initComponent.apply(this,arguments);
        
        var childCfg = this.initCfg;
        childCfg.propertyManager = this;
        childCfg.store = this.store;
        childCfg.autoDestroy = false;
        this.keyValGrid = new LConf.View.PropertyKeyValueGrid(childCfg);
        this.add(new Ext.Panel({    
            title: _('Properties'),
            items: this.keyValGrid,
            layout: 'fit'
        }));
        
    },

    initEvents: function() {
        // disable when connection is closed or an error occured during node loading
        this.eventDispatcher.addCustomListener("ConnectionClosed",this.disable,this);
        this.eventDispatcher.addCustomListener("invalidNode",this.disable,this);
        this.eventDispatcher.addCustomListener("nodeSelected",function(node) {
            
            this.clearNodeSpecificViews();
            this.addNodeSpecificViews(node);
            if(this.hasReferenceEvents()) {
                this.viewProperties.apply(this,arguments);
            }
        }, this,{
            buffer:true
        });
        Ext.TabPanel.prototype.initEvents.apply(this,arguments);
    },
    
    clearNodeSpecificViews: function() {
        for(var i=this.items.length-1;i>=1;i--) {
            this.items.items[i].destroy();
            this.remove(this.items.items[i]);
        }
        this.setActiveTab(0);
    },
    
    addNodeSpecificViews: function(node) {
        var mainTab = 0;
        var dialogs = LConf.Extensions.Registry.getMatchingPropertyViews(node,this.getStore());
        for(var i=0;i<dialogs.length;i++) {
            this.add(dialogs[i]);
            dialogs[i].doLayout();
            if(dialogs[i].isMain)
                mainTab = i+1;
        }
        this.setActiveTab(mainTab);
        this.doLayout();
    },

    toggleReferences: function(bool) {
        this.referenceEvents = bool;
    },
    
    hasReferenceEvents: function() {
        return this.referenceEvents;
    },

    setupFooter: function() {
        this.fbar = new Ext.Toolbar({
            disabled: !this.enableFb,
            items: [{
                xtype:'button',
                text: _('Save Changes'),
                iconCls: 'icinga-icon-disk',
                handler: function () {
                    if(this.getStore().isValid() !== true) {
                        Ext.Msg.alert(_("Property ")+this.getStore().isValid()+_(" is invalid"),_("Please review your entries "));
                    }
                    return this.getStore().saveChanges();
                },
                scope:this
            }]
        });
    },
    
    setupPropertyStore: function() {
        this.store =  new LConf.Store.LDAPPropertyStore(this.initCfg);
        if(this.parentNode) {
            this.store.setParentNode(this.parentNode);
        }
    },

    viewProperties: function (selectedDN,connection,noAsk) {
        var store = this.getStore();
        if(!store)
            return null;
        // check for pending changes
        if(store.hasPendingChanges() && !noAsk) {
            Ext.Msg.confirm(_("Unsaved changes pending"),_("Save changes?"),function (btn) {
                // ask, store if necessary, and then retry without asking
                if(btn == 'yes') {
                    store.save();
                    store.on("save",function () {
                        this.viewProperties(selectedDN,connection,true);
                    },this,{
                        single:true
                    });
                } else {
                    this.viewProperties(selectedDN,connection,true);
                }
            },this);
            return false;
        }

        this.selectedNode = selectedDN;
        var id = selectedDN.attributes["aliasdn"] || selectedDN.id;
        id = id.replace(/^\*\d{4}\*/,"");

        store.setNode(id);
        store.setConnection(connection);
        store.load();
        this.disable(false);
        return true;
    },

    disable: function (val) {
        
        for(var i=0;i<this.items.length;i++) {
            this.items.each(function(item) {
                item.items.each(function(p) {
                    if(typeof p.disableView === "function")
                        p.disableView(val);
                })
            })
        }
        this.getFooterToolbar().setDisabled(val);
        if(val == true)
            this.getStore().removeAll(false);
    }
    
});
