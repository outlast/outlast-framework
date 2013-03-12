/**
 * This is the basic Mozajik JS layer class for jQuery. The JS layer has three sections, 'base' (basic stuff for sending requests, logging, etc.), 'tool' (tabs, forms, etc.),
 *	and 'ui' (user interface elements)
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.0
 * 
 * @changes 1.0 Now supports pushstate, but ajax methods' parameter order has changed: bind is now the fourth param, the third is the new url.
 **/
 
// Create a new class which will contain the sections
	var zaj = {baseurl:'',fullrequest:'',fullurl:'',app:'',mode:'',debug_mode:false,protocol:'http',jslib:'jquery',jslibver:1.7};
	//var zaj = new Mozajik();


// Detect various fixed features (pushstate)
	// Pushstate support (from pjax)
	zaj.pushstate = window.history && window.history.pushState && window.history.replaceState
					// pushState isn't reliable on iOS until 5.
					&& !navigator.userAgent.match(/((iPod|iPhone|iPad).+\bOS\s+[1-4]|WebApps\/.+CFNetwork)/)
// Detect various dynamically loaded features (bootstrap, facebook, etc.)
	$(document).ready(function(){
		zaj.bootstrap = (typeof $().modal == 'function');
		zaj.facebook = (typeof FB == 'object');
	});

	/**
	 * Backwards compatibility for mootools
	 **/
	 var $$ = function(e){
	 	zaj.notice('Notice: Used $$ in jQuery.');
	 	return $(e);
	 };

