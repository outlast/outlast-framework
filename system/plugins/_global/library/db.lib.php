<?php
/**
 * Handle connection and queries to the database.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Model
 * @subpackage DatabaseApi
 **/

/**
 * Use this constant to set a MAX() value for a database field.
 **/
define('MYSQL_MAX', 'MAX');
/**
 * Use this constant to set a MAX()+1 value for a database field.
 **/
define('MYSQL_MAX_PLUS', 'MAX+1');
/**
 * Use this constant to set an AVG() value for a database field.
 **/
define('MYSQL_AVG', 'AVG');

class zajlib_db extends zajLibExtension implements Countable, Iterator {
	// instance variables
		/**
		 * The resouce of the default connection.
		 * @var resource
		 **/
		private $default_connection;
		
		/**
		 * An array of {@link zajlib_db_session} objects used to manage different session without using different connections.
		 * @var array
		 **/
		private $sessions = array();
		/**
		 * The current session object
		 * @var zajlib_db_session
		 **/
		private $current_session='';
		/**
		 * The total number of queries run for all sessions together.
		 * @var integer
		 **/
		private $num_of_queries=0;
		/**
		 * The total microtime of query execution time for all sessions together.
		 * @var float
		 **/
		private $total_time=0;
		/**
		 * The last executed query.
		 * @var string
		 **/
		private $last_query='';

	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// !Init methods
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * Create a new instance of the db library object
		 * @param zajLib $zajlib A reference to the global zajlib object.
		 * @param string $system_library The name of the library (db in this case always)
		 * @return zajlib_db
		 **/
		public function __construct(&$zajlib, $system_library){
			// check if zajlib properly defined
				if(!is_object($zajlib)) exit('System bug. Tried to create a db connection without zajlib.');
			// check if it already exists!
				if(!empty($zajlib->db) && is_object($zajlib->db) && is_a($zajlib->db, 'zajlib_db')) return $zajlib->error('Only one db instance allowed, so unable to create new db instance. Please use the session method if you need a new db session.');
			// send to parent
				parent::__construct($zajlib, $system_library);
			// create my default session
				$this->create_session('default');
		}	

		/**
		 * Connects to the database using the specified parameters.
		 * @param string $server The hostname or ip of the server.
		 * @param string $user The mysql user name.
		 * @param string $pass The mysql password.
		 * @param string $db The name of the database to use for this connection
		 * @param boolean $fatal_error If set to true (the default) a failed connection will result in a fatal error.
		 * @return boolean Returns true if successful, false (or fatal error) otherwise.
		 **/
		public function connect($server="", $user="", $pass="", $db="", $fatal_error = true){
			// connect to server
				$this->default_connection = @mysql_connect($server, $user, $pass, true);
				if($this->default_connection === false){
					if($fatal_error) return $this->zajlib->error("Unable to connect to sql server. Disable sql or correct the server/user/pass!");
					else return false;
				}
				$this->current_session->conn = $this->default_connection;
			// select db
				$result = mysql_select_db($db, $this->current_session->conn);
				if($result === false){
					if($fatal_error) return $this->zajlib->error("Unable to select db. Incorrect db given? Or no access for user $user?");
					else return false;
				}
			return true;
		}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// !Session management - sessions allow the user to not have to worry about query resources
	//							during simultaneously running queries.
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * Creates a new database session which allows managing several queries without several connections.
		 * @param bool|string $id The id does not need to be specified, but you can choose any string if you wish.
		 * @param bool|resource $connection The connection resource to mysql database.
		 * @return zajlib_db_session A new session will be returned.
		 */
		public function create_session($id = false, $connection = false){
			// create random id if not specified
				if($id) $sessionid = $id;
				else $sessionid = uniqid('');
			// create a new session
				$this->session[$sessionid] = new zajlib_db_session($this->zajlib, $sessionid, $connection);
				return $this->session[$sessionid];
		}

		/**
		 * Creates a new database connection. This is only needed if you need to connect to a separate database. If you need seperate queries, use sessions.
		 * @param string $server The hostname or ip of the server.
		 * @param string $user The mysql user name.
		 * @param string $pass The mysql password.
		 * @param string $db The name of the database to use for this connection
		 * @param bool|string $id The id does not need to be specified, but you can choose any string if you wish.
		 * @return zajlib_db_session A new session will be returned.
		 */
		public function create_connection($server="", $user="", $pass="", $db="", $id = false){
			// connect to server
				$conn = mysql_connect($server, $user, $pass, true) or $this->zajlib->error("Unable to connect to sql server. Disable sql or correct the server/user/pass!");
			// select db
				mysql_select_db($db, $conn) or $this->zajlib->error("Unable to select db. Incorrect db given? Or no access for user $user?");
			// Now create a session and return
				return $this->create_session($id, $conn);
		}

