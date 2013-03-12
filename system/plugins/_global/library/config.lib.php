<?php
/**
 * This library handles the loading and compiling of configuration and language files.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

$GLOBALS['regexp_config_variable'] = "";
$GLOBALS['regexp_config_comment'] = "//";

/**
 * @property stdClass $variable The config variables.
 */
class zajlib_config extends zajLibExtension{
	protected $dest_path = 'cache/conf/';		// string - subfolder where compiled conf files are stored (cannot be changed)
	protected $conf_path = 'conf/';			// string - default subfolder where uncompiled conf files are stored
	protected $type_of_file = 'configuration';// string - the name of the file type this is (either configuration or language)
	protected $loaded_files = array();		// array - all the files loaded with load()
	protected $debug_stats = array();			// array - contains debug stats about current compiled file
	protected $destination_files = array();	// array - an array of files to write to
	/**
	 * object - config variables are stored here
	 **/
	private $variable;
	
	/**
	 * Loads a configuration or language file at runtime.
	 * @param string $source_path The source of the configuration file relative to the conf folder.
	 * @param string|bool $section The section to compile.
	 * @param boolean $force_compile This will force recompile even if a cached version already exists.
	 * @param boolean $fail_on_error If set to true (the default), it will fail with error.
	 * @return bool Returns true if successful, false otherwise.
	 */
	public function load($source_path, $section=false, $force_compile=false, $fail_on_error = true){
		// check chroot
			if(strpos($source_path, '..') !== false) return $this->zajlib->error($this->type_of_file.' source file must be relative to conf path.');
		// generate the file name
			if($section) $fsection = '.'.$section;
			else $fsection = '';
			$file_name = $this->zajlib->basepath.$this->dest_path.$source_path.$fsection.'.php';
		// was it already loaded?
			if(!empty($this->loaded_files[$file_name])) return true;
		// does it exist? if not, compile now!
			$result = true;
			if($force_compile || $this->zajlib->debug_mode || !file_exists($file_name)) $result = $this->compile($source_path, $fail_on_error);
		// If compile failed or if include fails
			if(!$result || !(@include_once($file_name))){
				if($fail_on_error) $this->error("Could not load ".$this->type_of_file." file $source_path / $section! Section not found ($file_name)!");
				else return false;
			}
		// now load me!
		// set as loaded
			$this->loaded_files[$file_name] = true;
			return true; 
	}
	
	/**
	 * My getter method.
	 **/
	 	function __get($name){
		 	if($name == 'variable') return $this->zajlib->config->variable;
		 	return $this->$name;
	 	}


	/**
	 * Compiles a configuration file. Source_path should be relative to the conf path set by set_folder (conf/ by default). You should not call this method manually.
	 * @param string $source_path The source of the configuration file relative to the conf folder.
	 * @param boolean $fail_on_error If set to true (the default), it will fail with error.
	 * @return boolean Returns true if successful, false otherwise.
	 * @todo Make this private?
	 **/
	public function compile($source_path, $fail_on_error = true){
		// Search for my source file
			$full_path = $this->zajlib->load->file($this->conf_path.$source_path, false, false);
			if($full_path === false){
				if($fail_on_error) return $this->zajlib->error($this->type_of_file.' file failed to load. The file '.$source_path.' could not be found in any of the local or plugin folders.');
				else return false;
			}
			else $full_path = $this->zajlib->basepath.$full_path;
		// add the global output file
			$this->zajlib->load->library('file');
			$global_file = $this->zajlib->basepath.$this->dest_path.$source_path.'.php';
			$this->zajlib->file->create_path_for($global_file);
			$this->add_file($global_file);
			$section_file = false;
			$global_scope = '';
		// start debug stats
			$this->debug_stats['source'] = $full_path;
			$this->debug_stats['line'] = 1;
		// now open and run through all the lines
			$fsource = fopen($full_path, 'r');
				while(!feof($fsource)){
					// grab a trimmed line
						$line = trim(fgets($fsource));
					// lets see what kind of line is this?
						switch(substr($line, 0, 1)){
							case false:		// it's an empty line, ignore!
							case '#':		// it's a comment, ignore!
											break;
							case '[':		// it's a section marker, remove previous section file, add new section file
												// remove previous if there is one
													if($section_file) $this->remove_file($section_file);
												// add new one
													$section = trim($line, '[]');
													if(preg_replace('/^[a-zA-Z_][a-zA-Z0-9_]*/', '', $section) != '') $this->error('Illegal section definition. A-z, numbers, and _ allowed!');
													$section_file = $this->zajlib->basepath.$this->dest_path.$source_path.'.'.$section.'.php';
													$this->add_file($section_file, $global_scope);
											break;
							default:		// it's a variable line
												// separate by =
													list($varname, $varcontent) = explode('=', $line, 2);
													$varname = trim($varname);
													$varcontent = trim($varcontent);
												// is varname not valid?
													if(preg_replace('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', '', $varname) != ''){
														$this->error('Invalid variable found!');
														break;
													}
												// check for other malicious stuff (php tags)
													if(strpos($varcontent,'?>') !== false) $this->error('Illegal characters found in variable content');
													if(strpos($varcontent,'<?') !== false) $this->error('Illegal characters found in variable content');
												// generate variable
													// treat booleans and numbers separately
														if($varcontent == 'false' || $varcontent == 'true' || is_numeric($varcontent)) $current_line = '$this->zajlib->config->variable->'.$varname.' = '.addslashes($varcontent).";\n";
														else $current_line = '$this->zajlib->config->variable->'.$varname.' = \''.addslashes($varcontent)."';\n";
													$this->write_line($current_line);
												// while not in any section, add the current line to the "global" scope
													if(!$section_file) $global_scope .= $current_line;
											
						
						}
					$this->debug_stats['line']++;				
				}
			$this->remove_all_files();
			return true;
	}
	
