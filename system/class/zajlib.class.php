<?php
/**
 * The Mozajik base classes.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Base
 */
 
define('MAX_GLOBAL_EVENT_STACK', 50);
 
/**
 * The zajlib class is a single, global object which stores all the basic methods and variables. It is accessible from all controller and model objects.
 * @package Base
 * @property zajlib_array $array
 * @property zajlib_breadcrumb $breadcrumb
 * @property zajlib_browser $browser
 * @property zajlib_cache $cache
 * @property zajlib_compile $compile
 * @property zajlib_config $config
 * @property zajlib_cookie $cookie
 * @property zajlib_db $db
 * @property zajlib_dom $dom
 * @property zajlib_email $email
 * @property zajlib_error $error
 * @property zajlib_export $export
 * @property zajlib_feed $feed
 * @property zajlib_file $file
 * @property zajlib_form $form
 * @property zajlib_graphics $graphics
 * @property zajlib_html $html
 * @property zajlib_import $import
 * @property zajlib_lang $lang
 * @property zajlib_log $log
 * @property zajlib_model $model
 * @property zajlib_number $number
 * @property zajlib_plugin $plugin
 * @property zajlib_request $request
 * @property zajlib_sandbox $sandbox
 * @property zajlib_security $security
 * @property zajlib_template $template
 * @property zajlib_test $test
 * @property zajlib_text $text
 * @property zajlib_url $url
 * @todo All instance variables should be changed to read-only!
 **/
class zajLib {
	// instance variables	
		// my path and url
			/**
			 * The project root directory. This is automatically determined.
			 * @var string
			 **/
			public $basepath;
			/**
			 * The project root url. This is automatically determined.
			 * @var string
			 **/
			public $baseurl;
			/**
			 * The full request URL without the query string.
			 * @var string
			 **/
			public $fullurl;
			/**
			 * The full request URL including the query string.
			 * @var string
			 **/
			public $fullrequest;
			/**
			 * The host of the current request. This is automatically determined, though keep in mind the end user can modify this!
			 * @var string
			 **/
			public $host;
			/**
			 * The top level domain and the current domain. (example: 'mozajik.org' for www.mozajik.org)
			 * @var string
			 **/
			public $domain="";
			/**
			 * The top level domain. (example: 'org' for www.mozajik.org)
			 * @var string
			 **/
			public $tld="";
			/**
			 * The subdomain, excluding www. (example: 'mail' for www.mail.mozajik.org or for mail.mozajik.org)
			 * @var string
			 **/
			public $subdomain="";
			/**
			 * The currently requested app with trailing slash. Default for example will be 'default/'.
			 * @var string
			 **/
			public $app;
			/**
			 * The currently requested mode with trailing slash.
			 * @var string
			 **/
			public $mode;
			/**
			 * The currently active htaccess file version.
			 * @var integer
			 **/
			public $htver;
			/**
			 * Set to true if current request is a https secure request.
			 * @var boolean
			 **/
			public $https = false;			// boolean - am i in secure mode?
			/**
			 * Set to the current protocol. Can be http: or https:.
			 * @var string
			 **/
			public $protocol = 'http:';
			/**
			 * Set to true if output to user has begun already.
			 * @var boolean
			 **/
			public $output_started = false;
			/**
			 * An object which stores version information.
			 * @var MozajikVersion
			 **/
			public $mozajik;
			/**
			 * A boolean value which if set to false turns off autoloading of model files. This can be useful when integrating in other systems.
			 * @var boolean
			 **/
			public $model_autoloading = true;
			/**
			 * An array which stores the configuration values set in site/index.php.
			 * @var array
			 **/
			public $zajconf;
			 
			
		// my settings

			/**
			 * True if debug mode is currently on.
			 * @var boolean
			 **/
			public $debug_mode = false;
			/**
			 * Template vraiable for storing javascript logs.
			 * @var string
			 **/
			public $js_log;
			/**
			 * An array of custom tag files.
			 * @todo Depricated and should be removed from 1.0 version.
			 * @var boolean
			 **/
			public $customtags = false;
			/**
			 * A count of notices during this execution.
			 * @var integer
			 **/
			public $num_of_notices = 0;
			/**
			 * A count of sql queries during this execution.
			 * @var integer
			 **/
			public $num_of_queries = 0;
			/**
			 * The time of SQL queries in ms
			 * @var integer
			 **/
			public $time_of_queries = 0;
					
		// template variables
			/**
			 * An object which stores the template variables.
			 * @var stdClass|zajVariable
			 **/
			public $variable;

			/**
			 * The global event stack size.
			 * @var integer
			 **/
			public $event_stack = 0;

		// status of plugins

