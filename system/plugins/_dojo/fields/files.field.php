<?php
/**
 * Field definition for storing files associated with an object.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_files extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = false;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = false;		// boolean - true if fetch is modified
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/files.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
		
	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// set default options
			// no options
		// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name, $zajlib);
	}	

	/**
	 * Check to see if input data is valid.
	 * @param $input The input data.
	 * @return boolean Returns true if validation was successful, false otherwise.
	 **/
	public function validation($input){
		return true;
	}
	
	/**
	 * Preprocess the data before returning the data from the database.
	 * @param $data The first parameter is the input data.
	 * @param zajObject $object This parameter is a pointer to the actual object which is being modified here.
	 * @return Return the data that should be in the variable.
	 **/
	public function get($data, &$object){
		if(is_string($object)) return File::fetch()->filter('parent',$object);
		else return File::fetch()->filter('parent',$object->id);
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajObject $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		// if data is a photo object
			if(is_object($data) && is_a($data, 'File')){
				// check to see if already has parent (disable hijacking of photos)
					if($data->data->parent && $data->data->parent != $object->id) $this->zajlib->error("Cannot edit File: The requested File object is not an object of this parent!");
				// now set parent
					$data->set('parent', $object->id);
					$data->set('status', 'saved');
					$data->save();
			}
		// else if it is an array (form field input)
			else{
				$data = json_decode($data);
				// get new ones
					if(!empty($data->add)){
						foreach($data->add as $count=>$id){
							$pobj = File::fetch($id);
								// cannot reclaim here!
								if($pobj->status == 'saved') return $this->zajlib->error("Cannot save a final of a File that already exists!");							
							$pobj->set('parent',$object->id);							
							$pobj->upload();
						}
					}
				// delete old ones
					if(!empty($data->remove)){
						foreach($data->remove as $count=>$id){
							$pobj = File::fetch($id);
							// TODO: check to see if photo not someone else's
							$pobj->delete();
						}
					}
				// reorder (temporarily disabled!)
					//if(!empty($data->order)) File::reorder($data->order, false);
			}
		return array(false, false);
	}

}


?>