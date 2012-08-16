(function() {
    
    LConf.Extensions.Registry.registerPropertyView({
        objectclass: "alias",
        handler: function(store) {
            
            var p = new Ext.Panel({
                autoScroll: true,
                isMain: true,

                title: 'Alias',
                iconCls: 'icinga-icon-attach',
                defaults: {
                    flex: 1,
                    border: false
                },
                
                items: [{
                    cls: 'aliasinfobox',
                    xtype: 'panel',
                    buttonAlign: 'center',
                    html: "<div style='margin:auto' class='icon-32 icinga-icon-exclamation-white'>"+
                        "</div>This is an alias entry <br/>"+
                        "<span style='font-family:monaco,monospace'></span>",
                    buttons: [{
                        iconCls: 'icinga-icon-arrow-undo',
                        text: 'Go to aliased node',
                        handler: function() {
                            var rec = store.findProperty("aliasedobjectname")[0];
                            var alias = {
                                attributes: {
                                    aliasedobjectname: [rec.get("value")]
                                }
                            }
                            store.eventDispatcher.fireCustomEvent("searchDN",rec.get("value"));
                        }
                    }, {
                        iconCls: 'icinga-icon-attach',
                        text: 'Show aliased node in tree',
                        handler: function() {
                            store.eventDispatcher.fireCustomEvent("jumpToRealNode",alias);
                        }
                    }]
                }]
            });
            store.on("load",function() {
                var rec = store.findProperty("aliasedobjectname")[0].get("value")
                p.items.get(0).update("<div style='margin:auto' class='icon-32 icinga-icon-exclamation-white'>"+
                        "</div>This is an alias entry to <p>"+
                        "<span style='font-family:monaco,monospace'>"+rec+"</span></p>");
            },this,{single:true});
            return p;
        }
    });
    
})();