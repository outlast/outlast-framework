/**
 * The mozajik-gui.js file contains all the custom Mozajik GUI elements' javascript code.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.3
 **/


/**
 * Class PopOver creates a popover interface similar to what is used on iOS interfaces.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 * @todo Content can be some text, or a div object
 * @todo What if relativeTo not defined? There is a bug!
 * @todo Lots of code is repeated for PopOver and PopUp. Combine into a PopElement class.
 * @todo Bug with close button, so it is disabled for PopUps
 */
var PopOver = new Class({
	Implements: [Options, Events],
	
	options: {
		width: 300,						// Width of the new element
		height: 0,						// Set to 0 for automatic height based on content
		top: 0,							// Offset from top
		left: 0,						// Offset from left
		title: '',						// This goes in the title bar
		relativeTo: document.body,		// Offset relative to this element
		//closeButton: true,				// Do we need an X?
		//closeText: '',					// The text next to the X
		closeOnBackgroundClick: true,	// Closes when any other area in the background is clicked
		position: 'auto'				// Can be 'auto', 'right', or 'left'
	},
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(content, options){
			// set default options
				this.setOptions(options);
			// create div
				this.popover = new Element('div', {'class':'popover', 'html':"<div class='popover side top'></div><div class='popover side bottom'></div><div class='popover side left'></div><div class='popover side right'></div><div class='popover side tl'></div><div class='popover side tr'></div><div class='popover side bl'></div><div class='popover side br'></div>"});				
			// create title and content, plus pointer
				this.potitle = new Element('div', {'class':'popover contenttitle', 'html': this.options.title});
				this.pocontent = new Element('div', {'class':'popover contentview', 'html': content });
				this.poback = new Element('div', { 'class':'popover back' });
				this.popointer = new Element('div', { 'class':'popover pointer' });
				//if(this.options.closeButton) this.poclose = new Element('a', { 'class':'popover popup icon tiny close', 'html': this.options.closeText });
			// now inject popover into body and title and content into main div
				this.popover.inject(document.body);
				this.poback.inject(document.body);
				this.potitle.inject(this.popover);
				this.pocontent.inject(this.popover);
				this.popointer.inject(this.popover);
				//if(this.options.closeButton) this.poclose.inject(this.popup);
			// set properties and reposition
				this.popover.setStyles({
					width: this.options.width,
					height: this.options.height,
					display: 'none'
				});
				this.reposition()			
			
			// now add events
				var self = this;
				// add close on back click, but delay a bit to prevent double-click
					if(this.options.closeOnBackgroundClick) (function(){self.poback.addEvent('click', function(){ self.close(); });}).delay(200);
				// add close on close button if close button is enabled
					if(this.options.closeButton) this.poclose.addEvent('click', function(){ self.close(); });
			// now show
				this.show();
			// resize
				(function(){self.resize();}).delay(250);
			return true;
		},
	
	//////////////////////////////////////////////////////////////////////////////
	// reposition and resize box
		reposition: function(){
			// decide based on position
				var position, offsetx, offsety;
				switch(this.options.position){
					case 'right':	position = 'topRight';
									break;
					case 'left':	position = 'topLeft';
									break;
					default:		var parent_position = this.options.relativeTo.getPosition();
									var window_size = window.getSize();
									if(window_size.x/2 > parent_position.x) position = 'topRight';
									else position = 'topLeft';
									break;
				}
			// calculate offsets, set pointer class
				if(position == 'topRight'){
					offsetx = 40+this.options.left;
					offsety = -25+this.options.top;
					this.popointer.removeClass('right');
				}
				else{
					offsetx = this.options.left-40-this.options.width;
					offsety = -25+this.options.top;				
					this.popointer.addClass('right');
				}				
					
			// position it
				this.popover.position({
					relativeTo: this.options.relativeTo,
					position: position,
					offset: {x: offsetx, y: offsety}
				});
		},
		resize: function(){
			// calculate height and width
				var ssize = this.pocontent.getScrollSize();
			// morph to a new size
				if(typeof this.popover == 'object') this.popover.morph({width: this.options.width, height: ssize.y});
		},
		
	//////////////////////////////////////////////////////////////////////////////
	// load content
		get: function(request){
			this.lastrequest = request;
			var self = this;
			zaj.ajax.get(request, function(result){ self.setContent(result); } );
		},
		post: function(request){
			var self = this;
			zaj.ajax.post(request, function(result){ self.setContent(result); } );
		},
		reload: function(){
			this.get(this.lastrequest);
		},

	//////////////////////////////////////////////////////////////////////////////
	// reset content
		setContent: function(content){
			// set content
				this.pocontent.set('html', content);
			// resize to fit content
				var self = this;
				(function(){ self.resize();}).delay(1000);
		},	

	//////////////////////////////////////////////////////////////////////////////
	// show popover
		show: function(){
			this.popover.show();
		},
	
	//////////////////////////////////////////////////////////////////////////////
	// hide popover
		hide: function(){
			this.popover.hide();
		},
	//////////////////////////////////////////////////////////////////////////////
	// close and destroy popover
		close: function(){
			if(typeof this.popover == 'object'){
				this.popover.hide();
				this.popover.destroy();
				this.poback.destroy();
				this.popover = false;
			}
		}
	

});

