/**
 * Generic simple editor view for host,service or contactgroups, including a grid view
 * 
 */
new (function() {

var prefix = LConf.Configuration.prefix;

var updateFieldValues = function(map) {   
    if(!this.lconfProperty) {
        return;
    }
    var lconfProperty = this.lconfProperty.toLowerCase();
    for(var i in map) {
        if(lconfProperty == i.toLowerCase())
            this.setValue(map[i]);
    }
}

var updateFormValues = function() {
    var ldapMap = {}
    this.store.each(function(r) {
        ldapMap[r.get('property').toLowerCase()] = r.get('value');
    });
    if(this.rendered) {       
        this.getForm().callFieldMethod("updateFieldValues",[ldapMap]);

    } else {
        this.on("show",updateFormValues,this,{single:true});
    }
}

/**
 * Returns a {Ext.ux.data.PagingJsonStore} that can be used for selecting members of the 
 * hostgroup
 * 
 * @param {String}  The objectclass name of the appliable members
 * @param {Ext.data.JsonStore}  The (maybe not yet populated) store containing the nodes information. Required
 *                                                      to determine which members are already selected
 *                                                      
 * @return {Ext.ux.data.PagingJsonStore}
 */
var getGroupMembersStore = function(field,objectStore) {
    var urls = Ext.ns("LConf.Editors").EditorFieldManager.urls;
    var memberStore = new Ext.ux.data.PagingJsonStore({
        autoDestroy:true,
        idParam: 'entry',
        fields: ['active','entry'],
        url: String.format(urls.ldapmetaprovider),
        baseParams: {
            field:'{"LDAP":["objectclass='+field+'"],"Attr": "cn"}',
            connectionId: objectStore.getConnection() // maybe null, but is read on the objectStore's load event'
        },
        listeners: {
            load: function(store,records) {
                if(!this.activeMembers)
                    return false;
                for(var i=0;i<this.activeMembers.length;i++) {
                    var el = this.getById(this.activeMembers[i]);
                    if(el) {
                        el.set("active",true);
                    }
                }
                
                if(this.onlyAssigned)
                    this.filter("active",true);
            }
        }
    });
   
    memberStore.markActive = function(records) {
        this.activeMembers = [];
        for(var i=0;i<records.length;i++) {
            var splitted = records[i].get('value').split(",");
            for(var x=0;x<splitted.length;x++)
                this.activeMembers.push(splitted[x]);
        }
    }
    if(objectStore.getConnection()) { // if we already have a populated objectclass store, load directly
        memberStore.load();
    }
    
    objectStore.on("load", function(store,records) {
        memberStore.setBaseParam("connectionId",objectStore.getConnection());
        memberStore.markActive(store.findProperty(prefix+"members"));
        memberStore.load();
    });
    objectStore.on("update", function(store,records) {
        memberStore.markActive(store.findProperty(prefix+"members"));
        memberStore.load();
    });
    return memberStore;
}
 
/** 
 * Returns the {Ext.form.FormPanel} containing the editor fields for name and alias definition
 *
 * @param {String}  The group type (Hostgroup,Servicegroup, etc.)
 * 
 * @return {Ext.form.FormPanel}
 **/
var getGroupView = function(type,store) {
    var onFieldChange = function(cmp,value) {
        if(value == "" && cmp.allowBlank !== false) {
            store.deleteProperties(store.findProperty(cmp.lconfProperty));
        } else {
            store.setProperty(cmp.lconfProperty,value);
        }
    }
    var form = new Ext.form.FormPanel({
        xtype: 'form',
        width: '90%',
        store: store,
        anchor: '90%',

        padding: "1em 1em 1em 1em",
        autoHeight: true,
        items:{
            autoDestroy: true,
            xtype: 'fieldset',
            iconCls: 'icinga-icon-'+type.toLowerCase(),
            title: type+' info',
            labelWidth: 200,
            anchor: '90% ',
            defaults: {
                listeners: {
                    change: onFieldChange,
                    invalid: function(cmp) {
                        if(!cmp.activeError)
                            store.markInvalid(true);
                    },
                    valid: function(cmp) {
                        store.markInvalid(false);
                    }
                }
            },
            items: [{
                    xtype: 'textfield',
                    fieldLabel: type+" name",
                    lconfProperty: 'cn',
                    allowBlank: false,
                    updateFieldValues: updateFieldValues
            },{
                    xtype: 'textfield',
                    fieldLabel: type+" alias",
                    lconfProperty:  prefix+'alias',
                    updateFieldValues: updateFieldValues
            }]
        }
    });
    store.on("load",updateFormValues.createDelegate(form));
    store.on("update",updateFormValues.createDelegate(form));
    form.on("destroy",function() {
        store.removeListener("load",updateFormValues.createDelegate(form));
        store.removeListener("update",updateFormValues.createDelegate(form));
    },this)
    return form;
}


/**
 * Returns the grid view for this group editor
 * 
 * @param {String}  The grouptype (hostgroup,contactgroup,etc)
 * @param {Ext.data.Store} The store containing information about the currently selected node
 * @param {String}  The objectclass that can be added as a lconfMember
 * 
 * @return {Ext.grid.GridPanel}
 */
var getGroupMembersView = function(type,store,objectclasses) {

    var memberStore = getGroupMembersStore(prefix+objectclasses,store); 
    
    var syncLdapStore = function() {
        var property = [];
        (memberStore.snapshot || memberStore).each(function(record) {
            if(record.get('active'))
                property.push(record.get('entry'));
        });
        store.setProperty(prefix+"members",property.join(","));
    }
    
    var chkBox = new Ext.grid.CheckboxSelectionModel({
        sortable: true,
        checkOnly: true,
        listeners: {
            rowselect:  function(sm,rowId,record){
                if(!this.ignoreSelectEvents) {
                    record.set('active',true);
                    syncLdapStore();
                }
            },
            rowdeselect:  function(sm,rowId,record){
                if(!this.ignoreSelectEvents) {
                    record.set('active',false);
                    syncLdapStore();
                }
            }
        }
    });

    var grid = new Ext.grid.GridPanel({
        sm: chkBox,
        anchor: '95%',
        tbar: new Ext.Toolbar({
            items: [{
                xtype: 'button',
                iconCls: 'icinga-icon-accept',
                text: _('Only assigned'),
                enableToggle: true,
                toggleHandler: function(btn,state) {
                    if(state === false) {
                        memberStore.clearFilter();
                        memberStore.onlyAssigned = false;
                    } else {
                        memberStore.filter("active",true);
                        memberStore.onlyAssigned = true;
                    }
                },
                scope: this
            },'->',{
                xtype: 'textfield',
                iconCls: 'icinga-icon-search',
                emptyText: 'Enter text to filter',
                enableKeyEvents: true,
                listeners: {
                    keyup: function(t) {
                        memberStore.curFilter = t.getRawValue();
                        memberStore.filter("entry",new RegExp(memberStore.curFilter));
                    }
                }
            }]
        }),
        // allow paging
        bbar: new Ext.ux.PagingToolbar({
            store:  memberStore, 
            pageSize: 50
        }),
        colModel: new Ext.grid.ColumnModel({
            columns: [
                chkBox,{
                    header: "Name",
                    dataIndex: 'entry'
                }]
        }),
        store: memberStore,
        viewConfig: {
            forceFit: true
        },
        height: 400
    });

    var onChanged = function() {    
        var toSelect = []
        memberStore.each(function(record) {
            if(record.get('active'))
                toSelect.push(record);
        })
        // we can't use supressEvents here, because then the whole selection wouldn't be visible'
        grid.getSelectionModel().ignoreSelectEvents = true;
        grid.getSelectionModel().selectRecords(toSelect);
        grid.getSelectionModel().ignoreSelectEvents = false;
    }
    
    memberStore.on("load",onChanged ,this,{buffer:100});
    memberStore.on("load",function(my) {
        if(my.curFilter)
            my.filter("entry",new RegExp(my.curFilter));

    },this,{buffer:100});
    memberStore.on("datachanged",onChanged ,this,{buffer:100});
    
    return {
        xtype: 'fieldset',
        title: 'Members',
        items: grid
    }
}


LConf.Extensions.Registry.registerPropertyView({
    objectclass: ".*hostgroup$",
    handler: function(store) {
        var p = new Ext.Panel({
            autoScroll: true,
            isMain: true,
            autoDestroy: true,
            title: 'Hostgroups',
            iconCls: 'icinga-icon-hostgroup',
            defaults: {
                flex: 1,
                border: false
            },
            items: [
                getGroupView("Hostgroup",store),
                getGroupMembersView("Hostgroup",store,"host")
            ]
        });
        return p;
    }
});

LConf.Extensions.Registry.registerPropertyView({
    objectclass: ".*servicegroup$",
    handler: function(store) {
        var p = new Ext.Panel({
            autoScroll: true,
            isMain: true,
            autoDestroy: true,
            title: 'Servicegroups',
            iconCls: 'icinga-icon-servicegroup',
            defaults: {
                flex: 1,
                border: false

            },
            items: [
                getGroupView("Servicegroup",store),
                getGroupMembersView("Servicegroup",store,"service")
            ]
        });
        return p;
    }
});

LConf.Extensions.Registry.registerPropertyView({
    objectclass: ".*contactgroup$",
    handler: function(store) {
        var p = new Ext.Panel({
            autoDestroy: true,
            autoScroll: true,
            isMain: true,
            title: 'Contactgroup',
            iconCls: 'icinga-icon-group',
            defaults: {
                flex: 1,
                border: false

            },
            items: [
                getGroupView("Contactgroup",store),
                getGroupMembersView("Contactgroup",store,"contact")
            ]
        });
        return p;
    }
});

})();