<?php
/**
 * Field definition which defines a many to one relationship between models.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_manytoone extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = true;			// boolean - true if fetcher needs to be modified
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/manytoone.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
	
	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// set default options
			// relation fields dont really have options, they're parameters
			if(empty($options[0])) return zajLib::me()->error("Required parameter 1 missing for field $name!");
			
			// array parameters
			if(is_array($options[0])){
				$options = $options[0];
			}
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
					'comment' => 'manytoone',
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
		return zajFetcher::manytoone($object->class_name, $this->name, $data);
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		if(is_object($data)){
			// check to see if zajModel
				if(!is_a($data, 'zajModel')) $this->zajlib->error('Manytoone connections only accept single model objects!');
			// return my id and me as an object
				return array($data->id, $data);
		}
		else{
			// unload this field to make sure the data is reloaded next time around
				$object->data->unload($this->name);
			// return my id and id (it will be reloaded next time anyway)
				return array($data, $data);
		}
	}

	/**
	 * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
	 * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
	 * @param array $filter An array of values specifying what type of filter this is.
	 **/
	public function filter(&$fetcher, $filter){
		// break up filter
			list($field, $value, $logic, $type) = $filter;
		// assemble code
			// if value is a fetcher
			if(is_object($value) && is_a($value, 'zajFetcher')){
				// get my other query
					$other_fetcher = $value->limit(false)->sort(false);
					$query = '('.$other_fetcher->get_query().')';
				// figure out how to connect me
					if($logic=='NOT LIKE' || $logic=='!=' || $logic=='!==') $logic = "NOT IN";
					else $logic = "IN";
				// generate query and return
					return "model.`$field` $logic $query";
			}
			elseif(is_array($value)){
				// get my other query
					$query = '("'.join('","', $value).'")';
				// figure out how to connect me
					if($logic=='NOT LIKE' || $logic=='!=' || $logic=='!==') $logic = "NOT IN";
					else $logic = "IN";
				// generate query and return
					return "model.`$field` $logic $query";
			}
			else{
				// Possible values: object, string
					if(is_object($value) && is_a($value, 'zajModel')) $value = $value->id;
					elseif($value === false) return "0"; // Return no filter if boolean false
					elseif(!is_string($value)) return $this->zajlib->error("Invalid value given for filter/exclude of fetcher object for $this->class_name/$field! Must be a string, a model object, or a fetcher object!");
				// All is ok, now simply return
					return "model.`$field` $logic '".$this->zajlib->db->escape($value)."'";				
			}
	}

	/**
	 * This method is called just before the input field is generated. Here you can set specific variables and such that are needed by the field's GUI control.
	 * @param array $param_array The array of parameters passed by the input field tag. This is the same as for tag definitions.
	 * @param zajCompileSource $source This is a pointer to the source file object which contains this tag.
	 **/
	public function __onInputGeneration($param_array, &$source){
		// override to print all choices
			// use search method with all			
				$class_name = $this->options['model'];
			// write to compile destination
				$this->zajlib->compile->write('<?php $this->zajlib->variable->field->choices = '.$class_name.'::__onSearch('.$class_name.'::fetch()); if($this->zajlib->variable->field->choices === false) $this->zajlib->warning("__onSearch method required for '.$class_name.' for this input."); ?>');
		return true;
	}


}


?>