//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – ajax class
//////////////////////////////////////////////////////////////////////////////
// class: zajRate
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 2.0
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// this should work Firefox 1.5+, IE 5.5+, Opera 7+. send bugs to above email.
// check for new versions, go to hontalan.com.
//////////////////////////////////////////////////////////////////////////////
// version history.
// requires mootools
// - 2.0 - initial release, mootools compatible
//////////////////////////////////////////////////////////////////////////////
/*  usage: 
	var zajrater = new zajRate('divid',{options});
	
	events:
	- afterrate - fired after a rating is clicked...the rating number is passed...
*/
//////////////////////////////////////////////////////////////////////////////
// zajPhotoAdmin class

var zajRate = new Class({
	Implements: [Options, Events],
	
	options: {
		current_rating: 0,			// this is shown
		numofstars: 5
	},	
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, options){
			// set default options
				this.setOptions(options);
			// my container
				this.ratediv = $(divid);
				this.stardiv = new Array();
				var self = this;
				
				// create each star
				for(var i = 1; i<=this.options.numofstars; i++){
					this.stardiv[i] = new Element('div', {'class':'zajrate_star'});
					this.stardiv[i].store('rating', i);
					this.stardiv[i].addClass('zajrate_star_off');
					// add click event
					this.stardiv[i].addEvent('click', function(el){
							// set my rating
								self.options.current_rating = el.target.retrieve('rating');
								self.displayRating();
							// fire event with rating num
								self.fireEvent('afterrate',self.options.current_rating);
						});
					// add mouse enter/leave
					this.stardiv[i].addEvent('mouseenter', function(el){ self.displayRating(el.target.retrieve('rating')); });
					this.stardiv[i].addEvent('mouseleave', function(el){ self.displayRating(self.options.current_rating); });
					
					// append child
					this.stardiv[i].inject(this.ratediv);
				}
				// now show the display the current rating
				this.displayRating(this.options.current_rating);
		},

	//////////////////////////////////////////////////////////////////////////////
	// shows a rating of this value
		displayRating: function(rating){
			if(!$defined(rating)) rating = this.options.current_rating;
			
			this.stardiv.each(function(star, index){
					if($chk(star)){ // stupid IE needs this
					// first remove all classes
						star.removeClass('zajrate_star_on zajrate_star_half');
						star.removeClass('zajrate_star_off');
					// for each star, set it to true if rating larger than index
						if(rating >= index) star.addClass('zajrate_star_on');
						else star.addClass('zajrate_star_off');
					}
				});
		}
		
});


// end of class
////////////////////////////////////////////////////////////////////////////