<?php
/**
 * Field definition for serialized. An serialized field is useful for storing arrays or objects that we do not need as explicitly separate connected models. For example a simple log or some such could be stored here. Any array is actually converted and stored as an object for easier reference via the template language...
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_serialized extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = false;		// boolean - true if fetch is modified
	const search_field = true;		// boolean - true if this field is used during search()
	const edit_template = 'field/serialized.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
		
	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// set default options
			// no default options
		// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name, $zajlib);
	}	
	
	/**
	 * Defines the structure and type of this field in the mysql database.
	 * @return array Returns in array with the database definition.
	 **/
	public function database(){
		// define each field
			$fields[$this->name] = array(
					'field' => $this->name,
					'type' => 'text',
					'option' => array(),
 					'key' => '',
					'default' => $this->options['default'],
					'extra' => '',
					'comment' => 'array',
			);
		return $fields;
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
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return Return the data that should be in the variable.
	 **/
	public function get($data, &$object){
		$result = unserialize($data);
		if(!$result) $result = (object) array();
		return $result;
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		// First let's check if this is a special array
			if(is_array($data) && !empty($data['mozajik_field'])){
				$sdata = array();
				foreach($data['mozajik_field']['key'] as $key=>$value){
					$sdata[$value] = $data['mozajik_field']['value'][$key];
				}
				$data = $sdata;
			}
		// Standard array, so serialize
			if($data && is_array($data)) $newdata = serialize($this->zajlib->array->array_to_object($data));
			elseif($data && is_object($data)) $newdata = serialize($data);
			else $newdata = $data;
		return array($newdata, $data);
	}

}


?>