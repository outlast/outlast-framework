<?php
/**
 * The base filter collection includes filters which are also used by the Django template system.
 * @package Template
 * @subpackage Filters
 **/

////////////////////////////////////////////////////////////////////////////////////////////////
// The methods below will take the following parameters and generate the appropriate php code
//	using the write method.
//		- parameter - the parsed parameter variable/string
//		- source - the source file object
//		- counter (optional) - a 1-based counter specifying which filter is currently running (1st, 2nd, or 3rd, etcetc.)
////////////////////////////////////////////////////////////////////////////////////////////////

class zajlib_filter_base extends zajElementCollection{	
	
	/**
	 * Filter: add - Adds the specified amount to the variable (one by default)
	 *
	 *  <b>{{variable|add:'2'}}</b> = 3 (assuming variable is 1)
	 *  1. The amount to add to the variable.
	 **/
	public function filter_add($parameter, &$source){
		// validate parameter
			$parameter = (trim($parameter,"'\""));
			if(substr($parameter, 0, 1) != '$' && !is_numeric($parameter)) return $source->warning('add filter parameter not a variable or an integer!');
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var+'.$parameter.';');
		return true;
	}
	/**
	 * Filter: addslashes - Escape quotes with slashes.
	 *
	 *  <b>{{'"this is quote"|addslashes}}</b> = \"This is a quote\"
	 **/
	public function filter_addslashes($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=addslashes($filter_var);');
		return true;
	}
	/**
	 * Filter: capfirst - Capitalizes the first character of the value.
	 *
	 *  <b>{{'example sentence'|capfirst}}</b> = 'Example sentence'
	 **/
	public function filter_capfirst($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=ucfirst($filter_var);');
		return true;
	}
	/**
	 * Unsupported. Use css instead.
	 **/
	public function filter_center($parameter, &$source){
			$source->warning('Styling filters are unsupported. Please use CSS!');
		return true;
	}
	/**
	 * Filter: cut - Remove the specified character or string from the string.
	 *
	 *  <b>{{'example sentence'|cut:' '}}</b> = 'examplesentence'
	 *  1. <b>cut this</b> - The character or string to cut from the variable.
	 **/
	public function filter_cut($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=str_ireplace('.$parameter.', "", $filter_var);');
		return true;
	}
	/**
	 * Filter: date - Format according to the 
	 *
	 *  <b>{{user.data.time_create|date:'Y.m.d.'}}</b> = 2010.03.23.
	 *  1. <b>format</b> - Uses the format of PHP's {@link http://php.net/manual/en/function.date.php date function}.
	 **/
	public function filter_date($parameter, &$source){
		// default parameter
			if(empty($parameter)) $parameter = "'Y.m.d.'";
		// write to file
			$this->zajlib->compile->write('if(is_numeric($filter_var)) $filter_var=date('.$parameter.', $filter_var); else $filter_var=false;');
		return true;
	}
	/**
	 * Filter: default - If the variable evaluates to false, the paramter will be displayed.
	 *
	 *  <b>{{variable|default:'-none-'}}</b> = -none- if variable evaluates to false.
	 *  1. <b>display this</b> - Displays this string if variable is false.
	 **/
	public function filter_default($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('if(!$filter_var) $filter_var = '.$parameter.';');
		return true;
	}
	/**
	 * Filter: default_if_none - Same as default
	 *
	 *  See {@link filter_default()}
	 **/
	public function filter_default_if_none($parameter, &$source){
		// same as default (in php)
			$this->filter_default($parameter, $source);
		return true;
	}
	/**
	 * Filter: dictsort - Sorts a fetcher object according to the parameter in ASC order.
	 *
	 *  <b>{{users|dictsort:'name'}}</b> = A list of users ordered by the field 'name'.
	 *  1. <b>field</b> - The name of the field by which to sort the objects.
	 **/
	public function filter_dictsort($parameter, &$source){
		// param required!
			if(!$parameter) return $source->warning('dictsort filter parameter required!');
		// write to file
			$this->zajlib->compile->write('if(is_object($filter_var) && is_a($filter_var, "zajFetcher")) $filter_var->sort('.$parameter.', "ASC");');
		return true;
	}
	/**
	 * Filter: dictsortreversed - Sorts a fetcher object according to the parameter in DESC order.
	 *
	 *  <b>{{users|dictsortreversed:'name'}}</b> = A list of users ordered by the field 'name'.
	 *  1. <b>field</b> - The name of the field by which to sort the objects.
	 **/
	public function filter_dictsortreversed($parameter, &$source){
		// param required!
			if(!$parameter) return $source->warning('dictsortreversed filter parameter required!');
		// write to file
			$this->zajlib->compile->write('if(is_object($filter_var) && is_a($filter_var, "zajFetcher")) $filter_var->sort('.$parameter.', "DESC");');
		return true;
	}