/**
 * Class DropMenu creates dropdown menus from nested lists.
 **/
var DropMenu = new Class({


});


/**
 * Class CheckList turns a list of items in a div into a sortable, selectable list of items. You can specify URLs in the options or use events to initiate call-backs.
 * @version 3.0
 * @author Aron Budinszky /aron@mozajik.org/
 */
var CheckList = new Class({

});

/**
 * Class Progress creates a simple progress bar.
 * @version 3.0
 * @author Aron Budinszky /aron@mozajik.org/
 */
var Progress = new Class({
	Implements: [Options, Events],

	options: {
		classname: 'progressbar',		// The css class of the actual bar
		minimum: 0,						// The minimum, initial value
		maximum: 100,					// The maximum value
		picwidth: 13,					// Width of the picture
		infinite: false					// If set to true, the value doesnt matter - the bar will be animated
	},

	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(container, options){
			// set default options
				this.setOptions(options);
				this.container = $(container);
				this.value = this.options.minimum;
			// make sure container is absolute or relative, that overflow is hidden
				this.container.setStyle('overflow', 'hidden');
				var pos = this.container.getStyle('position');
				if(pos != 'relative' && pos != 'absolute') this.container.setStyle('position', 'relative');
			// create my progress bar and set width
				this.progressbar = new Element('div', {'styles': { 'width': this.container.getSize().x, 'right': '100%' }, 'class': this.options.classname });
			// if infinite
				if(this.options.infinite) this.progressbar.setStyles({ 'width': this.container.getSize().x + this.options.picwidth, 'right': 'auto', 'left': '0px' });
			// now inject progress bar into container
				this.progressbar.inject(this.container);
			// make sure that container is at least as high as my progressbar
				if(this.container.getStyle('height') < this.progressbar.getStyle('height')) this.container.setStyle('height', this.progressbar.getStyle('height'));
			this.refresh();
		},
	/**
	 * Sets the current value of the progress.
	 * @param integer val An integer value between the minimum and the maximum.
	 **/
		set: function(val){
			// if over max or under min
				if(val > this.options.maximum) val = this.options.maximum;
				if(val < this.options.minimum) val = this.options.minimum;
			// set and refresh
				this.value = val;
				this.refresh();
		},
	/**
	 * Repaints the progress bar at its current location.
	 **/
		refresh: function(){
			if(this.options.infinite){
				// Where am I now? Reset me if I am too far!
					var l = this.progressbar.getStyle('left').toInt()+1;
					if(l > 0) l = (this.options.picwidth)*(-1);
				// Set me
					this.progressbar.setStyle('left', l+'px');
			}
			else{
				// Calculate the percent
					var percent = 100*(1-(this.value / (this.options.maximum - this.options.minimum)));
				// Set the progress bar
					this.progressbar.setStyle('right', percent+'%');
			}

		},

	/**
	 * Stop and set to 100%.
	 **/		
		stop: function(){
			this.set(this.options.maximum);
			this.options.infinite = false;
		}
});

