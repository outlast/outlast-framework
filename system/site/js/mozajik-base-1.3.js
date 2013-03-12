/**
 * This is the basic Mozajik JS layer class. The JS layer has three sections, 'base' (basic stuff for sending requests, logging, etc.), 'tool' (tabs, forms, etc.),
 *	and 'ui' (user interface elements)
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.3
 * 
 * @changes 1.3 Now supports pushstate, but ajax methods' parameter order has changed: bind is now the fourth param, the third is the new url.
 **/

// Create a new class which will contain the sections
	var Mozajik = new Class({baseurl:'',fullrequest:'',fullurl:'',app:'',mode:'',debug_mode:false,protocol:'http',jslib:'mootools',jslibver:1.3});
	var zaj = new Mozajik();

// Pushstate support (from pjax)
	zaj.pushstate = window.history && window.history.pushState && window.history.replaceState
					// pushState isn't reliable on iOS until 5.
					&& !navigator.userAgent.match(/((iPod|iPhone|iPad).+\bOS\s+[1-4]|WebApps\/.+CFNetwork)/)

/**
 * Backwards-compatible functions implemented temporarily. The implementation is imperfect, but compatible - on purpose! Depricated, remove from release!
 **/
	var $chk = function(obj){
    	zaj.log('Depricated method chk or defined used!');
    	if(typeOf(obj) == 'element') return true;
    	else return false;
	};
	var $defined = $chk;	

/**
 * Enable JS error logging.
 * @todo Do not send request if js logging is not enabled!
 **/	
window.onerror=function(message, url, line){
	// determine my relative url
		var my_uri = new URI();
		var error_url = zaj.baseurl+'system/javascript/error/';
	// send error to console
		 zaj.notice('Logged JS error: '+message+' on line '+line+' of '+url);
 	// send an ajax request to log the error if it is a modern, supported browser
		var r = new Request.JSON({ 'url' : error_url, 'method':'get','data': 'js=error&message='+message+'&url='+url+'&location='+zaj.fullrequest+'&line='+line, link: 'chain' });
		r.send();
}

/**
 * Base class contains the most important and often used features of the Mozajik JS layer: ajax requests, history management, logging, etc.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 **/
var MozajikBase = new Class({
	Implements: [Options, Events],
	
	/**
	 * Default options
	 **/
		options: {			
		},

	/**
	 * A shortcut to the ajax class.
	 **/
		ajax: false,
	
	/**
	 * A list of ready functions to be executed upon successful load / ajax load.
	 **/
	 	ready_functions: new Array(),	 
	
	/**
	 * Creates the MozajikBase class and sets its options.
	 * @constructor
	 **/
		initialize: function(options){
			// set default options
				this.setOptions(options);

			// create my objects
				this.ajax = new MozajikBaseAjax();
				this.history = new MozajikBaseHistory();

		},
		
	/**
	 * Logs a message to the console.
	 * @param string message The message to log.
	 * @param string type Can be notice, warning, or error
	 * @param string context The context is any other element or object which will be logged.
	 **/
		log: function(message, type, context){
			if(typeof console != 'undefined' && typeOf(console) == 'object'){
				if(typeof context == 'undefined') context = '';
				switch(type){
					case 'error': return console.error(message, context);
					case 'warn':
					case 'warning': return console.warn(message, context);
					case 'info':
					case 'notice': return console.info(message, context);
					default: console.log(message, context);
				}
			}
			return true;
		},

	// Go back
		back: function(){ history.back(); },

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
	 * Redirect to a page relative to baseurl or absolute.
	 * @param relative_or_absolute_url The URL relative to baseurl. If it starts with // or http or https it is considered an absolute url
	 **/
		redirect: function(relative_or_absolute_url){
			// Is it relative?
			if(relative_or_absolute_url.substr(0,2) != '//' && relative_or_absolute_url.substr(4, 3) != "://" && relative_or_absolute_url.substr(5, 3) != "://") window.location = zaj.baseurl+relative_or_absolute_url;
			else window.location = relative_or_absolute_url;
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
	 		window.addEvent('domready', func);
	 	},

	/**
	 * A function which opens up a new window with the specified properties
	 * @param url The url of the window
	 * @param width The width in pixels.
	 * @param height The height in pixels
	 * @param options All other options as an object.
	 **/
		window: function(url, width, height, options){
			// Default options!
				if(typeof width == 'undefined') width = 500;
				if(typeof height == 'undefined') height = 300;
			// TODO: implement options
			return window.open (url,"mywindow","status=0,toolbar=0,location=0,menubar=0,resizable=1,scrollbars=1,height="+height+",width="+width);
		},
	/**
	 * URLencodes a string so that it can safely be submitted in a GET query.
	 * @param url The url to encode.
	 * @return The url in encoded form.
	 **/
	 	urlencode: function(url){
	 		return encodeURIComponent(url);
	 	}
		
});

