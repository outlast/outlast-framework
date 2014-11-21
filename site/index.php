<?php
	$zajconf = array(); // Leave this alone! :)
////////////////////////////////////////////////////////////////////////////////
// !BEGIN CONFIGURATION

	////////////////////////////////////////////////////////////////////////////////
	// debug mode (default: false, except for localhost)
	////////////////////////////////////////////////////////////////////////////////
	// Debug mode is a special feature which enables a range of tools, error messages,
	//		and logs to help you during development. It is DANGEROUS and SLOW to use
	//		on production sites!!
	// – debug_mode is false by default for all domains except the ones specified by
	//	 debug_mode_domains array.
	// - you can use the optional debug_mode_domains to enable debug mode on certain
	//	 domains that you use for development (for example, localhost (default),
	//	 test.mydomain.com, etc.). see docs for more info on usage.
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['debug_mode'] = false; // CHANGING IS NOT RECOMMENDED! use debug_mode_domains!
		$zajconf['debug_mode_domains'] = array("localhost");

	////////////////////////////////////////////////////////////////////////////////
	// root folder (default: "")
	////////////////////////////////////////////////////////////////////////////////
	// – automatically determined by default, but set it here to override
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['root_folder'] = "";

	////////////////////////////////////////////////////////////////////////////////
	// site folder  (default: "")
	////////////////////////////////////////////////////////////////////////////////
	// – needs to be set only if it is not the default /rootfolder/site
	// - note: current release does not allow for alternate locations!
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['site_folder'] = "";	// not completely supported yet!

	////////////////////////////////////////////////////////////////////////////////
	// default application (default: "main")
	////////////////////////////////////////////////////////////////////////////////
	// – this will be the default application, when no specific app is requested
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['default_app'] = "default";

	////////////////////////////////////////////////////////////////////////////////
	// default mode (default: "main")
	////////////////////////////////////////////////////////////////////////////////
	// – this will be the default application mode, when no specific mode requested
	// - do NOT include a trailing slash here! (slashes in general are alowed)
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['default_mode'] = "main";

	////////////////////////////////////////////////////////////////////////////////
	// plugin apps (default: _project)
	////////////////////////////////////////////////////////////////////////////////
	// - this array should include a list of registered apps in your plugin folder
	// - processing happens in this order: local app, plugins, system (local has priority)
	// - processing within plugin apps happens in reverse order (first overwrites last)
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['plugin_apps'] = array();

	////////////////////////////////////////////////////////////////////////////////
	// system apps (default: _mootools, _global)
	////////////////////////////////////////////////////////////////////////////////
	// - this array should include a list of registered apps in your system/plugins folder
	// - you should only change this if you know what you are doing!
	// - processing within system apps happens in reverse order (first overwrites last)
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['system_apps'] = array('_jquery', '_global');

	////////////////////////////////////////////////////////////////////////////////
	// database access
	////////////////////////////////////////////////////////////////////////////////
	// – mysql server, user, and pass
	// - note: only mysql connections are supported
	// - $zajconf['mysql_ignore_tables'] allows you to define external tables. these will
	//		not be updated and will not be available within the model framework.
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['mysql_enabled'] = false;
		$zajconf['mysql_server'] = "localhost";
		$zajconf['mysql_encoding'] = 'utf8';
		$zajconf['mysql_user'] = "";
		$zajconf['mysql_password'] = "";
		$zajconf['mysql_db'] = "";
		$zajconf['mysql_ignore_tables'] = array();

	////////////////////////////////////////////////////////////////////////////////
	// update access
	////////////////////////////////////////////////////////////////////////////////
	// – restrict username and password to update menu
	// - user name and password are required if not in debug_mode
	// - WARNING: changing update appname is not yet supported!
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['update_enabled'] = true;
		$zajconf['update_appname'] = 'update';
		$zajconf['update_user'] = "";
		$zajconf['update_password'] = "";

	////////////////////////////////////////////////////////////////////////////////
	// php error log
	////////////////////////////////////////////////////////////////////////////////
	// – enable/disable error logging
	// - for the log file name you can use a custom file name. this should be a
	//			full path. if empty, log will go to the server's phperror log.
	// - enabling notices could put strain on the server (unless you are careful
	//			about not generating notices in your code! (Mozajik is!)
	// - enabling backtraces will generate huge log files if you have lots of
	//			errors. This should only be used sparingly.
	// - enabling jserror will log all javascript errors (depending on browser support).
	//			This may be useful, but it will create extra AJAX requests each time a
	//			javascript error is detected.
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['error_log_enabled'] = false;
		$zajconf['error_log_notices'] = false;
		$zajconf['error_log_backtrace'] = false;
		$zajconf['error_log_file'] = '';
		$zajconf['jserror_log_enabled'] = false;
		$zajconf['jserror_log_file'] = '';


	////////////////////////////////////////////////////////////////////////////////
	// Custom configuration options
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['plupload_photo_maxwidth'] = 1000;
		$zajconf['plupload_photo_maxfilesize'] = 5000000;

	////////////////////////////////////////////////////////////////////////////////
	// locale and language
	////////////////////////////////////////////////////////////////////////////////
	// – make sure the locale is installed and enabled system wide as well
	// - locale_default - the default locale in case nothing else is set.
	// - locale_available - a comma-separated list of available locales.
	////////////////////////////////////////////////////////////////////////////////
		date_default_timezone_set("Europe/Budapest");
		$zajconf['locale_default'] = 'hu_HU';
		$zajconf['locale_available'] = 'hu_HU,en_US';