	/**
	 * Adds a configuration output file.
	 * @param string $file_name The name of the file.
	 * @param string $global_scope An optional string of content that all section files should contain (it is any content before any section marker).
	 * @return resource Returns the file pointer to the destination file.
	 **/
	private function add_file($file_name, $global_scope=''){
		$this->destination_files[$file_name] = fopen($file_name, 'w');
		fputs($this->destination_files[$file_name], "<?php\n".$global_scope);
		return $this->destination_files[$file_name];
	}
	/**
	 * Removes a configuration output file.
	 * @param string $file_name The name of the file.
	 * @return boolean Returns true.
	 **/
	private function remove_file($file_name){
		fputs($this->destination_files[$file_name], "\n?>");
		fclose($this->destination_files[$file_name]);
		unset($this->destination_files[$file_name]);
		return true;
	}
	/**
	 * Removes all configuration output files.
	 **/
	private function remove_all_files(){
		// run through and remove all
			foreach($this->destination_files as $file_name=>$file_pointer) $this->remove_file($file_name);
	}
	
	/**
	 * Write a line to all output files
	 * @param string $line_content The content of the line.
	 * @return integer The number of files that the output was written to.
	 **/
	private function write_line($line_content){
		// run through all the files
			$file_counter=0;
			foreach($this->destination_files as $file_name=>$file_pointer){
				fputs($file_pointer, $line_content);
				$file_counter++;
			}
			return $file_counter;
	}
	
	/**
	 * Set the base folder of the configuration files.
	 * @param $new_folder A new folder relative to the base path.
	 * @todo This should be fixed, but all plugins should have their own conf file.
	 **/
	public function set_folder($new_folder){
		// check chroot
			if(strpos($new_folder, '..') !== false) return $this->zajlib->error($this->type_of_file.' source folder must be relative to base path.');
		// set it
			$this->conf_path = $new_folder.'/';
		return true;
	}

	/**
	 * Display a compile warning.
	 * @param string $message Display this message.
	 * @param array $debug_stats If set, these debug stats will be displayed (instead of the default which is $this->debug_stats).
	 **/
	public function warning($message, $debug_stats=false){
		// get the object debug_stats
			if(!is_array($debug_stats)) $debug_stats = $this->debug_stats;
		echo $this->type_of_file." file compile warning: $message (file: $debug_stats[source] / line: $debug_stats[line])<br/>";
	}

	/**
	 * Display a fatal compile error and exit.
	 * @param string $message Display this message.
	 * @param array $debug_stats If set, these debug stats will be displayed (instead of the default which is $this->debug_stats).
	 **/
	public function error($message, $debug_stats=false){
		// get the object debug_stats
			if(!is_array($debug_stats)) $debug_stats = $this->debug_stats;
		// send to zajlib error
			$this->zajlib->error("Fatal ".$this->type_of_file." file compile error: $message (file: $debug_stats[source] / line: $debug_stats[line])");
		exit;
	}
	
}

?>