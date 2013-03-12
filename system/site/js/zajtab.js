//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2009 – popup class
//////////////////////////////////////////////////////////////////////////////
// class: zajTab
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 3.0
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// Tested on Firefox 2+, IE 7+, Opera 9+, Safari 2+.
//////////////////////////////////////////////////////////////////////////////
// version history.
// requires mootools, zaj.js (for logging), zajajax.css (for onclick loading)
// - 3.0 - initial release
// known issues:
//////////////////////////////////////////////////////////////////////////////
/* usage: 
var zajtab = new zajTab('tabgroupid',{options});

1. zajtab.add('tabid',['requesturl']);
	- add a tab to this group, with request url used upon show (if needed)
2. zajtab.show('tabid',['override_requesturl']);
	- shows a tab and hides all others

Events:
	tabready - fired after tab has loaded content

*/

var zajTab = new Class({
	Implements: [Options, Events],
	
	options: {
		nooptions: true		// no options imlemented yet!
	},


	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(tabgroup, options){
			// set default options
				this.setOptions(options);
			// set my variables
				this.group = tabgroup;
				this.tabs = new Hash();
		},

	//////////////////////////////////////////////////////////////////////////////
	// add a tab
		add: function(tabid, ajax_request){
			// by default ajax_request is empty
				if(!$defined(ajax_request)) ajax_request = '';
			// now add tabid and request to arrays
				this.tabs[tabid] = new Hash({id: tabid, divid: 'zajtab_'+this.group+'_'+tabid, request: ajax_request, loaded: false});
		},
	//////////////////////////////////////////////////////////////////////////////
	// show a tab
		show: function(tabid, override_ajax_request){
			// you can override the earlier ajax_request
				if($defined(override_ajax_request)) ajax_request = override_ajax_request;
				else ajax_request = this.tabs[tabid].request;
			// get my div
				var my_div = $(this.tabs[tabid].divid);
			// now do i need to get anything?
				if(ajax_request != '' && !this.tabs[tabid].loaded){

					// todo: add loading overlay

					// send ajax request and handle result
					var self = this;
					zajajax.get(ajax_request, function(result){
						// set result
							my_div.set('html',result);
						// set me to loaded and fire event
							self.tabs[tabid].loaded = true;
							self.fireEvent('tabready',self.tabs[tabid]);
					});
				}
				else{
					// set me to loaded and fire event
						this.tabs[tabid].loaded = true;
						this.fireEvent('tabready',this.tabs[tabid]);
				}
			// hide all, then show this one
				this.hide_all();
				my_div.show();
		},
	//////////////////////////////////////////////////////////////////////////////
	// hide all tabs
		hide_all: function(){
			this.tabs.each(function(item, index){
				$(item.divid).hide();
			});
		}
		
});

//////////////////////////////////////////////////////////////////////////////
// Static methods
zajTab.extend({
	show: function(tabgroupid, tabid){
		zajtab[tabgroupid].show(tabid);
	}
});

//////////////////////////////////////////////////////////////////////////////
// Finally, create new array
zajtab = new Hash();

