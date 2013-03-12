<?php
// Define my photo sizes if not already done!
if(empty($GLOBALS['photosizes'])) $GLOBALS['photosizes'] = array('thumb'=>50,'small'=>300,'normal'=>700,'large'=>2000,'full'=>true);

/**
 * A built-in model to store photos.
 *
 * This is a pointer to the data items in this model...
 * @property zajDataPhoto $data
 * And here are the cached fields...
 * @property string $status
 * @property string $class The class of the parent.
 * @property string $parent The id of the parent.
 * @property string $field The field name of the parent.
 * @property boolean $timepath If the new time-based path is used.
 * @property integer $time_create
 * @property string $extension
 * @property string $imagetype Can be IMAGETYPE_PNG, IMAGETYPE_GIF, or IMAGETYPE_JPG constant.
 **/
class Photo extends zajModel {

	/* If set to true, the file is not yet saved to the db. */
	public $temporary = false;
		
	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){	
		// define custom database fields
			$f = (object) array();
			$f->class = zajDb::text();
			$f->parent = zajDb::text();
			$f->field = zajDb::text();
			$f->name = zajDb::name();
			$f->imagetype = zajDb::integer();
			$f->original = zajDb::text();
			$f->description = zajDb::textbox();
			$f->timepath = zajDb::boolean();
			$f->status = zajDb::select(array("new","uploaded","saved","deleted"),"new");
		// do not modify the line below!
			$f = parent::__model(__CLASS__, $f); return $f;
	}
	///////////////////////////////////////////////////////////////
	// !Construction and other required methods
	///////////////////////////////////////////////////////////////
	public function __construct($id = ""){ parent::__construct($id, __CLASS__);	}
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }

	///////////////////////////////////////////////////////////////
	// !Magic methods
	///////////////////////////////////////////////////////////////
	public function __afterFetch(){
		// Set status and parents
			$this->status = $this->data->status;
			$this->class = $this->data->class;
			$this->parent = $this->data->parent;
			$this->field = $this->data->field;
			$this->timepath = $this->data->timepath;
			$this->time_create = $this->data->time_create;
		// See which file exists
			if(file_exists($this->zajlib->basepath.$this->get_file_path($this->id."-normal.png"))){
				$this->extension = 'png';
				$this->imagetype = IMAGETYPE_PNG;
			}
			elseif(file_exists($this->zajlib->basepath.$this->get_file_path($this->id."-normal.gif"))){
				$this->extension = 'gif';
				$this->imagetype = IMAGETYPE_GIF;
			}
			else{
				$this->extension = 'jpg';
				$this->imagetype = IMAGETYPE_JPEG;
			}
	}

	/**
	 * Returns the url based on size ($photo->small) or the relative url ($photo->rel_small)
	 **/
	public function __get($name){
		// Default the extension to jpg if not defined
			if(empty($this->extension)) $this->extension = 'jpg';
		// Figure out direct or relative file name
			$relname = str_ireplace('rel_', '', $name);
			if(!empty($GLOBALS['photosizes'][$name])) return $this->get_image($name);
			else{
				if(!empty($GLOBALS['photosizes'][$relname])) return $this->get_file_path($this->id."-$relname.".$this->extension);
				else return parent::__get($name);
			}
	}

	/**
	 * Helper function which returns the path based on the current settings.
	 * @param string $filename Can be thumb, small, normal, etc.
	 * @param bool $create_folders Create the subfolders if needed.
	 * @return string Returns the file path.
	 **/
	public function get_file_path($filename, $create_folders = false){
		// First, let's determine which function to use
			if($this->timepath) $path = $this->zajlib->file->get_time_path("data/Photo", $filename, $this->time_create, false);
			else $path = $this->zajlib->file->get_id_path("data/Photo", $filename, false);
		// Create folders if requested
			if($create_folders) $this->zajlib->file->create_path_for($path);
		// Now call and return!
			return $path;
	}

	///////////////////////////////////////////////////////////////
	// !Model methods
	///////////////////////////////////////////////////////////////

	/**
	 * This is an alias to set_image, because file also has one like it.
	 **/
	public function upload($filename = ""){ return $this->set_image($filename); }

	/**
	 * Resizes and saves the image. The status is always changed to saved and this method automatically saves changes to the database. Only call this when you are absolutely ready to commit the photo for public use.
	 * @param string $filename The name of the file within the cache upload folder.
	 * @return bool|Photo Returns the Photo object, false if error.
	 */
	public function set_image($filename = ""){
		// if filename is empty, use default tempoary name
			if(empty($filename)) $filename = $this->id.".tmp";
		// jail file
			if(strpos($filename, '..') !== false || strpos($filename, '/') !== false) $this->zajlib->error("invalid filename given when trying to save final image.");
		// set variables
			$file_path = $this->zajlib->basepath."cache/upload/".$filename;
			$image_data = getimagesize($file_path);
		// check for errors
			if(strpos($filename,"/") !== false) return $this->zajlib->error('uploaded photo cannot be saved: must specify relative path to cache/upload folder.');
			if(!file_exists($this->zajlib->basepath."cache/upload/".$filename)) return $this->zajlib->error("uploaded photo $filename does not exist!");
			if($image_data === false) return $this->zajlib->error('uploaded file is not a photo. you should always check this before calling set_image/upload!');
		// check image type of source
			$image_type = exif_imagetype($file_path);
		// select extension
			if($image_type == IMAGETYPE_PNG) $extension = 'png';
			elseif($image_type == IMAGETYPE_GIF) $extension = 'gif';
			else $extension = 'jpg';
		// now enable time-based folders
			$this->set('timepath', true);
			$this->timepath = true;
		// no errors, resize and save
			foreach($GLOBALS['photosizes'] as $key => $size){
				if($size !== false){
					// save resized images perserving extension
						$new_path = $this->zajlib->basepath.$this->get_file_path($this->id."-$key.".$extension, true);
					// resize it now!
						$this->zajlib->graphics->resize($file_path, $new_path, $size);
				}
			}
		// now remove the original or copy to full location
			if($GLOBALS['photosizes']['full']) copy($file_path, $this->zajlib->basepath.$this->get_file_path($this->id."-full.".$extension, true));
			else unlink($file_path);
		// Remove temporary location flag
			$this->temporary = false;
			//$this->set('imagetype', $image_type);
			$this->set('status', 'saved');
			$this->save();
		return $this;
	}

	/**
	 * Returns an image url based on the requested size.
	 * @param string $size One of the standard photo sizes.
	 * @return string Image url.
	 */
	public function get_image($size = 'normal'){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		return $this->zajlib->baseurl.$this->get_file_path($this->id."-$size.".$this->extension);
	}

	/**
	 * Returns an image path based on the requested size.
	 * @param string $size One of the standard photo sizes.
	 * @return string Image path.
	 **/
	public function get_path($size = 'normal'){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		return $this->zajlib->basepath.$this->get_file_path($this->id."-$size.".$this->extension);
	}

	/**
	 * An alias of Photo->download($size, false), which will display the photo instead of forcing a download.
	 * @param string $size One of the standard photo sizes.
	 **/
	public function show($size = "normal"){
		$this->download($size, false);
	}

	/**
	 * Forces a download dialog for the browser.
	 * @param string $size One of the standard photo sizes.
	 * @param boolean $force_download If set to true (default), this will force a download for the user.
	 * @return void This will force a download and exit.
	 */
	public function download($size = "normal", $force_download = true){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		// look for bad characters in $size
			if(($size != "preview" && empty($GLOBALS['photosizes'][$size])) || substr_count($size, "..") > 0)  $this->zajlib->error("File could not be found.");
			if(!$this->temporary && $size == "preview") $size = 'normal';
		// generate path
			$file_path = $this->zajlib->basepath.$this->get_file_path($this->id."-$size.".$this->extension);
		// if it is in preview mode (only if not yet finalized)
			$preview_path = $this->zajlib->basepath."cache/upload/".$this->id.".tmp";
			if($this->temporary && $size == "preview") $file_path = $preview_path;
		// final test, if file exists
			if(!file_exists($file_path)) $this->zajlib->error("File could not be found.");
		// pass file thru to user
			if($force_download) header('Content-Disposition: attachment; filename="'.$this->data->name.'"');
		// create header
			switch ($this->extension){
				case 'png': header('Content-Type: image/png;'); break;
				case 'gif': header('Content-Type: image/gif;'); break;
				default: header('Content-Type: image/jpeg;'); break;
			}
		// open and pass through
			$f = fopen($file_path, "r");
				fpassthru($f);
			fclose($f);
		// now exit
		exit;
	}
	/**
	 * Overrides the global delete.
	 * @param bool $complete If set to true, the file will be deleted too and the full entry will be removed.
	 * @return bool Returns true if successful.
	 **/
	public function delete($complete = false){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		// remove photo files
			if($complete){
				foreach($GLOBALS['photosizes'] as $name=>$size){
					if($size) @unlink($this->zajlib->basepath.$this->get_file_path($this->id."-$name.".$this->extension));
				}
			}
		// call parent
			return parent::delete($complete);
	}


	///////////////////////////////////////////////////////////////
	// !Static methods
	///////////////////////////////////////////////////////////////
	// be careful when using the import function to check if filename or url is valid


	/**
	 * Creates a photo object from a file or url. Will return false if it is not an image or not found.
	 * @param string $urlORfilename The url or file name.
	 * @param zajModel|bool $parent My parent object. If not specified, none will be set.
	 * @return Photo Returns the new photo object or false if none created.
	 **/
	public static function create_from_file($urlORfilename, $parent = false){
		// first check to see if it is a photo
			$image_data = @getimagesize($urlORfilename);
			if($image_data === false) return false;
		// ok, now copy it to uploads folder
			$updest = basename($urlORfilename);
			@mkdir(zajLib::me()->basepath."cache/upload/", 0777, true);
		// Verify new name is jailed
			zajLib::me()->file->file_check(zajLib::me()->basepath."cache/upload/".$updest);
			copy($urlORfilename, zajLib::me()->basepath."cache/upload/".$updest);
		// now create and set image
			/** @var Photo $pobj **/
			$pobj = Photo::create();
			if($parent !== false) $pobj->set('parent', $parent);
			return $pobj->set_image($updest);
	}
	/**
	 * Included for backwards-compatibility. Will be removed. Alias of create_from_file.
	 * @todo Remove from version release.
	 **/
	public static function import($urlORfilename){ return self::create_from_file($urlORfilename); }
	
	/**
	 * Creates a photo object from php://input stream.
	 **/
	public static function create_from_stream(){
		// tmp folder
			$folder = zajLib::me()->basepath.'/cache/upload/';
			$filename = uniqid().'.upload';
		// make temporary folder
			@mkdir($folder, 0777, true);
		// write to temporary file in upload folder
			$photofile = file_get_contents("php://input");
			@file_put_contents($folder.$filename, $photofile);
		// is photo an image
			$image_data = getimagesize($folder.$filename);
			if($image_data === false){
				// not image, delete file return false
				@unlink($folder.$filename);
				return false;
			}
		// now create object and return the object
			/** @var Photo $pobj **/
			$pobj = Photo::create();
		 	$pobj->set_image($filename);
			@unlink($folder.$filename);
			return $pobj;
	}
	
	/**
	 * Creates a photo object from a standard upload HTML4
	 * @param string $field_name The name of the file input field.
	 * @param boolean $save_now If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @param zajModel|bool $parent My parent object.
	 * @return Photo|bool Returns the Photo object on success, false if not.
	 **/
	public static function create_from_upload($field_name, $parent = false, $save_now = true){
		// File names
			$orig_name = $_FILES[$field_name]['name'];
			$tmp_name = $_FILES[$field_name]['tmp_name'];
		// If no file, return false
			if(empty($tmp_name)) return false;
		// Now create photo object and set me
			/** @var Photo $obj **/
			$obj = Photo::create();
		// Move uploaded file to tmp
			@mkdir(zajLib::me()->basepath.'cache/upload/');
			$new_name = zajLib::me()->basepath.'cache/upload/'.$obj->id.'.tmp';
		// Verify new name is jailed
			zajLib::me()->file->file_check($new_name);
			move_uploaded_file($tmp_name, $new_name);
		// Now set and save
			$obj->set('name', $orig_name);
			if($parent !== false) $obj->set('parent', $parent);
			//$obj->set('status', 'saved'); (done by set_image)
			if($save_now) $obj->upload();
			else $obj->temporary = true;
			$obj->save();
			@unlink($tmp_name);
		return $obj;
	}
}
?>