			/**
			 * An array of plugins loaded.
			 * @var array
			 **/
			public $loaded_plugins = array();

	/**
	 * Creates a the zajlib object.
	 * @param string $zaj_root_folder The root from which basepath and others are calculated.
	 * @param array|string $zajconf The configuration array. This can be blank for backwards-compatible reasons.
	 */
	public function __construct($zaj_root_folder, $zajconf = ''){
		// autodetect my path
			if($zaj_root_folder) $this->basepath = realpath($zaj_root_folder)."/"; 
			else $this->basepath = realpath(dirname(__FILE__)."/../../")."/";
		// store configuration
			$this->zajconf = $zajconf;
		// parse query string
			$default_mode = false;
			if(isset($_GET['zajapp'])){
			// autodetect my app
				$this->app = $_GET['zajapp'];
				$this->mode = $_GET['zajmode'];
				$this->htver = $_GET['zajhtver'];
			// set GET query string (cut off zajapp and zajmode)
				unset($_GET['zajapp'], $_GET['zajmode'], $_GET['zajhtver']);
			}
			elseif(isset($_POST['zajapp'])){	// TODO: is this even needed?
			// autodetect my app
				$this->app = $_POST['zajapp'];
				$this->mode = $_POST['zajmode'];
				$this->htver = $_POST['zajhtver'];
			// set POST query string (cut off zajapp and zajmode)
				unset($_POST['zajapp'], $_POST['zajmode'], $_GET['zajhtver']);
			}
		// default app & mode
			if(empty($this->app)){
				$this->app = $this->zajconf['default_app'];
				$this->mode = $this->zajconf['default_mode'];
				$default_mode = true;
			}
		// autodetect my url
			if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") $this->https = false;
			else{
				$this->https = true;
				$this->protocol = 'https:';
			}
		// base url detection
			$this->fullurl = "//".preg_replace('(/{2,})','/', preg_replace("([?&].*|/{1,}$)", "", addslashes($_SERVER['HTTP_HOST']).addslashes($_SERVER['REQUEST_URI'])).'/');
			$this->subfolder = str_ireplace('/site/index.php', '', $_SERVER['SCRIPT_NAME']);
			$this->baseurl = "//".$_SERVER['HTTP_HOST'].$this->subfolder.'/';
		// full request detection (includes query string)
			if(!empty($_GET)) $this->fullrequest = $this->fullurl.'?'.http_build_query($_GET);
			else $this->fullrequest = $this->fullurl.'?';
		// fix my app and mode to always have a single trailing slash
			$this->app = trim($this->app, '/').'/';
			$this->mode = trim($this->mode, '/').'/';
		// autodetect my domain (todo: optimize this part with regexp!)
			$this->host = $_SERVER['HTTP_HOST'];
			// if not an ip address
			if(!preg_match('/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/', $_SERVER['HTTP_HOST'])){
				$ddata = explode(".",$_SERVER['HTTP_HOST']);
				$this->domain = join(".",array_slice($ddata, -2));
				$this->subdomain = str_replace("www.","",join(".",array_slice($ddata, 0, -2)));		// will exclude www.!
				if($this->subdomain == "www") $this->subdomain = "";								// if only www, then set to none!
				$slice = array_slice($ddata, -1);
				$this->tld = reset($slice);
			}
		// loader
			$this->load = new zajLibLoader($this);
		// template variable object
			$this->variable = new zajVariable();				// for all variables
			$this->variable->field = (object) array();			// for field templates

		// check and load installation version (only for database format tracking)
			$installation = @file_get_contents($this->basepath.'cache/install.dat');
			$this->mozajik = @unserialize($installation);
		return true;
	}

	/**
	 * Returns true or false depending on whether the external file has been loaded already. This is simply an alias of the {@link zajLibLoader->is_loaded()}.
	 * @param string $type The type of the file (library, etc.)
	 * @param string $name The name of the file.
	 * @return bool Returns true if the file is loaded, false otherwise.
	 **/
	public function is_loaded($type, $name){
		return $this->load->is_loaded($type, $name);
	}


	/**
	 * Unlike load->app this actually changes the app and mode variables!
	 * @param string $request
	 * @param bool $allow_magic_methods
	 * @return mixed The value returned by the loaded app.
	 */
	public function app_mode_redirect($request, $allow_magic_methods = true){
		// TODO: check - and add if needed - subfolder support!
		// if magic methods aren't allowed
			if(!$allow_magic_methods && strpos($request, "__") !== false) return $this->error("invalid request. invoke magic methods is not allowed here!"); 
		// get all the seperate elements
			$rdata = explode("/",trim("/",$request));
		// now figure out which one is app and which one is mode
			$newapp = array_shift($rdata);
			$newmode = join("_",$rdata);
		// now set my new app&mode
			$this->app = $newapp;
			$this->mode = $newmode;
		// finally, load me and return
			return $this->load->app($request);	
	}


