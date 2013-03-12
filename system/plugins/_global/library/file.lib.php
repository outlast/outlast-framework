<?php
/**
 * File-system related methods for manipulating and getting information about files and folders.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_file extends zajLibExtension {

	/**
	 * Cleans a path and makes sure that it is jailed to my project folder basepath. This can take full paths (within the basepath) or relatives to the basepath.
	 * @param string $path The path of the file either relative to the basepath, or a full path within the basepath.
	 * @param string $custom_error The error message to fail on if an incorrect path is found.
	 * @param boolean $add_trailing_slash If this is a folder, then the trailing slash is added after sanitization.
	 * @return string Returns the sanitized path relative to the basepath if successful. Fatal error if not.
	 **/
	function folder_check($path, $custom_error = "Invalid folder path given.", $add_trailing_slash = true){
		// Save original
			$opath = $path;
		// First we need to get rid of our full path (if it exists) and trim /-es, \-es, and spaces.
			$path = str_ireplace($this->zajlib->basepath, '', $path);
		// Now make sure it is not still absolute url
			if($opath == $path && strpos($path, '/') === 0) return $this->zajlib->error($custom_error.' '.$opath);
		// Now make sure no relative path stuff is tried
			if(strstr($path, '..') !== false) return $this->zajlib->error($custom_error.' '.$opath);
		// If basepath was trimmed, now readd
			if($opath !== $path) $path = $this->zajlib->basepath.$path;
		// All is ok, so let's fix it up with single trailing slash!
			if($add_trailing_slash) $path = rtrim($path, '/').'/';
		return $path;
	}

	/**
	 * Same as {@link folder_check} except that this does not add a trailing slash by default (since it is for files).
	 * @param string $path The path of the file either relative to the basepath, or a full path within the basepath.
	 * @param string $custom_error The error message to fail on if an incorrect path is found.
	 * @return string Returns the sanitized file path relative to the basepath if successful. Fatal error if not.
	 **/
	function file_check($path, $custom_error = "Invalid file path given."){
		return $this->folder_check($path, $custom_error, false);
	}

	/**
	 * Sanitize a file name by removing all special characters.
	 * @param string $filename The original file name.
	 * @return string The sanitized file name.
	 **/
	function sanitize($filename){
		$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
		$filename = str_replace($special_chars, '', $filename);
		$filename = preg_replace('/[\s-]+/', '-', $filename);
		$filename = $this->zajlib->lang->convert_eng($filename);
		$filename = trim($filename, '.-_');
		return $filename;
	}

	/**
	 * Returns an array of files found in this folder. If set to recursive, the file paths will be returned relative to the specified path.
	 * @param string $path The path to check for files.
	 * @param boolean $recursive If set to true, subfolders will also be checked. False by default.
	 * @param string $mode Can be 'files' or 'folders'. This should not be used. If you want to check for folders, use {@link get_folders_in_dir()} instead.	 
	 * @param boolean $hidden_files_and_folders If set to true, hidden files and folders (beginning with .) will also be included. False by default.
	 * @return array An array of file paths within the directory.
	 **/
	function get_files_in_dir($path, $recursive = false, $mode = "files", $hidden_files_and_folders = false){
		$files = $folders = array();
		// validate path
			$path = $this->folder_check($path, "Invalid path requested for get_files_in_dir.");
		// check if folder
			if(!is_dir($path)) return array();
		// else, fetch files in folder		
			$dir = @opendir($path);
			while (false !== ($file = @readdir($dir))) { 
				if($file != "." && $file != ".." && ($hidden_files_and_folders || substr($file, 0, 1) != '.')){
					// if it is a file
					if(is_file($path."/".$file)) $files[] = $path."/".$file;
					// if it is a dir
					elseif(is_dir($path."/".$file)){
						$folders[] = $path."/".$file;
						// is recursive?
						if($recursive){
							$newfiles = $this->get_files_in_dir($path."/".$file, true, $mode);
							// add to files or folders
							if($mode == "files") $files = $this->zajlib->array->array_merge($files, $newfiles);
							else $folders = $this->zajlib->array->array_merge($folders, $newfiles);
						}
					}
				}
			}
		// decide what to return
			if($mode == "files") return $files;
			else return $folders;
	}
	/**
	 * Returns an array of folders found in this folder. If set to recursive, the folder paths will be returned relative to the specified path.
	 * @param string $path The path to check for folders.
	 * @param boolean $recursive If set to true, subfolders will also be checked. False by default.
	 * @param boolean $hidden_files_and_folders If set to true, hidden files and folders (beginning with .) will also be included. False by default.
	 * @return array An array of folder paths within the directory.
	 **/
	function get_folders_in_dir($path, $recursive = false, $hidden_files_and_folders=false){
		$folders = $this->get_files_in_dir($path, $recursive, "folders", $hidden_files_and_folders);
		return $folders;
	}
	
	/**
	 * Returns the extension section of the file.
	 * @param string $filename The full filename, including extension.
	 * @return string The file's extension
	 **/
	function get_extension($filename){
		$path_parts = pathinfo($filename);
		$path_parts['extension'] = mb_strtolower($path_parts['extension']);
		return $path_parts['extension'];
	}
	
	/**
	 * Creates folders and subfolders for the specified file name.
	 * @param string $filename The full filename, including extension.
	 * @return bool
	 */
	function create_path_for($filename){
		// get folder
			$path = dirname($filename);
		// validate path
			$path = $this->folder_check($path, "Invalid path requested for create_path_for.");
		// all ok, create
		return @mkdir($path, 0777, true);
	}

	/**
	 * Checks if the extension is valid.
	 * @param string $filename The full filename, including extension.
	 * @param array|string $extORextarray A single extension (string) or an array of extensions (array of strings). Defaults to an array of image extensions (jpg, jpeg, png, gif)
	 * @return boolean True if the file extension is valid according to the specified list.
	 */
	function is_correct_extension($filename, $extORextarray = ""){
		// set default (for images)
			if($extORextarray == "") $extORextarray = array("jpg", "jpeg", "png", "gif");
		// now check to see if not array
			if(!is_array($extORextarray)) $extORextarray = array($extORextarray);
		// get file extension
			$ext = $this->get_extension($filename);
		// is it in the array?
			return in_array($ext, $extORextarray);
	}

	/**
	 * Generate a list of subfolders based on the timestamp. So for example: 2010/Jan/3/example.txt could be created.
	 * @param string $basepath The base path of the file (this will not use the global base path!)
	 * @param string $filename The full filename, including extension.
	 * @param integer $timestamp The UNIX time stamp to use for generating the folders. The current timestamp will be used by default.
	 * @param boolean $create_folders_if_they_dont_exist If set to true, the folders will not only be calculated, but also created.
	 * @param bool $include_day Whether to include the day level as well.
	 * @return string The new full path of the file.
	 */
	function get_time_path($basepath,$filename,$timestamp = 0,$create_folders_if_they_dont_exist = true,$include_day=true){
		// Validate path
			$basepath = $this->folder_check($basepath, "Invalid path requested for get_time_path.");
		// Validate file
			$filename = $this->file_check($filename, "Invalid file requested for get_time_path.");
		// defaults and error checks
			if($basepath == "") return false;
			if($timestamp == 0) $timestamp = time();
		// Get timestamp based subfolder: /year/month/
			$timedata = localtime($timestamp, true);
			$sub1 = $timedata["tm_year"]+1900;
			$sub2 = date("m", $timestamp);
			if($include_day) $sub3 = date("d", $timestamp);
		// Generate full string and return
			$fullpath = $basepath."/".$sub1."/".$sub2."/".$sub3."/".$filename;
		// Make sure folders exist...if not, create...(unless not needed)
			if($create_folders_if_they_dont_exist){
				if(!$filename) @mkdir($fullpath, 0777, true);
				else $this->create_path_for($fullpath);
			}		
		// Return the full path	
		return $fullpath;
	}

	/**
	 * Generate a list of subfolders based on the file name. So example.txt at $level 3 will generate a path of e/x/a/example.txt
	 * @param string $basepath The base path of the file (this will not use the global base path!)
	 * @param string $filename The full filename, including extension.
	 * @param boolean $create_folders_if_they_dont_exist If set to true, the folders will not only be calculated, but also created.
	 * @param integer $level The number of levels of subfolders to calculate with.
	 * @return string The new full path of the file.
	 **/
	function get_id_path($basepath,$filename,$create_folders_if_they_dont_exist = true,$level = 10){
		// Validate path
			$basepath = $this->folder_check($basepath, "Invalid path requested for get_id_path.");
		// Validate file
			$filename = $this->file_check($filename, "Invalid file requested for get_id_path.");
		// defaults and error checks
			if($basepath == "") return false;		
		// get filename and parts
			$pathdata = pathinfo($filename);
			$parts = str_split($pathdata['filename']);
		// now generate folder structure
			// remove the ending (it will get the full filename)
				$parts = array_slice($parts,0,$level);
			// join into path
				$folder_structure = implode("/",$parts);
		// generate new path
			$new_folder = $basepath."/".$folder_structure."/";
			$new_full_path = $new_folder.$filename;
		// create folders?
			// TODO: review permissions!
			if($create_folders_if_they_dont_exist){
				if(!$filename) @mkdir($new_folder, 0777, true);
				else $this->create_path_for($new_full_path);
			}
		// done.
		return $new_full_path;
	}
		
	/**
	 * Get mime-type of file based on the extension. This is not too reliable, since it takes the file name and not file content as the key.
	 * @param string $filename The full filename, including extension.
	 * @return string The mime type of the file
	 **/
	function get_mime_type($filename) {
		// Validate path
			$filename = $this->file_check($filename, "Invalid file requested for get_mime_type.");
		// Define mime types
	        $mime_types = array(
	            'txt' => 'text/plain',
	            'htm' => 'text/html',
	            'html' => 'text/html',
	            'php' => 'text/html',
	            'css' => 'text/css',
	            'js' => 'application/javascript',
	            'json' => 'application/json',
	            'xml' => 'application/xml',
	            'swf' => 'application/x-shockwave-flash',
	            'flv' => 'video/x-flv',
	
	            // images
	            'png' => 'image/png',
	            'jpe' => 'image/jpeg',
	            'jpeg' => 'image/jpeg',
	            'jpg' => 'image/jpeg',
	            'gif' => 'image/gif',
	            'bmp' => 'image/bmp',
	            'ico' => 'image/vnd.microsoft.icon',
	            'tiff' => 'image/tiff',
	            'tif' => 'image/tiff',
	            'svg' => 'image/svg+xml',
	            'svgz' => 'image/svg+xml',
	
	            // archives
	            'zip' => 'application/zip',
	            'rar' => 'application/x-rar-compressed',
	            'exe' => 'application/x-msdownload',
	            'msi' => 'application/x-msdownload',
	            'cab' => 'application/vnd.ms-cab-compressed',
	
	            // audio/video
	            'mp3' => 'audio/mpeg',
	            'qt' => 'video/quicktime',
	            'mov' => 'video/quicktime',
	
	            // adobe
	            'pdf' => 'application/pdf',
	            'psd' => 'image/vnd.adobe.photoshop',
	            'ai' => 'application/postscript',
	            'eps' => 'application/postscript',
	            'ps' => 'application/postscript',
	
	            // ms office
	            'doc' => 'application/msword',
	            'rtf' => 'application/rtf',
	            'xls' => 'application/vnd.ms-excel',
	            'ppt' => 'application/vnd.ms-powerpoint',
	
	            // open office
	            'odt' => 'application/vnd.oasis.opendocument.text',
	            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
	        );
	
	        $ext = strtolower(array_pop(explode('.',$filename)));
	        if (array_key_exists($ext, $mime_types)) {
	            return $mime_types[$ext];
	        }
	        elseif (function_exists('finfo_open')) {
	            $finfo = finfo_open(FILEINFO_MIME);
	            $mimetype = finfo_file($finfo, $filename);
	            finfo_close($finfo);
	            return $mimetype;
	        }
	        else {
	            return 'application/octet-stream';
	        }
	}
	
	/**
	 * Calculate download time (in seconds)
	 * @param integer $bytes The file size in bytes.
	 * @param integer $kbps The connection speed in Kbps (kiloBITS per second!)
	 * @return integer The time in seconds
	 **/
	function download_time($bytes,$kbps=512)	{
		// convert kbps to Bytes Per Second
		$speed = ($kbps/8)*1024;
		// by seconds
		$time	= ceil($bytes / $speed);
		// hours, mins, secs
		$hours    = (int)floor($time/3600);
		$minutes  = (int)floor($time/60)%60;
		$seconds  = (int)$time%60;
	
		return (int)$time;
	}

	/**
	 * Format the value like a 'human-readable' file size (i.e. '13 KB', '4.1 MB', '102 bytes', etc).
	 * @param integer $bytes The number of bytes.
	 * @return string A human-readable string of file size.
	 **/
	function file_size_format($bytes){
		if($bytes < 950) return $bytes.' bytes';
		elseif($bytes < 1024*1024) return number_format($bytes/1024, 1, '.', ' ').' KB';
		elseif($bytes < 1024*1024*1024) return number_format($bytes/1024/1024, 1, '.', ' ').' MB';
		elseif($bytes < 1024*1024*1024*1024) return number_format($bytes/1024/1024, 1, '.', ' ').' GB';
		elseif($bytes < 1024*1024*1024*1024*1024) return number_format($bytes/1024/1024, 1, '.', ' ').' TB';
		else return number_format($bytes/1024/1024, 0, '.', ' ').' TB';
	}

}




?>