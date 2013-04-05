<?php
/**
 * A built-in model to handle files and uploads.
 *
 * You should not directly use this model unless you are developing extensions.
 *
 * @package Model
 * @subpackage BuiltinModels
 * @todo Can this be created on-the-fly like many-to-many field tables?
 */
 
class File extends zajModel {
	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){	
		// define custom database fields
			$fields = (object) array();
			$fields->parent = zajDb::text();
			$fields->field = zajDb::text();
			$fields->name = zajDb::name();
			$fields->mime = zajDb::text();
			$fields->size = zajDb::integer();
			$fields->original = zajDb::text();
			$fields->description = zajDb::textbox();
			$fields->status = zajDb::select(array("new","uploaded","saved","deleted"),"new");
		// do not modify the line below!
			$fields = parent::__model(__CLASS__, $fields); return $fields;
	}
	///////////////////////////////////////////////////////////////
	// !Construction and other required methods
	///////////////////////////////////////////////////////////////
	public function __construct($id = ""){ parent::__construct($id, __CLASS__);	}
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }

	
	/**
	 * Cache stuff.
	 **/
	public function __afterFetch(){
		$this->mime = $this->data->mime;
		$this->size = $this->data->size;
		$this->status = $this->data->status;
	}
	
	
	///////////////////////////////////////////////////////////////
	// !Model methods
	///////////////////////////////////////////////////////////////
	public function download($force_download=true){
		// generate path
			$this->zajlib->load->library('file');
			$file_path = $this->zajlib->file->get_id_path($this->zajlib->basepath."data/private/File", $this->id, true);
		// pass file thru to user			
			header('Content-Type: '.$this->data->mime);
			header('Content-Length: '.filesize($file_path));
			if($force_download) header('Content-Disposition: attachment; filename="'.$this->data->name.'"');
			else header('Content-Disposition: inline; filename="'.$this->data->name.'"');
			ob_clean();
			flush();
   			readfile($file_path);
		// now exit
		exit;
	}
	
	/**
	 * This is an alias to set_file, because Photo also has one like it.
	 **/
	public function upload($filename = ""){ return $this->set_file($filename); }
	
	public function set_file($filename=""){
		// if filename is empty, use default tempoary name
			if(empty($filename)) $filename = $this->id.".tmp";
		// get tmpname
			$tmpname = $this->zajlib->basepath."cache/upload/".$filename;
		// generate new path
			$new_path = $this->zajlib->file->get_id_path($this->zajlib->basepath."data/private/File", $this->id, true);
			//@mkdir($this->zajlib->basepath."data/private/File/");
		// move tmpname to new location
			rename($tmpname, $new_path);
		// now set restrictive permissions
			chmod($new_path, 0644);
		// now set and save me
			// TODO: add mime-type detection here!
			// $this->set('mime',$mimetype);
			$this->set('size',filesize($new_path));
			$this->set('status','saved');
			$this->save();
		return $this;
	}
	public function delete($complete = false){
		// remove photo files
			if($complete){
				// generate path
					$this->zajlib->load->library('file');
					$file_path = $this->zajlib->file->get_id_path($this->zajlib->basepath."data/private/File", $this->id, true);
				// delete file
					@unlink($file_path);
			}
		// call parent
			parent::delete($complete);
	}


	///////////////////////////////////////////////////////////////
	// !Static methods
	///////////////////////////////////////////////////////////////

	/**
	 * Creates a file object from a file or url.
	 * @param string $urlORfilename The url or file name.
	 * @param zajModel $parent My parent object. If not specified, none will be set.
	 * @param string $field The parent-field in which the file is to be stored.
	 * @return Photo Returns the new photo object or false if none created.
	 **/
	public static function create_from_file($urlORfilename, $parent = false, $field = false){
		// ok, now copy it to uploads folder
			$updest = basename($urlORfilename);
			@mkdir(zajLib::me()->basepath."cache/upload/", 0777, true);
			copy($urlORfilename, zajLib::me()->basepath."cache/upload/".$updest);
		// now create and set image
			$pobj = File::create();
			if(is_object($parent)) $parent = $parent->id;
			if($parent !== false) $pobj->set('parent', $parent);
			if($field !== false) $pobj->set('field', $field);
			return $pobj->set_file($updest);
	}
	/**
	 * Included for backwards-compatibility. Will be removed. Alias of create_from_file.
	 * @todo Remove from version release.
	 **/
	public static function import($urlORfilename){ return self::create_from_file($urlORfilename); }
	
	
	/**
	 * Creates a photo object from php://input stream.
	 * @param string $parent_field The name of the field in the parent model. Defaults to $field_name.
	 * @param zajModel $parent My parent object.
	 **/
	public static function create_from_stream($parent_field = false, $parent = false){
		// tmp folder
			$folder = zajLib::me()->basepath.'/cache/upload/';
			$filename = uniqid().'.upload';
		// make temporary folder
			@mkdir($folder, 0777, true);
		// write to temporary file in upload folder
			$photofile = file_get_contents("php://input");
			@file_put_contents($folder.$filename, $photofile);
		// now create object and return the object
			$pobj = File::create();
		// parent and field?
			if($parent !== false) $pobj->set('parent', $parent);
			if($parent_field !== false) $pobj->set('field', $parent);
		// set file and delete temp
		 	$pobj->set_file($filename);
			@unlink($folder.$filename);
		return $pobj;
	}
	
	/**
	 * Creates a file object from a standard upload HTML4
	 * @param string $field_name The name of the file input field.
	 * @param zajModel $parent My parent object.
	 * @param string $parent_field The name of the field in the parent model. Defaults to $field_name.
	 **/
	public static function create_from_upload($field_name, $parent = false, $parent_field = false){
		// File names
			$orig_name = $_FILES[$field_name]['name'];
			$tmp_name = $_FILES[$field_name]['tmp_name'];
		// If no file, return false
			if(empty($tmp_name)) return false;
		// Now create photo object and set me
			$obj = File::create();
		// Move uploaded file to tmp
			@mkdir(zajLib::me()->basepath.'cache/upload/');
			move_uploaded_file($tmp_name, zajLib::me()->basepath.'cache/upload/'.$obj->id.'.tmp');
		// Now set and save
			$obj->set('name', $orig_name);
			if($parent !== false){
				$obj->set('parent', $parent);
				if(!$parent_field) $obj->set('field', $field_name);
				else $obj->set('field', $parent_field);
			}
			$obj->set_file();
			@unlink($tmp_name);
		return $obj;
	}	

}
?>