	/**
	 * Returns an error message and exists. Useful for fatal errors.
	 * @param string $message The error message to display and/or log.
	 * @return bool Does not return anything.
	 **/
	public function error($message){
		// Manually load error reporting lib
			/* @var zajlib_error $error */
			$error = $this->load->library('error');
		// Now report the error and send 500 error
			if(!$this->output_started) header('HTTP/1.1 500 Internal Server Error');
			$error->error($message);
			exit;
	}

	/**
	 * Returns a warning message but continues execution.
	 * @param string $message The warning message to display and/or log.
	 * @return bool Always returns false.
	 **/
	public function warning($message){
		// Manually load error reporting lib
			/* @var zajlib_error $error */
			$error = $this->load->library('error');
		// Now report the error
			return $error->warning($message);
	}

	/**
	 * Displays a query in the browser log.
	 * @param string $message
	 **/
	public function query($message){
		// todo: log this instead of printing it?
			if(isset($_GET['query'])){
				$query_backtrace = debug_backtrace(false);
				//$this->js_log .= " zaj.ready(function(){zaj.log('ZAJLIB SQL QUERY: ".str_replace("'","\\'",$message).' in '.$query_backtrace[6]['file'].' on line '.$query_backtrace[6]['line']."'); });";
			}
			$this->num_of_queries++;
	}

	/**
	 * Displays a notice in the browser log.
	 * @param string $message The notice message to log.
	 **/
	public function notice($message){
		// todo: log this instead of printing it?
			if(isset($_GET['notice'])){
				//if($_GET['notice']=="screen") print "<div style='border: 2px red solid; padding: 5px;'>MOZAJIK NOTICE: $message</div>";
				//else $this->js_log .= " zaj.ready(function(){zaj.log('ZAJLIB NOTICE: ".str_replace("'","\\'",$message)."'); });";
			}
			$this->num_of_notices++;
		// log notices?
			if($this->zajconf['mysql_enabled'] && $this->zajconf['error_log_enabled'] && $this->zajconf['error_log_notices']) MozajikError::log($message, 'notice');
	}
	
	/**
	 * Custom error handler to override the PHP defaults.
	 **/
	public function error_handler($errno, $errstr, $errfile, $errline){
		// get current error_reporting value
			$errrep = error_reporting();
		
		if($errrep){
			switch ($errno) {
		        case E_NOTICE:
		        case E_USER_NOTICE:
		           $this->notice("$errstr on line $errline in file $errfile");
		            break;
		        case E_WARNING:
		        case E_USER_WARNING:
		           $this->warning("$errstr on line $errline in file $errfile");
		            break;
		        case E_ERROR:
		        case E_USER_ERROR:
		           $this->error("$errstr on line $errline in file $errfile");
		            break;
		        default:
		            //$errors = "Unknown Error Occurred";
		            break;
	        }
        }
   		return true;
	}
	
	/**
	 * Send an ajax response to the browser.
	 * @param string $message The content to send to the browser.
	 * @return bool Does not return anything.
	 **/
	public function ajax($message){
		header("Content-Type: application/x-javascript; charset=UTF-8");
		print $message;
		exit;
	}

	/**
	 * Send json data to the browser.
	 * @param string|array|object $data This can be a json-encoded string or any other data (in this latter case it would be converted to json data).
	 * @return bool Does not yet return anything.
	 **/
	public function json($data){
		// If the data is not already a string, convert it with json_encode()
			if(!is_string($data)) $data = json_encode($data);
		// Now output and exit!
			header("Content-Type: application/json; charset=UTF-8");
			print $data;
		exit;
	}

	/**
	 * Redirect the user to relative or absolute URL
	 * @param string $url The specific url to redirect the user to.
	 * @return bool Does not yet return anything.
	 **/
	public function redirect($url){
		// Now redirect
			if($this->url->is_url($url)) header("Location: ".$url);
			else header("Location: ".$this->baseurl.$url);
		exit;
	}

	/**
	 * Reroute processing to another app controller.
	 * @param string $request The request relative to my baseurl.
	 * @param array|bool $optional_parameters An array of parameters to be passed.
	 * @param boolean $reroute_to_error When set to true (the default), the function will reroute requests to the proper __error method.
	 * @param boolean $call_load_method If set to true (the default), the __load() magic method will be called.
	 * @return mixed Will return whatever the app method returns.
	 */
	public function reroute($request, $optional_parameters = false, $reroute_to_error = true, $call_load_method = true){
		// request must be a string
			if(!is_string($request)) $this->warning('Invalid reroute request!');		
		// load the app
			return $this->load->app($request, $optional_parameters, $reroute_to_error, $call_load_method);
	}

