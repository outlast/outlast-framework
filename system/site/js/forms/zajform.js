//////////////////////////////////////////////////////////////////////////////
// class: zajForm
// version: 3.0
//////////////////////////////////////////////////////////////////////////////
// description: used for client side display and validation of form fields
// files needed: mootools, zaj.js
//////////////////////////////////////////////////////////////////////////////
// version history
// - 3.0 - initial release
//////////////////////////////////////////////////////////////////////////////
// Usage: 
//	- zajform.type_of_field('name_of_field');
//////////////////////////////////////////////////////////////////////////////
// Notes: 
//		var my_helper_id = this.formid+'_'+field_name+'_';
//		var my_save_id = this.class_name+'['+this.object_id+']['+field_name+']';
//////////////////////////////////////////////////////////////////////////////

var zajForm = new Class({
	Implements: [Options, Events],
	
	options: {
	},
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(class_name, object_id, options){
			// set default options
				this.setOptions(options);
			// set default variables
				this.class_name = class_name;
				this.object_id = object_id;
				this.formid = class_name+'_'+object_id;
		},

	//////////////////////////////////////////////////////////////////////////////
	// relationship fields
		manytomany: function(field_name){
			// my helper id	
				var my_helper_id = this.formid+'_'+field_name+'_';
			// make search box
				var my_searcher = new zajRelation(my_helper_id+'add', { form_id: this.formid, class_name: this.class_name, object_id: this.object_id, field_name: field_name, query_string: zajlib.baseurl+'/system/search/relation?class='+this.class_name+'&id='+this.object_id+'&field='+field_name+'&query=' });
			
			// add the default values
				// if default defined
					if($(this.class_name+'['+this.object_id+']['+field_name+']').value){
					
					}
							
			return true;
		},


	//////////////////////////////////////////////////////////////////////////////
	// close 
		close: function(){
			return true;
		}

});

// now create the array for object
	var zajform = new Array();
	