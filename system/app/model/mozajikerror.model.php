<?php
/**
 * MozajikError is a model which stores php errors and backtraces for later analysis.
 *
 * @package Model
 * @subpackage BuiltinModels
 **/


define('MAX_ERRORS_TO_LOG', 3);

class MozajikError extends zajModel {
	static $number_of_errors = 0;
	
	/**
	 * Model definition
	 */
	static function __model(){
		// define custom database fields
			$f->errortext = zajDb::text();
			$f->errorlevel = zajDb::select(array('notice', 'warning', 'error'));

			$f->func = zajDb::text();
			$f->file = zajDb::textarea();
			$f->line = zajDb::integer();
			$f->class = zajDb::text();

			$f->backtrace = zajDb::textarea();

		// do not modify the line below!
			$f = parent::__model(__CLASS__, $f); return $f;
	}

	/**
	 * Construction and required methods
	 */
	public function __construct($id = ""){ parent::__construct($id, __CLASS__); return true; }
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }

	/**
	 * Log the error to the database and to the screen (if in debug mode)
	 * @param string $errortext 
	 */
	public static function log($errortext, $errorlevel){		
		// generate a backtrace
			$backtrace = debug_backtrace(false);
		// increment number of errors
			MozajikError::$number_of_errors++;
			if(MozajikError::$number_of_errors > MAX_ERRORS_TO_LOG) return false;
		// uhoh, error in myself!
			if($backtrace[1]['class'] == 'MozajikError') exit('Could not log error: '.$errortext);
			
		// check if db connection is ok, if so, log to db!
			if(zajLib::me()->zajconf['error_log_file'] == 'MYSQL' && zajLib::me()->zajconf['mysql_enabled'] && is_object(zajLib::me()->db) && !zajLib::me()->db->get_error()) $log_mode = 'db';
			else $log_mode = 'file';
		
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
			$backtrace = array_slice($backtrace, 1);

		// process backtrace (remove long classes, make human readable)
			foreach($backtrace as $key=>$element){
				// if call user function
					if($element['function'] == 'call_user_func_array') $element['args'] = $element['args'][0];
				//remove objects from argument list
					if(is_array($element['args'])) $backtrace[$key]['args'] = MozajikError::clean_backtrace($element['args']);
			}
		// now serialize and set full backtrace
			$error_details['backtrace'] = serialize($backtrace);
			
		// now add to file or db
			$error_details['time_create'] = time();
			$error_details['id'] = uniqid("");
		
		// is it db write mode? or file mode?
			// DB mode is disabled while I figure out how to handle it...
			//if($log_mode == 'db') zajLib::me()->db->add('mozajikerror', $error_details);
			//else error_log("Mozajik $errorlevel at ".date("Y.m.d. G:i:s")." - ".$errortext);
			
			error_log("Mozajik $errorlevel at ".date("Y.m.d. G:i:s")." - ".$errortext);
		// log the backtrace?
			if(zajLib::me()->zajconf['error_log_backtraces']) error_log("Backtrace:".print_r($backtrace, true));

		// print it to screen
			if(zajLib::me()->debug_mode){
				// display the error?
					$uid = $error_details['id'];
					print "<div style='border: 2px red solid; padding: 5px; font-family: Arial; font-size: 13px;'>MOZAJIK ".strtoupper($errorlevel).": ".$errortext;
						print " <a href='#' onclick=\"document.getElementById('error_$uid').style.display='block';\">details</a><pre id='error_$uid' style='width: 98%; font-size: 13px; border: 1px solid black; overflow: scroll; display: none;'>";
						print "This error was logged to $log_mode.<hr/>";
						print_r($backtrace);//print substr(debug_backtrace(), 0, 1000);
						print "</pre>";
					print "</div>";
			}
		return true;
	}
	
	public static function clean_backtrace($backtrace){
		foreach($backtrace as $argkey=>$arg){
			if(is_object($arg)) $backtrace[$argkey] = '[Object] '.get_class($arg);
			if(is_array($arg)) $backtrace[$argkey] = MozajikError::clean_backtrace($arg);
		}
		return $backtrace;
	}
}