/**
 * Class PopUp creates a lightbox-style popup interface.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 * @todo Add close on ESC keyup.
 */
var PopUp = new Class({
	Implements: [Options, Events],
	
	options: {
		width: 300,						// Width of the new element
		height: 0,						// Set to 0 for automatic height based on content
		maxHeight: 0.9, 				// If auto-height, then this is the maximum percent value of the window content
		top: 0,							// Offset from top
		left: 0,						// Offset from left
		title: '',						// This goes in the title bar
		closeButton: true,				// Do we need an X?
		closeText: '',					// The text next to the X
		closeOnBackgroundClick: false	// Closes when any other area in the background is clicked
	},
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(content, options){
			// set default options
				this.setOptions(options);
			// create div
				this.popup = new Element('div', {'class':'popover popup', 'html':""});				
			// create title and content, plus close box
				this.potitle = new Element('div', {'class':'popover contenttitle', 'html': this.options.title});
				this.pocontent = new Element('div', {'class':'popover contentview', 'html': content });
				this.poback = new Element('div', { 'class':'popover back' });
				if(this.options.closeButton) this.poclose = new Element('a', { 'class':'popover popup icon tiny close', 'html': this.options.closeText });
			// now inject popover into body and title and content into main div
				this.popup.inject(document.body);
				this.poback.inject(document.body);
				this.potitle.inject(this.popup);
				this.pocontent.inject(this.popup);
				if(this.options.closeButton) this.poclose.inject(this.popup);
			// set properties and reposition
				this.popup.setStyles({
					width: this.options.width,
					height: this.options.height,
					display: 'none'
				});
			// set automatic top if in FB context
				if(this.options.top == 0 && typeof FB != 'undefined'){
					var self = this;
					FB.Canvas.getPageInfo(function(info){
						self.options.top = info.scrollTop/2-info.offsetTop;
						self.resize();
					});
				}
				else this.reposition();
			
			// now add events
				var self = this;
				// add close on back click, but delay a bit to prevent double-click from closing
					if(this.options.closeOnBackgroundClick) (function(){self.poback.addEvent('click', function(){ self.close(); });}).delay(800);
				// add close on close button if close button is enabled
					if(this.options.closeButton) this.poclose.addEvent('click', function(){ self.close(); });
			// now show
				this.show();
			// resize
				this.resize();
			return true;
		},
	
	//////////////////////////////////////////////////////////////////////////////
	// reposition and resize box
		reposition: function(){		
			// position it
				this.popup.setStyle('top', '50%');
				this.popup.setStyle('left', '50%');
				// get size!
				this.popup.setStyle('margin-left', ((-1)*this.options.width/2));
			return true;
		},
		resize: function(){
			var newheight = this.options.height;
			// if no specific height, calculate
				if(newheight <= 0){
					var ssize = this.pocontent.getScrollSize();
					newheight = ssize.y;
					// calculate max height
						var wsize = window.getSize();
						var maxheight = wsize.y * this.options.maxHeight;
					// did I go past maximum?
					if(newheight > maxheight) newheight = maxheight;
				}
			// morph to a new size
				this.popup.morph({width: this.options.width, height: newheight, 'margin-top': ((-1)*newheight/2)+10+this.options.top });
			// make content auto scrollable
				this.pocontent.setStyle('overflow', 'auto');
			// reposition
				this.reposition();
			return true;
		},
		
	//////////////////////////////////////////////////////////////////////////////
	// load content
		get: function(request){
			this.lastrequest = request;
			var self = this;
			zaj.ajax.get(request, function(result){ self.setContent(result); } );
		},
		post: function(request){
			var self = this;
			zaj.ajax.post(request, function(result){ self.setContent(result); } );
		},
		reload: function(){
			this.get(this.lastrequest);
		},

	//////////////////////////////////////////////////////////////////////////////
	// reset content
		setContent: function(content){
			// set content
				this.pocontent.set('html', content);
			// resize to fit content
				this.resize();
		},	

	//////////////////////////////////////////////////////////////////////////////
	// show popover
		show: function(){
			this.popup.show();
		},
	
	//////////////////////////////////////////////////////////////////////////////
	// hide popover
		hide: function(){
			this.popup.hide();
		},
	//////////////////////////////////////////////////////////////////////////////
	// close and destroy popover
		close: function(){
			this.popup.hide();
			this.popup.destroy();
			this.poback.destroy();
		}
	

});

