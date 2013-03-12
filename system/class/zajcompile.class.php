<?php
/**
 * Backend compile-related classes.
 * 
 * This file contains classes related to the template-compiling backend. You do not need to access these classes and methods directly;
 * you should use the compile() library instead. These classes ensure that tags, variables, parameters, and filters are processed,
 * the appropriate plugin files (tags, filters) are loaded, and the necessary functions are called.
 *
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Template
 * @subpackage CompilingBackend
 */

/**
 * Regular expression to find a django-style tag.
 */
define('regexp_zaj_tag', "(\\{[%{][ ]*([[:alnum:]\'_#\\.]+)(.*?)([\\%}]}|\\n))");
/**
 * Regular expression to one filter.
 */
define('regexp_zaj_onefilter', '\|(([A-z]*)([ ]*(:)[ ]*)?)?(\'(.*?)[^\\\']\'|\"(.*?)[^\\\\\"]\"|[A-z.\-_0-9#]*)');
/**
 * Regular expression to one tag parameter (including filter support).
 */
define('regexp_zaj_oneparam', '(\'(.*?)\'|\"(.*?)\"|(<=|>=|!=|==|=|>|<)|[A-z.\-_0-9#]*)('.regexp_zaj_onefilter.")*");
/**
 * Regular expression to one tag variable.
 */
define('regexp_zaj_variable', '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/');
/**
 * Regular expression to tag operator.
 */
define('regexp_zaj_operator', '/(<=|>=|!=|==|=|>|<)/');

