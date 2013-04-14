<?php
/**
 * Field definition for the primary key ID. This is an internal field used by Mozajik as the basic ID field for individual objects.
 * @package Fields
 * @subpackage BuiltinFields
 **/
zajLib::me()->load->file('/fields/text.field.php');

class zajfield_id extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = false;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = false;	// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = false;		// boolean - true if fetch is modified
	const search_field = true;		// boolean - true if this field is used during search()
	const edit_template = '';		// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
			
	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name, $zajlib);
	}	

	/**
	 * Defines the structure and type of this field in the mysql database.
	 * @return array Returns in array with the database definition.
	 **/
	public function database(){
		if($this->options[0] == AUTO_INCREMENT){
			$type = 'int';
			$options = array(0 => 11);
			$extra = AUTO_INCREMENT;
		}
		else{
			$type = 'varchar';
			$options = array(0 => 13);
			$extra = '';
		}
		
		// define each field
			$fields[$this->name] = array(
					'field' => $this->name,
					'type' => $type,
					'option' => $options,
 					'key' => 'PRI',
					'default' => false,
					'extra' => $extra,
					'comment' => 'id',
			);
		return $fields;
	}
	
	public function save($data, &$object){
		$this->zajlib->error("You tried modifying the id of an object. This is not allowed.");
	}

}


?>