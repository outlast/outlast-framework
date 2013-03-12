/**
 * The mozajik-tool.js file contains all the custom Mozajik tools.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.3
 * @requires MozajikBase, MooTools 1.3+, Mootools/Asset
 **/

/**
 * Class ReorderList allows you to easily reorder a list of zajModel objects.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 */
Mozajik.ReorderList = new Class({
	Implements: [Options, Events],

	options: {
		handle: '.handle',			// a class selector
		url: ''						// url where to post data
	},


	/**
	 * Events
	 **/
		// onSort: function(ids){},
	
	/**
	 * Creates a new FileList object
	 * @param Element The element which is to contain the file list.
	 **/
		initialize: function(container, options){
			// create my container
				this.setOptions(options);
				this.container = $(container);
			// create my sortable
				var self = this;
				this.sortable = new Sortables(this.container, {
					opacity: .5, clone: true, revert: { duration: 500, transition: 'elastic:out' },
					handle: '.handle',
					onComplete: function(){ var ids = self.sortable.serialize(); self.sort(ids); } } );
		 },
	
	/**
	 * Send the event
	 **/
	 	sort: function(ids){
				//console.log(this.options.url, ids);
			// Fire event
				this.fireEvent('sort', [ids])
	 	}
});


/**
 * Class FileList allows you to easily list files when, for example, uploading.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 */
Mozajik.FileList = new Class({
	Implements: [Events],

	/**
	 * Events
	 **/
		// onAdd: function(id){},
		// onRemove: function(id){},
		// onSort: function(ids){},
	
	/**
	 * Creates a new FileList object
	 * @param Element The element which is to contain the file list.
	 **/
		initialize: function(container, options){
			// create my container
				this.container = $(container);
			// create my sortable
				var self = this;
				this.sortable = new Sortables(this.container, {
					opacity: .5, clone: true, revert: { duration: 500, transition: 'elastic:out' },
					onComplete: function(){ var ids = self.sortable.serialize(); self.fireEvent('sort', [ids]); } } );
		 },
	
	/**
	 * Adds a file to the list
	 **/
	 	add_file: function(name, id, is_new){
	 		// HTML: <div class="icon mime zip">name.zip <a>x</a></div>
	 		var components = name.split('.');
	 		var extension = components[components.length-1];
			
			// Create and inject file listing
				var f = new Element('div', {'id': id, 'class': 'icon mime '+extension, 'html': name+" <a id='remove-"+id+"' href='#remove'>x</a></div>" });
				f.inject(this.container);
				this.sortable.addItems(f);
			// Add event to a tag
				var self = this;
				$('remove-'+id).addEvent('click', function(ev){ self.remove_file(ev.target.parentNode.id); });
			// Fire event
				this.fireEvent('add', id)
	 	},
	 	
	/**
	 * Removes a file from the list
	 **/
	 	remove_file: function(id){
	 		// Destroy DOM element
				this.sortable.removeItems($(id));
		 		$(id).destroy();
			// Fire event
				this.fireEvent('remove', id);
	 	}
});

/**
 * Crop class allows you to crop an image.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 */
Crop = new Class({
	Implements: [Options, Events],
	
	options: {
		y: 10,
		x: 10,
		w: 50,
		h: 50
	},
	
	/**
	 * Creates a new Crop object
	 * @param Element The element which is to contain the ratings stars.
	 **/
		initialize: function(image, options){
			// set default options
				this.setOptions(options);
				this.image = $(image);
			// get my image location
				var ipos = this.image.getPosition();
			// create my crop select tool
				this.croptool = new Element('div', { 'styles': { 'border': '1px solid black', 'position': 'absolute', 'top':ipos.y+this.options.y, 'left':ipos.x+this.options.x, 'width': this.options.w, 'height': this.options.h }});
				document.body.appendChild(this.croptool);
		}

});





