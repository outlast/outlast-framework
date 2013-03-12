//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2009 – popup class
//////////////////////////////////////////////////////////////////////////////
// class: zajPopup
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 2.5
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// Tested on Firefox 2+, IE 7+, Opera 9+, Safari 2+.
//////////////////////////////////////////////////////////////////////////////
// version history.
// requires mootools, zaj.js (for logging), zajpopup.css (for styles)
// - 2.5 - initial release, mootools rewrite
// known issues:
// - doesnt handle window resizing
// - Windows Flash needs parameter wmode=transparent in <embed> tag /
//							swfobj.addParam("wmode","transparent"); for SWFObject
//							<param name="wmode" value="transparent"> for others
//////////////////////////////////////////////////////////////////////////////
/* usage: 
var zajpopup = new zajPopup(options);

1. zajpopup.get(title,url[w[,h[,allowClickOutCancel[,x[,y[,relativeTo]]]]]]);
	Usage
	- sends a GET request to server via AJAX and displays result in a popup
	Parameters
	- title: can also be set later via set('title','whatever');
	- url: the url to be fetched via AJAX; can include query string
	- allowClickOutCancel: [default: false] this works only if backdrop is enabled; any click on the backdrop will close the window
	- w,h: width and height - automatic by default (although setting a width is recommended usually)
	- x,y: location compared to top left
	- relativeTo: relative to what object (defaults to window)
2. zajpopup.post(title,url[,allowClickOutCancel[,w[,h[,x[,y[,relativeTo]]]]]]);
	Usage
	- same as above, but with POST
3. zajpopup.alert(text);
	Usage
	- basically an alternative to alert(text);
4. zajpopup.confirm(question, functionOnYes);
	Usage
	- an alternative to confim(text);
5. zajpopup.close();
	Usage
	- close the most recent popup
6. zajpopup.closeall();
	- close all popups

Events:
	popupready - fired on zajpopup object after popup object is ready


*/

