/**
 * This is the Dojo Toolkit version of the Mozajik JS layer class. The JS layer has three sections, 'base' (basic stuff for sending requests, logging, etc.), 'tool' (tabs, forms, etc.),
 *	and 'ui' (user interface elements). The Dojo version's ui is a wrapper for Digits.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.6
 **/

dojo.provide("Mozajik.base");

// Requirements (todo: load on demand)
dojo.require("dojox.html._base");

/**
 * Enable moo-style selector-aliases and "d" for dojo
 **/
var d = dojo;
var $ = dojo.byId;
var $$ = dojo.query;

/**
 * Enable JS error logging.
 * @todo Do not send request if js logging is not enabled!
 **/	
window.onerror=function(message, url, line){
	// determine my relative url
		var my_uri = new URI();
		var error_url = zaj.baseurl+'system/javascript/error/';
 	// send an ajax request to log the error if it is a modern, supported browser
 		dojo.xhrGet({ 'url': error_url+'?js=error&message='+message+'&url='+url+'&location='+zaj.fullrequest+'&line='+line });
	return true;
}


/**
 * Base class contains the most important and often used features of the Mozajik JS layer: ajax requests, history management, logging, etc.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 **/
dojo.declare("Mozajik", null, {
	/**
	 * Properties
	 **/
	 	// options
		options: {},
		// run-time variables
		baseurl:'',fullrequest:'',fullurl:'',app:'',mode:'',debugmode:false,protocol:'http',
		// performace variables
		execution_time:'', peak_memory:'', console: false,

	/**
	 * Constructor method.
	 **/
	 	constructor: function(options){
	 		dojo.mixin(this.options, options);
	 		this.console = (typeof console != 'undefined' && typeof console == 'object');
	 	},
	 
	/**
	 * Use the console log if it exists.
	 **/
		log: function(message, el){
			if(this.console) console.log(message, el);
			return true;
		},
		error: function(message, el){
			if(this.console) console.error(message, el);
			else alert(message);
			return false;
		},
		warning: function(message, el){
			if(this.console) console.warn(message, el);
			return true;
		},
		notice: this.log,

	/**
	 * Custom alerts, confirms, prompts
	 **/
		alert: function(message){
			//if(typeof zaj.popup == 'object') return zaj.popup.show(message);
			//else return alert(message);
			alert(message);
		},
		confirm: function(message, urlORfunction){
			// if the passed param is a function, then return confirmation as its param
			if(typeof urlORfunction == 'function'){
				var result = confirm(message);
				urlORfunction(result);
			}
			// if the passed param is a url, redirect if confirm
			else{
				if(confirm(message)) window.location=zaj.baseurl+urlORfunction;
			}
		},
		prompt: function(message){
			return prompt(message);
		},

	/**
	 * Toggles two DOM elements, showing one and hiding another.
	 * @param show The DOM element to show.
	 * @param hide The DOM element to hide.
	 **/
		toggle: function(show, hide){
			$(hide).dissolve();		
			$(show).reveal();		
		},
		
	/**
	 * Redirect to a page relative to baseurl or a full url.
	 * @param url The URL relative to baseurl or the full url.
	 **/
		redirect: function(url){
			// is it a full url or a relative?
				if(url.substr(0, 2) != '//' && url.substr(4, 3) != "://" && url.substr(5, 3) != "://") url = zaj.baseurl+'/'+url;
			// now redirect!
				window.location = url;
			return true;
		},
		
	/**
	 * Reload the current url.
	 **/
		reload: function(){
			window.location.reload(false);
		},
		refresh: function(){ this.reload(); },
		
	/**
	 * A function which serves to unify dojo.ready, window.addEvent('domready'), etcetc. This also fires after any ajax requests are completed and ready.
	 **/
	 	ready: function(func){
	 		// TODO: add ajax functionality via ready_functions array.
	 		dojo.ready(func);
	 	}
});

var zaj = new Mozajik();

/**
 * The Ajax class handles requests back and forth between the JS layer and Mozajik controllers.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.6
 **/
