<?php
/**
 * Mozajik tag collection includes tags which are not in Django by default, but are part of the Mozajik system.  
 * @package Template
 * @subpackage Tags
 **/
 
////////////////////////////////////////////////////////////////////////////////////////////////////
// Each method should use the write method to send the generated php code to each applicable file.
// The methods take two parameters:
//		- $param_array - an array of parameter objects {@link zajCompileVariable}
//		- $source - the source file object {@link zajCompileSource}
////////////////////////////////////////////////////////////////////////////////////////////////////

class zajlib_tag_mozajik extends zajElementCollection{

	/**
	 * Tag: input - Generates an input field based on the input defined in the model. So, a field of type photo will generate a photo upload box.
	 *
	 *  <b>{% input user.avatar user.data.avatar %}</b>
	 *  1. <b>model_field</b> - The field name defined in the model. The format is model_name.field_name.
	 *  2. <b>default_value</b> - The default value. This will usually be the existing data of the model object.
	 *  <b>{% input user.password user.data.password 'custom.field.html' %}</b>
	 *  3. <b>locale|custom_html</b> - If the fourth parameter is set, this field is the locale and the next is custom_html. If only three are set, this is custom_html. See next parameter for custom_html.
	 *  4. <b>custom_html</b> - If you want to use a custom HTML to generate your own field editor then you can specify the html relative to any of the view directories.
	 **/
	public function tag_input($param_array, &$source){
		// check for required param
			if(empty($param_array[0])) $source->error("Tag {%input%} requires at least one parameter.");
		// grab my class and field name
			list($classname, $fieldname) = explode('.', $param_array[0]->vartext);
		// check for required param
			if(empty($classname) || empty($fieldname)) $source->error("Tag {%input%} parameter one needs to be in 'modelname.fieldname' format.");
		// id or options
			$id = $template = '';
			$value = $param_array[1]->variable;
		// get field object
			$field_object = $classname::__field($fieldname);
		// are we in locale mode?
			if(isset($param_array[3])){
				// update field name with locale data
					$fieldname = "translation[".$fieldname."][{".$param_array[2]->variable."}]";
					// if it is a translation variable, then localize it!
					if(strstr($param_array[1]->variable, '->translation->') !== false) $value = $param_array[1]->variable.'->get_by_locale('.$param_array[2]->variable.')';
				// generate template based on type unless specified
					if(!empty($param_array[3])) $template = trim($param_array[3]->variable, "'\"");
					else $template = $field_object::edit_template;
			}
		// we are in compatibility mode!
			else{
				// generate template based on type unless specified
					if(!empty($param_array[2])) $template = trim($param_array[2]->variable, "'\"");
					else $template = $field_object::edit_template;
			}
		// generate content					
			// generate options
				$options_php = $this->zajlib->array->to_code($field_object->options);
			// create an empty field object
				$this->zajlib->compile->write('<?php $this->zajlib->variable->field = (object) array(); ?>');
			// callback
				$field_object->__onInputGeneration($param_array, $source);			
			// set stuff
				$this->zajlib->compile->write('<?php $this->zajlib->variable->field->options = (object) '.$options_php.'; $this->zajlib->variable->field->class_name = "'.$classname.'"; $this->zajlib->variable->field->name = "'.$fieldname.'"; $this->zajlib->variable->field->id = "field['.$fieldname.']"; $this->zajlib->variable->field->uid = uniqid("");  ?>');
			// add set value
				if(!empty($param_array[1])) $this->zajlib->compile->write('<?php $this->zajlib->variable->field->value = '.$value.'; ?>');
			// now create form field
				$this->zajlib->compile->compile($template);
				//$this->zajlib->compile->write("insert file $template");
				$this->zajlib->compile->insert_file($template.'.php');		
		// return debug_stats
			return true;
	}
	/**
	 * @ignore
	 * @todo Depricated. Remove from RC.
	 **/
	public function tag_formfield($param_array, &$source){
		// depricated old name for input
			return $this->tag_input($param_array, $source);
	}

