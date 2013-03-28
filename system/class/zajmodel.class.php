<?php
/**
 * The basic model class.
 *
 * This is an abstract model class from which all model classes are derived.
 *
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Model
 */

define('MAX_EVENT_STACK', 50);
define('CACHE_DIR_LEVEL', 4);

/**
 * This is the abstract model class from which all model classes are derived.
 *
 * All model classes need to extend zajModel which provides the basic set of methods and variables to the object.
 *
 * @author Aron Budinszky <aron@mozajik.org>
 * @package Model
 * @subpackage DefaultModel
 * @abstract Model files extend this base class.
 * @method boolean __beforeCreateSave() EVENT. Executed before the object is first created in the database. If returns false, the object is not saved!
 * @method boolean __beforeSave() EVENT. Executed before the object is saved to the database. If returns false, the object is not saved!
 * @method boolean __beforeCache() EVENT. Executed before the object is saved to a cache file. If returns false, the object is not cached!
 * @method boolean __beforeUncache() EVENT. Executed before the object cache is removed. If it returns false, the object cache will not be removed! Note: This may not be called in every situation!
 * @method boolean __beforeDelete() EVENT. Executed before the object is deleted. If returns false, the object is not deleted!
 * @method void __afterCreateSave() EVENT. Executed after the object is created in the database.
 * @method void __afterCreate() EVENT. Executed after the object is created in memory.
 * @method void __afterSave() EVENT. Executed after the object is saved to the database.
 * @method void __afterFetch() EVENT. Executed after the object is fetched from the database (and NOT from cache). Also fired after save.
 * @method void __afterFetchCache() EVENT. Executed after the object is fetched from a cache file. Note that this is also fired after a database fetch.
 * @method void __afterCache() EVENT. Executed after the object is saved to a cache file.
 * @method void __afterUncache() EVENT. Executed after the object cache is removed (but only if the remove was successful) Note: This may not be called in every situation!
 * @method void __afterDelete() EVENT. Executed after the object is deleted.
 * @method void __onFetch() EVENT. Executed when a fetch method is requested.
 * @method void __onCreate() EVENT. Executed when a create method is requested.
 * @method zajFetcher __onSearch() __onSearch(zajFetcher $fetcher, string $type) EVENT. Executed when an auto-search is running on the class.
 * Properties...
 * @property zajLib $zajlib A pointer to the global object.
 * @property string $name The name of the object.
 */
abstract class zajModel {
	// Instance variables
	/**
	 * Stores the unique id of this object
	 * @var string
	 **/
	public $id;
	/**
	 * Stores the value of the name field.
	 * @var string
	 **/
	private $name;
	/**
	 * Stores the name (or key) of the name field.
	 * @var string
	 **/
	public $name_key;

	// Model structure
	/**
	 * Stores the field types as an associative array. See {@see zajDb}.
	 * @var array
	 **/
	private $model;
	/**
	 * True if the object exists in the database, false otherwise.
	 * @var boolean
	 **/
	protected $exists = false;

	// Model settings
	/**
	 * Set to true if this object should be stored in the database.
	 * @var boolean
	 **/
	public static $in_database = true;
	/**
	 * Set to true if this object should have translations associated with it.
	 * @var boolean
	 **/
	public static $has_translations = true;
	/**
	 * Set to DESC or ASC depending on the default fetch sort order.
	 * @var string
	 **/
	public static $fetch_order = 'DESC';
	/**
	 * Set to the field which should be the default fetch sort field.
	 * @var string
	 **/
	public static $fetch_order_field = 'ordernum';
	/**
	 * Set the pagination default or leave as unlimited (which is the default value of 0)
	 * @var integer
	 **/
	public static $fetch_paginate = 0;

	// Mysql database and child details / settings
	/**
	 * My class (or model) name.
	 * @var string
	 **/
	public $class_name = "zajModel";
	/**
	 * My table name (typically a lower-case form of class_name)
	 * @var string
	 **/
	public $table_name = "models";
	/**
	 * My id column key/name ('id' by default)
	 * @var string
	 **/
	public $id_column = "id";

	// Objects used by this class
	/**
	 * Access to the database-stored data through the object's own {@link zajData} object.
	 * @var zajData
	 **/
	private $data;

	/**
	 * Access to the database-stored translation data through the object's own {@link zajModelLocalizer} object.
	 * @var zajModelLocalizer
	 **/
	private $translations;

	// Object event stack
	/**
	 * The event stack, which is basically an array of events currently running.
	 * @var array
	 **/
	private $event_stack = array();

	/**
	 * This is an object-specific private variable which registers if any extension of $this has had its event fired. This is used to prevent infinite loops.
	 * @var boolean
	 **/
	public $event_child_fired = false;


	// Model extension
	/**
	 * A key/value pair array of all extended models
	 * @var array
	 * @todo If it is possible to store this on a per-class basis, it would be better than this 'global' way!
	 **/
	public static $extensions = array();