/**
 * Mozajik zaj object implementations.
 **/
	/**
	 * Layer for onready functions.
	 **/
 	zaj.ready = function(func){ $(document).ready(func); };

	/**
	 * Logs a message to the console. Ingored if console not available.
	 * @param message The message to log.
	 * @param type Can be notice, warning, or error
	 * @param context The context is any other element or object which will be logged.
	 * @return bool Returns true or console.log.
	 **/
	zaj.log = function(message, type, context){
		if(typeof console != 'undefined' && typeof(console) == 'object'){
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
	};
	zaj.error = function(message, context){
		return zaj.log(message, 'error', context);
	};
	zaj.warning = function(message, context){
		return zaj.log(message, 'warning', context);
	};
	zaj.notice = function(message, context){
		return zaj.log(message, 'notice', context);
	};

	// Go back!
	zaj.back = function(){ history.back(); };


	/**
	 * Custom alerts, confirms, prompts
	 **/
	zaj.alert = function(message, urlORfunction, buttonText){
		if(zaj.bootstrap){
			// Create modal if not yet available
				if($('#zaj_bootstrap_modal').length <= 0){
					$('body').append('<div id="zaj_bootstrap_modal" class="modal hide fade"><div class="modal-body"></div><div class="modal-footer"><a data-dismiss="modal" class="modal-button btn btn-primary">Ok</a></div></div>');
				}
			// Reset and init button
				// Set action
				$('#zaj_bootstrap_modal a.modal-button').unbind('click');
				if(typeof urlORfunction == 'function') $('#zaj_bootstrap_modal a.modal-button').click(urlORfunction);
				else if(typeof urlORfunction == 'string') $('#zaj_bootstrap_modal a.modal-button').click(function(){ zaj.redirect(urlORfunction); });
				else $('#zaj_bootstrap_modal a.modal-button').click(function(){ $('#zaj_bootstrap_modal').modal('hide'); });
				// Set text (if needed)
				if(typeof buttonText == 'string') $('#zaj_bootstrap_modal a.modal-button').html(buttonText);
			// Set body and show it
				$('#zaj_bootstrap_modal div.modal-body').html(message);
				$('#zaj_bootstrap_modal').modal({backdrop: 'static', keyboard: false})
			// Is facebook enabled and in canvas? (move this to fb js)
				if(zaj.facebook){
					FB.Canvas.getPageInfo(function(e){
						$('#zaj_bootstrap_modal').css({top: 300 + e.scrollTop});
					});
				}
		}
		else{
			alert(message);
			if(typeof urlORfunction == 'function') urlORfunction();
			else if(typeof urlORfunction == 'string') zaj.redirect(urlORfunction);
		}
	};
	zaj.confirm = function(message, urlORfunction){
		// if the passed param is a function, then return confirmation as its param
		if(typeof urlORfunction == 'function'){
			var result = confirm(message);
			urlORfunction(result);
		}
		// if the passed param is a url, redirect if confirm
		else{
			if(confirm(message)) window.location=zaj.baseurl+urlORfunction;
		}
	};
	zaj.prompt = function(message){
		return prompt(message);
	};
	
	/**
	 * Reload the current url.
	 **/
		zaj.reload = function(){
			window.location.reload(false);
		};
		zaj.refresh = zaj.reload;
			
	/**
	 * Redirect to a page relative to baseurl or absolute.
	 * @param relative_or_absolute_url The URL relative to baseurl. If it starts with // or http or https it is considered an absolute url
	 **/
		zaj.redirect = function(relative_or_absolute_url){
			if(typeof relative_or_absolute_url == 'undefined' || !relative_or_absolute_url) return false;
			// Is it relative?
			if(relative_or_absolute_url.substr(0,2) != '//' && relative_or_absolute_url.substr(4, 3) != "://" && relative_or_absolute_url.substr(5, 3) != "://") window.location = zaj.baseurl+relative_or_absolute_url;
			else window.location = relative_or_absolute_url;
			return true;
		};



	/**
	 * Ajax methods.
	 **/
		zaj.ajax = {};
			zaj.ajax.get = function(request,result){
				zaj.ajax.request('get', request, result);
			};
			zaj.ajax.post = function(request,result){
				zaj.ajax.request('post', request, result);
			};
			zaj.ajax.request = function(mode,request,result){
				// Figure out query string
					if(mode == 'post'){
						var rdata = request.split('?');
						if(rdata.length > 2) zaj.warning("Found multiple question marks in query string!");
						request = rdata[0];
						datarequest = rdata[1];
					}
					else datarequest = '';
				// Now send request and call callback function, set callback element, or alert
				$.ajax(zaj.baseurl+request, {
					success: function(data, textStatus, jqXHR){
						if(typeof result == "function") result(data);
						else if(typeof result == "object") $(result).html(data);
						else{
							if(data == 'ok') zaj.redirect(result);
							else zaj.alert(data);
						}
					},
					complete: function(jqXHR, textStatus){
						if(textStatus != "success") console.log("Ajax request failed with status ".textStatus);
					},
					data: datarequest,
					dataType: 'html',
					type: mode
				});				
			};

		/**
		 * Class Search creates a search box which sends ajax requests at specified intervals to a given url.
		 * @author Aron Budinszky /aron@mozajik.org/
		 * @todo Add placeholder text
		 * @version 3.0
		 */
		zaj.search = {
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
						this.options = $.extend(this.options, options);
					// register events
						this.timer = false;
						this.element = $(element);
						var self = this;
						this.element.keyup(function(){
							// reset earlier timer
								if(self.timer){ clearTimeout(self.timer); }
							// now set a new timer
								self.timer = setTimeout(function(){ self.send(); }, self.options.delay);
						});
						this.element.blur(function(){
							self.send();
						});
					return true;
				},
			
			/**
			 * Sends the query to the set url and processes.
			 **/
				send: function(){
					// if the element value is empty, do not do anything
						if(!this.options.allow_empty_query && !this.element.val()) return false;
					// if url not set, just do callback immediately!
					if(this.options.url){			
						// append element value to url
							var url = this.options.url;
							if(this.options.url.indexOf('?') >= 0) url += '&query='+this.element.val();
							else url += '?query='+this.element.val();
							url += '&mozajik-tool-search=true';
						// check if the current query is like last query
							if(this.last_query == this.element.val()) return false;
							else this.last_query = this.element.val();
						// now send via the appropriate method
							console.log(url);
							if(this.options.method == 'get') zaj.ajax.get(url, this.options.callback, {'data': this.options.pushstate_data, 'title': this.options.pushstate_title, 'url': this.options.pushstate_url}, this.options.callback_bind);
							else zaj.ajax.post(url, this.options.callback, {'data': this.options.pushstate_data, 'title': this.options.pushstate_title, 'url': this.options.pushstate_url}, this.options.callback_bind);
					}
					else{
						this.options.callback(this.element.val());
					}
				}
			};
	/**
	 * A function which opens up a new window with the specified properties
	 * @param url The url of the window
	 * @param width The width in pixels.
	 * @param height The height in pixels
	 * @param options All other options as an object.
	 **/
		zaj.window = function(url, width, height, options){
			// Default options!
				if(typeof width == 'undefined') width = 500;
				if(typeof height == 'undefined') height = 300;
			// TODO: implement options
			return window.open (url,"mywindow","status=0,toolbar=0,location=0,menubar=0,resizable=1,scrollbars=1,height="+height+",width="+width);
		};
		zaj.open = zaj.window;

	/**
	 * URLencodes a string so that it can safely be submitted in a GET query.
	 * @param url The url to encode.
	 * @return The url in encoded form.
	 **/
	 	zaj.urlencode = function(url){
	 		return encodeURIComponent(url);
	 	};	 

	/**
	 * A function which enables sortable features on a list of items. Requires jquery-ui sortable feature.
	 * @param target The items to sort. Each item must have an data-sortable field corresponding to the id of item.
	 * @param url The url which will handle this sortable request.
	 **/
		zaj.sortable = function(target, url){
			// Make sortable
			$(target).sortable({
			    start: function(event, ui) {
			    	ui.item.addClass('sortableinprogress');
			    },
			    stop: function(event, ui) {
			    	ui.item.removeClass('sortableinprogress');
					// Build array
						var my_array = [];
						$(target).children().each(function(){
							var my_id = $(this).attr('data-sortable');
							if(!my_id) zaj.error("Cannot sort: data-sortable not set!");
							else my_array.push(my_id);
						});
						zaj.ajax.post(url+'?reorder='+JSON.stringify(my_array));
			    }
			});
		};

	/**
	 * Now extend the jQuery object.
	 **/
	(function($){
	   $.fn.$zaj = $.fn.zaj = function(){
	  	var target = this;
	  	// Create my object and return
	  	return {
	  		// Get or post serialized data
	  		get: function(url, response){ return zaj.ajax.get(url+'?'+target.serialize(), response); },
	  		post: function(url, response){ return zaj.ajax.post(url+'?'+target.serialize(), response); },
	  		sortable: function(receiver){ return zaj.sortable(target, receiver); },
	  		search: function(url, receiver){ return zaj.search.initialize(target, { url: url, callback: function(r){
	  			$(receiver).html(r);
	  		} }); }
	  	}
	  };
	})(jQuery);