/**
 * The Ajax class handles requests back and forth between the JS layer and Mozajik controllers.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 **/
var MozajikBaseAjax = new Class({
	Implements: [Options, Events],
	
	/**
	 * Default options
	 **/
		options: {
			loadOverlay: true,
			logActions: true,
			popupOnRequestError: false
		},
	
	/**
	 * Creates the MozajikBaseAjax class and sets its options.
	 * @constructor
	 **/
		initialize: function(options){
			// set default options
				this.setOptions(options);
			return true;
		},

	/**
	 * Creates a GET request and sends it to the specified URL (relative to baseurl), which is then routed to the appropriate controller
	 * @param string request The request url relative to baseurl.
	 * @param function|element|string result The result can be a function, an element, or a url.
	 * @param string|object pushstate If it is just a string, it will be the url for the pushState. If it is an object, you can specify all three params of pushState: data, title, url
	 * @param object bind Used to bind 'this' within the function. Only matters if result is a function.
	 **/
		get: function(request,result,pushstate,bind){
			this.send('get',request,result,pushstate,bind);
		},

	/**
	 * Creates a POST request and sends it to the specified URL (relative to baseurl), which is then routed to the appropriate controller
	 * @param string request The request url relative to baseurl.
	 * @param function|element|string result The result can be a function, an element, or a url.
	 * @param string|object pushstate If it is just a string, it will be the url for the pushState. If it is an object, you can specify all three params of pushState: data, title, url
	 * @param object bind Used to bind 'this' within the function. Only matters if result is a function.
	 **/
		post: function(request,result,pushstate,bind){
			this.send('post',request,result,pushstate,bind);
		},

	/**
	 * Sends a file via an ajax POST request. For now it only supports 'file' types and requires latest Gecko or WebKit to work.
	 * @todo Make Moo-compatible once moo allows file as data.
	 **/
		postfile: function(fileinput, request, result){
			// add the baseurl if needed
				if(request.substr(0,2) != '//' && request.substr(4, 3) != "://" && request.substr(5, 3) != "://") request = zaj.baseurl+'/'+request;
			// grab the file object
				var file = $(fileinput).files[0];
			// last request save
				this.last_request = request;
			// create a request
				var xhr = new XMLHttpRequest;
				var self = this;
				upload = xhr.upload;
			// add events
				//upload.onload = function(){ };
				xhr.onreadystatechange = function(){ if (xhr.readyState === 4){ self.process(result, xhr.responseText); } };
				xhr.upload.addEventListener('progress',function(progress){ self.fireEvent('progress', progress); } );
			// send request
				xhr.open("post", request, true);
				xhr.send(file);
		},

	/**
	 * Sends the actual request via method and returns the result to a div, a function, or url
	 **/
		send: function(method, request, result, pushstate, bind){
			// init variables
				var request_url = "";
				var request_data = "";
				// add the baseurl if needed
					if(request.substr(0,2) != '//' && request.substr(4, 3) != "://" && request.substr(5, 3) != "://") request = zaj.baseurl+'/'+request;
				// request is a div id or request is a url
					if(typeOf(request) == 'element') request_url = $(request).getProperty('action')+'?'+$(request).toQueryString();
					else request_url = request;
				// set request data if post
					if(method == 'post'){
						// construct request_data
							//request_data = url_obj.get('query');
						// construct the request url (without query string)
							request_url_parts = request_url.split('?');
							if(request_url_parts.length > 2) zaj.notice('Invalid query string! (more than one ? found!) '+request_url);
							request_url = request_url_parts[0];
							request_data = request_url_parts[1];
					}
					// somehow use the fragment section to transfer data related to the current request
			// last request save
				this.last_request = request_url;
			// create a new request object
				var new_request = new Request.HTML({
					method: method,
					url: request_url,
					data: request_data,
					link: 'chain',
					evalScripts: true,
					evalResponse: true,
					noCache: true
				});
			// set my process function
				var self = this;
				new_request.addEvent('success',function(responseTree, responseElements, responseHTML, responseJavaScript){ self.process(result, responseHTML, pushstate, bind); });
				new_request.addEvent('failure',function(xhr){self.error(xhr);});
			// fire request event
				this.fireEvent('request');
			// send request
				new_request.send();
		},


	/**
	 * Handle response and pass to a div, a function, or url
	 **/
		process: function(result, responseText, pushstate, bind){
			// is pushstate used now
				var psused = zaj.pushstate && (typeOf(pushstate) == 'string' || typeOf(pushstate) == 'object');
				var psdata = false;
				if(typeOf(pushstate) == 'object' && pushstate.data) psdata = pushstate.data;
			// now what is result?
				// is result even defined?
					if(!typeOf(result)){
						zaj.notice('No result container specified.');
						return true;
					} 
				// is result a function?
					else if(typeOf(result) == 'function'){
						// if bind requested
							if(bind != undefined && bind) result = result.bind(bind);
						// call result
							result(responseText.trim());	
						// we are done!
							this.fireEvent('complete');
					}
				// is it a div id?
					else if(typeOf(result) == 'elements' || typeOf($(result)) == 'element'){
						// set r
							if(typeOf(result) == 'elements') r = result;
							else r = $(result);
						// get old div contents
							if(psused && !psdata) psdata = r.get('html');
						// set div contents
							r.set('html', responseText);
						// we are done!
							this.fireEvent('complete');
					}
				// is result a URL?
					else if(typeOf(result) == 'string'){
						if(responseText.trim() == "ok"){
							// add the baseurl if needed
								if(result.substr(0, 2) != '//' && result.substr(4, 3) != "://" && result.substr(5, 3) != "://") result = zaj.baseurl+'/'+result;
							// now send
								window.location = result;
						}
						else zaj.alert(responseText);
					}
				// pushState actions
					if(psused){
						// string mode - convert to object
							if(typeOf(pushstate) == 'string') pushstate = {'data': psdata, 'title':"", 'url': pushstate};
						// now set everything and fire event
							pushstate = Object.merge({'title': false}, pushstate);	// default title is false
							if(pushstate.url) window.history.pushState(psdata, pushstate.title, pushstate.url);
							if(pushstate.title) document.title = pushstate.title;
						this.fireEvent('pushState', pushstate);							
					}
			return true;
		},

	/**
	 * Parses the page for and automatically sets up pushstate links. A pushstate link is one with data-container attribute.
	 * Format: <a href="{{baseurl}}url/to/content" data-container="#divid" data-block="template_block_name" data-title="My Page Title">My Link</a>
	 **/
		pushstate: function(){
			// Is pushstate supported?
				if(!zaj.pushstate) return false;
			// Run through all my data-container links				
				$$('a[data-container]').each(function(el){
					// mark this element with a class (TODO: can we select the a[] above negated so we can avoid this check?)
					if(!el.hasClass('mozajik-pushstate-enabled')){
						// parse my selector
							var sel = el.getAttribute('data-container');
							if(sel.substring(0, 1) == '#') sel = sel.substr(1);
							if(typeOf($(sel)) != 'element') return zaj.error("PushState error: Selector '"+sel+"' needs to be a single element (use an id).");
						// remove href
							var href = el.getAttribute('href');
							var req = href;
							if(typeOf(req) != 'string') return zaj.error("PushState error: the href parameter is required for the element.", el);
							el.removeAttribute('href');
							el.setAttribute('data-href', href);
							el.setStyle('cursor', 'pointer');
						// generate query string
							var qstr = 'zaj_pushstate_mode=true&zaj_pushstate_block='+el.getAttribute('data-block');
							if(req.contains('?')) req+='&'+qstr;
							else req+='?'+qstr;
						// add event
							el.addEvent('click', function(){
								zaj.ajax.get(req, $(sel), {'data': $(sel).get('html'), 'title': el.getAttribute('data-title'), 'url': href });
							});
						// mark this element with a class (TODO: can we select the a[] above negated so we can avoid this check?)
							el.addClass('mozajik-pushstate-enabled');
					}					
				});
		},

	/**
	 * Handle the error
	 **/
		error: function(xhr){
			// should i display the warning?
				if(this.options.popupOnRequestError != '') zaj.alert(this.options.popupOnRequestError);
				else zaj.warning('The ajax request failed: '+this.last_request);
			// fire the error event
				this.fireEvent('error');
		}
});


