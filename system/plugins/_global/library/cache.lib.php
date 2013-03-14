<?php
/**
 * Various methods for deleting and updating Mozajik cache files.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_cache extends zajLibExtension{

	/**
	 * This method will remove all object cache files. If the $class parameter is specified, only the cache for that class will be cleared.
	 * @param bool|string $class An optional CaseSensitive parameter which specifies the name of the class to be cleared.
	 * @return bool Will alway return true.
	 */
	public function clear_objects($class=false){
			// check to see if $class is valid
				if($class && (!class_exists($class) || strstr($class, '.') !== false || strstr($class, '/') !== false)) return $this->zajlib->warning("Invalid class name given while trying to clear the cache!");
			// load file handler
				system("rm -R ".$this->zajlib->basepath."cache/object/".$class."/");
		return true;
	}

	/**
	 * This method will remove a specific object cache file.
	 * @param string $class_name The id parameter which specifies the specific id of the object to be cleared.
	 * @param string $id The id parameter which specifies the specific id of the object to be cleared.
	 * @return boolean Returns true if something was deleted, false otherwise.
	 */
	public function clear_object($class_name, $id){
		// check to see if $class is valid
			if($class_name && (!class_exists($class_name) || strstr($class_name, '.') !== false || strstr($class_name, '/') !== false)) return $this->zajlib->warning("Invalid class name given while trying to clear an object from the cache!");
			if(strstr($id, '.') !== false) return $this->zajlib->warning("Could not delete cache file. Invalid characters detected in file name.");
		// Generate my path
			$filename = $this->zajlib->file->get_id_path($this->zajlib->basepath."cache/object/".$class_name, $id.".cache", false, CACHE_DIR_LEVEL);
		// Try to delete and return result
			return @unlink($filename);
	}
}