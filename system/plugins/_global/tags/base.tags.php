<?php
/**
 * The base tag collection includes tags which are part of the Django templates system.
 * @package Template
 * @subpackage Tags
 **/
 
////////////////////////////////////////////////////////////////////////////////////////////////////
// Each method should use the write method to send the generated php code to each applicable file.
// The methods take two parameters:
//		- $param_array - an array of parameter objects {@link zajCompileVariable}
//		- $source - the source file object {@link zajCompileSource}
////////////////////////////////////////////////////////////////////////////////////////////////////

class zajlib_tag_base extends zajElementCollection{

	/**
	 * Tag: comment - Comments are sections which are completely ignored during compilation.
	 *
	 *  <b>{% comment 'My comment' %}</b>
	 *  <br><b>{% comment %}My comment{% endcomment %}</b>	 
	 *  1. <b>comment</b> - The comment.
	 **/
	public function tag_comment($param_array, &$source){
		// convert to php comment
			if($param_array[0]) $this->zajlib->compile->write("<?php\n// $param_array[0]\n?>");
			else{
				// nested mode!
				$this->zajlib->compile->write("<?php\n/*");
				$source->add_level('comment', false);
			}
		// return true
			return true;
	}
	/**
	 * @ignore
	 **/
	public function tag_endcomment($param_array, &$source){
		// take out level
			$source->remove_level('comment');
		// write line
			$this->zajlib->compile->write("*/?>");
		// return debug_stats
			return true;	
	}
	
	/**
	 * Tag: cycle - Cycle among the given strings or variables each time this tag is encountered.
	 *
	 *  <b>{% cycle var1 'text' var3 %}</b>
	 *  1. <b>var1</b> - Use this the first time through.
	 *  2. <b>var2</b> - Use this the second time through.
	 *  etc.
	 **/
	public function tag_cycle($param_array, &$source){
		// generate cycle array
			$var_name = '$cycle_array_'.uniqid("");
			$var_name_counter = '$cycle_counter_'.uniqid("");
			$my_array = 'if(empty('.$var_name.')) '.$var_name.' = array(';
			foreach($param_array as $el) $my_array .= "$el->variable, ";
			$my_array .= ');';
			$which_one_var = "[\$which_one]";
		// generate content
			$contents = <<<EOF
<?php
	// define my choices and my default
		$my_array
		if(!isset($var_name_counter)) $var_name_counter = 0;
		else $var_name_counter++;
	// choose which one to display now
		\$which_one = abs($var_name_counter % count($var_name));
	// choose
		echo {$var_name}{$which_one_var};
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}

	/**
	 * Tag: filter - Applies a filter to all text within tag.
	 *
	 *  <b>{% filter lowercase|escapejs %}</b>
	 *  1. <b>filters</b> - A list of filters to apply to the text.
	 * @todo Implement this, but this may have to work differently!
	 **/
	public function tag_filter($param_array, &$source){

		// TODO: do this with capture output
		
		
		// write to file
			//$this->zajlib->compile->write($contents);
		// return true
			return true;
	}
	
	/**
	 * Tag: firstof - Prints the first in a list which evaluates to true.
	 *
	 *  <b>{% firstof var1 var2 var3 %}</b>
	 *  - <b>variables</b> - a list of variables
	 **/
	public function tag_firstof($param_array, &$source){
		// generate cycle array
			$var_name = '$firstof_array';
			$my_array = $var_name.' = array(';
			foreach($param_array as $el) $my_array .= "$el->variable, ";
			$my_array .= ');';
		// generate content
			$contents = "<?php\n";
			$contents .= <<<'EOF'
	// my array
		$my_array
	// first which is true
		foreach($firstof_array as $el->variable){
			if($el->variable) echo $el->variable;
			break;
		}
EOF;
			$contents .= "\n?>";
		
		
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}

