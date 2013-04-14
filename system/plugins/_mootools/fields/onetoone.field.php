<?php
/**
 * Field definition which defines a one to one relationship between models.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_onetoone extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = false;	// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = false;			// boolean - true if fetcher needs to be modified
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/onetoone.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
		
	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// set default options
			// relation fields dont really have options, they're parameters
			if(empty($options[0])) return zajLib::me()->error("Required parameter 1 missing for field $name!");
			// array parameters
			if(is_array($options[0])) $options = $options[0];
			else{	// depricated
				$options['model'] = $options[0];
				unset($options[0]);
			}


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
					'type' => 'varchar',
					'option' => array(
						0 => 50,
					),
 					'key' => 'MUL',
					'default' => $this->options['default'],
					'extra' => '',
					'comment' => 'onetoone',
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
		return zajFetcher::onetoone($object->class_name, $this->name, $data);
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		// Because there no side is the "parent", we need to choose one. We'll use alphabetical order!
			$array = array($this->options['model'], $object->class_name);
			sort($array);
			list($parent_model, $child_model) = $array;

		// Now let's save both the parent and the child
			// If I am the child, then just return the id (this avoids infinite loop!)
				if($child_model == $object->class_name){
					// Now, if $data is an id, resume it
						if(!is_object($data)) $data = $parent_model::fetch($data);
					return array($data->id, $data);
				}
			// If I am the parent, then save me and the child!
				else{
					// Now, if $data is an id, resume it
						if(!is_object($data)) $data = $child_model::fetch($data);
					// First, save my child
						$child_object = $data;
						// Find my field!
							$fields = $child_object::__model();							
							foreach($fields as $name=>$settings){
								if($settings->type == 'onetoone' && reset($settings->options) == $parent_model){
									// The other field is $child_model / $name
										$child_object->set($name, $object->id);
										$child_object->save();
								}
							}
					// Done saving child!				
				}
		// Now return me.
		return array($data->id, $data);
	}
}


?>