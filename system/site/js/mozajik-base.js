/**
 * This is the basic Mozajik JS layer class. The JS layer has three sections, 'base' (basic stuff for sending requests, logging, etc.), 'tool' (tabs, forms, etc.),
 *	and 'ui' (user interface elements)
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 **/

// Create a new class which will contain the sections
	var Mozajik = new Class({baseurl:'',fullrequest:'',fullurl:'',app:'',mode:'',debug_mode:false,protocol:'http'});
	var zaj = new Mozajik();

/**
 * Backwards-compatible functions implemented temporarily. The implementation is imperfect, but compatible - on purpose! Depricated, remove from release!
 **/
	var $chk = function(obj){
    	zaj.log('Depricated method chk or defined used!');
    	return typeOf(obj);
	};
	var $defined = $chk;
	// Add typeOf() if undefined
	//if(typeof typeOf == 'undefined'){
	
	//}

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
	 **/
		log: function(message, type){
			if(typeOf(console) == 'object') console.log(message);
			return true;
		},

	/**
	 * Redirect to a page relative to baseurl.
	 * @param relative_url The URL relative to baseurl.
	 **/
		redirect: function(relative_url){
			window.location = zaj.baseurl+relative_url;
			return true;
		},
		
	/**
	 * Reload the current url.
	 **/
		reload: function(){
			window.location = window.location;
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
	 **/
		get: function(request,result){
			this.send('get',request,result);		
		},

	/**
	 * Creates a POST request and sends it to the specified URL (relative to baseurl), which is then routed to the appropriate controller
	 **/
		post: function(request, result){
			this.send('post',request,result);
		},

	/**
	 * Sends the actual request via method and returns the result to a div, a function, or url
	 **/
		send: function(method, request, result){
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
							if(request_url_parts.length > 2) zajlib.log('error: invalid query string! (more than one ? found!)');
							request_url = request_url_parts[0];
							request_data = request_url_parts[1];
					}
					// somehow use the fragment section to transfer data related to the current request
				
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
				new_request.addEvent('success',function(responseTree, responseElements, responseHTML, responseJavaScript){self.process(result, responseHTML);});
				new_request.addEvent('failure',function(xhr){self.error(xhr);});
			// fire request event
				this.fireEvent('request');
			// send request
				new_request.send();
		},


	/**
	 * Handle response and pass to a div, a function, or url
	 **/
		process: function(result, responseText){
			// now what is result?
				// is result even defined?
					if(!typeOf(result)){
						zaj.notice('No result container specified.');
						return true;
					} 
				// is result a function?
					if(typeOf(result) == 'function'){
						//zaj.log('INFORMATION: results container is a function.');
						result(responseText);
						this.fireEvent('complete');
						return true;
					}
				// is it a div id?
					if(typeOf($(result)) == 'element'){
						//zaj.log('INFORMATION: results container is a div.');
						$(result).set('html', responseText);
						this.fireEvent('complete');
						return true;
					}
				// is result a URL?
					//zaj.log('INFORMATION: results container is a url.');
					if(responseText == "ok"){
						// add the baseurl if needed
							if(result.substr(0, 2) != '//' && result.substr(4, 3) != "://" && result.substr(5, 3) != "://") result = zaj.baseurl+'/'+result;
						// now send
							window.location = result;
					}
					else zaj.alert(responseText);
					return true;
		},

	/**
	 * Handle the error
	 **/
		error: function(xhr){
			// should i display the warning?
				if(this.options.popupOnRequestError != '') zaj.alert(this.options.popupOnRequestError);
				else zaj.warning('The ajax request failed.');
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

/**
 * Implement shortcuts from zaj to base
 **/
Mozajik.implement({
	/**
	 * Log messages.
	 **/
	log: function(message, type){
		return zaj.base.log(message, type);
	},
	error: function(message){
		return this.log(message, 'error');
	},
	warning: function(message){
		return this.log(message, 'warning');
	},
	notice: function(message){
		return this.log(message, 'notice');
	},
	/**
	 * Custom alerts, confirms, prompts
	 **/
	alert: function(message){
		return alert(message);
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
	redirect: zaj.base.redirect,
	reload: zaj.base.reload,
	refresh: zaj.base.reload
});

/**
 * Implement Element shortcuts
 **/
MozajikBaseElement.implement({
	/**
	 * Ajax requests
	 **/
		get: function(request, result){
			zaj.ajax.get(request+'?'+this.element.toQueryString(), result);
		},
		post: function(request, result){
			zaj.ajax.post(request+'?'+this.element.toQueryString(), result);
		}
});

/**
 * Check if Mozajik was properly loaded
 **/
window.addEvent('domready', function(){ if(zaj.baseurl == '') zaj.log('Mozajik JS layer loaded, but not properly initialized!'); });
