<?php
/**
 * The fetcher class.
 * 
 * The fetcher class is the API to fetch data from the database. This could be a single row (single object) or many rows
 * filtered by one or more parameters, sorted, paginated, etc.
 *
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Model
 * @subpackage DatabaseApi
 * @todo Make sure all methods can be chained! Parameters should be the only things that "stop" the chaining.
 */

/**
 * You can use this value to set the sort() method to random order using mysql's RAND() function.
 * @const string
 **/
define('RANDOM', 'RANDOM');
define('RAND', 'RANDOM');

/**
 * Additional magic methods.
 * @property integer $total The total number of items on all pages.
 * @property integer $count The number of items in the current limit / page.
 * @property integer $affected The number affected items.
 **/
class zajFetcher implements Iterator, Countable{
	// create a fetch class
		public $class_name;									// the class name
		public $table_name;									// the table name
	// public settings
		public $distinct = false;							// distinct is false by default
	// private vars
		private $db;										// my private db instance
		private $query_done = false;						// true if query has been run already
		private $total = false;								// the total count (limit not taken into account)
		private $count = false;								// the instance count (limit included)
		private $affected = false;							// the number returned (limit is taken into account)
	// instance variables needed for generating the sql
		private $select_what = array();					// what to select from db
		private $select_from = array();						// the tables to select from
		private $limit = "";								// the limit parameter
		private $orderby = "ORDER BY model.ordernum";		// ordered by ordernum by default
		private $ordermode = "DESC";						// default order ASC or DESC (defined by model)
		private $groupby = "";								// not grouped by default
		private $filter_deleted = "model.status!='deleted'";	// this does not show deleted items by default
		private $filters = array();							// an array of filters to be applied
		private $wherestr = "";								// where is empty by default
	// connection related stuff
		private $connection_wherestr = "";					// part of the where string if there is a connection involved
		public $connection_parent;							// connections have a parent object - this is a reference to that
		public $connection_field;							// field name of connection
		public $connection_other;							// connections sometimes have another field
		public $connection_type;							// string - manytomany, manytoone, etc.
	// full sql (utilized by connection-related methods)
		private $full_sql = false;							// full sql is when something wants to override the query
	// pagination variable
		private $pagination = false;						// pagination object (variable)
	// iterator variables
		private $current_object = false;					// object - the current object
		private $current_key = false;						// string - key of current object (id)

