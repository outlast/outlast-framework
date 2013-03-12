<?php
/**
 * Field definition for select (enum) fields.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_select extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = false;			// boolean - true if preprocessing required before getting data
	const use_save = false;			// boolean - true if preprocessing required before saving data
	const use_filter = false;		// boolean - true if fetch is modified
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/select.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
		
	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// set default options
			// for backwards compatibility (deprecated)
				if(!empty($options[0])) $options['choices'] = $options[0];
				if(!empty($options[1])) $options['default'] = $options[1];
				unset($options[0], $options[1]);
				if(!is_array($options['choices'])) return exit("You must specify an array of choices for the field $name of type select in $class_name.");
			if(empty($options['default'])) $options['default'] = reset($options['choices']);
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
					'type' => 'enum',
					'option' => $this->options['choices'],
 					'key' => 'MUL',
					'default' => $this->options['default'],
					'extra' => '',
					'comment' => 'select',
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
	 * @param zajObject $object This parameter is a pointer to the actual object which is being modified here.
	 * @return Return the data that should be in the variable.
	 **/
	public function get($data, &$object){
		return $data;
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajObject $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		return $data;	
	}

}


?>