dojo.declare("Mozajik.Ajax", null, {
	/**
	 * Properties
	 **/
	 	// options
		options: {},
		// other stuff
		last_request: '',
	
	/**
	 * Constructor method.
	 **/
	 	constructor: function(options){
	 		dojo.mixin(this.options, options);
	 	},

	/**
	 * Creates a GET request and sends it to the specified URL (relative to baseurl), which is then routed to the appropriate controller
	 * @param string request The request url relative to baseurl.
	 * @param function|element|string result The result can be a function, an element, or a url.
	 * @param object bind Used to bind 'this' within the function. Only matters if result is a function.
	 **/
		get: function(request,result,bind){
			this.send('get',request,result,bind);
		},

	/**
	 * Creates a POST request and sends it to the specified URL (relative to baseurl), which is then routed to the appropriate controller
	 * @param string request The request url relative to baseurl.
	 * @param function|element|string result The result can be a function, an element, or a url.
	 * @param object bind Used to bind 'this' within the function. Only matters if result is a function.
	 **/
		post: function(request, result,bind){
			this.send('post',request,result,bind);
		},
	
	/**
	 * Sets the innerHTML of a DOM element while also evaluating CSS and javascript.
	 * @param string|DOMobject el The DOM element.
	 * @param string content The innerHTML to set.
	 * @param object options The script and style options. See below for default.
	 * @param boolean parseOnLoad If set to true, a dojo parseOnLoad will be performed after the content. True by default. Can also be passed as an option.
	 **/
	 	set: function(el, content, options, parseOnLoad){
	 		var defaults = { executeScripts: true, scriptHasHooks: false, renderStyles: true, parseOnLoad: true };
	 		// default options
				if(typeof options == 'object') dojo.mixin(defaults, options);
			// set html
				dojox.html.set(el, content, defaults);
			// now fire parse if requested
				if(defaults.parseOnLoad || parseOnLoad) dojo.parser.parse();
			return true;
	 	}, 
	
	/**
	 * Sends the actual request via method and returns the result to a div, a function, or url
	 * @todo Optimize!
	 **/
		send: function(method, request, result, bind){
			// init variables
				var request_url = "";
				var request_data = "";
				// add the baseurl if needed
					if(typeof request != 'object' && request.substr(0,2) != '//' && request.substr(4, 3) != "://" && request.substr(5, 3) != "://") request_url = zaj.baseurl+'/'+request;
					else request_url = dojo.getNodeProp(request, 'action')+'?'+dojo.formToQuery(request);
				// set variables
					var self = this;
					var xhr_options = {
						'url': request_url,
						//'preventCache': true,
						'load': function(response){ self.process(result, response, bind); },
						'error': function(response){ self.error(result, response, bind); }
					};
				// if the method is post
					if(method=='post'){
						var rdata = request_url.split("?", 2);
						var postdata = dojo.queryToObject(rdata[1]);
						dojo.mixin(xhr_options, {'url': rdata[0], 'content': postdata });
					}				
				// GET or POST?
					var ajaxcall;
					if(method == 'get')	ajaxcall = dojo.xhrGet(xhr_options);
					else ajaxcall = dojo.xhrPost(xhr_options);
		},


	/**
	 * Handle response and pass to a div, a function, or url
	 **/
		process: function(callBack, responseText, bind){
			// decide what to do
				var whattodo = typeof callBack;
				switch(whattodo){
					case 'function': 	callBack(responseText, bind);
										break;
					case 'object':		this.set(callBack, responseText);
										break;
					default:			// It's a URL! Check if ok, and send if ok.
										if(responseText == 'ok') zaj.redirect(callBack);
										else zaj.alert(responseText);
										break;
				}
			return true;
		},

	/**
	 * Handle the error
	 **/
		error: function(error){
			zaj.log(error);
		}
});
zaj.ajax = new Mozajik.Ajax();

/**
 * Mozajik.Element class provides special functionality via the $ method. This is not dojo like and should not be used too much (it's here for mootools compatibility).
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.6
 **/
dojo.declare("Mozajik.Element", null, {
	/**
	 * Constructor method.
	 **/
	 	constructor: function(element){
	 		this.element = element;
	 	},
		get: function(request, result){
			zaj.ajax.get(request+'?'+dojo.formToQuery(this.element), result);
		},
		post: function(request, result){
			zaj.ajax.post(request+'?'+dojo.formToQuery(this.element), result);
		}
});

/**
 * Enable mootools-style referencing of $zaj() object.
 **/
var $ = function(id){ var element = dojo.byId(id); dojo.mixin(element, {
	// my $zaj method
		$zaj: function(){ return new Mozajik.Element(element); },
	/**
	 * Mootools compatibility
	 **/
	 	toQueryString: function(){ if(element.tagName != "form") zaj.error("Only FORM tags supported in Dojo version.", element); else return dojo.formToQuery(element); },
	 	addClass: function(className){ return dojo.addClass(element, className); },
	 	removeClass: function(className){ return dojo.removeClass(element, className); },
	 	toggleClass: function(className){ return dojo.toggleClass(element, className); },
	 	dissolve: function(){ dojo.style(element, 'display', 'none'); },
	 	reveal: function(){ dojo.style(element, 'display', 'block');  },
	 	
	});
	return element;
}

	
/**
 * Check if Mozajik variables were properly loaded
 **/
	dojo.ready(function(){ if(zaj.baseurl == '') zaj.log('Mozajik JS layer loaded, but not initialized. Requests will not work properly!'); });