	/**
	 * Tag: inputlocale - Generates a locale-enabled input field based on the input defined in the model. This must be supported by the model and field type.
	 *
	 *  <b>{% inputlocale user.avatar user.data.avatar 'sk_SK' %}</b>
	 *  1. <b>model_field</b> - The field name defined in the model. The format is model_name.field_name.
	 *  2. <b>default_value</b> - The default value. This will usually be the existing data of the model object.
	 *  3. <b>locale</b> - The locale name to use. If left empty, the current locale will be used.
	 *  4. <b>custom_html</b> - If you want to use a custom HTML to generate your own field editor then you can specify the html relative to any of the view directories.
	 **/
	public function tag_inputlocale($param_array, &$source){
		// check for required param
			if(empty($param_array[0])) $source->error("Tag {%inputlocale%} requires at least one parameter.");
		// add defaults
			if(empty($param_array[1])) $param_array[1] = '';
			if(empty($param_array[2])) $param_array[2] = '$this->zajlib->lang->get()';
			if(empty($param_array[3])) $param_array[3] = '';
		// update param array and pass over to input
			return $this->tag_input(array(
					$param_array[0],
					$param_array[1],
					$param_array[2],
					$param_array[3],
				), $source);
	}
	
	/**
	 * Tag: insert - Inserts another template at this location. The template is treated as if it were inline.
	 *
	 *  <b>{% insert '/admin/news_edit.html' 'block_name' %}</b>
	 *  1. <b>template_file</b> - The template file to insert.
	 *  2. <b>block_section</b> - If you only want to insert the block section from the file. (optional)
	 * @todo See comments below for optimization.
	 **/
	public function tag_insert($param_array, &$source){
		// get the first parameter...
			$var = $param_array[0]->variable;
			$tvar = trim($var, "'\"");
		// TODO: if it is a string, then its static, so compile and insert file here...
			/*if($var != $tvar){
				// compile contents
					$this->zajlib->compile->compile($tvar);
				// insert to current destination
					$this->zajlib->compile->insert_file($tvar.'.php');
			}*/
			// DO THIS FOR insertlocal as well

		// if it is a single variable, then we need to do it with template->show
				if(count($param_array) <= 1) $contents = <<<EOF
<?php
// start insert
	\$this->zajlib->template->show({$param_array[0]->variable});
?>
EOF;
		// if it is two variables, then we need to do it with template->block
				else $contents = <<<EOF
<?php
// start insert block
	\$this->zajlib->template->block({$param_array[0]->variable}, {$param_array[1]->variable});
?>
EOF;
			
		
				// write to file
					$this->zajlib->compile->write($contents);
			//}
		// return debug_stats
			return true;
	}	

	/**
	 * Tag: insertlocal - Same as {@link insert} except that this also checks for localized versions of the HTML file before including.
	 *
	 *  <b>{% insertlocal '/admin/news_edit.html' 'block_name' %}</b>
	 *  1. <b>template_file</b> - The template file to insert.
	 *  2. <b>block_section</b> - If you only want to insert the block section from the file. (optional)
	 **/
	public function tag_insertlocal($param_array, &$source){
		// get the first parameter...
			$var = $param_array[0]->variable;
			$tvar = trim($var, "'\"");
		// if it is a single variable, then we need to do it with template->show
				if(count($param_array) <= 1) $contents = <<<EOF
<?php
// start insert for local file
	\$this->zajlib->lang->template({$param_array[0]->variable});
?>
EOF;
		// if it is two variables, then we need to do it with template->block
				else $contents = <<<EOF
<?php
// start insert block for local file
	\$this->zajlib->lang->block({$param_array[0]->variable}, {$param_array[1]->variable});
?>
EOF;
			
		
				// write to file
					$this->zajlib->compile->write($contents);
			//}
		// return debug_stats
			return true;
	}	

	/**
	 * Tag: lorem - Generates a lorem ipsum text.
	 *
	 *  <b>{% lorem %}</b>
	 **/
	public function tag_lorem($param_array, &$source){
		// write to file
			$this->zajlib->compile->write("Lorem ipsum dolor sit amet, consectetur adipiscing elit, set eiusmod tempor incidunt et labore et dolore magna aliquam. Ut enim ad minim veniam, quis nostrud exerc. Irure dolor in reprehend incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse molestaie cillum. Tia non ob ea soluad incom dereud facilis est er expedit distinct. Nam liber te conscient to factor tum poen legum odioque civiuda et tam. Neque pecun modut est neque nonor et imper ned libidig met, consectetur adipiscing elit, sed ut labore et dolore magna aliquam is nostrud exercitation ullam mmodo consequet.");
		// return debug_stats
			return true;
	}