	/**
	 * Constructor for model object. You should never directly call this. Use {@link: create()} instead.
	 *
	 * @param string $id The id of the object.
	 * @param string $class_name The name of child class (model class).
	 * @return zajModel
	 */
	public function __construct($id, $class_name){
		$class_name = get_called_class();
		// check for errors
		if($id && !is_string($id)) zajLib::me()->error("Invalid ID value given as parameter for model constructor! You probably tried to use an object instead of a string!");
		// set class and table names
		$this->table_name = strtolower($class_name);
		$this->class_name = $class_name;
		// set id if its empty
		if($id == false) $this->id = uniqid("");
		else $this->id = $id;

		// everything else is loaded on request!
		return true;
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////
	// !Static Methods
	/////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Get the model structure for this object.
	 * @param object $fields The field's object generated by the child class.
	 * @param bool|object $compatibility_mode For older versions of OFW, this is where $fields are set and the first param is the class name.
 	 * @return object Returns an object containing the field settings as parameters.
	 */
	public static function __model($fields, $compatibility_mode = false){
		// If I am in compatibility mode
			if($compatibility_mode !== false) $fields = $compatibility_mode;
		// Get my class_name
			/* @var string|zajModel $class_name */
			$class_name = get_called_class();
			if(!$class_name::$in_database) return false; 	// disable for non-database objects
		// do I have an extension? if so, these override my own settings
			$ext = $class_name::extension();
			if($ext){
				// Merge my field objects together.
				$fields = (object) array_merge((array) $fields, (array) $ext::__model());
			}
			// now set defaults (if not already set)
			if(!isset($fields->time_create)) $fields->time_create = zajDb::time();
			if(!isset($fields->time_edit)) $fields->time_edit = zajDb::time();
			if(!isset($fields->ordernum)) $fields->ordernum = zajDb::ordernum();
			if(!isset($fields->status)) $fields->status = zajDb::select(array("new","deleted"),"new");
			if(!isset($fields->id)) $fields->id = zajDb::id();
		// if i am not in static mode, then i can save it as $this->fields
		return $fields;
	}
	/**
	 * Get the field object for a specific field in the model.
	 * @param string $field_name The name of the field in the model.
	 * @return object Returns a zajField object.
	 */
	public static function __field($field_name){
		// Get my class_name
		/* @var string|zajModel $class_name */
		$class_name = get_called_class();
		// make sure $field is chrooted
		if(strpos($field_name, '.')) return zajLib::me()->error('Invalid field name "'.$field_name.'" used in model "'.$class_name.'".');
		// TODO: can I create a version where $this is set?
		// get model
		$field_def = $class_name::__model()->$field_name;
		if(empty($field_def)) return zajLib::me()->error('Undefined field name "'.$field_name.'" used in model "'.$class_name.'".');
		// create my field object
		$field_object = zajField::create($field_name, $field_def, $class_name);
		return $field_object;
	}

	/**
	 * Fetch a single or multiple existing object(s) of this class.
	 * @param bool|string|zajModel $id OPTIONAL. The id of the object. Leave empty if you want to fetch multiple objects. You can also pass an existing zajModel object in which case it will simply pass through the function without change - this is useful so you can easily support both id's and existing objects in a function.
	 * @return zajFetcher|self Returns a zajFetcher object (for multiple objects) or a zajModel object (for single objects).
	 */
	public static function fetch($id=false){
		// Get my class_name
		/* @var string|zajModel $class_name */
		$class_name = get_called_class();
		// if id is specifically  empty, then return false
		if($id !== false && $id == '') return false;
		// call event
		$class_name::fire_static('onFetch', array($class_name, $id));
		// disable for non-database objects if id not given!
		if($id === false && !$class_name::$in_database) return false;
		// if id is false, then this is a multi-row fetch
		if($id === false) return new zajFetcher($class_name);
		// let's see if i can resume it!
		else{
			// first, is it already resumed? in this case let's make sure its the proper kind of object and just return it
			if(is_object($id)){
				// is it the proper kind of object? if not, warning, if so, return it
				if($class_name != $id->class_name) return zajLib::me()->warning("You passed an object to $class_name::fetch(), but it was not a(n) $class_name object. It is a $id->class_name instead.");
				else return $id;
			}
			// not resumed, so let's assume its a string and return the cache
			else return $class_name::get_cache($id);
		}
	}

	/**
	 * Create a new object in this model.
	 * @param bool|string $id Id's are automatically generated by default, but you can force one here. Once created, id's cannot be changed.
	 * @return zajModel Returns a brand new zajModel object.
	 */
	public static function create($id=false){
		// Get my class_name
		/* @var zajModel $class_name */
		$class_name = get_called_class();
		// call event
		$class_name::fire_static('onCreate', array($class_name));
		// create the new object
		$new_object = new $class_name(false, $class_name);
		// if id specified
		if($id) $new_object->id = $id;
		// do i have any extenders?
		/* @var zajModelExtender $ext */
		$ext = $class_name::extension();
		/* @var zajModelExtender $new_object */
		if($ext) $new_object = $ext::create($new_object, $id);
		// call the callback function
		$new_object->fire('afterCreate');
		// and return	
		return $new_object;
	}

	/**
	 * Set the value of a field for this object.
	 *
	 * @param string $field_name The name of model field.
	 * @param mixed $value The new value of the field.
	 * @return zajModel Returns me to allow chaining.
	 */
	public function set($field_name, $value){
		// disable for non-database objects
		if(!$this::$in_database) return false;
		// init the data object if not done already
		if(!$this->data) $this->data = new zajData($this);
		// set it in the data object
		$this->data->__set($field_name, $value);
		return $this;
	}

	/**
	 * Sets all the fields specified by the list of parameters. It uses GET or POST requests, and ignores fields where no value was sent (that is, not even an empty value). In cases where you need more control, use {@link set()} for each individual field.
	 * @internal param string $field_name1 The first parameter to set.
	 * @internal param string $field_name2 The second parameter to set.
	 * @internal param string $field_name3 The third parameter to set...etc...
	 * @return zajModel Returns me to allow chaining.
	 */
	public function set_these(){
		// Use _GET or _POST
		$_POST = array_merge($_GET, $_POST);
		// Run through each argument
		foreach(func_get_args() as $field_name){
			$this->set($field_name, $_POST[$field_name]);
		}
		return $this;
	}

	/**
	 * Save the values set by {@link: set()} to the database.
	 * @param boolean $events_and_cache If set to true, the before and after events and cacheing will be fired. If false, they will be ignored. This is useful when you are trying to speed things up (during import of data for example).
	 * @return zajModel|boolean Returns the chainable object if successful, false if beforeSave prevented it.
	 */
	public function save($events_and_cache = true){
		// same as cache() for non-database objects
		if(!$this::$in_database) return $this->cache();
		// init the data object if not done already
		if(!$this->data) $this->data = new zajData($this);
		// call beforeCreateSave event (only if this does not exist yet)
		$exists_before_save = $this->data->exists();
		if($events_and_cache && !$exists_before_save) $this->fire('beforeCreateSave');
		// call beforeSave event
		if($events_and_cache && $this->fire('beforeSave') === false) return false;
		// set it in the data object
		$this->data->save();
		// call afterSave events 
		if($events_and_cache && !$exists_before_save) $this->fire('afterCreateSave');
		if($events_and_cache) $this->fire('afterSave');
		if($events_and_cache) $this->fire('afterFetch');
		// IMPORANT: these same events also called after adding/removing connections if that is done by another object.
		// cache the new values
		if($events_and_cache) $this->cache();
		return $this;
	}


	/**
	 * Set the object status to deleted or remove from the database.
	 *
	 * @param boolean $permanent OPTIONAL. If set to true, object is permanently removed from db. Defaults to false.
	 * @return boolean Always returns true.
	 */
	public function delete($permanent = false){
		// fire __beforeDelete event
		if($this->fire('beforeDelete') === false) return false;
		// same as cache removal for non-database objects
		if(!$this::$in_database) return $this->uncache();
		// init the data object if not done already
		if(!$this->data) $this->data = new zajData($this);
		// set it in the data object
		$this->data->delete($permanent);
		// now fire __afterDelete
		$this->fire('afterDelete');
		return true;
	}


	/**
	 * Fire an event.
	 * @param string Event name.
	 * @param array|bool Array of parameters. Leave empty if no params.
	 * @return mixed Returns the value returned by the event method.
	 * @todo Somehow disable event methods from being declared public. They should be private or protected!
	 * @todo Make this static-compatible (though you cannot do event stack in that case! or can you?)
	 * @todo Optimize this!
	 **/
	public function fire($event, $arguments = false){
		// Do I even need to fire a child?
		if(!$this->event_child_fired){
			// Do I have an extension? If so, go down one level and start from there...
			$ext = self::extension();
			if($ext){
				// Create my child object, set event fired to true, and fire it!
				$child = $ext::create($this);
				$this->event_child_fired = true;
				return $child->fire($event, $arguments);
			}
		}
		// We are back here now, so set my child event fired to false
		$this->event_child_fired = false;
		// Add event to stack
		$stack_size = array_push($this->event_stack, $event);
		$this->zajlib->event_stack++;
		// Check stack size
		if($stack_size > MAX_EVENT_STACK) $this->zajlib->error("Exceeded maximum event stack size of ".MAX_EVENT_STACK." for object ".$this->class_name.". Possible infinite loop?");
		if($this->zajlib->event_stack > MAX_GLOBAL_EVENT_STACK) $this->zajlib->error("Exceeded maximum global event stack size of ".MAX_GLOBAL_EVENT_STACK.". Possible infinite loop?");
		// If no arguments specified
		if($arguments === false) $arguments = array();
		// Call event function
		$return_value = call_user_func_array(array($this, '__'.$event), $arguments);
		// Remove from stack
		array_pop($this->event_stack);
		$this->zajlib->event_stack--;
		// Return value
		return $return_value;
	}

	/**
	 * Fire a static event.
	 * @param string $event The name of the event.
	 * @param array|bool $arguments Array of parameters. Leave empty if no params.
	 * @return mixed Returns the value returned by the event method.
	 */
	public static function fire_static($event, $arguments = false){
		// Make sure propagation is enabled
		zajModelExtender::$event_stop_propagation = false;
		// Get my class_name
		$class_name = get_called_class();
		// Add event to stack
		zajLib::me()->event_stack++;
		// Check stack size
		if(zajLib::me()->event_stack > MAX_GLOBAL_EVENT_STACK) zajLib::me()->error("Exceeded maximum global event stack size of ".MAX_GLOBAL_EVENT_STACK.". Possible infinite loop?");
		// If no arguments specified
		if($arguments === false) $arguments = array();
		// Do I have an extension? If so, go down one level...
		$ext = self::extension();
		if($ext) $return_value = $ext::fire_static($event, $arguments);
		// Check to see if stop propagation, if so, return the return_value
		if(zajModelExtender::$event_stop_propagation){
			zajModelExtender::$event_stop_propagation = false;
			zajLib::me()->event_stack--;
			return $return_value;
		}
		// Call my version
		if(method_exists($class_name, '__'.$event)) $return_value = call_user_func_array("$class_name::__".$event, $arguments);
		else $return_value = false;
		// Remove from stack
		zajLib::me()->event_stack--;
		// Return value
		return $return_value;
	}


	/**
	 * This method returns the class name of the class which extends me.
	 * @return string The name of my extension class.
	 **/
	public static function extension(){
		$class_name = get_called_class();
		if(!empty(zajModel::${'extensions'}[$class_name])) return zajModel::${'extensions'}[$class_name];
		return false;
	}


	/**
	 * This method looks for methods in extends children and creates "virtual" menthods to events and actions.
	 *
	 * @ignore
	 */
	public function __call($name, $arguments){
		// Get my class name
		$class_name = get_called_class();
		// zajModel events
		switch($name){
			case '__beforeCreateSave':
			case '__beforeSave':
			case '__beforeCache':
			case '__beforeUncache':
			case '__beforeDelete':
				return true;
			case '__afterCreateSave':
			case '__afterCreate':
			case '__afterSave':
			case '__afterDelete':
			case '__afterFetch':
			case '__afterFetchCache':
			case '__afterCache':
			case '__afterUncache':
				return true;
			default:		break;
		}
		// Search for the method in any of my parents

		// Search for the method in any of my children
		$child_class_name = $class_name;
		// Set my extension and repeat while it exists
		$my_extension = $child_class_name::extension();
		while($my_extension){
			// Let's check to see if the method exists here
			if(method_exists($child_class_name, $name)) return call_user_func_array("$child_class_name->$name", $arguments);
			// Not found, now go up one level
			else $child_class_name = $my_extension;
			// Set my extension
			$my_extension = $child_class_name::extension();
		}
		// Not found anywhere, return error!
		$this->zajlib->warning("Method $name not found in model '$class_name' or any of it's child models.");
	}
	/**
	 * Shortcuts to static events and actions.
	 *
	 * @ignore
	 * @todo Once you remove passing of CLASS_NAME via $arguments[0] you MUST also remove array_shift() in this function.
	 */
	public static function __callStatic($name, $arguments){
		// get current class
		$class_name = get_called_class();
		// any specific static?
		switch($name){
			// Validation
			case 'validate':			return zajLib::me()->form->validate($class_name, $arguments);
			case 'check':				return zajLib::me()->form->check($class_name, $arguments);
			case 'filled':				return zajLib::me()->form->filled($arguments);
			// Extending
			case 'extend':
			case 'extension_of':
				zajLib::me()->error("The class $arguments[0] is not a child of zajModelExtender. Check the valid syntax for extending classes!");
				return false;
		}
		// do I have an extension? if so, these override my own settings but only if method is not __model() as that is special!
		$extended_but_does_not_exist = false;
		$ext = $class_name::extension();
		if($ext && $name != '__model' && $name != 'create'){
			// now, check if method exists on extension
			if(method_exists($ext, $name)){
				array_shift($arguments);
				return call_user_func_array("$ext::$name", $arguments);
			}
			else  $extended_but_does_not_exist = true;
		}
		// redirect static method calls to local private ones
		if(!method_exists($arguments[0], $name)) zajLib::me()->error("called undefined method '$name'!"); return call_user_func_array("$arguments[0]::$name", $arguments);
	}
	/**
	 * Shortcuts to private variables (lazy loading)
	 *
	 * @ignore
	 */
	public function __get($name){
		// the zajlib
		switch($name){
			case "zajlib": 		return zajLib::me();
			case "data":		if(!$this::$in_database) return false; 	// disable for non-database objects
				if(!$this->data) return $this->data = new zajData($this);
				return $this->data;
			case "translation":
			case "translations":if(!$this::$has_translations) return false; 	// disable where no translations available
				if(!$this->translations) return $this->translations = new zajModelLocalizer($this);
				return $this->translations;
			case "autosave":	if(!$this::$in_database) return false; 	// disable for non-database objects
				if(!$this->data) $this->data = new zajData($this);
				// turn on autosave
				$this->data->__autosave = true;
				$returned = $this->data;
				return $returned;
			case "model":		if(!$this::$in_database) return false; 	// disable for non-database objects
				if(!$this->model) return $this->model = $this->__model();
				else return $this->model;
			case "exists":		if(!$this::$in_database) return true; 	// always return true for non-database objects
				if(!$this->data) $this->data = new zajData($this);
				return $this->data->exists();
			case "name":		if(!$this::$in_database || $this->name) return $this->name;
				// load model if not yet loaded
				if(!$this->model) $this->model = $this->__model();
				// load data
				if(!$this->data) $this->data = new zajData($this);
				// look for name and return if found
				foreach($this->model as $field=>$fdata){
					if($fdata->type == 'name'){					// actual name field
						$this->name_key = $field;
						return $this->name = $this->data->$field;
					}
					if(!$this->name && $fdata->type == 'text'){ 	// first text field
						$this->name_key = $field;
						$this->name = $this->data->$field;
					}
				}
				return $this->name;
			case "name_key":	if(!$this::$in_database) return false; 	// disable for non-database objects
				if($this->name_key) return $this->name_key;
				// load name
				$this->__get("name");
				// now return it
				return $this->name_key;
		}
	}
	/**
	 * @ignore
	 */
	public function __toString(){
		return $this->__get('name');
	}

	/**
	 * Gets the cached version of an object.
	 * @param string $id. The id of the object.
	 * @return zajModel Returns the object.
	 * @ignore
	 * @todo Disable get_cache from being called outside. Events should be used instead of overriding...
	 */
	public static function get_cache($id){
		// get current class
		$class_name = get_called_class();
		// return the resumed class
		$filename = zajLib::me()->file->get_id_path(zajLib::me()->basepath."cache/object/".$class_name, $id.".cache", false, CACHE_DIR_LEVEL);
		// try opening the file
		$item_cached = false;
		if(!file_exists($filename)){
			// create object
			$new_object = new $class_name($id);
			// get my name (this will grab the db)
			if($new_object::$in_database) $new_object->__get('name');
		}
		else{
			$new_object = unserialize(file_get_contents($filename));
			$new_object->zajlib = zajLib::me();
			$item_cached = true;
		}
		// this is resumed from the db, so load the data
		if(!$new_object->exists && !$item_cached){
			// if in database load data
			if($new_object::$in_database){
				$new_object->data = new zajData($new_object);
				$new_object->exists = $new_object->data->exists();
			}
			// if does not exist, send back with false (only for ones with database)
			if($new_object::$in_database && !$new_object->exists) return false;
			// else, send to callback
			$new_object->fire('afterFetch');
			// now save to cache
			$new_object->cache();
		}
		// end of db fetch

		// one more callback, before finishing and returning
		$new_object->fire('afterFetchCache');
		return $new_object;
	}

	/**
	 * Remove a cached version of an object.
	 *
	 * @return zajModel|boolean Returns the chainable object if successful, false if beforeSave prevented it.
	 */
	public function uncache(){
		// call __beforeUncache event
		if($this->fire('beforeUncache') === false) return false;
		// return the resumed class
		$this->zajlib->load->library("file");
		$filename = $this->zajlib->file->get_id_path($this->zajlib->basepath."cache/object/".$this->class_name, $this->id.".cache", false, CACHE_DIR_LEVEL);
		// if remove is successful, call __afterUncache event and return true. false otherwise
		if(!@unlink($filename)) return false;
		else{
			$this->fire('afterUncache');
			return $this;
		}
	}
	/**
	 * Create a cached version of the object.
	 *
	 * @return zajModel|boolean Returns the chainable object if successful, false if beforeSave prevented it.
	 */
	public function cache(){
		// call __beforeCache event
		if($this->fire('beforeCache') === false) return false;
		// if not in_database, then this is creating it, so exists will equal to true
		if(!$this::$in_database) $this->exists = true;
		// get filename
		$this->zajlib->load->library("file");
		$filename = $this->zajlib->file->get_id_path($this->zajlib->basepath."cache/object/".$this->class_name,$this->id.".cache", true, CACHE_DIR_LEVEL);
		// model, data do not need to be saved!
		$data = $this->data;
		$model = $this->model;
		$this->data=$this->model=$this->zajlib="";
		// check for objects
		foreach($this as $varname=>$varval) if(is_object($varval) && is_a($varval, 'zajModel')){ zajLib::me()->warning("You cannot cache a Model object! Found at variable $this->class_name / $varname."); $this->$varname = "[Cache error: $this->class_name / $varname]"; }
		// now serialize and save to file
		file_put_contents($filename, serialize($this));
		// now bring back data
		$this->data = $data;
		$this->model = $model;
		$this->zajlib = zajLib::me();
		// call the callback function
		$this->fire('afterCache');
		return $this;
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////
	// reorder support, PHP 5.3+ version (TODO: move this to a lib)
	public static function reorder($reorder_array, $reverse_order = false){
		// get current class
		$class_name = get_called_class();
		// this supports JSON input from a Sortables.serialize method of mootools, always returns true
		if(is_string($reorder_array)) $reorder_data = json_decode($reorder_array);
		else $reorder_data = $reorder_array;
		// continue processing the array of ids
		if((is_object($reorder_data) || is_array($reorder_data)) && count($reorder_data) > 0){
			// get the order num of each
			foreach($reorder_data as $oneid){
				$obj = $class_name::fetch($oneid);
				// if failed to find, issue warning
				if(!is_object($obj) || !is_a($obj, 'zajModel')) zajLib::me()->warning("Tried to reorder non-existant object!");
				// all is okay
				else{
					// TODO: fix, but for now explicitly load data class, because autoload won't work in current scope
					$myobj[$oneid] = $obj;
					$myobj[$oneid]->data = new zajData($myobj[$oneid]);
					$array_of_ordernums[] = $myobj[$oneid]->data->ordernum;
				}
			}
			// Only proceed if actual array done
			if(is_array($array_of_ordernums)){
				// place them in order of descendence
				if($class_name::$fetch_order=="DESC" && !$reverse_order || $class_name::$fetch_order=="ASC" && $reverse_order) rsort($array_of_ordernums);
				else sort($array_of_ordernums);
				// now start with the first
				$current_ordernum = reset($array_of_ordernums);
				// now set their order id
				foreach($myobj as $oneobj){
					$oneobj->set('ordernum',$current_ordernum);
					$oneobj->save();
					$current_ordernum = next($array_of_ordernums);
				}
			}
		}
		return true;
	}

}

/**
 * This is the abstract extender model class from which all extended model classes are derived.
 *
 * @author Aron Budinszky <aron@mozajik.org>
 * @package Model
 * @subpackage DefaultModel
 * @abstract Model files which extend other models should extend this base class.
 */
abstract class zajModelExtender {
	// Instance variables
	/**
	 * A pointer to my parent object.
	 * @var zajModel
	 **/
	public $parent;

	/**
	 * The name of my own class.
	 * @var string
	 **/
	private $class_name;

	/**
	 * A key/value pair array of all parent models
	 * @var array
	 * @todo If it is possible to store this on a per-class basis, it would be better than this 'global' way!
	 **/
	private static $parents = array();

	/**
	 * The number of extender events currently running.
	 * @var integer
	 **/
	private static $event_stack_size = 0;

	/**
	 * Set to true if the current event propagation needs to be stopped (done by the static method STOP_PROPAGATION!)
	 * @var boolean
	 **/
	public static $event_stop_propagation = false;

	/**
	 * Set to true if this object should be stored in the database.
	 * @todo This should be set somehow based on parent.
	 * @var boolean
	 **/
	public static $in_database = true;

	/**
	 * Constructor for extender objects.
	 * @param zajModel $parent My parent object.
	 * @return zajModelExtender
	 */
	private function __construct($parent){ $this->parent = $parent; }

	/**
	 * This method allows you to extend existing models in a customized fashion.
	 * @param string $parentmodel The name of the model to extend. By default, Mozajik will try to extend plugins. If you need to extend something else, use the $model_source_file parameter.
	 * @param bool|string $known_as The name which it will be known by to controllers trying to access this model. By default, it is known by the name of the model it extends.
	 * @param bool|string $parentmodel_source_file An optional parameter which specifies the relative path to the source file containing the model to extend.
	 * @return bool
	 * @todo Once there is a solution for non-explicitly declared static variables, use that! See http://stackoverflow.com/questions/5513484/php-static-variables-in-an-abstract-parent-class-question-is-in-the-sample-code
	 */
	public static function extend($parentmodel, $known_as = false, $parentmodel_source_file = false){
		// Check to see if already extended (this will never run because once it is extended the parent class will exist, and any additional iterations will not autoload the other model file! fix this somehow to warn the user!)
		// if(!empty(zajModel::${extensions}[$parentmodel])) return zajLib::me()->error("Could not extend $parentmodel with $childmodel because the class $parentmodel was already extended by ".zajModel::${extensions}[$parentmodel].".");
		// Determine where the user called from
		$childmodel = get_called_class();
		// If a specific parentmodel source file was specified, use that!
		if(!class_exists($parentmodel, false) && $parentmodel_source_file) zajLib::me()->load->file($parentmodel_source_file, true, true, "specific");
		// If the current class does not exist, try to load it from all files in the plugin app hierarchy
		if(!class_exists($parentmodel, false)){
			foreach(zajLib::me()->loaded_plugins as $plugin_app){
				// Attempt to load file
				$result = zajLib::me()->load->file('plugins/'.$plugin_app.'/model/'.strtolower($parentmodel).'.model.php', false, true, "specific");
				// If successful, break
				if($result && class_exists($parentmodel, false)) break;
			}
		}
		// If the current class does not exist, try to load it from all files in the system app hierarchy
		if(!class_exists($parentmodel, false)){
			foreach(zajLib::me()->zajconf['system_apps'] as $system_app){
				// Attempt to load file
				$result = zajLib::me()->load->file('system/plugins/'.$system_app.'/model/'.strtolower($parentmodel).'.model.php', false, true, "specific");
				// If successful, break
				if($result && class_exists($parentmodel, false)) break;
			}
		}
		// See if successful
		if(class_exists($parentmodel, false)){
			// Add to my extensions
			zajModel::${'extensions'}[$parentmodel] = $childmodel;
			// Add to my parents
			zajModelExtender::${'parents'}[$childmodel] = $parentmodel;
			return true;
		}
		else return zajLib::me()->error("Could not extend $parentmodel with $childmodel because the class $parentmodel was not found in any plugin or system apps.");
	}

	/**
	 * This method returns the class name of my parent class.
	 * @return string The name of my extension class.
	 **/
	public static function extension_of(){
		$class_name = get_called_class();
		return zajModelExtender::${'parents'}[$class_name];
	}

	/**
	 * This method returns the class name of the class which extends me.
	 * @return string The name of my extension class.
	 **/
	public static function extension(){
		$class_name = get_called_class();
		return zajModel::${'extensions'}[$class_name];
	}

	/**
	 * Override model. Check to see if I hae extensions and extend me.
	 **/
	public static function __model($class_name, $fields){
		// do I have an extension? if so, these override my own settings
		$ext = $class_name::extension();
		if($ext){
			// Merge my field objects together.
			$fields = (object) array_merge((array) $fields, (array) $ext::__model());
		}
		return $fields;
	}

	/**
	 * This helps create a model-like object which is actually an extender object.
	 * @param bool|zajModel $parent_object The parent object that I extend.
	 * @return zajModelExtender The extended zajModel object in the form of a zajModelExtender object.
	 */
	public static function create($parent_object = false){
		$class_name = get_called_class();
		// create a new class based on my parent_object
		$object = new $class_name($parent_object);
		$object->class_name = $class_name;
		// check to see if i have any extensions...if so, repeat recursively!
		$ext = $class_name::extension();
		if($ext) $object = $ext::create($object);
		return $object;
	}


	/**
	 * Redirect inaccessible static method calls to my parent.
	 **/
	public static function __callStatic($name, $arguments){
		$class_name = get_called_class();
		$parent_class = $class_name::extension_of();
		// redirect static method calls to local private ones
		return call_user_func_array("$parent_class::$name", $arguments);
	}

	/**
	 * Redirect inaccessible method calls to my parent.
	 **/
	public function __call($name, $arguments){
		// redirect method calls to the parent object
		$parent_class = $this->parent->class_name;
		$my_class = $this->class_name;
		// call the method whether-or-not it exists, parent should handle errors...
		return call_user_func_array(array($this->parent, $name), $arguments);
	}

	/**
	 * Redirect inaccessible property setters to my parent.
	 **/
	public function __set($name, $value){
		return $this->parent->$name = $value;
	}

	/**
	 * Redirect inaccessible property getters to my parent.
	 **/
	public function __get($name){
		return $this->parent->$name;
	}

	/**
	 * Fire event for me if it exists, then just send on to parent.
	 *
	 * Firing non-static event methods starts at the child and goes to parent.
	 * @parameter string $event The name of the event.
	 * @parameter array $arguments An optional array of arguments to be passed to the event handler.
	 * @todo Add support for static events.
	 * @todo Add support for event stack.
	 **/
	public function fire($event, $arguments = false){
		// Make sure propagation is enabled
		zajModelExtender::$event_stop_propagation = false;
		// Add to current event stack
		zajModelExtender::$event_stack_size++;
		// Check event stack size
		if(zajModelExtender::$event_stack_size > MAX_EVENT_STACK) return $this->zajlib->error("Maximum extender event stack size exceeded. You probably have an infinite loop somewhere!");
		// Check to see if event function exists
		if(!$arguments) $arguments = array();
		if(method_exists($this, '__'.$event)) $return_value = call_user_func_array(array($this, '__'.$event), $arguments);
		// Subtract from current event stack
		zajModelExtender::$event_stack_size--;
		// Check to see if stop propagation, if so, return the return_value
		if(zajModelExtender::$event_stop_propagation){
			zajModelExtender::$event_stop_propagation = false;
			return $return_value;
		}
		// Now call for parent but tell them the child has been fired
		else{
			$this->parent->event_child_fired = true;
			return $this->parent->fire($event, $arguments);
		}
	}

	/**
	 * Fire a static event.
	 *
	 * Firing static event methods starts at the parent, but goes to child before the parent is executed. Thus the result is the same as with non-static events.
	 * @param string Event name.
	 * @param array|bool Array of parameters. Leave empty if no params.
	 * @return mixed Returns the value returned by the event method.
	 **/
	public static function fire_static($event, $arguments = false){
		// Make sure propagation is enabled
		zajModelExtender::$event_stop_propagation = false;
		// Get my class_name
		$class_name = get_called_class();
		// Add event to stack
		zajLib::me()->event_stack++;
		// Check stack size
		if(zajLib::me()->event_stack > MAX_GLOBAL_EVENT_STACK) zajLib::me()->error("Exceeded maximum global event stack size of ".MAX_GLOBAL_EVENT_STACK.". Possible infinite loop?");
		// If no arguments specified
		if($arguments === false) $arguments = array();
		// Do I have an extension? If so, go down one level...
		$ext = self::extension();
		if($ext) $return_value = $ext::fire_static($event, $arguments);
		// Check to see if stop propagation, if so, return the return_value
		if(zajModelExtender::$event_stop_propagation){
			zajLib::me()->event_stack--;
			return $return_value;
		}
		// Call my version
		if(method_exists($class_name, '__'.$event)) $return_value = call_user_func_array("$class_name::__".$event, $arguments);
		else $return_value = false;
		// Remove from stack
		zajLib::me()->event_stack--;
		// Return value
		return $return_value;
	}


	/**
	 * A static method used to set stop_propagation. This stops events from moving up my ancestors and forces the event to return the current value.
	 **/
	public static function stop_propagation(){
		zajModelExtender::$event_stop_propagation = true;
	}

}

/**
 * This class allows the model data translations to be fetched easily.
 *
 * @author Aron Budinszky <aron@mozajik.org>
 * @package Model
 * @subpackage DefaultModel
 */
class zajModelLocalizer {
	/**
	 * Create a new localizer object.
	 **/
	public function __construct($parent, $locale = false){
		if($locale != false) $this->locale = $locale;
		else $this->locale = zajLib::me()->lang->get();
		$this->parent = $parent;
	}

	/**
	 * Return data using the __get() method.
	 **/
	public function __get($name){
		return new zajModelLocalizerItem($this->parent, $name, $this->locale);
	}
}

/**
 * Helper class for a specific localization item. You can 'print' it (__toString) to get the translation
 * @todo Caching needs to be added to these!
 **/
class zajModelLocalizerItem {

	/** Make all variables private **/
	private $parent;
	private $fieldname;
	private $locale;

	/**
	 * Create a new localizer item.
	 * @param zajModel $parent The parent object. This is not just the id, it's the object!
	 * @param string $fieldname The field name of the parent object.
	 * @param string $locale The locale of the translation.
	 **/
	public function __construct($parent, $fieldname, $locale){
		$this->parent = $parent;
		$this->fieldname = $fieldname;
		$this->locale = $locale;
	}

	/**
	 * Returns the translation for the object's set locale.
	 **/
	public function get(){
		return $this->get_by_locale($this->locale);
	}

	/**
	 * Returns the translation for the given locale. It can be set to another locale if desired. If nothing set, the global default value will be returned (not a translation).
	 **/
	public function get_by_locale($locale = false){
		// Locale is not set or is default, so return the default value
		if(empty($locale) || $locale == zajLib::me()->lang->get_default_locale()) return $this->parent->data->{$this->fieldname};
		// A translation is requested, so let's retrieve it
		$tobj = Translation::fetch_by_properties($this->parent->class_name, $this->parent->id, $this->fieldname, $locale);
		if($tobj !== false) $field_value = $tobj->value;
		else $field_value = "";
		// check if translation filter is to be used
		// TODO: ADD THIS!
		// if not, filter through the usual get
		if($this->parent->model->{$this->fieldname}->use_get){
			// load my field object
			$field_object = zajField::create($this->fieldname, $this->parent->model->{$this->fieldname});
			// if no value, set to null (avoids notices)
			if(empty($field_value)) $field_value = null;
			// process get
			return $field_object->get($field_value, $this->parent);
		}
		// otherwise, just return the unprocessed value
		return $field_value;
	}

	/**
	 * Invoked as an object so must return properties.
	 **/
	public function __get($name){
		// Get the property of the value
		return $this->get()->$name;
	}

	/**
	 * Simply printing this object will result in the translation being printed.
	 **/
	public function __toString(){
		// Get the value
		return $this->get();
	}
}