	/**
	 * Magic method to automatically load libraries on first request.
	 **/
	public function __get($name){
		// return from loader
			return $this->load->library($name);
	}
	
	/**
	 * Magic method to display error when the object is converted to string.
	 **/
	public function __toString(){ return "[zajlib object]"; }
	
	/**
	 * Magic method to display debug information.
	 **/
	public function __toDebug(){ return "[zajlib object]"; }

	/**
	 * Get the global object and return it statically.
	 * @return zajLib Return me.
	 **/
	public static function me(){
		return $GLOBALS['zajlib'];
	}
}

/** 
 * An abstract class extended by library class files.
 * @package Base
 * @property zajLib $zajlib
 **/
abstract class zajLibExtension{	
	/**
	 * A reference to the global zajlib object.
	 * @var zajLib
	 **/
	protected $zajlib;
	/**
	 * A string which stores the name of my system library.
	 * @var string
	 **/
	protected $system_library;
	/**
	 * Stores any options that were created when loading the library. See second param $optional_parameters of {@link zajLibLoader->library()}.
	 * $var array
	 **/
	public $options;
	
	/**
	 * Creates a new {@link zajLibExtension}
	 * @param zajLib $zajlib A reference to the global zajlib object.
	 * @param string $system_library The name of the system library.
	 **/
	public function __construct(&$zajlib, $system_library){
		// set my system library
		$this->system_library = $system_library;
		// set my parent
		$this->zajlib =& $zajlib;
	}
	
	/**
	 * A magic method used to display an error message if the method is not available.
	 * @param string $method The method to call.
	 * @param array $args An array of arguments.
	 **/
	public function __call($method, $args){
		// throw warning
		$this->zajlib->warning("The method $method is not available in library {$this->system_library}!");
	}
}

/** 
 * This class allows the user to load files into the Mozajik system. These files may be libraries, apps, models, etc.
 * @package Base
 **/
class zajLibLoader{
	/**
	 * A reference to the global zajlib object.
	 * @var zajLib
	 **/
	protected $zajlib;

	/**
	 * A multi-dimensional array with the loaded files.
	 * @var array
	 **/
	public $loaded = array();

	/**
	 * Creates a new {@link zajLibLoader}. This is run when initializing the request.
	 * @param zajLib $zajlib A reference to the global zajlib object.
	 **/
	public function __construct(&$zajlib){
		// set my parent
			$this->zajlib =& $zajlib;
	}
	

	/**
	 * Load a controller file.
	 * @param string $file_name The relative file name of the controller to load.
	 * @param array|bool $optional_parameters An array or a single parameter which is passed as the first parameter to __load()
	 * @param boolean $call_load_method If set to true (the default), the __load() magic method will be called.
	 * @return boolean Returns whatever the __load() method returns. This should be a boolean value. Explicit false value means trouble.
	 * @todo Rewrite $controller_name generation to regexp
	 **/
	public function controller($file_name, $optional_parameters=false, $call_load_method=true){
		// Load the file
			$this->zajlib->load->file('controller/'.$file_name);
		// Remove .ctl.php off of end and / to _
			$controller_name = str_ireplace('/', '_', substr($file_name, 0, -8)); 
		// If default, then fix it!
			if(substr($controller_name, -8) == '_default') $controller_name = substr($controller_name, 0, -8); 
		// Create my class	
			$controller_class = 'zajapp_'.$controller_name;
		// Create a new object
			$cobj = new $controller_class($this->zajlib, $controller_name);
			if($call_load_method && method_exists($cobj, "__load")) return $cobj->__load($optional_parameters);
		// Return true since no __load method
			return true;
	}


	/**
	 * Load a library file.
	 * @param string $name The name of the library to load.
	 * @param array|bool $optional_parameters An array of optional parameters which are stored in {@link zajLibExtension->options}
	 * @return zajLibExtension|bool Returns a zajlib object or false if fails.
	 */
	public function library($name, $optional_parameters=false){
		// is it loaded already?
			if(isset($this->loaded['library'][$name])) return $this->loaded['library'][$name];
		// try to load the file
			$result = $this->file("library/$name.lib.php", false);			
		// if library does not exist
			if(!$result) $this->zajlib->error("Tried to auto-load library ($name), but failed: library file not found!");
			else{
				// return the new lib object
					$library_class = 'zajlib_'.$name;
					$libobj = new $library_class($this->zajlib, $name);
					$libobj->options = $optional_parameters;
					$this->loaded['library'][$name] = $libobj;
					return $this->loaded['library'][$name];
			}
	}
	
