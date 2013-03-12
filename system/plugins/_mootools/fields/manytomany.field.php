<?php
/**
 * Field definition which defines a many to many relationship between models.
 * @package Fields
 * @subpackage BuiltinFields
 * @todo Remove unique id generation for subquery aliases, because this (probably) is inefficient (also in manytoone, onetomany)
 **/
class zajfield_manytomany extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = false;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = true;			// boolean - true if fetcher needs to be modified
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/manytomany.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
	
	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// set default options
				if(empty($options[0])) return zajLib::me()->error("Required parameter 1 missing for field $name!");
			// passed as an array of options
				if(is_array($options[0])){
					$options = $options[0];
					// set defaults
					// model
						if(empty($options['model'])) return zajLib::me()->error("Required parameter 'model' missing for field $name!");
					// field (optional)
						if(empty($options['field'])) $options['field'] = false;
					// create (optional, false by default)
						if(empty($options['create'])) $options['create'] = false;
				
				}
			// passed as parameters (depricated!)
				else{
					$options['model'] = $options[0];
					if(!empty($options[1])) $options['field'] = $options[1];
					else $options['field'] = false;
					unset($options[0], $options[1]);
				}
		// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name, $zajlib);
	}	

	/**
	 * This method allows you to create a subtable which is associated with this field.
	 * @return Return the table definition.
	 **/
	public function table(){
		// if this is only a reference to another
			if(!empty($this->options[1])) return false;

		////////////////////////////////////
		// DEFINE TABLE NAME
			$table_name = strtolower('connection_'.$this->class_name.'_'.$this->options['model']);
		////////////////////////////////////	
		// BEGIN MODEL DEFINITION
			$f = (object) array();
			$f->id1 = zajDb::text();
			$f->id2 = zajDb::text();
			$f->field = zajDb::text();
			$f->order1 = zajDb::ordernum();
			$f->order2 = zajDb::ordernum();
			$f->status = zajDb::select(array('active','deleted'), 'active');
			$f->time_create = zajDb::time();
			$f->id = zajDb::id();
		// END OF MODEL DEFINITION
		////////////////////////////////////	

		// now create field objects
			$field_objects = array();
			foreach($f as $name=>$field_def){
				$field_objects[$name] = zajField::create($name, $field_def);
			}
		
		// now return as array
		return array($table_name=>$field_objects);
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
		return zajFetcher::manytomany($this->name, $object);
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajObject $object This parameter is a pointer to the actual object which is being modified here.
	 * @param array $additional_fields Use this to save additional columns in the manytomany table. This parameter is really only useful if you override this method to create a custom field.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object, $additional_fields=false){
		$field_name = $this->name;
		
		// is data a fetcher object or an array of objects? if so, add them
			if(is_array($data) || is_object($data) && is_a($data, 'zajFetcher')){
				foreach($data as $id=>$otherobject){					
					// only save if not connected already (TODO: make this an option!)
						//if(!$object->data->$field_name->is_connected($otherobject))					
							$object->data->$field_name->add($otherobject, 'add', $additional_fields);
				}
			}
		// is data a model object? if so, add this one
			elseif(is_object($data) && is_a($data, 'zajModel')){
				// add me
					$object->data->$field_name->add($data);
			}
		// is data a string of json data?
		 	elseif(is_string($data) && !empty($data)){
			 	$othermodel = $this->options['model'];
		 		$data = json_decode($data);
		 		// compatibility (add/remove is new)
		 		if(!empty($data->add)) $data->new = $data->add;
		 		if(!empty($data->remove)) $data->delete = $data->remove;
		 		// if it is null, warn!
		 		if(empty($data)) $this->zajlib->error('Tried to save a string to manytomany field which is not json data!'." ($field_name / $data)");
		 		// else, continue
		 		if(!empty($data->create)){
		 			foreach($data->create as $id=>$name){
		 				// create
			 				$otherobject = $othermodel::create();
			 				$otherobject->set('name', $name);
			 				$otherobject->save();
			 			// connect
			 				$object->data->$field_name->add($otherobject, 'add', $additional_fields);
		 			}
		 		}
		 		if(!empty($data->new)){
		 			// connect
		 			foreach($data->new as $id){
		 				$otherobject = $othermodel::fetch($id);
		 				if($otherobject && $otherobject->exists) $object->data->$field_name->add($otherobject, 'add', $additional_fields);
		 			}
		 		}
		 		if(!empty($data->delete)){
		 			// disconnect
		 			foreach($data->delete as $id){
		 				$otherobject = $othermodel::fetch($id);
		 				if($otherobject && $otherobject->exists) $object->data->$field_name->remove($otherobject);
		 			}
		 		}
		 		if(!empty($data->order)){
		 			// TODO: add order support for manytomany fields
		 		}				
		 	}
				
		// unload this field to make sure the data is reloaded next time around
			$object->data->unload($this->name);				
		// return whatever...first param will be removed, second reloaded
			return array(false, false);		 	
	}
	
	/**
	 * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
	 * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
	 * @param array $filter An array of values specifying what type of filter this is.
	 **/
	public function filter(&$fetcher, $filter){
		// break up filter
			list($field, $value, $logic, $type) = $filter;
		// First get my connection table
			if(empty($this->options['field'])){
				$table_name = strtolower('connection_'.$this->class_name.'_'.$this->options['model']);
				$my_field = "id1";
				$their_field = "id2";
			}
			else{
				$table_name = strtolower('connection_'.$this->options['model'].'_'.$this->class_name);
				$my_field = "id2";
				$their_field = "id1";				
			}
		
		// Assemble subquery
			// if value is a fetcher
			if(is_object($value) && is_a($value, 'zajFetcher')){
				// prepare my other query (remove limits, sorts)
					$other_fetcher = $value->limit(false)->sort(false);
				// generate subquery
					$query = "SELECT $my_field as id FROM $table_name WHERE $their_field IN (".$other_fetcher->get_query().")";
			}
			// if value is an array of ids
			elseif(is_array($value)){
				// list of ids
					$list = "";
					foreach($value as $v) $list .= "'".addslashes($v)."', ";
					$list = substr($list, 0, -2);
				// generate subquery
					$query = "SELECT $my_field as id FROM $table_name WHERE $their_field IN (".$list.")";			
			}
			// if value is a single object or single id
			else{
				// Possible values: object, string
					// If object, convert to id string
						if(is_object($value) && is_a($value, 'zajModel')) $value = $value->id;
					$query = "SELECT DISTINCT $my_field AS id FROM $table_name AS conn WHERE conn.$their_field = '".$this->zajlib->db->escape($value)."'";
			}
		// Create logic and query
			// figure out how to connect me
				if($logic=='NOT LIKE' || $logic=='!=' || $logic=='!==') $logic = "NOT IN";
				else $logic = "IN";
			// Generate query and return
				return "model.`id` $logic ($query)";
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