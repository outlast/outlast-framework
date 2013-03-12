<?php
/**
 * Insert messages into a log file.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_log extends zajLibExtension {
	
	// Logs a $message for $type, max it to 1 MB
	function file($type, $message, $max_size = 102400){
		// check for illegal file name
			if(substr_count($type,"..") > 0) $this->zajlib->error("logging for type $type cannot include '..'!");
		// create log_file_path
			$log_file_path = $this->zajlib->basepath.'/cache/log/';
			$log_file_name = $log_file_path.$type.'.log';
		// check to see if file exists
			if(!file_exists($log_file_name)){
				// create folder for file
					$this->zajlib->load->library('file');
					$this->zajlib->file->create_path_for($log_file_name);
			}
			else{
				// is file too large?
					if(filesize($log_file_name) > $max_size) unlink($log_file_name);
			}
		// now append log message
			$log = fopen($log_file_name, 'a');
			if($log){
				fputs($log, date("D M j G:i:s Y -- ").$message."\n");
				fclose($log);
			}
		return true;
	}
	
}


	
?>