/**
 * The History class is used to manage back and forward buttons when using ajax requests.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 * @todo Implement this!
 **/
var MozajikBaseHistory = new Class({
	Implements: [Options, Events],
	
	/**
	 * Default options
	 **/
		options: {			
		},
	
	/**
	 * Creates the MozajikBaseHistory class and sets its options.
	 * @constructor
	 **/
		initialize: function(options){
			// set default options
				this.setOptions(options);
		}
});


/**
 * The Element class allows the extension of mootools elements via the zaj object.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 **/
var MozajikBaseElement = new Class({
	Implements: [Events],
	/**
	 * Creates the MozajikBaseElement class and sets its options.
	 * @constructor
	 **/
		initialize: function(el){this.element = el;}	
});

/**
 * Class Loader allows you to load javascript, css, or image files async
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 */
Mozajik.Loader = new Class({
	Implements: [Events],
	/**
	 * Events:
	 * - load: fired after all the requested objects have been loaded (css not counted).
	 **/

	/**
	 * Creates a new Loader object
	 **/
		initialize: function(element, options){
			// set default options
				this.assets = new Array();
				this.added = 0;
				this.loaded = 0;
				this.loaded_assets = new Array();
		},

	/**
	 * Add an image, javascript, or css to the queue
	 * @param string url Image url relative to base.
	 **/
	 	image: function(url, id){ this.assets.push({'type':'image','url':url, 'id':id}); this.added++; },
	 	css: function(url, id){ this.assets.push({'type':'css','url':url, 'id':id}); },
	 	javascript: function(url, id){ this.assets.push({'type':'javascript','url':url, 'id':id});  this.added++; },
	 	//js: this.javascript,
	 
	/**
	 * Starts loading all of my requested assets
	 **/
	 	start: function(){
	 		// is my asset empty?
	 			if(this.assets.length <= 0) return false;
	 		// get my first asset
		 		var asset = this.assets.shift();
		 	// check to see if asset is not yet loaded
				var self = this;
		 		if(this.loaded_assets.indexOf(asset.url) < 0){
				 	// load me
				 		switch(asset.type){
				 			case 'image':		zaj.notice('Loading image '+zaj.baseurl+asset.url);
				 								Asset.image(zaj.baseurl+asset.url, {'id': asset.id, 'events': { 'load': function(){ self.loaded++; zaj.notice('Loaded '+asset.url); if(self.loaded >= self.added) self.fireEvent('load'); } } });
				 								break;
				 			case 'css':			zaj.notice('Loading css '+zaj.baseurl+asset.url);
				 								Asset.css(zaj.baseurl+asset.url, {'id': asset.id });
				 								break;
				 			case 'javascript':	zaj.notice('Loading javascript '+zaj.baseurl+asset.url);
				 								Asset.javascript(zaj.baseurl+asset.url, {'id': asset.id, 'events': { 'load': function(){ self.loaded++; zaj.notice('Loaded '+asset.url); if(self.loaded >= self.added) self.fireEvent('load'); } } });
				 								break;		 			
				 		}
		 		}
		 		else{
		 			// remove one from added (if not css)
			 			if(asset.type != 'css') self.added--;
			 		// fire load event
			 			if(self.loaded >= self.added){
			 				self.fireEvent('load');
			 				zaj.notice('Skipped loads, but now firing load event!');	
			 			}
		 			// give notice of skip
			 			zaj.notice('Skipping '+zaj.baseurl+asset.url+', already loaded!');
		 		}
		 	// add asset to loaded assets
		 		this.loaded_assets.push(asset.url);
	 		// recursive call
	 			return this.start();
	 	}
});
zaj.loader = new Mozajik.Loader();


