<?php
/**
 * Field definition for true/false.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_boolean extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = false;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = true;		// boolean - true if fetcher needs to be modified
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/boolean.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
		
	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// set default options
			// A single option is interpreted as the default field value (backwards-compatibility)
			if(!is_array($options)) $options = array('default'=>$options);
			else{
				if($options[0]) $options = array('default'=>true);
			}
		// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name, $zajlib);
	}	
	
	/**
	 * Defines the structure and type of this field in the mysql database.
	 * @return array Returns in array with the database definition.
	 **/
	public function database(){
		// set my default
			if($this->{options}['default']) $default = 'yes';
			else $default = '';
		// define each field
			$fields[$this->name] = array(
					'field' => $this->name,
					'type' => 'enum',
					'option' => array('yes',''),
 					'key' => 'MUL',
					'default' => $default,
					'extra' => '',
					'comment' => 'boolean',
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
		if($data && $data !== "no")	$value = 'yes';
		else $value = '';
		return array($value, $value);	
	}

	/**
	 * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
	 * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
	 * @param array $filter An array of values specifying what type of filter this is.
	 **/
	public function filter(&$fetcher, $filter){
		// break up filter
			list($field, $value, $logic, $type) = $filter;
		// modify value to yes or empty
			if($value && $value !== 'no') $value = 'yes';
			else $value = '';
		// filter return
		return "`$field` $logic '$value'";
	}


}


?>