var zajPopup = new Class({
	Implements: [Options, Events],
	
	options: {
		title: '',
		content: '',
		allowClickOutCancel: false,
		skin: 'default',
		default_alert_title: 'figyelem',
		default_confirm_title: 'kérdés',
		backdrop: true
	},


	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(options){
			// set default options
				this.setOptions(options);
			// set my variables
				this.myWindows = new Array();
				this.myLevel = 0;
		},

	//////////////////////////////////////////////////////////////////////////////
	// send the request
		get: function(title,url,w,h,allowClickOutCancel,x,y,relativeTo){
			if(!$defined(url)) zajlib.error('url parameter is required for zajpopup.get method!');
			var self = this;
			zajajax.get(url, function(result){ self.add(title,result,w,h,allowClickOutCancel,x,y,relativeTo); });
		},
		post: function(title,url,w,h,allowClickOutCancel,x,y,relativeTo){
			if(!$defined(url)) zajlib.error('url parameter is required for zajpopup.post method!');
			var self = this;
			zajajax.post(url, function(result){ self.add(title,result,w,h,allowClickOutCancel,x,y,relativeTo); });
		},

	//////////////////////////////////////////////////////////////////////////////
	// add the window
		add: function(title,content,w,h,allowClickOutCancel,x,y,relativeTo){
			var my_new_window = new zajWindow({
				title: title,
				content: content,
				allowClickOutCancel: allowClickOutCancel,
				level: this.myLevel,
				backdrop: this.options.backdrop,
				w: w,
				h: h,
				x: x,
				y: y,
				relativeTo: relativeTo
			});
			this.myWindows.push(my_new_window);
			this.myLevel++;
			// TODO: why the delay needed?
			this.fireEvent('complete',null,50);
			this.removeEvents('complete');
		},
	//////////////////////////////////////////////////////////////////////////////
	// warning
		alert: function(warning,title){
			if(!$defined(title)) title = this.options.default_alert_title;
			this.add(title,warning,200);
		},
		warning: function(warning,title){this.alert(warning,title);},

	//////////////////////////////////////////////////////////////////////////////
	// question in a window
		confirm: function(question,onYes,title){
			if(!$defined(title)) title = this.options.default_confirm_title;
			// create div
				var el = new Element('p',{'align':'center'});
				var yes = new Element('a',{'html':'igen', 'href':'#','class':'zajpopup_confirm_yes'});
				var no = new Element('a',{'html':'nem','href':'javascript:zajpopup.close();','class':'zajpopup_confirm_no'})
			// add stuff to element
				el.set('html',question+'<br>');
				el.appendChild(yes);
				el.appendChild(no);
			// add event or link
				if(typeof onYes == 'function') zajlib.log('this is not working now!'); // todo: wtf is going on here? fix this...
				if(typeof onYes == 'string'){
					// should i add baseurl?
					if(onYes.substr(0,2) != '//' && onYes.substr(4, 3) != "://" && onYes.substr(5, 3) != "://") onYes = zajlib.baseurl+onYes;
					yes.set('href',onYes);
				}
				
			this.add(title,el.get('html'),200);
		},
		question: function(question,onYes,title){this.confirm(question,onYes,title);},
		
	//////////////////////////////////////////////////////////////////////////////
	// resize the topmost window
		resize: function(w, h){
			this.myWindows[this.myLevel-1].resize(w, h);
		},
	
	//////////////////////////////////////////////////////////////////////////////
	// close a window
		close: function(zajwindow){
			if(!$defined(zajwindow)) zajwindow = this.myWindows.pop();
			this.myLevel--;
			zajwindow.close();
		},
		closeall: function(){
			var self = this;
			this.myWindows.each(function(el){
				self.close(el);
			});
		},

	//////////////////////////////////////////////////////////////////////////////
	// backwards compatibility (to be removed in a future version)
		addPopup: function(title, content, w, h, allowClickOutCancel, x, y, processFunction){
			//if($defined(processFunction)) this.addEvent('complete',processFunction);
			this.add(title,content,w,h,allowClickOutCancel,x,y);
		},
		addPopupUrl: function(title, url, w, h, allowClickOutCancel, x, y, processFunction, usePostMode){
			//if($defined(processFunction)) this.addEvent('complete',processFunction);
			if(usePostMode) this.post(title,url,w,h,allowClickOutCancel,x,y);
			else this.get(title,url,w,h,allowClickOutCancel,x,y);
		},
		addQuestion: function(question, onyes){
			this.confirm(question, onyes);
		},
		addWarning: function(warning){
			this.warning(warning);
		},
		deletePopup: function(){
			this.close();
		},
		deleteAllPopups: function(){
			this.closeall();
		}		
});

