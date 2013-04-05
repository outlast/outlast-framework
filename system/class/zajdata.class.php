<?php
/**
 * Model data access and loading.
 * 
 * The zajData class is a helper class which allows the end user to retrieve, set, and save the model field data. The class handles the various data
 *  types and loads additional helper classes (from plugins/fields/) if needed. It also helps out with cacheing.
 *
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Model
 * @subpackage Database
 */

/**
 * The basic fields that all zajModel objects have.
 * @property string $name The name of the object.
 * @property string $id The id of the object.
 * @property integer $ordernum The order number (autoincremented).
 * @property integer $time_create The time when the object was created.
 * @property integer $time_edit The time when the object was modified.
 **/
class zajData {
	// Instance variables
		/**
		 * A reference to the "parent" object.
		 * @var zajModel
		 **/
		private $zajobject;
		/**
		 * An array of my loaded data.
		 * @var array
		 **/
		private $data = array();
		/**
		 * The database connection session object.
		 * @var zajlib_db_session
		 **/
		private $db;
		/**
		 * The array of data which has been loaded. Each element is either true or false.
		 * @var array
		 **/
		private $loaded = array();
		/**
		 * The array of data which has been modified. Each element is either true or false.
		 * @var array
		 **/
		private $modified = array();
		/**
		 * This is set to true if the data has been loaded from the database.
		 * @var boolean
		 **/
		private $fetched = false;
		/**
		 * This is true if the {@link zajModel} object exists in the database.
		 * @var boolean
		 **/
		private $exists;
		/**
		 * If set to true, the modified data will be returned when requested.
		 * @var boolean
		 * @todo Need a nicer way of accessing this data!
		 **/
		public $__autosave = false;

		/**
		 * Create the zajData object. This should never be used manually, but instead should be accessed through the model via $model_object->data.
		 **/
		public function __construct(&$zajobject){
			// set my "parent" object
				$this->zajobject =& $zajobject;
			// create my db session
				$this->db = $this->zajobject->zajlib->db->create_session();		// create my own database session
			// get row data from db
				return $this->reload();
		}

		/**
		 * Reload the entire object data from the database.
		 **/
		public function reload(){
			// load from db
				$this->data = $this->db->select("SELECT * FROM `{$this->zajobject->table_name}` WHERE `{$this->zajobject->id_column}`='".addslashes($this->zajobject->id)."' LIMIT 1", true);
			// set exists to true (if any rows returned)
				$this->exists = $this->db->get_num_rows();
				if(!$this->exists) $this->data = array();
			// set to loaded from db
				$this->fetched = true;
			return $this->exists;
		}

		/**
		 * Return true if this exists in the database, false otherwise.
		 **/
		public function exists(){
			return $this->exists;
		}

		/**
		 * Save all fields to the database. If pre-processing is required, then call in the field's helper class.
		 **/
		public function save(){
			// if i dont exist, init...but if it fails, return
				if(!$this->exists && !$this->init()) return $this->zajobject->zajlib->warning('save failed! could not initialize object in database!');
			// if nothing modified, then return
				if(count($this->modified) <= 0) return true;
			// run through all modified variables
				$objupdate = array();
				foreach($this->modified as $name=>$value){
					// is preprocessing required for save?
						if($this->zajobject->model->{$name}->use_save || $this->zajobject->model->{$name}->virtual){
							// load my field object
								$field_object = zajField::create($name, $this->zajobject->model->$name);
							// process save
								list($dbupdate[$field_object->name], $objupdate[$field_object->name], $additional_updates) = $field_object->save($value, $this->zajobject);
							// any additional fields for db update?
								if(!empty($additional_updates) && is_array($additional_updates)){
									foreach($additional_updates as $k=>$v) $dbupdate[$k] = $v;
								}
							// if db update is prevented (by in_database setting or by explicit boolean false return)
								if($dbupdate[$field_object->name] === false || !$field_object::in_database) unset($dbupdate[$field_object->name]);
						}
						else{
							// simply set to value
								$dbupdate[$name] = $objupdate[$name] = $value;
						}						
					// if objupdate is not dbupdate, then this is not loaded
						// It is now the specific task of use_save enabled fields to explicitly unload() if needed...
				}
			// update in database
				$objupdate['time_edit'] = $dbupdate['time_edit'] = time(); // set edit time
				$this->db->edit($this->zajobject->table_name, $this->zajobject->id_column, $this->zajobject->id, $dbupdate);
			// merge $data with $objudpate and reset
				$this->reset();
			return true;
		}

		/**
		 * Reset the modified array to empty.
		 **/
		public function reset(){
			// reset modified array
				$this->modified = array();
			// reset loaded array
				$this->loaded = array();
			// reset data
				$this->data = array();
			// reset fetched status
				$this->fetched = false;
			return true;
		}

		/**
		 * Delete this row from the database.
		 * @param boolean $permanent Set to true if the delete should be permanent. Otherwise, by default, it will set the status to deleted.
		 **/
		public function delete($permanent = false){
			if($permanent) $this->db->delete($this->zajobject->table_name, $this->zajobject->id_column, $this->zajobject->id);
			else{
				$this->__set("status","deleted");
				$this->save();
			}			
		}		
		
