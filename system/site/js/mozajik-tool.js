/**
 * The mozajik-tool.js file contains all the custom Mozajik tools. Requires MooTools 1.3!
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 **/


/**
 * Class Search creates a search box which sends ajax requests at specified intervals to a given url.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 */
var Search = new Class({
	Implements: [Options, Events],
	
	options: {
		delay: 1000,					// Number of miliseconds before 
		url: false,						// The url to send the request to. This should be relative. &query=your+query will be appended.
		callback: false,				// A function or an element.
		method: 'post'					// The method to send by. Values can be 'post' (default) or 'get'.
	},
	
	/**
	 * Creates a new Search object
	 **/
		initialize: function(element, options){
			// set default options
				this.setOptions(options);
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
			return true;
		},
	
	/**
	 * Sends the query to the set url and processes.
	 **/
		send: function(){
			if(this.options.method == 'get') zaj.ajax.get(this.options.url, this.options.callback);
			else zaj.ajax.post(this.options.url, this.options.callback);
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
			}
	});

/* extend mootools Element */
	MozajikBaseElement.implement({
		search: function(url, callback){
			return zaj.search(this.element, url, callback);
		}
	});