/**
 * Rating allows you to create a generic rating system.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 * @event onRate(ratenumber) Called after a rating is clicked on by the user.
 * @event onShow(ratenumber) Called when the stars are shown (for example also after mouseenter).
 */
Rate = new Class({
	Implements: [Options, Events],
	
	options: {
		star_count: 5,			// the number of stars on this rating
		star_class: 'mozajik ratestar',	// the CSS class which defines the global star attributes
		off_class: 'off',		// the CSS class which modifies a div with an empty star background (not really used - off should be the default)
		on_class: 'on',			// the CSS class which modifies a div with a full star background
		half_class: 'half',		// the CSS class which modifies a div with a half-star background
		default_value: 0		// the default value of this rate box
	},
	
	/**
	 * Creates a new Upload object
	 * @param Element The element which is to contain the ratings stars.
	 **/
		initialize: function(container, options){
			// set default options
				this.setOptions(options);
				this.container = $(container);
				this.currentvalue = 0;
			// create the number of divs
				this.stars = new Array();
				for(var i = 1; i <= this.options.star_count; i++){
					// create star and inject
						this.stars[i] = new Element('div', {'class':this.options.star_class, 'html':''});
						this.stars[i].inject(this.container);
						this.stars[i].store('value', i);
					// add events to element
						var self = this;
						this.stars[i].addEvent('click', function(event){ self.set(event.target.retrieve('value')); });
						this.stars[i].addEvent('mouseenter', function(event){ self.show(event.target.retrieve('value')); });
						this.stars[i].addEvent('mouseleave', function(event){ self.show(self.currentvalue); });
				}
			// set current value
				return this.set(this.options.default_value);
		},
	/**
	 * Sets the current star rating to the value defined by the parameter.
	 * @param float This is the new rating value
	 **/
	 	set: function(value){
	 		// set current value and show
		 		this.currentvalue = value;
		 		this.show(value);
		 	// fire event
		 		this.fireEvent('rate', value);
		 	return true;
	 	},

	/**
	 * Shows the stars at the specific value
	 * @param float The current star is set to on, and all others below it.
	 **/
	 	show: function(value){
	 		// check value bounds
	 			if(value < 0 || value > this.options.star_count) return false;
	 		// remove on and half classes
	 			this.stars.each(function(el){ el.removeClass('on'); el.removeClass('half'); });
	 		// first, set all lower stars to on
	 			for(var i = 1; i <= value; i++) this.stars[i].addClass('on');
	 		// all higher ones are off anyway
	 		// am i off, on, or half?
	 			var fvalue = Math.floor(value);
	 			if(value - fvalue > 0.25){
	 				if(value - fvalue < 0.75) this.stars[fvalue].addClass('half');
	 				else this.stars[fvalue].addClass('on');
	 			}
		 	// fire event
		 		this.fireEvent('show', value);	 			
	 		return true;
	 	}
});

/**
 * Upload assists in creating and submitting uploads with progress bars.
 * @author Aron Budinszky /aron@mozajik.org/
 * @todo Make Progress optional
 * @version 3.0
 */
