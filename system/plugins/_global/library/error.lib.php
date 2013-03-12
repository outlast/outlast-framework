<?php
/**
 * Library helps Mozajik report nice, informative errors.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_error extends zajLibExtension {

	private $error_count = 0;
	
	/**
	 * Send a fatal error message.
	 * @param string $message The reported message.
	 * @param string $type Can be 'error', 'warning', or 'notice' to specify the mode of reporting.
	 **/
	public function error($message){
		// Log my error
			$this->log($message, 'error');
		// Fatal error, so exit
			exit(1);
	}

	/**
	 * Sends a warning message.
	 * @param string $message The reported message.
	 * @return Always returns false.
	 **/
	public function warning($message){
		// Log my error
			$this->log($message, 'warning');
		return false;	
	}

	/**
	 * Sends a notice message.
	 * @param string $message The reported message.
	 * @return Always returns false.
	 **/
	public function notice($message){
		// Log my error
			$this->log($message, 'notice');
		return false;	
	}
	
	
	/**
	 * Log the error to the database and to the screen (if in debug mode)
	 * @param string $errortext 
	 * @param string $errorlevel Can be 'error', 'warning', or 'notice' to specify the mode of reporting.
	 * @return boolean Will return true if logging was successful.
	 */
	private function log($errortext, $errorlevel='error'){		
		// generate a backtrace
			$backtrace = debug_backtrace(false);
		// increment number of errors
			$this->num_of_error++;
					
		// now create array
			$error_details = array(
				'errorlevel'=>$errorlevel,
				'errortext'=>$errortext,
			);
			// set first level backtrace
				if(!empty($backtrace[2])){
					$error_details['func'] = $backtrace[2]['function'];
					$error_details['file'] = $backtrace[1]['file'];
					$error_details['line'] = $backtrace[1]['line'];
					if(!empty($backtrace[2]['class'])) $error_details['class'] = $backtrace[2]['class'];
				}

		// remove the first entry
			$backtrace = array_slice($backtrace, 2);

		// process backtrace (remove long classes, make human readable)
			foreach($backtrace as $key=>$element){
				// if call user function
					if($element['function'] == 'call_user_func_array') $element['args'] = $element['args'][0];
				//remove objects from argument list
					if(is_array($element['args'])) $backtrace[$key]['args'] = $this->clean_backtrace($element['args']);
			}
		// now serialize and set full backtrace
			$error_details['backtrace'] = serialize($backtrace);
			
		// now add to file or db
			$error_details['time_create'] = time();
			$error_details['id'] = uniqid("");
		
		// which protocol?
			if(zajLib::me()->https) $protocol = 'https:';
			else $protocol = 'http:';
		// anything POSTed?
			if(!empty($_POST)) $post_data = "[POST]";
			else $post_data = "[GET]";
		// is there a referer?
			if(!empty($_SERVER['HTTP_REFERER'])) $referer = " [REFERER: ".$_SERVER['HTTP_REFERER']."]";
			else $referer = " [direct]";
		// are we in debug mode?
			if(zajLib::me()->debug_mode) $debug_mode = " [DEBUG_MODE]";
			else $debug_mode = "";
		// write to error_log			
			$this->file_log("[".$_SERVER['REMOTE_ADDR']."] [".$protocol.zajLib::me()->fullrequest."] $post_data [Mozajik $errorlevel - ".$errortext."]".$referer.$debug_mode);
			
		// log the backtrace?
			if(zajLib::me()->zajconf['error_log_backtrace']) $this->file_log("Backtrace:\n".print_r($backtrace, true));

		// only print if it is fatal error or debug mode
			if($errorlevel == 'error' || zajLib::me()->debug_mode){
				// print it to screen
					if(!zajLib::me()->debug_mode) $errortext = "Sorry, there has been a system error. The webmaster has been notified of the issue.";
					else "MOZAJIK ".strtoupper($errorlevel).": ".$errortext;
		
						// display the error?
							$uid = $error_details['id'];
							print "<div style='border: 2px red solid; padding: 5px; font-family: Arial; font-size: 13px;'>$errortext";
					if(zajLib::me()->debug_mode){
								print " <a href='#' onclick=\"document.getElementById('error_$uid').style.display='block';\">details</a><pre id='error_$uid' style='width: 98%; font-size: 13px; border: 1px solid black; overflow: scroll; display: none;'>";
								print_r($backtrace);//print substr(debug_backtrace(), 0, 1000);
								print "</pre>";
					}
							print "</div>";
			}
		return true;
	}
	

	/**
	 * Logs the message to a file.
	 * @param string $message The message to be logged.
	 * @return boolean Returns true if successful, false otherwise.
	 * @todo Remove MYSQL. That is only there for backwards compatibility.
	 **/ 
	private function file_log($message){
		// Is logging to a specific file enabled?
			if(zajLib::me()->zajconf['error_log_enabled'] && !empty(zajLib::me()->zajconf['error_log_file']) && zajLib::me()->zajconf['error_log_file'] != 'MYSQL') return @error_log('['.date("Y.m.d. G:i:s").'] '.$message."\n", 3, zajLib::me()->zajconf['error_log_file']);
			else return @error_log($message);
	}


	/**
	 * Recursively cleans objects so that they are not fully displayed.
	 * @param array $backtrace An array of backtrace items.
	 * @return array Returns a cleaned array where objects are replaced with [Object] ObjectName
	 **/ 
	private function clean_backtrace($backtrace){
		foreach($backtrace as $argkey=>$arg){
			if(is_object($arg)) $backtrace[$argkey] = '[Object] '.get_class($arg);
			if(is_array($arg)) $backtrace[$argkey] = $this->clean_backtrace($arg);
		}
		return $backtrace;
	}	
}
?>