/**
 * One compile session, which may include several source and destination files.
 * 
 * A compile session is the compilation of an entire tree of inherited, extended, included files. Individual blocks and insert tags will compile to their
 * own temporary files. The entire session is (in the end) combined into a single generated php file, which is stored in the cache folder.
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileSession {
     // private
     	private $zajlib;						// object - pointer to the zajlib object
		private $sources = array();				// array of objects - an array of source files
		private $destinations = array();		// array of objects - an array of destination files
		private $unlinks = array();				// array of objects - these are destination files which will be unlinked
     // public
     	/**
		 * A unique id generated to identify this session.
		 * @var string
		 */
		public $id;								// string - session id
		
	
	/**
	 * Constructor for compile session. You should not create this object directly, but instead use the compile library.
	 *
	 * @param string $source_file Relative path of source file.
	 * @param object $zajlib Pointer to the global zajlib object.
	 * @param string $destination_file Relative path of destination file. If not specified, the destination will be the same as the source, which is the preferred way of doing things. You should only specify this if you are customizing the template compilation process.
	 * @return zajCompileSession
	 */
	public function __construct($source_file, &$zajlib, $destination_file = false){
		// set zajlib
			$this->zajlib =& $zajlib;
		// create id
			$this->id = uniqid("");
		// start a new destination
			if(!$destination_file) $this->add_destination($source_file);
			else $this->add_destination($destination_file);
		// start a new source
			$this->add_source($source_file);		
	}


	/**
	 * Starts the compile session. You should not call methods of this object directly, but instead use the compile library.
	 *
	 * @return boolean True on success, false on failure.
	 */
	public function compile(){
		// go!		
			$success = $this->go();
		// if not success, return false
			if(!$success) return false;
		// do i have any unlinks?
			foreach($this->unlinks as $uobj) $uobj->unlink();
		return true;
	}
	
	/**
	 * Compiles the currently selected source file.
	 *
	 * @access private
	 * @return boolean True on success, false on failure.
	 */
	private function go(){
		// get current source
			$current_source = reset($this->sources);
		// unpause destination
			if($current_source->line_number == 0) $this->main_dest_paused(false);		
			else return false;
		// compile while i dont reach its eof
			while(!$current_source->eof()) $current_source->compile();
		// remove the source
			array_shift($this->sources);
		// now recursive if more sources left
			if(count($this->sources) > 0) return $this->go();
		return true;
	}

	/**
	 * Writes one line to each destination file.
	 *
	 * @return boolean Always returns true.
	 */
	public function write($content){
		foreach($this->destinations as $dest){
			$dest->write($content);
		}
		return true;
	}

	/**
	 * Inserts a file at current destination file location. The file will not be parsed.
	 *
	 * @param string $source_file Relative path of source file.
	 * @return boolean Always returns true.
	 */
	public function insert_file($source_path){
		// open file as source
			$source = new zajCompileSource($source_path, $this->zajlib);
		// set not to parse
			$source->set_parse(false);
		// now compile
			while(!$source->eof()) $source->compile();
		
		return true;
	}


	/**
	 * Add a source to this compile session. You should not call methods of this object directly, but instead use the compile library.
	 *
	 * @param string $source_file Relative path of source file.
	 * @return boolean Always returns true.
	 */
	public function add_source($source_path){
		$this->sources[] = new zajCompileSource($source_path, $this->zajlib);
		return true;
	}

	/**
	 * Gets the currently selected source file object.
	 *
	 * @return zajCompileSource
	 */
	public function get_source(){
		return reset($this->sources);
	}

	/**
	 * Add a destination file to this compile session. You should not call methods of this object directly, but instead use the compile library.
	 *
	 * @param string $dest_path Relative path of destination file.
	 * @param boolean $temporary OPTIONAL. If true file will be deleted at the end of this session. Defaults to false.
	 * @return boolean Returns true if file successfully created, false otherwise.
	 */
	public function add_destination($dest_path, $temporary = false){
		$this->destinations[$dest_path] = new zajCompileDestination($dest_path, $this->zajlib, $temporary);
		return $this->destinations[$dest_path]->exists;
	}
	/**
	 * Remove a destination file to this compile session. You should not call methods of this object directly, but instead use the compile library.
	 *
	 * @param string $dest_path Relative path of destination file.
	 * @return boolean Always returns true.
	 */
	public function remove_destination($dest_path){
		unset($this->destinations[$dest_path]);
		return true;
	}
	/**
	 * Pause all destination files. All writing will be ignored while pause is active.
	 *
	 * @return boolean Always returns true.
	 */
	public function pause_destinations(){
		foreach($this->destinations as $dest) $dest->pause();
		return true;
	}
	/**
	 * Resume all destination files. All writing will be resumed.
	 *
	 * @return boolean Always returns true.
	 */
	public function resume_destinations(){
		foreach($this->destinations as $dest) $dest->resume();
		return true;
	}
	/**
	 * Returns true if destinations are paused, false otherwise.
	 *
	 * @return boolean True or false, depending on whether destinations are currently paused.
	 */
	public function are_destinations_paused(){
		return end($this->destinations)->paused;
	}

	/**
	 * Sets the pause status of the main destination. The main destination is the primary destination file which will contain the full php code in the end.
	 *
	 * As with other other methods in this class, this is used internally by the system and should not be called directly.
	 *
	 * @param boolean $bool True if you want nothing to be written to the main file, false otherwise.
	 * @return void
	 */
	public function main_dest_paused($bool){
		if($bool) reset($this->destinations)->pause();
		else reset($this->destinations)->resume();
	}
	/**
	 * Returns true if main destination is paused, false otherwise.
	 *
	 * @return boolean True or false, depending on whether main destination is currently paused.
	 */
	public function is_main_dest_paused(){
		return reset($this->destinations)->paused;
	}

	/**
	 * Sets a destination object to be deleted upon completion.
	 *
	 * @todo Shouldn't this be private?
	 * @param zajCompileDestination $bool True if you want this file to be deleted, false otherwise.
	 * @return void
	 */
	public function unlink($object){
		// add to array of unlinks
			$this->unlinks[] = $object;
	}
	
}