		/**
		 * Sets the session to whatever is specified by the parameter.
		 * @param string $id The id of the session you wish to use. Default will be chosen if none specified.
		 * @return zajlib_db_session The current session will be returned.
		 **/
		public function set_session($id = ''){
			// if id is empty, then default session!
				if(empty($id)) return $this->set_session('default');
			// check if session exists
				if(empty($this->session[$id]) || !is_object($this->session[$id])) $this->zajlib->error("Could not select this session $id. Does not exist.");
			// check if i have a valid connection
				if(!$this->session[$id]->conn) $this->session[$id]->conn = $this->default_connection;
			// now set current session
				return $this->current_session = $this->session[$id];
		}
		/**
		 * Removes the session and it's result set from memory.
		 * @param string $id The id of the session you wish to remove.
		 **/
		public function delete_session($id){
			// remove the result set
				mysql_free_result($this->sessions[$id]->query);
			// remove from array
				unset($this->sessions[$id]);
			return true;
		}


	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// !Debug stuff - this does not relate to the session, so it is not private! (does not have to be filtered through
	//				the __call method...
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		/**
		 * Returns the number of queries performed in total.
		 * @return integer The number of queries.
		 **/
		public function get_num_of_queries(){
			return $this->num_of_queries;
		}

		/**
		 * Returns the total microtime used for executing queries.
		 * @return integer The time in microseconds.
		 **/
		public function get_total_time(){
			return $this->total_time;
		}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// !Data modification
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * Add a row to a table using an associative array.
		 * @param string $table The table to add to.
		 * @param array An associative array where the keys are the field names and the values are the field values.
		 **/
		private function add($table, $array){
			// Check for errors
				if(count($array) == 0)return false;
			// Create sql query
				// add slashes to avoid problems with quotes
					$function = false;
					foreach($array as $key => $value){
						// special functions
							switch($value){
								// this is needed since false is otherwise sent to the MAX (why?)
								case false:			$value = "'".addslashes($value)."'";
													break;
								case MYSQL_MAX:		$value = "MAX($key)";
													$function = true;
													break;
								case MYSQL_MAX_PLUS:$value = "MAX($key) +1";
													$function = true;
													break;
								case MYSQL_AVG:		$value = "AVG($key)";
													$function = true;
													break;
								default:			$value = "'".addslashes($value)."'";
													break;
							}
						$field[] = "$value as `$key`";
					}
				// check if no functions (TODO: fix this in a future release to allow!)
					if($function) $fromtable = "FROM `$table` LIMIT 1";
					else $fromtable = "";
				// join the fieldnames
					$keys = array_keys($array);
					$fieldnames = "`".join("`,`", $keys)."`";
				// join the values
					$values = join(",", $field);	
			// execute sql (use SELECT to enable functions)
				$sql = "INSERT INTO `$table` ($fieldnames) SELECT $values $fromtable";
				return $this->query($sql);
		}

		/**
		 * Edit a row in a table using an associative array.
		 * @param string $table The table to edit in.
		 * @param string|array $column The name of the column to use as the condition in the WHERE clause. If an array is specified, multiple values are used in the WHERE clause (key/value).
		 * @param string $condition When $column is just a single value, condition specifies the value to which the field must be equal to.
		 * @param array An associative array where the keys are the field names and the values are the field values. The fields will be modified according to this array.
		 * @param string $conditionType Can have a value of AND or OR. This only matters if $column is an array, and there are multiple key/value pairs to use in the WHERE clause.
		 **/
		private function edit($table, $column, $condition, $array, $conditionType = "AND"){
			// Generate data to add
				foreach($array as $key => $value){
					// special functions
						switch($value){
							// this is needed since false is otherwise sent to the MAX (why?)
							case false:			$value = "'".addslashes($value)."'";
												break;
							case MYSQL_MAX:		//print $value.'*'.MYSQL_MAX.'<br/>';
												$value = "MAX($key)";
												
												break;
							case MYSQL_MAX_PLUS:$value = "MAX($key) +1";
												break;
							case MYSQL_AVG:		$value = "AVG($key)";
												break;
							default:			$value = "'".addslashes($value)."'";
												break;
						}
					// generate field
						$field[] = "`$key` = $value";
				}
				if(is_array($field)) $newfielddata = join(", ", $field);
				else return $this->send_error(false, "mysql edit did not execute because parameter array is empty. nothing to change!");
			
			// Multiple conditions
				if(is_array($column)){
					foreach($column as $key => $value){
						$value = addslashes($value);
						$wherefield[] = "`$key` LIKE '$value'";
					}
					$whereStr = join(" $conditionType ", $wherefield);
				}
			// Single condition
				else{
					$condition = addslashes($condition);
					$whereStr = "`$column` LIKE '$condition'";
				}
			
			// Now execute
				$sql = "UPDATE LOW_PRIORITY `$table` SET $newfielddata WHERE $whereStr";
				return $this->query($sql);
		}
		