	/**
	 * Filter: divisibleby - If variable is divisible by parameter, then returns true.
	 *
	 *  <b>{% if variable|divisibleby:'2' %}</b> = Will be true if variable is even.
	 *  1. <b>value</b> - The value to be divisible by.
	 **/
	public function filter_divisibleby($parameter, &$source){
		// param required!
			if(!$parameter) return $source->warning('divisibleby filter parameter required!');
		// write to file
			$this->zajlib->compile->write('if($filter_var%'.$parameter.'==0) $filter_var = true; else  $filter_var = false;');
		return true;
	}

	/**
	 * Filter: escape - Convert strings in various ways to escape certain evil characters.
	 *
	 *  <b>{{variable|escape:'url'}}</b> = Variable will be url-encoded string.
	 *  1. <b>type</b> - The type of encoding to peform. Values can be: <b>html (default), htmlentities, htmlall, url, quotes, javascript, js, mail, htmlspecialchars, htmlquotes</b>
	 * 	
	 * 	htmlentities, htmlall: convert all applicable characters to HTML codes.
	 *  decode: convert all html entities to characters. this is html_entity_decode().
	 *  url: encode url entities (such as ? or &)
	 *  quotes, javascript, js: escape new lines and quotes with \
	 *  mail: convert the string to a user[at]domain[dot]com for some minimal spam protection
	 *  htmlquotes: convert ' and " to their HTML equivalents
	 *  htmlspecialchars, html (default): convert &, ', ", <, and > to their HTML equivalents
	 **/
	public function filter_escape($parameter, &$source){
		// TODO: fix javascript to be based on django docs
		if(empty($parameter)) $parameter = 'html';
		
			$contents = <<<EOF
switch($parameter){
	case 'htmlentities':
	case 'htmlall': \$filter_var = htmlentities(\$filter_var, ENT_QUOTES, 'UTF-8', false);
					break;
	case 'decode':	\$filter_var = html_entity_decode(\$filter_var);
					break;
	case 'url':		\$filter_var = urlencode(\$filter_var);
					break;
	case 'quotes': 
	case 'javascript':
	case 'js':
					\$filter_var = str_replace('"','\"',\$filter_var);
					\$filter_var = str_replace("'","\\'",\$filter_var);
					\$filter_var = str_replace("\\n"," ",\$filter_var);
					\$filter_var = str_replace("\\r","",\$filter_var);
					break;
					
	case 'mail': 	\$filter_var = str_replace('@',' [at] ',\$filter_var);
				 	\$filter_var = str_replace('.',' [dot] ',\$filter_var);
					break;

	case 'htmlquotes': 	\$filter_var = str_replace('"','&quot;',\$filter_var);
				 		\$filter_var = str_replace("'",'&#039;',\$filter_var);
					break;


	case 'urlpathinfo': 
	case 'hex': 
	case 'hexentity':
					\$filter_var = 'This filter not yet supported.';
					break;
	case 'htmlspecialchars':
	case 'html':
	default: 		\$filter_var = htmlspecialchars(\$filter_var, ENT_QUOTES, 'UTF-8');
					break;
}
EOF;
		// write to file
			$this->zajlib->compile->write($contents);	
		return true;
	}

	/**
	 * Filter: escapejs - Escapes characters for use in JavaScript strings. This does not make the string safe for use in HTML, but does protect you from syntax errors when using templates to generate JavaScript/JSON.
	 *
	 *  <b>{{variable|escapjs}}</b> = The same as using |escape:'javascript'.
	 **/
	public function filter_escapejs($parameter, &$source){
		// write to file
			$this->filter_escape('javascript', $source);
		return true;
	}

	/**
	 * Filter: filesizeformat - Format the value like a 'human-readable' file size (i.e. '13 KB', '4.1 MB', '102 bytes', etc).
	 *
	 *  <b>{{ value|filesizeformat }}</b> If value is 123456789, the output would be 117.7 MB.
	 **/
	public function filter_filesizeformat($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$this->zajlib->load->library("file"); $filter_var = $this->zajlib->file->file_size_format($filter_var);');
		return true;
	}

	/**
	 * Filter: first - Returns the first item in a list. Supports arrays or fetcher objects.
	 *
	 *  <b>{{ value|first }}</b> If value is the list ['a', 'b', 'c'], the output will be 'a'.
	 **/
	public function filter_first($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=reset($filter_var);');
		return true;
	}

