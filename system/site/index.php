<?php
	// define the current minimum htaccess / config file versions
		define('MOZAJIK_HTACCESS_VERSION', 303);
		define('MOZAJIK_CONFIG_VERSION', 303);

		define('MOZAJIK_RECOMMENDED_HTACCESS_VERSION', 303);
		define('MOZAJIK_RECOMMENDED_CONFIG_VERSION', 305);

	// Set locale but only if new config version
		global $zajconf;
		if(is_array($zajconf)){
			// Set my locale and numeric to US for compatibility
			setlocale(LC_ALL, $zajconf['locale_default']);
			setlocale(LC_NUMERIC, $zajconf['locale_numeric']);
		}
	// Set variables for backwards compatibility with old config version
		if(!is_array($zajconf)){
			$zajconf['default_app'] = $GLOBALS['zaj_default_app'];
			$zajconf['default_mode'] = $GLOBALS['zaj_default_mode'];
			$zajconf['plugin_apps'] = $GLOBALS['zaj_plugin_apps'];
			$zajconf['system_apps'] = $GLOBALS['zaj_system_apps'];
			
			$zajconf['debug_mode'] = $GLOBALS['debug_mode'];
			$zajconf['debug_mode_domains'] = $GLOBALS['debug_mode_domains'];
			$zajconf['root_folder'] = $GLOBALS['zaj_root_folder'];
			$zajconf['site_folder'] = $GLOBALS['zaj_site_folder'];

			$zajconf['update_enabled'] = $GLOBALS['zaj_update_enabled'];
			$zajconf['update_appname'] = $GLOBALS['zaj_update_appname'];
			$zajconf['update_user'] = $GLOBALS['zaj_update_user'];
			$zajconf['update_password'] = $GLOBALS['zaj_update_password'];

			$zajconf['mysql_enabled'] = $GLOBALS['zaj_mysql_enabled'];
			$zajconf['mysql_server'] = $GLOBALS['zaj_mysql_server'];
			$zajconf['mysql_user'] = $GLOBALS['zaj_mysql_user'];
			$zajconf['mysql_password'] = $GLOBALS['zaj_mysql_password'];
			$zajconf['mysql_db'] = $GLOBALS['zaj_mysql_db'];
			$zajconf['mysql_ignore_tables'] = $GLOBALS['zaj_mysql_ignore_tables'];

			$zajconf['error_log_enabled'] = $GLOBALS['zaj_error_log_enabled'];
			$zajconf['error_log_notices'] = $GLOBALS['zaj_error_log_notices'];
			$zajconf['error_log_backtrace'] = $GLOBALS['zaj_error_log_backtrace'];
			$zajconf['error_log_file'] = $GLOBALS['zaj_error_log_file'];
			$zajconf['jserror_log_enabled'] = $GLOBALS['zaj_jserror_log_enabled'];
			$zajconf['jserror_log_file'] = $GLOBALS['zaj_jserror_log_file'];

			$zajconf['plupload_photo_maxwidth'] = $GLOBALS['zaj_plupload_photo_maxwidth'];
			$zajconf['plupload_photo_maxfilesize'] = $GLOBALS['zaj_plupload_photo_maxfilesize'];
			$zajconf['plupload_photo_maxuploadwidth'] = $GLOBALS['zaj_plupload_photo_maxuploadwidth'];

			$zajconf['config_file_version'] = $GLOBALS['zaj_config_file_version'];
		}	
	
	// start execution
		$GLOBALS['execute_start'] = microtime(true);
	// set default encoding to unicode
		ini_set('default_charset','utf-8');
		mb_internal_encoding("UTF-8");
	// avoid scientific notation in large numbers (64bit int is 19 digits)
		ini_set('precision', 19);
	// check for request errors
		if(!empty($_REQUEST['error'])){
			if($_REQUEST['error'] == "querystring") exit("MOZAJIK REQUEST ERROR: cannot explicity use zajapp or zajmode in GET or POST query!");
			if($_REQUEST['error'] == "private") exit("MOZAJIK REQUEST ERROR: cannot access this folder!");
			if($_REQUEST['error'] == "norewrite") exit("MOZAJIK REQUEST ERROR: the required apache rewrite support not enabled!");
		}
	// check for versions
		if(empty($_REQUEST['zajhtver']) || $_REQUEST['zajhtver'] < MOZAJIK_HTACCESS_VERSION) exit("MOZAJIK VERSION ERROR: please update the htaccess file to the latest version!");
		if(empty($zajconf['config_file_version']) || $zajconf['config_file_version'] < MOZAJIK_CONFIG_VERSION) exit("MOZAJIK VERSION ERROR: please update your main config file to the latest version!");
	// prepare my requests - trim app and mode
		$_REQUEST['zajapp'] = trim($_REQUEST['zajapp'], " _-\"\\'/");
		$_REQUEST['zajmode'] = trim($_REQUEST['zajmode'], " _-\"\\'/");
		
	// figure out my relative path
		if(!empty($zajconf['site_folder']) && empty($zajconf['root_folder'])) exit("MOZAJIK CONFIG ERROR: If you set the site_folder parameter, you must also set the root_folder!");
	// auto-detect root folder if not set already
		if(empty($zajconf['root_folder'])) $zajconf['root_folder'] = realpath(dirname(__FILE__).'/../../');	
	// set the default system plugins (for backwards compatibility)
		if(empty($zajconf['system_apps'])) $zajconf['system_apps'] = array('_global', '_mootools');
	// include the zajlib system class
		if (!(include $zajconf['root_folder'].'/system/class/zajlib.class.php')) exit("<b>zajlib error:</b> missing zajlib system files or incorrect path given! set in site/index.php!");
	// create a new zajlib object
		$zajlib = new zajLib($zajconf['root_folder'], $zajconf);
	// set internal error handler
		set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext){ if(!is_object(zajLib::me())){ print "FATAL ERROR: Check error log."; } else zajLib::me()->error_handler($errno, $errstr, $errfile, $errline, $errcontext);});
	// set shutdown error handler (fatal)
		register_shutdown_function(function(){
			// Get error info (if there is one)
				$error = error_get_last();
				// Is there an error? Is it fatal or is it a parse error
				if($error !== NULL && ($error['type'] == 4 || $error['type'] == 1)){
					// Try to log it to file
						zajLib::me()->error_handler(E_USER_ERROR, $error['message'], $error['file'], $error['line']);
				}
		});			
	// debug mode needed?
		if(in_array($zajlib->host, $zajconf['debug_mode_domains']) || !empty($zajconf['debug_mode']) || !empty($_SERVER['DEBUG_MODE']) || !empty($_SERVER['MOZAJIK_DEBUG_MODE'])) $zajlib->debug_mode = true;
	// debug mode explicity overridden?
		if($zajlib->debug_mode && isset($_REQUEST['debug_mode'])) $zajlib->debug_mode = false;

	// If LOGIN_AUTH is set up in Apache conf and user does not have proper cookie set, redirect!
		if(!empty($_SERVER['MOZAJIK_LOGIN_AUTH']) && !empty($_SERVER['MOZAJIK_LOGIN_URL'])){
			// Check if whitelisted ip
				$whitelisted = false;
				if(!empty($_SERVER['MOZAJIK_LOGIN_WHITELIST'])){
					// Get all IPs that are whitelisted
						$whitelisted_ips = explode(',', $_SERVER['MOZAJIK_LOGIN_WHITELIST']);
					// Check against my ip
						foreach($whitelisted_ips as $whitelisted_ip){
							if($zajlib->security->ip_in_range($_SERVER['REMOTE_ADDR'], $whitelisted_ip)) $whitelisted = true;
						}
				}
			// Redirect to authentication
				if(!$whitelisted && $_SERVER['MOZAJIK_LOGIN_AUTH'] != $_COOKIE['MOZAJIK_LOGIN_AUTH']){ header("Location: ".$_SERVER['MOZAJIK_LOGIN_URL'].'?from='.urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])); exit; }
		}

	// load controller support
		include_once($zajconf['root_folder'].'/system/class/zajcontroller.class.php');
		
	// all init is completed, after this it's only checks and plugin loads, etcetc.
		if(!empty($GLOBALS['ZAJ_HOOK_INIT']) && is_callable($GLOBALS['ZAJ_HOOK_INIT'])) $GLOBALS['ZAJ_HOOK_INIT']();

	// load plugins
		foreach(array_reverse($zajconf['plugin_apps']) as $plugin){
			$zajlib->plugin->load($plugin);
		}
		
	// update progress check
		if(file_exists($zajlib->basepath."cache/progress.dat") && trim($zajlib->app, '/') != $zajconf['update_appname']) $zajlib->reroute($zajconf['update_appname'].'/progress/');

	// installation check
		$installation_valid = true;
		
		// 1. Check cache and data folder writable
			if(!is_writable($zajlib->basepath."cache/") || !is_writable($zajlib->basepath."data/")) $installation_valid  = false;
		// 2. Check if activated
			if(!is_object($zajlib->mozajik)) $installation_valid = false;
		// 3. Activate model support and check system file validity (fatal error if not)
			if (!(include $zajconf['root_folder'].'/system/class/zajmodel.class.php')) exit("<b>zajlib error:</b> missing zajlib system files or incorrect path given! set in site/index.php!");
		// 4. Check database issues (if mysql is enabled) - this does not actually connect but newly installed sites should already run into (2) activation error. Again, fatal errors if missing.
			if($zajconf['mysql_enabled']){	
				// include the data and fetcher system class
					if (!(include $zajconf['root_folder'].'/system/class/zajdata.class.php')) exit("<b>zajlib error:</b> missing zajlib system files or incorrect path given! set in site/index.php!");
					if (!(include $zajconf['root_folder'].'/system/class/zajfetcher.class.php')) exit("<b>zajlib error:</b> missing zajlib system files or incorrect path given! set in site/index.php!");
				// load db library
					$zajlib->load->library("db");
			}
		// 5. Check user/pass for update
			if(!$zajlib->debug_mode && (empty($zajconf['update_user']) || empty($zajconf['update_password']))) $installation_valid  = false;			

	// Now reroute to install script if installation issues found and not explicitly disabled with $zaj_dont_install_mode
		if(empty($zaj_dont_install_mode) && !$installation_valid && trim($zajlib->app, '/') != $zajconf['update_appname']) $zajlib->redirect($zajconf['update_appname'].'/install/');

	// select the right app and mode (todo: move this stuff to zajlib.class.php eventually)
		// select
			if(!isset($_REQUEST['zajapp']) || $_REQUEST['zajapp']=='' || $_REQUEST['zajapp'] == "default") $zaj_app = $zajconf['default_app'];
			else $zaj_app = $_REQUEST['zajapp'];
		// select the mode (and trim trailing slash)
			if(!isset($_REQUEST['zajmode']) || $_REQUEST['zajmode']=='' || $_REQUEST['zajmode'] == "default") $zaj_mode = '';
			else $zaj_mode = trim($_REQUEST['zajmode'], "/");
		// ready to unset $_REQUEST!
			unset($_REQUEST['zajhtver'], $_REQUEST['zajapp'], $_REQUEST['zajmode']);
	// now create url
		$app_request = $zaj_app."/".$zaj_mode;

?>