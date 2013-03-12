<?php
/**
 * Field definition for photo collections.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_photos extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = false;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = false;			// boolean - true if fetcher needs to be modified
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/photos.field.html';	// string - the edit template, false if not used
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
		return Photo::fetch()->filter('parent',$object->id)->sort('ordernum', 'ASC');
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
			if(is_object($data) && is_a($data, 'Photo')){
				// check to see if already has parent (disable hijacking of photos)
					if($data->data->parent) return $this->zajlib->warning("Cannot set parent of a photo object that already has a parent!");
				// now set parent
					$data->set('parent', $object->id);
					$pobj->set('field', $this->name);
					$data->set('status', 'saved');
					$data->save();
			}
		// else if it is an array (form field input)
			else{
				$sdata = $data;
				$data = json_decode($data);
				// If data is empty alltogether, it means that it wasnt JSON data, so it's a single photo id to be added!
					if(empty($data) && !empty($sdata)){
						$pobj = Photo::fetch($sdata);
							// cannot reclaim here!
							if($object->id != $pobj->parent && $pobj->status == 'saved') return $this->zajlib->warning("Cannot save a final of a photo that already exists! You are not the owner!");
						$pobj->set('parent',$object->id);							
						$pobj->set('field',$this->name);
						$pobj->upload();
						return array(false, false);
					}
				// get new ones
					if(!empty($data->add)){
						foreach($data->add as $count=>$id){
							$pobj = Photo::fetch($id);
								// cannot reclaim here!
								if($object->id != $pobj->parent && $pobj->status == 'saved') return $this->zajlib->warning("Cannot save a final of a photo that already exists! You are not the owner!");
							$pobj->set('parent',$object->id);							
							$pobj->set('field',$this->name);
							$pobj->upload();
						}
					}
				// delete old ones
					if(!empty($data->remove)){
						foreach($data->remove as $count=>$id){
							$pobj = Photo::fetch($id);
							$pobj->delete();
						}
					}
				// reorder
					if(!empty($data->order)) Photo::reorder($data->order, true);
			}

		return array(false, false);
	}

}


?>