	/**
	 * Filter: fix_ampersands - Replaces ampersands with &amp; entities.
	 *
	 *  <b>{{ value|fix_ampersands }}</b> If value is Tom & Jerry, the output will be Tom &amp; Jerry.
	 * @todo Check if already escaped!
	 **/
	public function filter_fix_ampersands($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var = str_replace("&","&amp;",$filter_var);');			
		return true;
	}

	/**
	 * Filter: floatformat - Round to the specified number of decimal places, two by default.
	 *
	 *  <b>{{ value|floatformat }}</b> If value is 2.322344, it will round to 2.32
	 **/
	public function filter_floatformat($parameter, &$source){
		// Round to two decimal places by default
			if(!$parameter) $parameter = 2;
		// write to file
			$this->zajlib->compile->write('$filter_var=number_format($filter_var, '.$parameter.');');
		return true;
	}

	/**
	 * Filter: force_escape - Here for full compatibility with Django. Exactly the same as escape.
	 **/
	public function filter_force_escape($parameter, &$source){
		return $this->filter_escape($parameter, $source);
	}


	/**
	 * Filter: get_digit - Given a whole number, returns the requested digit, where 1 is the right-most digit, 2 is the second-right-most digit, etc. Returns the original value for invalid input (if input or argument is not an integer, or if argument is less than 1). Otherwise, output is always an integer.
	 *
	 *  <b>{{ value|get_digit:2 }}</b> If value is 123456789, the output will be 8.
	 **/

	public function filter_get_digit($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}

	/**
	 * Filter: iriencode - Same as escaping with url.
	 *
	 *  <b>{{ value|iriencode }}</b> Url-escaped version.
	 **/
	public function filter_iriencode($parameter, &$source){
		// write to file
			$this->filter_escape('url', $source);
		return true;
	}

	/**
	 * Filter: join - Joins a list with a string, like PHP's implode.
	 *
	 *  <b>{{ value|join:' // ' }}</b> If value is the list ['a', 'b', 'c'], the output will be the string "a // b // c".
	 **/
	public function filter_join($parameter, &$source){
		// Parameter defaults to ','
			if(!$parameter) $parameter = '","';
		// write to file
			$this->zajlib->compile->write('if(is_array($filter_var) || is_a($filter_var, "zajFetcher")) $filter_var = implode('.$parameter.', $filter_var);');
		return true;
	}

	/**
	 * Filter: last - Returns the last item in a list. Supports arrays or fetcher objects.
	 *
	 *  <b>{{ value|last }}</b> If value is the list ['a', 'b', 'c'], the output will be 'c'.
	 **/
	public function filter_last($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=end($filter_var);');
		return true;
	}
	

	/**
	 * Filter: length - Returns the length of a string, an array, or a fetcher object. For fetchers, it returns the count, which is LIMITed.
	 *
	 *  <b>{{ value|length }}</b> Returns the length of value.
	 **/
	public function filter_length($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('if(is_object($filter_var) && is_a($filter_var, "zajFetcher")) $filter_var = $filter_var->count; elseif(is_array($filter_var)) count($filter_var); else $filter_var = strlen($filter_var);');
		return true;
	}

	/**
	 * Filter: length_is - Returns True if the value's length is the argument, or False otherwise.
	 *
	 *  <b>{{ value|length_is:'4' }}</b> If value is ['a', 'b', 'c', 'd'], the output will be True.
	 **/
	public function filter_length_is($parameter,&$source){
		// write to file
			$this->filter_length($parameter, $source);
			$this->zajlib->compile->write('if($filter_var=='.$parameter.') $filter_var=true; else $filter_var=false;');
		return true;
	}

	/**
	 * Filter: linebreaks - Replaces line breaks in plain text with appropriate HTML; a single newline becomes an HTML line break (br) and a new line followed by a blank line becomes a paragraph break (p).
	 *
	 *  <b>{{ value|linebreaks }}</b> If value is Joel\nis a slug, the output will be <p>Joel<br />is a slug</p>.
	 **/
	public function filter_linebreaks($parameter, &$source){
		// write to file
			// TODO: fix to conform to django's specs
			$this->zajlib->compile->write('$filter_var=str_ireplace("\n", "<br />", $filter_var);');
		return true;
	}

	/**
	 * Filter: linebreaksbr - Converts all newlines in a piece of plain text to HTML line breaks (br).
	 *
	 *  <b>{{ value|linebreaksbr }}</b> If value is Joel\nis a slug, the output will be Joel<br />is a slug.
	 **/
	public function filter_linebreaksbr($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=str_ireplace("\n", "<br />", $filter_var);');
		return true;
	}

	public function filter_linenumbers($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}

	/**
	 * Not implemented. Use CSS instead.
	 **/
	public function filter_ljust($parameter, &$source){
		// write to file
		return true;
	}

