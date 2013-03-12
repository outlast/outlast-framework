//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – quick search window class
//////////////////////////////////////////////////////////////////////////////
// class: zajRelation
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 3.0
//////////////////////////////////////////////////////////////////////////////
// files needed:
//  - zaj.js
//	- zajajax.js
//	- zajsearch.css
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// this should work Firefox 1.5+, IE 5.5+, Opera 7+. send bugs to above email.
// check for new versions, go to hontalan.com.
//////////////////////////////////////////////////////////////////////////////
// version history.
// - 3.0 - initial release, mootools support
//////////////////////////////////////////////////////////////////////////////
/*  usage: 
	var relation = new zajRelation('inputid',{ options });
	
	TODO: add support for typing in exact one
	
	events:
		afteradd - fired after something has been added
		afterremove - fired after something has been removed
		afterreorder - fired after reordering (not yet supported!)
*/
//////////////////////////////////////////////////////////////////////////////
// zajSearch class

var zajRelation = new Class({	
	Extends: zajSearch,
	options: {
		relation_kind: 'manytomany',					// manytomany, onetomany, manytoone, or onetoone
		class_name: '',									// name of the model class
		field_name: '',									// name of the model class field
		allow_new_entries: true,						// true if user can click to add new entries
		allow_edit_entries: false,						// N/A true if user can click to edit existing entries
		new_entries_url: zajlib.baseurl+'admin/',		// N/A url where new entries can be added (if more than just name are required)
		edit_entries_url: zajlib.baseurl+'admin/'		// N/A url where existing entries can be edited

	},
	all_entries: new Hash(),
	new_entries: new Hash(),
	deleted_entries: new Hash(),
	orderof_entries: new Hash(),
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, options){
			// set default options
				this.setOptions(options);
			// call parent
				this.parent(divid, options);
			// relationsbox needed for all types
					// create my relationsbox
						this.relationsbox = new Element('div',{'id': this.options.class_name+'_'+this.options.field_name+'_container', 'class': 'zajlib_relations_container', 'html': ''});
					// inject the div, but make sure it is outside of <label> tag if it is in such
						if(this.searchbox.getParent().tagName == 'LABEL') this.relationsbox.inject(this.searchbox.getParent(),'after');
						else this.relationsbox.inject(this.searchbox,'after');
			// add events
				var self = this;
				this.request.addEvent('complete', function(){ self.parseResultsBox(); });
		},

	//////////////////////////////////////////////////////////////////////////////
	// add a relation
		add: function(name, id, isnew){
			// isnew default (isnew only refers to whether this is a new or existing connection, not to whether its a new object!)
				if(!$defined(isnew) || isnew == '') isnew = false;
			// check for errors - is it already added?
				if(this.all_entries.has(id)) return this.close();
			// now remove from the deleted and add to the added hash (if new)
				this.deleted_entries.erase(id);
				if(isnew) this.new_entries[id] = name;
				this.all_entries[id] = name;
			// create relation
						var self = this;			
					// add to relationsbox
						var newdiv = new Element('div',{ id: id, 'class': 'zajlib_relations_onerelation', html: name+" - <a id='"+id+"-deletelink'>x</a>" });
					// append to results
						this.relationsbox.appendChild(newdiv);
					// add click event to deletelink
						$(id+"-deletelink").addEvent('click', function(ev){ self.remove(id); });
			// manytoone needs to hide search box!
				if(this.options.relation_kind == 'manytoone') this.searchbox.hide();
			// now close and reset
				this.close();
			// now fire after add
				this.fireEvent("afteradd");
		},
	//////////////////////////////////////////////////////////////////////////////
	// delete a relation
		remove: function(id){
			// remove if exists
				if($chk($(id))) $(id).destroy();
			// now remove from new hash and add to deleted
				this.new_entries.erase(id);
				this.all_entries.erase(id);
				this.deleted_entries[id] = true;
			// now close and reset
				this.close();
			// manytoone needs to hide search box!
				if(this.options.relation_kind == 'manytoone'){
					this.searchbox.show();
					this.searchbox.focus();
				}
			// now fire after remove
				this.fireEvent("afterremove");
		},
				
	//////////////////////////////////////////////////////////////////////////////
	// add events to result box items
		parseResultsBox: function(){
			// prepare results
				var self = this;
 				
				this.resultsbox.getElements('div.zajsearch_oneresult').each(function(item, index){
					// add a click event to each one
					item.addEvent('click', function(){
							self.add(item.get('html'),item.id,true);
						});
				});	
			// add cancel event
				$('zajsearch-cancel').addEvent('click',function(){ self.close(); });
			// add 'add new object' event
				if(this.options.allow_new_entries) $('zajsearch-new').addEvent('click',function(){ self.add($('zajsearch-new-value').value,$('zajsearch-new-id').value,true); });
				else $('zajsearch-new').destroy();
			return true;
		},
	
		close: function(delete_value){
			if(!$defined(delete_value)) delete_value = true;
			// reset all of my stuff
			if(delete_value){
				this.searchbox.value = '';
				this.searchbox.store('id',false);
				if($chk(this.relationsaddbutton)) this.relationsaddbutton.removeEvents('click');
			}				
			this.parent(delete_value);
		}
});