	/**
	 * Tag: debug - Outputs some exciting debug information.
	 *
	 *  <b>{% debug %}</b>
	 * @todo Update this with useful information.
	 **/
	public function tag_debug($param_array, &$source){
		// TODO: fix this to be more informative!
		// print info
			$this->zajlib->compile->write("<?php\n print_r(\$this->zajlib->variable);\n?>");
		// return debug_stats
			return true;
	}
	
	/**
	 * Tag select: Generates a select HTML.
	 *	- example: {% select keywords_list ['name'] [default_value] [additional_parameters] [values_equal_desc] %}
	 * @todo This is depricated and should not be used in this format.
	 **/
	public function tag_select($param_array, &$source){
		// generate random id
			if(!$param_array[1]) $p1="'".uniqid('')."'";
			else $p1 = $param_array[1]->variable;
		// optional params
			if($param_array[2]) $param_array[2] = ', '.$param_array[2]->variable;
			if($param_array[3]) $param_array[3] = ', '.$param_array[3]->variable;
			if($param_array[4]) $param_array[4] = ', '.$param_array[4]->variable;
		// generate content
			$contents = <<<EOF
<?php
// start select
	\$this->zajlib->load->library('html');
	echo \$this->zajlib->html->select($param_array[1], $param_array[0] $param_array[2] $param_array[3] $param_array[4]);
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;
	}	

	/**
	 * Tag: time - Generates a unix time stamp
	 *
	 *  <b>{% time %}</b>
	 **/
	public function tag_time($param_array, &$source){
		// figure out content
			$contents = "<?php echo time(); ?>";
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;
	}

	/**
	 * Tag: dump - Dump the value of the variable to output.
	 *
	 *  <b>{% dump forum.messages %}</b>
	 *  1. <b>variable</b> - The variable to dump.
	 **/
	public function tag_dump($param_array, &$source){
		// figure out content
			$contents = "<?php echo var_dump({$param_array[0]->variable}); ?>";
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;
	}