	/**
	 * Load a model file.
	 * @param string $name The name of the model to load.
	 * @param array $optional_parameters This will be passed to the __load method (not yet implemented)
	 * @todo Implement optional parameters.
	 **/
	public function model($name, $optional_parameters=false){
		// is it loaded already?
			if(isset($this->loaded['model'][$name])) return true;
		// now just load the file
			$result = $this->file("model/".strtolower($name).".model.php", false);
		// return result
			if(!$result) return $this->zajlib->error("model or app controller object <strong>$name</strong> has not been properly defined or does not exist! is the class name correctly defined in the model/ctl file?");
			else{
				// set it as loaded
					$this->loaded['model'][$name] = true;			
				return true;
			}
	}	

	/**
	 * Load an app file and call the appropriate method.
	 * @param string $request The application request.
	 * @param array $optional_parameters An array of parameters passed to the request method.
	 * @param boolean $reroute_to_error When set to true (the default), the function will reroute requests to the proper __error method.
	 * @param boolean $call_load_method If set to true (the default), the __load() magic method will be called.
	 **/
	public function app($request, $optional_parameters=false, $reroute_to_error=true, $call_load_method=true){
		// check for security
			if(substr_count($request, "..") > 0) $this->zajlib->error("application request ($request) could not be processed: illegal characters!");
		// remove the starting and trailing slash
			$request = trim($request, '/\\');
		// set defaults
			$result = false;
			$fnum = 1;
			$fmax = substr_count($request, "/")+1;
		// break into pieces
			$rdata = explode("/",$request);
		// order: /admin/whatever/ => 1. admin.ctl.php / whatever(), 2. admin/whatever.ctl.php / main() 3. admin/whatever/default.ctl.php / main() 4. admin/default.ctl.php / whatever() 5. default.ctl.php / admin_whatever();
		// - __error is called on the lowest default.ctl.php found. if the error is not found there, then it does not propogate upward...
			
		// now try to go through various alternatives ( 1. admin.ctl.php / whatever, 2. admin/whatever.ctl.php )
			while(!$result && $fnum <= $fmax){
				// create file name
					$zaj_app = implode("/", array_slice($rdata, 0, $fnum));
					$zaj_mode = implode("_", array_slice($rdata, $fnum));
				// now try to load the file
					$result = $this->file("controller/".strtolower($zaj_app).".ctl.php", false);
				// add one
					$fnum++;
			}
		// Fnum is now one two big!
			$fnum--;
		// now try to go through various alternatives (3. admin/whatever/default.ctl.php / 4. admin/default.ctl.php) if app is defined
			while(!empty($zaj_app) && !$result && $fnum >= 1){
				// create file name
					$zaj_app = implode("/", array_slice($rdata, 0, $fnum));
					$zaj_mode = implode("_", array_slice($rdata, $fnum));
				// now try to load the file
					$result = $this->file("controller/".strtolower($zaj_app).'/'.strtolower($this->zajlib->zajconf['default_app']).'.ctl.php', false);
				// add one
					$fnum--;
			}
		// if result still not successful just do default (5. default.ctl.php)
			if(!$result){
				// create file name
					$zaj_app = $this->zajlib->zajconf['default_app'];
					$zaj_mode = implode("_", $rdata);
				// now try to load the file
					$result = $this->file("controller/".strtolower($zaj_app).".ctl.php", false);
					if(!$result) $this->error("default controller not in place. you must have a $zaj_app.ctl.php file in your controller folder!");
			}
		
		// if zaj_mode not defined
			if(empty($zaj_mode)) $zaj_mode = strtolower($this->zajlib->zajconf['default_mode']);
		
		//////////////////////////////////////////////////
		// - zaj_mode and zaj_app are properly defined!
		// - now, let's direct to the right method
		//////////////////////////////////////////////////
		
		// make it a proper object name
			$zaj_app = str_ireplace('/', '_', $zaj_app);
		
		// set zajlib's app and mode
			$this->app = $zaj_app;
			$this->mode = $zaj_mode;
		

		// assemble optional parameters
			if(!$optional_parameters) $optional_parameters = array();
			elseif(!is_array($optional_parameters)){
				$op[] = $optional_parameters;
				$optional_parameters = $op;
			}

		// start the app controller
			$app_object_name = "zajapp_".$zaj_app;
			$my_app = new $app_object_name($this->zajlib, $zaj_app);
		// fire __load magic method if call_load_method is true
			$load_result = true;
			if($call_load_method && method_exists($my_app, "__load")){
				$load_result = $my_app->__load($zaj_mode, $optional_parameters);
			}
		// if __load() explicitly returns false, then do not continue with but instead return false
			if($load_result === false) return false;
					
		// if method does not exist, call __error
			// TODO: make errors go backwards as well: check child folder's default controllers first!
			if(!method_exists($my_app, $zaj_mode)){
				// If I have an __error method and it is allowed, reroute to that
					if(method_exists($my_app, '__error') && $reroute_to_error) return $my_app->__error($zaj_mode, $optional_parameters);
				// If no error method, but $reroute_to_error is true, throw an error
					elseif($reroute_to_error){
						// Check if not already default
							if($zaj_app == $this->zajlib->zajconf['default_app']) $this->zajlib->error("Could not route request and default controller does not implement __error() method.");
						// Split into sections and remerge into parent
							$parent_controller = implode('_', array_slice(explode('_', $zaj_app), 0, -1));
						// Set to default
							if(empty($parent_controller)) $parent_controller = $this->zajlib->zajconf['default_app'];
						// Reroute to parent method's error method
							// TODO: fix so that first parameter passed is correct (currently it is not!)
							return $this->app($parent_controller.'/__error', array($zaj_app.'_'.$zaj_mode, $optional_parameters));
						//return $this->zajlib->error("Could not route $request and $zaj_app no __error method found.");
					}
				// If reroute to error is disabled, then dont check and dont make noise - just return true.
					else return true;
			}
		// it exist, so call!
			else return call_user_func_array(array(&$my_app,$zaj_mode),$optional_parameters);
	}	