	/**
	 * Tag: for, foreach - Loops through a collection which can be a {@link zajFetcher} object or an array of values. It can accept django or php style syntax.
	 *
	 *  <b>{% for band in bands %}</b>
	 *  <br><b>{% foreach bands as band %}</b> 
	 *  1. <b>band</b> - The variable to use within the loop to refer to the current element.
	 *  2. <b>bands</b> - The fetcher or array to loop through.
	 *  Extra forloop.var helpers variables: key (the current key - if applicable), even (true if counter is even), odd (true if counter is odd)
	 **/
	public function tag_for($param_array, &$source){
		return $this->tag_foreach($param_array, $source);
	}	
	/**
	 * See for.
	 * 
	 * {@link tag_for()}
	 **/
	public function tag_foreach($param_array, &$source){
		// which parameter goes where?
			// django compatible
			if($param_array[1]->variable == '$this->zajlib->variable->in'){
				$fetcher = $param_array[2]->variable;
				$fetchervar = $param_array[2]->vartext;
				$item = $param_array[0]->variable;
			}
			// php compatible
			elseif($param_array[1]->variable == '$this->zajlib->variable->as'){
				$fetcher = $param_array[0]->variable;
				$fetchervar = $param_array[0]->vartext;
				$item = $param_array[2]->variable;
			}
			else $source->warning('Invalid foreach tag syntax.');
		// add a level to hierarchy
			$local_var = '$foreach_item_'.uniqid("");
			$source->add_level('foreach', array('item'=>$item, 'local'=>$local_var));
		
		// generate code
			$contents = <<<EOF
<?php
// save the item if it exists
	if(!empty({$item})) $local_var = {$item};

// this is an array or a fetcher object
	if(!is_array({$fetcher}) && !is_object({$fetcher})) \$this->zajlib->warning("Cannot use for loop for parameter ({$fetchervar}) because it is not an an array, a fetcher, or an object!");
	else{
		// does a parent forloop exist?
			if(is_object(\$this->zajlib->variable->forloop)) \$parent_forloop = \$this->zajlib->variable->forloop;
		// create for loop variables
			\$this->zajlib->variable->forloop = (object) '';
			\$this->zajlib->variable->forloop->counter0 = -1;
			// If not countable object, then typecast to array first (todo: can we do this in lib->array_to_object?)
			if(is_object({$fetcher}) && !is_a({$fetcher}, 'Countable')) \$this->zajlib->variable->forloop->length = count((array) {$fetcher});
			else \$this->zajlib->variable->forloop->length = count({$fetcher});
 			\$this->zajlib->variable->forloop->counter = 0;
			\$this->zajlib->variable->forloop->revcounter = \$this->zajlib->variable->forloop->length+1;
			\$this->zajlib->variable->forloop->revcounter0 = \$this->zajlib->variable->forloop->length;
			if(!empty(\$parent_forloop) && is_object(\$parent_forloop)) \$this->zajlib->variable->forloop->parentloop = \$parent_forloop;
			foreach({$fetcher} as \$key=>{$item}){	
				\$this->zajlib->variable->forloop->counter++;
				\$this->zajlib->variable->forloop->counter0++;
				\$this->zajlib->variable->forloop->revcounter--;
				\$this->zajlib->variable->forloop->revcounter0--;
				\$this->zajlib->variable->forloop->odd = (\$this->zajlib->variable->forloop->counter % 2);
				\$this->zajlib->variable->forloop->even = !(\$this->zajlib->variable->forloop->odd);
				\$this->zajlib->variable->forloop->first = !\$this->zajlib->variable->forloop->counter0;
				\$this->zajlib->variable->forloop->last = !\$this->zajlib->variable->forloop->revcounter0;
				\$this->zajlib->variable->forloop->key = \$key;
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}
	/**
	 * Tag: elsefor, elseforeach - This is shown if the for loop array or fetcher contains no elements.
	 *
	 * Please note that if the variable is neither a {@link zajFetcher} object or an array, then an error will be generated! Elsefor is only valid in case the count is zero.
	 *  <b>{% for band in bands %}</b>
	 *  <br><b>{% elsefor %}</b>
	 *  <br>Your content here.	 
	 *  <br><b>{% endfor %}</b> 
	 **/
	public function tag_elsefor($param_array, &$source){
		return $this->tag_empty($param_array, $source);
	}
	/**
	 * See elsefor.
	 * 
	 * {@link tag_elsefor()}
	 **/
	public function tag_elseforeach($param_array, &$source){
		return $this->tag_elsefor($param_array, $source);
	}
	/**
	 * See elsefor.
	 * 
	 * {@link tag_elsefor()}
	 **/
	public function tag_empty($param_array, &$source){
		// get level data
			$data = $source->get_level_data('foreach');
		// generate code
			$contents = <<<EOF
<?php
// end while
	}
//only print rest if 0
	if(\$this->zajlib->variable->forloop->length == 0){
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}	
	/**
	 * See for.
	 * 
	 * {@link tag_for()}
	 **/
	public function tag_endfor($param_array, &$source){
		return $this->tag_endforeach($param_array, $source);
	}
	/**
	 * See for.
	 * 
	 * {@link tag_for()}
	 **/
	public function tag_endforeach($param_array, &$source){
		// get the data
			$data = $source->remove_level('foreach');
		// generate code
			$contents = <<<EOF
<?php
// end while and if
	}}
	
// reset foreach item
	if(@defined($data[local])){
		$data[item] = $data[local];
		unset(\$foreach_item);
	}
	// if I have a parent, set me
	if(!empty(\$this->zajlib->variable->forloop->parentloop) && is_object(\$this->zajlib->variable->forloop->parentloop)){
		\$parent_forloop = \$this->zajlib->variable->forloop->parentloop;
		unset(\$this->zajlib->variable->forloop);
		\$this->zajlib->variable->forloop = \$parent_forloop;
	}
	else{
		// unset stuff
			unset(\$parent_forloop);
			unset(\$this->zajlib->variable->forloop);
	}
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}

	
	/**
	 * Tag: if - If the condition evaluates to true, the contents will be printed.
	 *
	 *  <b>{% if condition %}</b>
	 *  <br><b>{% elseif %}{% endif %}</b>	 
	 *  - <b>condition</b> - A variable which evaluates to true or false.<br>
	 *
	 *  <b>{% if condition eq '10' %}</b>
	 *  - <b>condition</b> - In this case condition is tested against a value. You can use <b>not, and, or</b> for boolean logic and <b>eq, gt, lt, gteq, lteq</b> for operators.<br>	 
	 * 
	 *	<b>{% elseif %}</b>
	 *	Content if condition is false.
	 *	<b>{% endif %}</b>
	 **/
	public function tag_if($param_array, &$source){
		// add level
			$source->add_level('if', false);
		
		// build condition
			$param_ok = true;	// needed to track that param cannot follow param
			$string = '';
			foreach($param_array as $param){
				switch($param->variable){
					case '$this->zajlib->variable->not':	$string .= "!";
															break;
					case '$this->zajlib->variable->and':	$string .= "&& ";
															$param_ok = true;	
															break;
					case '$this->zajlib->variable->or':		$string .= "|| ";
															$param_ok = true;	
															break;
					case '$this->zajlib->variable->gt':	
					case '>':	
															$string .= "> ";
															$param_ok = true;	
															break;
					case '$this->zajlib->variable->lt':
					case '<':	
															$string .= "< ";
															$param_ok = true;	
															break;
					case '$this->zajlib->variable->eq':		
					case '=':
					case '==':

															$string .= "== ";
															$param_ok = true;	
															break;
					case '$this->zajlib->variable->lteq':
					case '<=':
															$string .= "<= ";
															$param_ok = true;
															break;															
					case '$this->zajlib->variable->gteq':
					case '>=':
															$string .= ">= ";
															$param_ok = true;
															break;
					case '!=':
															$string .= "!= ";
															$param_ok = true;
															break;
					case '$this->zajlib->variable->in':
															$source->error("Use the |in filter instead!"); // fatal error
															$param_ok = false;
															break;
					default:	if(!$param_ok) $source->error("Proper operator expected instead of $param!"); // fatal error
								$string .= $param->variable.' ';
								$param_ok = false;
								break;
				}
			}
		
		
		// generate if true
			$contents = <<<EOF
<?php
// start if
	if($string){
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}		
	/**
	 * @ignore
	 * @todo Remove this depricated tag.
	 **/
	public function tag_ifequal($param_array, &$source){
		// add level
			$source->add_level('if', false);
		// generate if true
			$contents = <<<EOF
<?php
// start if
	if({$param_array[0]->variable} == {$param_array[1]->variable}){
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;	
	}
	/**
	 * @ignore
	 * @todo Remove this depricated tag.
	 **/
	public function tag_ifnotequal($param_array, &$source){
		// add level
			$source->add_level('if', false);
		// generate if true
			$contents = <<<EOF
<?php
// start if
	if({$param_array[0]->variable} != {$param_array[1]->variable}){
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;	
	}
	/**
	 * See if.
	 * 
	 * {@link tag_if()}
	 **/
	public function tag_elseif($param_array, &$source){
		// generate if true
			$contents = <<<EOF
<?php
	}
	else{
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;	
	}
	/**
	 * See if. Though this can also be used by foreach.
	 * 
	 * {@link tag_if()}
	 * {@link tag_for()}
	 **/
	public function tag_else($param_array, &$source){
		// auto-detect the current tag
			$current_tag = $source->get_level_tag();
		// do a switch
			switch($current_tag){
				case 'if':
				case 'ifequal':
				case 'ifnotequal':		return $this->tag_elseif($param_array, $source);
				case 'ifchanged':		return $this->tag_elseifchanged($param_array, $source);
				case 'foreach':			return $this->tag_elsefor($param_array, $source);
				default:				$source->error('Unexpected else tag!');
			}
	}
	/**
	 * See if.
	 * 
	 * {@link tag_if()}
	 **/
	public function tag_endif($param_array, &$source){
		// remove level
			$source->remove_level('if');
		// write to file
			$this->zajlib->compile->write("<?php } ?>");
		// return true
			return true;
	}
	/**
	 * @ignore
	 **/
	public function tag_endifequal($param_array, &$source){
		return $this->tag_endif($param_array, $source);
	}
	/**
	 * @ignore
	 **/
	public function tag_endifnotequal($param_array, &$source){
		return $this->tag_endif($param_array, $source);
	}

	/**
	 * Tag: ifchanged - If the param var has changed, returns true, print contents.
	 *
	 *  <br><b>{% ifchanged whatever.item %}print this if changed{% endifchanged%}</b>	 
	 *  1. <b>variable</b> - The variable to test for change.
	 **/
	public function tag_ifchanged($param_array, &$source){
		// create a random variable name
			$varname = '$ifchanged_'.uniqid("");
		// add level
			$source->add_level('ifchanged', array('var'=>$varname,'param'=>$param_array[0]->variable));
		// generate if true
			$contents = <<<EOF
<?php
// start ifchanged
	if(!isset($varname) || $varname != {$param_array[0]->variable}){
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}
	/**
	 * See ifchanged.
	 * 
	 * {@link tag_ifchanged()}
	 **/
	public function tag_elseifchanged($param_array, &$source){
		// generate if true
			$contents = <<<EOF
<?php	
	}
	else{
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;		
	}
	/**
	 * See ifchanged.
	 * 
	 * {@link tag_ifchanged()}
	 **/
	public function tag_endifchanged($param_array, &$source){
		// remove level, get var name
			$vars = $source->remove_level('ifchanged');
		// generate if true
			$contents = <<<EOF
<?php	
	}
	$vars[var] = $vars[param];
// end of ifchanged
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return debug_stats
			return true;		
	}

	/**
	 * Tag: include - Include an app controller by request url (relative to base url).
	 *
	 *  <br><b>{% include '/message/new/' parameter1 'parameter two' %}</b>	 
	 *  1. <b>request</b> - The request which will be routed as any other such URL request.
	 *  2. <b>optional parameters</b> - zero, one, or more optional parameters, passed as parameters to the controller method.
	 **/
	public function tag_include($param_array, &$source){
		// generate optional parameters
			$var1 = array_shift($param_array)->variable;
			$param_vars = array();
			foreach($param_array as $param) $param_vars[] = $param->variable;
			$var2 = join(', ', $param_vars);
		// generate content
			$contents = <<<EOF
<?php
// start include
	\$this->zajlib->load->library('url');
	\$this->zajlib->url->redirect($var1, array($var2));
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}	


	/**
	 * Tag: load - Load and register external tags and/or filters.
	 *
	 *  <br><b>{% load 'django' %}</b>	 
	 *  1. <b>name</b> - The name of the tag and/or filter collection to load.
	 **/
	public function tag_load($param_array, &$source){
		// generate content
			$contents = <<<EOF
<?php
// register tags and filters
	\$this->zajlib->compile->register_tags({$param_array[0]->variable});
	\$this->zajlib->compile->register_filters({$param_array[0]->variable});
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}	


	/**
	 * Tag: now - Generates the current time with PHP date as parameter
	 *
	 *  <br><b>{% now 'Y.m.d.' %}</b>	 
	 *  1. <b>format</b> - Uses the format of PHP's {@link http://php.net/manual/en/function.date.php date function}.
	 **/
	public function tag_now($param_array, &$source){
		// figure out content
			if(count($param_array)>0) $contents = "<?php if(!{$param_array[0]->variable}) echo date('Y.m.d.'); else echo date({$param_array[0]->variable}); ?>";
			else $contents = "<?php echo date('Y.m.d.');  ?>";
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}


	/**
	 * Tag: regroup - Not supported by mozajik.
	 **/
	public function tag_regroup($param_array, &$source){
		// write to file
			$source->error('Regroup tag not yet supported by Mozajik!');
		// return true
			return true;
	}


	/**
	 * Tag: spaceless - Not supported by mozajik.
	 **/
	public function tag_spaceless($param_array, &$source){
		// write to file
			$source->error('Spaceless tag not supported by Mozajik!');
		// return true
			return true;
	}


	/**
	 * Tag: ssi - Not supported by mozajik.
	 **/
	public function tag_ssi($param_array, &$source){
		// write to file
			$source->error('Ssi tag not supported by Mozajik!');
		// return true
			return true;
	}


	/**
	 * Tag: templatetag - Displays special template characters. You can also use {@link tag_literal()}.
	 *
	 *  <br><b>{% templatetag openblock %}</b>	 
	 *  1. <b>type</b> - Specifies the type of tag character to print. Possible values are <b>openblock, closeblock, openvariable, closevariable, openbrace, closebrace, opencomment, closecomment</b>
	 **/
	public function tag_templatetag($param_array, &$source){
		// write to file
			switch($param_array[0]->variable){
				case '$this->zajlib->variable->openblock':
				case "'openblock'":
																$this->zajlib->compile->write('{%');
																break;
				case '$this->zajlib->variable->closeblock':
				case "'closeblock'":
																$this->zajlib->compile->write('%}');
																break;
				case '$this->zajlib->variable->openvariable':
				case "'openvariable'":
																$this->zajlib->compile->write('{{');
																break;
				case '$this->zajlib->variable->closevariable':
				case "'closevariable'":
																$this->zajlib->compile->write('}}');
																break;
				case '$this->zajlib->variable->openbrace':
				case "'openbrace'":
																$this->zajlib->compile->write('{');
																break;
				case '$this->zajlib->variable->closebrace':
				case "'closebrace'":
																$this->zajlib->compile->write('}');
																break;
				case '$this->zajlib->variable->opencomment':
				case "'opencomment'":
																$this->zajlib->compile->write('{#');
																break;
				case '$this->zajlib->variable->closecomment':
				case "'closecomment'":
																$this->zajlib->compile->write('#}');
																break;
			}
		
		// return true
			return true;
	}

	/**
	 * Tag: url - Not supported by mozajik. Use the {{baseurl}}path/to/controller/ format.
	 **/
	public function tag_url($param_array, &$source){
		// write to file
			$source->error('Not supported by Mozajik! Just use {{baseurl}}path/to/controller/.');
		// return true
			return true;
	}

	/**
	 * Tag: widthratio - Not supported by mozajik. Use CSS instead.
	 **/
	public function tag_widthratio($param_array, &$source){
		// write to file
			$source->error('Widthratio tag not supported by Mozajik! Please use CSS to replicate this.');
		// return true
			return true;
	}

	/**
	 * Tag: with - Caches a complex variable under a simpler name.
	 *
	 *  <br><b>{% with business.employees.count as total %} total is {{total}} {% endwith %}</b>	 
	 *  1. <b>complex</b> - The complex variable to catch.
	 *  2. <b>simplename</b> - The local variable name to use within the nested tag area.
	 **/
	public function tag_with($param_array, &$source){
		// add level
			$source->add_level('with', $param_array[2]->variable);
		// generate with
		// TODO: add save the previous value of param_array[2] and restore on end
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
	 * @ignore
	 **/
	public function tag_endwith($param_array, &$source){
		// get the data
			$localvar = $source->remove_level('with');
		// generate code
			$contents = <<<EOF
<?php
// end with
	unset($localvar);
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}
		
	/**
	 * Tag: block - Creates a content block.
	 *
	 *  <br><b>{% block 'name_of_block' %}Content of block.{% endblock %}</b>	 
	 *  1. <b>block name</b> - A unique name used to identify this block. Blocks with the same names will override each other according to the rules of {@link http://docs.djangoproject.com/en/1.2/topics/templates/#template-inheritance Template inheritance}
	 * @todo Either merge or somehow optimize $permanent_name and $file_name
	 **/
	private $block_name = "";
	public function tag_block($param_array, &$source){
		// Note: the block parameter is special because even if it is a variable it is not
		//		treated as such. So {% block content %} is same as {% block 'content' %}.
		
		// prepare unparsed parameter string
			$block_name = strtolower(trim($param_array[0]->vartext, "'\" "));
		// validate block name (only a-z) (because the whole stucture is involved, this is a fatal error!)
			if(preg_match('/[a-z]{2,25}/',$block_name) <= 0) $source->error("invalid block name given!");

		// generate file name for permanent block store
			$permanent_name = '__block/'.$source->get_requested_path().'-'.$block_name.'.html';
		// generate file name from block and session id
			$file_name = "block/".$this->zajlib->compile->get_session_id()."-$block_name.html";
		
		// check to see if destinations already paused and hierarchy level is more than one
			$current_level = $source->get_level();
			if($this->zajlib->compile->are_destinations_paused() && $current_level > 0){
				return $source->add_level('block', array($block_name,$file_name,false));
			}
		
		// start writing this block to a file
			$file_exists = $this->zajlib->compile->add_destination($file_name, true);			
			$unpause_dest = false;
		// start writing permanent block file
			$this->zajlib->compile->add_destination($permanent_name);
		// if file already exists && main destination not paused (not extended)! so, insert file here to main destination!
			if($file_exists){ // && !$this->zajlib->compile->is_main_dest_paused()){
				// insert the file
					$this->zajlib->compile->insert_file($file_name.".php");
				// now pause main destination
					$this->zajlib->compile->pause_destinations();
					$unpause_dest = true;
			}

		// add the level with block parent as last param
			$source->add_level('block', array($block_name,$file_name,$unpause_dest,$permanent_name,$this->block_name));
		// set as current global block (overwriting parent)
			$this->block_name = $block_name;

		// return true
			return true;
	}
	/**
	 * See block.
	 * 
	 * {@link tag_block()}
	 **/
	public function tag_endblock($param_array, &$source){
		// Note: unlike previous versions, the parameter is simply ignored. It ends the 
		//		last opened block. If none open, then fatal error is issued.

		// remove level
			list($block_name, $file_name, $unpause_dest, $permanent_name, $parent_block) = $source->remove_level('block');
		// if this is a new block (file was written and needs to be removed)
			if($file_name) $this->zajlib->compile->remove_destination($file_name);
		// remove permanent block file (if exists)
			if($permanent_name) $this->zajlib->compile->remove_destination($permanent_name);
		// unpause the destination?
			//print "Resuming $block_name at $pause_level/$current_level\n";
			if($unpause_dest) $this->zajlib->compile->resume_destinations();
		// repause the main destination if current source is extended
			if($source->extended) $this->zajlib->compile->main_dest_paused(true);
		// reset current block to parent block
			$this->block_name = $parent_block;
		// return true
			return true;
	}

	/**
	 * Tag: parentblock - Inserts the block from the parent template (specified by extends tag) here. This can be used when you do not want to override the block but instead add to it.
	 *
	 *  <b>{% parentblock %}</b>
	 **/
	public function tag_parentblock($param_array, &$source){
		// check if valid
			if(empty($this->extended_path)) $source->error("No extends tag found. You cannot use parentblock unless this template extends another.");
		// set source to be extended and set actual file path
			$this->zajlib->compile->write("<?php  \$this->zajlib->template->block({$this->extended_path}, '{$this->block_name}', true); ?>");
		// return true
			return true;
	}
	
	/**
	 * Tag: extends - Extends a parent template. You can also use this programatically 
	 *
	 *  <b>{% extends '/my/template/path' %}</b>
	 *  1. <b>template_path</b> - The path to the parent template.
	 **/
	private $extended_path = "";
	public function tag_extends($param_array, &$source){
		// check if valid
			if(count($param_array) > 1) $source->error("Invalid extends parameter: must be a valid variable or string!");
		// save current extended path to var
			$this->extended_path = strtolower(trim($param_array[0]->vartext, " "));
		// prepare unparsed parameter string
			$source_path = trim($this->extended_path, "'\"");
		// is not in first line?
			if($source->line_number != 1) $source->error("Extends must be on first line before any other content!");
		// is the user jailed?
			if(strpos($source_path, '..') !== false) $source->error("Invalid extends path ($source_path) found during compilation! Path must be give relative to the 'view' folder.");
				
		// check if it exists
			if(!zajCompileSource::file_exists($source_path)) $source->error("Invalid extends path ($source_path) found during compilation! File does not exist.");
		
		// set source to be extended and set actual file path
			$source->extended = true;
		
		// now pause main destination
			$this->zajlib->compile->main_dest_paused(true);

		// add me to the compile queue
			$this->zajlib->compile->add_source($source_path);
		// return true
			return true;
	}

	/**
	 * These are special functions which return the current extend file path and block name in use. THESE ARE NOT TAGS!
	 **/
	public function tag_get_extend(){ return trim($this->extended_path, "'\""); }
	public function tag_get_block(){ return $this->block_name; }
}


?>