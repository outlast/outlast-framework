<?php
/**
 * Field definition for creating timestamps in the database.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_timestamp extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = false;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = true;		// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = false;		// boolean - true if fetch is modified
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/time.field.html';	// string - the edit template, false if not used
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
					'type' => 'timestamp',
					'option' => array(),
 					'key' => 'MUL',
					'default' => CURRENT_TIMESTAMP,
					'extra' => '',
					'comment' => 'timestamp',
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
		// turn date into unix date
			$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $data);
			$data = $datetime->getTimestamp();
		return $data;
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 * @todo Remove display/format version
	 **/
	public function save($data, &$object){
		if(is_array($data)){
			// date[format] and date[display] (backwards compatible
			if(!empty($data['format'])){
				$dt = date_create_from_format($data['format'], $data['display']);
				if(is_object($dt)) $data = $dt->getTimestamp();
				else $data = '';
			}
			else{
				$data = $data['value'];
			}
		}
		return array($data, $data);
	}
}


?>