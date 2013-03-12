<?php
/**
 * Manipulate the database to match the defined models.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Model
 * @subpackage DatabaseApi
 * @todo Renaming autoincrement fields is not supported and will run into error! This is because autoincrement key is created in a strange way.
 **/

define("CURRENT_TIMESTAMP", "CURRENT_TIMESTAMP");
define("AUTO_INCREMENT", "AUTO_INCREMENT");

class zajlib_model extends zajLibExtension {

	/**
	 * @var array
	 * An array of data types that do not have a character set.
	 **/
	private $numeric_types = array('INT','FLOAT','YEAR','SMALLINT','MEDIUMINT', 'DATE', 'DATETIME', 'TIMESTAMP');

	/**
	 * @var array
	 * An array of mysql constants available for use.
	 **/
	private $available_constants = array(CURRENT_TIMESTAMP, AUTO_INCREMENT);

	/**
	 * @var zajlib_db
	 * My own private db session.
	 **/
	private $db;

	/**
	 * @var integer
	 * Number of changes performed during this update.
	 **/
	public $num_of_changes = 0;

	/**
	 * @var integer
	 * Number of queries performed during this update.
	 **/
	public $num_of_queries = 0;

	/**
	 * @var integer
	 * Number of queries waiting for manual execution.
	 **/
	public $num_of_todo = 0;
	
	/**
	 * @var string
	 * A string of queries waiting for manual execution.
	 **/
	public $sql_todo = '';

	/**
	 * @var string
	 * The log of what has happened.
	 **/
	public $log = '';
	
	/**
	 * @var array
	 * An associated array of models already loaded with the name of the model as its key.
	 **/
	private $loaded_models = array();

	/**
	 * Creates a new model update session.
	 **/
	public function __construct(&$zajlib, $system_library) {
		parent::__construct($zajlib, $system_library);
		// create my database session
			$this->db = $this->zajlib->db->create_session('update');
	}

