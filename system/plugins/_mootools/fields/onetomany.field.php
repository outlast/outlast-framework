<?php
/**
 * Field definition which defines a one to many relationship between models.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_onetomany extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = false;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = true;			// boolean - true if fetcher needs to be modified
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/onetomany.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
		
	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// set default options
			// relation fields dont really have options, they're parameters
			if(empty($options[0])) return zajLib::me()->error("Required parameter 1 missing for field $name!");
		
			// array parameters
			if(is_array($options[0])){
				$options = $options[0];
				if(empty($options['model'])) return zajLib::me()->error("Required parameter 'model' missing for field $name!");
				if(empty($options['field'])) return zajLib::me()->error("Required parameter 'field' missing for field $name!");
			}
			else{	// depricated
				if(empty($options[1])) return zajLib::me()->error("Required parameter 2 missing for field $name!");
				$options['model'] = $options[0];
				$options['field'] = $options[1];
				unset($options[0], $options[1]);
			}
		
		
		// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name, $zajlib);
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
		return zajFetcher::onetomany($this->name, $object);
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajObject $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){	
		// TODO: known bug: if any unsaved changes are cached, this will save those (perhaps unintended). we need a way to save ONE field without saving everything...
		
		// is data a fetcher object? if so, add them
			if(is_object($data) && is_a($data, 'zajFetcher')){
				foreach($data as $id=>$otherobject){					
					// set me in the other object
						$otherobject->set($this->options['field'], $object);
						$otherobject->save();
				}
			}
		// is data a model object? if so, add this one
			elseif(is_object($data) && is_a($data, 'zajModel')){
				// add me
					$data->set($this->options['field'], $object);
					$data->save();
			}
		// is data an array?
		 	elseif(is_string($data)){
			 	$othermodel = $this->options['model'];
		 		$data = json_decode($data);
		 		// compatibility (add/remove is new)
		 		if(!empty($data->add)) $data->new = $data->add;
		 		if(!empty($data->remove)) $data->delete = $data->remove;

		 		if(!empty($data->create)){
		 			foreach($data->create as $id=>$name){
		 				// create
			 				$otherobject = $othermodel::create();
			 				$otherobject->set('name', $name);
							$otherobject->set($this->options['field'], $object);
			 				$otherobject->save();
		 			}
		 		}
		 		if(!empty($data->new)){
		 			// connect
		 			foreach($data->new as $id){
		 				$otherobject = $othermodel::fetch($id);
		 				if($otherobject && $otherobject->exists) $otherobject->set($this->options['field'], $object);
		 			}
		 		}
		 		if(!empty($data->delete)){
		 			// disconnect
		 			foreach($data->delete as $id){
		 				$otherobject = $othermodel::fetch($id);
		 				if($otherobject && $otherobject->exists) $otherobject->set($this->options['field'], '');
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
		// other fetcher's field
			$other_field = $this->options['field'];
		// if value is a fetcher
			if(is_object($value) && is_a($value, 'zajFetcher')){
				// get my other query
					$other_fetcher = $value->limit(false)->sort(false);
				// add field source
					$other_fetcher->add_field_source('model.'.$other_field, 'other_field', true);
			}	
		// else value is an id
			else{
				$model = $this->options['model'];
				$other_fetcher = $model::fetch();
			// filter the other fetcher
				$other_fetcher->filter('id', $value)->limit(false)->sort(false);
				$other_fetcher->add_field_source('model.'.$other_field, 'other_field', true);
			}
		// add source
			$as_name = strtolower('sub_'.$this->class_name.'_'.$this->options['model'].'_'.$this->name);
			$fetcher->add_source('('.$other_fetcher->get_query().')', $as_name);
		// create local query
			return "$as_name.other_field = model.id";
	}
	
}


?>