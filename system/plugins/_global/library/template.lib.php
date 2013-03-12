<?php
/**
 * Template related methods.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_template extends zajLibExtension {

	/**
	 * This variables is used to verify the validity of each included file.
	 **/
	private $template_valid = false;

	/**
	 * Compile the file specified by file_path.
	 * @param string $source_path This is the source file's path relative to any of the active view folders.
	 * @param bool|string $destination_path This is the destination file's path relative to the final compiled view folder. If not specified, the destination will be the same as the source (relative), which is the preferred way of doing things. You should only specify this if you are customizing the template compilation process.
	 * @return void
	 */
	private function compile($source_path, $destination_path=false){
		// load compile library
			$this->zajlib->compile->compile($source_path, $destination_path);
	}
	
	/**
	 * Prepares all files and variables for output. Compiles the file if necessary.
	 * @param string $source_path The path to the template to be compiled relative to the active view folders. 
	 * @param boolean $force_recompile If set to true, the template file will be recompiled even if a cached version already exists. (False by default.)
	 * @param string $destination_path This is the destination file's path relative to the final compiled view folder. If not specified, the destination will be the same as the source (relative), which is the preferred way of doing things. You should only specify this if you are customizing the template compilation process.
	 * @return string Returns the file path of the file to include.
	 **/
	private function prepare($source_path, $force_recompile=false, $destination_path=false){
		// include file path
			if(!$destination_path) $include_file = $this->zajlib->basepath."/cache/view/".$source_path.".php";
			else $include_file = $this->zajlib->basepath."/cache/view/".$destination_path.".php";
		// if force_recompile or debug_mode or not yet compiled then recompile
			if($this->zajlib->debug_mode || $force_recompile || !file_exists($include_file)) $this->compile($source_path, $destination_path);
		// set up my global {{zaj}} variable object
			$this->zajlib->variable->zaj = new zajlib_template_zajvariables($this->zajlib);
		// set up a few other globals
			$this->zajlib->variable->baseurl = $this->zajlib->baseurl;
			$this->zajlib->variable->self = $this->zajlib->variable->app.'/'.$this->zajlib->variable->mode;
			$this->zajlib->variable->fullurl = $this->zajlib->fullurl;
			$this->zajlib->variable->fullrequest = $this->zajlib->fullrequest;
		
		/*******
		 * ALL VARIABLES BELOW ARE NOT TO BE USED! THEY WILL BE REMOVED IN A FUTURE RELEASE!
		 *******/
		
		// access to request variables and version info
			$this->zajlib->variable->debug_mode = $this->zajlib->variable->zaj->bc_get('debug_mode');
			$this->zajlib->variable->app = $this->zajlib->variable->zaj->bc_get('app');
			$this->zajlib->variable->mode = $this->zajlib->variable->zaj->bc_get('mode');
			$this->zajlib->variable->mozajik = $this->zajlib->variable->zaj->bc_get('mozajik');
		// init js layer
		// requests and urls
			if($this->zajlib->https) $this->zajlib->variable->protocol = 'https';
			else $this->zajlib->variable->protocol = 'http';
			$this->zajlib->variable->get = (object) $_GET;
			$this->zajlib->variable->post = (object) $_POST;
			$this->zajlib->variable->cookie = (object) $_COOKIE;
			$this->zajlib->variable->request = (object) $_REQUEST;
			if(!empty($_SERVER['HTTP_REFERER'])) $this->zajlib->variable->referer = $_SERVER['HTTP_REFERER'];
		return $include_file;
	}
	
	/**
	 * Returns an object containing the built-in 'zaj' variables that are available to the template.
	 **/
	public function get_variables(){
		return new zajlib_template_zajvariables($this->zajlib);
	}
	
	/**
	 * Performs the actual display or return of the contents.
	 * @param string $include_file The full path to the file which is to be included.
	 * @param boolean $return_contents If set to true, the compiled contents will be returned by the function and not sent to the browser (as is the default).
	 * @return string If requested by the $return_contents parameter, it returns the entire generated contents.
	 **/
	private function display($include_file, $return_contents = false){
		// now include the file
			// but should i return the contents?
				if($return_contents) ob_start();	// start output buffer
			// validity
				$this->template_valid = false;
			// now include the file
				include($include_file);
			// verify validity
				if(!$this->template_valid){
					$this->zajlib->warning("Invalid template cache file found: $included_file. File was reset.");
					// TODO: add code for template reset
				}
				if($return_contents){ 				// end output buffer
					$contents = ob_get_contents();
					ob_end_clean();
					return $contents;
				}
				else return true;
	}

	/**
	 * Display a specific template.
	 * If the request contains zaj_pushstate_block, it will reroute to block. See Mozajik pushState support for more info.
	 * @param string $source_path The path to the template to be compiled relative to the active view folders. 
	 * @param boolean $force_recompile If set to true, the template file will be recompiled even if a cached version already exists. (False by default.)
	 * @param boolean $return_contents If set to true, the compiled contents will be returned by the function and not sent to the browser (as is the default).
	 * @param boolean $custom_compile_destination If set, this allows you to compile the template to a different location than the default. This is not recommended unless you really know what you are doing!
	 * @return string If requested by the $return_contents parameter, it returns the entire generated contents.
	 **/
	function show($source_path, $force_recompile = false, $return_contents = false, $custom_compile_destination = false){
		// do i need to show by block (if pushState request detected)
			if(!empty($_REQUEST['zaj_pushstate_block']) && preg_match("/^[a-z0-9_]{1,25}$/", $_REQUEST['zaj_pushstate_block'])){ $r = $_REQUEST['zaj_pushstate_block']; unset($_REQUEST['zaj_pushstate_block']); return $this->block($source_path, $r, $force_recompile, $return_contents); }
		// prepare
			$include_file = $this->prepare($source_path, $force_recompile, $custom_compile_destination);
		// set that we have started the output
			$this->zajlib->output_started = true;
		// now display or return
			return $this->display($include_file, $return_contents);
	}
	
	/**
	 * Extracts a specific block from a template and displays only that. This is useful for ajax requests.
	 * @param string $source_path The path to the template to be compiled relative to the active view folders. 
	 * @param string $block_name The name of the block within the template.
	 * @param boolean $recursive If set to true (false by default), all parent files will be checked for this block as well.
	 * @param boolean $force_recompile If set to true, the template file will be recompiled even if a cached version already exists. (False by default.)
	 * @param boolean $return_contents If set to true, the compiled contents will be returned by the function and not sent to the browser (as is the default).
	 **/
	function block($source_path, $block_name, $recursive = false, $force_recompile = false, $return_contents = false){
		// first do a show to compile (if needed)
			$include_file = $this->prepare($source_path, $force_recompile);
		// set that we have started the output
			$this->zajlib->output_started = true;
		// now extract and return the block content
			// generate appropriate file name
				$include_file = $this->zajlib->basepath."/cache/view/__block/".$source_path.'-'.$block_name.'.html.php';
			// check to see if block even exists
				if(!file_exists($include_file)){
					// if recursive and extended_path exists, try
					if($recursive){
						// see if extended
							$extend = $this->zajlib->compile->tags->extend;
							if($extend) return $this->block($extend, $block_name, $recursive, $force_recompile, $return_contents);
					}
					return $this->zajlib->error("Template block display failed! The request block '$block_name' could not be found in template file '$source_path'.");
				}
		// now display or return
			return $this->display($include_file, $return_contents);
	}

	/**
	 * This function will push the contents of the template to the user as a downloadable file. Useful for generating output like xml, csv, etc. The method will exit after execution is finished.
	 * @param string $source_path Path to the template file.
	 * @param string $mime_type The mime type by which to initiate the download.
	 * @param string $download_file_name The file name to use for this download.
	 * @param bool $force_download If set to true, the content will never be displayed within the browser. True is the default and the recommended setting.
	 * @param bool $force_recompile If set to true, the template will always be forced to recompile. Defaults to false.
	 */
	function download($source_path, $mime_type, $download_file_name, $force_download=true, $force_recompile=false){
		// pass file thru to user
			header('Content-Type: '.$mime_type);
			//header('Content-Length: '.filesize($source_path)); // can i somehow detect this?!
			if($force_download) header('Content-Disposition: attachment; filename="'.$download_file_name.'"');
			else header('Content-Disposition: inline; filename="'.$download_file_name.'"');
			ob_clean();
			flush();
		// now sent to show
			$this->show($source_path, $force_recompile);
			exit;
	}


	/**
	 * Will return the output as an ajax response, setting the appropriate headers.
	 * @param string $source_path The path to the template to be compiled relative to the active view folders. 
	 * @param string $block_name If specified, only this block tag of the template file will be returned in the request.
	 * @param boolean $force_recompile If set to true, the template file will be recompiled even if a cached version already exists. (False by default.)
	 **/
	function ajax($source_path, $block_name = false, $force_recompile = false){
		// send ajax header
			if(!$this->zajlib->output_started) header("Content-Type: application/x-javascript; charset=UTF-8");
		// now just show
			if(!is_string($block_name)) $this->show($source_path, $force_recompile);
			else $this->block($source_path, $block_name, $force_recompile);
	}
	
	/**
	 * Emails the template in an HTML format and returns true if successful.
	 * @param string $source_path The path to the template to be compiled relative to the active view folders. 
	 * @param string $from The email which is displayed as the from field.
	 * @param string $to The email to which this message should be sent.
	 * @param string $subject A string with the email's subject.
	 * @param string $sendcopyto If set, a copy of the email will be sent (bcc) to the specified email address. By default, no copy is sent.
	 * @param string $bounceto If set, the email will bounce to this address. By default, bounces are ignored and not sent anywhere.
	 * @param string $plain_text_version The path to the template to be compiled for the plain text version.
	 **/
	function email($source_path, $from, $to, $subject, $sendcopyto = "", $bounceto = "", $plain_text_version = ""){
		// capture output of this template
			$body = $this->show($source_path, false, true);
		// capture output of plain text template
			$plain_text_version = $this->show($plain_text_version, false, true);
		// load email library
			//$this->zajlib->load->library('email');
			// todo: it is probably a good idea to add a warning on a failed email send attempt
			return $this->zajlib->email->send_html($from, $to, $subject, $body, $sendcopyto, $bounceto, $plain_text_version);
	}
}