/**
 * Handles the compilation of single source file.
 * 
 * The compilation process includes syntax check, parsing, and writing to any potential destinations via the compile session object.
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileSource {
	public $zajlib;				// object - pointer to global zajlib object

	// instance variables
		private $file;					// file pointer - source file
		private $current_line = '';		// string - contains the current line's string (or part of it)
		private $current_tag = '';		// string - contains the current tag being processed
		private $line_number = 0;		// int - number of the current line in this file
		private $file_path;				// string - full path to the source file
		private $requested_path;		// string - the relative path to the source file
		private $hierarchy = array();	// array - stores info about open/close tags
		private $level = 0;				// int - current level of hierarchy
		public $extended = false;		// boolean - true if this source is extended
	
	// settings	
		private $paused = false;		// boolean - if paused, reading from this file will not occur
		private $parse = true;			// boolean - if parse is true, the line will be parsed before writing
		private $resume_at = '';		// string - when parse is turned off, you can set to resume at a certain tag
		private static $paths = array(			// array - array of paths to search, in order of preference
			'local'=>'app/view/',
			'plugin_apps'=>true,				// boolean - set this to false if you don't want to check for app plugin views
			'system'=>'system/app/view/',
			'system_apps'=>true,				// boolean - when true, system apps will be loaded (don't change this unless you know what you're doing!)
			'temp_block'=>'cache/temp/',
			'compiled'=>'cache/view/',
		);
	
	public function __construct($source_file, &$zajlib){
		// set zajlib & debug stats
			$this->zajlib =& $zajlib;
		// jail the user
			if(strpos($source_file, '..') !== false) $this->zajlib->error("invalid source path ($source_file) found during compilation!");
		// does it exist?
			if(!$path = $this->file_exists($source_file)) return $this->zajlib->error("template file $source_file could not be found.");
		// open file
			$this->requested_path = $source_file;
			$this->file_path = $path;
			return $this->file = fopen($path, 'r');
	}	

	/**
	 * Read and parse
	 **/
	public function compile(){
		// while not end of file
			if($this->eof()) return $this->zajlib->error("tried reading at eof. please use eof method!");
		// pause
			if($this->paused) return '';
		// read a line from the file if current_line is empty
			if($this->current_line == ''){
				$this->current_line = fgets($this->file);
				$this->line_number++;
			}
		// check for php related stuff (but only if parsing is on)
			if($this->parse){
				// disable PHP tags
					if(preg_match("/<[\?%](php| |\\n)+/", $this->current_line) > 0) return $this->zajlib->error("cannot use PHP or ASP tags in template file ($this->file_path): &lt;?, &lt;?php, or &lt;% are all forbidden.");
				// now replace any other codes in line (<?xml for example)
					$this->current_line = preg_replace("/(<[\?%][A-z]*)/", '<?php print "${1}"; ?>', $this->current_line);				
			}
		// try to match a tag
			$currentmatches = '';
			if(
				// if tag matched and parseing is on
					(preg_match(regexp_zaj_tag, $this->current_line, $currentmatches, PREG_OFFSET_CAPTURE) && $this->parse)
				// OR if parseing is off but match is equal to resume_at
					|| (!$this->parse && !empty($this->resume_at) && $currentmatches[3][0] == '%}' && $currentmatches[1][0] == $this->resume_at)
			){
				// check for syntax error
					if($currentmatches[3][0] != '%}' && $currentmatches[3][0] != '}}') $this->warning('line terminated before end of tag/variable!');
				// set my basics
					$full = trim($currentmatches[0][0], '{} ');
					$element_name = $currentmatches[1][0];
					$parameters = $currentmatches[2][0];
					$this->current_tag = $element_name;
				// calculate new offset
					$my_offset = $currentmatches[3][1] + 2;
				// write everything up to this tag to file
					$this->write(substr($this->current_line, 0, $my_offset - strlen($currentmatches[0][0])));
				// seek back to end of tag
					$new_offset = (strlen($this->current_line) - $my_offset)*-1;
				// if end of line
					if($new_offset >= 0) $this->current_line = '';
				// else, still some chars left
					else $this->current_line = substr($this->current_line, $new_offset);
				// is this a tag or variable? write either
					if($currentmatches[3][0] == '%}') zajCompileTag::compile($element_name, $parameters, $this);
					else zajCompileVariable::compile($full, $this);
			}
		// not tags/variables on this line, so just write it plain to the file
			else{
				// write current line
					$this->write($this->current_line);
				// reset current line
					$this->current_line = '';
			}
			return true;
	}
	/**
	 * Write a single line of content to each destination.
	 * @param string $content The content to be written to each file.
	 **/
	public function write($content){
		return $this->zajlib->compile->write($content);
	}

	////////////////////////////////////////////////////////
	// Settings and parameters
	////////////////////////////////////////////////////////
	public function eof(){
		return (!$this->current_line && feof($this->file));
	}
	public function pause(){
		$this->paused = true;
		return true;
	}
	public function resume(){
		$this->paused = false;
		return true;
	}
	public function set_parse($new_setting, $resume_at=''){
		$this->parse = $new_setting;
		$this->resume_at = $resume_at;
	}
	
	/**
	 * Returns the requested source path which is relative to the plugin/view/etc. folder.
	 **/
	public function get_requested_path(){ return $this->requested_path; }

	////////////////////////////////////////////////////////
	// Levels of hierarchy
	////////////////////////////////////////////////////////
	public function add_level($tag, $data){
		// add a level of hierarchy
			array_push($this->hierarchy, array(
				'tag'=>$tag,
				'data'=>$data,
			));
		// add one to level counter
			$this->level++;
		return true;
	}
	public function remove_level($tag){
		// remove a level of hierarchy
			list($start_tag, $data) = array_values(array_pop($this->hierarchy));
		// if tag mismatch
			if($tag != $start_tag) $this->error("Expecting $start_tag and found $tag end tag.");
		// remove one from level counter
			$this->level--;
		return $data;
	}
	public function get_level_data($tag){
		// get the last level of hierarchy
			list($start_tag, $data) = array_values(end($this->hierarchy));
		// if tag mismatch
			if($tag != $start_tag) $this->error("The tag $tag has to be nested within $start_tag tags.");
		return $data;
	}
	public function get_level_tag(){
		// get the last level of hierarchy
			list($start_tag, $data) = array_values(end($this->hierarchy));
		return $start_tag;
	}
	public function get_level(){
		return $this->level;
	}
	public function get_current_tag(){
		// returns the current tag being processed			
		return $this->current_tag;
	}
	
	// Read-only access to variables!
	public function __get($name){
		return $this->$name;
	}

	////////////////////////////////////////////////////////
	// Error methods
	////////////////////////////////////////////////////////
	public function warning($message){
		echo "Template compile warning: $message (file: $this->file_path / line: $this->line_number)";
	}
	public function error($message){
		echo "Template compile error: $message (file: $this->file_path / line: $this->line_number)";
		exit;	
	}
	
	/**
	 * Check if template file exists in any of the paths. Returns path if yes, false if no.
	 * @param string $source_file The path to the source file.
	 **/
	public static function file_exists($source_file){
		// run through all the paths
		foreach(zajCompileSource::$paths as $type=>$path){
			// if type is plugin_apps, then it is special!
				if($type == 'plugin_apps' && $path){
					// run through all of my registered plugin apps' views and return if one found!
						foreach(zajLib::me()->loaded_plugins as $plugin_app){
							$path = zajLib::me()->basepath.'plugins/'.$plugin_app.'/view/'.$source_file;
							if(file_exists($path)) return $path;
						}
				}
				elseif($type == 'system_apps' && $path){
					// run through all of my registered system apps' views and return if one found!
						foreach(zajLib::me()->zajconf['system_apps'] as $plugin_app){
							$path = zajLib::me()->basepath.'system/plugins/'.$plugin_app.'/view/'.$source_file;
							if(file_exists($path)) return $path;
						}
				}
				else $path = zajLib::me()->basepath.$path.$source_file;
			if(file_exists($path)) return $path;
		}
		// no existing files found
		return false;
	}
}