	/**
	 *  @todo Both js and css - preloading could be made more effecient by including them in the header during compilation.
	 *		Certain fields require certain js and css files, so it should be easy to rewrite this such that all of this is
	 *		already done during template compilation. This would eliminate the need for run-time file_exist and client-side
	 *		in-line loading of files, both of which are less efficient.
	 **/
	
	/**
	 * Load a js file runtime.
	 * @param string $file_path The file path relative to the system or site folder.
	 * @param boolean $check_if_exists Not implemented.
	 * @return void|bool Prints the string generated, returns nothing or true if already printed.
	 * @depricated
	 **/
	public function js($file_path, $check_if_exists = false){
		// is it loaded already?
			if(isset($this->loaded['js'][$file_path])) return true;
		// set it as loaded
			$this->loaded['js'][$file_path] = true;
		// check to see if this file exists in the user folder...if so, then use that instead of the system-provided version
			if(file_exists($this->zajlib->basepath."site/js/$file_path")) $subfolder = "";
			else $subfolder = "system";
		// now load the js file into zajlib.js variable OR print it
			if(!$this->zajlib->output_started) $this->zajlib->variable->js .= "\n\t\t<script language='JavaScript' src='".$this->zajlib->baseurl."$subfolder/js/$file_path' type='text/javascript'></script>";
			else print "<script>zajlib.load_js('".$this->zajlib->baseurl."$subfolder/js/$file_path');</script>";
	}

	/**
	 * Load a css file runtime.
	 * @param string $file_path The file path relative to the system or site folder.
	 * @param boolean $check_if_exists Not implemented.
	 * @return void|bool Prints the string generated, returns nothing or true if already printed.
	 * @depricated
	 **/
	public function css($file_path, $check_if_exists = false){
		// is it loaded already?
			if(isset($this->loaded['css'][$file_path])) return true;
		// set it as loaded
			$this->loaded['css'][$file_path] = true;
		// check to see if this file exists in the user folder...if so, then use that instead of the system-provided version
			if(file_exists($this->zajlib->basepath."site/css/$file_path")) $subfolder = "";
			else $subfolder = "system";
		// now load the css file into zajlib.css variable OR print it
			if(!$this->zajlib->output_started) $this->zajlib->variable->css .= "\n\t\t<link rel='stylesheet' type='text/css' href='".$this->zajlib->baseurl."$subfolder/css/$file_path' type='text/javascript'></script>";
			else print "<script>new Asset.css('".$this->zajlib->baseurl."$subfolder/css/$file_path', { oncomplete: function(){ zajlib.onCssLoad(); } } ); zajlib.asset_css_load_at_runtime++;</script>";
	}
	