		/**
		 * Delete a row from a table.
		 * @param string $table The table to edit in.
		 * @param string $column The name of the column to use as the condition in the WHERE clause.
		 * @param string $condition Condition specifies the value to which the field must be equal to.
		 * @param string $limit The maximum number of items to delete. By default, this is one to safeguard against accidental deletes.
		 **/
		private function delete($table, $column, $condition, $limit = 1){
			// Generate sql
				$condition = addslashes($condition);
				$sql = "DELETE FROM `$table` WHERE `$column` LIKE '$condition' LIMIT $limit";
				return $this->query($sql);
		}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// !Data access
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * Get a row from the result set. Use of this method is depricated. Use the iterator methods instead.
		 * @param integer $num The number of rows to retrieve. This is depricated, and only the default value should be used.
		 * @param integer $startat The number of rows to skip. This is depricated, and only the default value should be used.
		 * @param string $column_as_key Use a specific column as key. This is depricated, and only the default value should be used.
		 **/
		private function get($num = 1, $startat = 0, $column_as_key = '', $one_dimensional_by_key = ''){
			// set as array
				$result_set = array();
				$this->current_session->selected_row = false;
			// count the num of rows
				if(!$this->current_session->query || !$this->current_session->conn) return $this->send_error(true);
				$num_rows = $this->current_session->affected;			
			// get all remaining by setting num to the number remaining
				if($num == -1) $num = $num_rows - $this->current_session->row_pointer - $startat;
			// get a fixed number or the num remaining, whichever is less
				for($i = 0; ($i < $num && $i < $num_rows); $i++){
					 // fetch the row
						 $my_row = mysql_fetch_assoc($this->current_session->query);
					 // if column as key
						 if($column_as_key) $key = $my_row[$column_as_key];
						 else $key = $i;
					 // if $one_dimensional_by_key (this means that only one column will be returned; ex: id's only)
					 	if($one_dimensional_by_key) $my_row = $my_row[$one_dimensional_by_key];
					// now set
						$result_set[$key] = $my_row;
					// set current row
						$this->current_session->selected_row = (object) $my_row;
					// increment the row pointer variable
						$this->current_session->row_pointer++;
				}				
			return $result_set;
		}

		/**
		 * Get a single row from the result set. This is depricated. Use iterator methods instead.
		 * @param integer $startat The number of rows to skip.
		 * @return array An associative array is returned.
		 **/
		private function get_one($startat = 0){
			$result_set = $this->get(1, $startat);
			if(!empty($result_set[0])) return $result_set[0];
			else return false;
		}

		/**
		 * Get all of the remaining rows. This is depricated. Use iterator methods instead.
		 * @return array A multi-dimensional associative array is returned.
		 **/
		private function get_all($startat = 0, $column_as_key = '', $one_dimensional_by_key = ''){
			return $this->get(-1, $startat,$column_as_key,$one_dimensional_by_key);
		}

		/**
		 * Get all of the remaining rows as objects. This is depricated. Use iterator methods instead.
		 * @return array A multi-dimensional associative array is returned.
		 **/
		private function get_all_objects($class_name, $startat = 0, $id_column = 'id', $include_deleted = false){
			$my_results = $this->get(-1, $startat);
			$my_objects = array();
			foreach($my_results as $result){
				// only if not deleted (TODO: remove this)
				$cobj = new $class_name($result[$id_column]);
				if($include_deleted || $cobj->data->status != 'deleted') $my_objects[] = $cobj;
			}
			return $my_objects;
		}
		