	/**
	 * Run the update now
	 **/
	public function update(){
		// start log
			$this->log .= '<strong>Starting update...</strong>';
			$this->log .= '<ul><li>Examining tables...</li><ul>';
		// get all my tables
			$tables = $this->get_tables();
			$this->log('Found '.count($tables).' existing tables in database.');
		// get all my models
			$models = $this->get_models();
			$this->log('Found '.count($models).' model definitions.');
		// let's run through model definitions and collect all the tables we need
				$model_tables = $this->models_to_tables($models);
			///////////////////////////////////////////////////////////////////////////////////////////////
			// PROCESS TABLES
			///////////////////////////////////////////////////////////////////////////////////////////////

				// let's check for tables that dont exist!
					$existing_tables = array_keys($tables);
					$existing_models = array_keys($model_tables);			
					$old_tables = array_diff($existing_tables, $existing_models);
					$new_tables = array_diff($existing_models, $existing_tables);
				// let's find old tables
					foreach($old_tables as $old_table){
						$this->remove_table($old_table);
					}
				// let's find new tables
					if(count($new_tables) > 0){
						foreach($new_tables as $new_table){
							$this->add_table($new_table);
						}
						//return $this->update();	// recursively reload everything
					}
					
			///////////////////////////////////////////////////////////////////////////////////////////////
			// PROCESS COLUMNS
			///////////////////////////////////////////////////////////////////////////////////////////////
				$this->log .= '</ul><li>Examining columns...</li><ul>';
				$cols_changed = false;

			foreach($model_tables as $model_name=>$model_array){
				$this->log("Checking table $model_name.");
				// init a fresh missing_fields
					$all_fields = array();
					$missing_fields = array();
					$unnecessary_fields = array();
					$different_fields = array();
					$different_indexes = array();
					$rename_fields = array();
				
				///////////////////////////////////////////////////////////////////////////////////////////////
				// Look for: missing fields, different fields, different indexes
				///////////////////////////////////////////////////////////////////////////////////////////////
				
				// check each column within this model
					foreach($model_array as $field_name=>$field_data){
						// this is a field, so add to all fields
							$all_fields[$field_name] = $field_data;
						// is this a missing field?
							if(empty($tables[$model_name][$field_name])) $missing_fields[$field_name] = $field_data;
							else{
								// get difference
									$my_difference = array_diff_assoc($field_data, $tables[$model_name][$field_name]);
								// options available?
									$my_option_difference = array_diff_assoc($field_data['option'], $tables[$model_name][$field_name]['option']);

								// if my difference exists
									if(count($my_difference) > 0 || count($my_option_difference)){
										/*print_r($field_data);
										print_r($tables[$model_name][$field_name]);
										exit;*/
										// if only index is an issue
											if(count($my_difference) == 1 && !empty($my_difference['key'])){
												$different_indexes[$field_name] = $field_data;
												unset($my_difference['key']);
											}
											
										// add to different_fields (if other than key also different)
											if(count($my_difference) > 0 || count($my_option_difference) > 0){
												$different_fields[$field_name] = $field_data;
											}
									}
							}
					}

				///////////////////////////////////////////////////////////////////////////////////////////////
				// Look for: extra unnecessary fields
				///////////////////////////////////////////////////////////////////////////////////////////////
					if(is_array($tables[$model_name])) $unnecessary_fields = array_diff_assoc($tables[$model_name], $all_fields);
					else $unnecessary_fields = array();

				///////////////////////////////////////////////////////////////////////////////////////////////
				// Look for: rename fields
				///////////////////////////////////////////////////////////////////////////////////////////////
					// any field that is missing (to be added) AND unnecessary (to be removed) is probably rename
					// TODO: likely this can be done faster...but this is only an update script, so not a priority!
					foreach($unnecessary_fields as $ufield_name=>$ufield_data){
						foreach($missing_fields as $mfield_name=>$mfield_data){
							// is the data the same but the name different?
								if(array_slice($ufield_data, 1) == array_slice($mfield_data, 1) && $ufield_name != $mfield_name){
									// add to rename field (with old name as key)
										$rename_fields[$ufield_name] = $mfield_data;
									// remove from missing field
										unset($missing_fields[$mfield_name]);
									// remove from unnecessary field
										unset($unnecessary_fields[$ufield_name]);
								}
						}
					}
					
					
				///////////////////////////////////////////////////////////////////////////////////////////////
				// Execute: remove
				///////////////////////////////////////////////////////////////////////////////////////////////
					foreach($unnecessary_fields as $field_database){
						$this->remove_column($model_name, $field_database['field']);
						$cols_changed = true;
					}
									
				///////////////////////////////////////////////////////////////////////////////////////////////
				// Execute: add
				///////////////////////////////////////////////////////////////////////////////////////////////
					foreach($missing_fields as $field_database){
						$this->add_column($model_name, $field_database);
						$cols_changed = true;
					}

				///////////////////////////////////////////////////////////////////////////////////////////////
				// Execute: modify data types
				///////////////////////////////////////////////////////////////////////////////////////////////
					foreach($different_fields as $field_database){
						$this->edit_column($model_name, $field_database);
						$cols_changed = true;
					}
					
				///////////////////////////////////////////////////////////////////////////////////////////////
				// Execute: modify index types
				///////////////////////////////////////////////////////////////////////////////////////////////
					foreach($different_indexes as $field_database){
						// get my name, type
							$column_name = $field_database['field'];
							$index_type = $field_database['key'];
						// first remove this index
							$this->remove_index($model_name, $column_name, $index_type);
						// finally add the new index
							$this->add_index($model_name, $column_name, $index_type);
						$cols_changed = true;
					}
				///////////////////////////////////////////////////////////////////////////////////////////////
				// Execute: rename column
				///////////////////////////////////////////////////////////////////////////////////////////////
					foreach($rename_fields as $old_name=>$field_data){
						// send to rename
							$this->rename_column($model_name, $old_name, $field_data);
					}
			}
			if(!$cols_changed) $this->log("Columns are up-to-date. Nothing to change.");
			$this->log .= '</ul></ul><strong>Done!</strong>';
		// DONE. Display results...
			// are there any todos for sql
				if(!empty($this->sql_todo)){
					$this->log .= '<br/>Please manually run the queries below. (Since this will remove data it is not automatic.)<pre>'.$this->sql_todo.'</pre>';
				}
			
			// print log and exit
				return array($this->num_of_changes, $this->log);
	}

