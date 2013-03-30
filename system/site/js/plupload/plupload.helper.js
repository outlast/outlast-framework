/**
 * Helper js for plupload to make it compatible with Outlast Framework upload forms.
 * @param options
 * @option browse_button
 * @option drop_element
 * @option max_file_size
 * @option input_name
 * @option input_crop
 * @option alert_toosmall
 * @option alert_toolarge
 * @option debug_mode
 **/
zaj.plupload = {
	// Public variables
	ready: false,
	percent: 0,

	// Public methods
	cropper: function(options){
		// Make sure baseurl is defined
			if(typeof zaj.baseurl == 'undefined' || !zaj.baseurl) zaj.error("Baseurl not defined, cannot init uploader!");
		// Globals
			var selection_changed = false;
			var sel_instance;

		// Create plupload object
			var uploader = new plupload.Uploader({
				runtimes : 'html5,flash,html4',
				browse_button : options.browse_button,
				drop_element : options.drop_element,
				max_file_size : options.max_file_size,
				url : zaj.baseurl+'system/plupload/upload/photo/',
				flash_swf_url : zaj.baseurl+'system/js/plupload/plupload.flash.swf'
			});
			var uploadergo = function(){
				uploader.start()
			}
			if(options.debug_mode) zaj.log("Uploader is in debug mode.");

		// Add uploader events
			uploader.bind('Init', function(up, params){
				zaj.log("Uploader initialized. Runtime is " + params.runtime);
			});
			uploader.bind('FilesAdded', function(up, files) {
				if(options.debug_mode) zaj.log("File added to uploader.");
				setTimeout(uploadergo, 800);
			});
			uploader.bind('UploadProgress', function(up, file) {
				if(options.debug_mode) zaj.log("File at "+file.percent+"%.");
				zaj.plupload.percent = file.percent;
			});
			uploader.bind('Error', function(up, err) {
				if(options.debug_mode) zaj.log("Error: " + err.code +", Message: " + err.message + (err.file ? ", File: " + err.file.name : ""));
				zaj.alert(options.alert_toolarge);
				up.refresh(); // Reposition Flash/Silverlight
			});
			uploader.bind('FileUploaded', function(up, file, result) {
				// Log and set variables
					if(options.debug_mode) zaj.log("Completed.");
					zaj.plupload.percent = 100;
					zaj.plupload.ready = true;
				// Parse results and share
					var res = jQuery.parseJSON(result.response);
					if(res.status == 'error') zaj.alert(options.alert_toolarge);
					else uploader_update(res);
			});

		// Init crop default
			$(options.input_crop).val('{"x":0,"y":0,"w":'+options.min_width+',"h":'+options.min_height+'}');

		/**
		 * Expects an object as such:
		 *	res.height, res.width, res.id
		 **/
			var uploader_update = function(res){
				// Update values and hide 30 chars
				$('#szoveg').attr('maxlength', '15').val('');
				$('a.char30').hide();
				// Is it wide/tall enough?
				if(res.width < options.min_width  || res.height < options.min_height) return zaj.alert(options.alert_toosmall);
				// Add my image
					if(options.debug_mode) zaj.log("Adding preview to "+options.file_list);
					var imgurl = zaj.baseurl+'system/plupload/preview/?id='+res.id;
					$(options.file_list).html("<img src='"+imgurl+"'>");
				// Create a new selection (discard old)
					if(sel_instance) sel_instance.remove();
					selection_changed = false;
				// Init my cropper
					sel_instance = $(options.file_list+" img").imgAreaSelect({
						show: true,
						aspectRatio: "1:1",
						imageHeight: res.height,
						imageWidth: res.width,
						minWidth: options.min_width,
						instance: true,
						onInit: function(){ sel_instance.setSelection(0, 0, options.min_width, options.min_height); sel_instance.update(); },
						onSelectChange: function(img, selection) {
							selection_changed = true;
							var dimensions = '{"x":'+selection.x1+',"y":'+selection.y1+',"w":'+selection.width+',"h":'+selection.height+'}';
							$(options.input_crop).val(dimensions);
						}
					});
				// Set as my input value
					$(options.input_name).val(res.id);
			}
		 // Run init
			zaj.ready(function(){ uploader.init(); });

	}
	/*var open_graphapi_uploader_callback;
	function open_graphapi_uploader(){
		// set callback function
		open_graphapi_uploader_callback = uploader_update_{{field.uid}};
		zaj.window('{{nwappurl}}page=upload_from_gallery');*/
}