	/**
	 * Creates a new fetcher object for a specific {@link zajModel} class.
	 * @param string $classname A valid {@link zajModel} class name which will be used to retrieve the individual objects.
	 **/
	public function __construct($classname = ''){
		// if passed as an object
			$this->class_name = addslashes($classname);
			$this->table_name = strtolower($this->class_name);
		// generate query defaults
			$this->add_source('`'.$this->table_name.'`', "model");
			$this->add_field_source('model.id');
			$this->db = zajLib::me()->db->create_session();		// create my own database session
		// default order and pagination (defined by model)
			if($classname::$fetch_paginate > 0) $this->paginate($classname::$fetch_paginate);
			$this->ordermode = $classname::$fetch_order;
			$this->orderby = "ORDER BY model.".$classname::$fetch_order_field;
		return $this;
	}

	
	/**
	 * Paginate results by a specific number.
	 * @param integer $perpage The number of items to list per page.
	 * @param integer|bool $page The current page number. This is normally controlled automatically via the created template variables. See docs for details.
	 * @return zajFetcher This method can be chained.
	 **/
	public function paginate($perpage=10, $page = false){
		// if perpage is 0 or turned off
			if(!$perpage){
				$this->limit(false);
				$this->pagination = false;
			}
		// if specific value set
			else{
			// if page is false, then automatically set!
				if(!empty($_GET['zajpagination']) && $page === false) $page = $_GET['zajpagination'][$this->class_name];
			// set to default page
				if(!$page || !is_numeric($page) || $page <= 0) $page = 1;
			// set the start point
				$startat = $perpage*($page - 1);
			// set the limit values
				$this->limit($startat, $perpage);
			// now set pagination variables
				$this->pagination = (object) array();
				$this->pagination->page = $page;
				$this->pagination->perpage = $perpage;
				$this->pagination->pagefirstitem = ($page-1)*$perpage+1;
				$this->pagination->nextpage = $page+1;	// nextpage is reset to false if not enough object (done after query)
				$this->pagination->prevpage = $page-1;
				$this->pagination->prevurl = zajLib::me()->fullrequest."&zajpagination[{$this->class_name}]={$this->pagination->prevpage}";
				if($this->pagination->prevpage > 0) $this->pagination->prev = "<a href='".$this->pagination->prevurl."'>&lt;&lt;&lt;&lt;</a>";
				else $this->pagination->prev = '';
				$this->pagination->nexturl = zajLib::me()->fullrequest."&zajpagination[{$this->class_name}]={$this->pagination->nextpage}";
				$this->pagination->next = "<a href='".$this->pagination->nexturl."'>&gt;&gt;&gt;&gt;</a>";
				$this->pagination->pageurl = zajLib::me()->fullrequest."&zajpagination[{$this->class_name}]=";
				$this->pagination->pagecount = 1;		// pagecount is reset to actual number (after query)
			}
		// changes query, so reset me
			// done by limit
		return $this;
	}
	/**
	 * Sort by a specific field and by an order.
	 * @param string $by The field name to sort by. Or RANDOM if you want it randomly. Or CUSTOM_SORT if you want the second parameter to just be used directly.
	 * @param string $order ASC or DESC or RANDOM depending on what you want. If left empty, the default for this model will be used. If the first parameter is set to CUSTOM_SORT, you can provide a custom sort string here, including ORDER BY.
	 * @return zajFetcher This method can be chained.
	 **/
	public function sort($by, $order=''){
		// if order is not set
			if($order) $this->ordermode = $order;
			// else do not change
		// set the orderby
			if($by == RANDOM || $order == RANDOM) $this->orderby = "ORDER BY RAND()";
			elseif($by == 'CUSTOM_SORT'){
				$this->orderby = $order;
				$this->ordermode = '';
			}
			elseif($by) $this->orderby = "ORDER BY model.$by";
			else{
				$this->orderby = '';
				$this->ordermode = '';
			}
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * This allows you to group items by whatever field you prefer. Only one field can be specified for now.
	 * @param string|bool $by The fetcher results will be grouped by this field.
	 * @return zajFetcher This method can be chained.
	 */
	public function group($by = false){
		// set the orderby
			if($by) $this->groupby = "GROUP BY model.$by";
			else $this->groupby = '';
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Use this method to specify a custom WHERE clause. Begin with either || or && to continue the query! This is different from {@link zajFetcher->full_query()} because it is appended to the existing query. You should however use this only when necessary as it may cause unexpected behavior.
	 * @param string $wherestr The customized portion of the WHERE clause. Since it is appended to the existing query, begin with || or && to produce a valid query.
	 * @param bool $append If set to true (the detault), the string will be appended to any existing custom WHERE clause.
	 * @return zajFetcher This method can be chained.
	 **/
	public function where($wherestr, $append=true){
		// append or no
			if($append) $this->wherestr .= $wherestr;
			else $this->wherestr = $wherestr;
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * This method adds a joined source to the query. This is mostly for internal use.
	 * @param string $db_table The name of the table to select from.
	 * @param string $as_name The name of the table as referenced within the sql query (SELECT .... FROM table_name AS as_name)
	 * @param boolean $replace If set to true, this will remove all other sources before adding this new one.
	 * @return zajFetcher This method can be chained.
	 **/
	public function add_source($db_table, $as_name, $replace = false){
		//
		if($replace) $this->reset_sources();
		$this->select_from[] = "$db_table AS $as_name";
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Remove all existing joined source from the query. This is mostly for internal use.
	 * @return zajFetcher This method can be chained.
	 **/
	public function reset_sources(){
		// reset the array
			$this->select_from = array();
		// changes query, so reset me
			$this->reset();
		return $this;
	}
	

	/**
	 * This method adds a field to be selected from a joined source. This is mostly for internal use.
	 * @param string $source_field The name of the field to select.
	 * @param string|bool $as_name The name of the field as referenced within the sql query (SELECT field_name AS as_name)
	 * @param bool $replace If set to true, this will remove all other joined fields before adding this new one.
	 * @return zajFetcher This method can be chained.
	 **/
	public function add_field_source($source_field, $as_name=false, $replace = false){
		// if replace
			if($replace) $this->reset_field_sources();
		// if an as name was chosen
			if($as_name) $this->select_what[] = $source_field.' as '.$as_name;
			else $this->select_what[] = $source_field;
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Remove all existing joined fields from the query. This is mostly for internal use.
	 * @return zajFetcher This method can be chained.
	 **/
	public function reset_field_sources(){
		// reset the array
			$this->select_what = array();
		// changes query, so reset me
			$this->reset();
		return $this;
	}


	/**
	 * Create a fetcher object from an array of ids.
	 * @param array $id_array An array of ids to search for.
	 * @return zajFetcher This method can be chained.
	 **/
	public function from_array($id_array){
		$this->wherestr .= " && (0";
		foreach($id_array as $id) $this->wherestr .= " || `id`='".addslashes($id)."'";
		$this->wherestr .= ")";		
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Toggle whether or not to show deleted items. By default, Mozajik will not delete rows you remove, but simply put them in a 'deleted' status. However, {@link zajFetcher} will not show these unless you toggle this option.
	 * @param bool $default If set to true (the default), it will show deleted items for this query. If set to false, it will turn this feature off.
	 * @return zajFetcher This method can be chained.
	 **/
	public function show_deleted($default = true){
		// i want to hide them!
			if(!$default) $this->filter_deleted = "model.status!='deleted'";
			else $this->filter_deleted = "1";
		// changes query, so reset me
			$this->reset();		
		return $this;
	}

	/**
	 * Results are filtered according to $field and $value.
	 * @param string $field The name of the field to be filtered
	 * @param string $value The value by which to filter.
	 * @param string $operator The operator with which to filter. Can be any valid MySQL-compatible operator: LIKE, NOT LIKE, <, >, <=, =, REGEXP etc.
	 * @param string $type AND or OR depending on how you want this filter to connect
	 * @return zajFetcher This method can be chained.
	 **/
	public function filter($field, $value, $operator='LIKE', $type='AND'){
		// add to filters array
			$this->filters[] = array($field, $value, $operator, $type);
		// changes query, so reset me
			$this->reset();
		return $this;
	}
	/**
	 * Exclude/remove filter is just an alias of filter but with different defaults
	 * @param string $field The name of the field to be filtered
	 * @param string $value The value by which to filter.
	 * @param string $operator The operator with which to exclude. It defaults to NOT LIKE. Can be any valid MySQL-compatible operator: NOT LIKE, !=, <, >=, etc.
	 * @param string $type AND or OR depending on how you want this filter to connect
	 * @return zajFetcher This method can be chained.
	 **/
	public function exclude($field, $value, $operator='NOT LIKE', $type='AND'){ return $this->filter($field, $value, $operator, $type); }
	public function exc($field, $value, $operator='NOT LIKE', $type='AND'){ return $this->filter($field, $value, $operator, $type); }

	/**
	 * Remove all results. This is good for reseting a fetch to zero results by default.
	 * @return zajFetcher This method can be chained.
	 **/
	public function exclude_all(){
		return $this->filter('id', '-nothing');
	}
	
	/**
	 * Include results in the result set.
	 **/
	public function inc($field, $value, $operator='LIKE', $type='OR'){ return $this->filter($field, $value, $operator, $type); }

	/**
	 * A special filter method to be used for time filtering. It will filter results into everything BEFORE the given time or object. It is important to note that if you use an object as a parameter, it will change the sort order to the opposite since you are "going backwards" from the selected object.
	 * @param string|zajModel $value The value by which to filter. Can also be a zajmodel of the same type - then field is checked by the model's default sort order.
	 * @param string $field The name of the field to be filtered. Defaults to the time_create field.
	 * @param string $type AND or OR depending on how you want this filter to connect
	 * @todo Add support for any sort() situation.
	 * @return zajFetcher This method can be chained.
	 **/
	public function before($value, $field='time_create', $type='AND'){
		// default operator
			$operator = '<=';
		// is $value a zajmodel
			if(is_a($value, 'zajModel')){
				// check error
					if($value->class_name != $this->class_name) return $this->zajlib->error("Fetcher's before() method only supports using the same model. You tried using '$value->class_name' while this fetcher is a '$this->class_name'.");
				// check my default sort order
					$class_name = $value->class_name;
					$field = $class_name::$fetch_order_field;
				// set my value
					$value = $value->data->$field;
				// am i desc or asc? select operator and reverse the sort order
					if($class_name::$fetch_order == 'ASC'){
						$operator = '<';
						$this->sort($field, 'DESC');
					}
					else{
						$operator = '>';
						$this->sort($field, 'ASC');
					}
			}
		// filter it now
			return $this->filter($field, $value, $operator, $type);
	}
	/**
	 * A special filter method to be used for time filtering. It will filter results into everything AFTER the given time.
	 * @param string|zajModel $value The value by which to filter. Can also be a zajmodel of the same type - then field is checked by the model's default sort order.
	 * @param string $field The name of the field to be filtered. Defaults to the time_create field. Ignored if first paramter is zajModel.
	 * @param string $type AND or OR depending on how you want this filter to connect
	 * @todo Add support for any sort() situation.
	 * @return zajFetcher This method can be chained.
	 **/
	public function after($value, $field='time_create', $type='AND'){
		// default operator
			$operator = '>=';
		// is $value a zajmodel
			if(is_a($value, 'zajModel')){
				// check error
					if($value->class_name != $this->class_name) return $this->zajlib->error("Fetcher's after() method only supports using the same model. You tried using '$value->class_name' while this fetcher is a '$this->class_name'.");
				// check my default sort order
					$class_name = $value->class_name;
					$field = $class_name::$fetch_order_field;
				// set my value
					$value = $value->data->$field;
				// am i desc or asc?
					if($class_name::$fetch_order == 'DESC') $operator = '<';
					else $operator = '>';
			}
		// filter it now
			return $this->filter($field, $value, $operator, $type);
	}

	/**
	 * Limits the results of the query using LIMIT in MySQL.
	 * @param integer $startat This can be either startat or it can be the limit itself.
	 * @param integer|bool $limitto The number of objects to take. Leave empty if the first parameter is used as the limit.
	 * @return zajFetcher This method can be chained.
	 **/
	public function limit($startat, $limitto=false){
		// turn of limit
			if($startat === false || !is_numeric($startat) || ($limitto !== false && !is_numeric($limitto))) $this->limit = "";
		// set limit to value
			else{
			// no $limitto specified
				if($limitto === false) $this->limit = "LIMIT $startat";
			// else, it is specified
				else $this->limit = "LIMIT $startat, $limitto";
			}
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Performs a search on all searchable fields. You can optionally use similarity search. This will use the wherestr parameter, 
	 * @param string $query The text to search for.
	 * @param boolean $similarity_search If set to true (false is the default), similar sounding results will be returned as well.
	 * @todo Add the option to specify fields.
	 * @return zajFetcher This method can be chained.
	 **/
	public function search($query, $similarity_search = false){
		$class_name = $this->class_name;
		// retrieve model
			$model = $class_name::__model();
		// similarity?
			if($similarity_search) $sim = "SOUNDS";
			else $sim = "";
		// figure out search fields (searchfield=true is usually the case for text and id fields)
			$this->wherestr .= " && (0";
			foreach($model as $key=>$field){
				if($field->search_field) $this->wherestr .= " || model.$key $sim LIKE '".$this->db->escape($query)."' || model.$key LIKE '%".$this->db->escape($query)."%'";
			}
			$this->wherestr .= ")";
		// changes query, so reset me
			$this->reset();
		return $this;	
	}
	/**
	 * Execute a full, customized query. Any query must return a column 'id' with the IDs of corresponding {@link zajModel} objects. Otherwise it will not be a valid {@link zajFetcher} object and related methods will fail. A full query will override any other methods used, except for paginate and limit (the limit is appended to the end, if specified!).
	 * @param string $full_sql The full, customized query.
	 * @return zajFetcher This method can be chained.
	 **/
	public function full_query($full_sql){
		// set the full_sql parameter
			$this->full_sql = $full_sql;
		// changes query, so reset me
			$this->reset();
		return $this;
	}


	/**
	 * This method returns the sql statement which will be used during the query.
	 * @return string
	 * @todo Solve the issue with $type: if combining AND and OR then the order matters!
	 * @todo Bug when get_query called twice in a row!
	 */
	public function get_query(){
		// if full_sql set, just return that
			if($this->full_sql) return $this->full_sql.' '.$this->limit;
		// get my class field types
			$classname = $this->class_name;
			$mymodel = $classname::__model();
		// distinct?
			if($this->distinct) $distinct = "DISTINCT";
			else $distinct = "";
		// otherwise, generate a query
			// create filters
				$filters_sql = '';
				foreach($this->filters as $key=>$filter){
					// pre-process input parameters
						// Explode my filter into a list
							list($field, $value, $logic, $type) = $filter;
						// Validate the field name
							if(!zajLib::me()->db->verify_field($field)) return zajLib::me()->warning("Field '$classname.$field' contains invalid characters and did not pass safety inspection!");
						// Now process type
							if($type == "OR" || $type == "||") $type = "||";
							else $type = "&&";
						// Verify logic param
							if($logic != "SOUNDS LIKE" && $logic != "LIKE" && $logic != "NOT LIKE" && $logic != "REGEXP" && $logic != "NOT REGEXP" && $logic != "!=" && $logic != "==" && $logic != "=" && $logic != "<=>" && $logic != ">" && $logic != ">=" && $logic != "<" && $logic != "<=") return zajLib::me()->warning("Fetcher class could not generate query. The logic parameter ($logic) specified is not valid.");
						// if $value is a model object, use its id
							if(is_object($value) && is_a($value, 'zajModel')) $value = $value->id;

					// if use_filter is true, then not a standard field object
						if($mymodel->{$field}->use_filter){
							// create the model
								$fieldobject = $classname::__field($field);
							// call my filter generator
								$filters_sql .= " $type ".$fieldobject->filter($this, $filter);
						}
						else{
							// check if it is a string
								if(is_object($value)) zajLib::me()->error("Invalid filter/exclude value on fetcher object for $classname/$field! Value cannot be an object since this is not a special field!");
							// allow subquery
								if($logic != 'IN' && $logic != 'NOT IN') $filters_sql .= " $type model.`$field` $logic '".$this->db->escape($value)."'";
								else $filters_sql .= " $type model.`$field` $logic ($value)";
						}
				}
			// generate from and what
				$from = join(', ', $this->select_from);
				$what = join(', ', $this->select_what);
			// generate
				return "SELECT $distinct $what FROM $from WHERE $this->filter_deleted $filters_sql $this->wherestr $this->connection_wherestr $this->groupby $this->orderby $this->ordermode $this->limit";
	}
	
	

	/**
	 * Executes the fetcher query.
	 * @param bool $force By default, query will only execute once, so a second query() will be ignored. Set this to true if you want to force execution regardless of previous status.
	 * @return zajFetcher
	 */
	public function query($force = false){		
		// if query already done
			if($this->query_done === true && !$force) return $this;
		// get query and execute it			
			$this->db->query($this->get_query());
		// count rows
			$this->total = $this->db->get_total_rows();
			$this->count = $this->db->get_num_rows();
		// set pagination stuff
			if(is_object($this->pagination)){
				$this->pagination->pagecount = ceil($this->total/$this->pagination->perpage);
				if($this->pagination->nextpage > $this->pagination->pagecount){
					$this->pagination->nextpage = false;
					$this->pagination->next = '';
				}
			}
		// query is done
			$this->query_done = true;
		// return me, so as to enable chaining
			return $this;
	}

	/**
	 * Counts the total number of rows available in this query. Accessible via the {@link zajFetcher->total} parameter.
	 * @return integer
	 **/
	private function count_total(){
		// if already counted, just return
			if($this->total !== false) return $this->total;
		// execute the query
			if(!$this->query_done) $this->query();
		// count
			return $this->total;
	}

	/****************************************************************************************
	 * Control methods used to retrieve objects from the database.
	 * External use is now depricated and you should use foreach, next() and other standard
	 *		php functions, structures.
	 ***************************************************************************************/

	/**
	 * Retrieves the next row data
	 * @todo Set this to private in 1.0
	 **/
	public function next_row($num = 1){
		// if query not yet run, run now
			if($this->query_done == false) $this->query();
		// now return the next row
			return $this->db->get($num, 0);
	}

	/**
	 * Retrieves the next object
	 * @param string $id If id is set, next_row is not called.
	 * @todo Set this to private in 1.0
	 **/
	public function next_object($id = false){
		$class_name = $this->class_name; // i havent found a syntax to do the following in one line!
		if(!$id){
			$data = $this->next_row();
			if(empty($data[0])) return false;
			else $id = $data[0]['id'];
		}
		return $class_name::fetch($id);
	}

	/**
	 * Reset will force the fetcher to reload the next time it is accessed
	 **/
	public function reset(){
		// Set query_done to false
			$this->query_done = false;
		// Set counters to false
			$this->total = false;
			$this->affected = false;
			$this->count = false;
		// Reset iteration
			$this->current_object = false;
			$this->current_key = false;
		return $this;
	}

	/****************************************************************************************
	 *	!Iterator methods
	 *		- These are used by foreach
	 *		- TODO: clean up this mess and remove the old next_row() stuff!
	 ***************************************************************************************/
	/**
	 * Returns the current object in the iteration.
	 **/
	public function current(){
		// if current is not an object, rewind
			if(!is_object($this->current_object)) $this->rewind();
		// else return the current
			return $this->current_object;
	}
	/**
	 * Returns the current key in the iteration.
	 **/
	public function key(){
		if(!is_object($this->current_object)) $this->rewind();
		return $this->current_key;
	}
	/**
	 * Returns the next object in the iteration.
	 **/
	public function next(){
		// Run query if not yet done
			if(!$this->query_done) $this->query();
		// Get the next row
			$result = $this->db->next();
		// Convert to an object and return
			return $this->row_to_current_object($result);
	}
	/**
	 * Rewinds the iterator.
	 **/
	public function rewind(){
		// reewind db pointer
			// if query not yet run, run now
			if(!$this->query_done){
				$this->query();
				return $this->next();
			}
			else{
				// Rewind my db
					$result = $this->db->rewind();
				// Now get result
					return $this->row_to_current_object($result);
			}
	}

	/**
	 * Returns true if the current object of the iterator is a valid object.
	 **/
	public function valid(){
		return is_object($this->current_object);
	}


	/**
	 * Converts the current database row to the current fetched object. Also sets current_key and current_object vars.
	 * @param resource $result The database result row object.
	 * @return zajModel Returns the currently selected zajModel object.
	 **/
	public function row_to_current_object($result){
		// First off, check to see if valid result
			if(!is_object($result) || empty($result->id)) $this->current_object = false;
			else{
				// Now fetch based on my id result
					$class_name = $this->class_name;
					$this->current_object = $class_name::fetch($result->id);
			}
		// Set current key, but only if current object is successful
			if(is_object($this->current_object)) $this->current_key = $this->current_object->id;
			else $this->current_key = false;
		return $this->current_object;
	}


	/****************************************************************************************
	 *	!Countable methods
	 *		- This is used by count()
	 *
	 ***************************************************************************************/

	/**
	 * Returns the total count of this fetcher object.
	 **/
	public function count(){
		return $this->count_total();
	}

	/****************************************************************************************
	 *	!Magic methods
	 *
	 ***************************************************************************************/

	/**
	 * Get object variables which are private or undefined by default.
	 **/
	public function __get($name){
		switch($name){
			case "total":	// if total not yet loaded, then retrieve and return it...otherwise just return it
							if($this->total === false) return $this->count_total();
							return $this->total;
			case "count":	// if count not yet loaded, then retrieve and return it...otherwise just return it
							if($this->count === false) $this->count_total();
							return $this->count;
			case "affected":// if total not yet loaded, then retrieve and return it...otherwise just return it
							if($this->total === false) $this->count_total();
							return $this->total;
			case "paginate":
			case "pagination":
							// if query not yet executed, do it now
							if(!$this->query_done) $this->query();
							return $this->pagination;
			
			default: 		zajLib::me()->warning("Attempted to access inaccessible variable ($name) for zajFetcher class!");
		}
	}

	/****************************************************************************************
	 *	!Multiple object connections
	 *		- Many-to-many & one-to-many both return a fetcher object and not a single one.
	 *		- Chaining is enabled, yes!
	 ***************************************************************************************/

	/**
	 * This method returns the connected fetcher object. This will be a collection of {@link zajModel} objects.
	 * @return zajModel
	 **/
	public static function manytomany($field, &$object){
		// Fetch the other model and other field
			// get my
				$class_name = $object->class_name;
				$table_name = $object->table_name;
			// get other via field
				$field_model = $class_name::__field($field);
				$other_model = $field_model->options['model'];
				$other_field = $field_model->options['field'];		
				$other_table = strtolower($other_model);
		// Am I a primary manytomany connection	
			if(!$other_field){
				// Create a new zajFetcher object
				$my_fetcher = new zajFetcher($other_model);
				// I am a primary connection!
				$field_sql = addslashes($field);	// added for extra safety!
				$my_fetcher->add_source(strtolower("connection_{$class_name}_{$other_table}"), 'conn', true)->add_source('`'.$my_fetcher->table_name.'`', 'model');
				$my_fetcher->add_field_source("conn.id2", "id", true);
				$my_fetcher->connection_wherestr = "&& conn.field='$field_sql' && conn.id1='{$object->id}' && model.id=conn.id2 && conn.status!='deleted' ";
				$my_fetcher->orderby = "ORDER BY conn.order2";
				$my_fetcher->ordermode = "ASC";
			}
			else{
				// Create a new zajFetcher object
				$my_fetcher = new zajFetcher($class_name);
				// I am a reference to a primary connection!
				$my_fetcher->add_source(strtolower("connection_{$other_model}_{$table_name}"), 'conn', true)->add_source('`'.$other_table.'`', 'model');
				$my_fetcher->add_field_source("conn.id1", "id", true);
				$my_fetcher->connection_wherestr = "&& conn.field='$other_field' && conn.id2='{$object->id}' && model.id=conn.id1 && conn.status!='deleted' ";
				$my_fetcher->orderby = "ORDER BY conn.order1";
				$my_fetcher->ordermode = "ASC";
				$my_fetcher->class_name = $other_model;
				$my_fetcher->table_name = $other_table;
			}
		// Set my parent object
			$my_fetcher->connection_parent = $object;
			$my_fetcher->connection_field = $field;
			$my_fetcher->connection_other = $other_field;
			$my_fetcher->connection_type = 'manytomany';
			
		// Return my fetcher object
			return $my_fetcher;
	}

	/**
	 * This method returns the connected fetcher object. This will be a collection of {@link zajModel} objects.
	 * @return zajModel
	 **/
	public static function onetomany($field, &$object){
		// Fetch the other model and other field		
			$class_name = $object->class_name;
			$field_model = $class_name::__field($field);
			$other_model = $field_model->options['model'];
			$other_field = $field_model->options['field'];		
		// Create a new zajFetcher object
			$my_fetcher = new zajFetcher($other_model);
		// Now filter to only ones where id matches me!
			$my_fetcher->filter($other_field, $object->id);
		// Set my parent object
			$my_fetcher->connection_parent = $object;			
			$my_fetcher->connection_field = $field;
			$my_fetcher->connection_other = $other_field;
			$my_fetcher->connection_type = 'onetomany';
		// Return my fetcher object
			return $my_fetcher;
	}


	/****************************************************************************************
	 *	!Single object connections
	 *		- Many-to-one & one-to-one both return single objects, so no fetch really.
	 *		- Chaining is enabled!
	 ***************************************************************************************/

	/**
	 * This method returns the connected fetcher object (which actually translates to a single zajModel object).
	 * @return zajModel
	 **/
	public static function manytoone($class_name, $field, $id){
		// if not id, then return false
			if(empty($id)) return false;
		// if id is already an object
			if(is_object($id) && is_a($id, 'zajModel')) return $id;
		// get the other model
			$field_model = $class_name::__field($field);
			$other_model = $field_model->options['model'];
		// return the one object
			$fetcher = $other_model::fetch($id);
			if(is_object($fetcher)) $fetcher->connection_type = 'manytoone';
			return $fetcher;
	}
	/**
	 * This method returns the connected fetcher object (which actually translates to a single zajModel object).
	 * @return zajModel
	 **/
	public static function onetoone($class_name, $field, $id){
		// return the one object
			$fetcher = zajFetcher::manytoone($class_name, $field, $id);
			if(is_object($fetcher)) $fetcher->connection_type = 'onetoone';
			return $fetcher;
	}

	/****************************************************************************************
	 *	!Relationships editing
	 *		
	 ***************************************************************************************/

	/**
	 * This method adds $object to the manytomany relationship.
	 * @param zajModel $object 
	 * @param string $mode Can be add or delete. This will add or remove the relationship. Defaults to add.
	 * @param array $additonal_fields An assoc array with key/value pairs of additional columns to save to the relationship connection table.
	 * @return zajFetcher Returns the zajFetcher object, so it can be chained.
	 **/	
	public function add($object, $mode = 'add', $additional_fields = false){
		// if not an object
			if(!is_object($object)) zajLib::me()->error('tried to edit a relationship with something that is not a model or fetcher object.');
			//if(!$object->exists) zajLib::me()->error('tried to add a relationship that was not an object');
		// if manytomany, write in separate table
			if($this->connection_type == 'manytomany'){
				$row = array('time_create'=>time());
				if(empty($this->connection_other)){
					$table_name = strtolower('connection_'.$this->connection_parent->class_name.'_'.$object->class_name);
					$row['id1'] = $this->connection_parent->id;
					$row['id2'] = $object->id;
					$row['field'] = $this->connection_field;
				}
				else{
					$table_name = strtolower('connection_'.$object->class_name.'_'.$this->connection_parent->class_name);
					$row['id1'] = $object->id;
					$row['id2'] = $this->connection_parent->id;
					$row['field'] = $this->connection_other;
				}
				// add additional fields
					if(!empty($additional_fields) && is_array($additional_fields)){
						foreach($additional_fields as $key=>$value){
							$row[$key] = $value;
						}
					}
				// create a row to add
					$row['id'] = uniqid("");
					$row['order1'] = MYSQL_MAX_PLUS;
					$row['order2'] = MYSQL_MAX_PLUS;
					$db = zajLib::me()->db->create_session();
					if($mode == 'add') $db->add($table_name, $row);
					if($mode == 'delete') $db->query("DELETE FROM `$table_name` WHERE `id1`='".$row['id1']."' && `id2`='".$row['id2']."' && `field`='".$row['field']."' LIMIT 1");
					// @todo: destroy db session
			}
			elseif($this->connection_type == 'manytoone' || $this->connection_type == 'onetoone'){
				zajLib::me()->warning('Using add is only necessary on manytomany fields.');
			}
			elseif($this->connection_type == 'onetomany'){
				zajLib::me()->warning('Using add is only necessary on manytomany fields. On onetomany fields, you should try setting up the relationship from the manytoone direction.');
			}
		// Update other object (if needed)! Since the save() method is only called on $connection_parent and not on $object, the appropriate magic methods
		//			need to be called here....
			if(!empty($this->connection_other)){
				// same events called as after a save()
					$object->fire('afterSave');
					$object->fire('afterFetch');
				// cache the new values
					$object->cache();
			}
		// return me so i can chain more
			return $this;	
	}

	/**
	 * This method removes $object from the manytomany relationship.
	 * @param zajModel $object 
	 * @param string $mode Can be add or delete. This will add or remove the relationship. Defaults to delete.
	 * @return zajFetcher Returns the zajFetcher object, so it can be chained.
	 **/
	public function remove($object, $mode='delete'){
		return $this->add($object, $mode);
	}
	
	/**
	 * Returns true or false based on whether or not the current fetcher is connected to the object $object.
	 * @param zajModel $object The object in question.
	 * @return boolean True if connected, false otherwise.
	 * @todo Change count_only!
	 **/
	public function is_connected($object){
		// primary connection
			if($this->connection_other) return $this->db->count_only("connection_{$object->table_name}_{$this->connection_parent->table_name}","(`id1`='{$object->id}' && `id2`='{$this->connection_parent->id}')");
		// secondary connection
			else return $this->db->count_only("connection_{$this->connection_parent->table_name}_{$object->table_name}","(`id2`='{$object->id}' && `id1`='{$this->connection_parent->id}')");
	}
	
	
}
?>