	/**
	 * Convert models to table definitions.
	 * @param $models An array of models database definitions.
	 * @return array An array of tables with definitions.
	 **/
	private function models_to_tables($models){
		// create field_tables array
			$field_tables = array();
			$not_in_db = 0;
		// run through each model def (which is an array of field objects)
			foreach($models as $model_name=>$model){
				// by going through each field we will create an array which is equivalent to $tables in structure
				$model_tables[$model_name] = array();
				foreach($model as $field_name=>$field_object){
					// save this field if it is meant to be in database
						if($field_object::in_database){
							$my_db = array();
							// run through all my fields
							foreach($field_object->database() as $field_name=>$db){
								// merge my new fields into the current table
									$my_db[$field_name] = $db;
									$model_tables[$model_name] = array_merge($model_tables[$model_name], $my_db);
							}
						}
					// do i have a field table?
						$my_table = $field_object->table();
						if($my_table !== false){
							// add my_table to models
								$field_tables = array_merge($field_tables, $my_table);
						}
				}
				
			}
		// do i have any field tables?
			if(count($field_tables) > 0){
				// convert me to array of field objects
					$field_tables = $this->models_to_tables($field_tables);
				// send a log message
					$this->log('Found '.count($field_tables).' field tables.');
				// now merge with existing model_tables
					$model_tables = array_merge($model_tables, $field_tables);	
					$this->log('Total of '.count($model_tables).' tables.');
			}
		return $model_tables;
	}

	/**
	 * Create a new table with this name.
	 * @param string $name The name of the table created.
	 **/
	private function add_table($name){
		// execute adding of this table
			$this->db->query("CREATE TABLE IF NOT EXISTS `$name` (id VARCHAR(50)) CHARACTER SET utf8 ENGINE=MyIsam");
		// count query and action
			$this->log('Adding table '.$name, true);
			$this->num_of_changes++;
			$this->num_of_queries++;
		return true;
	}

	/**
	 * Add a column to a table.
	 * @param string $table The name of the table.
	 * @param array $field_data The field's database definition array.
	 **/
	private function add_column($table, $field_data){
		// Id columns can only be edited, not added, since they already exist when the table is created!
		if($field_data['field'] == 'id') return $this->edit_column($table, $field_data, 'edit');
		else return $this->edit_column($table, $field_data, 'add');
	}

	/**
	 * Edit a column in a table.
	 * @param string $table The name of the table.
	 * @param array $field_data The field's database definition array.
	 * @param string $mode The mode specifies whether it is added or editted. Values are 'add' or 'edit'.
	 * @param string $old_name The old name of the table, used during renames.
	 **/
	private function edit_column($table, $field_data, $mode = 'edit', $old_name = ''){
		// create options, make sure sql safe!
			$table = addslashes($table);
			$column = addslashes($field_data['field']);
			$type = addslashes(strtoupper($field_data['type']));
			$default = $field_data['default']; // sql-safe later!
			$extra = $field_data['extra']; // sql-safe later!
			$remove_index = false;
		// generate options & type declaration
			if(is_array($field_data['option']) && count($field_data['option']) > 0){
				foreach($field_data['option'] as $key=>$option) if(!is_numeric($option) || $type == 'ENUM') $field_data['option'][$key] = "'".addslashes($option)."'";
				$options = implode(',',$field_data['option']);
				$type_dec = "$type($options)";
			}
			else $type_dec = $type;
		// character set			
			if(!in_array($type, $this->numeric_types)) $char_set = "CHARACTER SET utf8";
		// default value: is it not a constant? then put in quotes!
			if($default === false) $default = '';
			else{
				if(!in_array($default, $this->available_constants)) $default = "'".addslashes($default)."'";
				$default = "DEFAULT $default";
			}
		// extra value (only constants!) so set to none if not available!
			if(!in_array($extra, $this->available_constants)) $extra = '';
		// add or edit?
			if($mode == 'edit'){
				$edit_mode = "CHANGE COLUMN `$column` `$column`";
				$this->log("Modified $table.$column to new type settings.", true);
				$remove_index = true;	// i also need to remove the index!
				$index_name = $column;
			}
			elseif($mode == 'rename'){
				$edit_mode = "CHANGE COLUMN `$old_name` `$column`";
				$this->log("Renaming $table.$old_name to $table.$column.", true);
				$remove_index = true;	// really it only needs to be renamed...but this is how its done now!
				$index_name = $old_name;
			}
			else{
				$edit_mode = "ADD COLUMN `$column`";
				$this->log("Creating $table.$column.", true);
			}
		// remove an index?
			if($remove_index) $this->remove_index($table, $index_name, $field_data['key']);
		// do i have a primary index? if so add at the same time
			$key = $primary = "";
			switch($field_data['key']){
				case 'PRI':		$primary = "PRIMARY KEY";
								$this->log("Adding primary key on $table.$column.", true);
								$this->num_of_changes++;
								break;
				case 'MUL':		$key = ", ADD INDEX (`$column`)";
								$this->log("Adding index on $table.$column.", true);
								$this->num_of_changes++;
								break;
				case 'UNI':		$key = ", ADD UNIQUE (`$column`)";
								$this->log("Adding unique key on $table.$column.", true);
								$this->num_of_changes++;
								break;
			}
		// execute adding of this table
			$this->db->query("ALTER TABLE `$table` $edit_mode $type_dec $char_set NOT NULL $default $extra $primary COMMENT '".$field_data['comment']."' $key");
		// count query and action
			$this->num_of_changes++;
			$this->num_of_queries++;
		return true;		
	}

