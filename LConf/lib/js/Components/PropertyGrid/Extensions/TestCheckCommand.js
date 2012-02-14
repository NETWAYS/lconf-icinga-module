/**
 * Grid extension that adds a column to test checks 
 * 
 */

Ext.ns("LConf.PropertyGrid.Extensions").TestCheckCommand = {
    xtype: 'action',
    appliesOn: {
        object: {
            "objectclass": ".*?service$"
        },
        properties: ".*servicecheckcommand$"
    },
    iconCls: 'icinga-icon-cog',
    qtip: _('Test this definition'),
    grid: null,
    
    handler: function(grid) {
        var checkValue = this.record.get("value");
        if(typeof checkValue !== "string")
            return;
        LConf.PropertyGrid.Extensions.TestCheckCommand.grid = grid;

        Ext.Ajax.request({
            url: grid.urls.ldapmetaprovider,
            params: {
                field: Ext.encode({"LDAP":["objectclass=lconfCommand","cn="+checkValue],"Attr":"*"}),
                connectionId: grid.connId
            },

            success: function(result) {
                var resultSet = Ext.decode(result.responseText);
                if(resultSet.total < 1) {
                    Ext.Msg.alert(
                        _("Couldn't find checkcommand!"),
                        _("Couldn't find a checkcommandentry for checkValue")
                    );
                } else {
                    LConf.PropertyGrid.Extensions.TestCheckCommand.showCheckCommandWindow(resultSet.result[0].entry,this.record);
                }
            },
            scope: this
        });
    },

    resolveToField: function(fieldname, record, prefix) {
        var me = LConf.PropertyGrid.Extensions.TestCheckCommand;
        switch(fieldname) {
            case '$SERVICENAME$':
                var field = null;
                record.store.each(function(entry) {
                    if(entry.get("property") == "cn") {
                        field = {
                            xtype: 'textfield',
                            fieldLabel: fieldname,
                            value: entry.get("value")
                        };
                        return false;
                    }
                    return true;
                },this);
                return field;
                break;
           case '$HOSTNAME$':
                var combobox = new (LConf.Editors.ComboBoxFactory.create(
                    Ext.encode({"LDAP": ["objectclass="+prefix+"host"],"Attr": "cn"}),me.grid.urls
                ))();
                combobox.fieldLabel = fieldname;
                combobox.getStore().setBaseParam("connectionId", me.grid.connId)
                return combobox;
           case '$HOSTADDRESS$':
                var combobox = new (LConf.Editors.ComboBoxFactory.create(
                    Ext.encode({"LDAP": ["objectclass="+prefix+"host"],"Attr": prefix+"address"}),me.grid.urls
                ))();
                combobox.fieldLabel = fieldname;
                combobox.getStore().setBaseParam("connectionId", me.grid.connId)
                return combobox;
            default:
               return {
                   xtype: 'textfield',
                   fieldLabel: fieldname
               }
        }
    },

    showCheckCommandWindow: function(ldapEntry,record) {
        var commandLine = null;
        var prefix = "";
        var me = LConf.PropertyGrid.Extensions.TestCheckCommand;
        for(var i in ldapEntry) {
            if(/(.*?)commandline$/i.test(i)) {
                commandLine = ldapEntry[i][0];

                prefix = i.match(/(.*?)commandline$/i)[1];
                
            }
        };
        if(commandLine === null)
            return Ext.Msg.alert("Error",_("Couldn't find commandline"));
        
        var insertVars = commandLine.match(/(\$.*?\$)/gi);
        var items = [{
            xtype: 'container',
            html: '<b>Checkcommand: </b><br/> '+commandLine

        }];

        for(var i =0;i<insertVars.length;i++) {
            var currentValue = insertVars[i];
            items.push(me.resolveToField(currentValue,record,prefix));
        }

        
        var window = new Ext.Window({   
            closable: true,
            closeaction: 'destroy',
            width: 400,
            
            constrain:true,
            title: 'Test check',
            items: [
                new Ext.form.FormPanel({
                    padding:"2em",
                    autoScroll:true,
                    defaults: {
                        anchor: '80%'
                    },
                    items: items,
                })
            ],
            buttons: [{
                text: 'Test Check result',
                iconCls: 'icinga-icon-cog'
            }, {
                text: 'Cancel',
                iconCls: 'icinga-icon-cancel'
            }]
        });
        var pos = Ext.EventObject.getXY();
        window.setPosition(pos[0],pos[1]);
        window.show();
    }

};