var zajWindow = new Class({
	Implements: [Options, Events],
	
	options: {
		title: '',
		content: '',
		allowClickOutCancel: false,
		w: -1,
		h: -1,
		x: -1,
		y: -1,
		level: 0,
		relativeTo: null,
		skin: 'default',
		backdrop: true,
		backdrop_opacity: 0.8,
		default_width: 0.7,
		default_height: 0.9,
		close_with_top_right: true,
		close_with_top_left: false
	},

	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(options){
			// set default options
				this.setOptions(options);
			// create divs
				this.backdrop = new Element('div',{
					'class':'zajpopup_backdrop_'+this.options.skin,
					'styles': {
						'display':'block',
						'position':'fixed',
						'top':'0px',
						'left':'0px',
						'z-index':this.options.level+400
					}
				});
				this.zajwindow = new Element('div',{
					'class':'zajpopup_window_'+this.options.skin,
					'styles': {
						'display':'block',
						'position':'fixed',
						'z-index':this.options.level+401
					}
				});
				this.tlcorner = new Element('div',{'class':'zajpopup_tlcorner_'+this.options.skin,'styles':{'position':'absolute','top':'0px','left':'0px'}});
				this.trcorner = new Element('div',{'class':'zajpopup_trcorner_'+this.options.skin,'styles':{'position':'absolute','top':'0px','right':'0px'}});
				this.blcorner = new Element('div',{'class':'zajpopup_blcorner_'+this.options.skin,'styles':{'position':'absolute','bottom':'0px','left':'0px'}});
				this.brcorner = new Element('div',{'class':'zajpopup_brcorner_'+this.options.skin,'styles':{'position':'absolute','bottom':'0px','right':'0px'}});
				this.top = new Element('div',{'class':'zajpopup_top_'+this.options.skin,'styles':{'position':'absolute','overflow':'hidden','top':'0px'}});
				this.content = new Element('div',{'class':'zajpopup_content_'+this.options.skin,'styles':{'position':'absolute','overflow':'auto','overflow-x':'hidden','overflow-y':'auto'}});
				this.bottom = new Element('div',{'class':'zajpopup_bottom_'+this.options.skin,'styles':{'position':'absolute','overflow':'hidden','bottom':'0px'}});
			// append divs
				if(this.options.backdrop) document.body.appendChild(this.backdrop);
				document.body.appendChild(this.zajwindow);
					this.zajwindow.appendChild(this.tlcorner);
					this.zajwindow.appendChild(this.trcorner);
					this.zajwindow.appendChild(this.blcorner);
					this.zajwindow.appendChild(this.brcorner);
					this.zajwindow.appendChild(this.top);
					this.zajwindow.appendChild(this.content);
					this.zajwindow.appendChild(this.bottom);

			// set content 
				this.content.set('html',this.options.content);
				this.top.set('html',this.options.title);

			// calculate window size
				this.resize(this.options.w, this.options.h);
			// add events
					var self = this;
					if(this.options.close_with_top_right) this.trcorner.addEvent('click',function(){ self.close(); });
					if(this.options.close_with_top_left) this.tlcorner.addEvent('click',function(){ self.close(); });					
		},
		
		
	//////////////////////////////////////////////////////////////////////////////
	// resize
		resize: function(w, h){
			if(!$defined(w)) w = -1;
			if(!$defined(h)) h = -1;

			// calculate window size
				// set backdrop width and height
					var wscrollsize = window.getScrollSize();
					this.backdrop.setStyles({'width':wscrollsize.x,'height':wscrollsize.y})
					
				// get window width and set zajwindow to x% of it (if no w given)
					var wsize = window.getSize();
					if(w > 0) zajwindow_width = w;
					else zajwindow_width = wsize.x*this.options.default_width;
					this.zajwindow.setStyle('width',zajwindow_width);
					this.content.setStyle('width',this.zajwindow.clientWidth);
					var wdata = this.content.getComputedSize(['computed','totalWidth']);
					this.content.setStyle('width',this.zajwindow.clientWidth - (wdata.totalWidth - wdata.width));
					
					this.zajwindow.setStyle('left',(wsize.x-zajwindow_width)/2);
				// now get content height
					var csize = this.content.getSize();
					var tsize = this.top.getSize();
					var bsize = this.bottom.getSize();

				// if h specified!
					if(h > 0) this.zajwindow.setStyle('height',h);
				// is it too big (larger than 90% of window)? if so, restrict its size
					else{
						if((csize.y+tsize.y+bsize.y) > wsize.y*this.options.default_height) this.zajwindow.setStyle('height',wsize.y*this.options.default_height);
						else this.zajwindow.setStyle('height',csize.y+tsize.y+bsize.y+5);
					}
					
				// get all the other sizes
					var zsize = this.zajwindow.getSize();
					var tlsize = this.tlcorner.getSize();
					var trsize = this.trcorner.getSize();
					var blsize = this.blcorner.getSize();
					var brsize = this.brcorner.getSize();
				// position the window vertically
					this.zajwindow.setStyle('top',(wsize.y-zsize.y)/2);
				// now position everything else within the window
					this.content.setStyle('top',tsize.y);
					this.content.setStyle('height',zsize.y-tsize.y-bsize.y);
					this.top.setStyle('width',zsize.x-tlsize.x-trsize.x);
					this.top.setStyle('left',tlsize.x);
					this.bottom.setStyle('width',zsize.x-blsize.x-brsize.x);
					this.bottom.setStyle('left',blsize.x);		
		},

	//////////////////////////////////////////////////////////////////////////////
	// close
		close: function(){
			// destroy all the divs
			this.zajwindow.destroy();
			this.backdrop.destroy();
		}
		
});

var zajpopup = new zajPopup();