	/**
	 * Include a file as relative to the base path.
	 * @param string $file_path The file path relative to the base path.
	 * @param boolean $fail_with_error_message If error, then fail with a fatal error.
	 * @param boolean $include_now If set to true (the default), the file will also be included. On false, only the file path will be returned (and $this->loaded will not be set to true!).
	 * @param string $scope Can be "full" (looks for all variations - default), "specific" (looks for a specific relative path and fails if not found), "project" (looks for anything in the projects folder), "plugin" (looks for anything in the plugins folder), "system" (looks for anything in the system folder)
	 * @return boolean Returns false on error, otherwise returns the path of the file found, relative to to basepath
	 **/
	public function file($file_path, $fail_with_error_message = true, $include_now = true, $scope = "full"){
		// is it loaded already?
			if(isset($this->loaded['file'][$file_path])) return true;
		// test file path
			if(!$this->check_path($file_path)) $this->zajlib->error("Invalid file path detected when including file. Please refer to manual for requirements.");
		
		
		// Is it a specific path scope? If so, just try to load it!
			if($scope == "specific"){
				if(file_exists($this->zajlib->basepath.$file_path) && (!$include_now || include_once $this->zajlib->basepath.$file_path)){
					if($include_now) $this->loaded['file'][$file_path] = true;
					return $file_path;
				}
			}
		// Else, I need to search subfolders
			else{		
				// 1. try the project path	
					if($scope == "full" || $scope == "project"){
						if(file_exists($this->zajlib->basepath.'app/'.$file_path) && (!$include_now || include_once $this->zajlib->basepath.'app/'.$file_path)){
							if($include_now) $this->loaded['file'][$file_path] = true;
							return 'app/'.$file_path;
						}
					}
				// 2. try plugin paths in order					
					if($scope == "full" || $scope == "plugin"){
						foreach($this->zajlib->loaded_plugins as $app){
							if(file_exists($this->zajlib->basepath.'plugins/'.$app.'/'.$file_path) && (!$include_now || include_once $this->zajlib->basepath.'plugins/'.$app.'/'.$file_path)){
								// set file as loaded and return true
									if($include_now) $this->loaded['file'][$file_path] = true;
									return 'plugins/'.$app.'/'.$file_path;
							}
						}
					}
				// 3. try the system path
					if($scope == "full" || $scope == "system"){
						if(file_exists($this->zajlib->basepath.'system/app/'.$file_path) && (!$include_now || include_once $this->zajlib->basepath.'system/app/'.$file_path)){
							if($include_now) $this->loaded['file'][$file_path] = true;
							return 'system/app/'.$file_path;
						}
					}
				// 4. try the system plugins
					if($scope == "full" || $scope == "system"){
						foreach($this->zajlib->zajconf['system_apps'] as $app){
							if(file_exists($this->zajlib->basepath.'system/plugins/'.$app.'/'.$file_path) && (!$include_now || include_once $this->zajlib->basepath.'system/plugins/'.$app.'/'.$file_path)){
								if($include_now) $this->loaded['file'][$file_path] = true;
								return 'system/plugins/'.$app.'/'.$file_path;
							}
						}
					}
			}
		// None worked, so fail with error or return false		
			if($fail_with_error_message) $this->zajlib->error("Search for included file $file_path failed. Is the plugin activated? Is the file where it should be?");
			else return false;
	}
	
	/**
	 * Checks to see if file of certain type has been loaded.
	 * @param string $type The type (for example, 'library')
	 * @param string $name The name of the element to load.
	 * @return boolean True if already loaded, false otherwise.
	 **/
	public function is_loaded($type, $name){
		if(isset($this->loaded[$type][$name]) && $this->loaded[$type][$name]) return true;
		else return false;
	}

	/**
	 * Does a security check to see if the given path is valid and is chrooted.
	 * @param string $file_path The path to check.
	 * @return boolean Returns true if the path is valid and ready to be used. False otherwise.
	 * @todo Add more checks!
	 */
	public static function check_path($file_path){
		if(substr_count($file_path, "..") > 0) return false;
		// todo: do some more checks here!
		return true;
	}
}

/** 
 * Basic field structure is stored in this class. This is a static class used to create the field array structure.
 * @package Base
 **/
class zajDb {
		
		/**
		 * This method returns the type and structure of the field definition in an array format.
		 **/
		public static function __callStatic($method, $args){
			// Create my db field
				$zdb = new zajDb();
			// Create my datastructure
				$zdb->type = $method;
				$zdb->options = $args;
			// Now load my settings file
				$cname = 'zajfield_'.$method;
				$result = zajLib::me()->load->file("fields/$method.field.php", false);
				if(!$result) zajLib::me()->error("Field type '$method' is not defined. Was there a typo? Are you missing the field definition plugin file?");
			// Set my settings
				$zdb->in_database = $cname::in_database;
				$zdb->use_validation = $cname::use_validation;
				$zdb->use_get = $cname::use_get;
				$zdb->use_save = $cname::use_save;
				$zdb->use_filter = $cname::use_filter;
				$zdb->search_field = $cname::search_field;
				$zdb->edit_template = $cname::edit_template;
				$zdb->show_template = $cname::show_template;
			// return
			return $zdb;
		}
}


