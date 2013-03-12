/**
 * This file containes Mozajik GUI elements in the Dojo Toolkit version.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.6
 **/

dojo.provide("Mozajik.gui");

// Requirements (todo: load these on demand only!) 
dojo.require("dijit.Dialog");

/**
 * Class PopUp creates an in-page div-based popup interface.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 */
dojo.declare("PopUp", null, {
	/**
	 * Properties
	 **/
	 	// options
		options: {
			width: 300,						// Width of the new element
			height: 0,						// Set to 0 for automatic height based on content
			maxHeight: 600, 				// If auto-height, then this is the maximum
			top: 0,							// Offset from top
			left: 0,						// Offset from left
			title: '',						// This goes in the title bar
			//closeButton: true,				// Do we need an X?
			//closeText: '',					// The text next to the X
			//closeOnBackgroundClick: false,	// Closes when any other area in the background is clicked
			load_text: '<p align="center"><img src="'+zaj.baseurl+'system/img/assets/ajax-loader.gif"></p>'
		},
	
	/**
	 * Constructor method.
	 **/
	 	constructor: function(content, options){
	 		dojo.mixin(this.options, options);		 	
		 	this.content = content;
			this.dialog = new dijit.Dialog({
				title: this.options.title,
				content: this.content,
				style: "width: "+this.options.width+"px"
			});
			this.dialog.show();
			// add event for hiding - remove one from popup list
			dojo.connect(this.dialog, 'onHide', null, function(){  });
	 	},
	 	
	/**
	 * Get a url and display.
	 **/
	 	get: function(url){
			// set to loading
				this.loading();	 			
		 	// load content
		 		var self = this;
		 		zaj.ajax.get(url, function(result){ self.set_content(result); zaj.popup.callback(); });
		 	return true;
	 	},

	/**
	 * Post a url and display.
	 **/
	 	post: function(url){
			// set to loading
				this.loading();	 			
		 	// load content
		 		var self = this;
		 		zaj.ajax.post(url, function(result){ self.set_content(result); zaj.popup.callback(); });
		 	return true;
	 	},

	/**
	 * Loading mode
	 **/
	 	loading: function(url){ this.set_content(this.options.load_text); },

	/**
	 * Set content of window.
	 **/
	 	set_content: function(content){ zaj.ajax.set(this.dialog.containerNode, content); },
	 	
	/**
	 * Close the popup.
	 **/
		close: function(){ this.dialog.destroy(); }	

});

/**
 * Class PopOver creates a popover interface similar to what is used on iOS interfaces.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 **/

// For backwards compatibility popover is temporarily an alias of PopUp
var PopOver = PopUp;

/**
 * Extend base class.
 **/
dojo.extend(Mozajik, {
	/* Popups */
		popup: {
			popups: new Array(),	// an array of current popups
			callbacks: new Array(),	// an array of 'ready' callback functions
			show: function(content, options){
				var p = new PopUp(content, options);
				this.popups.push(p);
				return p;
			},
			get: function(url, options){
				var p = new PopUp("", options);
				p.get(url);
				this.popups.push(p);
				return p;
			},
			post: function(url, options){
				var p = new PopUp("", options);
				p.post(url);
				this.popups.push(p);
				return p;
			},
			// these actions will be performed on the top-most popup!
			resize: function(){
				//var popup = this.popups.getLast();
				//if(popup) popup.resize();
				zaj.warning("Resize not yet supported in Dojo version.");
			},
			close: function(){
				var popup = this.popups.pop();
				if(popup) popup.close();
			},
			// create a ready function
			ready: function(func){
				this.callbacks.push(func);
			},
			// call the ready function
			callback: function(){
				var func = this.callbacks.pop();
				func();
			}
		},
});
// extend again to provide aliases (TODO: remove this after implementing popover)
dojo.extend(Mozajik, { popover: zaj.popup });

/**
 * Extend the Element class.
 **/
dojo.extend(Mozajik.Element, {

	/* Popups */

});