Upload = new Class({
	Implements: [Options, Events],
	
	options: {
		url: false						// When defined, the request will automatically be sent to this URL after the file was selected.
	},
	
	/**
	 * Creates a new Upload object
	 * @param Element The element which is to contain the upload box.
	 **/
		initialize: function(container, options){
			// set default options
				this.setOptions(options);
			// create my elements
				this.container = $(container);
				this.progressbar = new Element('div', { });
			// create objects
				this.uploader = new MozajikBaseAjax();
		 	// add upload box
		 		this.addbox();		 	
			// inject elements into container
				this.progressbar.inject(this.container);
			// create my progress
				this.progress = new Progress(this.progressbar);				
			// now add events
				var self = this;
				this.uploader.addEvent('progress', function(progress){
					// set max and min
						self.progress.options.minimum = 0;
						self.progress.options.maximum = progress.total;
					// now set my current
						self.progress.set(progress.loaded);
				});
			return true;
		},

	/**
	 * Creates an upload box.
	 **/
	 	addbox: function(){
			// destroy any previous
				if(typeOf(this.filebox) == 'element') this.filebox.destroy();
			// create and inject
				this.filebox = new Element('input', { 'type':'file' });
				this.filebox.inject(this.container);
			// if auto url
			if(this.options.url){
				var self = this;
				this.filebox.addEvent('change', function(){
					self.filebox.hide();
					self.uploader.postfile(self.filebox, self.options.url, function(res){ self.process(res); });
				});
			}
			else zaj.log('No URL specified for file upload.');
	 	},	 
	
	/**
	 * Processes a completed upload
	 * @param string Result in JSON string.
	 **/
	 	process: function(res){
	 		// decode the JSON
	 			var response = JSON.decode(res);
	 		// was it ok?
	 			if(typeOf(response) == 'object' && response.status == 'ok'){ this.fireEvent('success', response); }
				else{ this.fireEvent('failure'); }
		 	// create new filebox
		 		this.addbox();		 	
		 	// reset progress bar
		 		this.progress.set(0);
	 	}
});

/**
 * Class Tab allows users to create a tabbed interface easily.
 * @author Aron Budinszky /aron@mozajik.org/
 * @todo Add history support!
 * @version 3.0
 * @event onSelect(tab_name) Fired when the tab is selected.
 */
Tab = new Class({
	Implements: [Events],
	
	/**
	 * Creates a new Tab object
	 * @param class_name Define the class of the tab objects you wish to use. One class per group of tabs on a page.
	 **/
		initialize: function(class_name, options){
			// set default options
				//this.setOptions(options);
			// set my tabs class
				this.class_name = class_name;
			// do i have a selected tab?
				var uri = new URI();
				var fragment = uri.get('fragment');
				var self = this;
				// show if fragment defined and div exists
				if(fragment != '') window.addEvent('domready', function(){ if($$('div.'+self.class_name+'.'+fragment).length > 0) self.show(fragment); });
			// register me in the global zaj object (if it exists)
				if(typeOf(zaj.tab_objects) == 'object') zaj.tab_objects[class_name] = this;
		},
	
	/**
	 * Show a tab
	 * @param class_name The class name of the specific tab you wish to show. All others in this group will be hidden.
	 **/
	 	show: function(tab_name){
			$$('div.'+this.class_name).each(function(el){
				// if it has this tab_name among its classes, show and fire event
				if(el.hasClass(tab_name)){ el.show(); this.fireEvent(tab_name); }
				// otherwise hide
				else el.hide();
			});
	 	}
	 	
});


/**
 * Class Search creates a search box which sends ajax requests at specified intervals to a given url.
 * @author Aron Budinszky /aron@mozajik.org/
 * @todo Add placeholder text
 * @version 3.0
 */