/**
 * This is a special class which loads up the template variables when requested.
 * @author Aron Budinszky <aron@mozajik.org>
 **/
class zajlib_template_zajvariables {
	private $zajlib;	// The local copy of zajlib variable
	
	var $baseurl; 		// The base url of this project.
	var $self; 			// My own app/mode request.
	var $fullurl; 		// The base url + the request.
	var $fullrequest; 	// The base url + the request + query string.	
	
	/**
	 * Initializes all of the important variables which are always available.
	 **/
	public function __construct($zajlib){
		// First get my zajlib
			$this->zajlib = $zajlib;
		// Important variables
			$this->baseurl = $this->zajlib->baseurl;
			$this->self = $this->zajlib->variable->app.'/'.$this->zajlib->variable->mode;
			$this->fullurl = $this->zajlib->fullurl;
			$this->fullrequest = $this->zajlib->fullrequest;
		// The rest of the variables are built on request via the __get() magic method...
	}
	
	/**
	 * Backwards-compatible access to these variables (will throw warning).
	 * @todo Remove this from a future version (when the depricated vars are removed as well)
	 **/
	public function bc_get($name){
		//$this->zajlib->warning("You are using an depricated variable ({{{$name}}}). Please use {{zaj.variable_name}} for all such variables.");
		return $this->__get($name);
	}	
	