		/**
		 * This is an alias of rewind. This is depricated.
		 **/
		private function reset(){
			$this->rewind();
		}


	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// !Implementations
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * Count method returns the total number of rows returned by the last query. Implements Countable.
		 * @return integer The total number of rows from last query.
		 **/
		public function count(){
			return $this->get_total_rows();
		}

		/**
		 * Returns the current object in the iteration. Implements Iterator.
		 * @return object Returns the selected row as an object.
		 **/
		public function current(){
			// if current row not selected
				if(empty($this->current_session->selected_row)) $this->rewind();
			// now return the current
				return $this->current_session->selected_row;
		}
		/**
		 * Returns the current key in the iteration.
		 * @return integer Returns the row pointer of the current row.
		 **/
		public function key(){
			// if current row not selected
				if(empty($this->current_session->selected_row)) $this->rewind();
			// now return the current row pointer
				return $this->current_session->row_pointer;
		}
		
		/**
		 * Returns the next object in the iteration.
		 * @return object Returns the selected row as an object.
		 **/
		public function next(){
			// get the next row
				$this->get_one();
			// return the current row
				return $this->current_session->selected_row;
		}
		
		/**
		 * Rewinds the iterator.
		 * @return boolean Always returns true
		 **/
		public function rewind(){
			// if not at 0, then rewind
				if($this->current_session->row_pointer > 0){
					// reset row pointer
						$this->current_session->row_pointer = 0;
						mysql_data_seek($this->current_session->query, 0);
				}
			// return the next one
				return $this->next();
		}
		
		/**
		 * Returns true if the current object of the iterator is a valid object.
		 * @return boolean Returns true or false depending on whether the currently select row is valid.
		 **/
		public function valid(){
			if($this->current_session->affected > 0 && $this->current_session->row_pointer <= $this->current_session->affected) return true;
			else return false;
		}


	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// !Helpers
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		/**
		 * Return the maximum value of a given column. This is depricated and will be removed.
		 **/
		private function max($table, $col, $wherestr = ""){
			// wherestr
				if($wherestr) $wherestr = "WHERE $wherestr";
				$sql = "SELECT MAX($col) as m FROM `$table` $wherestr";
			// query and return result
				$this->query($sql);
				$row = $this->get_one();
			return $row['m'];
		}
		/**
		 * Returns a multi-dimensional array of selected rows based on the SQL query passed. This is depricated and will be removed.
		 **/
		private function select($sql, $onerow = false, $column_as_key = ''){
			$this->query($sql);
			if($onerow) return $this->get_one(0);
			else return $this->get_all(0, $column_as_key);
		}
		/**
		 * Return the count of a given column. This is depricated and will be removed.
		 **/		
		private function count_only($table, $wherestr = ""){
			// static, so cannot reference $this
			if($wherestr) $wherestr = "WHERE $wherestr";
			$sql = "SELECT COUNT(*) as c FROM `$table` $wherestr";
			// query and return result
				$this->query($sql);
				$row = $this->get_one();
			return $row['c'];
		}
		

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// !Search
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		private function search($table, $query, $columns, $max_results = 5, $return_columns="", $similarity_search = true){
 			// TODO: implement $similarity_search = false
			
						
 			// generate where string
				// query should be escaped
					$condition = addslashes($query);
	 			// is $column an array of columns?
	 				$wherestr = "0";
	 				if(is_array($columns)) foreach($columns as $c) $wherestr .= " || $c SOUNDS LIKE '$condition' || $c LIKE '%$condition%'";
	 				else $wherestr .= " || $columns SOUNDS LIKE '$condition' || $columns LIKE '%$condition%'";
			// generate return part
				// if not an array of columns, then return evthing
					if(!is_array($return_columns)) $return = "$table.id";
					else $return = join(",",$return_columns);
 			// generate full query
 				$sql = "SELECT $return FROM `$table` WHERE $wherestr  ORDER BY $table.ordernum DESC LIMIT $max_results";
			// send the query
				return $this->query($sql);
		}


	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// !Query and error handling
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * Send a query to the database.
		 * @param string $sql The SQL query to execute.
		 * @param boolean $disable_error If set to true, no error will be returned or logged, though false will still be returned if the query failed.
		 * @return zajlib_db_session Returns the current query session.
		 **/
		private function query($sql, $disable_error = false){
			// create the connection if it doesnt already exist
				if(!$this->current_session->conn) $this->connect($this->zajlib->zajconf['mysql_server'], $this->zajlib->zajconf['mysql_user'], $this->zajlib->zajconf['mysql_password'], $this->zajlib->zajconf['mysql_db']);
			// add total tracking if not already there
				$sql = trim($sql);
				if(strpos($sql, 'SELECT') !== false && strpos($sql, 'SQL_CALC_FOUND_ROWS') === false  && substr($sql, 0, 6) == 'SELECT') $sql ='SELECT SQL_CALC_FOUND_ROWS '.substr($sql, 7);
			// send query to server
				// TODO: change this to debug timer, which doesn't do it unless debug_mode is enabled!
				$before_query = microtime(true);
				$this->current_session->query = mysql_query($sql, $this->current_session->conn);
				if($this->current_session->query == false) $this->current_session->last_error = mysql_error($this->current_session->conn);
			// count affected
				if(is_resource($this->current_session->query)) $this->current_session->affected = mysql_num_rows($this->current_session->query);
				else $this->current_session->affected = mysql_affected_rows($this->current_session->conn);
			// now count total
				$res = mysql_query('SELECT FOUND_ROWS() as total;', $this->current_session->conn);
				$data = mysql_fetch_assoc($res);
				$this->current_session->total = $data['total'];
			// end the timer	
				$time_it_took = microtime(true) - $before_query;
				$this->num_of_queries++;
				$this->total_time += round($time_it_took,5);
				$this->zajlib->time_of_queries += $time_it_took;
				$this->zajlib->query("$sql (".round($time_it_took*1000,5)." msec)");
				$this->last_query = $sql;
			// now save log data to session
				$this->current_session->num_of_queries++;
				$this->current_session->total_time += $time_it_took;
			// check if all is okay
				if($this->current_session->query == false && !$disable_error) return $this->send_error();
			// reset row pointer
				$this->current_session->row_pointer = 0;
			return $this->current_session;
		}
		
