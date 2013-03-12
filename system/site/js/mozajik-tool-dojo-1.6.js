/**
 * This file containes Mozajik tools.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.6
 **/

dojo.provide("Mozajik.tool");


/**
 * This class allows you to generate a drag and drop list.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 */
dojo.declare("Mozajik.DragList", null, {
	/**
	 * Properties
	 **/
	 	// options
		options: {
		},

	/**
	 * Events
	 **/
		onDrag: function(item){},		// Called when the dragging begins of a certain item
		onComplete: function(ids){},	// Called when dragging is finished
	
	/**
	 * Constructor method.
	 **/
	 	constructor: function(container, options){
	 		dojo.mixin(this.options, options);		 	
		 	this.container = dojo.byId(container);
		 	// Make container DND sortable!
				dojo.require("dojo.dnd.Source");
				var self = this;
		 		dojo.ready(function(){ var v = new dojo.dnd.Source(self.container); v.sync(); });
		}

});



/**
 * This class allows you to generate a file list.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 */
dojo.declare("Mozajik.FileList", null, {
	/**
	 * Properties
	 **/
	 	// options
		options: {
		},

	/**
	 * Events
	 **/
		onAdd: function(id){},
		onRemove: function(id){},
		onSort: function(){},
	
	/**
	 * Constructor method.
	 **/
	 	constructor: function(container, options){
	 		dojo.mixin(this.options, options);		 	
		 	this.container = dojo.byId(container);
		},

	/**
	 * Adds a file to the list
	 **/
	 	add_file: function(name, id, is_new){
	 		// HTML: <div class="icon mime zip">name.zip <a>x</a></div>
	 		var components = name.split('.');
	 		var extension = components[components.length-1];
			dojo.place("<div id='"+id+"' class='icon mime "+extension+"'>"+name+" <a id='remove-"+id+"' href='#remove'>x</a></div>", this.container, 'last');
			// Add event to a tag
				var self = this;
				dojo.connect(dojo.byId('remove-'+id), 'onclick', function(ev){ self.remove_file(ev.target.parentNode.id); });
			// Fire event
				this.onAdd(id);
	 	},
	 	
	/**
	 * Removes a file from the list
	 **/
	 	remove_file: function(id){
	 		// Destroy DOM element
		 		dojo.destroy(id);
			// Fire event
				this.onRemove(id);
	 	}
});


dojo.extend(Mozajik.Element, {
	search: function(url, divid){
		var timeout;
		var el = this.element;
		// Add onchange event to the search box
			dojo.connect(this.element, 'onkeyup', this.element, function(){
				// Clear it now
					clearTimeout(timeout);
				// Timeout for two seconds
					timeout = setTimeout(function(){
						// Now execute the search
							zaj.ajax.get(url+'?query='+el.value, $(divid));
					}, 300);
			});
	}
});