Search = new Class({
	Implements: [Options, Events],
	
	options: {
		delay: 300,						// Number of miliseconds before 
		url: false,						// The url to send the request to. This should be relative. &query=your+query will be appended. If no url (false), it will not be submitted anywhere.
		callback: false,				// A function or an element.
		callback_bind: false,			// The callback function's 'this' will bind to whatever is specified here.
		method: 'get',					// The method to send by. Values can be 'post' (default) or 'get'.
		allow_empty_query: true,		// If set to true, an empty query will also execute
		pushstate_url: false,			// You can use pushState to change the url and data of the site after the search is done
		pushstate_data: false,			// You can use pushState to change the url and data of the site after the search is done
		pushstate_name: false			// You can use pushState to change the url and data of the site after the search is done
	},
	
	/**
	 * Creates a new Search object
	 **/
		initialize: function(element, options){
			// set default options
				this.setOptions(options);
				this.last_query;
			// register events
				this.timer = false;
				this.element = $(element);
				var self = this;
				this.element.addEvent('keyup', function(){
					// reset earlier timer
						if(self.timer){ clearTimeout(self.timer); }
					// now set a new timer
						self.timer = (function(){ self.send(); }).delay(self.options.delay);
				});
				this.element.addEvent('blur', function(){
					self.send();
				});
			return true;
		},
	
	/**
	 * Sends the query to the set url and processes.
	 **/
		send: function(){
			// if the element value is empty, do not do anything
				if(!this.options.allow_empty_query && !this.element.value) return false;
			// if url not set, just do callback immediately!
			if(this.options.url){			
				// append element value to url
					var url = this.options.url;
					if(this.options.url.contains('?')) url += '&query='+this.element.value;
					else url += '?query='+this.element.value;
					url += '&mozajik-tool-search=true';
				// check if the current query is like last query
					if(this.last_query == this.element.value) return false;
					else this.last_query = this.element.value;
				// now send via the appropriate method
					if(this.options.method == 'get') zaj.ajax.get(url, this.options.callback, {'data': this.options.pushstate_data, 'title': this.options.pushstate_title, 'url': this.options.pushstate_url}, this.options.callback_bind);
					else zaj.ajax.post(url, this.options.callback, {'data': this.options.pushstate_data, 'title': this.options.pushstate_title, 'url': this.options.pushstate_url}, this.options.callback_bind);
			}
			else{
				this.options.callback(this.element.value);
			}
		}
});


/**
 * Class AutoComplete can be attached to any text box and display result suggestions in a box based on a query sent to a url.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 * @requires Search
 * @event onAdd(id, name) Called when a search result is added to the list of results.
 * @event onRemove(id) Called when a search result is removed from the list of results.
 * @event onSelect(id, name) Called when a search result is selected from the list of results.
 */
AutoComplete = new Class({
	Implements: [Options, Events],
	
	options: {
		url: false,						// The url to send the request to. This should be relative. &query=your+query will be appended.
		relative_to: false,				// If set, the relative_to is an element to which the results box is relatively appended to. If not set, the results box is appended to the text box element.
		method: 'post',					// The method to send by. Values can be 'post' (default) or 'get'.
		search: false,					// Use a custom Search object for sending requests. If this is done, the Search object's parameters are used.
		placeholder: ''					// A text which is displayed when no search has been started yet
	},
	
	/**
	 * Creates a new Search object
	 **/
		initialize: function(element, options){
			// set default options
				this.setOptions(options);
			// create results container
				this.rescontainer = new Element('div', { 'html' : this.options.placeholder, 'class': 'mozajik autocomplete results' });
				this.rescontainer.inject(document.body);
			// create search object, unless it is already set
				if(!this.options.search) this.options.search = new Search(element, { 'url': this.options.url, 'method': this.options.method, 'callback': this.process, 'callback_bind': this, 'allow_empty_query': false });
			// set my objects (search box, relative box)
				this.search = this.options.search;
				if(this.options.relative_to) this.relbox = $(this.options.relative_to);
				else this.relbox = $(element);
			// add events
				var self = this;
			// rebind my search
				this.search.options.callback_bind = this;
			// add blur event
				element.addEvent('blur', function(){ (function(){self.rescontainer.hide()}).delay(500); });
		},
	
	/** 
	 * Process the results.
	 * @param json Processes JSON data. An array of objects where there is a required 'id' and 'name' element.
	 **/
	 	process: function(result){
	 		var res = JSON.decode(result, true);
	 		// if not an object, just display (it's probably an error)
	 			if(typeOf(res) != 'object') return zaj.alert(result);
	 		// show my results container and position
	 			this.remove_all();
	 			this.rescontainer.show();
	 			this.position_box();
	 		// add each item
	 			var self = this;
	 			Object.each(res, function(item){ self.add_item(item.id, item.name, item.html); });
	 		
	 	},
		
	/** 
	 * Add one item to the results list. Items are identified by ID.
	 * @param id The unique id of this item.
	 * @param name The displayed name of this item.
	 * @param html Custom HTML. You can use any custom HTML, if needed.
	 **/
	 	add_item: function(id, name, html){
	 		// create my results element
	 			var el = new Element('div', { 'id':'autocomplete-result-'+id, 'class': 'mozajik autocomplete resultitem' });
	 		// what html to use
	 			if(html != undefined) el.set('html', html);
	 			else el.set('html', name);
	 		// store id
	 			el.store('id', id);
	 		// add click event to element
	 			var self = this;
	 			el.addEvent('click', function(){ self.search.last_query=''; self.fireEvent('select', [id, name]); self.remove_all(); });
	 		// now inject
	 			el.inject(this.rescontainer);
	 		// fire the event
	 			this.fireEvent('add', id);
	 	},
	 	
	/**
	 * Remove a single search result by id.
	 **/
	 	remove_item: function(id){
	 		// destroy the element
	 			$('autocomplete-result-'+id).destroy();
	 		// fire the event
	 			this.fireEvent('remove', id);
	 	},
	 	
	/**
	 * Remove all items
	 **/
	 	remove_all: function(){
	 		// go through all results and remove them
	 			this.rescontainer.getElements('div.mozajik.autocomplete.resultitem').each(function(el){
	 				var id = el.retrieve('id');
	 				this.remove_item(id);
	 			}, this);
	 		// hide the box
	 			this.rescontainer.hide();
	 	},
	 	
	/**
 	 * Reset the search and remove results box.
	 **/
	 	reset: function(){
	 		// remove all and hide
		 		this.remove_all();
		 	// set search box to empty
		 		this.search.element.value='';
	 	},

	/** 
	 * Positions the results container box relative to the relbox
	 **/
	 	position_box: function(){
	 		// get position and size
	 			var pos = this.relbox.getPosition();
	 			var size = this.relbox.getSize();
	 			var border = parseInt(this.relbox.getStyle('border-left-width'));
	 		// position my results container
	 			this.rescontainer.setStyles({'left': pos.x, 'top': pos.y+size.y, 'width': size.x-(border*2) });
	 	}
		
});