		/**
		 * Initialize fields prior to inserting into the database.
		 **/
		private function init(){
			// init default fields
				if(empty($this->data['time_create'])) $this->data['time_create'] = time();
				// TODO: set ordernum to autoincrement
				$this->data['ordernum'] = MYSQL_MAX_PLUS; //$this->db->max($this->zajobject->table_name,"ordernum")+1;
				$this->data['id'] = $this->zajobject->id;
			// save to db
				$result = $this->db->add($this->zajobject->table_name, $this->data);
				if(!$result) $this->zajobject->zajlib->warning("SQL SAVE ERROR: ".$this->db->get_error()." <a href='{$this->zajobject->zajlib->baseurl}update/database/'>Update needed?</a>");
			// set that i exist
				$this->exists = true;
			return $result;
		}
	
		/**
		 * Return all or a specific field's unprocessed data.
		 * @param string $field_name If field_name is specified, only that field name will be returned.
		 * @return mixed Returns the data or an array of data.
		 **/
		public function get_unprocessed($field_name=''){
			if($field_name) return $this->data[$field_name];
			else return $this->data;
		}

		/**
		 * Return JSON-encoded unprocessed data.
		 * @todo Add a parameter to return process JSON data.
		 **/
		public function json(){
			return json_encode($this->data);
		}
	
		/**
		 * Magic method for retrieving specific fields from the data class. Since most of the time we use the data class to retrieve field data this is what is called
		 *  most often via $model_object->data->field_name. If pre-processing is required, the data will be processed first and then sent to the end user.
		 * @todo Make this more effecient via cacheing, especially if pre-processing is required.
		 **/
		public function __get($name){
			// check for error
				if(!$this->zajobject->model->$name) return $this->zajobject->zajlib->warning("Cannot get value of '$name'. field '$name' does not exist in model '{$this->zajobject->class_name}'!");
			// do i need to reload the data?
				if(!$this->fetched) $exists = $this->reload();
							
			// is preprocessing required for get?
				if(empty($this->loaded[$name]) && ($this->zajobject->model->{$name}->use_get|| $this->zajobject->model->{$name}->virtual)){
					// load my field object
						$field_object = zajField::create($name, $this->zajobject->model->$name);
					// if no value, set to null (avoids notices)
						if(empty($this->data[$field_object->name])) $this->data[$field_object->name] = null;
					// process get
						$this->data[$name] = $field_object->get($this->data[$field_object->name], $this->zajobject);
				}
			// It has been loaded!
				$this->loaded[$name] = true;
			// Turn off autosave
				$autosavemode = $this->__autosave;
				$this->__autosave = false;
			// if modified has been requested...
				if($autosavemode && isset($this->modified[$name])) return $this->modified[$name];
			// else return the data
				else return $this->data[$name];
		}

		/**
		 * Magic method used for modifying the data in specific fields. This is most often accessed via {@link zajModel->set()} method.
		 * @param string $name The name of the field to set.
		 * @param mixed $value Any kind of variable that the field accepts.
		 * @return bool Returns true if successul, false otherwise.
		 **/		
		public function __set($name, $value){
			// check for error
				if(!$this->zajobject->model->$name) return $this->zajobject->zajlib->warning("cannot set value of '$name'. field '$name' does not exist in model '{$this->zajobject->class_name}'!");
			// set the data
				$this->modified[$name] = $value;
			return true;
		}
		
		/**
		 * Unload a specific field.
		 * @todo Do we still need this?
		 **/		
		public function unload($name){
			// unset the data
				$this->loaded[$name] = false;
		}

		/**
		 * Get the array of modified fields (or a specific field).
		 * @param string|bool $specific_field If not set, this method will return an array of data fields. Otherwise, it will return the specific field in question.
		 * @return array The array of modified fields or a specific modified field.
		 **/		
		public function get_modified($specific_field = false){
			// return full array if requested
				if($specific_field === false) return $this->modified;
			// return specific key
				else return $this->modified[$specific_field];
		}

		/**
		 * Returns true or false depending on whether the specified field has been modified. Modification means that set() has been used, but it has not yet been saved.
		 * @param string $specific_field This is a string representing the name of the field in question.
		 * @return bool
		 */
		public function is_modified($specific_field){
			return array_key_exists($specific_field, $this->modified);
		}

		/**
		 * Magic method used to warn the user if he/she tried to use zajData as a string.
		 * @ignore
		 **/		
		public function __toString(){
			$this->zajobject->zajlib->warning('Tried using zajData object as a String!');
			return '';
		}
		
		/**
		 * Magic method used to display debug information about the data.
		 **/		
		public function __toDebug(){
			/**foreach($this->zajobject->model as $name=>$type){
				$value = $this->data[$name];
				// is this a special field?
					if($type == "manytomany" || $type == "onetomany" || $type == "manytoone" || $type == "photos"){
						// and it is not yet loaded
							if(!is_array($value) && !is_object($value)) $value = "[$type field not loaded]";
							else $value = str_replace("\n","\n\t\t\t\t",print_r($value, true));
					}
				// generate string				
					$str .= "\t\t\t[$name] => $value\n";
			}
			foreach($this->modified as $name=>$value) $mstr .= "\t\t\t[$name] => $value\n";
			foreach($this->loaded as $name=>$value) $lstr .= "\t\t\t[$name] => $value\n";
			return "\n\t\tStored\n\t\t(\n".$str."\t\t)\n\t\tModified\n\t\t(\n".$mstr."\n\t\t)\n\t\tLoaded\n\t\t(\n".$lstr."\n\t\t)";**/
		}
}