/**
 * Handles a compile destination file.
 * 
 * This handles writing to the file and deleting temporary files after use.
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileDestination {
	private $zajlib;				// object - pointer to global zajlib object

	// instance variables
		private $file;					// file pointer - source file
		private $line_number = 0;		// int - number of the current line in this file
		private $file_path;				// file pointer - source file
	
	// controls
		private $exists = false;		// boolean - true if file exists, writing to this file will not occur 
		private $paused = false;		// boolean - if paused, writing to this file will not occur
		private $temporary = false;		// boolean - if true, then this is a temporary file, delete on unset
	
	public function __construct($dest_file, &$zajlib, $temporary = false){
		// set zajlib & debug stats
			$this->zajlib =& $zajlib;
		// jail the user
			if(strpos($dest_file, '..') !== false) $this->zajlib->error("invalid destination path ($dest_file) found during compilation!");
		// tmp or not?
			$this->temporary = $temporary;
			if($this->temporary) $subfolder = "temp";
			else $subfolder = "view";
		// check path
			$this->file_path = $this->zajlib->basepath.'cache/'.$subfolder.'/'.$dest_file.'.php';
		// does it exist...temporary files are not recreated
			if(file_exists($this->file_path)){
				$this->exists = true;
				if($this->temporary) return false;
			}
		// open the cache file, create folders (if needed)
			@mkdir(dirname($this->file_path), 0777, true);
			$this->file = fopen($this->file_path, 'w');
		// start with writing validation
			$this->write("<? \$this->template_valid = true; ?>");
		// did it fail?
			if(!$this->file) return $this->zajlib->error("could not open ($dest_file) for writing. does cache folder have write permissions?");
		return true;
	}

	public function write($content){
		// if paused, just return OR if exists&temp
			if(($this->exists && $this->temporary) || $this->paused) return true;
		// write this to file
			return fputs($this->file, $content);
	}

	public function pause(){
		$this->paused = true;
		return true;
	}

	public function resume(){
		$this->paused = false;
		return true;
	}
	
	public function unlink(){
		// delete this file
			return @unlink($this->file_path);
	}
	
	public function __destruct(){
		// close the file
			if($this->file) fclose($this->file);
		// if this is temporary, delete
			if($this->temporary) $this->zajlib->compile->unlink($this);
	}

	// Read-only access to variables!
	public function __get($name){
		return $this->$name;
	}

}

/**
 * A tag compiling object.
 * 
 * This represents a single tag in the source. The class is responsible for parsing the tag, and sending control to the
 * appropriate tag/filter methods.
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileTag extends zajCompileElement {
	private $parameters = array();	// array - array of params
	private $paramtext;				// string - string of all params as in source
	private $tag;					// string - tag name
	public $param_count = 0;		// int - number of params

	protected function __construct($element_name, $parameters, &$parent){
		// call parent
			parent::__construct($element_name, $parent);
		// set paramtext & tag
			$this->paramtext = $parameters;
			$this->tag = $element_name;
			if(!empty($parameters)){
				// process parameters
					// now match all the parameters
						preg_match_all('/'.regexp_zaj_oneparam.'/', $parameters, $param_matches, PREG_PATTERN_ORDER);//PREG_SET_ORDER
					// grab parameter plus filters (all are at odd keys (why?))
						foreach($param_matches[0] as $param){
							if(trim($param) != ''){
								// create a compile variable
									$pobj = new zajCompileVariable($param, $this->parent);
								// set to parameter mode
									$pobj->set_parameter_mode(true);
								// set as a parameter
									$this->parameters[$this->param_count++] = $pobj;
							}						
						}
			}
	}
		
	public function write(){
		// prepare all filtered parameters
			$filter_prepare = '';
			foreach($this->parameters as $pkey=>$param){
				// does it have any filters?
				if($param->filter_count){
					// prepare the filtered variable
						$param->prepare();	// set to $filter_var
					// now reset parameter to filtered variable
						$random_var = 'tmp_'.uniqid("");
						$this->parent->write('<?php $this->zajlib->variable->'.$random_var.' = $'.$random_var.' = $filter_var; ?>');
					// reset parameter in array of objects (TODO: there may be a more memory-efficient way of doing this?)
						$this->parameters[$pkey] = new zajCompileVariable($random_var, $this->parent);
				}
			}
		// now call me
			$element_name = $this->element_name;
			$this->parent->zajlib->compile->tags->$element_name($this->parameters, $this->parent);
	}
	
	public static function compile($element_name, $parameters, &$parent){
		// create new
			$tag = new zajCompileTag($element_name, $parameters, $parent);
		// write
			$tag->write();
	}

	public function __get($name){
		switch($name){
			case 'tag': return $this->tag;
			case 'paramtext': return $this->paramtext;
			default: $this->parent->zajlib->warning("Tried to access inaccessible parameter $name of zajCompileTag.");
		}
	}


}

/**
 * A variable compiling object.
 * 
 * This represents a single variable in the source. The class is responsible for parsing the variable, and sending control to the
 * appropriate filter methods (if needed).
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileVariable extends zajCompileElement {
	private $vartext;					// string - original text of var (as in source)
	private $variable;					// string - string representation of var
	private $filters = array();			// array - array of filters to be applied
	private $parameter_mode = false;	// bool - true if this is part of the parameter
	public $filter_count = 0;			// int - number of filters

	public function __construct($element_name, &$parent){
		// call parent
			parent::__construct($element_name, $parent);
		// now match all the filters
			preg_match_all('/'.regexp_zaj_onefilter.'/', $element_name, $filter_matches, PREG_SET_ORDER);//PREG_PATTERN_ORDER
		// now run through all the filters
			$trim_from_end = 0;
			foreach($filter_matches as $filter){
				if(!empty($filter[0])){
					// pass: full filter text, filter value, file pointer, debug stats
					if(!empty($filter[5])) $filter[5] = $this->convert_variable($filter[5]);
					$this->filters[$this->filter_count++] = array(
						'filter'=>$filter[2],
						'parameter'=>$filter[5],
					);
					$trim_from_end -= strlen($filter[0]);
				}
			}
		// trim filters from me
			if($trim_from_end < 0) $element_name = substr($element_name, 0, $trim_from_end);
		// original text
			$this->vartext = $element_name;
		// convert me
			$this->variable = $this->convert_variable($element_name);
			$this->element_name = $element_name;
		return true;
	}
	
	public function set_parameter_mode($parameter_mode){
		$this->parameter_mode = $parameter_mode;
	}
	
	public function prepare(){
		// start the filter var
			$this->parent->write("<?php \$filter_var = $this->variable; ");
		// now execute all the filters
			$count = 1;
			foreach($this->filters as $filter){
				// get filter
					list($filter, $parameter) = array_values($filter);
				// now call the filter
					$this->parent->zajlib->compile->filters->$filter($parameter, $this->parent, $count);
					$count++;
			}			
			//$this->parent->write("\$filter_var = \$filter_var;");
		// now end it
			$this->parent->write(" ?>");
		return true;	
	}
	
	public function write(){
		// if filter count
			if($this->filter_count){
				// prepare the filter var
					$this->prepare();
				// now echo the filter var
					$this->parent->write("<?php echo \$filter_var; ?>");
			}
		// no filter, just the var
			else $this->parent->write("<?php echo $this->variable; ?>");		
		return true;	
	}
	
	public static function compile($element_name, &$parent){
		// create new
			$var = new zajCompileVariable($element_name, $parent);
		// write
			$var->write();
	}
	
	public function __get($name){
		switch($name){
			case 'vartext': return $this->vartext;
			case 'variable': return $this->variable;
			default: $this->parent->zajlib->warning("Tried to access inaccessible parameter $name of zajCompileVariable.");
		}
	}

}

/**
 * A general element in the source (tag or variable).
 * 
 * This is a parent class for tags and variables in the source. It provides various methods that may be needed by these elements.
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileElement{
	// variables
	protected $element_name;		// string - name of variable
	protected $parent;				// object - pointer to parent source file

	protected function __construct($element_name, &$parent){
		// set parent and element
			$this->parent =& $parent;
			$this->element_name = $element_name;
		return true;
	}

	// convert variable to the format used in the final php output
	protected function convert_variable($variable){
		// leaves 'asdf' as is but converts asdf.qwer to $this->zajlib->variable->asdf->qwer
		// config variables #asdf# now supported!
		// and so are filters...
			if(substr($variable, 0, 1) == '"' || substr($variable, 0, 1) == "'") return $variable;
			elseif(substr($variable, 0, 1) == '#'){
				$var_element = trim($variable, '#');
				return '$this->zajlib->config->variable->'.$var_element;
			}
			else{
				$var_elements = explode('.',$variable);
				$new_var = '$this->zajlib->variable';
				// Run through each variable. Variables are valid in three ways: (1) actual variable, (2) numerical, or (3) operator in if tag
				foreach($var_elements as $element){
					// (1) Is it an actual variable?
						if(preg_match(regexp_zaj_variable, $element) <= 0){							
							// (2) Is it an operator in an if tag
							if(preg_match(regexp_zaj_operator, $element) <= 0){
								// (3) Is it a numerical value
								if(is_numeric($variable)) $new_var = $element;
								else{
									// Nothing worked, this is just invalid...STOP!
									$this->parent->error("invalid variable/operator found: $variable!");
								}
							}
							else{
								// This is an operator! So now let's make sure this is an if tag
								if($this->parent->get_current_tag() != 'if'){
									$this->parent->warning("operator $variable is only supported for 'if' tags!");
									return '$empty';
								}
								else $new_var = $element;
							}
						}
						else $new_var .= '->'.$element;
				}
				return $new_var;
			}
	}


}

?>