		/**
		 * Gets the total number of rows affected by the last query, taking into account the LIMIT clause.
		 * @return integer The total number of rows LIMITed.
		 **/
		private function get_num_rows(){
			if($this->current_session->query === false){
				$this->send_error();
				return 0;
			}
			return $this->current_session->affected;
		}

		/**
		 * Gets the total number of rows affected by the last query, regardless of the LIMIT clause.
		 * @return integer The total number of rows.
		 **/
		private function get_total_rows(){
			if($this->current_session->query === false){
				$this->send_error();
				return 0;
			}
			return $this->current_session->total;
		}
		
		/**
		 * Send an error to the user or to the log.
		 * @param boolean $display_warning Will display the warning even if debug mode is off. When debug mode is on, the warning is displayed regardless of this setting.
		 * @param string $error_text This is the error string.
		 **/
		private function send_error($display_warning = false, $error_text = ""){
			// set all the necessary variables
				if(!empty($error_text)) $this->current_session->last_error = $error_text;
			// display the error
				if($display_warning || $this->zajlib->debug_mode) $this->zajlib->error('SQL: '.$this->current_session->last_error.' / '.$this->last_query.' (Hints: Typo? Missing DB update?)');
			// reset error
				$this->current_session->last_error = '';
			// now return false
				return false;
		}

		/**
		 * Get the last error and return it.
		 * @return string $error_text This is the error string. An empty string is returned if no error.
		 **/
		public function get_error(){
			return $this->current_session->last_error;
		}

		/**
		 * Escape a string using the current connection.
		 * @param string $string_to_escape
		 * @return string $escaped_string
		 **/
		public function escape($string_to_escape){
			// create the connection if it doesnt already exist
				if(!$this->current_session->conn) $this->connect($this->zajlib->zajconf['mysql_server'], $this->zajlib->zajconf['mysql_user'], $this->zajlib->zajconf['mysql_password'], $this->zajlib->zajconf['mysql_db']);
			// now escape
				return mysql_real_escape_string($string_to_escape, $this->current_session->conn);
		}

		/**
		 * Verify a field name.
		 * @param string $field_name
		 * @return boolean Returns true if success, false if not.
		 **/
		public function verify_field($field_name){
			// use regexp to allow letters, numbers, and _
				return preg_match("/[A-z0-9_]+/", $field_name);
		}
		