/** 
 * Full field structure is stored in this class. These are the default return values of each method which are overridden in the field definition files.
 * @package Base
 * @todo Make this abstract?
 **/
class zajField {
	protected $zajlib;					// object - a reference to the global zajlib object
	protected $class_name;				// string - class name of the parent class
	public $name;						// string - name of this field
	public $options;					// array - this is an array of the options set in the model definition
	public $type;						// string - type of the field (mozajik type, not mysql)

	/**
	 * Creates a field definition object
	 **/
	public function __construct($field_class, $name, $options, $class_name, &$zajlib){
		$this->zajlib =& $zajlib;	
		$this->name = $name;
		$this->options = $options;
		$this->class_name = $class_name;
		$this->type = substr($field_class, 9);
	}

	/**
	 * Check to see if input data is valid.
	 * @param $input mixed The input data.
	 * @return boolean Returns true if validation was successful, false otherwise.
	 **/
	public function validation($input){
		return true;
	}	

	/**
	 * Preprocess the data before returning the data from the database.
	 * @param $data mixed The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return mixed Return the data that should be in the variable.
	 **/
	public function get($data, &$object){
		return $data;
	}

	/**
	 * Preprocess the data before saving to the database.
	 * @param $data mixed The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 **/
	public function save($data, &$object){
		return $data;	
	}

	/**
	 * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
	 * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
	 * @param array $value An array of values specifying what type of filter this is.
	 * @return bool Returns false by default.
	 */
	public function filter(&$fetcher, $value){
		return false;	
	}

	/**
	 * This method allows you to create a subtable which is associated with this field.
	 * @return bool Return the table definition. False if no table.
	 **/
	public function table(){
		return false;
	}

	/**
	 * Defines the structure and type of this field in the mysql database.
	 * @return array Returns in array with the database definition.
	 **/
	public function database(){
		return array();
	}

	/**
	 * Returns an error message, but is this still needed?
	 **/
	public function form(){
		return "[undefined form field for $this->name. this is a bug in the system or in a plugin.]";
	}

	/**
	 * A static create method used to initialize this object.
	 * @param string $name The name of this field.
	 * @param array $field_def The array definition of this field as defined by {@link zajDb}
	 * @param string $class_name The class name of the model.
	 **/
	public static function create($name, $field_def, $class_name=''){
		// get options and type
			$options = $field_def->options;
			$type = $field_def->type;
		// load field object file
			zajLib::me()->load->file('/fields/'.$type.'.field.php');
			$field_class = 'zajfield_'.$type;
		// create and return
			return new $field_class($name, $options, $class_name, zajLib::me());
	}

	/**
	 * This method is called just before the input field is generated. Here you can set specific variables and such that are needed by the field's GUI control.
	 * @param array $param_array The array of parameters passed by the input field tag. This is the same as for tag definitions.
	 * @param zajCompileSource $source This is a pointer to the source file object which contains this tag.
	 * @return bool Returns true by default.
	 **/
	public function __onInputGeneration($param_array, &$source){
		// does not do anything by default
		return true;
	}

}

/** 
 * The class (single object) which stores template variables.
 * @package Base
 * @todo What are the benefits of defining this class? Really, we could just have an (object) array();
 **/
class zajVariable {
	/**
	 * An array of the variable data stored herein.
	 **/
	private $data = array();
	
	/**
	 * Magic method to return the data.
	 **/
	public function __get($name){
		if(isset($this->data[$name])) return $this->data[$name];
		else return '';
	}

	/**
	 * Magic method to set the data.
	 **/
	public function __set($name, $value){
		$this->data[$name] = $value;
	}	

	/**
	 * Magic method to return debug information
	 * @return string Returns some nice debug info.
	 **/
	public function __toDebug(){
		// Init the string
		$str = "";
		// Generate output
		foreach($this->data as $name=>$value){
			if(is_array($value) || is_object($value)){
				foreach($value as $k=>$v){
					if(is_object($v)) $str .= "\n[$name][$k] => [object]";
					else $str .= "\n[$name][$k] => ".str_replace("\n","\n\t\t",$v);
				}
			}
			else $str .= "\n[$name] => $value";
		}
		return $str; 
	}
}


/** 
 * Autoloads object files
 **/
function __autoload($class_name){	
	// If autoloading enabled or not
		if(!zajLib::me()->model_autoloading) return false;
	// check if models enabled
		if(!zajLib::me()->zajconf['mysql_enabled']) zajLib::me()->error("Mysql support not enabled for this installation, so model $class_name could not be loaded!");
	// load the model
		return zajLib::me()->load->model($class_name);
}


?>