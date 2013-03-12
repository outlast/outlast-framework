<?php
/**
 * Field definition for several locales.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_locales extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = true;		// boolean - true if fetch is modified
	const search_field = true;		// boolean - true if this field is used during search()
	const edit_template = 'field/locales.field.html';	// string - the edit template, false if not used
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
					'comment' => 'locales',
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
		$result = unserialize($data);
		if(!$result) $result = (object) array();
		return $result;
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajObject $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		// Standard array, so serialize
			if($data && is_array($data)) $newdata = serialize($this->zajlib->array->array_to_object($data));
			elseif($data && is_object($data)) $newdata = serialize($data);
			else $newdata = $data;
		return array($newdata, $data);
	}

	/**
	 * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
	 * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
	 * @param array $filter An array of values specifying what type of filter this is.
	 **/
	public function filter(&$fetcher, $filter){
		// break up filter
			list($field, $value, $logic, $type) = $filter;
		// escape value
			
		// modify value to yes or empty
			if($value) $value = 'yes';
			else $value = '';
		// filter return
		return "`$field` $logic '$value'";
	}

	/**
	 * This method is called just before the input field is generated. Here you can set specific variables and such that are needed by the field's GUI control.
	 * @param array $param_array The array of parameters passed by the input field tag. This is the same as for tag definitions.
	 * @param zajCompileSource $source This is a pointer to the source file object which contains this tag.
	 **/
	public function __onInputGeneration($param_array, &$source){
		// override to print all choices
			// write to compile destination
				$this->zajlib->compile->write('<?php $this->zajlib->variable->field->choices = $this->zajlib->lang->get_locales(); ?>');
		return true;
	}

}


?>