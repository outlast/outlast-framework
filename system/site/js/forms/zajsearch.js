//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – quick search window class
//////////////////////////////////////////////////////////////////////////////
// class: zajSearch
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 3.0
//////////////////////////////////////////////////////////////////////////////
// files needed:
//  - zaj.js
//	- zajajax.js
//	- zajsearch.css
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// this should work Firefox 1.5+, IE 5.5+, Opera 7+. send bugs to above email.
// check for new versions, go to hontalan.com.
//////////////////////////////////////////////////////////////////////////////
// version history.
// - 3.0 - initial release, mootools support
//////////////////////////////////////////////////////////////////////////////
/*  usage: 
		var searcher = new zajSearch('searchdivid',{ options });
	Events:
		onComplete - called after a successful search request has completed
		//onRequest - called when a request is first made
		//onError - called when a request fail
*/
//////////////////////////////////////////////////////////////////////////////
// zajSearch class

var zajSearch = new Class({
	Implements: [Options, Events],
	
	options: {
		query_string: 'search/?q=',				// the query sent to the server
		results_box: false,						// new one created by default
		loading_message: 'keresés...',			// text displayed
		search_delay: 1000						// this much time before query sent
	},	
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, options){
			// set default options
				this.setOptions(options);
				this.searchbox = $(divid);
			// create new results box div or use existing
				if($chk(this.options.results_box)){
					// set results box to existing div
						this.resultsbox = $(this.options.results_box);
						this.isNewBox = false;
				}
				else{
					// create new div
						this.resultsbox = new Element('div');
						this.resultsbox.id = divid+'_resultsbox';
						this.isNewBox = true;
					// set class of resulsbox
						this.resultsbox.addClass('zajresultsbox');
						this.positionResultsBox();
					// add the box to body
					rbox = this.resultsbox;
					window.addEvent('domready', function(){
						document.body.appendChild(rbox);
					});
				}
				this.resultsbox.innerHTML = this.options.loading_message;			
				var self = this;
			// start my own request object
				this.request = new zajAjax();
				this.request.addEvent('complete', function(){ zajlib.fireEvent('afterresult'); });
			// create events
				this.keydownTimer = 0;
				this.searchbox.addEvent('keydown',function(){
					// position and show the box
					self.positionResultsBox();
					self.resultsbox.setStyle('display','block');
					// clear the timer
					$clear(self.keydownTimer);
				});
				this.searchbox.addEvent('keyup',function(){
					// check if deleted evthing
					if(self.searchbox.value == '') return self.close();
					// else, start a new timer
					$clear(self.keydownTimer);
					self.keydownTimer = (function(){ self.search(); }).delay(1000);
				});			
		},
	//////////////////////////////////////////////////////////////////////////////
	// position results box
		positionResultsBox: function(){
			// if no position needed
				if(!this.isNewBox) return false;
			
			// set position of results box
				// get search box size/position
					this.searchboxsize = this.searchbox.getSize();
					this.searchboxposition = this.searchbox.getPosition();
				// set results box styles
					this.resultsbox.setStyles({
						position: 'absolute',
						top: this.searchboxposition.y+this.searchboxsize.y+2,
						left: this.searchboxposition.x,
						display: 'none',
						overflow: 'hidden'
					});
			return true;
		},

	//////////////////////////////////////////////////////////////////////////////
	// execute a search
		search: function(){
			// send ajax query
				zajlog.log('search now for "'+this.searchbox.value+'" and send to '+this.resultsbox.id);
				this.request.get(this.options.query_string+this.searchbox.value,this.resultsbox.id);
		},

	//////////////////////////////////////////////////////////////////////////////
	// close and reset
		close: function(delete_value){
			if(!$defined(delete_value)) delete_value = true;
			// hide the box
				this.resultsbox.setStyle('display','none');
			// clear the timer
				$clear(self.keydownTimer);
			// reset the box contents
				this.resultsbox.innerHTML = this.options.loading_message;
				if(delete_value) this.searchbox.value = "";
			// fire the blur event
				this.searchbox.fireEvent('blur');
			return true;
		}

});








