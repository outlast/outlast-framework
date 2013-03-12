//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – ajax class
//////////////////////////////////////////////////////////////////////////////
// class: zajPhoto
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
	var zajPhoto = new zajPhoto('divid',{useroptions});

	methods:
	zajPhoto.addPhoto(photoid, photourl);			// add existing
	zajPhoto.savePhotos(parentid, parentobj);		// save new and existing

	events:
		onPhotoadd(id, url, isnew) - called after a photo has been added to the photo box (even for old, non-uploaded photos)
		onPhotoremove(id) - called after a photo has been removed from the photo box (even if not deleted)
		onAftersort(ids) - called after sorting


		// implement these:
		//onUploadcompleted - called after an upload session has been completed
		//onPhotoupload - called after a photo has been uploaded and added to the photo box
		//onPhotodelete - called after a photo has been deleted from the box
	
*/
//////////////////////////////////////////////////////////////////////////////
// zajPhoto class

var zajPhoto = new Class({
	Implements: [Options, Events],
	
	options: {
		limitSize: false,
		limitFiles: 5,
		instantStart: true,
		allowDuplicates: false,
		action: 'system/upload/photo/',
		removelink: false
	},	
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, options){
			//zajlog.log('initializing photoadmin in '+divid);
			// set default options
				this.setOptions(options);
			// now set all default values
				this.divid = divid;
				this.mydiv = $(divid);
				this.iMyPhotos = new Array();
				this.iMyPhotoIDs = new Array();
			// now create the divs
				this.container = new Element('div', {
						'id': this.divid+'-containerbox',
						'class': 'zajPhoto_containerbox'
				
					});
				this.uploadlink = new Element('div', {
						'id': this.divid+'-uploadlink',
						'class': 'zajPhoto_upload',
						'html': ''
					});
				this.uploadbox = new Element('div', {
						'id': this.divid+'-uploadbox',
						'class': 'zajPhoto_uploadbox'
					});
			// asseble the divs
				this.mydiv.appendChild(this.container);
				this.container.appendChild(this.uploadlink);
				this.mydiv.appendChild(this.uploadbox);
			// add br
				this.mydiv.appendChild(Element('br'));
			// init the sortables but remove uploadlink
				var self = this;
				this.photoSortables = new Sortables(this.container, { opacity: .5, clone: true, revert: { duration: 500, transition: 'elastic:out' }, onComplete: function(){ self.afterPhotoSort(); } });
				this.uploadlink.removeEvents();
				this.initUploadBox();
		},

	//////////////////////////////////////////////////////////////////////////////
	// initialize upload box
		initUploadBox: function(){
			// create the upload box
				this.zajupload = new zajUpload(this.divid+'-uploadbox', { uploadTrigger: this.uploadlink, action: this.options.action, removelink: this.options.removelink });
			// now add an event for the upload box
				var self = this;
				this.zajupload.addEvent('success', function(response){ self.addPhoto(response.id, response.fileurl, true); });
				//this.zajupload.addEvent('allComplete', function(){ $(self.divid+'-uploadbox').innerHTML = ''; self.fireEvent('uploadComleted'); });
				this.zajupload.addEvent('cancel', function(){ $(self.divid+'-uploadbox').innerHTML = ''; } );
		},


	//////////////////////////////////////////////////////////////////////////////
	// add / remove photos
		addPhoto: function(photoid, photourl, isnew){
			// default values
				if(!$chk(isnew)) isnew = false;
			// now add to array - note: do we really need the array?
				this.iMyPhotos[photoid] = { url: photourl, isnew: isnew, deleted: false };
				this.iMyPhotoIDs.pop(photoid);
			// create a new photo div
				var anotherphoto = new Element('div', {'class':'zajPhoto_thumb','id':photoid,'styles': { 'background-image': "url("+photourl+")", 'overflow': 'hidden' } });
				$(this.divid+"-containerbox").insertBefore(anotherphoto, this.uploadlink);
			// insert photo as 50px wide thumbnail
				var newphotothumb = new Element('img', {'class':'zajPhoto_thumb_pic','id':photoid+'-photo-pic','src': photourl });
				anotherphoto.appendChild(newphotothumb);
			
			// store photo data in element (photoid, isnew)
				anotherphoto.store('photoid',photoid);
				anotherphoto.store('isnew',isnew);
			// add event for showing options
				var self = this;
				anotherphoto.addEvent('mouseenter',function(){ self.photoShowOptions(anotherphoto.id); });
				anotherphoto.addEvent('mouseleave',function(){ (function(){self.photoHideOptions(anotherphoto.id);}).delay(400); });
				
 			// add class if isnew
				if(isnew) anotherphoto.addClass('zajPhoto_newthumb');
			// reset sortables
				this.photoSortables.detach();
				this.photoSortables.attach();
				this.uploadlink.removeEvents();	// make sure that the new photo link is not part of sortable
			// now fire event
				this.fireEvent('photoadd',[photoid, photourl, isnew]);
			// now send to photoSort
				//this.afterPhotoSort();
		},
		removePhoto: function(photoid){
			// deleted true
				this.iMyPhotos[photoid].deleted = true;
			// remove from sortables
				this.photoSortables.removeItems($(photoid));
				this.photoSortables.detach();
				this.photoSortables.attach();
				this.uploadlink.removeEvents();	// make sure that the new photo link is not part of sortable
			// remove physically from box
				var self = this;
				$(photoid).fade(0);
				(function(){ $(photoid).destroy(); }).delay(500);
				$(photoid+'-options').store('cancelhide', false);
				this.photoHideOptions(photoid);
			// now fire event
				this.fireEvent('photoremove',[photoid]);
		},

	//////////////////////////////////////////////////////////////////////////////
	// photo options
		photoShowOptions: function(elementid){
			// create element
				var photooptions = new Element('div',
				{	'class':'zajPhoto_thumb_options',
					'id':elementid+'-options',
					'styles': {
						'opacity': '0',
						'top': ($(elementid).offsetTop+$(elementid).getSize().y)+'px',
						'left':($(elementid).offsetLeft)+'px'
				} });
			// create option elements
				//var photooptions_edit = new Element('div', { 'class':'zajPhoto_editicon', 'id':elementid+'-options-edit'} );
				var photooptions_delete = new Element('div', { 'class':'zajPhoto_deleteicon', 'id':elementid+'-options-delete'} );
			// add and fade it in
				$(this.divid).appendChild(photooptions);
				//photooptions.appendChild(photooptions_edit);
				photooptions.appendChild(photooptions_delete);
				photooptions.fade(0.8);			
			// now add events
				var self = this;
				var photoid = $(elementid).retrieve('photoid');
				photooptions.addEvent('mouseenter',function(){ $(elementid+'-options').store('cancelhide', true); });
				photooptions.addEvent('mouseleave',function(){ $(elementid+'-options').store('cancelhide', false); self.photoHideOptions(elementid); });
				// option events
				photooptions_delete.addEvent('click',function(){ self.removePhoto(photoid); });
		},
		photoHideOptions: function(elementid){
			if($chk($(elementid+'-options')) && !$(elementid+'-options').retrieve('cancelhide')){
				$(elementid+'-options').fade('out');
				(function(){ if($chk($(elementid+'-options'))) $(elementid+'-options').destroy(); }).delay(500);
				return true;
			}
			else return false;
		},


	//////////////////////////////////////////////////////////////////////////////
	// after sorting
		afterPhotoSort: function(){
			// get the current order
				var div_ids = this.getPhotoDivs();
			// fire aftersort event			
				this.fireEvent('aftersort', [div_ids]);
		},
	
	//////////////////////////////////////////////////////////////////////////////
	// get photos
		getPhotoUrls: function(onlynew){
			if(!$chk(onlynew)) onlynew = false;
			var myurls = new Array();
			var mydivs = this.getPhotoDivs();
			for(var i = 0; i < mydivs.length; i++){
				// retrieve the photoid in the div element
					if(!onlynew || $(mydivs[i]).retrieve('isnew')) myurls.push($(mydivs[i]).retrieve('photoid'));
			}
			return myurls;
		},
		
		getNewPhotoUrls: function(){
			return this.getPhotoUrls(true);
		},
	
		// returns the photo div ids
		getPhotoDivs: function(){
			var rarray = new Array();
			var self = this;
			$(this.divid+'-containerbox').getChildren('div').each(function(el){
				// add everything besides the upload link
				if(el.id != self.divid+'-uploadlink') rarray.push(el.id);;
			});
			return rarray;
		}
		
});


// end of class
////////////////////////////////////////////////////////////////////////////