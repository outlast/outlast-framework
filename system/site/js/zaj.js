////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2008 - basic javascript
////////////////////////////////////////////////////////////////////////////
// basic helper javascript functions
// version 3.0
////////////////////////////////////////////////////////////////////////////
// version history
// 3.0 - initial release, mozajik version
////////////////////////////////////////////////////////////////////////////
// global variables
	var is_ie6 = (window.external && typeof window.XMLHttpRequest == "undefined");

// create logger
	var zajLog = new Class({ log: function(message){if($defined(window.console)) window.console.log(message); else if(typeof console != "undefined") console.log(message);}});
	var zajlog = new zajLog();

// create js lib (for variables like baseurl, app, etc. - these are set at runtime in {{zajlib.js}})
	
var zajLib = new Class({
	Implements: [Options, Events],
	
	// options
		options: {
		},

	// variables
		asset_js_load_at_runtime: 0,
		asset_js_loaded: 0,
		asset_css_load_at_runtime: 0,
		asset_css_loaded: 0,
		asset_ext_control_load_at_runtime: 0,


	// load assets
		load_js: function(path){
			// load the asset and add one to the asset counter
			var self = this;
			new Asset.javascript(path, { onload: function(){ window.addEvent('domready',function(){ self.onJsLoad();}); } });
			this.asset_js_load_at_runtime++;
		},
		load_css: function(path){
			var self = this;
			new Asset.css(path, { oncomplete: function(){ window.addEvent('domready',function(){ self.onCssLoad();}); } });
			this.asset_css_load_at_runtime++;
		},
		load_ext_control: function(){
			// loads an external control such as yui (usually in an iframe)
			var self = this;
			this.asset_ext_control_load_at_runtime++;
			this.log('load '+this.asset_ext_control_load_at_runtime+' controls');
		},
	
	// events
		onJsLoad: function(){
			// add one to the amount loaded
				this.asset_js_loaded++;
			// check if any left
				if(this.asset_js_load_at_runtime <= this.asset_js_loaded){
					this.log('js is now ready. '+this.asset_js_loaded+' files loaded successfully!');
					this.fireEvent('jsready');
					this.removeEvents('jsready');
				}
		},
		onCssLoad: function(){
			// WARNING! This is never actually fired on most browsers. Likely a mootools bug!
			// add one to the amount loaded
				this.asset_css_loaded++;
			// check if any left
				if(this.asset_css_load_at_runtime <= this.asset_css_loaded){
					this.log('css is now ready. '+this.asset_css_loaded+' files loaded successfully!');
					this.fireEvent('cssready');
					this.removeEvents('cssready');
				}
		},
		onExtControlLoad: function(){
			// this is fired by the external control's library (yui for example)
			// subtract one from js loads needed
				this.asset_ext_control_load_at_runtime--;
			// check if any left
				if(this.asset_ext_control_load_at_runtime <= 0){
					this.log('external controls ready!');
					this.fireEvent('extcontrolready');
					this.removeEvents('extcontrolready');
				}		
		},
		onExtControlEvent: function(field, event){
			// any event can be fired by an external control (yui for example)
				$(field).fireEvent(event);
				$(field).removeEvents(event);
		},

	// error and logging functions
		error: function(message){ zajpopup.warning('MozajikJS runtime error: '+message); },
		log: function(message){ zajlog.log(message); }
});

var zajlib = new zajLib();
window.addEvent('domready', function(){ zajlib.log('in-line loading: js ('+zajlib.asset_js_load_at_runtime+') / css ('+zajlib.asset_css_load_at_runtime+')'); });