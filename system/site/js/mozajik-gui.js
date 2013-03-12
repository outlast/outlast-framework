/**
 * The mozajik-gui.js file contains all the custom Mozajik GUI elements' javascript code.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
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
				this.resize();
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
				this.popover.morph({width: this.options.width, height: ssize.y});
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
			this.popover.hide();
			this.popover.destroy();
			this.poback.destroy();
		}
	

});


/**
 * Class CheckList turns a list of items in a div into a sortable, selectable list of items. You can specify URLs in the options or use events to initiate call-backs.
 * @version 3.0
 * @author Aron Budinszky /aron@mozajik.org/
 */
var CheckList = new Class({

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
				this.popup = new Element('div', {'class':'popover popup', 'html':"<div class='popover side top'></div><div class='popover side bottom'></div><div class='popover side left'></div><div class='popover side right'></div><div class='popover side tl'></div><div class='popover side tr'></div><div class='popover side bl'></div><div class='popover side br'></div>"});				
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
				this.reposition()			
			
			// now add events
				var self = this;
				// add close on back click, but delay a bit to prevent double-click from closing
					if(this.options.closeOnBackgroundClick) (function(){self.poback.addEvent('click', function(){ self.close(); });}).delay(200);
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
				var psize = this.pocontent.getScrollSize();
				this.popup.setStyle('margin-left', ((-1)*psize.x/2)+40);
			return true;
		},
		resize: function(){
			// if specific height
				if(this.options.height > 0) return true;
			// calculate height and width
				var ssize = this.pocontent.getScrollSize();
			// morph to a new size
				this.popup.morph({width: this.options.width, height: ssize.y, 'margin-top': ((-1)*ssize.y/2)+10});
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
					var p = new PopUp("<p align='center'><img src='"+this.baseurl+"system/img/assets/ajax-loader.gif'></p>", options);
					p.get(url);
					this.popups.push(p);
					return p;
				},
				post: function(url, options){
					var p = new PopUp("<p align='center'><img src='"+this.baseurl+"system/img/assets/ajax-loader.gif'></p>", options);
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
					options = $extend({height: 60, relativeTo: this.element}, options);
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
					options = $extend({height: 60, relativeTo: this.element}, options);
				// create a new popover relative to me
					zaj.popover.object = new PopOver(text+"<br/><br/><a class='icon tiny okay' onclick='zaj.popover.close();'></a>", options);
				return zaj.popover.object;
		}
	});