	/**
	 * Generate and return all other useful variables only upon request.
	 **/
	public function __get($name){
		switch($name){
			// Debug mode			
				case 'debug':
				case 'debug_mode':
					return $this->zajlib->debug_mode;
			// My current app
				case 'app': return $this->zajlib->app;
			// My current mode/action
				case 'mode': return $this->zajlib->mode;
			// The GET request
				case 'get': return $this->zajlib->array->array_to_object($_GET);
			// The POST request
				case 'post': return $this->zajlib->array->array_to_object($_POST);
			// The COOKIE request
				case 'cookie': return $this->zajlib->array->array_to_object($_COOKIE);
			// The REQUEST request
				case 'request': return $this->zajlib->array->array_to_object($_REQUEST);
			// The SERVER variables
				case 'server': return $this->zajlib->array->array_to_object($_SERVER);
			// The current protocol (HTTP/HTTPS)
				case 'protocol': return $this->zajlib->protocol;
			// Domain and top level domain
				case 'subdomain': return $this->zajlib->subdomain;
				case 'domain': return $this->zajlib->domain;
				case 'tld': return $this->zajlib->tld;
			// True if https
				case 'https': return $this->zajlib->https;
			// Return the current locale
				case 'locale': return $this->zajlib->lang->get();
			// Mozajik version info and other stuff
				case 'mozajik': return $this->zajlib->mozajik;
			// Mobile detection (uses server-side detection)
				case 'mobile': return $this->zajlib->browser->is_mobile();
			// Platform detection (uses server-side detection, returns string from browser.lib.php)
				case 'platform': return $this->zajlib->browser->get_platform();
			// Referer
				case 'referer': if(!empty($_SERVER['HTTP_REFERER'])) return $_SERVER['HTTP_REFERER']; else return '';
			// User-agent
				case 'useragent': if(!empty($_SERVER['HTTP_USER_AGENT'])) return $_SERVER['HTTP_USER_AGENT']; else return '';
			// Return which plugins are loaded
				case 'plugin':
					$my_plugins = $this->zajlib->loaded_plugins;
					array_unshift($my_plugins, '');
					return (object) array_flip($my_plugins);
			// JS layer init script
				case 'js':
					if($this->zajlib->https) $protocol = 'https'; else $protocol = 'http';
					if($this->zajlib->debug_mode) $debug_mode = 'true';
					else $debug_mode = 'false';
					return "\n\t\t<script type='text/javascript'>if(typeof zaj != 'undefined'){zaj.baseurl = '{$protocol}:{$this->zajlib->baseurl}'; zaj.fullrequest = '{$protocol}:{$this->zajlib->fullrequest}'; zaj.fullurl = '{$protocol}:{$this->zajlib->fullurl}'; zaj.app = '{$this->zajlib->app}'; zaj.mode = '{$this->zajlib->mode}'; zaj.debug_mode = $debug_mode; zaj.protocol = '{$protocol}'; }</script>";
		
			
			
			// By default return nothing.
				default: return '';
		}
	}

}

?>