	/**
	 * Filter: lower - Converts a string into all lowercase.
	 *
	 *  <b>{{ value|lower }}</b> If value is Still MAD At Yoko, the output will be still mad at yoko.
	 **/
	public function filter_lower($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=mb_strtolower($filter_var);');
		return true;
	}

	public function filter_make_list($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_phone2numeric($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_pluralize($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_random($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_removetags($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}

	/**
	 * Not implemented. Use CSS instead.
	 **/
	public function filter_rjust($parameter, &$source){
		return true;
	}

	public function filter_safe($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_safeseq($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_slice($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_slugify($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_stringformat($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}

	/**
	 * Filter: striptags - Removes [X]HTML tags from the output except those specified by argument. Argument is space separated (Django-style) or accepts the {@link http://php.net/manual/en/function.strip-tags.php PHP format}.
	 *
	 *  <b>{{ value|striptags:'<b><a>' }}</b> Removes all tags from value except <b> and <a>.
	 **/
	public function filter_striptags($parameter, &$source){
		if(empty($parameter)) $parameter = "''";
		// parameter
		$content = <<<EOF
			\$filter_var = strip_tags(\$filter_var, $parameter);
EOF;
		// write to file
			$this->zajlib->compile->write($content);
		return true;
	}

	/**
	 * Filter: time - Formats a time according to the given format.
	 *
	 *  <b>{{ value|time:'H:i' }}</b> Formats value to hour and minute (03:32). Argument uses the format of PHP's {@link http://php.net/manual/en/function.date.php date function}.
	 **/
	public function filter_time($parameter, &$source){
		// default parameter
			if(empty($parameter)) $parameter = "'H:i'";
		// write to file
			$this->zajlib->compile->write('$filter_var=date('.$parameter.', $filter_var);');
		return true;
	}

	public function filter_timesince($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_timeuntil($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_naturaltime($parameter, &$source){
		// write to file
		$content = <<<EOF
			if(\$filter_var < 60) \$filter_var = \$filter_var.' seconds';
			else if(\$filter_var >= 60 && \$filter_var < 60*60) \$filter_var = round(\$filter_var/60).' minute(s)';
			else if(\$filter_var >= 60*60 && \$filter_var < 60*60*24) \$filter_var = round(\$filter_var/(60*60)).' hour(s)';
			else if(\$filter_var >= 60*60*24 && \$filter_var < 60*60*24*14) \$filter_var = round(\$filter_var/(60*60*24)).' day(s)';
			else if(\$filter_var >= 60*60*24*14 && \$filter_var < 60*60*24*30) \$filter_var = round(\$filter_var/(60*60*24*7)).' week(s)';
			else if(\$filter_var >= 60*60*24*30 && \$filter_var < 60*60*24*365) \$filter_var = round(\$filter_var/(60*60*24*30)).' month(s)';
			else if(\$filter_var >= 60*60*24*365) \$filter_var = round(\$filter_var/(60*60*24*30)).' year(s)';
EOF;
		// write to file
		$this->zajlib->compile->write($content);
		return true;
	}

	/**
	 * Filter: title - Converts a string into titlecase.
	 *
	 *  <b>{{'example sentence'|title}}</b> = 'Example Sentence'
	 **/
	public function filter_title($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=ucwords($filter_var);');
		return true;
	}
	
	public function filter_truncatewords($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_truncatewords_html($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_unordered_list($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}

	/**
	 * Filter: upper - Converts all characters to uppercase
	 *
	 *  <b>{{value|upper}}</b> = If value is "Joel is a slug", the output will be "JOEL IS A SLUG".
	 **/
	public function filter_upper($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=mb_strtoupper($filter_var);');	
		return true;
	}

	/**
	 * Filter: urlencode - Escapes a value for use in a URL.
	 *
	 *  <b>{{value|urlencode}}</b> If value is "http://www.example.org/foo?a=b&c=d", the output will be "http%3A//www.example.org/foo%3Fa%3Db%26c%3Dd".
	 **/
	public function filter_urlencode($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=urlencode($filter_var);');
		return true;
	}
	
	public function filter_urlize($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=$this->zajlib->text->urlize($filter_var);');
		return true;
	}
	public function filter_urlizetrunc($parameter, &$source){
		// default parameter
			if(empty($parameter)) $parameter = 15;
		// write to file
			$this->zajlib->compile->write('$filter_var=$this->zajlib->text->urlize($filter_var, '.$parameter.');');
			// TODO: add truncating!
		return true;
	}
	public function filter_wordcount($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_wordwrap($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	public function filter_yesno($parameter, &$source){
		// write to file
			// TODO: add
		return true;
	}
	


}


?>