	/**
	 * Rename a table column to a new name.
	 * @param string $table The name of the table.
	 * @param string $old_name The old name of the table, used during renames.
	 * @param array $new_field_data The field's database definition array.
	 **/
	private function rename_column($table, $old_name, $new_field_data){
		// send to rename
			return $this->edit_column($table, $new_field_data, 'rename', $old_name);
	}

	/**
	 * Remove column from the table. This is not actually performed, but added to the sql todo.
	 * @param string $table The name of the table.
	 * @param string $name The column to remove.
	 **/
	private function remove_column($table, $name){
		// add todo sql
			$this->sql_todo .= "ALTER TABLE `$table` DROP `$name`;\n";
			$this->log("Found extra column $table.$name. This can be deleted.", true);
		// count query and action
			$this->num_of_todo++;
		return true;	
	}

	/**
	 * Remove a table from the database. This is not actually performed, but added to the sql todo.
	 * @param string $name The table to remove.
	 **/
	private function remove_table($name){
		// add todo sql
			$this->sql_todo .= "DROP TABLE `$name`;\n";
			$this->log("Found extra table $name. This can be deleted.", true);
			$this->num_of_todo++;
		return true;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////
	// Indexes
	////////////////////////////////////////////////////////////////////////////////////////////////


	/**
	 * Add an index to the table.
	 * @param string $table The name of the table.
	 * @param string $name The column to index.
	 * @param string $index The type of index to add.
	 **/
	private function add_index($table, $column, $index){
		// add index
			switch($index){
				case 'MUL': $this->db->query("ALTER TABLE `$table` ADD INDEX (`$column`)");
							break;
				case 'PRI': $this->db->query("ALTER TABLE `$table` ADD PRIMARY KEY (`$column`)");
							break;
				case 'UNI': $this->db->query("ALTER TABLE `$table` ADD UNIQUE (`$column`)");
							break;
				default:	// no change needed
							return true;
			}
		// count query and action
			$this->log("Adding index of type $index on $table.$column.", true);
			$this->num_of_changes++;
			$this->num_of_queries++;
		return true;	
	}

	/**
	 * Remove a certain type of index from a column.
	 * @param string $table The name of the table.
	 * @param string $name The column to index.
	 * @param string $index The type of index to remove.
	 **/
	private function remove_index($table, $column, $index){
		// execute a removal of the index
			switch($index){
				case 'PRI':	$this->db->query("ALTER TABLE `$table` DROP PRIMARY KEY", true);
							break;
				default:	$this->db->query("DROP INDEX `$column` ON `$table`", true);
							break;
			}
		// count query and action			
			$this->log("Dropping index from $table.$column.", true);
			$this->num_of_changes++;
			$this->num_of_queries++;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////
	// !Private database read methods
	////////////////////////////////////////////////////////////////////////////////////////////////


	/**
	 * Get the tables as currently in the database.
	 **/
	private function get_tables(){
		$tables_array = array();
		// First get the tables
			$this->db->query('SHOW TABLES');
			$tables = $this->db->get_all();
			foreach($tables as $table){
				// get the first element
					$name = reset($table);
				// add to assoc array with columns (if not in the ignore list)
					if(!in_array($name, $this->zajlib->zajconf['mysql_ignore_tables'])){
						// Is Wordpress enabled? If so, also ignore any tables beginning with wp_
						if(!$this->zajlib->plugin->is_enabled('wordpress') || substr($name, 0, 3) != 'wp_'){
							$tables_array[$name] = $this->get_columns($name);
						}
					}
			}
		return $tables_array;
		
	}

	/**
	 * Get the table columns as currently in the database.
	 * @param string $table The name of the table to retrieve.
	 **/
	private function get_columns($table){
		$columns = array();
		// Get database name from settings
			$database_name = $this->zajlib->zajconf['mysql_db'];
		// Create a new database connection to information_schema
			$db = $this->zajlib->db->create_connection($this->zajlib->zajconf['mysql_server'], $this->zajlib->zajconf['mysql_user'], $this->zajlib->zajconf['mysql_password'], 'information_schema');
			// select scheme db then revert!
				$db->query("SELECT `COLUMN_NAME` as 'Field', `COLUMN_TYPE` as 'Type', `COLUMN_KEY` as 'Key', `COLUMN_DEFAULT` as 'Default', `EXTRA` as 'Extra', `COLUMN_COMMENT` as 'Comment' FROM `COLUMNS` WHERE `TABLE_SCHEMA`='$database_name' && `TABLE_NAME`='$table'");			
				foreach($db as $col){
					// process type
						$tdata = explode('(', $col->Type);
						$type = $tdata[0];
					// process options
						$options = array();
						foreach(explode(',', $tdata[1]) as $option) if($option != '') $options[] = trim($option, "')");
					// create my array		
						$columns[$col->Field] = array(
							'field'=>$col->Field,
							'type'=>$type,
							'option'=>$options,
							'key'=>$col->Key,
							'default'=>$col->Default,
							'extra'=>strtoupper($col->Extra),
							'comment'=>$col->Comment,
						);
				}
		return $columns;
	}

	/**
	 * Get the model definitions.
	 * @todo Add support for models in system plugins. Also add support for dynamically loaded plugins!
	 **/
	private function get_models(){
		$models = array();
		$this->loaded_models = array();
		// Load necessary libraries
			$this->zajlib->load->library("file");
		// Search for all my model files
			// Load my local files
				$myfiles = $this->zajlib->file->get_files_in_dir($this->zajlib->basepath.'/app/model/');
			// Load my plugin files
				foreach($this->zajlib->zajconf['plugin_apps'] as $plugin_app) $myfiles = array_merge($myfiles, $this->zajlib->file->get_files_in_dir($this->zajlib->basepath.'/plugins/'.$plugin_app.'/model/'));
			// Load my system files
				$myfiles = array_merge($myfiles, $this->zajlib->file->get_files_in_dir($this->zajlib->basepath.'/system/app/model/'));


		// Get model for each file
			foreach($myfiles as $myfile){
				// Get model name
					$model_name = basename($myfile, '.model.php');
				// If class doesnt exist, holler!
					if(!class_exists($model_name)) $this->zajlib->error("Could not update model <strong>$model_name</strong>! The class was not found. (You probably misspelled the class name in $myfile!)");
				// Get model definition
					if(empty($this->loaded_models[$model_name]) && $model_name::$in_database) $models[$model_name] = $this->get_model($model_name);
			}
		// Skip extender models
			foreach(zajModel::$extensions as $parent => $child){
				unset($models[$child]);
				$this->log("Skipping model $child because it extends $parent.");
			}
		return $models;
	}

	/**
	 * Get a specific model's definition.
	 * @param string $model_name The name of the model to get.
	 **/
	private function get_model($model_name){
		$model = array();
		// Load the model
			$model_def = $model_name::__model();				
		// Load the field definition for each
			foreach($model_def as $field=>$array){
				$model[$field] = $model_name::__field($field);
			}
		$this->loaded_models[$model_name] = true;
		return $model;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////
	// !Private logging methods
	////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Add a log message
	 * @param string $message The contents of the message.
	 * @param boolean $is_change If set to true, the message will be displayed as a change.
	 **/
	private function log($message, $is_change = false){
		if($is_change) $this->log .= "<li><font color='red'>$message</font></li>";
		else $this->log .= "<li>$message</li>";
	}
}




?>