/**
 * TextList
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 * @event onCreate(id, name) A brand new item has been added. ID in this case is a counter integer.
 * @event onAdd(id, name) An existing item with id has been added.
 * @event onRemove(id) An item with id has been removed.
 * @event onSort(ids) An array of ids passed after items have been reordered/sorted.
 * @event onResult() Called after the autocomplete results have been listed.
 **/
var TextList = new Class({
	Implements: [Options, Events],
	
	options: {
		url: false,						// The url to send the search request to. This should be relative. &query=your+query will be appended.
		placeholder: '',				// A text which is displayed when no search has been started yet
		allow_duplicate_ids: false,		// If set to true, two items with the same id can be added. Defaults to false.
		allow_new_items: false,			// If set to true, pressing enter will create a new item
		allow_duplicate_names: false,	// If set to true, two items with the same name but different id can be added.
		sortable: false,				// If set to true, the item list becomes sortable.
		link: false,					// If set a click on the tag will send the user to this url (in a new window) with id appended to the end of it
		max_children: 0					// The maximum number of children. If set to 0, it is unlimited.
	},	
	
	/**
	 * Creates a new TextList object and inserts it into element.
	 **/
		initialize: function(element, options){
			// set default options
				this.setOptions(options);
			// create divs
				this.textlist = new Element('div', {'class': 'mozajik textlist'});
				this.items = new Element('div');
				this.input = new Element('input', {'class': 'mozajik item', 'type':'text'});
			// now inject into one another
				this.textlist.inject($(element));
				this.items.inject(this.textlist);
				this.input.inject(this.textlist);
			// now create autocomplete
				this.autocomplete = new AutoComplete(this.input, {'relative_to': this.textlist, 'url': this.options.url });
				var self = this;
				this.autocomplete.addEvent('select', function(id, name){ self.add_item(id, name); });
			// add other events
				this.textlist.addEvent('click', function(){ self.input.focus(); });
				this.autocomplete.search.element.addEvent('keydown', function(event){ if(event.key == 'enter') self.add_item('', self.autocomplete.search.element.value); });
				this.newitems = 0;
			// create sortable
				if(this.options.sortable) this.sortable = new Sortables(this.items, { opacity: .5, clone: true, revert: { duration: 500, transition: 'elastic:out' }, onComplete: function(){ var s = self.sortable.serialize(false, function(element){ return element.retrieve('id'); }); self.fireEvent('sort', [s]); } });
		},
		
	/**
	 * Add a list item.
	 * @param id The unique id of this item. If empty, this will be considered a new item.
	 * @param name The displayed name of this item.
	 **/
	 	add_item: function(id, name){
		 	var new_item = false;
		 	// is this a new item?
		 		if(!id){
		 			// are they allowed?
		 				if(!this.options.allow_new_items) return false;
		 			// assign a random id!
		 				id = this.newitems++;
		 				new_item = true;
		 		}
		 	// set autocomplete search box to empty and make autocomplete box disappear
		 		this.autocomplete.reset();
		 	// if item id exists and no duplicates, return false
		 		if(this.has_item(id) && !this.options.allow_duplicate_ids) return false;
		 	// create elements
		 		var item = new Element('div', {'id':'textlist-item-'+id, 'class':'mozajik item', 'html': name });
		 	// is there a link?
		 		if(this.options.link) item.set('html', "<a target='_blank' href='"+zaj.baseurl+this.options.link+id+"'>"+name+"</a>");
			// add x
		 		var x = new Element('a', {'class':'cancel', 'html': 'x' });
		 	// add remove event
		 		var self = this;
		 		x.addEvent('click', function(){ self.remove_item(id); });
		 	// inject items
		 		item.inject(this.items);
		 		x.inject(item);
		 		item.store('id', id);
		 	// is there sortability
		 		if(this.options.sortable) this.sortable.addItems(item);
		 	// now resize my textbox and update input
		 		this.resize_box();
		 		this.update_input();
	 		// fire event
	 			if(!new_item) this.fireEvent('add', [id, name]);
	 			else this.fireEvent('create', [id, name]);
	 	},

	/**
	 * Remove a single item based on id.
	 **/
	 	remove_item: function(id){
	 		// get my class
	 			var el = $('textlist-item-'+id);
	 			el.destroy();
		 	// now resize my textbox and update input
		 		this.resize_box();
		 		this.update_input();
	 		// fire event
	 			this.fireEvent('remove', id);
	 	},
	 	
	 /**
	  * Checks to see if item exists among my currently selected.
	  **/
	  	has_item: function(id){
	  		var has = false;
	  		this.items.getElements('div.mozajik.item').each(function(el){ if(id == el.retrieve('id')) has = true; });
	  		return has;
	  	},
	  	
	 /**
	  * Enable/disable input based on the number of maximum entries.
	  **/
	  	update_input: function(){
	  		// Disable input if max is reached
		  		if(this.options.max_children > 0 && this.items.getElements('div.mozajik.item').length >= this.options.max_children) this.input.disabled = true;
		  	// Enable otherwise
		  		else this.input.disabled = false;
		  	
	  	},
	 	
	 /**
	  * Automatically resize my textbox.
	  **/
	 	resize_box: function(){
	 		// set me to zero height
	 			//this.textlist.setStyle('height', 0);
	 		// get scroll size
	 			//var ssize = this.textlist.getScrollSize();
	 		// set based on scroll size
	 			//this.textlist.setStyle('height', ssize.y);
	 	}


});

