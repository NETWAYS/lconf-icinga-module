/**
 * The simple editor for service information. Contains most parameters that are appliable for hosts
 *
 * @TODO: Almost excatly the same as the Hostview, refactor
 **/
/*jshint browser:true, curly:false */
/*global Ext:true, LConf: true */
(function() {

"use strict";

var prefix = LConf.Configuration.prefix;

/**
 * Eventhandler function for syncing properties defined in the ldap editor with this editor
 *
 * @param {Object} a (lconf-)property->value map
 **/
var updateFieldValues = function(map) {
    if(!this.lconfProperty) {
        return;
    }
    if(this.hideOn) {
        if(new RegExp(".*"+this.hideOn+"$","i").test(map.objectclass)) {
            this.hide();
            return;
        }
    }
    var lconfProperty = this.lconfProperty.toLowerCase();
    for(var i in map) {
        if(lconfProperty === i.toLowerCase())
            this.setValue(map[i]);
    }
};

/**
 * Special helper for syncing TriStateButtons
 *
 * @param {Object} a (lconf-)property->value map
 **/
var updateTristateButtonValues = function(map) {
    if(map[this.lconfProperty.toLowerCase()] === "0" || map[this.lconfProperty] === "0")
        this.toggle("false",true);
    else if(map[this.lconfProperty.toLowerCase()] === "1" || map[this.lconfProperty] === "1")
        this.toggle("true",true);
    else
        this.toggle("disabled");
};



var getServiceInfoPanel = function(store) {

    var onFieldChange = function(cmp,value) {

        additiveInheritanceBox.updateStates();

        if(value === "" && cmp.allowBlank !== false) {
            store.deleteProperties(store.findProperty(cmp.lconfProperty));
        } else {
            store.setProperty(cmp.lconfProperty,value);
        }

    };

    // specific comboboxes for groups
    var contactgroupBox = LConf.Editors.EditorFieldManager.getEditorFieldForProperty(
        prefix+"ServiceContactGroups",{
            fieldLabel: 'Contactgroups',
            lconfProperty: prefix+"ServiceContactgroups",
            xtype: 'combo',
            anchor: '90%',

            listeners: {
                change: onFieldChange
            }
        },[prefix+"service"]
    );
    var contactBox = LConf.Editors.EditorFieldManager.getEditorFieldForProperty(
        prefix+"ServiceContacts",{
            fieldLabel: 'Contacts',
            lconfProperty: prefix+"ServiceContacts",
            xtype: 'combo',
            anchor: '90%',
            listeners: {
                change: onFieldChange
            }
        },[prefix+"service"]
    );
    var servicegroupBox = LConf.Editors.EditorFieldManager.getEditorFieldForProperty(
        prefix+"Servicegroups",{
            fieldLabel: 'Servicegroups',
            lconfProperty: prefix+"Servicegroups",
            xtype: 'combo',
            anchor: '90%' ,
            listeners: {
                change: onFieldChange
            }
        },[prefix+"service"]
    );

    var additiveInheritanceBox = new Ext.ButtonGroup({
        xtype: 'buttongroup',
        fieldLabel: 'Additive Inheritance',
        listeners: {
            toggle: function(button) {

                if (! button.box.getValue() || 0 === button.box.getValue().length) {
                    button.toggle(false, false);
                    return;
                }

                var values = button.box.getValue();

                if (values.charAt(0) != "+" && values.length > 0 && button.pressed == true) {
                    values = "+" + values;
                }

                if (values.charAt(0) == "+" && values.length > 0 && button.pressed == false) {
                    values = values.substring(1);
                }

                button.box.setValue(values);
                store.setProperty(button.box.lconfProperty, values);
            },
            render: function() {
                this.items.each(function(button) {
                    if ( /\+/i.test(button.box.getValue()) ) {
                        button.toggle(true, true);
                    } else {
                        button.toggle(false, false);
                    }
                });
            }
        },
        autoHeight: true,
        anchor: '90%',
        defaults: {
            bubbleEvents: ["toggle","change"],
            xtype: 'button'
        },
        layout: 'column',
        disabled: false,
        border: false,
        items: [{
            text: 'Servicegroups',
            style: 'margin-right:2px',
            enableToggle: true,
            box: servicegroupBox
        },{
            text: 'Contactgroups',
            style: 'margin-right:2px',
            enableToggle: true,
            box: contactgroupBox
        },{
            text: 'Contacts',
            style: 'margin-right:2px',
            enableToggle: true,
            box: contactBox
        }],
        updateStates: function() {
            this.items.each(function(button) {
                if ( /\+/i.test(button.box.getValue()) ) {
                    button.toggle(true, true);
                } else {
                    button.toggle(false, false);
                }
            });
        }
    });

    var fn = function(me) {
        if(!contactgroupBox.store ||!contactBox.store || !servicegroupBox.store) {
            me.defer(200,null,[me])
            return;
        }
        contactgroupBox.store.setBaseParam("connectionId",store.getConnection());
        contactgroupBox.updateFieldValues = updateFieldValues;

        contactBox.store.setBaseParam("connectionId",store.getConnection());
        contactBox.updateFieldValues = updateFieldValues;
        servicegroupBox.store.setBaseParam("connectionId",store.getConnection());
        servicegroupBox.updateFieldValues = updateFieldValues;

    }

    var onTristateToggle = function(cmp,state) {
        if(state === "true") {
            store.setProperty(this.lconfProperty,"1");
        } else {
            store.deleteProperties(store.findProperty(this.lconfProperty));
        }
    };

    fn.defer(200,null,[fn]);
    return {
        xtype:'form',
        layout: 'form',
        padding: "1em 1em 1em 1em",
        autoHeight: true,
        items:{
            xtype: 'fieldset',
            iconCls: 'icinga-icon-service',
            title: 'Service info',
            labelWidth: 200,
            anchor: '90% ',
            defaults: {
                updateFieldValues: updateFieldValues,
                listeners: {
                    change: onFieldChange,
                    invalid: function(cmp) {
                        if(!cmp.activeError)
                            store.markInvalid(true);
                    },
                    valid: function() {
                        store.markInvalid(false);
                    }
                }
            },
            items: [{
                fieldLabel: 'Service name',
                xtype: 'textfield',
                hideOn: 'structuralObject',
                lconfProperty: "cn",
                allowBlank: false,
                anchor: '90%'
            },
            servicegroupBox,
            contactgroupBox,
            contactBox,
            additiveInheritanceBox,
            {
                xtype: 'tristatebutton',
                enableToggle: true,
                pressed: "disabled",
                fieldLabel: "Disable",
                text: 'Default',
                stateText: {
                    "true": 'Yes',
                    "false": 'No',
                    "disabled": 'Default'
                },
                lconfProperty: prefix+'ServiceDisable',
                updateFieldValues: updateTristateButtonValues,
                listeners: {
                    toggle: onTristateToggle
                }
	    }]
        }
    };

};

/**
 * Returns the general host information FormPanel
 *
 * @param {Ext.data.Store}  The (possibly yet unpopulated) store containing the hosts information
 *
 * @return {Ext.form.FormPanel}
 **/
var getCheckPreferences = function(store) {

    // these helperfunctions are defined inline as we need the store
    // @TODO: not nice and a lot of copy&paste
    var onFieldChange = function(cmp,value) {
        if(value === "") {
            store.deleteProperties(store.findProperty(cmp.lconfProperty));
        } else {
            store.setProperty(cmp.lconfProperty,value);
        }
    };

    var onTristateToggle = function(cmp,state) {
        if(state === "true") {
            store.setProperty(this.lconfProperty,"1");
        } else if(state === "false") {
            store.setProperty(this.lconfProperty,"0");
        } else {
            store.deleteProperties(store.findProperty(this.lconfProperty));
        }
    };
    // specific comboboxes for groups
    var checkCommandBox = LConf.Editors.EditorFieldManager.getEditorFieldForProperty(
        prefix+"ServiceCheckCommand",{
            fieldLabel: 'Command',
            lconfProperty: prefix+"ServiceCheckCommand",
            xtype: 'combo',
            emptyText: 'Default service check command',
            anchor: '90%',
            listeners: {
                change: onFieldChange
            }
        },[prefix+"service"]
    );
    (function() {
        if(!checkCommandBox.store)
         return checkCommandBox;
        checkCommandBox.store.setBaseParam("connectionId",store.getConnection());
        checkCommandBox.updateFieldValues = updateFieldValues;
    }).defer(200);
    return {
        xtype:'form',


        padding: "1em 1em 1em 1em",
        autoHeight: true,
        items: [{
            xtype: 'fieldset',
            title: 'Check settings',
            anchor: '90%',
            iconCls: 'icinga-icon-cog',
            defaults: {
                updateFieldValues: updateFieldValues,
                listeners: {
                    change: onFieldChange
                }

            },
            items: [checkCommandBox,{
                xtype:'compositefield',
                labelwidth: 200,
                anchor: '90%',
                defaults: {
                    updateFieldValues: updateFieldValues,
                    listeners: {
                        change: onFieldChange
                    }

                },
                updateFieldValues: function(ldapMap) {
                    this.items.each(function(field) {
                        if(field.updateFieldValues)
                           field.updateFieldValues(ldapMap);
                    });
                },
                items: [{
                    xtype: 'numberfield',
                    fieldLabel:'Check Interval',
                    lconfProperty: prefix+'ServiceCheckInterval',
                    width: 35
                },{
                    xtype: 'label',
                    text: 'Retry Interval',
                    style: {
                        'margin-top' : "4px"
                    }
                },{
                    xtype: 'numberfield',
                    lconfProperty: prefix+'ServiceCheckRetryInterval',
                    width: 35
                },{
                    xtype: 'label',
                    text: 'Max Check Attempts',
                    style: {
                        'margin-top' : "4px"
                    }
                },{
                    xtype: 'numberfield',
                    lconfProperty: prefix+'ServiceCheckMaxCheckAttempts',
                    width: 35
                }]
            },{
                xtype: 'hidden',
                updateFieldValues: function() {
                    // required for button update
                    var btnGroups = this.ownerCt.findByType('buttongroup');
                    for(var i=0;i<btnGroups.length;i++) {
                        btnGroups[i].updateFieldValues.apply(btnGroups[i],arguments);
                    }

                    var tristateBtns = this.ownerCt.findByType('tristatebutton');
                    for(i=0;i<tristateBtns.length;i++) {
                        tristateBtns[i].updateFieldValues.apply(tristateBtns[i],arguments);
                    }
                }
            },{
                xtype: 'numberfield',
                fieldLabel:'Freshness threshold',
                lconfProperty: prefix+'ServiceFreshnessThreshold',
                updateFieldValues: updateFieldValues,
                width: 35
            },{
                xtype: 'tristatebutton',
                enableToggle: true,
                pressed: "disabled",
                fieldLabel: "Use freshness checks",
                text: 'Default',
                stateText: {
                    "true": 'Yes',
                    "false": 'No',
                    "disabled": 'Default'
                },
                lconfProperty: prefix+'ServiceCheckFreshness',
                updateFieldValues: updateTristateButtonValues,
                listeners: {
                    toggle: onTristateToggle
                }
            },{
                xtype:'buttongroup',
                anchor: '90%',
                labelwidth: 100,
                align: 'center',
                layout: 'column',

                border: false,
                defaults: {
                    updateFieldValues: updateFieldValues,
                    border: false,
                    listeners: {
                        change: onFieldChange
                    }

                },
                items: [{
                    xtype: 'tristatebutton',
                    text: 'Active: Default',
                    width:100,
                    stateText: {
                        "true": 'Active: Yes',
                        "false": 'Active: No',
                        "disabled": 'Active: Default'
                    },
                    updateFieldValues: updateTristateButtonValues,
                    listeners: {
                        toggle: onTristateToggle
                    },
                    pressed: "disabled",
                    lconfProperty: prefix+'ServiceActiveChecksEnabled',
                    iconCls: 'icinga-icon-arrow-out'
                },{
                    xtype: 'tristatebutton',
                    text: 'Passive: Default',
                    width:100,
                    stateText: {
                        "true": 'Passive: Yes',
                        "false": 'Passive: No',
                        "disabled": 'Passive: Default'
                    },
                    updateFieldValues: updateTristateButtonValues,
                    listeners: {
                        toggle: onTristateToggle
                    },
                    pressed: "disabled",
                    iconCls: 'icinga-icon-info-passive',
                    lconfProperty: prefix+'ServicePassiveChecksEnabled'
                },{
                    xtype: 'tristatebutton',
                    text: 'Perfdata: Default',
                    width:100,
                    updateFieldValues: updateTristateButtonValues,
                    listeners: {
                        toggle: onTristateToggle
                    },
                    stateText: {
                        "true": 'Perfdata: Yes',
                        "false": 'Perfdata: No',
                        "disabled": 'Perfdata: Default'
                    },
                    pressed: "disabled",
                    iconCls: 'icinga-icon-application',
                    lconfProperty: prefix+'ServiceProcessPerfData'
                }]
            }]
        }]
    };
};

var getNotificationPreferences = function(store) {

    var onFieldChange = function(cmp,value) {
        if(value === "") {
            store.deleteProperties(store.findProperty(cmp.lconfProperty));
        } else {
            store.setProperty(cmp.lconfProperty,value);
        }
    };
    var onTristateToggle = function(cmp,state) {
        if(state === "true") {
            store.setProperty(this.lconfProperty,"1");
        } else if(state === "false") {
            store.setProperty(this.lconfProperty,"0");
        } else {
            store.deleteProperties(store.findProperty(this.lconfProperty));
        }
    };
    // specific comboboxes for groups
    var tpCommandBox = LConf.Editors.EditorFieldManager.getEditorFieldForProperty(
        prefix+"ServiceNotificationPeriod",{
            fieldLabel: 'Period',
            lconfProperty: prefix+"ServiceNotificationPeriod",
            xtype: 'combo',
            emptyText: 'Default service check command',
            anchor: '90%',
            listeners: {
                change: onFieldChange
            }
        },[prefix+"service"]
    );
    (function() {
        if(!tpCommandBox.store)
            return;
        tpCommandBox.store.setBaseParam("connectionId",store.getConnection());
        tpCommandBox.updateFieldValues = updateFieldValues;
    }).defer(200);

    var btnGroup = new Ext.ButtonGroup({
        xtype: 'buttongroup',
        autoHeight: true,
        lconfProperty: prefix+'ServiceNotificationOptions',
        defaults: {
            bubbleEvents: ["toggle","change"],
            xtype: 'button'
        },
        layout: 'column',

        listeners: {
            toggle: function() {
                var opts = "";
                this.items.each(function(btn) {
                    if(btn.pressed)
                        opts += (opts ? "," : "")+btn.notificationType;
                });
                store.setProperty(this.lconfProperty,opts);
            }
        },
        disabled: true,
        border: false,
        items: [{
            text: 'Warning',
            notificationType: 'w',
            enableToggle: true
        },{
            text: 'Unknown',
            notificationType: 'u',
            enableToggle: true
        },{
            text: 'Critical',
            notificationType: 'c',
            enableToggle: true
        },{
            text: 'Recovery',
            notificationType: 'r',
            enableToggle: true
        },{
            text: 'Flapping',
            notificationType: 'f',
            enableToggle: true,
            iconCls: 'icinga-icon-flapping'
        },{
            text: 'Downtime',
            notificationType: 's',
            enableToggle: true,
            iconCls: 'icinga-icon-downtime'
        },{
            text: 'None',
            notificationType: 'n',
            enableToggle: true,
            iconCls: 'icinga-icon-cancel'
        }]
    });
    btnGroup.updateFieldValues = function(map) {
        var p = this.lconfProperty.toLowerCase();
        if(typeof map[p] === "undefined")
            return;
        this.items.each(function(btn) {
            btn.toggle(false,true);
            var split = map[p].split(",");
            for(var i=0;i<split.length;i++) {
                if(split[i].toLowerCase() === btn.notificationType)
                    btn.toggle(true,true);
            }
        },this);
    };


    var defaultBtn =  new Ext.Button({
        xtype: 'button',
        text: 'Use default rules',
        fieldLabel: 'Notify on',
        pressed: true,
        enableToggle: true,
        lconfProperty: prefix+'ServiceNotificationOptions',
        toggleHandler: function(btn,state) {

            for(var i=1;i<this.ownerCt.items.length;i++) {
                this.ownerCt.items.items[1].setDisabled(state);
            }
            if(state === true) {
                btnGroup.items.each(function(btn) {
                    btn.toggle(false,true);
                });
                store.deleteProperties(store.findProperty(btn.lconfProperty));
            }
        },
        updateFieldValues: function(map) {
            if(typeof map[this.lconfProperty.toLowerCase()] === "undefined") {
                this.toggle(true,true);

                btnGroup.setDisabled(true);

            } else {
                this.toggle(false,true);
                btnGroup.setDisabled(false);

            }
        }
    });

    return {
        xtype:'form',
        autoHeight: true,
        flex: 1,
        layout: 'form',
        padding: "1em 1em 1em 1em",

        items: {
            xtype: 'fieldset',
            flex:1,
            title: 'Notifications',
            iconCls: 'icinga-icon-notify',
            border: true,
            anchor: '90%',
            defaults: {

                listeners: {
                    change: onFieldChange
                }

            },
            items: [
                defaultBtn,
                btnGroup,
            {
                xtype: 'hidden',
                updateFieldValues: function() {
                    // required for button update
                    var btnGroups = this.ownerCt.findByType('buttongroup');
                    for(var i=0;i<btnGroups.length;i++) {
                        btnGroups[i].updateFieldValues.apply(btnGroups[i],arguments);
                    }

                    var tristateBtns = this.ownerCt.findByType('tristatebutton');
                    for(i=0;i<tristateBtns.length;i++) {
                        tristateBtns[i].updateFieldValues.apply(tristateBtns[i],arguments);
                    }
                }
            },{
                xtype: 'spacer',
                height: 20
            },tpCommandBox,{
                xtype: 'numberfield',
                fieldLabel: 'Interval',
                updateFieldValues: function() {
                    updateFieldValues.apply(this,arguments);
                    btnGroup.updateFieldValues.apply(btnGroup,arguments);
                    defaultBtn.updateFieldValues.apply(defaultBtn,arguments);

                },
                lconfProperty: prefix+'ServiceNotificationInterval',

                width:35
            },{
                xtype: 'tristatebutton',
                fieldLabel: 'Enable notifications',
                lconfProperty: prefix+'ServiceNotificationsEnabled',
                text: 'Use default',
                stateText: {
                    "true": 'Yes',
                    "false": 'No',
                    "disabled": 'Use default'
                },
                updateFieldValues: updateTristateButtonValues,
                listeners: {
                    toggle: onTristateToggle
                },
                pressed: 'disabled'
            }]
        }
    };
};


var getFlappingPreferences = function(store) {
     var onFieldChange = function(cmp,value) {
        if(value === "") {
            store.deleteProperties(store.findProperty(cmp.lconfProperty));
        } else {
            store.setProperty(cmp.lconfProperty,value);
        }
    };
    var onTristateToggle = function(cmp,state) {
        if(state === "true") {
            store.setProperty(this.lconfProperty,"1");
        } else if(state === "false") {
            store.setProperty(this.lconfProperty,"0");
        } else {
            store.deleteProperties(store.findProperty(this.lconfProperty));
        }
    };
    var btnGroup = new Ext.ButtonGroup({
        xtype: 'buttongroup',
        columns: 3,
        layout: 'column',
        defaults: {
            xtype: 'button',
            width:75,
            bubbleEvents: ["toggle","change"]
        },
        lconfProperty: prefix+'ServiceFlapDetectionOptions',
        listeners: {
            toggle: function() {
                var opts = "";
                this.items.each(function(btn) {
                    if(btn.pressed)
                        opts += (opts ? "," : "")+btn.flapSetting;
                });
                store.setProperty(this.lconfProperty.toLowerCase(),opts);
            }
        },
        border: false,
        disabled: true,
        items: [{
            text: 'Up',
            flapSetting: 'o',
            enableToggle: true
        },{
            text: 'Warning',
            flapSetting: 'w',
            enableToggle: true
        },{
            text: 'Critical',
            flapSetting: 'c',
            enableToggle: true
        },{
            text: 'Unknown',
            flapSetting: 'u',
            enableToggle: true
        }]
    });

    btnGroup.updateFieldValues = function(map) {
        var p = this.lconfProperty.toLowerCase();
        if(typeof map[p] === "undefined")
            return;
        this.items.each(function(btn) {
            btn.toggle(false,true);
            var split = map[p].split(",");
            for(var i=0;i<split.length;i++) {
                if(split[i].toLowerCase() === btn.flapSetting)
                    btn.toggle(true,true);
            }
        },this);
    };

    var defaultBtn = new Ext.Button({
        xtype: 'button',
        text: 'Use default rules',
        fieldLabel: 'Flap states:',
        pressed: true,
        lconfProperty: prefix+'ServiceFlapDetectionOptions',
        enableToggle: true,
        toggleHandler: function(btn,state) {
            this.ownerCt.items.items[1].setDisabled(state);
            if(state === false) {
                btnGroup.items.each(function(btn) {
                    btn.toggle(false,true);
                });
                store.deleteProperties(store.findProperty(btn.lconfProperty));
            }
        },
        updateFieldValues: function(map) {
            if(typeof map[this.lconfProperty.toLowerCase()] === "undefined") {
                this.toggle(true,true);
                btnGroup.setDisabled(true);

            } else {
                this.toggle(false,true);
                btnGroup.setDisabled(false);

            }
        }
    });

    return {
        xtype:'form',
        height: 350,
        flex: 1,
        padding: "1em 1em 1em 1em",
        items: [{
            xtype: 'fieldset',
            flex:1,
            iconCls: 'icinga-icon-flapping',
            title: 'Flap detection',
            border: true,
            anchor: '90%',
            defaults: {
                listeners: {
                    change: onFieldChange
                },
                border: false

            },
            items: [{
                // buttons are ignored by forms...
                xtype: 'hidden',
                updateFieldValues: function() {
                    var args = arguments;
                    this.ownerCt.cascade(function() {
                        if(this.xtype !== 'hidden' && this.updateFieldValues)
                            this.updateFieldValues.apply(this,args);

                    });
                }
            },{
                xtype: 'tristatebutton',
                enableToggle: true,
                fieldLabel: 'Enable flap detection',
                text: 'Use default',
                lconfProperty: prefix+'ServiceFlapDetectionEnabled',
                stateText: {
                    "true": 'Yes',
                    "false": 'No',
                    "disabled": 'Use default'
                },
                updateFieldValues: updateTristateButtonValues,
                listeners: {
                    toggle: onTristateToggle
                },
                pressed: 'disabled'
            },{
                xtype: 'hidden',
                updateFieldValues: function() {
                    // required for button update
                    var btnGroups = this.ownerCt.findByType('buttongroup');
                    for(var i=0;i<btnGroups.length;i++) {
                        btnGroups[i].updateFieldValues.apply(btnGroups[i],arguments);
                    }

                    var tristateBtns = this.ownerCt.findByType('tristatebutton');
                    for(i=0;i<tristateBtns.length;i++) {
                        tristateBtns[i].updateFieldValues.apply(tristateBtns[i],arguments);
                    }
                }
            },{
                xtype: 'fieldset',
                anchor: '90%',
                border: false,
                defaults: {
                    listeners: {
                        change: onFieldChange
                    }

                },
                items: [defaultBtn,btnGroup]
            }/* Not implemented in lconf backend
            ,{
                xtype: 'compositefield',
                anchor: '90%',
                items: [{
                    xtype:'numberfield',
                    id: 'txtnumberfield_min',
                    value:0,
                    width:35,
                    listeners: {
                        change: function(cmp,val) {
                            Ext.getCmp('txtnumberfield_sld').thumbs[0].value = val;
                            Ext.getCmp('txtnumberfield_sld').syncThumb();
                        }
                    }
                },{
                    fieldLabel: 'Flap thresholds',
                    xtype: 'slider',
                    id: 'txtnumberfield_sld',
                    minValue: 0,
                    maxValue: 100,
                    width: 150,
                    values: [0,100],
                    constrainThumbs: true,
                    listeners: {

                        drag: function(cmp,x) {
                            console.log(cmp);
                            Ext.getCmp('txtnumberfield_min').setValue(cmp.thumbs[0].value);
                            Ext.getCmp('txtnumberfield_max').setValue(cmp.thumbs[1].value);
                        }
                    }
                },{
                    xtype:'numberfield',
                    id: 'txtnumberfield_max',
                    value:0,
                    width:35,
                    listeners:{
                        change: function(cmp,val) {
                            Ext.getCmp('txtnumberfield_sld').thumbs[1].value = val;
                            Ext.getCmp('txtnumberfield_sld').syncThumb();
                        }
                    }
                }]
            }*/]
        }]
    };
};

var updateFormValues = function() {
    var ldapMap = {};
    this.store.each(function(r) {
        ldapMap[r.get('property').toLowerCase()] = r.get('value');
    });
    if(this.rendered) {
        this.items.each(function(item) {
            if(!item)
                return false;
            if(item.xtype !== 'panel') {
                item.getForm().callFieldMethod("updateFieldValues",[ldapMap]);
            } else {
                item.items.each(function(subitem) {
                    if(!subitem)
                        return false;
                    subitem.getForm().callFieldMethod("updateFieldValues",[ldapMap]);
                });
            }
        });
        if(this.items.items[1])
            this.items.items[1].getForm().callFieldMethod("updateFieldValues",[ldapMap]);
    } else {
        this.on("show",updateFormValues,this,{single:true});
    }
};

LConf.Extensions.Registry.registerPropertyView({

    objectclass: ".*(structuralobject|service)$",
    handler: function(store) {
        var p = new Ext.Panel({
            autoDestroy: true,
            autoScroll: true,
            priority: 1,
            title: 'Service settings',
            iconCls: 'icinga-icon-service',
            defaults: {
                flex: 1,
                border: false

            },
            items: [
                getServiceInfoPanel(store),
                getCheckPreferences(store),
                getNotificationPreferences(store),
                getFlappingPreferences(store)
            ]
        });

        p.store = store;
        var storeFn = updateFormValues.createDelegate(p);
        store.on("update",storeFn);
        store.on("load",storeFn);
        p.addListener("destroy",function() {
            store.removeListener("update",storeFn);
            store.removeListener("load",storeFn);
        });
        return p;

    }
});

})();

//window.TESTFUNC();
