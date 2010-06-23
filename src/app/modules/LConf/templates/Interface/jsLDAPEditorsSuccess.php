(function() {
	Ext.ns("lconf.editors");
	lconf.editors = {}
	
	lconf.editors.editorFieldMgr = new function() {
		/**
		 * Private static map of property=>editorfield relations
		 */
		var registeredEditorFields = {}
		
		this.registerEditorField = function(property,editor) {
			registeredEditorFields[property] = editor; 
		} 
		
		this.unregisterEditorField = function(property) {
			delete(registeredEditorFields[property]);
		}	
		
		this.getEditorFieldForProperty = function(property) {
			var field = registeredEditorFields[property];
			
			if(Ext.isDefined(field)) {
				return new field;
			}
		
			return registeredEditorFields["default"];
		}
	}
	
	lconf.editors.genericTextfield = Ext.form.TextField;
	
	lconf.editors.comboBoxFactory = new function() {
		var baseRoute = '<?php echo $ro->gen("lconf.data.ldapmetaprovider") ?>';
		this.setBaseRoute = function(route) {
			this.baseRoute = route;
		}
		this.getBaseRoute = function() {
			return this.baseRoute;
		}
		
		this.create = function(src) {
			var propertyStore = new Ext.data.JsonStore({
				autoDestroy:false,
				url: String.format(baseRoute+"{0}",src)
				// Metadata is provided by the server  
			})
		
			return Ext.extend(Ext.form.ComboBox,{
			    triggerAction: 'all',
			    lazyRender:true,
				displayField: 'entry',
				valueField: 'entry',
				mode:'remote',
				store: propertyStore
			});
		}
	}
	

	// shorthands
	var _lconf = lconf.editors; 
	var _f = _lconf.comboBoxFactory;
	var _register = _lconf.editorFieldMgr.registerEditorField;
	_register("property",_f.create("properties"));
	_register("objectclass",_f.create("objectclass"));
	_register("alias",_f.create("alias"));
	_register("hosts",_f.create("hosts"));
	_register("services",_f.create("services"));
	_register("timeperiods",_f.create("timeperiods"));
	_register("default", lconf.editors.genericTextfield);
})() // EOF loadable code snippet