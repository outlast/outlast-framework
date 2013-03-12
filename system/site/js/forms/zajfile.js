//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – ajax class
//////////////////////////////////////////////////////////////////////////////
// class: zajFile
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
// requires zajupload, mootools, FancyUpload3
// - 2.0 - initial release, mootools compatible
//////////////////////////////////////////////////////////////////////////////
/*  usage: 
	var filefield = new zajFile('divid',{useroptions});

	methods:
		zajFile.add(fileid, filename, isnew);	// add existing

	events:
		onAdd(id, url, isnew) - called after a file has been added to the box
		onRemove(id) - called after a file has been removed from the box
		onAftersort(ids) - called after sorting	
*/
//////////////////////////////////////////////////////////////////////////////
// zajFile class

var zajFile = new Class({
	Implements: [Options, Events],
	
	options: {
		limitSize: false,
		limitFiles: 5,
		instantStart: true,
		allowDuplicates: false,
		action: 'system/upload/file/'
	},	
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, options){
			// set default options
				this.setOptions(options);
			// now set all default values
				this.divid = divid;
				this.mydiv = $(divid);
				this.files = new Array();
				this.fileids = new Array();
			// now create the divs
				this.container = new Element('ul', {
						'id': this.divid+'-containerbox',
						'class': 'zajFile_containerbox zajupload-list'
				
					});
				this.uploadbox = new Element('div', {
						'id': this.divid+'-uploadbox',
						'class': 'zajFile_uploadbox'				
					});
			// asseble the divs
				this.mydiv.appendChild(this.container);
				this.mydiv.appendChild(this.uploadbox);
			// add br
				//this.mydiv.appendChild(Element('br'));
			// init the sortables but remove uploadlink
				var self = this;
				this.fileSortables = new Sortables(this.container, { opacity: .5, clone: true, revert: { duration: 500, transition: 'elastic:out' }, onComplete: function(){ self.afterFileSort(); } });
				this.initUploadBox();
		},

	//////////////////////////////////////////////////////////////////////////////
	// initialize upload box
		initUploadBox: function(){
			// create the upload box
				this.zajupload = new zajUpload(this.divid+'-uploadbox', { action: this.options.action, destroyUploadStatusOnSuccess: true });
			// now add an event for the upload box
				var self = this;
				this.zajupload.addEvent('success', function(response){ self.add(response, true); });
				//this.zajupload.addEvent('cancel', function(){ $(self.divid+'-uploadbox').innerHTML = ''; } );
		},


	//////////////////////////////////////////////////////////////////////////////
	// add / remove files
		add: function(filedata, isnew){
			// default values
				if(!$chk(isnew)) isnew = false;
				if(!$defined(filedata.name)) filedata = JSON.decode(filedata);
			// process size
				var size = Math.round(filedata.size/1024*10)/10;
				if(size >= 1000) size = Math.round(filedata.size/(1024*1024)*10)/10+' MB';
				else size = size+' KB';
			
			// create new element
				var newfile = new Element('li', {'class':'file','id':filedata.id, 'styles':{ 'background-color':'transparent' } });
				newfile.set('html','<span class="file-title">'+filedata.name+'</span><span class="file-size">'+size+'</span>')
			// create new trash image
				var newtrash = new Element('img', {'src':zajlib.baseurl+'system/img/transparent.gif','class':'zajupload-deletebutton'});
			// inject element at bottom
				newfile.inject(this.container);
				newtrash.inject(newfile);
			// add events to trash
				var self = this;
				newtrash.addEvent('click',function(){ self.remove(filedata.id); });
			// add sortables item
				this.fileSortables.addItems(newfile);
			// fire add event
				this.fireEvent('add',[filedata.id, isnew]);
			
			return true;
		},
		remove: function(id){
			// get item div
				var me = $(id);
			// remove from sortables
				this.fileSortables.removeItems(me);
			// remove physically from box
				var self = this;
				me.fade(0);
				(function(){ me.destroy(); }).delay(500);
			// now fire event
				this.fireEvent('remove',[id]);
		},

	//////////////////////////////////////////////////////////////////////////////
	// after sorting
		afterFileSort: function(){
			// get the current order
				var div_ids = this.fileSortables.serialize();
			// fire aftersort event			
				this.fireEvent('aftersort', [div_ids]);
		}
		
});


// end of class
////////////////////////////////////////////////////////////////////////////