/**
 * Extend the global zaj object with GUI elements.
 * @version 3.0
 * @author Aron Budinszky /aron@mozajik.org/
 */
	Mozajik.implement({	
		/* popovers */
			popover: {
				object: false,
				reload: function(){
					this.object.reload();
				},
				resize: function(){
					this.object.resize();
				},
				close: function(){
				 	// close popover object if not false
				 		if(this.object) this.object.close();
				 	// set popover object to false
				 		this.object = false;
				}
			},
		/* popups */
			popup: {
				popups: new Array(),	// an array of current popups
				show: function(content, options){
					var p = new PopUp(content, options);
					this.popups.push(p);
					return p;
				},
				get: function(url, options){
					var p = new PopUp("<p align='center'><img src='"+zaj.baseurl+"system/img/assets/ajax-loader.gif'></p>", options);
					p.get(url);
					this.popups.push(p);
					return p;
				},
				post: function(url, options){
					var p = new PopUp("<p align='center'><img src='"+zaj.baseurl+"system/img/assets/ajax-loader.gif'></p>", options);
					p.post(url);
					this.popups.push(p);
					return p;
				},
				// these actions will be performed on the top-most popup!
				resize: function(){
					var popup = this.popups.getLast();
					if(popup) popup.resize();
				},
				close: function(){
					var popup = this.popups.pop();
					if(popup) popup.close();
				}
			}
			
	});

/* extend mootools Element */
	MozajikBaseElement.implement({
		popover: function(url, options){
				// close any that already exist
					zaj.popover.close();
				// extend options
					options = Object.append({height: 60, relativeTo: this.element}, options);
				// create a new popover relative to me
					zaj.popover.object = new PopOver("<p align='center'><img src='"+zaj.baseurl+"system/img/assets/ajax-loader.gif'></p>", options);
				// now load a url
					zaj.popover.object.get(url);
				return zaj.popover.object;
		},		
		popovertext: function(text, options){
				// close any that already exist
					zaj.popover.close();
				// extend options
					options = Object.append({height: 60, relativeTo: this.element}, options);
				// create a new popover relative to me
					zaj.popover.object = new PopOver(text, options);
				return zaj.popover.object;
		}
	});


