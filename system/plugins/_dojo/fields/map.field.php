<?php
/**
 * Field definition for plotting points on a map. The default controller uses Google maps API.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_map extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = false;			// boolean - true if fetcher needs to be modified
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/map.field.html';	// string - the edit template, false if not used
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
			$fields[$this->name.'_lat'] = array(
					'field' => $this->name.'_lat',
					'type' => 'float',
					'option' => array(),
 					'key' => '',
					'default' => 0,
					'extra' => '',
					'comment' => 'map',
			);
			$fields[$this->name.'_lng'] = array(
					'field' => $this->name.'_lng',
					'type' => 'float',
					'option' => array(),
 					'key' => '',
					'default' => 0,
					'extra' => '',
					'comment' => 'map',
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
		//$this->data[$name] = array("lat"=>$this->data[$name."_lat"],"lng"=>$this->data[$name."_lng"]);
		return $data;
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		return $data;	
	}

}


?>