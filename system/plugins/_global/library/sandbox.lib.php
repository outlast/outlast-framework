<?php
/**
 * Check if files are sandboxed within the base folder.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 * @depricated
 **/

class zajlib_sandbox extends zajLibExtension {
	
	/**
	 * Checks if any strange characters are included in the given path which might allow the user to get outside of the sandbox.
	 * @return boolean True if it is a valid file. Will throw a fatal error if not.
	 **/
	public function check($file){
		if(strpos($file, '..') !== false || strpos($file, '.') == 0) $this->zajlib->error("Failed sandbox requirement for file $file");
		return true;
	}

	/**
	 * Returns true if the file is sandboxed within the basepath. Fatal error otherwise. Note that this will also fail if the requested file/folder does not exist. For performance reasons, only use this if you must enable relative paths in your query and cannot use {@link zajlib_sandbox->check()} instead.
	 * @return boolean True if it is a valid file.
	 **/
	public function realpath($file){
		// Check file realpath
			$realpath = realpath($file);
		// Is the first part of realpath my basepath
			if(substr($realpath, 0, strlen($this->zajlib->basepath)) == $this->zajlib->basepath) return true;
			else $this->zajlib->error("Failed realpath sandbox requirement for file $file");
	}
}

?>