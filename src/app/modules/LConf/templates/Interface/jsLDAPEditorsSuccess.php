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
	
	lconf.editors.ComboBoxFactory = new function() {
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
				url: String.format(baseRoute),
				baseParams: {field:src}
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
	
	lconf.editors.SetFactory = new function() {
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
				url: String.format(baseRoute),
				baseParams: {field:src}
				// Metadata is provided by the server  
			})
		
			return Ext.extend(Ext.form.ComboBox,{
			    triggerAction: 'all',
			    lazyRender:true,
				displayField: 'entry',
				valueField: 'entry',
				mode:'remote',
				store: propertyStore,
				listeners:  {
					change: function(_form,newVal,old) {
						if(old)
							_form.setValue(old+","+newVal);
					}
				}
			});
		}
	}
	

	// shorthands
	var _lconf = lconf.editors; 
	var _f = _lconf.comboBoxFactory;
	var _register = _lconf.editorFieldMgr.registerEditorField;
	_register("default",Ext.form.TextField);
	
	// register editor factories
	<?php foreach(AgaviConfig::get("modules.lconf.propertyPresets") as $type=>$preset) :?>
		_register('<?php echo $type?>',lconf.editors.<?php echo $preset["factory"];?>Factory.create('<?php echo @$preset["parameter"]?>'));	
	<?php endforeach;?>

})() // EOF loadable code snippet