	/**
	 * Tag: count - Loop which counts from the first value to the second and places it in the third param.
	 *
	 *  <b>{% count '1' '10' as counter %}{{counter}}. Commandment!{% endcount %}</b><br>
	 *  <b>{% count from '10' to '1' as counter %}{{counter}}. Countdown!{% endcount %}</b>
	 *  1. <b>from</b> - Count from this number.
	 *  2. <b>to</b> - Count to this number.
	 *  3. <b>counter</b> - Use this variable to store the current counter value.
	 **/
	public function tag_count($param_array, &$source){
		// pause the parsing
			$source->add_level('count', '');
		// generate a for loop
			if($param_array[2]->variable == '$this->zajlib->variable->as'){
				$contents = <<<EOF
		<?php
			if({$param_array[1]->variable} < {$param_array[0]->variable}){
				\$count_from = {$param_array[1]->variable};
				\$count_to = {$param_array[0]->variable};
				\$count_reverse = true;
			}
			else{
				\$count_from = {$param_array[0]->variable};
				\$count_to = {$param_array[1]->variable};
				\$count_reverse = false;
			}
			\$count_var =& {$param_array[3]->variable};	
EOF;
			}
			elseif($param_array[4]->variable == '$this->zajlib->variable->as'){
				$contents = <<<EOF
		<?php
			if({$param_array[3]->variable} <= {$param_array[1]->variable}){
				\$count_from = {$param_array[3]->variable};
				\$count_to = {$param_array[1]->variable};
				\$count_reverse = true;
			}
			else{
				\$count_from = {$param_array[1]->variable};
				\$count_to = {$param_array[3]->variable};
				\$count_reverse = false;
			}
			\$count_var =& {$param_array[5]->variable};
EOF;
			}
			else $source->error("Incorrect syntax for tag {%count%}.");

			$contents .= <<<EOF
			for(\$count_var_real=\$count_from; \$count_var_real<=\$count_to; \$count_var_real++){
				if(!\$count_reverse) \$count_var = \$count_var_real;
				else \$count_var = \$count_to - \$count_var_real + \$count_from;
EOF;
			if($param_array[2]->variable == '$this->zajlib->variable->as') $contents .= "{$param_array[3]->variable} = \$count_var;?>";
			else $contents .= "{$param_array[5]->variable} = \$count_var;?>";
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;
	}
	/**
	 * @ignore
	 **/
	public function tag_endcount($param_array, &$source){
		// pause the parsing
			$source->remove_level('count');
		// generate an end to the for loop
			$contents = "<?php } ?>";
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;
	}


	/**
	 * Tag: literal - The section in between is taken as literal, meaning it is not compiled.
	 *
	 *  - <b>example:</b> {% literal %}Text goes here!{% endliteral %}
	 * @todo A known issue requires the {%endliteal%} tag to be on its own seperate line. Otherwise it is not detected!
	 **/
	public function tag_literal($param_array, &$source){
		// pause the parsing
			$source->set_parse(false, 'endliteral');
			$source->add_level('literal', '');
		// return debug_stats
			return true;
	}
	/**
	 * @ignore
	 **/
	public function tag_endliteral($param_array, &$source){
		// resume parsing
			$source->set_parse(true);
			$source->remove_level('literal');
		// return debug_stats
			return true;
	}
	
	/**
	 * Tag: config - Loads a configuration file in full or just a specified section
	 *
	 *  <b>{% config 'file_name.conf.ini' 'section_name' %}</b>
	 *  1. <b>file_name</b> - A filename of the configuration file relative to the plugins conf directory.
	 *  2. <b>section_name</b> - The name of the section to load. If omitted, the entire file will be loaded.
	 **/
	public function tag_config($param_array, &$source){
		// Is section name specified?
			if(!empty($param_array[1])) $param2 = ", {$param_array[1]->variable}";
			else $param2 = "";
		// figure out content
			$contents = "<?php \$this->zajlib->config->load({$param_array[0]->variable} $param2); ?>";
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;
	}
	
	/**
	 * Tag: lang - Loads a language file in full or just a specified section
	 *
	 *  <b>{% lang 'file_name.lang.ini' 'section_name' %}</b>
	 *  1. <b>file_name</b> - A filename of the language file relative to the plugins lang directory.
	 *  2. <b>section_name</b> - The name of the section to load. If omitted, the entire file will be loaded.
	 **/
	public function tag_lang($param_array, &$source){
		// Is section name specified?
			if(!empty($param_array[1])) $param2 = ", {$param_array[1]->variable}";
			else $param2 = "";
		// figure out content
			$contents = "<?php \$this->zajlib->lang->load({$param_array[0]->variable} $param2); ?>";
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;
	}

	/**
	 * Tag: unique - Generates a random unique id using php's uniqid("") and prints it or saves it to a variable.
	 *
	 *  <b>{% unique as uid %}</b>
	 *  1. <b>variable</b> - Variable which to save the unique id. If specified, no output will be generated. If not specified, uniqid will be echoed.
	 **/
	public function tag_unique($param_array, &$source){
		// figure out content
			if(empty($param_array[1])) $contents = "<?php echo uniqid(''); ?>";
			else $contents = "<?php {$param_array[1]->variable} = uniqid(''); ?>";
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;
	}

	/**
	 * Tag: applyfilter - Applies the specified filters to the variable and sets it as the value. Similar to {% with %} except that applyfilter sets the value for the remainder of the document. If you don't explicitly need this, we recommend using {% with %} as {% applyfilter %} can cause unexpected outcomes!
	 *
	 *  <b>{% applyfilter counter|add:'1' as incremented %}</b>
	 *  1. <b>counter</b> - In this example counter will be incremented by one each time it is encountered. The filtered value (incremented by one) is set back to the original.
	 *  2. <b>incremented</b> - The second variable specifies how the filtered value will be known from now on. You can use the original value if you want to modify the original.
	 **/
	public function tag_applyfilter($param_array, &$source){
		// figure out content
			$contents = <<<EOF
<?php
// start with
	{$param_array[2]->variable} = {$param_array[0]->variable};
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;
	}

	/**
	 * Tag: cachebuster - Generates a seconds-based timestamp (s after 2013.03.04. when this feature was developed) which you can place after files like so: style.css?{% cachebuster %}. This will only regenerate when the template cache is reset.
	 *
	 * Because the query string is different after each template cache reset, the browser will be forced to reload the file. This is useful since the cache will be reset when the page is in active development but will stay static when it is not. For optimization you should remove it though from files that are no longer being changed often.
	 *  <b>style.css?{% cachebuster %}</b>
	 **/
	public function tag_cachebuster($param_array, &$source){
		$my_fixed_timestamp = time() - 1362430170;
		// write to file
		$this->zajlib->compile->write("<?php print '?v{$my_fixed_timestamp}';?>");
		// return debug_stats
		return true;
	}

}

?>