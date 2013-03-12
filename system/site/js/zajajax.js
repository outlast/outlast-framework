//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2009 – ajax class
//////////////////////////////////////////////////////////////////////////////
// class: zajAjax
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 3.1
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// this should work Firefox 1.5+, IE 5.5+, Opera 7+. send bugs to above email.
// check for new versions, go to hontalan.com.
//////////////////////////////////////////////////////////////////////////////
// version history.
// requires mootools, zaj.js (for logging), zajpopup.js (for certain functions)
// - 3.0 - initial release, mootools rewrite
// - 3.1 - added element extensions
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////
/* usage: 
1. zajajax.get(request, [result]);
	Usage
	- sends a GET request to server via AJAX
	Parameters
	- request: can be a query string or a form id (the contents of which will be put in a query string)
	- result (optional): can be a div id, a process function, or a redirect url
2. zajajax.post(request, [result]);
	Usage
	- sends a POST request to server via AJAX
	Parameters
	- request: can be a query string or a form id (the contents of which will be put in a query string)
	- result (optional): can be a div id, a process function, or a redirect url

3. zajajax.secure(request, [result]);
	Usage:
	- sends a POST request via a secure https connection
	TODO: implement this!

	Notes:
		When [result] is given as a URL (has .php or .html in it) the server must return "ok". If not, the
		script will send a popup window with the returned string. This is used for form validation actions.

	Events:
		onComplete - called after a successful request has completed
		onRequest - called when a request is first made
		onError - called when a request fail
*/

var zajAjax = new Class({
	Implements: [Options, Events],
	
	options: {
		loadOverlay: true,
		logActions: true,
		popupOnRequestError: 'hálózati hiba. próbáld újra!'
	},
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(options){
			// set default options
				this.setOptions(options);
			return true;
		},

	//////////////////////////////////////////////////////////////////////////////
	// send get request
		get: function(request,result){
			this.send('get',request,result);		
		},

	//////////////////////////////////////////////////////////////////////////////
	// send post request
		post: function(request, result){
			this.send('post',request,result);
		},

	//////////////////////////////////////////////////////////////////////////////
	// send post request
		send: function(method, request, result){
			// init variables
				var request_url = "";
				var request_data = "";
				// add the baseurl if needed
					if(request.substr(0,2) != '//' && request.substr(4, 3) != "://" && request.substr(5, 3) != "://") request = zajlib.baseurl+'/'+request;
				// request is a div id or request is a url
					if($defined($(request))) request_url = $(request).getProperty('action')+'?'+$(request).toQueryString();
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


	//////////////////////////////////////////////////////////////////////////////
	// handle response
		process: function(result, responseText){
			// now what is result?
				// is result even defined?
					if(!$defined(result) || !$chk(result)){
						zajlog.log('NOTICE: no result container specified.');
						return true;
					} 
				// is result a function?
					if(typeof result == 'function'){
						//zajlog.log('INFORMATION: results container is a function.');
						result(responseText);
						this.fireEvent('complete');
						return true;
					}
				// is it a div id?
					if($defined($(result)) && $chk($(result))){
						//zajlog.log('INFORMATION: results container is a div.');
						$(result).set('html', responseText);
						this.fireEvent('complete');
						return true;
					}
				// is result a URL?
					//zajlog.log('INFORMATION: results container is a url.');
					if(responseText == "ok"){
						// add the baseurl if needed
							if(result.substr(0, 2) != '//' && result.substr(4, 3) != "://" && result.substr(5, 3) != "://") result = zajlib.baseurl+'/'+result;
						// now send
							window.location = result;
					}
					else zajpopup.warning(responseText);
					return true;
		},

	//////////////////////////////////////////////////////////////////////////////
	// handle error
		error: function(xhr){
			// should i display the warning?
				if(this.options.popupOnRequestError != '') zajpopup.warning(this.options.popupOnRequestError);
				else zajlog.log('the ajax request failed!')
			// fire the error event
				this.fireEvent('error');
		},


	//////////////////////////////////////////////////////////////////////////////
	// backwards compatibility
		addGetRequest: function(url, resultdiv, processFunction){
			if($defined(processFunction) && typeof processFunction == "function") this.get(url,processFunction);
			else this.get(url,resultdiv);
		},
		addPostRequest: function(url, formidORresultdiv, resultdivORprocessfunction, processFunction){
			// mode one: zajajax.addPostRequest('thephpfile.php', '[formid]', '', processFunction);
			if($defined(processFunction) && typeof processFunction == "function"){
				// request url and result
					var request = url;
					var result = processFunction;
				// is it only a query string or also formid
					if($defined(formidORresultdiv)) request = url+'?'+$(formidORresultdiv).toQueryString();
			}
			// mode two: zajajax.addPostRequest('thephpfile.php', 'resultdivOrProcessFunction', processFunction);
			else{
				// request url and result
					var request = url;
					var result = formidORresultdiv;
				// unless processFunction is defined - then it is the result
					if($defined(resultdivORprocessfunction) && typeof resultdivORprocessfunction == "function"){
						result = resultdivORprocessfunction;
					}
			}
			// now send request
			return this.post(request,result);
		}
		
});

/* now add helpers for elements */
	Element.implement({
		/* gets or posts the contents of element to url */
		ajaxpost: function(url, result){	
			zajajax.post(url+'?'+this.toQueryString(), result);
		},
		ajaxget: function(url, result){	
			zajajax.get(url+'?'+this.toQueryString(), result);
		}
	});



// now create the object!
	var zajajax = new zajAjax();