/**
 * This is the actual instance of the base class.
 **/
zaj.base = new MozajikBase();

/**
 * Create the zaj layer of Element extensions
 **/
Element.implement({
	/* Implement the zaj object */
	$zaj: function(){ return new MozajikBaseElement(this); }
});
Elements.implement({
	/* Implement the zaj object - only the first element is supported for these! */
	$zaj: function(){
		var elements = this;
		return new MozajikBaseElement(elements[0]);
	}
});

/**
 * Implement shortcuts from zaj to base
 **/
Mozajik.implement({
	/**
	 * Log messages.
	 **/
	log: function(message, type, context){
		return zaj.base.log(message, type, context);
	},
	error: function(message, context){
		return this.log(message, 'error', context);
	},
	warning: function(message, context){
		return this.log(message, 'warning', context);
	},
	notice: function(message, context){
		return this.log(message, 'notice', context);
	},
	/**
	 * Custom alerts, confirms, prompts
	 **/
	alert: function(message, urlORfunction){
		alert(message);
		// if the passed param is a function, then return confirmation as its param
		if(typeof urlORfunction == 'function') urlORfunction();
		else if(typeof urlORfunction == 'string') zaj.redirect(urlORfunction);
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
	 * Shortcuts to base
	 **/
	ajax: zaj.base.ajax,
	ready: zaj.base.ready,
	redirect: zaj.base.redirect,
	reload: zaj.base.reload,
	refresh: zaj.base.reload,
	window: zaj.base.window,
	urlencode: zaj.base.urlencode,
	back: zaj.base.back,
	open: zaj.base.window
});

/**
 * Implement Element shortcuts
 **/
MozajikBaseElement.implement({
	/**
	 * Ajax requests
	 **/
		get: function(request, result){
			if(this.element.toQueryString() == "") zaj.error("Request error: Your query string is empty! ("+request+")");
			else zaj.ajax.get(request+'?'+this.element.toQueryString(), result);
		},
		post: function(request, result){
			if(this.element.toQueryString() == "") zaj.error("Request error: Your query string is empty! ("+request+")");
			else zaj.ajax.post(request+'?'+this.element.toQueryString(), result);
		}
});

/**
 * Check if Mozajik was properly loaded
 **/
window.addEvent('domready', function(){
	if(zaj.baseurl == '') zaj.warning('Mozajik JS layer loaded, but not initialized. Requests will not work properly!');
	else{
		// init stuff
			if(zaj.pushstate){
				// pushstate support
					zaj.base.ajax.pushstate();
				// ajax pushstate support
					zaj.ajax.addEvent('complete', function(){ zaj.base.ajax.pushstate(); });
			}
	}		
});
