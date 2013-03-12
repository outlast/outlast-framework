//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – ajax class
//////////////////////////////////////////////////////////////////////////////
// class: zajUpload
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 2.0
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// this should work Firefox 1.5+, IE 5.5+, Opera 7+, Flash 9 & 10. send bugs to above email.
// check for new versions, go to hontalan.com.
//////////////////////////////////////////////////////////////////////////////
// version history.
// requires mootools, FancyUpload3
// - 2.0 - initial release, mootools compatible
//////////////////////////////////////////////////////////////////////////////
/* usage: 
	var zajUpload = new zajUpload('this.divid',{useroptions},{usertexts});
	
*/
//////////////////////////////////////////////////////////////////////////////
// zajUpload class
//var zajUpload = new zajUpload();


var zajUpload = new Class({
	Implements: [Options, Events],
	
	options: {
		limitSize: false,
		limitFiles: 5,
		instantStart: false,
		allowDuplicates: false,
		uploadTrigger: null,
		action: 'system/upload/file/',
		destroyUploadStatusOnSuccess: false
	},
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, options){
			// set default options
				this.setOptions(options);
			// set variables
				this.id = 'zajupload-'+divid;
				this.divid = divid;
				this.myNewUploads = new Array();
				var self = this;
			// load external files
				// load js
					if (window.FancyUpload3 && window.Swiff.Uploader && window.Fx.ProgressBar) self.initializeProcess();
					else{
						var pb_js = Asset.javascript(zajlib.baseurl+'system/js/forms/flash/Fx.ProgressBar.js?v1', { onload: function(){self.initializeProcess('progressbar');} });
						var su_js = Asset.javascript(zajlib.baseurl+'system/js/forms/flash/Swiff.Uploader.js?v1', { onload: function(){self.initializeProcess('swiff');} });
					}
			return true;
		},
		initializeProcess: function(whatwasloaded){
			// are they all loaded?
				if(!window.Swiff.Uploader || !window.Fx.ProgressBar) return false;
			// is FancyUpload loaded?
				if(!window.FancyUpload3){
					var self = this;
					var fu_js = Asset.javascript(zajlib.baseurl+'system/js/forms/flash/FancyUpload3.js?v17', { onload: function(){self.initializeProcess('fancy');} });
					alert('!');
					return false;
				}

			// now create html				
				$(this.divid).innerHTML = "<div id='"+this.id+"-status' class='zajupload-status'></div>";
				if(this.options.uploadTrigger){
					$(this.id+'-status').set('html',"<ul id='"+this.id+"-list'></ul>");
					var my_container = this.options.uploadTrigger;
					var my_target = null;
					var my_width = this.options.uploadTrigger.clientWidth;
					var my_height = this.options.uploadTrigger.clientHeight;
				}
				else{
					var my_status = $(this.id+'-status');
					// new upload add button and list
						var my_container = new Element('a',{'id':this.id+'-attach','class':'zajupload-addbutton','html':'&nbsp'}); 
						var my_list = new Element('ul',{'id':this.id+"-list"});
					// now inject them
						my_container.inject(my_status);
						my_list.inject(my_status);
					// now calculate size
						var size = my_container.getSize();
						var my_width = size.x;
						var my_height = size.y;
				}
			

			var self = this;
			// instantiate swiffy
			var baseurl = zajlib.baseurl;
			this.myFancyUpload = new FancyUpload3.Attach(this.id+'-list', {
				path: baseurl+'system/js/forms/flash/Swiff.Uploader.swf',
				url: baseurl+self.options.action,
				fileSizeMax: 10 * 1024 * 1024,
				container: my_container,
				width: my_width,
				height: my_height,
				
				verbose: true,
				
				onSelectFail: function(files) {
					files.each(function(file) {
						new Element('li', {
							'class': 'file-invalid',
							events: {
								click: function() {
									this.destroy();
								}
							}
						}).adopt(
							new Element('span', {html: file.validationErrorMessage || file.validationError})
						).inject(this.list, 'bottom');
					}, this);	
				},
				
				onFileSuccess: function(file) {
					file.ui.cancel = new Element('span').inject(file.ui.element, 'bottom');
					file.ui.element.highlight('#e6efc2');
					var zajupload_response = JSON.decode(file.response.text);
					// if error
						if(zajupload_response['status'] == 0){
							file.ui.cancel.set('html', '<a href="#">oké</a>').addEvents().addEvent('click', function() {
								file.remove();
								return false;
							});
							new Element('span', {
								html: zajupload_response['error'],
								'class': 'file-error'
							}).inject(file.ui.cancel, 'after');
							// fire error event with result text as parameter
							self.fireEvent('error',zajupload_response);
						}
					// if success
						else{
							new Element('span', {
								html: '',
								'class': 'file-success'
							}).inject(file.ui.cancel, 'after');
							
							// remove this whole ui element?
							if(self.options.destroyUploadStatusOnSuccess) file.ui.element.destroy();
							
							// fire success event with result text as parameter
							self.fireEvent('success',zajupload_response);
						}
				},
				
				onFileError: function(file) {
					file.ui.cancel.set('html', 'újra').removeEvents().addEvent('click', function() {
						file.requeue();
						return false;
					});
					
					new Element('span', {
						html: file.errorMessage,
						'class': 'file-error'
					}).inject(file.ui.cancel, 'after');
				},
				
				onFileRequeue: function(file) {
					file.ui.element.getElement('.file-error').destroy();
					
					file.ui.cancel.set('html', 'mégsem').removeEvents().addEvent('click', function() {
						file.remove();
						return false;
					});
					
					this.start();
				}
				
			});
		}
});




// end of class
////////////////////////////////////////////////////////////////////////////
