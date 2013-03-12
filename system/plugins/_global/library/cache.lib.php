<?php
/**
 * Various methods for deleting and updating Mozajik cache files.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_cache extends zajLibExtension{

	/**
	 * This method will remove all object cache files. If the $class parameter is specified, the only the cache for that class will be cleared.
	 * @param string $class An optional CaseSensitive parameter which specifies the name of the class to be cleared.
	 * @return integer Returns the number of files cleared.
	 */
	public function clear_objects($class=false){
			// check to see if $class is valid
				if($class && (!class_exists($class) || strstr($class, '.') !== false || strstr($class, '/') !== false)) return $this->zajlib->error("Invalid class name given while trying to clear the cache!");
			// load file handler
				//$this->zajlib->load->library('file');
				//$all_files = $this->zajlib->file->get_files_in_dir($this->zajlib->basepath."cache/object/".$class."/", true);
				system("rm -R ".$this->zajlib->basepath."cache/object/".$class."/");
			// delete all files in cache folder
				/**$count = 0;
				foreach($all_files as $file){
					$fdata = pathinfo($file);
					if($fdata['extension'] == "cache"){
						unlink($file);
						$count++;
					}
				}**/
		return $this->zajlib->basepath."cache/object/".$class."/";
	}

	/**
	 * This method will remove a specific object cache file.
	 * @param string $class The CaseSensitive parameter which specifies the name of the class to be cleared.
	 * @param string $class The id parameter which specifies the specific id of the object to be cleared.
	 * @return boolean Returns true if something was deleted, false otherwise.
	 */
	public function clear_object($class, $id){
		// Generate my path
			$filename = $this->zajlib->file->get_id_path($this->zajlib->basepath."cache/object/".$class_name, $id.".cache", false, CACHE_DIR_LEVEL);
		// Try to delete and return result
			return @unlink($filename);
	}
	
	
}

?>