// END OF CONFIGURATION
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// !BEGIN - DO NOT EDIT BELOW THIS LINE UNLESS YOU KNOW WHAT YOU'RE DOING!

	////////////////////////////////////////////////////////////////////////////////
	// config file version (only update this if you know what you are doing!)
	////////////////////////////////////////////////////////////////////////////////
		$zajconf['config_file_version'] = 305;

	///////////////////////////////////////////////////////////////////////////////////////////////
	// this is for mysql compatibility. change this only if you do not have US locale installed
	// 		but it is recommended to change it to some locale that uses dot (.) for decimal
	///////////////////////////////////////////////////////////////////////////////////////////////
		$zajconf['locale_numeric'] = 'en_US';

	// preload all system stuff
		require(realpath(dirname(__FILE__).'/../')."/system/site/index.php");
	// done.

	////////////////////////////////
	// Load my settings

		// PLACE CUSTOM CONFIGURATION HERE! (this will be run each time any request is made)
			// IMPORTANT: typically, you should use __load() in controller files and __plugin()
			//	for plugins to perform any such custom configurations.

	// End of settings
	////////////////////////////////

	// If not in include mode, load up the app request and create logs, etcetc.
		if(empty($zaj_include_mode)){
			// now load the app request
				$zajlib->load->app($app_request);
			// if in debug mode add script with execution time
				$execution_time = round(microtime(true) - $GLOBALS['execute_start'], 5);
				$peak_memory = round((memory_get_peak_usage())/1024, 0);
				if($zajlib->debug_mode){
					$zajlib->js_log .= "zaj.log('$zajlib->num_of_notices notices during execution. to view them, add ?notice=on to the url.');";
					$zajlib->js_log .= "zaj.log('$zajlib->num_of_queries sql queries during execution. to view them, add ?query=on to the url.');";
				}
				else $zajlib->js_log = "";
				print "<script> if(typeof zaj != 'undefined') { zaj.execution_time = $execution_time; zaj.peak_meory = $peak_memory; zaj.log('exec time: $execution_time sec / peak memory: $peak_memory kb'); ".$zajlib->js_log." }\n</script>";

			// thats it, we're done without errors, but exit only if not in include_mode
				exit(0);
		}
	// Include mode means that we have finished setting up the system and we're ready to process requests to Mozajik objects and libraries...
// END
////////////////////////////////////////////////////////////////////////////////