		/**
		 * Magic method to handle session calls to the default session.
		 * @param string $name The name of the method to call.
		 * @param array $arguments An array of arguments to pass to the session.
		 **/
		public function __call($name, $arguments){
			// get current session
				$current_session = $this->current_session->id;
			// set to default session
				$this->set_session();
			// is the method available
				if(!method_exists($this, $name)) $this->zajlib->error("The method $name could not be found in the database API.");
			// now call function
				$value = call_user_func_array(array($this, $name), $arguments);
			// set back to current session
				$this->set_session($current_session);
			return $value;
		}		

		/**
		 * Magic method to handle session calls. This will set the current session and execute the method.
		 * @param string $name The name of the method to call.
		 * @param array $arguments An array of arguments to pass to the session.
		 * @param string $id The session id to use.
		 **/
		public function __call_session($name, $arguments, $sessionid){
			// set to default session
				$this->set_session($sessionid);
			// now call function
				return call_user_func_array(array($this, $name), $arguments);
		}
}




/**
 * This class helps manage database sessions. Database sessions are a way to handle simultaneous queries without using different connections.
 * @package Model
 * @subpackage DatabaseApi
 **/
class zajlib_db_session implements Countable, Iterator {
	// private instance variables 
		/**
		 * A reference to the global zajlib object.
		 * @var zajLib
		 **/
		private $zajlib;
	// public db session variables
		/**
		 * My session id.
		 * @var string
		 **/
		public $id;
		/**
		 * The last error message encountered during this session.
		 * @var string
		 **/
		public $last_error = '';
		/**
		 * The resource of the current connection. Usually this is the same as the global db object's connection.
		 * @var resource
		 **/
		public $conn = false;
		/**
		 * The resource of the current query.
		 * @var resource
		 **/
		public $query;
		/**
		 * Current position of the internal pointer.
		 * @var integer
		 **/
		public $row_pointer = 0;
		/**
		 * The currently selected row from the result set. It is an object, not an associative array. It is possible to select more than one row - regardless, selected_row will only be set to the single latest row.
		 * @var object
		 **/
		public $selected_row = false;
		/**
		 * The total number of rows in this query.
		 * @var integer
		 **/
		public $total = 0;
		/**
		 * The total number of affected rows in this query.
		 * @var integer
		 **/
		public $affected = 0;
	// log for this session
		/**
		 * The total number of queries in this session so far.
		 * @var integer
		 **/
		public $num_of_queries = 0;
		/**
		 * The microtime value of the query execution time in this session.
		 * @var float
		 **/
		public $total_time = 0;
	

	/**
	 * Creates a new session.
	 * @param zajLib $zajlib A pointer to the global zajlib object.
	 * @param string $id The id of this session.
	 * @param resource $connection The mysql connection resource.
	 **/
	public function __construct(&$zajlib, $id, $connection){
		// set my id
		$this->id = $id;
		// set my parent
		$this->zajlib =& $zajlib;
		// connection
		$this->conn = $connection;
	}
	
	/**
	 * Magic method to reroute methods to the {@link zajlib_db} class
	 **/
	public function __call($name, $arguments){
		if(!is_object($this->zajlib)){ print "Could not access db object!"; debug_print_backtrace(); exit; return false; } 
		return $this->zajlib->db->__call_session($name, $arguments, $this->id);
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// !Implementations - redirected to current session
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * Count method returns the total number of rows returned by the last query. Implements Countable.
		 * @return integer The total number of rows from last query.
		 **/
		public function count(){ return $this->zajlib->db->__call_session('count', array(), $this->id); }

		/**
		 * Returns the current object in the iteration. Implements Iterator.
		 * @return object Returns the selected row as an object.
		 **/
		public function current(){ return $this->zajlib->db->__call_session('current', array(), $this->id); }

		/**
		 * Returns the current key in the iteration.
		 * @return integer Returns the row pointer of the current row.
		 **/
		public function key(){ return $this->zajlib->db->__call_session('key', array(), $this->id); }
		
		/**
		 * Returns the next object in the iteration.
		 * @return object Returns the selected row as an object.
		 **/
		public function next(){ return $this->zajlib->db->__call_session('next', array(), $this->id); }
		
		/**
		 * Rewinds the iterator.
		 * @return boolean Always returns true
		 **/
		public function rewind(){ return $this->zajlib->db->__call_session('rewind', array(), $this->id); }

		/**
		 * Returns true if the current object of the iterator is a valid object.
		 * @return boolean Returns true or false depending on whether the currently select row is valid.
		 **/
		public function valid(){ return $this->zajlib->db->__call_session('valid', array(), $this->id); }

}



?>