/**
 * Extend the global zaj object with GUI elements.
 * @version 3.0
 * @author Aron Budinszky /aron@mozajik.org/
 */
	Mozajik.implement({	
		/* search */
			search: function(element, url, callback){
				var search_object = new Search(element, { 'url': url, 'callback': callback });
				return search_object;
			},
		/* tabs */
			tab_objects:{
				'mozajik_tab': new Tab('mozajik_tab')
			}
	});

/* extend mootools Element */
	MozajikBaseElement.implement({
		/* search */
		search: function(url, callback){
			return zaj.search(this.element, url, callback);
		},
		/* reorder */
		reorder: function(url){
			var something = Mozajik.ReorderList(this.element, { 'url': url });
		},
		/* tab */
		tab: function(tab_class){
			// select the right object
				if(tab_class == undefined) tab_class = 'mozajik_tab';
			// get tab name
				var tabname = this.element.getProperty('href').substr(1);
			// now select the right tab
				zaj.tab_objects[tab_class].show(tabname);
		},
		/* placeholder for input elements */
		placeholder: function(placeholder, placeholder_class){
			// if no custom class defined
				if(placeholder_class == undefined) placeholder_class = 'mozajik_placeholder';
			// now add events
				var self = this;
				this.element.addEvent('focus', function(){ if(self.element.value==placeholder){ self.element.value=''; self.element.removeClass(placeholder_class); } });
				this.element.addEvent('blur', function(){ if(self.element.value==''){ self.element.value=placeholder; self.element.addClass(placeholder_class); } });
		}
	});
	
	
/* add automatically loaded items */
	window.addEvent('domready', function(){
		$$("input[rel=placeholder]").each(function(el){
			el.$zaj().placeholder(el.getProperty('value'));
		});
	});


